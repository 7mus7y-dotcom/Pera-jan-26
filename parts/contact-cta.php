<section class="section section-soft">
    <div class="content-panel-box border-dm">
        <div class="content-panel-grid">

            <!-- LEFT COLUMN -->
            <div>
                <header class="section-header">
                    <h2>Talk to Pera about your Istanbul plans</h2>
                    <p>
                        Whether you’re buying, selling, or renting in Istanbul, our team can walk you
                        through the numbers, the legal steps, and the neighbourhoods that fit your strategy.
                    </p>
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
                        55686,             // The attachment ID
                        'pera-card',           // WP will generate srcset automatically
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

            </div><!-- media-frame -->

        </div><!-- content-panel-grid -->
    </div><!-- content-panel-box -->
</section>
