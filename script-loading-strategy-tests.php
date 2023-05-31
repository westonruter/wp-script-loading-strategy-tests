<?php
/**
 * Plugin Name: Script Loading Strategy Tests
 */

add_action( 'wp_enqueue_scripts', static function () {

	foreach ( [ 'blocking', 'async', 'defer' ] as $strategy ) {

		wp_enqueue_script(
			$strategy,
			plugin_dir_url( __FILE__ ) . "{$strategy}.js",
			[],
			false,
			[ 'strategy' => $strategy ]
		);
		wp_add_inline_script(
			$strategy,
			sprintf( 'console.log( %s );', wp_json_encode( "$strategy before standalone!" ) ),
			'before',
			false
		);
		wp_add_inline_script(
			$strategy,
			sprintf( 'console.log( %s );', wp_json_encode( "$strategy after standalone!" ) ),
			'after',
			false
		);
	}

//
//	wp_enqueue_script(
//		'async',
//		plugin_dir_url( __FILE__ ) . '/async.js',
//		[],
//		false,
//		[
//			'strategy' => 'async',
//			'in_footer' => true,
//		]
//	);
//	wp_enqueue_script(
//		'defer',
//		plugin_dir_url( __FILE__ ) . '/defer.js',
//		[],
//		false,
//		[ 'strategy' => 'async' ]
//	);

} );
