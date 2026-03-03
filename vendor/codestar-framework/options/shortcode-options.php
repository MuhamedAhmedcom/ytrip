<?php if ( ! defined( 'ABSPATH' )  ) { die; } // Cannot access directly.

//
// Set a unique slug-like ID
//
$prefix = 'prowpsite_coupon_shortcodes';

//
// Create a shortcoder
//
CSF::createShortcoder( $prefix, array(
   'button_title'   => 'Add Shortcode',
   'select_title'   => 'Select a shortcode',
   'insert_title'   => 'Insert Shortcode',
   'show_in_editor' => true,
   'gutenberg'      => array(
    'title'        => 'Pro Shortcodes',
   'description'  => 'Pro Shortcode Block',
     'icon'         => 'screenoptions',
     'category'     => 'widgets',
     'keywords'     => array( 'shortcode', 'pro', 'insert','coupon','store' ),
     'placeholder'  => 'Write shortcode here...',
   ),

   
) );

//
// A shortcode [foo title=""]
//
CSF::createSection( $prefix, array(
  'title'     => '[storeID] view: normal',
  'view'      => 'normal',
  'shortcode' => 'storeInfo',
  'fields'    => array(

    array(
      'id'       => 'coupon_store_info',
      'type'        => 'select',
      'multiple' => true,
      'chosen' => true,
      'title'       => __('Select Term', TRANSLATEDOMAIN),
      'placeholder' => __('Select Term', TRANSLATEDOMAIN),
      'options'     => 'categories',
      'query_args'  => array(
        'taxonomy'  => 'coupon_store',
      ),
      ),
  )
) );


