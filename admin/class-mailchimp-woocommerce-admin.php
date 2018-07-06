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

	/**
	 * @return MailChimp_WooCommerce_Admin
	 */
	public static function connect()
	{
		$env = mailchimp_environment_variables();

		return new self('mailchimp-woocommerce', $env->version);
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mailchimp-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
        add_menu_page(
            'MailChimp - WooCommerce Setup',
            'MailChimp',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'data:image/svg+xml;base64,'.$this->mailchimp_svg()
        );
	}

    /**
     * @return string
     */
    protected function mailchimp_svg()
    {
        return base64_encode('<?xml version="1.0" encoding="UTF-8"?>
<svg width="111px" height="116px" viewBox="0 0 111 116" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <!-- Generator: Sketch 50 (54983) - http://www.bohemiancoding.com/sketch -->
    <title>Group</title>
    <desc>Created with Sketch.</desc>
    <defs></defs>
    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <g id="Group">
            <path d="M76.5859,0.7017 L76.5849,0.7017 C71.6089,0.7017 65.7009,2.4987 59.7709,5.7927 C58.6459,4.8877 57.3339,3.8317 57.2839,3.7927 C55.0919,2.0667 52.4549,1.1907 49.4489,1.1897 C39.2019,1.1897 27.9399,11.1957 22.1389,17.1607 C12.9849,26.5727 5.6129,37.8427 2.4179,47.3087 C-1.5161,58.9627 1.8869,64.6287 5.4329,67.3307 L8.1899,69.4387 C6.2259,78.2517 11.1159,87.7027 19.6639,90.8437 C21.2849,91.4397 22.9629,91.7857 24.6639,91.8777 C28.2049,97.4237 41.6679,115.5347 66.4699,115.5417 C80.1529,115.5417 91.7799,109.9377 100.1089,99.3337 C105.7329,92.1747 108.1719,84.8867 108.8149,82.7097 C109.6379,81.3307 111.6759,77.2977 110.0739,73.4267 L110.0479,73.3637 L110.0189,73.3007 C109.1609,71.4697 107.6859,70.0227 105.8399,69.1577 C105.6629,68.5617 105.4829,68.0447 105.3149,67.6077 C106.7299,65.4787 107.0949,62.8477 106.2809,60.4207 C105.4089,57.8187 103.3169,55.7857 100.6029,54.8587 C98.0479,52.5407 95.7049,51.3697 93.9629,50.4987 L93.6009,50.3177 C93.1359,50.0857 92.8879,49.9617 92.6859,49.8647 C92.6089,49.1767 92.5369,48.3707 92.4619,47.5407 C92.2669,45.3737 92.0449,42.9177 91.6319,40.8417 C90.9369,37.3437 89.4899,34.6077 87.3279,32.6967 C86.8749,31.7957 86.2689,30.7457 85.4899,29.7127 C91.4609,19.6007 92.1439,10.1877 87.1759,4.8097 C84.6939,2.1217 81.0319,0.7017 76.5859,0.7017 M76.5859,3.7017 C80.0809,3.7017 83.0039,4.7137 84.9719,6.8447 C89.3919,11.6297 87.8029,20.7677 81.7689,30.0187 C83.0659,31.2507 84.1419,32.9257 84.9099,34.5887 C86.8279,36.0687 88.0709,38.3197 88.6899,41.4277 C89.3779,44.8837 89.5209,49.8057 89.9519,51.9207 C91.5789,52.6557 90.8869,52.3157 92.6209,53.1827 C94.4309,54.0867 96.6339,55.1877 99.0679,57.5307 C103.3969,58.6147 105.0489,63.2157 102.5169,66.3567 C102.4479,66.4407 102.1429,66.8027 101.8259,67.1627 C101.9009,67.3627 102.7319,68.7067 103.3309,71.4447 C105.1239,71.8687 106.5529,72.9727 107.3019,74.5737 C108.5989,77.7077 106.0479,81.4777 106.0479,81.4777 C105.9629,81.7547 97.9549,112.5417 66.4849,112.5417 L66.4709,112.5417 C39.2589,112.5337 26.3599,88.8807 26.3599,88.8807 C26.0989,88.8947 25.8389,88.9027 25.5799,88.9027 C23.8749,88.9027 22.2309,88.5907 20.6979,88.0277 C12.7149,85.0947 8.8839,75.9607 11.6389,68.2977 L7.2499,64.9447 C-8.0381,53.2977 29.3809,4.1887 49.4489,4.1897 C51.6919,4.1907 53.7189,4.8037 55.4309,6.1517 C55.5169,6.2197 59.4949,9.4217 59.4949,9.4217 C65.6079,5.7087 71.6629,3.7017 76.5849,3.7017 L76.5859,3.7017" id="Fill-40" fill="#FFFFFF"></path>
            <path d="M79.1367,27.5845 L79.1377,27.5835 C79.1297,27.5625 79.0647,27.3825 79.0387,27.3335 C78.0087,25.1455 75.6057,23.6175 73.4647,23.0045 C73.8397,23.3955 74.3827,24.1095 74.6037,24.5345 C72.9877,23.4245 70.8397,22.4465 68.5987,21.9825 C68.5987,21.9825 68.8657,22.1745 68.9117,22.2165 C69.3587,22.6325 69.9527,23.2905 70.1967,23.8555 C68.0547,22.9955 65.3467,22.5135 63.0297,22.9595 C62.9957,22.9655 62.7397,23.0265 62.7397,23.0265 C62.7397,23.0265 63.0407,23.1015 63.1087,23.1215 C63.8767,23.3495 64.9737,23.8175 65.4567,24.4595 C61.6097,23.7825 57.3767,24.5315 55.0947,25.8085 C55.3887,25.7955 55.3857,25.7945 55.6337,25.8015 C56.4727,25.8235 58.1647,25.9375 58.8747,26.3895 C56.4487,26.8825 52.9367,27.9685 51.0457,29.5945 C51.3787,29.5555 53.2737,29.3145 54.0487,29.4385 C43.6427,35.3935 38.9067,44.3965 38.9067,44.3965 C41.9747,40.3985 45.9547,36.5505 51.3827,33.0325 C63.2387,25.3485 75.0797,24.4655 79.1367,27.5845" id="Fill-42" fill="#FFFFFE"></path>
            <path d="M27.9473,80.7656 C26.3263,80.8026 25.4413,79.7896 25.6003,79.5686 C25.6743,79.4626 25.9213,79.5096 26.3013,79.5656 C28.3663,79.8866 29.6193,78.6266 29.9223,77.3066 C29.9263,77.2856 30.0083,76.9336 30.0043,76.6886 C30.0203,76.4766 29.9973,76.2606 29.9653,76.0676 C29.7203,74.7016 28.1543,74.4446 27.1473,73.3576 C26.2433,72.3756 26.4223,71.1166 26.9893,70.5126 C27.6723,69.8396 28.6483,70.0846 28.6353,70.3236 C28.6343,70.4496 28.3993,70.5446 28.1073,70.7446 C27.7283,71.0116 27.6783,71.2696 27.7743,71.7126 C27.8393,71.9556 27.9473,72.1156 28.1813,72.2996 C29.0283,72.9756 31.3043,73.4466 31.7183,75.7366 C32.1753,78.3656 30.2603,80.7166 27.9473,80.7656 M24.3623,68.1646 C23.9123,68.2606 24.1643,68.2056 23.7193,68.3306 C23.6503,68.3506 23.5873,68.3696 23.5323,68.3926 C23.3923,68.4586 23.2633,68.5036 23.1393,68.5696 C23.0363,68.6266 22.1513,69.0216 21.4313,69.9016 C20.4633,71.1046 20.1113,72.6796 20.1703,74.1956 C20.2283,75.6676 20.6603,76.4826 20.7403,76.6816 C21.0743,77.4006 20.2913,77.5476 19.5763,76.7756 L19.5743,76.7736 C19.0033,76.1676 18.6353,75.2456 18.4573,74.4276 L18.4573,74.4266 L18.4573,74.4276 C17.7313,71.0386 19.2443,67.6326 22.7353,66.2716 C22.9303,66.1946 23.1583,66.1466 23.3423,66.0926 L23.3413,66.0936 C23.6973,65.9876 24.9743,65.7286 26.2723,65.9306 C27.6973,66.1516 28.9563,66.8666 29.7543,67.7936 L29.7563,67.7956 C30.3703,68.4946 30.8333,69.4786 30.7503,70.3546 L30.7503,70.3566 C30.7193,70.7206 30.5573,71.2436 30.2293,71.3786 C30.1073,71.4286 29.9853,71.4026 29.8993,71.3186 C29.6623,71.0856 29.8453,70.6076 29.2663,69.7876 C28.4993,68.6996 26.7633,67.6566 24.3623,68.1646 M34.8423,74.1896 C34.9993,71.6386 34.5973,69.1156 33.8133,66.7166 C33.8133,66.7166 33.4683,68.1466 32.7823,69.8896 C32.1243,64.3126 29.7833,63.1316 29.7833,63.1316 C28.4263,62.5036 26.9273,62.1546 25.3503,62.1546 C19.2683,62.1546 14.3393,67.3446 14.3393,73.7476 C14.3393,80.1516 19.2683,85.3416 25.3503,85.3416 C32.2013,85.3416 37.4013,78.8196 36.1913,71.6936 C36.1913,71.6936 35.4923,73.3246 34.8423,74.1896" id="Fill-44" fill="#FFFFFF"></path>
            <path d="M55.2637,15.4038 C54.6187,15.8608 54.0497,16.2798 53.5687,16.6428 L50.2657,13.8778 L55.2637,15.4038 Z M57.7737,13.7038 C57.2307,14.0548 56.7147,14.3998 56.2307,14.7308 L55.3507,11.6728 L57.7737,13.7038 Z M18.5667,60.1648 L18.3907,60.1708 C22.0977,47.2248 32.0597,33.9258 42.2127,25.2628 C44.7447,23.1018 49.8377,19.6738 49.8377,19.6738 L44.0837,14.6028 L43.6637,11.6738 L51.5817,18.2108 C51.5807,18.2118 51.5797,18.2128 51.5797,18.2128 C51.2217,18.5058 50.8627,18.8068 50.5027,19.1138 C49.7227,19.7818 48.9407,20.4808 48.1597,21.2078 C47.0237,22.2638 45.8907,23.3788 44.7697,24.5368 C42.3547,27.0338 39.9957,29.7368 37.7857,32.5168 C33.8777,37.4318 32.2517,39.7248 29.7837,44.3968 C29.7727,44.4108 43.2807,28.2128 46.6437,24.8758 C52.8167,18.7528 62.9197,11.9348 63.0257,11.8738 C67.3387,9.2338 70.9807,7.6838 73.9687,7.0118 C69.0587,7.5128 63.8067,10.0368 59.6847,12.5108 C59.6777,12.5048 53.6927,7.4468 53.6847,7.4408 L51.4907,8.2808 L52.6747,12.5858 L51.2097,12.1118 L50.7927,10.4258 L51.4907,8.2808 C43.5097,6.3878 25.9397,19.6458 15.3837,36.4458 C11.1127,43.2428 5.1097,55.2008 8.5607,61.0238 C9.1437,62.0148 13.2437,65.0248 13.2867,65.0358 C14.8877,62.8148 16.7207,61.1818 18.5667,60.1648 Z" id="Fill-46" fill="#FFFFFF"></path>
            <path d="M62.7568,61.0156 C62.6058,60.8996 62.7548,60.4276 63.2788,59.9196 C63.7368,59.4816 64.2098,59.2346 64.7548,59.0046 C64.8408,58.9686 64.9288,58.9356 65.0208,58.9126 C65.2728,58.8456 65.5278,58.7596 65.8048,58.7176 C68.0218,58.3406 69.6438,59.5706 69.4328,59.9276 C69.3378,60.0936 68.9278,60.0566 68.3158,60.0156 C67.0468,59.9276 65.7148,59.9486 63.8318,60.7316 C63.2698,60.9576 62.9058,61.1346 62.7568,61.0156 M60.2968,57.2016 C59.4678,57.5176 58.8788,57.7626 58.5968,57.7206 C58.1368,57.6526 58.5818,56.8096 59.5878,55.9936 C61.6108,54.3786 64.3888,53.8776 66.7638,54.7616 C67.8028,55.1446 68.9698,55.9076 69.5798,56.8006 C69.8118,57.1386 69.8738,57.3946 69.7798,57.4996 C69.5938,57.7176 68.9288,57.4236 67.9448,57.0406 C65.4308,56.1026 63.5768,55.9596 60.2968,57.2016 M76.5508,58.4516 C76.8948,57.8226 77.8728,57.6966 78.7348,58.1676 C79.5958,58.6406 80.0158,59.5326 79.6708,60.1616 C79.3268,60.7886 78.3488,60.9156 77.4868,60.4436 C76.6258,59.9716 76.2058,59.0786 76.5508,58.4516 M80.1828,47.5176 L80.1818,47.5176 C80.1828,47.5176 80.1828,47.5176 80.1838,47.5176 C81.4978,47.0616 83.2388,50.4286 83.3188,53.4166 C82.2798,52.8976 81.0598,52.6766 79.8038,52.7856 C79.4878,51.8236 79.3368,50.9166 79.2998,49.9356 C79.2828,49.1196 79.4128,47.7686 80.1828,47.5176 M84.3258,57.6766 C84.1928,58.5366 83.5748,59.1536 82.9488,59.0556 C82.3218,58.9576 81.9228,58.1826 82.0578,57.3236 C82.1918,56.4636 82.8078,55.8466 83.4348,55.9446 C84.0608,56.0416 84.4598,56.8186 84.3258,57.6766 M101.6328,74.9486 C100.7498,74.9486 100.0828,75.1816 100.0828,75.1816 C100.0828,75.1816 100.0938,71.5046 98.3928,68.6416 C97.0818,70.1166 93.4438,73.0366 88.6678,75.3036 C84.1908,77.4296 78.2158,79.2906 70.8118,79.5376 C68.7378,79.6276 67.4548,79.2896 66.7338,81.7066 C66.6748,81.9226 66.6378,82.1646 66.6178,82.4116 C74.7338,84.9606 87.5698,79.9886 88.8158,79.6996 C88.8678,79.6876 88.9008,79.6806 88.9318,79.6796 C89.7358,79.7446 82.0398,84.5996 71.3608,84.5996 C69.5578,84.5996 68.0038,84.4426 66.7768,84.1966 C67.3608,86.1986 68.8858,87.0806 70.8948,87.5476 C72.4178,87.9026 74.0348,87.9366 74.0348,87.9366 C88.8308,88.3516 101.0058,76.8876 101.5088,76.2986 C101.5088,76.2986 101.3758,76.6096 101.3608,76.6446 C99.1918,81.5226 86.6338,90.3676 74.1338,90.1146 L74.0928,90.1276 C71.1948,90.1176 67.6718,89.3706 65.8408,87.1006 C62.9438,83.5076 64.4468,77.5356 69.0648,77.3946 C69.0698,77.3946 70.1318,77.3696 70.6048,77.3586 C82.0368,77.0066 92.2948,72.8066 99.6398,64.0356 C100.6498,62.7816 99.5218,61.0576 97.3328,61.0046 L97.3018,61.0046 C97.3018,61.0046 97.2888,60.9906 97.2808,60.9826 C94.6868,58.2026 92.4048,57.2216 90.3758,56.1906 C86.1208,54.0236 86.5218,55.8956 85.5308,45.3816 C85.2638,42.5476 84.7268,38.6816 82.2368,37.1996 C81.5838,36.8106 80.8708,36.6446 80.1298,36.6446 C79.3988,36.6446 79.0418,36.7936 78.8978,36.8256 C77.5338,37.1276 76.7578,37.9016 75.7858,38.7966 C71.1948,43.0306 67.5268,41.8816 62.0488,41.8326 C56.9048,41.7876 52.3628,45.3756 51.4948,50.8866 L51.4928,50.8896 C51.0708,53.7746 51.3448,56.7046 51.7958,58.0226 C51.7958,58.0226 50.4008,57.0936 49.7348,56.2716 C50.5398,61.3056 55.1328,64.5956 55.1328,64.5956 C54.4088,64.7616 53.3688,64.6926 53.3688,64.6926 C53.3728,64.6966 55.9898,66.7666 58.2618,67.4886 C57.6698,67.8566 54.6758,70.8106 53.1318,74.8776 C51.6878,78.6836 52.2758,83.2536 52.2758,83.2536 L53.5298,81.3826 C53.5298,81.3826 52.7108,85.5516 54.2968,89.5886 C54.8228,88.3886 55.9738,86.2446 55.9738,86.2446 C55.9738,86.2446 55.7908,90.7206 57.9458,94.3866 C58.0018,93.5406 58.3248,91.3866 58.3248,91.3866 C58.3248,91.3866 59.5628,95.2086 62.4188,97.8816 C67.7678,102.6276 81.9298,103.4856 92.7078,95.0156 C101.2448,88.3076 102.7358,80.2286 102.8788,79.9506 C104.9798,77.4306 104.4078,74.9486 101.6328,74.9486" id="Fill-48" fill="#FFFFFF"></path>
        </g>
    </g>
</svg>');
    }

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
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

		$this->handle_abandoned_cart_table();

		register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
	}

	/**
	 * Depending on the version we're on we may need to run some sort of migrations.
	 */
	public function update_db_check() {
		// grab the current version set in the plugin variables
		$version = mailchimp_environment_variables()->version;

		// grab the saved version or default to 1.0.3 since that's when we first did this.
		$saved_version = get_site_option('mailchimp_woocommerce_version', '1.0.3');

		// if the saved version is less than the current version
		if (version_compare($version, $saved_version) > 0) {
			// resave the site option so this only fires once.
			update_site_option('mailchimp_woocommerce_version', $version);
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
                created_at datetime NOT NULL
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

		if (empty($active_tab)) {
			return $this->getOptions();
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
                // remove all the pointers to be sure
                $service = new MailChimp_Service();
                $service->removePointers(true, true);

                $this->startSync();
                $this->showSyncStartedMessage();
                $this->setData('sync.config.resync', true);
				break;

            case 'logs':

                if (isset($_POST['log_file']) && !empty($_POST['log_file'])) {
                    set_site_transient('mailchimp-woocommerce-view-log-file', $_POST['log_file'], 30);
                }
                
                $data = array(
                    'mailchimp_logging' => isset($input['mailchimp_logging']) ? $input['mailchimp_logging'] : 'none',
                );

                if (isset($_POST['mc_action']) && in_array($_POST['mc_action'], array('view_log', 'remove_log'))) {
                    $path = 'options-general.php?page=mailchimp-woocommerce&tab=logs';
                    wp_redirect($path);
                    exit();
                }

                break;
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
        } catch (Exception $e) {
            unset($data['mailchimp_api_key']);
            $data['active_tab'] = 'api_key';
            add_settings_error('mailchimp_store_settings', $e->getCode(), $e->getMessage());
            return $data;
        }

		return $data;
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

			return array();
		}

		$this->setData('validation.store_info', true);

        $data['active_tab'] = 'campaign_defaults';

		if ($this->hasValidMailChimpList()) {
			$this->syncStore(array_merge($this->getOptions(), $data));
		}

		return $data;
	}

    /**
     * @param $input
     * @return array
     */
	protected function compileStoreInfoData($input)
    {
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
            'store_timezone' => isset($input['store_timezone']) ? $input['store_timezone'] : false,
            'store_currency_code' => isset($input['store_currency_code']) ? $input['store_currency_code'] : false,
            'admin_email' => isset($input['admin_email']) && is_email($input['admin_email']) ? $input['admin_email'] : $this->getOption('admin_email', false),
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
        add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a contact email and a physical mailing address.');
    }

    /**
     *
     */
    protected function addInvalidPhoneAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'As part of the MailChimp Terms of Use, we require a valid phone number for your store.');
    }

    /**
     *
     */
    protected function addInvalidStoreNameAlert()
    {
        add_settings_error('mailchimp_store_settings', '', 'MailChimp for WooCommerce requires a Store Name to connect your store.');
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
			'campaign_permission_reminder' => isset($input['campaign_permission_reminder']) ? $input['campaign_permission_reminder'] : 'You were subscribed to the newsletter from '.get_option('blogname'),
		);

		if (!$this->hasValidCampaignDefaults($data)) {
			$this->setData('validation.campaign_defaults', false);

			return array('active_tab' => 'campaign_defaults');
		}

		$this->setData('validation.campaign_defaults', true);

        $data['active_tab'] = 'newsletter_settings';

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

		$data = array(
			'mailchimp_list' => isset($input['mailchimp_list']) ? $input['mailchimp_list'] : $this->getOption('mailchimp_list', ''),
			'newsletter_label' => isset($input['newsletter_label']) ? $input['newsletter_label'] : $this->getOption('newsletter_label', 'Subscribe to our newsletter'),
			'mailchimp_auto_subscribe' => isset($input['mailchimp_auto_subscribe']) ? (bool) $input['mailchimp_auto_subscribe'] : $this->getOption('mailchimp_auto_subscribe', '0'),
			'mailchimp_checkbox_defaults' => $checkbox,
			'mailchimp_checkbox_action' => isset($input['mailchimp_checkbox_action']) ? $input['mailchimp_checkbox_action'] : $this->getOption('mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form'),
            'mailchimp_product_image_key' => isset($input['mailchimp_product_image_key']) ? $input['mailchimp_product_image_key'] : 'medium',
        );

		if ($data['mailchimp_list'] === 'create_new') {
			$data['mailchimp_list'] = $this->createMailChimpList(array_merge($this->getOptions(), $data));
		}

		// as long as we have a list set, and it's currently in MC as a valid list, let's sync the store.
		if (!empty($data['mailchimp_list']) && $this->api()->hasList($data['mailchimp_list'])) {

            $this->setData('validation.newsletter_settings', true);

			// sync the store with MC
			$this->syncStore(array_merge($this->getOptions(), $data));

			// start the sync automatically if the sync is false
			if ((bool) $this->getData('sync.started_at', false) === false) {
				$this->startSync();
				$this->showSyncStartedMessage();
			}

            $data['active_tab'] = 'sync';

            return $data;
		}

        $this->setData('validation.newsletter_settings', false);

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
			'store_locale', 'store_timezone', 'store_currency_code',
			'store_phone',
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
	 * @param null|array $data
	 * @return bool
	 */
	public function hasValidApiKey($data = null)
	{
		if (!$this->validateOptions(array('mailchimp_api_key'), $data)) {
			return false;
		}

		if (($pinged = $this->getCached('api-ping-check', null)) === null) {
			if (($pinged = $this->api()->ping())) {
				$this->setCached('api-ping-check', true, 120);
			}
			return $pinged;
		}
		return $pinged;
	}

	/**
	 * @return bool
	 */
	public function hasValidMailChimpList()
	{
		if (!$this->hasValidApiKey()) {
			add_settings_error('mailchimp_api_key', '', 'You must supply your MailChimp API key to pull the lists.');
			return false;
		}

		if (!($this->validateOptions(array('mailchimp_list')))) {
			return $this->api()->getLists(true);
		}

		return $this->api()->hasList($this->getOption('mailchimp_list'));
	}


	/**
	 * @return array|bool|mixed|null
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

	/**
	 * @param null|array $data
	 * @return bool|string
	 */
	private function createMailChimpList($data = null)
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
				return false;
			}
		}

		$submission = new MailChimp_WooCommerce_CreateListSubmission();

		// allow the subscribers to choose preferred email type (html or text).
		$submission->setEmailTypeOption(true);

		// set the store name
		$submission->setName($data['store_name']);

		// set the campaign defaults
		$submission->setCampaignDefaults(
			$data['campaign_from_name'],
			$data['campaign_from_email'],
			$data['campaign_subject'],
			$data['campaign_language']
		);

		// set the permission reminder message.
		$submission->setPermissionReminder($data['campaign_permission_reminder']);

		if (isset($data['admin_email']) && !empty($data['admin_email'])) {
			$submission->setNotifyOnSubscribe($data['admin_email']);
			$submission->setNotifyOnUnSubscribe($data['admin_email']);
		}

		$submission->setContact($this->address($data));

		try {
			$response = $this->api()->createList($submission);

			$list_id = array_key_exists('id', $response) ? $response['id'] : false;

			$this->setData('errors.mailchimp_list', false);

			return $list_id;

		} catch (MailChimp_WooCommerce_Error $e) {
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
		$store->setTimezone($this->array_get($data, 'store_timezone', 'America\New_York'));
		$store->setCurrencyCode($this->array_get($data, 'store_currency_code', 'USD'));

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
			// let's create a new store for this user through the API
			$this->api()->$call($store);

			// apply extra meta for store created at
			$this->setData('errors.store_info', false);
			$this->setData($time_key, time());

			// on a new store push, we need to make sure we save the site script into a local variable.
			if ($new) {
                mailchimp_update_connected_site_script();
            }

			return true;

		} catch (\Exception $e) {
			$this->setData('errors.store_info', $e->getMessage());
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

		$address->setCountryCode($this->array_get($data, 'store_currency_code', 'USD'));

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
	private function startSync()
	{
	    mailchimp_flush_sync_pointers();

	    $coupon_sync = new MailChimp_WooCommerce_Process_Coupons();
	    wp_queue($coupon_sync);

		$job = new MailChimp_WooCommerce_Process_Products();
		$job->flagStartSync();
		wp_queue($job);
	}

	/**
	 * Show the sync started message right when they sync things.
	 */
	private function showSyncStartedMessage()
	{
		$text = 'Starting the sync process…<br/>'.
			'<p id="sync-status-message">Please hang tight while we work our mojo. Sometimes the sync can take a while, '.
			'especially on sites with lots of orders and/or products. You may refresh this page at '.
			'anytime to check on the progress.</p>';

		add_settings_error('mailchimp-woocommerce_notice', $this->plugin_name, __($text), 'updated');
	}
}
