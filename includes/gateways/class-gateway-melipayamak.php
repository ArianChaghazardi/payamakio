<?php
/**
 * MeliPayamak Gateway Driver
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Gateway_MeliPayamak implements Payamakio_Gateway_Interface {

    private $settings;
    private $username;
    private $password;
    private $sender;

    public function __construct( array $settings = array() ) {
        $this->settings = $settings;
        $this->username = trim( $settings['melipayamak_username'] ?? '' );
        $this->password = trim( $settings['melipayamak_password'] ?? '' );
        $this->sender   = trim( $settings['melipayamak_sender'] ?? '' );
    }

    public function get_id() {
        return 'melipayamak';
    }

    public function get_name() {
        return 'ملی پیامک (MeliPayamak)';
    }

    public function send_pattern( $to, $pattern_code, array $variables = array() ) {
        if ( empty( $this->username ) || empty( $this->password ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => 'نام کاربری یا رمز عبور ملی پیامک تنظیم نشده است.',
            );
        }

        $to = Payamakio_Helper::normalize_mobile( $to );
        if ( empty( $to ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => 'شماره موبایل گیرنده نامعتبر است.',
            );
        }

        // MeliPayamak BaseServiceNumber expects text as array of tokens separated by ';'
        $tokens = array_values( $variables );
        $text = implode( ';', $tokens );

        $body = array(
            'username' => $this->username,
            'password' => $this->password,
            'text'     => $text,
            'to'       => $to,
            'bodyId'   => (int) $pattern_code,
        );

        $response = wp_remote_post(
            'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
            array(
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => $response->get_error_message(),
                'raw'        => $response->get_error_message(),
            );
        }

        $res_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $res_body, true );

        $recId = $data['Value'] ?? null;
        $is_success = ( is_numeric( $recId ) && (float) $recId > 1000 );

        return array(
            'success'    => $is_success,
            'message_id' => $is_success ? (string) $recId : null,
            'error'      => $is_success ? null : ( $data['StrRetStatus'] ?? 'خطا در وب سرویس خدماتی ملی پیامک' ),
            'raw'        => $data,
        );
    }

    public function send_sms( $to, $message ) {
        if ( empty( $this->username ) || empty( $this->password ) ) {
            return array(
                'success' => false,
                'error'   => 'اطلاعات کاربری ملی پیامک وارد نشده است.',
            );
        }

        $to = Payamakio_Helper::normalize_mobile( $to );
        $body = array(
            'username' => $this->username,
            'password' => $this->password,
            'to'       => $to,
            'from'     => $this->sender,
            'text'     => $message,
            'isFlash'  => false,
        );

        $response = wp_remote_post(
            'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
            array(
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $recId = $data['Value'] ?? null;
        $is_success = ( is_numeric( $recId ) && (float) $recId > 1000 );

        return array(
            'success'    => $is_success,
            'message_id' => $is_success ? (string) $recId : null,
            'error'      => $is_success ? null : ( $data['StrRetStatus'] ?? null ),
            'raw'        => $data,
        );
    }

    public function get_balance() {
        if ( empty( $this->username ) || empty( $this->password ) ) {
            return false;
        }

        $body = array(
            'username' => $this->username,
            'password' => $this->password,
        );

        $response = wp_remote_post(
            'https://rest.payamak-panel.com/api/SendSMS/GetCredit',
            array(
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['Value'] ) && is_numeric( $data['Value'] ) ) {
            return (float) $data['Value'];
        }

        return false;
    }

    public function get_panel_url() {
        return 'https://melipayamak.ir';
    }
}
