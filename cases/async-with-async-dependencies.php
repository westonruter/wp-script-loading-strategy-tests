<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'async-no-dependency', 'async', [] );
	enqueue_test_script( 'async-one-async-dependency', 'async', [ 'async-no-dependency' ] );
	enqueue_test_script( 'async-two-async-dependencies', 'async', [ 'async-no-dependency', 'async-one-async-dependency' ] );
} );

// Snapshot of output below:
?>
async-no-dependency: before inline
async-no-dependency: script
async-no-dependency: after inline
async-one-async-dependency: before inline
async-one-async-dependency: script
async-one-async-dependency: after inline
async-two-async-dependencies: before inline
async-two-async-dependencies: script
async-two-async-dependencies: after inline
