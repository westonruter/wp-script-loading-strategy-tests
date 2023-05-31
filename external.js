(function ( currentScript ) {
	const url = new URL( currentScript.src );
	scriptEventLog.push( url.searchParams.get( 'script_event_log' ) );
})( document.currentScript );
