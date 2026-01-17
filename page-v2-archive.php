<?php
/**
 * Template Name: V2 Property Archive (AJAX)
 * Description: V2 property search page (progressive enhancement: SSR + AJAX).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/* ------------------------------------------------------------
   HERO TITLE / DESCRIPTION (page template safe)
   - Do NOT reference $property_query here (it is created later)
------------------------------------------------------------ */
$archive_title       = 'Property for sale in Istanbul';
$archive_description = 'We’ve got dozens of pages covering hundreds of options across almost all 48 districts of Istanbul. If you are looking for something more specific, be sure to contact us with your details, requirements, budget, etc. – take it easy and leave the rest to us.';
$archive_base_url = trailingslashit( get_permalink() );

// ------------------------------------------------------------
// 1) PAGED RESOLUTION (robust for /page/N/ and ?paged=N)
// ------------------------------------------------------------
$paged = (int) get_query_var( 'paged' );

// Fallback: some links/flows use ?paged=N on a page template
if ( $paged < 1 && isset( $_GET['paged'] ) ) {
  $paged = absint( $_GET['paged'] );
}

// Defensive fallback for WP's internal 'page' var
if ( get_query_var( 'page' ) ) {
  $paged = max( $paged, (int) get_query_var( 'page' ) );
}

$paged = max( 1, $paged );


/* ------------------------------------------------------------
   2) READ FILTERS FROM QUERY STRING (GET)
   (These variables drive:
   - SSR query for SEO pagination
   - initial UI state for controls
------------------------------------------------------------ */

// Property type (single-select taxonomy slug)
$current_type = isset( $_GET['property_type'] )
  ? sanitize_title( wp_unslash( (string) $_GET['property_type'] ) )
  : '';

// District (multi-select)
$current_district = array();
if ( isset( $_GET['district'] ) ) {
  $raw = $_GET['district'];
  if ( is_array( $raw ) ) {
    $current_district = array_map( 'sanitize_title', wp_unslash( $raw ) );
  } elseif ( $raw !== '' ) {
    $current_district = array( sanitize_title( wp_unslash( $raw ) ) );
  }
}

// Tags (multi-select)
$current_tag = array();
if ( isset( $_GET['property_tags'] ) ) {
  $raw = $_GET['property_tags'];
  if ( is_array( $raw ) ) {
    $current_tag = array_map( 'sanitize_title', wp_unslash( $raw ) );
  } elseif ( $raw !== '' ) {
    $current_tag = array( sanitize_title( wp_unslash( $raw ) ) );
  }
}

// Bedrooms (V2 single radio)
$selected_beds = isset($_GET['v2_beds'])
  ? sanitize_text_field( wp_unslash( (string) $_GET['v2_beds'] ) )
  : '';


// Keyword
$current_keyword = isset( $_GET['s'] )
  ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) )
  : '';
  
  // Sort (match V1: date_desc/date_asc/price_asc/price_desc)
$sort = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['sort'] ) ) : 'date_desc';
$allowed_sorts = array( 'date_desc', 'date_asc', 'price_asc', 'price_desc' );
if ( ! in_array( $sort, $allowed_sorts, true ) ) {
  $sort = 'date_desc';
}


/* ------------------------------------------------------------
   3) GLOBAL PRICE BOUNDS (V2 meta range)
------------------------------------------------------------ */
if ( function_exists( 'pera_v2_get_price_bounds' ) ) {
  $bounds = pera_v2_get_price_bounds();
  $global_min_price = isset( $bounds['min'] ) ? (int) $bounds['min'] : 100000;
  $global_max_price = isset( $bounds['max'] ) ? (int) $bounds['max'] : 5000000;
} else {
  $global_min_price = 100000;
  $global_max_price = 5000000;
}

/* ------------------------------------------------------------
   4) PRICE QS + SLIDER POSITIONS
   IMPORTANT:
   - slider UI can show bounds even when no price filter is active
   - hidden inputs should be empty unless HAS_PRICE_QS or user touches slider (JS)
------------------------------------------------------------ */
$qs_min = ( isset( $_GET['min_price'] ) && $_GET['min_price'] !== '' ) ? absint( $_GET['min_price'] ) : 0;
$qs_max = ( isset( $_GET['max_price'] ) && $_GET['max_price'] !== '' ) ? absint( $_GET['max_price'] ) : 0;
$has_price_qs = ( $qs_min > 0 || $qs_max > 0 );

// Default to global bounds when not specified
$slider_min = ( $qs_min > 0 ) ? $qs_min : $global_min_price;
$slider_max = ( $qs_max > 0 ) ? $qs_max : $global_max_price;

// Defensive swap/clamp
if ( $slider_min > $slider_max ) {
  $tmp = $slider_min;
  $slider_min = $slider_max;
  $slider_max = $tmp;
}

$slider_min = max( $global_min_price, (int) $slider_min );
$slider_max = min( $global_max_price, (int) $slider_max );

/* ------------------------------------------------------------
   SSR QUERY (SEO pagination baseline)
   - Mirrors the V2 AJAX filtering logic closely
   - Supports:
     district[] (IN)
     property_type (single)
     property_tags[] (IN)
     v2_beds (radio, via v2_index_flat token)
     min_price/max_price (via v2_price_usd_min/max overlap logic)
     keyword (s)
   - IMPORTANT: define meta_query as an array before pushing to it
------------------------------------------------------------ */

// Ensure these are defined (defensive)
if ( ! isset( $paged ) ) {
  $paged = max( 1, (int) get_query_var( 'paged' ) );
  if ( get_query_var( 'page' ) ) {
    $paged = max( $paged, (int) get_query_var( 'page' ) );
  }
}

$current_district = isset($current_district) && is_array($current_district) ? $current_district : array();
$current_tag      = isset($current_tag) && is_array($current_tag) ? $current_tag : array();
$current_type     = isset($current_type) ? (string) $current_type : '';
$selected_beds    = isset($selected_beds) ? (string) $selected_beds : '';
$current_keyword  = isset($current_keyword) ? (string) $current_keyword : '';

$has_price_qs = isset($has_price_qs) ? (bool) $has_price_qs : false;
$qs_min       = isset($qs_min) ? (int) $qs_min : 0;
$qs_max       = isset($qs_max) ? (int) $qs_max : 0;

// Base args
$args = array(
  'post_type'              => 'property',
  'post_status'            => 'publish',
  'posts_per_page'         => 12,
  'paged'                  => $paged,
  'orderby'                => 'date',
  'order'                  => 'DESC',
  'update_post_meta_cache' => false,
  'update_post_term_cache' => false,
);

// ------------------------------------------------------------
// TAX QUERY
// ------------------------------------------------------------
$tax_query = array();

// District (multi)
if ( ! empty( $current_district ) ) {
  $tax_query[] = array(
    'taxonomy' => 'district',
    'field'    => 'slug',
    'terms'    => $current_district,
    'operator' => 'IN',
  );
}

// Property type (single)
if ( $current_type !== '' ) {
  $tax_query[] = array(
    'taxonomy' => 'property_type',
    'field'    => 'slug',
    'terms'    => $current_type,
  );
}

// Tags (multi)
if ( ! empty( $current_tag ) ) {
  $tax_query[] = array(
    'taxonomy' => 'property_tags',
    'field'    => 'slug',
    'terms'    => $current_tag,
    'operator' => 'IN',
  );
}

if ( ! empty( $tax_query ) ) {
  $args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
}

// ------------------------------------------------------------
// META QUERY (V2)
// ------------------------------------------------------------
$meta_query = array();

// V2 Beds filter (radio) -> v2_index_flat LIKE "|2|"
if ( $selected_beds !== '' && preg_match( '/^\d+$/', $selected_beds ) ) {
  $b = (int) $selected_beds;

  $meta_query[] = array(
    'key'     => 'v2_index_flat',
    'value'   => '|' . $b . '|',
    'compare' => 'LIKE',
  );
}

// Price filter: overlap logic using v2_price_usd_min/max
// A property matches if:
// - v2_price_usd_max >= min (when min provided)
// - v2_price_usd_min <= max (when max provided)
if ( $has_price_qs ) {

  $min = ( $qs_min > 0 ) ? $qs_min : null;
  $max = ( $qs_max > 0 ) ? $qs_max : null;

  if ( $min !== null ) {
    $meta_query[] = array(
      'key'     => 'v2_price_usd_max',
      'value'   => $min,
      'type'    => 'NUMERIC',
      'compare' => '>=',
    );
  }

  if ( $max !== null ) {
    $meta_query[] = array(
      'key'     => 'v2_price_usd_min',
      'value'   => $max,
      'type'    => 'NUMERIC',
      'compare' => '<=',
    );
  }
}

if ( ! empty( $meta_query ) ) {
  $args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_query );
}

// ------------------------------------------------------------
// KEYWORD
// ------------------------------------------------------------
if ( $current_keyword !== '' ) {
  $args['s'] = $current_keyword;
}

// ------------------------------------------------------------
// SORTING (V2)
// - price sorts use v2_price_usd_min
// ------------------------------------------------------------
switch ( $sort ) {
  case 'price_asc':
    $args['meta_key'] = 'v2_price_usd_min';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'ASC';
    break;

  case 'price_desc':
    $args['meta_key'] = 'v2_price_usd_min';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'DESC';
    break;

  case 'date_asc':
    $args['orderby'] = 'date';
    $args['order']   = 'ASC';
    break;

  case 'date_desc':
  default:
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
    break;
}



$property_query = new WP_Query( $args );
$initial_count_text = sprintf(
  '%d properties found',
  (int) $property_query->found_posts
);
                            

?>


<main id="primary" class="site-main pera-lean pera-v2-archive content-rail">

<?php
/* ======================================================
   SEARCH-AWARE ARCHIVE HEADING (SAFE)
   - No dependency on $property_query
   ====================================================== */

// Detect whether this is a filtered/search result
$is_filtered_search = false;

$search_keys = array(
  's',
  'property_type',
  'district',
  'min_price',
  'max_price',
  'property_tags',
  'v2_beds',
);

foreach ( $search_keys as $key ) {
  if ( isset( $_GET[ $key ] ) ) {
    $val = $_GET[ $key ];

    // Treat arrays (district[], property_tags[]) as active if non-empty
    if ( is_array( $val ) && ! empty( $val ) ) {
      $is_filtered_search = true;
      break;
    }

    // Treat scalars as active if non-empty after trim
    if ( ! is_array( $val ) && trim( (string) $val ) !== '' ) {
      $is_filtered_search = true;
      break;
    }
  }
}

// Build heading (no count here — count belongs in #results-count)
if ( $is_filtered_search ) {
  $hero_title = 'Here are your search results';
  $hero_desc  = 'Use the filters below to refine your results.';
} else {
  $hero_title = $archive_title;
  $hero_desc  = $archive_description;
}

?>

    <!-- HERO -->
    <section class="hero hero--left">

          <?php
            // Taxonomy hero image (ACF term field). Expected: image array or ID.
            $term        = get_queried_object();
            $term_id     = ( isset($term->term_id) ) ? (int) $term->term_id : 0;
        
            // ACF term field key format: {taxonomy}_{term_id}
            $acf_ref     = ( $term_id && ! empty($term->taxonomy) ) ? ($term->taxonomy . '_' . $term_id) : '';
        
            $district_image = ( function_exists('get_field') && $acf_ref )
              ? get_field('district_image', $acf_ref)
              : null;
        
            // Support ACF returning array or ID
            $district_img_id = 0;
            if ( is_array($district_image) && ! empty($district_image['ID']) ) {
              $district_img_id = (int) $district_image['ID'];
            } elseif ( is_numeric($district_image) ) {
              $district_img_id = (int) $district_image;
            }
        
            // Optional fallback (use any attachment ID you like, or 0 for no image)
            $fallback_img_id = 55482;
            $hero_img_id     = $district_img_id ?: $fallback_img_id;
          ?>


        
          <?php if ( $hero_img_id ) : ?>
            <div class="hero__media" aria-hidden="true">
              <?php
                echo wp_get_attachment_image(
                  $hero_img_id,
                  'full',
                  false,
                  array(
                    'class'         => 'hero-media',
                    'loading'       => 'eager',
                    'decoding'      => 'async',
                    'fetchpriority' => 'high',
                  )
                );
              ?>
              <div class="hero-overlay" aria-hidden="true"></div>
            </div>
          <?php endif; ?>
        
          <div class="hero-content">
        
            <h1><?php echo esc_html( $hero_title ); ?></h1>
        
            <?php if ( ! empty( $hero_desc ) ) : ?>
              <p class="lead"><?php echo wp_kses_post( $hero_desc ); ?></p>
            <?php endif; ?>
        
            <div class="hero-actions">
              <?php
                $whatsapp_number = '905452054356'; // international format, no "+"
                $wa_text         = 'Hello Pera Property, I would like to discuss my property needs in Istanbul';
                $wa_url          = 'https://wa.me/' . $whatsapp_number . '?text=' . rawurlencode( $wa_text );
              ?>
        
              <a
                class="btn btn--solid btn--green"
                href="<?php echo esc_url( $wa_url ); ?>"
                target="_blank"
                rel="noopener"
              >
                <svg class="icon" aria-hidden="true" width="18" height="18">
                  <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-whatsapp' ); ?>"></use>
                </svg>
                WhatsApp
              </a>
            </div>
        
          </div>
        </section>

    <!-- FILTER BAR + RESULTS WRAPPER -->
    <section class="section section-soft">
        <div class="container">
            <div class="property-filters-wrapper">
                <header class="section-header">
                            <h2>Available properties</h2>
                            <p>Use the filters below to refine by district, property type, bedrooms and budget.</p>
                </header>
    
    
    
                      <!-- ======================================
                           FILTER FORM (V2)
                           - Keep names consistent with your JS:
                             v2_beds[] checkboxes
                           ====================================== -->
                    <form id="property-filter-form" 
                          class="property-filters" 
                          method="get" action="<?php echo esc_url( $archive_base_url ); ?>"

                        <input
                          type="hidden"
                          name="archive_base"
                          value="<?php echo esc_attr( trailingslashit( wp_parse_url( get_permalink(), PHP_URL_PATH ) ?: '/' ) ); ?>"
                        >

                        <div class="filter-row">
                            <!-- PRICE RANGE (V2, based on v2_price_usd_min) -->
                            <div class="filter-group">
                              <div class="filter-group__label">Price range (USD)</div>
                            
                              <div class="filter-price">
                                <div class="filter-price__slider">
                                  <input
                                    id="price-min-range"
                                    type="range"
                                    min="<?php echo esc_attr($global_min_price); ?>"
                                    max="<?php echo esc_attr($global_max_price); ?>"
                                    step="10000"
                                    value="<?php echo esc_attr($slider_min); ?>"
                                  >
                                  <input
                                    id="price-max-range"
                                    type="range"
                                    min="<?php echo esc_attr($global_min_price); ?>"
                                    max="<?php echo esc_attr($global_max_price); ?>"
                                    step="10000"
                                    value="<?php echo esc_attr($slider_max); ?>"
                                  >
                                </div>
                            
                                <input
                                  id="price-min-hidden"
                                  type="hidden"
                                  name="min_price"
                                  value="<?php echo esc_attr( $has_price_qs ? $slider_min : '' ); ?>"
                                >
                                
                                <input
                                  id="price-max-hidden"
                                  type="hidden"
                                  name="max_price"
                                  value="<?php echo esc_attr( $has_price_qs ? $slider_max : '' ); ?>"
                                >
            
                            
                                <div class="filter-price__summary">
                                  <span id="price-summary-text">
                                    <?php
                                      $min_label = '$' . number_format_i18n($slider_min);
                                      $max_label = '$' . number_format_i18n($slider_max);
                                      echo esc_html("{$min_label} — {$max_label}");
                                    ?>
                                  </span>
                                </div>
                              </div>
                            </div>
            
                            
                            <!-- PROPERTY TYPE (pills, single-select) -->
                            <div class="filter-group">
                                <div class="filter-group__label">Property type</div>
                        
                                <div class="filter-pill-row" role="radiogroup" aria-label="Property type">
                            <?php
                              $desired_types = array(
                                'apartments' => 'Apartment',
                                'villas'     => 'Villa',
                              );
                        
                              $all_active = empty( $current_type );
                            ?>
                        
                            <label class="pill pill--outline filter-pill <?php echo $all_active ? 'pill--active' : ''; ?>">
                              <input
                                type="radio"
                                name="property_type"
                                value=""
                                <?php checked( $all_active ); ?>
                              >
                              <span>All types</span>
                            </label>
                        
                            <?php foreach ( $desired_types as $slug => $label ) :
                              $term = get_term_by( 'slug', $slug, 'property_type' );
                              if ( ! $term || is_wp_error( $term ) ) continue;
                        
                              $is_active = ( $current_type === $term->slug );
                            ?>
                              <label class="pill pill--outline filter-pill <?php echo $is_active ? 'pill--active' : ''; ?>">
                                <input
                                  type="radio"
                                  name="property_type"
                                  value="<?php echo esc_attr( $term->slug ); ?>"
                                  <?php checked( $is_active ); ?>
                                >
                                <span><?php echo esc_html( $label ); ?></span>
                              </label>
                            <?php endforeach; ?>
                          </div>
                            </div>

                             
                            <!-- Bedrooms (V2 driver) -->
                            <div class="filter-group">
                              <div class="filter-group__label">Bedrooms</div>
                            
                              <div class="filter-pill-row" role="radiogroup" aria-label="Bedrooms">
                            
                                <?php
                                  $beds_options  = array(1,2,3,4,5,6);
                                  $selected_beds = isset($_GET['v2_beds']) ? sanitize_text_field( wp_unslash($_GET['v2_beds']) ) : '';
                                  $any_active    = ( $selected_beds === '' );
                                ?>
                        
                            
                                <?php foreach ( $beds_options as $b ) :
                                  $is_active = ( (string) $b === (string) $selected_beds );
                                ?>
                                  <label class="pill pill--outline filter-pill <?php echo $is_active ? 'pill--active' : ''; ?>">
                                    <input
                                      type="radio"
                                      name="v2_beds"
                                      value="<?php echo esc_attr( $b ); ?>"
                                      <?php checked( $is_active ); ?>
                                    >
                                    <span>
                                      <svg class="icon icon-bed" aria-hidden="true">
                                        <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                                      </svg>
                                      <?php echo esc_html( $b ); ?>
                                    </span>
                                  </label>
                                <?php endforeach; ?>
                            
                              </div>
                            </div>
            
                        </div>

                    <!-- ===== SECOND ROW: LOCATION + TAGS ===== -->
                    <div class="filter-row filter-row--stacked">

                        <!-- LOCATION (district pills) -->
                        <div class="filter-group filter-group--full">
                            <div class="filter-group__label">Location</div>

                            <div class="filter-pill-row">
                                <button
                                    type="button"
                                    class="pill pill--outline filter-pill filter-pill--all <?php echo empty( $current_district ) ? 'pill--active' : ''; ?>"
                                >
                                    <span>All locations</span>
                                </button>

                                <?php
                                $districts = get_terms( array(
                                    'taxonomy'   => 'district',
                                    'hide_empty' => true,
                                    'orderby'    => 'name',
                                ) );

                                if ( ! is_wp_error( $districts ) ) :
                                    foreach ( $districts as $district ) :
                                        $is_active = in_array( $district->slug, $current_district, true );
                                        ?>
                                        <label class="pill pill--outline filter-pill <?php echo $is_active ? 'pill--active' : ''; ?>">
                                            <input
                                                type="checkbox"
                                                name="district[]"
                                                value="<?php echo esc_attr( $district->slug ); ?>"
                                                <?php checked( $is_active ); ?>
                                            >
                                            <span><?php echo esc_html( $district->name ); ?> (<?php echo (int) $district->count; ?>)</span>
                                        </label>
                                    <?php endforeach;
                                endif;
                                ?>
                            </div>
                        </div>

                        <!-- TAGS (property_tags pills) -->
                        <div class="filter-group filter-group--full">
                            <div class="filter-group__label">Tags</div>

                            <div class="filter-pill-row">
                                <button
                                    type="button"
                                    class="pill pill--outline filter-pill filter-pill--all <?php echo empty( $current_tag ) ? 'pill--active' : ''; ?>"
                                >
                                    <span>All tags</span>
                                </button>

                                <?php
                                $tags = get_terms( array(
                                    'taxonomy'   => 'property_tags',
                                    'hide_empty' => true,
                                    'orderby'    => 'name',
                                ) );

                                if ( ! is_wp_error( $tags ) ) :
                                    foreach ( $tags as $tag ) :
                                        $is_active = in_array( $tag->slug, $current_tag, true );
                                        ?>
                                        <label class="pill pill--outline filter-pill <?php echo $is_active ? 'pill--active' : ''; ?>">
                                            <input
                                                type="checkbox"
                                                name="property_tags[]"
                                                value="<?php echo esc_attr( $tag->slug ); ?>"
                                                <?php checked( $is_active ); ?>
                                            >
                                            <span><?php echo esc_html( $tag->name ); ?> (<?php echo (int) $tag->count; ?>)</span>
                                        </label>
                                    <?php endforeach;
                                endif;
                                ?>
                            </div>
                        </div>

                    </div><!-- /.filter-row -->


                        <!-- KEYWORD + ACTIONS -->
                        <div class="filter-row filter-row--footer">
    
                            <div class="filter-group filter-group--grow">
                                <div class="filter-group__label">Keyword</div>
                                <input
                                    id="filter-keyword"
                                    type="text"
                                    name="s"
                                    value="<?php echo esc_attr( $current_keyword ); ?>"
                                    placeholder="Search by title or description"
                                >
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <button
                                  type="button"
                                  id="filter-reset-btn"
                                  class="btn btn--solid btn--black"
                                >
                                  Reset
                                </button>

                            
                            </div>
                        
                        </div>
                        
                        
                        
                        <!-- RESULTS SUMMARY + SORT -->
                        <div class="property-results-bar">
                        
                          <div class="property-results-count">
                            <span id="results-count" class="text-soft"><?php echo esc_html( $initial_count_text ); ?></span>
                          </div>
                        
                          <div class="property-sort">
                            <span class="property-sort__label">Sort by:</span>
                        
                            <input type="hidden" name="sort" id="sort-input" value="<?php echo esc_attr( $sort ); ?>">
                        
                            <div class="property-sort__pills">
                              <button type="button" class="pill pill--outline sort-pill <?php echo ( $sort === 'date_desc' ) ? 'pill--active' : ''; ?>" data-sort="date_desc">
                                Newest first
                              </button>
                              <button type="button" class="pill pill--outline sort-pill <?php echo ( $sort === 'date_asc' ) ? 'pill--active' : ''; ?>" data-sort="date_asc">
                                Oldest first
                              </button>
                              <button type="button" class="pill pill--outline sort-pill <?php echo ( $sort === 'price_asc' ) ? 'pill--active' : ''; ?>" data-sort="price_asc">
                                Price (Low → High)
                              </button>
                              <button type="button" class="pill pill--outline sort-pill <?php echo ( $sort === 'price_desc' ) ? 'pill--active' : ''; ?>" data-sort="price_desc">
                                Price (High → Low)
                              </button>
                            </div>
                          </div>
                        
                        </div>

                    
                  </form>
            


            





<!-- Results Grid (SSR baseline; AJAX will replace/append) -->
<div id="property-grid" class="cards-grid">
  <?php if ( $property_query->have_posts() ) : ?>
    <?php while ( $property_query->have_posts() ) : $property_query->the_post(); ?>

      <?php
        set_query_var( 'pera_property_card_args', array(
          'variant' => 'archive',
        ) );

        // V2 card template part
        get_template_part( 'parts/property-card-v2' );

        set_query_var( 'pera_property_card_args', array() );
      ?>

    <?php endwhile; ?>
  <?php else : ?>
    <p class="no-results">No properties found.</p>
  <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>

<?php
// ------------------------------------------------------------
// Pagination: preserve active filters in querystring
// NOTE: add_args supports arrays for district/property_tags (will become district[0]=...)
// That is acceptable, but if you want district[]=slug you need a custom base builder.
// For now, this is correct + stable.
// ------------------------------------------------------------

$use_archive_base = false; // set TRUE when you migrate v2 into archive-property.php at /property/

$pagination_base = $use_archive_base
  ? trailingslashit( get_post_type_archive_link( 'property' ) )
  : trailingslashit( get_permalink() );
  
$add_args = array();

if ( ! empty( $current_district ) ) {
  $add_args['district'] = $current_district;
}
if ( ! empty( $current_tag ) ) {
  $add_args['property_tags'] = $current_tag;
}
if ( $current_type !== '' ) {
  $add_args['property_type'] = $current_type;
}
if ( $selected_beds !== '' ) {
  $add_args['v2_beds'] = $selected_beds;
}
if ( $has_price_qs ) {
  if ( $qs_min > 0 ) $add_args['min_price'] = $qs_min;
  if ( $qs_max > 0 ) $add_args['max_price'] = $qs_max;
}
if ( $current_keyword !== '' ) {
  $add_args['s'] = $current_keyword;
}

// Sort (keep it in pagination so next/prev keep the same ordering)
if ( ! empty( $sort ) && $sort !== 'date_desc' ) {
  $add_args['sort'] = $sort;
}

?>

<div class="flex-center" style="margin-top:18px; gap:14px; flex-wrap:wrap;">
  <?php if ( $property_query->max_num_pages > 1 ) : ?>
    <nav class="property-pagination" aria-label="Property results pages">
      <?php

        echo paginate_links( array(
          'total'     => (int) $property_query->max_num_pages,
          'current'   => (int) max( 1, $paged ),
          'mid_size'  => 1,
          'end_size'  => 1,
          'prev_text' => 'Prev',
          'next_text' => 'Next',
          'type'      => 'list',
        
        // SEO-friendly pagination: /page/N/
        'base'   => $pagination_base . '%_%',
        'format' => 'page/%#%/',

        
          // Keep active filters (district, tags, type, beds, price, keyword, sort)
          'add_args'  => $add_args,
        ) );

      ?>
    </nav>
  <?php endif; ?>

  <?php if ( $property_query->max_num_pages > 1 && $paged < $property_query->max_num_pages ) : ?>
    <div class="property-load-more-wrap">
      <button
        type="button"
        class="btn btn-outline"
        id="load-more-btn"
        data-next-page="<?php echo esc_attr( $paged + 1 ); ?>"
      >
        Load more
      </button>
    </div>
  <?php else : ?>
    <!-- Keep button available for JS if you prefer -->
    <div class="property-load-more-wrap">
      <button
        type="button"
        class="btn btn-outline is-hidden"
        id="load-more-btn"
        data-next-page="2"
      >
        Load more
      </button>
    </div>
  <?php endif; ?>
</div>


            
                
            </div>
        </div>
    </section>

</main>

<script>
(function () {
  const form        = document.getElementById('property-filter-form');
  const grid        = document.getElementById('property-grid');
  const countEl     = document.getElementById('results-count');
  const loadMoreBtn = document.getElementById('load-more-btn');
  const resetBtn    = document.getElementById('filter-reset-btn');
  const paginationNav = document.querySelector('.property-pagination');

  // Price slider bits
  const priceMinRange  = document.getElementById('price-min-range');
  const priceMaxRange  = document.getElementById('price-max-range');
  const priceMinHidden = document.getElementById('price-min-hidden');
  const priceMaxHidden = document.getElementById('price-max-hidden');
  const priceSummary   = document.getElementById('price-summary-text');

  // Sort bits (Step 6)
  const sortInput  = document.getElementById('sort-input');         // hidden input name="sort"
  const sortPills  = Array.from(document.querySelectorAll('.sort-pill'));

  // Global bounds from PHP
  const GLOBAL_MIN_PRICE = <?php echo (int) $global_min_price; ?>;
  const GLOBAL_MAX_PRICE = <?php echo (int) $global_max_price; ?>;

  // If URL had min_price/max_price initially
  const HAS_PRICE_QS = <?php echo $has_price_qs ? 'true' : 'false'; ?>;

  if (!form || !grid) return;

  const ajaxUrl = "<?php echo esc_js( admin_url('admin-ajax.php') ); ?>";

  let activeController = null;
  let sliderDebounceT  = null;

  // Default sort (must match PHP allowed sorts)
  const DEFAULT_SORT = 'date_desc';

  // “Price filter active” only after touch, or URL already had it
  let priceTouched = HAS_PRICE_QS;

  function setLoading(on, append) {
    if (loadMoreBtn) loadMoreBtn.disabled = !!on;
    if (on && !append) {
      grid.innerHTML = '<p class="text-soft">Loading…</p>';
    }
  }

  function formatUsd(n) {
    n = Number(n) || 0;
    try {
      return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(n);
    } catch (e) {
      return String(Math.round(n));
    }
  }

  // ---------------------------
  // Read current form state
  // ---------------------------
  function getSelectedV2Bed() {
    const el = form.querySelector('input[name="v2_beds"]:checked');
    return el ? String(el.value || '').trim() : '';
  }

  function getSelectedPropertyType() {
    const el = form.querySelector('input[name="property_type"]:checked');
    return el ? String(el.value || '').trim() : '';
  }

  function getSortValue() {
    // Prefer hidden input if present, else fallback
    const v = sortInput ? String(sortInput.value || '').trim() : '';
    return v || DEFAULT_SORT;
  }

  // ---------------------------
  // PRICE UI SYNC
  // ---------------------------
  function syncPriceUi(triggerAjax = false) {
    if (!priceMinRange || !priceMaxRange || !priceMinHidden || !priceMaxHidden) return;

    let minV = parseInt(priceMinRange.value || String(GLOBAL_MIN_PRICE), 10);
    let maxV = parseInt(priceMaxRange.value || String(GLOBAL_MAX_PRICE), 10);

    // Clamp
    if (!Number.isFinite(minV)) minV = GLOBAL_MIN_PRICE;
    if (!Number.isFinite(maxV)) maxV = GLOBAL_MAX_PRICE;

    if (minV < GLOBAL_MIN_PRICE) minV = GLOBAL_MIN_PRICE;
    if (maxV > GLOBAL_MAX_PRICE) maxV = GLOBAL_MAX_PRICE;

    // Prevent crossing
    if (minV > maxV) {
      minV = maxV;
      priceMinRange.value = String(minV);
    }

    if (priceTouched) {
      priceMinHidden.value = String(minV);
      priceMaxHidden.value = String(maxV);
    } else {
      priceMinHidden.value = '';
      priceMaxHidden.value = '';
    }

    if (priceSummary) {
      priceSummary.textContent = '$' + formatUsd(minV) + ' — $' + formatUsd(maxV);
    }

    if (triggerAjax) runAjaxFilter(1, false);
  }

  function syncPriceUiDebounced() {
    if (sliderDebounceT) clearTimeout(sliderDebounceT);
    sliderDebounceT = setTimeout(function () {
      syncPriceUi(true);
    }, 180);
  }

  // ---------------------------
  // Build AJAX payload
  // ---------------------------
  function buildPayload(paged) {
    // IMPORTANT: do price sync first so hidden inputs are correct before FormData reads them
    syncPriceUi(false);

    const fd = new FormData(form);
    fd.set('action', 'pera_filter_properties_v2');
    fd.set('paged', String(paged));
    
    const basePath = window.location.pathname
        .replace(/\/page\/\d+\/?$/, '/')
        .replace(/\/?$/, '/');
    fd.set('archive_base', basePath);

    // Hygiene: ensure scalar v2_beds (radio)
    fd.delete('v2_beds[]');
    const v2Bed = getSelectedV2Bed();
    if (v2Bed !== '' && /^\d+$/.test(v2Bed)) {
      fd.set('v2_beds', v2Bed);
    } else {
      fd.delete('v2_beds');
    }

    // Hygiene: property_type scalar
    const pType = getSelectedPropertyType();
    if (pType !== '') fd.set('property_type', pType);
    else fd.delete('property_type');

    // Sort: always send (keeps paging stable)
    fd.set('sort', getSortValue());

    return fd;
  }

  // ---------------------------
  // Share URL (Step 7)
  // - include ALL active filters
  // - don’t include price if untouched
  // ---------------------------
  
   function buildPagedPath(pageNum) {
      pageNum = parseInt(String(pageNum || '1'), 10);
      if (!pageNum || pageNum < 2) {
        // page 1: base path without /page/N/
        return window.location.pathname.replace(/\/page\/\d+\/?$/, '/');
      }
      // ensure trailing slash
      const base = window.location.pathname.replace(/\/page\/\d+\/?$/, '/').replace(/\/?$/, '/');
      return base + 'page/' + pageNum + '/';
    }
        
    function updateShareUrl(pagedForUrl) {
          const params = new URLSearchParams();
        
          // Beds
          const v2Bed = getSelectedV2Bed();
          if (v2Bed !== '') params.set('v2_beds', v2Bed);
        
          // Type
          const pType = getSelectedPropertyType();
          if (pType !== '') params.set('property_type', pType);
        
          // District[] / Tags[]
          form.querySelectorAll('input[name="district[]"]:checked').forEach(el => {
            params.append('district[]', String(el.value || ''));
          });
          form.querySelectorAll('input[name="property_tags[]"]:checked').forEach(el => {
            params.append('property_tags[]', String(el.value || ''));
          });
        
          // Keyword
          const keywordEl = form.querySelector('input[name="s"]');
          const keywordVal = keywordEl ? String(keywordEl.value || '').trim() : '';
          if (keywordVal !== '') params.set('s', keywordVal);
        
          // Sort (only if not default)
          const sortVal = getSortValue();
          if (sortVal && sortVal !== DEFAULT_SORT) params.set('sort', sortVal);
        
          // Price only if active
          const minRaw = priceMinHidden ? String(priceMinHidden.value || '').trim() : '';
          const maxRaw = priceMaxHidden ? String(priceMaxHidden.value || '').trim() : '';
          if (minRaw !== '') params.set('min_price', minRaw);
          if (maxRaw !== '') params.set('max_price', maxRaw);
        
          const pageNum = parseInt(String(pagedForUrl || '1'), 10);
          const path = buildPagedPath(pageNum);
        
          const qs = params.toString();
          const newUrl = path + (qs ? ('?' + qs) : '');
          window.history.replaceState({}, '', newUrl);
        }




  function renderResults(d, { append, paged }) {
    if (d.grid_html) {
      if (append) {
        const temp = document.createElement('div');
        temp.innerHTML = d.grid_html;
        while (temp.firstChild) grid.appendChild(temp.firstChild);
      } else {
        grid.innerHTML = d.grid_html;
      }
    } else if (!append) {
      grid.innerHTML = '<p class="text-soft">No results.</p>';
    }

    if (countEl) countEl.textContent = d.count_text ? d.count_text : '';

    if (loadMoreBtn) {
      if (d.has_more) {
        loadMoreBtn.dataset.nextPage = String(d.next_page || (paged + 1));
        loadMoreBtn.classList.remove('is-hidden');
      } else {
        loadMoreBtn.classList.add('is-hidden');
      }
    }

    // Update pagination UI (critical for Next/Prev + active page)
    if (paginationNav && typeof d.pagination_html === 'string') {
      if (d.pagination_html.trim()) {
        paginationNav.innerHTML = d.pagination_html;
        paginationNav.classList.remove('is-hidden');
      } else {
        paginationNav.classList.add('is-hidden');
      }
    }


    // Keep URL in sync (Step 7)
    updateShareUrl(paged);
  }

  function runAjaxFilter(paged = 1, append = false) {
    if (activeController) activeController.abort();
    activeController = new AbortController();

    const payload = buildPayload(paged);

    setLoading(true, append);

    fetch(ajaxUrl, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin',
      signal: activeController.signal
    })
    .then(r => r.json())
    .then(resp => {
      setLoading(false, append);

      if (!resp || !resp.success || !resp.data) {
        if (!append) grid.innerHTML = '<p class="text-soft">No results.</p>';
        if (countEl) countEl.textContent = '';
        if (loadMoreBtn) loadMoreBtn.classList.add('is-hidden');
        return;
      }

      renderResults(resp.data, { append, paged });
    })
    .catch(err => {
      if (err && err.name === 'AbortError') return;

      setLoading(false, append);
      if (!append) grid.innerHTML = '<p class="text-soft">Error loading results.</p>';
      if (loadMoreBtn) loadMoreBtn.classList.add('is-hidden');
      console.error('V2 AJAX error', err);
    });
  }

  // ---------------------------
  // Step 6: Sort pills binding
  // ---------------------------
  function bindSortPills() {
    if (!sortInput || !sortPills.length) return;

    function paint() {
      const current = getSortValue();
      sortPills.forEach(btn => {
        const v = String(btn.dataset.sort || '').trim();
        btn.classList.toggle('pill--active', v === current);
      });
    }

    sortPills.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const v = String(btn.dataset.sort || '').trim();
        if (!v) return;

        sortInput.value = v;
        paint();
        runAjaxFilter(1, false);
      });
    });

    // Initial paint
    if (!sortInput.value) sortInput.value = DEFAULT_SORT;
    paint();
  }

  // ---------------------------
  // Pill rows binder (checkbox + radio)
  // - triggers AJAX on change
  // ---------------------------
  function bindPillRows() {
    const rows = Array.from(form.querySelectorAll('.filter-pill-row'));

    rows.forEach(function (row) {
      const allBtn = row.querySelector('.filter-pill--all');
      const inputs = Array.from(row.querySelectorAll('input[type="checkbox"], input[type="radio"]'));
      if (!inputs.length) return;

      const isRadioRow = inputs.some(i => i.type === 'radio');

      function refreshRowUi() {
        if (isRadioRow) {
          inputs.forEach(function (inp) {
            const pill = inp.closest('.filter-pill');
            if (!pill) return;
            pill.classList.toggle('pill--active', !!inp.checked);
          });

          if (allBtn) {
            const checked = row.querySelector('input[type="radio"]:checked');
            const checkedVal = checked ? String(checked.value || '').trim() : '';
            allBtn.classList.toggle('pill--active', checkedVal === '');
          }
        } else {
          const anyChecked = inputs.some(i => i.checked);
          if (allBtn) allBtn.classList.toggle('pill--active', !anyChecked);

          inputs.forEach(function (inp) {
            const pill = inp.closest('.filter-pill');
            if (!pill) return;
            pill.classList.toggle('pill--active', !!inp.checked);
          });
        }
      }

      inputs.forEach(function (inp) {
        inp.addEventListener('change', function () {
          refreshRowUi();
          runAjaxFilter(1, false);
        });
      });

      if (allBtn) {
        allBtn.addEventListener('click', function (e) {
          e.preventDefault();

          if (isRadioRow) {
            const emptyRadio = row.querySelector('input[type="radio"][value=""]');
            if (emptyRadio) emptyRadio.checked = true;
            else inputs.forEach(i => i.checked = false);
          } else {
            inputs.forEach(i => i.checked = false);
          }

          refreshRowUi();
          runAjaxFilter(1, false);
        });
      }

      refreshRowUi();
    });
  }

  // ---------------------------
  // Form submit (keyword)
  // ---------------------------
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    runAjaxFilter(1, false);
  });

  // Load more
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
      const next = parseInt(loadMoreBtn.dataset.nextPage || '2', 10);
      runAjaxFilter(next, true);
    });
  }

  // Optional reset button support
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      // Clear checkboxes + radios
      form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(el => el.checked = false);

      // Restore All types radio if exists
      const allType = form.querySelector('input[name="property_type"][value=""]');
      if (allType) allType.checked = true;

      // Restore sort default
      if (sortInput) sortInput.value = DEFAULT_SORT;
      sortPills.forEach(btn => btn.classList.toggle('pill--active', String(btn.dataset.sort || '') === DEFAULT_SORT));

      // Reset slider to bounds + mark as untouched
      if (priceMinRange && priceMaxRange) {
        priceMinRange.value = String(GLOBAL_MIN_PRICE);
        priceMaxRange.value = String(GLOBAL_MAX_PRICE);
      }
      priceTouched = false;
      syncPriceUi(false);

      runAjaxFilter(1, false);
    });
  }

  // Slider listeners
  if (priceMinRange) {
    priceMinRange.addEventListener('input', function () {
      priceTouched = true;
      syncPriceUiDebounced();
    });
    priceMinRange.addEventListener('change', function () {
      priceTouched = true;
      syncPriceUi(true);
    });
  }

  if (priceMaxRange) {
    priceMaxRange.addEventListener('input', function () {
      priceTouched = true;
      syncPriceUiDebounced();
    });
    priceMaxRange.addEventListener('change', function () {
      priceTouched = true;
      syncPriceUi(true);
    });
  }

  // SEO pagination links: enhance with AJAX
  document.addEventListener('click', function (e) {
  const a = e.target.closest('.property-pagination a');
  if (!a) return;

  // allow open in new tab etc.
  if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

  const href = a.getAttribute('href') || '';

    let pageNum = 0;
    
    // 1) Try query arg ?paged=N (page template standard)
    try {
      const u = new URL(href, window.location.origin);
      const qp = parseInt(u.searchParams.get('paged') || '', 10);
      if (qp && qp > 0) pageNum = qp;
    } catch (err) {}
    
    // 2) Fallback: try /page/N/ (archive-style, future-proof)
    if (!pageNum) {
      const m = href.match(/\/page\/(\d+)(\/|$)/);
      if (m) pageNum = parseInt(m[1], 10);
    }
    
    // If we couldn't parse, allow default navigation
    if (!pageNum || pageNum < 1) return;
    
    e.preventDefault();
    runAjaxFilter(pageNum, false);


  const resultsTop = document.getElementById('property-grid');
  if (resultsTop) {
    resultsTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});


  // ---------------------------
  // Init bindings
  // ---------------------------
  bindSortPills();
  bindPillRows();

  // Initial UI sync: if URL had price params, hidden inputs should be populated
  syncPriceUi(false);

  // Decide whether to run initial AJAX refresh (keep SSR for empty state)
  const keywordEl  = form.querySelector('input[name="s"]');
  const keywordVal = keywordEl ? String(keywordEl.value || '').trim() : '';

  const hasActiveFilters =
    (getSelectedV2Bed() !== '') ||
    (getSelectedPropertyType() !== '') ||
    (keywordVal !== '') ||
    !!form.querySelector('input[name="district[]"]:checked') ||
    !!form.querySelector('input[name="property_tags[]"]:checked') ||
    (sortInput && String(sortInput.value || '') !== '' && String(sortInput.value) !== DEFAULT_SORT) ||
    ((priceMinHidden && String(priceMinHidden.value || '').trim() !== '') ||
     (priceMaxHidden && String(priceMaxHidden.value || '').trim() !== ''));

  if (hasActiveFilters) {
    runAjaxFilter(1, false);
  }

})();
</script>




<?php get_footer(); ?>
