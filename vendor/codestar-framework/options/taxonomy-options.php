<?php if (!defined('ABSPATH')) {
  die;
} // Cannot access directly.

//
// Set a unique slug-like ID
//
$prefix = 'mukka_taxonomy_meta';
$prefix_city = 'mukka_taxonomy_meta_city';
$prefix_type = 'mukka_taxonomy_meta_type';



CSF::createTaxonomyOptions($prefix_type, array(
  'taxonomy' => ['type','city'],
  'data_type' => 'unserialize', // add this param.

));

//createSection to  add description, icon and image

CSF::createSection($prefix_type, array(
  'title'  => __('Type options'),
  'icon'   => 'fas fa-info-circle',
  'fields' => array(
    array(
      'id'      => 'type_image',
      'type'    => 'upload',
      'title'   => __('Image'),
      'desc' => __('Add image'),
      'preview' => true,
    ),
  )
));
