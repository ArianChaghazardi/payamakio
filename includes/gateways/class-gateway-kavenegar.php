<?php
/**
 * Kavenegar Gateway Driver
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Gateway_Kavenegar implements Payamakio_Gateway_Interface {

    private $settings;
    private $api_key;
    private $sender;

    public function __construct( array $settings = array() ) {
        $this->settings = $settings;
        $this->api_key  = trim( $settings['kavenegar_apikey'] ?? '' );
        $this->sender   = trim( $settings['kavenegar_sender'] ?? '' );
    }

    public function get_id() {
        return 'kavenegar';
    }

    public function get_name() {
        return 'کاوه نگار (Kavenegar)';
    }

    public function send_pattern( $to, $pattern_code, array $variables = array() ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => 'کلید API کاوه نگار تنظیم نشده است.',
            );
        }

        $to = Payamakio_Helper::normalize_mobile( $to );
        if ( empty( $to ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => 'شماره موبایل گیرنده معتبر نیست.',
            );
        }

        // Kavenegar expects token, token2, token3, etc.
        $params = array(
            'receptor' => $to,
            'template' => trim( (string) $pattern_code ),
        );

        $keys = array_values( $variables );
        if ( isset( $keys[0] ) ) {
            $params['token'] = str_replace( ' ', '-', (string) $keys[0] );
        }
        if ( isset( $keys[1] ) ) {
            $params['token2'] = str_replace( ' ', '-', (string) $keys[1] );
        }
        if ( isset( $keys[2] ) ) {
            $params['token3'] = str_replace( ' ', '-', (string) $keys[2] );
        }
        if ( isset( $keys[3] ) ) {
            $params['token10'] = str_replace( ' ', '-', (string) $keys[3] );
        }
        if ( isset( $keys[4] ) ) {
            $params['token20'] = str_replace( ' ', '-', (string) $keys[4] );
        }

        $url = sprintf( 'https://api.kavenegar.com/v1/%s/verify/lookup.json', $this->api_key );

        $response = wp_remote_post(
            $url,
            array(
                'body'    => $params,
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

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $status = $data['return']['status'] ?? 0;
        $is_success = ( 200 === (int) $status );
        $message_id = null;

        if ( $is_success && isset( $data['entries'][0]['messageid'] ) ) {
            $message_id = (string) $data['entries'][0]['messageid'];
        }

        return array(
            'success'    => $is_success,
            'message_id' => $message_id,
            'error'      => $is_success ? null : ( $data['return']['message'] ?? 'خطا در وب سرویس کاوه نگار' ),
            'raw'        => $data,
        );
    }

    public function send_sms( $to, $message ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'success' => false,
                'error'   => 'کلید API کاوه نگار وارد نشده است.',
            );
        }

        $to = Payamakio_Helper::normalize_mobile( $to );
        $url = sprintf( 'https://api.kavenegar.com/v1/%s/sms/send.json', $this->api_key );

        $params = array(
            'receptor' => $to,
            'message'  => $message,
        );
        if ( ! empty( $this->sender ) ) {
            $params['sender'] = $this->sender;
        }

        $response = wp_remote_post(
            $url,
            array(
                'body'    => $params,
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
        $status = $data['return']['status'] ?? 0;

        return array(
            'success'    => ( 200 === (int) $status ),
            'message_id' => $data['entries'][0]['messageid'] ?? null,
            'error'      => $data['return']['message'] ?? null,
            'raw'        => $data,
        );
    }
}
