<?php
// Grab plugin admin object
$handler = MailChimp_WooCommerce_Admin::connect();

// Grab all options for this particular tab we're viewing.
$options = get_option($this->plugin_name, array());

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($options['active_tab']) ? $options['active_tab'] : 'api_key');
$mc_configured = mailchimp_is_configured();

if (!$mc_configured) {
    if ($active_tab == 'sync' || $active_tab == 'logs' ) isset($options['active_tab']) ? $options['active_tab'] : 'api_key';
}

$is_mailchimp_post = isset($_POST['mailchimp_woocommerce_settings_hidden']) && $_POST['mailchimp_woocommerce_settings_hidden'] === 'Y';

$show_sync_tab = isset($_GET['resync']) ? $_GET['resync'] === '1' : false;

// if we have a transient set to start the sync on this page view, initiate it now that the values have been saved.
if ($mc_configured && !$show_sync_tab && (bool) get_site_transient('mailchimp_woocommerce_start_sync', false)) {
    $show_sync_tab = true;
    $active_tab = 'sync';
}

$show_newsletter_settings = true;
$has_valid_api_key = false;
$allow_new_list = true;
$only_one_list = false;
$show_wizard = true;
$clicked_sync_button = $mc_configured && $is_mailchimp_post && $active_tab == 'sync';
$has_api_error = isset($options['api_ping_error']) && !empty($options['api_ping_error']) ? $options['api_ping_error'] : null;

if (isset($options['mailchimp_api_key'])) {
    try {
        if ($handler->hasValidApiKey(null, true)) {
            $has_valid_api_key = true;

            // if we don't have a valid api key we need to redirect back to the 'api_key' tab.
            if (($mailchimp_lists = $handler->getMailChimpLists()) && is_array($mailchimp_lists)) {
                $show_newsletter_settings = true;
                $allow_new_list = false;
                $only_one_list = count($mailchimp_lists) === 1;

            }

            // only display this button if the data is not syncing and we have a valid api key
            if ((bool) $this->getData('sync.started_at', false)) {
                $show_sync_tab = true;
            }

            //display wizard if not all steps are complete
            if ($show_sync_tab && $this->getData('validation.store_info', false) && $this->getData('validation.newsletter_settings', false)) {
                $show_wizard = false;        
            }
                
        }
    } catch (\Exception $e) {
        $has_api_error = $e->getMessage().' on '.$e->getLine().' in '.$e->getFile();
    }
}
else {
    $active_tab = 'api_key';
}

?>

<div class="mc-woocommerce-settings">
    <form id="mailchimp_woocommerce_options" method="post" name="cleanup_options" action="options.php">
        <?php if($show_wizard): ?>
            <div class="mc-woocommerce-settings-header-wrapper wiz-header">
                <div class="mc-woocommerce-settings-header">
                    <svg class="mc-woocommerce-logo" width="93" height="93" viewBox="0 0 46 49" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M34.5458 23.5193C34.8988 23.4778 35.2361 23.4759 35.5457 23.5193C35.7252 23.107 35.7568 22.397 35.5951 21.6239C35.3544 20.4741 35.029 19.7778 34.3584 19.8863C33.6859 19.9948 33.6622 20.8271 33.9028 21.9769C34.037 22.6238 34.2776 23.1761 34.5458 23.5193Z" fill="black"/>
                        <path d="M28.7763 24.4284C29.2575 24.6394 29.5534 24.7795 29.6678 24.6572C29.7427 24.5803 29.719 24.4363 29.6046 24.2489C29.368 23.8624 28.8788 23.4679 28.3621 23.249C27.303 22.7934 26.0407 22.9453 25.0664 23.6454C24.745 23.8801 24.4393 24.2075 24.4826 24.4047C24.4965 24.4698 24.5458 24.5172 24.6582 24.5329C24.9225 24.5625 25.8494 24.0951 26.9164 24.03C27.6718 23.9827 28.295 24.2174 28.7763 24.4284Z" fill="black"/>
                        <path d="M27.8105 24.9806C27.1852 25.0793 26.8381 25.2863 26.6172 25.4777C26.4279 25.6433 26.3115 25.8267 26.3115 25.9549C26.3115 26.0161 26.3391 26.0516 26.3589 26.0693C26.3865 26.095 26.422 26.1088 26.4614 26.1088C26.6034 26.1088 26.919 25.9826 26.919 25.9826C27.7907 25.6709 28.3647 25.7084 28.9346 25.7735C29.2502 25.809 29.3981 25.8287 29.4672 25.7202C29.4869 25.6887 29.5125 25.6216 29.4494 25.521C29.3054 25.2804 28.6723 24.8781 27.8105 24.9806Z" fill="black"/>
                        <path d="M32.5975 27.0061C33.0235 27.2152 33.4909 27.1324 33.6428 26.8227C33.7946 26.5131 33.5737 26.093 33.1497 25.8839C32.7237 25.6749 32.2563 25.7577 32.1044 26.0673C31.9506 26.377 32.1734 26.7971 32.5975 27.0061Z" fill="black"/>
                        <path d="M35.3306 24.6177C34.9854 24.6118 34.6995 24.9905 34.6916 25.4638C34.6837 25.9372 34.9578 26.3257 35.303 26.3317C35.6481 26.3376 35.9341 25.9589 35.942 25.4855C35.9499 25.0122 35.6757 24.6237 35.3306 24.6177Z" fill="black"/>
                        <path d="M12.1324 33.1577C12.0456 33.0492 11.9056 33.0827 11.7695 33.1143C11.6749 33.136 11.5664 33.1616 11.448 33.1596C11.1936 33.1557 10.9786 33.0452 10.8583 32.8598C10.7006 32.6192 10.7104 32.2583 10.884 31.8461C10.9076 31.7909 10.9353 31.7297 10.9648 31.6607C11.241 31.0394 11.7064 30 11.1857 29.008C10.7932 28.2625 10.1542 27.797 9.38702 27.7004C8.64939 27.6077 7.89006 27.8798 7.40685 28.4143C6.64358 29.2565 6.52328 30.4044 6.6712 30.8087C6.72445 30.9566 6.80925 30.998 6.87237 31.0059C7.00254 31.0237 7.19385 30.929 7.31416 30.6055C7.32205 30.5819 7.33388 30.5464 7.34769 30.501C7.40094 30.3294 7.50152 30.0099 7.66522 29.7555C7.86245 29.4478 8.17012 29.2348 8.53105 29.1579C8.89789 29.079 9.2746 29.15 9.58819 29.3551C10.1227 29.7062 10.3298 30.361 10.101 30.9862C9.98264 31.3096 9.79133 31.9289 9.83275 32.4378C9.91756 33.4673 10.5507 33.8795 11.1206 33.9249C11.6729 33.9466 12.0594 33.6349 12.1581 33.4081C12.2133 33.274 12.164 33.1932 12.1324 33.1577Z" fill="black"/>
                        <path d="M44.044 31.2761C44.0223 31.2012 43.8862 30.7002 43.6969 30.0967C43.5075 29.4932 43.3142 29.0672 43.3142 29.0672C44.0696 27.9351 44.0834 26.9233 43.9828 26.3514C43.8763 25.6414 43.5805 25.0359 42.9829 24.4107C42.3873 23.7854 41.1684 23.1445 39.4545 22.6632C39.2593 22.608 38.6123 22.4305 38.5551 22.4127C38.5512 22.3753 38.5078 20.2945 38.4684 19.3991C38.4408 18.7522 38.3836 17.7444 38.0719 16.7504C37.6992 15.4053 37.0483 14.2298 36.2377 13.4764C38.4763 11.157 39.8726 8.60091 39.8707 6.40774C39.8647 2.19102 34.6855 0.914962 28.3033 3.55781C28.2974 3.55978 26.9602 4.1278 26.9503 4.13174C26.9444 4.12582 24.5066 1.73346 24.4692 1.7019C17.1954 -4.64488 -5.55475 20.6436 1.71899 26.7853L3.30864 28.1323C2.89644 29.2013 2.73471 30.4241 2.86685 31.7396C3.03647 33.4299 3.90822 35.0511 5.32234 36.3015C6.66348 37.4908 8.42669 38.2422 10.1386 38.2402C12.9688 44.7626 19.4359 48.7643 27.0193 48.9891C35.153 49.2317 41.981 45.4134 44.8428 38.5578C45.0301 38.0765 45.825 35.909 45.825 33.9939C45.825 32.0729 44.7382 31.2761 44.044 31.2761ZM10.7638 36.41C10.5173 36.4514 10.2649 36.4691 10.0104 36.4632C7.55298 36.3981 4.90027 34.1852 4.63598 31.5621C4.34409 28.6629 5.82527 26.4322 8.44839 25.9017C8.76198 25.8386 9.14066 25.8011 9.54892 25.8228C11.0183 25.9037 13.1838 27.0318 13.6789 30.2328C14.1187 33.0689 13.4225 35.9564 10.7638 36.41ZM8.02041 24.1681C6.38736 24.4856 4.9476 25.4106 4.06797 26.6886C3.54137 26.2508 2.56115 25.4007 2.38956 25.0694C0.985306 22.4009 3.92202 17.2138 5.97516 14.285C11.0478 7.04676 18.9922 1.56581 22.6705 2.55984C23.2681 2.72945 25.2482 5.02518 25.2482 5.02518C25.2482 5.02518 21.5719 7.06451 18.1618 9.90853C13.5704 13.4468 10.0992 18.5885 8.02041 24.1681ZM33.8079 35.3252C33.8611 35.3035 33.8986 35.2424 33.8927 35.1812C33.8848 35.1063 33.8177 35.0531 33.7448 35.0609C33.7448 35.0609 29.8969 35.6309 26.26 34.2996C26.6564 33.0117 27.7096 33.4772 29.3012 33.6054C32.1709 33.777 34.7408 33.3569 36.642 32.8125C38.2889 32.3392 40.4505 31.4083 42.1309 30.0829C42.6969 31.3274 42.8981 32.6962 42.8981 32.6962C42.8981 32.6962 43.3359 32.6173 43.7028 32.8441C44.0499 33.0571 44.3024 33.5009 44.1288 34.6448C43.7758 36.7847 42.8665 38.5223 41.338 40.1198C40.4071 41.1217 39.277 41.9935 37.9852 42.6266C37.2988 42.9875 36.5671 43.2991 35.7959 43.5516C30.033 45.4331 24.1339 43.3642 22.2326 38.9207C22.0807 38.5874 21.9525 38.2363 21.852 37.8714C21.0414 34.9426 21.7297 31.43 23.8795 29.2171C23.8795 29.2171 23.8795 29.2171 23.8795 29.2151C24.0116 29.0751 24.1477 28.9094 24.1477 28.7004C24.1477 28.5248 24.0372 28.3414 23.9406 28.2112C23.1892 27.1206 20.5818 25.2607 21.1045 21.6613C21.4792 19.0757 23.7414 17.2553 25.8498 17.3637C26.0273 17.3736 26.2067 17.3834 26.3842 17.3953C27.2974 17.4485 28.0942 17.5669 28.8476 17.5984C30.1059 17.6537 31.238 17.4702 32.5792 16.3519C33.0308 15.9752 33.3937 15.6478 34.0071 15.5453C34.0722 15.5335 34.2319 15.4763 34.5534 15.492C34.8808 15.5098 35.1924 15.5985 35.4725 15.7859C36.5474 16.5018 36.6992 18.2335 36.7545 19.4997C36.786 20.2235 36.8728 21.9729 36.9044 22.4759C36.9734 23.6237 37.2751 23.7874 37.8846 23.9886C38.2278 24.101 38.5473 24.1858 39.0167 24.318C40.4387 24.7183 41.2828 25.1227 41.8153 25.6433C42.1329 25.9688 42.2808 26.3139 42.3261 26.6433C42.4938 27.8661 41.3755 29.3788 38.4171 30.7515C35.1826 32.2524 31.2577 32.6331 28.5459 32.3313C28.3388 32.3076 27.5992 32.2248 27.5952 32.2248C25.4257 31.9329 24.1891 34.7355 25.4908 36.6565C26.329 37.8951 28.6149 38.6998 30.9008 38.6998C36.1431 38.6998 40.1724 36.4613 41.6713 34.5284C41.7167 34.4712 41.7206 34.4633 41.7916 34.3568C41.8646 34.2464 41.8055 34.1852 41.7128 34.2464C40.488 35.0846 35.0484 38.4099 29.2322 37.4099C29.2322 37.4099 28.5261 37.2936 27.8792 37.0431C27.3664 36.8439 26.2935 36.3508 26.1634 35.2483C30.8514 36.6979 33.8079 35.3252 33.8079 35.3252ZM26.3704 34.4476C26.3704 34.4476 26.3724 34.4476 26.3704 34.4476C26.3724 34.4495 26.3724 34.4495 26.3724 34.4515C26.3724 34.4495 26.3724 34.4476 26.3704 34.4476ZM17.3887 14.2554C19.1914 12.1707 21.4121 10.3602 23.4002 9.34249C23.4692 9.30699 23.5422 9.38193 23.5047 9.44899C23.3469 9.73497 23.0432 10.3464 22.9466 10.8118C22.9308 10.8848 23.0097 10.9381 23.0708 10.8966C24.3074 10.0525 26.4612 9.14921 28.3486 9.03284C28.4295 9.02693 28.4689 9.13146 28.4039 9.18076C28.1159 9.40166 27.8023 9.70539 27.5735 10.0131C27.5341 10.0663 27.5716 10.1413 27.6366 10.1413C28.962 10.1511 30.8317 10.6146 32.0486 11.297C32.1315 11.3424 32.0723 11.5021 31.9796 11.4824C30.1375 11.0603 27.1199 10.7389 23.986 11.5041C21.1893 12.1865 19.0533 13.2397 17.4952 14.3738C17.4203 14.4329 17.3256 14.3304 17.3887 14.2554Z" fill="black"/>
                    </svg>
                    
                    <div class="mc-woocommerce-settings-subtitles">
                        <?php
                            $allowed_html = array(
                                'br' => array()
                            );
                        ?>
                        

                        <?php if ($active_tab == 'api_key' ) : ?>
                            <span class="mc-woocommerce-header-steps">1 of 3 - Connect</span>
                            <span class="mc-woocommerce-header-title"> <?php wp_kses(_e('Add Mailchimp for WooCommerce', 'mailchimp-for-woocommerce'), $allowed_html);?> </span>
                            <span class="mc-woocommerce-header-subtitle"> <?php wp_kses(_e('Build custom segments, send automations, and track purchase <br/>activity in Mailchimp. Login to authorize an account connection.', 'mailchimp-for-woocommerce'), $allowed_html);?> </span>
                        <?php endif;?>
                
                        <?php if ($active_tab == 'store_info' && $has_valid_api_key) :?>
                            <span class="mc-woocommerce-header-steps">2 of 3 - Store</span>    
                            <span class="mc-woocommerce-header-title"> <?php wp_kses(_e('Add WooCommerce store settings', 'mailchimp-for-woocommerce'), $allowed_html);?> </span>
                            <span class="mc-woocommerce-header-subtitle"> <?php wp_kses(_e('Please provide a bit of information about your WooCommerce <br/> store and location.', 'mailchimp-for-woocommerce'), $allowed_html);?> </span>                      
                        <?php endif;?>
                
                        <?php if ($active_tab == 'newsletter_settings' ) :?>
                            <span class="mc-woocommerce-header-steps">3 of 3 - Audience</span>    
                            <span class="mc-woocommerce-header-title"> <?php wp_kses(_e('Add WooCommerce audience settings', 'mailchimp-for-woocommerce'), $allowed_html);?> </span>
                            <span class="mc-woocommerce-header-subtitle">
                                <?php wp_kses(_e('Please provide a bit of information about your WooCommerce <br/> campaign and messaging settings.', 'mailchimp-for-woocommerce'), $allowed_html);?> 
                                <?php if (!$only_one_list) wp_kses(_e('If you don’t have an audience, <br/> you can choose to create one', 'mailchimp-for-woocommerce'), $allowed_html); ?>
                            </span>
                        <?php endif;?>    
                    </div>
                    <?php if ($active_tab == 'api_key' ): ?>
                        
                            <div class="box">
                                <?php if ($show_wizard) : ?>
                                    <input type="hidden" name="mailchimp_woocommerce_wizard_on" value=1>
                                <?php endif; ?>
                                
                                <input type="hidden" name="mailchimp_woocommerce_settings_hidden" value="Y">
                                
                                <?php
                                    if (!$clicked_sync_button) {
                                        settings_fields($this->plugin_name);
                                        do_settings_sections($this->plugin_name);
                                        include('tabs/notices.php');
                                    }
                                ?>
                            </div>
                        
                            <div class="connect-buttons">
                                <?php include_once 'tabs/api_key.php'; ?>
                            </div>
                    <?php endif; ?>
                    <div class="mc-woocommerce-wizard-btn">
                        <?php if ($active_tab == 'store_info' && $has_valid_api_key) : ?>
                                <?php submit_button(__('Next step', 'mailchimp-for-woocommerce'), 'primary tab-content-submit','mailchimp_submit', TRUE); ?>
                                <a href="?page=mailchimp-woocommerce&tab=api_key" class="button button-default back-step"><?= __('Back', 'mailchimp-for-woocommerce') ?></a>
                        <?php endif; ?>

                        <?php if ($active_tab == 'newsletter_settings' && $has_valid_api_key) : ?>
                                <?php submit_button(__('Start sync', 'mailchimp-for-woocommerce'), 'primary tab-content-submit','mailchimp_submit', TRUE); ?>
                                <a href="?page=mailchimp-woocommerce&tab=store_info" class="button button-default back-step"><?= __('Back', 'mailchimp-for-woocommerce') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="mc-woocommerce-settings-header-wrapper">
                <div class="mc-woocommerce-settings-header">
                    <svg class="mailchimp-logo" width="46" height="49" viewBox="0 0 46 49" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M34.5458 23.5193C34.8988 23.4778 35.2361 23.4759 35.5457 23.5193C35.7252 23.107 35.7568 22.397 35.5951 21.6239C35.3544 20.4741 35.029 19.7778 34.3584 19.8863C33.6859 19.9948 33.6622 20.8271 33.9028 21.9769C34.037 22.6238 34.2776 23.1761 34.5458 23.5193Z" fill="black"/>
                        <path d="M28.7763 24.4284C29.2575 24.6394 29.5534 24.7795 29.6678 24.6572C29.7427 24.5803 29.719 24.4363 29.6046 24.2489C29.368 23.8624 28.8788 23.4679 28.3621 23.249C27.303 22.7934 26.0407 22.9453 25.0664 23.6454C24.745 23.8801 24.4393 24.2075 24.4826 24.4047C24.4965 24.4698 24.5458 24.5172 24.6582 24.5329C24.9225 24.5625 25.8494 24.0951 26.9164 24.03C27.6718 23.9827 28.295 24.2174 28.7763 24.4284Z" fill="black"/>
                        <path d="M27.8105 24.9806C27.1852 25.0793 26.8381 25.2863 26.6172 25.4777C26.4279 25.6433 26.3115 25.8267 26.3115 25.9549C26.3115 26.0161 26.3391 26.0516 26.3589 26.0693C26.3865 26.095 26.422 26.1088 26.4614 26.1088C26.6034 26.1088 26.919 25.9826 26.919 25.9826C27.7907 25.6709 28.3647 25.7084 28.9346 25.7735C29.2502 25.809 29.3981 25.8287 29.4672 25.7202C29.4869 25.6887 29.5125 25.6216 29.4494 25.521C29.3054 25.2804 28.6723 24.8781 27.8105 24.9806Z" fill="black"/>
                        <path d="M32.5975 27.0061C33.0235 27.2152 33.4909 27.1324 33.6428 26.8227C33.7946 26.5131 33.5737 26.093 33.1497 25.8839C32.7237 25.6749 32.2563 25.7577 32.1044 26.0673C31.9506 26.377 32.1734 26.7971 32.5975 27.0061Z" fill="black"/>
                        <path d="M35.3306 24.6177C34.9854 24.6118 34.6995 24.9905 34.6916 25.4638C34.6837 25.9372 34.9578 26.3257 35.303 26.3317C35.6481 26.3376 35.9341 25.9589 35.942 25.4855C35.9499 25.0122 35.6757 24.6237 35.3306 24.6177Z" fill="black"/>
                        <path d="M12.1324 33.1577C12.0456 33.0492 11.9056 33.0827 11.7695 33.1143C11.6749 33.136 11.5664 33.1616 11.448 33.1596C11.1936 33.1557 10.9786 33.0452 10.8583 32.8598C10.7006 32.6192 10.7104 32.2583 10.884 31.8461C10.9076 31.7909 10.9353 31.7297 10.9648 31.6607C11.241 31.0394 11.7064 30 11.1857 29.008C10.7932 28.2625 10.1542 27.797 9.38702 27.7004C8.64939 27.6077 7.89006 27.8798 7.40685 28.4143C6.64358 29.2565 6.52328 30.4044 6.6712 30.8087C6.72445 30.9566 6.80925 30.998 6.87237 31.0059C7.00254 31.0237 7.19385 30.929 7.31416 30.6055C7.32205 30.5819 7.33388 30.5464 7.34769 30.501C7.40094 30.3294 7.50152 30.0099 7.66522 29.7555C7.86245 29.4478 8.17012 29.2348 8.53105 29.1579C8.89789 29.079 9.2746 29.15 9.58819 29.3551C10.1227 29.7062 10.3298 30.361 10.101 30.9862C9.98264 31.3096 9.79133 31.9289 9.83275 32.4378C9.91756 33.4673 10.5507 33.8795 11.1206 33.9249C11.6729 33.9466 12.0594 33.6349 12.1581 33.4081C12.2133 33.274 12.164 33.1932 12.1324 33.1577Z" fill="black"/>
                        <path d="M44.044 31.2761C44.0223 31.2012 43.8862 30.7002 43.6969 30.0967C43.5075 29.4932 43.3142 29.0672 43.3142 29.0672C44.0696 27.9351 44.0834 26.9233 43.9828 26.3514C43.8763 25.6414 43.5805 25.0359 42.9829 24.4107C42.3873 23.7854 41.1684 23.1445 39.4545 22.6632C39.2593 22.608 38.6123 22.4305 38.5551 22.4127C38.5512 22.3753 38.5078 20.2945 38.4684 19.3991C38.4408 18.7522 38.3836 17.7444 38.0719 16.7504C37.6992 15.4053 37.0483 14.2298 36.2377 13.4764C38.4763 11.157 39.8726 8.60091 39.8707 6.40774C39.8647 2.19102 34.6855 0.914962 28.3033 3.55781C28.2974 3.55978 26.9602 4.1278 26.9503 4.13174C26.9444 4.12582 24.5066 1.73346 24.4692 1.7019C17.1954 -4.64488 -5.55475 20.6436 1.71899 26.7853L3.30864 28.1323C2.89644 29.2013 2.73471 30.4241 2.86685 31.7396C3.03647 33.4299 3.90822 35.0511 5.32234 36.3015C6.66348 37.4908 8.42669 38.2422 10.1386 38.2402C12.9688 44.7626 19.4359 48.7643 27.0193 48.9891C35.153 49.2317 41.981 45.4134 44.8428 38.5578C45.0301 38.0765 45.825 35.909 45.825 33.9939C45.825 32.0729 44.7382 31.2761 44.044 31.2761ZM10.7638 36.41C10.5173 36.4514 10.2649 36.4691 10.0104 36.4632C7.55298 36.3981 4.90027 34.1852 4.63598 31.5621C4.34409 28.6629 5.82527 26.4322 8.44839 25.9017C8.76198 25.8386 9.14066 25.8011 9.54892 25.8228C11.0183 25.9037 13.1838 27.0318 13.6789 30.2328C14.1187 33.0689 13.4225 35.9564 10.7638 36.41ZM8.02041 24.1681C6.38736 24.4856 4.9476 25.4106 4.06797 26.6886C3.54137 26.2508 2.56115 25.4007 2.38956 25.0694C0.985306 22.4009 3.92202 17.2138 5.97516 14.285C11.0478 7.04676 18.9922 1.56581 22.6705 2.55984C23.2681 2.72945 25.2482 5.02518 25.2482 5.02518C25.2482 5.02518 21.5719 7.06451 18.1618 9.90853C13.5704 13.4468 10.0992 18.5885 8.02041 24.1681ZM33.8079 35.3252C33.8611 35.3035 33.8986 35.2424 33.8927 35.1812C33.8848 35.1063 33.8177 35.0531 33.7448 35.0609C33.7448 35.0609 29.8969 35.6309 26.26 34.2996C26.6564 33.0117 27.7096 33.4772 29.3012 33.6054C32.1709 33.777 34.7408 33.3569 36.642 32.8125C38.2889 32.3392 40.4505 31.4083 42.1309 30.0829C42.6969 31.3274 42.8981 32.6962 42.8981 32.6962C42.8981 32.6962 43.3359 32.6173 43.7028 32.8441C44.0499 33.0571 44.3024 33.5009 44.1288 34.6448C43.7758 36.7847 42.8665 38.5223 41.338 40.1198C40.4071 41.1217 39.277 41.9935 37.9852 42.6266C37.2988 42.9875 36.5671 43.2991 35.7959 43.5516C30.033 45.4331 24.1339 43.3642 22.2326 38.9207C22.0807 38.5874 21.9525 38.2363 21.852 37.8714C21.0414 34.9426 21.7297 31.43 23.8795 29.2171C23.8795 29.2171 23.8795 29.2171 23.8795 29.2151C24.0116 29.0751 24.1477 28.9094 24.1477 28.7004C24.1477 28.5248 24.0372 28.3414 23.9406 28.2112C23.1892 27.1206 20.5818 25.2607 21.1045 21.6613C21.4792 19.0757 23.7414 17.2553 25.8498 17.3637C26.0273 17.3736 26.2067 17.3834 26.3842 17.3953C27.2974 17.4485 28.0942 17.5669 28.8476 17.5984C30.1059 17.6537 31.238 17.4702 32.5792 16.3519C33.0308 15.9752 33.3937 15.6478 34.0071 15.5453C34.0722 15.5335 34.2319 15.4763 34.5534 15.492C34.8808 15.5098 35.1924 15.5985 35.4725 15.7859C36.5474 16.5018 36.6992 18.2335 36.7545 19.4997C36.786 20.2235 36.8728 21.9729 36.9044 22.4759C36.9734 23.6237 37.2751 23.7874 37.8846 23.9886C38.2278 24.101 38.5473 24.1858 39.0167 24.318C40.4387 24.7183 41.2828 25.1227 41.8153 25.6433C42.1329 25.9688 42.2808 26.3139 42.3261 26.6433C42.4938 27.8661 41.3755 29.3788 38.4171 30.7515C35.1826 32.2524 31.2577 32.6331 28.5459 32.3313C28.3388 32.3076 27.5992 32.2248 27.5952 32.2248C25.4257 31.9329 24.1891 34.7355 25.4908 36.6565C26.329 37.8951 28.6149 38.6998 30.9008 38.6998C36.1431 38.6998 40.1724 36.4613 41.6713 34.5284C41.7167 34.4712 41.7206 34.4633 41.7916 34.3568C41.8646 34.2464 41.8055 34.1852 41.7128 34.2464C40.488 35.0846 35.0484 38.4099 29.2322 37.4099C29.2322 37.4099 28.5261 37.2936 27.8792 37.0431C27.3664 36.8439 26.2935 36.3508 26.1634 35.2483C30.8514 36.6979 33.8079 35.3252 33.8079 35.3252ZM26.3704 34.4476C26.3704 34.4476 26.3724 34.4476 26.3704 34.4476C26.3724 34.4495 26.3724 34.4495 26.3724 34.4515C26.3724 34.4495 26.3724 34.4476 26.3704 34.4476ZM17.3887 14.2554C19.1914 12.1707 21.4121 10.3602 23.4002 9.34249C23.4692 9.30699 23.5422 9.38193 23.5047 9.44899C23.3469 9.73497 23.0432 10.3464 22.9466 10.8118C22.9308 10.8848 23.0097 10.9381 23.0708 10.8966C24.3074 10.0525 26.4612 9.14921 28.3486 9.03284C28.4295 9.02693 28.4689 9.13146 28.4039 9.18076C28.1159 9.40166 27.8023 9.70539 27.5735 10.0131C27.5341 10.0663 27.5716 10.1413 27.6366 10.1413C28.962 10.1511 30.8317 10.6146 32.0486 11.297C32.1315 11.3424 32.0723 11.5021 31.9796 11.4824C30.1375 11.0603 27.1199 10.7389 23.986 11.5041C21.1893 12.1865 19.0533 13.2397 17.4952 14.3738C17.4203 14.4329 17.3256 14.3304 17.3887 14.2554Z" fill="black"/>
                    </svg>
                    
                    <p class="mc-woocommerce-settings-subtitles">
                        <?php
                        
                        $allowed_html = array(
                            'br' => array()
                        );
                        
                        if ($active_tab == 'api_key' ) {
                            wp_kses(_e('Add Mailchimp for WooCommerce to build custom segments, send automations, and track purchase activity in Mailchimp', 'mailchimp-for-woocommerce'), $allowed_html);
                        }
                
                        if ($active_tab == 'store_info' && $has_valid_api_key) {
                            if ($show_sync_tab) {
                                wp_kses(_e('WooCommerce store and location', 'mailchimp-for-woocommerce'), $allowed_html);
                            }
                            else wp_kses(_e('Please provide a bit of information about your WooCommerce store', 'mailchimp-for-woocommerce'), $allowed_html);
                        }
                
                        if ($active_tab == 'newsletter_settings' ) {
                            if ($show_sync_tab) {
                                wp_kses(_e('Campaign and messaging settings', 'mailchimp-for-woocommerce'), $allowed_html);
                            }
                            else {
                                if ($only_one_list) {
                                    wp_kses(_e('Please apply your audience settings.', 'mailchimp-for-woocommerce'), $allowed_html);
                                }
                                else {
                                    wp_kses(_e('Please apply your audience settings. ', 'mailchimp-for-woocommerce'), $allowed_html);
                                    wp_kses(_e('If you don’t have an audience, you can choose to create one', 'mailchimp-for-woocommerce'), $allowed_html);    
                                }
                            }
                        }
                        if ($active_tab == 'sync' && $show_sync_tab) {
                            if (mailchimp_is_done_syncing()) {
                                wp_kses(_e('Success! You are connected to Mailchimp', 'mailchimp-for-woocommerce'), $allowed_html);
                            }
                            else {
                                wp_kses(_e('Your WooCommerce store is syncing to Mailchimp', 'mailchimp-for-woocommerce'), $allowed_html);
                            }
                        }
                
                        if ($active_tab == 'logs' && $show_sync_tab) {
                            wp_kses(_e('Log events from the Mailchimp plugin', 'mailchimp-for-woocommerce'), $allowed_html);
                        }

                        if ($active_tab == 'plugin_settings' && $show_sync_tab) {
                            wp_kses(_e('Connection settings', 'mailchimp-for-woocommerce'), $allowed_html);
                        }
                        ?>
                    </p>
                    <div class="nav-tab-wrapper">
                        <?php if($has_valid_api_key): ?>
                            <?php if ($active_tab == 'api_key'): ?>
                                <a href="?page=mailchimp-woocommerce&tab=api_key" class="nav-tab <?php echo $active_tab == 'api_key' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Connect', 'mailchimp-for-woocommerce');?></a>
                            <?php endif ;?>
                            <a href="?page=mailchimp-woocommerce&tab=sync" class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Overview', 'mailchimp-for-woocommerce');?></a>
                            <a href="?page=mailchimp-woocommerce&tab=store_info" class="nav-tab <?php echo $active_tab == 'store_info' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Store', 'mailchimp-for-woocommerce');?></a>
                            <?php if ($handler->hasValidStoreInfo()) : ?>
                            
                                    <a href="?page=mailchimp-woocommerce&tab=newsletter_settings" class="nav-tab <?php echo $active_tab == 'newsletter_settings' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Audience', 'mailchimp-for-woocommerce');?></a>
                            <?php endif;?>
                            <a href="?page=mailchimp-woocommerce&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Logs', 'mailchimp-for-woocommerce');?></a>
                            <a href="?page=mailchimp-woocommerce&tab=plugin_settings" class="nav-tab <?php echo $active_tab == 'plugin_settings' ? 'nav-tab-active' : ''; ?>"><?= esc_html_e('Settings', 'mailchimp-for-woocommerce');?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="notices-content-wrapper <?= $active_tab == 'sync' ? 'sync-notices' : '' ?>">
        <?php
            $settings_errors = get_settings_errors();
            if (!$show_wizard || ($show_wizard && isset($settings_errors[0]) && $settings_errors[0]['type'] != 'success' )) {
                echo mailchimp_settings_errors();
            }
        ?>
        </div>
        <?php if ($active_tab != 'sync'): ?>
        <div class="tab-content-wrapper">
        <?php endif; ?>
                <?php if (!defined('PHP_VERSION_ID') || (PHP_VERSION_ID < 70000)): ?>
                    <div data-dismissible="notice-php-version" class="error notice notice-error">
                        <p><?php esc_html_e('Mailchimp says: Please upgrade your PHP version to a minimum of 7.0', 'mailchimp-for-woocommerce'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($has_api_error)): ?>
                    <div data-dismissible="notice-api-error" class="error notice notice-error is-dismissible">
                        <p><?php esc_html_e("Mailchimp says: API Request Error - ".$has_api_error, 'mailchimp-for-woocommerce'); ?></p>
                    </div>
                <?php endif; ?>
                <div class="box">
                    <?php if ($show_wizard) : ?>
                        <input type="hidden" name="mailchimp_woocommerce_wizard_on" value=1>
                    <?php endif; ?>
                    
                    <input type="hidden" name="mailchimp_woocommerce_settings_hidden" value="Y">
                
                    <?php
                        if (!$clicked_sync_button) {
                            settings_fields($this->plugin_name);
                            do_settings_sections($this->plugin_name);
                            include('tabs/notices.php');
                        }
                    ?>
                </div>
                

                <input type="hidden" name="<?php echo $this->plugin_name; ?>[mailchimp_active_tab]" value="<?php echo esc_attr($active_tab); ?>"/>
                
                <?php if ($active_tab == 'api_key'): ?>
                    <?php //include_once 'tabs/api_key_content.php'; ?>
                <?php endif; ?>

                <?php if ($active_tab == 'store_info' && $has_valid_api_key): ?>
                    <?php include_once 'tabs/store_info.php'; ?>
                <?php endif; ?>

                <?php if ($active_tab == 'newsletter_settings' ): ?>
                    <?php include_once 'tabs/newsletter_settings.php'; ?>
                <?php endif; ?>

                <?php if ($active_tab == 'sync' && $show_sync_tab): ?>
                    <?php include_once 'tabs/store_sync.php'; ?>
                <?php endif; ?>

                <?php if ($active_tab == 'logs' && $show_sync_tab): ?>
                    <?php include_once 'tabs/logs.php'; ?>
                <?php endif; ?>

                <?php if ($active_tab == 'plugin_settings' && $show_sync_tab): ?>
                    <?php include_once 'tabs/plugin_settings.php'; ?>
                <?php endif; ?>
                <?php if (mailchimp_is_configured()) : ?>
                    <div class="box"> 
                        <?php 
                            if ($active_tab !== 'api_key' && $active_tab !== 'sync' && $active_tab !== 'logs' && $active_tab != 'plugin_settings') {
                                submit_button(__('Save all changes'), 'primary tab-content-submit','mailchimp_submit', TRUE);
                            }
                        ?>
                    </div>
                <?php endif; ?>
        <?php if ($active_tab != 'sync'): ?>
        </div>
        <?php endif; ?>
    </form>
</div><!-- /.wrap -->
