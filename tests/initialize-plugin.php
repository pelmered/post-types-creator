<?php

$text_domain = 'my-custom-post-types';

/*
$locale = apply_filters( 'plugin_locale', get_locale(), $text_domain );
load_plugin_textdomain( $text_domain, false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
*/
$ptc = new \PE\Post_Types_Creator();

$ptc->set_post_types(array(
    'stores' => array(
        'singular_label' => _x('store', 'Post type singular', $text_domain),
        'plural_label'  => _x('stores', 'Post type plural', $text_domain),
        'description'   => _x('', 'Post type description', $text_domain),

        // Override any defaults from register_post_type()
        // http://codex.wordpress.org/Function_Reference/register_post_type
        'supports'            => array( 'title', 'editor', 'thumbnail',),
        'taxonomies'          => array( 'area' ),

        // Icon for the menu in WP-Admin
        // See all available icons here: https://developer.wordpress.org/resource/dashicons/
        // If you do now find anything suitable, you can link to your own icon (png)
        'menu_icon'           => 'dashicons-store',

        // Make post type drag and drop sortable in admin list view
        'sortable'      => false,

        //Custom post statuses
        'post_statuses' => array(
            // 'slug' => array($args_array)
            // https://codex.wordpress.org/Function_Reference/register_post_status
            'active'   => array(
                'singular_label'            => _x('Active', 'Post status singular', $text_domain),
                'plural_label'              => _x('Active', 'Post status plural', $text_domain),
                'public'                    => true,
                'show_in_admin_status_list' => true
            ),
            'completed'   => array(
                'singular_label'            => _x('Completed', 'Post status singular', $text_domain),
                'plural_label'              => _x('Completed', 'Post status plural', $text_domain),
                'public'                    => true,
                'show_in_admin_status_list' => true
            ),
        ),

        'admin_columns' => array(
            /*
            'slug' => array(
                'label' => 'Column header',
                'cb'    => 'callback for column content. Arguments: $post_id'
            )
             */
            'featured_image' => array(
                'label'     => 'Logo',
                'location'  => 2, // Position of column. 2 = second, after post title
                // Callback for outputting content. gets post ID as argument
                'cb'        => 'example_get_featured_image_column'
            )
        )
    ),
    'employees' => array(
        'singular_label' => _x('employee', 'Post type singular', $text_domain),
        'plural_label'  => _x('employees', 'Post type plural', $text_domain),
        'description'   => _x('', 'Post type description', $text_domain),

        // Override any defaults from register_post_type()
        'menu_icon'           => 'dashicons-businessman',
        'supports'            => array( 'title', 'thumbnail' ),
        'taxonomies'          => array( 'business_unit' ),
    )
));


$ptc->set_taxonomies(array(
    'area' => array(
        'singular_label'  => _x('area', 'Taxonomy name singular', $text_domain),
        'plural_label' => _x('areas', 'Taxonomy name plural', $text_domain),
        'description'   => _x('', 'Post type description', $text_domain),
        'post_type'    => 'stores',


        // Override any defaults from register_taxonomy()
        // http://codex.wordpress.org/Function_Reference/register_taxonomy
        'hierarchical' => true
    ),
    'business_unit' => array(
        'singular_label'  => _x('business unit', 'Taxonomy name singular', $text_domain),
        'plural_label' => _x('business units', 'Taxonomy name plural', $text_domain),
        'description'   => _x('Business unit for categorizing the employees', 'Post type description', $text_domain),
        'post_type'    => 'employees'
    )
));

add_action( 'init', array($ptc, 'init'), 0 );