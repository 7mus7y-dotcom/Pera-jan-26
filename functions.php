<?php
/**
 * Pera Property – Hello Elementor Child Theme
 * Clean, optimised, production-ready
 */
if ( ! defined( 'ABSPATH' ) ) exit;

error_log('ACTIVE CHILD THEME FUNCTIONS LOADED');


/**
 * Load taxonomy term meta (needed for term excerpt + featured image)
 * Safe to load globally.
 */
require_once get_stylesheet_directory() . '/Inc/taxonomy-meta.php';
/**
 * Published term counts helper (publish-only counts for property CPT)
 */
require_once get_stylesheet_directory() . '/Inc/published-term-counts.php';


/**
 * Conditionally load SEO modules (must load before wp_head fires)
 */
add_action( 'wp', function () {

  if ( is_admin() ) return;

  $inc = trailingslashit( get_stylesheet_directory() ) . 'Inc/';

  // 1) Single Property
  if ( is_singular( 'property' ) ) {
    require_once $inc . 'seo-property.php';
    return;
  }

  // 2) Property Archive (your search page)
  if ( is_post_type_archive( 'property' ) ) {
    require_once $inc . 'seo-property-archive.php';
    return;
  }

  // 3) Everything else (pages, posts, taxonomies, etc.)
  require_once $inc . 'seo-all.php';

}, 1 );

define('PERA_V2_IS_LIVE_ON_PROPERTY_ARCHIVE', false);



/* =======================================================
   HELPERS
   ======================================================= */

/**
 * Helper: are we on a BLOG archive (not property archives)?
 * - Category / Tag / Author / Date archives for posts
 * - Excludes custom post type "property" archives and property taxonomies
 */
 

function pera_is_blog_archive() {
    // Only archives
    if ( ! is_archive() ) {
        return false;
    }

    // Exclude property CPT archive
    if ( is_post_type_archive( 'property' ) ) {
        return false;
    }

    // Exclude property taxonomies
    if ( is_tax( array(
        'property_type',
        'region',
        'district',
        'bedrooms',
        'special',
        'property_tags',
    ) ) ) {
        return false;
    }

    return true;
}

/**
 * Are we on a PROPERTY archive (CPT or its taxonomies)?
 */
function pera_is_property_archive() {
    return is_post_type_archive( 'property' ) || is_tax( array(
        'property_type',
        'region',
        'district',
        'bedrooms',
        'special',
        'property_tags',
    ) );
}

/* =======================================================
   TEMPLATE ROUTING
   ======================================================= */

/**
 * Force all PROPERTY archives (CPT + taxonomies)
 * to use our archive-property.php template.
 */
function pera_force_property_archive_template( $template ) {

    // Only affect the front end
    if ( is_admin() ) {
        return $template;
    }

    if ( pera_is_property_archive() ) {
        $custom = get_stylesheet_directory() . '/archive-property.php';

        if ( file_exists( $custom ) ) {
            return $custom;
        }
    }

    return $template;
}
add_filter( 'template_include', 'pera_force_property_archive_template', 20 );

/* =======================================================
   GLOBAL: ENQUEUE CORE STYLES & SCRIPTS (ALL PAGES)
   ======================================================= */
add_action( 'wp_enqueue_scripts', function () {

  /* =========================
     0) ALWAYS
  ========================= */

  // main.css everywhere
  wp_enqueue_style(
    'pera-main-css',
    get_stylesheet_directory_uri() . '/CSS/main.css',
    filemtime( get_stylesheet_directory() . '/CSS/main.css' )
  );

  // main.js everywhere
  wp_enqueue_script(
    'pera-main-js',
    get_stylesheet_directory_uri() . '/js/main.js',
    array(),
    filemtime( get_stylesheet_directory() . '/js/main.js' ),
    true
  );

  /* =========================
     1) VIEW FLAGS
  ========================= */

  $is_home = is_front_page() || is_page_template( 'home-page.php' );

  $is_property_archive = is_post_type_archive( 'property' ) || is_tax( array(
    'property_type',
    'region',
    'district',
    'bedrooms',
    'special',
    'property_tags',
  ) );

  $is_single_property = is_singular( 'property' );

  $is_blog_page    = is_page_template( 'page-posts.php' ) || is_page( 'blog' );
  $is_single_post  = is_singular( 'post' );
  $is_blog_archive = function_exists( 'pera_is_blog_archive' ) ? pera_is_blog_archive() : false;

  // Specific templates
  $is_contact_page = is_page_template( 'page-contact.php' );
  $is_about_new    = is_page_template( 'page-about-new.php' );

  /* =========================
     2) slider.css
     Rule: home, single-property, single-post, contact, about-new
     NOT on property archives / general archives
  ========================= */

  $needs_slider = (
    $is_home ||
    $is_single_property ||
    $is_single_post ||
    $is_contact_page ||
    $is_about_new
  );

  if ( $needs_slider ) {
    wp_enqueue_style(
      'pera-slider-css',
      get_stylesheet_directory_uri() . '/CSS/slider.css',
      array( 'pera-main-css' ),
      filemtime( get_stylesheet_directory() . '/CSS/slider.css' )
    );
  }

  /* =========================
     3) property.css
     Rule: property archive OR single property OR home
  ========================= */

  if ( $is_property_archive || $is_single_property || $is_home ) {
    wp_enqueue_style(
      'pera-property-css',
      get_stylesheet_directory_uri() . '/CSS/property.css',
      array( 'pera-main-css' ),
      filemtime( get_stylesheet_directory() . '/CSS/property.css' )
    );
  }

  /* =========================
     4) property-card.css
     Rule: home OR property archive OR single property OR single post
  ========================= */

  if ( $is_home || $is_property_archive || $is_single_property || $is_single_post ) {

    $deps = array( 'pera-main-css' );
    if ( $needs_slider ) {
      $deps[] = 'pera-slider-css';
    }

    wp_enqueue_style(
      'pera-property-card',
      get_stylesheet_directory_uri() . '/CSS/property-card.css',
      $deps,
      filemtime( get_stylesheet_directory() . '/CSS/property-card.css' )
    );
  }

  /* =========================
     5) blog.css
     Rule: blog page OR single post OR blog archive
  ========================= */

  if ( $is_blog_page || $is_single_post || $is_blog_archive ) {

    $deps = array( 'pera-main-css' );
    if ( $needs_slider ) {
      $deps[] = 'pera-slider-css';
    }

    wp_enqueue_style(
      'pera-blog-css',
      get_stylesheet_directory_uri() . '/CSS/blog.css',
      $deps,
      filemtime( get_stylesheet_directory() . '/CSS/blog.css' )
    );
  }

  /* =========================
     6) posts.css
     Rule: blog page OR single post OR blog archive OR single property
  ========================= */

  if ( $is_blog_page || $is_single_post || $is_blog_archive || $is_single_property ) {

    $deps = array( 'pera-main-css' );
    if ( $needs_slider ) {
      $deps[] = 'pera-slider-css';
    }

    wp_enqueue_style(
      'pera-posts-css',
      get_stylesheet_directory_uri() . '/CSS/posts.css',
      $deps,
      filemtime( get_stylesheet_directory() . '/CSS/posts.css' )
    );
  }

}, 20 );

  /* =========================
     7) New archive page using child elements for property cpt
  ========================= */

add_action('wp_enqueue_scripts', function () {

  if ( is_page_template('page-v2-archive.php') ) {

    // Page-specific CSS (main.css loads globally already)
    wp_enqueue_style(
      'pera-property',
      get_stylesheet_directory_uri() . '/CSS/property.css',
      ['pera-main-css'], // change to your real main.css handle if different
      null
    );

    wp_enqueue_style(
      'pera-property-card',
      get_stylesheet_directory_uri() . '/CSS/property-card.css',
      ['pera-property'],
      null
    );

    // If your AJAX JS lives in main.js already, do nothing here.
    // If you split it later, enqueue that file here.

    // Provide ajaxUrl to JS (if not already localized elsewhere)
    wp_localize_script('pera-main-js', 'peraAjax', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('pera_archive_nonce'),
    ]);
  }

}, 20);


/**
 * Dequeue parent Hello Elementor CSS that constrains .site-main width
 * (safe: leaves your child + main.css intact)
 */
add_action( 'wp_enqueue_scripts', function () {

  // Parent Hello Elementor handles commonly used:
  // - hello-elementor
  // - hello-elementor-style
  // - hello-elementor-theme-style
  // (Dequeue whichever are actually enqueued on your site.)

  wp_dequeue_style( 'hello-elementor' );
  wp_deregister_style( 'hello-elementor' );

  wp_dequeue_style( 'hello-elementor-style' );
  wp_deregister_style( 'hello-elementor-style' );

  wp_dequeue_style( 'hello-elementor-theme-style' );
  wp_deregister_style( 'hello-elementor-theme-style' );

}, 20 );


/**
 * Remove Gutenberg block styles on frontend
 * Safe for lean / non-block themes
 */
add_action( 'wp_enqueue_scripts', function () {

    if ( is_admin() ) {
        return;
    }

    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' ); // WooCommerce blocks (safe even if WC inactive)

}, 100 );

    /* =======================================================
    DEFER SCRIPTS
    ======================================================= */


add_filter( 'style_loader_tag', function ( $html, $handle ) {

  // Only optimise homepage
  if ( ! ( is_front_page() || is_page_template( 'home-page.php' ) ) ) {
    return $html;
  }

  $defer_styles = [
    'pera-slider-css',
    'pera-property-card',
  ];

  if ( ! in_array( $handle, $defer_styles, true ) ) {
    return $html;
  }

  $original = $html;

  $html = preg_replace(
    '/rel=(["\'])stylesheet\1/i',
    'rel=$1stylesheet$1 media="print" onload="this.media=\'all\'"',
    $html,
    1
  );

  $html .= '<noscript>' . $original . '</noscript>';

  return $html;

}, 10, 2 );


/* =======================================================
   6. REGISTER 450px card size
   ======================================================= */


add_action( 'after_setup_theme', function () {
  add_image_size( 'pera-card', 800, 450, true ); // 16:9 crop, good for cards
});


/* =======================================================
   6. REGISTER MENUS
   ======================================================= */
add_action( 'after_setup_theme', function() {
    register_nav_menus( array(
        'footer_menu'   => __( 'Footer Menu', 'hello-elementor-child' ),
        'guidance'      => __( 'Guidance Menu', 'hello-elementor-child' ),
        'main_menu_v1'  => __( 'Main Menu v1', 'hello-elementor-child' ),
    ) );
});

// ============================================================
// SEO: Prevent indexing of unit-specific property URLs
// (?unit_key=2 etc.)
// ============================================================
add_action( 'wp_head', function () {

  if ( ! is_singular( 'property' ) ) {
    return;
  }

  if ( isset( $_GET['unit_key'] ) && absint( $_GET['unit_key'] ) > 0 ) {

    // Canonical to clean property URL
    echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '">' . "\n";

    // Do not index parameterised unit views
    echo '<meta name="robots" content="noindex,follow">' . "\n";
  }

}, 1 );



/* =======================================================
   LOGIN SCREEN (wp-login.php): login.css + BRANDING
   ======================================================= */
add_action( 'login_enqueue_scripts', function () {

  $css_rel  = '/CSS/login.css';
  $css_path = get_stylesheet_directory() . $css_rel;
  $css_url  = get_stylesheet_directory_uri() . $css_rel;

  // Cache-bust using file modified time (falls back to theme version)
  $ver = file_exists( $css_path ) ? (string) filemtime( $css_path ) : wp_get_theme()->get( 'Version' );

  wp_enqueue_style( 'pera-login', $css_url, array(), $ver );

  // Optional: load your theme font if your login.css relies on it
  // wp_enqueue_style( 'pera-fonts', get_stylesheet_directory_uri() . '/CSS/fonts.css', array(), $ver );
}, 20 );

add_filter( 'login_headerurl', function () {
  return home_url( '/' );
} );

add_filter( 'login_headertext', function () {
  return 'Pera Property – Client Login';
} );


add_action('login_enqueue_scripts', function () {

  $bg = wp_get_attachment_image_url(55484, 'full');

  if ($bg) {
    wp_add_inline_style('pera-login', "
      body.login {
        background-image: url('{$bg}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      }
      body.login:before {
        content:'';
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55); /* brand overlay */
        z-index: -1;
      }
    ");
  }

}, 30);

/* =======================================================
   Allow registration
   ======================================================= */

add_action('login_init', function () {
  if (!empty($_GET['action'])) {
    error_log('WP-LOGIN ACTION: ' . sanitize_text_field($_GET['action']));
  }
});


/* =======================================================
   PRELOAD MONTSERRAT (ALL PAGES)
   ======================================================= */
function pera_preload_fonts() {
  $base = get_stylesheet_directory_uri() . '/fonts/';

  echo '<link rel="preload" as="font" href="' . esc_url( $base . 'Montserrat-Regular.woff2' ) . '" type="font/woff2" crossorigin>' . "\n";
  // Optional (recommended if you actually use these weights site-wide):
  // echo '<link rel="preload" as="font" href="' . esc_url( $base . 'Montserrat-Medium.woff2' )  . '" type="font/woff2" crossorigin>' . "\n";
  // echo '<link rel="preload" as="font" href="' . esc_url( $base . 'Montserrat-SemiBold.woff2' ) . '" type="font/woff2" crossorigin>' . "\n";
  // echo '<link rel="preload" as="font" href="' . esc_url( $base . 'Montserrat-Bold.woff2' ) . '" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'pera_preload_fonts', 1 );


/* =======================================================
   8. Floating WhatsApp Button (global except client login)
   (currently disabled by comment)
   ======================================================= */

function pera_floating_whatsapp_button() {

    // Do not output on wp-login.php
    if ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'wp-login.php' ) {
        return;
    }

    ?>
    <a href="https://wa.me/905452054356?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20learn%20more%20about%20your%20Istanbul%20properties."
       class="floating-whatsapp"
       id="floating-whatsapp"
       aria-label="Chat on WhatsApp"
       target="_blank"
       rel="noopener">

        <span class="floating-whatsapp__tooltip">
            Chat on WhatsApp
        </span>

        <svg class="icon" aria-hidden="true">
            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-whatsapp' ); ?>"></use>
        </svg>

    </a>
    <?php
}
add_action( 'wp_footer', 'pera_floating_whatsapp_button' );

/* =======================================================
   9. Ensure the "Forgot Password" page exists with correct slug and template
   ======================================================= */
function pera_register_forgot_password_page() {

    $page_slug     = 'client-forgot-password';
    $page_title    = 'Forgot Password';
    $template_file = 'page-client-forgot-password.php';

    $existing_page = get_page_by_path( $page_slug );

    if ( ! $existing_page ) {

        $page_id = wp_insert_post( array(
            'post_title'   => $page_title,
            'post_name'    => $page_slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );

        if ( ! is_wp_error( $page_id ) ) {
            update_post_meta( $page_id, '_wp_page_template', $template_file );
        }

    } else {

        update_post_meta( $existing_page->ID, '_wp_page_template', $template_file );

        if ( $existing_page->post_name !== $page_slug ) {
            wp_update_post( array(
                'ID'        => $existing_page->ID,
                'post_name' => $page_slug,
            ) );
        }
    }
}
add_action( 'after_switch_theme', 'pera_register_forgot_password_page' );

/* =======================================================
   10. AJAX: Filter properties + Load More (Unified Handler)
   ======================================================= */
function pera_ajax_filter_properties() {

    // -----------------------------------------
    // 1. READ REQUEST
    // -----------------------------------------
    $paged = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;

    $current_region = isset( $_POST['region'] )
        ? sanitize_text_field( wp_unslash( $_POST['region'] ) )
        : '';

    $current_type = isset( $_POST['property_type'] )
        ? sanitize_text_field( wp_unslash( $_POST['property_type'] ) )
        : '';

    // Multi-select: district
    $current_district = array();
    if ( isset( $_POST['district'] ) ) {
        $raw = $_POST['district'];
        if ( is_array( $raw ) ) {
            $current_district = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
        } elseif ( $raw !== '' ) {
            $current_district = array( sanitize_text_field( wp_unslash( $raw ) ) );
        }
    }

    // Multi-select: bedrooms
    $current_beds = array();
    if ( isset( $_POST['bedrooms'] ) ) {
        $raw = $_POST['bedrooms'];
        if ( is_array( $raw ) ) {
            $current_beds = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
        } elseif ( $raw !== '' ) {
            $current_beds = array( sanitize_text_field( wp_unslash( $raw ) ) );
        }
    }

    // Multi-select: tags
    $current_tag = array();
    if ( isset( $_POST['property_tags'] ) ) {
        $raw = $_POST['property_tags'];
        if ( is_array( $raw ) ) {
            $current_tag = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
        } elseif ( $raw !== '' ) {
            $current_tag = array( sanitize_text_field( wp_unslash( $raw ) ) );
        }
    }

    // Price range
    $current_min = ( isset( $_POST['min_price'] ) && $_POST['min_price'] !== '' )
        ? absint( $_POST['min_price'] )
        : null;

    $current_max = ( isset( $_POST['max_price'] ) && $_POST['max_price'] !== '' )
        ? absint( $_POST['max_price'] )
        : null;

    // Keyword search
    $current_keyword = isset( $_POST['s'] )
        ? sanitize_text_field( wp_unslash( $_POST['s'] ) )
        : '';

    // Sort value
    $sort = isset( $_POST['sort'] )
        ? sanitize_text_field( wp_unslash( $_POST['sort'] ) )
        : 'date_desc';

    // -----------------------------------------
    // 2. BASE QUERY
    // -----------------------------------------
    $args = array(
        'post_type'              => 'property',
        'post_status'            => 'publish',
        'posts_per_page'         => 12,
        'paged'                  => $paged,
        'update_post_meta_cache' => false, // keep for speed
        'update_post_term_cache' => false, // keep for speed
    );

    // -----------------------------------------
    // 3. SORTING
    // -----------------------------------------
    switch ( $sort ) {
        case 'price_asc':
            $args['meta_key'] = 'price_usd';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
            break;

        case 'price_desc':
            $args['meta_key'] = 'price_usd';
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

    // -----------------------------------------
    // 4. TAX QUERY
    // -----------------------------------------
    $tax_query = array();

    if ( $current_region ) {
        $tax_query[] = array(
            'taxonomy' => 'region',
            'field'    => 'slug',
            'terms'    => $current_region,
        );
    }

    if ( ! empty( $current_district ) ) {
        $tax_query[] = array(
            'taxonomy' => 'district',
            'field'    => 'slug',
            'terms'    => $current_district,
        );
    }

    if ( $current_type ) {
        $tax_query[] = array(
            'taxonomy' => 'property_type',
            'field'    => 'slug',
            'terms'    => $current_type,
        );
    }

    if ( ! empty( $current_beds ) ) {
        $tax_query[] = array(
            'taxonomy' => 'bedrooms',
            'field'    => 'slug',
            'terms'    => $current_beds,
        );
    }

    if ( ! empty( $current_tag ) ) {
        $tax_query[] = array(
            'taxonomy' => 'property_tags',
            'field'    => 'slug',
            'terms'    => $current_tag,
        );
    }

    if ( ! empty( $tax_query ) ) {
        $args['tax_query'] = array_merge(
            array( 'relation' => 'AND' ),
            $tax_query
        );
    }

    // -----------------------------------------
    // 5. META QUERY – PRICE RANGE
    // -----------------------------------------
    $meta_query = array();

    if ( $current_min !== null || $current_max !== null ) {

        if ( $current_min !== null && $current_max !== null ) {
            $meta_query[] = array(
                'key'     => 'price_usd',
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

    // Keyword search (supports Post ID lookup)
    if ( $current_keyword !== '' ) {
    
        $kw_trim = trim( $current_keyword );
    
        // If the search term is numeric, try treating it as a Property post ID
        if ( ctype_digit( $kw_trim ) ) {
    
            $maybe_id = (int) $kw_trim;
    
            // Only allow published properties
            $p = get_post( $maybe_id );
    
            if ( $p && $p->post_type === 'property' && $p->post_status === 'publish' ) {
    
                // Force exact match by ID
                $args['post__in'] = array( $maybe_id );
    
                // Avoid WP forcing "include" order in some setups
                $args['orderby'] = 'post__in';
    
                // Remove other search constraints that don't matter now
                unset( $args['s'] );
    
            } else {
    
                // Not a valid published property ID: fall back to text search
                $args['s'] = $kw_trim;
            }
    
        } else {
    
            // Normal text search
            $args['s'] = $kw_trim;
        }
    }


        
            // -----------------------------------------
        // 5A. FACET DATA (filtered min/max + term counts) – FAST + PUBLISH ONLY
        // -----------------------------------------
        $facet_min_price = null;
        $facet_max_price = null;
        $district_counts = array();
        $bedroom_counts  = array();
        $tag_counts      = array();
        
        // Clone args for facet query (same filters, but all matching posts)
        $facet_args = $args;
        $facet_args['post_status']     = 'publish'; // enforce
        $facet_args['posts_per_page']  = -1;
        $facet_args['fields']          = 'ids';
        $facet_args['no_found_rows']   = true;
        $facet_args['orderby']         = 'none';
        $facet_args['suppress_filters']= true;
        
        $facet_query = new WP_Query( $facet_args );
        
        if ( $facet_query->have_posts() ) {
        
          $post_ids = array_map( 'intval', (array) $facet_query->posts );
        
          // --- Min/max price across filtered set (fast SQL on postmeta + IN)
          global $wpdb;
        
          $ids_in = implode( ',', $post_ids );
        
          // Min
          $facet_min_price = (int) $wpdb->get_var("
            SELECT MIN(CAST(pm.meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = 'price_usd'
              AND pm.post_id IN ($ids_in)
              AND pm.meta_value <> ''
          ");
        
          // Max
          $facet_max_price = (int) $wpdb->get_var("
            SELECT MAX(CAST(pm.meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = 'price_usd'
              AND pm.post_id IN ($ids_in)
              AND pm.meta_value <> ''
          ");
        
          // Helper: counts for taxonomy within post_ids
          $pera_counts_for_tax_in_posts = function( string $taxonomy ) use ( $wpdb, $ids_in ) {
        
            $sql = $wpdb->prepare("
              SELECT t.slug, t.name, COUNT(DISTINCT tr.object_id) AS cnt
              FROM {$wpdb->term_relationships} tr
              INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
              INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
              WHERE tt.taxonomy = %s
                AND tr.object_id IN ($ids_in)
              GROUP BY t.term_id
            ", $taxonomy );
        
            $rows = $wpdb->get_results( $sql );
        
            $out = array();
            foreach ( (array) $rows as $r ) {
              $out[ $r->slug ] = array(
                'name'  => $r->name,
                'count' => (int) $r->cnt,
              );
            }
        
            return $out;
          };
        
          $district_counts = $pera_counts_for_tax_in_posts( 'district' );
          $bedroom_counts  = $pera_counts_for_tax_in_posts( 'bedrooms' );
          $tag_counts      = $pera_counts_for_tax_in_posts( 'property_tags' );
        }
        
        wp_reset_postdata();


    // -----------------------------------------
    // 6. RUN QUERY
    // -----------------------------------------
    $query = new WP_Query( $args );

    // -----------------------------------------
    // 7. BUILD GRID HTML
    // -----------------------------------------
    ob_start();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            set_query_var( 'pera_property_card_args', array(
              'variant' => 'archive',
            ) );
            
            get_template_part( 'parts/property-card' );
            
            set_query_var( 'pera_property_card_args', array() );

        }
    } else {
        echo '<p class="no-results">No properties found.</p>';
    }

    $grid_html = ob_get_clean();
    wp_reset_postdata();

    // -----------------------------------------
    // 8. RESPONSE
    // -----------------------------------------
    wp_send_json_success( array(
        'grid_html'  => $grid_html,
        'count_text' => $query->found_posts . ' properties found',
        'has_more'   => ( $paged < $query->max_num_pages ),
        'next_page'  => $paged + 1,

        // New: facet data
        'facet_min_price' => $facet_min_price,
        'facet_max_price' => $facet_max_price,
        'district_counts' => $district_counts,
        'bedroom_counts'  => $bedroom_counts,
        'tag_counts'      => $tag_counts,
    ) );
}
add_action( 'wp_ajax_pera_filter_properties', 'pera_ajax_filter_properties' );
add_action( 'wp_ajax_nopriv_pera_filter_properties', 'pera_ajax_filter_properties' );

/* =======================================================
   11. Global min/max price_usd with transient caching
   ======================================================= */
function pera_get_price_usd_range() {
    $cached = get_transient( 'pera_price_usd_range' );

    if ( $cached && isset( $cached['min'], $cached['max'] ) ) {
        return $cached;
    }

    global $wpdb;

    $min_price = (int) $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT MIN( CAST(pm.meta_value AS UNSIGNED) )
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND p.post_type = 'property'
              AND p.post_status = 'publish'
            ",
            'price_usd'
        )
    );

    $max_price = (int) $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT MAX( CAST(pm.meta_value AS UNSIGNED) )
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
              AND p.post_type = 'property'
              AND p.post_status = 'publish'
            ",
            'price_usd'
        )
    );

    if ( ! $min_price ) {
        $min_price = 0;
    }
    if ( ! $max_price ) {
        $max_price = 1000000;
    }

    $data = array(
        'min' => $min_price,
        'max' => $max_price,
    );

    // Cache for 6 hours
    set_transient( 'pera_price_usd_range', $data, 6 * HOUR_IN_SECONDS );

    return $data;
}

/**
 * Invalidate the cached price range when properties change.
 */
function pera_flush_price_range_cache( $post_id ) {
    // Only flush for property CPT
    if ( get_post_type( $post_id ) === 'property' ) {
        delete_transient( 'pera_price_usd_range' );
    }
}
add_action( 'save_post_property', 'pera_flush_price_range_cache' );
add_action( 'deleted_post', 'pera_flush_price_range_cache' );
add_action( 'trashed_post', 'pera_flush_price_range_cache' );


/**
 * home-page-dev JS (hero search logic)
 */
 
 add_action( 'wp_enqueue_scripts', function () {

  if ( ! is_page_template( 'home-page.php' ) ) {
    return;
  }

  wp_enqueue_script(
    'pera-home-hero-search',
    get_stylesheet_directory_uri() . '/js/home-hero-search.js',
    array(),
    filemtime( get_stylesheet_directory() . '/js/home-hero-search.js' ),
    true
  );

}, 40 );

/**
 * -------------------------------------------------
 * V2 Search / Index System (isolated, non-breaking)
 * -------------------------------------------------
 */
require_once get_stylesheet_directory() . '/Inc/v2-units-index.php';
require_once get_stylesheet_directory() . '/Inc/ajax-property-archive.php';

/**
 * functions.php (or your existing loader section)
 * Conditionally load /Inc/enquiry.php only on:
 * - page-citizenship.php
 * - page-rent-with-pera.php
 * - page-sell-with-pera.php
 * - single-property.php
 */
/**
 * Conditionally load enquiry handler early enough for init hook.
 * Location: functions.php
 */
add_action( 'init', function () {

  // Always load if this is a relevant POST (so submissions work even if template checks fail)
  if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ( isset( $_POST['sr_action'] ) || isset( $_POST['pera_citizenship_action'] ) ) ) {
    require_once get_stylesheet_directory() . '/Inc/enquiry.php';
    return;
  }

  // Otherwise load only on relevant front-end views
  if ( is_admin() ) {
    return;
  }

  // Single property
  if ( is_singular( 'property' ) ) {
    require_once get_stylesheet_directory() . '/Inc/enquiry.php';
    return;
  }

  // Pages by template (only works if these are actual template filenames in your theme)
  if (
    is_page_template( 'page-citizenship.php' ) ||
    is_page_template( 'page-rent-with-pera.php' ) ||
    is_page_template( 'page-sell-with-pera.php' )
  ) {
    require_once get_stylesheet_directory() . '/Inc/enquiry.php';
    return;
  }

  // Safety fallback: if your pages are not using those exact filenames, load by slug as well
  if ( is_page( array( 'citizenship-by-investment', 'rent-with-pera', 'sell-with-pera' ) ) ) {
    require_once get_stylesheet_directory() . '/Inc/enquiry.php';
    return;
  }

}, 1 );




/* =======================================================
   ALLOW SVG UPLOADS (ADMIN ONLY)
   ======================================================= */
function pera_allow_svg_uploads( $mimes ) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'pera_allow_svg_uploads' );


/* =======================================================
    GOOGLE MAPS
======================================================= */

add_filter('acf/fields/google_map/api', function ($api) {
  if ( defined('PERA_GOOGLE_MAPS_KEY') && PERA_GOOGLE_MAPS_KEY ) {
    $api['key'] = PERA_GOOGLE_MAPS_KEY;
  }
  return $api;
});

/**
 * Remove language switcher from wp-login.php
 */
add_filter( 'login_display_language_dropdown', '__return_false' );


/**
 * Enable Excerpt field on Pages (for SEO meta descriptions).
 */
add_action( 'init', function () {
  add_post_type_support( 'page', 'excerpt' );
}, 20 );


/* =======================================================
    FORCE A TERMS RECOUNT CODE
 ======================================================= 


add_action( 'init', function () {
  if ( ! current_user_can('manage_options') ) return;

  $terms = get_terms([
    'taxonomy'   => 'bedrooms',
    'hide_empty' => true,
    'fields'     => 'ids',
  ]);

  if ( ! is_wp_error($terms) && $terms ) {
    wp_update_term_count_now( $terms, 'bedrooms' );
  }
}, 1 );*/
