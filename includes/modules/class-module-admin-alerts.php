<?php
/**
 * Admin Notification Alerts Module
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Module_Admin_Alerts {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 20, 2 );
    }

    public function on_new_order( $order_id, $order = null ) {
        if ( ! $order ) {
            $order = wc_get_order( $order_id );
        }
        if ( ! $order ) {
            return;
        }

        $settings = get_option( 'payamakio_settings', array() );
        if ( 'yes' !== ( $settings['admin_alert_active'] ?? 'no' ) ) {
            return;
        }

        $admin_mobile = trim( $settings['admin_alert_mobile'] ?? '' );
        $pattern      = trim( $settings['admin_alert_pattern'] ?? '' );

        if ( empty( $admin_mobile ) || empty( $pattern ) ) {
            return;
        }

        $admin_mobile = Payamakio_Helper::normalize_mobile( $admin_mobile );
        $tags = Payamakio_Helper::get_order_tags( $order );

        $variables = array(
            'order_id' => $tags['{order_id}'],
            'total'    => $tags['{order_total}'],
            'name'     => $tags['{full_name}'],
        );

        $gateway = payamakio_get_gateway();
        $result  = $gateway->send_pattern( $admin_mobile, $pattern, $variables );

        Payamakio_Logger::log(
            $admin_mobile,
            $gateway->get_id(),
            $pattern,
            $result['success'] ? 'success' : 'failed',
            $result['success'] ? sprintf( 'پیامک اطلاع رسانی سفارش جدید #%d به مدیر ارسال شد.', $order->get_id() ) : ( 'خطا در ارسال پیامک مدیر: ' . ( $result['error'] ?? 'نامشخص' ) ),
            'admin_alert',
            $result['raw'] ?? ''
        );
    }
}
