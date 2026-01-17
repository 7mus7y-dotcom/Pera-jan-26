<?php
/**
 * The header for our theme
 *
 * Displays all of the <head> section and everything up until <main>.
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32"
        href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/favicon-32x32.png' ); ?>">
  <link rel="icon" type="image/png" sizes="512x512"
        href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/favicon.png' ); ?>">
  <link rel="apple-touch-icon"
        href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/apple-touch-icon.png' ); ?>">

  <meta name="theme-color" content="#ffed00" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#000080" media="(prefers-color-scheme: dark)">
  <meta name="color-scheme" content="light dark">

  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a href="#primary" class="skip-link">Skip to content</a>

<input type="checkbox" id="nav-toggle" class="nav-toggle" hidden>

<?php
$logo_path = get_stylesheet_directory() . '/logos-icons/pera-logo.svg';
?>

<header id="site-header" class="site-header">
  <div class="container header-inner">

    <!-- LEFT: LOGO -->
    <div class="site-branding">
      <a href="<?php echo esc_url( home_url('/') ); ?>"
         class="site-logo logo-pera"
         aria-label="Pera Property">

        <?php
        if ( file_exists( $logo_path ) ) {
          echo file_get_contents( $logo_path );
        } else {
          ?>
          <img
            src="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/logo-white.svg' ); ?>"
            alt="Pera Property Logo"
            width="120"
          />
        <?php } ?>

      </a>
    </div>

    <!-- RIGHT: ICONS -->
    <div class="header-icons">

      <a href="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>"
         class="header-search-toggle"
         aria-label="Browse Istanbul properties">
        <svg class="icon" aria-hidden="true">
          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-search' ); ?>"></use>
        </svg>
      </a>

      <label for="nav-toggle"
             class="header-menu-toggle"
             aria-label="Open main menu">
        <svg class="icon" aria-hidden="true">
          <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-bars' ); ?>"></use>
        </svg>
      </label>

    </div>

  </div>
</header>

<!-- OFF-CANVAS MENU -->
<nav class="offcanvas-nav" aria-label="Main">
  <div class="offcanvas-inner">

    <div class="offcanvas-top">
      <a href="<?php echo esc_url( home_url('/') ); ?>"
         class="site-logo logo-pera"
         aria-label="Pera Property">

        <?php
        if ( file_exists( $logo_path ) ) {
          echo file_get_contents( $logo_path );
        } else {
          ?>
          <img
            src="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/logo-white.svg' ); ?>"
            alt="Pera Property Logo"
            width="250"
          />
        <?php } ?>

      </a>

      <label for="nav-toggle"
             class="offcanvas-close"
             aria-label="Close menu">&times;</label>
    </div>

    <div class="offcanvas-main">

      <div class="offcanvas-main-left">
        <?php
        wp_nav_menu( array(
          'theme_location' => 'main_menu_v1',
          'container'      => false,
          'menu_class'     => 'offcanvas-menu',
          'fallback_cb'    => false,
        ) );
        ?>
      </div>

      <aside class="offcanvas-main-right">
        <h2 class="offcanvas-director-title">Message from our Director</h2>
        <p class="offcanvas-director-text">
          Istanbul real estate is a long-term, relationship-based business.
          Our team has been advising local and international buyers since 2016.
        </p>
        <p class="offcanvas-director-text">
          If you have questions about any property or neighbourhood,
          reach us directly via WhatsApp or a quick call.
        </p>
        <p class="offcanvas-director-name">
          — D. Koray Dillioglu<br>
          Founder &amp; CEO, Pera Property
        </p>
      </aside>

    </div>

    <div class="offcanvas-contact">

      <div class="offcanvas-contact-text">
        <p>Reach our Istanbul team by phone, WhatsApp or social media.</p>
      </div>

      <div class="offcanvas-contact-social footer-social">
        <!-- keep your socials block here -->
      </div>

      <div class="offcanvas-contact-login">
        <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn--solid btn--green">
          Client login
        </a>
      </div>

    </div>

  </div>
</nav>

<div class="offcanvas-backdrop" aria-hidden="true"></div>
