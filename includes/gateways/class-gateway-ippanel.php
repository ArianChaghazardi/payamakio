<?php
/**
 * IPPanel / FarazSMS Edge API Gateway Driver
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
    private $base_url = 'https://edge.ippanel.com/v1';

    public function __construct( array $settings = array() ) {
        $this->settings = $settings;
        $this->api_key  = trim( $settings['ippanel_apikey'] ?? '' );
        $sender_val     = trim( $settings['ippanel_sender'] ?? '+983000505' );
        $this->sender   = empty( $sender_val ) ? '+983000505' : $sender_val;
    }

    public function get_id() {
        return 'ippanel';
    }

    public function get_name() {
        return 'فراز اس ام اس / آی پی پنل (IPPanel Edge)';
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
            $clean_key = trim( (string) $k, '%{}' );
            $formatted_vars[ $clean_key ] = (string) $v;
        }

        $body = array(
            'sending_type' => 'pattern',
            'from_number'  => $this->sender,
            'code'         => trim( (string) $pattern_code ),
            'recipients'   => array( $to ),
            'params'       => $formatted_vars,
        );

        $response = wp_remote_post(
            $this->base_url . '/api/send',
            array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 20,
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

        if ( ! empty( $data['data']['message_outbox_ids'] ) && is_array( $data['data']['message_outbox_ids'] ) ) {
            $is_success = true;
            $tracking_code = (string) $data['data']['message_outbox_ids'][0];
        } elseif ( ! empty( $data['meta']['status'] ) && true === $data['meta']['status'] ) {
            $is_success = true;
            $tracking_code = 'OK';
        } else {
            $error_msg = $data['meta']['message'] ?? ( $data['message'] ?? 'خطا در ارسال پیامک الگوی آی پی پنل' );
            if ( ! empty( $data['meta']['errors'] ) && is_array( $data['meta']['errors'] ) ) {
                $flat = array();
                foreach ( $data['meta']['errors'] as $err_field => $err_list ) {
                    $flat[] = is_array( $err_list ) ? implode( ' - ', $err_list ) : $err_list;
                }
                if ( ! empty( $flat ) ) {
                    $error_msg .= ' (' . implode( ' | ', $flat ) . ')';
                }
            }
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
                'success'    => false,
                'message_id' => null,
                'error'      => 'کلید وب سرویس تنظیم نشده است.',
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

        $body = array(
            'sending_type' => 'webservice',
            'from_number'  => $this->sender,
            'message'      => $message,
            'params'       => array(
                'recipients' => array( $to ),
            ),
        );

        $response = wp_remote_post(
            $this->base_url . '/api/send',
            array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 20,
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

        if ( ! empty( $data['data']['message_outbox_ids'] ) && is_array( $data['data']['message_outbox_ids'] ) ) {
            $is_success = true;
            $tracking_code = (string) $data['data']['message_outbox_ids'][0];
        } elseif ( ! empty( $data['meta']['status'] ) && true === $data['meta']['status'] ) {
            $is_success = true;
            $tracking_code = 'OK';
        } else {
            $error_msg = $data['meta']['message'] ?? ( $data['message'] ?? 'خطا در ارسال پیامک وب سرویس' );
        }

        return array(
            'success'    => $is_success,
            'message_id' => $tracking_code,
            'error'      => $error_msg,
            'raw'        => $data,
        );
    }

    public function get_balance() {
        if ( empty( $this->api_key ) ) {
            return false;
        }

        $response = wp_remote_get(
            $this->base_url . '/api/payment/credit/mine',
            array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                ),
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['data']['credit'] ) ) {
            return (float) $data['data']['credit'];
        }

        return false;
    }

    public function create_pattern( $title, $description, $message, array $variables = array(), $website = '' ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'success' => false,
                'error'   => 'کلید وب سرویس آی پی پنل تنظیم نشده است.',
            );
        }

        if ( empty( $website ) ) {
            $website = home_url();
        }

        $formatted_vars = array();
        foreach ( $variables as $v ) {
            $formatted_vars[] = array(
                'name' => trim( (string) $v['name'], '%{}' ),
                'type' => $v['type'] ?? 'string',
            );
        }

        $body = array(
            'title'       => $title,
            'description' => $description,
            'is_share'    => false,
            'message'     => $message,
            'website'     => $website,
            'variable'    => $formatted_vars,
        );

        $response = wp_remote_post(
            $this->base_url . '/api/patterns/normal',
            array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 20,
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message(),
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['data']['pattern_code'] ) ) {
            return array(
                'success'      => true,
                'pattern_code' => $data['data']['pattern_code'],
                'status'       => $data['data']['pattern_status_fa'] ?? 'در انتظار تایید',
                'raw'          => $data,
            );
        }

        $error = $data['meta']['message'] ?? ( $data['message'] ?? 'خطا در ثبت الگو در آی پی پنل' );
        if ( ! empty( $data['meta']['errors'] ) && is_array( $data['meta']['errors'] ) ) {
            $err_parts = array();
            foreach ( $data['meta']['errors'] as $err_arr ) {
                $err_parts[] = is_array( $err_arr ) ? implode( ' - ', $err_arr ) : $err_arr;
            }
            if ( ! empty( $err_parts ) ) {
                $error .= ' (' . implode( ' | ', $err_parts ) . ')';
            }
        }

        return array(
            'success' => false,
            'error'   => $error,
            'raw'     => $data,
        );
    }

    public function get_patterns() {
        if ( empty( $this->api_key ) ) {
            return false;
        }

        $response = wp_remote_get(
            $this->base_url . '/api/patterns?per_page=50',
            array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['data'] ?? false;
    }
}
