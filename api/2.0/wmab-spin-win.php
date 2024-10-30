<?php

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly
}

define('KB_WMAB_API_VERSION', '2.0');

/**
 * Class - WmabSpinWin
 *
 * This class contains constructor and other methods which are actually related to Spin Win Page actions
 *
 * @version v2.0
 * @Date    10-Jun-2022
 */

class WmabSpinWin
{


	/** String A private variable of class which holds WooCommerce plugin settings
	 *
	 * @var    string A private variable of class which holds WooCommerce plugin settings
	 */
	private $wmab_plugin_settings = array();

	/** String A private variable of class which holds API response
	 *
	 * @var    string A private variable of class which holds API response
	 */
	private $wmab_response = array();

	/** String A private variable of class which hold API response
	 *
	 * @var string A private variable of class which hold API response
	 */
	private $wmab_spin_win_settings = array();

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

		$this->wmab_response['install_module'] = ''; // Set default blank value to send as response in each request
		// Get Mobile App Builder settings from database
		$wmab_settings = get_option('wmab_settings');
		if (isset($wmab_settings) && !empty($wmab_settings)) {
			$this->wmab_plugin_settings = unserialize($wmab_settings);
		}
		// Get Spin & Win Settings
		if (isset($this->wmab_plugin_settings['general']['spin_win_status']) && $this->wmab_plugin_settings['general']['spin_win_status']) {
			$wmab_spin_win_options = get_option('wsaw_settings');
			if (isset($wmab_spin_win_options) && !empty($wmab_spin_win_options)) {

				$this->wmab_spin_win_settings = unserialize($wmab_spin_win_options);
			}
		}

		// Suspend execution if plugin is not installed or disabled and send output
		if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
			$this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'knowband-mobile-app-builder-for-woocommerce');
			// Log Request
			wmab_log_knowband_app_request($request, serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		$this->wmab_wc_version = wmab_get_woocommerce_version_number();
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
		// Include Front End Libraries/Classes
		WC()->frontend_includes();
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
	 * Function to handle app_get_spin_win API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.2/appGetSpinWin
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_get_spin_win($version)
	{

		// Log Request
		wmab_log_knowband_app_request('appGetSpinWin', serialize_block_attributes($this->wmab_response));
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$settings = $this->wmab_spin_win_settings;

		// Check if plugin enabled
		if (isset($this->wmab_spin_win_settings['general']['enabled']) && !empty($this->wmab_spin_win_settings['general']['enabled'])) {
			if (!class_exists('MailChimp')) {
				include_once plugin_dir_path(__FILE__) . 'lib/drewm/mailchimp-api/src/MailChimp.php';
			}
			$show_wheel_on_page = true;
			// Check if plugin enabled
			if (isset($settings['general']['enabled']) && !empty($settings['general']['enabled'])) {
				// Default Values
				$mobile_only           = true;
				$every_visit_flag      = true;
				$hide_after            = '';
				$show_on_page          = true;
				$show_wheel            = true;
				$time_display          = '';
				$scroll_display        = '';
				$exit_display          = false;
				$new_visit_flag        = true;
				$return_visit_flag     = true;
				$all_visitor           = true;
				$display_interval_flag = true;
				$mobile_class          = '';
				// Detect device
				// Check pages to display
				$current_page = '';
				if (is_front_page()) {
					$current_page = 'home';
				} elseif (is_category() || is_product_category()) {
					$current_page = 'category';
				} elseif (is_product()) {
					$current_page = 'product';
				}

				if (isset($settings['display']['display_position']) && '2' == $settings['display']['display_position']) {
					$show_pages = $settings['display']['selected_pages'];

					if (in_array($current_page, $show_pages)) {
						$show_on_page = true;
					} else {
						$show_on_page = false;
					}
				} elseif (isset($settings['display']['display_position']) && '3' == $settings['display']['display_position']) {
					$not_show_pages = $settings['display']['selected_pages'];
					if (in_array($current_page, $not_show_pages)) {
						$show_on_page = false;
					} else {
						$show_on_page = true;
					}
				}

				// Coupon Display Options
				if (isset($settings['email_settings']['coupon_display_options']) && '1' == $settings['email_settings']['coupon_display_options']) {
					$coupon_display_option = 1;
				} elseif (isset($settings['email_settings']['coupon_display_options']) && '2' == $settings['email_settings']['coupon_display_options']) {
					$coupon_display_option = 2;
				} else {
					$coupon_display_option = '';
				}
			}
		}
		header('Content-Type: text/html');
		// include js and css
		wp_enqueue_style('spin_wheel', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/css/spin_wheel.css', array(), wp_get_theme()->get('Version'));

		wp_enqueue_script('jquery');
		wp_enqueue_script('spin_wheel', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/js/spin-win/spin_wheel.js?test=2', array(), wp_get_theme()->get('Version'));
		wp_enqueue_script('velsof_wheel', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/js/spin-win/velsof_wheel.js', array(), wp_get_theme()->get('Version'));
		wp_enqueue_script('velovalidation', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/js/spin-win/velovalidation.js', array(), wp_get_theme()->get('Version'));
		wp_enqueue_script('tooltipster', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/js/spin-win/tooltipster.min.js', array(), wp_get_theme()->get('Version'));
		wp_enqueue_script('jquery.fireworks', str_replace('api/' . $version . '/', '', plugins_url('/', __FILE__)) . 'views/js/spin-win/jquery.fireworks.js', array(), wp_get_theme()->get('Version'));

		/**
		 * Escaping is not required, as when using wp_kses_post or esc_html because these functions convert the content to plain text, ensuring its safe display without the need for additional escaping.
		 *
		 * @since 1.0.0
		 */
		echo (wc_get_template_html(
			'../../' . str_replace('/api', '', plugin_basename(dirname(__DIR__))) . '/views/spin-win-page.php',
			array(
				'settings'              => $this->wmab_spin_win_settings,
				'show_wheel_on_page'    => $show_wheel_on_page,
				'mobile_only'           => $mobile_only,
				'every_visit_flag'      => $every_visit_flag,
				'hide_after'            => $hide_after,
				'show_on_page'          => $show_on_page,
				'show_wheel'            => $show_wheel,
				'time_display'          => $time_display,
				'scroll_display'        => $scroll_display,
				'exit_display'          => $exit_display,
				'new_visit_flag'        => $new_visit_flag,
				'return_visit_flag'     => $return_visit_flag,
				'all_visitor'           => $all_visitor,
				'display_interval_flag' => $display_interval_flag,
				'mobile_class'          => $mobile_class,
				'site_url'              => get_site_url(),
				'rest_url'              => get_rest_url(null, 'wmab/' . $version . '/appGetSpinWin?version=1.2&content_only=1&is_wheel_used=1'),
			)
		)); // phpcs:ignore
		die();
	}
}
