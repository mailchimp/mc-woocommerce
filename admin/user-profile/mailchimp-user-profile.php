<?php $mailchimp_user_is_subscribed = ( isset( $user ) && $user ) ? get_user_meta( $user->ID, 'mailchimp_woocommerce_is_subscribed', true ) : false; ?>
<h2>Mailchimp</h2>
<table class="form-table">
	<tr>
		<th><label for="mailchimp_woocommerce_is_subscribed"><?php esc_html_e( 'User Subscribed', 'mailchimp-for-woocommerce' ); ?></label></th>
		<td>
			<input type="checkbox" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed"<?php echo ! empty( $mailchimp_user_is_subscribed ) ? esc_attr( 'checked' ) : ''; ?>>
		</td>
	</tr>
</table>
<?php if ( isset( $gdpr_fields ) && ! empty( $gdpr_fields ) ) : ?>
	<?php echo wp_kses( $gdpr_fields, mailchimp_expanded_alowed_tags() ); ?>
<?php endif; ?>
