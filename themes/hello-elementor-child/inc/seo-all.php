<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Pera SEO / Social meta (OG + Twitter) – No Yoast
 * - Applies to all public pages EXCEPT single Property CPT (handled separately)
 * - Adds <meta description>, canonical, robots, OG, Twitter
 * - Adds document title filters (so <title> is correct)
 * - Adds "noindex,follow" for:
 *    - WP search results
 *    - Property archive URLs that are filtered via querystring (to avoid index bloat)
 */

/* =======================================================
   CONFIG
======================================================= */

/**
 * Optional: fallback share image (attachment ID).
 * Put a site-wide share image in Media Library and set its attachment ID here.
 * If 0, no fallback image is used.
 */
if ( ! defined('PERA_SEO_DEFAULT_OG_IMAGE_ID') ) {
  define('PERA_SEO_DEFAULT_OG_IMAGE_ID', 0);
}

/* =======================================================
   HELPERS
======================================================= */

if ( ! function_exists('pera_seo_all_get_description') ) {
  function pera_seo_all_get_description( int $post_id ): string {

    $desc = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    $desc = trim( preg_replace('/\s+/', ' ', $desc) );

    if ( $desc === '' ) {
      $content = get_post_field( 'post_content', $post_id );
      $content = wp_strip_all_tags( apply_filters( 'the_content', $content ) );
      $content = trim( preg_replace('/\s+/', ' ', $content) );
      $desc = $content;
    }

    if ( $desc === '' ) return '';

    if ( function_exists('mb_substr') ) $desc = mb_substr( $desc, 0, 160 );
    else $desc = substr( $desc, 0, 160 );

    return $desc;
  }
}

if ( ! function_exists('pera_seo_all_get_image') ) {
  function pera_seo_all_get_image( int $post_id ): array {
    $url = '';
    $alt = '';

    $thumb_id = get_post_thumbnail_id( $post_id );

    if ( $thumb_id ) {
      $url = (string) wp_get_attachment_image_url( (int) $thumb_id, 'full' );

      $alt_meta = get_post_meta( (int) $thumb_id, '_wp_attachment_image_alt', true );
      if ( is_string($alt_meta) && $alt_meta !== '' ) {
        $alt = $alt_meta;
      }
    }

    return array(
      'url' => $url ? esc_url($url) : '',
      'alt' => $alt ? trim($alt) : '',
    );
  }
}

if ( ! function_exists('pera_seo_default_image') ) {
  function pera_seo_default_image(): array {
    $id = (int) PERA_SEO_DEFAULT_OG_IMAGE_ID;
    if ( ! $id ) return array('url' => '', 'alt' => '');

    $url = (string) wp_get_attachment_image_url( $id, 'full' );
    $alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );

    return array(
      'url' => $url ? esc_url($url) : '',
      'alt' => $alt ? trim($alt) : '',
    );
  }
}

/**
 * Canonical fallback (keeps scheme/host consistent).
 */
if ( ! function_exists('pera_seo_all_canonical_fallback') ) {
  function pera_seo_all_canonical_fallback(): string {
    $req = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $req = $req ?: '/';
    $req = preg_replace('/#.*/', '', $req);
    return esc_url( home_url( $req ) );
  }
}

/**
 * Detect “filtered property archive” requests (querystring facets & sort).
 * These should almost always be NOINDEX to avoid index bloat.
 */
if ( ! function_exists('pera_is_filtered_property_archive') ) {
  function pera_is_filtered_property_archive(): bool {

    $is_property_context = is_post_type_archive('property')
      || is_tax( array('region','district','property_type','bedrooms','property_tags') );

    if ( ! $is_property_context ) return false;

    $keys = array(
      's',
      'min_price',
      'max_price',
      'district',
      'bedrooms',
      'property_tags',
      'property_type',
      'sort',
      'paged', // if you ever pass it
    );

    foreach ( $keys as $k ) {
      if ( isset($_GET[$k]) ) {
        $v = $_GET[$k];
        if ( is_array($v) ) {
          if ( ! empty($v) ) return true;
        } else {
          if ( (string)$v !== '' ) return true;
        }
      }
    }

    return false;
  }
}

/**
 * For property contexts, return the stable base URL you want to canonical to
 * when filters are present.
 */
if ( ! function_exists('pera_property_archive_base_url') ) {
  function pera_property_archive_base_url(): string {
    $qo = get_queried_object();

    // Taxonomy archive canonical should usually be itself (term link),
    // but for FILTERED taxonomy archive URLs, we still canonical to the term page.
    if ( is_tax() && $qo && ! is_wp_error($qo) ) {
      $term_link = get_term_link( $qo );
      if ( ! is_wp_error($term_link) && $term_link ) return (string) $term_link;
    }

    return (string) get_post_type_archive_link( 'property' );
  }
}

/* =======================================================
   DOCUMENT TITLE OVERRIDES (browser <title>)
======================================================= */

add_filter( 'pre_get_document_title', function( $title ) {

  if ( is_admin() ) return $title;
  if ( is_singular('property') ) return $title; // excluded

  $qo = get_queried_object();

  // Property archive context
  if ( is_post_type_archive('property') ) {

    // If filtered, use a cleaner generic title (don’t put every filter in title)
    if ( pera_is_filtered_property_archive() ) {
      return 'Property search results in Istanbul';
    }

    return 'Property for sale in Istanbul';
  }

  // Property taxonomy titles
  if ( is_tax( 'region' ) && $qo && ! is_wp_error($qo) ) {
    return sprintf( 'Property for sale in %s, Istanbul', $qo->name );
  }
  if ( is_tax( 'district' ) && $qo && ! is_wp_error($qo) ) {
    return sprintf( '%s property for sale in Istanbul', $qo->name );
  }
  if ( is_tax( 'property_type' ) && $qo && ! is_wp_error($qo) ) {
    return sprintf( '%s in Istanbul', $qo->name );
  }
  if ( is_tax( 'bedrooms' ) && $qo && ! is_wp_error($qo) ) {
    return sprintf( '%s bedroom property for sale in Istanbul', $qo->name );
  }

  return $title;
}, 20 );

/* =======================================================
   HEAD META (description, canonical, robots, OG, Twitter)
======================================================= */

add_action( 'wp_head', function () {

  if ( is_admin() ) return;

  // Exclude your single-property SEO module
  if ( is_singular('property') ) return;

  // Usually not worth indexing/sharing like normal pages
  if ( is_404() ) return;

  $site_name = (string) get_bloginfo('name');
  $title     = wp_strip_all_tags( wp_get_document_title() );

  // Determine a sensible "context id" for excerpt/image
  $post_id = (int) get_queried_object_id();

  // Blog posts index (Settings -> Reading -> Posts page)
  if ( is_home() && ! is_front_page() ) {
    $posts_page_id = (int) get_option('page_for_posts');
    if ( $posts_page_id ) $post_id = $posts_page_id;
  }

  // ---------- Robots ----------
  $robots = '';

  // WP search results: noindex
  if ( is_search() ) {
    $robots = 'noindex,follow';
  }

  // Filtered property archive URLs: noindex (prevents index bloat)
  if ( $robots === '' && pera_is_filtered_property_archive() ) {
    $robots = 'noindex,follow';
  }

  // ---------- Canonical ----------
  $canonical = '';

  // If filtered property archive: canonical should be the stable base (term page or archive)
  if ( pera_is_filtered_property_archive() ) {
    $canonical = pera_property_archive_base_url();
  } else {
    if ( function_exists('wp_get_canonical_url') ) {
      $canonical = wp_get_canonical_url( $post_id ?: null );
    }
    if ( ! $canonical ) {
      $canonical = pera_seo_all_canonical_fallback();
    }
  }

  // ---------- Description ----------
  $desc = '';

  if ( $post_id ) {
    $desc = pera_seo_all_get_description( $post_id );
  } else {
    // Term archives: use term description if available
    if ( is_category() || is_tag() || is_tax() ) {
      $term = get_queried_object();
      if ( $term && ! is_wp_error($term) && ! empty($term->description) ) {
        $desc = wp_strip_all_tags( (string) $term->description );
        $desc = trim( preg_replace('/\s+/', ' ', $desc) );
        if ( function_exists('mb_substr') ) $desc = mb_substr($desc, 0, 160);
        else $desc = substr($desc, 0, 160);
      }
    }
  }

  // If filtered property archive and we have no desc, use a short stable one
  if ( $desc === '' && pera_is_filtered_property_archive() ) {
    $desc = 'Browse Istanbul property listings filtered by district, type, bedrooms and budget.';
  }

  // ---------- Image ----------
  $img_url = '';
  $img_alt = $title;

  if ( $post_id ) {
    $img = pera_seo_all_get_image( $post_id );
    $img_url = $img['url'];
    $img_alt = $img['alt'] ?: $title;
  }

  // Default fallback share image (optional)
  if ( ! $img_url ) {
    $fallback = pera_seo_default_image();
    if ( ! empty($fallback['url']) ) {
      $img_url = $fallback['url'];
      $img_alt = $fallback['alt'] ?: $title;
    }
  }

  $og_type = is_singular() ? 'article' : 'website';

  echo "\n<!-- Pera: SEO / Social -->\n";

  if ( $robots !== '' ) {
    echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
  }

  if ( $desc !== '' ) {
    echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
  }

  echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";

  // Open Graph
  echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
  echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
  echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
  echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";

  if ( $desc !== '' ) {
    echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
  }

  if ( $img_url ) {
    echo '<meta property="og:image" content="' . esc_url($img_url) . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($img_alt) . '">' . "\n";
  }

  // Twitter
  echo '<meta name="twitter:card" content="' . ( $img_url ? 'summary_large_image' : 'summary' ) . '">' . "\n";
  echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";

  if ( $desc !== '' ) {
    echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
  }

  if ( $img_url ) {
    echo '<meta name="twitter:image" content="' . esc_url($img_url) . '">' . "\n";
    echo '<meta name="twitter:image:alt" content="' . esc_attr($img_alt) . '">' . "\n";
  }
  
  if ( is_tax() || is_category() || is_tag() ) {

  $term = get_queried_object();

  if ( $term instanceof WP_Term ) {

    $taxonomy = (string) $term->taxonomy;
    $term_id  = (int) $term->term_id;

    // Use your saved term meta, with safe fallbacks
    $desc = function_exists('pera_get_term_excerpt')
      ? pera_get_term_excerpt( $term_id, $taxonomy, 28 )
      : '';

    $img  = function_exists('pera_get_term_featured_image_url')
      ? pera_get_term_featured_image_url( $term_id, $taxonomy, 'full' )
      : '';

    if ( $desc !== '' ) {
      $desc = wp_strip_all_tags( $desc );
      $desc = trim( preg_replace('/\s+/', ' ', $desc ) );

      echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
      echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
      echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
    }

    if ( $img !== '' ) {
      echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
      echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
    }
  }
}

  echo "<!-- /Pera: SEO / Social -->\n\n";

}, 12 );
