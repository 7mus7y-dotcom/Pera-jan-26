<?php
/**
 * V2 Units – Index Builders (ACF Repeater)
 * ---------------------------------------
 * Builds / maintains:
 * 1) Per-row v2_index_key (beds + size range + price range)
 * 2) Post-meta v2_index_flat (beds only, for fast LIKE filtering)
 * 3) Post-meta v2_price_usd_min / v2_price_usd_max (derived from v2_units for range filtering)
 * 4) Global slider bounds helper (cached): pera_v2_get_price_bounds()
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
/**
 * ---------------------------------------------------------
 * GLOBAL PRICE BOUNDS (for slider)
 * ---------------------------------------------------------
 * Uses post meta:
 * - v2_price_usd_min (required)
 * - v2_price_usd_max (optional; if missing/0, max falls back to min)
 *
 * Cached to transient; auto-cleared on property save.
 */
if ( ! function_exists( 'pera_v2_get_price_bounds' ) ) {
  function pera_v2_get_price_bounds( bool $force_refresh = false ): array {

    $cache_key = 'pera_v2_price_bounds_v1';

    if ( ! $force_refresh ) {
      $cached = get_transient( $cache_key );
      if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
        return $cached;
      }
    }

    global $wpdb;

    // MIN from v2_price_usd_min (>0)
    $min_sql = $wpdb->prepare(
      "
      SELECT MIN(CAST(pm.meta_value AS UNSIGNED))
      FROM {$wpdb->postmeta} pm
      INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
      WHERE p.post_type = %s
        AND p.post_status = 'publish'
        AND pm.meta_key = %s
        AND CAST(pm.meta_value AS UNSIGNED) > 0
      ",
      'property',
      'v2_price_usd_min'
    );

    $min_val = $wpdb->get_var( $min_sql );
    $min     = is_numeric( $min_val ) ? (int) $min_val : 0;

    // MAX: prefer v2_price_usd_max (>0), fallback to v2_price_usd_min
    $max_sql = $wpdb->prepare(
      "
      SELECT GREATEST(
        COALESCE((
          SELECT MAX(CAST(pm2.meta_value AS UNSIGNED))
          FROM {$wpdb->postmeta} pm2
          INNER JOIN {$wpdb->posts} p2 ON p2.ID = pm2.post_id
          WHERE p2.post_type = %s
            AND p2.post_status = 'publish'
            AND pm2.meta_key = %s
            AND CAST(pm2.meta_value AS UNSIGNED) > 0
        ), 0),
        COALESCE((
          SELECT MAX(CAST(pm3.meta_value AS UNSIGNED))
          FROM {$wpdb->postmeta} pm3
          INNER JOIN {$wpdb->posts} p3 ON p3.ID = pm3.post_id
          WHERE p3.post_type = %s
            AND p3.post_status = 'publish'
            AND pm3.meta_key = %s
            AND CAST(pm3.meta_value AS UNSIGNED) > 0
        ), 0)
      ) AS max_val
      ",
      'property', 'v2_price_usd_max',
      'property', 'v2_price_usd_min'
    );

    $max_val = $wpdb->get_var( $max_sql );
    $max     = is_numeric( $max_val ) ? (int) $max_val : 0;

    // Base fallbacks (prevents $0 UI)
    if ( $min <= 0 ) { $min = 50000; }
    if ( $max <= 0 ) { $max = 1000000; }
    if ( $max < $min ) { $max = $min; }

    /**
     * OPTION A — UX clamps
     * Prevent a single outlier (e.g. $27m) from ruining the slider.
     * Adjust these to taste.
     */
    $min_floor = 50000;     // never show below this
    $max_cap   = 5000000;   // never show above this

    $min = max( $min, $min_floor );
    $max = min( $max, $max_cap );

    if ( $max < $min ) {
      $max = $min;
    }

    $out = array(
      'min' => (int) $min,
      'max' => (int) $max,
    );

    // Cache for 6 hours
    set_transient( $cache_key, $out, 6 * HOUR_IN_SECONDS );

    return $out;
  }
}

/**
 * Clear cached bounds whenever a property is saved (so slider updates)
 */
if ( ! function_exists( 'pera_v2_clear_price_bounds_cache' ) ) {
  function pera_v2_clear_price_bounds_cache(): void {
    delete_transient( 'pera_v2_price_bounds_v1' );
  }
}
add_action( 'save_post_property', 'pera_v2_clear_price_bounds_cache', 20 );


add_action('admin_init', function () {
  if ( ! current_user_can('manage_options') ) return;
  if ( ! isset($_GET['debug_price_max']) ) return;

  $q = new WP_Query([
    'post_type'      => 'property',
    'post_status'    => 'publish',
    'posts_per_page' => 30,
    'meta_key'       => 'v2_price_usd_max',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'meta_query'     => [
      [
        'key'     => 'v2_price_usd_max',
        'value'   => 0,
        'compare' => '>',
        'type'    => 'NUMERIC',
      ]
    ],
  ]);

  echo '<pre>';
  foreach ($q->posts as $p) {
    $min = get_post_meta($p->ID, 'v2_price_usd_min', true);
    $max = get_post_meta($p->ID, 'v2_price_usd_max', true);
    echo "{$p->ID} | {$p->post_status} | " . get_the_title($p->ID) . " | min={$min} | max={$max}\n";
  }
  echo '</pre>';
  exit;
});


/**
 * ---------------------------------------------------------
 * V2 REINDEX ON SAVE (ACF)
 * ---------------------------------------------------------
 */
add_action( 'acf/save_post', 'pera_v2_units_reindex_on_save', 20 );

function pera_v2_units_reindex_on_save( $post_id ) {

  // Only Property CPT
  if ( get_post_type( $post_id ) !== 'property' ) {
    return;
  }

  // Prevent autosave / revisions
  if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
    return;
  }

  // Prevent recursion if update_field triggers save_post again
  static $running = array();
  if ( isset( $running[ $post_id ] ) ) {
    return;
  }
  $running[ $post_id ] = true;

  try {

    $rows = function_exists('get_field') ? get_field( 'v2_units', $post_id ) : null;

    // If no rows, clear derived metas and exit.
    if ( empty( $rows ) || ! is_array( $rows ) ) {
      delete_post_meta( $post_id, 'v2_index_flat' );
      delete_post_meta( $post_id, 'v2_price_usd_min' );
      delete_post_meta( $post_id, 'v2_price_usd_max' );
      pera_v2_clear_price_bounds_cache();
      return;
    }

    $flat_tokens = array(); // e.g. ["|1","|2"]
    $updated     = false;

    // Post-level price aggregation across ALL rows
    $post_price_min = 0;
    $post_price_max = 0;

    foreach ( $rows as $i => $row ) {

      // Read min/max size
      $size_min = isset( $row['v2_gross_size_min'] ) ? (float) $row['v2_gross_size_min'] : 0;
      $size_max = isset( $row['v2_gross_size_max'] ) ? (float) $row['v2_gross_size_max'] : 0;

      // Read min/max price
      $price_min = isset( $row['v2_price_usd_min'] ) ? (float) $row['v2_price_usd_min'] : 0;
      $price_max = isset( $row['v2_price_usd_max'] ) ? (float) $row['v2_price_usd_max'] : 0;

      // Normalize resales (max defaults to min)
      if ( $size_max  <= 0 && $size_min  > 0 ) $size_max  = $size_min;
      if ( $price_max <= 0 && $price_min > 0 ) $price_max = $price_min;

      // Defensive: if only max exists, copy to min
      if ( $size_min  <= 0 && $size_max  > 0 ) $size_min  = $size_max;
      if ( $price_min <= 0 && $price_max > 0 ) $price_min = $price_max;

      // Defensive: swap if reversed
      if ( $size_min > 0 && $size_max > 0 && $size_min > $size_max ) {
        $tmp = $size_min; $size_min = $size_max; $size_max = $tmp;
      }
      if ( $price_min > 0 && $price_max > 0 && $price_min > $price_max ) {
        $tmp = $price_min; $price_min = $price_max; $price_max = $tmp;
      }

      // Post-level price aggregation (ints)
      $pm = (int) round( $price_min );
      $px = (int) round( $price_max );

      if ( $pm > 0 ) {
        if ( $post_price_min === 0 || $pm < $post_price_min ) $post_price_min = $pm;
      }
      if ( $px > 0 ) {
        if ( $px > $post_price_max ) $post_price_max = $px;
      }

      // Bedrooms (required for index_key + flat index)
      $beds_raw = isset( $row['v2_bedrooms'] ) ? trim( (string) $row['v2_bedrooms'] ) : '';

      if ( $beds_raw === '' || ! ctype_digit( $beds_raw ) ) {
        // no beds => skip index_key + flat token (but keep price aggregation)
        continue;
      }

      $beds = (int) $beds_raw;
      if ( $beds <= 0 ) continue;

      // Flat bed token
      $flat_tokens[] = '|' . $beds;

      // Compact decimals for size (avoid 55.00 etc.)
      $fmt_size_min = rtrim( rtrim( (string) $size_min, '0' ), '.' );
      $fmt_size_max = rtrim( rtrim( (string) $size_max, '0' ), '.' );

      // Per-row v2_index_key
      // Format: beds|sizeMin-sizeMax|priceMin-priceMax
      // Example: 2|55-85|250000-420000
      $index_key = $beds . '|' . $fmt_size_min . '-' . $fmt_size_max . '|' . (int) round( $price_min ) . '-' . (int) round( $price_max );

      $current_key = isset( $row['v2_index_key'] ) ? (string) $row['v2_index_key'] : '';

      if ( $current_key !== $index_key ) {
        $rows[ $i ]['v2_index_key'] = $index_key;
        $updated = true;
      }
    }

    // Write repeater back ONLY if we changed any row key
    if ( $updated ) {
      update_field( 'v2_units', $rows, $post_id );
    }

    // Store post-level price metas
    if ( $post_price_min > 0 ) update_post_meta( $post_id, 'v2_price_usd_min', $post_price_min );
    else delete_post_meta( $post_id, 'v2_price_usd_min' );

    if ( $post_price_max > 0 ) update_post_meta( $post_id, 'v2_price_usd_max', $post_price_max );
    else delete_post_meta( $post_id, 'v2_price_usd_max' );

    // Store v2_index_flat (beds only)
    $flat_tokens = array_values( array_unique( $flat_tokens ) );

    if ( empty( $flat_tokens ) ) {
      delete_post_meta( $post_id, 'v2_index_flat' );
    } else {
      $flat_index = implode( '', $flat_tokens ) . '|';
      update_post_meta( $post_id, 'v2_index_flat', $flat_index );
    }

    // Clear bounds cache so slider reflects new data
    pera_v2_clear_price_bounds_cache();

  } finally {
    unset( $running[ $post_id ] );
  }
}


/* ============================================================
   V2 UNITS – SHARED HELPERS (Archive + AJAX + Single)
   Purpose:
   - Reuse the SAME aggregation logic everywhere.
   - Keep ajax-property-archive.php + templates thin.
   ============================================================ */

if ( ! function_exists( 'pera_v2_get_units_rows' ) ) {
  /**
   * Get v2_units rows for a property post.
   */
  function pera_v2_get_units_rows( int $post_id ): array {
    if ( ! function_exists( 'get_field' ) ) {
      return array();
    }

    $rows = get_field( 'v2_units', $post_id );
    return is_array( $rows ) ? $rows : array();
  }
}

if ( ! function_exists( 'pera_v2_units_filter_by_beds' ) ) {
  /**
   * Filter repeater rows to only those matching $beds (if $beds > 0).
   */
  function pera_v2_units_filter_by_beds( array $rows, int $beds = 0 ): array {
    if ( $beds <= 0 ) {
      return $rows;
    }

    $out = array();
    foreach ( $rows as $row ) {
      $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
      if ( $b === $beds ) {
        $out[] = $row;
      }
    }
    return $out;
  }
}

if ( ! function_exists( 'pera_v2_units_aggregate' ) ) {
  /**
   * Aggregate min/max beds, price, and size from v2_units.
   *
   * Rules:
   * - If v2_price_usd_max missing/0 => treat as v2_price_usd_min
   * - If v2_gross_size_max missing/0 => treat as v2_gross_size_min
   *
   * Returns:
   * [
   *   'beds_min'  => int,
   *   'beds_max'  => int,
   *   'price_min' => int,
   *   'price_max' => int,
   *   'size_min'  => float,
   *   'size_max'  => float,
   *   'rows'      => array,   // rows used for aggregation (filtered if beds provided)
   * ]
   */
  function pera_v2_units_aggregate( array $rows, int $beds = 0 ): array {

    $rows_to_use = pera_v2_units_filter_by_beds( $rows, $beds );

    $beds_vals = array();

    $beds_min  = 0; $beds_max  = 0;
    $price_min = 0; $price_max = 0;
    $size_min  = 0; $size_max  = 0;

    foreach ( $rows_to_use as $row ) {

      $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
      if ( $b > 0 ) {
        $beds_vals[] = $b;
      }

        $pmin = isset( $row['v2_price_usd_min'] ) ? (int) $row['v2_price_usd_min'] : 0;
        $pmax = isset( $row['v2_price_usd_max'] ) ? (int) $row['v2_price_usd_max'] : 0;
    
    // Normalize (match reindex logic)
    if ( $pmin > 0 && $pmax <= 0 ) $pmax = $pmin;
    if ( $pmin <= 0 && $pmax > 0 ) $pmin = $pmax;
    if ( $pmin > 0 && $pmax > 0 && $pmin > $pmax ) { $t = $pmin; $pmin = $pmax; $pmax = $t; }


      if ( $pmin > 0 ) {
        $price_min = ( $price_min === 0 ) ? $pmin : min( $price_min, $pmin );
      }
      if ( $pmax > 0 ) {
        $price_max = ( $price_max === 0 ) ? $pmax : max( $price_max, $pmax );
      }

    $smin = isset( $row['v2_gross_size_min'] ) ? (float) $row['v2_gross_size_min'] : 0;
    $smax = isset( $row['v2_gross_size_max'] ) ? (float) $row['v2_gross_size_max'] : 0;
    
    // Normalize (match reindex logic)
    if ( $smin > 0 && $smax <= 0 ) $smax = $smin;
    if ( $smin <= 0 && $smax > 0 ) $smin = $smax;
    if ( $smin > 0 && $smax > 0 && $smin > $smax ) { $t = $smin; $smin = $smax; $smax = $t; }


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

    return array(
      'beds_min'  => (int) $beds_min,
      'beds_max'  => (int) $beds_max,
      'price_min' => (int) $price_min,
      'price_max' => (int) $price_max,
      'size_min'  => (float) $size_min,
      'size_max'  => (float) $size_max,
      'rows'      => $rows_to_use,
    );
  }
}

if ( ! function_exists( 'pera_v2_units_format_price_text' ) ) {
  /**
   * Standardize your V2 headline price rules (card + single):
   * - Project: "From $MIN"
   * - Resale: "$MIN" or "$MIN–$MAX"
   */
  function pera_v2_units_format_price_text( int $price_min, int $price_max, bool $is_project ): string {

    if ( $price_min <= 0 ) {
      return '';
    }

    $fmt = function( int $n ): string {
      return '$' . number_format_i18n( $n );
    };

    if ( $is_project ) {
      return 'From ' . $fmt( $price_min );
    }

    if ( $price_max > 0 && $price_max !== $price_min ) {
      return $fmt( $price_min ) . '–' . $fmt( $price_max );
    }

    return $fmt( $price_min );
  }
}

if ( ! function_exists( 'pera_v2_units_format_size_text' ) ) {
  /**
   * Format size range "X–Y m²" (or single value).
   */
  function pera_v2_units_format_size_text( float $size_min, float $size_max ): string {

    if ( $size_min <= 0 ) {
      return '';
    }

    $fmt = function( float $n ): string {
      return number_format_i18n( (int) round( $n ) ) . ' m²';
    };

    if ( $size_max > 0 && (int) $size_max !== (int) $size_min ) {
      return $fmt( $size_min ) . '–' . $fmt( $size_max );
    }

    return $fmt( $size_min );
  }
}

/* ============================================================
   COMPATIBILITY ALIAS (so templates can call pera_v2_get_units)
   ============================================================ */
if ( ! function_exists( 'pera_v2_get_units' ) ) {
  function pera_v2_get_units( int $post_id ): array {
    return function_exists( 'pera_v2_get_units_rows' )
      ? pera_v2_get_units_rows( $post_id )
      : ( function_exists( 'get_field' ) ? ( is_array( get_field( 'v2_units', $post_id ) ) ? get_field( 'v2_units', $post_id ) : array() ) : array() );
  }
}

/* ============================================================
   PICKER: choose the “best” row for a bed count
   Rule: cheapest matching row by price_min
   Returns a NORMALISED structure to match your single template:
   ['beds','size_min','size_max','price_min','price_max','raw']
   ============================================================ */
if ( ! function_exists( 'pera_v2_pick_unit_by_beds' ) ) {
  function pera_v2_pick_unit_by_beds( array $rows, int $beds ): ?array {
    if ( $beds <= 0 ) return null;

    $best_row = null;
    $best_min = 0;

    foreach ( $rows as $row ) {
      if ( ! is_array( $row ) ) continue;

      $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
      if ( $b !== $beds ) continue;

      $pmin = isset( $row['v2_price_usd_min'] ) ? (int) $row['v2_price_usd_min'] : 0;
      $pmax = isset( $row['v2_price_usd_max'] ) ? (int) $row['v2_price_usd_max'] : 0;

      // Normalise price (match your reindex logic)
      if ( $pmin > 0 && $pmax <= 0 ) $pmax = $pmin;
      if ( $pmin <= 0 && $pmax > 0 ) $pmin = $pmax;
      if ( $pmin > 0 && $pmax > 0 && $pmin > $pmax ) { $t = $pmin; $pmin = $pmax; $pmax = $t; }

      // Choose cheapest available
      if ( $best_row === null ) {
        $best_row = array( 'row' => $row, 'pmin' => $pmin, 'pmax' => $pmax );
        $best_min = $pmin;
        continue;
      }

      if ( $pmin > 0 && ( $best_min <= 0 || $pmin < $best_min ) ) {
        $best_row = array( 'row' => $row, 'pmin' => $pmin, 'pmax' => $pmax );
        $best_min = $pmin;
      }
    }

    if ( $best_row === null ) return null;

    $row = $best_row['row'];

    $smin = isset( $row['v2_gross_size_min'] ) ? (float) $row['v2_gross_size_min'] : 0;
    $smax = isset( $row['v2_gross_size_max'] ) ? (float) $row['v2_gross_size_max'] : 0;

    // Normalise size (match your reindex logic)
    if ( $smin > 0 && $smax <= 0 ) $smax = $smin;
    if ( $smin <= 0 && $smax > 0 ) $smin = $smax;
    if ( $smin > 0 && $smax > 0 && $smin > $smax ) { $t = $smin; $smin = $smax; $smax = $t; }

    return array(
      'beds'      => $beds,
      'size_min'  => $smin,
      'size_max'  => $smax,
      'price_min' => (int) $best_row['pmin'],
      'price_max' => (int) $best_row['pmax'],
      'raw'       => $row,
    );
  }
}


/* ============================================================
   NORMALISER (internal) — keep structure consistent everywhere
   ============================================================ */
if ( ! function_exists( 'pera_v2_normalise_unit_row' ) ) {
  function pera_v2_normalise_unit_row( array $row ): array {

    $beds = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;

    $pmin = isset( $row['v2_price_usd_min'] ) ? (int) $row['v2_price_usd_min'] : 0;
    $pmax = isset( $row['v2_price_usd_max'] ) ? (int) $row['v2_price_usd_max'] : 0;

    // Normalise price (match reindex logic)
    if ( $pmin > 0 && $pmax <= 0 ) $pmax = $pmin;
    if ( $pmin <= 0 && $pmax > 0 ) $pmin = $pmax;
    if ( $pmin > 0 && $pmax > 0 && $pmin > $pmax ) { $t = $pmin; $pmin = $pmax; $pmax = $t; }

    $smin = isset( $row['v2_gross_size_min'] ) ? (float) $row['v2_gross_size_min'] : 0;
    $smax = isset( $row['v2_gross_size_max'] ) ? (float) $row['v2_gross_size_max'] : 0;

    // Normalise size (match reindex logic)
    if ( $smin > 0 && $smax <= 0 ) $smax = $smin;
    if ( $smin <= 0 && $smax > 0 ) $smin = $smax;
    if ( $smin > 0 && $smax > 0 && $smin > $smax ) { $t = $smin; $smin = $smax; $smax = $t; }

    return array(
      'beds'      => $beds,
      'size_min'  => $smin,
      'size_max'  => $smax,
      'price_min' => $pmin,
      'price_max' => $pmax,
      'raw'       => $row,
    );
  }
}

/* ============================================================
   AGGREGATOR: one row per bed type (cheapest min; highest max)
   ============================================================ */
if ( ! function_exists( 'pera_v2_units_aggregate_by_beds' ) ) {
  /**
   * Returns normalised unit summary per bed type.
   *
   * @param int $post_id Property ID
   * @return array[] [
   *   ['beds'=>2,'size_min'=>55,'size_max'=>85,'price_min'=>250000,'price_max'=>420000,'raw'=>array|null],
   *   ...
   * ]
   */
  function pera_v2_units_aggregate_by_beds( int $post_id ): array {

    $rows = function_exists( 'pera_v2_get_units_rows' )
      ? pera_v2_get_units_rows( $post_id )
      : array();

    if ( empty( $rows ) || ! is_array( $rows ) ) {
      return array();
    }

    $by_beds = array();

    foreach ( $rows as $row ) {
      if ( ! is_array( $row ) ) continue;

      $unit = function_exists( 'pera_v2_normalise_unit_row' )
        ? pera_v2_normalise_unit_row( $row )
        : array();

      $beds = isset( $unit['beds'] ) ? (int) $unit['beds'] : 0;
      if ( $beds <= 0 ) continue;

      // First entry for this bed type
      if ( ! isset( $by_beds[ $beds ] ) ) {
        $by_beds[ $beds ] = array(
          'beds'      => $beds,
          'size_min'  => (float) ( $unit['size_min'] ?? 0 ),
          'size_max'  => (float) ( $unit['size_max'] ?? 0 ),
          'price_min' => (int)   ( $unit['price_min'] ?? 0 ),
          'price_max' => (int)   ( $unit['price_max'] ?? 0 ),
          'raw'       => $row,
        );
        continue;
      }

      // Cheapest min
      $pmin = (int) ( $unit['price_min'] ?? 0 );
      if ( $pmin > 0 && ( $by_beds[ $beds ]['price_min'] <= 0 || $pmin < $by_beds[ $beds ]['price_min'] ) ) {
        $by_beds[ $beds ]['price_min'] = $pmin;
        $by_beds[ $beds ]['raw']       = $row; // keep the row that gave cheapest min
      }

      // Highest max (truthful range)
      $pmax = (int) ( $unit['price_max'] ?? 0 );
      if ( $pmax > 0 && $pmax > (int) $by_beds[ $beds ]['price_max'] ) {
        $by_beds[ $beds ]['price_max'] = $pmax;
      }

      // Size range min of mins / max of maxes
      $smin = (float) ( $unit['size_min'] ?? 0 );
      $smax = (float) ( $unit['size_max'] ?? 0 );

      if ( $smin > 0 && ( $by_beds[ $beds ]['size_min'] <= 0 || $smin < (float) $by_beds[ $beds ]['size_min'] ) ) {
        $by_beds[ $beds ]['size_min'] = $smin;
      }
      if ( $smax > 0 && $smax > (float) $by_beds[ $beds ]['size_max'] ) {
        $by_beds[ $beds ]['size_max'] = $smax;
      }
    }

    $out = array_values( $by_beds );

    // Defensive clamp (covers any future changes)
    foreach ( $out as &$r ) {
      if ( $r['price_min'] > 0 && $r['price_max'] > 0 && $r['price_max'] < $r['price_min'] ) {
        $r['price_max'] = $r['price_min'];
      }
      if ( $r['size_min'] > 0 && $r['size_max'] > 0 && $r['size_max'] < $r['size_min'] ) {
        $r['size_max'] = $r['size_min'];
      }
    }
    unset( $r );

    // Sort by beds asc (and then min price)
    usort( $out, function( $a, $b ) {
      if ( (int) $a['beds'] === (int) $b['beds'] ) {
        return (int) $a['price_min'] <=> (int) $b['price_min'];
      }
      return (int) $a['beds'] <=> (int) $b['beds'];
    } );

    return $out;
  }
}

/* ============================================================
   SELECTED UNIT: reads unit_key (beds) and returns normalised row
   ============================================================ */
if ( ! function_exists( 'pera_v2_get_selected_unit' ) ) {
  /**
   * Select a unit based on passed beds (unit_key) and return a normalised structure.
   *
   * @param int $post_id Property ID
   * @param int $beds    Bed count (e.g. 2 from ?unit_key=2)
   * @return array|null  Normalised unit structure (or null if none)
   */
  function pera_v2_get_selected_unit( int $post_id, int $beds ): ?array {

    if ( $beds <= 0 ) return null;

    // Prefer your existing picker if present
    $rows = function_exists( 'pera_v2_get_units' )
      ? pera_v2_get_units( $post_id )
      : ( function_exists( 'pera_v2_get_units_rows' ) ? pera_v2_get_units_rows( $post_id ) : array() );

    if ( empty( $rows ) ) return null;

    if ( function_exists( 'pera_v2_pick_unit_by_beds' ) ) {
      return pera_v2_pick_unit_by_beds( $rows, $beds );
    }

    // Fallback: find first matching row and normalise
    foreach ( $rows as $row ) {
      if ( ! is_array( $row ) ) continue;
      $b = isset( $row['v2_bedrooms'] ) ? (int) $row['v2_bedrooms'] : 0;
      if ( $b !== $beds ) continue;

      return function_exists( 'pera_v2_normalise_unit_row' )
        ? pera_v2_normalise_unit_row( $row )
        : array(
            'beds'      => $beds,
            'size_min'  => 0,
            'size_max'  => 0,
            'price_min' => 0,
            'price_max' => 0,
            'raw'       => $row,
          );
    }

    return null;
  }
}

/* ============================================================
   RENDERER: Price range table (Step 2C)
   Uses existing classes: content-panel-box, table, table--striped
   ============================================================ */
if ( ! function_exists( 'pera_v2_render_units_price_table' ) ) {
  function pera_v2_render_units_price_table( int $post_id ): void {

    // Prefer the shared aggregator (Step 1) so this renderer stays thin
    $out = function_exists( 'pera_v2_units_aggregate_by_beds' )
      ? pera_v2_units_aggregate_by_beds( $post_id )
      : array();

    if ( empty( $out ) ) {
      return;
    }
    ?>
    <section class="section section-soft property-price-range">
      <div class="container">

        <header class="section-header">
          <h2>Price range</h2>
          <p>Indicative prices by unit type. Availability may change. Contact us for specific pricing and floor plans.</p>
        </header>

        <div class="content-panel-box">
          <div class="table-wrap" role="region" aria-label="Price range table">
            <table>
              <thead>
                <tr>
                  <th scope="col">Type</th>
                  <th scope="col">Gross size</th>
                  <th scope="col">Price (USD)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ( $out as $r ) : ?>
                  <?php
                    $type = (int) $r['beds'] . '+1';

                    $size_txt = function_exists( 'pera_v2_units_format_size_text' )
                      ? pera_v2_units_format_size_text( (float) $r['size_min'], (float) $r['size_max'] )
                      : '';

                    $price_txt = function_exists( 'pera_v2_units_format_price_text' )
                      ? pera_v2_units_format_price_text( (int) $r['price_min'], (int) $r['price_max'], false )
                      : '';
                  ?>
                  <tr>
                    <td><?php echo esc_html( $type ); ?></td>
                    <td><?php echo esc_html( $size_txt ?: '—' ); ?></td>
                    <td><?php echo esc_html( $price_txt ?: '—' ); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </section>
    <?php
  }
}


