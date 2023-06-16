<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this, but without the inline scripts.
	wp_register_script( 'defer-bundle-of-two-with-inline-scripts', false, [ 'defer-bundle-member-one-with-inline-scripts', 'defer-bundle-member-two-with-inline-scripts' ], null );
	add_test_inline_script( 'defer-bundle-of-two-with-inline-scripts', 'before' );
	add_test_inline_script( 'defer-bundle-of-two-with-inline-scripts', 'after' );
	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-one-with-inline-scripts', 'defer', [] );
	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-two-with-inline-scripts', 'defer', [] );

	// A dependency of the alias. Only this can be defer because of the alias's inline scripts.
	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-defer-bundle-of-two-with-inline-scripts', 'defer', [ 'defer-bundle-of-two-with-inline-scripts' ] );

	// TODO: What if one of the bundle members is non-blocking? What if one is non-blocking and the other is blocking? What if the bundle itself is marked as non-blocking?
} );

// Snapshot of output below:
?>
defer-bundle-member-one-with-inline-scripts: before inline
defer-bundle-member-one-with-inline-scripts: script
defer-bundle-member-one-with-inline-scripts: after inline
defer-bundle-member-two-with-inline-scripts: before inline
defer-bundle-member-two-with-inline-scripts: script
defer-bundle-member-two-with-inline-scripts: after inline
defer-bundle-of-two-with-inline-scripts: before inline
defer-bundle-of-two-with-inline-scripts: after inline
defer-dependent-of-defer-bundle-of-two-with-inline-scripts: before inline
defer-dependent-of-defer-bundle-of-two-with-inline-scripts: script
defer-dependent-of-defer-bundle-of-two-with-inline-scripts: after inline
