<?php
/**
 * Gateway Interface for Payamakio
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Payamakio_Gateway_Interface {

    /**
     * Get unique gateway identifier
     *
     * @return string
     */
    public function get_id();

    /**
     * Get human readable gateway title
     *
     * @return string
     */
    public function get_name();

    /**
     * Send pattern / OTP message
     *
     * @param string $to Recipient mobile number
     * @param string $pattern_code Pattern identifier or template code
     * @param array $variables Associative array of variable keys and values
     * @return array Array with keys 'success' (bool), 'message_id' (string|null), 'error' (string|null)
     */
    public function send_pattern( $to, $pattern_code, array $variables = array() );

    /**
     * Send plain text SMS (optional, falls back if supported)
     *
     * @param string $to Recipient mobile
     * @param string $message Text content
     * @return array
     */
    public function send_sms( $to, $message );

    /**
     * Get account credit / wallet balance
     *
     * @return float|false
     */
    public function get_balance();

    /**
     * Get external management portal URL
     *
     * @return string
     */
    public function get_panel_url();
}
