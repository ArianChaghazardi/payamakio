<?php
/**
 * Order Status SMS Module
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Module_Order_SMS {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Order status hooks
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 20, 1 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_processing' ), 20, 1 );
    }

    public function on_order_completed( $order_id ) {
        $this->handle_status_notification( $order_id, 'completed' );
    }

    public function on_order_processing( $order_id ) {
        $this->handle_status_notification( $order_id, 'processing' );
    }

    private function handle_status_notification( $order_id, $status ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Avoid duplicate sends
        $meta_key = '_payamakio_sent_' . $status;
        if ( $order->get_meta( $meta_key ) ) {
            return;
        }

        $settings = get_option( 'payamakio_settings', array() );
        $is_active = ( 'yes' === ( $settings[ 'order_' . $status . '_active' ] ?? 'no' ) );
        $pattern   = trim( $settings[ 'order_' . $status . '_pattern' ] ?? '' );

        if ( ! $is_active || empty( $pattern ) ) {
            return;
        }

        // Skip 0-toman orders if lead magnet module is active for completed orders
        if ( 'completed' === $status && (float) $order->get_total() == 0 && ( 'yes' === ( $settings['lead_magnet_active'] ?? 'no' ) ) ) {
            // Handled by Lead Magnet module
            return;
        }

        $mobile = $order->get_billing_phone();
        $mobile = Payamakio_Helper::normalize_mobile( $mobile );
        if ( empty( $mobile ) ) {
            return;
        }

        // Prepare pattern variables based on configured parameters
        $param_keys_str = $settings[ 'order_' . $status . '_param' ] ?? 'name,order_id,amount';
        $param_keys = array_map( 'trim', explode( ',', $param_keys_str ) );

        $all_tags = Payamakio_Helper::get_order_tags( $order );
        $variables = array();

        // Map values to pattern variables
        foreach ( $param_keys as $idx => $key ) {
            if ( empty( $key ) ) continue;

            // Map common aliases
            if ( in_array( $key, array( 'name', 'first_name', 'customer_name' ), true ) ) {
                $variables[ $key ] = $all_tags['{first_name}'];
            } elseif ( in_array( $key, array( 'order_id', 'id', 'order' ), true ) ) {
                $variables[ $key ] = $all_tags['{order_id}'];
            } elseif ( in_array( $key, array( 'amount', 'total', 'price' ), true ) ) {
                $variables[ $key ] = $all_tags['{order_total}'];
            } elseif ( in_array( $key, array( 'download', 'link' ), true ) ) {
                $variables[ $key ] = $all_tags['{download_link}'];
            } else {
                $variables[ $key ] = $all_tags['{' . $key . '}'] ?? '';
            }
        }

        $gateway = payamakio_get_gateway();
        $result  = $gateway->send_pattern( $mobile, $pattern, $variables );

        // Log result
        Payamakio_Logger::log(
            $mobile,
            $gateway->get_id(),
            $pattern,
            $result['success'] ? 'success' : 'failed',
            $result['success'] ? ( 'پیامک وضعیت ' . $status . ' با موفقیت ارسال شد. کد پیگیری: ' . ( $result['message_id'] ?? '-' ) ) : ( 'خطا در ارسال: ' . ( $result['error'] ?? 'نامشخص' ) ),
            'order_' . $status,
            $result['raw'] ?? ''
        );

        if ( $result['success'] ) {
            $order->update_meta_data( $meta_key, current_time( 'mysql' ) );
            $order->add_order_note( sprintf( 'پیامکیو: پیامک وضعیت %s با موفقیت به شماره %s ارسال گردید.', $status, $mobile ) );
            $order->save();
        }
    }
}
