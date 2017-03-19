<?php
class logfile{
	function write($the_string ) {
		if( $fh = @fopen("/tmp/php-debug.log", "a+" ) ) {
			fputs( $fh, $the_string, strlen($the_string) );
			fclose( $fh );
			return( true );
		} else {
			return( false );
		}
	}
}

// vim:ai:ts=4:sw=4
?>
