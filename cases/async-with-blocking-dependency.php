<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'async-with-blocking-dependent', 'async', [] );
	enqueue_test_script( 'blocking-dependent-of-async', 'blocking', [ 'async-with-blocking-dependent' ] );
} );
