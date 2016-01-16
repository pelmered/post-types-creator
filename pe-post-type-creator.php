<?php
/**
 * Plugin Name: Post types creator
 * Description: Helper plugin for easily creating localize-ready custom post types and custom taxonomies with extra functionality in WordPress
 * Version:     0.2.0
 * Author:      Peter Elmered
 * Text Domain: post-type-creator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


class PE_Post_Type_Creator {

    private $plugin_slug = 'post-type-creator';
    private $text_domain = 'post-type-creator';

    public $post_types = array();
    public $taxonomies = array();

    public $use_acf = false;


    function __construct( $options = array() )
    {
        if( isset( $options['text_domain'] ) )
        {
            $this->text_domain = $options['text_domain'];
        }
        if( isset( $options['use_acf'] ) && $options['use_acf'] )
        {
            $this->use_acf = true;
        }

        $this->load_plugin_textdomain();
    }

    function init()
    {
        add_action( 'wp_ajax_pe_ptc_sort_posts', array($this, 'sortable_ajax_handler') );

        $this->register_post_types();
        $this->register_taxonomies();

        add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );

        add_filter('get_terms_orderby', array( $this, 'sort_get_terms' ), 10, 3 );
    }

    public function force_reinitialize()
    {
        add_action( 'init', array( $this, 'force_reinitialize2' ) );
    }

    public function force_reinitialize2()
    {
        global $wp_rewrite;

        $wp_rewrite->flush_rules();

        foreach( $this->post_types AS $post_slug => $post_args )
        {

            if( isset($post_args['sortable']) && $post_args['sortable'] )
            {
                $sort_meta_key = apply_filters( 'pe_ptc_sort_meta_key', 'sort', $post_slug );

                //delete_post_meta_by_key( $sort_meta_key );

                $args = array(
                    'posts_per_page'   => -1,
                    'post_type'        => $post_slug,
                    'post_status'      => 'publish',
                );
                $posts = get_posts( $args );

                $sort_value = 1;

                foreach( $posts AS $post )
                {
                    $current = get_post_meta( $post->ID, $sort_meta_key, true );

                    var_dump($current);

                    if( empty( $current ) )
                    {
                        var_dump('$sort_value');
                        var_dump($sort_value);
                        delete_post_meta( $post->ID, $sort_meta_key );
                        update_post_meta( $post->ID, $sort_meta_key, $sort_value++ );
                    }

                }
            }
        }
    }

    function parse_post_type_args( $post_slug, $post_type )
    {
        $post_type['singular_label_ucf'] = ucfirst($post_type['singular_label']);
        $post_type['plural_label_ucf'] = ucfirst($post_type['plural_label']);

        $generated_args = array(
            'label'               => __( $post_slug, $this->text_domain ),
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

        return wp_parse_args(array_merge( $generated_args, $post_type ), $default_args);
    }

    function parse_taxonomy_args( $taxonomy_slug, $taxonomy )
    {
        $taxonomy['singular_label_ucf'] = ucfirst($taxonomy['singular_label']);
        $taxonomy['plural_label_ucf'] = ucfirst($taxonomy['plural_label']);

        $generated_args = array(
            'label'               => __( $taxonomy_slug, $this->text_domain ),
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
                'choose_from_most_used' => __( 'Choose from the most used items', $this->text_domain ),
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

            // register_taxonomy overrides
            // https://codex.wordpress.org/Function_Reference/register_taxonomy
            'rewrite' => array(
                'slug' => sanitize_title($taxonomy['plural_label']),
            )
        );

        return wp_parse_args(array_merge( $generated_args, $taxonomy ), $default_args);
    }

    function set_post_types($post_types)
    {
        $parsed_post_types = array();

        foreach( $post_types AS $slug => $post_type ) {
            $parsed_post_types[$slug] = $this->parse_post_type_args($slug, $post_type);
        }

        $this->post_types = $parsed_post_types;
    }


    function set_taxonomies($taxonomies)
    {
        $parsed_taxonomies = array();

        foreach( $taxonomies AS $slug => $post_type ) {
            $parsed_taxonomies[$slug] = $this->parse_taxonomy_args($slug, $post_type);
        }

        $this->taxonomies = $parsed_taxonomies;
    }


    function register_post_types()
    {
        $post_types = $this->post_types;

        foreach($post_types AS $slug => $post_args)
        {
            register_post_type( $slug, $post_args );

            if( isset( $post_args['post_statuses'] ) && !empty( $post_args['post_statuses'] ) && is_array( $post_args['post_statuses'] ) )
            {
                foreach( $post_args['post_statuses'] AS $post_status_slug => $post_status_args )
                {

                    $post_status_args['label'] = $post_status_args['singular_label'];
                    $post_status_args['label_count'] = _n_noop(
                        $post_status_args['singular_label'].' <span class="count">(%s)</span>',
                        $post_status_args['plural_label'].' <span class="count">(%s)</span>',
                        $this->text_domain
                    ); // $post_status_args['singular_label'];

                    register_post_status( $post_status_slug, $post_status_args );
                }
            }

            if( is_admin() )
            {
                $current_post_type = $this->get_current_post_type();

                add_action( 'admin_footer-post.php', array( $this, 'append_post_status_list' ) );

                if( isset($post_args['admin_columns']))
                {
                    foreach( $post_args['admin_columns'] AS $column )
                    {
                        add_filter( 'manage_posts_columns' , array($this, 'add_admin_column'), 10, 2 );
                        //add_filter( 'manage_'.$slug.'_posts_columns' , array($this, 'add_admin_column'), 10, 2 );

                        add_action( 'manage_'.$slug.'_posts_custom_column' , array($this, 'add_admin_column_content'), 10, 2 );
                    }
                }

                if( $post_args['sortable'] &&
                    //in_array( $current_post_type, array_keys( $this->post_types ) ) &&
                    isset($this->post_types[$current_post_type]['sortable']) &&
                    $this->post_types[$current_post_type]['sortable'] == true
                )
                {
                    /**
                     * Make post list in admin sorted without meta value
                     */
                    add_filter('pre_get_posts', array( $this, 'sort_admin_post_list' ) );

                    wp_enqueue_script('jquery-ui-core');
                    wp_enqueue_script('jquery-ui-sortable');

                    wp_enqueue_script('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/js/sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'));
                    wp_enqueue_style('pe-post-type-creator-sortable', plugins_url('', __FILE__) . '/assets/css/sortable.css', array());
                }
                else
                {
                    $this->post_types[$slug]['sortable'] = false;
                }

            }

        }

    }

    function register_taxonomies()
    {
        $taxonomies = $this->taxonomies;

        foreach($taxonomies AS $slug => $taxonomy_args)
        {
            register_taxonomy( $slug, $taxonomy_args['post_type'], $taxonomy_args );

            if( is_admin() )
            {
                $current_taxonomy = $this->get_current_taxonomy( $taxonomy_args['post_type'] );

                if( $taxonomy_args['sortable'] &&
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

        if(array_key_exists( $taxonomy, $this->taxonomies) && isset($this->taxonomies[$taxonomy]['sortable']) )
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
            $sort_value = apply_filters('pe_ptc_sort_default', 99, $post_id, $post, $update );

            $sort_meta_key = apply_filters( 'pe_ptc_sort_meta_key', 'sort', $post->post_type );

            if( $this->use_acf )
            {
                update_field( $sort_meta_key, $sort_value, $post_id );
            }
            else
            {
                update_post_meta( $post_id, $sort_meta_key, $sort_value );
            }
        }
    }

    function sort_admin_post_list( $wp_query )
    {
        $sort_meta_key = apply_filters( 'pe_ptc_sort_meta_key', 'sort', $wp_query->query_vars['post_type'] );

        $wp_query->set( 'orderby', 'meta_value_num' );
        $wp_query->set( 'meta_key', $sort_meta_key );
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

    function append_post_status_list()
    {
        global $post;

        if( isset( $this->post_types[$post->post_type ]['post_statuses'] ) && is_array( $this->post_types[$post->post_type ]['post_statuses'] ) )
        {
            echo '<script>';
            echo 'jQuery(document).ready(function($) {';

            foreach( $this->post_types[$post->post_type ]['post_statuses'] AS $post_status_slug => $post_status )
            {
                $label = $post_status['singular_label'];

                if( $post->post_status == $post_status_slug && in_array( $post->post_status, array_keys( $this->post_types[$post->post_type ]['post_statuses'] ) ) )
                {
                    $selected = ' selected="selected"';
                    ?>
                    $(".misc-pub-section label").append(" <?php echo $label; ?>");
                    <?php
                }
                else
                {
                    $selected = '';
                }
                ?>
                $("select#post_status").append('<option value="<?php echo $post_status_slug; ?>" <?php echo $selected; ?>><?php echo $label ?></option>');
                <?php
            }

            echo '});';
            echo '</script>';

        }
    }

    function sortable_ajax_handler()
    {
        parse_str(filter_input(INPUT_POST, 'post_data', FILTER_SANITIZE_STRING), $post_data );

        $post_type = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING);
        $taxonomy = filter_input(INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING);

        $i = 1;

        // TODO
        // Sorted taxonomies not supported yes
        if( empty( $taxonomy ) )
        {
            //return;
        }

        if( isset($post_data['post']) && is_array($post_data['post']))
        {
            $sort_meta_key = apply_filters( 'pe_ptc_sort_meta_key', 'sort', $post_type );

            foreach( $post_data['post'] AS $post_id )
            {
                if( $this->use_acf )
                {
                    update_field( $sort_meta_key, $i++, $post_id );
                }
                else
                {
                    update_post_meta( $post_id, $sort_meta_key, $i++ );
                }

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

        wp_send_json( array( 'status' => 'ok' ) );

        die();
    }


    function add_admin_column( $columns, $post_type )
    {
        if( !isset($this->post_types[$post_type]))
        {
            return $columns;
        }

        $options = $this->post_types[$post_type];

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

            if( isset($options['admin_columns'][$column_name]['cb'])  && is_callable($options['admin_columns'][$column_name]['cb']))
            {
                call_user_func_array($options['admin_columns'][$column_name]['cb'], array($post_id));
            }
        }
    }




    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Translations / Locales are loaded from:
     * 	 - WP_LANG_DIR/post-type-creator/post-type-creator-LOCALE.mo (first prority)
     * 	 - [path to this plugin]/languages/post-type-creator-LOCALE.mo (Loaded if the file above does not exist)
     *
     */
    public function load_plugin_textdomain()
    {
        $locale = apply_filters( 'plugin_locale', get_locale(), $this->text_domain );

        load_textdomain( $this->text_domain, WP_LANG_DIR . '/' . $this->text_domain . '/' . $this->text_domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $this->text_domain, false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
    }


}

