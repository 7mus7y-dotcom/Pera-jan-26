<?php
/**
 * Partial: About Pera Property
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$about_page_url = get_permalink( get_page_by_path( 'about-us' ) );
?>

<section class="section section-soft">
    <div class="content-panel-box border-dm">

        <header class="section-header section-header--center">
            <h2>About Our Company</h2>
            <p>
                Pera Property brings together the most experienced minds of the real estate industry.
                It is a strategy which has created a large portfolio of new-build as well as unique
                property in Turkey.
            </p>
            <p>
                <em>Our impartial whole-of-market approach ensures our clients achieve the optimal end goal.</em>
            </p>
        </header>

        <div class="signoff-card width-restricter centered">
            <div class="signoff-avatar">
                <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/dkd-thumb.jpg' ); ?>" alt="D Koray Dillioglu">
            </div>
        
            <div class="signoff-text">
                <h5>D Koray Dillioglu</h5>
                <p>Director @ Pera Property</p>
            </div>
        </div>


        <div class="hero-actions flex-center">
            <a href="<?php echo esc_url( $about_page_url ); ?>" class="btn btn--solid btn--blue">
                Learn more about Pera
            </a>
        </div>

    </div>
</section>
