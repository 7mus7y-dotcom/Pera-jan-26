<?php
/**
 * AJAX: Property Archive (V2-only)
 * Endpoint action: pera_filter_properties_v2
 *
 * Filters supported (POST):
 * - v2_beds (int)                  -> meta LIKE on v2_index_flat: |2|
 * - district[] (slugs)             -> taxonomy filter (district)
 * - property_tags[] (slugs)        -> taxonomy filter (property_tags)
 * - property_type (slug)           -> taxonomy filter (property_type)
 * - min_price / max_price (number) -> meta range filter on v2_price_usd_min
 * - sort (string)                  -> newest|oldest|price_asc|price_desc
 * - paged (int)
 *
 * Returns JSON:
 * - grid_html
 * - count_text
 * - has_more, next_page
 * - district_counts, bedroom_counts, tag_counts, property_type_counts
 * - price_bounds (global + applied)
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/* ============================================================
   Helpers
   ============================================================ */

if ( ! function_exists( 'pera_v2_filter_array_of_slugs' ) ) {
  function pera_v2_filter_array_of_slugs( $raw ): array {
    if ( ! is_array( $raw ) ) $raw = array();
    $raw = array_map( 'wp_unslash', $raw );
    $raw = array_map( 'sanitize_title', $raw );
    $raw = array_values( array_filter( $raw ) );
    return $raw;
  }
}

if ( ! function_exists( 'pera_v2_parse_beds_from_index' ) ) {
  /**
   * v2_index_flat example: "|1|2|3|"
   * Returns: ["1","2","3"]
   */
  function pera_v2_parse_beds_from_index( string $idx ): array {
    if ( $idx === '' ) return array();

    $parts = array_filter( explode( '|', $idx ) );
    $out   = array();

    foreach ( $parts as $p ) {
      $p = trim( (string) $p );
      if ( $p === '' ) continue;
      if ( ! ctype_digit( $p ) ) continue;
      $out[] = $p;
    }

    return array_values( array_unique( $out ) );
  }
}

if ( ! function_exists( 'pera_v2_add_term_counts_for_posts' ) ) {
  /**
   * Counts terms for a taxonomy within a given post ID set.
   * Output:
   * [
   *   "slug" => ["name" => "Name", "count" => X],
   * ]
   */
  function pera_v2_add_term_counts_for_posts( array $post_ids, string $taxonomy ): array {
    $counts = array();

    if ( empty( $post_ids ) ) return $counts;

    // Pull all terms for these posts in one go (then count per post)
    $terms = wp_get_object_terms(
      $post_ids,
      $taxonomy,
      array( 'fields' => 'all' )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
      return $counts;
    }

    foreach ( $post_ids as $pid ) {
      $post_terms = get_the_terms( $pid, $taxonomy );
      if ( empty( $post_terms ) || is_wp_error( $post_terms ) ) continue;

      foreach ( $post_terms as $t ) {
        $slug = (string) $t->slug;
        if ( ! isset( $counts[ $slug ] ) ) {
          $counts[ $slug ] = array(
            'name'  => (string) $t->name,
            'count' => 0,
          );
        }
        $counts[ $slug ]['count']++;
      }
    }

    return $counts;
  }
}


/* ============================================================
   Main AJAX handler
   ============================================================ */

if ( ! function_exists( 'pera_ajax_filter_properties_v2' ) ) {

  function pera_ajax_filter_properties_v2() {

    try {

        // -----------------------------
        // 1) Inputs (sanitised)
        // -----------------------------
        $paged = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
        
        // Beds: numeric scalar (radio). 0 = no filter.
        $v2_beds = 0;
        if ( isset( $_POST['v2_beds'] ) ) {
          $v2_beds = absint( wp_unslash( $_POST['v2_beds'] ) );
        }
        
        // Taxonomy arrays (slugs)
        $district_slugs = pera_v2_filter_array_of_slugs( $_POST['district'] ?? array() );
        $tag_slugs      = pera_v2_filter_array_of_slugs( $_POST['property_tags'] ?? array() );
        
        // Property type: single slug (or empty)
        $property_type_slug = '';
        if ( isset( $_POST['property_type'] ) ) {
          $property_type_slug = sanitize_title( wp_unslash( (string) $_POST['property_type'] ) );
        }
        
        // Sort: use your v1 keys for consistency with SSR + UI
        $allowed_sorts = array( 'date_desc', 'date_asc', 'price_asc', 'price_desc' );
        $sort = 'date_desc'; // default = Newest first
        if ( isset( $_POST['sort'] ) ) {
          $maybe_sort = sanitize_key( wp_unslash( (string) $_POST['sort'] ) );
          if ( in_array( $maybe_sort, $allowed_sorts, true ) ) {
            $sort = $maybe_sort;
          }
        }
        
        // Price range: allow empty/blank hidden inputs to mean "no filter"
        $min_price = 0;
        $max_price = 0;
        
        if ( isset( $_POST['min_price'] ) ) {
          $raw = trim( (string) wp_unslash( $_POST['min_price'] ) );
          if ( $raw !== '' ) $min_price = max( 0, (float) $raw );
        }
        
        if ( isset( $_POST['max_price'] ) ) {
          $raw = trim( (string) wp_unslash( $_POST['max_price'] ) );
          if ( $raw !== '' ) $max_price = max( 0, (float) $raw );
        }
        
        // Swap if reversed (only when both provided)
        if ( $min_price > 0 && $max_price > 0 && $min_price > $max_price ) {
          $tmp       = $min_price;
          $min_price = $max_price;
          $max_price = $tmp;
        }
        
        // keyword search box
        $keyword = '';
        if ( isset( $_POST['s'] ) ) {
          $keyword = trim( (string) wp_unslash( $_POST['s'] ) );
          $keyword = sanitize_text_field( $keyword );
        }



         // -----------------------------
        // 1B) Global bounds (for clamping + response)
        // -----------------------------
        $global_min_price = 100000;   // fallback
        $global_max_price = 5000000;  // fallback
        
        if ( function_exists( 'pera_v2_get_price_bounds' ) ) {
          $bounds = pera_v2_get_price_bounds();
        
          // Defensive: bounds might not be an array or keys might be missing
          if ( is_array( $bounds ) ) {
            if ( isset( $bounds['min'] ) ) $global_min_price = (int) $bounds['min'];
            if ( isset( $bounds['max'] ) ) $global_max_price = (int) $bounds['max'];
          }
        }
        
        // Last line of defense: ensure min/max are sane
        if ( $global_min_price < 0 ) $global_min_price = 0;
        if ( $global_max_price <= 0 ) $global_max_price = 5000000;
        if ( $global_min_price > $global_max_price ) {
          $tmp = $global_min_price;
          $global_min_price = $global_max_price;
          $global_max_price = $tmp;
        }


      // Clamp only if provided; keep 0 meaning "no filter"
      if ( $min_price > 0 ) $min_price = max( (float) $global_min_price, (float) $min_price );
      if ( $max_price > 0 ) $max_price = min( (float) $global_max_price, (float) $max_price );

      // Ensure still valid if both provided
      if ( $min_price > 0 && $max_price > 0 && $min_price > $max_price ) {
        $min_price = $max_price;
      }

      // For UI: if not provided, default applied to globals (prevents $0 display issues)
      $applied_min = $min_price > 0 ? (int) $min_price : $global_min_price;
      $applied_max = $max_price > 0 ? (int) $max_price : $global_max_price;

      // -----------------------------
      // 2) Base query args (paged)
      // -----------------------------
      $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $paged,
      );

     

      // tax_query
      $tax_query = array( 'relation' => 'AND' );

      if ( ! empty( $district_slugs ) ) {
        $tax_query[] = array(
          'taxonomy' => 'district',
          'field'    => 'slug',
          'terms'    => $district_slugs,
        );
      }

      if ( ! empty( $tag_slugs ) ) {
        $tax_query[] = array(
          'taxonomy' => 'property_tags',
          'field'    => 'slug',
          'terms'    => $tag_slugs,
        );
      }

      if ( $property_type_slug !== '' ) {
        $tax_query[] = array(
          'taxonomy' => 'property_type',
          'field'    => 'slug',
          'terms'    => array( $property_type_slug ),
        );
      }

      if ( count( $tax_query ) > 1 ) {
        $args['tax_query'] = $tax_query;
      }

      // meta_query (V2 bedrooms + price range)
      $meta_query = array();

      // Bedrooms (fast LIKE on v2_index_flat)
      if ( $v2_beds > 0 ) {
        $meta_query[] = array(
          'key'     => 'v2_index_flat',
          'value'   => '|' . $v2_beds . '|',
          'compare' => 'LIKE',
        );
      }
      

      // -----------------------------
        // SORTING (V2)
        // -----------------------------
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


        // Price filter: overlap logic using v2_price_usd_min/max
        // A property matches if:
        // - v2_price_usd_max >= min (when min provided)
        // - v2_price_usd_min <= max (when max provided)
        if ( $min_price > 0 || $max_price > 0 ) {
        
          if ( $min_price > 0 ) {
            $meta_query[] = array(
              'key'     => 'v2_price_usd_max',
              'value'   => (int) $min_price,
              'type'    => 'NUMERIC',
              'compare' => '>=',
            );
          }
        
          if ( $max_price > 0 ) {
            $meta_query[] = array(
              'key'     => 'v2_price_usd_min',
              'value'   => (int) $max_price,
              'type'    => 'NUMERIC',
              'compare' => '<=',
            );
          }
        }


      if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_query );
      }
      
      if ( $keyword !== '' ) {
          $args['s'] = $keyword;
        }

      // -----------------------------
      // 3) Facet query (unpaged) to compute counts
      // -----------------------------
      $facet_args = $args;
      $facet_args['posts_per_page'] = -1;
      $facet_args['paged']          = 1;
      $facet_args['fields']         = 'ids';

      // For facets, ordering doesn't matter; strip meta_key/orderby just in case
      unset( $facet_args['orderby'], $facet_args['order'], $facet_args['meta_key'] );

      $facet_q  = new WP_Query( $facet_args );
      $post_ids = ! empty( $facet_q->posts ) ? array_map( 'intval', (array) $facet_q->posts ) : array();

      $district_counts      = pera_v2_add_term_counts_for_posts( $post_ids, 'district' );
      $tag_counts           = pera_v2_add_term_counts_for_posts( $post_ids, 'property_tags' );
      $property_type_counts = pera_v2_add_term_counts_for_posts( $post_ids, 'property_type' );

      // Bedroom facets from v2_index_flat
      $bedroom_counts = array();

      foreach ( $post_ids as $pid ) {
        $idx = (string) get_post_meta( $pid, 'v2_index_flat', true );
        if ( $idx === '' ) continue;

        $beds = pera_v2_parse_beds_from_index( $idx );
        foreach ( $beds as $b ) {
          if ( ! isset( $bedroom_counts[ $b ] ) ) {
            $bedroom_counts[ $b ] = array( 'name' => $b, 'count' => 0 );
          }
          $bedroom_counts[ $b ]['count']++;
        }
      }

      if ( ! empty( $bedroom_counts ) ) {
        uksort( $bedroom_counts, function( $a, $b ) {
          return (int) $a <=> (int) $b;
        } );
      }

      // -----------------------------
      // 4) Grid query + render cards
      // -----------------------------
      $q = new WP_Query( $args );

      ob_start();

      if ( $q->have_posts() ) {
        while ( $q->have_posts() ) {
          $q->the_post();

          set_query_var( 'pera_property_card_args', array(
            'variant'      => 'archive',
            'v2_beds'      => (int) $v2_beds,
            'show_badges'  => true,
            'show_admin'   => true,
            'show_excerpt' => true,
          ) );

          // Your V2 card template
          get_template_part( 'parts/property-card-v2' );

          set_query_var( 'pera_property_card_args', array() );
        }
      } else {
        echo '<p class="no-results">No properties found.</p>';
      }

      $grid_html = ob_get_clean();
      wp_reset_postdata();

      $found = (int) $q->found_posts;
        
        // -----------------------------
        // 4B) Pagination HTML (so UI updates after AJAX)
        // -----------------------------
        $add_args = array();
        
        if ( ! empty( $district_slugs ) ) {
          $add_args['district'] = $district_slugs;
        }
        if ( ! empty( $tag_slugs ) ) {
          $add_args['property_tags'] = $tag_slugs;
        }
        if ( $property_type_slug !== '' ) {
          $add_args['property_type'] = $property_type_slug;
        }
        if ( $v2_beds > 0 ) {
          $add_args['v2_beds'] = (string) $v2_beds;
        }
        if ( $min_price > 0 ) {
          $add_args['min_price'] = (int) $min_price;
        }
        if ( $max_price > 0 ) {
          $add_args['max_price'] = (int) $max_price;
        }
        if ( $keyword !== '' ) {
          $add_args['s'] = $keyword;
        }
        if ( ! empty( $sort ) && $sort !== 'date_desc' ) {
          $add_args['sort'] = $sort;
        }
        
        
        // Base path should come from the page itself (not referrer)
        $base_path = '';
        
        if ( isset($_POST['archive_base']) ) {
          $base_path = (string) wp_unslash($_POST['archive_base']);
          $base_path = wp_parse_url($base_path, PHP_URL_PATH) ?: $base_path; // allow full URL or path
        }
        
        // Fallback to referrer only if needed
        if ( ! $base_path ) {
          $ref = wp_get_referer();
          if ( $ref ) {
            $base_path = wp_parse_url($ref, PHP_URL_PATH) ?: '';
          }
        }
        
        // Normalise: remove /page/N/
        $base_path = preg_replace('#/page/\d+/?$#', '/', $base_path);
        
        // Final fallback: current request path (better than hardcoding)
        if ( ! $base_path ) {
          $base_path = wp_parse_url( home_url( add_query_arg(array(), $_SERVER['REQUEST_URI']) ), PHP_URL_PATH ) ?: '/';
          $base_path = preg_replace('#/page/\d+/?$#', '/', $base_path);
        }
        
        // Build base URL
        $pagination_base = home_url( trailingslashit( ltrim( $base_path, '/' ) ) );

        
        $pagination_html = paginate_links( array(
          'total'     => (int) $q->max_num_pages,
          'current'   => (int) $paged,
          'mid_size'  => 1,
          'end_size'  => 1,
          'prev_text' => 'Prev',
          'next_text' => 'Next',
          'type'      => 'list',
          'base'      => $pagination_base . '%_%',
          'format'    => 'page/%#%/',
          'add_args'  => $add_args,
        ) );


      wp_send_json_success( array(
        'grid_html'            => $grid_html,
        'count_text'           => $found . ' properties found',
        'has_more'             => ( $paged < (int) $q->max_num_pages ),
        'next_page' => ( $paged < (int) $q->max_num_pages ) ? $paged + 1 : null,

        'district_counts'      => $district_counts,
        'bedroom_counts'       => $bedroom_counts,
        'tag_counts'           => $tag_counts,
        'property_type_counts' => $property_type_counts,
        'pagination_html' => $pagination_html ? $pagination_html : '',
        'max_pages'       => (int) $q->max_num_pages,
        'current_page'    => (int) $paged,


        // Slider bounds + applied values (single source of truth)
          'price_bounds' => array(
          'global_min'  => $global_min_price,
          'global_max'  => $global_max_price,
          'applied_min' => $applied_min,
          'applied_max' => $applied_max,
        ),
      ) );

    } catch ( Throwable $e ) {
      wp_send_json_error(
        array(
          'message' => 'Server error: ' . $e->getMessage(),
        ),
        500
      );
    }
  }
}

add_action( 'wp_ajax_pera_filter_properties_v2', 'pera_ajax_filter_properties_v2' );
add_action( 'wp_ajax_nopriv_pera_filter_properties_v2', 'pera_ajax_filter_properties_v2' );
