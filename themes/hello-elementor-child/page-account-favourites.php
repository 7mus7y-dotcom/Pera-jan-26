<?php
/**
 * Template Name: Account Favourites
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

$favourites = array();

if ( $client_id && function_exists( 'peracrm_favourite_list' ) ) {
    $favourites = peracrm_favourite_list( $client_id, 200, 0 );
}

get_header();
?>

<div class="crm-client-area">
    <main class="crm-client-wrapper" id="primary">
        <header class="crm-client-header">
            <h1><?php esc_html_e( 'Saved Properties', 'pera' ); ?></h1>
            <p><?php esc_html_e( 'Properties you have saved for later.', 'pera' ); ?></p>
        </header>

        <?php if ( ! $client_id ) : ?>
            <div class="crm-client-empty">
                <?php esc_html_e( 'Your account is not yet linked to a client profile. Please contact your advisor.', 'pera' ); ?>
            </div>
        <?php elseif ( empty( $favourites ) ) : ?>
            <div class="crm-client-empty">
                <?php esc_html_e( 'No saved properties yet.', 'pera' ); ?>
            </div>
        <?php else : ?>
            <div class="crm-favourites">
                <?php foreach ( $favourites as $row ) : ?>
                    <?php
                    $property_id = isset( $row['property_id'] ) ? absint( $row['property_id'] ) : 0;
                    $property    = $property_id ? get_post( $property_id ) : null;
                    $title       = $property ? get_the_title( $property_id ) : '';
                    $link        = ( $property && 'publish' === $property->post_status ) ? get_permalink( $property_id ) : '';
                    $thumbnail   = $property ? get_the_post_thumbnail( $property_id, 'medium' ) : '';
                    ?>
                    <div class="crm-favourites__item">
                        <div class="crm-favourites__media">
                            <?php if ( $link && $thumbnail ) : ?>
                                <a href="<?php echo esc_url( $link ); ?>" class="crm-favourites__thumb">
                                    <?php echo $thumbnail; ?>
                                </a>
                            <?php elseif ( $thumbnail ) : ?>
                                <div class="crm-favourites__thumb">
                                    <?php echo $thumbnail; ?>
                                </div>
                            <?php else : ?>
                                <div class="crm-favourites__thumb crm-favourites__thumb--empty"></div>
                            <?php endif; ?>
                        </div>
                        <div class="crm-favourites__content">
                            <div class="crm-favourites__title">
                                <?php
                                echo $title
                                    ? esc_html( $title )
                                    : esc_html__( 'Property unavailable', 'pera' );
                                ?>
                            </div>
                            <?php if ( $link && $title ) : ?>
                                <a class="crm-client-link" href="<?php echo esc_url( $link ); ?>">
                                    <?php esc_html_e( 'View property', 'pera' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="crm-favourites__actions">
                            <?php if ( $property_id ) : ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <input type="hidden" name="action" value="peracrm_toggle_favourite" />
                                    <input type="hidden" name="property_id" value="<?php echo esc_attr( $property_id ); ?>" />
                                    <?php wp_nonce_field( 'peracrm_toggle_favourite' ); ?>
                                    <button type="submit" class="crm-favourites__button">
                                        <?php esc_html_e( 'Remove', 'pera' ); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
