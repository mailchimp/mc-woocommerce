<?php

class MailChimp_WooCommerce_Logs {

	protected $view         = null;
	protected $limit        = false;
	protected $search_query = null;
	public $items           = array();

	/**
	 * @param $view
	 * @return $this
	 */
	public function withView( $view ) {
		$this->view = $view;
		return $this;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit( int $limit ) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param $value
	 * @return $this
	 */
	public function searching( $value ) {
		$this->search_query = $value;
		return $this;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function handle() {
		if ( $this->view ) {
			MailChimp_WooCommerce_Log_Viewer::setFile( base64_decode( $this->view ) );
		}

		$logs = array();

		// Check if we're viewing connection logs
		if ( $this->view === base64_encode( 'connection-logs' ) ) {
			// Get enhanced connection logs
			if ( class_exists( 'MailChimp_WooCommerce_Enhanced_Logger' ) ) {
				$connection_logs = MailChimp_WooCommerce_Enhanced_Logger::get_connection_logs( 200 );
				
				foreach ( $connection_logs as $log ) {
					try {
						$date = new DateTime( $log['timestamp'] );
					} catch ( Exception $e ) {
						$date = new DateTime();
					}
					
					// Format log entry for display
					$http_code = isset( $log['connection']['http_code'] ) ? $log['connection']['http_code'] : 'N/A';
					$error_msg = isset( $log['error']['message'] ) ? $log['error']['message'] : '';
					$url_parts = parse_url( $log['url'] );
					$path = isset( $url_parts['path'] ) ? $url_parts['path'] : $log['url'];
					
					// Determine context based on error
					$context = 'info';
					if ( ! empty( $log['error'] ) ) {
						$context = 'error';
					} elseif ( $http_code >= 400 ) {
						$context = 'error';
					} elseif ( $http_code >= 300 ) {
						$context = 'warning';
					}
					
					// Build text for display
					$text = sprintf( 
						'[%s %s] HTTP %s - %s%s', 
						$log['method'],
						$path,
						$http_code,
						! empty( $error_msg ) ? "Error: $error_msg" : 'Success',
						isset( $log['connection']['total_time'] ) ? sprintf( ' (%.3fs)', $log['connection']['total_time'] ) : ''
					);
					
					// Add diagnostic info if available
					if ( ! empty( $log['diagnostics']['possible_causes'] ) ) {
						$text .= ' | Causes: ' . implode( '; ', $log['diagnostics']['possible_causes'] );
					}
					
					$item = array(
						'context'     => $context,
						'level'       => $context,
						'level_class' => $context === 'error' ? 'error' : ( $context === 'warning' ? 'warning' : 'success' ),
						'level_img'   => $context === 'error' ? 'warning' : 'info',
						'date'        => $date->format( 'D, M j, Y g:i A' ),
						'datetime'    => $date->format( 'Y-m-d H:i:s A' ),
						'text'        => strtolower( $text ),
						'stack'       => json_encode( $log, JSON_PRETTY_PRINT ),
					);
					
					if ( ! empty( $this->search_query ) && ! mailchimp_string_contains( $item['text'], $this->search_query ) ) {
						continue;
					}
					
					$logs[] = $item;
				}
			}
		} else {
			// Regular file logs
			foreach ( MailChimp_WooCommerce_Log_Viewer::all() as $item ) {
				try {
					$date = new DateTime( $item['date'] );
				} catch ( Exception $e ) {
					$date = new DateTime();
				}
				$item['date']     = $date->format( 'D, M j, Y g:i A' );
				$item['datetime'] = $date->format( 'Y-m-d H:i:s A' );
				$item['text']     = strtolower( str_replace( '[]', ' ', $item['text'] ) );
				if ( ! empty( $this->search_query ) && ! mailchimp_string_contains( $item['text'], $this->search_query ) ) {
					continue;
				}
				$logs[] = $item;
			}
		}

		if ( $this->limit ) {
			$logs = array_slice( $logs, 0, $this->limit, true );
		}

		$files = array();
		
		// Add connection logs as a special "file"
		$files[] = array(
			'value'    => base64_encode( 'connection-logs' ),
			'filename' => 'connection-logs',
			'label'    => 'Connection Logs (Enhanced)',
		);

		foreach ( MailChimp_WooCommerce_Log_Viewer::getFiles( true ) as $key => $file ) {
			preg_match( '/(.*)-(\d{4}-\d{2}-\d{2})-(.*).log/', $file, $matches );
			// the date should be here
			if ( ! isset( $matches[2] ) ) {
				continue;
			}
			if ( !mailchimp_string_contains($file, array('mailchimp_', 'fatal-')) ) {
				continue;
			}
			$files[] = array(
				'value'    => base64_encode( $file ),
				'filename' => $file,
				'label'    => $matches[1] . ' ' . $matches[2],
			);
		}

		return $this->items = array(
			'view_file' => $this->view,
			'search'    => $this->search_query,
			'dir'       => MailChimp_WooCommerce_Log_Viewer::getLogDirectory(),
			'current'   => $this->view ? $this->view : base64_encode( MailChimp_WooCommerce_Log_Viewer::getFileName() ),
			'files'     => $files,
			'logs'      => $logs,
		);
	}
}
