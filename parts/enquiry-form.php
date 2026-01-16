<?php
/**
 * Core Enquiry Form (reusable)
 * Location: /parts/enquiry-form.php
 *
 * Args:
 * - context        (string)  'sell' | 'rent' | 'property' | 'general'
 * - heading        (string)
 * - intro          (string)
 * - submit_label   (string)
 * - property_id    (int)
 * - property_title (string)
 * - property_url   (string)
 * - form_context   (string)  optional (e.g. 'sell-page', 'rent-page')
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$context      = isset( $args['context'] ) ? (string) $args['context'] : 'general';
$heading      = isset( $args['heading'] ) ? (string) $args['heading'] : 'Send an enquiry';
$intro        = isset( $args['intro'] ) ? (string) $args['intro'] : '';
$submit_label = isset( $args['submit_label'] ) ? (string) $args['submit_label'] : 'Send my details';

$property_id    = isset( $args['property_id'] ) ? (int) $args['property_id'] : 0;
$property_title = isset( $args['property_title'] ) ? (string) $args['property_title'] : '';
$property_url   = isset( $args['property_url'] ) ? (string) $args['property_url'] : '';

// For tracking/logging in email body (and your existing redirect logic)
$form_context = ( $context === 'property' )
  ? 'property'
  : ( isset( $args['form_context'] ) ? (string) $args['form_context'] : $context );
?>


  <div class="enquiry-cta-header m-sm">
    <h2><?php echo esc_html( $heading ); ?></h2>
    <?php if ( $intro ) : ?>
      <p><?php echo esc_html( $intro ); ?></p>
    <?php endif; ?>
  </div>
  
  <style>
      .sr-hp-field {
        position: absolute;
        left: -9999px;
        width: 1px;
        height: 1px;
        overflow: hidden;
      }
    </style>


  <form class="enquiry-cta-form m-sm" action="" method="post">

    <input type="hidden" name="sr_action" value="1">
    <input type="hidden" name="form_context" value="<?php echo esc_attr( $form_context ); ?>">

        <?php wp_nonce_field( 'pera_seller_landlord_enquiry', 'sr_nonce' ); ?>
        <!-- Honeypot field (spam bots only) -->
        <div class="sr-hp-field" aria-hidden="true">
          <label for="sr_company">Company</label>
          <input
            type="text"
            name="sr_company"
            id="sr_company"
            value=""
            autocomplete="off"
            tabindex="-1"
          >
        </div>


    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( home_url( add_query_arg( array(), $wp->request ) ) ); ?>">

    <?php if ( $context === 'property' ) : ?>
      <input type="hidden" name="sr_property_id" value="<?php echo esc_attr( $property_id ); ?>">
      <input type="hidden" name="sr_property_title" value="<?php echo esc_attr( $property_title ); ?>">
      <input type="hidden" name="sr_property_url" value="<?php echo esc_url( $property_url ); ?>">
    <?php endif; ?>

    <?php
    /**
     * INTENT HANDLING RULES:
     * - sell page: no radios; hard-set intent to "sell"
     * - rent page: show radios for "rent" and "short-term" only
     * - general: show full radios (sell/rent/short-term)
     * - property: no radios at all
     */
    if ( $context === 'sell' ) : ?>
      <input type="hidden" name="sr_intent" value="sell">
    <?php endif; ?>

    <div class="cta-fieldset">

      <div class="cta-field">
        <label class="cta-label">Full name</label>
        <input type="text" name="sr_name" class="cta-control" required placeholder="Your full name">
      </div>

      <div class="cta-field">
        <label class="cta-label">Email</label>
        <input type="email" name="sr_email" class="cta-control" required placeholder="name@example.com">
      </div>

      <div class="cta-field">
        <label class="cta-label">Mobile / WhatsApp</label>
        <input type="text" name="sr_phone" class="cta-control" required placeholder="+90 … or your international number">
      </div>

      <?php if ( $context === 'rent' ) : ?>

        <div class="cta-field">
          <span class="cta-label">Rental type</span>
          <div class="cta-options">
            <label class="cta-checkbox">
              <input type="radio" name="sr_intent" value="rent" checked>
              <span>Long-term rental</span>
            </label>
            <label class="cta-checkbox">
              <input type="radio" name="sr_intent" value="short-term">
              <span>Short-term rental / Airbnb</span>
            </label>
          </div>
        </div>

      <?php elseif ( $context === 'general' ) : ?>

        <div class="cta-field">
          <span class="cta-label">I would like to</span>
          <div class="cta-options">
            <label class="cta-checkbox">
              <input type="radio" name="sr_intent" value="sell" checked>
              <span>Sell my property</span>
            </label>
            <label class="cta-checkbox">
              <input type="radio" name="sr_intent" value="rent">
              <span>Rent out my property (long-term)</span>
            </label>
            <label class="cta-checkbox">
              <input type="radio" name="sr_intent" value="short-term">
              <span>Short-term rental / Airbnb</span>
            </label>
          </div>
        </div>

      <?php endif; ?>

      <?php if ( $context !== 'property' ) : ?>

        <div class="cta-field">
          <label class="cta-label">Property location</label>
          <input type="text" name="sr_location" class="cta-control" placeholder="District / neighbourhood (e.g. Beşiktaş – Dikilitaş)">
        </div>

        <div class="cta-field">
          <label class="cta-label">Property details</label>
          <textarea name="sr_details" rows="4" class="cta-control" placeholder="Apartment or villa, number of bedrooms, approximate size, building age, tenancy status, etc."></textarea>
        </div>

        <div class="cta-field">
          <label class="cta-label">
            <?php echo ( $context === 'rent' ) ? 'Rent expectations (optional)' : 'Price expectations (optional)'; ?>
          </label>
          <input type="text" name="sr_expectations" class="cta-control" placeholder="<?php echo esc_attr( ( $context === 'rent' ) ? 'Your target monthly rent (if you have one)' : 'Your target sale price (if you have one)' ); ?>">
        </div>

        <div class="cta-field">
          <label class="cta-label">Message (optional)</label>
          <textarea name="sr_message" rows="4" class="cta-control" placeholder="Anything else we should know?"></textarea>
        </div>

      <?php else : ?>

        <div class="cta-field">
          <label class="cta-label">Message</label>
          <textarea name="sr_message" rows="4" class="cta-control" placeholder="Tell us what you need (availability, brochure request, viewing, questions, etc.)."></textarea>
        </div>

      <?php endif; ?>

        <div class="enquiry-cta-footer">
            <label class="cta-checkbox">
              <input type="checkbox" name="sr_consent" value="1" required>
              <span>
                I agree for Pera Property to contact me regarding this enquiry and to
                process my personal data in accordance with the
                <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">
                  Privacy Policy
                </a>.
              </span>
            </label>
    
    
          <button type="submit" class="btn btn--solid btn--green">
            <?php echo esc_html( $submit_label ); ?>
          </button>
        </div>

    </div>

    

  </form>
