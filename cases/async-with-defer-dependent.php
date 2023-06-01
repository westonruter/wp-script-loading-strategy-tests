<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'async-with-defer-dependent', 'async', [] );
	enqueue_test_script( 'defer-dependent-of-async', 'defer', [ 'async-with-defer-dependent' ] );
} );
