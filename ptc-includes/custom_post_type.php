<?php


namespace PE;


class Custom_Post_Type extends Abstract_WP_Object
{



    /**
     * @param $post_type
     * @return mixed|null|void
     */
    private function get_post_type_labels( $post_slug, $post_type ) {

        $labels = [
            'name'                  => _x( $post_type['plural_label_ucf'], 'Post Type General Name', $this->text_domain ),
            'singular_name'         => _x( $post_type['singular_label_ucf'], 'Post Type Singular Name', $this->text_domain ),
            'menu_name'             => __( $post_type['plural_label_ucf'], $this->text_domain ),
            'parent'                => sprintf( __( 'Parent %s', $this->text_domain ), $post_type['singular_label'] ),
            'parent_item_colon'     => sprintf( __( 'Parent %s:', $this->text_domain ), $post_type['singular_label'] ),
            'all_items'             => sprintf( __( 'All %s', $this->text_domain ), $post_type['plural_label'] ),
            'view'                  => sprintf( __( 'View %s', $this->text_domain ), $post_type['singular_label'] ),
            'view_item'             => sprintf( __( 'View %s', $this->text_domain ), $post_type['singular_label'] ),
            'add_new'               => sprintf( __( 'Add %s', $this->text_domain ), $post_type['singular_label'] ),
            'add_new_item'          => sprintf( __( 'Add new %s', $this->text_domain ), $post_type['singular_label'] ),
            'edit'                  => __( 'Edit', $this->text_domain ),
            'edit_item'             => sprintf( __( 'Edit %s', $this->text_domain ), $post_type['singular_label'] ),
            'update_item'           => sprintf( __( 'Update %s', $this->text_domain ), $post_type['singular_label'] ),
            'search_items'          => sprintf( __( 'Search %s', $this->text_domain ), $post_type['plural_label'] ),
            'not_found'             => sprintf( __( 'No %s found', $this->text_domain ), $post_type['plural_label'] ),
            'not_found_in_trash'    => sprintf( __( 'No %s found in trash', $this->text_domain ), $post_type['plural_label'] ),
        ];

        $labels = apply_filters( 'ptc_post_type_labels', $labels, $post_slug, $post_type );
        $labels = apply_filters( 'ptc_post_type_labels_'.$post_slug, $labels, $post_type );

        return $labels;
    }


    /**
     * Generates all post type labels and merges the labels and default values with the values passed to the plugin
     *
     * @param $post_slug - Post type slug
     * @param $post_type - Post type arguments/settings
     * @return array - Generated arguments for passing to register_post_type()
     */
    private function parse_post_type_args( $post_slug, $post_type ) {

        $post_type['singular_label_ucf'] = ucfirst( $post_type['singular_label'] );
        $post_type['plural_label_ucf'] = ucfirst( $post_type['plural_label'] );

        $generated_args = [
            'label'               => __( $post_slug, $this->text_domain ),
            'description'         => __( $post_type['plural_label_ucf'], $this->text_domain ),
            'labels'              => $this->get_post_type_labels( $post_slug, $post_type ),
        ];

        $default_args = [
            // Override some defaults to cover most cases out of the box
            'supports'              => [ 'title', 'editor', 'thumbnail' ],
            'taxonomies'            => [],
            'public'                => true,
            'menu_position'         => 6,   //Below posts
            'has_archive'           => true,

            //Custom
            'admin_columns'         => [],
            'sortable'              => false,
        ];

        return wp_parse_args( array_merge( $generated_args, $post_type ), $default_args );
    }


    private function register_post_statuses( $post_args ) {

        if ( empty( $post_args['post_statuses'] ) || ! is_array( $post_args['post_statuses'] ) ) {
            return null;
        }

        foreach ($post_args['post_statuses'] as $post_status_slug => $post_status_args ) {

            $post_status_args['label'] = $post_status_args['singular_label'];
            $post_status_args['label_count'] = _n_noop(
                $post_status_args['singular_label'].' <span class="count">(%s)</span>',
                $post_status_args['plural_label'].' <span class="count">(%s)</span>',
                $this->text_domain
            );

            register_post_status( $post_status_slug, $post_status_args );
        }
    }


    protected function update_sort( $post_data ) {

        $post_type = filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_STRING );
        $sort_meta_key = $this->get_sort_meta_key( $post_type );
        $i = 1;

        foreach ( $post_data as $post_id ) {
            $this->update_post_meta( $sort_meta_key, $i++, $post_id );
        }

    }

    function update_post_meta( $meta_key, $meta_value, $post_id ) {
        if ( $this->use_acf ) {
            update_field( $meta_key, $meta_value, $post_id );
        }
        else {
            update_post_meta( $post_id, $meta_key, $meta_value );
        }
    }



}