<?php
/**
 * Template Name: Account Dashboard
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
            <h1><?php esc_html_e( 'Client Dashboard', 'pera' ); ?></h1>
            <?php $current_user = wp_get_current_user(); ?>
            <p>
                <?php
                printf(
                    esc_html__( 'Welcome back, %s.', 'pera' ),
                    esc_html( $current_user->display_name )
                );
                ?>
            </p>
        </header>

        <?php if ( ! $client_id ) : ?>
            <div class="crm-client-empty">
                <?php esc_html_e( 'Your account is not yet linked to a client profile. Please contact your advisor.', 'pera' ); ?>
            </div>
        <?php else : ?>
            <?php
            $advisor_name = get_post_meta( $client_id, 'advisor_name', true );
            if ( ! $advisor_name ) {
                $advisor_name = get_post_meta( $client_id, 'advisor', true );
            }

            $enquiry_count  = 0;
            $property_count = 0;

            if ( function_exists( 'pera_crm_get_table_name' ) ) {
                global $wpdb;

                $activity_table = pera_crm_get_table_name( 'crm_activity' );
                if ( $activity_table ) {
                    $enquiry_count = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$activity_table} WHERE client_id = %d AND event_type = %s",
                            $client_id,
                            'enquiry'
                        )
                    );
                }

                $property_table = pera_crm_get_table_name( 'crm_client_property' );
                if ( $property_table ) {
                    $property_count = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$property_table} WHERE client_id = %d AND relation_type = %s",
                            $client_id,
                            'enquiry'
                        )
                    );
                }
            }
            ?>

            <section>
                <h2><?php esc_html_e( 'Assigned advisor', 'pera' ); ?></h2>
                <p>
                    <?php
                    echo $advisor_name
                        ? esc_html( $advisor_name )
                        : esc_html__( 'Not assigned yet.', 'pera' );
                    ?>
                </p>
            </section>

            <section class="crm-client-cards">
                <div class="crm-client-card">
                    <h3><?php esc_html_e( 'Total enquiries', 'pera' ); ?></h3>
                    <span><?php echo esc_html( number_format_i18n( $enquiry_count ) ); ?></span>
                </div>
                <div class="crm-client-card">
                    <h3><?php esc_html_e( 'Linked properties', 'pera' ); ?></h3>
                    <span><?php echo esc_html( number_format_i18n( $property_count ) ); ?></span>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
