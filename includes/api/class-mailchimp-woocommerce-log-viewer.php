<?php

class MailChimp_WooCommerce_Log_Viewer {

	/**
	 * @var string file
	 */
	private static $file     = null;
	public static $directory = '*';

	private static $levels_classes = array(
		'debug'     => 'info',
		'info'      => 'success',
		'notice'    => 'info',
		'warning'   => 'warning',
		'error'     => 'error',
		'critical'  => 'error',
		'alert'     => 'error',
		'emergency' => 'error',
		'processed' => 'success',
	);

	private static $levels_imgs = array(
		'debug'     => 'info',
		'info'      => 'info',
		'notice'    => 'info',
		'warning'   => 'warning',
		'error'     => 'warning',
		'critical'  => 'warning',
		'alert'     => 'warning',
		'emergency' => 'warning',
		'processed' => 'info',
	);

	/**
	 * Log levels that are used
	 *
	 * @var array
	 */
	public static $log_levels = array(
		'emergency',
		'alert',
		'critical',
		'error',
		'warning',
		'notice',
		'info',
		'debug',
		'processed',
	);

	const MAX_FILE_SIZE = 52428800;

	/**
	 * @param $file
	 *
	 * @throws Exception
	 */
	public static function setFile( $file ) {
		$file = self::pathToLogFile( $file );
		if ( file_exists( $file ) ) {
			self::$file = $file;
		}
	}

	/**
	 * @param $file
	 * @return string
	 * @throws Exception
	 */
	public static function pathToLogFile( $file ) {
		if ( ! strpos( '/', $file ) ) {
			$logsPath = static::getLogDirectory();
			if ( static::$directory !== '*' ) {
				$logsPath .= '/' . static::$directory;
			}
			$file = $logsPath . '/' . $file;
		}
		return $file;
	}

	/**
	 * @return string
	 */
	public static function getFileName() {
		return basename( self::$file );
	}

	/**
	 * @return array|null
	 */
	public static function all() {
		$log       = array();
		$directory = static::getLogDirectory();
		$pattern   = '/\d{4}-\d{2}-\d{2}[T]\d{2}:\d{2}:\d{2}[+]\d\d[:]\d\d.*/';
		if ( ! self::$file ) {
			$log_file = self::getFiles();
			if ( ! count( $log_file ) ) {
				return array();
			}
			self::$file = $log_file[0];
			if ( self::$file === 'test-log.log' && isset( $log_file[1] ) ) {
				self::$file = $log_file[1];
			}
			self::$file = $directory . '/' . self::$file;
		}
		$file_path = self::$file;
		if ( filesize( $file_path ) > self::MAX_FILE_SIZE ) {
			return null;
		}
		$file = file_get_contents( $file_path );
		preg_match_all( $pattern, $file, $headings );
		if ( ! is_array( $headings ) || empty( $headings ) ) {
			return $log;
		}
		$log_data = preg_split( $pattern, $file );
		if ( $log_data[0] < 1 ) {
			array_shift( $log_data );
		}
		foreach ( $headings as $h ) {
			for ( $i = 0, $j = count( $h ); $i < $j; $i++ ) {
				preg_match( '/^(\d{4}-\d{2}-\d{2}[T]\d{2}:\d{2}:\d{2}[+]\d\d[:]\d\d) (EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG|PROCESSED) (.*)/', $h[ $i ], $current );
				if ( ! isset( $current[3] ) ) {
					continue;
				}
				$context = strtolower( $current[2] );
				if ( ! array_key_exists( $context, self::$levels_classes ) ) {
					continue;
				}
				$log[] = array(
					'context'     => $context,
					'level'       => $context,
					'level_class' => self::$levels_classes[ $context ],
					'level_img'   => self::$levels_imgs[ $context ],
					'date'        => $current[1],
					'text'        => $current[3],
					'stack'       => preg_replace( "/^\n*/", '', $log_data[ $i ] ),
				);
			}
		}
		return array_reverse( $log );
	}

	public static function setDirectory( $dir ) {
		static::$directory = "{$dir}";
	}

	/**
	 * @param bool $basename
	 * @return array
	 */
	public static function getFiles( $basename = false ) {
		if ( ! ( $dir = static::getLogDirectory() ) ) {
			return array();
		}
		$files = @scandir( $dir );
		$files = array_reverse( $files );
		$files = array_filter(
			$files,
			function( $value ) {
				return static::stringEndsWith( $value, '.log' ) && static::isValidLogFile( $value );
			}
		);
		if ( $basename && is_array( $files ) ) {
			foreach ( $files as $k => $file ) {
				$files[ $k ] = basename( $file );
			}
		}
		return array_values( $files );
	}

	/**
	 * @return null
	 */
	public static function getLogDirectory() {
		return ! defined( 'WC_LOG_DIR' ) ? null : rtrim( WC_LOG_DIR, '/' );
	}

	/**
	 * @param $haystack
	 * @param $needles
	 * @return bool
	 */
	public static function stringEndsWith( $haystack, $needles ) {
		foreach ( (array) $needles as $needle ) {
			if ( substr( $haystack, -strlen( $needle ) ) === (string) $needle ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $file
	 *
	 * @return bool
	 */
	public static function isValidLogFile( $file ) {
		return mailchimp_string_contains( $file, array('mailchimp_', 'fatal-') );
	}
}
