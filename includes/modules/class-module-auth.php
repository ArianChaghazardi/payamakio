<?php
/**
 * SMS OTP Login & Registration Module for Payamakio
 * Digits-style mobile authentication, WooCommerce & WP-Login integration, and modal management
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Module_Auth {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        // Shortcodes
        add_shortcode( 'payamakio_auth', array( $this, 'render_shortcode' ) );
        add_shortcode( 'payamakio_login', array( $this, 'render_shortcode' ) );

        // Modal popup in footer
        add_action( 'wp_footer', array( $this, 'render_modal_markup' ) );

        // Default wp-login.php redirection
        add_action( 'login_init', array( $this, 'maybe_redirect_wplogin' ) );

        // WooCommerce Integration
        add_action( 'woocommerce_before_customer_login_form', array( $this, 'maybe_override_woocommerce_login' ) );
        add_action( 'woocommerce_created_customer', array( $this, 'sync_wc_customer_mobile' ), 10, 3 );
        add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'sync_checkout_mobile' ), 10, 2 );

        // AJAX Endpoints
        add_action( 'wp_ajax_payamakio_auth_send_otp', array( $this, 'ajax_send_otp' ) );
        add_action( 'wp_ajax_nopriv_payamakio_auth_send_otp', array( $this, 'ajax_send_otp' ) );

        add_action( 'wp_ajax_payamakio_auth_verify_otp', array( $this, 'ajax_verify_otp' ) );
        add_action( 'wp_ajax_nopriv_payamakio_auth_verify_otp', array( $this, 'ajax_verify_otp' ) );

        add_action( 'wp_ajax_payamakio_auth_password_login', array( $this, 'ajax_password_login' ) );
        add_action( 'wp_ajax_nopriv_payamakio_auth_password_login', array( $this, 'ajax_password_login' ) );

        add_action( 'wp_ajax_payamakio_auth_reset_password', array( $this, 'ajax_reset_password' ) );
        add_action( 'wp_ajax_nopriv_payamakio_auth_reset_password', array( $this, 'ajax_reset_password' ) );

        add_action( 'wp_ajax_payamakio_auth_create_page', array( $this, 'ajax_create_page' ) );
    }

    /**
     * Check if the Auth Module is active in settings
     */
    public static function is_active() {
        $settings = get_option( 'payamakio_settings', array() );
        return ( ( $settings['auth_active'] ?? 'yes' ) === 'yes' );
    }

    /**
     * Enqueue CSS & JS assets on the frontend
     */
    public function enqueue_frontend_assets() {
        if ( ! self::is_active() ) {
            return;
        }

        wp_enqueue_style(
            'payamakio-auth-css',
            PAYAMAKIO_URL . 'assets/css/payamakio-auth.css',
            array(),
            PAYAMAKIO_VERSION
        );

        wp_enqueue_script(
            'payamakio-auth-js',
            PAYAMAKIO_URL . 'assets/js/payamakio-auth.js',
            array( 'jquery' ),
            PAYAMAKIO_VERSION,
            true
        );

        wp_localize_script(
            'payamakio-auth-js',
            'payamakioAuthVars',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'payamakio_auth_nonce' ),
            )
        );
    }

    /**
     * Render the shortcode [payamakio_auth]
     */
    public function render_shortcode( $atts = array() ) {
        $atts = shortcode_atts( array(
            'redirect'    => '',
            'default_tab' => '',
        ), $atts, 'payamakio_auth' );

        ob_start();
        self::render_auth_card( $atts );
        return ob_get_clean();
    }

    /**
     * Render Authentication Card Component
     */
    public static function render_auth_card( $args = array() ) {
        $template_file = PAYAMAKIO_PATH . 'templates/auth-card.php';
        if ( file_exists( $template_file ) ) {
            include $template_file;
        }
    }

    /**
     * Render Modal Markup in Footer
     */
    public function render_modal_markup() {
        if ( ! self::is_active() || is_user_logged_in() ) {
            return;
        }

        $template_file = PAYAMAKIO_PATH . 'templates/auth-modal.php';
        if ( file_exists( $template_file ) ) {
            include $template_file;
        }
    }

    /**
     * Redirect default WordPress login (wp-login.php) to custom auth page if enabled
     */
    public function maybe_redirect_wplogin() {
        if ( ! self::is_active() ) {
            return;
        }

        $settings = get_option( 'payamakio_settings', array() );
        if ( ( $settings['auth_redirect_wplogin'] ?? 'no' ) !== 'yes' ) {
            return;
        }

        // Allow logout, postpass, register action bypasses
        $action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login';
        if ( in_array( $action, array( 'logout', 'postpass', 'rp', 'resetpass' ), true ) ) {
            return;
        }

        // Check if dedicated page exists
        $page_id = absint( $settings['auth_page_id'] ?? 0 );
        if ( $page_id > 0 && get_post_status( $page_id ) === 'publish' ) {
            $target_url = get_permalink( $page_id );
        } elseif ( function_exists( 'wc_get_page_permalink' ) ) {
            $target_url = wc_get_page_permalink( 'myaccount' );
        } else {
            return;
        }

        if ( ! empty( $_REQUEST['redirect_to'] ) ) {
            $target_url = add_query_arg( 'redirect_to', urlencode( esc_url_raw( $_REQUEST['redirect_to'] ) ), $target_url );
        }

        wp_safe_redirect( $target_url );
        exit;
    }

    /**
     * Override or enhance WooCommerce My Account login form
     */
    public function maybe_override_woocommerce_login() {
        if ( ! self::is_active() ) {
            return;
        }

        $settings = get_option( 'payamakio_settings', array() );
        $override = $settings['auth_override_woo'] ?? ( $settings['auth_override_wc'] ?? 'yes' );
        if ( 'yes' !== $override ) {
            return;
        }

        // Hide standard WooCommerce login columns with CSS and render Payamakio card
        echo '<style>.woocommerce-form-login, .woocommerce-form-register, .woocommerce-account .u-columns { display: none !important; }</style>';
        echo '<div class="payamakio-wc-auth-wrapper">';
        self::render_auth_card( array(
            'redirect' => wc_get_page_permalink( 'myaccount' ),
        ) );
        echo '</div>';
    }

    /**
     * Sync phone number when WooCommerce creates a new customer
     */
    public function sync_wc_customer_mobile( $customer_id, $new_customer_data, $password_generated ) {
        if ( ! empty( $_POST['billing_phone'] ) ) {
            $mobile = Payamakio_Helper::normalize_mobile( $_POST['billing_phone'] );
            if ( ! empty( $mobile ) ) {
                update_user_meta( $customer_id, 'payamakio_mobile', $mobile );
                update_user_meta( $customer_id, 'billing_phone', $mobile );
                update_user_meta( $customer_id, 'digits_phone', $mobile );
            }
        }
    }

    /**
     * Sync phone on checkout submission
     */
    public function sync_checkout_mobile( $customer_id, $posted ) {
        if ( ! empty( $posted['billing_phone'] ) ) {
            $mobile = Payamakio_Helper::normalize_mobile( $posted['billing_phone'] );
            if ( ! empty( $mobile ) ) {
                update_user_meta( $customer_id, 'payamakio_mobile', $mobile );
                update_user_meta( $customer_id, 'billing_phone', $mobile );
                update_user_meta( $customer_id, 'digits_phone', $mobile );
            }
        }
    }

    /**
     * Get redirect destination URL after authentication
     */
    private function get_redirect_url( $custom_redirect = '', $is_new_user = false ) {
        if ( ! empty( $custom_redirect ) ) {
            return esc_url_raw( $custom_redirect );
        }

        $settings = get_option( 'payamakio_settings', array() );
        $type = $is_new_user ? ( $settings['auth_redirect_register'] ?? 'account' ) : ( $settings['auth_redirect_login'] ?? 'account' );

        switch ( $type ) {
            case 'home':
                return home_url();

            case 'custom':
                $custom = $settings['auth_custom_redirect_url'] ?? '';
                return ! empty( $custom ) ? esc_url_raw( $custom ) : home_url();

            case 'account':
            default:
                if ( function_exists( 'wc_get_page_permalink' ) ) {
                    return wc_get_page_permalink( 'myaccount' );
                }
                return admin_url();
        }
    }

    /**
     * AJAX: Send OTP Code
     */
    public function ajax_send_otp() {
        check_ajax_referer( 'payamakio_auth_nonce', 'nonce' );

        if ( ! self::is_active() ) {
            wp_send_json_error( array( 'message' => 'سیستم احراز هویت پیامکی در حال حاضر غیرفعال است.' ) );
        }

        $mobile = isset( $_POST['mobile'] ) ? Payamakio_Helper::normalize_mobile( $_POST['mobile'] ) : '';
        $mode   = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'auto';

        if ( empty( $mobile ) || strlen( $mobile ) !== 11 || substr( $mobile, 0, 2 ) !== '09' ) {
            wp_send_json_error( array( 'message' => 'شماره موبایل وارد شده معتبر نیست. لطفا یک شماره ۱۱ رقمی معتبر وارد کنید.' ) );
        }

        $settings = get_option( 'payamakio_settings', array() );

        // Check Cooldown to prevent SMS flooding
        $cooldown_key = 'payamakio_cd_' . md5( $mobile );
        if ( false !== get_transient( $cooldown_key ) ) {
            $remaining = (int) get_transient( $cooldown_key ) - time();
            $msg = sprintf( 'لطفا تا پایان زمان انتظار شکیبا باشید (%s ثانیه دیگر).', Payamakio_Helper::to_persian_digits( max( 1, $remaining ) ) );
            wp_send_json_error( array( 'message' => $msg ) );
        }

        // IP-based Rate Limiting (Max 12 requests in 10 minutes per IP)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip_key = 'payamakio_ip_' . md5( $ip );
        $ip_hits = (int) get_transient( $ip_key );
        if ( $ip_hits > 12 ) {
            wp_send_json_error( array( 'message' => 'تعداد درخواست های بیش از حد از این دستگاه ثبت شده است. لطفا بعدا تلاش فرمایید.' ) );
        }
        set_transient( $ip_key, $ip_hits + 1, 600 );

        // User existence check
        $user = Payamakio_Helper::get_user_by_mobile( $mobile );

        if ( 'reset' === $mode ) {
            if ( ! $user ) {
                wp_send_json_error( array( 'message' => 'کاربری با این شماره موبایل در سایت یافت نشد.' ) );
            }
        } elseif ( 'login' === $mode ) {
            if ( ! $user && ( $settings['auth_allow_register'] ?? 'yes' ) !== 'yes' ) {
                wp_send_json_error( array( 'message' => 'کاربری با این شماره یافت نشد و ثبت نام کاربران جدید غیرفعال است.' ) );
            }
        }

        // Generate Numeric OTP Code
        $code_len = absint( $settings['auth_code_length'] ?? 5 );
        $code_len = in_array( $code_len, array( 4, 5, 6 ), true ) ? $code_len : 5;

        $min = (int) pow( 10, $code_len - 1 );
        $max = (int) pow( 10, $code_len ) - 1;
        $otp = (string) rand( $min, $max );

        $expiry = absint( $settings['auth_expiry'] ?? 120 );
        $expiry = $expiry > 0 ? $expiry : 120;

        // Save OTP transient
        $transient_key = 'payamakio_otp_' . md5( $mobile );
        set_transient( $transient_key, array(
            'code'       => $otp,
            'attempts'   => 0,
            'created_at' => time(),
            'mode'       => $mode,
        ), $expiry );

        // Set Cooldown
        set_transient( $cooldown_key, time() + $expiry, $expiry );

        // Send via Gateway
        $gateway     = payamakio_get_gateway();
        $pattern_key = ( 'reset' === $mode ) ? 'auth_reset' : 'auth_otp';
        $pattern     = $settings[ $pattern_key . '_pattern' ] ?? ( $settings['auth_otp_pattern'] ?? '' );
        $param_var   = $settings['auth_otp_param'] ?? 'code';

        if ( ! empty( $pattern ) ) {
            $variables = array(
                $param_var => $otp,
                'code'     => $otp,
            );
            $send_res = $gateway->send_pattern( $mobile, $pattern, $variables );
        } else {
            $site_title = get_bloginfo( 'name' );
            $sms_text = sprintf( "کد تایید شما در %s:\n%s\nاعتبار: ۲ دقیقه", $site_title, $otp );
            $send_res = $gateway->send_sms( $mobile, $sms_text );
        }

        // Log into database
        Payamakio_Logger::log(
            $mobile,
            $gateway->get_id(),
            $pattern,
            $send_res['success'] ? 'success' : 'failed',
            $send_res['success'] ? ( 'کد تایید ورود و عضویت با موفقیت ارسال شد.' ) : ( 'خطا در ارسال کد تایید: ' . ( $send_res['error'] ?? 'نامشخص' ) ),
            'auth_otp',
            $send_res['raw'] ?? ''
        );

        if ( $send_res['success'] ) {
            wp_send_json_success( array(
                'message'  => 'کد تایید با موفقیت ارسال گردید.',
                'cooldown' => $expiry,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => 'ارسال پیامک با خطا مواجه شد: ' . ( $send_res['error'] ?? 'خطای درگاه پیامک' ),
            ) );
        }
    }

    /**
     * AJAX: Verify OTP & Login or Register
     */
    public function ajax_verify_otp() {
        check_ajax_referer( 'payamakio_auth_nonce', 'nonce' );

        if ( ! self::is_active() ) {
            wp_send_json_error( array( 'message' => 'سیستم احراز هویت پیامکی غیرفعال است.' ) );
        }

        $mobile   = isset( $_POST['mobile'] ) ? Payamakio_Helper::normalize_mobile( $_POST['mobile'] ) : '';
        $code     = isset( $_POST['otp_code'] ) ? Payamakio_Helper::to_english_digits( trim( $_POST['otp_code'] ) ) : '';
        $mode     = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'auto';
        $fname    = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $lname    = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';

        if ( empty( $mobile ) || empty( $code ) ) {
            wp_send_json_error( array( 'message' => 'شماره موبایل و کد تایید الزامی است.' ) );
        }

        $transient_key = 'payamakio_otp_' . md5( $mobile );
        $otp_data      = get_transient( $transient_key );

        if ( false === $otp_data || empty( $otp_data['code'] ) ) {
            wp_send_json_error( array( 'message' => 'کد تایید منقضی شده است یا وجود ندارد. لطفا مجددا درخواست کد دهید.' ) );
        }

        // Verify Attempt Counts
        $attempts = (int) ( $otp_data['attempts'] ?? 0 ) + 1;
        if ( $attempts > 5 ) {
            delete_transient( $transient_key );
            wp_send_json_error( array( 'message' => 'تعداد تلاش های ناموفق بیش از حد مجاز است. لطفا کد جدید دریافت فرمایید.' ) );
        }

        // Code Match Check
        if ( (string) $otp_data['code'] !== (string) $code ) {
            $otp_data['attempts'] = $attempts;
            set_transient( $transient_key, $otp_data, 120 );
            wp_send_json_error( array( 'message' => 'کد تایید وارد شده نادرست است. لطفا دوباره بررسی نمایید.' ) );
        }

        // Code is correct: delete transient and clear cooldown
        delete_transient( $transient_key );
        delete_transient( 'payamakio_cd_' . md5( $mobile ) );

        $settings = get_option( 'payamakio_settings', array() );
        $user     = Payamakio_Helper::get_user_by_mobile( $mobile );

        if ( $user instanceof WP_User ) {
            // Existing User Login
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID, $user->user_login );
            wp_set_auth_cookie( $user->ID, true, is_ssl() );
            do_action( 'wp_login', $user->user_login, $user );

            // Update metadata if empty
            if ( ! empty( $fname ) && empty( $user->first_name ) ) {
                update_user_meta( $user->ID, 'first_name', $fname );
                update_user_meta( $user->ID, 'billing_first_name', $fname );
            }
            if ( ! empty( $lname ) && empty( $user->last_name ) ) {
                update_user_meta( $user->ID, 'last_name', $lname );
                update_user_meta( $user->ID, 'billing_last_name', $lname );
            }
            update_user_meta( $user->ID, 'payamakio_mobile', $mobile );
            update_user_meta( $user->ID, 'billing_phone', $mobile );
            update_user_meta( $user->ID, 'digits_phone', $mobile );

            Payamakio_Logger::log(
                $mobile,
                'auth',
                '',
                'success',
                sprintf( 'ورود موفقیت آمیز کاربر %s با کد تایید OTP.', $user->user_login ),
                'auth_login'
            );

            wp_send_json_success( array(
                'message'  => 'ورود با موفقیت انجام شد. در حال انتقال به حساب کاربری...',
                'redirect' => $this->get_redirect_url( $redirect, false ),
            ) );

        } else {
            // New User Registration
            if ( ( $settings['auth_allow_register'] ?? 'yes' ) !== 'yes' ) {
                wp_send_json_error( array( 'message' => 'عضویت کاربران جدید در سایت فعال نیست.' ) );
            }

            // Generate Username
            $username = $mobile;
            if ( username_exists( $username ) ) {
                $username = $mobile . '_' . rand( 10, 99 );
            }

            // Generate or use password
            if ( empty( $password ) ) {
                $password = wp_generate_password( 16, true, true );
            }

            // Generate Email if not provided
            if ( empty( $email ) || ! is_email( $email ) ) {
                $host = wp_parse_url( home_url(), PHP_URL_HOST );
                $host = preg_replace( '/^www\./', '', (string) $host );
                $host = ! empty( $host ) ? $host : 'site.local';
                $email = $mobile . '@' . $host;
                if ( email_exists( $email ) ) {
                    $email = $mobile . '_' . rand( 10, 99 ) . '@' . $host;
                }
            } elseif ( email_exists( $email ) ) {
                wp_send_json_error( array( 'message' => 'این آدرس ایمیل قبلا در سیستم ثبت شده است. لطفا ایمیل دیگری وارد نمایید.' ) );
            }

            $default_role = $settings['auth_default_role'] ?? 'customer';
            if ( ! get_role( $default_role ) ) {
                $default_role = 'subscriber';
            }

            $user_id = wp_insert_user( array(
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $email,
                'first_name'   => $fname,
                'last_name'    => $lname,
                'display_name' => trim( $fname . ' ' . $lname ) ?: $mobile,
                'role'         => $default_role,
            ) );

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( array( 'message' => 'خطا در ثبت نام: ' . $user_id->get_error_message() ) );
            }

            // Save phone metadata
            update_user_meta( $user_id, 'payamakio_mobile', $mobile );
            update_user_meta( $user_id, 'billing_phone', $mobile );
            update_user_meta( $user_id, 'digits_phone', $mobile );
            if ( ! empty( $fname ) ) {
                update_user_meta( $user_id, 'billing_first_name', $fname );
            }
            if ( ! empty( $lname ) ) {
                update_user_meta( $user_id, 'billing_last_name', $lname );
            }

            // Log in newly created user
            wp_clear_auth_cookie();
            wp_set_current_user( $user_id, $username );
            wp_set_auth_cookie( $user_id, true, is_ssl() );
            do_action( 'wp_login', $username, get_userdata( $user_id ) );

            Payamakio_Logger::log(
                $mobile,
                'auth',
                '',
                'success',
                sprintf( 'عضویت و ثبت نام کاربر جدید با شماره %s با موفقیت انجام شد.', $mobile ),
                'auth_register'
            );

            wp_send_json_success( array(
                'message'  => 'عضویت شما با موفقیت انجام شد! در حال انتقال به سایت...',
                'redirect' => $this->get_redirect_url( $redirect, true ),
            ) );
        }
    }

    /**
     * AJAX: Standard Password Login
     */
    public function ajax_password_login() {
        check_ajax_referer( 'payamakio_auth_nonce', 'nonce' );

        if ( ! self::is_active() ) {
            wp_send_json_error( array( 'message' => 'احراز هویت غیرفعال است.' ) );
        }

        $log      = isset( $_POST['log'] ) ? sanitize_text_field( $_POST['log'] ) : '';
        $pwd      = isset( $_POST['pwd'] ) ? $_POST['pwd'] : '';
        $remember = ( ! empty( $_POST['remember'] ) );
        $redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';

        if ( empty( $log ) || empty( $pwd ) ) {
            wp_send_json_error( array( 'message' => 'لطفا نام کاربری یا شماره موبایل و کلمه عبور را وارد فرمایید.' ) );
        }

        // If log looks like Iranian phone, find username
        $user_login = $log;
        $norm_mobile = Payamakio_Helper::normalize_mobile( $log );
        if ( ! empty( $norm_mobile ) && strlen( $norm_mobile ) === 11 && substr( $norm_mobile, 0, 2 ) === '09' ) {
            $user = Payamakio_Helper::get_user_by_mobile( $norm_mobile );
            if ( $user instanceof WP_User ) {
                $user_login = $user->user_login;
            }
        }

        $creds = array(
            'user_login'    => $user_login,
            'user_password' => $pwd,
            'remember'      => $remember,
        );

        $signon = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $signon ) ) {
            wp_send_json_error( array( 'message' => 'نام کاربری یا رمز عبور اشتباه است.' ) );
        }

        wp_send_json_success( array(
            'message'  => 'ورود با موفقیت انجام شد. در حال انتقال...',
            'redirect' => $this->get_redirect_url( $redirect, false ),
        ) );
    }

    /**
     * AJAX: Reset Password via SMS OTP
     */
    public function ajax_reset_password() {
        check_ajax_referer( 'payamakio_auth_nonce', 'nonce' );

        $mobile   = isset( $_POST['mobile'] ) ? Payamakio_Helper::normalize_mobile( $_POST['mobile'] ) : '';
        $code     = isset( $_POST['otp_code'] ) ? Payamakio_Helper::to_english_digits( trim( $_POST['otp_code'] ) ) : '';
        $new_pass = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';

        if ( empty( $mobile ) || empty( $code ) || empty( $new_pass ) ) {
            wp_send_json_error( array( 'message' => 'تمامی فیلدها الزامی می باشند.' ) );
        }

        if ( strlen( $new_pass ) < 6 ) {
            wp_send_json_error( array( 'message' => 'کلمه عبور باید حداقل ۶ کاراکتر باشد.' ) );
        }

        $transient_key = 'payamakio_otp_' . md5( $mobile );
        $otp_data      = get_transient( $transient_key );

        if ( false === $otp_data || (string) $otp_data['code'] !== (string) $code ) {
            wp_send_json_error( array( 'message' => 'کد تایید بازیابی نامعتبر یا منقضی شده است.' ) );
        }

        delete_transient( $transient_key );

        $user = Payamakio_Helper::get_user_by_mobile( $mobile );
        if ( ! $user instanceof WP_User ) {
            wp_send_json_error( array( 'message' => 'کاربر مورد نظر یافت نشد.' ) );
        }

        wp_set_password( $new_pass, $user->ID );

        // Sign in user
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID, true, is_ssl() );
        do_action( 'wp_login', $user->user_login, $user );

        Payamakio_Logger::log(
            $mobile,
            'auth',
            '',
            'success',
            sprintf( 'رمز عبور کاربر %s با موفقیت از طریق پیامک بازیابی شد.', $user->user_login ),
            'auth_reset'
        );

        wp_send_json_success( array(
            'message'  => 'کلمه عبور جدید با موفقیت ذخیره شد و وارد شدید.',
            'redirect' => $this->get_redirect_url( '', false ),
        ) );
    }

    /**
     * AJAX: 1-Click Auto-Create Dedicated Auth Page
     */
    public function ajax_create_page() {
        check_ajax_referer( 'payamakio_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'عدم دسترسی کافی.' ) );
        }

        $page_title = 'ورود و عضویت';
        $content    = '[payamakio_auth]';

        $page_id = wp_insert_post( array(
            'post_title'     => $page_title,
            'post_name'      => 'auth',
            'post_content'   => $content,
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ) );

        if ( is_wp_error( $page_id ) ) {
            wp_send_json_error( array( 'message' => 'خطا در ایجاد برگه: ' . $page_id->get_error_message() ) );
        }

        // Save page ID in settings
        $settings = get_option( 'payamakio_settings', array() );
        $settings['auth_page_id'] = $page_id;
        update_option( 'payamakio_settings', $settings );

        wp_send_json_success( array(
            'message'  => 'برگه اختصاصی ورود و عضویت با موفقیت ایجاد گردید.',
            'page_id'  => $page_id,
            'page_url' => get_permalink( $page_id ),
        ) );
    }
}
