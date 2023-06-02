<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this.
	wp_register_script( 'blocking-bundle-of-none', false, [], null );
	add_test_inline_script( 'blocking-bundle-of-none', 'before' );
	add_test_inline_script( 'blocking-bundle-of-none', 'after' );

	// Note: the before script for this will be blocking because the dependency is blocking.
	// TODO: What if one of the bundle members is non-blocking? What if one is non-blocking and the other is blocking? What if the bundle itself is marked as non-blocking?
	enqueue_test_script( 'defer-dependent-of-blocking-bundle-of-none', 'defer', [ 'blocking-bundle-of-none' ] );
} );

// Snapshot of output below:
?>
blocking-bundle-of-none: before inline
blocking-bundle-of-none: after inline
defer-dependent-of-blocking-bundle-of-none: before inline
defer-dependent-of-blocking-bundle-of-none: script
defer-dependent-of-blocking-bundle-of-none: after inline
