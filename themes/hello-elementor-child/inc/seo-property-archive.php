<?php
/**
 * SEO: Property archive + V2 staging page
 *
 * Current state:
 * - /property/ is primary (indexable)
 * - /v2-property/ is staging (noindex,follow + canonical to /property/)
 *
 * Future state:
 * - V2 becomes /property/ (still indexable)
 * - /v2-property/ should 301 to /property/ (recommended)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_head', function () {

  $is_property_archive = is_post_type_archive( 'property' );

  // IMPORTANT: match your actual V2 template filename
  $is_v2_page = is_page_template( 'page-v2-archive.php' );

  if ( ! $is_property_archive && ! $is_v2_page ) {
    return;
  }

  // -------------------------------
  // Resolve current page number
  // -------------------------------
  $paged = max( 1, (int) get_query_var( 'paged' ) );
  if ( get_query_var( 'page' ) ) {
    $paged = max( $paged, (int) get_query_var( 'page' ) );
  }

  // -------------------------------
  // Filter detection (querystring)
  // -------------------------------
  $filter_keys = array(
    's',
    'property_type',
    'v2_beds',
    'district',
    'property_tags',
    'min_price',
    'max_price',
    'sort',
    'region',
  );

  $is_filtered = false;

  foreach ( $filter_keys as $k ) {
    if ( isset( $_GET[ $k ] ) && $_GET[ $k ] !== '' ) {
      $is_filtered = true;
      break;
    }
  }

  // Arrays (district[], property_tags[])
  if ( isset($_GET['district']) && is_array($_GET['district']) && ! empty($_GET['district']) ) {
    $is_filtered = true;
  }
  if ( isset($_GET['property_tags']) && is_array($_GET['property_tags']) && ! empty($_GET['property_tags']) ) {
    $is_filtered = true;
  }

  // -------------------------------
  // Canonical base decisions
  // -------------------------------
  $property_base = get_post_type_archive_link( 'property' );
  if ( ! $property_base ) {
    $property_base = home_url( '/property/' );
  }

  // Self base for V2 page
  $v2_base = $is_v2_page ? get_permalink() : '';

  // Build canonicals with /page/N/
  $property_canonical = ( $paged > 1 )
    ? trailingslashit( $property_base ) . 'page/' . $paged . '/'
    : $property_base;

  $v2_canonical = ( $is_v2_page && $v2_base )
    ? ( ($paged > 1) ? trailingslashit($v2_base) . 'page/' . $paged . '/' : $v2_base )
    : '';

  // -------------------------------
  // MODE: Is V2 live on /property/ yet?
  // -------------------------------
  $v2_live = defined('PERA_V2_IS_LIVE_ON_PROPERTY_ARCHIVE') && PERA_V2_IS_LIVE_ON_PROPERTY_ARCHIVE;

  // -------------------------------
  // Robots + canonical logic
  // -------------------------------
  $robots    = '';
  $canonical = '';

  if ( $is_v2_page ) {

    // While V2 is NOT live on /property/, always keep staging noindexed.
    if ( ! $v2_live ) {
      $robots    = 'noindex,follow';
      $canonical = $property_canonical; // point staging to primary archive
    } else {
      // If you keep the v2 page after launch, it should not compete.
      $robots    = 'noindex,follow';
      $canonical = $property_canonical;
    }

  } else {
    // /property/ archive
    // Always indexable base/pagination, but filtered should be noindex.
    if ( $is_filtered ) {
      $robots = 'noindex,follow';
    }
    $canonical = $property_canonical;
  }

  // -------------------------------
  // Output
  // -------------------------------
  echo "\n<!-- Pera SEO: Property archive / V2 -->\n";

  if ( $robots ) {
    echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
  }

  if ( $canonical ) {
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
  }

  echo "<!-- /Pera SEO: Property archive / V2 -->\n\n";

}, 30 );
