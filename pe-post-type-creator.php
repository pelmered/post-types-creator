<?php
/**
 * Plugin Name: Post types creator
 * Description: Helper plugin for easily creating localize-ready custom post types and custom taxonomies in WordPress
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
add_action('plugins_loaded', 'PE_Post_Type_Creator', 1);

function PE_Post_Type_Creator()
{
    new PE_Post_Type_Creator();
}
*/


/**
 * Description of bakerhansen-image-boxes
 *
 * @author peter
 */
class PE_Post_Type_Creator {

    private $plugin_slug = 'post-type-creator';

    //Also edit in plugin header
    private $text_domain = 'post-type-creator';

    public $post_types = array();
    public $taxonomies = array();


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
        add_action( 'wp_ajax_pe_ptc_sort_posts', array($this, 'sortable_ajax_handler') );


        $this->load_plugin_textdomain();

        $this->register_post_types();

        $this->register_taxonomies();

        add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );

        //add sort to get_terms()
        add_filter('get_terms_orderby', array( $this, 'sort_get_terms' ), 10, 3 );
    }

    function register_post_types()
    {
        $post_types = $this->post_types;

        foreach($post_types AS $slug => $post_type)
        {
            $post_type['singular_label_ucf'] = ucfirst($post_type['singular_label']);
            $post_type['plural_label_ucf'] = ucfirst($post_type['plural_label']);

            $generated_args = array(
                'label'               => __( $slug, $this->text_domain ),
                'description'         => __( $post_type['plural_label_ucf'], $this->text_domain ),
                'labels'              => array(
                    'name'                  => _x( $post_type['plural_label_ucf'], 'Post Type General Name', $this->text_domain ),
                    'singular_name'         => _x( $post_type['singular_label_ucf'], 'Post Type Singular Name', $this->text_domain ),
                    'menu_name'             => __( $post_type['plural_label_ucf'], $this->text_domain ),
                    'parent'                => sprintf(__( 'Parent %s', $this->text_domain ), $post_type['singular_label']),
                    //'parent_item_colon'     => sprintf(__( 'Parent %s:', $this->text_domain ), $post_type['singular_label']),
                    'all_items'             => sprintf(__( 'All %s', $this->text_domain ), $post_type['plural_label']),
                    'view'                  => sprintf(__( 'View %s', $this->text_domain ), $post_type['singular_label']),
                    'view_item'             => sprintf(__( 'View %s', $this->text_domain ), $post_type['singular_label']),
                    'add_new'               => sprintf(__( 'Add %s', $this->text_domain ), $post_type['singular_label']),
                    'add_new_item'          => sprintf(__( 'Add new %s', $this->text_domain ), $post_type['singular_label']),
                    'edit'                  => __( 'Edit', $this->text_domain ),
                    'edit_item'             => sprintf(__( 'Edit %s', $this->text_domain ), $post_type['singular_label']),
                    'update_item'           => sprintf(__( 'Update %s', $this->text_domain ), $post_type['singular_label']),
                    'search_items'          => sprintf( __('Search %s', $this->text_domain), $post_type['plural_label']),
                    'not_found'             => sprintf(__( 'No %s found', $this->text_domain ), $post_type['plural_label']),
                    'not_found_in_trash'    => sprintf(__( 'No %s found in trash', $this->text_domain ), $post_type['plural_label']),
                ),
            );

            $default_args = array(
                // Override some defaults to cover most cases out of the box
                'supports'              => array( 'title', 'editor', 'thumbnail', ),
                'taxonomies'            => array( ),
                'public'                => true,
                'menu_position'         => 6,   //Below posts
                'has_archive'           => true,

                //Custom
                'admin_columns'         => array(),
                'sortable'              => false,
            );

            $final_args = wp_parse_args(array_merge( $generated_args, $post_type ), $default_args);

            register_post_type( $slug, $final_args );

            if( is_admin() )
            {
                $current_post_type = $this->get_current_post_type();

                if( isset($final_args['admin_columns']))
                {
                    foreach( $final_args['admin_columns'] AS $column )
                    {
                        add_filter( 'manage_posts_columns' , array($this, 'add_admin_column'), 10, 2 );
                        //add_filter( 'manage_'.$slug.'_posts_columns' , array($this, 'add_admin_column'), 10, 2 );

                        add_action( 'manage_'.$slug.'_posts_custom_column' , array($this, 'add_admin_column_content'), 10, 2 );
                    }
                }

                if( $final_args['sortable'] &&
                    //in_array( $current_post_type, array_keys( $this->post_types ) ) &&
                    isset($this->post_types[$current_post_type]['sortable']) &&
                    $this->post_types[$current_post_type]['sortable'] == true
                )
                {
                    /**
                     * Make post list in admin sorted without meta value
                     */
                    //add_filter('pre_get_posts', array( $this, 'sort_admin_post_list' ) );

                    wp_enqueue_script('jquery-ui-core');
                    wp_enqueue_script('jquery-ui-sortable');

                    wp_enqueue_script('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/js/sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'));
                    wp_enqueue_style('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/css/sortable.css', array());
                }

            }




        }

    }

    /**
     * Sets ORDER BY in teh get_terms() query wich should used in both admin and in themes to get terms
     *
     * @param string $orderby
     * @param type $args
     * @param type $taxonomies
     * @return string
     */
    function sort_get_terms( $orderby, $args, $taxonomies )
    {
        $taxonomy = $taxonomies[0];

        if(array_key_exists( $taxonomy, $this->taxonomies) && $this->taxonomies[$taxonomy]['sortable'] )
        {
            $order = get_option('taxonomy_order_'.$taxonomy, array());

            if( !empty($order) )
            {
                $orderby = 'FIELD(t.term_id, ' . implode(',', $order) . ')';
                return $orderby;
            }
        }

        return $orderby;
    }

    function save_post( $post_id, $post, $update )
    {
        if(in_array($post->post_type , $this->post_types) && $this->post_types[$post->post_type]['sortable'] )
        {
            update_field('sort', apply_filters('pe_ptc_sort_default', 99, $post_id, $post, $update ), $post_id);
            //update_post_meta( $post_id, 'sort', apply_filters('pe_ptc_sort_default', 99, $post_id, $post, $update ));
        }
    }

    function sort_admin_post_list( $wp_query )
    {
        $wp_query->set( 'orderby', 'meta_value_num' );
        $wp_query->set( 'meta_key', 'sort' );
        $wp_query->set( 'order', 'ASC' );

        return $wp_query;
    }

    // https://gist.github.com/mjangda/476964
    function get_current_post_type()
    {
        global $post, $typenow, $current_screen;

        if( $post && $post->post_type )
        {
            return $post->post_type;
        }
        elseif( $typenow )
        {
            return $typenow;
        }
        elseif( $current_screen && $current_screen->post_type )
        {
            return $current_screen->post_type;
        }
        elseif( isset( $_REQUEST['post_type'] ) )
        {
            return sanitize_key( $_REQUEST['post_type'] );
        }
        else
        {
            return null;
        }
    }

    function get_current_taxonomy( $post_type = '' )
    {
        if( isset( $_REQUEST['taxonomy'] ) && ( empty($post_type) || $_REQUEST['post_type'] == $post_type ) )
        {
            return sanitize_key( $_REQUEST['taxonomy'] );
        }
        else
        {
            return null;
        }
    }

    function sortable_ajax_handler()
    {
        //$post_type = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING);
        parse_str(filter_input(INPUT_POST, 'post_data', FILTER_SANITIZE_STRING), $post_data );

        $post_type = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING);
        $taxonomy = filter_input(INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING);

        $i = 0;

        // TODO
        // Sorted taxonomies not supported yes
        if( empty( $taxonomy ) )
        {
            //return;
        }

        if( isset($post_data['post']) && is_array($post_data['post']))
        {
            foreach( $post_data['post'] AS $tag_id )
            {
                update_field('sort', $i++, $tag_id);
                //update_post_meta($p, 'pe_ptc_sort', $i++);
            }
        }
        if( isset($post_data['tag']) && is_array($post_data['tag']))
        {
            $taxonomy = filter_input(INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING);

            if( !empty($taxonomy) && !empty($post_data['tag']) )
            {
                update_option( 'taxonomy_order_'.$taxonomy , $post_data['tag'] );
            }
        }

        die();
    }


    function add_admin_column( $columns, $post_type )
    {
        $options = $this->post_types[$post_type];

        //if( is_admin() && isset($options['admin_columns']))

        foreach($options['admin_columns'] AS $slug => $data)
        {

            if( isset($data['location']) && is_int($data['location']) )
            {
                $columns = array_slice($columns, 0, $data['location'], true) +
                    array( $slug => $data['label']) +
                    array_slice($columns, $data['location'], count($columns)-$data['location'], true);
            }
            else
            {
                $columns[$slug] = $data['label'];
            }

        }

        return $columns;
    }
    function add_admin_column_content( $column_name, $post_id  )
    {
        // No query is executed and no performance penalty as this is already cached internaly in WP
        $post = get_post($post_id);

        if(isset($this->post_types[ $post->post_type ]))
        {
            $options = $this->post_types[ $post->post_type ];

            if(is_callable($options['admin_columns'][$column_name]['cb']))
            {
                call_user_func_array($options['admin_columns'][$column_name]['cb'], array($post_id));
            }
        }
    }

    function register_taxonomies()
    {
        $taxonomies = $this->taxonomies;

        foreach($taxonomies AS $slug => $taxonomy)
        {
            $taxonomy['singular_label_ucf'] = ucfirst($taxonomy['singular_label']);
            $taxonomy['plural_label_ucf'] = ucfirst($taxonomy['plural_label']);

            $args = array(
                'label'               => __( $slug, $this->text_domain ),
                'description'         => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
                'labels'              => array(
                    'name'                  => _x( $taxonomy['plural_label_ucf'], 'Taxonomy General Name', $this->text_domain ),
                    'singular_name'         => _x( $taxonomy['singular_label_ucf'], 'Taxonomy Singular Name', $this->text_domain ),
                    'menu_name'             => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
                    'parent'                => sprintf(__( 'Parent %s', $this->text_domain ), $taxonomy['singular_label']),
                    'parent_item'           => sprintf(__( 'Parent %s', $this->text_domain ), $taxonomy['singular_label']),
                    'parent_item_colon'     => sprintf(__( 'Parent %s:', $this->text_domain ), $taxonomy['singular_label']),
                    'new_item_name'         => sprintf(__( 'Add new %s', $this->text_domain ), $taxonomy['singular_label']),
                    'add_new_item'          => sprintf(__( 'Add new %s', $this->text_domain ), $taxonomy['singular_label']),
                    'edit'                  => __( 'Edit', $this->text_domain ),
                    'edit_item'             => sprintf(__( 'Edit %s', $this->text_domain ), $taxonomy['singular_label']),
                    'update_item'           => sprintf(__( 'Update %s', $this->text_domain ), $taxonomy['singular_label']),
                    //'separate_items_with_commas' => __( 'Separate items with commas', $this->text_domain ),
                    'search_items'          => sprintf( __('Search %s', $this->text_domain), $taxonomy['plural_label']),
                    'add_or_remove_items'   => __( 'Add or remove %s', $taxonomy['plural_label'] ),
                    'choose_from_most_used' => __( 'Choose from the most used items', 'woocommerce-as400' ),
                    'not_found'             => sprintf(__( 'No %s found', $this->text_domain ), $taxonomy['plural_label']),
                ),
            );

            $default_args = array(
                'hierarchical'               => true,
                'public'                     => true,
                'show_ui'                    => true,
                'show_admin_column'          => true,
                'show_in_nav_menus'          => true,
                'show_tagcloud'              => true,

                //Custom
                'sortable'              => false,
            );

            $final_args = wp_parse_args(array_merge($taxonomy, $args), $default_args);

            register_taxonomy( $slug, $taxonomy['post_type'], $final_args );

            if( is_admin() )
            {

                $current_taxonomy = $this->get_current_taxonomy( $taxonomy['post_type'] );

                if( isset($final_args['admin_fields']))
                {
                    //TODO
                }

                if( $final_args['sortable'] &&
                    //in_array( $current_post_type, array_keys( $this->post_types ) ) &&
                    isset($this->taxonomies[$current_taxonomy]['sortable']) &&
                    $this->taxonomies[$current_taxonomy]['sortable'] == true
                )
                {
                    /**
                     * Make post list in admin sorted with out meta value
                     */
                    //add_filter('pre_get_posts', array( $this, 'sort_admin_tax_list' ) );

                    /**
                     * Show all terms on the same page. Needed for sortable to work.
                     */
                    // TODO: Better solution if there are many terms needed.
                    add_filter( 'edit_' . $slug . '_per_page', function() {
                        return 999999999;
                    } );

                    wp_enqueue_script('jquery-ui-core');
                    wp_enqueue_script('jquery-ui-sortable');

                    wp_enqueue_script('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/js/sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'));
                    wp_enqueue_style('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/css/sortable.css', array());
                }
                else
                {
                    $this->taxonomies[$slug]['sortable'] = false;
                }

            }
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
