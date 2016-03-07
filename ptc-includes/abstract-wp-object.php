<?php


namespace PE;


abstract class Abstract_WP_Object
{

    abstract protected function update_sort();













    /**
     * AJAX handler/callback for drag-and-drop sorting
     */
    public function sortable_ajax_handler() {

        $nonce = filter_input( INPUT_POST, 'peptc_sort_nonce', FILTER_SANITIZE_STRING );
        $nonce = $_POST['peptc_sort_nonce'];

        //var_dump('sadasd');
        //die();

        //wp_send_json( [ 'status' => 'auth_failed' ] );

        if ( ! wp_verify_nonce( $nonce, 'peptc-sorting-nonce' ) || ! current_user_can( 'edit_posts' ) ) {
            //todo
            wp_send_json( [ 'status' => 'auth_failed' ] );
        }

        parse_str( filter_input( INPUT_POST, 'post_data', FILTER_SANITIZE_STRING ), $post_data );

        var_dump($post_data);

        if ( isset( $post_data['post'] ) && is_array( $post_data['post'] ) ) {
            $this->update_post_sort( $post_data['post'] );
        }
        if ( isset( $post_data['tag'] ) && is_array( $post_data['tag'] ) ) {

            // TODO
            $this->update_term_sort( $post_data['tag'] );


            /*
            $taxonomy = filter_input( INPUT_POST, 'taxonomy', FILTER_SANITIZE_STRING );

            if ( ! empty( $taxonomy ) && ! empty( $post_data['tag'] ) ) {
                update_option( 'taxonomy_order_'.$taxonomy , $post_data['tag'] );
            }
            */
        }



        wp_send_json( [ 'status' => 'ok' ] );

        //wp_die();
    }



}