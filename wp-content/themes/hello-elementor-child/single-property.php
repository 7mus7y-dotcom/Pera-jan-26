<?php
/**
 * Single Property (Lean)
 * Location: /single-property.php
 * Notes:
 * - Uses main.css layout and your lean component classes.
 * - Do NOT hard-code a property ID; this runs on the current Property CPT item.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

get_header();

/* -------------------------------------------------------
   CTA copy (safe defaults; replace with ACF options later if needed)
-------------------------------------------------------- */
$hero_heading = 'Talk to Pera about your Istanbul plans';
$hero_intro   = 'Whether you’re buying, selling, or renting in Istanbul, our team can walk you through the numbers, the legal steps, and the neighbourhoods that fit your strategy.';

$form_heading = 'Request full details';
$form_intro   = 'Ask about availability, pricing, floor plans, or arrange a viewing.';
?>

<main id="primary" class="site-main pera-single-property content-rail">

<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>

<?php
/* ======================================================
   1) CURRENT PROPERTY CONTEXT
   ====================================================== */
$property_id = get_the_ID();

if ( get_post_type( $property_id ) !== 'property' ) : ?>
  <div class="container">
    <p>This template is for Property posts only.</p>
  </div>
  <?php continue; ?>
<?php endif; ?>

<?php

/* ======================================================
   PREP BLOCK (CLEAN + EXTENDED)
   ====================================================== */

/* 1) CORE DATA */
$title        = get_the_title( $property_id );
$permalink    = get_permalink( $property_id );
$project_name = function_exists( 'get_field' ) ? (string) get_field( 'project_name', $property_id ) : '';
$excerpt      = get_the_excerpt( $property_id );

/* 2) TAXONOMIES */
$district      = wp_get_post_terms( $property_id, 'district' );
$region        = wp_get_post_terms( $property_id, 'region' );
$type_terms    = wp_get_post_terms( $property_id, 'property_type' );
$bed_terms     = wp_get_post_terms( $property_id, 'bedrooms' );
$property_tags = wp_get_post_terms( $property_id, 'property_tags' );
$special_slugs = wp_get_post_terms( $property_id, 'special', array( 'fields' => 'slugs' ) );

/* Safe links (only when term exists) */
$district_link = ( ! empty( $district ) && ! is_wp_error( $district ) ) ? get_term_link( $district[0] ) : '';
$region_link   = ( ! empty( $region ) && ! is_wp_error( $region ) ) ? get_term_link( $region[0] ) : '';



/* Property type */
$type_name = ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ) ? (string) $type_terms[0]->name : '';
$type_link = ( ! empty( $type_terms ) && ! is_wp_error( $type_terms ) ) ? get_term_link( $type_terms[0] ) : '';

/* Bedrooms (primary) */
$bed_name = ( ! empty( $bed_terms ) && ! is_wp_error( $bed_terms ) ) ? (string) $bed_terms[0]->name : '';
$bed_link = ( ! empty( $bed_terms ) && ! is_wp_error( $bed_terms ) ) ? get_term_link( $bed_terms[0] ) : '';

/* 3) SPECIAL FLAGS */
$special_slugs = is_array( $special_slugs ) ? $special_slugs : array();

$is_project  = in_array( 'project',  $special_slugs, true );
$is_resale   = in_array( 'resales',  $special_slugs, true );
$is_featured = in_array( 'featured', $special_slugs, true );
$is_special  = in_array( 'special',  $special_slugs, true );

/* Normalize property type label for display */
$display_type = '';

if ( $type_name ) {
  if ( stripos( $type_name, 'apartment' ) !== false ) {
    $display_type = 'Apartment';
  } elseif ( stripos( $type_name, 'villa' ) !== false ) {
    $display_type = 'Villa';
  } else {
    $display_type = $type_name;
  }
}

/* Quick facts context text (Specials taxonomy) */
$quick_facts_intro = '';

if ( $is_resale ) {
  $quick_facts_intro = 'This is a resale property exclusive to Pera Property.';
} elseif ( $is_project ) {
  $quick_facts_intro = 'This is a project with several options available. Contact us for a specific offer.';
}


/* 4) HERO IMAGE (ACF main_image) */
$main_image   = function_exists( 'get_field' ) ? get_field( 'main_image', $property_id ) : null; // ACF image array
$hero_img_id  = ( is_array( $main_image ) && ! empty( $main_image['ID'] ) ) ? (int) $main_image['ID'] : 0;
$hero_img_url = ( is_array( $main_image ) && ! empty( $main_image['url'] ) ) ? (string) $main_image['url'] : '';
$hero_img_alt = ( is_array( $main_image ) && ! empty( $main_image['alt'] ) ) ? (string) $main_image['alt'] : $title;

/* ======================================================
   V2 UNIT CONTEXT + HERO CONTEXT (V2-ONLY)
   Rules:
   - unit_key (beds) selects the unit row (e.g. ?unit_key=2 means 2+1)
   - If unit_key missing/invalid -> cheapest unit
   - "From" label is controlled by Specials taxonomy:
       * Show "From" only if (project && !resale)
       * Never show "From" for resales
====================================================== */

// unit_key = beds (int), e.g. ?unit_key=2
$unit_key = isset( $_GET['unit_key'] ) ? absint( $_GET['unit_key'] ) : 0;

// Aggregate: one row per bed type (must contain beds, price_min, price_max, size_min, size_max)
$v2_units_by_beds = function_exists( 'pera_v2_units_aggregate_by_beds' )
  ? (array) pera_v2_units_aggregate_by_beds( $property_id )
  : array();

// Defensive sort: ensure cheapest is first (price_min ascending)
if ( ! empty( $v2_units_by_beds ) ) {
  usort( $v2_units_by_beds, function( $a, $b ) {
    return (int) ( $a['price_min'] ?? 0 ) <=> (int) ( $b['price_min'] ?? 0 );
  } );
}

/**
 * Resolve selected unit row:
 * 1) explicit selection (unit_key) if possible
 * 2) fallback to cheapest
 */
$selected_v2_unit = null;

// Explicit selection by unit_key (beds)
if ( $unit_key > 0 ) {

  // Prefer your dedicated selector if it exists
  if ( function_exists( 'pera_v2_get_selected_unit' ) ) {
    $selected_v2_unit = pera_v2_get_selected_unit( $property_id, $unit_key );
  }

  // Fallback: if selector failed but we have aggregation, match by beds
  if ( ! is_array( $selected_v2_unit ) && ! empty( $v2_units_by_beds ) ) {
    foreach ( $v2_units_by_beds as $row ) {
      if ( (int) ( $row['beds'] ?? 0 ) === $unit_key ) {
        $selected_v2_unit = $row;
        break;
      }
    }
  }
}

// Cheapest fallback (no unit_key or invalid selection)
if ( ! is_array( $selected_v2_unit ) && ! empty( $v2_units_by_beds ) ) {
  $selected_v2_unit = $v2_units_by_beds[0];
  $unit_key = (int) ( $selected_v2_unit['beds'] ?? 0 ); // keep context consistent
}

/* -----------------------------
   Beds label for hero pills
------------------------------ */
$selected_beds     = 0;
$hero_bed_label_v2 = '';

if ( is_array( $selected_v2_unit ) ) {
  $selected_beds = (int) ( $selected_v2_unit['beds'] ?? 0 );
} elseif ( $unit_key > 0 ) {
  $selected_beds = (int) $unit_key;
}

if ( $selected_beds > 0 ) {
  $hero_bed_label_v2 = $selected_beds . '+1';
}


/* ------------------------------------------------------
   HERO DISPLAY VALUES (V2-only)
------------------------------------------------------ */
$hero_price_min = 0;
$hero_price_max = 0;

$selected_size_min  = 0;
$selected_size_max  = 0;
$selected_size_text = '';

if ( is_array( $selected_v2_unit ) ) {

  $hero_price_min = (int) ( $selected_v2_unit['price_min'] ?? 0 );
  $hero_price_max = (int) ( $selected_v2_unit['price_max'] ?? 0 );

  $selected_size_min = (float) ( $selected_v2_unit['size_min'] ?? 0 );
  $selected_size_max = (float) ( $selected_v2_unit['size_max'] ?? 0 );

  // Optional size pill text
  if ( $selected_size_min > 0 && $selected_size_max > 0 ) {
    if ( abs( $selected_size_max - $selected_size_min ) < 0.01 ) {
      $selected_size_text = number_format_i18n( $selected_size_min, 0 ) . ' m²';
    } else {
      $selected_size_text =
        number_format_i18n( $selected_size_min, 0 ) . '–' .
        number_format_i18n( $selected_size_max, 0 ) . ' m²';
    }
  } elseif ( $selected_size_min > 0 ) {
    $selected_size_text = number_format_i18n( $selected_size_min, 0 ) . ' m²';
  }
}

/* ------------------------------------------------------
   "FROM" LABEL RULE (Special taxonomy)
   - Show only for projects (and not resales)
------------------------------------------------------ */
$hero_show_from = ( $is_project && ! $is_resale );



/* 7) PROJECT-ONLY FACTS (ACF) — show only for projects (not resales) */
$number_of_units = 0;
$compound_size   = 0;

if ( $is_project && ! $is_resale && function_exists( 'get_field' ) ) {
  $number_of_units = (int) get_field( 'number_of_units', $property_id );
  $compound_size   = (int) get_field( 'land_size', $property_id );
}


/* 8) COMPLETION / READY LABEL (ACF: completion_date) */
$completion_raw = function_exists( 'get_field' ) ? get_field( 'completion_date', $property_id ) : '';
$ready_label    = '';

if ( ! empty( $completion_raw ) ) {

  $completion_raw = trim( (string) $completion_raw );
  $ready_date     = null;

  if ( preg_match( '/^\d{8}$/', $completion_raw ) ) {
    $ready_date = DateTime::createFromFormat( 'Ymd', $completion_raw, wp_timezone() );
  } elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $completion_raw ) ) {
    $ready_date = DateTime::createFromFormat( 'Y-m-d', $completion_raw, wp_timezone() );
  } else {
    try {
      $ready_date = new DateTime( $completion_raw, wp_timezone() );
    } catch ( Exception $e ) {
      $ready_date = null;
    }
  }

  if ( $ready_date instanceof DateTime ) {
    $today = new DateTime( 'today', wp_timezone() );
    $ready_label = ( $ready_date <= $today )
      ? 'Key-ready'
      : 'Ready on ' . $ready_date->format( 'm/y' );
  }
}
/* ======================================================
   KEY FIGURES (V2) — pills use integers only
   ====================================================== */

// Bedrooms int for key figures: prefer selected unit, fallback to unit_key
$key_beds_int = 0;

if ( is_array( $selected_v2_unit ) ) {
  $key_beds_int = (int) ( $selected_v2_unit['beds'] ?? 0 );
} elseif ( $unit_key > 0 ) {
  $key_beds_int = (int) $unit_key;
}

// Gross size for key figures: selected unit size range (already computed earlier)
$key_size_text = $selected_size_text;

// If size text empty, build from selected unit values (defensive)
if ( $key_size_text === '' && is_array( $selected_v2_unit ) ) {

  $smin = (float) ( $selected_v2_unit['size_min'] ?? 0 );
  $smax = (float) ( $selected_v2_unit['size_max'] ?? 0 );

  if ( $smin > 0 && $smax > 0 ) {
    $key_size_text = ( abs( $smax - $smin ) < 0.01 )
      ? number_format_i18n( $smin, 0 ) . ' m²'
      : number_format_i18n( $smin, 0 ) . '–' . number_format_i18n( $smax, 0 ) . ' m²';
  } elseif ( $smin > 0 ) {
    $key_size_text = number_format_i18n( $smin, 0 ) . ' m²';
  }
}

/**
 * Other types (projects only):
 * Must list ALL bed types as INTEGERS (for pill display).
 * Uses $v2_units_by_beds aggregation ("one row per bed type").
 */
$key_other_beds = array();

if ( ! empty( $v2_units_by_beds ) && is_array( $v2_units_by_beds ) ) {
  foreach ( $v2_units_by_beds as $row ) {
    $b = (int) ( $row['beds'] ?? 0 );
    if ( $b > 0 ) {
      $key_other_beds[] = $b;
    }
  }
}

// De-dupe + sort numerically
$key_other_beds = array_values( array_unique( $key_other_beds ) );
sort( $key_other_beds );

// Remove currently selected bed count from other types (optional but usually desired)
if ( $key_beds_int > 0 ) {
  $key_other_beds = array_values( array_diff( $key_other_beds, array( $key_beds_int ) ) );
}

// Facts visibility (V2 aware)
$has_facts_v2 = (
  $display_type !== '' ||
  $key_beds_int > 0 ||
  $key_size_text !== '' ||
  ( ! empty( $district ) && ! is_wp_error( $district ) ) ||
  $ready_label !== '' ||
  ( $is_project && ! $is_resale && ( ! empty( $key_other_beds ) || $number_of_units > 0 || $compound_size > 0 ) )
);


/* 9) MAIN GALLERY (ACF: main_gallery) — returns image arrays */
$gallery_ids   = array();
$gallery_items = array();
$photo_count   = 0;

$main_gallery = function_exists( 'get_field' ) ? get_field( 'main_gallery', $property_id ) : array();

if ( is_array( $main_gallery ) && ! empty( $main_gallery ) ) {

  foreach ( $main_gallery as $img ) {

    if ( is_array( $img ) && ! empty( $img['ID'] ) ) {
      $gallery_ids[]   = (int) $img['ID'];
      $gallery_items[] = $img;
    }
  }

  $gallery_ids = array_values( array_unique( $gallery_ids ) );
  $photo_count = count( $gallery_ids );
}


/* 10) FACILITIES (ACF checkbox) */
$facilities = function_exists( 'get_field' ) ? get_field( 'facilities', $property_id ) : array();
$facilities = is_array( $facilities )
  ? array_values( array_filter( array_map( 'trim', $facilities ) ) )
  : array();

$has_facilities = ! empty( $facilities );

/* 11) FLOOR PLANS (ACF) */
$fp_enabled = function_exists( 'get_field' ) ? (bool) get_field( 'fp_check_box', $property_id ) : false;

$floor_plans_heading = function_exists( 'get_field' ) ? (string) get_field( 'floor_plans_heading', $property_id ) : '';
$floor_plans_text    = function_exists( 'get_field' ) ? get_field( 'floor_plans_custom_text', $property_id ) : '';
$floor_plans         = function_exists( 'get_field' ) ? get_field( 'floor_plans', $property_id ) : array();

$floor_plan_items = array();

if ( $fp_enabled && is_array( $floor_plans ) && ! empty( $floor_plans ) ) {
  foreach ( $floor_plans as $img ) {
    if ( is_array( $img ) && ! empty( $img['ID'] ) ) {
      $floor_plan_items[] = $img;
    }
  }
}

$has_floor_plans = ( $fp_enabled && ( ! empty( $floor_plan_items ) || ! empty( $floor_plans_text ) ) );

/* 12) FURTHER READING (ACF relationship to Posts) */
$further_reading_heading = function_exists( 'get_field' ) ? (string) get_field( 'further_reading_heading', $property_id ) : '';
$further_reading_text    = function_exists( 'get_field' ) ? (string) get_field( 'further_reading_text', $property_id ) : '';

$fr_posts = function_exists( 'get_field' ) ? get_field( 'post_heading', $property_id ) : array();
$post_ids = array();

if ( is_array( $fr_posts ) ) {
  foreach ( $fr_posts as $p ) {
    if ( is_numeric( $p ) ) {
      $post_ids[] = (int) $p;
    } elseif ( is_object( $p ) && ! empty( $p->ID ) ) {
      $post_ids[] = (int) $p->ID;
    }
  }
}

$post_ids = array_values( array_unique( array_filter( $post_ids ) ) );
$post_ids = array_slice( $post_ids, 0, 4 );

$has_further_reading = ! empty( $post_ids );
?>

<!-- =====================================
  HERO (SINGLE PROPERTY)
  ===================================== -->
<section class="hero hero--left property-hero">

  <?php if ( $hero_img_id || $hero_img_url ) : ?>

    <?php
    if ( $hero_img_id ) {
      echo wp_get_attachment_image(
        $hero_img_id,
        'full',
        false,
        array(
          'class'    => 'hero-media',
          'loading'  => 'eager',
          'decoding' => 'async',
          'alt'      => $hero_img_alt,
        )
      );
    } else {
      ?>
      <img
        class="hero-media"
        src="<?php echo esc_url( $hero_img_url ); ?>"
        alt="<?php echo esc_attr( $hero_img_alt ); ?>"
        loading="eager"
        decoding="async"
      />
      <?php
    }
    ?>

    <div class="hero-overlay" aria-hidden="true"></div>

  <?php endif; ?>

  <div class="hero-content property-hero__content">

    <div class="property-hero__pills">
      <?php if ( ! empty( $district ) && ! is_wp_error( $district ) ) : ?>
        <?php if ( $district_link && ! is_wp_error( $district_link ) ) : ?>
          <a class="pill pill--green" href="<?php echo esc_url( $district_link ); ?>">
            <?php echo esc_html( $district[0]->name ); ?>
          </a>
        <?php else : ?>
          <span class="pill pill--green"><?php echo esc_html( $district[0]->name ); ?></span>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ( ! empty( $region ) && ! is_wp_error( $region ) ) : ?>
        <?php if ( $region_link && ! is_wp_error( $region_link ) ) : ?>
          <a class="pill pill--green" href="<?php echo esc_url( $region_link ); ?>">
            <?php echo esc_html( $region[0]->name ); ?>
          </a>
        <?php else : ?>
          <span class="pill pill--green"><?php echo esc_html( $region[0]->name ); ?></span>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ( current_user_can( 'manage_options' ) && $project_name ) : ?>
        <span class="pill pill--green pill--admin"><?php echo esc_html( $project_name ); ?></span>
      <?php endif; ?>
    </div>

    <h1 class="property-hero__title"><?php echo esc_html( $title ); ?></h1>

    <?php if ( $excerpt ) : ?>
      <p class="property-hero__excerpt text-light">
        <?php echo esc_html( $excerpt ); ?>
      </p>
    <?php endif; ?>

    <div class="property-hero__meta">

    <!-- PRICE (V2 only) -->
        <div class="property-hero__price price--xl">

          <?php if ( $hero_price_min > 0 ) : ?>
        
            <span class="property-price__current">
              <?php if ( $hero_show_from ) : ?>
                <span class="property-price__from">
                  <?php echo esc_html__( 'From', 'hello-elementor-child' ); ?>
                </span>
              <?php endif; ?>
        
              <?php echo '$' . number_format_i18n( $hero_price_min ); ?>
            </span>
        
            <?php
            // Optional: show a range if you want (only when max is meaningful and > min)
            // if ( $hero_price_max > $hero_price_min ) :
            //   echo '<span class="property-price__range text-soft text-xs">Up to $' . number_format_i18n( $hero_price_max ) . '</span>';
            // endif;
            ?>
        
          <?php else : ?>
        
            <span class="property-price__current">
              <?php echo esc_html__( 'Contact us for pricing', 'hello-elementor-child' ); ?>
            </span>
        
          <?php endif; ?>
        
        </div>

        <!-- KEY FACTS (pills) — V2-aware -->
        <div class="property-hero__facts" role="list">
        
          <?php if ( $hero_bed_label_v2 ) : ?>
            <span class="pill pill--green" aria-label="<?php echo esc_attr( $hero_bed_label_v2 ); ?>">
              <svg class="pill__icon" aria-hidden="true" focusable="false" width="14" height="14" style="margin-right:6px; vertical-align:-2px;">
                <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
              </svg>
              <?php echo esc_html( $hero_bed_label_v2 ); ?>
            </span>
          <?php endif; ?>
        
          <?php if ( $selected_size_text ) : ?>
            <span class="pill pill--green" aria-label="<?php echo esc_attr__( 'Selected unit gross size', 'hello-elementor-child' ); ?>">
              <svg class="pill__icon" aria-hidden="true" focusable="false" width="14" height="14" style="margin-right:6px; vertical-align:-2px;">
                <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-arrows-expand' ); ?>"></use>
              </svg>
              <?php echo esc_html( $selected_size_text ); ?>
            </span>
          <?php endif; ?>
        
          <?php if ( $ready_label ) : ?>
            <span class="pill pill--green">
              <svg aria-hidden="true" focusable="false" width="14" height="14" style="margin-right:6px; vertical-align:-2px;">
                <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-key-ready' ); ?>"></use>
              </svg>
              <?php echo esc_html( $ready_label ); ?>
            </span>
          <?php endif; ?>
        
        </div>

     
      <!-- CTA -->
      <div class="property-hero__cta">
        <a class="btn btn--solid btn--blue" href="#contact-form">Request details</a>

        <?php
        $whatsapp_number = '905452054356'; // international format, no "+"
        $wa_text = sprintf(
          'Hello Pera Property, I would like details for the listing: "%s". Ref: %d',
          $title,
          (int) $property_id
        );
        $wa_url = 'https://wa.me/' . $whatsapp_number . '?text=' . rawurlencode( $wa_text );
        ?>

        <a
          class="btn btn--solid btn--green"
          href="<?php echo esc_url( $wa_url ); ?>"
          target="_blank"
          rel="noopener"
        >
          <svg class="icon" aria-hidden="true" width="18" height="18">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-whatsapp' ); ?>"></use>
          </svg> WhatsApp
        </a>
      </div>

    </div><!-- /.property-hero__meta -->

  </div><!-- /.hero-content -->
</section>


<!-- =====================================
  OVERVIEW
  ===================================== -->
<section class="section section-soft property-overview" id="property-overview">

  <?php
  $summary_heading = function_exists( 'get_field' ) ? (string) get_field( 'project_summary_heading', $property_id ) : '';
  $summary_html    = function_exists( 'get_field' ) ? get_field( 'project_summary', $property_id ) : '';
  ?>

  <div class="grid-2--tight" style="align-items:start;">

    <!-- LEFT -->
    <div class="property-overview__main">

      <h2><?php echo esc_html( $summary_heading ?: 'Overview' ); ?></h2>

      <?php if ( $summary_html ) : ?>

        <?php
        $summary_html = apply_filters( 'the_content', $summary_html );

        // Ensure FIRST list has checklist class
        if ( preg_match( '/<ul\b/i', $summary_html ) ) {
          $summary_html = preg_replace( '/<ul\b(?![^>]*\bclass=)/i', '<ul class="checklist"', $summary_html, 1 );
          $summary_html = preg_replace( '/<ul\b([^>]*\bclass=")([^"]*)"/i', '<ul$1$2 checklist"', $summary_html, 1 );
        } elseif ( preg_match( '/<ol\b/i', $summary_html ) ) {
          $summary_html = preg_replace( '/<ol\b(?![^>]*\bclass=)/i', '<ol class="checklist"', $summary_html, 1 );
          $summary_html = preg_replace( '/<ol\b([^>]*\bclass=")([^"]*)"/i', '<ol$1$2 checklist"', $summary_html, 1 );
        }

        $tick_svg = '<svg class="icon-check checklist-icon" aria-hidden="true" focusable="false" width="18" height="18">
          <use href="' . esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-check' ) . '"></use>
        </svg>';

        $summary_html = preg_replace(
          '/<li(\s[^>]*)?>/i',
          '$0' . $tick_svg,
          $summary_html
        );
        ?>

        <div class="property-overview__summary">
          <?php echo $summary_html; ?>
        </div>

      <?php endif; ?>

    </div><!-- /.property-overview__main -->

            <!-- RIGHT -->
            <aside class="property-overview__aside">
            
              <?php if ( $has_facts_v2 ) : ?>
                <div class="card-shell">
            
                  <h3 style="margin:0;">Key figures</h3>
            
                  <?php if ( $quick_facts_intro ) : ?>
                    <p class="text-soft" style="margin:4px 0 12px;">
                      <?php echo esc_html( $quick_facts_intro ); ?>
                    </p>
                  <?php endif; ?>
            
                  <div class="property-facts">
            
                    <!-- =========================
                         1) TYPE (resales + projects)
                    ========================== -->
                    <?php if ( $display_type ) : ?>
                      <div class="property-fact">
                        <svg class="icon" aria-hidden="true" width="18" height="18">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-home' ); ?>"></use>
                        </svg>
                        <span class="fact-label">Type</span>
                        <span class="fact-value"><?php echo esc_html( $display_type ); ?></span>
                      </div>
                    <?php endif; ?>
            
                    <!-- =========================
                         2) BEDROOMS (resales + projects) — pill number only
                    ========================== -->
                    <?php if ( $key_beds_int > 0 ) : ?>
                      <div class="property-fact">
                        <svg class="icon" aria-hidden="true" width="18" height="18">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                        </svg>
                        <span class="fact-label">Bedrooms</span>
                        <span class="fact-value">
                          <span class="pill pill--green"><?php echo esc_html( (int) $key_beds_int ); ?></span>
                        </span>
                      </div>
                    <?php endif; ?>
            
                    <!-- =========================
                         3) GROSS SIZE (resales + projects)
                    ========================== -->
                    <?php if ( $key_size_text ) : ?>
                      <div class="property-fact">
                        <svg class="icon" aria-hidden="true" width="18" height="18">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-arrows-expand' ); ?>"></use>
                        </svg>
                        <span class="fact-label">Gross size</span>
                        <span class="fact-value"><?php echo esc_html( $key_size_text ); ?></span>
                      </div>
                    <?php endif; ?>
            
            
                    <?php if ( $is_project && ! $is_resale ) : ?>
            
                      <!-- =========================
                           4) OTHER TYPES (projects only) — row of numeric pills
                      ========================== -->
                      <?php if ( ! empty( $key_other_beds ) ) : ?>
                        <div class="property-fact">
                          <svg class="icon" aria-hidden="true" width="18" height="18">
                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                          </svg>
                          <span class="fact-label">Other types</span>
                          <span class="fact-value">
                            <span class="fact-pills fact-pills--row">
                              <?php foreach ( $key_other_beds as $beds_int ) : ?>
                                <span class="pill pill--outline"><?php echo esc_html( (int) $beds_int ); ?></span>
                              <?php endforeach; ?>
                            </span>
                          </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- =========================
                             5) UNITS (projects only)
                        ========================== -->
                        <?php if ( $number_of_units > 0 ) : ?>
                          <div class="property-fact">
                            <svg class="icon" aria-hidden="true" width="18" height="18">
                              <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-building' ); ?>"></use>
                            </svg>
                            <span class="fact-label">Units</span>
                            <span class="fact-value"><?php echo esc_html( number_format_i18n( $number_of_units ) ); ?></span>
                          </div>
                        <?php endif; ?>
                      
                      
            
                      <!-- =========================
                           5) COMPOUND SIZE (projects only)
                      ========================== -->
                      <?php if ( $compound_size > 0 ) : ?>
                        <div class="property-fact">
                          <svg class="icon" aria-hidden="true" width="18" height="18">
                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-layout-grid' ); ?>"></use>
                          </svg>
                          <span class="fact-label">Compound size</span>
                          <span class="fact-value"><?php echo esc_html( number_format_i18n( $compound_size ) ); ?> m²</span>
                        </div>
                      <?php endif; ?>
            
                    <?php endif; ?>
            
            
                    <!-- =========================
                         District (resales + projects)
                    ========================== -->
                    <?php if ( ! empty( $district ) && ! is_wp_error( $district ) ) : ?>
                      <div class="property-fact">
                        <svg class="icon" aria-hidden="true" width="18" height="18">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-map' ); ?>"></use>
                        </svg>
                        <span class="fact-label">District</span>
                        <span class="fact-value"><?php echo esc_html( $district[0]->name ); ?></span>
                      </div>
                    <?php endif; ?>
            
                    <!-- =========================
                         Status (resales + projects)
                    ========================== -->
                    <?php if ( $ready_label ) : ?>
                      <div class="property-fact">
                        <svg class="icon" aria-hidden="true" width="18" height="18">
                          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-door-open' ); ?>"></use>
                        </svg>
                        <span class="fact-label">Status</span>
                        <span class="fact-value"><?php echo esc_html( $ready_label ); ?></span>
                      </div>
                    <?php endif; ?>
            
                  </div><!-- .property-facts -->
                </div><!-- .card-shell -->
              <?php endif; ?>
            
            </aside>


  </div><!-- /.grid-2 -->

  <!-- TAGS (FULL-WIDTH UNDER BOTH COLUMNS) -->
  <?php if ( ! empty( $property_tags ) && ! is_wp_error( $property_tags ) ) : ?>

    <div class="grid-2--tight mt-sm" style="align-items:start;">
      <!-- LEFT: TAGS -->
      <div>
        <?php foreach ( $property_tags as $tag ) :
          $tag_link = get_term_link( $tag );
          ?>
          <?php if ( ! is_wp_error( $tag_link ) && $tag_link ) : ?>
            <a class="pill pill--green" href="<?php echo esc_url( $tag_link ); ?>">
              <?php echo esc_html( $tag->name ); ?>
            </a>
          <?php else : ?>
            <span class="pill pill--green"><?php echo esc_html( $tag->name ); ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- RIGHT: META -->
      <div class="text-soft text-xs">
        <?php $updated_date = get_the_modified_date( 'j F Y', $property_id ); ?>
        This listing was last updated on <?php echo esc_html( $updated_date ); ?>.
        Ref: <?php echo esc_html( $property_id ); ?>
      </div>
    </div>

  <?php endif; ?>

</section>
<?php
if ( function_exists( 'pera_v2_render_units_price_table' ) ) {
  pera_v2_render_units_price_table( $property_id );
}
?>

<!-- =====================================
   GALLERY (ANCHOR TARGET)
   ===================================== -->
<section class="section property-gallery" id="property-gallery">
  <div class="container">
    <?php
    $has_gallery = ( ! empty( $gallery_ids ) && is_array( $gallery_ids ) && (int) $photo_count > 0 );

    if ( $has_gallery ) :

      // Split into two rows (alternating), only keep valid attachment IDs.
      $row1 = array();
      $row2 = array();

      foreach ( $gallery_ids as $i => $img_id ) {
        $img_id = absint( $img_id );
        if ( ! $img_id ) { continue; }

        if ( $i % 2 === 0 ) { $row1[] = $img_id; }
        else { $row2[] = $img_id; }
      }

      $has_rows = ( ! empty( $row1 ) || ! empty( $row2 ) );

      if ( $has_rows ) :
    ?>

      <div class="property-gallery-shell" aria-label="Property photos">

        <button
          class="property-gallery-nav property-gallery-nav--prev"
          type="button"
          aria-label="Scroll left"
        >
          <svg aria-hidden="true" width="22" height="22">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-chevron-left' ); ?>"></use>
          </svg>
        </button>

        <button
          class="property-gallery-nav property-gallery-nav--next"
          type="button"
          aria-label="Scroll right"
        >
          <svg aria-hidden="true" width="22" height="22">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-chevron-right' ); ?>"></use>
          </svg>
        </button>

        <div class="property-gallery-strip" aria-label="Gallery photos">

          <?php
          /**
           * Render one row of gallery items (no lightbox, no JS hooks).
           *
           * @param int[] $row_ids
           */
          $render_gallery_row = function( array $row_ids ) use ( $title ) {

            if ( empty( $row_ids ) ) { return; }

            echo '<div class="property-gallery-strip__row" role="list">';

            foreach ( $row_ids as $img_id ) {

              $img_id = absint( $img_id );
              if ( ! $img_id ) { continue; }

              // Skip if attachment is missing
              if ( ! wp_get_attachment_image_url( $img_id, 'full' ) ) { continue; }

              $alt_meta  = trim( (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) );
              $alt_label = $alt_meta !== '' ? $alt_meta : $title;

              echo '<div class="property-gallery-strip__item" role="listitem" aria-label="' . esc_attr( $alt_label ) . '">';

              echo wp_get_attachment_image(
                $img_id,
                'pera-card',
                false,
                array(
                  'loading'  => 'lazy',
                  'decoding' => 'async',
                  'alt'      => $alt_label,
                )
              );

              echo '</div>';
            }

            echo '</div>';
          };

          $render_gallery_row( $row1 );
          $render_gallery_row( $row2 );
          ?>

        </div><!-- /.property-gallery-strip -->
      </div><!-- /.property-gallery-shell -->

    <?php
      else :
        echo '<p class="text-soft" style="margin:0;">No gallery images available.</p>';
      endif;

    else :
      echo '<p class="text-soft" style="margin:0;">No gallery images available.</p>';
    endif;
    ?>
  </div><!-- /.container -->
</section>




<!-- =====================================
  ABOUT + LOCATION
  ===================================== -->
<section class="section section-soft property-location" id="property-location">

  <?php
  $about_heading     = function_exists( 'get_field' ) ? (string) get_field( 'whats_special_heading', $property_id ) : '';
  $about_html        = function_exists( 'get_field' ) ? get_field( 'about_this_project', $property_id ) : '';
  $location_heading  = function_exists( 'get_field' ) ? (string) get_field( 'location_info_heading', $property_id ) : '';
  $distances_html    = function_exists( 'get_field' ) ? get_field( 'distances', $property_id ) : '';
  $map               = function_exists( 'get_field' ) ? get_field( 'map', $property_id ) : null;

  $has_about     = ! empty( $about_html );
  $has_distances = ! empty( $distances_html );
  $has_map       = is_array( $map ) && ( ! empty( $map['lat'] ) && ! empty( $map['lng'] ) );
  ?>

  <div class="grid-2--tight" style="align-items:start;">

    <!-- LEFT: TEXT -->
    <div class="property-location__main">
      <?php if ( $has_about ) : ?>
        <h2><?php echo esc_html( $about_heading ?: 'About this project' ); ?></h2>
        <div class="property-location__about">
          <?php echo apply_filters( 'the_content', $about_html ); ?>
        </div>
      <?php endif; ?>

      <?php if ( $has_facilities ) : ?>
        <div class="property-facilities">
          <h3>Facilities</h3>
          <div class="property-facilities__pills">
            <?php foreach ( $facilities as $facility ) : ?>
              <span class="pill pill--green">
                <?php echo esc_html( $facility ); ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT: MAP -->
    <aside class="property-location__aside">
      <?php if ( $has_map ) : ?>
        <?php
          $lat      = (float) $map['lat'];
          $lng      = (float) $map['lng'];
          $address  = ! empty( $map['address'] ) ? (string) $map['address'] : '';
          $gmaps_url = 'https://www.google.com/maps?q=' . rawurlencode( $lat . ',' . $lng );
          $embed_url = 'https://www.google.com/maps?q=' . rawurlencode( $lat . ',' . $lng ) . '&z=15&output=embed';
        ?>
    
        <div class="card-shell property-map-card">
          <h3 class="property-map-card__title">Map</h3>
    
          <?php if ( $address ) : ?>
            <p class="property-map-card__address text-soft"><?php echo esc_html( $address ); ?></p>
          <?php endif; ?>
    
          <div class="media-frame media-frame--map property-map-card__frame">
            <iframe
              class="media-embed--map"
              src="<?php echo esc_url( $embed_url ); ?>"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen
              title="Location map"
            ></iframe>
          </div>
    
          <div class="property-map-card__actions">
            <a class="btn btn--solid btn--green" href="<?php echo esc_url( $gmaps_url ); ?>" target="_blank" rel="noopener">
              Open in Google Maps
            </a>
          </div>
    
          <?php if ( $has_distances ) : ?>
            <div class="property-map-card__distances">
              <h4 class="property-map-card__distances-title">
                <?php echo esc_html( $location_heading ?: 'Location & distances' ); ?>
              </h4>
    
              <div class="archive-hero-desc" data-collapsed="true">
                <div id="property-distances-content" class="archive-hero-desc__content text-soft">
                  <?php echo apply_filters( 'the_content', $distances_html ); ?>
                </div>
    
                <button
                  type="button"
                  class="pill pill--green archive-hero-desc__toggle"
                  aria-expanded="false"
                  aria-controls="property-distances-content"
                >
                  Read more
                </button>
              </div>
            </div>
          <?php endif; ?>
    
        </div>
    
      <?php else : ?>
    
        <div class="card-shell property-map-card">
          <h3 class="property-map-card__title">Map</h3>
          <p class="text-soft" style="margin:0;">Map location is not available for this listing.</p>
        </div>
    
      <?php endif; ?>
    </aside>


  </div>
</section>


<?php if ( $has_floor_plans ) : ?>
<section class="section section-soft property-floor-plans" id="property-floor-plans">
  <div class="container">

    <h2><?php echo esc_html( $floor_plans_heading ?: 'Floor plans' ); ?></h2>

    <?php if ( ! empty( $floor_plans_text ) ) : ?>
      <div class="property-floor-plans__intro text-soft">
        <?php echo apply_filters( 'the_content', $floor_plans_text ); ?>
      </div>
    <?php endif; ?>

    <?php if ( ! empty( $floor_plan_items ) ) : ?>

      <div class="media-grid property-floor-plans__grid">
        <?php foreach ( $floor_plan_items as $img ) :

          $img_id  = (int) $img['ID'];
          $full    = wp_get_attachment_image_url( $img_id, 'full' );
          if ( ! $full ) { continue; }

          $alt     = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
          $alt     = $alt ? $alt : $title;

          $caption = wp_get_attachment_caption( $img_id );
          ?>
          <a
              class="media-grid__item card-shell"
              href="<?php echo esc_url( $full ); ?>"
              aria-label="<?php echo esc_attr( $alt ); ?>"
              target="_blank"
              rel="noopener"
            >

              <?php
                echo wp_get_attachment_image(
                  $img_id,
                  'pera-card',
                  false,
                  array(
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'alt'      => $alt,
                  )
                );
              ?>
            </a>

        <?php endforeach; ?>
      </div>

    <?php else : ?>

      <p class="text-soft" style="margin:0;">Floor plans are available on request.</p>

    <?php endif; ?>

  </div>
</section>
<?php endif; ?>


<!-- =====================================
   ENQUIRY
   ===================================== -->
<section class="section section-soft" id="contact-form">
  <div class="content-panel-box">

    <!-- Top panel (2-col) -->
    <div class="content-panel-grid">

      <!-- LEFT: copy + checklist -->
      <div>
        <header class="section-header mb-sm">
          <h2><?php echo esc_html( $hero_heading ); ?></h2>
          <p class="mb-sm"><?php echo esc_html( $hero_intro ); ?></p>
        </header>

        <ul class="checklist mb-md">
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/logos-icons/icons.svg#icon-check"></use>
            </svg>
            Reliable, data-driven advice.
          </li>
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/logos-icons/icons.svg#icon-check"></use>
            </svg>
            On-the-ground Istanbul expertise.
          </li>
          <li>
            <svg class="icon icon-tick" aria-hidden="true">
              <use href="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/logos-icons/icons.svg#icon-check"></use>
            </svg>
            Multi-lingual support.
          </li>
        </ul>
      </div>

      <!-- RIGHT: media CTA -->
      <div class="media-frame media-frame--image-fill">
        <div class="media-frame__bg">
          <?php
            echo wp_get_attachment_image(
              55686,
              'pera-card',
              false,
              array(
                'class'    => 'media-frame__bg-img',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'alt'      => 'Isometric illustration of Beşiktaş',
              )
            );
          ?>
        </div>

        <div class="hero-overlay"></div>

        <div class="hero-content section--center">
          <h3 class="text-light">Speak with a Consultant</h3>

          <div class="hero-actions flex-center">
            <a href="https://www.peraproperty.com/contact-us/" class="btn btn--solid btn--blue">
              Book a consultation
            </a>

            <a
              href="https://wa.me/905452054356?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20discuss%20Istanbul%20real%20estate."
              class="btn btn--solid btn--green"
              target="_blank"
              rel="noopener"
            >
              Chat on WhatsApp
            </a>
          </div>
        </div>
      </div>

    </div><!-- /.content-panel-grid -->

    <hr class="content-panel-divider">

    <!-- Form -->
        <?php
          get_template_part(
            'parts/enquiry-form',
            null,
            array(
              'context'        => 'property',
              'heading'        => $form_heading,
              'intro'          => $form_intro,
              'submit_label'   => 'Request details',
              'form_context'   => 'property',
              'property_id'    => $property_id,
              'property_title' => $title,
              'property_url'   => $permalink,
            )
          );
        ?>

        <?php if ( isset( $_GET['sr_success'] ) && $_GET['sr_success'] === '1' ) : ?>
          <div class="form-success">
            Thank you – we have received your details. A Pera consultant will contact you shortly.
          </div>
        <?php endif; ?>
  </div><!-- /.content-panel-box -->
</section>

    <!-- =====================================
   OTHER PROPERTIES IN THIS REGION
   ===================================== -->
<section class="section section-related-properties" aria-label="Other properties in this region">
  <div class="container">

    <h2 class="section-title">Other properties in this region</h2>

    <?php
    $current_id = get_the_ID();

    // Region taxonomy (as per your card markup: region-*)
    $region_terms = get_the_terms( $current_id, 'region' );

    if ( ! empty( $region_terms ) && ! is_wp_error( $region_terms ) ) :

      $region_ids = wp_list_pluck( $region_terms, 'term_id' );

      $related = new WP_Query( array(
        'post_type'           => 'property',
        'posts_per_page'      => 4,
        'post_status'         => 'publish',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'post__not_in'        => array( $current_id ),
        'orderby'             => 'rand',
        'tax_query'           => array(
          array(
            'taxonomy' => 'region',
            'field'    => 'term_id',
            'terms'    => $region_ids,
          ),
        ),
      ) );

      if ( $related->have_posts() ) : ?>

        <div class="cards-slider cards-slider--sidebar">
          <div class="slider-track">

            <?php
            while ( $related->have_posts() ) :
              $related->the_post();

              // This is the critical part that makes the root element:
              // <article class="slider-card property-card ...">
              set_query_var( 'pera_property_card_args', array(
                'variant'       => 'sidebar',     // reuse the same card variant if you want identical styling
                'card_classes'  => 'slider-card', // ensures the <article> has slider-card on it
                'show_badges'   => true,
                'show_admin'    => true,
                'show_excerpt'  => true,
                'excerpt_words' => 18,
                'image_size'    => 'pera-card',
              ) );

              get_template_part( 'parts/property-card' );

            endwhile;

            set_query_var( 'pera_property_card_args', null );
            wp_reset_postdata();
            ?>

          </div>
        </div>

      <?php else : ?>

        <p class="text-soft" style="margin:0;">No other properties found in this region.</p>

      <?php endif; ?>

    <?php else : ?>

      <p class="text-soft" style="margin:0;">No region set for this property.</p>

    <?php endif; ?>

  </div>
</section>



<?php
/* ======================================================
   FURTHER READING (ACF Relationship → up to 4 posts)
   ====================================================== */
if ( $has_further_reading ) :

  $related_posts = new WP_Query( array(
    'post_type'           => 'post',
    'post__in'            => $post_ids,
    'orderby'             => 'post__in',
    'posts_per_page'      => 4,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
  ) );

  if ( $related_posts->have_posts() ) : ?>
    <section class="sidebar-block sidebar-block--related property-further-reading" id="property-further-reading">
      <div class="container">

        <div class="property-further-reading__head">
          <h2><?php echo esc_html( $further_reading_heading ?: 'Further reading' ); ?></h2>

          <?php if ( $further_reading_text ) : ?>
            <div class="text-soft property-further-reading__intro">
              <?php echo apply_filters( 'the_content', $further_reading_text ); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="cards-slider cards-slider--sidebar">
          <div class="slider-track">
            <?php
            while ( $related_posts->have_posts() ) :
              $related_posts->the_post();

              set_query_var( 'pera_post_card_args', array(
                'variant'       => 'sidebar',
                'card_classes'  => 'slider-card',
                'show_excerpt'  => true,
                'excerpt_words' => 22,
                'thumb_size'    => 'pera-card',
                'show_cat_pill' => true,
                'pill_class'    => 'pill pill--outline',
                'show_readmore' => false,
              ) );

              get_template_part( 'parts/post-card' );

            endwhile;

            set_query_var( 'pera_post_card_args', null );
            ?>
          </div>
        </div>

      </div>
    </section>
  <?php
  endif;

  wp_reset_postdata();

endif;
?>



<?php endwhile; ?>
<?php else : ?>

  <div class="container">
    <p>Listing not found.</p>
  </div>

<?php endif; ?>

</main>


<script>
/* ==========================================================
   PERA PROPERTY — GALLERY CHEVRON SCROLL + READ MORE TOGGLE
   (Drag removed, lightbox removed)
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* ==========================================================
     1) "READ MORE / LESS" TOGGLE
     ========================================================== */
  function initArchiveHeroToggle() {
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.archive-hero-desc__toggle');
      if (!btn) return;

      const wrap = btn.closest('.archive-hero-desc');
      if (!wrap) return;

      const isCollapsed = wrap.getAttribute('data-collapsed') === 'true';

      wrap.setAttribute('data-collapsed', isCollapsed ? 'false' : 'true');
      btn.setAttribute('aria-expanded', String(isCollapsed));
      btn.textContent = isCollapsed ? 'Read less' : 'Read more';
    });
  }

  /* ==========================================================
     2) PROPERTY GALLERY STRIP (chevrons only)
     ========================================================== */
  function initGalleryChevronScroll() {
    const shell = document.querySelector('.property-gallery-shell');
    if (!shell) return;

    // No more data-gallery-* hooks — use existing classes only
    const strip  = shell.querySelector('.property-gallery-strip');
    const btnPrev = shell.querySelector('.property-gallery-nav--prev');
    const btnNext = shell.querySelector('.property-gallery-nav--next');

    if (!strip) return;

    function scrollByAmount(dir) {
      const amount = Math.max(240, Math.round(strip.clientWidth * 0.8));
      strip.scrollBy({ left: dir * amount, behavior: 'smooth' });
    }

    if (btnPrev) btnPrev.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      scrollByAmount(-1);
    });

    if (btnNext) btnNext.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      scrollByAmount(1);
    });
  }

  // Init
  initArchiveHeroToggle();
  initGalleryChevronScroll();

});
</script>



<?php get_footer(); ?>
