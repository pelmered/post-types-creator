<?php


namespace PE;


class Custom_Post_Type_Admin
{

    function __construct()
    {

    }


    /**
     * Set default sort meta for new posts. Hooked into save_post
     * Reference: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
     *
     * @param $post_id
     * @param $post
     * @param $update
     */
    public function save_post( $post_id, $post, $update ) {

        if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

        if ( in_array( $post->post_type , $this->post_types ) && $this->post_types[ $post->post_type ]['sortable'] ) {
            $current = get_post_meta( $post->ID, $this->get_sort_meta_key( $post->post_type ), true );

            if ( empty( $current ) || ! is_numeric( $current ) ) {
                $sort_value = apply_filters( 'pe_ptc_sort_default', 0, $post_id, $post, $update );

                $sort_meta_key = $this->get_sort_meta_key( $post->post_type );

                if ( $this->use_acf ) {
                    update_field( $sort_meta_key, $sort_value, $post_id );
                }
                else {
                    update_post_meta( $post_id, $sort_meta_key, $sort_value );
                }
            }
        }
    }

    /**
     * Adds order by query to all post queries
     *
     * @param $wp_query
     * @return mixed
     */
    public function sort_admin_post_list( $wp_query ) {

        if ( ! isset( $wp_query->query_vars ) || ! isset( $wp_query->query_vars['post_type'] ) || is_array( $wp_query->query_vars['post_type'] ) ) {
            return $wp_query;
        }

        if (
            isset( $wp_query->query_vars['post_type'] ) &&
            isset( $this->post_types[ $wp_query->query_vars['post_type'] ] ) &&
            $this->post_types[ $wp_query->query_vars['post_type'] ]['sortable']
        ) {
            $wp_query->set( 'orderby', 'meta_value_num' );
            $wp_query->set( 'meta_key', $this->get_sort_meta_key( $wp_query->query_vars['post_type'] ) );
            $wp_query->set( 'order', 'ASC' );
        }

        return $wp_query;
    }

    public function restrict_admin_posts_by_taxonomy() {

        global $typenow;

        $post_type = $this->get_current_post_type();

        if ( isset( $this->taxonomy_filters[ $post_type ] ) && is_array( $this->taxonomy_filters[ $post_type ] ) ) {
            foreach ( $this->taxonomy_filters[ $post_type ] as $taxonomy ) {
                $selected_taxonomy = filter_input( INPUT_GET, $taxonomy, FILTER_SANITIZE_STRING );

                $selected = isset( $selected_taxonomy ) ? $selected_taxonomy : '';

                $info_taxonomy = get_taxonomy( $taxonomy );
                wp_dropdown_categories([
                    'show_option_all' => __( "Show All {$info_taxonomy->label}" ),
                    'taxonomy' => $taxonomy,
                    'name' => $taxonomy,
                    'orderby' => 'name',
                    'selected' => $selected,
                    'show_count' => true,
                    'hide_empty' => true,
                ]);
            }
        }
    }

    public function add_terms_filter_to_query( $query ) {
        global $pagenow;

        if ($pagenow != 'edit.php' ) {
            return $query;
        }

        $post_type = $this->get_current_post_type();

        if ( isset( $this->taxonomy_filters[ $post_type ] ) && is_array( $this->taxonomy_filters[ $post_type ] ) ) {
            foreach ( $this->taxonomy_filters[ $post_type ] as $taxonomy ) {
                $query_vars = $query->query_vars;

                if (
                    isset( $query_vars['post_type'] ) &&
                    $query_vars['post_type'] == $post_type &&
                    isset( $query_vars[ $taxonomy ] ) &&
                    is_numeric( $query_vars[ $taxonomy ] )
                    && $query_vars[ $taxonomy ] != 0
                ) {
                    $term = get_term_by( 'id', $query_vars[ $taxonomy ], $taxonomy );
                    $query->query_vars[ $taxonomy ] = $term->slug;
                }
            }
        }

        return $query;
    }


    private function register_admin_columns( $slug, $post_args ) {

        if ( isset( $post_args['admin_columns'] ) ) {
            foreach ( $post_args['admin_columns'] as $column ) {
                add_filter( 'manage_posts_columns' , [ $this, 'add_admin_column' ], 10, 2 );

                add_action( 'manage_'.$slug.'_posts_custom_column' , [ $this, 'add_admin_column_content' ], 10, 2 );
            }
        }
    }

    private function add_taxonomy_filters( $slug, $post_args ) {

        if ( isset( $post_args['taxonomy_filters'] ) ) {

            if ( $post_args['taxonomy_filters'] === true ) {
                $this->taxonomy_filters[ $slug ] = $post_args['taxonomies'];
            }
            elseif (is_array( $post_args['taxonomy_filters'] ) && ! empty( $post_args['taxonomy_filters'] ) ) {
                $this->taxonomy_filters[ $slug ] = $post_args['taxonomy_filters'];
            }
        }
    }

    private function register_sortable( $slug, $args, $taxonomy = false ) {

        if ( ! $args['sortable'] ) {
            return;
        }

        if ( $taxonomy ) {
            $current = $this->get_current_taxonomy( $args['post_type'] );

            if ( $args['sortable'] &&
                isset( $this->taxonomies[ $current ]['sortable'] ) &&
                $this->taxonomies[ $current ]['sortable'] == true
            ) {

                // Show all terms on the same page. Needed for drag and drop sorting to work.
                // TODO: Better solution if there are many terms needed.
                add_filter( 'edit_' . $slug . '_per_page', function() {
                    return 5000;
                } );

                $this->enqueue_sortable_scripts();
            } else {
                $this->taxonomies[ $slug ]['sortable'] = false;
            }
        } else {
            $current = $this->get_current_post_type();

            if ( $args['sortable'] &&
                isset( $this->post_types[ $current ]['sortable'] ) &&
                $this->post_types[ $current ]['sortable'] == true
            ) {

                if ( $taxonomy ) {

                }
            } else {
                $this->post_types[ $slug ]['sortable'] = false;
            }
        }

        if ( $args['sortable'] &&
            isset( $this->post_types[ $current ]['sortable'] ) &&
            $this->post_types[ $current ]['sortable'] == true
        ) {

            if ( $taxonomy ) {

            }
        }
        else {
            $this->post_types[ $slug ]['sortable'] = false;
        }

    }

    private function enqueue_sortable_scripts() {

        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_script( 'pe-post-type-creator-sortable', plugins_url( '', __FILE__ ) . '/assets/js/sortable.js', [ 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ] );
        wp_enqueue_style( 'pe-post-type-creator-sortable', plugins_url( '', __FILE__ ) . '/assets/css/sortable.css', [] );

        wp_localize_script( 'pe-post-type-creator-sortable', 'PEPTCSortable', array(
            'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
            'peptcSortNonce'	=> wp_create_nonce( 'peptc-sorting-nonce' ),
        ) );
    }



    public function add_admin_column( $columns, $post_type ) {

        if ( ! isset( $this->post_types[ $post_type ] ) ) {
            return $columns;
        }

        $options = $this->post_types[ $post_type ];

        foreach ($options['admin_columns'] as $slug => $data ) {

            if ( isset( $data['location'] ) && is_int( $data['location'] ) ) {
                $columns = array_slice( $columns, 0, $data['location'], true ) +
                    [ $slug => $data['label'] ] +
                    array_slice( $columns, $data['location'], count( $columns ) -$data['location'], true );
            }
            else {
                $columns[ $slug ] = $data['label'];
            }
        }

        return $columns;
    }

    public function add_admin_column_content( $column_name, $post_id ) {

        // No query is executed and no performance penalty as this is already cached internaly in WP
        $post = get_post( $post_id );

        if (isset( $this->post_types[ $post->post_type ] ) ) {
            $options = $this->post_types[ $post->post_type ];

            if ( isset( $options['admin_columns'][ $column_name ]['cb'] )  && is_callable( $options['admin_columns'][ $column_name ]['cb'] ) ) {
                call_user_func_array( $options['admin_columns'][ $column_name ]['cb'], [ $post_id ] );
            }
        }
    }
}