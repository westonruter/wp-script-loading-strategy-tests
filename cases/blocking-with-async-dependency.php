<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'blocking-not-async-without-dependency', 'blocking', [] );
	enqueue_test_script( 'async-with-blocking-dependency', 'async', [ 'blocking-not-async-without-dependency' ] );
} );

// Snapshot of output below:
?>
blocking-not-async-without-dependency: before inline
blocking-not-async-without-dependency: script
blocking-not-async-without-dependency: after inline
async-with-blocking-dependency: before inline
async-with-blocking-dependency: script
async-with-blocking-dependency: after inline
