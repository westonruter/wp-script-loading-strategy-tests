<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this.
	wp_register_script( 'blocking-bundle-of-two', false, [], null );
	enqueue_test_script( 'blocking-bundle-member-one', 'blocking', [ 'blocking-bundle-of-two' ] );
	enqueue_test_script( 'blocking-bundle-member-two', 'blocking', [ 'blocking-bundle-of-two' ] );

	// Note: the before script for this will be blocking because the dependency is blocking.
	// TODO: What if one of the bundle members is non-blocking? What if one is non-blocking and the other is blocking? What if the bundle itself is marked as non-blocking?
	enqueue_test_script( 'defer-dependent-of-blocking-bundle-of-two', 'defer', [ 'blocking-bundle-of-two' ] );
} );

// Snapshot of output below:
?>
blocking-bundle-member-one: before inline
blocking-bundle-member-one: script
blocking-bundle-member-one: after inline
blocking-bundle-member-two: before inline
blocking-bundle-member-two: script
blocking-bundle-member-two: after inline
defer-dependent-of-blocking-bundle-of-two: before inline
defer-dependent-of-blocking-bundle-of-two: script
defer-dependent-of-blocking-bundle-of-two: after inline
