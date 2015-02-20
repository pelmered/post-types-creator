<?php
/**
 * Plugin Name: Post types creator
 * Description: 
 * Version:     0.1.0
 * Author:      Peter Elmered
 * Text Domain: post-type-creator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*
 * @package   post-type-creator
 * @author    Peter Elmered <peter@elmered.com>
 * @license   GPL-2.0+
 * @link      http://elmered.com
 * @copyright 2014 Peter Elmered
 *
 * @wordpress-plugin
 */

/*
add_action('plugins_loaded', 'Pelmered_Post_Type_Creator', 1);

function Pelmered_Post_Type_Creator()
{
    new Pelmered_Post_Type_Creator();
}
*/


/**
 * Description of bakerhansen-image-boxes
 *
 * @author peter
 */
class Pelmered_Post_Type_Creator {
    
    private $plugin_slug = 'post-type-creator';
    
    //Also edit in plugin header
    private $text_domain = 'post-type-creator'; 
    
    public $post_types = array();
    public $taxonomies = array();
    
    function get_post_types()
    {
        return array(
            

        );
    }
    function get_taxonomies()
    {
        
    }
    
    function set_post_types($post_types)
    {
        $this->post_types = $post_types;
    }
    
    function set_taxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }
    /*
    function __construct()
    {
        add_action( 'init', array($this, 'init'), 0 );
    }
    */
    
    function init()
    {
        $this->load_plugin_textdomain();

        $this->register_post_types();
        
        $this->register_taxonomies();
    }
    
    function register_post_types()
    {
        $post_types = $this->post_types;
        
        foreach($post_types AS $slug => $post_type)
        {
            $post_type['sigular_label_ucf'] = ucfirst($post_type['sigular_label']);
            $post_type['plural_label_ucf'] = ucfirst($post_type['plural_label']);
            
            $args = array(
                'label'               => __( $slug, $this->text_domain ),
                'description'         => __( $post_type['plural_label_ucf'], $this->text_domain ),
                'labels'              => array(
                    'name'                  => _x( $post_type['plural_label_ucf'], 'Post Type General Name', $this->text_domain ),
                    'singular_name'         => _x( $post_type['sigular_label_ucf'], 'Post Type Singular Name', $this->text_domain ),
                    'menu_name'             => __( $post_type['plural_label_ucf'], $this->text_domain ),
                    'parent'                => sprintf(__( 'Parent %s', $this->text_domain ), $post_type['sigular_label']),
                    //'parent_item_colon'     => sprintf(__( 'Parent %s:', $this->text_domain ), $post_type['sigular_label']),
                    'all_items'             => sprintf(__( 'All %s', $this->text_domain ), $post_type['plural_label']),
                    'view'                  => sprintf(__( 'View %s', $this->text_domain ), $post_type['sigular_label']),
                    'view_item'             => sprintf(__( 'View %s', $this->text_domain ), $post_type['sigular_label']),
                    'add_new'               => sprintf(__( 'Add %s', $this->text_domain ), $post_type['sigular_label']),
                    'add_new_item'          => sprintf(__( 'Add new %s', $this->text_domain ), $post_type['sigular_label']),
                    'edit'                  => __( 'Edit', $this->text_domain ),
                    'edit_item'             => sprintf(__( 'Edit %s', $this->text_domain ), $post_type['sigular_label']),
                    'update_item'           => sprintf(__( 'Update %s', $this->text_domain ), $post_type['sigular_label']),
                    'search_items'          => sprintf( __('Search %s', $this->text_domain), $post_type['plural_label']),
                    'not_found'             => sprintf(__( 'No %s found', $this->text_domain ), $post_type['plural_label']),
                    'not_found_in_trash'    => sprintf(__( 'No %s found in trash', $this->text_domain ), $post_type['plural_label']),
                ),
            );
            
            $defaults = array(
                'supports'            => array( 'title', 'editor', 'thumbnail', ),
                'taxonomies'          => array( ),
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'show_in_admin_bar'   => true,
                'menu_position'       => 5,
                'can_export'          => true,
                'has_archive'         => true,
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
                'capability_type'     => 'page',
            );
            
            register_post_type( $slug, wp_parse_args(array_merge($post_type, $args), $defaults) );
        }
        
    }
    
    function register_taxonomies()
    {
        $taxonomies = $this->taxonomies;
        
        foreach($taxonomies AS $slug => $taxonomy)
        {
            $taxonomy['sigular_label_ucf'] = ucfirst($taxonomy['sigular_label']);
            $taxonomy['plural_label_ucf'] = ucfirst($taxonomy['plural_label']);
            
            $args = array(
                'label'               => __( $slug, $this->text_domain ),
                'description'         => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
                'labels'              => array(
                    'name'                  => _x( $taxonomy['plural_label_ucf'], 'Taxonomy General Name', $this->text_domain ),
                    'singular_name'         => _x( $taxonomy['sigular_label_ucf'], 'Taxonomy Singular Name', $this->text_domain ),
                    'menu_name'             => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
                    'parent'                => sprintf(__( 'Parent %s', $this->text_domain ), $taxonomy['sigular_label']),
                    'parent_item'           => sprintf(__( 'Parent %s', $this->text_domain ), $taxonomy['sigular_label']),
                    'parent_item_colon'     => sprintf(__( 'Parent %s:', $this->text_domain ), $taxonomy['sigular_label']),
                    'new_item_name'         => sprintf(__( 'New %s:', $this->text_domain ), $taxonomy['sigular_label']),
                    'add_new_item'          => sprintf(__( 'Parent %s:', $this->text_domain ), $taxonomy['sigular_label']),
                    'edit'                  => __( 'Edit', $this->text_domain ),
                    'edit_item'             => sprintf(__( 'Edit %s', $this->text_domain ), $taxonomy['sigular_label']),
                    'update_item'           => sprintf(__( 'Update %s', $this->text_domain ), $taxonomy['sigular_label']),
                    //'separate_items_with_commas' => __( 'Separate items with commas', $this->text_domain ),
                    'search_items'          => sprintf( __('Search %s', $this->text_domain), $taxonomy['plural_label']),
                    'add_or_remove_items'   => __( 'Add or remove %s', $taxonomy['plural_label'] ),
                    'choose_from_most_used' => __( 'Choose from the most used items', 'woocommerce-as400' ),
                    'not_found'             => sprintf(__( 'No %s found', $this->text_domain ), $taxonomy['plural_label']),
                ),
            );
            
            $defaults = array(
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
            );
            
            register_taxonomy( $slug, $taxonomy['post_type'], wp_parse_args(array_merge($taxonomy, $args), $defaults) );
        }
        
        
    }
    

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Admin Locales are found in:
     * 		- WP_LANG_DIR/post-tpye-creator/post-tpye-creator-admin-LOCALE.mo
     * 		- WP_LANG_DIR/plugins/post-tpye-creator-admin-LOCALE.mo
     *
     * Frontend/global Locales found in:
     * 		- WP_LANG_DIR/post-tpye-creator/post-tpye-creator-LOCALE.mo
     * 	 	- woocommerce/i18n/languages/post-tpye-creator-LOCALE.mo (which if not found falls back to:)
     * 	 	- WP_LANG_DIR/plugins/post-tpye-creator-LOCALE.mo
     */
    public function load_plugin_textdomain() 
    {
        $locale = apply_filters( 'plugin_locale', get_locale(), $this->text_domain );

        load_textdomain( $this->text_domain, WP_LANG_DIR . '/' . $this->text_domain . '/-' . $this->text_domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $this->text_domain, false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
    }
    
    
}
