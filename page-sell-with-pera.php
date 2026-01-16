<?php
/**
 * Template Name: Sell with Pera
 * Description: Landing page for property owners who want to sell with Pera Property.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$hero_heading = $args['hero_heading'] ?? 'Talk to Pera about your Istanbul plans';
$hero_intro   = $args['hero_intro']   ?? 'Whether you’re buying, selling, or renting in Istanbul, our team can walk you through the numbers, the legal steps, and the neighbourhoods that fit your strategy.';

get_header();
?>

<main id="primary" class="site-main">

    <!-- =====================================
     HERO (SELL WITH PERA)
     Canonical structure + existing content
     ===================================== -->
        <section class="hero hero--left hero--sell" id="sell-hero">
        
          <div class="hero__media" aria-hidden="true">
            <?php
              // If you later set a featured image for this page, it will be used.
              $hero_img_id = get_post_thumbnail_id();
        
              if ( $hero_img_id ) {
                echo wp_get_attachment_image(
                  $hero_img_id,
                  'full',
                  false,
                  array(
                    'class'    => 'hero-media',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                  )
                );
              } else {
                // Fallback background (vopbesiktas.svg uploaded to WP)
                echo wp_get_attachment_image(
                  55756,
                  'full',
                  false,
                  array(
                    'class'    => 'hero-media',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                  )
                );
              }
            ?>
            <div class="hero-overlay" aria-hidden="true"></div>
          </div>
        
          <div class="hero-content">
            <h1>Sell your Istanbul property with confidence.</h1>
        
            <p class="lead">
              From pricing and marketing to viewings and paperwork, our team handles every step
              so you achieve the best possible result with minimum stress.
            </p>
        
            <div class="hero-actions">
              <a href="#contact" class="btn btn--solid btn--green">
                Request a free valuation
              </a>
        
              <a href="#process" class="btn btn--solid btn--blue">
                How our selling process works
              </a>
        
              <a
                href="https://wa.me/905452054356?text=Hello%20I%20would%20like%20to%20sell%20my%20property%20with%20Pera%20Property"
                target="_blank"
                rel="noopener"
                class="btn btn-icon-circle btn-whatsapp"
                aria-label="Contact Pera Property via WhatsApp"
              >
                <svg class="icon" aria-hidden="true">
                  <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-whatsapp' ); ?>"></use>
                </svg>
              </a>
            </div>
          </div>
        
        </section>


    <!-- CONTENT PANEL (overlapping hero) -->
    <section class="content-panel">
        <div class="content-panel-box">
            <div class="content-panel-grid">
                <!-- LEFT: TEXT -->
                <div>
                    <header class="section-header">
                        <h2>Why sell your property with Pera?</h2>
                        <p>
                            We are an Istanbul-focused, data-driven agency that treats every listing
                            like a bespoke investment project. Our goal is simple: secure the right
                            buyer at the right price, on the right terms.
                        </p>
                    </header>

                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Honest valuation based on real comparable data
                        </li>
                    
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Access to both local and international buyers
                        </li>
                    
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Professional presentation: photos, videos, floor plans
                        </li>
                    
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Negotiation, paperwork and follow-up handled end-to-end
                        </li>
                    </ul>


                    <div class="signoff-card">
                        <div class="signoff-avatar">
                                        <?php
                                        echo wp_get_attachment_image(
                                            55700,
                                            'full',
                                            false,
                                            array(
                                                'class'   => '',
                                                'alt'     => 'Pera Property Director',
                                                'loading' => 'lazy',
                                                'decoding'=> 'async',
                                            )
                                        );
                                        ?>
                                    </div>
                        <div class="signoff-text">
                            <h5>Your dedicated consultant</h5>
                            <p>
                                One point of contact from first valuation to key handover. Direct,
                                clear and honest communication throughout the process.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: MEDIA / VISUAL -->
                <div>
                    <div class="media-frame media-frame--image-fill">
                        <?php
                        echo wp_get_attachment_image(
                            55704,
                            'full',
                            false,
                            array(
                                'class'    => 'media-image', // IMPORTANT: this class
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                                'alt'      => esc_attr(
                                    'Istanbul real estate market overview by Pera Property'
                                ),
                            )
                        );
                        ?>
                    </div>
                </div>





            </div>
        </div>
    </section>

    <!-- WHY SELL WITH US – FEATURE GRID -->
    <section class="section">
        <div class="section-header section-header--center">
            <h2>What you gain when you sell with Pera</h2>
            <p>
                We combine Istanbul market experience, international investor reach and a
                structured selling process to protect both your price and your time.
            </p>
        </div>

        <div class="feature-grid">
            <!-- FEATURE 1 -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-card-icon">
                        <svg class="icon" aria-hidden="true">
                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-map' ); ?>"></use>
                        </svg>
                    </div>
                    <h3>Accurate pricing strategy</h3>
                </div>
                <div class="feature-card-body">
                    <p>
                        We benchmark your property against recent sales, active listings and
                        investor demand in your specific micro-location, not just the district
                        average.
                    </p>
                </div>
            </article>

            <!-- FEATURE 2 -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-card-icon">
                        <svg class="icon" aria-hidden="true">
                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-pdf' ); ?>"></use>
                        </svg>
                    </div>
                    <h3>Professional marketing</h3>
                </div>
                <div class="feature-card-body">
                    <p>
                        Clean photography, clear plans, bilingual presentation and targeted
                        campaigns ensure your property stands out instead of getting lost among
                        generic listings.
                    </p>
                </div>
            </article>

            <!-- FEATURE 3 -->
            <article class="feature-card">
                <div class="feature-card-header">
                    <div class="feature-card-icon">
                        <svg class="icon" aria-hidden="true">
                            <use href="<?php echo esc_url( get_stylesheet_directory_uri() . '/logos-icons/icons.svg#icon-map' ); ?>"></use>
                        </svg>
                    </div>
                    <h3>Serious buyers only</h3>
                </div>
                <div class="feature-card-body">
                    <p>
                        We pre-qualify buyers, manage viewing schedules and filter out “property
                        tourists”, so the people walking through your door are real prospects.
                    </p>
                </div>
            </article>
        </div>
    </section>

    <!-- OUR PROCESS – INFO STEPS -->
    <section id="process" class="section section-soft">
        <div class="section-header section-header--center">
            <h2>How the selling process works</h2>
            <p>
                A clear, structured roadmap from first chat to completed sale. You always know
                what is happening, and what comes next.
            </p>
        </div>

        <div class="info-steps">
            <!-- STEP 1 -->
            <div class="info-step">
                <div class="info-step-icon">
                    <span class="info-step-number">1</span>
                </div>
                <div class="info-step-body">
                    <h3 class="info-step-title">Initial conversation & property review</h3>
                    <p class="info-step-text">
                        We listen to your goals, review your property details and documents,
                        and advise whether a sale, rental or hold strategy makes most sense.
                    </p>
                </div>
            </div>

            <!-- STEP 2 -->
            <div class="info-step">
                <div class="info-step-icon">
                    <span class="info-step-number">2</span>
                </div>
                <div class="info-step-body">
                    <h3 class="info-step-title">Valuation & pricing strategy</h3>
                    <p class="info-step-text">
                        We prepare a realistic price range backed by comps and demand data,
                        then agree the asking price and negotiation boundaries with you.
                    </p>
                </div>
            </div>

            <!-- STEP 3 -->
            <div class="info-step">
                <div class="info-step-icon">
                    <span class="info-step-number">3</span>
                </div>
                <div class="info-step-body">
                    <h3 class="info-step-title">Marketing & viewings</h3>
                    <p class="info-step-text">
                        Your listing goes live across our channels and direct investor network.
                        We handle enquiries, schedule viewings and keep you updated with
                        feedback.
                    </p>
                </div>
            </div>

            <!-- STEP 4 -->
            <div class="info-step">
                <div class="info-step-icon">
                    <span class="info-step-number">4</span>
                </div>
                <div class="info-step-body">
                    <h3 class="info-step-title">Offer, negotiation & paperwork</h3>
                    <p class="info-step-text">
                        Once offers arrive, we negotiate terms in your favour, coordinate the
                        sales contract, legal checks and tapu process together with your chosen
                        lawyer or our partner firms.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- WHAT WE HANDLE FOR YOU – 2 COL LAYOUT -->
    <section class="section">
        <div class="container grid-2">
            <div>
                <h2>Everything taken care of, from start to finish.</h2>
                <p>
                    Selling a property in Istanbul doesn’t have to be chaotic. We project-manage
                    the entire journey so you can focus on your life, not paperwork and phone calls.
                </p>
            </div>
            <div>
                <ul class="checklist">
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Pre-sale advice on minor improvements that increase value
                  </li>
                
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Document check: tapu, plans, iskan, mortgage and encumbrances
                  </li>
                
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Professional photos and listing preparation
                  </li>
                
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Coordinating viewings with tenants or caretakers where relevant
                  </li>
                
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Negotiation strategy and best-offer analysis
                  </li>
                
                  <li>
                    <svg class="icon icon-tick" aria-hidden="true">
                      <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                    </svg>
                    Guidance on tax, fees and timelines together with your advisors
                  </li>
                </ul>

            </div>
        </div>
    </section>


    <!-- ABOUT PERA -->
    <?php get_template_part( 'parts/about-pera' ); ?>

    
    <section class="section section-soft" id="contact">
        <div class="content-panel-box">
    
            <!-- =========================
                 1) HERO CTA GRID (LEFT TEXT + RIGHT IMAGE)
                 ========================== -->
            <div class="content-panel-grid">
    
                <!-- LEFT COLUMN -->
                <div>
                    <header class="section-header">
                        <h2><?php echo esc_html( $hero_heading ); ?></h2>
                        <p><?php echo esc_html( $hero_intro ); ?></p>
                    </header>
    
                    <ul class="checklist">
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Reliable, data-driven advice.
                        </li>
    
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            On-the-ground Istanbul expertise.
                        </li>
    
                        <li>
                            <svg class="icon icon-tick" aria-hidden="true">
                                <use href="<?php echo get_stylesheet_directory_uri(); ?>/logos-icons/icons.svg#icon-check"></use>
                            </svg>
                            Multi-lingual support.
                        </li>
                    </ul>
                </div>
    
                <!-- RIGHT COLUMN -->
                <div class="media-frame">
    
                    <!-- RESPONSIVE BACKGROUND IMAGE -->
                    <div class="media-frame__bg">
                        <?php
                        echo wp_get_attachment_image(
                            55686,
                            'large',
                            false,
                            array(
                                'class'    => 'media-frame__bg-img',
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                                'alt'      => 'Isometric illustration of Beşiktaş'
                            )
                        );
                        ?>
                    </div>
    
                    <div class="hero-overlay"></div>
    
                    <div class="hero-content section--center">
                        <h3 class="text-light">Speak with a Consultant</h3>
    
                        <div class="hero-actions flex-center">
                            <a href="https://www.peraproperty.com/contact-us/" class="btn btn--solid btn--blue">
                                Book a consultation
                            </a>
    
                            <a href="https://wa.me/905452054356?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20discuss%20Istanbul%20real%20estate."
                               class="btn btn--solid btn--green">
                                Chat on WhatsApp
                            </a>
                        </div>
                    </div>
    
                </div><!-- .media-frame -->
    
            </div><!-- .content-panel-grid -->

            <div>
    
                <?php if ( isset( $_GET['sr_success'] ) && $_GET['sr_success'] === '1' ) : ?>
                    <div class="form-success">
                        Thank you – we have received your details. A Pera consultant will contact you shortly.
                    </div>
                <?php endif; ?>
    
                <div class="enquiry-cta-header">
                    <h2><?php echo esc_html( $form_heading ); ?></h2>
                    <p><?php echo esc_html( $form_intro ); ?></p>
                </div>
    
                <?php
                get_template_part('parts/enquiry-form', null, array(
                  'context'      => 'sell',
                  'heading'      => 'Request a free appraisal',
                  'intro'        => 'Share a few details and we will prepare an initial sale strategy and price guidance for your property in Istanbul.',
                  'submit_label' => 'Send my details',
                  'form_context' => 'sell-page',
                ));

                ?>
    
    
                
            </div><!-- .enquiry-cta -->
    
    
        </div><!-- .content-panel-box -->
    </section>


</main>

<?php
get_footer();
