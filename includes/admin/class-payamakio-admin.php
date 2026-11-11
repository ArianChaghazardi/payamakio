<?php
/**
 * Admin Controller for Payamakio
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'save_settings' ) );

        // AJAX Handlers
        add_action( 'wp_ajax_payamakio_send_test', array( $this, 'ajax_send_test' ) );
        add_action( 'wp_ajax_payamakio_register_pattern', array( $this, 'ajax_register_pattern' ) );
        add_action( 'wp_ajax_payamakio_clear_logs', array( $this, 'ajax_clear_logs' ) );
    }

    public function register_menu() {
        add_menu_page(
            'پیامکیو | سامانه پیامک ووکامرس',
            'پیامکیو',
            'manage_woocommerce',
            'payamakio',
            array( $this, 'render_page' ),
            'dashicons-email-alt',
            56
        );
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_payamakio' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'payamakio-admin-css',
            PAYAMAKIO_URL . 'assets/css/payamakio-admin.css',
            array(),
            PAYAMAKIO_VERSION
        );

        wp_enqueue_script(
            'payamakio-admin-js',
            PAYAMAKIO_URL . 'assets/js/payamakio-admin.js',
            array( 'jquery' ),
            PAYAMAKIO_VERSION,
            true
        );

        wp_localize_script(
            'payamakio-admin-js',
            'payamakio_vars',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'payamakio_admin_nonce' ),
            )
        );
    }

    public function render_page() {
        $settings = get_option( 'payamakio_settings', array() );
        $stats    = Payamakio_Logger::get_stats();
        $logs     = Payamakio_Logger::get_logs( 50 );
        $gateway  = payamakio_get_gateway();
        $balance  = method_exists( $gateway, 'get_balance' ) ? $gateway->get_balance() : false;
        require_once PAYAMAKIO_PATH . 'includes/admin/views/settings-page.php';
    }

    public function save_settings() {
        if ( ! isset( $_POST['payamakio_save_settings'] ) ) {
            return;
        }

        if ( ! check_admin_referer( 'payamakio_settings_verify', 'payamakio_nonce' ) ) {
            wp_die( 'خطای امنیتی. لطفا دوباره تلاش کنید.' );
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'شما اجازه دسترسی به این بخش را ندارید.' );
        }

        $raw = $_POST['payamakio'] ?? array();
        $clean = array(
            'gateway'                  => sanitize_text_field( $raw['gateway'] ?? 'ippanel' ),
            'ippanel_apikey'           => sanitize_text_field( $raw['ippanel_apikey'] ?? '' ),
            'ippanel_sender'           => sanitize_text_field( $raw['ippanel_sender'] ?? '+983000505' ),
            'kavenegar_apikey'         => sanitize_text_field( $raw['kavenegar_apikey'] ?? '' ),
            'kavenegar_sender'         => sanitize_text_field( $raw['kavenegar_sender'] ?? '' ),
            'melipayamak_username'     => sanitize_text_field( $raw['melipayamak_username'] ?? '' ),
            'melipayamak_password'     => sanitize_text_field( $raw['melipayamak_password'] ?? '' ),
            'melipayamak_sender'       => sanitize_text_field( $raw['melipayamak_sender'] ?? '' ),

            'order_completed_active'   => isset( $raw['order_completed_active'] ) ? 'yes' : 'no',
            'order_completed_pattern'  => sanitize_text_field( $raw['order_completed_pattern'] ?? '' ),
            'order_completed_param'    => sanitize_text_field( $raw['order_completed_param'] ?? 'name,order_id,amount' ),

            'order_processing_active'  => isset( $raw['order_processing_active'] ) ? 'yes' : 'no',
            'order_processing_pattern' => sanitize_text_field( $raw['order_processing_pattern'] ?? '' ),

            'cart_recovery_active'     => isset( $raw['cart_recovery_active'] ) ? 'yes' : 'no',
            'cart_recovery_delay'      => absint( $raw['cart_recovery_delay'] ?? 30 ),
            'cart_recovery_pattern'    => sanitize_text_field( $raw['cart_recovery_pattern'] ?? '' ),

            'lead_magnet_active'       => isset( $raw['lead_magnet_active'] ) ? 'yes' : 'no',
            'lead_magnet_pattern'      => sanitize_text_field( $raw['lead_magnet_pattern'] ?? '' ),
            'lead_magnet_coupon'       => sanitize_text_field( $raw['lead_magnet_coupon'] ?? 'VIP30' ),

            'admin_alert_active'       => isset( $raw['admin_alert_active'] ) ? 'yes' : 'no',
            'admin_alert_mobile'       => sanitize_text_field( $raw['admin_alert_mobile'] ?? '' ),
            'admin_alert_pattern'      => sanitize_text_field( $raw['admin_alert_pattern'] ?? '' ),
        );

        update_option( 'payamakio_settings', $clean );

        wp_safe_redirect( add_query_arg( array( 'page' => 'payamakio', 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function ajax_send_test() {
        check_ajax_referer( 'payamakio_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'عدم دسترسی کافی.' ) );
        }

        $mobile  = sanitize_text_field( $_POST['mobile'] ?? '' );
        $pattern = sanitize_text_field( $_POST['pattern'] ?? '' );
        $param1  = sanitize_text_field( $_POST['param1'] ?? 'کاربر تستی' );
        $param2  = sanitize_text_field( $_POST['param2'] ?? '12345' );

        if ( empty( $mobile ) ) {
            wp_send_json_error( array( 'message' => 'شماره موبایل گیرنده الزامی است.' ) );
        }

        $mobile  = Payamakio_Helper::normalize_mobile( $mobile );
        $gateway = payamakio_get_gateway();

        if ( ! empty( $pattern ) ) {
            $variables = array(
                'name'     => $param1,
                'code'     => $param2,
                'order_id' => $param2,
            );
            $result = $gateway->send_pattern( $mobile, $pattern, $variables );
            $event_type = 'test_pattern';
        } else {
            $message = "پیامک تستی پیامکیو\nسلام " . $param1 . " عزیز، ارتباط سامانه با درگاه پیامکی با موفقیت برقرار شد.\nکد رهگیری: " . $param2;
            $result = $gateway->send_sms( $mobile, $message );
            $event_type = 'test_webservice';
        }

        // Log the test attempt
        Payamakio_Logger::log(
            $mobile,
            $gateway->get_id(),
            $pattern,
            $result['success'] ? 'success' : 'failed',
            $result['success'] ? ( 'تست ارسال موفق. کد رهگیری: ' . ( $result['message_id'] ?? 'OK' ) ) : ( 'خطای تست: ' . ( $result['error'] ?? 'نامشخص' ) ),
            $event_type,
            $result['raw'] ?? ''
        );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'message'  => ! empty( $pattern ) ? 'پیامک الگوی آزمایشی با موفقیت ارسال شد.' : 'پیامک وب سرویس آزمایشی با موفقیت به شماره گیرنده ارسال شد.',
                'tracking' => $result['message_id'] ?? 'OK',
            ) );
        } else {
            wp_send_json_error( array(
                'message' => 'ارسال پیامک ناموفق بود: ' . ( $result['error'] ?? 'خطای ناشناخته در درگاه' ),
            ) );
        }
    }

    public function ajax_register_pattern() {
        check_ajax_referer( 'payamakio_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'عدم دسترسی کافی.' ) );
        }

        $pattern_key = sanitize_text_field( $_POST['pattern_key'] ?? '' );
        $custom_text = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        $defaults = Payamakio_Helper::get_default_patterns();
        if ( ! isset( $defaults[ $pattern_key ] ) ) {
            wp_send_json_error( array( 'message' => 'نوع الگوی درخواستی نامعتبر است.' ) );
        }

        $pattern_data = $defaults[ $pattern_key ];
        $message = ! empty( $custom_text ) ? $custom_text : $pattern_data['message'];

        $gateway = payamakio_get_gateway();
        if ( ! method_exists( $gateway, 'create_pattern' ) ) {
            wp_send_json_error( array( 'message' => 'درگاه فعال قابلیت ثبت خودکار الگو را پشتیبانی نمی کند.' ) );
        }

        $result = $gateway->create_pattern(
            $pattern_data['title'],
            $pattern_data['description'],
            $message,
            $pattern_data['variables'],
            home_url()
        );

        if ( $result['success'] ) {
            // Also auto-save the pattern code into options!
            $settings = get_option( 'payamakio_settings', array() );
            $opt_key = $pattern_key . '_pattern';
            $settings[ $opt_key ] = $result['pattern_code'];
            update_option( 'payamakio_settings', $settings );

            wp_send_json_success( array(
                'message'      => 'الگو با موفقیت در سامانه آی پی پنل ثبت شد و در صف تایید اپراتور قرار گرفت.',
                'pattern_code' => $result['pattern_code'],
                'status'       => $result['status'],
            ) );
        } else {
            wp_send_json_error( array(
                'message' => 'خطا در ثبت الگو: ' . ( $result['error'] ?? 'خطای ناشناخته' ),
            ) );
        }
    }

    public function ajax_clear_logs() {
        check_ajax_referer( 'payamakio_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'عدم دسترسی.' ) );
        }

        global $wpdb;
        $table = Payamakio_Logger::get_table_name();
        $wpdb->query( "TRUNCATE TABLE $table" );

        wp_send_json_success( array( 'message' => 'تمام تاریخچه لاگ ها پاکسازی شدند.' ) );
    }
}
