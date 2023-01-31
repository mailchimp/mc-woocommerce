<?php $mailchimp_user_subscription_status = ( isset( $user ) && $user ) ? get_user_meta( $user->ID, 'mailchimp_woocommerce_is_subscribed', true ) : false; ?>
<h2>Mailchimp</h2>
<table class="form-table">
	<tr>
		<th><label for="mailchimp_woocommerce_is_subscribed"><?php esc_html_e( 'User Subscribed', 'mailchimp-for-woocommerce' ); ?></label></th>
		<td>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="mailchimp_woocommerce_is_subscribed">
                    <input type="radio"
                           class="woocommerce-form__input woocommerce-form__input-radio input-radio"
                           name="mailchimp_woocommerce_is_subscribed_radio"
                           id="mailchimp_woocommerce_is_subscribed"
                            <?= $mailchimp_user_subscription_status === '1' ? ' checked="checked"' : '' ?>
                           value="1" />
                    <?= translate( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' ) ?>
                </label>
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="mailchimp_woocommerce_is_unsubscribed">
                    <input type="radio"
                           class="woocommerce-form__input woocommerce-form__input-radio input-radio"
                           name="mailchimp_woocommerce_is_subscribed_radio"
                           id="mailchimp_woocommerce_is_unsubscribed"
                            <?= $mailchimp_user_subscription_status === 'unsubscribed' ? ' checked="checked"' : '' ?>
                           value="unsubscribed" />
                    <?= translate( 'Unsubscribe from our newsletter', 'mailchimp-for-woocommerce' ) ?>
                </label>
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="mailchimp_woocommerce_is_transactional">
                    <input type="radio"
                           class="woocommerce-form__input woocommerce-form__input-radio input-radio"
                           name="mailchimp_woocommerce_is_subscribed_radio"
                           id="mailchimp_woocommerce_is_transactional"
                           <?= $mailchimp_user_subscription_status === '0' ? ' checked="checked"' : '' ?>
                           value="0" />
                    <?= translate( 'Receive Order Updates', 'mailchimp-for-woocommerce' ) ?>
                </label>
            </p>
		</td>
	</tr>
</table>
<?php if ( isset( $gdpr_fields ) && ! empty( $gdpr_fields ) ) : ?>
	<?php echo wp_kses( $gdpr_fields, mailchimp_expanded_alowed_tags() ); ?>
<?php endif; ?>

