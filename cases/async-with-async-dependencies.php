<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'async-no-dependency', 'async', [] );
	enqueue_test_script( 'async-one-async-dependency', 'async', [ 'async-no-dependency' ] );
	enqueue_test_script( 'async-two-async-dependencies', 'async', [ 'async-no-dependency', 'async-one-async-dependency' ] );
} );
