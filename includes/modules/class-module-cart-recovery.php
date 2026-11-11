<?php
/**
 * Smart Abandoned Cart Recovery Module
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Module_Cart_Recovery {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'payamakio_check_abandoned_carts_event', array( $this, 'process_abandoned_carts' ) );
    }

    public static function schedule_recovery_cron() {
        if ( ! wp_next_scheduled( 'payamakio_check_abandoned_carts_event' ) ) {
            wp_schedule_event( time(), 'hourly', 'payamakio_check_abandoned_carts_event' );
        }
    }

    public static function clear_recovery_cron() {
        wp_clear_scheduled_hook( 'payamakio_check_abandoned_carts_event' );
    }

    public function process_abandoned_carts() {
        $settings = get_option( 'payamakio_settings', array() );
        if ( 'yes' !== ( $settings['cart_recovery_active'] ?? 'no' ) ) {
            return;
        }

        $pattern = trim( $settings['cart_recovery_pattern'] ?? '' );
        if ( empty( $pattern ) ) {
            return;
        }

        $delay_minutes = absint( $settings['cart_recovery_delay'] ?? 30 );
        if ( $delay_minutes < 5 ) {
            $delay_minutes = 5;
        }

        $cutoff_time = gmdate( 'Y-m-d H:i:s', time() - ( $delay_minutes * MINUTE_IN_SECONDS ) );
        $max_age     = gmdate( 'Y-m-d H:i:s', time() - ( 48 * HOUR_IN_SECONDS ) ); // Do not recover orders older than 48h

        // Query pending / failed orders
        $orders = wc_get_orders( array(
            'status'        => array( 'pending', 'failed' ),
            'date_created'  => $max_age . '...' . $cutoff_time,
            'limit'         => 15,
            'meta_query'    => array(
                array(
                    'key'     => '_payamakio_recovery_sent',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ) );

        if ( empty( $orders ) ) {
            return;
        }

        $gateway = payamakio_get_gateway();

        foreach ( $orders as $order ) {
            $mobile = $order->get_billing_phone();
            $mobile = Payamakio_Helper::normalize_mobile( $mobile );
            if ( empty( $mobile ) ) {
                continue;
            }

            $tags = Payamakio_Helper::get_order_tags( $order );

            // Send recovery pattern
            $variables = array(
                'name'  => $tags['{first_name}'],
                'link'  => $tags['{payment_link}'],
                'total' => $tags['{order_total}'],
            );

            $result = $gateway->send_pattern( $mobile, $pattern, $variables );

            Payamakio_Logger::log(
                $mobile,
                $gateway->get_id(),
                $pattern,
                $result['success'] ? 'success' : 'failed',
                $result['success'] ? sprintf( 'پیامک بازیابی سبد خرید سفارش #%d با موفقیت ارسال شد.', $order->get_id() ) : ( 'خطا در ارسال بازیابی سبد: ' . ( $result['error'] ?? 'نامشخص' ) ),
                'cart_recovery',
                $result['raw'] ?? ''
            );

            if ( $result['success'] ) {
                $order->update_meta_data( '_payamakio_recovery_sent', current_time( 'mysql' ) );
                $order->add_order_note( sprintf( 'پیامکیو: پیامک یادآوری و بازیابی سبد خرید به شماره %s ارسال شد.', $mobile ) );
                $order->save();
            }
        }
    }
}
