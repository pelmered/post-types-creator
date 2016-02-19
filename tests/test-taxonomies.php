<?php

class TaxonomiesTest extends WP_UnitTestCase {

	function test_exists() {

		$this->assertTrue( in_array('business_unit', get_taxonomies() ) );

	}
}

