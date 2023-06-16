<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	$outer_alias_handle = 'outer-bundle-of-two';
	$inner_alias_handle = 'inner-bundle-of-two';

	// The outer alias contains a blocking member, as well as a nested alias that contains defer scripts.
	wp_register_script( $outer_alias_handle, false, [ $inner_alias_handle, 'outer-bundle-leaf-member' ], null );
	register_test_script( 'outer-bundle-leaf-member', 'blocking', [] );

	// Inner alias only contains delay scripts.
	wp_register_script( $inner_alias_handle, false, [ 'inner-bundle-member-one', 'inner-bundle-member-two' ], null );
	register_test_script( 'inner-bundle-member-one', 'defer', [] );
	register_test_script( 'inner-bundle-member-two', 'defer', [] );

	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-nested-aliases', 'defer', [ $outer_alias_handle ] );
} );

// Snapshot of output below:
?>
outer-bundle-leaf-member: script
inner-bundle-member-one: script
inner-bundle-member-two: script
defer-dependent-of-nested-aliases: before inline
defer-dependent-of-nested-aliases: script
defer-dependent-of-nested-aliases: after inline
