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
}
