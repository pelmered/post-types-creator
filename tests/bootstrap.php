<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}


require_once $_tests_dir . '/includes/functions.php';



function _manually_load_plugin() {

	$plugin_base_dir = dirname( dirname( __FILE__ ) );

	require $plugin_base_dir . '/post-types-creator.php';

	require_once $plugin_base_dir . '/example-plugin/my-custom-post-types/my-custom-post-types.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
