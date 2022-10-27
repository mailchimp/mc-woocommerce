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

		if ( $this->limit ) {
			$logs = array_slice( $logs, 0, $this->limit, true );
		}

		$files = array();

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
			'current'   => base64_encode( MailChimp_WooCommerce_Log_Viewer::getFileName() ),
			'files'     => $files,
			'logs'      => $logs,
		);
	}
}
