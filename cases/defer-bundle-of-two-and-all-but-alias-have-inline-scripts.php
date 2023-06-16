<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this.
	wp_register_script( 'defer-bundle-of-two-without-inline-scripts', false, [ 'defer-bundle-member-uno-with-inline-scripts', 'defer-bundle-member-dos-with-inline-scripts' ], null );
	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-uno-with-inline-scripts', 'defer', [] );
	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-dos-with-inline-scripts', 'defer', [] );

	// A dependency of the alias.
	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-defer-bundle-of-two-without-inline-scripts', 'defer', [ 'defer-bundle-of-two-without-inline-scripts' ] );

	// TODO: What if one of the bundle members is non-blocking? What if one is non-blocking and the other is blocking? What if the bundle itself is marked as non-blocking?
} );

// Snapshot of output below:
?>
defer-bundle-member-uno-with-inline-scripts: before inline
defer-bundle-member-dos-with-inline-scripts: before inline
defer-bundle-member-uno-with-inline-scripts: script
defer-bundle-member-uno-with-inline-scripts: after inline
defer-bundle-member-dos-with-inline-scripts: script
defer-bundle-member-dos-with-inline-scripts: after inline
defer-dependent-of-defer-bundle-of-two-without-inline-scripts: before inline
defer-dependent-of-defer-bundle-of-two-without-inline-scripts: script
defer-dependent-of-defer-bundle-of-two-without-inline-scripts: after inline
