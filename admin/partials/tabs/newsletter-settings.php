<?php
/**
 * Audience settings page
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

if ( ! isset( $handler ) ) {
	$handler = MailChimp_WooCommerce_Admin::instance();
}
// if we don't have a valid api key we need to redirect back to the 'api_key' tab.
if ( ! $handler->validateApiKey() || ( ! isset( $mailchimp_lists ) ) ) {
	$mailchimp_lists = $handler->getMailChimpLists();
	if ( false === $mailchimp_lists ) {
		wp_safe_redirect( 'admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key' );
		exit;
	}
}

// if we don't have valid store information, we need to redirect back to the 'store_info' tab.
if ( ! $handler->hasValidStoreInfo() ) {
	wp_safe_redirect( 'admin.php?page=mailchimp-woocommerce&tab=store_info&error_notice=missing_store' );
	exit;
}

$list_is_configured = isset( $options['mailchimp_list'] ) && ( ! empty( $options['mailchimp_list'] ) ) && array_key_exists( $options['mailchimp_list'], ( isset( $mailchimp_lists ) ? $mailchimp_lists : array() ) );

if ( ! isset( $options ) ) {
	$options = array();
}
$newsletter_settings_error = $this->getData( 'errors.mailchimp_list', false );

$checkout_page_id = get_option('woocommerce_checkout_page_id');
?>

<?php if ( $newsletter_settings_error ) : ?>
	<div class="error notice is-dismissable">
		<p><?php echo wp_kses_post( $newsletter_settings_error ); ?></p>
	</div>
<?php endif; ?>

<input type="hidden" name="mailchimp_active_settings_tab" value="newsletter_settings"/>
<fieldset>
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Audience Settings', 'mailchimp-for-woocommerce' ); ?></span>
	</legend>
	<?php if ( ! $list_is_configured ) : ?>
		<div class="box fieldset-header no-padding" >
			<h3><?php esc_html_e( 'Connect your store with an audience', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>
		<div class="box" >
			<label for="<?php echo esc_attr( $this->plugin_name ); ?>-mailchimp-list-label">
				<strong><?php esc_html_e( 'Audience name', 'mailchimp-for-woocommerce' ); ?></strong>
			</label>
			<div class="mailchimp-select-wrapper">
				<select name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_list]" required <?php echo ( isset( $only_one_list ) && $only_one_list ) ? 'disabled' : ''; ?>>
					<?php if ( ! isset( $allow_new_list ) || true === $allow_new_list ) : ?>
						<option value="create_new"><?php esc_html_e( 'Create New Audience', 'mailchimp-for-woocommerce' ); ?></option>
					<?php endif ?>
					<?php if ( isset( $allow_new_list ) && false === $allow_new_list ) : ?>
						<option value="">-- <?php esc_html_e( 'Select Audience', 'mailchimp-for-woocommerce' ); ?> --</option>
					<?php endif; ?>
					<?php
					if ( isset( $mailchimp_lists ) && is_array( $mailchimp_lists ) ) {
						$selected_list = isset( $options['mailchimp_list'] ) ? $options['mailchimp_list'] : null;
						foreach ( $mailchimp_lists as $key => $value ) {
							echo '<option value="' . esc_attr( $key ) . '" ' . selected( ( (string) $key === (string) $selected_list || $only_one_list ), true, false ) . '>' . esc_html( $value ) . '</option>';
						}
					}
					?>
				</select>
			</div>
		</div>
	<?php else : ?>
		<div class="box fieldset-header no-padding" >
			<h3><?php esc_html_e( 'Your store is currently connected to:', 'mailchimp-for-woocommerce' ); ?> <?php echo esc_html( $handler->getListName() ); ?> </h3>
		</div>
		<div class="box" >
			<p><?php esc_html_e( 'To select another audience, you must first disconnect your store on the Settings tab.', 'mailchimp-for-woocommerce' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="box fieldset-header" >
		<h3><?php esc_html_e( 'Audience Defaults', 'mailchimp-for-woocommerce' ); ?></h3>
	</div>
	<div class="box box-half">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-from-name-label">
			<?php esc_html_e( 'From Name', 'mailchimp-for-woocommerce' ); ?>
			<span class="required-field-mark">*</span>
		</label>
		<input type="text" id="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-from-name-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[campaign_from_name]" value="<?php echo esc_html( ( isset( $options['campaign_from_name'] ) ? $options['campaign_from_name'] : get_option( 'blogname' ) ) ); ?>" required/>
	</div>
	<div class="box box-half">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-from-email-label">
			<?php esc_html_e( 'From Email', 'mailchimp-for-woocommerce' ); ?>
			<span class="required-field-mark">*</span>
		</label>
		<input type="email" id="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-from-email-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[campaign_from_email]" value="<?php echo esc_html( ( isset( $options['campaign_from_email'] ) ? $options['campaign_from_email'] : get_option( 'admin_email' ) ) ); ?>" required/>
	</div>
	<div class="box box-half">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-subject-label">
			<?php esc_html_e( 'Subject', 'mailchimp-for-woocommerce' ); ?>
			<span class="required-field-mark">*</span>
		</label>
		<input type="text" id="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-subject-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[campaign_subject]" value="<?php echo isset( $options['campaign_subject'] ) ? esc_html( $options['campaign_subject'] ) : esc_html__( 'Store Newsletter', 'mailchimp-for-woocommerce' ); ?>" required/>
	</div>
	<div class="box box-half">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-language-label">
			<?php esc_html_e( 'Language', 'mailchimp-for-woocommerce' ); ?>
			<span class="required-field-mark">*</span>
		</label>
		<div class="mailchimp-select-wrapper">
			<select id="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-language-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[campaign_language]" required>
				<?php $selected_locale = isset( $options['store_locale'] ) && ! empty( $options['store_locale'] ) ? esc_html( $options['store_locale'] ) : get_locale(); ?> ?>
				<?php
				foreach ( MailChimp_Api_Locales::all() as $locale_key => $local_value ) {
					echo '<option value="' . esc_attr( $locale_key ) . '" ' . selected( $locale_key === $selected_locale, true, false ) . '>' . esc_html( $local_value ) . '</option>';
				}
				?>
			</select>    
		</div>
	</div>
	<div class="box">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-permission-reminder-label">
			<?php esc_html_e( 'Permission reminder message', 'mailchimp-for-woocommerce' ); ?>
			<span class="required-field-mark">*</span>
		</label>
		<textarea
			rows="4"
			id="<?php echo esc_attr( $this->plugin_name ); ?>-campaign-permission-reminder-label"
			name="<?php echo esc_attr( $this->plugin_name ); ?>[campaign_permission_reminder]"
			required><?php echo isset( $options['campaign_permission_reminder'] ) ? esc_html( $options['campaign_permission_reminder'] ) : sprintf( /* translators: %s - plugin name. */esc_html__( 'You were subscribed to the newsletter from %s', 'mailchimp-for-woocommerce' ), esc_html( get_option( 'blogname' ) ) ); ?>
		</textarea>
	</div>
	<div class="optional-settings-content">
		<div class="box fieldset-header" >
			<h3><?php esc_html_e( 'Sync Settings', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>
		<?php $enable_auto_subscribe = ( array_key_exists( 'mailchimp_auto_subscribe', $options ) && ! is_null( $options['mailchimp_auto_subscribe'] ) ) ? $options['mailchimp_auto_subscribe'] : '1'; ?>
		<?php if ( ! $list_is_configured ) : ?>
		<div class="box" >
			<label>
				<h4><?php esc_html_e( 'Initial Sync', 'mailchimp-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'WooCommerce does not keep a historical record of a customer\'s opt-in preferences. This plugin will add this functionality moving forward. Do you want to subscribe all customers to your Mailchimp Audience or only those moving forward?', 'mailchimp-for-woocommerce' ); ?></p>
			</label>
			<div class="box margin-large"></div>
			<label class="radio-label">
				<input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_auto_subscribe]" value="1"<?php echo (bool) $enable_auto_subscribe ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Auto-subscribe all customers on the initial sync', 'mailchimp-for-woocommerce' ); ?>
				<br>
			</label>
			<label class="radio-label">
				<input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_auto_subscribe]" value="0"<?php echo ! (bool) $enable_auto_subscribe ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Only sync customers in this store who are subscribed in my Mailchimp Audience', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
		</div>
		<?php endif; ?>
		<div class="box" >
			<?php $ongoing_sync_subscribe = ( array_key_exists( 'mailchimp_ongoing_sync_status', $options ) && ! is_null( $options['mailchimp_ongoing_sync_status'] ) ) ? $options['mailchimp_ongoing_sync_status'] : '1'; ?>
			<label>
				<h4><?php esc_html_e( 'Ongoing Sync', 'mailchimp-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'To maximize your marketing efforts, we recommend syncing all customer data with Mailchimp. This way, you can send both marketing and transactional campaigns to your clients. You also have the option to only sync those who subscribe to your Audience at checkout or create an account on your store. Transactional messages like abandoned carts, order confirmations, etc., will not trigger if you decide to only send to subscribers.', 'mailchimp-for-woocommerce' ); ?></p>
			</label>
			<div class="box margin-large"></div>
			<label class="radio-label">
				<input type="radio" id="ongoing_sync_all" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_ongoing_sync_status]" value="1"<?php echo (bool) $ongoing_sync_subscribe ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Send all data to your Mailchimp Audience, including subscribers and transactional customers', 'mailchimp-for-woocommerce' ); ?>
				<br>
			</label>
			<label class="radio-label">
				<input type="radio" id="ongoing_sync_subscribed" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_ongoing_sync_status]" value="0"<?php echo ! (bool) $ongoing_sync_subscribe ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Only sync subscribers. No transactional customers will be sent to your Mailchimp Audience', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
		</div>
		<div class="box" >
			<?php $mailchimp_cart_tracking = ( array_key_exists( 'mailchimp_cart_tracking', $options ) && ! is_null( $options['mailchimp_cart_tracking'] ) ) ? $options['mailchimp_cart_tracking'] : 'all'; ?>
			<label>
				<h4><?php esc_html_e( 'Cart Tracking', 'mailchimp-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'You may disable this if you are not using Abandoned Cart Automations.', 'mailchimp-for-woocommerce' ); ?></p>
			</label>
			<div class="box margin-large"></div>
			<label class="radio-label">
				<input type="radio" id="cart_track_all" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="all"<?php echo 'all' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Track carts for all customers', 'mailchimp-for-woocommerce' ); ?>
				<br>
			</label>
			<label class="radio-label">
				<input type="radio" id="cart_track_subscribed" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="subscribed"<?php echo 'subscribed' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Only track carts for subscribed customers', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
			<label class="radio-label">
				<input type="radio" id="cart_track_none" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="disabled"<?php echo 'disabled' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Disable cart tracking', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
		</div>
		<div class="box fieldset-header margin-large" >
			<h3><?php esc_html_e( 'Opt-In Checkbox Settings', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>        
								<?php if ( has_block( 'woocommerce/checkout', get_post($checkout_page_id ) ) ) : ?>								
        <div class="box">
            <h4><?= sprintf(__('Checkout page is using Woocommerce blocks. Settings are available within the block options while editing the <a href="%s">checkout page</a>.', 'mailchimp-for-woocommerce'), get_the_permalink($checkout_page_id) ) ?></h4>
        </div>
        <?php else: ?>
		<div class="box box-half">
			<label>
				<h4><?php esc_html_e( 'Checkbox display options', 'mailchimp-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'Choose how you want the opt-in to your newsletter checkbox to render at checkout', 'mailchimp-for-woocommerce' ); ?> </p>
			</label>
			<?php $checkbox_default_settings = ( array_key_exists( 'mailchimp_checkbox_defaults', $options ) && ! is_null( $options['mailchimp_checkbox_defaults'] ) ) ? $options['mailchimp_checkbox_defaults'] : 'check'; ?>
			<div class="box margin-large"></div>
			<label class="radio-label">
				<input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_defaults]" value="check"<?php echo 'check' === $checkbox_default_settings ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Visible, checked by default', 'mailchimp-for-woocommerce' ); ?>
				<br>
			</label>
			<label class="radio-label">
				<input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_defaults]" value="uncheck"<?php echo 'uncheck' === $checkbox_default_settings ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Visible, unchecked by default', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
			<label class="radio-label">
				<input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_defaults]" value="hide"<?php echo 'hide' === $checkbox_default_settings ? ' checked="checked" ' : ''; ?>>
				<?php esc_html_e( 'Hidden, unchecked by default', 'mailchimp-for-woocommerce' ); ?>
				<br/>
			</label>
			<div class="box margin-large"></div>
			<label for="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-label">
				<h4><?php esc_html_e( 'Message for the opt-in checkbox', 'mailchimp-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'The call-to-action text that prompts customers to subscribe to your newsletter at checkout.', 'mailchimp-for-woocommerce' ); ?> </p>
			</label>
			<div class="box"></div>
			<textarea rows="3" id="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[newsletter_label]"><?php echo isset( $options['newsletter_label'] ) ? esc_html( $options['newsletter_label'] ) : ''; ?></textarea>
			<p class="description"><?php echo esc_html( __( 'HTML tags allowed: <a href="" target="" title=""></a> and <br>', 'mailchimp-for-woocommerce' ) ); ?><br/><?php echo esc_html( __( 'Leave it blank to use language translation files (.po / .mo), translating the string: "Subscribe to our newsletter".', 'mailchimp-for-woocommerce' ) ); ?></p>
		</div>
		<div class="box box-half">
			<h4><?php esc_html_e( 'Shop checkout preview', 'mailchimp-for-woocommerce' ); ?></h4>
			<p><?php echo esc_html( __( 'The box below is a preview of your checkout page. Styles and fields may not be exact.', 'mailchimp-for-woocommerce' ) ); ?></p>
			<div class="box margin-large"></div>
			<div class="settings-sample-frame">
				<div class="woocommerce-billing-fields__field-wrapper">
					<p class="form-row address-field validate-required validate-postcode form-row-wide" id="billing_postcode_field" data-priority="65" data-o_class="form-row form-row-wide address-field validate-required validate-postcode">
						<label for="billing_postcode" class="">Postcode / ZIP&nbsp;</label><span class="woocommerce-input-wrapper"><input type="text" class="input-text " name="billing_postcode" id="billing_postcode" placeholder="" value="" autocomplete="postal-code"></span>
					</p>
					<p class="form-row address-field validate-required form-row-wide" id="billing_city_field" data-priority="70" data-o_class="form-row form-row-wide address-field validate-required">
						<label for="billing_city" class="">Town / City&nbsp;</label><span class="woocommerce-input-wrapper"><input type="text" class="input-text " name="billing_city" id="billing_city" placeholder="" value="" autocomplete="address-level2"></span>
					</p>
					<p class="form-row address-field validate-state form-row-wide" id="billing_state_field" style="display: none" data-o_class="form-row form-row-wide address-field validate-state">
						<label for="billing_state" class="">State / County&nbsp;<span class="optional">(optional)</span></label><span class="woocommerce-input-wrapper"><input type="hidden" id="billing_state" name="billing_state" placeholder="" data-input-classes="" class="hidden"></span>
					</p>
					<p class="form-row form-row-wide validate-required validate-email" id="billing_email_field" data-priority="110">
						<label for="billing_email" class="">Email address&nbsp;</label><span class="woocommerce-input-wrapper"><input type="email" class="input-text " name="billing_email" id="billing_email" placeholder="" value="" autocomplete="email"></span>
					</p>
				</div>
				<?php
					$label = $this->getOption( 'newsletter_label' );
				if ( '' === $label || is_null($label) ) {
					$label = __( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' );
                }
				?>
				<fieldset>
					<p class="form-row form-row-wide mailchimp-newsletter">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="mailchimp_woocommerce_newsletter" type="checkbox" name="mailchimp_woocommerce_newsletter" value="1" checked="checked">
						<label id="preview-label" for="mailchimp_woocommerce_newsletter" class="inline"><?php echo wp_kses_post( $label ); ?> </label>
					</p>
				</fieldset>
				<div class="overlay"></div>
			</div>
		</div>
        <?php endif; // checkout page is using blocks ?>
		<div class="box fieldset-header" >
			<h3><?php esc_html_e( 'Opt-In checkbox position', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>
		<div class="box box-half">
			<label for="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-action">
				<p>
				<?php
				echo sprintf(
					/* translators: %s - Woocommerce Actions documentation URL. */                    wp_kses(
						__( 'To change the position of the opt-in <br/>checkbox at checkout, input one of the <a href=%s target=_blank>available WooCommerce form actions</a>.', 'mailchimp-for-woocommerce' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => '_blank',
							),
						)
					),
					esc_url( 'https://woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/' )
				);
				?>
				</p>
			</label>
		</div>
		<div class="box box-half">
			<input type="text" id="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-action" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_action]" value="<?php echo isset( $options['mailchimp_checkbox_action'] ) ? esc_html( $options['mailchimp_checkbox_action'] ) : 'woocommerce_after_checkout_billing_form'; ?>" />
			<p class="description"><?php esc_html_e( 'Enter a WooCommerce form action', 'mailchimp-for-woocommerce' ); ?></p>
		</div>
		<div class="box fieldset-header" >
			<h3><?php esc_html_e( 'Tags', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>
		<div class="box box-half" >
			<label for="<?php echo esc_attr( $this->plugin_name ); ?>-user-tags">
				<p><?php esc_html_e( 'Add a comma-separated list of tags to apply to a subscriber in Mailchimp after a transaction occurs', 'mailchimp-for-woocommerce' ); ?></p>
			</label>
		</div>
		<div class="box box-half" >
			<input type="text" id="<?php echo esc_attr( $this->plugin_name ); ?>-user-tags" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_user_tags]" value="<?php echo isset( $options['mailchimp_user_tags'] ) ? esc_html( str_replace( ',', ', ', $options['mailchimp_user_tags'] ) ) : ''; ?>" />
		</div>
		<div class="box fieldset-header" >
			<h3><?php esc_html_e( 'Product Image Size', 'mailchimp-for-woocommerce' ); ?></h3>
		</div>
		<div class="box box-half">
			<label for="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_product_image_key]">
				<p><?php esc_html_e( 'Define the product image size used by abandoned carts, order notifications, and product recommendations.', 'mailchimp-for-woocommerce' ); ?></p>
			</label>
		</div>
		<div class="box box-half" >
			<div class="mailchimp-select-wrapper">
				<select name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_product_image_key]">
					<?php
					$enable_auto_subscribe = ( array_key_exists( 'mailchimp_product_image_key', $options ) && ! is_null( $options['mailchimp_product_image_key'] ) ) ? $options['mailchimp_product_image_key'] : 'medium';
					foreach ( mailchimp_woocommerce_get_all_image_sizes_list() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
			</div>
		</div>
	</div>
</fieldset>