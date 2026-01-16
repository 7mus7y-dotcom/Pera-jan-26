<?php
/**
 * Combined CTA + Sell/Rent form panel (single card)
 *
 * Usage:
 * get_template_part(
 *     'parts/form-sell-rent',
 *     null,
 *     array(
 *         'hero_heading'  => 'Sell your Istanbul property with confidence',
 *         'hero_intro'    => 'We combine data-driven valuation with on-the-ground experience to position your property for the right buyers.',
 *         'form_heading'  => 'Request a free appraisal',
 *         'form_intro'    => 'Share a few details and we will prepare an initial sale strategy and price guidance for your property in Istanbul.',
 *         'form_context'  => 'sell-page',
 *     )
 * );
 */

$hero_heading = $args['hero_heading'] ?? 'Talk to Pera about your Istanbul plans';
$hero_intro   = $args['hero_intro']   ?? 'Whether you’re buying, selling, or renting in Istanbul, our team can walk you through the numbers, the legal steps, and the neighbourhoods that fit your strategy.';

$form_heading = $args['form_heading'] ?? 'Request a valuation / rental appraisal';
$form_intro   = $args['form_intro']   ?? 'Share a few details and we will prepare an initial sale strategy and price guidance for your apartment or villa.';

$form_context = $args['form_context'] ?? 'general-contact';
?>

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
                        'pera-card',
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
                        <a href="https://www.peraproperty.com/contact-us/" class="btn btn-primary">
                            Book a consultation
                        </a>

                        <a href="https://wa.me/905452054356?text=Hello%20Pera%20Property%2C%20I%27d%20like%20to%20discuss%20Istanbul%20real%20estate."
                           class="btn btn-secondary">
                            Chat on WhatsApp
                        </a>
                    </div>
                </div>

            </div><!-- .media-frame -->

        </div><!-- .content-panel-grid -->


        <!-- Optional divider between CTA and form -->
        <hr class="content-panel-divider" style="margin: 32px 0 24px; border: 0; border-top: 1px solid #e5e7eb;">

        <div class="enquiry-cta enquiry-cta--panel">

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
            get_template_part(
              'parts/enquiry-form',
              null,
              array(
                'context'      => $form_context === 'sell-page' ? 'sell' : 'rent',
                'heading'      => $form_heading,
                'intro'        => $form_intro,
                'submit_label' => 'Send my details',
                'form_context' => $form_context,
              )
            );
            ?>


            
        </div><!-- .enquiry-cta -->


    </div><!-- .content-panel-box -->
</section>
