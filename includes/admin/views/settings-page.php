<?php
/**
 * Settings Page View for Payamakio
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'gateways';
$current_gateway = $settings['gateway'] ?? 'ippanel';
?>

<div class="payamakio-wrap">

    <!-- Top Glow Header -->
    <header class="payamakio-header">
        <div class="payamakio-header-inner">
            <div class="payamakio-brand">
                <div class="payamakio-brand-icon">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <path d="M13 8l-4 4"></path>
                        <path d="M9 8l4 4"></path>
                    </svg>
                </div>
                <div class="payamakio-brand-text">
                    <div class="payamakio-brand-title">
                        <h1>پیامکیو</h1>
                        <span class="payamakio-brand-badge">Payamakio v<?php echo esc_html( PAYAMAKIO_VERSION ); ?></span>
                    </div>
                    <p class="payamakio-brand-desc">
                        سامانه هوشمند ارسال فوق سریع پترن و اتوماسیون پیامک ووکامرس
                    </p>
                </div>
            </div>

            <div class="payamakio-header-meta">
                <div class="payamakio-status-pill">
                    <span class="payamakio-pulse-dot"></span>
                    <span>سامانه ارسال پیامک فعال و آنلاین</span>
                </div>
                <div class="payamakio-author-badge">
                    <span>توسعه توسط</span>
                    <a href="https://arianchaghazardi.ir" target="_blank" rel="noopener">آرین چقازردی</a>
                    <span class="payamakio-sep">&bull;</span>
                    <a href="https://hectortm.ir" target="_blank" rel="noopener">هکتورتیم</a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="payamakio-tabs">
            <a href="#tab-gateways" class="payamakio-tab-btn active" data-tab="tab-gateways">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg></span>
                <span class="payamakio-tab-text">تنظیمات درگاه پیامک</span>
            </a>
            <a href="#tab-orders" class="payamakio-tab-btn" data-tab="tab-orders">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg></span>
                <span class="payamakio-tab-text">پیامک وضعیت سفارشات</span>
            </a>
            <a href="#tab-cart" class="payamakio-tab-btn" data-tab="tab-cart">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></span>
                <span class="payamakio-tab-text">بازیابی سبد خرید رها شده</span>
            </a>
            <a href="#tab-lead" class="payamakio-tab-btn" data-tab="tab-lead">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg></span>
                <span class="payamakio-tab-text">لید مگنت و هدیه 0 تومانی</span>
            </a>
            <a href="#tab-admin" class="payamakio-tab-btn" data-tab="tab-admin">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></span>
                <span class="payamakio-tab-text">اعلان سفارش جدید به مدیر</span>
            </a>
            <a href="#tab-sandbox" class="payamakio-tab-btn" data-tab="tab-sandbox">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></span>
                <span class="payamakio-tab-text">تست زنده ارسال</span>
            </a>
            <a href="#tab-logs" class="payamakio-tab-btn" data-tab="tab-logs">
                <span class="payamakio-tab-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></span>
                <span class="payamakio-tab-text">تاریخچه و لاگ ها</span>
            </a>
        </nav>
    </header>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="payamakio-alert payamakio-alert-success">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>تنظیمات افزونه پیامکیو با موفقیت ذخیره شدند.</span>
        </div>
    <?php endif; ?>

    <!-- Live Stats Bar -->
    <div class="payamakio-stats-grid">
        <div class="payamakio-stat-card">
            <div class="payamakio-stat-icon payamakio-stat-total">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="M7 15h0M2 9.5h20"></path></svg>
            </div>
            <div>
                <div class="payamakio-stat-num"><?php echo esc_html( number_format( $stats['total'] ) ); ?></div>
                <div class="payamakio-stat-desc">کل پیامک های ارسالی</div>
            </div>
        </div>

        <div class="payamakio-stat-card">
            <div class="payamakio-stat-icon payamakio-stat-success">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div>
                <div class="payamakio-stat-num text-emerald"><?php echo esc_html( number_format( $stats['success'] ) ); ?></div>
                <div class="payamakio-stat-desc">ارسال های موفق</div>
            </div>
        </div>

        <div class="payamakio-stat-card">
            <div class="payamakio-stat-icon payamakio-stat-failed">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div>
                <div class="payamakio-stat-num text-rose"><?php echo esc_html( number_format( $stats['failed'] ) ); ?></div>
                <div class="payamakio-stat-desc">ارسال های ناموفق</div>
            </div>
        </div>

        <div class="payamakio-stat-card">
            <div class="payamakio-stat-icon payamakio-stat-today">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <div class="payamakio-stat-num text-amber"><?php echo esc_html( number_format( $stats['today'] ) ); ?></div>
                <div class="payamakio-stat-desc">ارسال های امروز</div>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <form method="post" action="" id="payamakio-settings-form">
        <?php wp_nonce_field( 'payamakio_settings_verify', 'payamakio_nonce' ); ?>
        <input type="hidden" name="payamakio_save_settings" value="1">

        <!-- TAB 1: GATEWAYS -->
        <div id="tab-gateways" class="payamakio-tab-content active">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">انتخاب و پیکربندی سامانه پیامک</h2>
                    <p class="payamakio-card-desc">سامانه پیامکی مورد نظر خود را انتخاب کرده و اطلاعات احراز هویت وب سرویس را وارد فرمایید.</p>
                </div>

                <div class="payamakio-field-group">
                    <label class="payamakio-label">سامانه پیامکی فعال</label>
                    <div class="payamakio-gateway-picker">
                        <label class="payamakio-gateway-option <?php echo ( 'ippanel' === $current_gateway ) ? 'selected' : ''; ?>">
                            <input type="radio" name="payamakio[gateway]" value="ippanel" <?php checked( $current_gateway, 'ippanel' ); ?>>
                            <span class="payamakio-gw-badge">پیشنهادی</span>
                            <span class="payamakio-gw-title">فراز اس ام اس / IPPanel</span>
                            <span class="payamakio-gw-desc">پشتیبانی از الگوهای متغیر و ارسال زیر ۳ ثانیه</span>
                        </label>

                        <label class="payamakio-gateway-option <?php echo ( 'kavenegar' === $current_gateway ) ? 'selected' : ''; ?>">
                            <input type="radio" name="payamakio[gateway]" value="kavenegar" <?php checked( $current_gateway, 'kavenegar' ); ?>>
                            <span class="payamakio-gw-title">کاوه نگار (Kavenegar)</span>
                            <span class="payamakio-gw-desc">وب سرویس اعتبارسنجی الگویی Lookup</span>
                        </label>

                        <label class="payamakio-gateway-option <?php echo ( 'melipayamak' === $current_gateway ) ? 'selected' : ''; ?>">
                            <input type="radio" name="payamakio[gateway]" value="melipayamak" <?php checked( $current_gateway, 'melipayamak' ); ?>>
                            <span class="payamakio-gw-title">ملی پیامک (MeliPayamak)</span>
                            <span class="payamakio-gw-desc">ارسال با خط خدماتی اشتراکی BaseService</span>
                        </label>
                    </div>
                </div>

                <!-- IPPanel Settings -->
                <div class="payamakio-sub-settings" id="gw-settings-ippanel" style="<?php echo ( 'ippanel' === $current_gateway ) ? '' : 'display:none;'; ?>">
                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="ippanel_apikey">کلید وب سرویس (API Key / Token)</label>
                            <input type="password" id="ippanel_apikey" name="payamakio[ippanel_apikey]" value="<?php echo esc_attr( $settings['ippanel_apikey'] ?? '' ); ?>" class="payamakio-input" placeholder="کلید دسترسی IPPanel Edge">
                            <span class="payamakio-hint">توکن دسترسی معتبر از منوی برنامه نویسان یا وب سرویس پنل IPPanel.</span>
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="ippanel_sender">شماره خط خدماتی / فرستنده</label>
                            <input type="text" id="ippanel_sender" name="payamakio[ippanel_sender]" value="<?php echo esc_attr( $settings['ippanel_sender'] ?? '+983000505' ); ?>" class="payamakio-input" placeholder="+983000505">
                            <span class="payamakio-hint">خط خدماتی اشتراکی پیشفرض: <strong>+983000505</strong> (دارای تاییدیه خدماتی و عبور از بلک لیست مخابرات).</span>
                        </div>
                    </div>
                    <?php if ( 'ippanel' === $current_gateway && false !== $balance ) : ?>
                        <div style="margin-top: 14px; padding: 10px 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                            <span>اتصال با IPPanel Edge برقرار است | موجودی حساب: <strong><?php echo esc_html( number_format( round( $balance ) ) ); ?> ریال</strong> (معادل <?php echo esc_html( number_format( round( $balance / 10 ) ) ); ?> تومان)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Kavenegar Settings -->
                <div class="payamakio-sub-settings" id="gw-settings-kavenegar" style="<?php echo ( 'kavenegar' === $current_gateway ) ? '' : 'display:none;'; ?>">
                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="kavenegar_apikey">کلید API کاوه نگار</label>
                            <input type="password" id="kavenegar_apikey" name="payamakio[kavenegar_apikey]" value="<?php echo esc_attr( $settings['kavenegar_apikey'] ?? '' ); ?>" class="payamakio-input" placeholder="API Key کاوه نگار">
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="kavenegar_sender">شماره خط اختصاصی (اختیاری)</label>
                            <input type="text" id="kavenegar_sender" name="payamakio[kavenegar_sender]" value="<?php echo esc_attr( $settings['kavenegar_sender'] ?? '' ); ?>" class="payamakio-input" placeholder="1000...">
                        </div>
                    </div>
                </div>

                <!-- MeliPayamak Settings -->
                <div class="payamakio-sub-settings" id="gw-settings-melipayamak" style="<?php echo ( 'melipayamak' === $current_gateway ) ? '' : 'display:none;'; ?>">
                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="melipayamak_username">نام کاربری ملی پیامک</label>
                            <input type="text" id="melipayamak_username" name="payamakio[melipayamak_username]" value="<?php echo esc_attr( $settings['melipayamak_username'] ?? '' ); ?>" class="payamakio-input">
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="melipayamak_password">کلمه عبور ملی پیامک</label>
                            <input type="password" id="melipayamak_password" name="payamakio[melipayamak_password]" value="<?php echo esc_attr( $settings['melipayamak_password'] ?? '' ); ?>" class="payamakio-input">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: ORDERS -->
        <div id="tab-orders" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">اطلاع رسانی وضعیت سفارشات</h2>
                    <p class="payamakio-card-desc">ارسال پیامک آنی با پترن خدماتی پس از تغییر وضعیت سفارش مشتری.</p>
                </div>

                <!-- Order Completed -->
                <div class="payamakio-box-section">
                    <div class="payamakio-box-header">
                        <div class="payamakio-box-title-wrap">
                            <span class="payamakio-status-bullet status-completed"></span>
                            <h3 class="payamakio-box-title">سفارش تکمیل شده (Completed)</h3>
                        </div>
                        <label class="payamakio-switch">
                            <input type="checkbox" name="payamakio[order_completed_active]" value="yes" <?php checked( $settings['order_completed_active'] ?? 'no', 'yes' ); ?>>
                            <span class="payamakio-slider"></span>
                        </label>
                    </div>

                    <?php Payamakio_Helper::render_default_pattern_box( 'order_completed', 'order_completed_pattern' ); ?>

                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="order_completed_pattern">کد پترن / الگو در پنل پیامک</label>
                            <input type="text" id="order_completed_pattern" name="payamakio[order_completed_pattern]" value="<?php echo esc_attr( $settings['order_completed_pattern'] ?? '' ); ?>" class="payamakio-input" placeholder="مثلا: 10450">
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label">ترتیب متغیرهای پترن (جدا شده با کاما انگلیسی)</label>
                            <input type="text" name="payamakio[order_completed_param]" value="<?php echo esc_attr( $settings['order_completed_param'] ?? 'name,order_id,amount' ); ?>" class="payamakio-input" placeholder="name,order_id,amount">
                            <span class="payamakio-hint">متغیرهای مجاز: name, order_id, amount, download, title</span>
                        </div>
                    </div>
                </div>

                <!-- Order Processing -->
                <div class="payamakio-box-section">
                    <div class="payamakio-box-header">
                        <div class="payamakio-box-title-wrap">
                            <span class="payamakio-status-bullet status-processing"></span>
                            <h3 class="payamakio-box-title">سفارش در حال پردازش (Processing)</h3>
                        </div>
                        <label class="payamakio-switch">
                            <input type="checkbox" name="payamakio[order_processing_active]" value="yes" <?php checked( $settings['order_processing_active'] ?? 'no', 'yes' ); ?>>
                            <span class="payamakio-slider"></span>
                        </label>
                    </div>

                    <?php Payamakio_Helper::render_default_pattern_box( 'order_processing', 'order_processing_pattern' ); ?>

                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="order_processing_pattern">کد پترن در پنل پیامک</label>
                            <input type="text" id="order_processing_pattern" name="payamakio[order_processing_pattern]" value="<?php echo esc_attr( $settings['order_processing_pattern'] ?? '' ); ?>" class="payamakio-input" placeholder="کد پترن">
                        </div>
                    </div>
                </div>

                <!-- Available Tags Documentation -->
                <div class="payamakio-tags-box">
                    <h4 class="payamakio-tags-title">شورتکدها و تگ های داینامیک قابل استفاده:</h4>
                    <div class="payamakio-tags-list">
                        <?php foreach ( Payamakio_Helper::get_available_tags_info() as $tag => $desc ) : ?>
                            <span class="payamakio-tag-item" title="<?php echo esc_attr( $desc ); ?>">
                                <code><?php echo esc_html( $tag ); ?></code>
                                <span class="payamakio-tag-desc"><?php echo esc_html( $desc ); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: CART RECOVERY -->
        <div id="tab-cart" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">بازیابی هوشمند سبدهای خرید رها شده</h2>
                    <p class="payamakio-card-desc">سفارشات پرداخت نشده را به صورت کاملا خودکار تشخیص داده و با ارسال لینک ۱ کلیکه پرداخت در پیامک، فروش را بازگردانید.</p>
                </div>

                <div class="payamakio-box-section">
                    <div class="payamakio-box-header">
                        <div class="payamakio-box-title-wrap">
                            <span class="payamakio-status-bullet status-cart"></span>
                            <h3 class="payamakio-box-title">فعال سازی اتوماسیون بازیابی سبد خرید</h3>
                        </div>
                        <label class="payamakio-switch">
                            <input type="checkbox" name="payamakio[cart_recovery_active]" value="yes" <?php checked( $settings['cart_recovery_active'] ?? 'no', 'yes' ); ?>>
                            <span class="payamakio-slider"></span>
                        </label>
                    </div>

                    <?php Payamakio_Helper::render_default_pattern_box( 'cart_recovery', 'cart_recovery_pattern' ); ?>

                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="cart_recovery_delay">مدت زمان انتظار پس از رها شدن سبد (دقیقه)</label>
                            <input type="number" id="cart_recovery_delay" name="payamakio[cart_recovery_delay]" value="<?php echo esc_attr( $settings['cart_recovery_delay'] ?? 30 ); ?>" min="5" max="1440" class="payamakio-input">
                            <span class="payamakio-hint">پیشنهادی: ۳۰ دقیقه پس از ایجاد سفارش ناموفق یا رها شده.</span>
                        </div>

                        <div class="payamakio-col">
                            <label class="payamakio-label" for="cart_recovery_pattern">کد پترن بازیابی سبد</label>
                            <input type="text" id="cart_recovery_pattern" name="payamakio[cart_recovery_pattern]" value="<?php echo esc_attr( $settings['cart_recovery_pattern'] ?? '' ); ?>" class="payamakio-input" placeholder="کد الگو در پنل">
                            <span class="payamakio-hint">متغیرهای ارسالی به پترن: name (نام خریدار) و link (لینک پرداخت ۱ کلیکه).</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: LEAD MAGNET -->
        <div id="tab-lead" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">موتور لید مگنت و هدیه سفارشات رایگان</h2>
                    <p class="payamakio-card-desc">به محض ثبت سفارش رایگان (0 تومانی)، پیامک خوش آمد گویی به همراه لینک دانلود اختصاصی و کد تخفیف ویژه خرید بعدی ارسال می شود.</p>
                </div>

                <div class="payamakio-box-section">
                    <div class="payamakio-box-header">
                        <div class="payamakio-box-title-wrap">
                            <span class="payamakio-status-bullet status-gift"></span>
                            <h3 class="payamakio-box-title">فعال سازی پیامک هدیه لید مگنت</h3>
                        </div>
                        <label class="payamakio-switch">
                            <input type="checkbox" name="payamakio[lead_magnet_active]" value="yes" <?php checked( $settings['lead_magnet_active'] ?? 'no', 'yes' ); ?>>
                            <span class="payamakio-slider"></span>
                        </label>
                    </div>

                    <?php Payamakio_Helper::render_default_pattern_box( 'lead_magnet', 'lead_magnet_pattern' ); ?>

                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="lead_magnet_pattern">کد پترن لید مگنت</label>
                            <input type="text" id="lead_magnet_pattern" name="payamakio[lead_magnet_pattern]" value="<?php echo esc_attr( $settings['lead_magnet_pattern'] ?? '' ); ?>" class="payamakio-input" placeholder="مثلا: 10452">
                            <span class="payamakio-hint">متغیرهای ارسالی: name (نام)، code (کد تخفیف)، link (لینک مستقیم فایل).</span>
                        </div>

                        <div class="payamakio-col">
                            <label class="payamakio-label" for="lead_magnet_coupon">کد کوپن هدیه ارسالی</label>
                            <input type="text" id="lead_magnet_coupon" name="payamakio[lead_magnet_coupon]" value="<?php echo esc_attr( $settings['lead_magnet_coupon'] ?? 'VIP30' ); ?>" class="payamakio-input" placeholder="VIP30">
                            <span class="payamakio-hint">کد کوپن تخفیفی که در ووکامرس تعریف کرده اید.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: ADMIN ALERTS -->
        <div id="tab-admin" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">اعلان های پیامکی مدیر فروشگاه</h2>
                    <p class="payamakio-card-desc">اطلاع رسانی فوری به شماره همراه مدیر سایت هنگام ثبت هر سفارش جدید.</p>
                </div>

                <div class="payamakio-box-section">
                    <div class="payamakio-box-header">
                        <div class="payamakio-box-title-wrap">
                            <span class="payamakio-status-bullet status-admin"></span>
                            <h3 class="payamakio-box-title">ارسال اعلان پیامکی به مدیر</h3>
                        </div>
                        <label class="payamakio-switch">
                            <input type="checkbox" name="payamakio[admin_alert_active]" value="yes" <?php checked( $settings['admin_alert_active'] ?? 'no', 'yes' ); ?>>
                            <span class="payamakio-slider"></span>
                        </label>
                    </div>

                    <?php Payamakio_Helper::render_default_pattern_box( 'admin_alert', 'admin_alert_pattern' ); ?>

                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="admin_alert_mobile">شماره موبایل مدیر سایت</label>
                            <input type="text" id="admin_alert_mobile" name="payamakio[admin_alert_mobile]" value="<?php echo esc_attr( $settings['admin_alert_mobile'] ?? '' ); ?>" class="payamakio-input" placeholder="09123456789">
                        </div>

                        <div class="payamakio-col">
                            <label class="payamakio-label" for="admin_alert_pattern">کد پترن اعلان مدیر</label>
                            <input type="text" id="admin_alert_pattern" name="payamakio[admin_alert_pattern]" value="<?php echo esc_attr( $settings['admin_alert_pattern'] ?? '' ); ?>" class="payamakio-input" placeholder="کد پترن">
                            <span class="payamakio-hint">متغیرهای ارسالی: order_id, total, name</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 6: LIVE SANDBOX -->
        <div id="tab-sandbox" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header">
                    <h2 class="payamakio-card-title">تست زنده ارسال پیامک پترن</h2>
                    <p class="payamakio-card-desc">اتصال درگاه و الگوی پیامک خود را به صورت زنده تست کرده و نتیجه تحویل را در لحظه مشاهده کنید.</p>
                </div>

                <div class="payamakio-sandbox-box">
                    <div class="payamakio-row">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="test_mobile">شماره موبایل گیرنده تست</label>
                            <input type="text" id="test_mobile" class="payamakio-input" placeholder="09123456789">
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="test_pattern">کد الگوی تست (اختیاری - در صورت خالی بودن پیامک وب سرویس ارسال می شود)</label>
                            <input type="text" id="test_pattern" class="payamakio-input" placeholder="مثلا 10450 (یا برای تست وب سرویس متنی خالی بگذارید)">
                        </div>
                    </div>

                    <div class="payamakio-row" style="margin-top: 15px;">
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="test_param1">مقدار متغیر اول (name)</label>
                            <input type="text" id="test_param1" class="payamakio-input" value="تست پیامکیو">
                        </div>
                        <div class="payamakio-col">
                            <label class="payamakio-label" for="test_param2">مقدار متغیر دوم (code / order_id)</label>
                            <input type="text" id="test_param2" class="payamakio-input" value="99999">
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; align-items: center; gap: 15px;">
                        <button type="button" id="payamakio-btn-test-send" class="payamakio-btn payamakio-btn-primary">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            ارسال پیامک آزمایشی زنده
                        </button>
                        <span id="payamakio-test-spinner" class="payamakio-spinner" style="display: none;"></span>
                    </div>

                    <div id="payamakio-test-result" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>
        </div>

        <!-- TAB 7: LOGS -->
        <div id="tab-logs" class="payamakio-tab-content">
            <div class="payamakio-card">
                <div class="payamakio-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 class="payamakio-card-title">تاریخچه و گزارش تحویل پیامک ها</h2>
                        <p class="payamakio-card-desc">آخرین ۵۰ پیامک ارسال شده توسط پیامکیو به همراه کد پیگیری و وضعیت تحویل.</p>
                    </div>
                    <button type="button" id="payamakio-btn-clear-logs" class="payamakio-btn payamakio-btn-ghost text-rose">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        پاکسازی تاریخچه
                    </button>
                </div>

                <div class="payamakio-table-wrap">
                    <table class="payamakio-table">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>زمان ارسال</th>
                                <th>شماره گیرنده</th>
                                <th>درگاه</th>
                                <th>نوع رویداد</th>
                                <th>کد الگو</th>
                                <th>وضعیت</th>
                                <th>توضیحات / کد رهگیری</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $logs ) ) : ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px; color: #94a3b8;">
                                        هنوز هیچ پیامی در تاریخچه ثبت نشده است. با ثبت اولین سفارش یا تست زنده، گزارشات اینجا نمایش داده می شوند.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $logs as $log ) : ?>
                                    <tr>
                                        <td>#<?php echo esc_html( $log['id'] ); ?></td>
                                        <td><?php echo esc_html( $log['created_at'] ); ?></td>
                                        <td dir="ltr" class="text-right"><code><?php echo esc_html( $log['mobile'] ); ?></code></td>
                                        <td><span class="payamakio-badge badge-gw"><?php echo esc_html( $log['gateway'] ); ?></span></td>
                                        <td><span class="payamakio-badge"><?php echo esc_html( $log['event_type'] ); ?></span></td>
                                        <td><code><?php echo esc_html( $log['pattern_code'] ?: '-' ); ?></code></td>
                                        <td>
                                            <?php if ( 'success' === $log['status'] ) : ?>
                                                <span class="payamakio-badge badge-success">موفق</span>
                                            <?php else : ?>
                                                <span class="payamakio-badge badge-failed">خطا</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="payamakio-log-msg"><?php echo esc_html( $log['response_message'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sticky Save Action Footer -->
        <div class="payamakio-footer-bar">
            <button type="submit" class="payamakio-btn payamakio-btn-primary payamakio-btn-lg">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                ذخیره تمام تغییرات
            </button>
            <div class="payamakio-footer-info">
                <span>پیامکیو - طراحی و اجرا شده با افتخار توسط <strong>آرین چقازردی</strong> در <strong>هکتورتیم</strong></span>
            </div>
        </div>

    </form>
</div>
