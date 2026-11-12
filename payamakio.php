<?php
/**
 * Plugin Name: پیامکیو | سامانه هوشمند پیامک و اتوماسیون ووکامرس
 * Plugin URI: https://hectortm.ir/payamakio
 * Description: افزونه جامع و مدرن اطلاع رسانی پیامکی، بازیابی هوشمند سبد خرید رها شده و ارسال فوق سریع پترن ووکامرس با پنل مدیریت پیشرفته.
 * Version: 1.0.2
 * Author: آرین چقازردی
 * Author URI: https://arianchaghazardi.ir
 * Developer: هکتورتیم
 * Developer URI: https://hectortm.ir
 * Text Domain: payamakio
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Constants
define( 'PAYAMAKIO_VERSION', '1.0.2' );
define( 'PAYAMAKIO_FILE', __FILE__ );
define( 'PAYAMAKIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'PAYAMAKIO_URL', plugin_dir_url( __FILE__ ) );
define( 'PAYAMAKIO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare WooCommerce HPOS Compatibility
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Main Payamakio Core Class
 */
final class Payamakio {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        // Core interfaces and helpers
        require_once PAYAMAKIO_PATH . 'includes/interfaces/interface-payamakio-gateway.php';
        require_once PAYAMAKIO_PATH . 'includes/class-payamakio-helper.php';
        require_once PAYAMAKIO_PATH . 'includes/class-payamakio-logger.php';

        // Gateways
        require_once PAYAMAKIO_PATH . 'includes/gateways/class-gateway-ippanel.php';
        require_once PAYAMAKIO_PATH . 'includes/gateways/class-gateway-kavenegar.php';
        require_once PAYAMAKIO_PATH . 'includes/gateways/class-gateway-melipayamak.php';

        // Modules
        require_once PAYAMAKIO_PATH . 'includes/modules/class-module-order-sms.php';
        require_once PAYAMAKIO_PATH . 'includes/modules/class-module-cart-recovery.php';
        require_once PAYAMAKIO_PATH . 'includes/modules/class-module-lead-magnet.php';
        require_once PAYAMAKIO_PATH . 'includes/modules/class-module-admin-alerts.php';

        // Admin
        if ( is_admin() ) {
            require_once PAYAMAKIO_PATH . 'includes/admin/class-payamakio-admin.php';
        }
    }

    private function init_hooks() {
        register_activation_hook( PAYAMAKIO_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( PAYAMAKIO_FILE, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // Initialize Logger table
        Payamakio_Logger::check_table();

        // Boot Modules
        Payamakio_Module_Order_SMS::get_instance();
        Payamakio_Module_Cart_Recovery::get_instance();
        Payamakio_Module_Lead_Magnet::get_instance();
        Payamakio_Module_Admin_Alerts::get_instance();

        if ( is_admin() ) {
            Payamakio_Admin::get_instance();
        }
    }

    public function activate() {
        Payamakio_Logger::create_table();
        Payamakio_Module_Cart_Recovery::schedule_recovery_cron();

        // Set default options if not exists
        if ( false === get_option( 'payamakio_settings' ) ) {
            $defaults = array(
                'gateway'               => 'ippanel',
                'ippanel_apikey'        => '',
                'ippanel_sender'        => '+983000505',
                'kavenegar_apikey'      => '',
                'kavenegar_sender'      => '',
                'melipayamak_username'  => '',
                'melipayamak_password'  => '',
                'melipayamak_sender'    => '',
                'order_completed_active'=> 'yes',
                'order_completed_pattern'=> '',
                'order_completed_param' => 'name,order_id,amount',
                'order_processing_active'=> 'no',
                'order_processing_pattern'=> '',
                'cart_recovery_active'  => 'no',
                'cart_recovery_delay'   => 30, // minutes
                'cart_recovery_pattern' => '',
                'lead_magnet_active'    => 'yes',
                'lead_magnet_pattern'   => '',
                'lead_magnet_coupon'    => 'VIP30',
                'admin_alert_active'    => 'no',
                'admin_alert_mobile'    => '',
                'admin_alert_pattern'   => '',
            );
            update_option( 'payamakio_settings', $defaults );
        }
    }

    public function deactivate() {
        Payamakio_Module_Cart_Recovery::clear_recovery_cron();
    }
}

/**
 * Helper global accessor for gateway
 */
function payamakio_get_gateway( $gateway_name = null ) {
    $settings = get_option( 'payamakio_settings', array() );
    if ( is_null( $gateway_name ) ) {
        $gateway_name = $settings['gateway'] ?? 'ippanel';
    }

    switch ( $gateway_name ) {
        case 'kavenegar':
            return new Payamakio_Gateway_Kavenegar( $settings );
        case 'melipayamak':
            return new Payamakio_Gateway_MeliPayamak( $settings );
        case 'ippanel':
        default:
            return new Payamakio_Gateway_IPPanel( $settings );
    }
}

// Instantiate
Payamakio::get_instance();
