<?php
/**
 * Database Logger for Payamakio
 *
 * @package Payamakio
 * @author آرین چقازردی
 * @developer هکتورتیم
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payamakio_Logger {

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'payamakio_logs';
    }

    public static function create_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            mobile varchar(20) NOT NULL,
            gateway varchar(50) NOT NULL,
            event_type varchar(50) NOT NULL DEFAULT 'custom',
            pattern_code varchar(100) DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            response_message text,
            raw_response longtext,
            PRIMARY KEY  (id),
            KEY mobile (mobile),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function check_table() {
        if ( get_option( 'payamakio_db_version' ) !== PAYAMAKIO_VERSION ) {
            self::create_table();
            update_option( 'payamakio_db_version', PAYAMAKIO_VERSION );
        }
    }

    public static function log( $mobile, $gateway, $pattern_code, $status, $response_message, $event_type = 'custom', $raw_response = '' ) {
        global $wpdb;
        $table_name = self::get_table_name();

        if ( is_array( $raw_response ) || is_object( $raw_response ) ) {
            $raw_response = wp_json_encode( $raw_response );
        }

        $wpdb->insert(
            $table_name,
            array(
                'created_at'       => current_time( 'mysql' ),
                'mobile'           => sanitize_text_field( $mobile ),
                'gateway'          => sanitize_text_field( $gateway ),
                'event_type'       => sanitize_text_field( $event_type ),
                'pattern_code'     => sanitize_text_field( $pattern_code ),
                'status'           => sanitize_text_field( $status ),
                'response_message' => sanitize_textarea_field( $response_message ),
                'raw_response'     => $raw_response,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public static function get_logs( $limit = 50, $offset = 0 ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $limit = absint( $limit );
        $offset = absint( $offset );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    public static function get_stats() {
        global $wpdb;
        $table_name = self::get_table_name();

        $total   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
        $success = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE status = 'success'" );
        $failed  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE status = 'failed'" );

        $today_start = current_time( 'Y-m-d 00:00:00' );
        $today   = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE created_at >= %s", $today_start )
        );

        return array(
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
            'today'   => $today,
        );
    }

    public static function purge_old_logs( $days = 30 ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $days = absint( $days );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}
