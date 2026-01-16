<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Template Part: Property Card
 * Source of truth for options: $card_args (query var bridge)
 */

$card_args = get_query_var( 'pera_property_card_args' );
$card_args = is_array( $card_args ) ? $card_args : array();

$variant = isset( $card_args['variant'] ) ? sanitize_key( $card_args['variant'] ) : 'archive';

/* Optional knobs */
$show_badges   = array_key_exists( 'show_badges',  $card_args ) ? (bool) $card_args['show_badges']  : true;
$show_admin    = array_key_exists( 'show_admin',   $card_args ) ? (bool) $card_args['show_admin']   : true;
$show_excerpt  = array_key_exists( 'show_excerpt', $card_args ) ? (bool) $card_args['show_excerpt'] : true;
$excerpt_words = isset( $card_args['excerpt_words'] ) ? max( 0, (int) $card_args['excerpt_words'] ) : 24;
$image_size    = isset( $card_args['image_size'] ) ? sanitize_key( $card_args['image_size'] ) : 'pera-card';

$extra_classes = isset( $card_args['card_classes'] ) ? sanitize_text_field( $card_args['card_classes'] ) : '';
$card_classes  = trim( $extra_classes . ' property-card property-card--' . $variant );

$post_id = get_the_ID();

/* ============================================================
   DATA
   ============================================================ */

// ACF fields
$main_image   = function_exists( 'get_field' ) ? get_field( 'main_image', $post_id ) : null; // array (ACF image)
$price_usd    = function_exists( 'get_field' ) ? get_field( 'price_usd', $post_id ) : '';
$project_name = function_exists( 'get_field' ) ? get_field( 'project_name', $post_id ) : '';

// District / Region
$district_terms = get_the_terms( $post_id, 'district' );
$region_terms   = get_the_terms( $post_id, 'region' );

$district_term = ( ! empty( $district_terms ) && ! is_wp_error( $district_terms ) ) ? $district_terms[0] : null;
$region_term   = ( ! empty( $region_terms ) && ! is_wp_error( $region_terms ) ) ? $region_terms[0] : null;

// Bedrooms (for pill)
$bedroom_terms  = get_the_terms( $post_id, 'bedrooms' );
$bedrooms_label = ( ! empty( $bedroom_terms ) && ! is_wp_error( $bedroom_terms ) ) ? $bedroom_terms[0]->name : '';

// Price text
$price_txt = '';
if ( $price_usd !== '' && $price_usd !== null ) {
  $price_txt = sprintf( 'From $%s', number_format_i18n( (float) $price_usd ) );
}

// Last updated text – formatted as "M d, Y"
$last_update_ts  = get_the_modified_time( 'U', $post_id );
$last_update_txt = $last_update_ts ? date_i18n( 'M d, Y', $last_update_ts ) : '';

// Specials (pill + tooltip)
$specials_terms = get_the_terms( $post_id, 'special' );
$specials_term  = ( ! empty( $specials_terms ) && ! is_wp_error( $specials_terms ) ) ? $specials_terms[0] : null;

$specials_label = $specials_term ? $specials_term->name : '';
$specials_slug  = $specials_term ? $specials_term->slug : '';

// Tooltip copy
$specials_tooltip = '';
if ( $specials_slug === 'resales' || $specials_slug === 'resale' ) {
  $specials_tooltip = 'Resale: offered by an individual owner (private seller).';
} elseif ( $specials_slug === 'project' || $specials_slug === 'projects' ) {
  $specials_tooltip = 'Project: sold by the developer; multiple unit types may be available.';
}

// Image ID (safe)
$image_id = ( is_array( $main_image ) && ! empty( $main_image['ID'] ) ) ? (int) $main_image['ID'] : 0;
?>

<article <?php post_class( $card_classes ); ?>>
  <div class="property-card__inner">

    <div class="property-card__media">

      <?php if ( $show_badges && ( $bedrooms_label || $specials_label ) ) : ?>
        <div class="property-card__badge">

          <?php if ( $bedrooms_label ) : ?>
            <span class="pill pill--green property-card__badge-inner">
              <svg class="icon icon-bed" aria-hidden="true">
                <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
              </svg>
              <span class="property-card__badge-text">
                <?php echo esc_html( $bedrooms_label ); ?>
              </span>
            </span>
          <?php endif; ?>

          <?php if ( $specials_label ) : ?>
            <div class="property-card__specials-row">

              <span class="pill pill--green pill--sm">
                <?php echo esc_html( $specials_label ); ?>
              </span>

              <?php if ( $specials_tooltip ) : ?>
                <span class="property-card__tooltip-wrap">
                  <button
                    type="button"
                    class="property-card__tooltip-btn"
                    aria-label="<?php echo esc_attr( 'More info about ' . $specials_label ); ?>"
                  >
                    i
                  </button>

                  <span class="property-card__tooltip text-xs" role="tooltip">
                    <?php echo esc_html( $specials_tooltip ); ?>
                  </span>
                </span>
              <?php endif; ?>

            </div>
          <?php endif; ?>

        </div>
      <?php endif; ?>

      <?php if ( $show_admin && current_user_can( 'edit_post', $post_id ) ) : ?>
        <div class="property-card__admin-pills">

          <?php if ( $project_name ) : ?>
            <div class="pill pill--brand pill--sm">
              <?php echo esc_html( $project_name ); ?>
            </div>
          <?php endif; ?>

          <a
            href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"
            class="pill pill--brand text-xs"
            target="_blank"
            rel="noopener"
          >
            Edit
          </a>

        </div>
      <?php endif; ?>

      <!-- IMAGE LINKS TO LISTING -->
      <a href="<?php the_permalink(); ?>" class="property-card__media-link">
        <?php if ( $image_id ) : ?>

          <?php
            echo wp_get_attachment_image(
              $image_id,
              $image_size,
              false,
              array(
                'loading'  => 'lazy',
                'decoding' => 'async',
              )
            );
          ?>

        <?php else : ?>

          <span class="property-card__media-placeholder" aria-hidden="true">
            <img
              src="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/logo-white.svg' ); ?>"
              alt=""
              loading="lazy"
              decoding="async"
              class="property-card__media-placeholder-logo"
            >
          </span>

        <?php endif; ?>
      </a>

    </div><!-- /.property-card__media -->

    <div class="property-card__body">

      <?php if ( $district_term || $region_term ) : ?>
        <div class="property-card__location">

          <?php if ( $district_term ) : ?>
            <a
              href="<?php echo esc_url( get_term_link( $district_term ) ); ?>"
              class="property-card__location-link"
            >
              <?php echo esc_html( $district_term->name ); ?>
            </a>
          <?php endif; ?>

          <?php if ( $region_term ) : ?>
            <a
              href="<?php echo esc_url( get_term_link( $region_term ) ); ?>"
              class="property-card__location-link"
            >
              <?php echo esc_html( $region_term->name ); ?>
            </a>
          <?php endif; ?>

        </div>
      <?php endif; ?>

      <h2 class="property-card__title">
        <a href="<?php the_permalink(); ?>" class="property-card__title-link">
          <?php the_title(); ?>
        </a>
      </h2>

      <?php if ( $show_excerpt && has_excerpt() ) : ?>
        <p class="property-card__excerpt">
          <?php echo esc_html( wp_trim_words( get_the_excerpt(), $excerpt_words, '…' ) ); ?>
        </p>
      <?php endif; ?>

    </div><!-- /.property-card__body -->

    <div class="property-card__footer">

      <?php if ( $price_txt ) : ?>
        <span class="property-card__price">
          <?php echo esc_html( $price_txt ); ?>
        </span>
      <?php endif; ?>

      <?php if ( $last_update_txt ) : ?>
        <span class="property-card__updated">
          <?php echo esc_html( $last_update_txt ); ?>
        </span>
      <?php endif; ?>

    </div><!-- /.property-card__footer -->

  </div><!-- /.property-card__inner -->
</article>
