<?php
/**
 * Template Name: Privacy Policy (Lean)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

    <!-- =====================================================
     HERO – PRIVACY POLICY (Unified Global Hero)
     ====================================================== -->
        <section class="hero" id="privacy-hero">
        
          <div class="hero-content">
            <h1><?php the_title(); ?></h1>
        
            <?php if ( has_excerpt() ) : ?>
              <p class="lead"><?php echo get_the_excerpt(); ?></p>
            <?php else : ?>
              <p class="lead">
                How we collect, use, and protect your personal data.
              </p>
            <?php endif; ?>
          </div>
        </section>
        
        <section class="section">
          <div class="container narrow legal-content">
            <?php
              while ( have_posts() ) :
                the_post();
                the_content();
              endwhile;
            ?>
          </div>
        </section>


</main>

<?php get_footer(); ?>
