<?php if ( ! defined( 'ABSPATH' )  ) { die; } // Cannot access directly.

//
// Set a unique slug-like ID
//
$prefix = '_pro_profile_options';

//
// Create profile options
//
CSF::createProfileOptions( $prefix, array(
  'data_type' => 'unserialize'
) );

//
// Create a section
//
CSF::createSection( $prefix, array(
  'title'  => 'Custom Profile Options',
  'fields' => array(

    //
    // A text field
    //
    array(
      'id'    => 'phone_number',
      'type'  => 'text',
      'title' => 'Text',
    ),

  )
) );
