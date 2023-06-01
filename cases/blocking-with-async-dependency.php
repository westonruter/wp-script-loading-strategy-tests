<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'blocking-not-async-without-dependency', 'blocking', [] );
	enqueue_test_script( 'async-with-blocking-dependency', 'async', [ 'blocking-not-async-without-dependency' ] );
} );
