<?php


namespace PE;


class Custom_Post_Type extends Abstract_WP_Object
{

















    private function get_taxonomy_labels( $taxonomy_slug, $taxonomy ) {

        $labels = [
            'name'                  => _x( $taxonomy['plural_label_ucf'], 'Taxonomy General Name', $this->text_domain ),
            'singular_name'         => _x( $taxonomy['singular_label_ucf'], 'Taxonomy Singular Name', $this->text_domain ),
            'menu_name'             => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
            'parent'                => sprintf( __( 'Parent %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'parent_item'           => sprintf( __( 'Parent %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'parent_item_colon'     => sprintf( __( 'Parent %s:', $this->text_domain ), $taxonomy['singular_label'] ),
            'new_item_name'         => sprintf( __( 'Add new %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'add_new_item'          => sprintf( __( 'Add new %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'edit'                  => __( 'Edit', $this->text_domain ),
            'edit_item'             => sprintf( __( 'Edit %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'update_item'           => sprintf( __( 'Update %s', $this->text_domain ), $taxonomy['singular_label'] ),
            'separate_items_with_commas' => __( 'Separate items with commas', $this->text_domain ),
            'search_items'          => sprintf( __( 'Search %s', $this->text_domain ), $taxonomy['plural_label'] ),
            'add_or_remove_items'   => __( 'Add or remove %s', $taxonomy['plural_label'] ),
            'choose_from_most_used' => __( 'Choose from the most used items', $this->text_domain ),
            'not_found'             => sprintf( __( 'No %s found', $this->text_domain ), $taxonomy['plural_label'] ),
        ];

        $labels = apply_filters( 'ptc_taxonomy_labels', $labels, $taxonomy_slug, $taxonomy );
        $labels = apply_filters( 'ptc_taxonomy_labels_'.$taxonomy_slug, $labels, $taxonomy );

        return $labels;
    }



    /**
     * Generates all taxonomy labels and merges the labels and default values with the values passed to the plugin
     *
     * @param $taxonomy_slug - Taxonomy slug
     * @param $taxonomy - Taxonomy arguments/settings
     * @return array - Generated arguments for passing to register_taxonomy()
     */
    private function parse_taxonomy_args( $taxonomy_slug, $taxonomy ) {

        $taxonomy['singular_label_ucf'] = ucfirst( $taxonomy['singular_label'] );
        $taxonomy['plural_label_ucf'] = ucfirst( $taxonomy['plural_label'] );

        $generated_args = [
            'label'               => __( $taxonomy_slug, $this->text_domain ),
            'description'         => __( $taxonomy['plural_label_ucf'], $this->text_domain ),
            'labels'              => $this->get_taxonomy_labels( $taxonomy_slug, $taxonomy ),
        ];

        $default_args = [
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
            'rewrite' => [
                'slug' => sanitize_title( $taxonomy['plural_label'] ),
            ],
        ];

        return wp_parse_args( array_merge( $generated_args, $taxonomy ), $default_args );
    }




    protected function update_sort( $term_data ) {

        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );
        $sort_meta_key = $this->get_sort_meta_key( $taxonomy );
        $i = 1;

        global $wp_version;

        if ( version_compare( $wp_version, 4.4, '>=' ) || $this->use_acf ) {

            foreach ( $term_data as $term_id ) {
                $this->update_post_meta( $sort_meta_key, $i++, $term_id );
            }

        }
        else {
            if ( ! empty( $taxonomy ) && ! empty( $term_data ) ) {
                update_option( 'taxonomy_order_'.$taxonomy, $term_data );
            }
        }

    }

    function update_term_meta( $meta_key, $meta_value, $term_id ) {

        $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );

        if ( $this->use_acf ) {

            $term = get_term( $term_id, $taxonomy );

            update_field( $meta_key, $meta_value, $term );
        }
        else {
            update_term_meta( $term_id, $meta_key, $meta_value );
        }
    }

}