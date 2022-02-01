<?php
class Sferanet_Wordpress_Integration_Logs_Admin {

	private static $instance = null;

	private function __construct() {

	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			$c              = __CLASS__;
			self::$instance = new $c();
		}

		return self::$instance;
	}

	/**
	 * Write an entry to a log file in the uploads directory.
	 *
	 * @since x.x.x
	 *
	 * @param mixed  $entry String or array of the information to write to the log.
	 * @param string $file Optional. The file basename for the .log file.
	 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
	 * @return boolean|int Number of bytes written to the lof file, false otherwise.
	 */
	function sferanet_logs( $entry, $mode = 'a', $file = 'sferanet_api' ) {

		if ( ! defined( 'SFERANET_DEBUG' ) ) {
			return;
		}
		// Get WordPress uploads directory.
		$upload_dir = wp_upload_dir()['basedir'];

		// If the entry is array, json_encode.
		if ( is_array( $entry ) ) {
			$entry = json_encode( $entry );
		}

		// Write the log file.
		$file  = $upload_dir . '/' . $file . '.log';
		$file  = fopen( $file, $mode );
		$bytes = fwrite( $file, current_time( 'mysql' ) . '::' . $entry . "\n" );
		fclose( $file );

		return $bytes;
	}

}
