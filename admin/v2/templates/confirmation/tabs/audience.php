<?php
/**
 * Audience tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>

<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_AUDIENCE_TAB; ?>"/>
<div class="mc-wc-tab-content-wrapper audience">
    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e( 'Audience settings', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-warning-info">
            <div class="mc-wc-warning-info-wrapper">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12V11L11 10H13V16L15 17V18H9V17L11 16V12H9Z" fill="#241C15"/>
                        <path d="M11.75 8C12.4404 8 13 7.44036 13 6.75C13 6.05964 12.4404 5.5 11.75 5.5C11.0596 5.5 10.5 6.05964 10.5 6.75C10.5 7.44036 11.0596 8 11.75 8Z" fill="#241C15"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 23C18.0751 23 23 18.0751 23 12C23 5.92487 18.0751 1 12 1C5.92487 1 1 5.92487 1 12C1 18.0751 5.92487 23 12 23ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" fill="#241C15"/>
                    </svg>
                </div>
                
                <p>
                <?php 
                    echo sprintf(
                        __( 'If you plan to use <a href=%s target="_blank">Abandoned Cart automations</a>, choose the option to <b>track carts for all customers</b> and select the checkbox to <b>sync new non-subscribed contacts</b>.', 'mailchimp-for-woocommerce' ),
                        esc_url( 'https://mailchimp.com/help/create-abandoned-cart-customer-journey/' )
                    );
                ?>
                </p>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box has-underline">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e( 'Cart tracking preferences', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description-small">
        <?php esc_html_e( 'Sync real-time shopping cart activity to Mailchimp.', 'mailchimp-for-woocommerce' ); ?>
        </div>
        <?php $mailchimp_cart_tracking = ( array_key_exists( 'mailchimp_cart_tracking', $options ) && ! is_null( $options['mailchimp_cart_tracking'] ) ) ? $options['mailchimp_cart_tracking'] : 'all'; ?>
        <div class="mc-wc-tracking-choose">
            <div class="mc-wc-radio">
                <label class="mc-wc-radio-label fw-700">
                    <input type="radio" id="cart_track_all" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="all"<?php echo 'all' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
                    <?php esc_html_e( 'Track carts for all customers', 'mailchimp-for-woocommerce' ); ?>
                </label>
            </div>
            <div class="mc-wc-radio">
                <label class="mc-wc-radio-label fw-700">
                    <input type="radio" id="cart_track_subscribed" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="subscribed"<?php echo 'subscribed' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
                    <?php esc_html_e( 'Only track carts for subscribed contacts', 'mailchimp-for-woocommerce' ); ?>
                </label>
            </div>
            <div class="mc-wc-radio">
                <label class="mc-wc-radio-label fw-700">
                    <input type="radio" id="cart_track_none" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_cart_tracking]" value="disabled"<?php echo 'disabled' === $mailchimp_cart_tracking ? ' checked="checked" ' : ''; ?>>
				    <?php esc_html_e( 'Disable cart tracking', 'mailchimp-for-woocommerce' ); ?>
                </label>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box has-underline">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e( 'Contact import preferences', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-contact-import-ref-choose">
            <?php $ongoing_sync_subscribe = ( array_key_exists( 'mailchimp_ongoing_sync_status', $options ) && ! is_null( $options['mailchimp_ongoing_sync_status'] ) ) ? $options['mailchimp_ongoing_sync_status'] : '1'; ?>
            <div class="mc-wc-import-list-sync">
                <div class="mc-wc-import-list-sync-item">
                    <div class="mc-wc-import-list-sync-input">
                        <div class="mc-wc-checkbox">
                            <label class="mc-wc-checkbox-label fw-700">
                                <input type="checkbox" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_ongoing_sync_status]" value="1" <?php if ((bool) $ongoing_sync_subscribe){ echo "checked"; } ?>>
                                <?php esc_html_e( 'Sync new non-subscribed contacts', 'mailchimp-for-woocommerce' ); ?>
                            </label>
                        </div>
                    </div>
                    <div class="mc-wc-import-list-sync-description">
                        <?php
                            echo sprintf(
                                __( 'Sync customers that have never opted in to receive your email marketing. You can use Mailchimp to send <a href=%s target="_blank">non-subscribed contacts</a> transactional emails (such as <a href=%s target="_blank">Abandoned Cart automations</a>) and target them with ads.', 'mailchimp-for-woocommerce' ),
                                esc_url( 'https://mailchimp.com/help/about-non-subscribed-contacts/'),
                                esc_url( 'https://mailchimp.com/help/create-abandoned-cart-customer-journey/')
                            );
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e( 'Tag contacts from WooCommerce', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description-small">
            <?php echo __( 'Tagging helps you filter your contacts and personalize your marketing in Mailchimp. Any tags created here will be applied to all contacts imported through this plugin.'); ?>
        </div>
        <?php 
            $user_tags = ( array_key_exists( 'mailchimp_user_tags', $options ) && ! is_null( $options['mailchimp_user_tags'] ) ) ? esc_html( str_replace( ',', ', ', $options['mailchimp_user_tags'] )) : ''; 

            $user_tags_arr = array();

            if($user_tags) {
                $user_tags_arr = explode( ', ', $user_tags);
            }
            
        ?>
        <div class="mc-wc-tag-list">
            <div class="mc-wc-tag-form-input">
                <input type="hidden" id="<?php echo esc_attr( $this->plugin_name ); ?>-user-tags" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_user_tags]" value="<?php echo $user_tags; ?>" />
                <input class="mc-wc-input" type="text" placeholder="<?php esc_html_e( 'Enter tag', 'mailchimp-for-woocommerce' ); ?>">
                <a class="mc-wc-btn-2 mc-wc-btn-2-outline btn-add">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15 10.5C15 11.0523 14.5523 11.5 14 11.5H11V14.5C11 15.0523 10.5523 15.5 10 15.5C9.44772 15.5 9 15.0523 9 14.5V11.5H6C5.44772 11.5 5 11.0523 5 10.5C5 9.94772 5.44772 9.5 6 9.5H9V6.5C9 5.94772 9.44772 5.5 10 5.5C10.5523 5.5 11 5.94772 11 6.5V9.5H14C14.5523 9.5 15 9.94772 15 10.5ZM10 2.5C5.582 2.5 2 6.082 2 10.5C2 14.918 5.582 18.5 10 18.5C14.418 18.5 18 14.918 18 10.5C18 6.082 14.418 2.5 10 2.5Z" fill="#7D57A4"/>
                    </svg>
                    <?php esc_html_e( 'Add', 'mailchimp-for-woocommerce' ); ?>
                </a>
            </div>
            <div class="mc-wc-tag-show-tagged">
            <?php foreach ($user_tags_arr as $tag): ?>
                <div>
                    <span class="mc-wc-tag-text"><?php echo $tag; ?></span>
                    <span class="mc-wc-tag-icon-del" data-value="<?php echo $tag; ?>"></span>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>