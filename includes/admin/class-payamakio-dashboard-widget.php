<?php
/**
 * Payamakio WordPress Dashboard Widget
 * Real-time SMS overview, wallet balance, sending statistics, module statuses, recent outbox feeds, and quick navigation.
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Dashboard_Widget {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_payamakio_dashboard_refresh', array( $this, 'ajax_refresh' ) );
    }

    /**
     * Check if current user has permissions
     */
    private function can_view() {
        return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
    }

    /**
     * Register Dashboard Widget in WordPress
     */
    public function register_widget() {
        if ( ! $this->can_view() ) {
            return;
        }

        wp_add_dashboard_widget(
            'payamakio_dashboard_widget',
            'پیامکیو | وضعیت پیامک و اتوماسیون',
            array( $this, 'render_widget' )
        );

        // Position widget near the top of the main dashboard column if possible
        global $wp_meta_boxes;
        if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['payamakio_dashboard_widget'] ) ) {
            $my_widget = array( 'payamakio_dashboard_widget' => $wp_meta_boxes['dashboard']['normal']['core']['payamakio_dashboard_widget'] );
            unset( $wp_meta_boxes['dashboard']['normal']['core']['payamakio_dashboard_widget'] );
            $wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $my_widget, $wp_meta_boxes['dashboard']['normal']['core'] );
        }
    }

    /**
     * Enqueue styles and scripts for dashboard widget on index.php
     */
    public function enqueue_assets( $hook ) {
        if ( 'index.php' !== $hook ) {
            return;
        }

        if ( ! $this->can_view() ) {
            return;
        }

        wp_enqueue_style(
            'payamakio-dashboard-widget',
            PAYAMAKIO_URL . 'assets/css/payamakio-dashboard.css',
            array(),
            PAYAMAKIO_VERSION
        );

        wp_enqueue_script(
            'payamakio-dashboard-widget',
            PAYAMAKIO_URL . 'assets/js/payamakio-dashboard.js',
            array( 'jquery' ),
            PAYAMAKIO_VERSION,
            true
        );

        wp_localize_script(
            'payamakio-dashboard-widget',
            'payamakioDashboardVars',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'payamakio_dashboard_nonce' ),
            )
        );
    }

    /**
     * AJAX handler for live refresh
     */
    public function ajax_refresh() {
        check_ajax_referer( 'payamakio_dashboard_nonce', 'nonce' );

        if ( ! $this->can_view() ) {
            wp_send_json_error( array( 'message' => 'عدم دسترسی کافی' ) );
        }

        ob_start();
        $this->render_widget_content();
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Map event types to readable Persian labels
     */
    public static function get_event_label( $event_type ) {
        switch ( $event_type ) {
            case 'order_completed':
                return 'تکمیل سفارش';
            case 'order_processing':
                return 'پردازش سفارش';
            case 'cart_recovery':
                return 'بازیابی سبد خرید';
            case 'lead_magnet':
                return 'لید مگنت و هدیه';
            case 'admin_alert':
                return 'اعلان به مدیر';
            case 'test_pattern':
                return 'تست الگوی پترن';
            case 'test_webservice':
                return 'تست وب سرویس';
            case 'test_sandbox':
                return 'تست آزمایشی';
            default:
                return ! empty( $event_type ) ? esc_html( $event_type ) : 'ارسال پیامک';
        }
    }

    /**
     * Render widget wrapper
     */
    public function render_widget() {
        echo '<div id="payamakio-dashboard-widget-content" class="payamakio-dw-root" dir="rtl">';
        $this->render_widget_content();
        echo '</div>';
    }

    /**
     * Render the internal HTML content of the widget
     */
    public function render_widget_content() {
        $settings     = get_option( 'payamakio_settings', array() );
        $gateway      = payamakio_get_gateway();
        $gateway_id   = $gateway->get_id();
        $gateway_name = $gateway->get_name();
        $panel_url    = method_exists( $gateway, 'get_panel_url' ) ? $gateway->get_panel_url() : 'https://ippanel.com';
        $balance      = method_exists( $gateway, 'get_balance' ) ? $gateway->get_balance() : false;
        $balance_rial  = ( false !== $balance ) ? round( (float) $balance ) : false;
        $balance_toman = ( false !== $balance ) ? round( (float) $balance / 10 ) : false;

        $stats        = Payamakio_Logger::get_stats();
        $total        = (int) ( $stats['total'] ?? 0 );
        $success      = (int) ( $stats['success'] ?? 0 );
        $failed       = (int) ( $stats['failed'] ?? 0 );
        $today        = (int) ( $stats['today'] ?? 0 );
        $success_rate = ( $total > 0 ) ? round( ( $success / $total ) * 100 ) : 100;

        $recent_logs  = Payamakio_Logger::get_logs( 5 );

        $mod_completed  = ( ( $settings['order_completed_active'] ?? 'yes' ) === 'yes' );
        $mod_processing = ( ( $settings['order_processing_active'] ?? 'no' ) === 'yes' );
        $mod_cart       = ( ( $settings['cart_recovery_active'] ?? 'no' ) === 'yes' );
        $mod_lead       = ( ( $settings['lead_magnet_active'] ?? 'yes' ) === 'yes' );
        $mod_admin      = ( ( $settings['admin_alert_active'] ?? 'no' ) === 'yes' );

        $gateway_tab_url = admin_url( 'admin.php?page=payamakio#tab-gateway' );
        $orders_url      = admin_url( 'admin.php?page=payamakio#tab-orders' );
        $recovery_url    = admin_url( 'admin.php?page=payamakio#tab-recovery' );
        $lead_url        = admin_url( 'admin.php?page=payamakio#tab-lead' );
        $admin_alert_url = admin_url( 'admin.php?page=payamakio#tab-admin' );
        $sandbox_url     = admin_url( 'admin.php?page=payamakio#tab-sandbox' );
        $logs_url        = admin_url( 'admin.php?page=payamakio#tab-logs' );
        $base_admin_url  = admin_url( 'admin.php?page=payamakio' );
        ?>

        <!-- Top Bar: Status and Live Refresh -->
        <div class="payamakio-dw-topbar">
            <div class="payamakio-dw-status">
                <?php if ( false !== $balance ) : ?>
                    <span class="payamakio-dw-pill payamakio-dw-pill-online" title="ارتباط با درگاه پیامک فعال و برقرار است">
                        <span class="payamakio-dw-dot online"></span>
                        <span>درگاه متصل: <?php echo esc_html( $gateway_name ); ?></span>
                    </span>
                <?php else : ?>
                    <span class="payamakio-dw-pill payamakio-dw-pill-offline" title="ارتباط با درگاه برقرار نیست یا نیازمند تنظیمات است">
                        <span class="payamakio-dw-dot offline"></span>
                        <span>درگاه: <?php echo esc_html( $gateway_name ); ?> (نیاز به تنظیمات)</span>
                    </span>
                <?php endif; ?>
            </div>
            <div class="payamakio-dw-top-actions">
                <button type="button" class="payamakio-dw-btn-refresh" onclick="payamakioDashboardRefresh(this)" title="بروزرسانی زنده آمار و اعتبار">
                    <?php echo Payamakio_Helper::icon( 'refresh', 13 ); ?>
                    <span>بروزرسانی</span>
                </button>
            </div>
        </div>

        <!-- Hero Balance Card -->
        <div class="payamakio-dw-hero-card">
            <div class="payamakio-dw-hero-bg-glow"></div>
            <div class="payamakio-dw-hero-content">
                <div class="payamakio-dw-hero-left">
                    <div class="payamakio-dw-hero-label">
                        <span class="payamakio-dw-hero-icon"><?php echo Payamakio_Helper::icon( 'wallet', 18 ); ?></span>
                        <span>اعتبار کیف پول سرویس پیامک</span>
                    </div>
                    <div class="payamakio-dw-hero-balance">
                        <?php if ( false !== $balance_toman ) : ?>
                            <span class="payamakio-dw-balance-num"><?php echo esc_html( Payamakio_Helper::format_number( $balance_toman ) ); ?></span>
                            <span class="payamakio-dw-balance-unit">تومان</span>
                            <span class="payamakio-dw-balance-rial">(<?php echo esc_html( Payamakio_Helper::format_number( $balance_rial ) ); ?> ریال)</span>
                        <?php else : ?>
                            <span class="payamakio-dw-balance-na">ثبت نشده / نیاز به تنظیم</span>
                        <?php endif; ?>
                    </div>
                    <div class="payamakio-dw-hero-meta">
                        <?php if ( false !== $balance_toman ) : ?>
                            <?php if ( $balance_toman >= 2000 ) : ?>
                                <span class="payamakio-dw-badge payamakio-dw-badge-good">موجودی کافی</span>
                            <?php elseif ( $balance_toman > 0 ) : ?>
                                <span class="payamakio-dw-badge payamakio-dw-badge-warn">موجودی رو به اتمام (نیاز به شارژ)</span>
                            <?php else : ?>
                                <span class="payamakio-dw-badge payamakio-dw-badge-danger">بدون اعتبار</span>
                            <?php endif; ?>
                        <?php else : ?>
                            <a href="<?php echo esc_url( $gateway_tab_url ); ?>" class="payamakio-dw-badge payamakio-dw-badge-warn">ورود کلید وب سرویس</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="payamakio-dw-hero-actions">
                    <?php if ( ! empty( $panel_url ) ) : ?>
                        <a href="<?php echo esc_url( $panel_url ); ?>" target="_blank" rel="noopener noreferrer" class="payamakio-dw-btn-hero payamakio-dw-btn-hero-primary">
                            <?php echo Payamakio_Helper::icon( 'external', 13 ); ?>
                            <span>ورود به پنل پیامک</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $sandbox_url ); ?>" class="payamakio-dw-btn-hero payamakio-dw-btn-hero-secondary">
                        <?php echo Payamakio_Helper::icon( 'send', 13 ); ?>
                        <span>تست سریع</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Primary KPI Stats Cards -->
        <div class="payamakio-dw-stats-grid">
            <!-- 1. Total Sent -->
            <div class="payamakio-dw-stat-card">
                <div class="payamakio-dw-stat-header">
                    <span class="payamakio-dw-stat-label">کل ارسال ها</span>
                    <span class="payamakio-dw-stat-icon indigo"><?php echo Payamakio_Helper::icon( 'sms', 15 ); ?></span>
                </div>
                <div class="payamakio-dw-stat-val"><?php echo esc_html( Payamakio_Helper::format_number( $total ) ); ?></div>
                <div class="payamakio-dw-stat-sub">مجموع ثبت شده</div>
            </div>

            <!-- 2. Success Rate -->
            <div class="payamakio-dw-stat-card">
                <div class="payamakio-dw-stat-header">
                    <span class="payamakio-dw-stat-label">ارسال های موفق</span>
                    <span class="payamakio-dw-stat-icon emerald"><?php echo Payamakio_Helper::icon( 'check', 15 ); ?></span>
                </div>
                <div class="payamakio-dw-stat-val"><?php echo esc_html( Payamakio_Helper::format_number( $success ) ); ?></div>
                <div class="payamakio-dw-stat-sub">
                    <span class="payamakio-dw-stat-rate"><?php echo esc_html( Payamakio_Helper::to_persian_digits( $success_rate ) ); ?>٪ موفقیت</span>
                </div>
            </div>

            <!-- 3. Sent Today -->
            <div class="payamakio-dw-stat-card">
                <div class="payamakio-dw-stat-header">
                    <span class="payamakio-dw-stat-label">ارسال های امروز</span>
                    <span class="payamakio-dw-stat-icon purple"><?php echo Payamakio_Helper::icon( 'clock', 15 ); ?></span>
                </div>
                <div class="payamakio-dw-stat-val"><?php echo esc_html( Payamakio_Helper::format_number( $today ) ); ?></div>
                <div class="payamakio-dw-stat-sub">از بامداد امروز تاکنون</div>
            </div>

            <!-- 4. Failed Sends -->
            <div class="payamakio-dw-stat-card">
                <div class="payamakio-dw-stat-header">
                    <span class="payamakio-dw-stat-label">ارسال های ناموفق</span>
                    <span class="payamakio-dw-stat-icon rose"><?php echo Payamakio_Helper::icon( 'alert', 15 ); ?></span>
                </div>
                <div class="payamakio-dw-stat-val"><?php echo esc_html( Payamakio_Helper::format_number( $failed ) ); ?></div>
                <div class="payamakio-dw-stat-sub">
                    <?php echo ( 0 === $failed ) ? 'بدون خطا' : 'نیاز به بررسی در لاگ ها'; ?>
                </div>
            </div>
        </div>

        <!-- Modules Status Strip -->
        <div class="payamakio-dw-section">
            <div class="payamakio-dw-section-header">
                <span class="payamakio-dw-section-title">وضعیت ماژول های خودکار</span>
                <a href="<?php echo esc_url( $orders_url ); ?>" class="payamakio-dw-section-link">پیکربندی ماژول ها</a>
            </div>
            <div class="payamakio-dw-modules-grid">
                <a href="<?php echo esc_url( $orders_url ); ?>" class="payamakio-dw-module-chip <?php echo $mod_completed ? 'is-active' : 'is-inactive'; ?>">
                    <span class="payamakio-dw-module-dot"></span>
                    <span class="payamakio-dw-module-name">تکمیل سفارش</span>
                    <span class="payamakio-dw-module-state"><?php echo $mod_completed ? 'فعال' : 'غیر فعال'; ?></span>
                </a>
                <a href="<?php echo esc_url( $orders_url ); ?>" class="payamakio-dw-module-chip <?php echo $mod_processing ? 'is-active' : 'is-inactive'; ?>">
                    <span class="payamakio-dw-module-dot"></span>
                    <span class="payamakio-dw-module-name">در حال انجام</span>
                    <span class="payamakio-dw-module-state"><?php echo $mod_processing ? 'فعال' : 'غیر فعال'; ?></span>
                </a>
                <a href="<?php echo esc_url( $recovery_url ); ?>" class="payamakio-dw-module-chip <?php echo $mod_cart ? 'is-active' : 'is-inactive'; ?>">
                    <span class="payamakio-dw-module-dot"></span>
                    <span class="payamakio-dw-module-name">سبد رها شده</span>
                    <span class="payamakio-dw-module-state"><?php echo $mod_cart ? 'فعال' : 'غیر فعال'; ?></span>
                </a>
                <a href="<?php echo esc_url( $lead_url ); ?>" class="payamakio-dw-module-chip <?php echo $mod_lead ? 'is-active' : 'is-inactive'; ?>">
                    <span class="payamakio-dw-module-dot"></span>
                    <span class="payamakio-dw-module-name">لید مگنت و هدیه</span>
                    <span class="payamakio-dw-module-state"><?php echo $mod_lead ? 'فعال' : 'غیر فعال'; ?></span>
                </a>
                <a href="<?php echo esc_url( $admin_alert_url ); ?>" class="payamakio-dw-module-chip <?php echo $mod_admin ? 'is-active' : 'is-inactive'; ?>">
                    <span class="payamakio-dw-module-dot"></span>
                    <span class="payamakio-dw-module-name">اعلان به مدیر</span>
                    <span class="payamakio-dw-module-state"><?php echo $mod_admin ? 'فعال' : 'غیر فعال'; ?></span>
                </a>
            </div>
        </div>

        <!-- Recent Outbox Feed -->
        <div class="payamakio-dw-section">
            <div class="payamakio-dw-section-header">
                <span class="payamakio-dw-section-title">آخرین پیامک های ارسالی</span>
                <a href="<?php echo esc_url( $logs_url ); ?>" class="payamakio-dw-section-link">
                    <span>مشاهده همه لاگ ها</span>
                    <?php echo Payamakio_Helper::icon( 'chevron-left', 11 ); ?>
                </a>
            </div>
            <div class="payamakio-dw-logs-list">
                <?php if ( empty( $recent_logs ) ) : ?>
                    <div class="payamakio-dw-empty">
                        <div class="payamakio-dw-empty-icon"><?php echo Payamakio_Helper::icon( 'sms', 24 ); ?></div>
                        <p>هنوز هیچ پیامکی در تاریخچه سیستم ثبت نشده است.</p>
                        <a href="<?php echo esc_url( $sandbox_url ); ?>" class="payamakio-dw-empty-btn">ارسال پیامک آزمایشی</a>
                    </div>
                <?php else : ?>
                    <?php foreach ( $recent_logs as $log ) : ?>
                        <?php
                        $is_ok        = ( 'success' === $log['status'] );
                        $masked_phone = Payamakio_Helper::mask_mobile( $log['mobile'] );
                        $event_label  = self::get_event_label( $log['event_type'] );
                        $time_label   = Payamakio_Helper::time_ago( $log['created_at'] );
                        ?>
                        <div class="payamakio-dw-log-item <?php echo $is_ok ? 'is-success' : 'is-failed'; ?>">
                            <div class="payamakio-dw-log-icon">
                                <?php echo $is_ok ? Payamakio_Helper::icon( 'check', 13 ) : Payamakio_Helper::icon( 'alert', 13 ); ?>
                            </div>
                            <div class="payamakio-dw-log-details">
                                <div class="payamakio-dw-log-row1">
                                    <span class="payamakio-dw-log-phone" dir="ltr"><?php echo esc_html( $masked_phone ); ?></span>
                                    <span class="payamakio-dw-log-badge"><?php echo esc_html( $event_label ); ?></span>
                                    <span class="payamakio-dw-log-time"><?php echo esc_html( $time_label ); ?></span>
                                </div>
                                <div class="payamakio-dw-log-row2">
                                    <span class="payamakio-dw-log-msg" title="<?php echo esc_attr( $log['response_message'] ); ?>">
                                        <?php echo esc_html( wp_trim_words( $log['response_message'], 10, '...' ) ); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="payamakio-dw-log-status">
                                <span class="payamakio-dw-status-tag <?php echo $is_ok ? 'tag-success' : 'tag-failed'; ?>">
                                    <?php echo $is_ok ? 'موفق' : 'خطا'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Navigation Bar -->
        <div class="payamakio-dw-actions-grid">
            <a href="<?php echo esc_url( $gateway_tab_url ); ?>" class="payamakio-dw-action-btn">
                <span class="payamakio-dw-action-icon"><?php echo Payamakio_Helper::icon( 'settings', 16 ); ?></span>
                <span class="payamakio-dw-action-label">تنظیمات درگاه</span>
            </a>
            <a href="<?php echo esc_url( $orders_url ); ?>" class="payamakio-dw-action-btn">
                <span class="payamakio-dw-action-icon"><?php echo Payamakio_Helper::icon( 'box', 16 ); ?></span>
                <span class="payamakio-dw-action-label">پیامک سفارشات</span>
            </a>
            <a href="<?php echo esc_url( $recovery_url ); ?>" class="payamakio-dw-action-btn">
                <span class="payamakio-dw-action-icon"><?php echo Payamakio_Helper::icon( 'cart', 16 ); ?></span>
                <span class="payamakio-dw-action-label">سبد رها شده</span>
            </a>
            <a href="<?php echo esc_url( $sandbox_url ); ?>" class="payamakio-dw-action-btn">
                <span class="payamakio-dw-action-icon"><?php echo Payamakio_Helper::icon( 'send', 16 ); ?></span>
                <span class="payamakio-dw-action-label">تست زنده ارسال</span>
            </a>
        </div>

        <!-- Footer -->
        <div class="payamakio-dw-footer">
            <div class="payamakio-dw-footer-brand">
                <span class="payamakio-dw-footer-name">پیامکیو</span>
                <span class="payamakio-dw-divider">•</span>
                <span>نسخه <?php echo esc_html( PAYAMAKIO_VERSION ); ?></span>
            </div>
            <a href="<?php echo esc_url( $base_admin_url ); ?>" class="payamakio-dw-footer-link">
                <span>ورود به پنل مدیریت پیامکیو</span>
                <?php echo Payamakio_Helper::icon( 'chevron-left', 11 ); ?>
            </a>
        </div>
        <?php
    }
}
