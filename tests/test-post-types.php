<?php

class PostTypesTest extends WP_UnitTestCase {

	function test_exists() {

		$this->assertTrue( in_array('stores', get_post_types() ) );

	}
}

