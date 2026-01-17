<?php
/**
 * Template: Property Archive (Lean)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

/* -------------------------------------------------------
   SEO TITLE / DESCRIPTION (context-aware)
-------------------------------------------------------- */
$archive_title       = 'Property for sale in Istanbul';
$archive_description = 'We’ve got dozens of pages covering hundreds of options across almost all 48 districts of Istanbul. If you are looking for something more specific, be sure to contact us with your details, requirements, budget, etc. – take it easy and leave the rest to us.';

$qo = get_queried_object();

/* Base URL for filters / reset depending on context */
$property_archive_url = get_post_type_archive_link( 'property' );

if ( is_post_type_archive( 'property' ) ) {
    $archive_base_url = $property_archive_url;
} elseif ( is_tax() && $qo && ! is_wp_error( $qo ) ) {
    $archive_base_url = get_term_link( $qo );
} else {
    $archive_base_url = $property_archive_url;
}

/* Override title and description for tax archives */
if ( is_tax( 'region' ) && $qo && ! is_wp_error( $qo ) ) {
    $archive_title = sprintf( 'Property for sale in %s, Istanbul', esc_html( $qo->name ) );

} elseif ( is_tax( 'district' ) && $qo && ! is_wp_error( $qo ) ) {
    $archive_title = sprintf( '%s property for sale in Istanbul', esc_html( $qo->name ) );

} elseif ( is_tax( 'property_type' ) && $qo && ! is_wp_error( $qo ) ) {
    $archive_title = sprintf( '%s in Istanbul', esc_html( $qo->name ) );

} elseif ( is_tax( 'bedrooms' ) && $qo && ! is_wp_error( $qo ) ) {
    $archive_title = sprintf( '%s bedroom property for sale in Istanbul', esc_html( $qo->name ) );
}

/* If this is ANY taxonomy archive and the term has a description, use it */
if ( is_tax() && $qo && ! is_wp_error( $qo ) && ! empty( $qo->description ) ) {
    $archive_description = $qo->description; // escaped when outputting
}

/* -------------------------------------------------------
   READ FILTER VALUES FROM QUERY STRING
-------------------------------------------------------- */
$current_region = isset( $_GET['region'] )
    ? sanitize_text_field( wp_unslash( $_GET['region'] ) )
    : '';

$current_type = isset( $_GET['property_type'] )
    ? sanitize_text_field( wp_unslash( $_GET['property_type'] ) )
    : '';

/* Multi-select facets: normalise to arrays of slugs */
$current_district = array();
if ( isset( $_GET['district'] ) ) {
    $raw = $_GET['district'];
    if ( is_array( $raw ) ) {
        $current_district = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
    } elseif ( $raw !== '' ) {
        $current_district = array( sanitize_text_field( wp_unslash( $raw ) ) );
    }
}

$current_beds = array();
if ( isset( $_GET['bedrooms'] ) ) {
    $raw = $_GET['bedrooms'];
    if ( is_array( $raw ) ) {
        $current_beds = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
    } elseif ( $raw !== '' ) {
        $current_beds = array( sanitize_text_field( wp_unslash( $raw ) ) );
    }
}

$current_tag = array();
if ( isset( $_GET['property_tags'] ) ) {
    $raw = $_GET['property_tags'];
    if ( is_array( $raw ) ) {
        $current_tag = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
    } elseif ( $raw !== '' ) {
        $current_tag = array( sanitize_text_field( wp_unslash( $raw ) ) );
    }
}

/* -------------------------------------------------------
   Ensure taxonomy archives filter by their term
   (only if not overridden via ?region= etc.)
-------------------------------------------------------- */
if ( is_tax() && $qo && ! is_wp_error( $qo ) ) {

    if ( is_tax( 'region' ) && ! $current_region ) {
        $current_region = $qo->slug;
    }

    if ( is_tax( 'district' ) && empty( $current_district ) ) {
        $current_district = array( $qo->slug );
    }

    if ( is_tax( 'property_type' ) && ! $current_type ) {
        $current_type = $qo->slug;
    }

    if ( is_tax( 'bedrooms' ) && empty( $current_beds ) ) {
        $current_beds = array( $qo->slug );
    }

    if ( is_tax( 'property_tags' ) && empty( $current_tag ) ) {
        $current_tag = array( $qo->slug );
    }
}

/* Price range */
$current_min = ( isset( $_GET['min_price'] ) && $_GET['min_price'] !== '' )
    ? absint( $_GET['min_price'] )
    : null;

$current_max = ( isset( $_GET['max_price'] ) && $_GET['max_price'] !== '' )
    ? absint( $_GET['max_price'] )
    : null;

/* Keyword */
$current_keyword = isset( $_GET['s'] )
    ? sanitize_text_field( wp_unslash( $_GET['s'] ) )
    : '';

/* Global slider bounds (V2) */
if ( function_exists( 'pera_v2_get_price_bounds' ) ) {
  $bounds = pera_v2_get_price_bounds();
  $global_min_price = isset($bounds['min']) ? (int) $bounds['min'] : 50000;
  $global_max_price = isset($bounds['max']) ? (int) $bounds['max'] : 1000000;
} else {
  // Hard fallback so UI never shows $0
  $global_min_price = 20000;
  $global_max_price = 1000000;
}



/* Slider positions */
$slider_min = ( $current_min !== null ) ? $current_min : $global_min_price;
$slider_max = ( $current_max !== null ) ? $current_max : $global_max_price;

/* -------------------------------------------------------
   BUILD PROPERTY QUERY
-------------------------------------------------------- */
$paged = get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : 1;

$args = array(
    'post_type'      => 'property',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
);

/* Sorting */
$sort = isset( $_GET['sort'] )
    ? sanitize_text_field( wp_unslash( $_GET['sort'] ) )
    : 'date_desc';

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

$tax_query  = array();
$meta_query = array();

/* Region */
if ( $current_region ) {
    $tax_query[] = array(
        'taxonomy' => 'region',
        'field'    => 'slug',
        'terms'    => $current_region,
    );
}

/* District (multi) */
if ( ! empty( $current_district ) ) {
    $tax_query[] = array(
        'taxonomy' => 'district',
        'field'    => 'slug',
        'terms'    => $current_district,
        'operator' => 'IN',
    );
}

/* Property type (single) */
if ( $current_type ) {
    $tax_query[] = array(
        'taxonomy' => 'property_type',
        'field'    => 'slug',
        'terms'    => $current_type,
    );
}

/* Bedrooms (multi) */
if ( ! empty( $current_beds ) ) {
    $tax_query[] = array(
        'taxonomy' => 'bedrooms',
        'field'    => 'slug',
        'terms'    => $current_beds,
        'operator' => 'IN',
    );
}

/* Tags (multi) */
if ( ! empty( $current_tag ) ) {
    $tax_query[] = array(
        'taxonomy' => 'property_tags',
        'field'    => 'slug',
        'terms'    => $current_tag,
        'operator' => 'IN',
    );
}

/* Apply tax_query */
if ( ! empty( $tax_query ) ) {
    $args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
}

/* Price range meta_query */
if ( $current_min !== null || $current_max !== null ) {

    if ( $current_min !== null && $current_max !== null ) {
        $meta_query[] = array(
            'key'     => 'v2_price_usd_min',
            'type'    => 'NUMERIC',
            'value'   => array( $current_min, $current_max ),
            'compare' => 'BETWEEN',
        );
    } elseif ( $current_min !== null ) {
        $meta_query[] = array(
            'key'     => 'price_usd',
            'type'    => 'NUMERIC',
            'value'   => $current_min,
            'compare' => '>=',
        );
    } else {
        $meta_query[] = array(
            'key'     => 'price_usd',
            'type'    => 'NUMERIC',
            'value'   => $current_max,
            'compare' => '<=',
        );
    }
}

if ( ! empty( $meta_query ) ) {
    $args['meta_query'] = $meta_query;
}

/* Keyword search */
if ( $current_keyword ) {
    $args['s'] = $current_keyword;
}

$property_query = new WP_Query( $args );
?>

<main id="primary" class="site-main property-archive">

    <?php
/* ======================================================
   SEARCH-AWARE ARCHIVE HEADING
   ====================================================== */

// Detect whether this is a filtered/search result
$is_filtered_search = false;

$search_keys = array(
    's',
    'property_type',
    'bedrooms',
    'district',
    'min_price',
    'max_price',
    'property_tags',
);

foreach ( $search_keys as $key ) {
    if ( isset( $_GET[ $key ] ) && $_GET[ $key ] !== '' ) {
        $is_filtered_search = true;
        break;
    }
}

// Build heading
if ( $is_filtered_search ) {
    $hero_title = 'Here are your search results';
    $hero_desc  = sprintf(
        '%d properties found matching your criteria.',
        intval( $property_query->found_posts )
    );
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
        <div class="content-panel-box">

            <div class="property-filters-wrapper">
                <header class="section-header">
                    <h2>Available properties</h2>
                    <p>Use the filters below to refine by district, property type, bedrooms and budget.</p>
                </header>

                <form
                    method="get"
                    class="property-filters"
                    action="<?php echo esc_url( $archive_base_url ); ?>"
                >

                    <!-- ===== TOP ROW: PRICE / TYPE / BEDROOMS ===== -->
                    <div class="filter-row">

                        <!-- PRICE RANGE -->
                        <div class="filter-group">
                            <div class="filter-group__label">Price range (USD)</div>

                            <div class="filter-price">
                                <div class="filter-price__slider">
                                    <input
                                        id="price-min-range"
                                        type="range"
                                        min="<?php echo esc_attr( $global_min_price ); ?>"
                                        max="<?php echo esc_attr( $global_max_price ); ?>"
                                        step="1000"
                                        value="<?php echo esc_attr( $slider_min ); ?>"
                                    >
                                    <input
                                        id="price-max-range"
                                        type="range"
                                        min="<?php echo esc_attr( $global_min_price ); ?>"
                                        max="<?php echo esc_attr( $global_max_price ); ?>"
                                        step="1000"
                                        value="<?php echo esc_attr( $slider_max ); ?>"
                                    >
                                </div>

                                <input
                                    id="price-min-hidden"
                                    type="hidden"
                                    name="min_price"
                                    value="<?php echo esc_attr( $slider_min ); ?>"
                                >
                                <input
                                    id="price-max-hidden"
                                    type="hidden"
                                    name="max_price"
                                    value="<?php echo esc_attr( $slider_max ); ?>"
                                >

                                <div class="filter-price__summary">
                                    <span id="price-summary-text">
                                        <?php
                                        $min_label = '$' . number_format_i18n( $slider_min );
                                        $max_label = '$' . number_format_i18n( $slider_max );
                                        echo esc_html( "{$min_label} — {$max_label}" );
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
                                    if ( ! $term || is_wp_error( $term ) ) {
                                        continue;
                                    }

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

                        <!-- BEDROOMS (pills, multi-select) -->
                        <div class="filter-group">
                            <div class="filter-group__label">Bedrooms</div>

                            <div class="filter-pill-row">
                                <button
                                    type="button"
                                    class="pill pill--outline filter-pill filter-pill--all <?php echo empty( $current_beds ) ? 'pill--active' : ''; ?>"
                                >
                                    <span>
                                        <svg class="icon icon-bed" aria-hidden="true">
                                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed' ); ?>"></use>
                                        </svg>
                                        Any
                                    </span>
                                </button>


                                <?php
                                $bed_pub_counts = pera_archive_published_property_term_counts('bedrooms');
                                
                                $beds = get_terms(array(
                                  'taxonomy'   => 'bedrooms',
                                  'hide_empty' => false,
                                ));
                                
                                if ( ! is_wp_error($beds) && ! empty($beds) ) {
                                
                                  // Filter to terms that have at least 1 PUBLISHED property
                                  $beds = array_values(array_filter($beds, function($t) use ($bed_pub_counts) {
                                    return (int) ($bed_pub_counts[(int)$t->term_id] ?? 0) > 0;
                                  }));
                                
                                  // Sort numerically (1,2,3...)
                                  usort($beds, function($a, $b) {
                                    $anum = 9999; $bnum = 9999;
                                    if ( preg_match('/(\d+)/', (string)$a->name, $m) ) $anum = (int)$m[1];
                                    if ( preg_match('/(\d+)/', (string)$b->name, $m2) ) $bnum = (int)$m2[1];
                                    return $anum <=> $bnum;
                                  });
                                
                                  foreach ( $beds as $bed ) :
                                    $is_active = in_array( $bed->slug, $current_beds, true );
                                    $cnt = (int) ($bed_pub_counts[(int)$bed->term_id] ?? 0);
                                    ?>
                                    <label class="pill pill--outline filter-pill <?php echo $is_active ? 'pill--active' : ''; ?>">
                                      <input
                                        type="checkbox"
                                        name="bedrooms[]"
                                        value="<?php echo esc_attr($bed->slug); ?>"
                                        <?php checked($is_active); ?>
                                      >
                                      <span>
                                        <svg class="icon icon-bed" aria-hidden="true">
                                          <use href="<?php echo esc_url(get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bed'); ?>"></use>
                                        </svg>
                                        <?php echo esc_html($bed->name); ?> (<?php echo $cnt; ?>)
                                      </span>
                                    </label>
                                  <?php endforeach;
                                }
                                ?>


                                
                            </div>
                        </div>

                    </div><!-- /.filter-row -->

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
                            <!-- Type button to avoid any ambiguity with submit interception -->
                            <button type="button" id="jump-results-btn" class="btn btn--solid btn--blue">
                                Jump to results
                            </button>

                            <a href="<?php echo esc_url( $archive_base_url ); ?>" class="btn btn--solid btn--black">
                                Reset
                            </a>
                        </div>

                    </div><!-- /.filter-row -->

                    <!-- RESULTS SUMMARY + SORT -->
                    <div class="property-results-bar">

                        <div class="property-results-count">
                            <?php echo intval( $property_query->found_posts ); ?> properties found
                        </div>

                        <div class="property-sort">
                            <span class="property-sort__label">Sort by:</span>

                            <input
                                type="hidden"
                                name="sort"
                                id="sort-input"
                                value="<?php echo esc_attr( $sort ); ?>"
                            >

                            <div class="property-sort__pills">

                                <button
                                    type="button"
                                    class="pill pill--outline sort-pill <?php echo ( $sort === 'date_desc' || $sort === '' ) ? 'pill--active' : ''; ?>"
                                    data-sort="date_desc"
                                >
                                    Newest first
                                </button>

                                <button
                                    type="button"
                                    class="pill pill--outline sort-pill <?php echo ( $sort === 'date_asc' ) ? 'pill--active' : ''; ?>"
                                    data-sort="date_asc"
                                >
                                    Oldest first
                                </button>

                                <button
                                    type="button"
                                    class="pill pill--outline sort-pill <?php echo ( $sort === 'price_asc' ) ? 'pill--active' : ''; ?>"
                                    data-sort="price_asc"
                                >
                                    Price (Low → High)
                                </button>

                                <button
                                    type="button"
                                    class="pill pill--outline sort-pill <?php echo ( $sort === 'price_desc' ) ? 'pill--active' : ''; ?>"
                                    data-sort="price_desc"
                                >
                                    Price (High → Low)
                                </button>

                            </div>
                        </div>
                    </div><!-- /.property-results-bar -->

                </form>

            </div><!-- /.property-filters-wrapper -->

            <!-- RESULTS GRID -->
            <div class="cards-grid" id="results">
                <?php if ( $property_query->have_posts() ) : ?>
                    <?php
                        while ( $property_query->have_posts() ) :
                          $property_query->the_post();
                        
                          set_query_var( 'pera_property_card_args', array(
                            'variant' => 'archive',
                          ) );
                        
                          get_template_part( 'parts/property-card' );
                        
                          set_query_var( 'pera_property_card_args', array() );
                        
                        endwhile;
                        ?>

                <?php else : ?>
                    <p class="no-results">
                        No properties found. Try adjusting your filters or
                        <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">contact us</a>
                        for off-market options.
                    </p>
                <?php endif; ?>
            </div><!-- /.cards-grid -->
            
            <div class="flex-center">
                <?php if ( $property_query->max_num_pages > 1 ) : ?>
                  <nav class="property-pagination" aria-label="Property results pages">
                    <?php
                    echo paginate_links( array(
                      'total'     => $property_query->max_num_pages,
                      'current'   => max( 1, $paged ),
                      'mid_size'  => 1,
                      'end_size'  => 1,
                      'prev_text' => 'Prev',
                      'next_text' => 'Next',
                      'type'      => 'list',
                    ) );
                    ?>
                  </nav>
                <?php endif; ?>



                <?php if ( $property_query->have_posts() ) : ?>
                    <div class="property-load-more-wrap">
                        <?php if ( $property_query->max_num_pages > 1 && $paged < $property_query->max_num_pages ) : ?>
                            <button
                                type="button"
                                class="btn btn--ghost btn--blue property-load-more"
                                data-next-page="<?php echo esc_attr( $paged + 1 ); ?>"
                            >
                                Load more properties
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            
            </div>
            
            <?php wp_reset_postdata(); ?>

        </div><!-- /.content-panel-box -->
    </section>
    
    
</main>

<script>
// Price slider + AJAX filtering
document.addEventListener('DOMContentLoaded', function () {
  console.log('Pera Property archive JS loaded');

  function formatUSD(value) {
    return '$' + Number(value).toLocaleString();
  }

  // ------------------------------
  // PRICE SLIDER (STATIC RANGE)
  // ------------------------------
  const minRange  = document.getElementById('price-min-range');
  const maxRange  = document.getElementById('price-max-range');
  const minHidden = document.getElementById('price-min-hidden');
  const maxHidden = document.getElementById('price-max-hidden');
  const summary   = document.getElementById('price-summary-text');

  function updateTrackFillStatic(minVal, maxVal) {
    const slider = document.querySelector('.filter-price__slider');
    if (!slider || !minRange || !maxRange) return;

    const minAllowed = parseInt(minRange.min, 10);
    const maxAllowed = parseInt(maxRange.max, 10);

    const leftPercent  = ((minVal - minAllowed) / (maxAllowed - minAllowed)) * 100;
    const rightPercent = ((maxVal - minAllowed) / (maxAllowed - minAllowed)) * 100;

    slider.classList.add('fill-active');
    slider.style.setProperty('--fill-left', leftPercent + '%');
    slider.style.setProperty('--fill-right', rightPercent + '%');
  }

  // ------------------------------
  // AJAX FILTERING + LOAD MORE
  // ------------------------------
  const form        = document.querySelector('.property-filters');
  const grid        = document.querySelector('.cards-grid');
  const loadMoreBtn = document.querySelector('.property-load-more');
  const countEl     = document.querySelector('.property-results-count');
  const ajaxUrl     = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

  if (!form || !grid || !ajaxUrl) {
    console.warn('Missing form / grid / ajaxUrl, aborting AJAX wiring.', { form, grid, ajaxUrl });
    return;
  }

  if (minRange && maxRange && minHidden && maxHidden) {
    function syncPriceSlider() {
      let minVal = parseInt(minRange.value, 10);
      let maxVal = parseInt(maxRange.value, 10);

      if (minVal > maxVal) {
        const tmp = minVal;
        minVal = maxVal;
        maxVal = tmp;
        minRange.value = minVal;
        maxRange.value = maxVal;
      }

      minHidden.value = minVal;
      maxHidden.value = maxVal;

      if (summary) {
        summary.textContent = formatUSD(minVal) + ' — ' + formatUSD(maxVal);
      }

      updateTrackFillStatic(minVal, maxVal);
    }

    minRange.addEventListener('input', syncPriceSlider);
    maxRange.addEventListener('input', syncPriceSlider);

    minRange.addEventListener('change', function () {
      console.log('Min slider change → filter');
      runAjaxFilter(1, false);
    });

    maxRange.addEventListener('change', function () {
      console.log('Max slider change → filter');
      runAjaxFilter(1, false);
    });

    syncPriceSlider();
  }

  function updateTermPills(dataMap, inputName) {
    if (!dataMap) return;

    const checkboxes = document.querySelectorAll('input[name="' + inputName + '"]');

    checkboxes.forEach(function (cb) {
      const slug = cb.value;
      const pill = cb.closest('.filter-pill');
      if (!pill) return;

      const span = pill.querySelector('span');
      const termInfo = dataMap[slug];

      if (!termInfo) {
        cb.disabled = true;
        pill.classList.add('pill--disabled');

        if (span) {
          if (!span.dataset.originalLabel) {
            span.dataset.originalLabel = span.textContent;
          }
          span.textContent = span.dataset.originalLabel.replace(/\(\d+\)\s*$/, '').trim() + ' (0)';
        }
      } else {
        cb.disabled = false;
        pill.classList.remove('pill--disabled');

        if (span) {
          const label = termInfo.name + ' (' + termInfo.count + ')';
          span.dataset.originalLabel = label;
          span.textContent = label;
        }
      }
    });
  }

  function runAjaxFilter(paged = 1, append = false) {
    console.log('runAjaxFilter()', { paged, append });

    const formData = new FormData(form);
    formData.append('action', 'pera_filter_properties_v2');
    formData.set('paged', String(paged));

    fetch(ajaxUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      console.log('AJAX JSON data', data);

      if (!data || !data.success) {
        console.warn('AJAX returned non-success', data);
        return;
      }

      // GRID HTML
      if (data.data.grid_html && grid) {
        if (append) {
          const temp = document.createElement('div');
          temp.innerHTML = data.data.grid_html;
          while (temp.firstChild) {
            grid.appendChild(temp.firstChild);
          }
        } else {
          grid.innerHTML = data.data.grid_html;
        }
      }

      // COUNT TEXT
      if (countEl && data.data.count_text) {
        countEl.textContent = data.data.count_text;
      }

      // LOAD MORE
      if (loadMoreBtn) {
        if (data.data.has_more) {
          loadMoreBtn.dataset.nextPage = data.data.next_page;
          loadMoreBtn.classList.remove('is-hidden');
        } else {
          loadMoreBtn.classList.add('is-hidden');
        }
      }

      // FACETS
      updateTermPills(data.data.bedroom_counts, 'bedrooms[]');
      updateTermPills(data.data.tag_counts, 'property_tags[]');

      // Update URL so filters are shareable (do not attempt to delete action/paged; they are not form fields)
      const urlParams = new URLSearchParams(new FormData(form));
      const newQuery  = urlParams.toString();
      const newUrl    = window.location.pathname + (newQuery ? '?' + newQuery : '');
      window.history.replaceState({}, '', newUrl);
    })
    .catch(err => {
      console.error('AJAX filter error', err);
    });
  }

  // Intercept form submit (Enter key in keyword, etc.)
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    console.log('Form submit intercepted → runAjaxFilter(1, false)');
    runAjaxFilter(1, false);
  });

  // PROPERTY TYPE PILLS (radio) → AJAX
  const typeRadios = form.querySelectorAll('input[type="radio"][name="property_type"]');

  if (typeRadios.length) {
    console.log('Property type radio pills found:', typeRadios.length);

    function refreshTypePills() {
      typeRadios.forEach(function (radio) {
        const pill = radio.closest('.filter-pill');
        if (!pill) return;
        pill.classList.toggle('pill--active', radio.checked);
      });
    }

    typeRadios.forEach(function (radio) {
      radio.addEventListener('change', function () {
        refreshTypePills();
        console.log('Property type changed →', this.value);
        runAjaxFilter(1, false);
      });
    });

    refreshTypePills();
  }

  // SORT PILLS
  const sortInput = document.getElementById('sort-input');
  const sortPills = document.querySelectorAll('.sort-pill');

  if (sortInput && sortPills.length) {
    console.log('Sort pills found:', sortPills.length);

    sortPills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        const value = this.dataset.sort || '';
        console.log('Sort pill clicked →', value);

        sortInput.value = value;

        sortPills.forEach(function (p) {
          p.classList.remove('pill--active');
        });
        this.classList.add('pill--active');

        runAjaxFilter(1, false);
      });
    });
  }

  // FACET PILL LOGIC (checkbox rows only; skip radio rows)
  const pillRows = form.querySelectorAll('.filter-pill-row');

  pillRows.forEach(function (row) {

    // Skip rows that are radio-based (property_type)
    if (row.querySelector('input[type="radio"]')) return;

    const allPill    = row.querySelector('.filter-pill--all');
    const checkboxes = row.querySelectorAll('input[type="checkbox"]');

    function refreshPills() {
      const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

      if (allPill) {
        allPill.classList.toggle('pill--active', !anyChecked);
      }

      checkboxes.forEach(function (cb) {
        const pill = cb.closest('.filter-pill');
        if (!pill) return;
        pill.classList.toggle('pill--active', cb.checked);
      });
    }

    checkboxes.forEach(function (cb) {
      cb.addEventListener('change', function () {
        refreshPills();
        console.log('Pill checkbox changed → filter');
        runAjaxFilter(1, false);
      });
    });

    if (allPill) {
      allPill.addEventListener('click', function (e) {
        e.preventDefault();
        checkboxes.forEach(cb => { cb.checked = false; });
        refreshPills();
        console.log('"All" pill clicked → filter');
        runAjaxFilter(1, false);
      });
    }

    refreshPills();
  });

  // LOAD MORE
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
      const next = parseInt(loadMoreBtn.dataset.nextPage || '2', 10) || 2;
      console.log('Load more clicked → next page', next);
      runAjaxFilter(next, true);
    });
  }

  // Jump to results button — run AJAX + scroll
  const jumpBtn = document.getElementById('jump-results-btn');

  if (jumpBtn) {
    jumpBtn.addEventListener('click', function (e) {
      e.preventDefault();
      console.log('Jump to results clicked');
      runAjaxFilter(1, false);

      const results = document.getElementById('results');
      if (results) {
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }
});
</script>

<?php
get_footer();
