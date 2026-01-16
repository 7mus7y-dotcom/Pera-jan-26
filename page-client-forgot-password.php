<?php
/**
 * Template Name: Client Forgot Password
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>


<style>
/* ============================================================
   Force DARK MODE only on this page (template: client-forgot-password)
   ============================================================ */

/* Set dark variables */
.page-template-page-client-forgot-password body {
    --bg: #0e0e12;
    --bg-soft: #1a1a1e;
    --text: #e9e9e9;
    --text-soft: #c0c0c0;
    --brand: #000080;
    --accent: #3b82f6;
    --inverse: #ffffff;
}

/* Page background wrapper */
.page-template-page-client-forgot-password .client-login-wrapper {
    background: var(--bg-soft);
}

/* The card container */
.page-template-page-client-forgot-password .client-login-container {
    background: #020617;
    border: 1px solid rgba(148,163,184,0.3);
    box-shadow: none;
    color: var(--text);
}

/* Title */
.page-template-page-client-forgot-password .client-login-title {
    color: var(--text);
}

/* Subtitle + labels */
.page-template-page-client-forgot-password .client-login-subtitle,
.page-template-page-client-forgot-password .client-login-container label {
    color: var(--text-soft);
}

/* Input fields */
.page-template-page-client-forgot-password .client-login-container input[type="text"] {
    background: #1a1a1e;
    border: 1px solid #2c2c31;
    color: var(--text);
}

/* Button */
.page-template-page-client-forgot-password .client-login-container .button-primary {
    background: var(--brand);
    border-color: var(--brand);
    color: #ffffff;
}

.page-template-page-client-forgot-password .client-login-container .button-primary:hover {
    background: #ffffff;
    color: var(--brand);
}
</style>


<main id="primary" class="site-main">
  <div class="client-login-wrapper">
    <section class="client-login-section">
      <div class="client-login-container">

        <h1 class="client-login-title">Reset your password</h1>
        <p class="client-login-subtitle">
          Enter your username or email address and we’ll send you a link to reset your password.
        </p>

        <?php
        // Core WP lost password form (styled via your existing .client-login CSS)
        $lostpassword_url = wp_lostpassword_url();
        ?>
        <form name="lostpasswordform"
              id="lostpasswordform"
              action="<?php echo esc_url( $lostpassword_url ); ?>"
              method="post">

          <p>
            <label for="user_login">Username or Email</label>
            <input type="text" name="user_login" id="user_login" class="input" />
          </p>

          <?php do_action( 'lostpassword_form' ); ?>

          <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit"
                   class="btn btn--solid btn--blue"
                   value="Get new password" />
          </p>
        </form>

      </div>
    </section>
  </div>
</main>

<?php
get_footer();
