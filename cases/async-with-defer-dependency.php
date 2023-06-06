<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'defer-with-async-dependent', 'defer', [] );
	enqueue_test_script( 'async-with-defer-dependency', 'async', [ 'defer-with-async-dependent' ] );
} );

// Snapshot of output below:
?>
defer-with-async-dependent: before inline
defer-with-async-dependent: script
defer-with-async-dependent: after inline
async-with-defer-dependency: before inline
async-with-defer-dependency: script
async-with-defer-dependency: after inline
