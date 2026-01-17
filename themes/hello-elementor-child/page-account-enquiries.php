<?php
/**
 * Template Name: Account Enquiries
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
            <h1><?php esc_html_e( 'Your Enquiries', 'pera' ); ?></h1>
            <p><?php esc_html_e( 'A read-only list of your latest enquiries.', 'pera' ); ?></p>
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
                $activity_table = pera_crm_get_table_name( 'crm_activity' );

                if ( $activity_table ) {
                    $rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT created_at, payload FROM {$activity_table} WHERE client_id = %d AND event_type = %s ORDER BY created_at DESC LIMIT 50",
                            $client_id,
                            'enquiry'
                        )
                    );
                }
            }
            ?>

            <?php if ( empty( $rows ) ) : ?>
                <div class="crm-client-empty">
                    <?php esc_html_e( 'No enquiries found yet.', 'pera' ); ?>
                </div>
            <?php else : ?>
                <table class="crm-client-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'pera' ); ?></th>
                            <th><?php esc_html_e( 'Enquiry type', 'pera' ); ?></th>
                            <th><?php esc_html_e( 'Related property', 'pera' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $rows as $row ) : ?>
                            <?php
                            $payload = array();
                            if ( ! empty( $row->payload ) ) {
                                $decoded = json_decode( $row->payload, true );
                                if ( is_array( $decoded ) ) {
                                    $payload = $decoded;
                                }
                            }

                            $enquiry_type = '';
                            if ( ! empty( $payload['enquiry_type'] ) ) {
                                $enquiry_type = $payload['enquiry_type'];
                            } elseif ( ! empty( $payload['type'] ) ) {
                                $enquiry_type = $payload['type'];
                            } elseif ( ! empty( $payload['intent'] ) ) {
                                $enquiry_type = $payload['intent'];
                            }

                            $property_id = 0;
                            if ( ! empty( $payload['property_id'] ) ) {
                                $property_id = absint( $payload['property_id'] );
                            } elseif ( ! empty( $payload['propertyId'] ) ) {
                                $property_id = absint( $payload['propertyId'] );
                            } elseif ( ! empty( $payload['property'] ) ) {
                                $property_id = absint( $payload['property'] );
                            }

                            $property_title = $property_id ? get_the_title( $property_id ) : '';
                            $property_link  = $property_id ? get_permalink( $property_id ) : '';

                            if ( ! $property_title && ! empty( $payload['property_title'] ) ) {
                                $property_title = $payload['property_title'];
                            }

                            if ( ! $property_link && ! empty( $payload['property_url'] ) ) {
                                $property_link = $payload['property_url'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php
                                    echo $row->created_at
                                        ? esc_html( mysql2date( get_option( 'date_format' ), $row->created_at ) )
                                        : esc_html__( 'N/A', 'pera' );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo $enquiry_type
                                        ? esc_html( $enquiry_type )
                                        : esc_html__( 'Enquiry', 'pera' );
                                    ?>
                                </td>
                                <td>
                                    <?php if ( $property_title && $property_link ) : ?>
                                        <a class="crm-client-link" href="<?php echo esc_url( $property_link ); ?>">
                                            <?php echo esc_html( $property_title ); ?>
                                        </a>
                                    <?php elseif ( $property_title ) : ?>
                                        <?php echo esc_html( $property_title ); ?>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Not linked', 'pera' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
