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
  if ( ! function_exists( 'peracrm_find_or_create_client_by_email' ) || ! function_exists( 'peracrm_log_event' ) ) {
    return;
  }

  $email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
  if ( ! is_email( $email ) ) {
    return;
  }

  $first_name = isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '';
  $last_name  = isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '';
  $phone      = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';

  $client_id = peracrm_find_or_create_client_by_email(
    $email,
    array(
      'first_name' => $first_name,
      'last_name'  => $last_name,
      'phone'      => $phone,
      'source'     => 'form',
      'status'     => 'enquiry',
    )
  );

  if ( empty( $client_id ) ) {
    return;
  }

  $payload = array(
    'enquiry_type' => isset( $data['enquiry_type'] ) ? sanitize_text_field( $data['enquiry_type'] ) : '',
  );

  if ( ! empty( $data['property_id'] ) ) {
    $payload['property_id'] = absint( $data['property_id'] );
  }

  peracrm_log_event( $client_id, 'enquiry', $payload );

  if ( ! empty( $data['property_id'] ) && $payload['enquiry_type'] === 'property' && function_exists( 'peracrm_client_property_link' ) ) {
    peracrm_client_property_link( $client_id, $payload['property_id'], 'enquiry' );
  }
}
