<?php
/**
 * Admin page template for ProWPSite Panel
 *
 * @package ProWPSite
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap prowpsite-panel">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (class_exists('CSF')): ?>
        <div class="prowpsite-panel-content">
            <?php
            // Initialize CSF framework settings
            CSF::createOptions('prowpsite_panel_options', array(
                'framework_title' => __('ProWPSite Panel Settings', 'prowpsite-panel'),
                'menu_type'      => false,
                'show_bar_menu'  => false,
                'theme'          => 'light',
                'ajax_save'      => true,
            ));
            
            // Create sections
            CSF::createSection('prowpsite_panel_options', array(
                'title'  => __('General Settings', 'prowpsite-panel'),
                'fields' => array(
                    array(
                        'id'      => 'enable_features',
                        'type'    => 'switcher',
                        'title'   => __('Enable Features', 'prowpsite-panel'),
                        'default' => true,
                    ),
                    // Add more fields here
                )
            ));
            ?>
        </div>
    <?php else: ?>
        <div class="notice notice-error">
            <p><?php _e('Codestar Framework is required for this plugin to work.', 'prowpsite-panel'); ?></p>
        </div>
    <?php endif; ?>
</div>