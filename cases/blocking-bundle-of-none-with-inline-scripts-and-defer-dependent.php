<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this.
	wp_register_script( 'blocking-bundle-of-none', false, [], null );
	add_test_inline_script( 'blocking-bundle-of-none', 'before' );
	add_test_inline_script( 'blocking-bundle-of-none', 'after' );

	// Note: the before script for this will be blocking because the dependency is blocking.
	// TODO: What if the bundle is actually marked as non-blocking?
	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-blocking-bundle-of-none', 'defer', [ 'blocking-bundle-of-none' ] );
} );

// Snapshot of output below:
?>
blocking-bundle-of-none: before inline
blocking-bundle-of-none: after inline
defer-dependent-of-blocking-bundle-of-none: before inline
defer-dependent-of-blocking-bundle-of-none: script
defer-dependent-of-blocking-bundle-of-none: after inline
