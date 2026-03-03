<?php if (!defined('ABSPATH')) {
  die;
} // Cannot access directly.


//
// Metabox of the PAGE
// Set a unique slug-like ID
//
$prefix_page_opts = OPTION;
$prefix_post_opts = OPTION;


//Create seprated metabox

$couponMeta = '_couponMeta';
$post_type = 'coupon';
$prefix_coupon_usage = 'prefix_coupon_usage';

CSF::createMetabox($couponMeta, array(
    'title'        => __('Coupon Details', TRANSLATEDOMAIN),
    'post_type'    => $post_type,
    'priority' => 'high',
    'data_type' => 'unserialize',
    //'show_restore' => true,
  ));
  
  //
  // Create a section
  //
  
  CSF::createSection($couponMeta, array(
    'title'     => __('Coupon Details', TRANSLATEDOMAIN),
    'post_type' => $post_type,
    'fields'    => array(

        /*
        array(
            'title' => __('Coupon Type', TRANSLATEDOMAIN),
            'desc' => __('Select the type of coupon', TRANSLATEDOMAIN),
            'id'   => '_pro_coupon_type',
            'type' => 'select',
            'options' => array(
                'code'    => __('Code', TRANSLATEDOMAIN),
                'sale'    => __('Sale', TRANSLATEDOMAIN),
                'print'   => __('Printable', TRANSLATEDOMAIN),
            ),
        ),
        */
        //coupon type is coupon or offer by button_set 
        array(
          'title' => __('Coupon Type', TRANSLATEDOMAIN),
          'desc' => __('Select the type of coupon', TRANSLATEDOMAIN),
          'id'   => '_coupon_type',
          'type' => 'button_set',
          'options' => array(
            'coupon'    => __('Coupon', TRANSLATEDOMAIN),
            'offer'    => __('Offer', TRANSLATEDOMAIN),
            ),
            'default' => 'coupon',
            ),              
        array(
            'title' => __('Coupon Code', TRANSLATEDOMAIN),
            'desc' => __('Enter the coupon code', TRANSLATEDOMAIN),
            'id'   => '_pro_coupon_code',
            'type' => 'text',
            'attributes' => array(
                'placeholder' => 'Enter the coupon code',
            ),
            'dependency' => array('_coupon_type', '==', 'coupon'),

        ),
        
        array(
            'title' => __('Coupon Destination URL', TRANSLATEDOMAIN),
            'desc' => __('Enter the coupon destination URL', TRANSLATEDOMAIN),
            'id'   => '_pro_destination_url',
            'type' => 'text',
            'attributes' => array(
                'placeholder' => 'https://',
            ),
            'sanitize' => 'url',

        ),
        array(
            'id'    => '_pro_coupon_short_description',
            'type'  => 'wp_editor',
            'tinymce'       => true,
            'quicktags'     => true,
            'media_buttons' => true,
            'height'        => '300px',
            'title' => __('Short Description', TRANSLATEDOMAIN),
          ),
          array(
            'id'    => '_pro_coupon_more_description',
            'type'  => 'wp_editor',
            'title' => __('More Description', TRANSLATEDOMAIN),
            'tinymce'       => true,
            'quicktags'     => true,
            'media_buttons' => true,
            'height'        => '300px',
          ),
          // expire date 
          array(
            'title' => __('Coupon Expire Date', TRANSLATEDOMAIN),
            'desc' => __('Enter the coupon expire date', TRANSLATEDOMAIN),
            'id'   => '_pro_coupon_expire_date',
            'type' => 'date',
            'settings' => array(
              'dateFormat'      => 'dd/mm/yy',
              'changeMonth'     => true,
              'changeYear'      => true,
              'showWeek'        => true,
              'showButtonPanel' => true,
              'weekHeader'      => 'Week',
              'monthNamesShort' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ),
              'dayNamesMin'     => array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
            ),
            'attributes' => array(
              'placeholder' => 'Enter the coupon expire date',
              ),
              'sanitize' => 'date',
              ),
              

        /*
        array(
            'title' => __('Free Shipping Coupon', TRANSLATEDOMAIN),
            'desc' => __('Check this box if the coupon offers free shipping', TRANSLATEDOMAIN),
            'id'   => '_pro_free_shipping',
            'type' => 'checkbox',
        ),
        array(
            'title' => __('Exclusive Coupon', TRANSLATEDOMAIN),
            'desc' => __('Check this box if the coupon is exclusive', TRANSLATEDOMAIN),
            'id'   => '_pro_exclusive',
            'type' => 'checkbox',
        ),
        array(
            'title' => __('Hide Excerpt', TRANSLATEDOMAIN),
            'desc' => __('Select "Yes" to hide the excerpt', TRANSLATEDOMAIN),
            'id'   => '_pro_hide_excerpt',
            'type' => 'select',
            'options' => array(
                'yes' => 'Yes',
                'no'  => 'No',
            ),
        ),
        */

        
    ),
  ));


  //
// Coupon usage
//

CSF::createMetabox($prefix_coupon_usage, array(
    'title'        => __('Coupon usage', TRANSLATEDOMAIN),
    'post_type'    => $post_type,
    'priority' => 'high',
    'data_type' => 'unserialize',
    //'show_restore' => true,
  ));

  
  CSF::createSection($prefix_coupon_usage, array(
    'title'  => __('Coupon usage', TRANSLATEDOMAIN),
    'icon'   => 'fas fa-info-circle',
    'fields' => array(
      array(
        'id' => 'coupon_usage',
        'type' => 'text',
        'title' => __('Coupon usage', TRANSLATEDOMAIN),
      ),
    ),
  ));