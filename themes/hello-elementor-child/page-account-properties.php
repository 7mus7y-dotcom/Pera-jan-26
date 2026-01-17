<?php
/**
 * Template Name: Account Properties
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$client_id = function_exists( 'pera_get_current_crm_client_id' )
    ? pera_get_current_crm_client_id()
    : 0;

get_header();
?>

<div class="crm-client-area">
    <main class="crm-client-wrapper" id="primary">
        <header class="crm-client-header">
            <h1><?php esc_html_e( 'Your Properties', 'pera' ); ?></h1>
            <p><?php esc_html_e( 'Properties linked to your enquiries.', 'pera' ); ?></p>
        </header>

        <?php if ( ! $client_id ) : ?>
            <div class="crm-client-empty">
                <?php esc_html_e( 'Your account is not yet linked to a client profile. Please contact your advisor.', 'pera' ); ?>
            </div>
        <?php else : ?>
            <?php
            $rows = array();

            if ( function_exists( 'pera_crm_get_table_name' ) ) {
                global $wpdb;
                $property_table = pera_crm_get_table_name( 'crm_client_property' );

                if ( $property_table ) {
                    $rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT property_id, created_at FROM {$property_table} WHERE client_id = %d AND relation_type = %s ORDER BY created_at DESC",
                            $client_id,
                            'enquiry'
                        )
                    );
                }
            }
            ?>

            <?php if ( empty( $rows ) ) : ?>
                <div class="crm-client-empty">
                    <?php esc_html_e( 'No properties linked yet.', 'pera' ); ?>
                </div>
            <?php else : ?>
                <div class="crm-client-grid">
                    <?php foreach ( $rows as $row ) : ?>
                        <?php
                        $property_id    = absint( $row->property_id );
                        $property_title = $property_id ? get_the_title( $property_id ) : '';
                        $property_link  = $property_id ? get_permalink( $property_id ) : '';
                        ?>
                        <div class="crm-client-property">
                            <?php if ( $property_id ) : ?>
                                <a href="<?php echo esc_url( $property_link ); ?>">
                                    <?php echo get_the_post_thumbnail( $property_id, 'medium' ); ?>
                                </a>
                            <?php endif; ?>
                            <div class="crm-client-property-body">
                                <div class="crm-client-property-title">
                                    <?php
                                    echo $property_title
                                        ? esc_html( $property_title )
                                        : esc_html__( 'Property unavailable', 'pera' );
                                    ?>
                                </div>
                                <?php if ( $property_link && $property_title ) : ?>
                                    <a class="crm-client-link" href="<?php echo esc_url( $property_link ); ?>">
                                        <?php esc_html_e( 'View property', 'pera' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
