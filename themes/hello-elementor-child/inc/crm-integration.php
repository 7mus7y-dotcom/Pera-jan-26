<?php
/**
 * CRM integration helpers for enquiry forms.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Split a full name into first and last name parts.
 *
 * @param string $name Full name.
 * @return array{first_name:string,last_name:string}
 */
function pera_crm_split_name( $name ) {
  $name = trim( (string) $name );

  if ( $name === '' ) {
    return array(
      'first_name' => '',
      'last_name'  => '',
    );
  }

  $parts = preg_split( '/\s+/', $name );
  $first = array_shift( $parts );
  $last  = $parts ? implode( ' ', $parts ) : '';

  return array(
    'first_name' => sanitize_text_field( $first ),
    'last_name'  => sanitize_text_field( $last ),
  );
}

/**
 * Log enquiry activity to CRM.
 *
 * @param array $data Enquiry payload.
 */
function pera_crm_log_enquiry( $data ) {
  if ( ! function_exists( 'peracrm_log_event' ) && ! function_exists( 'peracrm_resolve_client_id_from_enquiry' ) ) {
    return;
  }

  $email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
  if ( ! is_email( $email ) ) {
    return;
  }

  $first_name = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
  $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
  $phone      = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
  $source     = isset( $data['form_source'] ) ? sanitize_key( $data['form_source'] ) : 'web_enquiry';
  $full_name  = trim( $first_name . ' ' . $last_name );

  $client_id = 0;
  if ( function_exists( 'peracrm_resolve_client_id_from_enquiry' ) ) {
    $client_id = peracrm_resolve_client_id_from_enquiry( $email, $full_name, $phone, $source, true );
  } elseif ( function_exists( 'peracrm_find_or_create_client_by_email' ) ) {
    $client_id = peracrm_find_or_create_client_by_email(
      $email,
      array(
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'phone'      => $phone,
        'source'     => $source,
        'status'     => 'enquiry',
      )
    );
  }

  if ( empty( $client_id ) ) {
    return;
  }

  $can_log_activity = false;
  $can_link_property = false;

  if ( function_exists( 'peracrm_table' ) ) {
    $activity_table = peracrm_table( 'crm_activity' );
    $can_log_activity = ! empty( $activity_table ) && pera_crm_table_exists( $activity_table );

    $link_table = peracrm_table( 'crm_client_property' );
    $can_link_property = ! empty( $link_table ) && pera_crm_table_exists( $link_table );
  }

  $payload = array(
    'enquiry_type' => isset( $data['enquiry_type'] ) ? sanitize_text_field( $data['enquiry_type'] ) : '',
    'form_source'  => $source,
  );

  if ( ! empty( $data['property_id'] ) ) {
    $payload['property_id'] = absint( $data['property_id'] );
  }

  if ( $can_log_activity && function_exists( 'peracrm_log_event' ) ) {
    peracrm_log_event( $client_id, 'enquiry', $payload );
  }

  if ( ! empty( $data['property_id'] ) && $payload['enquiry_type'] === 'property' && function_exists( 'peracrm_client_property_link' ) && $can_link_property ) {
    peracrm_client_property_link( $client_id, $payload['property_id'], 'enquiry' );
  }

  if ( function_exists( 'peracrm_reminder_add' ) ) {
    $advisor_id = function_exists( 'peracrm_enquiry_get_assigned_advisor_id' )
      ? (int) peracrm_enquiry_get_assigned_advisor_id( $client_id )
      : 0;

    if ( $advisor_id > 0 ) {
      $timestamp = current_time( 'timestamp' ) + DAY_IN_SECONDS;
      $due_at    = date_i18n( 'Y-m-d H:i:s', $timestamp, false );
      $note      = 'Follow up: ' . ( $payload['enquiry_type'] ?: 'general' ) . ' enquiry from ' . $email;

      if ( ! empty( $data['property_id'] ) ) {
        $note .= ' (property ' . absint( $data['property_id'] ) . ')';
      }

      peracrm_reminder_add( $client_id, $advisor_id, $due_at, $note );
    }
  }
}

/**
 * Check if a CRM table exists.
 *
 * @param string $table_name Table name.
 * @return bool
 */
function pera_crm_table_exists( $table_name ) {
  global $wpdb;

  if ( empty( $table_name ) ) {
    return false;
  }

  $table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

  return $table === $table_name;
}
