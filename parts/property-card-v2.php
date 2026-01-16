<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Template Part: Property Card (V2)
 * Uses ACF repeater v2_units with:
 * - v2_bedrooms
 * - v2_gross_size_min / v2_gross_size_max
 * - v2_price_usd_min / v2_price_usd_max
 *
 * Summary mode (no v2_beds): shows bed range + price range + size range
 * Single mode (v2_beds set): shows aggregate for that bedroom across matching rows
 */

$card_args = get_query_var( 'pera_property_card_args' );
$card_args = is_array( $card_args ) ? $card_args : array();

$variant = isset( $card_args['variant'] ) ? sanitize_key( $card_args['variant'] ) : 'archive';

$show_badges   = array_key_exists( 'show_badges',  $card_args ) ? (bool) $card_args['show_badges']  : true;
$show_admin    = array_key_exists( 'show_admin',   $card_args ) ? (bool) $card_args['show_admin']   : true;
$show_excerpt  = array_key_exists( 'show_excerpt', $card_args ) ? (bool) $card_args['show_excerpt'] : true;
$excerpt_words = isset( $card_args['excerpt_words'] ) ? max( 0, (int) $card_args['excerpt_words'] ) : 24;
$image_size    = isset( $card_args['image_size'] ) ? sanitize_key( $card_args['image_size'] ) : 'pera-card';

$extra_classes = isset( $card_args['card_classes'] ) ? sanitize_text_field( $card_args['card_classes'] ) : '';
$card_classes  = trim( $extra_classes . ' property-card property-card--' . $variant );

$post_id          = get_the_ID();
$v2_beds_selected = isset( $card_args['v2_beds'] ) ? (int) $card_args['v2_beds'] : 0;

/* ============================================================
   URL (Option A)
   - If v2_beds is active in archive, carry it into the single page
   ============================================================ */

$card_url = get_permalink( $post_id );
if ( $v2_beds_selected > 0 ) {
  $card_url = add_query_arg(
    array( 'unit_key' => $v2_beds_selected ),
    $card_url
  );
}

/* ============================================================
   DATA
   ============================================================ */

// ACF
$main_image   = function_exists( 'get_field' ) ? get_field( 'main_image', $post_id ) : null;
$project_name = function_exists( 'get_field' ) ? (string) get_field( 'project_name', $post_id ) : '';

// Repeater
$units = function_exists( 'get_field' ) ? get_field( 'v2_units', $post_id ) : array();
$units = is_array( $units ) ? $units : array();

// District / Region (kept)
$district_terms = get_the_terms( $post_id, 'district' );
$region_terms   = get_the_terms( $post_id, 'region' );

$district_term = ( ! empty( $district_terms ) && ! is_wp_error( $district_terms ) ) ? $district_terms[0] : null;
$region_term   = ( ! empty( $region_terms ) && ! is_wp_error( $region_terms ) ) ? $region_terms[0] : null;

// Specials (optional pill + tooltip, kept)
$specials_terms = get_the_terms( $post_id, 'special' );
$specials_term  = ( ! empty( $specials_terms ) && ! is_wp_error( $specials_terms ) ) ? $specials_terms[0] : null;

$specials_label = $specials_term ? $specials_term->name : '';
$specials_slug  = $specials_term ? $specials_term->slug : '';

$specials_tooltip = '';
if ( $specials_slug === 'resales' || $specials_slug === 'resale' ) {
  $specials_tooltip = 'Resale: offered by an individual owner (private seller).';
} elseif ( $specials_slug === 'project' || $specials_slug === 'projects' ) {
  $specials_tooltip = 'Project: sold by the developer; multiple unit types may be available.';
}

// Image ID
$image_id = ( is_array( $main_image ) && ! empty( $main_image['ID'] ) ) ? (int) $main_image['ID'] : 0;

// Published date
$published_ts  = get_the_time( 'U', $post_id );
$last_update_txt = $published_ts ? date_i18n( 'M d, Y', $published_ts ) : '';



/* ============================================================
   HELPERS
   ============================================================ */

$fmt_usd = function( $n ) {
  $n = (int) $n;
  if ( $n <= 0 ) return '';
  return '$' . number_format_i18n( $n );
};

$fmt_m2 = function( $n ) {
  $n = (float) $n;
  if ( $n <= 0 ) return '';
  return number_format_i18n( (int) round( $n ) ) . ' m²';
};

$bed_label = function( int $beds ) {
  return (string) $beds;
};

/* ============================================================
   V2 AGGREGATION
   - Summary mode (no v2_beds): compute global ranges across all rows
   - Single mode (v2_beds): compute ranges across matching rows
   ============================================================ */

$rows_to_use = array();

if ( $v2_beds_selected > 0 ) {
  foreach ( $units as $row ) {
    $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
    if ( $b === $v2_beds_selected ) {
      $rows_to_use[] = $row;
    }
  }
} else {
  $rows_to_use = $units;
}

// Collect mins/maxes safely
$beds_min  = 0; $beds_max  = 0;
$price_min = 0; $price_max = 0;
$size_min  = 0; $size_max  = 0;

$beds_vals = array();

foreach ( $rows_to_use as $row ) {

  $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
  if ( $b > 0 ) $beds_vals[] = $b;

  $pmin = isset( $row['v2_price_usd_min'] ) ? (int) $row['v2_price_usd_min'] : 0;
  $pmax = isset( $row['v2_price_usd_max'] ) ? (int) $row['v2_price_usd_max'] : 0;

  // If max not provided, treat max as min (resale behaviour)
  if ( $pmin > 0 && $pmax <= 0 ) $pmax = $pmin;

  if ( $pmin > 0 ) {
    $price_min = ( $price_min === 0 ) ? $pmin : min( $price_min, $pmin );
  }
  if ( $pmax > 0 ) {
    $price_max = ( $price_max === 0 ) ? $pmax : max( $price_max, $pmax );
  }

  $smin = isset( $row['v2_gross_size_min'] ) ? (float) $row['v2_gross_size_min'] : 0;
  $smax = isset( $row['v2_gross_size_max'] ) ? (float) $row['v2_gross_size_max'] : 0;

  if ( $smin > 0 && $smax <= 0 ) $smax = $smin;

  if ( $smin > 0 ) {
    $size_min = ( $size_min === 0 ) ? $smin : min( $size_min, $smin );
  }
  if ( $smax > 0 ) {
    $size_max = ( $size_max === 0 ) ? $smax : max( $size_max, $smax );
  }
}

if ( ! empty( $beds_vals ) ) {
  $beds_min = min( $beds_vals );
  $beds_max = max( $beds_vals );
}

// Build badge text
$beds_badge_txt = '';
if ( $v2_beds_selected > 0 ) {
  $beds_badge_txt = $bed_label( $v2_beds_selected );
} elseif ( $beds_min > 0 ) {
  $beds_badge_txt = ( $beds_min === $beds_max )
    ? $bed_label( $beds_min )
    : ( $bed_label( $beds_min ) . '–' . $bed_label( $beds_max ) );
}

// Price display (V2 rules)
// - Project: "From $MIN" only
// - Resale: "$MIN" or "$MIN–$MAX" (no "From")
$price_txt = '';

$is_project = ( $specials_slug === 'project' || $specials_slug === 'projects' );

if ( $price_min > 0 ) {

  if ( $is_project ) {
    $price_txt = 'From ' . $fmt_usd( $price_min );
  } else {
    if ( $price_max > 0 && $price_max !== $price_min ) {
      $price_txt = $fmt_usd( $price_min ) . '–' . $fmt_usd( $price_max );
    } else {
      $price_txt = $fmt_usd( $price_min );
    }
  }
}

// Size display
$size_txt = '';
if ( $size_min > 0 ) {
  if ( $size_max > 0 && (int) $size_max !== (int) $size_min ) {
    $size_txt = $fmt_m2( $size_min ) . '–' . $fmt_m2( $size_max );
  } else {
    $size_txt = $fmt_m2( $size_min );
  }
}
?>

<article <?php post_class( $card_classes ); ?>>
  <div class="property-card__inner">

    <div class="property-card__media">

      <?php if ( $show_badges && ( $beds_badge_txt || $specials_label ) ) : ?>
        <div class="property-card__badge">

          <?php if ( $beds_badge_txt ) : ?>
            <span class="pill pill--green property-card__badge-inner">
              <svg class="icon icon-bed" aria-hidden="true">
                <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
              </svg>
              <span class="property-card__badge-text">
                <?php echo esc_html( $beds_badge_txt ); ?>
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

          <?php if ( $project_name !== '' ) : ?>
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

      <a href="<?php echo esc_url( $card_url ); ?>" class="property-card__media-link">
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
            <a href="<?php echo esc_url( get_term_link( $district_term ) ); ?>" class="property-card__location-link">
              <?php echo esc_html( $district_term->name ); ?>
            </a>
          <?php endif; ?>

          <?php if ( $region_term ) : ?>
            <a href="<?php echo esc_url( get_term_link( $region_term ) ); ?>" class="property-card__location-link">
              <?php echo esc_html( $region_term->name ); ?>
            </a>
          <?php endif; ?>

        </div>
      <?php endif; ?>

      <h2 class="property-card__title">
        <a href="<?php echo esc_url( $card_url ); ?>" class="property-card__title-link">
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

      <div class="property-card__footer-row property-card__footer-row--top">
        <?php if ( $price_txt ) : ?>
          <span class="property-card__price">
            <?php echo esc_html( $price_txt ); ?>
          </span>
        <?php endif; ?>
    
        <?php if ( $size_txt ) : ?>
          <span class="property-card__size property-card__meta-sm">
            <?php echo esc_html( $size_txt ); ?>
          </span>
        <?php endif; ?>
      </div>
    
      <?php if ( $last_update_txt ) : ?>
        <div class="property-card__footer-row property-card__footer-row--bottom">
          <span class="property-card__updated property-card__meta-sm">
            <?php echo esc_html( $last_update_txt ); ?>
          </span>
        </div>
      <?php endif; ?>
    
    </div><!-- /.property-card__footer -->


  </div><!-- /.property-card__inner -->
</article>
