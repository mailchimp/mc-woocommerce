<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 * @author     Ryan Hungate <ryan@vextras.com>
 */
class MailChimp_WooCommerce_Admin extends MailChimp_WooCommerce_Options {

	protected $swapped_list_id = null;
	protected $swapped_store_id = null;

    /** @var null|static */
    protected static $_instance = null;

    /**
     * @return MailChimp_WooCommerce_Admin
     */
    public static function instance()
    {
        if (!empty(static::$_instance)) {
            return static::$_instance;
        }
        $env = mailchimp_environment_variables();
        static::$_instance = new MailChimp_WooCommerce_Admin();
        static::$_instance->setVersion($env->version);
        return static::$_instance;
    }

	/**
	 * @return MailChimp_WooCommerce_Admin|MailChimp_WooCommerce_Options
	 */
	public static function connect()
	{
		return static::instance();
	}

    /**
     * @return array
     */
	private function disconnect_store()
	{
		// remove user from our marketing status audience
		try {
            mailchimp_remove_communication_status();
        } catch (\Exception $e) {}

		if (($store_id = mailchimp_get_store_id()) && ($mc = mailchimp_get_api()))  {
		    set_site_transient('mailchimp_disconnecting_store', true, 15);
            if ($mc->deleteStore($store_id)) {
                mailchimp_log('store.disconnected', 'Store id ' . mailchimp_get_store_id() . ' has been disconnected');
            } else {
                mailchimp_log('store.NOT DISCONNECTED', 'Store id ' . mailchimp_get_store_id() . ' has NOT been disconnected');
            }
        }

        // clean database
        mailchimp_clean_database();

        $options = array();

		return $options;
	}
	
	/**
	 * Tests admin permissions, disconnect action and nonce
	 * @return bool 
	 */
	private function is_disconnecting() {
		return isset($_REQUEST['mailchimp_woocommerce_disconnect_store'])
			   && current_user_can( 'manage_options' )
			   && $_REQUEST['mailchimp_woocommerce_disconnect_store'] == 1 
			   && isset($_REQUEST['_disconnect-nonce']) 
			   && wp_verify_nonce($_REQUEST['_disconnect-nonce'], '_disconnect-nonce-'.mailchimp_get_store_id());
	}

    /**
     * Tests admin permissions, disconnect action and nonce
     * @return bool
     */
    private function is_resyncing() {
        return isset($_REQUEST['mailchimp_woocommerce_resync'])
            && current_user_can( 'manage_options' )
            && $_REQUEST['mailchimp_woocommerce_resync'] == 1
            && isset($_REQUEST['_resync-nonce'])
            && wp_verify_nonce($_REQUEST['_resync-nonce'], '_resync-nonce-'.mailchimp_get_store_id());
    }
		
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook) {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin.css', array(), $this->version.'.21', 'all' );
		wp_enqueue_style( $this->plugin_name . 'checkbox', plugin_dir_url( __FILE__ ) . 'css/checkbox.min.css', array(), $this->version, 'all' );

		if ( strpos($hook, 'page_mailchimp-woocommerce') !== false ) {
			if ( get_bloginfo( 'version' ) < '5.3') {
				wp_enqueue_style( $this->plugin_name."-settings", plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin-settings-5.2.css', array(), $this->version, 'all' );
			}	
			wp_enqueue_style( $this->plugin_name."-settings", plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin-settings.css', array(), $this->version, 'all' );
			wp_style_add_data( $this->plugin_name."-settings", 'rtl', 'replace' );	
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {
		if ( strpos($hook, 'page_mailchimp-woocommerce') !== false ) {
			wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp-woocommerce-admin.js', array( 'jquery', 'swal' ), $this->version.'.21', false );
			wp_localize_script(
				$this->plugin_name,
				'phpVars',
				array( 
					'removeReviewBannerRestUrl' => MailChimp_WooCommerce_Rest_Api::url('review-banner'),
					'l10n' => array(
						'are_you_sure' => __('Are you sure?', 'mailchimp-for-woocommerce'),
						'log_delete_subtitle' => __('You will not be able to revert.', 'mailchimp-for-woocommerce'),
						'log_delete_confirm' => __('Yes, delete it!', 'mailchimp-for-woocommerce'),
						'no_cancel' => __('No, cancel!', 'mailchimp-for-woocommerce'),
						'store_disconnect_subtitle' => __('You are about to disconnect your store from Mailchimp.', 'mailchimp-for-woocommerce'),
						'store_disconnect_confirm' => __('Yes, disconnect.', 'mailchimp-for-woocommerce'),
					),
				)
			);
			wp_enqueue_script( $this->plugin_name);
			wp_enqueue_script('swal', "//cdn.jsdelivr.net/npm/sweetalert2@8", '', $this->version, false);
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Add woocommerce menu subitem
		add_submenu_page( 
			'woocommerce', 
			__( 'Mailchimp for WooCommerce', 'mailchimp-for-woocommerce'), 
			__( 'Mailchimp', 'mailchimp-for-woocommerce' ),
			mailchimp_get_allowed_capability(),
			$this->plugin_name,
			array($this, 'display_plugin_setup_page')
		);
	}

	/**
	 * Include the new Navigation Bar the Admin page.
	 */
	public function add_woocommerce_navigation_bar() {
		if ( function_exists( 'wc_admin_connect_page' ) ) {
			wc_admin_connect_page(
				array(
					'id'        => $this->plugin_name,
					'screen_id' => 'woocommerce_page_mailchimp-woocommerce',
					'title'     => __( 'Mailchimp for WooCommerce', 'mailchimp-for-woocommerce' ),
				)
			);
		}
	}

	/**
	 * check if current user can view options pages/ save plugin options
	 */
	public function mailchimp_woocommerce_option_page_capability() {
		return mailchimp_get_allowed_capability();
	}

	/**
	 * Setup Feedback Survey Form
	 *
	 * @since    2.1.15
	 */
	public function setup_survey_form() {
		if (is_admin()) {
            try {
                new Mailchimp_Woocommerce_Deactivation_Survey($this->plugin_name, 'mailchimp-for-woocommerce');
            } catch (\Throwable $e) {
                mailchimp_error('admin@setup_survey_form', $e->getCode() . ' :: ' . $e->getMessage() . ' on ' . $e->getLine() . ' in ' . $e->getFile());
                return false;
            }
        }
	}

    /**
     * @return string
     */
    protected function mailchimp_svg()
    {
        return base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52.03 55"><defs><style>.cls-1{fill:#fff;}</style></defs><title>Asset 1</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M11.64,28.54a4.75,4.75,0,0,0-1.17.08c-2.79.56-4.36,2.94-4.05,6a6.24,6.24,0,0,0,5.72,5.21,4.17,4.17,0,0,0,.8-.06c2.83-.48,3.57-3.55,3.1-6.57C15.51,29.83,13.21,28.63,11.64,28.54Zm2.77,8.07a1.17,1.17,0,0,1-1.1.55,1.53,1.53,0,0,1-1.37-1.58A4,4,0,0,1,12.23,34a1.44,1.44,0,0,0-.55-1.74,1.48,1.48,0,0,0-1.12-.21,1.44,1.44,0,0,0-.92.64,3.39,3.39,0,0,0-.34.79l0,.11c-.13.34-.33.45-.47.43s-.16-.05-.21-.21a3,3,0,0,1,.78-2.55,2.46,2.46,0,0,1,2.11-.76,2.5,2.5,0,0,1,1.91,1.39,3.19,3.19,0,0,1-.23,2.82l-.09.2A1.16,1.16,0,0,0,13,36a.74.74,0,0,0,.63.32,1.38,1.38,0,0,0,.34,0c.15,0,.3-.07.39,0A.24.24,0,0,1,14.41,36.61Z"/><path class="cls-1" d="M51,33.88a3.84,3.84,0,0,0-1.15-1l-.11-.37-.14-.42a5.57,5.57,0,0,0,.5-3.32,5.43,5.43,0,0,0-1.54-3,10.09,10.09,0,0,0-4.24-2.26c0-.67,0-1.43-.06-1.9a12.83,12.83,0,0,0-.49-3.25,10.46,10.46,0,0,0-1.3-2.92c2.14-2.56,3.29-5.21,3.29-7.57,0-3.83-3-6.3-7.59-6.3a19.3,19.3,0,0,0-7.22,1.6l-.34.14L28.7,1.52A6.31,6.31,0,0,0,24.43,0,14.07,14.07,0,0,0,17.6,2.2a36.93,36.93,0,0,0-6.78,5.21c-4.6,4.38-8.3,9.63-9.91,14A12.51,12.51,0,0,0,0,26.54a6.16,6.16,0,0,0,2.13,4.4l.78.66A10.44,10.44,0,0,0,2.74,35a9.36,9.36,0,0,0,3.21,6,10,10,0,0,0,5.13,2.43,20.19,20.19,0,0,0,7.31,8A23.33,23.33,0,0,0,30.17,55H31a23.27,23.27,0,0,0,12-3.16,19.1,19.1,0,0,0,7.82-9.06l0,0A16.89,16.89,0,0,0,52,37.23,5.17,5.17,0,0,0,51,33.88Zm-1.78,8.21c-3,7.29-10.3,11.35-19,11.09-8.06-.24-14.94-4.5-18-11.43a7.94,7.94,0,0,1-5.12-2.06,7.56,7.56,0,0,1-2.61-4.85A8.31,8.31,0,0,1,5,31L3.32,29.56C-4.42,23,19.77-3.86,27.51,2.89l2.64,2.58,1.44-.61c6.79-2.81,12.3-1.45,12.3,3,0,2.33-1.48,5.05-3.86,7.52a7.54,7.54,0,0,1,2,3.48,11,11,0,0,1,.42,2.82c0,1,.09,3.16.09,3.2l1,.27A8.64,8.64,0,0,1,47.2,27a3.66,3.66,0,0,1,1.06,2.06A4,4,0,0,1,47.55,32,10.15,10.15,0,0,1,48,33.08c.2.64.35,1.18.37,1.25.74,0,1.89.85,1.89,2.89A15.29,15.29,0,0,1,49.18,42.09Z"/><path class="cls-1" d="M48,36a1.36,1.36,0,0,0-.86-.16,11.76,11.76,0,0,0-.82-2.78A17.89,17.89,0,0,1,40.45,36a23.64,23.64,0,0,1-7.81.84c-1.69-.14-2.81-.63-3.23.74a18.3,18.3,0,0,0,8,.81.14.14,0,0,1,.16.13.15.15,0,0,1-.09.15s-3.14,1.46-8.14-.08a2.58,2.58,0,0,0,1.83,1.91,8.24,8.24,0,0,0,1.44.39c6.19,1.06,12-2.47,13.27-3.36.1-.07.16,0,.08.12l-.13.18c-1.59,2.06-5.88,4.44-11.45,4.44-2.43,0-4.86-.86-5.75-2.17-1.38-2-.07-5,2.24-4.71l1,.11a21.13,21.13,0,0,0,10.5-1.68c3.15-1.46,4.34-3.07,4.16-4.37A1.87,1.87,0,0,0,46,28.34a6.8,6.8,0,0,0-3-1.41c-.5-.14-.84-.23-1.2-.35-.65-.21-1-.39-1-1.61,0-.53-.12-2.4-.16-3.16-.06-1.35-.22-3.19-1.36-4a1.92,1.92,0,0,0-1-.31,1.86,1.86,0,0,0-.58.06,3.07,3.07,0,0,0-1.52.86,5.24,5.24,0,0,1-4,1.32c-.8,0-1.65-.16-2.62-.22l-.57,0a5.22,5.22,0,0,0-5,4.57c-.56,3.83,2.22,5.81,3,7a1,1,0,0,1,.22.52.83.83,0,0,1-.28.55h0a9.8,9.8,0,0,0-2.16,9.2,7.59,7.59,0,0,0,.41,1.12c2,4.73,8.3,6.93,14.43,4.93a15.06,15.06,0,0,0,2.33-1,12.23,12.23,0,0,0,3.57-2.67,10.61,10.61,0,0,0,3-5.82C48.6,36.7,48.33,36.23,48,36Zm-8.25-7.82c0,.5-.31.91-.68.9s-.66-.42-.65-.92.31-.91.68-.9S39.72,27.68,39.71,28.18Zm-1.68-6c.71-.12,1.06.62,1.32,1.85a3.64,3.64,0,0,1-.05,2,4.14,4.14,0,0,0-1.06,0,4.13,4.13,0,0,1-.68-1.64C37.29,23.23,37.31,22.34,38,22.23Zm-2.4,6.57a.82.82,0,0,1,1.11-.19c.45.22.69.67.53,1a.82.82,0,0,1-1.11.19C35.7,29.58,35.47,29.13,35.63,28.8Zm-2.8-.37c-.07.11-.23.09-.57.06a4.24,4.24,0,0,0-2.14.22,2,2,0,0,1-.49.14.16.16,0,0,1-.11,0,.15.15,0,0,1-.05-.12.81.81,0,0,1,.32-.51,2.41,2.41,0,0,1,1.27-.53,1.94,1.94,0,0,1,1.75.57A.19.19,0,0,1,32.83,28.43Zm-5.11-1.26c-.12,0-.17-.07-.19-.14s.28-.56.62-.81a3.6,3.6,0,0,1,3.51-.42A3,3,0,0,1,33,26.87c.12.2.15.35.07.44s-.44,0-.95-.24a4.18,4.18,0,0,0-2-.43A21.85,21.85,0,0,0,27.71,27.17Z"/><path class="cls-1" d="M35.5,13.29c.1,0,.16-.15.07-.2a11,11,0,0,0-4.69-1.23.09.09,0,0,1-.07-.14,4.78,4.78,0,0,1,.88-.89.09.09,0,0,0-.06-.16,12.46,12.46,0,0,0-5.61,2,.09.09,0,0,1-.13-.09,6.16,6.16,0,0,1,.59-1.45.08.08,0,0,0-.11-.11A22.79,22.79,0,0,0,20,16.24a.09.09,0,0,0,.12.13A19.53,19.53,0,0,1,27,13.32,19.1,19.1,0,0,1,35.5,13.29Z"/><path class="cls-1" d="M28.34,6.42S26.23,4,25.6,3.8C21.69,2.74,13.24,8.57,7.84,16.27,5.66,19.39,2.53,24.9,4,27.74a11.43,11.43,0,0,0,1.79,1.72A6.65,6.65,0,0,1,10,26.78,34.21,34.21,0,0,1,20.8,11.62,55.09,55.09,0,0,1,28.34,6.42Z"/></g></g></svg>');
    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		$settings_link = array(
			'<a href="' . admin_url( 'admin.php?page=' . $this->plugin_name ) . '">' . __('Settings') . '</a>',
		);
		return array_merge($settings_link, $links);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_setup_page() {
		include_once( 'partials/mailchimp-woocommerce-admin-tabs.php' );
	}

	/**
	 *
	 */
	public function options_update() {
		global $pagenow;

		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));

		// tammullen found this.
        if ($pagenow == 'admin.php' && isset($_GET) && isset($_GET['page']) && 'mailchimp-woocommerce' === $_GET['page']) {
            $this->handle_abandoned_cart_table();
            $this->update_db_check();
            if (get_option('mailchimp-woocommerce-sync.initial_sync') == 1 && get_option('mailchimp-woocommerce-sync.completed_at') > 0 ) {
                $this->mailchimp_show_initial_sync_message();
            }
			if (isset($_GET['log_removed']) && $_GET['log_removed'] == "1") {
				add_settings_error('mailchimp_log_settings', '', __('Log file deleted.', 'mailchimp-for-woocommerce'), 'info');
			}
        }
	}


	/**
	 * Displays notice when plugin is installed but not yet configured / connected to Mailchimp.
	 */
	public function initial_notice() {
		if (!mailchimp_is_configured()) {
			// If WC_Admin_Notes doesn't exist, show normal wordpress admin notice...
			if ( ! class_exists( '\Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes' ) ) {
				$class = 'notice notice-warning is-dismissible';
				$message = sprintf(
					/* translators: Placeholders %1$s - opening strong HTML tag, %2$s - closing strong HTML tag, %3$s - opening link HTML tag, %4$s - closing link HTML tag */
					esc_html__(
						'%1$sMailchimp for Woocommerce%2$s is not yet connected to a Mailchimp account. To complete the connection, %3$svisit the plugin settings page%4$s.',
						'facebook-for-woocommerce'
					),
					'<strong>',
					'</strong>',
					'<a href="' . admin_url( 'admin.php?page=') . $this->plugin_name . '">',
					'</a>'
				);
				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
			}
			// else show the WooCoomerce admin inbox note.
			else {
				if ( ! class_exists( 'WC_Data_Store' ) ) {
					return;
				}
		
				$data_store = WC_Data_Store::load( 'admin-note' );
		
				// First, see if we've already created this kind of note so we don't do it again.
				$note_ids = $data_store->get_notes_with_name( 'mailchimp-for-woocommerce-incomplete-install' );
				foreach( (array) $note_ids as $note_id ) {
					$note         = \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes::get_note( $note_id );
					$content_data = $note->get_content_data();
					if ( property_exists( $content_data, 'getting_started' ) ) {
						return;
					}
				}
		
				// Otherwise, add the note
				$activated_time = current_time( 'timestamp', 0 );
				$activated_time_formatted = date( 'F jS', $activated_time );
				$note = new \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note();
				$note->set_title( __( 'Mailchimp For WooCommerce', 'mailchimp-for-woocommerce' ) );
				$note->set_content(
					esc_html__(
						'Plugin is not yet connected to a Mailchimp account. To complete the connection, open the settings page.',
						'mailchimp-for-woocommerce'
					)
				);
				$note->set_content_data( (object) array(
					'getting_started'     => true,
					'activated'           => $activated_time,
					'activated_formatted' => $activated_time_formatted,
				) );
				$note->set_type( \Automattic\WooCommerce\Admin\Notes\WC_Admin_Note::E_WC_ADMIN_NOTE_WARNING );
				$note->set_layout('plain');
				$note->set_image('');
				$note->set_name( 'mailchimp-for-woocommerce-incomplete-install' );
				$note->set_source( 'mailchimp-for-woocommerce' );
				$note->set_layout('plain');
				$note->set_image('');
				$note->add_action(
					'settings',
					__( 'Open Settings', 'mailchimp-for-woocommerce' ),
					admin_url( 'admin.php?page=') . $this->plugin_name
				);
				$note->save();
			}
		}
	}

	/**
	 * Depending on the version we're on we may need to run some sort of migrations.
	 */
	public function update_db_check() {
		// grab the current version set in the plugin variables
		global $wpdb;
		global $pagenow;

		$version = mailchimp_environment_variables()->version;

		// grab the saved version or default to 1.0.3 since that's when we first did this.
		$saved_version = get_site_option('mailchimp_woocommerce_version', '1.0.3');

		// if the saved version is less than the current version
		if (version_compare($version, $saved_version) > 0) {
			// resave the site option so this only fires once.
			update_site_option('mailchimp_woocommerce_version', $version);

			// get plugin options
			$options = $this->getOptions();
			
			// set permission_cap in case there's none set.
			if (!isset($options['mailchimp_permission_cap']) || empty($options['mailchimp_permission_cap']) ) {
				$options['mailchimp_permission_cap'] = 'manage_options';
				update_option($this->plugin_name, $options);
			}

			// resend marketing status to update latest changes
			if (!empty($options['admin_email'])) {
				try {
					// send the post to the mailchimp server
					$comm_opt = get_option('mailchimp-woocommerce-comm.opt', 0);
					$this->mailchimp_set_communications_status_on_server($comm_opt, $options['admin_email']);
				} catch (\Exception $e) {
					mailchimp_error("marketing_status_update", $e->getMessage());
				}
			}
		}

		if (!get_option( $this->plugin_name.'_cart_table_add_index_update')) {
			$check_index_sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema='{$wpdb->dbname}' AND table_name='{$wpdb->prefix}mailchimp_carts' AND index_name='primary' and column_name='email';";
			$index_exists = $wpdb->get_var($check_index_sql);
			if ($index_exists == '1') {
				update_option( $this->plugin_name.'_cart_table_add_index_update', true);
			}
			else {
				//remove table duplicates
				$delete_sql = "DELETE carts_1 FROM {$wpdb->prefix}mailchimp_carts carts_1 INNER JOIN {$wpdb->prefix}mailchimp_carts carts_2 WHERE carts_1.created_at < carts_2.created_at AND carts_1.email = carts_2.email;";
				if ($wpdb->query($delete_sql) !== false) {
					$sql = "ALTER TABLE {$wpdb->prefix}mailchimp_carts ADD PRIMARY KEY (email);";
					// only update the option if the query returned sucessfully
					if ($wpdb->query($sql) !== false) {
						update_option( $this->plugin_name.'_cart_table_add_index_update', true);
					}	
				}
			}
		}
		
		if (!get_option( $this->plugin_name.'_woo_currency_update')) {
			if ($this->mailchimp_update_woo_settings()) {
				update_option( $this->plugin_name.'_woo_currency_update', true);
			} 
		}
		
		if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mailchimp_jobs';") != $wpdb->prefix.'mailchimp_jobs') {
			MailChimp_WooCommerce_Activator::create_queue_tables();
			MailChimp_WooCommerce_Activator::migrate_jobs();
		}

		if (defined( 'DISABLE_WP_HTTP_WORKER' ) || defined( 'MAILCHIMP_USE_CURL' ) || defined( 'MAILCHIMP_REST_LOCALHOST' ) || defined( 'MAILCHIMP_REST_IP' ) || defined( 'MAILCHIMP_DISABLE_QUEUE') && true === MAILCHIMP_DISABLE_QUEUE) {
			$constants_used = array();
			
			if (defined( 'DISABLE_WP_HTTP_WORKER')) {
				$constants_used[] = 'DISABLE_WP_HTTP_WORKER';
			}

			if (defined( 'MAILCHIMP_DISABLE_QUEUE')) {
				$constants_used[] = 'MAILCHIMP_DISABLE_QUEUE';
			}

			if (defined( 'MAILCHIMP_USE_CURL')) {
				$constants_used[] = 'MAILCHIMP_USE_CURL';
			}

			if (defined( 'MAILCHIMP_REST_LOCALHOST')) {
				$constants_used[] = 'MAILCHIMP_REST_LOCALHOST';
			}

			if (defined( 'MAILCHIMP_REST_IP')) {
				$constants_used[] = 'MAILCHIMP_REST_IP';
			}
			
			$text = __('Mailchimp for Woocommerce','mailchimp-for-woocommerce').'<br/>'.
			'<p id="http-worker-deprecated-message">'.__('We dectected that this site has the following constants defined, likely at wp-config.php file' ,'mailchimp-for-woocommerce').': '.
			implode(' | ', $constants_used).'<br/>'.
			__('These constants are deprecated since Mailchimp for Woocommerce version 2.3. Please refer to the <a href="https://github.com/mailchimp/mc-woocommerce/wiki/">plugin official wiki</a> for further details.' ,'mailchimp-for-woocommerce').'</p>';
			
			// only print notice for deprecated constants, on mailchimp woocoomerce pages
			if ($pagenow == 'admin.php' && 'mailchimp-woocommerce' === $_GET['page']) {
				add_settings_error('mailchimp-woocommerce_notice', $this->plugin_name, $text, 'info');
			}
		}
		
	}

	/**
	 * Sets the Store Currency code on plugin options
	 * 
	 * @param string $code
	 * @return array $options 
	 */
	private function mailchimp_set_store_currency_code($code = null) {
		if (!isset($code)) {
			$code = get_woocommerce_currency();
		}
		$options = $this->getOptions();
		$options['woocommerce_settings_save_general'] = true;
		$options['store_currency_code'] = $code;
		update_option($this->plugin_name, $options);
		return $options;
	}

	/**
	 * Fired when woocommerce store settings are saved
	 * 
	 * @param string $code
	 * @return array $options 
	 */
	public function mailchimp_update_woo_settings() {
		$new_currency_code = null;

		if (isset($_POST['woo_multi_currency_params'])) {
			$new_currency_code = $_POST['currency_default'];
		}
		else if (isset($_POST['woocommerce_currency'])) {
			$new_currency_code = $_POST['woocommerce_currency'];
		}
		
		$data = $this->mailchimp_set_store_currency_code($new_currency_code);
		
		// sync the store with MC
		try {
			$store_created = $this->syncStore($data);
		}
		catch (Exception $e){
			mailchimp_log('store.sync@woo.update', 'Store cannot be synced', $e->getMessage());
			return false;
		}
		
		return $store_created;
	}

    /**
     * We were considering auto subscribing people that had just updated the plugin for the first time
     * after releasing the marketing status block, but decided against that. The admin user must subscribe specifically.
     *
     * @return array|WP_Error|null
     */
	protected function automatically_subscribe_admin_to_marketing()
    {
        $site_option = 'mailchimp_woocommerce_updated_marketing_status';

        // if we've already done this, just return null.
        if (get_site_option($site_option, false)) {
            return null;
        }

        // if we've already set this value to something other than NULL, that means they've already done this.
        if (($original_opt = $this->getData('comm.opt',null)) !== null) {
            return null;
        }

        // if they have not set the admin_email yet during plugin setup, we will just return null
        $admin_email = $this->getOption('admin_email');

        if (empty($admin_email)) {
            return null;
        }

        // tell the site options that we've already subscribed this person to marketing through the
        // plugin update process.
        update_site_option($site_option, true);

        try {
            // send the post to the mailchimp server
            return $this->mailchimp_set_communications_status_on_server(true, $admin_email);
        } catch (\Exception $e) {
            mailchimp_error("initial_marketing_status", $e->getMessage());
            return null;
        }
    }
	
	/**
	 * We need to do a tidy up function on the mailchimp_carts table to
	 * remove anything older than 30 days.
	 *
	 * Also if we don't have the configuration set, we need to create the table.
	 */
	protected function handle_abandoned_cart_table()
	{
		global $wpdb;

		if (get_site_option('mailchimp_woocommerce_db_mailchimp_carts', false)) {
			// need to tidy up the mailchimp_cart table and make sure we don't have anything older than 30 days old.
			$date = gmdate( 'Y-m-d H:i:s', strtotime(date ("Y-m-d") ."-30 days"));
			$sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}mailchimp_carts WHERE created_at <= %s", $date);
			$wpdb->query($sql);
		} else {

			// create the table for the first time now.
			$charset_collate = $wpdb->get_charset_collate();
			$table = "{$wpdb->prefix}mailchimp_carts";

			$sql = "CREATE TABLE IF NOT EXISTS $table (
				id VARCHAR (255) NOT NULL,
				email VARCHAR (100) NOT NULL,
				user_id INT (11) DEFAULT NULL,
                cart text NOT NULL,
                created_at datetime NOT NULL,
				PRIMARY KEY  (email)
				) $charset_collate;";

			if (($result = $wpdb->query($sql)) > 0) {
				update_site_option('mailchimp_woocommerce_db_mailchimp_carts', true);
			}
		}
	}

	/**
	 * @param $input
	 * @return array
	 */
	public function validate($input) {

		$active_tab = isset($input['mailchimp_active_tab']) ? $input['mailchimp_active_tab'] : null;

		if (empty($active_tab) && isset($input['woocommerce_settings_save_general']) && $input['woocommerce_settings_save_general']) {
			unset($input['woocommerce_settings_save_general']);
			$data['store_currency_code'] = (string) $input['store_currency_code'];
		}

		if (get_site_transient('mailchimp_disconnecting_store')) {
            return array(
                'active_tab' => 'api_key',
                'mailchimp_api_key' => null,
                'mailchimp_list' => null,
            );
        }

		switch ($active_tab) {

			case 'api_key':
				$data = $this->validatePostApiKey($input);
				break;

			case 'store_info':
				$data = $this->validatePostStoreInfo($input);
				break;

			case 'campaign_defaults' :
				$data = $this->validatePostCampaignDefaults($input);
				break;

			case 'newsletter_settings':
				$data = $this->validatePostNewsletterSettings($input);
				break;

			case 'sync':
				// case disconnect
				if ($this->is_disconnecting()) {
					// Disconnect store!
					if ($this->disconnect_store()) {
					    return array(
                            'active_tab' => 'api_key',
                            'mailchimp_api_key' => null,
                            'mailchimp_list' => null,
                        );
						add_settings_error('mailchimp_store_settings', '', __('Store Disconnected', 'mailchimp-for-woocommerce'), 'info');
					} else {
						$data['active_tab'] = 'sync';
						add_settings_error('mailchimp_store_settings', '', __('Store Disconnect Failed', 'mailchimp-for-woocommerce'), 'warning');
					}	
				}
				//case sync
				elseif ($this->is_resyncing()) {

					// remove all the pointers to be sure
					$service = new MailChimp_Service();
					$service->removePointers(true, true);
					$this->startSync();
					$this->showSyncStartedMessage();
					$this->setData('sync.config.resync', true);
				}
				break;

            case 'logs':

                if (isset($_POST['log_file']) && !empty($_POST['log_file'])) {
                    set_site_transient('mailchimp-woocommerce-view-log-file', $_POST['log_file'], 30);
                }
                
                $data = array(
                    'mailchimp_logging' => isset($input['mailchimp_logging']) ? $input['mailchimp_logging'] : 'none',
                );

                if (isset($_POST['mc_action']) && in_array($_POST['mc_action'], array('view_log', 'remove_log'))) {
                    $path = 'admin.php?page=mailchimp-woocommerce&tab=logs';
                    wp_redirect($path);
                    exit();
                }

                break;
		}

		// if no API is provided, check if the one saved on the database is still valid, ** only not if disconnect store is issued **.
		if (!$this->is_disconnecting() && !isset($input['mailchimp_api_key']) && $this->getOption('mailchimp_api_key')) {
			// set api key for validation
			$input['mailchimp_api_key'] = $this->getOption('mailchimp_api_key');
			$api_key_valid = $this->validatePostApiKey($input);
			
			// if there's no error, remove the api_ping_error from the db
			if (!$api_key_valid['api_ping_error'])
				$data['api_ping_error'] = $api_key_valid['api_ping_error'];
		}

		return (isset($data) && is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
	}

	/**
	 * STEP 1.
	 *
	 * Handle the 'api_key' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostApiKey($input)
	{
		$data = array(
			'mailchimp_api_key' => isset($input['mailchimp_api_key']) ? trim($input['mailchimp_api_key']) : false,
			'mailchimp_debugging' => isset($input['mailchimp_debugging']) ? $input['mailchimp_debugging'] : false,
			'mailchimp_account_info_id' => null,
			'mailchimp_account_info_username' => null,
		);

		$api = new MailChimp_WooCommerce_MailChimpApi($data['mailchimp_api_key']);

		try {
		    $profile = $api->ping(true, true);
            // tell our reporting system whether or not we had a valid ping.
            $this->setData('validation.api.ping', true);
            $data['active_tab'] = 'store_info';
            if (isset($profile) && is_array($profile) && array_key_exists('account_id', $profile)) {
                $data['mailchimp_account_info_id'] = $profile['account_id'];
                $data['mailchimp_account_info_username'] = $profile['username'];
            }
            $data['api_ping_error'] = false;
        } catch (Exception $e) {
            unset($data['mailchimp_api_key']);
            $data['active_tab'] = 'api_key';
            $data['api_ping_error'] = $e->getCode().' :: '.$e->getMessage().' on '.$e->getLine().' in '.$e->getFile();
            mailchimp_error('admin@validatePostApiKey', $e->getCode().' :: '.$e->getMessage().' on '.$e->getLine().' in '.$e->getFile());
            add_settings_error('mailchimp_store_settings', $e->getCode(), $e->getMessage());
            return $data;
        }

		return $data;
	}

	/**
     * Mailchimp OAuth connection start
     */
    public function mailchimp_woocommerce_ajax_oauth_start()
    {   
		$secret = uniqid();
        $args = array(
            'domain' => site_url(),
            'secret' => $secret,
        );

        $pload = array(
            'headers' => array( 
                'Content-type' => 'application/json',
            ),
            'body' => json_encode($args)
        );

        $response = wp_remote_post( 'https://woocommerce.mailchimpapp.com/api/start', $pload);
        if ($response['response']['code'] == 201 ){
			set_site_transient('mailchimp-woocommerce-oauth-secret', $secret, 60*60);
			wp_send_json_success($response);
        }
        else wp_send_json_error( $response );
        
    }

	/**
     * Mailchimp OAuth connection finish
     */
    public function mailchimp_woocommerce_ajax_oauth_finish()
    {   
        $args = array(
            'domain' => site_url(),
            'secret' => get_site_transient('mailchimp-woocommerce-oauth-secret'),
            'token' => $_POST['token']
        );

        $pload = array(
            'headers' => array( 
                'Content-type' => 'application/json',
            ),
            'body' => json_encode($args)
        );

        $response = wp_remote_post( 'https://woocommerce.mailchimpapp.com/api/finish', $pload);
        if ($response['response']['code'] == 200 ){
			delete_site_transient('mailchimp-woocommerce-oauth-secret');
            // save api_key? If yes, we can skip api key validation for validatePostApiKey();
            wp_send_json_success($response);
        }
        else wp_send_json_error( $response );
        
    }


	public function mailchimp_woocommerce_ajax_create_account_check_username () {
		$user = $_POST['username'];
		$response = wp_remote_get( 'https://woocommerce.mailchimpapp.com/api/usernames/available/' . $_POST['username']);
		$response_body = json_decode($response['body']);
		if ($response['response']['code'] == 200 && $response_body->success == true ){
			wp_send_json_success($response);
		}
		
		else if ($response['response']['code'] == 404 ){
			wp_send_json_error(array(
				'success' => false,
			));
		}

        else {
			$suggestion = wp_remote_get( 'https://woocommerce.mailchimpapp.com/api/usernames/suggestions/' . preg_replace('/[^A-Za-z0-9\-\@\.]/', '', $_POST['username']));
			$suggested_username = json_decode($suggestion['body'])->data;
			wp_send_json_error( array(
				'success' => false,
				'suggestion' => $suggested_username[0]
			));
		}
	}
	
	public function mailchimp_woocommerce_ajax_support_form() {
		$data = $_POST['data'];
		
		// try to figure out user IP address
		if ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
			$data['ip_address'] = '127.0.0.1';
		}
		else {
			$data['ip_address'] = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
		}

		$pload = array(
            'headers' => array( 
                'Content-type' => 'application/json',
            ),
			'body' => json_encode($data),
			'timeout'     => 30,
        );

		$response = wp_remote_post( 'https://woocommerce.mailchimpapp.com/api/support', $pload);
		$response_body = json_decode($response['body']);
		if ($response['response']['code'] == 200 && $response_body->success == true ){
			
			wp_send_json_success($response_body);
		}
		
		else if ($response['response']['code'] == 404 ){
			wp_send_json_error(array(
				'success' => false,
				'error' => $response
			));
		}

	}

	public function mailchimp_woocommerce_ajax_create_account_signup() {
		$data = $_POST['data'];
		
		// try to figure out user IP address
		if ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
			$data['ip_address'] = '127.0.0.1';
		}
		else {
			$data['ip_address'] = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
		}

		$pload = array(
            'headers' => array( 
                'Content-type' => 'application/json',
            ),
			'body' => json_encode($data),
			'timeout'     => 30,
        );
		

		$response = wp_remote_post( 'https://woocommerce.mailchimpapp.com/api/signup/', $pload);
		$response_body = json_decode($response['body']);
		if ($response['response']['code'] == 200 && $response_body->success == true ){
			
			wp_send_json_success($response_body);
		}
		
		else if ($response['response']['code'] == 404 ){
			wp_send_json_error(array(
				'success' => false,
			));
		}

        else {
			$suggestion = wp_remote_get( 'https://woocommerce.mailchimpapp.com/api/usernames/suggestions/' . $_POST['username']);
			$suggested_username = json_decode($suggestion['body'])->data;
			wp_send_json_error( array(
				'success' => false,
				'suggestion' => $suggested_username[0]
			));
		}
	}

	/**
	 * STEP 2.
	 *
	 * Handle the 'store_info' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostStoreInfo($input)
	{
		$data = $this->compileStoreInfoData($input);

		if (!$this->hasValidStoreInfo($data)) {

		    if ($this->hasInvalidStoreAddress($data)) {
		        $this->addInvalidAddressAlert();
            }

            if ($this->hasInvalidStorePhone($data)) {
		        $this->addInvalidPhoneAlert();
            }

            if ($this->hasInvalidStoreName($data)) {
		        $this->addInvalidStoreNameAlert();
            }

			$this->setData('validation.store_info', false);

            $data['active_tab'] = 'store_info';

			return $input;
		}

		// change communication status options
		$comm_opt = get_option('mailchimp-woocommerce-comm.opt', 0);
		$this->mailchimp_set_communications_status_on_server($comm_opt, $data['admin_email']);

		$this->setData('validation.store_info', true);

		if ($this->hasValidMailChimpList()) {
			// sync the store with MC
			try {
				$this->syncStore(array_merge($this->getOptions(), $data));
			}
			catch (Exception $e){
				$this->setData('validation.store_info', false);
				mailchimp_log('errors.store_info', 'Store cannot be synced :: ' . $e->getMessage());
				add_settings_error('mailchimp_store_info', '', __('Cannot create or update Store at Mailchimp.', 'mailchimp-for-woocommerce') . ' Mailchimp says: ' . $e->getMessage());
				return $data;
			}
		}
		
		$data['active_tab'] = 'campaign_defaults';
		$data['store_currency_code'] = get_woocommerce_currency();
		
		return $data;
	}

    /**
     * @param $input
     * @return array
     */
	protected function compileStoreInfoData($input)
    {	
		$checkbox = $this->getOption('mailchimp_permission_cap', 'check');

		// see if it's posted in the form.
		if (isset($input['mailchimp_permission_cap']) && !empty($input['mailchimp_permission_cap'])) {
			$checkbox = $input['mailchimp_permission_cap'];
		}
        return array(
            // store basics
            'store_name' => trim((isset($input['store_name']) ? $input['store_name'] : get_option('blogname'))),
            'store_street' => isset($input['store_street']) ? $input['store_street'] : false,
            'store_city' => isset($input['store_city']) ? $input['store_city'] : false,
            'store_state' => isset($input['store_state']) ? $input['store_state'] : false,
            'store_postal_code' => isset($input['store_postal_code']) ? $input['store_postal_code'] : false,
            'store_country' => isset($input['store_country']) ? $input['store_country'] : false,
            'store_phone' => isset($input['store_phone']) ? $input['store_phone'] : false,
            // locale info
            'store_locale' => isset($input['store_locale']) ? $input['store_locale'] : false,
			'store_timezone' => mailchimp_get_timezone(),
            'admin_email' => isset($input['admin_email']) && is_email($input['admin_email']) ? $input['admin_email'] : $this->getOption('admin_email', false),
			'mailchimp_permission_cap' => $checkbox,
        );
    }

    /**
     * @param array $data
     * @return array|bool
     */
	protected function hasInvalidStoreAddress($data)
    {
        $address_keys = array(
            'admin_email',
            'store_city',
            'store_state',
            'store_postal_code',
            'store_country',
            'store_street'
        );

        $invalid = array();
        foreach ($address_keys as $address_key) {
            if (empty($data[$address_key])) {
                $invalid[] = $address_key;
            }
        }
        return empty($invalid) ? false : $invalid;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasInvalidStorePhone($data)
    {
        if (empty($data['store_phone']) || strlen($data['store_phone']) <= 6) {
            return true;
        }

        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasInvalidStoreName($data)
    {
        if (empty($data['store_name'])) {
            return true;
        }
        return false;
    }

    /**
     *
     */
	protected function addInvalidAddressAlert()
    {
        add_settings_error('mailchimp_store_settings', '', __('As part of the Mailchimp Terms of Use, we require a contact email and a physical mailing address.', 'mailchimp-for-woocommerce'));
    }

    /**
     *
     */
    protected function addInvalidPhoneAlert()
    {
        add_settings_error('mailchimp_store_settings', '', __('As part of the Mailchimp Terms of Use, we require a valid phone number for your store.', 'mailchimp-for-woocommerce'));
    }

    /**
     *
     */
    protected function addInvalidStoreNameAlert()
    {
        add_settings_error('mailchimp_store_settings', '', __('Mailchimp for WooCommerce requires a Store Name to connect your store.', 'mailchimp-for-woocommerce'));
    }

	/**
	 * STEP 3.
	 *
	 * Handle the 'campaign_defaults' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostCampaignDefaults($input)
	{
		$data = array(
			'campaign_from_name' => isset($input['campaign_from_name']) ? $input['campaign_from_name'] : false,
			'campaign_from_email' => isset($input['campaign_from_email']) && is_email($input['campaign_from_email']) ? $input['campaign_from_email'] : false,
			'campaign_subject' => isset($input['campaign_subject']) ? $input['campaign_subject'] : get_option('blogname'),
			'campaign_language' => isset($input['campaign_language']) ? $input['campaign_language'] : 'en',
			'campaign_permission_reminder' => isset($input['campaign_permission_reminder']) ? $input['campaign_permission_reminder'] : sprintf(/* translators: %s - plugin name. */esc_html__( 'You were subscribed to the newsletter from %s', 'mailchimp-for-woocommerce' ),get_option('blogname')),
		);

		if (!$this->hasValidCampaignDefaults($data)) {
			$this->setData('validation.campaign_defaults', false);
			add_settings_error('mailchimp_list_settings', '', __('One or more fields were not updated', 'mailchimp-for-woocommerce'));
			return array('active_tab' => 'campaign_defaults');
		}

		$this->setData('validation.campaign_defaults', true);

        $data['active_tab'] = 'newsletter_settings';

        $list_id = mailchimp_get_list_id();

        if (!empty($list_id)) {
            $this->updateMailChimpList(array_merge($this->getOptions(), $data), $list_id);
        }

		return $data;
	}

	/**
	 * STEP 4.
	 *
	 * Handle the 'newsletter_settings' tab post.
	 *
	 * @param $input
	 * @return array
	 */
	protected function validatePostNewsletterSettings($input)
	{
		// default value.
		$checkbox = $this->getOption('mailchimp_checkbox_defaults', 'check');

		// see if it's posted in the form.
		if (isset($input['mailchimp_checkbox_defaults']) && !empty($input['mailchimp_checkbox_defaults'])) {
			$checkbox = $input['mailchimp_checkbox_defaults'];
		}
		$sanitized_tags = array_map("sanitize_text_field", explode(",", $input['mailchimp_user_tags']));

		$allowed_html = array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array()
			),
			'br' => array()
		);

		$data = array(
			'mailchimp_list' => isset($input['mailchimp_list']) ? $input['mailchimp_list'] : $this->getOption('mailchimp_list', ''),
			'newsletter_label' => (isset($input['newsletter_label']) && $input['newsletter_label'] != '') ? wp_kses($input['newsletter_label'], $allowed_html) : $this->getOption('newsletter_label', __('Subscribe to our newsletter', 'mailchimp-for-woocommerce')),
			'mailchimp_auto_subscribe' => isset($input['mailchimp_auto_subscribe']) ? (bool) $input['mailchimp_auto_subscribe'] : false,
			'mailchimp_checkbox_defaults' => $checkbox,
			'mailchimp_checkbox_action' => isset($input['mailchimp_checkbox_action']) ? $input['mailchimp_checkbox_action'] : $this->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form'),
			'mailchimp_user_tags' => isset($input['mailchimp_user_tags']) ? implode(",",$sanitized_tags) : $this->getOption('mailchimp_user_tags'),
            'mailchimp_product_image_key' => isset($input['mailchimp_product_image_key']) ? $input['mailchimp_product_image_key'] : 'medium',
        );
		
		//if we don't have any audience on the account, create one
		if ($data['mailchimp_list'] === 'create_new') {
			$data['mailchimp_list'] = $this->updateMailChimpList(array_merge($this->getOptions(), $data));
		}

		// as long as we have a list set, and it's currently in MC as a valid list, let's sync the store.
		if (!empty($data['mailchimp_list']) && $this->api()->hasList($data['mailchimp_list'])) {

            $this->setData('validation.newsletter_settings', true);

			// sync the store with MC
			try {
				$store_created = $this->syncStore(array_merge($this->getOptions(), $data));
			}
			catch (Exception $e){
				$this->setData('validation.newsletter_settings', false);
				mailchimp_log('errors.newsletter_settings', 'Store cannot be synced :: ' . $e->getMessage());
				add_settings_error('mailchimp_newsletter_settings', '', __('Cannot create or update Store at Mailchimp.', 'mailchimp-for-woocommerce') . ' Mailchimp says: ' . $e->getMessage());
				$data['active_tab'] = 'newsletter_settings';
				return $data;
			}

			// if there was already a store in Mailchimp, use the list ID from Mailchimp
			if ($this->swapped_list_id) {
				$data['mailchimp_list'] = $this->swapped_list_id;
			}

			// start the sync automatically if the sync is false
			if ($store_created && ((bool) $this->getData('sync.started_at', false) === false)) {
				// tell the next page view to start the sync with a transient since the data isn't available yet
                set_site_transient('mailchimp_woocommerce_start_sync', microtime(), 300);

                $this->showSyncStartedMessage();
			}
			
            $data['active_tab'] = 'sync';

            return $data;
		}

		$this->setData('validation.newsletter_settings', false);
		
		add_settings_error('mailchimp_newsletter_settings', '', __('One or more fields were not updated', 'mailchimp-for-woocommerce'));

        $data['active_tab'] = 'newsletter_settings';

        return $data;
	}



	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidStoreInfo($data = null)
	{
		return $this->validateOptions(array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'store_phone',
			'store_locale',
			'store_phone','mailchimp_permission_cap',
		), $data);
	}

	/**
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidCampaignDefaults($data = null)
	{
		return $this->validateOptions(array(
			'campaign_from_name', 'campaign_from_email', 'campaign_subject', 'campaign_language',
			'campaign_permission_reminder'
		), $data);
	}

    /**
     * @param null $data
     * @param bool $throw_if_not_valid
     * @return array|bool|mixed|null|object
     * @throws Exception
     */
	public function hasValidApiKey($data = null, $throw_if_not_valid = false)
	{
		if (!$this->validateOptions(array('mailchimp_api_key'), $data)) {
			return false;
		}

		if (($pinged = $this->getCached('api-ping-check', null)) === null) {
            if (($pinged = $this->api()->ping(false, $throw_if_not_valid === true))) {
                $this->setCached('api-ping-check', true, 120);
            }
		}

		return $pinged;
	}

    /**
     * @return array|bool|mixed|null|object
     * @throws Exception
     * @throws MailChimp_WooCommerce_Error
     * @throws MailChimp_WooCommerce_ServerError
     */
	public function hasValidMailChimpList()
	{
		if (!$this->hasValidApiKey()) {
			add_settings_error('mailchimp_api_key', '', __('You must supply your Mailchimp API key to pull the audiences.', 'mailchimp-for-woocommerce'));
			return false;
		}

		if (!($this->validateOptions(array('mailchimp_list')))) {
			return $this->api()->getLists(true);
		}

		return $this->api()->hasList($this->getOption('mailchimp_list'));
	}


    /**
     * @return array|bool|mixed|null|object
     * @throws Exception
     */
	public function getAccountDetails()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		try {
			if (($account = $this->getCached('api-account-name', null)) === null) {
				if (($account = $this->api()->getProfile())) {
					$this->setCached('api-account-name', $account, 120);
				}
			}
			return $account;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @return array|bool
	 */
	public function getMailChimpLists()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		try {
			if (($pinged = $this->getCached('api-lists', null)) === null) {
				$pinged = $this->api()->getLists(true);
				if ($pinged) {
					$this->setCached('api-lists', $pinged, 120);
				}
				return $pinged;
			}
			return $pinged;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @return array|bool
	 */
	public function getListName()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!($list_id = $this->getOption('mailchimp_list', false))) {
			return false;
		}

		try {
			if (($lists = $this->getCached('api-lists', null)) === null) {
				$lists = $this->api()->getLists(true);
				if ($lists) {
					$this->setCached('api-lists', $lists, 120);
				}
			}

			return array_key_exists($list_id, $lists) ? $lists[$list_id] : false;
		} catch (\Exception $e) {
			return array();
		}
	}

	/**
	 * @return bool
	 */
	public function isReadyForSync()
	{
		if (!$this->hasValidApiKey()) {
			return false;
		}

		if (!$this->getOption('mailchimp_list', false)) {
			return false;
		}

		if (!$this->api()->hasList($this->getOption('mailchimp_list'))) {
			return false;
		}

		if (!$this->api()->getStore($this->getUniqueStoreID())) {
			return false;
		}

		return true;
	}

    public function inject_sync_ajax_call() { global $wp; ?>
        <script type="text/javascript" >
            jQuery(document).ready(function($) {
                var endpoint = '<?php echo MailChimp_WooCommerce_Rest_Api::url('sync/stats'); ?>';
                var on_sync_tab = '<?php echo (mailchimp_check_if_on_sync_tab() ? 'yes' : 'no')?>';
                var sync_status = '<?php echo ((mailchimp_has_started_syncing() && !mailchimp_is_done_syncing()) ? 'historical' : 'current') ?>';
				
				var promo_rulesProgress = 0;
				var orderProgress = 0;
				var productProgress = 0;

                if (on_sync_tab === 'yes') {
                    var call_mailchimp_for_stats = function (showSpinner = false) {
						if (showSpinner ) jQuery('#mailchimp_last_updated').next('.spinner').css('visibility', 'visible');
                        jQuery.get(endpoint, function(response) {
                            if (response.success) {
								
                                // if the response is now finished - but the original sync status was "historical"
                                // perform a page refresh because we need the re-sync buttons to show up again.
                                if (response.has_finished === true && sync_status === 'historical') {
                                	return document.location.reload(true);
                                }
								
								if (response.has_started && !response.has_finished) {
									jQuery('.sync-stats-audience .sync-loader').css('visibility', 'visible');
									jQuery('.sync-stats-audience .card_count').hide();
									
									jQuery('.sync-stats-store .card_count').hide();

									jQuery('.sync-stats-card .progress-bar-wrapper').show();
									
									if (response.promo_rules_page == 'complete') {
										promo_rulesProgress = 100;
										jQuery('#mailchimp_promo_rules_count').html(response.promo_rules_in_mailchimp.toLocaleString(undefined, {maximumFractionDigits: 0})).css('display', 'inline-block');
										jQuery('.sync-stats-card.promo_rules .progress-bar-wrapper').hide();
									} else {
										if (response.promo_rules_in_mailchimp == 0) {
											promo_rulesProgress = 0;
											promo_rulesPartial = "0 / " + response.promo_rules_in_store;
										} else {
											promo_rulesProgress = response.promo_rules_in_mailchimp / response.promo_rules_in_store * 100
											promo_rulesPartial = response.promo_rules_in_mailchimp + " / " + response.promo_rules_in_store;
										}
										if (promo_rulesProgress > 100) promo_rulesProgress = 100;
										jQuery('.mailchimp_promo_rules_count_partial').html(promo_rulesPartial);
									}
									jQuery('.sync-stats-card.promo_rules .progress-bar').width(promo_rulesProgress+"%");

									if (response.products_page == 'complete') {
										productsProgress = 100;
										jQuery('#mailchimp_product_count').html(response.products_in_mailchimp.toLocaleString(undefined, {maximumFractionDigits: 0})).css('display', 'inline-block');
										jQuery('.sync-stats-card.products .progress-bar-wrapper').hide();
									} else {
										if (response.products_in_mailchimp == 0) {
											productsProgress = 0;
											productsPartial = "0 / " + response.products_in_store;
										} else {
											productsProgress = response.products_in_mailchimp / response.products_in_store * 100
											productsPartial = response.products_in_mailchimp + " / " + response.products_in_store;
										}
										if (productsProgress > 100) productsProgress = 100;
										jQuery('.mailchimp_product_count_partial').html(productsPartial);
									}
									jQuery('.sync-stats-card.products .progress-bar').width(productsProgress+"%");

									if (response.orders_page == 'complete') {
										ordersProgress = 100;
										jQuery('#mailchimp_order_count').html(response.orders_in_mailchimp.toLocaleString(undefined, {maximumFractionDigits: 0})).css('display', 'inline-block');
										jQuery('.sync-stats-card.orders .progress-bar-wrapper').hide();
									} else {
										if (response.orders_in_mailchimp == 0) {
											ordersProgress = 0;
											ordersPartial = "0 / " + response.orders_in_store;
										} else {
											ordersProgress = response.orders_in_mailchimp / response.orders_in_store * 100
											ordersPartial = response.orders_in_mailchimp + " / " + response.orders_in_store;
										}
										if (ordersProgress > 100) ordersProgress = 100;
										jQuery('.mailchimp_order_count_partial').html(ordersPartial);
									}
									jQuery('.sync-stats-card.orders .progress-bar').width(ordersProgress+"%");

									jQuery('#mailchimp_last_updated').html(response.date);

									// only call status again if sync is running.
									setTimeout(function() {
										call_mailchimp_for_stats(true);
									}, 10000);
									jQuery('#mailchimp_last_updated').next('.spinner').css('visibility', 'hidden');
								}
								else {
									jQuery('#mailchimp_last_updated').next('.spinner').css('visibility', 'hidden');	
									jQuery('.sync-stats-card .progress-bar-wrapper').hide();
									jQuery('#mailchimp_order_count').css('display', 'inline-block');
									jQuery('#mailchimp_product_count').css('display', 'inline-block');
									jQuery('#mailchimp_promo_rules_count').css('display', 'inline-block');
								}
                            }
                        });
                    };
					
					call_mailchimp_for_stats();
                }
            });
        </script> <?php
    }

	/**
	 * @param null|array $data
	 * @return bool|string
	 */
	private function updateMailChimpList($data = null, $list_id = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

		$required = array(
			'store_name', 'store_street', 'store_city', 'store_state',
			'store_postal_code', 'store_country', 'campaign_from_name',
			'campaign_from_email', 'campaign_subject', 'campaign_permission_reminder',
		);

		foreach ($required as $requirement) {
			if (!isset($data[$requirement]) || empty($data[$requirement])) {
			    mailchimp_log('admin', 'does not have enough data to update the mailchimp list.');
				return false;
			}
		}

		$submission = new MailChimp_WooCommerce_CreateListSubmission();

		// allow the subscribers to choose preferred email type (html or text).
		$submission->setEmailTypeOption(true);

        // set the store name if the list id is not set.
		if (empty($list_id)) {
            $submission->setName($data['store_name']);
			if (isset($data['admin_email']) && !empty($data['admin_email'])) {
				$submission->setNotifyOnSubscribe($data['admin_email']);
				$submission->setNotifyOnUnSubscribe($data['admin_email']);
			}
        }

		// set the campaign defaults
		$submission->setCampaignDefaults(
			$data['campaign_from_name'],
			$data['campaign_from_email'],
			$data['campaign_subject'],
			$data['campaign_language']
		);

		// set the permission reminder message.
		$submission->setPermissionReminder($data['campaign_permission_reminder']);

		$submission->setContact($this->address($data));

		try {
			$submission->setDoi(mailchimp_list_has_double_optin(true));
		}
		catch (\Exception $e) {
			add_settings_error('list_sync_error', '', __('Cannot create or update List at Mailchimp.', 'mailchimp-for-woocommerce') . ' ' . $e->getMessage() . ' ' . __('Please retry.', 'mailchimp-for-woocommerce'));
			$this->setData('errors.mailchimp_list', $e->getMessage());
			return false;
		}
		
		// let's turn this on for debugging purposes.
		mailchimp_debug('admin', 'list info submission', array('submission' => print_r($submission->getSubmission(), true)));

		try {
			$response = !empty($list_id) ?
                $this->api()->updateList($list_id, $submission) :
                $this->api()->createList($submission);

			if (empty($list_id)) {
			    $list_id = array_key_exists('id', $response) ? $response['id'] : false;
            }

			$this->setData('errors.mailchimp_list', false);

			return $list_id;

		} catch (MailChimp_WooCommerce_Error $e) {
            mailchimp_error('admin', $e->getMessage());
			$this->setData('errors.mailchimp_list', $e->getMessage());
			return false;
		}
	}

	/**
	 * @param null $data
	 * @return bool
	 */
	private function syncStore($data = null)
	{
		if (empty($data)) {
			$data = $this->getOptions();
		}

        $list_id = $this->array_get($data, 'mailchimp_list', false);
        $site_url = $this->getUniqueStoreID();

		if (empty($list_id) || empty($site_url)) {
		    return false;
        }

		$new = false;

		if (!($store = $this->api()->getStore($site_url))) {
			$new = true;
			$store = new MailChimp_WooCommerce_Store();
		}

		$call = $new ? 'addStore' : 'updateStore';
		$time_key = $new ? 'store_created_at' : 'store_updated_at';

		$store->setId($site_url);
		$store->setPlatform('woocommerce');

		// set the locale data
		$store->setPrimaryLocale($this->array_get($data, 'store_locale', 'en'));
		$store->setTimezone(mailchimp_get_timezone());
		$store->setCurrencyCode($this->array_get($data, 'store_currency_code', 'USD'));
		$store->setMoneyFormat($store->getCurrencyCode());

		// set the basics
		$store->setName($this->array_get($data, 'store_name'));
		$store->setDomain(get_option('siteurl'));

        // don't know why we did this before
        //$store->setEmailAddress($this->array_get($data, 'campaign_from_email'));
        $store->setEmailAddress($this->array_get($data, 'admin_email'));

		$store->setAddress($this->address($data));
		$store->setPhone($this->array_get($data, 'store_phone'));
		$store->setListId($list_id);

		try {
            mailchimp_log('sync_store', 'posting data', array(
                'store_post' => $store->toArray(),
            ));

			// let's create a new store for this user through the API
			$this->api()->$call($store, false);

			// apply extra meta for store created at
			$this->setData('errors.store_info', false);
			$this->setData($time_key, time());

			// on a new store push, we need to make sure we save the site script into a local variable.
            mailchimp_update_connected_site_script();

			// we need to update the list again with the campaign defaults
			$this->updateMailChimpList(null, $list_id);

			return true;

		} catch (\Exception $e) {
			if (mailchimp_string_contains($e->getMessage(),'woocommerce already exists in the account' )) {
			    // retrieve Mailchimp store using domain
				$stores = $this->api()->stores();
				//iterate thru stores, find correct store ID and save it to db
				foreach ($stores as $mc_store) {
					if ($mc_store->getDomain() === $store->getDomain() && $store->getPlatform() == "woocommerce") {
						update_option('mailchimp-woocommerce-store_id', $mc_store->getId(), 'yes');
						
						// update the store with the previous listID
						$store->setListId($mc_store->getListId());
						$store->setId($mc_store->getId());

						$this->swapped_list_id = $mc_store->getListId();
						$this->swapped_store_id = $mc_store->getId();

						// check if list id is the same, if not, throw error saying that there's already a store synched to a list, so we can't proceed.
						
						if ($this->api()->updateStore($store)) {
							return true;
						}
					}
				}
			}
			$this->setData('errors.store_info', $e->getMessage());
			throw($e);
		}

		return false;
	}

	/**
	 * @param array $data
	 * @return MailChimp_WooCommerce_Address
	 */
	private function address(array $data)
	{
		$address = new MailChimp_WooCommerce_Address();

		if (isset($data['store_street']) && $data['store_street']) {
			$address->setAddress1($data['store_street']);
		}

		if (isset($data['store_city']) && $data['store_city']) {
			$address->setCity($data['store_city']);
		}

		if (isset($data['store_state']) && $data['store_state']) {
			$address->setProvince($data['store_state']);
		}

		if (isset($data['store_country']) && $data['store_country']) {
			$address->setCountry($data['store_country']);
		}

		if (isset($data['store_postal_code']) && $data['store_postal_code']) {
			$address->setPostalCode($data['store_postal_code']);
		}

		if (isset($data['store_name']) && $data['store_name']) {
			$address->setCompany($data['store_name']);
		}

		if (isset($data['store_phone']) && $data['store_phone']) {
			$address->setPhone($data['store_phone']);
		}
		
		$woo_countries = new WC_Countries();
		$address->setCountryCode($woo_countries->get_base_country());

		return $address;
	}

	/**
	 * @param array $required
	 * @param null $options
	 * @return bool
	 */
	private function validateOptions(array $required, $options = null)
	{
		$options = is_array($options) ? $options : $this->getOptions();

		foreach ($required as $requirement) {
			if (!isset($options[$requirement]) || empty($options[$requirement])) {
				return false;
			}
		}

		return true;
	}

    /**
     * Start the sync
     */
	public function startSync()
	{
	    // delete the transient so this only happens one time.
	    delete_site_transient('mailchimp_woocommerce_start_sync');

		$full_sync = new MailChimp_WooCommerce_Process_Full_Sync_Manager();
		
		// make sure the storeeId saved on DB is the same on Mailchimp
		try {
			$this->syncStore();
		}
		catch (\Exception $e) {
			mailchimp_log('error.sync', 'Store cannot be synced :: ' . $e->getMessage());
			add_settings_error('mailchimp_sync_error', '', __('Cannot create or update Store at Mailchimp.', 'mailchimp-for-woocommerce') . ' Mailchimp says: ' . $e->getMessage());
			return false;
		}

        // tell Mailchimp that we're syncing
		$full_sync->start_sync();
		
        // enqueue sync manager
		as_enqueue_async_action( 'MailChimp_WooCommerce_Process_Full_Sync_Manager', array(), 'mc-woocommerce' );
	}

	/**
	 * Show the sync started message right when they sync things.
	 */
	private function showSyncStartedMessage()
	{
		$text = '<b>' . __('Starting the sync process...', 'mailchimp-for-woocommerce').'</b><br/>'.
			'<p id="sync-status-message">'.
			__('The plugin has started the initial sync with your store, and the process will work in the background automatically.', 'mailchimp-for-woocommerce') .
			' ' .
            __('Sometimes the sync can take a while, especially on sites with lots of orders and/or products. It is safe to navigate away from this screen while it is running.', 'mailchimp-for-woocommerce') .
            '</p>';
		add_settings_error('mailchimp-woocommerce_notice', $this->plugin_name, $text, 'success');
	}

	/**
	 * Show the review banner.
	 */
	private function mailchimp_show_initial_sync_message()
	{
	    try {
            $order_count = mailchimp_get_api()->getOrderCount(mailchimp_get_store_id());
        } catch (\Exception $e) {
	        $order_count = mailchimp_get_order_count();
        }

		$text = '<b>' . __('Your store is synced with Mailchimp!', 'mailchimp-for-woocommerce').'</b></br>'.
		'<p id="sync-status-message">'.
			/* translators: %1$s: Number of synced orders %2$s: Audience name */	
			sprintf(__('We\'ve successfully synced %1$s orders to your Audience %2$s, that\'s awesome!', 'mailchimp-for-woocommerce'),
                $order_count,
				$this->getListName()
			).
		'</p>'.

		'<p id="sync-status-message">'.
			/* translators: %s - Wordpress.org plugin review URL. */	
			sprintf(wp_kses( __( 'Could you please do us a favor and leave the plugin a 5-star <a href=%s target=\'_blank\'>rating on Wordpress.org</a>? It helps our community know that we\'re working hard to make it better each day.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target' => '_blank' ) ) ), esc_url( 'https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/' ) ).
		'</p>'.
		'<a style="display:inline align-right" class="button" href="https://wordpress.org/support/plugin/mailchimp-for-woocommerce/reviews/" target=_blank>'.
			esc_html__('Leave a Review', 'mailchimp-for-woocommerce').
        '</a>';
		
		add_settings_error('mailchimp-woocommerce_notice', $this->plugin_name.'-initial-sync-end', $text, 'success');
	}

	/**
	 * set Communications status via sync page.
	 */
	public function mailchimp_woocommerce_communication_status() {
		$original_opt = $this->getData('comm.opt',0);
		$opt = $_POST['opt'];
		$admin_email = $this->getOptions()['admin_email'];

		mailchimp_debug('communication_status', "setting to {$opt}");

		// try to set the info on the server
		// post to communications api
		$response = $this->mailchimp_set_communications_status_on_server($opt, $admin_email);
		
		// if success, set internal option to check for opt and display on sync page
		if ($response['response']['code'] == 200) {
			$response_body = json_decode($response['body']);
			if (isset($response_body) && $response_body->success == true) {
				$this->setData('comm.opt', $opt);
				wp_send_json_success(__('Saved', 'mailchimp-for-woocommerce'));	
			}
		}
		else {
			//if error, keep option to original value 
			wp_send_json_error(array('error' => __('Error setting communications status', 'mailchimp-for-woocommerce'), 'opt' => $original_opt));	
		}
		
		wp_die();
	}
	
	/**
	 * set Communications box status.
	 */
	public function mailchimp_set_communications_status_on_server($opt, $admin_email, $remove = false) {
		$env = mailchimp_environment_variables();
		$audience = !empty(mailchimp_get_list_id()) ? 1 : 0;
		$synced = get_option('mailchimp-woocommerce-sync.completed_at') > 0 ? 1 : 0;
		
		$post_data = array(
			'store_id' => mailchimp_get_store_id(),
			'email' => $admin_email,
			'domain' => site_url(),
			'marketing_status' => $opt,
			'audience' => $audience,
			'synced' => $synced,
			'plugin_version' => "MailChimp for WooCommerce/{$env->version}; PHP/{$env->php_version}; WordPress/{$env->wp_version}; Woo/{$env->wc_version};",
			
		);
		if ($remove) {
			$post_data['remove_email'] = true;
		}

		$route = "https://woocommerce.mailchimpapp.com/api/opt_in_status";
		
		return wp_remote_post(esc_url_raw($route), array(
			'timeout'   => 12,
			'cache-control' => 'no-cache',
            'blocking'  => true,
            'method'      => 'POST',
            'data_format' => 'body',
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode($post_data),
		));
	}

	public function mailchimp_woocommerce_ajax_load_log_file() {
		if (isset($_POST['log_file']) && !empty($_POST['log_file'])) {
			$requested_log_file = $_POST['log_file'];
		}
		else {
			return wp_send_json_error(  __('No log file provided', 'mailchimp-for-woocommerce'));
		}
		
		$files  = defined('WC_LOG_DIR') ? @scandir( WC_LOG_DIR ) : array();

		$logs = array();
		if (!empty($files)) {
			foreach (array_reverse($files) as $key => $value) {
				if (!in_array( $value, array( '.', '..' ))) {
					if (!is_dir($value) && mailchimp_string_contains($value, 'mailchimp_woocommerce')) {
						$logs[sanitize_title($value)] = $value;
					}
				}
			}
		}

		if (!empty($requested_log_file) && isset($logs[sanitize_title($requested_log_file)])) {
			$viewed_log = $logs[sanitize_title($requested_log_file)];
		} else {
			return wp_send_json_error( __('Error loading log file contents', 'mailchimp-for-woocommerce'));
		}

		return wp_send_json_success( esc_html( file_get_contents( WC_LOG_DIR . $viewed_log ) ) );
		
	}

}
