<?php
/**
 * Enquiry Handlers (Sell / Rent / Property / Citizenship)
 * Location: /inc/enquiry.php
 *
 * Loads only when required (see functions.php loader snippet below).
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Master handler for both Citizenship and Sell/Rent/Property enquiries.
 */
function pera_handle_citizenship_enquiry() {

  if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    return;
  }

  /* =========================================
   * A) SELL / RENT / PROPERTY ENQUIRY BRANCH
   * Trigger: <input type="hidden" name="sr_action" value="1">
   * ========================================= */
  if ( isset( $_POST['sr_action'] ) ) {

    // Security: SR nonce
    if (
      ! isset( $_POST['sr_nonce'] ) ||
      ! wp_verify_nonce( $_POST['sr_nonce'], 'pera_seller_landlord_enquiry' )
    ) {
      wp_die( 'Security check failed', 'Error', array( 'response' => 403 ) );
    }

    // Honeypot check – bots fill this, humans don't
    if ( ! empty( $_POST['sr_company'] ?? '' ) ) {
      // Fail silently or hard-stop
      wp_die( 'Spam detected', 403 );
    }


    // Context (whitelist)
    $raw_context  = isset( $_POST['form_context'] ) ? sanitize_text_field( wp_unslash( $_POST['form_context'] ) ) : 'general';
    $allowed_ctx  = array( 'sell-page', 'rent-page', 'property', 'general', 'general-contact', 'sell', 'rent' );
    $form_context = in_array( $raw_context, $allowed_ctx, true ) ? $raw_context : 'general';

    // Core fields
    $name    = isset( $_POST['sr_name'] )  ? sanitize_text_field( wp_unslash( $_POST['sr_name'] ) )  : '';
    $email   = isset( $_POST['sr_email'] ) ? sanitize_email( wp_unslash( $_POST['sr_email'] ) )      : '';
    $phone   = isset( $_POST['sr_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_phone'] ) ) : '';
    $consent = ! empty( $_POST['sr_consent'] ) ? 'Yes' : 'No';

    // Optional message
    $message = isset( $_POST['sr_message'] )
      ? sanitize_textarea_field( wp_unslash( $_POST['sr_message'] ) )
      : '';

    // Sell/Rent-only fields
    $intent       = isset( $_POST['sr_intent'] )       ? sanitize_text_field( wp_unslash( $_POST['sr_intent'] ) )       : '';
    $location     = isset( $_POST['sr_location'] )     ? sanitize_text_field( wp_unslash( $_POST['sr_location'] ) )     : '';
    $details      = isset( $_POST['sr_details'] )      ? sanitize_textarea_field( wp_unslash( $_POST['sr_details'] ) )  : '';
    $expectations = isset( $_POST['sr_expectations'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_expectations'] ) ) : '';

    // Property-only hidden fields
    $property_id    = isset( $_POST['sr_property_id'] )    ? absint( $_POST['sr_property_id'] ) : 0;
    $property_title = isset( $_POST['sr_property_title'] ) ? sanitize_text_field( wp_unslash( $_POST['sr_property_title'] ) ) : '';
    $property_url   = isset( $_POST['sr_property_url'] )   ? esc_url_raw( wp_unslash( $_POST['sr_property_url'] ) ) : '';

    $to = 'info@peraproperty.com';

    if ( $form_context === 'property' ) {

      $ref     = $property_id ? (string) $property_id : 'N/A';
      $subject = 'Property enquiry – ' . ( $property_title ?: 'Listing' ) . ' (Ref: ' . $ref . ')';

      $body  = "New property enquiry submitted:\n\n";
      $body .= "Name: {$name}\n";
      $body .= "Phone: {$phone}\n";
      $body .= "Email: {$email}\n\n";
      $body .= "Listing Ref: {$ref}\n";
      $body .= "Listing Title: " . ( $property_title ?: 'N/A' ) . "\n";
      $body .= "Listing URL: " . ( $property_url ?: 'N/A' ) . "\n\n";

      if ( $message !== '' ) {
        $body .= "Message:\n{$message}\n\n";
      }

      $body .= "Consent to contact: {$consent}\n";
      $body .= "Form context: {$form_context}\n";

    } else {

      // Normalize intent (sell page may not send radios; rent page does)
      $intent_norm = $intent ?: ( ( $form_context === 'sell-page' ) ? 'sell' : '' );

      if ( $intent_norm === 'sell' ) {
        $subject = 'I want to sell my property';
      } elseif ( $intent_norm === 'rent' ) {
        $subject = 'I want to rent out my property (long-term)';
      } elseif ( $intent_norm === 'short-term' ) {
        $subject = 'I want to rent out my property (short-term / Airbnb)';
      } else {
        $subject = 'New enquiry – ' . $name . ' (' . $form_context . ')';
      }

      $body  = "New enquiry submitted:\n\n";
      $body .= "Name: {$name}\n";
      $body .= "Phone: {$phone}\n";
      $body .= "Email: {$email}\n\n";
      $body .= "Intent: {$intent_norm}\n";
      $body .= "Property location: {$location}\n\n";
      $body .= "Property details:\n{$details}\n\n";
      $body .= "Price / rent expectations: {$expectations}\n\n";

      if ( $message !== '' ) {
        $body .= "Message:\n{$message}\n\n";
      }

      $body .= "Consent to contact: {$consent}\n";
      $body .= "Form context: {$form_context}\n";
    }

    $headers = array(
      'From: ' . ( $name ?: 'Website Enquiry' ) . ' <info@peraproperty.com>',
      'Content-Type: text/plain; charset=UTF-8',
    );
    
    if ( is_email( $email ) ) {
      $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }


    $sent = wp_mail( $to, $subject, $body, $headers );

    // Redirect: base (referer), add sr_success, then force the correct fragment by context.
    $redirect = ! empty( $_POST['_wp_http_referer'] )
      ? esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) )
      : home_url( '/' );

    // Remove any existing fragment so we can set a deterministic one.
    $redirect = preg_replace( '/#.*$/', '', $redirect );

    // Append success flag
    $redirect = add_query_arg( 'sr_success', $sent ? '1' : '0', $redirect );

    // Append fragment
    if ( $form_context === 'property' ) {
      $redirect .= '#contact-form';
    } else {
      // Sell / Rent / General enquiries should return to the contact section.
      $redirect .= '#contact';
    }

    wp_safe_redirect( $redirect );
    exit;
  }

  /* ==============================
   * B) CITIZENSHIP ENQUIRY BRANCH
   * Trigger: <input type="hidden" name="pera_citizenship_action" value="1">
   * ============================== */
  if ( isset( $_POST['pera_citizenship_action'] ) ) {

    if (
      ! isset( $_POST['pera_citizenship_nonce'] ) ||
      ! wp_verify_nonce( $_POST['pera_citizenship_nonce'], 'pera_citizenship_enquiry' )
    ) {
      wp_die( 'Security check failed', 'Error', array( 'response' => 403 ) );
    }

    $name  = isset( $_POST['name'] )  ? sanitize_text_field( wp_unslash( $_POST['name'] ) )  : '';
    $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) )      : '';

    $enquiry_type = isset( $_POST['enquiry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['enquiry_type'] ) ) : '';
    $family       = isset( $_POST['family'] )       ? sanitize_text_field( wp_unslash( $_POST['family'] ) )       : '';
    $message      = isset( $_POST['message'] )      ? wp_kses_post( wp_unslash( $_POST['message'] ) )             : '';

    $contact_methods = array();
    if ( ! empty( $_POST['contact_method'] ) && is_array( $_POST['contact_method'] ) ) {
      $contact_methods = array_map( 'sanitize_text_field', wp_unslash( $_POST['contact_method'] ) );
    }

    $to      = 'info@peraproperty.com';
    $subject = 'New Citizenship Enquiry from ' . $name;

    $body  = "New citizenship enquiry submitted:\n\n";
    $body .= "Name: {$name}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Email: {$email}\n\n";

    $body .= "Preferred contact method(s): " . ( ! empty( $contact_methods ) ? implode( ', ', $contact_methods ) : 'Not specified' ) . "\n";
    $body .= "Type of enquiry: {$enquiry_type}\n";
    $body .= "Family members: {$family}\n\n";
    $body .= "Questions / Comments:\n{$message}\n";

    $headers = array();
    if ( is_email( $email ) ) {
      $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }

    $sent = wp_mail( $to, $subject, $body, $headers );

    $status   = $sent ? 'ok' : 'mail-failed';
    $redirect = home_url( '/citizenship-by-investment/?enquiry=' . $status . '#citizenship-form' );

    wp_safe_redirect( $redirect );
    exit;
  }

  // If neither branch, do nothing.
}

/**
 * Gateway on init: decide whether to call the handler at all.
 * Keeps the original behaviour but now supports both forms.
 */
function pera_maybe_handle_citizenship_enquiry() {

  if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    return;
  }

  if ( isset( $_POST['sr_action'] ) || isset( $_POST['pera_citizenship_action'] ) ) {
    pera_handle_citizenship_enquiry();
  }
}
add_action( 'init', 'pera_maybe_handle_citizenship_enquiry' );
