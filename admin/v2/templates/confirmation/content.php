<?php
/**
 * Confirmation content template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

$active_tab = isset( $_GET['tab'] ) ? esc_attr( sanitize_key( $_GET['tab'] ) ) : MC_WC_OVERVIEW_TAB;

?>
<div class="mc-wc-confirmation-settings-content">
	<div class="mc-wc-tab-page">
		<div class="mc-wc-tab-page-wrapper">
			<div class="mc-wc-tab-buttons">
				<ul class="mc-wc-tab-buttons-wrapper">
					<li class="<?php if (MC_WC_OVERVIEW_TAB === $active_tab) { echo 'current'; } ?>">
						<a href="?page=mailchimp-woocommerce&tab=<?php echo MC_WC_OVERVIEW_TAB; ?>" class="mc-wc-tab-button-text"><?php esc_html_e( 'Overview', 'mailchimp-for-woocommerce' ); ?></a>
					</li>
					<li class="<?php if (MC_WC_STORE_INFO_TAB === $active_tab) { echo 'current'; } ?>">
						<a href="?page=mailchimp-woocommerce&tab=<?php echo MC_WC_STORE_INFO_TAB; ?>" class="mc-wc-tab-button-text"><?php esc_html_e( 'Store', 'mailchimp-for-woocommerce' ); ?></a>
					</li>
					<li class="<?php if (MC_WC_AUDIENCE_TAB === $active_tab) { echo 'current'; } ?>">
						<a href="?page=mailchimp-woocommerce&tab=<?php echo MC_WC_AUDIENCE_TAB; ?>" class="mc-wc-tab-button-text"><?php esc_html_e( 'Audience', 'mailchimp-for-woocommerce' ); ?></a>
					</li>
					<li class="<?php if (MC_WC_LOGS_TAB === $active_tab) { echo 'current'; } ?>">
						<a href="?page=mailchimp-woocommerce&tab=<?php echo MC_WC_LOGS_TAB; ?>" class="mc-wc-tab-button-text"><?php esc_html_e( 'Logs', 'mailchimp-for-woocommerce' ); ?></a>
					</li>
					<li class="<?php if (MC_WC_ADVANCED_TAB === $active_tab) { echo 'current'; } ?>">
						<a href="?page=mailchimp-woocommerce&tab=<?php echo MC_WC_ADVANCED_TAB; ?>" class="mc-wc-tab-button-text"><?php esc_html_e( 'Advanced', 'mailchimp-for-woocommerce' ); ?></a>
					</li>
				</ul>
			</div>
			<?php
				$settings_errors = get_settings_errors();
				if (MC_WC_OVERVIEW_TAB === $active_tab && ( isset( $settings_errors[0] ) && 'success' !== $settings_errors[0]['type'] ) ): ?>
					<div class="notices-content-wrapper sync-notices">
						<?php echo wp_kses_post( mailchimp_settings_errors() ); ?>
					</div>
			<?php endif; ?>
			<div class="mc-wc-tab-content <?php echo $active_tab; ?>">
				<div class="mc-wc-notice">
                    <div class="flex justify-between items-center">
                        <span id="mc_notice_text"></span>
                        <button class="wink" type="button" id="mc_notice_button">
                            <span class="wink-visually-hidden">Dismiss notification</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" focusable="false" aria-hidden="true" class="wink-icon" style="fill: #fff;">
                                <path d="M12 13.414l6.293 6.293 1.414-1.414L13.414 12l6.293-6.293-1.414-1.414L12 10.586 5.707 4.293 4.293 5.707 10.586 12l-6.293 6.293 1.414 1.414L12 13.414z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
			<?php
				switch ($active_tab) {
					case MC_WC_STORE_INFO_TAB:
						Mailchimp_Woocommerce_Event::track('navigation_store:view', new DateTime());
						include_once  __DIR__ .'/tabs/store-info.php';
						break;

					case MC_WC_AUDIENCE_TAB:
						Mailchimp_Woocommerce_Event::track('navigation_audience:view', new DateTime());
						include_once  __DIR__ .'/tabs/audience.php';
						break;

					case MC_WC_LOGS_TAB:
						Mailchimp_Woocommerce_Event::track('navigation_logs:view', new DateTime());
						include_once  __DIR__ .'/tabs/logs.php';
						break;

					case MC_WC_ADVANCED_TAB:
						Mailchimp_Woocommerce_Event::track('navigation_advanced:view', new DateTime());
						include_once  __DIR__ .'/tabs/advanced.php';
						break;

					default:
						Mailchimp_Woocommerce_Event::track('audience_stats:view_screen', new DateTime());
						include_once  __DIR__ .'/tabs/overview.php';
						break;
				}
			?>
			</div>
		</div>
	</div>
</div>