<?php
/**
 * Professional Footer Template
 *
 * @package YTrip
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

$settings = get_option( "ytrip_settings", array() );
$logo = $settings["footer_logo"] ?? "";
$description = $settings["footer_description"] ?? "";
$copyright = $settings["footer_copyright"] ?? sprintf( __( "© %s YTrip. All rights reserved.", "ytrip" ), date( "Y" ) );
$social_links = $settings["footer_social_links"] ?? array();
$show_newsletter = ! empty( $settings["footer_show_newsletter"] );
$newsletter_title = $settings["footer_newsletter_title"] ?? __( "Subscribe to Our Newsletter", "ytrip" );
?>

<footer class="ytrip-footer" role="contentinfo">
    <!-- Newsletter Section -->
    <?php if ( $show_newsletter ) : ?>
    <div class="ytrip-footer__newsletter">
        <div class="ytrip-container">
            <div class="ytrip-footer__newsletter-content">
                <div class="ytrip-footer__newsletter-text">
                    <h3><?php echo esc_html( $newsletter_title ); ?></h3>
                    <p><?php esc_html_e( "Get the latest travel deals and tips directly to your inbox.", "ytrip" ); ?></p>
                </div>
                <form class="ytrip-footer__newsletter-form" action="#" method="post">
                    <input type="email" name="ytrip_newsletter_email" placeholder="<?php esc_attr_e( "Enter your email", "ytrip" ); ?>" required>
                    <button type="submit" class="ytrip-btn ytrip-btn--primary">
                        <?php esc_html_e( "Subscribe", "ytrip" ); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Footer -->
    <div class="ytrip-footer__main">
        <div class="ytrip-container">
            <div class="ytrip-footer__grid">
                <!-- Company Info -->
                <div class="ytrip-footer__col ytrip-footer__col--about">
                    <?php if ( $logo ) : ?>
                    <div class="ytrip-footer__logo">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( "name" ) ); ?>">
                    </div>
                    <?php else : ?>
                    <div class="ytrip-footer__logo-text">
                        <?php echo esc_html( get_bloginfo( "name" ) ); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( $description ) : ?>
                    <p class="ytrip-footer__description"><?php echo esc_html( $description ); ?></p>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php if ( ! empty( $social_links ) ) : ?>
                    <div class="ytrip-footer__social">
                        <?php foreach ( $social_links as $social ) : ?>
                        <a href="<?php echo esc_url( $social["url"] ?? "#" ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $social["network"] ?? "" ); ?>">
                            <?php if ( $social["network"] === "facebook" ) : ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            <?php elseif ( $social["network"] === "twitter" ) : ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            <?php elseif ( $social["network"] === "instagram" ) : ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            <?php elseif ( $social["network"] === "youtube" ) : ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <div class="ytrip-footer__col">
                    <h4 class="ytrip-footer__title"><?php esc_html_e( "Quick Links", "ytrip" ); ?></h4>
                    <ul class="ytrip-footer__links">
                        <li><a href="<?php echo esc_url( get_post_type_archive_link( "ytrip_tour" ) ); ?>"><?php esc_html_e( "All Tours", "ytrip" ); ?></a></li>
                        <li><a href="<?php echo esc_url( get_term_link( get_terms( array( "taxonomy" => "ytrip_destination", "number" => 1 ) )[0] ?? "" ) ); ?>"><?php esc_html_e( "Destinations", "ytrip" ); ?></a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( "about" ) ) ); ?>"><?php esc_html_e( "About Us", "ytrip" ); ?></a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( "contact" ) ) ); ?>"><?php esc_html_e( "Contact", "ytrip" ); ?></a></li>
                    </ul>
                </div>

                <!-- Tour Types -->
                <div class="ytrip-footer__col">
                    <h4 class="ytrip-footer__title"><?php esc_html_e( "Tour Categories", "ytrip" ); ?></h4>
                    <ul class="ytrip-footer__links">
                        <?php
                        $categories = get_terms( array(
                            "taxonomy" => "ytrip_category",
                            "hide_empty" => true,
                            "number" => 5,
                        ) );
                        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                            foreach ( $categories as $cat ) :
                        ?>
                        <li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></li>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="ytrip-footer__col">
                    <h4 class="ytrip-footer__title"><?php esc_html_e( "Contact Us", "ytrip" ); ?></h4>
                    <ul class="ytrip-footer__contact">
                        <?php if ( ! empty( $settings["contact_phone"] ) ) : ?>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:<?php echo esc_attr( $settings["contact_phone"] ); ?>"><?php echo esc_html( $settings["contact_phone"] ); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if ( ! empty( $settings["contact_email"] ) ) : ?>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a href="mailto:<?php echo esc_attr( $settings["contact_email"] ); ?>"><?php echo esc_html( $settings["contact_email"] ); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if ( ! empty( $settings["contact_address"] ) ) : ?>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span><?php echo esc_html( $settings["contact_address"] ); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="ytrip-footer__bottom">
        <div class="ytrip-container">
            <div class="ytrip-footer__bottom-content">
                <p class="ytrip-footer__copyright"><?php echo wp_kses_post( $copyright ); ?></p>
                <nav class="ytrip-footer__bottom-nav">
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( "privacy-policy" ) ) ); ?>"><?php esc_html_e( "Privacy Policy", "ytrip" ); ?></a>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( "terms-conditions" ) ) ); ?>"><?php esc_html_e( "Terms & Conditions", "ytrip" ); ?></a>
                </nav>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.ytrip-footer {
    background: linear-gradient(135deg, #0f4c81 0%, #1a365d 100%);
    color: #fff;
    margin-top: 4rem;
}

.ytrip-footer__newsletter {
    background: rgba(255, 255, 255, 0.1);
    padding: 2.5rem 0;
    backdrop-filter: blur(10px);
}

.ytrip-footer__newsletter-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    flex-wrap: wrap;
}

.ytrip-footer__newsletter-text h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
}

.ytrip-footer__newsletter-text p {
    margin: 0;
    opacity: 0.9;
}

.ytrip-footer__newsletter-form {
    display: flex;
    gap: 0.75rem;
    flex: 1;
    max-width: 500px;
}

.ytrip-footer__newsletter-form input[type="email"] {
    flex: 1;
    padding: 0.875rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.95);
}

.ytrip-footer__newsletter-form button {
    padding: 0.875rem 2rem;
    background: var(--ytrip-accent, #f59e0b);
    color: #000;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ytrip-footer__newsletter-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.ytrip-footer__main {
    padding: 4rem 0 3rem;
}

.ytrip-footer__grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, 1fr);
    gap: 3rem;
}

.ytrip-footer__logo img {
    max-height: 60px;
    width: auto;
}

.ytrip-footer__logo-text {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.ytrip-footer__description {
    opacity: 0.9;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.ytrip-footer__social {
    display: flex;
    gap: 0.75rem;
}

.ytrip-footer__social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: #fff;
    transition: all 0.3s ease;
}

.ytrip-footer__social a:hover {
    background: var(--ytrip-accent, #f59e0b);
    color: #000;
    transform: translateY(-3px);
}

.ytrip-footer__title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 1.25rem 0;
}

.ytrip-footer__links,
.ytrip-footer__contact {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ytrip-footer__links li,
.ytrip-footer__contact li {
    margin-bottom: 0.75rem;
}

.ytrip-footer__links a,
.ytrip-footer__contact a {
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    transition: color 0.2s ease;
}

.ytrip-footer__links a:hover,
.ytrip-footer__contact a:hover {
    color: var(--ytrip-accent, #f59e0b);
}

.ytrip-footer__contact li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: rgba(255, 255, 255, 0.85);
}

.ytrip-footer__contact li svg {
    flex-shrink: 0;
    margin-top: 0.125rem;
    opacity: 0.7;
}

.ytrip-footer__bottom {
    background: rgba(0, 0, 0, 0.2);
    padding: 1.25rem 0;
}

.ytrip-footer__bottom-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.ytrip-footer__copyright {
    margin: 0;
    opacity: 0.8;
    font-size: 0.875rem;
}

.ytrip-footer__bottom-nav {
    display: flex;
    gap: 1.5rem;
}

.ytrip-footer__bottom-nav a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.ytrip-footer__bottom-nav a:hover {
    color: var(--ytrip-accent, #f59e0b);
}

/* Responsive */
@media (max-width: 992px) {
    .ytrip-footer__grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
    }
    
    .ytrip-footer__col--about {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    .ytrip-footer__newsletter-content {
        flex-direction: column;
        text-align: center;
    }
    
    .ytrip-footer__newsletter-form {
        width: 100%;
        max-width: 100%;
        flex-direction: column;
    }
    
    .ytrip-footer__grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .ytrip-footer__col--about {
        grid-column: auto;
        text-align: center;
    }
    
    .ytrip-footer__social {
        justify-content: center;
    }
    
    .ytrip-footer__bottom-content {
        flex-direction: column;
        text-align: center;
    }
}

/* Dark Mode Support */
.ytrip-dark-mode .ytrip-footer {
    background: linear-gradient(135deg, #0a2540 0%, #0d1b2a 100%);
}
</style>
