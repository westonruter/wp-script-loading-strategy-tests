<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {
	enqueue_test_script( 'blocking-not-async-without-dependency', 'blocking', [] );
	enqueue_test_script( 'async-with-blocking-dependency', 'async', [ 'blocking-not-async-without-dependency' ] );
} );

// Snapshot of output below:
/*
 * Note: This was sometimes result in a snapshot as follows:
 *
 *   blocking-not-async-without-dependency: before inline
 *   blocking-not-async-without-dependency: script
 *   blocking-not-async-without-dependency: after inline
 *   async-with-blocking-dependency: script
 *   async-with-blocking-dependency: after inline
 *   async-with-blocking-dependency: before inline
 *
 * The before script for async-with-blocking-dependency is getting executed _after_ the after script. This appears to
 * have been fixed with the introduction of \WP_Scripts::should_delay_inline_script() which prevents delaying a before
 * script when there are blocking dependencies.
 */
?>
blocking-not-async-without-dependency: before inline
blocking-not-async-without-dependency: script
blocking-not-async-without-dependency: after inline
async-with-blocking-dependency: before inline
async-with-blocking-dependency: script
async-with-blocking-dependency: after inline
