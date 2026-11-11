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
}
