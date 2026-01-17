<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Get published Property counts per taxonomy term.
 * Counts ONLY post_type=property and post_status=publish.
 * Returns [ term_id => count ].
 */
function pera_archive_published_property_term_counts( string $taxonomy ): array {

  global $wpdb;

  $cache_key = 'pera_pub_property_counts_' . $taxonomy;

  $cached = wp_cache_get( $cache_key );
  if ( is_array( $cached ) ) {
    return $cached;
  }

  $sql = $wpdb->prepare("
    SELECT tt.term_id, COUNT(DISTINCT p.ID) AS cnt
    FROM {$wpdb->term_relationships} tr
    INNER JOIN {$wpdb->term_taxonomy} tt
      ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$wpdb->posts} p
      ON p.ID = tr.object_id
    WHERE tt.taxonomy = %s
      AND p.post_type = 'property'
      AND p.post_status = 'publish'
    GROUP BY tt.term_id
  ", $taxonomy );

  $rows = $wpdb->get_results( $sql );

  $counts = [];
  foreach ( (array) $rows as $row ) {
    $counts[ (int) $row->term_id ] = (int) $row->cnt;
  }

  wp_cache_set( $cache_key, $counts, '', 300 );

  return $counts;
}
