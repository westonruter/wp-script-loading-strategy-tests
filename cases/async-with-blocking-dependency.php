<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script_with_inline_scripts( 'async-with-blocking-dependent', 'async', [] );
	enqueue_test_script_with_inline_scripts( 'blocking-dependent-of-async', 'blocking', [ 'async-with-blocking-dependent' ] );
} );

// Snapshot of output below:
?>
async-with-blocking-dependent: before inline
async-with-blocking-dependent: script
async-with-blocking-dependent: after inline
blocking-dependent-of-async: before inline
blocking-dependent-of-async: script
blocking-dependent-of-async: after inline
