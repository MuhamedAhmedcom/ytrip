<?php if (!defined('ABSPATH')) {
  die;
} // Cannot access directly.


//
// Metabox of the PAGE
// Set a unique slug-like ID
//
$prefix_post_opts = 'prefix_post_opts';

//
// Metabox of the POST
// Set a unique slug-like ID
//




//
// Create a section
//

// get post type in admin area 
$post_type = get_post_type();

// get list of coupon_store taxonomy 

$relatedStore = '_relatedStore';
CSF::createMetabox($relatedStore, array(
  'title'          => 'Related Store',
  'post_type'      => ['post'],
  'priority'       => 'high',
  'data_type'      => 'unserialize'
));

CSF::createSection($relatedStore, array(
  'title'  => __('Related Store'),
  'icon'   => 'fas fa-question-circle',
  'fields' => array(
    array(
      'id'          => 'relatedStore',
      'type'        => 'select',
      'title'       => __('Select a store'),
      'placeholder' => __('Select an option'),
      'options'     => 'categories',
      'query_args' => array(
        'taxonomy' => 'coupon_store',
        'hide_empty' => false,
      ),
      
    ),
  ),
));

//
// Create a metabox
//
CSF::createMetabox($prefix_post_opts, array(
  'title'        => __('FAQ schema'),
  'post_type'    => ['post','page'],

  //unserialize
  //'show_restore' => true,
));

CSF::createSection($prefix_post_opts, array(
  'title'  => __('FAQ schema'),
  'icon'   => 'fas fa-question-circle',
  'fields' => array(

    array(
      'type'    => 'submessage',
      'style'   => 'warning',
      'content' => __('Add questions and answers for FAQ schema'),
    ),

    array(
      'id'        => 'AppFaq',
      'type'      => 'group',
      'button_title' =>  '<i class="fa fa-plus"> ' . __('Add new question') . '</i>',

      'accordion_title_number' => true,
      'accordion_title_auto'   => false,
      'accordion_title_prefix' =>  __('Question'),

      'fields'    => array(

        array(
          'id'    => 'question',
          'type'  => 'text',
          'title' => __('Question'),
        ),


        array(
          'id'    => 'answer',
          'type'  => 'wp_editor',
          'title' => __('Answer'),
          'tinymce'       => true,
          'quicktags'     => true,
          'media_buttons' => true,
          'height'        => '300px',
        ),



      ),

      'default'   => array(
        array(
          'question' => '',
          'answer' => '',
        ),
      ),
    ),


  ),

));



$ratingP = '_ratingP';


CSF::createMetabox($ratingP, array(
  'title'          => 'Star rating schema',
  'post_type'      => ['post','page'],
  'priority'       => 'high',
  'data_type'      => 'unserialize'
));

CSF::createSection($ratingP, array(
  'title'  => __('Star rating schema'),
  'icon'   => 'fas fa-star',
  'fields' => array(

    array(
      'id'    => __('post_ratings'),
      'type'  => 'text',
      'title' => __('Number of star rating'),
      'subtitle'  => __('Rating may be 5 star or less'),
      'desc' => '<img style="width: 150px;"src="/icons/star-rating.jpg" alt="' . __('Star rating') . '">',
      'attributes' => array(
        'id' => 'post_ratings',

      ),
    ),
    array(
      'id'    => __('post_users_rated'),
      'type'  => 'text',
      'title' => __('Users'),
      'desc'  => __('Number of users rated'),
      'attributes' => array(
        'id' => 'post_users_rated',

      ),
    ),

    array(
      'id'    => __('allPostRatings'),
      'type'  => 'text',
      'attributes' => array(
        'id' => 'allPostRatings',
        'class' => 'hide',
      ),
    ),

  ),

));





        // Use Codestar Framework to create meta box fields for ad details
        CSF::createMetabox('ad_details', array(
          'title'          => 'Ad Details',
          'post_type'      => ['ad'],
          'priority'       => 'high',
          'data_type'      => 'unserialize'
      ));

      CSF::createSection('ad_details', array(
          'title' => __('Ad Details', 'text-domain'),
          'icon'   => 'fas fa-ad',
          'fields' => array(
              array(
                  'id'    => 'ad_type',
                  'type' => 'select',
                  'title' => __('Ad Type', 'text-domain'),
                  'options' => array(
                      'html' => 'HTML',
                      'js' => 'Javascript',
                      'banner' => 'Banner with URL and Alt',
                      'shortcode' => 'WP Shortcode'
                  ),
              ),
              array(
                  'id'    => 'ad_content',
                  'type' => 'textarea',
                  'title' => __('Ad Content', 'text-domain'),
                  'dependency' => array('ad_type', 'any', 'html,js'), // Show only for HTML and JS ad types
              ),
              array(
                  'id'    => 'ad_image',
                  'type' => 'media',
                  'title' => __('Ad Image', 'text-domain'),
                  'dependency' => array('ad_type', '==', 'banner'), // Show only for Banner ad type
              ),
              array(
                  'id'    => 'ad_url',
                  'type' => 'text',
                  'title' => __('Ad URL', 'text-domain'),
                  'dependency' => array('ad_type', '==', 'banner'), // Show only for Banner ad type
              ),
              array(
                  'id'    => 'ad_alt',
                  'type' => 'text',
                  'title' => __('Ad Alt Text', 'text-domain'),
                  'dependency' => array('ad_type', '==', 'banner'), // Show only for Banner ad type
              ),
              array(
                  'id'    => 'ad_shortcode',
                  'type' => 'textarea',
                  'title' => __('WP Shortcode', 'text-domain'),
                  'dependency' => array('ad_type', '==', 'shortcode'), // Show only for WP Shortcode ad type
              ),

              array(
                'id'    => 'ad_location_meta',
                'type' => 'select',
                'title' => __('Ad Location (Meta)', 'text-domain'),
                //GET TETMS FROM ad_location TAXONOMY 
                'options' => 'categories',
                'query_args' => array(
                  'taxonomy' => 'ad_location',
                  'hide_empty' => false,
                ),

                
            ),
          ),
      ));


