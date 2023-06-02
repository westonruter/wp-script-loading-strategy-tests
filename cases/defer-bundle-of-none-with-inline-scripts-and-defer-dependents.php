<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// The eligible loading strategy for this will be forced to be blocking when rendered since $src = false.
	wp_register_script( 'defer-bundle-of-none', false, [], null );
	wp_scripts()->registered['defer-bundle-of-none']->extra['strategy'] = 'defer'; // Bypass wp_script_add_data() which should no-op with _doing_it_wrong() because of $src=false.
	add_test_inline_script( 'defer-bundle-of-none', 'before' );
	add_test_inline_script( 'defer-bundle-of-none', 'after' );

	// Note: the before script for this will be blocking because the dependency is blocking.
	enqueue_test_script( 'defer-dependent-of-defer-bundle-of-none', 'defer', [ 'defer-bundle-of-none' ] );
} );

// Snapshot of output below:
?>
defer-bundle-of-none: before inline
defer-bundle-of-none: after inline
defer-dependent-of-defer-bundle-of-none: before inline
defer-dependent-of-defer-bundle-of-none: script
defer-dependent-of-defer-bundle-of-none: after inline
