<?php
if ( ! defined('ABSPATH') ) exit;

/* -------------------------------------------------------
   1) ADMIN COLUMN: Beds (No.)
------------------------------------------------------- */
add_filter( 'manage_property_posts_columns', function( $columns ) {

  $new = [];

  foreach ( $columns as $key => $label ) {
    $new[ $key ] = $label;

    if ( 'title' === $key ) {
      $new['pera_beds_num'] = 'Beds (No.)';
    }
  }

  return $new;

}, 20 );

add_action( 'manage_property_posts_custom_column', function( $column, $post_id ) {

  if ( 'pera_beds_num' !== $column ) return;

  // 1) Try stored numeric meta
  $v = get_post_meta( $post_id, '_pera_beds_num', true );

  // 2) Lazy-derive from Bedrooms taxonomy if missing
  if ( $v === '' ) {
    $terms = get_the_terms( $post_id, 'bedrooms' );
    $beds_num = 0;

    if ( ! empty($terms) && ! is_wp_error($terms) ) {
      $label = (string) $terms[0]->name;
      if ( preg_match('/\d+/', $label, $m ) ) {
        $beds_num = (int) $m[0];
      }
    }

    update_post_meta( $post_id, '_pera_beds_num', $beds_num );
    $v = $beds_num;
  }

  echo esc_html( (int) $v );

}, 10, 2 );

/* -------------------------------------------------------
   2) MAKE Beds (No.) SORTABLE (numeric)
------------------------------------------------------- */
add_filter( 'manage_edit-property_sortable_columns', function( $columns ) {
  $columns['pera_beds_num'] = 'pera_beds_num';
  return $columns;
}, 10 );

add_action( 'pre_get_posts', function ( $query ) {

  if ( ! is_admin() || ! $query->is_main_query() ) return;
  if ( $query->get( 'post_type' ) !== 'property' ) return;

  // Bedrooms taxonomy filter
  if ( isset($_GET['bedrooms']) && $_GET['bedrooms'] !== '' && $_GET['bedrooms'] !== '0' ) {
    $query->set( 'tax_query', [
      [
        'taxonomy' => 'bedrooms',
        'field'    => 'term_id',
        'terms'    => (int) $_GET['bedrooms'],
      ]
    ] );
  }

  // Numeric sort
  if ( $query->get('orderby') === 'pera_beds_num' ) {
    $query->set( 'meta_key', '_pera_beds_num' );
    $query->set( 'orderby', 'meta_value_num' );
  }

}, 10 );

/* -------------------------------------------------------
   3) FILTER DROPDOWN: Bedrooms taxonomy
------------------------------------------------------- */
add_action( 'restrict_manage_posts', function () {

  global $typenow;
  if ( $typenow !== 'property' ) return;

  $taxonomy = 'bedrooms';
  $selected = isset($_GET[$taxonomy]) ? (int) $_GET[$taxonomy] : 0;

  wp_dropdown_categories([
    'show_option_all' => 'All Bedrooms',
    'taxonomy'        => $taxonomy,
    'name'            => $taxonomy,
    'orderby'         => 'name',
    'selected'        => $selected,
    'hide_empty'      => false,
    'hierarchical'    => false,
    'value_field'     => 'term_id',
  ]);

}, 10 );

/* -------------------------------------------------------
   4) KEEP META IN SYNC WHEN Bedrooms TERMS CHANGE
------------------------------------------------------- */
add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ) {

  if ( $taxonomy !== 'bedrooms' ) return;
  if ( get_post_type( $object_id ) !== 'property' ) return;

  $beds_num = 0;
  $term_objs = get_the_terms( $object_id, 'bedrooms' );

  if ( ! empty($term_objs) && ! is_wp_error($term_objs) ) {
    $label = (string) $term_objs[0]->name;
    if ( preg_match('/\d+/', $label, $m ) ) {
      $beds_num = (int) $m[0];
    }
  }

  update_post_meta( $object_id, '_pera_beds_num', $beds_num );

}, 10, 4 );
