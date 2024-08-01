<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

/** Grab plugin admin object */
$handler = MailChimp_WooCommerce_Admin::connect();

/** Grab all options for this particular tab we're viewing. */

$options = \Mailchimp_Woocommerce_DB_Helpers::get_option( $this->plugin_name, array() );

/** Verify that the nonce is correct for the GET and POST variables. */

$active_breadcrumb = isset( $_GET['breadcrumb'] ) ?
	esc_attr( sanitize_key( $_GET['breadcrumb'] ) ) :
	( isset( $options['breadcrumb'] ) ? esc_attr( wp_unslash( $options['breadcrumb'] ) ) : '' );

// Active tab for confirmation breadcrumb
$active_tab = isset( $_GET['tab'] ) ?
	esc_attr( sanitize_key( $_GET['tab'] ) ) :
	( isset( $options['active_tab'] ) ? esc_attr( wp_unslash( $options['active_tab'] ) ) : 'api_key' );

$mc_configured 		= mailchimp_is_configured();
$is_mailchimp_post 	= isset( $_POST['mailchimp_woocommerce_settings_hidden'] ) && check_admin_referer( 'mailchimp_woocommerce_options', 'mailchimp_woocommerce_nonce' ) && strtolower( esc_attr( sanitize_key( $_POST['mailchimp_woocommerce_settings_hidden'] ) ) ) === 'y';
$is_confirmation 	= isset( $_GET['resync'] ) ? ( esc_attr( sanitize_key( $_GET['resync'] ) ) === '1' ) : false;
/**  If we have a transient set to start the sync on this page view, initiate it now that the values have been saved. */

if ($mc_configured && ! $is_confirmation && (bool) \Mailchimp_Woocommerce_DB_Helpers::get_transient( 'mailchimp_woocommerce_start_sync' ) ) {
	$is_confirmation 		= true;
}

$has_valid_api_key        	= false;
$allow_new_list           	= true;
$only_one_list            	= false;
$clicked_sync_button      	= $mc_configured && $is_mailchimp_post && MC_WC_OVERVIEW_TAB === $active_tab;
$has_api_error            	= isset( $options['api_ping_error'] ) && ! empty( $options['api_ping_error'] ) ? $options['api_ping_error'] : null;
$audience_name 				= $handler->getListName() ? $handler->getListName() : '';
$account_name               = $handler->getAccountName();
$store_name                 = get_option( 'blogname' );

// only do this if we haven't selected an audience.
if ( isset( $options['mailchimp_api_key'] ) ) {
	try {
		if ( $handler->hasValidApiKey( null, true ) ) {
			$has_valid_api_key = true;

			/**  If we don't have a valid api key we need to redirect back to the 'api_key' tab. */
			$mailchimp_lists = $handler->getMailChimpLists();

			if ( is_array( $mailchimp_lists ) ) {
				$allow_new_list           	= false;
				$only_one_list            	= count( $mailchimp_lists ) === 1;
				if (empty($audience_name)) {
					$audience_name 				= $only_one_list ? reset($mailchimp_lists) : '';
				}
			}

			/** Only display this button if the data is not syncing and we have a valid api key */
			if ( (bool) $this->getData( 'sync.started_at', false ) ) {
				$is_confirmation = true;
			}

			if (!$active_breadcrumb) {
				if ($is_confirmation ) {
					$active_breadcrumb = MC_WC_CONFIRMATION;
				} else {
					$active_breadcrumb = MC_WC_REVIEW_SYNC_SETTINGS;
				}
			}
		}
	} catch ( Exception $e ) {
		if (mailchimp_string_contains($e->getMessage(), array('API key', 'User Disabled'))) {
			$active_breadcrumb    = MC_WC_CONNECT_ACCOUNTS;
            $active_tab = 'api_key';
			$is_confirmation = false;
			$has_api_error = "This Mailchimp API key has been disabled, please flush any object caches you may be using and re-connect.";
		} else {
			$has_api_error = $e->getMessage() . ' on ' . $e->getLine() . ' in ' . $e->getFile();
		}
	}
} else {
	$active_breadcrumb = MC_WC_CONNECT_ACCOUNTS;
}

if (MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb && ! $has_valid_api_key){
	$active_breadcrumb = MC_WC_CONNECT_ACCOUNTS;
}

if ((MC_WC_CONFIRMATION === $active_breadcrumb && ! $is_confirmation)) {
	if ($has_valid_api_key && MC_WC_CONNECT_ACCOUNTS !== $active_breadcrumb) {
		$active_breadcrumb = MC_WC_REVIEW_SYNC_SETTINGS;
	}
}
$promo_active = false;
?>

<div class="mc-wc-settings-wrapper woocommerce <?php echo $active_breadcrumb; ?>">
	<h2 class="mc-wc-settings-title">
        <?php echo __( 'Mailchimp for WooCommerce', 'mailchimp-for-woocommerce' ); ?>
	</h2>
	<form id="mailchimp_woocommerce_options" method="post" name="cleanup_options" action="options.php">
		<?php wp_nonce_field( 'mailchimp_woocommerce_options', 'mailchimp_woocommerce_nonce' ); ?>
		<div class="mc-wc-setting-header">
			<?php if ( MC_WC_CONFIRMATION !== $active_breadcrumb && ! $is_confirmation): ?>
				<div class="mc-wc-breadcrumbs">
					<ul class="mc-wc-breadcrumbs-wrapper">
						<li class="<?php echo ( MC_WC_CONNECT_ACCOUNTS === $active_breadcrumb ) ? 'current' : 'mc-wc-breadcrumb-link'; ?> <?php echo ($has_valid_api_key ? 'mc-wc-breadcrumb-nextable' : ''); ?>">
							<?php if (MC_WC_CONNECT_ACCOUNTS === $active_breadcrumb): ?>
								<span class="mc-wc-breadcrumb-text"><?php echo __('Connect accounts', 'mailchimp-for-woocommerce'); ?></span>
							<?php else: ?>
								<span class="mc-wc-breadcrumb-text a"><?php echo __('Connect accounts', 'mailchimp-for-woocommerce'); ?></span>
							<?php endif; ?>
						</li>
						<li class="<?php echo ( MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb && $has_valid_api_key) ? 'current' : (!$has_valid_api_key ? 'disabled' : 'mc-wc-breadcrumb-link'); ?> <?php echo ($is_confirmation ? 'mc-wc-breadcrumb-nextable' : ''); ?>">
							<?php if (MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb || !$has_valid_api_key): ?>
								<span class="mc-wc-breadcrumb-text"><?php echo __('Review sync settings', 'mailchimp-for-woocommerce'); ?></span>
							<?php else: ?>
								<span class="mc-wc-breadcrumb-text a"><?php echo __('Review sync settings', 'mailchimp-for-woocommerce'); ?></span>
							<?php endif; ?>
						</li>
						<li class="<?php echo ( MC_WC_CONFIRMATION === $active_breadcrumb ) ? 'current' : ($is_confirmation ? 'mc-wc-breadcrumb-link' : 'disabled') ; ?>">
							<?php if (MC_WC_CONFIRMATION === $active_breadcrumb || !$is_confirmation): ?>
								<span class="mc-wc-breadcrumb-text"><?php echo __('Confirmation', 'mailchimp-for-woocommerce'); ?></span>
							<?php else: ?>
								<span class="mc-wc-breadcrumb-text a"><?php echo __('Confirmation', 'mailchimp-for-woocommerce'); ?></span>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			<?php endif; ?>

			<!-- Banner -->
			<?php if ( MC_WC_CONNECT_ACCOUNTS === $active_breadcrumb) : ?>
				<?php Mailchimp_Woocommerce_Event::track('connect_accounts:view_screen', new DateTime()); ?>
				<?php include_once 'connect-accounts/header.php'; ?>
      <?php elseif (MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb && $has_valid_api_key): ?>
				<?php Mailchimp_Woocommerce_Event::track('review_settings:view_screen', new DateTime()); ?>
				<?php include_once 'review-sync-settings/header.php'; ?>
			<?php elseif (MC_WC_CONFIRMATION === $active_breadcrumb && $is_confirmation): ?>
				<?php include_once 'confirmation/header.php'; ?>
			<?php else: ?>
				<?php include_once 'connect-accounts/header.php'; ?>
			<?php endif; ?>
		</div>

		<!-- Notifications -->
		<?php if (! $is_confirmation): ?>
		<div class="notices-content-wrapper">
			<?php
				$settings_errors = get_settings_errors();
                // ignore the settings saved banner
			if (( isset( $settings_errors[0] ) && 'success' !== $settings_errors[0]['type'] ) && strtolower((string) $settings_errors[0]['message']) !== 'settings saved.') {
				echo wp_kses_post( mailchimp_settings_errors() );
			}
			?>
		</div>
		<?php endif; ?>

		<?php if ( ! defined( 'PHP_VERSION_ID' ) || ( PHP_VERSION_ID < 70000 ) ) : ?>
			<div data-dismissible="notice-php-version" class="error notice notice-error">
				<p><?php esc_html_e( 'Mailchimp says: Please upgrade your PHP version to a minimum of 7.0', 'mailchimp-for-woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $has_api_error ) ) : ?>
			<div data-dismissible="notice-api-error" class="error notice notice-error is-dismissible">
				<p>
					<?php esc_html_e( 'Mailchimp says: API Request Error', 'mailchimp-for-woocommerce' ); ?>
					<?php echo ' ' . esc_html( $has_api_error ); ?>
				</p>
			</div>
		<?php endif; ?>
		<div class="box">
			<input type="hidden" name="mailchimp_woocommerce_settings_hidden" value="Y">
			<?php
			if ( ! $clicked_sync_button ) {
				settings_fields( $this->plugin_name );
				do_settings_sections( $this->plugin_name );
				include 'tabs/notices.php';
			}
			?>
		</div>
		<!-- End notification -->

		<!-- Content -->
		<input type="hidden" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_active_breadcrumb]" value="<?php echo esc_attr( $active_breadcrumb ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_active_tab]" value="<?php echo esc_attr( $active_tab ); ?>"/>
		<?php if (MC_WC_CONNECT_ACCOUNTS !== $active_breadcrumb): ?>
		<div class="mc-wc-setting-content">
			<?php if ( MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb && $has_valid_api_key): ?>
				<?php include_once 'review-sync-settings/content.php'; ?>

			<?php elseif ( MC_WC_CONFIRMATION === $active_breadcrumb && $is_confirmation): ?>
				<?php include_once 'confirmation/content.php'; ?>
			<?php endif;?>
		</div>

		<!-- Button footer -->
		<div class="mc-wc-setting-footer-buttons">
			<?php if ( MC_WC_REVIEW_SYNC_SETTINGS === $active_breadcrumb && $has_valid_api_key): ?>
				<input type="submit" name="mailchimp_submit" class="mc-wc-btn mc-wc-btn-primary" data-position="bottom" value="<?php esc_html_e('Sync now', 'mailchimp-for-woocommerce'); ?>" />
			<?php endif;?>
		</div>
		<?php endif; ?>

        <?php if($promo_active): ?>
        <div class="promo-disclaimer">
            *24X ROI Standard Plan: Based on all e-commerce revenue attributable to Standard plan users’ Mailchimp campaigns from April 2023 to April 2024.
        </div>
        <?php endif; ?>
	</form>
</div>
