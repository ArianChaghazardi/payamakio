<?php
/**
 * Lead Magnet & Free Product Coupon Module
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Module_Lead_Magnet {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_free_order_completed' ), 10, 1 );
    }

    public function on_free_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Only handle free 0-toman orders
        if ( (float) $order->get_total() > 0 ) {
            return;
        }

        $meta_key = '_payamakio_lead_magnet_sent';
        if ( $order->get_meta( $meta_key ) ) {
            return;
        }

        $settings = get_option( 'payamakio_settings', array() );
        if ( 'yes' !== ( $settings['lead_magnet_active'] ?? 'no' ) ) {
            return;
        }

        $pattern = trim( $settings['lead_magnet_pattern'] ?? '' );
        if ( empty( $pattern ) ) {
            return;
        }

        $mobile = $order->get_billing_phone();
        $mobile = Payamakio_Helper::normalize_mobile( $mobile );
        if ( empty( $mobile ) ) {
            return;
        }

        $coupon_code = trim( $settings['lead_magnet_coupon'] ?? 'VIP30' );
        $tags = Payamakio_Helper::get_order_tags( $order, array( 'coupon_code' => $coupon_code ) );

        $variables = array(
            'name'   => $tags['{first_name}'],
            'code'   => $coupon_code,
            'link'   => $tags['{download_link}'],
        );

        $gateway = payamakio_get_gateway();
        $result  = $gateway->send_pattern( $mobile, $pattern, $variables );

        Payamakio_Logger::log(
            $mobile,
            $gateway->get_id(),
            $pattern,
            $result['success'] ? 'success' : 'failed',
            $result['success'] ? sprintf( 'پیامک لید مگنت و هدیه با کد تخفیف %s ارسال شد.', $coupon_code ) : ( 'خطا در ارسال پیامک لید مگنت: ' . ( $result['error'] ?? 'نامشخص' ) ),
            'lead_magnet',
            $result['raw'] ?? ''
        );

        if ( $result['success'] ) {
            $order->update_meta_data( $meta_key, current_time( 'mysql' ) );
            $order->add_order_note( sprintf( 'پیامکیو: پیامک لید مگنت به همراه کد هدیه %s به شماره %s ارسال شد.', $coupon_code, $mobile ) );
            $order->save();
        }
    }
}
