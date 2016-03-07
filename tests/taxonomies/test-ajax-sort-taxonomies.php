<?php

/**
 * Test case for the Ajax callback to update 'some_option'.
 *
 * @group ajax
 */
class AjaxSortTaxonomiesTest extends WP_Ajax_UnitTestCase {

    private $term_ids = [];

    /*
    static function setUpBeforeClass() {

        for( $i = 0; $i < 10;  $i++ ) {

            self::$term_ids[] = wp_insert_term( 'random string'.$i, 'business_unit' );

        }
    }
*/

    public function setup () {
        parent::setup();



        for( $i = 0; $i < 10;  $i++ ) {

            $ids = wp_insert_term( 'random string'.$i, 'business_unit' );
            $this->term_ids[] = $ids['term_id'];

        }


        wp_set_current_user( 1 );
    }

    public function test_ajax_sort_admin(  )
    {
        $this->_setRole( 'administrator' );

        $_POST['peptc_sort_nonce'] = wp_create_nonce( 'peptc-sorting-nonce' );
        $_POST['taxonomy'] = 'business_unit';
        $_POST['post_data'] = 'yes';

        /*

				action: 'pe_ptc_sort_posts',
				post_type: $post_type,
				taxonomy: $taxonomy,
				post_data: post_data,
				peptc_sort_nonce: PEPTCSortable.peptcSortNonce
         */

        try {
            $this->_handleAjax( 'pe_ptc_sort_posts' );
        } catch ( WPAjaxDieStopException $e ) {
            // We expected this, do nothing.
        } catch ( WPAjaxDieContinueException $e ) {
            // We expected this, do nothing.
        }

        // Check that the exception was thrown.
        $this->assertTrue( isset( $e ) );

        $response = json_decode( $this->_last_response, true );

        //var_dump($response);

        $this->assertTrue( isset( $response['status'] ) );


        $this->assertEquals( 'ok', $response['status'] );

        //TODO: Verify data
        //$this->assertEquals( 'yes', get_option( 'some_option' ) );


    }

    public function test_ajax_sort_subscriber(  )
    {
        $this->_setRole( 'subscriber' );

        $_POST['peptc_sort_nonce'] = wp_create_nonce( 'peptc-sorting-nonce' );
        $_POST['option_value'] = 'yes';

        try {
            $this->_handleAjax( 'pe_ptc_sort_posts' );
        } catch ( WPAjaxDieStopException $e ) {
            // We expected this, do nothing.
        } catch ( WPAjaxDieContinueException $e ) {
            // We expected this, do nothing.
        }

        // Check that the exception was thrown.
        $this->assertTrue( isset( $e ) );

        $response = json_decode( $this->_last_response, true );

        $this->assertTrue( isset( $response['status'] ) );

        $this->assertEquals( 'auth_failed', $response['status'] );
    }






}