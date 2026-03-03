<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

/*******************************************************************
 ***                                                               **
 ***                                                               **
 ***            List Post Types and Taxonomies Widget              **
 ***                                                               **
 ***                                                               **
 ********************************************************************/

// List Taxonomies
$postTypesAndTaxonomies = array('place');
$taxonomies = array('city', 'type', 'place-tags');

foreach ($postTypesAndTaxonomies as $postTypeOrTaxonomy) {
    $callbackFunction = 'list_taxonomy_widget_callback_' . $postTypeOrTaxonomy;

    CSF::createWidget($callbackFunction, array(
        'title'           => __('List ' . $postTypeOrTaxonomy, TRANSLATEDOMAIN),
        'classname'       => 'csf-widget-classname',
        'description'     => __('Display the ' . $postTypeOrTaxonomy, TRANSLATEDOMAIN),
        'fields'          => list_taxonomy_widget_get_widget_fields($postTypeOrTaxonomy),
        'widget_callback' => $callbackFunction,
    ));
}

function list_taxonomy_widget_get_widget_fields($postTypeOrTaxonomy)
{
    $widgetTitle = __('Title', TRANSLATEDOMAIN);
    $widgetFields = array(
        array(
            'id'      => $postTypeOrTaxonomy . '_title',
            'type'    => 'text',
            'title'   => $widgetTitle,
        ),
        // Other fields specific to the taxonomy or post type
    );

    return $widgetFields;
}

// List Social Accounts Widget
$widgetTitleSocial = __('Display social accounts', TRANSLATEDOMAIN);
$widgetClassNameSocial = 'csf-widget-display-social-accounts';
$widgetDescriptionSocial = __('Display social accounts', TRANSLATEDOMAIN);
$listSocialAccountsWidgetFields = list_social_accounts_widget_fields();

CSF::createWidget('list_social_accounts_widget_callback', array(
    'title'           => $widgetTitleSocial,
    'classname'       => $widgetClassNameSocial,
    'description'     => $widgetDescriptionSocial,
    'fields'          => $listSocialAccountsWidgetFields,
    'widget_callback' => 'list_social_accounts_widget_callback',
));

function list_social_accounts_widget_fields()
{
    $fields = array(
        array(
            'id'      => 'display_social_accounts_title',
            'type'    => 'text',
            'title'   => __('Title', TRANSLATEDOMAIN),
            'default' => __('Follow us', TRANSLATEDOMAIN),
        ),
        array(
            'id'      => 'display_social_accounts',
            'type'    => 'switcher',
            'title'   => __('Display', TRANSLATEDOMAIN),
            'default' => true,
        ),
    );

    return $fields;
}

/*******************************************************************
 ***                                                               **
 ***                                                               **
 ***          List Custom Post Type Posts Widget                   **
 ***                                                               **
 ***                                                               **
 ********************************************************************/

$postTypeTitle = __('Display Posts', TRANSLATEDOMAIN);
$postTypeClassName = 'csf-widget-list_posts';
$postTypeDescription = __('Display posts', TRANSLATEDOMAIN);
$postTypeFields = list_posts_widget_get_widget_fields();
$postTypeCallback = 'list_posts_widget_callback';

CSF::createWidget($postTypeCallback, array(
    'title'           => $postTypeTitle,
    'classname'       => $postTypeClassName,
    'description'     => $postTypeDescription,
    'fields'          => $postTypeFields,
    'widget_callback' => $postTypeCallback,
));

function list_posts_widget_get_widget_fields()
{
    $fields = array(
        array(
            'id'      => 'posts_title',
            'type'    => 'text',
            'title'   => __('Title', TRANSLATEDOMAIN),
        ),
        array(
            'id'      => 'post_type',
            'type'    => 'button_set',
            'title'   => __('Select Post Type', TRANSLATEDOMAIN),
            'options' => 'post_types',
        ),
        // Other fields specific to listing posts
    );

    return $fields;
}

function list_posts_widget_callback($args, $instance)
{
    // Custom post type listing logic
}

// Additional callback functions for custom post types or taxonomies
// list_taxonomy_widget_callback_place
// list_taxonomy_widget_callback_city
// list_taxonomy_widget_callback_type
// list_taxonomy_widget_callback_place_tags
?>
