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

            default:
                return '';
        }
    }
}
