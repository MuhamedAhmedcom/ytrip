<?php
/**
 * YTrip Footer Functions
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * Render Professional Footer
 */
function ytrip_render_professional_footer() {
    $settings = get_option("ytrip_settings", array());
    $logo = $settings["footer_logo"] ?? "";
    $description = $settings["footer_description"] ?? __("Discover amazing travel experiences with YTrip. Book your next adventure today!", "ytrip");
    $copyright = $settings["footer_copyright"] ?? sprintf(__("© %s YTrip. All rights reserved.", "ytrip"), date("Y"));
    ?>
    
    <footer class="ytrip-pro-footer">
        <div class="ytrip-pro-footer__newsletter">
            <div class="ytrip-container">
                <h3><?php esc_html_e("Subscribe to Our Newsletter", "ytrip"); ?></h3>
                <p><?php esc_html_e("Get the latest travel deals and tips directly to your inbox.", "ytrip"); ?></p>
                <form class="ytrip-newsletter-form">
                    <input type="email" placeholder="<?php esc_attr_e("Enter your email", "ytrip"); ?>" required>
                    <button type="submit" class="ytrip-btn ytrip-btn--primary"><?php esc_html_e("Subscribe", "ytrip"); ?></button>
                </form>
            </div>
        </div>
        
        <div class="ytrip-pro-footer__main">
            <div class="ytrip-container">
                <div class="ytrip-pro-footer__grid">
                    <div class="ytrip-pro-footer__col">
                        <div class="ytrip-pro-footer__logo">
                            <?php echo esc_html(get_bloginfo("name")); ?>
                        </div>
                        <p><?php echo esc_html($description); ?></p>
                        <div class="ytrip-pro-footer__social">
                            <a href="#" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                            <a href="#" aria-label="Twitter"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg></a>
                            <a href="#" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                        </div>
                    </div>
                    
                    <div class="ytrip-pro-footer__col">
                        <h4><?php esc_html_e("Quick Links", "ytrip"); ?></h4>
                        <ul>
                            <li><a href="<?php echo esc_url(get_post_type_archive_link("ytrip_tour")); ?>"><?php esc_html_e("All Tours", "ytrip"); ?></a></li>
                            <li><a href="#"><?php esc_html_e("Destinations", "ytrip"); ?></a></li>
                            <li><a href="#"><?php esc_html_e("About Us", "ytrip"); ?></a></li>
                            <li><a href="#"><?php esc_html_e("Contact", "ytrip"); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="ytrip-pro-footer__col">
                        <h4><?php esc_html_e("Tour Categories", "ytrip"); ?></h4>
                        <ul>
                            <?php
                            $categories = get_terms(array("taxonomy" => "ytrip_category", "hide_empty" => true, "number" => 5));
                            if (!empty($categories) && !is_wp_error($categories)) :
                                foreach ($categories as $cat) :
                            ?>
                            <li><a href="<?php echo esc_url(get_term_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a></li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>
                    
                    <div class="ytrip-pro-footer__col">
                        <h4><?php esc_html_e("Contact Us", "ytrip"); ?></h4>
                        <ul class="ytrip-pro-footer__contact">
                            <li><a href="mailto:info@zakharioustours.de">info@zakharioustours.de</a></li>
                            <li><a href="tel:+49123456789">+49 123 456 789</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ytrip-pro-footer__bottom">
            <div class="ytrip-container">
                <p><?php echo wp_kses_post($copyright); ?></p>
            </div>
        </div>
    </footer>
    
    <style>
    .ytrip-pro-footer {
        background: linear-gradient(135deg, #0f4c81 0%, #1a365d 100%);
        color: #fff;
        margin-top: 4rem;
    }
    .ytrip-pro-footer__newsletter {
        background: rgba(255,255,255,0.1);
        padding: 2.5rem 0;
        text-align: center;
    }
    .ytrip-pro-footer__newsletter h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
    }
    .ytrip-pro-footer__newsletter p {
        margin: 0 0 1.5rem 0;
        opacity: 0.9;
    }
    .ytrip-newsletter-form {
        display: flex;
        gap: 0.75rem;
        max-width: 500px;
        margin: 0 auto;
    }
    .ytrip-newsletter-form input {
        flex: 1;
        padding: 0.875rem 1.25rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
    }
    .ytrip-newsletter-form button {
        padding: 0.875rem 2rem;
        background: var(--ytrip-accent, #f59e0b);
        color: #000;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .ytrip-pro-footer__main {
        padding: 4rem 0 3rem;
    }
    .ytrip-pro-footer__grid {
        display: grid;
        grid-template-columns: 1.5fr repeat(3, 1fr);
        gap: 3rem;
    }
    .ytrip-pro-footer__logo {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .ytrip-pro-footer__col p {
        opacity: 0.9;
        line-height: 1.7;
        margin-bottom: 1.5rem;
    }
    .ytrip-pro-footer__social {
        display: flex;
        gap: 0.75rem;
    }
    .ytrip-pro-footer__social a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        color: #fff;
        transition: all 0.3s ease;
    }
    .ytrip-pro-footer__social a:hover {
        background: var(--ytrip-accent, #f59e0b);
        color: #000;
    }
    .ytrip-pro-footer__col h4 {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0 0 1.25rem 0;
    }
    .ytrip-pro-footer__col ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .ytrip-pro-footer__col ul li {
        margin-bottom: 0.75rem;
    }
    .ytrip-pro-footer__col ul a {
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .ytrip-pro-footer__col ul a:hover {
        color: var(--ytrip-accent, #f59e0b);
    }
    .ytrip-pro-footer__bottom {
        background: rgba(0,0,0,0.2);
        padding: 1.25rem 0;
        text-align: center;
    }
    .ytrip-pro-footer__bottom p {
        margin: 0;
        opacity: 0.8;
        font-size: 0.875rem;
    }
    @media (max-width: 992px) {
        .ytrip-pro-footer__grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        .ytrip-pro-footer__col:first-child {
            grid-column: span 2;
        }
    }
    @media (max-width: 768px) {
        .ytrip-newsletter-form {
            flex-direction: column;
        }
        .ytrip-pro-footer__grid {
            grid-template-columns: 1fr;
        }
        .ytrip-pro-footer__col:first-child {
            grid-column: auto;
        }
    }
    </style>
    <?php
}

// Add to theme footer
add_action("wp_footer", "ytrip_render_professional_footer", 20);
