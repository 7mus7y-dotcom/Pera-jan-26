<?php
/**
 * CRM client context helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the current user's linked CRM client ID or 0.
 *
 * @return int
 */
function pera_get_current_crm_client_id() {
    if ( ! is_user_logged_in() ) {
        return 0;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return 0;
    }

    $client_id = absint( get_user_meta( $user_id, 'crm_client_id', true ) );
    if ( ! $client_id ) {
        return 0;
    }

    $post_type = get_post_type( $client_id );
    if ( $post_type !== 'crm_client' ) {
        return 0;
    }

    return $client_id;
}

/**
 * Check whether a CRM table exists.
 *
 * @param string $table_name
 * @return bool
 */
function pera_crm_table_exists( $table_name ) {
    global $wpdb;

    if ( empty( $table_name ) ) {
        return false;
    }

    $sql   = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
    $found = $wpdb->get_var( $sql );

    return ! empty( $found );
}

/**
 * Resolve a CRM table name with or without the WordPress prefix.
 *
 * @param string $base_name
 * @return string
 */
function pera_crm_get_table_name( $base_name ) {
    global $wpdb;

    if ( empty( $base_name ) ) {
        return '';
    }

    $prefixed = $wpdb->prefix . $base_name;
    if ( pera_crm_table_exists( $prefixed ) ) {
        return $prefixed;
    }

    if ( pera_crm_table_exists( $base_name ) ) {
        return $base_name;
    }

    return '';
}
