<input type="hidden" name="mailchimp_active_settings_tab" value="plugin_settings"/>

<?php
$store_id = mailchimp_get_store_id();

$opt = get_option('mailchimp-woocommerce-comm.opt');
$tower_opt = get_option('mailchimp-woocommerce-tower.opt');
$admin_email = mailchimp_get_option('admin_email', get_option('admin_email'));
$comm_enabled = $opt != null ? $opt : '0';
$tower_enabled = $tower_opt != null ? $tower_opt : '0';
?>
<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Plugin Settings', 'mailchimp-for-woocommerce');?></span>
	</legend>

    <div class="box ">
        <label for="<?php echo $this->plugin_name; ?>-tower-support-checkbox-label">
            <h4><?php esc_html_e('Remote Diagnostics', 'mailchimp-for-woocommerce'); ?></h4>
            <p>
                <?php
                echo sprintf(
                /* translators: Placeholders %1$s - admin email address */
                    __('Remote diagnostics for the Mailchimp for WooCommerce plugin allows our development team to troubleshoot syncing issues.', 'mailchimp-for-woocommerce'),
                    $admin_email
                );?>
            </p>
        </label>
        <br/>
        <fieldset>
            <p id="mc-comm-wrapper">
                <label class="el-switch el-checkbox-green">
                    <input id="tower_box_switch" type="checkbox" name="switch" <?php if($tower_enabled === '1') echo ' checked="checked" '; ?> value="1">
                    <span><?= __('Enable support', 'mailchimp-for-woocommerce'); ?></span>
                    <br/>
                    <span class="mc-tower-save" id="mc-tower-save">Saved</span>
                </label>
            </p>
        </fieldset>
    </div>

	<div class="box ">
		<label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
			<h4><?php esc_html_e('Communication', 'mailchimp-for-woocommerce'); ?></h4>
			<p>
				<?php 
				
				echo sprintf(
					/* translators: Placeholders %1$s - admin email address */
					__('Occasionally we may send you information about how-to\'s, updates, and other news to the store\'s admin email address. Choose whether or not you want to receive these messages at %1$s ', 'mailchimp-for-woocommerce'),
					$admin_email
				);?>
			</p>
		</label>
		<br/>
		<fieldset>    
			<p id="mc-comm-wrapper">
				<label class="el-switch el-checkbox-green">
					<input id="comm_box_switch" type="checkbox" name="switch" <?php if($comm_enabled === '1') echo ' checked="checked" '; ?> value="1">
					<span><?= __('Opt-in to our newsletter', 'mailchimp-for-woocommerce'); ?></span>
					<br/>
					<span class="mc-comm-save" id="mc-comm-save">Saved</span>
				</label>
				
			</p>
		</fieldset>
	</div>

	<div class="box"></div>
	<div class="box">
		<label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
			<h4><?php esc_html_e('Disconnect Store', 'mailchimp-for-woocommerce'); ?></h4>
			<p>
				<?= 
				sprintf(
					__('Disconnect your store from Mailchimp. This action will remove all entries from the database but you will be able to reconnect anytime.', 'mailchimp-for-woocommerce'),
					$admin_email
				);?>
			</p>
		</label>
		<p>
			<?php wp_nonce_field( '_disconnect-nonce-'.$store_id, '_disconnect-nonce' ); ?>

			<a id="mailchimp_woocommerce_disconnect" class="mc-woocommerce-disconnect-button button button-default tab-content-submit">
				<?php esc_html_e('Disconnect Store', 'mailchimp-for-woocommerce');?>
			</a>
		</p>
	</div>

	<div class="box box-half comm_box_wrapper">
		
	</div>
</fieldset>