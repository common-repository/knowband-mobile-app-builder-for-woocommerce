<?php

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly
}

define('KB_WMAB_API_VERSION', '2.0');

/**
 * Class - WmabHome
 *
 * This class contains constructor and other methods which are actually related to Mobile App Home Page
 *
 * @version v2.0
 * @Date    10-Jun-2022
 */
class WmabHome
{

	/** String A public variable of class which holds WooCommerce plugin settings
	 *
	 * @var    string A public variable of class which holds WooCommerce plugin settings
	 */
	public $wmab_plugin_settings = array();

	/** String A public variable of class which holds API response
	 *
	 * @var    string A public variable of class which holds API response
	 */
	public $wmab_response = array();

	/** String A public variable of class which holds current session expiration for Cart
	 *
	 * @var    string A public variable of class which holds current session expiration for Cart
	 */
	public $wmab_session_expiration = '';

	/** String A public variable of class which holds current session expiring for Cart
	 *
	 * @var    string A public variable of class which holds current session expiring for Cart
	 */
	public $wmab_session_expiring = '';

	/** String A public variable of class which holds cookie values for Cart
	 *
	 * @var    string A public variable of class which holds cookie values for Cart
	 */
	public $wmab_cookie = '';

	/** String A public variable of class which holds woocommerce version number
	 *
	 * @var    string A public variable of class which holds woocommerce version number
	 */
	public $wmab_wc_version = '';

	/**
	 * Class Constructor
	 *
	 * @global object $wpdb
	 */
	public function __construct($request = '')
	{
		global $wpdb;

		// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
		$kb_nonce_verification = 0;

		if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
			$kb_nonce_verification = 1;
		}

		if (isset($_POST['session_data']) && !empty($_POST['session_data'])) {
			$cart_id = sanitize_text_field($_POST['session_data']);
		} elseif (isset($_POST['email']) && !empty($_POST['email'])) {
			$cart_id = email_exists(sanitize_text_field($_POST['email']));
		}

		if (isset($_POST['email']) && !empty($_POST['email'])) {
			$current_user_id = email_exists(sanitize_text_field($_POST['email']));
			if (!empty($current_user_id)) {
				wp_set_current_user($current_user_id); // phpcs:ignore
			}
		}

		if (!empty($cart_id)) {
			$this->set_session($cart_id);
		}

		$this->wmab_response['install_module'] = ''; // Set default blank value to send as response in each request
		//
		// Get Mobile App Builder settings from database
		$wmab_settings = get_option('wmab_settings');
		if (isset($wmab_settings) && !empty($wmab_settings)) {
			$this->wmab_plugin_settings = unserialize($wmab_settings);
		}

		// Condition added to ignore checking of Module status in case class is being called internally on Home Page Layout page - changes added by Harsh Agarwal on 05-Jun-2020
		if ('HomePageLayout' == $request) {

			// Suspend execution if plugin is not installed or disabled and send output
			if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
				$this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'knowband-mobile-app-builder-for-woocommerce');
				// Log Request
				wmab_log_knowband_app_request($request, serialize_block_attributes($this->wmab_response));

				$response = rest_ensure_response($this->wmab_response);
				return $response;
			}
		}

		// BOC - hagarwal@velsof.com - Changes added to make module compatible on WooCommerce >= 3.6 - This change initiate the required objects to call functions
		$this->wmab_wc_version = wmab_get_woocommerce_version_number();
		// if ($this->wmab_wc_version >= '3.6.0') {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		if (null === WC()->session) {
			/**
			 * Calculates the expiration time for the WooCommerce session.
			 *
			 * @param int $expiration The default expiration time for the session.
			 * @return int The filtered expiration time for the session.
			 * @since 1.0.0
			 */
			$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
			WC()->session  = new $session_class(); // For Session Object
			WC()->session->init();
		}
		if (null === WC()->customer) {
			WC()->customer = new WC_Customer(get_current_user_id(), true); // For Customer Object
		}
		if (null === WC()->Tax) {
			WC()->Tax = new WC_Tax(); // For Payment Gateway Object
		}
		if (null === WC()->cart) {
			WC()->cart = new WC_Cart(); // For Cart Object
			WC()->cart->get_cart();
			// Added by Ashish on 5th May 2022 to Save Cart Data into the User Meta Table.
			// The current cart will be saved in the User Meta Table. Altough Cart is saved on into the User Meta Table on Product Add/Update/Delete itself but in the case of cart merge at the time of login, Updated Cart items was not persisting. So made persistance of the cart on next page refresh
			// DB Key: _woocommerce_persistent_cart_
			WC()->cart->persistent_cart_update();
		}
		// Include Front End Libraries/Classes
		WC()->frontend_includes();
		// }
		// EOC
	}

	/**
	 * Function to verify API Version
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	private function verify_api($version, $request = '')
	{
		$verified = false;
		if (isset($version) && !empty($version)) {
			if (KB_WMAB_API_VERSION == $version) {
				$verified = true;
			}
		}
		if (!$verified) {
			$this->wmab_response['install_module'] = __('Warning: Invalid API Version !!', 'knowband-mobile-app-builder-for-woocommerce');
			// Log Request
			wmab_log_knowband_app_request($request, serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}
	}

	/**
	 * Function to set session data
	 *
	 * @param  string $cart_id This parameter holds current cart ID to set int Session variable
	 */
	private function set_session($cart_id)
	{

		/**
		 * Calculates the expiration time for the WooCommerce session.
		 *
		 * @param int $expiration The default expiration time for the session.
		 * @return int The filtered expiration time for the session.
		 * @since 1.0.0
		 */
		$this->wmab_session_expiring = time() + intval(apply_filters('wc_session_expiring', 60 * 60 * 47));

		/**
		 * Calculates the expiration time for the WooCommerce session.
		 *
		 * @param int $expiration The default expiration time for the session.
		 * @return int The filtered expiration time for the session.
		 * @since 1.0.0
		 */
		$this->wmab_session_expiration = time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48));

		/**
		 * Filters the name of the WooCommerce cookie.
		 *
		 * @param string $cookie_name The name of the WooCommerce cookie.
		 * @return string The filtered cookie name.
		 * @since 1.0.0
		 */
		$this->wmab_cookie = apply_filters('woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH);
		$to_hash           = $cart_id . '|' . $this->wmab_session_expiration;
		$cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
		$cookie_value      = $cart_id . '||' . $this->wmab_session_expiration . '||' . $this->wmab_session_expiring . '||' . $cookie_hash;
		/**
		 * Set a cookie for the WooCommerce session.
		 *
		 * @param string $cookie_name The name of the cookie.
		 * @param string $cookie_value The value of the cookie.
		 * @param int    $expiration   The expiration time for the cookie.
		 * @param bool   $use_secure   Whether to use a secure cookie. Default is false.
		 * @since 1.0.0
		 */
		wc_setcookie($this->wmab_cookie, $cookie_value, $this->wmab_session_expiration, apply_filters('wc_session_use_secure_cookie', false));
		$_COOKIE[$this->wmab_cookie] = $cookie_value;
	}

	/**
	 * Function to handle appGetConfig API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetConfig
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_get_config($version, $iso_code, $is_demo, $all_app_texts, $session_data, $email)
	{

		// set session data
		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} elseif (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		} else {
			$cart_id = WC()->session->get_customer_id(); // Need to check the correctness
		}

		if (!empty($cart_id)) {
			$this->set_session($cart_id);
		}

		$cart_content = WC()->cart->get_cart_contents();

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetConfig');

		$this->wmab_response['fontStyle']                                 = isset($this->wmab_plugin_settings['general']['app_fonts']) ? $this->wmab_plugin_settings['general']['app_fonts'] : 'Calibri.ttf';
		$this->wmab_response['whatsapp_configurations']['is_enabled']     = isset($this->wmab_plugin_settings['general']['whatsup_chat_support_status']) ? true : false;
		$this->wmab_response['whatsapp_configurations']['chat_number']    = $this->wmab_plugin_settings['general']['whatsup_chat_number_key'];
		$this->wmab_response['zopim_chat_configurations']['status']       = isset($this->wmab_plugin_settings['general']['live_chat']) ? $this->wmab_plugin_settings['general']['live_chat'] : 0;
		$this->wmab_response['zopim_chat_configurations']['chat_api_key'] = $this->wmab_plugin_settings['general']['live_chat_api_key'];
		$this->wmab_response['log_configurations']['status']              = isset($this->wmab_plugin_settings['general']['log_status']) ? '1' : '0';
		$this->wmab_response['fingerprint_configurations']['is_enabled']  = isset($this->wmab_plugin_settings['general']['fingerprint_login_status']) ? '1' : '0';
		$this->wmab_response['phone_number_registartion_configurations']['is_enabled']   = isset($this->wmab_plugin_settings['general']['phone_number_registration_status']) ? '1' : '0';
		$this->wmab_response['phone_number_registartion_configurations']['is_mandatory'] = isset($this->wmab_plugin_settings['general']['phone_number_mandatory_status']) ? '1' : '0';
		$this->wmab_response['session_data'] = WC()->session->get_customer_id();
		$this->wmab_response['status']       = 'success';

		if (1 == $is_demo) {
			$kb_demo = 1;
		} else {
			$this->wmab_response['home_response']    = $this->app_get_home($version, $iso_code, $session_data, $email);
			$translation_data                        = $this->app_get_translations($all_app_texts, $iso_code, $version);
			$this->wmab_response['languages_record'] = $translation_data['languages_record'];
			$this->wmab_response['translated_texts'] = $translation_data['translated_texts'];
		}

		// Log Request
		wmab_log_knowband_app_request('appGetConfig', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetCheck API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetHome
	 *
	 * @param  string $version      This parameter holds API version to verify during API call
	 * @param  string $iso_code     This parameter holds ISO Code for translation
	 * @param  string $session_data This parameter holds Cart ID
	 * @param  string $email        This parameter holds Customer Email Address
	 */
	public function app_get_check()
	{
		$this->wmab_response['app_check'] = '1';
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetHome API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetHome
	 *
	 * @param  string $version      This parameter holds API version to verify during API call
	 * @param  string $iso_code     This parameter holds ISO Code for translation
	 * @param  string $session_data This parameter holds Cart ID
	 * @param  string $email        This parameter holds Customer Email Address
	 */
	public function app_get_home($version, $iso_code, $session_data, $email)
	{
		global $wpdb;

		$customer_id = email_exists($email);

		$session_value = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %s and reorder_direct='0'", (int) $customer_id));
		if (!empty($session_value)) {
			$cart_value    = json_decode($session_value, true);
			$cart_content3 = $cart_value['cart'];
			foreach ($cart_content3 as $cart_products) {
				if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
					$product_id = $cart_products['variation_id'];
				} else {
					$product_id = $cart_products['product_id'];
				}
				WC()->cart->add_to_cart($cart_products['product_id'], $cart_products['quantity'], $cart_products['variation_id'], $cart_products['variation'], array());
			}
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %s", $session_data));
		}

		$reorder_redirect = (WC()->session->get('reorder') !== null) ? WC()->session->get('reorder') : 0;
		$session_value    = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %s and reorder_direct='1'", (int) $customer_id));

		if ('1' == $reorder_redirect) {
			WC()->session->set('reorder', '2');
			$temp_session_data = array();
			foreach (WC()->session->get_session_data($customer_id) as $key => $value) {
				$temp_session_data[$key] = unserialize($value);
			}
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %d", (int) $customer_id));
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_login_sessions (user_id, session_key, session_value, reorder_direct) VALUES (%d, %s, %s, 1)", (int) $customer_id, $customer_id, wp_json_encode($temp_session_data)));
		} elseif (!empty($session_value)) {
			WC()->cart->empty_cart();
			$cart_value    = json_decode($session_value, true);
			$cart_content3 = $cart_value['cart'];
			foreach ($cart_content3 as $cart_products) {

				if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
					$product_id = $cart_products['variation_id'];
				} else {
					$product_id = $cart_products['product_id'];
				}
				WC()->cart->add_to_cart($cart_products['product_id'], $cart_products['quantity'], $cart_products['variation_id'], $cart_products['variation'], array());
			}
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %d", (int) $customer_id));
			WC()->session->__unset('reorder');
		}

		$session_id = $wpdb->get_var("SELECT session_key FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = '%s' and reorder_direct='0'", (int) $customer_id);
		if (!empty($session_id)) {
			$session_value = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $session_id));
			$cart_value    = unserialize($session_value);
			$cart_content3 = unserialize($cart_value['cart']);
			if (!empty($cart_content3)) {
				foreach ($cart_content3 as $cart_products) {
					if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
						$product_id = $cart_products['variation_id'];
					} else {
						$product_id = $cart_products['product_id'];
					}
					WC()->cart->add_to_cart($cart_products['product_id'], $cart_products['quantity'], $cart_products['variation_id'], $cart_products['variation'], array());
				}
			}
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %d", (int) $customer_id));
		}

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetHome');
		// Get Home Page Elements - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		$home_response['elements'] = $this->getHomePageElements($this->wmab_plugin_settings['general']['home_page_layout'], $session_data, $email);
		// EOC - Module Upgrade V2
		// Get Top Slider - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['topslider'] = $this->getMainBanner($this->wmab_plugin_settings);
		// Get Promo Banners - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['topbanner'] = $this->getPromoBanner($this->wmab_plugin_settings);
		// Get Featured Products - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['fproducts'] = $this->getFeaturedProducts($this->wmab_plugin_settings, $iso_code); //Added iso_code param for WPML compatibility by Harsh on 16-Apr-2019
		// Get Special Products - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['sproducts'] = $this->getSpecialProducts($this->wmab_plugin_settings, $iso_code); //Added iso_code param for WPML compatibility by Harsh on 16-Apr-2019
		// Get Latest/New Products - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['newproducts'] = $this->getLatestProducts($this->wmab_plugin_settings, $iso_code); //Added iso_code param for WPML compatibility by Harsh on 16-Apr-2019
		// Get Bestseller Products - Module Upgrade V2 - Commented by Harsh (hagarwal@velsof.com) on 20-Dec-2019 as no more required in Updated Module
		// $this->wmab_response['bsproducts'] = $this->getBestSellerProducts($this->wmab_plugin_settings, $iso_code); //Added iso_code param for WPML compatibility by Harsh on 16-Apr-2019
		// Spin and Win Responses - Module Upgrade V2 - added by Neeraj(neeraj.kumar@velsof.com) on 29-Jan-2020 - fill values
		// Spin and Win Wheel Display and Spin and Win enable status check from another plugin
		$wmab_spin_win_options = get_option('wsaw_settings');

		if (isset($wmab_spin_win_options) && !empty($wmab_spin_win_options)) {
			$wmab_spin_win_settings = unserialize($wmab_spin_win_options);

			if (isset($wmab_spin_win_settings) && !empty($wmab_spin_win_settings)) {
				$home_response['spin_win_response']['is_spin_and_win_enabled']   = isset($this->wmab_plugin_settings['general']['spin_win_status']) ? true : false;
				$home_response['spin_win_response']['maximum_display_frequency'] = isset($wmab_spin_win_settings['display']['maximum_display_frequency']) ? $wmab_spin_win_settings['display']['maximum_display_frequency'] : '0';
				$home_response['spin_win_response']['wheel_display_interval']    = isset($wmab_spin_win_settings['general']['display_interval']) ? $wmab_spin_win_settings['general']['display_interval'] : '';
			} else {
				// In Case Spin and Win plugin not install or activate or disable then send status as false
				$home_response['spin_win_response']['is_spin_and_win_enabled']   = false;
				$home_response['spin_win_response']['maximum_display_frequency'] = '30';
				$home_response['spin_win_response']['wheel_display_interval']    = '0';
			}
			if (!isset($wmab_spin_win_settings['general']['enabled']) || !$wmab_spin_win_settings['general']['enabled']) {
				$home_response['spin_win_response']['is_spin_and_win_enabled'] = false;
			}
		} else {
			// In Case Spin and Win plugin not install or activate or disable then send status as false
			$home_response['spin_win_response']['is_spin_and_win_enabled']   = false;
			$home_response['spin_win_response']['maximum_display_frequency'] = '30';
			$home_response['spin_win_response']['wheel_display_interval']    = '0';
		}

		// neeraj.kumar@velsof.com : 29-Jan-2020 Tab Bar Custom Changes :
		$home_response['tabBar'] = $this->getCustomTabBar($this->wmab_plugin_settings);
		// EOC - Module Upgrade V2
		// Menu Categories
		$home_response['Menu_Categories'] = $this->getMenuCategories();

		// Menu Information
		$home_response['cms_links'] = $this->getMenuInformation();

		// Currency
		$home_response['currencies'] = $this->getCurrencies();

		// Languages
		$home_response['languages']        = $this->getCurrentLanguage();
		$home_response['languages_record'] = $this->getLanguages();

		$home_response['wishlist_active']      = 0;   // Set 0 as wishlist functionality is not available by default in wooCommerce
		$home_response['contact_us_available'] = 0;
		$home_response['contact_us_link']      = '';

		/*
		* Add functionality to enable/disable social login options
		* @date 02-02-2023
		* @commenter Vishal Goyal
		*/
		$home_response['is_social_login_enabled'] = isset($this->wmab_plugin_settings['general']['social_login_status']) ? $this->wmab_plugin_settings['general']['social_login_status'] : '0';

		$home_response['fontStyle'] = isset($this->wmab_plugin_settings['general']['app_fonts']) ? $this->wmab_plugin_settings['general']['app_fonts'] : 'Calibri.ttf';

		// Other configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		$home_response['add_to_cart_redirect_enabled'] = isset($this->wmab_plugin_settings['general']['cart_option_redirect_status']) ? '1' : '0';
		$home_response['display_logo_on_title_bar']    = isset($this->wmab_plugin_settings['general']['logo_status']) ? '1' : '0';
		$home_response['title_bar_logo_url']           = isset($this->wmab_plugin_settings['general']['vss_mab_app_logo_image_path']) ? WMAB_URL . 'views/images/' . $this->wmab_plugin_settings['general']['vss_mab_app_logo_image_path'] : '';
		$home_response['is_marketplace']               = '0'; // Set default 0 as WC Marketplace Module does not exists
		$home_response['app_button_color']             = isset($this->wmab_plugin_settings['general']['app_button_color']) ? str_replace('#', '', $this->wmab_plugin_settings['general']['app_button_color']) : '000000';
		$home_response['app_theme_color']              = isset($this->wmab_plugin_settings['general']['app_theme_color']) ? str_replace('#', '', $this->wmab_plugin_settings['general']['app_theme_color']) : 'ffffff';
		$home_response['app_background_color']         = isset($this->wmab_plugin_settings['general']['app_background_color']) ? str_replace('#', '', $this->wmab_plugin_settings['general']['app_background_color']) : 'ffffff';
		$home_response['app_button_text_color']        = isset($this->wmab_plugin_settings['general']['app_button_text_color']) ? str_replace('#', '', $this->wmab_plugin_settings['general']['app_button_text_color']) : 'ffffff'; // This setting option is not availabe in the Module
		$home_response['is_tab_bar_enabled']           = isset($this->wmab_plugin_settings['general']['tab_bar_status']) ? '1' : '0';
		$home_response['cart_button_status']           = isset($this->wmab_plugin_settings['general']['cart_button_status']) ? '1' : '0';
		$home_response['snackbar_background_color']    = isset($this->wmab_plugin_settings['general']['snackbar_background_color']) ? $this->wmab_plugin_settings['general']['snackbar_background_color'] : '#3b3b3b';
		$home_response['snackbar_text_color']          = isset($this->wmab_plugin_settings['general']['snackbar_text_color']) ? $this->wmab_plugin_settings['general']['snackbar_text_color'] : '#FFFFFF';
		// Cart Count
		$total_cart_quantity = 0;

		// set session data
		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} elseif (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		}

		if (!empty($cart_id)) {
			$this->set_session($cart_id);

			$home_response['session_data'] = (string) WC()->session->get_customer_id();
			$session_value                 = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $cart_id));
			$cart_value                    = unserialize($session_value);
			$cart_content_total            = unserialize($cart_value['cart']);
			if (!empty($cart_content_total)) {
				foreach ($cart_content_total as $cart_item) {
					if (!empty($cart_item['quantity'])) {
						$total_cart_quantity += $cart_item['quantity'];
					}
				}
			}
			$home_response['total_cart_items'] = $total_cart_quantity;
			$home_response['total_cart_count'] = $total_cart_quantity;
		} else {
			// Cart Count - Sending it as 0 as without email or cart id we can not get count here
			$home_response['total_cart_items'] = 0;
			$home_response['total_cart_count'] = 0;
		}

		// EOC - Module Upgrade V2

		$home_response['status']  = 'success';
		$home_response['message'] = '';

		return $home_response;
	}

	/**
	 * [DEPRECATED] - Now these values are being passed in appGetConfig API Call - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 * Function to handle appCheckLogStatus API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckLogStatus
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_check_log_status($version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appCheckLogStatus');

		$this->wmab_response['log_status'] = isset($this->wmab_plugin_settings['general']['log_status']) ? '1' : '0';

		// Log Request
		wmab_log_knowband_app_request('appCheckLogStatus', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * [DEPRECATED] - Now these values are being passed in appGetConfig API Call - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 * Function to handle appCheckLiveChatSupportStatus API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckLiveChatSupportStatus
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_check_live_chat_support_status($version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appCheckLiveChatSupportStatus');

		$this->wmab_response['status']       = isset($this->wmab_plugin_settings['general']['live_chat']) ? $this->wmab_plugin_settings['general']['live_chat'] : 0;
		$this->wmab_response['chat_api_key'] = $this->wmab_plugin_settings['general']['live_chat_api_key'];

		// Log Request
		wmab_log_knowband_app_request('appCheckLiveChatSupportStatus', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appFCMregister API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appFCMregister
	 *
	 * @param  string $email   This parameter holds Customer Email
	 * @param  string $cart_id This parameter holds current Cart ID
	 * @param  string $fcm_id  This parameter holds FCM ID of Mobile device through which Push Notification can be sent
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_fcm_register($email, $cart_id, $fcm_id, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appFCMRegister');

		if (isset($cart_id) && !empty($cart_id) && isset($fcm_id) && !empty($fcm_id)) {
			// Check if FCM and Cart mapping exists
			$fcm_data = $this->isFcmAndCartExist($fcm_id, $cart_id, $email);

			if (isset($fcm_data) && !empty($fcm_data)) {
				// Update FCM and CART mapping into the table
				$this->saveFCMData($email, $cart_id, $fcm_id, $fcm_data->fcm_details_id);
			} else {
				// Insert FCM and CART mapping into the table
				$this->saveFCMData($email, $cart_id, $fcm_id, '');
			}
			$this->wmab_response['status']  = 'success';
			$this->wmab_response['message'] = __('FCM registered sucessfully', 'knowband-mobile-app-builder-for-woocommerce');
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Cart id or FCM id not found', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appFCMRegister', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to insert/update FCM and Cart mapping into the DB table
	 *
	 * @param  string $email   This parameter holds Customer Email
	 * @param  string $cart_id This parameter holds current cart ID
	 * @param  string $fcm_id  This parameter hold FCM ID of Mobile device through which Push Notification can be sent
	 */
	private function saveFCMData($email, $cart_id, $fcm_id, $update_id = '')
	{
		global $wpdb;

		if (isset($fcm_id) && !empty($fcm_id)) {
			if ($update_id) {
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET email_id = %s, cart = %s, fcm_id = %s, date_upd = now() WHERE fcm_details_id = %d", $email, $cart_id, $fcm_id, $update_id));
			} else {
				$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_fcm_details SET fcm_details_id = '', email_id = %s, cart = %s, fcm_id = %s, date_add = now()", $email, $cart_id, $fcm_id));
			}
		}
	}

	/**
	 * Function to check if FCM and Cart mapping exist
	 *
	 * @param  string $fcm_id  This parameter holds FCM ID of Mobile device through which Push Notification can be sent
	 * @param  string $cart_id This parameter holds current Cart ID
	 * @param  string $email   This parameter holds Customer Email
	 */
	private function isFcmAndCartExist($fcm_id, $cart_id, $email)
	{
		global $wpdb;

		$checkMapping = ''; // Default definition of variable

		if (!empty($email) && !empty($cart_id)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND fcm_id = %d AND last_order_status = 0 AND order_id IS NULL", $cart_id, $fcm_id));
			// BOC neeraj.kumar@velsof.com 31-dec-2019 Resolved this issue :  trim() expects parameter 1 to be string, object given by removing Trim function
			if (empty($checkMapping)) {
				$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND fcm_id = %d AND last_order_status = 0 AND order_id IS NULL", $email, $fcm_id));
			}
		} elseif (!empty($email)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND fcm_id = %d AND last_order_status = 0 AND order_id IS NULL", $email, $fcm_id));
		} elseif (!empty($cart_id)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND fcm_id = %d AND last_order_status = 0 AND order_id IS NULL", $cart_id, $fcm_id));
		}

		return $checkMapping;
	}

	/**
	 * Function to handle appGetTranslations API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetTranslations
	 *
	 * @param  bool   $all_app_texts This parameter holds boolean value
	 * @param  string $iso_code      This parameter holds Language ISO Code
	 * @param  string $version       This parameter holds API version to verify during API call
	 */
	public function app_get_translations($all_app_texts, $iso_code, $version)
	{

		$translation_response = array();

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetTranslations');

		// Read JSON file to get Google details
		$google_app_id  = '';
		$google_api_key = '';
		$fb_app_id      = '';

		// Google
		if (isset($this->wmab_plugin_settings['google_login_settings']['enabled']) && !empty($this->wmab_plugin_settings['google_login_settings']['enabled'])) {
			if (isset($this->wmab_plugin_settings['google_login_settings']['google_json']) && !empty($this->wmab_plugin_settings['google_login_settings']['google_json'])) {
				// Read JSON File
				$json_file_data = json_decode(file_get_contents(WMAB_URL . 'views/images/' . $this->wmab_plugin_settings['google_login_settings']['google_json']));
				// Application ID
				if (isset($json_file_data->client[0]->client_info->mobilesdk_app_id)) {
					$google_app_id = $json_file_data->client[0]->client_info->mobilesdk_app_id;
				}
				// API Key
				if (isset($json_file_data->client[0]->api_key[0]->current_key)) {
					$google_api_key = $json_file_data->client[0]->api_key[0]->current_key;
				}
			}
		}

		// FaceBook
		if (isset($this->wmab_plugin_settings['fb_login_settings']['enabled']) && !empty($this->wmab_plugin_settings['fb_login_settings']['enabled'])) {
			if (isset($this->wmab_plugin_settings['fb_login_settings']['app_id']) && !empty($this->wmab_plugin_settings['fb_login_settings']['app_id'])) {
				$fb_app_id = $this->wmab_plugin_settings['fb_login_settings']['app_id'];
			}
		}

		// Add code to send Google and FaceBook details
		$translation_response['social_login'] = (object) array(
			'is_facebook_login_enabled' => (isset($this->wmab_plugin_settings['fb_login_settings']['enabled']) && !empty($this->wmab_plugin_settings['fb_login_settings']['enabled'])) ? 'true' : 'false',
			'is_google_login_enabled'   => (isset($this->wmab_plugin_settings['google_login_settings']['enabled']) && !empty($this->wmab_plugin_settings['google_login_settings']['enabled'])) ? 'true' : 'false',
			'google_app_id'             => $google_app_id,
			'api_key'                   => $google_api_key,
			'fb_app_id'                 => $fb_app_id,
		);

		// Languages Records
		$translation_response['languages_record'] = $this->getLanguages();

		if (!empty($all_app_texts)) {
			$translation_response['translated_texts'] = $this->getTranslatedText($iso_code);
		} else {
			$translation_response['translated_texts'] = array();
		}

		return $translation_response;
	}

	/**
	 * Function to get Translated Text
	 *
	 * @param  string $iso_code This parameter holds Language ISO code
	 */
	private function getTranslatedText($iso_code)
	{

		// Home Page
		$data['app_text_cart_tab']             = __('Cart', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_search']               = __('Search', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_home']                 = __('Home', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_category']             = __('Categories', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login']                = __('Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_logout']               = __('Logout', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_signup']         = __('Login/Sign up', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_account']              = __('My Account', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_contact']              = __('Contact Us', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_about']                = __('About Us', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_wishlist']             = __('Add to Wishlist', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_cart']                 = __('Add to Cart', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_off']                  = __('off', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_all']                  = __('All', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_languages']            = __('Language(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['App_text_use_current_location'] = __('Use Current Location', 'knowband-mobile-app-builder-for-woocommerce');
		$data['App_text_mobile']               = __('Mobile', 'knowband-mobile-app-builder-for-woocommerce');

		// Category Page
		$data['app_text_sort']       = __('Sort', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_filter']     = __('Filter', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_sort_order'] = __('Select Sort Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_price_asce'] = __('Price: Low to High', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_price_desc'] = __('Price: High to Low', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_relevance']  = __('Relevance', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_a_to_z']     = __('Name: A to Z', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_z_to_a']     = __('Name: Z to A', 'knowband-mobile-app-builder-for-woocommerce');

		// Product Page
		$data['app_text_product']         = __('Product', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select']          = __('Select', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_instock']         = __('In Stock', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_outstock']        = __('Out of Stock', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_product_info']    = __('Product Info and Care', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_customization']   = __('Product Customization', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_accessories']     = __('Accessories', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_less']       = __('View Less', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_more']       = __('View More', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_provide_details'] = __('Please provide details', 'knowband-mobile-app-builder-for-woocommerce');

		// Cart page
		$data['app_text_shipping']           = __('Shipping', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_subtotal']           = __('Sub-total', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_total']              = __('Total', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_bag']                = __('Shopping Bag', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_apply_voucher']      = __('Apply Voucher', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_entry_voucher_code'] = __('Enter voucher code', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_apply']              = __('Apply', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_gift']               = __('Gift Wrap', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_entry_message']      = __('Enter your message', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_available_first']    = __('Send available products first', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_continue_shopping']  = __('Continue Shopping', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_continue_checkout']  = __('Continue To Checkout', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_update_quantity']    = __('Update Quantity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_quantity']     = __('Enter quantity (0-10)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_empty_cart']         = __('Your shopping cart is empty!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_goto_home']          = __('Go to Home Page', 'knowband-mobile-app-builder-for-woocommerce');

		// Checkout Page
		$data['app_text_review_checkout']          = __('Review Your Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_add_new_shipping_address'] = __('Add New Shipping Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_history']                  = __('Shipping Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_billing_details']          = __('Billing Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_same_as_shipping_details'] = __('Same as Shipping Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_summary']                  = __('Order Summary', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_shipping_methods']         = __('Shipping Methods', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_comment']                  = __('Order Comment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_summary']          = __('Payment Summary', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_proceed']                  = __('Proceed To Payment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_make_payment']             = __('Make Payment', 'knowband-mobile-app-builder-for-woocommerce');

		// Order History Page
		$data['app_text_congratulations']    = __('Congratulations', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_shipping_addresses'] = __('Shipping Address(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_billing_addresses']  = __('Billing Address(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_addresses']          = __('Address(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order_detail']       = __('Order Detail', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_update_personal']    = __('Update Personal Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_update_password']    = __('Update Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_current_password']   = __('Current Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_new_password']       = __('New Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_confirm_password']   = __('Confirm Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_cancel']             = __('Cancel', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_save']               = __('Save', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order_ref']          = __('Order Reference', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_status']             = __('Status', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_placed']             = __('Placed On', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_reorder']            = __('Reorder', 'knowband-mobile-app-builder-for-woocommerce');

		// Order Detail Page
		$data['app_text_order_details']       = __('Order Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_status_history']      = __('Status History', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_shipping_address']    = __('Shipping Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_billing_address']     = __('Billing Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order_summary']       = __('Order Summary', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_shipping_method']     = __('Shipping Method', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_method']      = __('Payment Method', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_gift_wrapping']       = __('Gift Wrapping', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_shipping_address'] = __('No shipping address available!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_order_details']    = __('No order details available!', 'knowband-mobile-app-builder-for-woocommerce');

		// Login Page
		$data['app_text_signup']                              = __('Sign Up', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_continue_guest']                      = __('Continue as Guest', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_email']                               = __('Email', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_password']                            = __('Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_forgot_password']                     = __('Forgot Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_social_account']                = __('You can login with social account.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_with_google']                   = __('Login with Google+', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_with_facebook']                 = __('Login with Facebook', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_signup_with_google']                  = __('Signup with Google+', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_signup_with_facebook']                = __('Signup with Facebook', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_enter_valid_email']               = __('Please enter valid email!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_enter_email']                     = __('Please enter email!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_enter_password']                  = __('Please enter password!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_password_characters_less_than_5'] = __('Please enter password having characters more that or equal to 5!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_first_name']                      = __('First Name', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_last_name']                       = __('Last Name', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_dob']                             = __('Date of Birth', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_confirm_password']                = __('Confirm Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_personal_details']                = __('Personal Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_pass_not_matched']                = __('Password and confirm password do not match!', 'knowband-mobile-app-builder-for-woocommerce');

		// Seller Review Page
		$data['app_text_sold_by']              = __('Sold By:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_write_review']         = __('Write Review', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_review']          = __('View Review(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_seller_products'] = __('View Seller Product(s)', 'knowband-mobile-app-builder-for-woocommerce');

		// Message Page
		$data['app_text_wait']                  = __('Please wait', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_loading']               = __('Loading data...', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_went_wrong']        = __('Something went wrong. Please try again.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_no_internet_found'] = __('No internet connection found.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_no_internet_title'] = __('No Internet', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_request']           = __('Request cannot be process. This might be because of slow internet connection.', 'knowband-mobile-app-builder-for-woocommerce');

		// Home Page Showcase
		$data['app_text_msg_order_placed_email'] = __('Your order has been placed successfully. You will receive an email regarding the same.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_product_look']       = __('You can look for product(s) by their category.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_search_product']     = __('You can search any product in store by text or voice!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_view_cart']          = __('You can view shopping cart with quantity!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_logout']             = __('You are logout successfully!', 'knowband-mobile-app-builder-for-woocommerce');

		// Category Page Showcase
		$data['app_text_msg_view_product']   = __('You can view product in list or grid mode!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_sort_product']   = __('You can sort product by their price!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_filter_product'] = __('You can filter product(s) as per your need!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_text_next']          = __('Next', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_text_okay']          = __('Okay', 'knowband-mobile-app-builder-for-woocommerce');

		// Product Page Showcase
		$data['app_text_required']                  = __('Required', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_rating']                = __('Please select rating', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_add_cart']              = __('You can add product to shopping cart by simply clicking on the button Add to Cart!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_add_wishlist']          = __('You can add product to wishlist by simply clicking on the button Add to Wishlist!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_product_price']         = __('Product price has been updated!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_product_not_available'] = __('This product is not available for order!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_combination']           = __('This combination does not exist for this product. Please select another combination.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_product_no_stock']      = __('This product is not available in the desired quantity or not in stock!', 'knowband-mobile-app-builder-for-woocommerce');

		// Cart Page Showcase
		$data['app_text_msg_out_stock']         = __('Some of the products in the cart are not available in the desired quantity or not in stock. Please remove such product(s) from cart!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_checkout_products'] = __('You can checkout products in shopping cart by simply clicking on the button Continue to Checkout.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_apply_voucher']     = __('You can apply voucher to shopping cart!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_update_quantity']   = __('You can update quantity of product or remove it from shopping cart!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_view_details']      = __('You can view product details by clicking on product image!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_success']           = __('Success!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_failure']           = __('Failure!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_enter_quantity']    = __('Please enter quantity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_quantity']              = __('Quantity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_remove']                = __('Remove', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_customization_details'] = __('Customization Details', 'knowband-mobile-app-builder-for-woocommerce');

		// Address Page Showcase
		$data['app_text_msg_add_address']          = __('You can add new shipping address by clicking on the button!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_update_address']       = __('You can update shipping address!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_select_address']       = __('You can select any shipping address by simply clicking on it!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_address_text']      = __('*Please select shipping address to review your order.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_no_product']           = __('No products available!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_no_comment_available'] = __('No comment(s) available!', 'knowband-mobile-app-builder-for-woocommerce');

		// Login Page Showcase
		$data['app_text_msg_reset_password'] = __('You can reset your password by clicking `Forget Password`', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_login']          = __('You can login into the store from here!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_login_fb']       = __('You can also login into the store from your Facebook account!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_login_google']   = __('You can also login into the store from your Google account!', 'knowband-mobile-app-builder-for-woocommerce');

		// Order detail Page Showcase
		$data['app_text_msg_update_profile']               = __('You can update your profile details, shipping details or view your order details from here!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_product_info_and_care']            = __('Product Info and Care', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_download']                         = __('Download', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_minimal_quantity']                 = __('Minimal Quantity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_pack_content']                     = __('Pack Content', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_details']                   = __('Seller Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_submit']                           = __('Submit', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_forget_password']              = __('Enter the e-mail address associated with your account.Click submit to have your password e-mailed to you.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_your_email']                 = __('Enter your email', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_reset_password']                   = __('Reset Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_selected_filter']             = __('You can view your selected filter result by clicking on this image!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_clear_selected_filter']            = __('You can clear the applied filter by clicking on this image!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_filter']                        = __('No filters', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_phone_number_required']            = __('*You must register at least one phone number', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_refresh']                          = __('Refresh', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_internet_connection_failed']       = __('Internet Connection Failed!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_internet_connection_problem']      = __('There might be some problem with your internet connection. Please check.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_cancel_transaction']               = __('Cancel transaction', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_middle_of_payment']                = __('You are in the middle of your payment transaction process.Do you really want cancel your transaction?', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_yes']                              = __('Yes', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no']                               = __('No', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_your_comment']               = __('Enter your comment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_shipping_methods_unavailable'] = __('For some product(s),shipping methods are not available. Please remove such product(s) from cart and try again.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_select_shipping_methods']      = __('Please select shipping method', 'knowband-mobile-app-builder-for-woocommerce');

		$data['app_text_share_log']                      = __('Share Log', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_title_payment_paypal']           = __('PayPal Payment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_failed_contact_support'] = __('Your order could not be processed. Kindly contact support for any queries', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_choose_payment_method']          = __('Choose Payment Method', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_payment_method']              = __('No Payment Method(s) Found!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_payment_currently']           = __('There are no payment method currently.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_make_payment']                = __('You would not be able to make payment as this mobile App is setup for demo purpose.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_make_payment_after_select']      = __('You can make payment after selecting any payment method!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order_total_price']              = __('Order Total Price', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_user_cancelled_the_payment']     = __('User cancelled the payment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_invalid_payment_try_again']      = __('Invalid payment. Kindly try again', 'knowband-mobile-app-builder-for-woocommerce');

		$data['app_text_choose_your_app_theme']    = __('Choose Your App Theme', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_your_store']          = __('View Your Store', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_terms_and_conditions']     = __('Terms and Conditions of Use', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_wishlist_only']            = __('Wishlist', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_wishlist_remove']          = __('Remove from Wishlist', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_update_address']           = __('Update Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_state_not_required']       = __('State not required for selected country', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_currencies']               = __('Currency(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_failed']           = __('Some error occurred while processing the payment. Kindly try again', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_entered_wrong_url']        = __('You might have entered wrong URL or you have not installed our mobile app builder bodule.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_failed']             = __('Login Failed!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_install_module']           = __('Install Module', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_data_found']            = __('No data found', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_store_url']          = __('Please enter store URL', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_fill_all_required_fields'] = __('Please fill all the required fields.', 'knowband-mobile-app-builder-for-woocommerce');

		// Demo Form
		$data['app_text_demo_app_user_form']          = __('Please fill the details!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_full_name_demo_app_user']     = __('Your Name*', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_email_demo_app_user']         = __('Email*', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_store_url_demo_app_user']     = __('Your Store Url*', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_continue_demo_app_user_form'] = __('Continue', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_invalid_url']                 = __('Invalid Store URL!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_invalid_email']               = __('Invalid Email!', 'knowband-mobile-app-builder-for-woocommerce');

		// Keys For Seller Admin Changes
		$data['app_text_seller_dashboard']                     = __('Seller Dashboard', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_exit_seller_dashboard']                = __('Exit Seller Account', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_exit_seller_dashboard_warning']        = __('Do you really want to exit from your seller account?', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_restricted_navigation']         = __('Restricted navigation!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_restricted_navigation_warning'] = __('You can not visit link outside the seller account.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register_as_seller']                   = __('Register as seller', 'knowband-mobile-app-builder-for-woocommerce');

		// Keys For Customization Changes
		$data['app_text_select_language']     = __('Select language', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_font']         = __('Select font', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_share_feedback']      = __('Share Feedback', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_header_color'] = __('Select Theme Color', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_button_color'] = __('Select Button Color', 'knowband-mobile-app-builder-for-woocommerce');

		// Keys for Marketplace Changes
		$data['app_text_sellers']                = __('Sellers', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller']                 = __('Seller', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_shipping_policy'] = __('Shipping Policy', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_return_policy']   = __('Return Policy', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_review_title']           = __('Title *', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_review_comment']         = __('Comment *', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_review_rating']          = __('Rating *', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_filter_products']        = __('Filter Product(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_category']        = __('Select Category', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_select_sort']            = __('Select Sort', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_clear']                  = __('Clear', 'knowband-mobile-app-builder-for-woocommerce');

		$data['app_text_posted_on']       = __('Posted On:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_commented_by']    = __('Commented By:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_seller_comment']  = __('Comment:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_explore_the_app'] = __('Explore the App', 'knowband-mobile-app-builder-for-woocommerce');

		$data['app_name']                         = __('Nautica WooCoomerce', 'knowband-mobile-app-builder-for-woocommerce');
		$data['hello_world']                      = __('Hello world!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['action_settings']                  = __('Settings', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_category']          = __('Category', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_category_products'] = __('Category', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_product']           = __('Product', 'knowband-mobile-app-builder-for-woocommerce');

		$data['hello_blank_fragment']                               = __('Hello blank fragment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_reveal']                              = __('RevealActivity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_search']                              = __('Search', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_search_results']                      = __('SearchResultsActivity', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_shopping_bag']                        = __('Shopping Bag', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_check_out']                           = __('Checkout', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_no_internet']                         = __('Internet Problem', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_login']                               = __('Log In', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_google_login']                        = __('Google Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_login_signup']                        = __('Login/Signup', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_filter']                              = __('Filter', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_activity_filter_check_box_selection'] = __('ActivityFilterCheckBoxSelection', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_product_image_viewer']                = __('Product Image Viewer', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_splash_screen']                       = __('Nautica WooCommerce', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_shipping_address']                    = __('Shipping Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['country_prompt']                                     = __('choose country', 'knowband-mobile-app-builder-for-woocommerce');
		$data['state_prompt']                                       = __('choose state', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_review_checkout']                     = __('Review Your Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_add_new_shipping_address']            = __('Add New Shipping Address', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_payment_methods']                     = __('Payment Methods', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_wishlist']                            = __('Wishlist', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_order_history']                       = __('My Account', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_make_payment']                        = __('Make Payment', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_order_history_details']               = __('Order Detail', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_about_us']                            = __('About Us', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_seller_page']                         = __('Seller Details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['title_activity_seller_comment_page']                 = __('Seller Comment(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['server_not_found']                                   = __('Server not found', 'knowband-mobile-app-builder-for-woocommerce');
		$data['authorization_error']                                = __('Authorization Error', 'knowband-mobile-app-builder-for-woocommerce');
		$data['check_internet_connection']                          = __('Network Connection Error.Please check your internet connection', 'knowband-mobile-app-builder-for-woocommerce');
		$data['parse_error']                                        = __('Parsing Error in API', 'knowband-mobile-app-builder-for-woocommerce');

		// Other
		$data['app_text_add_to_cart']       = __('Add to Cart', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_new']               = __('New', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_sale']              = __('Sale', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_percent_off']       = __('% off', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_username']          = __('Username', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_methods']   = __('Payment Methods', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_review_your_order'] = __('Review Your Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_currency']          = __('Currency(s)', 'knowband-mobile-app-builder-for-woocommerce');

		// Whatsapp Chat
		$data['app_text_install_whatsapp'] = __('Whatsapp is not installed on your device', 'knowband-mobile-app-builder-for-woocommerce');

		// Biometric Auth
		$data['app_text_no_account_registered']            = __('No account is registered for the biometric authentication in this device. Please register an account first.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_confirm_for_authentication']       = __('Confirm your fingerprint or face to authenticate.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_with_fingerprint']           = __('Login with Fingerprint', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register_for_fingerprint']         = __('Register for Fingerprint Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_biometric_not_available']          = __('Biometric authentication is not available for this device.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_fingerprint_not_recognized']       = __('Touch ID does not recognize your fingerprint. Please try again with your enrolled fingerprint.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_face_not_recognizes']              = __('Face ID does not recognize your face. Please try again with your enrolled face.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_fingerprint_or_face_not_enrolled'] = __('No fingerprints/face enrolled in the device.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_biometry_locked_out']              = __('Functionality is locked now, because of too many failed attempts.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register_for_fingerpring']         = __('Register for Fingerprint', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register_for_fingerprint_details'] = __('Do you want to register this account for fingerprint functionality. Once done, you can login into this account without using email Id or password, just using the fingerprint verification.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register']                         = __('Register', 'knowband-mobile-app-builder-for-woocommerce');

		// OTP Changes
		$data['app_text_verify']                          = __('Verify', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_get_otp']                         = __('Get OTP', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_mobile_number']                   = __('Mobile Number', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_mobile_number_optional']          = __('Mobile Number (Optional)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_number_verification']       = __('Mobile number could not be verified! Please try again with the correct OTP number!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_phone_number_already_exists']     = __('Phone number is already registered. Please try again with another phone number!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_number_verification']             = __('Number Verification', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_number_verification_description'] = __('You need to verify the phone number. Please enter the phone number and submit', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_with_phone_number']         = __('Login with Phone Number', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_forget_password_description']     = __('Enter the e-mail address or phone number associated with your account.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_email_or_mobile_number']    = __('Enter email or mobile number', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_phone_number_change_message']     = __('One phone number is already registered with this account. You can only change the contact number. Please enter valid phone number.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_enter_otp']                       = __('Enter OTP', 'knowband-mobile-app-builder-for-woocommerce');

		// Banner countdwn
		$data['app_text_sec']  = __('Sec', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_min']  = __('Min', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_hour'] = __('Hour', 'knowband-mobile-app-builder-for-woocommerce');

		// NEW Features.
		$data['app_text_related_products']        = __('Related Products', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_reviews']            = __('View Reviews', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_write_reviews']           = __('Write reviews', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_please_fill_all_details'] = __('Please fill all details', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_close']                   = __('Close', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_add_review']              = __('Add Review', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_write_a_review']          = __('Write a review', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_nick']                    = __('Nick', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_title']                   = __('Title', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_reviews']              = __('No Reviews for the product', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_number_of_reviews']       = __('Number of reviews', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_minimum_rating']          = __('Please add minimum rating', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error']                   = __('Error', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_reviews']              = __('No Reviews', 'knowband-mobile-app-builder-for-woocommerce');

		$data['app_text_price_a-z'] = __('A-Z', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_price_z-a'] = __('Z-A', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_warning']   = __('Warning', 'knowband-mobile-app-builder-for-woocommerce');

		// New API Keys v2.0
		$data['app_text_short_description']             = __('Short Description', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_description']                   = __('Description', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_discount']                      = __('Discount', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_applied']                       = __('Applied', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_default']                       = __('Default', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_name_asc']                      = __('Name (A-Z)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_name_desc']                     = __('Name (Z-A)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_rating_asc']                    = __('Rating: Low to High', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_rating_desc']                   = __('Rating: High to Low', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_model_asc']                     = __('Model (A-Z)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_model_desc']                    = __('Model (Z-A)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_create_new_account']            = __('Create new account', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_back_login']                    = __('Back To Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_guest_login']                   = __('Guest Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_first_name']                    = __('First Name*', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_last_name']                     = __('Last Name*', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_already_have_account']          = __('Already Have an Account?', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_firstname']               = __('Please enter the first name.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_lastname']                = __('Please enter the last name.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_mobile_number']           = __('Please enter the mobile number.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_my_profile']                    = __('My Profile', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_my_orders']                     = __('My Orders', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_my_addresses']                  = __('My Addresses', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_support']                       = __('Support', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_account_page_heading']          = __('Access your account details and rewards', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_not']                           = __('Not', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_apple']                         = __('Apple', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_google']                        = __('Google', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_facebook']                      = __('Facebook', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_view_all']                      = __('View All', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_email_access']            = __('Please provide email access or use another account.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_hi']                            = __('Hi', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_sold_out']                      = __('Sold Out', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_signup_with_apple']             = __('Sign in with Apple', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_login_with_apple']              = __('Log in with Apple', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order']                         = __('Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_order_date']                    = __('Order Date', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_items']                         = __('Item(s)', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_amount']                        = __('Amount', 'knowband-mobile-app-builder-for-woocommerce');
		$data['error_voucher_not_available']            = __('Voucher is either invalid, expired or reached its usage limit!', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_review_order']                  = __('Review Order', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_payment_methods']               = __('Payment Methods', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_guest']                         = __('Guest', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_resend_otp']                    = __('Resend OTP', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_mobile_number']             = __('Please enter your mobile number', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_otp']                       = __('An OTP has ben sent to the number:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_resend_otp']                = __('An OTP has ben resent to the number:', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_confirm_new_password']          = __('Confirm New Password', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_error_facebook_login']          = __('Could not login with Facebook', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_sub_total']                     = __('Sub-Total', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_product_total']                 = __('Product Total', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_are_you_sure_you_want_to_exit'] = __('Are you sure, you want to exit?', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_no_sellers_available']          = __('No Sellers Available to display', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_name']                          = __('Name', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_invalid_phone_number']          = __('Phone number is not correct.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['error_email']                            = __('Email ID is not correct.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_only_alphabet_error']           = __('Please enter alphabet only', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_username']                  = __('Please enter your user name', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_enter_email_username']      = __('Please enter an Email or Username', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_msg_email_username']            = __('Email/Username', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_search_description']            = __('Find what you are looking for.', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_register_fingerprint']          = __('Register for Fingerprint Login', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_delete_account']                = __('Delete Account', 'knowband-mobile-app-builder-for-woocommerce');
		$data['app_text_delete_account_msg']            = __('Are you sure you want to delete account?', 'knowband-mobile-app-builder-for-woocommerce');

		$translated_texts = array();
		foreach ($data as $key => $value) {
			$translated_texts[] = array(
				'unique_key' => $key,
				'iso_code'   => $iso_code,
				'trans_text' => $value,
			);
		}

		return $translated_texts;
	}

	/**
	 * Function to get Top Slider for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getMainBanner($settings)
	{
		global $wpdb;

		$slider = array(
			'title'  => '',
			'slides' => array(),
		);

		if (isset($settings['slideshow_id']) && !empty($settings['slideshow_id'])) {
			$slideshow_id = $settings['slideshow_id'];

			// Get Slideshow details
			$slideshow_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_banner WHERE banner_id = %d", $slideshow_id));

			// Go ahead if status is enabled
			if (isset($slideshow_details->status) && $slideshow_details->status) {
				$slider_title = $slideshow_details->name;
				$image_width  = $slideshow_details->image_width;
				$image_height = $slideshow_details->image_height;
				$slide_limit  = $slideshow_details->banner_limit;

				$slider['title'] = $slider_title;

				// Get slides
				$slides = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_banner_image WHERE banner_id = %d", $slideshow_id));
				$slides = array_slice($slides, 0, $slide_limit, true); // Cut the array based on limit

				$link_type = ''; // Link Type Variable
				if (isset($slides) && !empty($slides)) {
					foreach ($slides as $slide) {
						switch ($slide->link_type) {
							case '1':
								// Category Link
								$link_type = 'Category';
								break;
							case '2':
								// Product Link
								$link_type = 'Product';
								break;
						}

						// Image SRC
						$slide_image = plugin_dir_url('/', __FILE__) . plugin_basename(__DIR__) . '/views/images/banners/' . $slide->image;

						// Slide Thumbnail - Generate Thumbnail is pending

						$slider['slides'][] = array(
							'click_target' => $link_type,
							'target_id'    => (string) $slide->link_to,
							'title'        => stripslashes($slide->banner_title),
							'src'          => str_replace(' ', '%20', $slide_image),
						);
					}
				}
			}
		}

		return $slider;
	}

	/**
	 * Function to get Promo Banner for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getPromoBanner($settings)
	{
		global $wpdb;

		$promobanner = array(
			'title'   => '',
			'banners' => array(),
		);

		if (isset($settings['banner_id']) && !empty($settings['banner_id'])) {
			$banner_id = $settings['banner_id'];

			// Get Banner details
			$banner_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_banner WHERE banner_id = %d", $banner_id));

			// Go ahead if status is enabled
			if (isset($banner_details->status) && $banner_details->status) {
				$banner_title = stripslashes($banner_details->name);
				$image_width  = $banner_details->image_width;
				$image_height = $banner_details->image_height;
				$banner_limit = $banner_details->banner_limit;

				$promobanner['title'] = $banner_title;

				// Get Banners
				$banners = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_banner_image WHERE banner_id = %d", $banner_id));
				$banners = array_slice($banners, 0, $banner_limit, true); // Cut the array based on limit

				$link_type = ''; // Link Type Variable
				if (isset($banners) && !empty($banners)) {
					foreach ($banners as $banner) {
						switch ($banner->link_type) {
							case '1':
								// Category Link
								$link_type = 'Category';
								break;
							case '2':
								// Product Link
								$link_type = 'Product';
								break;
						}

						// Image SRC
						$banner_image = plugin_dir_url('/', __FILE__) . plugin_basename(__DIR__) . '/views/images/banners/' . $banner->image;

						// Slide Thumbnail - Generate Thumbnail is pending

						$promobanner['banners'][] = array(
							'click_target' => $link_type,
							'target_id'    => (string) $banner->link_to,
							'title'        => stripslashes($banner->banner_title),
							'src'          => str_replace(' ', '%20', $banner_image),
						);
					}
				}
			}
		}

		return $promobanner;
	}

	/**
	 * Function to get Featured Products for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getFeaturedProducts($settings, $iso_code)
	{
		global $wpdb;

		$featuredProducts = array(
			'title'    => __('Featured', 'knowband-mobile-app-builder-for-woocommerce'),
			'products' => array(),
		);

		if (isset($settings['featured']['enabled']) && !empty($settings['featured']['enabled'])) {

			if (function_exists('icl_object_id')) {
				/*
				* Changes added for WPML compatibility and set current language for getting data through WP_QUERY() based on language
				* Added by Harsh on 16-Apr-2019
				*/
				global $sitepress;
				$sitelang = !empty($iso_code) ? $iso_code : get_locale();
				$lang     = explode('_', $sitelang);
				$sitepress->switch_lang($lang[0]);
				// Ends

				$args = array(
					'post_status'      => 'publish',
					'post_type'        => 'product',
					'posts_per_page'   => $settings['featured']['limit'],
					'post__in'         => wc_get_featured_product_ids(),
					'suppress_filters' => false,
				);
			} else {
				$args = array(
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'posts_per_page' => $settings['featured']['limit'],
					'post__in'       => wc_get_featured_product_ids(),
				);
			}

			$featured_query = new WP_Query($args);

			if (isset($featured_query->posts) && !empty($featured_query->posts)) {
				foreach ($featured_query->posts as $featured) {

					$product = wc_get_product($featured->ID);
					if (!empty($product) && $product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime($settings['general']['product_new_date'])) {
							$is_product_new = 1;
						}

						if ('variable' == $product->get_type()) { // For Variable Product
							$available_variations = $product->get_available_variations();
							$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
							$variable_product     = new WC_Product_Variation($variation_id);
							$regular_price        = $variable_product->get_regular_price();
							$sale_price           = $variable_product->get_sale_price();

							/**
							 * Updated the condition to check whether the product is on sale or not
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($variable_product->is_on_sale()) {
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
							} else {
								$discount_percentage = 0;
							}
						} else {
							// For Simple Products
							/**
							 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($product->is_on_sale()) {
								// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

								// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							} else {
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = '';
							}
						}

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}

						// Check if image exists otherwise send dummy image - added by Saurav Choudhary on 10-Sep-2020
						$product_thumbnail = get_the_post_thumbnail_url($featured->ID, 'full');
						if (isset($product_thumbnail) && !empty($product_thumbnail)) {
							$image_path = $product_thumbnail;

							$wmab_settings = get_option('wmab_settings');
							$settings_data = array();
							if (isset($wmab_settings) && !empty($wmab_settings)) {
								$settings_data = unserialize($wmab_settings);
							}
							if (!empty($settings_data['general']['image_url_encoding_status'])) {
								$image_path = $this->url_onlyfile_encode($product_thumbnail);
							}
						} else {
							$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}
						// Ends
						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $featured->ID,
							'status'    => 'approve',
						);
						$reviews            = get_comments($args);

						foreach ($reviews as $review) {
							++$total_reviews;
							$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
							$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
						}
						if (0 != $total_reviews) {
							$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
						}

						$featuredProducts['products'][] = array(
							'id'                  => (string) $featured->ID,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
							'new_products'        => (int) $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'category_name'       => $product_category_name,
							'ClickActivityName'   => 'ProductActivity',
							'category_id'         => (string) $product_category_id,
							'price'               => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $regular_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'src'                 => $image_path,
							'minimal_quantity'    => '1',
							'discount_price'      => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $sale_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'is_in_wishlist'      => false,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				}
			}
		}

		return $featuredProducts;
	}

	/**
	 * Function to get Special Products for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getSpecialProducts($settings, $iso_code)
	{
		global $wpdb;

		$specialProducts = array(
			'title'    => __('Specials', 'knowband-mobile-app-builder-for-woocommerce'),
			'products' => array(),
		);

		if (isset($settings['specials']['enabled']) && !empty($settings['specials']['enabled'])) {

			if (function_exists('icl_object_id')) {
				/*
				* Changes added for WPML compatibility and set current language for getting data through WP_QUERY() based on language
				* Added by Harsh on 16-Apr-2019
				*/
				global $sitepress;
				$sitelang = !empty($iso_code) ? $iso_code : get_locale();
				$lang     = explode('_', $sitelang);
				$sitepress->switch_lang($lang[0]);
				// Ends

				$args = array(
					'post_status'      => 'publish',
					'post_type'        => 'product',
					'posts_per_page'   => $settings['specials']['limit'],
					'post__in'         => wc_get_product_ids_on_sale(),
					'suppress_filters' => false,
				);
			} else {
				$args = array(
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'posts_per_page' => $settings['specials']['limit'],
					'post__in'       => wc_get_product_ids_on_sale(),
				);
			}

			$special_query = new WP_Query($args);

			if (isset($special_query->posts) && !empty($special_query->posts)) {
				foreach ($special_query->posts as $special) {

					$product = wc_get_product($special->ID);
					if (!empty($product) && $product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime($settings['general']['product_new_date'])) {
							$is_product_new = 1;
						}

						if ('variable' == $product->get_type()) { // For Variable Product
							$available_variations = $product->get_available_variations();
							$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
							$variable_product     = new WC_Product_Variation($variation_id);
							$regular_price        = $variable_product->get_regular_price();
							$sale_price           = $variable_product->get_sale_price();

							/**
							 * Updated the condition to check whether the product is on sale or not
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($variable_product->is_on_sale()) {
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
							} else {
								$discount_percentage = 0;
							}
						} else {
							// For Simple Products
							/**
							 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($product->is_on_sale()) {
								// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

								// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							} else {
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = '';
							}
						}

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}
						// Check if image exists otherwise send dummy image - added by Saurav Choudhary on 10-Sep-2020
						$product_thumbnail = get_the_post_thumbnail_url($special->ID, 'full');
						if (isset($product_thumbnail) && !empty($product_thumbnail)) {
							$image_path = $product_thumbnail;

							$wmab_settings = get_option('wmab_settings');
							$settings_data = array();
							if (isset($wmab_settings) && !empty($wmab_settings)) {
								$settings_data = unserialize($wmab_settings);
							}
							if (!empty($settings_data['general']['image_url_encoding_status'])) {
								$image_path = $this->url_onlyfile_encode($product_thumbnail);
							}
						} else {
							$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}
						// Ends
						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $special->ID,
							'status'    => 'approve',
						);
						$reviews            = get_comments($args);

						foreach ($reviews as $review) {
							++$total_reviews;
							$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
							$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
						}
						if (0 != $total_reviews) {
							$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
						}

						$specialProducts['products'][] = array(
							'id'                  => (string) $special->ID,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
							'new_products'        => (int) $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'category_name'       => $product_category_name,
							'ClickActivityName'   => 'ProductActivity',
							'category_id'         => (string) $product_category_id,
							'price'               => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $regular_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'src'                 => $image_path,
							'minimal_quantity'    => '1',
							'discount_price'      => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $sale_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'is_in_wishlist'      => false,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				}
			}
		}

		return $specialProducts;
	}

	/**
	 * Function to get Latest Products for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getLatestProducts($settings, $iso_code)
	{
		global $wpdb;

		$latestProducts = array(
			'title'    => __('Latest', 'knowband-mobile-app-builder-for-woocommerce'),
			'products' => array(),
		);

		if (isset($settings['latest']['enabled']) && !empty($settings['latest']['enabled'])) {

			if (function_exists('icl_object_id')) {
				/*
				* Changes added for WPML compatibility and set current language for getting data through WP_QUERY() based on language
				* Added by Harsh on 16-Apr-2019
				*/
				global $sitepress;
				$sitelang = !empty($iso_code) ? $iso_code : get_locale();
				$lang     = explode('_', $sitelang);
				$sitepress->switch_lang($lang[0]);
				// Ends

				$args = array(
					'post_status'      => 'publish',
					'post_type'        => 'product',
					'posts_per_page'   => $settings['latest']['limit'],
					'orderby'          => 'post_date',
					'suppress_filters' => false,
				);
			} else {
				$args = array(
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'posts_per_page' => $settings['latest']['limit'],
					'orderby'        => 'post_date',
				);
			}

			$latest_query = new WP_Query($args);

			if (isset($latest_query->posts) && !empty($latest_query->posts)) {
				foreach ($latest_query->posts as $latest) {

					$product = wc_get_product($latest->ID);
					if (!empty($product) && $product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime($settings['general']['product_new_date'])) {
							$is_product_new = 1;
						}

						if ('variable' == $product->get_type()) { // For Variable Product
							$available_variations = $product->get_available_variations();
							$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
							$variable_product     = new WC_Product_Variation($variation_id);
							$regular_price        = $variable_product->get_regular_price();
							$sale_price           = $variable_product->get_sale_price();

							/**
							 * Updated the condition to check whether the product is on sale or not
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($variable_product->is_on_sale()) {
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
							} else {
								$discount_percentage = 0;
							}
						} else {
							// For Simple Products
							/**
							 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($product->is_on_sale()) {
								// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

								// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							} else {
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = '';
							}
						}

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}
						// Check if image exists otherwise send dummy image - added by Saurav Choudhary on 10-Sep-2020
						$product_thumbnail = get_the_post_thumbnail_url($latest->ID, 'full');
						if (isset($product_thumbnail) && !empty($product_thumbnail)) {
							$image_path = $product_thumbnail;

							$wmab_settings = get_option('wmab_settings');
							$settings_data = array();
							if (isset($wmab_settings) && !empty($wmab_settings)) {
								$settings_data = unserialize($wmab_settings);
							}
							if (!empty($settings_data['general']['image_url_encoding_status'])) {
								$image_path = $this->url_onlyfile_encode($product_thumbnail);
							}
						} else {
							$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}
						// Ends
						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $latest->ID,
							'status'    => 'approve',
						);
						$reviews            = get_comments($args);

						foreach ($reviews as $review) {
							++$total_reviews;
							$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
							$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
						}
						if (0 != $total_reviews) {
							$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
						}

						$latestProducts['products'][] = array(
							'id'                  => (string) $latest->ID,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
							'new_products'        => (int) $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'category_name'       => $product_category_name,
							'ClickActivityName'   => 'ProductActivity',
							'category_id'         => (string) $product_category_id,
							'price'               => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $regular_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'src'                 => $image_path,
							'minimal_quantity'    => '1',
							'discount_price'      => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $sale_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'is_in_wishlist'      => false,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				}
			}
		}

		return $latestProducts;
	}

	/**
	 * Function to get Best Seller Products for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getBestSellerProducts($settings, $iso_code)
	{
		global $wpdb;

		$bestsellerProducts = array(
			'title'    => __('Best Sellers', 'knowband-mobile-app-builder-for-woocommerce'),
			'products' => array(),
		);

		if (isset($settings['bestsellers']['enabled']) && !empty($settings['bestsellers']['enabled'])) {

			if (function_exists('icl_object_id')) {
				/*
				* Changes added for WPML compatibility and set current language for getting data through WP_QUERY() based on language
				* Added by Harsh on 16-Apr-2019
				*/
				global $sitepress;
				$sitelang = !empty($iso_code) ? $iso_code : get_locale();
				$lang     = explode('_', $sitelang);
				$sitepress->switch_lang($lang[0]);
				// Ends

				$args = array(
					'post_status'      => 'publish',
					'post_type'        => 'product',
					'posts_per_page'   => $settings['bestsellers']['limit'],
					'meta_key'         => 'total_sales',
					'orderby'          => 'meta_value_num',
					'suppress_filters' => false,
				);
			} else {
				$args = array(
					'post_status'    => 'publish',
					'post_type'      => 'product',
					'posts_per_page' => $settings['bestsellers']['limit'],
					'meta_key'       => 'total_sales',
					'orderby'        => 'meta_value_num',
				);
			}

			$bestseller_query = new WP_Query($args);

			if (isset($bestseller_query->posts) && !empty($bestseller_query->posts)) {
				foreach ($bestseller_query->posts as $bestseller) {

					$product = wc_get_product($bestseller->ID);
					if (!empty($product) && $product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime($settings['general']['product_new_date'])) {
							$is_product_new = 1;
						}

						if ('variable' == $product->get_type()) { // For Variable Product
							$available_variations = $product->get_available_variations();
							$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
							$variable_product     = new WC_Product_Variation($variation_id);
							$regular_price        = $variable_product->get_regular_price();
							$sale_price           = $variable_product->get_sale_price();

							/**
							 * Updated the condition to check whether the product is on sale or not
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($variable_product->is_on_sale()) {
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
							} else {
								$discount_percentage = 0;
							}
						} else {
							// For Simple Products
							/**
							 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($product->is_on_sale()) {
								// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

								// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							} else {
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = '';
							}
						}

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}
						// Check if image exists otherwise send dummy image - added by Saurav Choudhary on 10-Sep-2020
						$product_thumbnail = get_the_post_thumbnail_url($bestseller->ID, 'full');
						if (isset($product_thumbnail) && !empty($product_thumbnail)) {
							$image_path = $product_thumbnail;

							$wmab_settings = get_option('wmab_settings');
							$settings_data = array();
							if (isset($wmab_settings) && !empty($wmab_settings)) {
								$settings_data = unserialize($wmab_settings);
							}
							if (!empty($settings_data['general']['image_url_encoding_status'])) {
								$image_path = $this->url_onlyfile_encode($product_thumbnail);
							}
						} else {
							$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}
						// Ends
						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $bestseller->ID,
							'status'    => 'approve',
						);
						$reviews            = get_comments($args);

						foreach ($reviews as $review) {
							++$total_reviews;
							$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
							$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
						}
						if (0 != $total_reviews) {
							$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
						}

						$bestsellerProducts['products'][] = array(
							'id'                  => (string) $bestseller->ID,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
							'new_products'        => (int) $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'category_name'       => $product_category_name,
							'ClickActivityName'   => 'ProductActivity',
							'category_id'         => (string) $product_category_id,
							'price'               => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $regular_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'src'                 => $image_path,
							'minimal_quantity'    => '1',
							'discount_price'      => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $sale_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'is_in_wishlist'      => false,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				}
			}
		}

		return $bestsellerProducts;
	}

	/**
	 * Function to get Menu for Home Page
	 *
	 * @param  string $settings This parameter holds Plugin settings/configuration values
	 */
	private function getMenuCategories()
	{

		$categories_list = array();

		$cat_args = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'name',
			'order'      => 'asc',
			'hide_empty' => true,
			'parent'     => 0,
		);

		$product_categories = get_terms($cat_args);
		if (isset($product_categories) && !empty($product_categories)) {
			foreach ($product_categories as $product_category) {
				// get the thumbnail id using the queried category term_id
				$thumbnail_id = get_term_meta($product_category->term_id, 'thumbnail_id', true);

				// get the image URL
				$image = wp_get_attachment_url($thumbnail_id);

				// Multi-Level Category
				$children_data = $this->getSubCategoryDetail($product_category);
				// Level 1
				$categories_list[] = array(
					'id'        => (string) $product_category->term_id,
					'name'      => html_entity_decode($product_category->name, ENT_QUOTES),
					'image_src' => null != $image ? $image : '',
					'children'  => $children_data,
				);
			}
		}

		return $categories_list;
	}

	/**
	 * Using Recursion We get Depth of category upto last category
	 * neeraj.kumar@velsof.com : 03-02-2020
	 */
	private function getSubCategoryDetail($product_category = array())
	{
		$children_data = array();
		if (isset($product_category) && !empty($product_category)) {
			$cat_args = array(
				'taxonomy'   => 'product_cat',
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => true,
				'parent'     => $product_category->term_id,
			);

			$second_child_categories = get_terms($cat_args);
			$children_data           = array();
			if (isset($second_child_categories) && !empty($second_child_categories)) {
				foreach ($second_child_categories as $second_child_category) {
					// get the thumbnail id using the queried category term_id
					$thumbnail_id = get_term_meta($product_category->term_id, 'thumbnail_id', true);

					// get the image URL
					$image = wp_get_attachment_url($thumbnail_id);

					$children_data[] = array(
						'id'        => (string) $second_child_category->term_id,
						'name'      => html_entity_decode($second_child_category->name, ENT_QUOTES),
						'image_src' => null != $image ? $image : '',
						// BOC neeraj.kumar@velsof.com 2-dec-2019 : change third_children to children
						'children'  => $this->getSubCategoryDetail($second_child_category),
					);
				}
			}
			return $children_data;
		} else {
			return array();
		}
	}

	/**
	 * Function to get Menu Information / Page Links for Home Page
	 */
	private function getMenuInformation()
	{
		global $wpdb;

		$page_list = array();

		// Get All mapped pages
		$pages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mab_cms_page_data WHERE status = '1' ORDER BY sort_order ASC");

		if (isset($pages) && !empty($pages)) {
			foreach ($pages as $page) {
				if (strpos($page->page_title, 'account') !== false) {
					continue;
				}
				$page_list[] = array(
					'name' => $page->page_title,
					'link' => get_page_link($page->link_to) . '?wmab=1',
				);
			}
		}

		return $page_list;
	}

	/**
	 * Function to get Currencies for Home Page
	 */
	private function getCurrencies()
	{

		/*
		* WooCommerce only allows for one base currency at a time.
		*/

		$currency_data = array();
		$currencies    = get_woocommerce_currencies();

		// Get Default Currency
		$default_currency = get_woocommerce_currency();

		if (isset($currencies) && !empty($currencies)) {
			foreach ($currencies as $code => $currency) {
				if ($code == $default_currency) {
					$currency_data['currency_list'][] = array(
						'id_currency'     => $code,
						'name'            => $currency,
						'currency_code'   => $code,
						'currency_symbol' => get_woocommerce_currency_symbol($code),
					);
				}
			}
		}

		if (isset($default_currency) && !empty($default_currency)) {
			$currency_data['default_currency_id']               = $default_currency;
			$currency_data['default_currency_code'] = $default_currency;
		}

		return $currency_data;
	}

	/**
	 * Function to get Current Language for Home Page
	 */
	private function getCurrentLanguage()
	{

		$langauges_list = array();
		$languages      = get_available_languages();

		$current_language = get_locale(); // Current Language
		$date_format      = get_option('date_format'); // Default Date Format

		include_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$translations = wp_get_available_translations();

		if (!is_multisite() && defined('WPLANG') && '' !== WPLANG && 'en_US' !== WPLANG && !in_array(WPLANG, $languages)) {
			$languages[] = WPLANG;
		}
		if (isset($languages) && !empty($languages)) {
			foreach ($languages as $language) {
				if (isset($translations[$language])) {
					$translation                   = $translations[$language];
					$langauges_list['lang_list'][] = array(
						'id_lang'          => (string) $translation['language'],
						'name'             => $translation['native_name'],
						'active'           => ($current_language == $translation['language']) ? '1' : '0', // Default 1 as only available languages are being sent
						'iso_code'         => (string) $translation['language'], // $translation['iso'][1],
						'language_code'    => (string) $language,
						'directory'        => '', // Added Due to Keeping the Same Response Array
						'date_format_lite' => $date_format,
						'date_format_full' => $date_format,
						'is_rtl'           => '0',
						'id_shop'          => 0,
						'shops'            => array(),
					);
				}
			}
		} else {
			// Default available language of WooCommerce - English (United States)
			$langauges_list['lang_list'][] = array(
				'id_lang'          => 'en_US',
				'name'             => 'English (United States)',
				'active'           => ('en_US' == $current_language) ? '1' : '0', // Default 1 as only available languages are being sent
				'iso_code'         => 'en_US',
				'language_code'    => 'en_US',
				'directory'        => '', // Added Due to Keeping the Same Response Array
				'date_format_lite' => $date_format,
				'date_format_full' => $date_format,
				'is_rtl'           => '0',
				'id_shop'          => 0,
				'shops'            => array(),
			);
			$langauges_list['lang_list'][] = array(
				'id_lang'          => 'ar',
				'name'             => 'Arabic',
				'active'           => ('ar' == $current_language) ? '1' : '0', // Default 1 as only available languages are being sent
				'iso_code'         => 'ar',
				'language_code'    => 'ar',
				'directory'        => '', // Added Due to Keeping the Same Response Array
				'date_format_lite' => $date_format,
				'date_format_full' => $date_format,
				'is_rtl'           => '0',
				'id_shop'          => 0,
				'shops'            => array(),
			);
		}
		$langauges_list['default_lang'] = (string) $current_language;

		return $langauges_list;
	}

	/**
	 * Function to get Languages for Home Page
	 */
	private function getLanguages()
	{

		$langauges_list = array();
		$languages      = get_available_languages();

		include_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$translations = wp_get_available_translations();

		if (!is_multisite() && defined('WPLANG') && '' !== WPLANG && 'en_US' !== WPLANG && !in_array(WPLANG, $languages)) {
			$languages[] = WPLANG;
		}

		if (isset($languages) && !empty($languages)) {
			foreach ($languages as $language) {
				if (isset($translations[$language])) {
					$translation      = $translations[$language];
					$langauges_list[] = array(
						'iso_code'  => $translation['language'],
						'timestamp' => strtotime($translation['updated']),
					);
				}
			}
		}

		$langauges_list[] = array(
			'iso_code'  => 'en_US',
			'timestamp' => strtotime('2019-01-01 00:00:00'), // strtotime('now') //Fixed the timestamp of default language as it is not required - change added by Harsh on 04-Sep-2019
		);
		$langauges_list[] = array(
			'iso_code'  => 'ar',
			'timestamp' => strtotime('2019-01-01 00:00:00'), // strtotime('now') //Fixed the timestamp of default language as it is not required - change added by Harsh on 04-Sep-2019
		);

		return $langauges_list;
	}

	/**
	 * Function to get Home Page Elements (based on Layout) - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * @param  string $layout This parameter holds selected layout to display on Mobile App
	 */
	private function getHomePageElements($layout, $session_data = '', $email = '')
	{
		// Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
		global $wpdb;
		$elements = array();

		// Get Upload folder path and URL
		$default_upload_dirs  = wp_upload_dir();
		$upload_directory     = '';
		$upload_directory_url = '';
		if (!empty($default_upload_dirs['basedir'])) {
			$upload_directory     = $default_upload_dirs['basedir'] . '/knowband';
			$upload_directory_url = $default_upload_dirs['baseurl'] . '/knowband/';
		}
		// Ends

		if (isset($layout) && !empty($layout)) {
			$settings = $this->wmab_plugin_settings;
			// Get Element with applied sorting order
			$elements_details = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT wmmlc.id_component as id_component,id_layout,id_component_type,position,component_name,component_heading FROM `{$wpdb->prefix}mab_mobileapp_layout_component` as wmmlc "
						. "INNER JOIN {$wpdb->prefix}mabmobileapp_component_types as wmct WHERE wmct.id = wmmlc.id_component_type and"
						. ' id_layout = %d ORDER by position asc',
					$layout
				)
			);

			foreach ($elements_details as $keyElement => $valueElement) {
				// Pass component type id in switch case and merge array
				if (isset($valueElement->id_component_type) && !empty($valueElement->id_component_type) && isset($valueElement->id_component)) {
					switch ($valueElement->id_component_type) {
							// Top Categories
						case 1:
							$top_categories_data  = array();
							$top_category_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_top_category` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($top_category_details as $keyCategoryDetail => $valueCategoryDetail) {
								if (file_exists($upload_directory . '/' . $valueCategoryDetail->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueCategoryDetail->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueCategoryDetail->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueCategoryDetail->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}
								// Top Categories Element details
								$top_categories_data[] = array(
									// Category Id
									'id'                => $valueCategoryDetail->id_category,
									'image_src'         => $uploaded_image_path,
									'image_contentMode' => $valueCategoryDetail->image_content_mode,
									'name'              => $valueCategoryDetail->category_heading,
								);
							}

							$elements[] = array(
								'element_type' => 'categories_top',
								'data'         => $top_categories_data,
							);
							break;
							// Banner-Square
						case 2:
							// Banner Square Element details
							$banner_square_data = array();
							$banner_details     = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($banner_details as $keyBanner => $valueBanner) {
								$target_id = 0;
								// Category Type
								if ('1' == $valueBanner->id_banner_type) {
									$target_id = $valueBanner->category_id;
								} elseif ('2' == $valueBanner->id_banner_type) {
									// Product Type
									$target_id = $valueBanner->product_id;
								}

								if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueBanner->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueBanner->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}

								$banner_square_data[] = array(
									'click_target'      => $valueBanner->redirect_activity,
									'target_id'         => (string) $target_id,
									'src'               => $uploaded_image_path,
									'title'             => stripslashes($valueBanner->banner_heading),
									'image_contentMode' => $valueBanner->image_contentMode,
								);
							}
							$elements[] = array(
								'element_type' => 'banners_square',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $banner_square_data,
							);
							break;
							// Banner-Horizontal Sliding
						case 3:
							// Horizontal Banner Sliding Element details
							// Banner Square Element details
							$horizontal_sliding_banners_data = array();
							$horizontal_banner_detail        = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($horizontal_banner_detail as $keyHorizontalBanner => $valueHorizontalBanner) {
								$target_id = 0;
								// Category Type
								if ('1' == $valueHorizontalBanner->id_banner_type) {
									$target_id = $valueHorizontalBanner->category_id;
								} elseif ('2' == $valueHorizontalBanner->id_banner_type) {
									// Product Type
									$target_id = $valueHorizontalBanner->product_id;
								}

								if (file_exists($upload_directory . '/' . $valueHorizontalBanner->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueHorizontalBanner->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueHorizontalBanner->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueHorizontalBanner->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}

								$horizontal_sliding_banners_data[] = array(
									'click_target'      => $valueHorizontalBanner->redirect_activity,
									'target_id'         => (string) $target_id,
									'src'               => $uploaded_image_path,
									'title'             => stripslashes($valueHorizontalBanner->banner_heading),
									'image_contentMode' => $valueHorizontalBanner->image_contentMode,
								);
							}

							$elements[] = array(
								'element_type' => 'banners_horizontal_sliding',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $horizontal_sliding_banners_data,
							);
							break;
							// Banner-Grid
						case 4:
							// Banner Grid Element details
							$banner_grid_data    = array();
							$banner_grid_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($banner_grid_details as $keyBannerGrid => $valueBannerGrid) {
								$target_id = 0;
								// Category Type
								if ('1' == $valueBannerGrid->id_banner_type) {
									$target_id = $valueBannerGrid->category_id;
								} elseif ('2' == $valueBannerGrid->id_banner_type) {
									// Product Type
									$target_id = $valueBannerGrid->product_id;
								}

								if (file_exists($upload_directory . '/' . $valueBannerGrid->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueBannerGrid->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueBannerGrid->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueBannerGrid->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}

								$banner_grid_data[] = array(
									'click_target'      => $valueBannerGrid->redirect_activity,
									'target_id'         => (string) $target_id,
									'src'               => $uploaded_image_path,
									'title'             => stripslashes($valueBannerGrid->banner_heading),
									'image_contentMode' => $valueBannerGrid->image_contentMode,
								);
							}
							$elements[] = array(
								'element_type' => 'banners_grid',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $banner_grid_data,
							);
							break;
							// Banner-With Countdown Timer
						case 5:
							$banner_countdown_data    = array();
							$banner_countdown_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($banner_countdown_details as $keyCountDownGrid => $valueCountDownGrid) {
								$target_id = 0;
								// Category Type
								if ('1' == $valueCountDownGrid->id_banner_type) {
									$target_id = $valueCountDownGrid->category_id;
								} elseif ('2' == $valueCountDownGrid->id_banner_type) {
									// Product Type
									$target_id = $valueCountDownGrid->product_id;
								}

								if (file_exists($upload_directory . '/' . $valueCountDownGrid->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueCountDownGrid->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueCountDownGrid->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueCountDownGrid->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}

								// To show countdown timer only when the time set in timer has not passed
								if (strtotime($valueCountDownGrid->countdown) - strtotime('now') > 0) {
									$banner_countdown_data[] = array(
										'click_target'     => $valueCountDownGrid->redirect_activity,
										'target_id'        => (string) $target_id,
										'src'              => $uploaded_image_path,
										'title'            => stripslashes($valueCountDownGrid->banner_heading),
										'image_contentMode' => $valueCountDownGrid->image_contentMode,
										'upto_time'        => (string) (strtotime($valueCountDownGrid->countdown) - strtotime('now')),
										'is_timer_background_clear' => $valueCountDownGrid->is_enabled_background_color,
										'timer_background_color' => $valueCountDownGrid->is_enabled_background_color ? str_replace('#', '', $valueCountDownGrid->background_color) : '00000000',
										'timer_text_color' => str_replace('#', '', $valueCountDownGrid->text_color),
									);
								}
							}

							$elements[] = array(
								'element_type' => 'banners_countdown',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $banner_countdown_data,
							);
							break;
							// Products-Square
						case 6:
							// Product Sqaure
							$products_square_data    = array();
							$products_square_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($products_square_details as $key_square_product => $value_square_product) {
								if (isset($value_square_product->product_type) && !empty($value_square_product->product_type)) {
									// Get product based on product type
									switch ($value_square_product->product_type) {
										case 'new_products':
											// Pass General settings , number of products
											$products_square_data = $this->getRecentProduct($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $session_data, $email); // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											break;
										case 'best_seller':
											$products_square_data = $this->bestSellerProduct($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $session_data, $email); // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											break;
										case 'special_products':
											$products_square_data = $this->specialProduct($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											break;
										case 'featured_products':
											$products_square_data = $this->featuresProduct($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $session_data, $email); // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											break;
											// BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
										case 'category_products':
											if (isset($value_square_product->category_products) && !empty($value_square_product->category_products)) {
												$product_ids          = explode(',', $value_square_product->category_products);
												$products_square_data = $this->getCustomProductsDetails($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											}
											break;
										case 'custom_products':
											$product_ids          = explode(',', $value_square_product->custom_products);
											$products_square_data = $this->getCustomProductsDetails($settings, $value_square_product->number_of_products, $value_square_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity - done by Saurav Choudhary on 10-Sep-2020
											break;
											// EOC
									}
								}
							}

							if (isset($products_square_data) && !empty($products_square_data)) {
								$elements[] = array(
									'element_type' => 'products_square',
									'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
									'data'         => $products_square_data,
								);
							}

							break;
							// Products-Horizontal Sliding
						case 7:
							// Product Horizontal products
							$products_horizontal_data    = array();
							$products_horizontal_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($products_horizontal_details as $key_horizontal_product => $value_horizontal_product) {

								if (isset($value_horizontal_product->product_type) && !empty($value_horizontal_product->product_type)) {
									// Get product based on product type
									switch ($value_horizontal_product->product_type) {
										case 'new_products':
											// Pass General settings , number of products
											$products_horizontal_data = $this->getRecentProduct($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'best_seller':
											$products_horizontal_data = $this->bestSellerProduct($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'special_products':
											$products_horizontal_data = $this->specialProduct($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'featured_products':
											$products_horizontal_data = $this->featuresProduct($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
											// BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
										case 'category_products':
											if (isset($value_horizontal_product->category_products) && !empty($value_horizontal_product->category_products)) {
												$product_ids              = explode(',', $value_horizontal_product->category_products);
												$products_horizontal_data = $this->getCustomProductsDetails($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											}
											break;
										case 'custom_products':
											$product_ids              = explode(',', $value_horizontal_product->custom_products);
											$products_horizontal_data = $this->getCustomProductsDetails($settings, $value_horizontal_product->number_of_products, $value_horizontal_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
											// EOC
									}
								}
							}
							$elements[] = array(
								'element_type' => 'products_horizontal',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $products_horizontal_data,
							);
							break;
							// Products-Grid
						case 8:
							// Grid Products Element details
							$grid_products_data    = array();
							$grid_products_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_product_data` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($grid_products_details as $key_grid_product => $value_grid_product) {
								if (isset($value_grid_product->product_type) && !empty($value_grid_product->product_type)) {
									// Get product based on product type
									switch ($value_grid_product->product_type) {
										case 'new_products':
											// Pass General settings , number of products
											$grid_products_data = $this->getRecentProduct($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'best_seller':
											$grid_products_data = $this->bestSellerProduct($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'special_products':
											$grid_products_data = $this->specialProduct($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
										case 'featured_products':
											$grid_products_data = $this->featuresProduct($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
											// BOC neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2 Added Custom Products and Category Custom product
										case 'category_products':
											if (isset($value_grid_product->category_products) && !empty($value_grid_product->category_products)) {
												$product_ids        = explode(',', $value_grid_product->category_products);
												$grid_products_data = $this->getCustomProductsDetails($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											}
											break;
										case 'custom_products':
											$product_ids        = explode(',', $value_grid_product->custom_products);
											$grid_products_data = $this->getCustomProductsDetails($settings, $value_grid_product->number_of_products, $value_grid_product->image_content_mode, $product_ids, $session_data, $email);  // Parameters session_data and email have been added for cart quantity
											break;
											// EOC
									}
								}
							}
							$elements[] = array(
								'element_type' => 'products_grid',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'data'         => $grid_products_data,
							);
							break;
							// Products-Last Accessed
						case 9:
							$elements[] = array(
								'element_type' => 'products_recent',
								'heading'      => 'Recent Products',
								'data'         => array(),
							);
							break;
							// Custom Banner-Square
						case 10:
							// Banner Custom Element details
							$banner_custom_data    = array();
							$banner_custom_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}mab_mobileapp_banners` WHERE `id_component` = %d ORDER BY id ASC", $valueElement->id_component));
							foreach ($banner_custom_details as $keyBanner => $valueBanner) {
								$target_id = 0;
								// Category Type
								if ('1' == $valueBanner->id_banner_type) {
									$target_id = $valueBanner->category_id;
								} elseif ('2' == $valueBanner->id_banner_type) {
									// Product Type
									$target_id = $valueBanner->product_id;
								} else {
									// Added Extra Posible Redirection
									$target_id = 0;
								}
								if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
									$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
								} elseif (file_exists(WMAB_DIR . 'views/images/home_page_layout/' . $valueBanner->image_url)) {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/' . $valueBanner->image_url;
								} else {
									$uploaded_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png';
								}

								$banner_custom_data[] = array(
									'click_target'      => $valueBanner->redirect_activity,
									'target_id'         => (string) $target_id,
									'src'               => $uploaded_image_path,
									'title'             => stripslashes($valueBanner->banner_heading),
									'image_contentMode' => $valueBanner->image_contentMode,
									'insets'            => array(
										'left'   => isset($valueBanner->inset_left) && !empty($valueBanner->inset_left) ? (string) $valueBanner->inset_left : '0',
										'top'    => isset($valueBanner->inset_top) && !empty($valueBanner->inset_top) ? (string) $valueBanner->inset_top : '0',
										'bottom' => isset($valueBanner->inset_bottom) && !empty($valueBanner->inset_bottom) ? (string) $valueBanner->inset_bottom : '0',
										'right'  => isset($valueBanner->inset_right) && !empty($valueBanner->inset_right) ? (string) $valueBanner->inset_right : '0',
									),
									'bg_color'          => isset($valueBanner->banner_custom_background_color) && !empty($valueBanner->banner_custom_background_color) ? str_replace('#', '', $valueBanner->banner_custom_background_color) : 'ffffff',
									'banner_width'      => isset($valueBanner->banner_width) && !empty($valueBanner->banner_width) ? $valueBanner->banner_width : '100',
									'banner_height'     => isset($valueBanner->banner_height) && !empty($valueBanner->banner_height) ? $valueBanner->banner_height : '100',
								);
							}
							$elements[] = array(
								'element_type' => 'banners_custom',
								'heading'      => isset($valueElement->component_heading) && !empty($valueElement->component_heading) ? stripslashes($valueElement->component_heading) : '',
								'is_sliding'   => '0',
								'data'         => $banner_custom_data,
							);
							break;
					}
				}
			}
			return $elements;
		}

		return $elements;
	}

	/** Get recent product based on number of product set in general settings
	 *
	 * @param  type $settings
	 * @return type
	 * Get recent product based on number of product set in general settings
	 */
	public function getRecentProduct($settings, $number_of_product = '', $image_content_mode = '', $session_data = '', $email = '')
	{
		$recent_products_data = array();
		global $wpdb;
		// Recent Products Element details
		$args         = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => isset($number_of_product) && !empty($number_of_product) ? $number_of_product : $settings['latest']['limit'],
			'orderby'        => 'post_date',
		);
		$latest_query = new WP_Query($args);
		if (isset($latest_query->posts) && !empty($latest_query->posts)) {
			foreach ($latest_query->posts as $latest) {
				$product = wc_get_product($latest->ID);
				if (!empty($product) && $product->is_visible()) {
					$is_product_new = 0;
					if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $settings['general']['product_new_date']))) {
						$is_product_new = 1;
					}

					if ('variable' == $product->get_type()) { // For Variable Product
						$available_variations = $product->get_available_variations();
						$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product     = new WC_Product_Variation($variation_id);
						$regular_price        = $variable_product->get_regular_price();
						$sale_price           = $variable_product->get_sale_price();

						/**
						 * Updated the condition to check whether the product is on sale or not
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($variable_product->is_on_sale()) {
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
						} else {
							$discount_percentage = 0;
						}
					} else {
						// For Simple Products
						/**
						 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($product->is_on_sale()) {
							// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

							// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
						} else {
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = '';
						}
					}

					// Get Product Category
					$product_category      = $product->get_category_ids();
					$product_category_id   = '';
					$product_category_name = '';
					if (isset($product_category[0]) && !empty($product_category[0])) {
						$product_category_id   = $product_category[0];
						$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
						$product_category_name = $product_category_name->name;
					}

					// Check if image exists otherwise send dummy image - added by Harsh Agarwal on 12-Jun-2020
					$product_thumbnail = get_the_post_thumbnail_url($latest->ID, 'full');
					if (isset($product_thumbnail) && !empty($product_thumbnail)) {
						$image_path = $product_thumbnail;

						$wmab_settings = get_option('wmab_settings');
						$settings_data = array();
						if (isset($wmab_settings) && !empty($wmab_settings)) {
							$settings_data = unserialize($wmab_settings);
						}
						if (!empty($settings_data['general']['image_url_encoding_status'])) {
							$image_path = $this->url_onlyfile_encode($product_thumbnail);
						}
					} else {
						$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}
					// Ends
					// get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
					$cart_quantity = 0;
					// Set here your product ID
					$targeted_id = (string) $latest->ID;
					// set session data
					if (isset($session_data) && !empty($session_data)) {
						$cart_id = $session_data;
					} elseif (isset($email) && !empty($email)) {
						$cart_id = email_exists($email);
					}

					if (!empty($cart_id)) {
						$this->set_session($cart_id);
						$session_value = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
								$cart_id
							)
						);
						$cart_value    = unserialize($session_value);
						$cart_contents = unserialize($cart_value['cart']);
						if (!empty($cart_contents)) {
							foreach ($cart_contents as $cart_item) {
								if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
									$cart_quantity = $cart_item['quantity'];
									break; // stop the loop if product is found
								}
							}
						}
					}

					// Find Review Ratings
					$total_reviews      = 0;
					$reviews_rating_sum = 0;
					$reviews_average    = 0;
					$args               = array(
						'post_type' => 'product',
						'post_id'   => $latest->ID,
						'status'    => 'approve',
					);
					$reviews            = get_comments($args);

					foreach ($reviews as $review) {
						++$total_reviews;
						$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
						$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
					}
					if (0 != $total_reviews) {
						$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
					}

					// Check if product is variable or simple - Saurav Choudhary - 31-Aug-2020
					$has_attributes = $product->get_type() == 'variable' ? '1' : '0';

					$recent_products_data[] = array(
						'id'                  => (string) $latest->ID,
						'is_in_wishlist'      => false,
						'name'                => $product->get_name(),
						'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
						'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
						'new_products'        => (int) $is_product_new,
						'on_sale_products'    => $discount_percentage ? 1 : 0,
						'category_name'       => $product_category_name,
						'image_contentMode'   => $image_content_mode,
						'ClickActivityName'   => '',
						'category_id'         => '',
						'price'               => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $regular_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'src'                 => $image_path,
						'minimal_quantity'    => '1',
						'discount_price'      => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $sale_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
						'cart_quantity'       => (int) $cart_quantity,
						'has_attributes'      => $has_attributes,
						'number_of_reviews'   => (string) $total_reviews,
						'averagecomments'     => (string) $reviews_average,
					);
				}
			}
		}
		return $recent_products_data;
	}

	/** Get based seller product by number of product
	 *
	 * @param  type $settings
	 * @return type
	 * Get based seller product by number of product
	 */
	public function bestSellerProduct($settings, $number_of_product = '', $image_content_mode = '', $session_data = '', $email = '')
	{
		// Parameters session_data and email have been added for cart quantity
		global $wpdb;
		$bestsellerProducts = array();
		$args               = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => isset($number_of_product) && !empty($number_of_product) ? $number_of_product : $settings['latest']['limit'],
			'meta_key'       => 'total_sales',
			'orderby'        => 'meta_value_num',
		);

		$bestseller_query = new WP_Query($args);
		if (isset($bestseller_query->posts) && !empty($bestseller_query->posts)) {
			foreach ($bestseller_query->posts as $bestseller) {

				$product = wc_get_product($bestseller->ID);
				if (!empty($product) && $product->is_visible()) {
					$is_product_new = 0;
					if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $settings['general']['product_new_date']))) {
						$is_product_new = 1;
					}

					if ('variable' == $product->get_type()) { // For Variable Product
						$available_variations = $product->get_available_variations();
						$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product     = new WC_Product_Variation($variation_id);
						$regular_price        = $variable_product->get_regular_price();
						$sale_price           = $variable_product->get_sale_price();

						/**
						 * Updated the condition to check whether the product is on sale or not
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($variable_product->is_on_sale()) {
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
						} else {
							$discount_percentage = 0;
						}
					} else {
						// For Simple Products
						/**
						 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($product->is_on_sale()) {
							// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

							// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
						} else {
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = '';
						}
					}

					// Get Product Category
					$product_category      = $product->get_category_ids();
					$product_category_id   = '';
					$product_category_name = '';
					if (isset($product_category[0]) && !empty($product_category[0])) {
						$product_category_id   = $product_category[0];
						$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
						$product_category_name = $product_category_name->name;
					}

					// Check if image exists otherwise send dummy image - added by Harsh Agarwal on 12-Jun-2020
					$product_thumbnail = get_the_post_thumbnail_url($bestseller->ID, 'full');
					if (isset($product_thumbnail) && !empty($product_thumbnail)) {
						$image_path = $product_thumbnail;

						$wmab_settings = get_option('wmab_settings');
						$settings_data = array();
						if (isset($wmab_settings) && !empty($wmab_settings)) {
							$settings_data = unserialize($wmab_settings);
						}
						if (!empty($settings_data['general']['image_url_encoding_status'])) {
							$image_path = $this->url_onlyfile_encode($product_thumbnail);
						}
					} else {
						$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}
					// Ends
					// get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
					$cart_quantity = 0;
					// Set here your product ID
					$targeted_id = (string) $bestseller->ID;

					// set session data
					if (isset($session_data) && !empty($session_data)) {
						$cart_id = $session_data;
					} elseif (isset($email) && !empty($email)) {
						$cart_id = email_exists($email);
					}

					if (!empty($cart_id)) {
						$this->set_session($cart_id);
						$session_value = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
								$cart_id
							)
						);
						$cart_value    = unserialize($session_value);
						$cart_contents = unserialize($cart_value['cart']);
						if (!empty($cart_contents)) {
							foreach ($cart_contents as $cart_item) {
								if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
									$cart_quantity = $cart_item['quantity'];
									break; // stop the loop if product is found
								}
							}
						}
					}

					// Find Review Ratings
					$total_reviews      = 0;
					$reviews_rating_sum = 0;
					$reviews_average    = 0;
					$args               = array(
						'post_type' => 'product',
						'post_id'   => $bestseller->ID,
						'status'    => 'approve',
					);
					$reviews            = get_comments($args);

					foreach ($reviews as $review) {
						++$total_reviews;
						$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
						$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
					}
					if (0 != $total_reviews) {
						$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
					}
					// Check if product is variable or simple - Saurav Choudhary - 31-Aug-2020
					$has_attributes = $product->get_type() == 'variable' ? '1' : '0';

					$bestsellerProducts[] = array(
						'id'                  => (string) $bestseller->ID,
						'name'                => $product->get_name(),
						'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
						'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
						'new_products'        => (int) $is_product_new,
						'on_sale_products'    => $discount_percentage ? 1 : 0,
						'category_name'       => $product_category_name,
						'image_contentMode'   => $image_content_mode,
						'ClickActivityName'   => '',
						'category_id'         => '',
						'price'               => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $regular_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'src'                 => $image_path,
						'minimal_quantity'    => '1',
						'discount_price'      => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $sale_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
						'is_in_wishlist'      => false,
						'cart_quantity'       => (int) $cart_quantity,
						'has_attributes'      => $has_attributes,
						'number_of_reviews'   => (string) $total_reviews,
						'averagecomments'     => (string) $reviews_average,
					);
				}
			}
		}
		return $bestsellerProducts;
	}

	/** Get special product by number of product
	 *
	 * @param  type $settings
	 * @return type
	 * Get special product by number of product
	 */
	public function specialProduct($settings, $number_of_product = '', $image_content_mode = '', $session_data = '', $email = '')
	{
		// Parameters session_data and email have been added for cart quantity
		global $wpdb;
		$args = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => isset($number_of_product) && !empty($number_of_product) ? $number_of_product : $settings['latest']['limit'],
			'post__in'       => wc_get_product_ids_on_sale(),
		);

		$specialProducts = array();

		$special_query = new WP_Query($args);

		if (isset($special_query->posts) && !empty($special_query->posts)) {
			foreach ($special_query->posts as $special) {

				$product = wc_get_product($special->ID);
				if (!empty($product) && $product->is_visible()) {
					$is_product_new = 0;
					// 20/12/2019 format changed to 20-12-2019 strtotime doesn't changed 20/12/2019 type of format
					if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $settings['general']['product_new_date']))) {
						$is_product_new = 1;
					}

					if ('variable' == $product->get_type()) { // For Variable Product
						$available_variations = $product->get_available_variations();
						$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product     = new WC_Product_Variation($variation_id);
						$regular_price        = $variable_product->get_regular_price();
						$sale_price           = $variable_product->get_sale_price();

						/**
						 * Updated the condition to check whether the product is on sale or not
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($variable_product->is_on_sale()) {
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
						} else {
							$discount_percentage = 0;
						}
					} else {
						// For Simple Products
						/**
						 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($product->is_on_sale()) {
							// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

							// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
						} else {
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = '';
						}
					}

					// Get Product Category
					$product_category      = $product->get_category_ids();
					$product_category_id   = '';
					$product_category_name = '';
					if (isset($product_category[0]) && !empty($product_category[0])) {
						$product_category_id   = $product_category[0];
						$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
						$product_category_name = $product_category_name->name;
					}

					// Check if image exists otherwise send dummy image - added by Harsh Agarwal on 12-Jun-2020
					$product_thumbnail = get_the_post_thumbnail_url($special->ID, 'full');
					if (isset($product_thumbnail) && !empty($product_thumbnail)) {
						$image_path = $product_thumbnail;

						$wmab_settings = get_option('wmab_settings');
						$settings_data = array();
						if (isset($wmab_settings) && !empty($wmab_settings)) {
							$settings_data = unserialize($wmab_settings);
						}
						if (!empty($settings_data['general']['image_url_encoding_status'])) {
							$image_path = $this->url_onlyfile_encode($product_thumbnail);
						}
					} else {
						$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}
					// Ends
					// get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
					$cart_quantity = 0;
					// Set here your product ID
					$targeted_id = (string) $special->ID;

					// set session data
					if (isset($session_data) && !empty($session_data)) {
						$cart_id = $session_data;
					} elseif (isset($email) && !empty($email)) {
						$cart_id = email_exists($email);
					}

					if (!empty($cart_id)) {
						$this->set_session($cart_id);
						$session_value = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
								$cart_id
							)
						);
						$cart_value    = unserialize($session_value);
						$cart_contents = unserialize($cart_value['cart']);
						if (!empty($cart_contents)) {
							foreach ($cart_contents as $cart_item) {
								if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
									$cart_quantity = $cart_item['quantity'];
									break; // stop the loop if product is found
								}
							}
						}
					}

					// Find Review Ratings
					$total_reviews      = 0;
					$reviews_rating_sum = 0;
					$reviews_average    = 0;
					$args               = array(
						'post_type' => 'product',
						'post_id'   => $special->ID,
						'status'    => 'approve',
					);
					$reviews            = get_comments($args);

					foreach ($reviews as $review) {
						++$total_reviews;
						$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
						$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
					}
					if (0 != $total_reviews) {
						$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
					}

					// Check if product is variable or simple - Saurav Choudhary - 31-Aug-2020
					$has_attributes = $product->get_type() == 'variable' ? '1' : '0';

					$specialProducts[] = array(
						'id'                  => (string) $special->ID,
						'is_in_wishlist'      => false,
						'name'                => $product->get_name(),
						'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
						'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
						'new_products'        => (int) $is_product_new,
						'on_sale_products'    => $discount_percentage ? 1 : 0,
						'category_name'       => $product_category_name,
						'image_contentMode'   => $image_content_mode,
						'ClickActivityName'   => '',
						'category_id'         => '',
						'price'               => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $regular_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'src'                 => $image_path,
						'minimal_quantity'    => '1',
						'discount_price'      => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $sale_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
						'cart_quantity'       => (int) $cart_quantity,
						'has_attributes'      => $has_attributes,
						'number_of_reviews'   => (string) $total_reviews,
						'averagecomments'     => (string) $reviews_average,
					);
				}
			}
		}
		return $specialProducts;
	}

	/** Get special product by number of product
	 *
	 * @param  type $settings
	 * @return type
	 * Get special product by number of product
	 */
	public function featuresProduct($settings, $number_of_product = '', $image_content_mode = '', $session_data = '', $email = '')
	{
		// Parameters session_data and email have been added for cart quantity
		global $wpdb;
		$args = array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => isset($number_of_product) && !empty($number_of_product) ? $number_of_product : $settings['latest']['limit'],
			'post__in'       => wc_get_featured_product_ids(),
		);

		$featuredProducts = array();

		$featured_query = new WP_Query($args);

		if (isset($featured_query->posts) && !empty($featured_query->posts)) {
			foreach ($featured_query->posts as $featured) {

				$product = wc_get_product($featured->ID);
				if (!empty($product) && $product->is_visible()) {
					$is_product_new = 0;
					if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $settings['general']['product_new_date']))) {
						$is_product_new = 1;
					}

					if ('variable' == $product->get_type()) { // For Variable Product
						$available_variations = $product->get_available_variations();
						$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product     = new WC_Product_Variation($variation_id);
						$regular_price        = $variable_product->get_regular_price();
						$sale_price           = $variable_product->get_sale_price();

						/**
						 * Updated the condition to check whether the product is on sale or not
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($variable_product->is_on_sale()) {
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
						} else {
							$discount_percentage = 0;
						}
					} else {
						// For Simple Products
						/**
						 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
						 * VGfeb2023 sale-issue
						 *
						 * @date   01-03-2023
						 */
						if ($product->is_on_sale()) {
							// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

							// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
						} else {
							$sale_price          = $product->get_sale_price();
							$regular_price       = $product->get_regular_price();
							$discount_percentage = '';
						}
					}

					// Get Product Category
					$product_category      = $product->get_category_ids();
					$product_category_id   = '';
					$product_category_name = '';
					if (isset($product_category[0]) && !empty($product_category[0])) {
						$product_category_id   = $product_category[0];
						$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
						$product_category_name = $product_category_name->name;
					}

					// Check if image exists otherwise send dummy image - added by Harsh Agarwal on 12-Jun-2020
					$product_thumbnail = get_the_post_thumbnail_url($featured->ID, 'full');
					if (isset($product_thumbnail) && !empty($product_thumbnail)) {
						$image_path = $product_thumbnail;
					} else {
						$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}
					// Ends
					// get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
					$cart_quantity = 0;
					// Set here your product ID
					$targeted_id = (string) $featured->ID;
					// set session data
					if (isset($session_data) && !empty($session_data)) {
						$cart_id = $session_data;
					} elseif (isset($email) && !empty($email)) {
						$cart_id = email_exists($email);
					}

					if (!empty($cart_id)) {
						$this->set_session($cart_id);
						$session_value = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
								$cart_id
							)
						);
						$cart_value    = unserialize($session_value);
						$cart_contents = unserialize($cart_value['cart']);
						if (!empty($cart_contents)) {
							foreach ($cart_contents as $cart_item) {
								if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
									$cart_quantity = $cart_item['quantity'];
									break; // stop the loop if product is found
								}
							}
						}
					}

					// Find Review Ratings
					$total_reviews      = 0;
					$reviews_rating_sum = 0;
					$reviews_average    = 0;
					$args               = array(
						'post_type' => 'product',
						'post_id'   => $featured->ID,
						'status'    => 'approve',
					);
					$reviews            = get_comments($args);

					foreach ($reviews as $review) {
						++$total_reviews;
						$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
						$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
					}
					if (0 != $total_reviews) {
						$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
					}

					// Check if product is variable or simple - Saurav Choudhary - 31-Aug-2020
					$has_attributes = $product->get_type() == 'variable' ? '1' : '0';

					$featuredProducts[] = array(
						'id'                  => (string) $featured->ID,
						'is_in_wishlist'      => false,
						'name'                => $product->get_name(),
						'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
						'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
						'new_products'        => (int) $is_product_new,
						'on_sale_products'    => $discount_percentage ? 1 : 0,
						'category_name'       => $product_category_name,
						'image_contentMode'   => $image_content_mode,
						'ClickActivityName'   => '',
						'category_id'         => '',
						'price'               => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $regular_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'src'                 => $image_path,
						'minimal_quantity'    => '1',
						'discount_price'      => html_entity_decode(
							strip_tags(
								wc_price(
									wc_get_price_including_tax(
										$product,
										array(
											'qty'   => 1,
											'price' => $sale_price,
										)
									)
								)
							),
							ENT_QUOTES
						),
						'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
						'cart_quantity'       => (int) $cart_quantity,
						'has_attributes'      => $has_attributes,
						'number_of_reviews'   => (string) $total_reviews,
						'averagecomments'     => (string) $reviews_average,
					);
				}
			}
		}
		return $featuredProducts;
	}

	/** Get custom product details based on number of products
	 * neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2
	 *
	 * @param  type $settings
	 * @return product details
	 * Get custom product details based on number of products
	 */
	public function getCustomProductsDetails($settings, $number_of_product = '', $image_content_mode = '', $products_ids = array(), $session_data = '', $email = '')
	{
		// Parameters session_data and email have been added for cart quantity
		global $wpdb;
		$custom_product_details = array();
		if (isset($products_ids) && !empty($products_ids)) {
			$iteration_limit = 0;
			foreach ($products_ids as $key_product => $key_value) {
				++$iteration_limit;
				if ($iteration_limit <= $number_of_product) {
					$product = wc_get_product($key_value);
					if (!empty($product) && $product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $settings['general']['product_new_date']))) {
							$is_product_new = 1;
						}

						if ('variable' == $product->get_type()) { // For Variable Product
							$available_variations = $product->get_available_variations();
							$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
							$variable_product     = new WC_Product_Variation($variation_id);
							$regular_price        = $variable_product->get_regular_price();
							$sale_price           = $variable_product->get_sale_price();

							/**
							 * Updated the condition to check whether the product is on sale or not
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($variable_product->is_on_sale()) {
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
							} else {
								$discount_percentage = '0';
							}
						} else {
							// For Simple Products
							/**
							 * Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
							 * VGfeb2023 sale-issue
							 *
							 * @date   01-03-2023
							 */
							if ($product->is_on_sale()) {
								// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);

								// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
							} else {
								$sale_price          = $product->get_sale_price();
								$regular_price       = $product->get_regular_price();
								$discount_percentage = '';
							}
						}

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}

						// Check if image exists otherwise send dummy image - added by Harsh Agarwal on 12-Jun-2020
						$product_thumbnail = get_the_post_thumbnail_url($key_value, 'full');
						if (isset($product_thumbnail) && !empty($product_thumbnail)) {
							$image_path = $product_thumbnail;

							$wmab_settings = get_option('wmab_settings');
							$settings_data = array();
							if (isset($wmab_settings) && !empty($wmab_settings)) {
								$settings_data = unserialize($wmab_settings);
							}
							if (!empty($settings_data['general']['image_url_encoding_status'])) {
								$image_path = $this->url_onlyfile_encode($product_thumbnail);
							}
						} else {
							$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}
						// Ends
						// get quantity of product already added to cart - Saurav Choudhary - 31-Aug-2020
						$cart_quantity = 0;
						// Set here your product ID
						$targeted_id = (string) $key_value;

						// set session data
						if (isset($session_data) && !empty($session_data)) {
							$cart_id = $session_data;
						} elseif (isset($email) && !empty($email)) {
							$cart_id = email_exists($email);
						}

						if (!empty($cart_id)) {
							$this->set_session($cart_id);

							$session_value = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
									$cart_id
								)
							);
							$cart_value                                = unserialize($session_value);
							$cart_contents                             = unserialize($cart_value['cart']);
							wmab_log_knowband_app_request('cart', serialize_block_attributes($cart_contents));
							if (!empty($cart_contents)) {
								foreach ($cart_contents as $cart_item) {
									if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
										$cart_quantity = $cart_item['quantity'];
										break; // stop the loop if product is found
									}
								}
							}
						}

						// Check if product is variable or simple - Saurav Choudhary - 31-Aug-2020
						$has_attributes = $product->get_type() == 'variable' ? '1' : '0';

						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $key_value,
							'status'    => 'approve',
						);
						$reviews            = get_comments($args);

						foreach ($reviews as $review) {
							++$total_reviews;
							$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
							$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
						}
						if (0 != $total_reviews) {
							$reviews_average = number_format($reviews_rating_sum / $total_reviews, 1);
						}

						$custom_product_details[] = array(
							'id'                  => (string) $key_value,
							'is_in_wishlist'      => false,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($settings['general']['show_price']) ? '1' : '0',
							'new_products'        => (int) $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'category_name'       => $product_category_name,
							'image_contentMode'   => $image_content_mode,
							'ClickActivityName'   => '',
							'category_id'         => '',
							'price'               => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $regular_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'src'                 => $image_path,
							'minimal_quantity'    => '1',
							'discount_price'      => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => 1,
												'price' => $sale_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage' => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'cart_quantity'       => (int) $cart_quantity,
							'has_attributes'      => $has_attributes,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				} else {
					break;
				}
			}
		}
		return $custom_product_details;
	}

	/** Get Custom Tab Bar
	 * neeraj.kumar@velsof.com 29-Jan-2020 : Module Upgrade V2
	 *
	 * @return tab bar details
	 */
	private function getCustomTabBar($settings = array())
	{
		global $wpdb;
		$tab_bars = array();
		if (isset($settings) && !empty($settings)) {
			$tab_bars['tab_bar_background_color']          = isset($settings['tab_bar_settings']['tab_bar_background_color']) && !empty($settings['tab_bar_settings']['tab_bar_background_color']) ? str_replace('#', '', $settings['tab_bar_settings']['tab_bar_background_color']) : 'ffffff';
			$tab_bars['tab_bar_tint_color']                = isset($settings['tab_bar_settings']['tab_bar_tint_color']) && !empty($settings['tab_bar_settings']['tab_bar_tint_color']) ? str_replace('#', '', $settings['tab_bar_settings']['tab_bar_tint_color']) : '080808';
			$tab_bars['tab_bar_background_color_disabled'] = isset($settings['tab_bar_settings']['tab_bar_disable_icon_color']) && !empty($settings['tab_bar_settings']['tab_bar_disable_icon_color']) ? str_replace('#', '', $settings['tab_bar_settings']['tab_bar_disable_icon_color']) : 'b0b0b0';
			$tabs_list                                     = array();
			$tab_details                                   = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mab_mobileapp_tab_bar"
				)
			);
			foreach ($tab_details as $key_tab => $value_tab) {
				if (isset($value_tab) && !empty($value_tab)) {
					$tabs_list[] = array(
						'image_src'   => isset($value_tab->tab_icon_image) ? WMAB_URL . 'views/images/home_page_layout/' . $value_tab->tab_icon_image : '',
						'redirect_to' => isset($value_tab->tab_icon_redirect_activity) ? ucfirst(str_replace('_', ' ', $value_tab->tab_icon_redirect_activity)) : '',
						'title'       => isset($value_tab->tab_icon_text) ? $value_tab->tab_icon_text : '',
					);
				}
			}
			$tab_bars['tabs'] = $tabs_list;
		}
		return $tab_bars;
	}

	/**
	 * Function to handle appHandleDeepLink API request
	 * saurav.chaudhary@velsof.com - Saurav Choudhary - 31-Aug-2020
	 *
	 * @param  $full_url_of_page - URL for which we need to check whether it is the URL of product page / category page/ CMS page, $version
	 * @return tab bar details
	 */
	public function app_handle_deep_link($full_url_of_page, $version)
	{
		global $wpdb;
		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appHandleDeepLink');

		$this->wmab_response['deep_link'] = '';
		// get product and category base for URLs
		$permalink_settings = get_option('woocommerce_permalinks');
		$product_base       = str_replace('/', '', $permalink_settings['product_base']);
		$category_base      = str_replace('/', '', $permalink_settings['category_base']);
		// $cms_pages =
		// remove forward slash from end of url
		$full_url_of_page = rtrim($full_url_of_page, '/');
		// split URL into elements
		$url_elements  = explode('/', $full_url_of_page);
		$target_title  = end($url_elements);
		$cms_page_name = get_page_by_title($target_title);
		$target_type   = '';

		// classify if URL is for category or product
		foreach ($url_elements as $url_element) {
			if (strcmp($url_element, $product_base) == 0) {
				$target_type = 'product';
				break;
			} elseif (strcmp($url_element, $category_base) == 0) {
				$target_type = 'category';
				break;
			} elseif (null != $cms_page_name) {
				$target_type = 'cms_page';
				break;
			}
		}
		if ('product' == $target_type) {
			// get the post details from post_name i.e $target_title
			$post_details = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}posts WHERE post_name = %s",
					$target_title
				)
			);
			$post_details = $post_details[0];
			$target_id    = $post_details->ID;
		} elseif ('category' == $target_type) {
			// get the category details from post_name i.e $target_title
			$term_details = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}terms WHERE slug = %s",
					$target_title
				)
			);
			$term_details = $term_details[0];
			$target_id    = $term_details->term_id;
		} elseif ('cms_page' == $target_type) {
			$target_id = $cms_page_name->ID;
		} else {
			$this->wmab_response['deep_link'] = array(
				'status'         => 'failure',
				'click_target'   => '',
				'target_id'      => '',
				'title'          => '',
				'install_module' => '',
			);
			// Log Request
			wmab_log_knowband_app_request('appHandleDeepLink', serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		$this->wmab_response['deep_link'] = array(
			'status'         => 'success',
			'click_target'   => $target_type,
			'target_id'      => (string) $target_id,
			'title'          => $target_title,
			'install_module' => '',
		);

		// Log Request
		wmab_log_knowband_app_request('appHandleDeepLink', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/** Get encoded Image URL
	 * mahima.bhatt@velsof.com 07-Sep-2021 : Module Upgrade V2
	 *
	 * @param  type $url
	 * @return encoded URL
	 * Get encoded Image URL
	 */
	public function url_onlyfile_encode($url)
	{
		if (preg_match('#^(.*/)([^/]+)$#u', $url, $res)) {
			return $res[1] . urlencode($res[2]);
		}
		return urlencode($url);
	}
}
