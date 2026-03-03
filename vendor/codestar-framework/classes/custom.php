<?php
namespace MuhamedAhmed;

if (!defined('ABSPATH')) exit;

class LawyerShortcode {
    
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
            add_shortcode('lawyer_profile', [self::$instance, 'render']);
        }
        return self::$instance;
    }
    
    public function render($atts) {
        $atts = shortcode_atts([
            'post_id' => null,
            'theme' => 'beige',
        ], $atts);
        
        $post_id = $this->getPostId($atts['post_id']);
        
        if (!$post_id) {
            return '<div style="padding:30px;background:#fee;border:2px solid #c33;border-radius:8px;text-align:center;color:#c33;font-weight:bold;">⚠️ خطأ: لم يتم العثور على المنشور</div>';
        }
        
        $data = $this->collectData($post_id);
        
        return $this->buildProfile($data, $atts['theme']);
    }
    
    private function getPostId($provided_id) {
        if ($provided_id) {
            return intval($provided_id);
        }
        
        global $wp_query;
        
        if (!empty($wp_query->queried_object_id) && get_post_type($wp_query->queried_object_id) === 'lawyer') {
            return $wp_query->queried_object_id;
        }
        
        if (!empty($wp_query->post->ID) && get_post_type($wp_query->post->ID) === 'lawyer') {
            return $wp_query->post->ID;
        }
        
        $post_id = get_queried_object_id();
        if (get_post_type($post_id) === 'lawyer') {
            return $post_id;
        }
        
        return null;
    }
    
    private function collectData($post_id) {
        return [
            'id' => $post_id,
            'name' => get_the_title($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'large'),
            'city' => Meta::getTerms($post_id, 'city'),
            'phone' => Meta::field('phone', $post_id),
            'whatsapp' => Meta::field('whatsapp', $post_id),
            'email' => Meta::field('email', $post_id),
            'website' => Meta::field('website', $post_id),
            'office_address' => Meta::field('office_address', $post_id),
            'lawyer_bio' => Meta::field('lawyer_bio', $post_id),
            'legal_specialist' => Meta::field('legal_specialist', $post_id),
            'academic_qualifications' => Meta::field('academic_qualifications', $post_id),
            'practical_experience' => Meta::field('practical_experience', $post_id),
            'professional_memberships' => Meta::field('professional_memberships', $post_id),
            'social_accounts' => Meta::field('social_accounts', $post_id, []),
        ];
    }
    
    private function buildProfile($data, $theme) {
        ob_start();
        ?>
        <style><?php echo $this->getFlexCSS($theme); ?></style>
        
        <div class="lp-wrapper lp-theme-<?php echo esc_attr($theme); ?>">
            
            <?php // Hero ?>
            <div class="lp-hero">
                <div class="lp-hero-bg"></div>
                <div class="lp-hero-flex">
                    <?php if ($data['image']): ?>
                    <div class="lp-avatar">
                        <img src="<?php echo esc_url($data['image']); ?>" alt="<?php echo esc_attr($data['name']); ?>">
                        <div class="lp-badge-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                            </svg>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="lp-info">
                        <span class="lp-label">محامي ومستشار قانوني</span>
                        <h1><?php echo esc_html($data['name']); ?></h1>
                        <?php if ($data['city']): ?>
                        <div class="lp-city">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <span><?php echo esc_html($data['city']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php // Quick Actions ?>
            <?php if ($data['phone'] || $data['whatsapp'] || $data['email']): ?>
            <div class="lp-actions-flex">
                <?php if ($data['phone']): ?>
                <a href="tel:<?php echo esc_attr($data['phone']); ?>" class="lp-action-item lp-phone">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                    </svg>
                    <span>اتصل</span>
                </a>
                <?php endif; ?>
                
                <?php if ($data['whatsapp']): ?>
                <?php $wa = preg_replace('/[^0-9]/', '', $data['whatsapp']); ?>
                <a href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" class="lp-action-item lp-whatsapp">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    <span>واتساب</span>
                </a>
                <?php endif; ?>
                
                <?php if ($data['email']): ?>
                <a href="mailto:<?php echo esc_attr($data['email']); ?>" class="lp-action-item lp-email">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <span>راسلنا</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php // Main Content ?>
            <div class="lp-content-flex">
                
                <?php // Main Column ?>
                <div class="lp-main">
                    
                    <?php if ($data['lawyer_bio']): ?>
                    <section class="lp-section">
                        <div class="lp-sec-head">
                            <div class="lp-icon"><i class="fas fa-user-tie"></i></div>
                            <h2>من نحن</h2>
                        </div>
                        <div class="lp-sec-body"><?php echo wpautop($data['lawyer_bio']); ?></div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if ($data['legal_specialist']): ?>
                    <section class="lp-section">
                        <div class="lp-sec-head">
                            <div class="lp-icon"><i class="fas fa-balance-scale"></i></div>
                            <h2>التخصصات القانونية</h2>
                        </div>
                        <div class="lp-sec-body"><?php echo wpautop($data['legal_specialist']); ?></div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if ($data['practical_experience']): ?>
                    <section class="lp-section">
                        <div class="lp-sec-head">
                            <div class="lp-icon"><i class="fas fa-briefcase"></i></div>
                            <h2>الخبرات العملية</h2>
                        </div>
                        <div class="lp-sec-body"><?php echo wpautop($data['practical_experience']); ?></div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if ($data['academic_qualifications']): ?>
                    <section class="lp-section">
                        <div class="lp-sec-head">
                            <div class="lp-icon"><i class="fas fa-graduation-cap"></i></div>
                            <h2>المؤهلات العلمية</h2>
                        </div>
                        <div class="lp-sec-body"><?php echo wpautop($data['academic_qualifications']); ?></div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if ($data['professional_memberships']): ?>
                    <section class="lp-section">
                        <div class="lp-sec-head">
                            <div class="lp-icon"><i class="fas fa-certificate"></i></div>
                            <h2>العضويات المهنية</h2>
                        </div>
                        <div class="lp-sec-body"><?php echo wpautop($data['professional_memberships']); ?></div>
                    </section>
                    <?php endif; ?>
                    
                </div>
                
                <?php // Sidebar ?>
                <div class="lp-sidebar">
                    
                    <?php // Contact Card ?>
                    <?php if ($data['phone'] || $data['email'] || $data['whatsapp'] || $data['office_address']): ?>
                    <div class="lp-card">
                        <h3 class="lp-card-title">معلومات الاتصال</h3>
                        <div class="lp-contact-list">
                            
                            <?php if ($data['phone']): ?>
                            <div class="lp-contact-row">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <small>الهاتف</small>
                                    <a href="tel:<?php echo esc_attr($data['phone']); ?>"><?php echo esc_html($data['phone']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($data['whatsapp']): ?>
                            <div class="lp-contact-row">
                                <i class="fab fa-whatsapp"></i>
                                <div>
                                    <small>واتساب</small>
                                    <?php $wa = preg_replace('/[^0-9]/', '', $data['whatsapp']); ?>
                                    <a href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank"><?php echo esc_html($data['whatsapp']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($data['email']): ?>
                            <div class="lp-contact-row">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <small>البريد</small>
                                    <a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($data['website']): ?>
                            <div class="lp-contact-row">
                                <i class="fas fa-globe"></i>
                                <div>
                                    <small>الموقع</small>
                                    <a href="<?php echo esc_url($data['website']); ?>" target="_blank"><?php echo esc_html($data['website']); ?></a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($data['office_address']): ?>
                            <div class="lp-contact-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <small>العنوان</small>
                                    <span><?php echo esc_html($data['office_address']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php // Social Media Card ?>
                    <?php 
                    $valid_socials = array_filter($data['social_accounts'], function($acc) {
                        return !empty($acc['platform_url']);
                    });
                    ?>
                    <?php if (!empty($valid_socials)): ?>
                    <div class="lp-card">
                        <h3 class="lp-card-title">تابعنا</h3>
                        <div class="lp-social-flex">
                            <?php foreach ($valid_socials as $social): ?>
                            <?php 
                            $icon_type = $social['icon_type'] ?? 'icon';
                            $bg_color = $social['background_color'] ?? '#8b6914';
                            ?>
                            
                            <a href="<?php echo esc_url($social['platform_url']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="lp-social-btn"
                               style="--hover-bg: <?php echo esc_attr($bg_color); ?>"
                               title="<?php echo esc_attr($social['platform_name'] ?? 'رابط'); ?>">
                                
                                <?php if ($icon_type === 'icon' && !empty($social['platform_icon'])): ?>
                                    <i class="<?php echo esc_attr($social['platform_icon']); ?>"></i>
                                
                                <?php elseif ($icon_type === 'image' && !empty($social['platform_image'])): ?>
                                    <?php 
                                    $img_url = is_array($social['platform_image']) 
                                        ? $social['platform_image']['url'] 
                                        : $social['platform_image']; 
                                    ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($social['platform_name'] ?? ''); ?>" style="width:24px;height:24px;object-fit:contain;">
                                
                                <?php else: ?>
                                    <span><?php echo esc_html(mb_substr($social['platform_name'], 0, 1)); ?></span>
                                <?php endif; ?>
                                
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php // CTA Card ?>
                    <?php if ($data['phone']): ?>
                    <div class="lp-card lp-cta">
                        <div class="lp-cta-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <h3>استشارة قانونية</h3>
                        <p>تواصل معنا للحصول على استشارة متخصصة</p>
                        <a href="tel:<?php echo esc_attr($data['phone']); ?>" class="lp-cta-btn">اتصل الآن</a>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Flexbox-based Responsive CSS
     */
    private function getFlexCSS($theme) {
        $themes = [
            'beige' => ['bg' => '#f5f1e8', 'primary' => '#8b6914', 'accent' => '#daa520', 'dark' => '#3d3020', 'light' => '#faf8f3', 'card' => '#fff'],
            'dark' => ['bg' => '#1a1a1a', 'primary' => '#daa520', 'accent' => '#ffd700', 'dark' => '#0d0d0d', 'light' => '#2d2d2d', 'card' => '#252525'],
            'green' => ['bg' => '#f0f5f0', 'primary' => '#2d5016', 'accent' => '#5a8e2d', 'dark' => '#1a2e0d', 'light' => '#e8f0e8', 'card' => '#fff'],
        ];
        
        $t = $themes[$theme] ?? $themes['beige'];
        
        return "
        .lp-wrapper{background:{$t['bg']};font-family:'Tajawal','Cairo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;direction:rtl;line-height:1.7}
        .lp-wrapper *{box-sizing:border-box;margin:0;padding:0}
        
        .lp-hero{background:linear-gradient(135deg,{$t['dark']},{$t['primary']});padding:60px 20px;position:relative;overflow:hidden}
        .lp-hero-bg{position:absolute;inset:0;opacity:.08;background:repeating-linear-gradient(45deg,transparent,transparent 10px,rgba(255,255,255,.1) 10px,rgba(255,255,255,.1) 20px)}
        .lp-hero-flex{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:30px;flex-wrap:wrap;position:relative}
        .lp-avatar{position:relative;flex-shrink:0}
        .lp-avatar img{width:180px;height:180px;border-radius:50%;object-fit:cover;border:5px solid rgba(255,255,255,.2);box-shadow:0 15px 50px rgba(0,0,0,.4)}
        .lp-badge-icon{position:absolute;bottom:5px;right:5px;width:50px;height:50px;background:{$t['accent']};border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.3);border:3px solid #fff}
        .lp-badge-icon svg{width:24px;height:24px;color:#fff}
        .lp-info{flex:1;min-width:250px;text-align:center;color:#fff}
        .lp-label{display:inline-block;padding:6px 18px;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border-radius:20px;font-size:13px;font-weight:600;margin-bottom:12px}
        .lp-info h1{font-size:2.5em;font-weight:800;margin-bottom:12px;text-shadow:0 3px 15px rgba(0,0,0,.3)}
        .lp-city{display:inline-flex;align-items:center;gap:8px;font-size:1.1em;opacity:.95}
        .lp-city svg{width:18px;height:18px}
        
        .lp-actions-flex{max-width:1200px;margin:-25px auto 30px;padding:0 20px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;position:relative;z-index:10}
        .lp-action-item{flex:1 1 150px;max-width:220px;min-width:120px;padding:16px 20px;background:#fff;border-radius:12px;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:10px;transition:all .3s;box-shadow:0 8px 25px rgba(0,0,0,.12);border:2px solid transparent}
        .lp-action-item svg{width:20px;height:20px;flex-shrink:0}
        .lp-action-item span{font-weight:700;font-size:14px}
        .lp-action-item:hover{transform:translateY(-5px);box-shadow:0 12px 35px rgba(0,0,0,.2)}
        .lp-phone{color:{$t['primary']}}.lp-phone:hover{border-color:{$t['primary']};background:linear-gradient(135deg,{$t['light']},#fff)}
        .lp-whatsapp{color:#25d366}.lp-whatsapp:hover{border-color:#25d366;background:linear-gradient(135deg,#dcfce7,#fff)}
        .lp-email{color:#ea4335}.lp-email:hover{border-color:#ea4335;background:linear-gradient(135deg,#fef2f2,#fff)}
        
        .lp-content-flex{max-width:1400px;margin:0 auto;padding:0 20px 50px;display:flex;gap:30px;flex-wrap:wrap}
        .lp-main{flex:1 1 600px;min-width:0}
        .lp-sidebar{flex:0 1 350px;min-width:280px}
        
        .lp-section{background:{$t['card']};border-radius:14px;padding:30px;margin-bottom:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:all .3s;border-right:4px solid {$t['accent']}}
        .lp-section:hover{box-shadow:0 12px 35px rgba(0,0,0,.12);transform:translateY(-3px)}
        .lp-sec-head{display:flex;align-items:center;gap:15px;margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid {$t['light']}}
        .lp-icon{width:55px;height:55px;background:linear-gradient(135deg,{$t['primary']},{$t['accent']});border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;box-shadow:0 6px 18px rgba(0,0,0,.15);flex-shrink:0}
        .lp-sec-head h2{font-size:1.6em;font-weight:800;color:{$t['dark']}}
        .lp-sec-body{font-size:1.05em;line-height:1.9;color:#444}
        .lp-sec-body p{margin-bottom:15px}
        .lp-sec-body ul,.lp-sec-body ol{padding-right:25px;margin-bottom:15px}
        .lp-sec-body li{margin-bottom:10px}
        
        .lp-card{background:{$t['card']};border-radius:14px;padding:25px;margin-bottom:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);transition:all .3s}
        .lp-card:hover{box-shadow:0 12px 35px rgba(0,0,0,.12)}
        .lp-card-title{font-size:1.4em;font-weight:700;color:{$t['dark']};margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid {$t['light']}}
        
        .lp-contact-list{display:flex;flex-direction:column;gap:10px}
        .lp-contact-row{display:flex;align-items:center;gap:15px;padding:14px;background:{$t['light']};border-radius:10px;transition:all .3s;border-right:3px solid transparent}
        .lp-contact-row:hover{background:{$t['accent']};color:#fff;border-right-color:{$t['primary']};transform:translateX(-5px)}
        .lp-contact-row i{font-size:18px;width:32px;text-align:center;flex-shrink:0}
        .lp-contact-row div{flex:1;min-width:0}
        .lp-contact-row small{display:block;font-size:11px;opacity:.8;margin-bottom:3px;text-transform:uppercase;font-weight:600}
        .lp-contact-row a,.lp-contact-row span{font-weight:700;color:inherit;text-decoration:none;display:block;font-size:14px;word-break:break-word}
        
        .lp-social-flex{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
        .lp-social-btn{width:55px;height:55px;background:{$t['light']};border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:{$t['primary']};transition:all .3s;text-decoration:none;flex-shrink:0}
        .lp-social-btn:hover{background:var(--hover-bg,{$t['primary']});color:#fff;transform:translateY(-5px) scale(1.1);box-shadow:0 10px 25px rgba(0,0,0,.2)}
        .lp-social-btn i{line-height:1}
        .lp-social-btn img{border-radius:4px}
        .lp-social-btn span{font-size:18px;font-weight:700}
        
        .lp-cta{background:linear-gradient(135deg,{$t['primary']},{$t['accent']});color:#fff;text-align:center;border:none!important}
        .lp-cta-icon{width:70px;height:70px;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border-radius:50%;margin:0 auto 15px;display:flex;align-items:center;justify-content:center}
        .lp-cta-icon svg{width:35px;height:35px}
        .lp-cta h3{font-size:1.6em;font-weight:800;margin-bottom:8px}
        .lp-cta p{opacity:.95;margin-bottom:18px;font-size:1em}
        .lp-cta-btn{display:inline-block;padding:12px 32px;background:#fff;color:{$t['primary']};border-radius:50px;text-decoration:none;font-weight:800;font-size:15px;transition:all .3s;box-shadow:0 6px 20px rgba(0,0,0,.2)}
        .lp-cta-btn:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(0,0,0,.3)}
        
        @media (max-width:768px){
            .lp-hero{padding:50px 15px}
            .lp-hero-flex{flex-direction:column;text-align:center}
            .lp-avatar img{width:140px;height:140px}
            .lp-info h1{font-size:2em}
            .lp-actions-flex{flex-direction:column;padding:0 15px}
            .lp-action-item{max-width:100%;flex:1 1 auto}
            .lp-content-flex{flex-direction:column;padding:0 15px 40px}
            .lp-main,.lp-sidebar{flex:1 1 100%}
            .lp-section{padding:20px}
            .lp-sec-head{flex-wrap:wrap;justify-content:center;text-align:center}
            .lp-social-flex{justify-content:center}
        }
        
        @media (max-width:480px){
            .lp-info h1{font-size:1.6em}
            .lp-avatar img{width:120px;height:120px}
            .lp-section{padding:18px}
            .lp-card{padding:20px}
            .lp-social-btn{width:50px;height:50px;font-size:20px}
        }
        ";
    }
}

LawyerShortcode::init();
