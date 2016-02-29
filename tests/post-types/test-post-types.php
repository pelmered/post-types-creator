<?php

class PostTypesTest extends WP_UnitTestCase {

	static function setUpBeforeClass() {
		require 'bootstrap-post-types.php';
	}

	function test_cpt_registered() {

		$post_types = get_post_types();

		$this->assertContains( 'stores', $post_types );
		$this->assertContains( 'employees', $post_types );
	}

	function test_custom_post_status_exists() {

		$post_statuses = get_post_stati();

		$this->assertContains( 'active', $post_statuses );
		$this->assertContains( 'completed', $post_statuses );
	}

	function test_insert_post() {

		$last_id = 0;

		for ( $i = 0; $i < 4; $i++ ) {
			// Create post object
			$my_post = [
				'post_title'    => 'random title',
				'post_content'  => 'random text',
				'post_status'   => 'active',
				'post_type'		=> 'stores',
				'post_author'   => 1,
				'post_category' => [ 8,39 ]
			];

			$post_id = wp_insert_post( $my_post );

			if ( $i != 0 ) {
				// Post ID should be incremented by one for each successful insert
				$this->assertEquals( $post_id,  ( $last_id + 1 ) );
			}

			$last_id = $post_id;
		}
	}
}

