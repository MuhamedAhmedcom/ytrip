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

/**
 * Render WhatsApp Floating Button
 */
function ytrip_render_whatsapp_footer() {
    $settings = get_option( 'ytrip_settings', array() );
    $wa_enable = isset( $settings['wa_enable'] ) ? $settings['wa_enable'] : false;
    
    if ( ! $wa_enable ) {
        return;
    }
    
    $wa_number = isset( $settings['wa_number'] ) ? $settings['wa_number'] : '';
    if ( empty( $wa_number ) ) {
        return;
    }
    
    $wa_position = isset( $settings['wa_position'] ) ? $settings['wa_position'] : 'right';
    $wa_mobile   = isset( $settings['wa_mobile_behavior'] ) ? $settings['wa_mobile_behavior'] : 'float';
    $wa_anim     = isset( $settings['wa_animation'] ) ? $settings['wa_animation'] : 'pulse';
    
    $wa_url = 'https://wa.me/' . esc_attr( ltrim( $wa_number, '+' ) );
    
    $classes = array( 'ytrip-whatsapp-btn' );
    $classes[] = 'ytrip-wa-pos-' . $wa_position;
    $classes[] = 'ytrip-wa-mob-' . $wa_mobile;
    $classes[] = 'ytrip-wa-anim-' . $wa_anim;
    
    ?>
    <a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" aria-label="Contact on WhatsApp" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
      <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" class="ytrip-whatsapp-svg">
        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.1 0-65.6-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-5.5-2.8-23.2-8.5-44.2-27.1-16.4-14.6-27.4-32.7-30.6-38.1-3.2-5.5-.3-8.5 2.5-11.2 2.5-2.4 5.5-6.5 8.3-9.7 2.8-3.3 3.7-5.5 5.5-9.2 1.9-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 13.2 5.8 23.5 9.2 31.5 11.8 13.3 4.2 25.4 3.6 35 2.2 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
      </svg>
      <?php if ( $wa_mobile === 'bar' ) : ?>
        <span class="ytrip-whatsapp-text"><?php esc_html_e( 'Chat on WhatsApp', 'ytrip' ); ?></span>
      <?php endif; ?>
    </a>

    <style>
    :root {
      --ytrip-wa-color: #25d366;
      --ytrip-wa-hover: #128c7e;
      --ytrip-wa-shadow: rgba(37, 211, 102, 0.4);
      --ytrip-wa-size: 60px;
      --ytrip-wa-icon: 32px;
    }
    
    .ytrip-whatsapp-btn {
      position: fixed;
      bottom: 20px;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      width: var(--ytrip-wa-size);
      height: var(--ytrip-wa-size);
      background: linear-gradient(135deg, var(--ytrip-wa-color) 0%, var(--ytrip-wa-hover) 100%);
      border: 2px solid #ffffff;
      border-radius: 50%;
      box-shadow: 0 4px 15px var(--ytrip-wa-shadow), 0 2px 8px rgba(0, 0, 0, 0.15);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      will-change: transform;
      text-decoration: none;
      -webkit-tap-highlight-color: transparent;
    }
    
    .ytrip-whatsapp-text {
      display: none;
    }

    .ytrip-wa-pos-right { right: 20px; }
    .ytrip-wa-pos-left { left: 20px; }

    .ytrip-whatsapp-svg {
      width: var(--ytrip-wa-icon);
      height: var(--ytrip-wa-icon);
      fill: #ffffff;
    }

    .ytrip-whatsapp-btn:hover {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 6px 25px rgba(37, 211, 102, 0.6), 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .ytrip-whatsapp-btn:active {
      transform: scale(0.95);
      transition: all 0.1s ease;
    }
    
    /* Animations */
    .ytrip-wa-anim-pulse {
      animation: ytrip-wa-pulse 2s infinite;
    }
    @keyframes ytrip-wa-pulse {
      0%, 100% { box-shadow: 0 4px 15px var(--ytrip-wa-shadow), 0 0 0 0 rgba(37, 211, 102, 0.7); }
      50% { box-shadow: 0 4px 15px var(--ytrip-wa-shadow), 0 0 0 15px rgba(37, 211, 102, 0); }
    }
    
    .ytrip-wa-anim-bounce {
      animation: ytrip-wa-bounce 2s infinite;
    }
    @keyframes ytrip-wa-bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-15px); }
      60% { transform: translateY(-7px); }
    }
    .ytrip-wa-anim-bounce:hover {
      animation: none;
    }

    @media (max-width: 768px) {
      /* Base Mobile Float Size Adjustment */
      .ytrip-wa-mob-float {
        bottom: 80px; /* Above normal mobile navigation */
        width: 55px;
        height: 55px;
      }
      .ytrip-wa-mob-float .ytrip-whatsapp-svg {
        width: 28px;
        height: 28px;
      }
      
      /* Full Width Sticky Bar Behavior on Mobile */
      .ytrip-wa-mob-bar {
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        height: 60px;
        border-radius: 0;
        border: none;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        animation: none; /* Disable animations for bar */
        gap: 10px;
      }
      .ytrip-wa-mob-bar:hover {
        transform: none;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
      }
      .ytrip-wa-mob-bar .ytrip-whatsapp-svg {
        width: 24px;
        height: 24px;
      }
      .ytrip-wa-mob-bar .ytrip-whatsapp-text {
        display: block;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
      }
    }

    @media (prefers-color-scheme: dark) {
      .ytrip-whatsapp-btn {
        border-color: rgba(255, 255, 255, 0.9);
      }
      .ytrip-wa-mob-bar {
        border-color: transparent;
      }
    }
    </style>
    <?php
}
add_action( 'wp_footer', 'ytrip_render_whatsapp_footer', 30 );
