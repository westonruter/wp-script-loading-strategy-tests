<?php
namespace ScriptLoadingStrategyTests;

add_action( 'wp_enqueue_scripts', static function () {

	// Note that jQuery is registered like this.
	wp_register_script( 'defer-bundle-of-two', false, [], null );
	wp_scripts()->registered['defer-bundle-of-two']->extra['strategy'] = 'defer'; // Bypass wp_script_add_data() which should no-op with _doing_it_wrong() because of $src=false.
	add_test_inline_script( 'defer-bundle-of-two', 'before' );
	add_test_inline_script( 'defer-bundle-of-two', 'after' );

	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-one', 'defer', [ 'defer-bundle-of-two' ] );
	enqueue_test_script_with_inline_scripts( 'defer-bundle-member-two', 'defer', [ 'defer-bundle-of-two' ] );

	// Note: the before script for this will be blocking because the dependency is blocking.
	// TODO: What if one of the bundle members is non-blocking? What if one is non-blocking and the other is blocking? What if the bundle itself is marked as non-blocking?
	enqueue_test_script_with_inline_scripts( 'defer-dependent-of-blocking-bundle-of-two', 'defer', [ 'defer-bundle-of-two' ] );
} );

// Snapshot of output below:
?>
defer-bundle-of-two: before inline
defer-bundle-of-two: after inline
defer-bundle-member-one: before inline
defer-bundle-member-one: script<?php /* Not executing here! */ echo PHP_EOL; ?>
defer-bundle-member-one: after inline
defer-bundle-member-two: before inline
defer-bundle-member-two: script
defer-bundle-member-two: after inline
defer-dependent-of-blocking-bundle-of-two: before inline
defer-dependent-of-blocking-bundle-of-two: script
defer-dependent-of-blocking-bundle-of-two: after inline
