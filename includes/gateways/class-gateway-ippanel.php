<?php
/**
 * IPPanel / FarazSMS Gateway Driver
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Gateway_IPPanel implements Payamakio_Gateway_Interface {

    private $settings;
    private $api_key;
    private $sender;

    public function __construct( array $settings = array() ) {
        $this->settings = $settings;
        $this->api_key  = trim( $settings['ippanel_apikey'] ?? '' );
        $this->sender   = trim( $settings['ippanel_sender'] ?? '+983000505' );
        if ( empty( $this->sender ) ) {
            $this->sender = '+983000505';
        }
    }

    public function get_id() {
        return 'ippanel';
    }

    public function get_name() {
        return 'فراز اس ام اس / آی پی پنل (IPPanel)';
    }

    public function send_pattern( $to, $pattern_code, array $variables = array() ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'success'    => false,
                'message_id' => null,
                'error'      => 'کلید وب سرویس IPPanel تنظیم نشده است.',
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

        // Format variables to strings
        $formatted_vars = array();
        foreach ( $variables as $k => $v ) {
            $clean_key = trim( $k, '%{}' );
            $formatted_vars[ $clean_key ] = (string) $v;
        }

        $body = array(
            'pattern_code' => trim( (string) $pattern_code ),
            'originator'   => $this->sender,
            'recipient'    => $to,
            'values'       => $formatted_vars,
        );

        $response = wp_remote_post(
            'http://rest.ippanel.com/v1/messages/patterns/send',
            array(
                'headers' => array(
                    'Authorization' => 'AccessKey ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
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

        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );

        $is_success = false;
        $tracking_code = null;
        $error_msg = null;

        if ( isset( $data['data']['message_out_id'] ) ) {
            $is_success = true;
            $tracking_code = (string) $data['data']['message_out_id'];
        } elseif ( isset( $data['code'] ) && (int) $data['code'] === 0 ) {
            $is_success = true;
            $tracking_code = (string) ( $data['data']['message_id'] ?? 'OK' );
        } else {
            $error_msg = $data['message'] ?? ( $data['error_message'] ?? 'خطا در برقراری ارتباط با آی پی پنل' );
        }

        return array(
            'success'    => $is_success,
            'message_id' => $tracking_code,
            'error'      => $error_msg,
            'raw'        => $data,
        );
    }

    public function send_sms( $to, $message ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'success' => false,
                'error'   => 'کلید وب سرویس تنظیم نشده است.',
            );
        }

        $to = Payamakio_Helper::normalize_mobile( $to );
        $body = array(
            'originator' => $this->sender,
            'recipients' => array( $to ),
            'message'    => $message,
        );

        $response = wp_remote_post(
            'http://rest.ippanel.com/v1/messages',
            array(
                'headers' => array(
                    'Authorization' => 'AccessKey ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
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
        $is_success = isset( $data['data']['message_out_id'] ) || ( isset( $data['code'] ) && (int) $data['code'] === 0 );

        return array(
            'success'    => $is_success,
            'message_id' => $data['data']['message_out_id'] ?? null,
            'error'      => $data['message'] ?? null,
            'raw'        => $data,
        );
    }
}
