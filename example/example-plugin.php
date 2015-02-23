<?php
/**
 * Plugin Name: Example plugin
 * Description: 
 * Version:     0.1.0
 * Author:      Peter Elmered
 * Text Domain: example-plugin
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt 
 */

/*
 * @package   example-plugin
 * @author    Peter Elmered <peter@elmered.com>
 * @license   GPL-2.0+
 * @link      http://elmered.com
 * @copyright 2014 Peter Elmered
 *
 * @wordpress-plugin
 */



add_action('plugins_loaded', 'Example_Post_Type_Creator');

function Example_Post_Type_Creator()
{
    //Needed for is_plugin_active() call
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    
    if(
        is_plugin_active( 'pe-example-plugin/pe-example-plugin.php' ) &&
        class_exists( 'Pelmered_Post_Type_Creator' )
    )
    {
        $text_domain = 'example-plugin';
        
        
        $ptc = new Pelmered_Post_Type_Creator();
        
        $ptc->set_post_types(array(
            'stores' => array(
                'sigular_label' => _x('butikk', 'Post type plural', $text_domain),
                'plural_label'  => _x('butikker', 'Post type sigular', $text_domain),
                'description'   => _x('', 'Post type description', $text_domain),
                
                // Override any defaults from register_post_type()
                // http://codex.wordpress.org/Function_Reference/register_post_type
                'supports'            => array( 'title', 'editor', 'thumbnail',),
                'taxonomies'          => array( 'area' ),
                
                // Make post type drag and drop sortable in admin list view
                'sortable'      => true,
                'admin_columns' => array(
                    /*
                    'slug' => array(
                        'label' => 'Column header',
                        'cb'    => 'callback for column content. Arguments: $post_id'
                    )
                     */
                    'featured_image' => array(
                        'label'     => 'Image',
                        'location'  => 2,
                        // Callback for outputting content. gets post ID as argument
                        'cb'        => 'example_get_featured_image_column'
                    )
                )
            ),
            'employees' => array(
                'sigular_label' => _x('employee', 'Post type plural', $text_domain),
                'plural_label'  => _x('employees', 'Post type sigular', $text_domain),
                'description'   => _x('', 'Post type description', $text_domain),
                
                // Override any defaults from register_post_type()
                'supports'            => array( 'title', 'thumbnail' ),
                'taxonomies'          => array( 'image_box_type' ),
            )
        ));
        
        $ptc->set_taxonomies(array(
            'area' => array(
                'sigular_label' => _x('area', 'Post type plural', $text_domain),
                'plural_label'  => _x('areas', 'Post type sigular', $text_domain),
                'description'   => _x('', 'Post type description', $text_domain),
                'post_type'    => 'stores',
                
                
                // Override any defaults from register_taxonomy()
                // http://codex.wordpress.org/Function_Reference/register_taxonomy
                
                
            ),
            'business_unit' => array(
                'sigular_label' => _x('Business unit', 'Post type plural', $text_domain),
                'plural_label'  => _x('Business units', 'Post type sigular', $text_domain),
                'description'   => _x('Business unit for categorizing the employees', 'Post type description', $text_domain),
                'post_type'    => 'employees'
            )
        ));
        
        add_action( 'init', array($ptc, 'init'), 0 );
    }
}


function example_get_featured_image_column( $post_id )
{
    echo get_the_post_thumbnail( $post_id, 'thumbnail' );
}