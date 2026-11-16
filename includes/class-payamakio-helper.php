<?php
/**
 * Helper class for Payamakio
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Helper {

    /**
     * Convert Persian and Arabic numbers to standard English digits
     */
    public static function to_english_digits( $string ) {
        $persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
        $arabic  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
        $english = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );

        $string = str_replace( $persian, $english, (string) $string );
        return str_replace( $arabic, $english, $string );
    }

    /**
     * Convert English digits to Persian
     */
    public static function to_persian_digits( $string ) {
        $english = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
        $persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
        return str_replace( $english, $persian, (string) $string );
    }

    /**
     * Normalize Iranian mobile numbers to 09XXXXXXXXX format
     */
    public static function normalize_mobile( $mobile ) {
        $mobile = self::to_english_digits( trim( (string) $mobile ) );
        $mobile = preg_replace( '/[^0-9]/', '', $mobile );

        if ( substr( $mobile, 0, 4 ) === '0098' ) {
            $mobile = '0' . substr( $mobile, 4 );
        } elseif ( substr( $mobile, 0, 2 ) === '98' ) {
            $mobile = '0' . substr( $mobile, 2 );
        } elseif ( substr( $mobile, 0, 1 ) === '9' && strlen( $mobile ) === 10 ) {
            $mobile = '0' . $mobile;
        }

        return $mobile;
    }

    /**
     * Parse dynamic tags in templates
     */
    public static function parse_template( $template, $order = null, array $custom_data = array() ) {
        $tags = self::get_order_tags( $order, $custom_data );
        return str_replace( array_keys( $tags ), array_values( $tags ), $template );
    }

    /**
     * Build tag replacement map
     */
    public static function get_order_tags( $order = null, array $custom_data = array() ) {
        $site_title = get_bloginfo( 'name' );

        $tags = array(
            '{site_title}'     => $site_title,
            '{first_name}'     => '',
            '{last_name}'      => '',
            '{full_name}'      => '',
            '{order_id}'       => '',
            '{order_total}'    => '',
            '{items_list}'     => '',
            '{payment_link}'   => '',
            '{download_link}'  => '',
            '{coupon_code}'    => $custom_data['coupon_code'] ?? '',
        );

        if ( $order instanceof WC_Order ) {
            $first_name = $order->get_billing_first_name();
            $last_name  = $order->get_billing_last_name();
            $full_name  = trim( $first_name . ' ' . $last_name );
            if ( empty( $full_name ) ) {
                $full_name = 'مشتری گرامی';
            }

            // Items list
            $items = array();
            foreach ( $order->get_items() as $item ) {
                $items[] = $item->get_name();
            }
            $items_list = implode( '، ', $items );

            // Download link if downloadable
            $download_url = '';
            if ( $order->has_downloadable_item() && $order->is_download_permitted() ) {
                $downloads = $order->get_downloadable_items();
                if ( ! empty( $downloads ) ) {
                    $first_dl = reset( $downloads );
                    $download_url = $first_dl['download_url'] ?? '';
                }
            }
            if ( empty( $download_url ) ) {
                $download_url = $order->get_view_order_url();
            }

            $tags['{first_name}']    = ! empty( $first_name ) ? $first_name : 'کاربر';
            $tags['{last_name}']     = $last_name;
            $tags['{full_name}']     = $full_name;
            $tags['{order_id}']      = (string) $order->get_id();
            $tags['{order_total}']   = number_format( (float) $order->get_total() );
            $tags['{items_list}']    = $items_list;
            $tags['{payment_link}']  = $order->get_checkout_payment_url();
            $tags['{download_link}'] = $download_url;
        }

        // Merge any explicit overrides from custom_data
        foreach ( $custom_data as $key => $val ) {
            $tag_key = '{' . trim( $key, '{}' ) . '}';
            $tags[ $tag_key ] = (string) $val;
        }

        return $tags;
    }

    /**
     * Supported tags list for UI documentation
     */
    public static function get_available_tags_info() {
        return array(
            '{order_id}'      => 'شماره سفارش',
            '{first_name}'    => 'نام مشتری',
            '{last_name}'     => 'نام خانوادگی مشتری',
            '{full_name}'     => 'نام کامل مشتری',
            '{order_total}'   => 'مبلغ سفارش (تومان)',
            '{items_list}'    => 'عنوان محصولات خریداری شده',
            '{payment_link}'  => 'لینک مستقیم پرداخت سبد رها شده',
            '{download_link}' => 'لینک دانلود سفارش',
            '{coupon_code}'   => 'کد تخفیف هدیه',
            '{site_title}'    => 'عنوان سایت',
        );
    }

    /**
     * Get default standard pattern templates formatted for IPPanel rules
     */
    public static function get_default_patterns() {
        $site_name = get_bloginfo( 'name' );
        $host_url  = wp_parse_url( home_url(), PHP_URL_HOST );

        return array(
            'order_completed' => array(
                'title'       => 'سفارش تکمیل شده ' . $site_name,
                'description' => 'الگوی اطلاع رسانی تکمیل سفارش به خریدار در ' . $site_name,
                'message'     => 'سلام %name% عزیز، سفارش شما با شماره %order_id% به مبلغ %amount% تومان تکمیل شد. سپاس از همراهی شما: ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'name', 'type' => 'string', 'desc' => 'نام خریدار' ),
                    array( 'name' => 'order_id', 'type' => 'integer', 'desc' => 'شماره سفارش' ),
                    array( 'name' => 'amount', 'type' => 'string', 'desc' => 'مبلغ فاکتور' ),
                ),
                'tip'         => 'این الگو پس از پرداخت موفق و تکمیل سفارش برای مشتری ارسال می شود.',
            ),
            'order_processing' => array(
                'title'       => 'سفارش در حال انجام ' . $site_name,
                'description' => 'الگوی تایید پرداخت و آماده سازی سفارش در ' . $site_name,
                'message'     => 'سلام %name% عزیز، سفارش شماره %order_id% دریافت شد و در مرحله آماده سازی است. سپاس از خرید شما: ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'name', 'type' => 'string', 'desc' => 'نام خریدار' ),
                    array( 'name' => 'order_id', 'type' => 'integer', 'desc' => 'شماره سفارش' ),
                ),
                'tip'         => 'مناسب برای محصولاتی که پس از پرداخت نیاز به آماده سازی دارند.',
            ),
            'cart_recovery' => array(
                'title'       => 'بازیابی سبد خرید ' . $site_name,
                'description' => 'الگوی یادآوری و بازگرداندن خریداران سبد رها شده در ' . $site_name,
                'message'     => 'سلام %name% عزیز، خرید شما به مبلغ %total% تومان ناتمام ماند! جهت تکمیل سفارش کلیک کنید: %link% ' . $site_name,
                'variables'   => array(
                    array( 'name' => 'name', 'type' => 'string', 'desc' => 'نام خریدار' ),
                    array( 'name' => 'total', 'type' => 'string', 'desc' => 'مبلغ سبد خرید' ),
                    array( 'name' => 'link', 'type' => 'string', 'desc' => 'لینک مستقیم ادامه پرداخت' ),
                ),
                'tip'         => 'لینک یکتای تکمیل سفارش به صورت خودکار در متغیر link جایگذاری می شود.',
            ),
            'lead_magnet' => array(
                'title'       => 'لید مگنت و هدیه ' . $site_name,
                'description' => 'الگوی ارسال لینک فایل رایگان و کد تخفیف ویژه به مشتری در ' . $site_name,
                'message'     => 'سلام %name% عزیز، سفارش شما آماده است: %link% هدیه شما کد تخفیف اختصاصی: %code% ' . $site_name . ': ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'name', 'type' => 'string', 'desc' => 'نام خریدار' ),
                    array( 'name' => 'link', 'type' => 'string', 'desc' => 'لینک دانلود یا مشاهده' ),
                    array( 'name' => 'code', 'type' => 'string', 'desc' => 'کد تخفیف اختصاصی' ),
                ),
                'tip'         => 'بهترین روش برای تبدیل خریداران محصولات رایگان به مشتریان محصولات پولی.',
            ),
            'admin_alert' => array(
                'title'       => 'هشدار سفارش جدید مدیر ' . $site_name,
                'description' => 'الگوی اطلاع رسانی سریع سفارش ثبت شده به مدیر سایت در ' . $site_name,
                'message'     => 'مدیر گرامی، سفارش جدید شماره %order_id% به مبلغ %total% تومان توسط %name% در سایت ثبت شد: ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'order_id', 'type' => 'integer', 'desc' => 'شماره سفارش' ),
                    array( 'name' => 'total', 'type' => 'string', 'desc' => 'مبلغ سفارش' ),
                    array( 'name' => 'name', 'type' => 'string', 'desc' => 'نام خریدار' ),
                ),
                'tip'         => 'بلافاصله پس از ثبت سفارش به شماره موبایل مدیر سایت پیامک می شود.',
            ),
            'auth_otp' => array(
                'title'       => 'کد تایید ورود و عضویت ' . $site_name,
                'description' => 'الگوی ارسال کد تایید یکبار مصرف (OTP) جهت ورود و ثبت نام در ' . $site_name,
                'message'     => 'کد تایید شما: %code%' . "\n" . 'اعتبار: ۲ دقیقه' . "\n" . $site_name . ': ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'code', 'type' => 'string', 'desc' => 'کد تایید یکبار مصرف' ),
                ),
                'tip'         => 'این الگو برای ارسال آنی کد اعتبارسنجی ورود و عضویت کاربران استفاده می شود.',
            ),
            'auth_reset' => array(
                'title'       => 'بازیابی رمز عبور ' . $site_name,
                'description' => 'الگوی ارسال کد تایید بازیابی و تغییر رمز عبور در ' . $site_name,
                'message'     => 'کد تایید بازیابی رمز عبور: %code%' . "\n" . 'اعتبار: ۲ دقیقه' . "\n" . $site_name . ': ' . $host_url,
                'variables'   => array(
                    array( 'name' => 'code', 'type' => 'string', 'desc' => 'کد تایید بازیابی رمز' ),
                ),
                'tip'         => 'این الگو هنگام درخواست بازیابی کلمه عبور ارسال می شود.',
            ),
        );
    }

    /**
     * Render the UI block for default pattern with copy & auto-register features
     */
    public static function render_default_pattern_box( $pattern_key, $input_id ) {
        $patterns = self::get_default_patterns();
        if ( ! isset( $patterns[ $pattern_key ] ) ) {
            return;
        }

        $def = $patterns[ $pattern_key ];
        $settings = get_option( 'payamakio_settings', array() );
        $current_gateway = $settings['gateway'] ?? 'ippanel';
        ?>
        <div class="payamakio-default-pattern-card">
            <div class="payamakio-dpc-header">
                <div class="payamakio-dpc-title-wrap">
                    <span class="payamakio-dpc-badge">الگوی استاندارد پیشنهادی IPPanel</span>
                    <strong class="payamakio-dpc-title"><?php echo esc_html( $def['title'] ); ?></strong>
                </div>
                <div class="payamakio-dpc-actions">
                    <button type="button" class="payamakio-btn payamakio-btn-sm payamakio-btn-copy" data-target="def-pattern-<?php echo esc_attr( $pattern_key ); ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        کپی متن الگو
                    </button>
                    <?php if ( 'ippanel' === $current_gateway ) : ?>
                        <button type="button" class="payamakio-btn payamakio-btn-sm payamakio-btn-auto-reg" data-key="<?php echo esc_attr( $pattern_key ); ?>" data-target-input="<?php echo esc_attr( $input_id ); ?>">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            ثبت مستقیم در آی پی پنل
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="payamakio-dpc-body">
                <textarea id="def-pattern-<?php echo esc_attr( $pattern_key ); ?>" class="payamakio-pattern-textarea" rows="2"><?php echo esc_textarea( $def['message'] ); ?></textarea>
            </div>

            <div class="payamakio-dpc-footer">
                <div class="payamakio-var-chips">
                    <span class="payamakio-var-label">متغیرهای مجاز:</span>
                    <?php foreach ( $def['variables'] as $var ) : ?>
                        <span class="payamakio-var-pill" title="<?php echo esc_attr( $var['desc'] ); ?>">
                            <code>%<?php echo esc_html( $var['name'] ); ?>%</code>
                            <small>(<?php echo esc_html( $var['desc'] ); ?>)</small>
                        </span>
                    <?php endforeach; ?>
                </div>
                <p class="payamakio-dpc-tip">
                    <strong>راهنما:</strong> <?php echo esc_html( $def['tip'] ); ?> (می توانید متن الگو را در صورت تمایل ویرایش کرده و سپس کپی نمایید یا با دکمه ثبت مستقیم در سامانه آی پی پنل به ثبت برسانید).
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Format number with thousand separators and Persian digits
     */
    public static function format_number( $number ) {
        if ( ! is_numeric( $number ) ) {
            return self::to_persian_digits( (string) $number );
        }
        $formatted = number_format( (float) $number );
        return self::to_persian_digits( $formatted );
    }

    /**
     * Human readable Persian time difference without ZWNJ
     */
    public static function time_ago( $datetime ) {
        $timestamp = is_numeric( $datetime ) ? (int) $datetime : strtotime( $datetime );
        if ( ! $timestamp ) {
            return '';
        }

        $now  = current_time( 'timestamp' );
        $diff = max( 0, $now - $timestamp );

        if ( $diff < 10 ) {
            return 'همین الان';
        } elseif ( $diff < 60 ) {
            return self::to_persian_digits( $diff ) . ' ثانیه پیش';
        } elseif ( $diff < 3600 ) {
            $mins = max( 1, round( $diff / 60 ) );
            return self::to_persian_digits( $mins ) . ' دقیقه پیش';
        } elseif ( $diff < 86400 ) {
            $hours = max( 1, round( $diff / 3600 ) );
            return self::to_persian_digits( $hours ) . ' ساعت پیش';
        } else {
            $days = max( 1, round( $diff / 86400 ) );
            return self::to_persian_digits( $days ) . ' روز پیش';
        }
    }

    /**
     * Mask Iranian mobile number for privacy (e.g. 0933***4401)
     */
    public static function mask_mobile( $mobile ) {
        $clean = self::normalize_mobile( $mobile );
        if ( strlen( $clean ) === 11 ) {
            $masked = substr( $clean, 0, 4 ) . '***' . substr( $clean, 7, 4 );
            return self::to_persian_digits( $masked );
        }
        return self::to_persian_digits( $mobile );
    }

    /**
     * Mask email address for privacy (e.g. a***@gmail.com)
     */
    public static function mask_email( $email ) {
        if ( ! is_email( $email ) ) {
            return $email;
        }
        $parts = explode( '@', $email );
        $name  = $parts[0];
        $host  = $parts[1] ?? '';
        $len   = mb_strlen( $name );

        if ( $len <= 2 ) {
            $masked_name = substr( $name, 0, 1 ) . '*';
        } else {
            $masked_name = substr( $name, 0, 2 ) . str_repeat( '*', min( 4, $len - 2 ) );
        }

        return $masked_name . '@' . $host;
    }

    /**
     * Find WordPress user by Iranian mobile number
     * Checks username, payamakio_mobile, billing_phone, and digits_phone
     *
     * @param string $mobile
     * @return WP_User|null
     */
    public static function get_user_by_mobile( $mobile ) {
        $mobile = self::normalize_mobile( $mobile );
        if ( empty( $mobile ) ) {
            return null;
        }

        // 1. Check user_login
        $user = get_user_by( 'login', $mobile );
        if ( $user instanceof WP_User ) {
            return $user;
        }

        // 2. Check usermeta payamakio_mobile
        $users = get_users( array(
            'meta_key'   => 'payamakio_mobile',
            'meta_value' => $mobile,
            'number'     => 1,
        ) );
        if ( ! empty( $users ) && $users[0] instanceof WP_User ) {
            return $users[0];
        }

        // 3. Check WooCommerce billing_phone
        $users = get_users( array(
            'meta_key'   => 'billing_phone',
            'meta_value' => $mobile,
            'number'     => 1,
        ) );
        if ( ! empty( $users ) && $users[0] instanceof WP_User ) {
            return $users[0];
        }

        // 4. Check Digits digits_phone meta
        $users = get_users( array(
            'meta_key'   => 'digits_phone',
            'meta_value' => $mobile,
            'number'     => 1,
        ) );
        if ( ! empty( $users ) && $users[0] instanceof WP_User ) {
            return $users[0];
        }

        // 5. Check alternative format +98
        $alt = '+98' . substr( $mobile, 1 );
        $users = get_users( array(
            'meta_key'   => 'billing_phone',
            'meta_value' => $alt,
            'number'     => 1,
        ) );
        if ( ! empty( $users ) && $users[0] instanceof WP_User ) {
            return $users[0];
        }

        return null;
    }

    /**
     * SVG icon provider
     */
    public static function icon( $name, $size = 20, $class = '' ) {
        $s = (int) $size;
        $c = esc_attr( $class );
        $common = 'viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $c . '"';

        switch ( $name ) {
            case 'wallet':
                return '<svg ' . $common . '><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"></path><path d="M16 3H4a2 2 0 0 0-2 2v2"></path><circle cx="16" cy="14" r="1.5"></circle></svg>';

            case 'sms':
            case 'message':
                return '<svg ' . $common . '><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';

            case 'send':
                return '<svg ' . $common . '><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';

            case 'check':
                return '<svg ' . $common . '><polyline points="20 6 9 17 4 12"></polyline></svg>';

            case 'alert':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

            case 'clock':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>';

            case 'refresh':
                return '<svg ' . $common . '><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>';

            case 'settings':
                return '<svg ' . $common . '><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>';

            case 'cart':
                return '<svg ' . $common . '><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>';

            case 'box':
            case 'order':
                return '<svg ' . $common . '><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>';

            case 'gift':
                return '<svg ' . $common . '><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>';

            case 'bell':
                return '<svg ' . $common . '><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';

            case 'external':
                return '<svg ' . $common . '><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>';

            case 'chevron-left':
                return '<svg ' . $common . '><polyline points="15 18 9 12 15 6"></polyline></svg>';

            case 'key':
                return '<svg ' . $common . '><circle cx="7.5" cy="15.5" r="5.5"></circle><path d="m21 2-9.6 9.6"></path><path d="m15.5 7.5 3 3L22 7l-3-3"></path></svg>';

            case 'lock':
                return '<svg ' . $common . '><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';

            case 'unlock':
                return '<svg ' . $common . '><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>';

            case 'user':
                return '<svg ' . $common . '><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';

            case 'user-plus':
                return '<svg ' . $common . '><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>';

            case 'user-check':
                return '<svg ' . $common . '><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>';

            case 'phone':
                return '<svg ' . $common . '><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>';

            case 'mail':
                return '<svg ' . $common . '><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22 6 12 13 2 6"></polyline></svg>';

            case 'eye':
                return '<svg ' . $common . '><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';

            case 'eye-off':
                return '<svg ' . $common . '><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

            case 'shield':
                return '<svg ' . $common . '><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>';

            case 'close':
                return '<svg ' . $common . '><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

            case 'arrow-right':
                return '<svg ' . $common . '><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>';

            default:
                return '';
        }
    }
}
