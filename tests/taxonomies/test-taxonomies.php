<?php

class TaxonomiesTest extends WP_UnitTestCase {

	static function setUpBeforeClass() {
		require 'bootstrap-taxonomies.php';
	}

	function test_taxonomy_registered() {

		$taxonomies = get_taxonomies();

		$this->assertContains( 'business_unit', $taxonomies );
	}

	function test_insert_term( )
	{
		$last_id = 0;

		for( $i = 0; $i < 4; $i++ )
		{
			$ids = wp_insert_term( 'random string'.$i, 'business_unit' );

			if( is_wp_error( $ids ) )
			{
				$this->assertTrue( false );
				continue;
			}

			if( $i != 0 )
			{
				// Term ID should be incremented by one for each successful insert
				$this->assertEquals( $ids['term_id'],  ( $last_id + 1 ) );
			}

			$last_id = $ids['term_id'];
		}
	}
}

