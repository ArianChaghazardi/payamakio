<?php
/**
 * Payamakio Authentication Card Template
 * Digits-style responsive SMS OTP login, registration, password login, and reset password card
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings      = get_option( 'payamakio_settings', array() );
$site_title    = get_bloginfo( 'name' );
$current_user  = wp_get_current_user();
$is_logged_in  = is_user_logged_in();

// Component configurations
$redirect_to      = ! empty( $args['redirect'] ) ? esc_url( $args['redirect'] ) : ( ! empty( $_GET['redirect_to'] ) ? esc_url( $_GET['redirect_to'] ) : '' );
$allow_password   = ( ( $settings['auth_allow_password'] ?? 'yes' ) === 'yes' );
$allow_reset      = ( ( $settings['auth_allow_reset'] ?? 'yes' ) === 'yes' );
$allow_register   = ( ( $settings['auth_allow_register'] ?? 'yes' ) === 'yes' );
$name_field       = $settings['auth_name_field'] ?? 'optional';
$email_field      = $settings['auth_email_field'] ?? 'optional';
$code_length      = absint( $settings['auth_code_length'] ?? 5 );
$code_length      = in_array( $code_length, array( 4, 5, 6 ), true ) ? $code_length : 5;
$cooldown_seconds = absint( $settings['auth_expiry'] ?? 120 );
$cooldown_seconds = $cooldown_seconds > 0 ? $cooldown_seconds : 120;

$default_tab = ! empty( $args['default_tab'] ) ? sanitize_key( $args['default_tab'] ) : ( $settings['auth_default_tab'] ?? 'otp' );
if ( ! in_array( $default_tab, array( 'otp', 'password', 'register' ), true ) ) {
    $default_tab = 'otp';
}
if ( 'password' === $default_tab && ! $allow_password ) {
    $default_tab = 'otp';
}

$my_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url();
$logout_url     = wp_logout_url( home_url() );
$in_modal       = ! empty( $args['in_modal'] );
$card_classes   = 'payamakio-auth-card' . ( $in_modal ? ' is-in-modal' : '' );
?>

<div class="<?php echo esc_attr( $card_classes ); ?>" dir="rtl" data-code-length="<?php echo esc_attr( $code_length ); ?>" data-cooldown="<?php echo esc_attr( $cooldown_seconds ); ?>">
    
    <?php if ( $is_logged_in ) : ?>
        <!-- Logged-in State Card -->
        <div class="payamakio-auth-logged-in">
            <div class="payamakio-auth-avatar-wrap">
                <?php echo get_avatar( $current_user->ID, 72 ); ?>
            </div>
            <h3 class="payamakio-auth-logged-title">خوش آمدید، <?php echo esc_html( $current_user->display_name ?: $current_user->user_login ); ?></h3>
            <p class="payamakio-auth-logged-desc">شما در حال حاضر با موفقیت وارد حساب کاربری خود در <?php echo esc_html( $site_title ); ?> شده اید.</p>
            <div class="payamakio-auth-logged-actions">
                <a href="<?php echo esc_url( $my_account_url ); ?>" class="payamakio-auth-btn payamakio-auth-btn-primary">
                    <?php echo Payamakio_Helper::icon( 'user', 16 ); ?>
                    <span>ورود به حساب کاربری</span>
                </a>
                <a href="<?php echo esc_url( $logout_url ); ?>" class="payamakio-auth-btn payamakio-auth-btn-outline">
                    <?php echo Payamakio_Helper::icon( 'unlock', 16 ); ?>
                    <span>خروج از حساب</span>
                </a>
            </div>
        </div>

    <?php else : ?>
        <!-- Guest Authentication Card -->
        <div class="payamakio-auth-header">
            <div class="payamakio-auth-logo-badge">
                <?php echo Payamakio_Helper::icon( 'phone', 24 ); ?>
            </div>
            <h2 id="payamakio-auth-modal-title" class="payamakio-auth-title">ورود و عضویت در <?php echo esc_html( $site_title ); ?></h2>
            <p class="payamakio-auth-subtitle">احراز هویت سریع، امن و یکپارچه با شماره موبایل</p>
        </div>

        <!-- Tab Switcher Navigation -->
        <div class="payamakio-auth-tabs">
            <button type="button" class="payamakio-auth-tab-btn <?php echo ( 'otp' === $default_tab ) ? 'is-active' : ''; ?>" data-view="otp">
                <?php echo Payamakio_Helper::icon( 'sms', 15 ); ?>
                <span>ورود پیامکی</span>
            </button>
            <?php if ( $allow_password ) : ?>
                <button type="button" class="payamakio-auth-tab-btn <?php echo ( 'password' === $default_tab ) ? 'is-active' : ''; ?>" data-view="password">
                    <?php echo Payamakio_Helper::icon( 'key', 15 ); ?>
                    <span>ورود با رمز عبور</span>
                </button>
            <?php endif; ?>
            <?php if ( $allow_register ) : ?>
                <button type="button" class="payamakio-auth-tab-btn <?php echo ( 'register' === $default_tab ) ? 'is-active' : ''; ?>" data-view="register">
                    <?php echo Payamakio_Helper::icon( 'user-plus', 15 ); ?>
                    <span>ثبت نام جدید</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Global Alert Notification Box -->
        <div class="payamakio-auth-alert is-hidden" style="display: none !important;">
            <div class="payamakio-auth-alert-icon"></div>
            <div class="payamakio-auth-alert-text"></div>
        </div>

        <div class="payamakio-auth-body">

            <!-- VIEW 1: OTP AUTHENTICATION (LOGIN / QUICK AUTO-REGISTER) -->
            <div class="payamakio-auth-view payamakio-view-otp <?php echo ( 'otp' === $default_tab ) ? 'is-active' : ''; ?>">
                
                <!-- Step 1: Mobile Phone Number Input -->
                <form class="payamakio-auth-form payamakio-form-otp-send" method="post" autocomplete="on">
                    <input type="hidden" name="mode" value="auto" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
                    
                    <div class="payamakio-auth-field">
                        <label for="payamakio-otp-mobile" class="payamakio-auth-label">شماره موبایل خود را وارد نمایید:</label>
                        <div class="payamakio-auth-input-wrap payamakio-phone-input-wrap">
                            <span class="payamakio-country-badge" title="پیش شماره ایران">
                                <span class="payamakio-flag-iran">🇮🇷</span>
                                <span class="payamakio-prefix" dir="ltr">+98</span>
                            </span>
                            <input type="tel" id="payamakio-otp-mobile" name="mobile" class="payamakio-auth-input payamakio-input-mobile" placeholder="09123456789" inputmode="numeric" maxlength="11" required dir="ltr" />
                        </div>
                        <span class="payamakio-auth-hint">کد تایید ۵ رقمی به این شماره ارسال خواهد شد.</span>
                    </div>

                    <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                        <span class="payamakio-btn-text">ارسال کد تایید</span>
                        <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'send', 16 ); ?></span>
                        <span class="payamakio-spinner"></span>
                    </button>
                </form>

                <!-- Step 2: OTP Verification Digit Boxes -->
                <form class="payamakio-auth-form payamakio-form-otp-verify is-hidden" method="post" style="display: none !important;" autocomplete="off">
                    <input type="hidden" name="mobile" class="payamakio-target-mobile" value="" />
                    <input type="hidden" name="mode" class="payamakio-target-mode" value="auto" />
                    <input type="hidden" name="first_name" class="payamakio-target-fname" value="" />
                    <input type="hidden" name="last_name" class="payamakio-target-lname" value="" />
                    <input type="hidden" name="email" class="payamakio-target-email" value="" />
                    <input type="hidden" name="password" class="payamakio-target-pwd" value="" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

                    <div class="payamakio-auth-step2-header">
                        <div class="payamakio-auth-sent-info">
                            <span>کد تایید به شماره </span>
                            <strong class="payamakio-auth-sent-number" dir="ltr"></strong>
                            <span> ارسال گردید.</span>
                        </div>
                        <button type="button" class="payamakio-auth-btn-edit-phone">
                            <?php echo Payamakio_Helper::icon( 'close', 12 ); ?>
                            <span>ویرایش شماره</span>
                        </button>
                    </div>

                    <div class="payamakio-auth-field payamakio-digits-field">
                        <label class="payamakio-auth-label">کد تایید پیامک شده را وارد فرمایید:</label>
                        <div class="payamakio-digit-inputs" dir="ltr">
                            <?php for ( $i = 1; $i <= $code_length; $i++ ) : ?>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" class="payamakio-digit-box" maxlength="1" data-index="<?php echo esc_attr( $i ); ?>" autocomplete="one-time-code" required />
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="otp_code" class="payamakio-full-otp-input" value="" />
                    </div>

                    <!-- Resend Timer -->
                    <div class="payamakio-auth-resend-row">
                        <div class="payamakio-auth-timer">
                            <?php echo Payamakio_Helper::icon( 'clock', 14 ); ?>
                            <span>ارسال مجدد کد پس از </span>
                            <strong class="payamakio-timer-countdown">02:00</strong>
                        </div>
                        <button type="button" class="payamakio-auth-btn-resend is-hidden" style="display: none !important;">
                            <?php echo Payamakio_Helper::icon( 'refresh', 13 ); ?>
                            <span>ارسال مجدد کد تایید</span>
                        </button>
                    </div>

                    <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                        <span class="payamakio-btn-text">تایید و ورود به سایت</span>
                        <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'check', 16 ); ?></span>
                        <span class="payamakio-spinner"></span>
                    </button>
                </form>

            </div>

            <?php if ( $allow_password ) : ?>
                <!-- VIEW 2: PASSWORD LOGIN -->
                <div class="payamakio-auth-view payamakio-view-password <?php echo ( 'password' === $default_tab ) ? 'is-active' : ''; ?>">
                    <form class="payamakio-auth-form payamakio-form-password-login" method="post" autocomplete="on">
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

                        <div class="payamakio-auth-field">
                            <label for="payamakio-pwd-log" class="payamakio-auth-label">شماره موبایل، نام کاربری یا ایمیل:</label>
                            <div class="payamakio-auth-input-wrap">
                                <span class="payamakio-input-icon"><?php echo Payamakio_Helper::icon( 'user', 16 ); ?></span>
                                <input type="text" id="payamakio-pwd-log" name="log" class="payamakio-auth-input" placeholder="09123456789 یا نام کاربری" required dir="ltr" />
                            </div>
                        </div>

                        <div class="payamakio-auth-field">
                            <div class="payamakio-label-row">
                                <label for="payamakio-pwd-pass" class="payamakio-auth-label">رمز عبور:</label>
                                <?php if ( $allow_reset ) : ?>
                                    <button type="button" class="payamakio-btn-switch-reset">رمز عبور را فراموش کرده اید؟</button>
                                <?php endif; ?>
                            </div>
                            <div class="payamakio-auth-input-wrap">
                                <span class="payamakio-input-icon"><?php echo Payamakio_Helper::icon( 'lock', 16 ); ?></span>
                                <input type="password" id="payamakio-pwd-pass" name="pwd" class="payamakio-auth-input payamakio-pwd-input" placeholder="کلمه عبور حساب کاربری" required dir="ltr" />
                                <button type="button" class="payamakio-pwd-toggle" title="نمایش/پنهان کردن رمز عبور">
                                    <?php echo Payamakio_Helper::icon( 'eye', 16 ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="payamakio-auth-options-row">
                            <label class="payamakio-auth-checkbox-label">
                                <input type="checkbox" name="remember" value="forever" checked />
                                <span>مرا به خاطر بسپار</span>
                            </label>
                        </div>

                        <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                            <span class="payamakio-btn-text">ورود به حساب کاربری</span>
                            <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'arrow-right', 16 ); ?></span>
                            <span class="payamakio-spinner"></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ( $allow_register ) : ?>
                <!-- VIEW 3: NEW USER REGISTRATION -->
                <div class="payamakio-auth-view payamakio-view-register <?php echo ( 'register' === $default_tab ) ? 'is-active' : ''; ?>">
                    <form class="payamakio-auth-form payamakio-form-register" method="post" autocomplete="on">
                        <input type="hidden" name="mode" value="register" />
                        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

                        <?php if ( 'disabled' !== $name_field ) : ?>
                            <div class="payamakio-auth-row">
                                <div class="payamakio-auth-field payamakio-col-6">
                                    <label for="payamakio-reg-fname" class="payamakio-auth-label">نام <?php echo ( 'required' === $name_field ) ? '<span class="req">*</span>' : ''; ?>:</label>
                                    <div class="payamakio-auth-input-wrap">
                                        <input type="text" id="payamakio-reg-fname" name="first_name" class="payamakio-auth-input" placeholder="مثال: علی" <?php echo ( 'required' === $name_field ) ? 'required' : ''; ?> />
                                    </div>
                                </div>
                                <div class="payamakio-auth-field payamakio-col-6">
                                    <label for="payamakio-reg-lname" class="payamakio-auth-label">نام خانوادگی <?php echo ( 'required' === $name_field ) ? '<span class="req">*</span>' : ''; ?>:</label>
                                    <div class="payamakio-auth-input-wrap">
                                        <input type="text" id="payamakio-reg-lname" name="last_name" class="payamakio-auth-input" placeholder="مثال: محمدی" <?php echo ( 'required' === $name_field ) ? 'required' : ''; ?> />
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="payamakio-auth-field">
                            <label for="payamakio-reg-mobile" class="payamakio-auth-label">شماره موبایل (جهت دریافت کد تایید) <span class="req">*</span>:</label>
                            <div class="payamakio-auth-input-wrap payamakio-phone-input-wrap">
                                <span class="payamakio-country-badge" title="پیش شماره ایران">
                                    <span class="payamakio-flag-iran">🇮🇷</span>
                                    <span class="payamakio-prefix" dir="ltr">+98</span>
                                </span>
                                <input type="tel" id="payamakio-reg-mobile" name="mobile" class="payamakio-auth-input payamakio-input-mobile" placeholder="09123456789" inputmode="numeric" maxlength="11" required dir="ltr" />
                            </div>
                        </div>

                        <?php if ( 'optional' === $email_field ) : ?>
                            <div class="payamakio-auth-field">
                                <label for="payamakio-reg-email" class="payamakio-auth-label">آدرس ایمیل (اختیاری):</label>
                                <div class="payamakio-auth-input-wrap">
                                    <span class="payamakio-input-icon"><?php echo Payamakio_Helper::icon( 'mail', 16 ); ?></span>
                                    <input type="email" id="payamakio-reg-email" name="email" class="payamakio-auth-input" placeholder="info@example.com" dir="ltr" />
                                </div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                            <span class="payamakio-btn-text">دریافت کد تایید و عضویت</span>
                            <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'user-check', 16 ); ?></span>
                            <span class="payamakio-spinner"></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ( $allow_reset ) : ?>
                <!-- VIEW 4: FORGOT PASSWORD RESET FLOW -->
                <div class="payamakio-auth-view payamakio-view-reset">
                    <!-- Reset Step 1: Request OTP -->
                    <form class="payamakio-auth-form payamakio-form-reset-send" method="post" autocomplete="on">
                        <input type="hidden" name="mode" value="reset" />

                        <div class="payamakio-auth-field">
                            <label for="payamakio-reset-mobile" class="payamakio-auth-label">شماره موبایل ثبت شده در حساب خود را وارد نمایید:</label>
                            <div class="payamakio-auth-input-wrap payamakio-phone-input-wrap">
                                <span class="payamakio-country-badge">
                                    <span class="payamakio-flag-iran">🇮🇷</span>
                                    <span class="payamakio-prefix" dir="ltr">+98</span>
                                </span>
                                <input type="tel" id="payamakio-reset-mobile" name="mobile" class="payamakio-auth-input payamakio-input-mobile" placeholder="09123456789" inputmode="numeric" maxlength="11" required dir="ltr" />
                            </div>
                            <span class="payamakio-auth-hint">کد تایید بازیابی رمز عبور به این شماره پیامک می شود.</span>
                        </div>

                        <div class="payamakio-auth-sub-actions">
                            <button type="button" class="payamakio-btn-back-to-login">
                                <?php echo Payamakio_Helper::icon( 'arrow-right', 13 ); ?>
                                <span>بازگشت به صفحه ورود</span>
                            </button>
                        </div>

                        <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                            <span class="payamakio-btn-text">ارسال کد بازیابی رمز</span>
                            <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'send', 16 ); ?></span>
                            <span class="payamakio-spinner"></span>
                        </button>
                    </form>

                    <!-- Reset Step 2: Verify OTP and Enter New Password -->
                    <form class="payamakio-auth-form payamakio-form-reset-verify is-hidden" method="post" style="display: none !important;" autocomplete="off">
                        <input type="hidden" name="mobile" class="payamakio-target-reset-mobile" value="" />

                        <div class="payamakio-auth-field">
                            <label for="payamakio-reset-code" class="payamakio-auth-label">کد تایید دریافت شده:</label>
                            <div class="payamakio-auth-input-wrap">
                                <span class="payamakio-input-icon"><?php echo Payamakio_Helper::icon( 'key', 16 ); ?></span>
                                <input type="text" id="payamakio-reset-code" name="otp_code" class="payamakio-auth-input" placeholder="کد پیامک شده" inputmode="numeric" required dir="ltr" />
                            </div>
                        </div>

                        <div class="payamakio-auth-field">
                            <label for="payamakio-new-pass" class="payamakio-auth-label">کلمه عبور جدید:</label>
                            <div class="payamakio-auth-input-wrap">
                                <span class="payamakio-input-icon"><?php echo Payamakio_Helper::icon( 'lock', 16 ); ?></span>
                                <input type="password" id="payamakio-new-pass" name="new_password" class="payamakio-auth-input payamakio-pwd-input" placeholder="حداقل ۸ کاراکتر" minlength="6" required dir="ltr" />
                                <button type="button" class="payamakio-pwd-toggle" title="نمایش/پنهان کردن رمز عبور">
                                    <?php echo Payamakio_Helper::icon( 'eye', 16 ); ?>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="payamakio-auth-submit payamakio-btn-primary">
                            <span class="payamakio-btn-text">ذخیره رمز جدید و ورود</span>
                            <span class="payamakio-btn-icon"><?php echo Payamakio_Helper::icon( 'check', 16 ); ?></span>
                            <span class="payamakio-spinner"></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>

        <div class="payamakio-auth-footer">
            <span class="payamakio-auth-secured">
                <?php echo Payamakio_Helper::icon( 'shield', 13 ); ?>
                <span>سامانه امن احراز هویت پیامکیو</span>
            </span>
        </div>

    <?php endif; ?>

</div>
