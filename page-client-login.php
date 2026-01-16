<?php
/**
 * Template Name: Client Login
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<style>
  /* Force dark theme variables on this page */
  body {
    --bg: #0e0e12;
    --bg-soft: #1a1a1e;
    --text: #e9e9e9;
    --text-soft: #c0c0c0;
    --brand: #000080;
    --accent: #3b82f6;
    --inverse: #ffffff;
  }

  /* Force dark styling for the login card, regardless of OS theme */
  .page-template-page-client-login .client-login-wrapper {
    background: var(--bg-soft);
  }

  .page-template-page-client-login .client-login-container {
    background: #020617;
    border: 1px solid rgba(148,163,184,0.3);
    box-shadow: none;
  }

  .page-template-page-client-login .client-login-title {
    color: var(--text);
  }

  .page-template-page-client-login .client-login-subtitle,
  .page-template-page-client-login .client-login-container label {
    color: var(--text-soft);
  }
</style>

<div class="client-login-wrapper">
    <main id="primary" class="site-main">
        <section class="client-login-section">
            <div class="client-login-container">
                <?php if ( is_user_logged_in() ) : ?>

                    <?php $current_user = wp_get_current_user(); ?>

                    <h1 class="client-login-title">Client Portal</h1>
                    <p class="client-login-subtitle">
                        Hello <?php echo esc_html( $current_user->display_name ); ?>, you are already logged in.
                    </p>

                    <p>
                        <a class="btn btn--solid btn--blue" href="<?php echo esc_url( home_url( '/client-portal/' ) ); ?>">
                            Go to client portal
                        </a>
                    </p>

                    <div class="client-login-links">
                        <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">
                            Log out
                        </a>
                    </div>

                <?php else : ?>

                    <h1 class="client-login-title">Client Login</h1>
                    <p class="client-login-subtitle">
                        Access your reserved project documents and reports.
                    </p>

                    <?php
                    // WP core login form, styled by .client-login-container inputs & .button-primary
                    $login_args = array(
                        'echo'           => true,
                        'redirect'       => home_url( '/client-portal/' ), // where to send after login
                        'form_id'        => 'pera-client-login-form',
                        'label_username' => __( 'Email or Username', 'pera' ),
                        'label_password' => __( 'Password', 'pera' ),
                        'label_remember' => __( 'Remember me', 'pera' ),
                        'label_log_in'   => __( 'Login', 'pera' ),
                        'id_username'    => 'user_login',
                        'id_password'    => 'user_pass',
                        'id_remember'    => 'rememberme',
                        'id_submit'      => 'wp-submit',
                        'remember'       => true,
                        'value_username' => '',
                        'value_remember' => false,
                    );

                    wp_login_form( $login_args );
                    ?>

                    <div class="client-login-links">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'client-forgot-password' ) ) ); ?>">
                          Forgot your password?
                        </a>
                    </div>

                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<?php
get_footer();
