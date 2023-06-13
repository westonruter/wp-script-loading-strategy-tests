<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script_with_inline_scripts( 'async-no-dependency-with-defer-dependent', 'async', [] );
	enqueue_test_script_with_inline_scripts( 'defer-dependent-with-async-dependency', 'defer', [ 'async-no-dependency-with-defer-dependent' ] );
} );

// Snapshot of output below:
?>
async-no-dependency-with-defer-dependent: before inline
async-no-dependency-with-defer-dependent: script
async-no-dependency-with-defer-dependent: after inline
defer-dependent-with-async-dependency: before inline
defer-dependent-with-async-dependency: script
defer-dependent-with-async-dependency: after inline
