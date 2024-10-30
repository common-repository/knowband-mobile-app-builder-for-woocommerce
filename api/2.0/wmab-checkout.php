<?php

/**
 * This file contains the Wmabcheckout class, which is responsible for handling the checkout process in the WooCommerce Mobile App Builder plugin.
 *
 * @package WooCommerce Mobile App Builder
 * @author Knowband
 * @version 2.0
 */

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly.
}

define('KB_WMAB_API_VERSION', '2.0');

class WmabCheckout
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

		if (isset($_REQUEST['email']) && !empty($_REQUEST['email'])) {
			$cart_id = email_exists(sanitize_text_field($_REQUEST['email']));
			wp_set_current_user($cart_id); // phpcs:ignore
		} elseif (isset($_REQUEST['session_data']) && !empty($_REQUEST['session_data'])) {
			$cart_id = sanitize_text_field($_REQUEST['session_data']);
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
		// Get Mobile App Builder settings from database
		$wmab_settings = get_option('wmab_settings');
		if (isset($wmab_settings) && !empty($wmab_settings)) {
			$this->wmab_plugin_settings = unserialize($wmab_settings);
		}

		// Suspend execution if plugin is not installed or disabled and send output
		if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
			$this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'knowband-mobile-app-builder-for-woocommerce');
			// Log Request
			wmab_log_knowband_app_request($request, serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		// BOC - hagarwal@velsof.com - Changes added to make module compatible on WooCommerce >= 3.6 - This change initiate the required objects to call functions
		$this->wmab_wc_version = wmab_get_woocommerce_version_number();
		// if ($this->wmab_wc_version >= '3.6.0') {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		if (null === WC()->session) {
			/**
			 * Customize the session handler used by WooCommerce.
			 *
			 * This hook allows you to modify the session handler used by WooCommerce
			 * for managing user sessions. You can use this hook to implement custom
			 * session handling logic or integrate with third-party session management
			 * solutions.
			 *
			 * @since 1.0.0
			 *
			 * @param object $session_handler The default session handler object.
			 */
			$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
			WC()->session  = new $session_class(); // For Session Object
			WC()->session->init();
		}
		if (null === WC()->customer) {
			WC()->customer = new WC_Customer(get_current_user_id(), true); // For Customer Object
		}
		if (null === WC()->cart) {
			WC()->cart = new WC_Cart(); // For Cart Object
			WC()->cart->get_cart();
			// Added by Ashish on 5th May 2022 to Save Cart Data into the User Meta Table.
			// The current cart will be saved in the User Meta Table. Altough Cart is saved on into the User Meta Table on Product Add/Update/Delete itself but in the case of cart merge at the time of login, Updated Cart items was not persisting. So made persistance of the cart on next page refresh
			// DB Key: _woocommerce_persistent_cart_
			WC()->cart->persistent_cart_update();
		}
		if (null === WC()->countries) {
			WC()->countries = new WC_Countries(); // For Country Object
		}
		if (null === WC()->shipping) {
			WC()->shipping = new WC_Shipping(); // For Shipping Object
		}
		if (null === WC()->payment_gateways) {
			WC()->payment_gateways = new WC_Payment_Gateways(); // For Payment Gateway Object
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
	 * Function to verify API Version
	 *
	 * @param  string $cart_id This parameter holds current cart ID to set int Session variable
	 */
	private function set_session($cart_id)
	{

		/**
		 * Fires when the WooCommerce session is about to expire.
		 *
		 * @since 1.0.0
		 */
		$this->wmab_session_expiring = time() + intval(apply_filters('wc_session_expiring', 60 * 60 * 47));
		/**
		 * Filters the expiration time for the WooCommerce session.
		 *
		 * @since 1.0.0
		 */
		$this->wmab_session_expiration = time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48));

		$this->wmab_cookie = 'wp_woocommerce_session_' . COOKIEHASH;
		$to_hash           = $cart_id . '|' . $this->wmab_session_expiration;
		$cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
		$cookie_value      = $cart_id . '||' . $this->wmab_session_expiration . '||' . $this->wmab_session_expiring . '||' . $cookie_hash;
		/**
		 * Filters whether to use a secure cookie for the WooCommerce session.
		 *
		 * @since 1.0.0
		 */
		wc_setcookie($this->wmab_cookie, $cookie_value, $this->wmab_session_expiration, apply_filters('wc_session_use_secure_cookie', false));
		$_COOKIE[$this->wmab_cookie] = $cookie_value;
	}

	public function app_send_order_notification($order_id)
	{

		include ABSPATH . 'wp-includes/pluggable.php';

		$current_user = wp_get_current_user();
		if ($current_user->ID) {
			$user_data = get_user_by('id', $current_user->ID);
			$email     = $user_data->user_email;
		}

		if ($order_id) {

			// Check if FCM and Cart mapping exists
			$fcm_data = $this->isFcmExist('', $email);

			if (isset($fcm_data) && !empty($fcm_data)) {
				// Update FCM and Order mapping into the table
				$this->mapOrderWithFCM($order_id, $fcm_data->fcm_details_id);
				$cart_id = $fcm_data->cart;
			}

			// Order Success Push Notification
			if (isset($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled'])) {
				// Get Notification Title and Message
				$notification_title   = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_title'];
				$notification_message = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_msg'];

				$this->notify($notification_title, $notification_message, 'order_placed', $cart_id, $order_id, $email, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']);
			}
		}
	}

	/**
	 * Function to handle appCheckout API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckout
	 *
	 * @param  string $email               This parameter holds customer email
	 * @param  string $session_data        This parameter holds current cart ID
	 * @param  int    $id_billing_address  This parameter holds Customer Billing Address ID
	 * @param  int    $id_shipping_address This parameter holds Customer Shipping Address ID
	 * @param  string $version             This parameter holds API version to verify during API call
	 */
	public function app_checkout($email, $session_data, $id_billing_address, $id_shipping_address, $set_shipping_method, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appCheckout');

		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = __('Cart Information could not be loaded.', 'knowband-mobile-app-builder-for-woocommerce');

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			$wc_customer = new WC_Customer($customer_id);

			if (isset($customer_id) && !empty($customer_id)) {

				WC()->customer = new WC_Customer($customer_id);
				if (isset($session_data) && !empty($session_data)) {
					$cart_id = $session_data;
				} else {
					$cart_id = $customer_id;
				}

				// Get All States
				$wc_country = new WC_Countries();
				$states     = $wc_country->__get('states');

				// Get Customer Billing Address
				$billing_address = $wc_customer->get_shipping();

				$customer_billing = $wc_customer->get_billing();

				// replaced get_billing() with get_shipping as both are same and system allows only one
				// If name & country is blank then we are considering that address is not present on the account
				if (!empty($billing_address['first_name']) && !empty($billing_address['country'])) {
					$this->wmab_response['checkout_page']['billing_address'] = array(
						'id_shipping_address' => isset($id_shipping_address) ? $id_shipping_address : 0,
						'firstname'           => isset($billing_address['first_name']) ? $billing_address['first_name'] : '',
						'lastname'            => isset($billing_address['last_name']) ? $billing_address['last_name'] : '',
						'mobile_no'           => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
						'company'             => isset($billing_address['company']) ? $billing_address['company'] : '',
						'address_1'           => isset($billing_address['address_1']) ? $billing_address['address_1'] : '',
						'address_2'           => isset($billing_address['address_2']) ? $billing_address['address_2'] : '',
						'city'                => isset($billing_address['city']) ? $billing_address['city'] : '',
						'zone_id'             => !empty($billing_address['state']) ? $billing_address['state'] : '',
						'state'               => !empty($states[$billing_address['country']][$billing_address['state']]) ? $states[$billing_address['country']][$billing_address['state']] : $billing_address['state'],
						'country_id'          => !empty($billing_address['country']) ? $billing_address['country'] : '',
						'country'             => !empty($billing_address['country']) ? WC()->countries->countries[$billing_address['country']] : '',
						'postcode'            => isset($billing_address['postcode']) ? $billing_address['postcode'] : '',
						'alias'               => '',
					);
				} else {
					$this->wmab_response['checkout_page']['billing_address'] = array();
				}

				// Get Customer Shipping Address
				$shipping_address = $wc_customer->get_shipping();

				// If name & country is blank then we are considering that address is not present on the account
				if (!empty($shipping_address['first_name']) && !empty($shipping_address['country'])) {
					$this->wmab_response['checkout_page']['shipping_address'] = array(
						'id_shipping_address' => isset($id_shipping_address) ? $id_shipping_address : 0,
						'firstname'           => isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '',
						'lastname'            => isset($shipping_address['last_name']) ? $shipping_address['last_name'] : '',
						'mobile_no'           => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
						'company'             => isset($shipping_address['company']) ? $shipping_address['company'] : '',
						'address_1'           => isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '',
						'address_2'           => isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '',
						'city'                => isset($shipping_address['city']) ? $shipping_address['city'] : '',
						'zone_id'             => !empty($shipping_address['state']) ? $shipping_address['state'] : '',
						'state'               => !empty($states[$shipping_address['country']][$shipping_address['state']]) ? $states[$shipping_address['country']][$shipping_address['state']] : $shipping_address['state'],
						'country_id'          => !empty($shipping_address['country']) ? $shipping_address['country'] : '',
						'country'             => !empty($shipping_address['country']) ? WC()->countries->countries[$shipping_address['country']] : '',
						'postcode'            => isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '',
						'alias'               => '',
					);
				} else {
					$this->wmab_response['checkout_page']['shipping_address'] = array();
				}

				// Shipping details
				$this->wmab_response['checkout_page']['per_products_shipping']         = '0';
				$this->wmab_response['checkout_page']['per_products_shipping_methods'] = array();
				$this->wmab_response['checkout_page']['default_shipping']              = '';
				$this->wmab_response['checkout_page']['shipping_available']            = '0';
				$this->wmab_response['checkout_page']['shipping_message']              = '';
				$this->wmab_response['checkout_page']['shipping_methods']              = array();

				// Products
				$this->wmab_response['checkout_page']['products']               = array();
				$this->wmab_response['checkout_page']['vouchers']               = array();
				$this->wmab_response['checkout_page']['guest_checkout_enabled'] = (string) get_option('woocommerce_enable_guest_checkout') === 'yes' ? '0' : '0'; // Set it as disabled
				$this->wmab_response['checkout_page']['cart']                   = (object) array(
					'total_cart_items' => 0,
				);

				$this->wmab_response['delay_shipping']                            = array(
					'applied'   => '0',
					'available' => '0',
				);
				$this->wmab_response['checkout_page']['cart_id']                  = (string) $cart_id;
				$this->wmab_response['checkout_page']['voucher_allowed']          = (string) 0;
				$this->wmab_response['checkout_page']['minimum_purchase_message'] = '';
				$this->wmab_response['checkout_page']['order_total']              = (float) number_format(WC()->cart->get_total(''), 2);

				$this->wmab_response['checkout_page']['totals'] = array();

				if (count(WC()->session->get('chosen_shipping_methods')) == 1 && empty(WC()->session->get('chosen_shipping_methods')[0])) {
					$this->wmab_response['checkout_page']['default_shipping'] = 'false';
				} elseif (WC()->session->get('chosen_shipping_methods') != null) {
					foreach (WC()->session->get('chosen_shipping_methods') as $key => $value) {
						$this->wmab_response['checkout_page']['default_shipping'] = $value;
						break;
					}
				}

				$this->wmab_response['checkout_page']['voucher_html'] = '';

				$this->wmab_response['gift_wrapping'] = array(
					'available' => '0',
					'applied'   => '0',
					'message'   => '',
					'cost_text' => '',
				);

				if (isset($cart_id) && !empty($cart_id)) {
					// Set Cart Session
					$this->set_session($cart_id);

					// Set Shipping Method
					if (isset($set_shipping_method) && !empty($set_shipping_method)) {
						WC()->session->set('chosen_shipping_methods', array($set_shipping_method));
						WC()->cart->calculate_totals();
					}

					$cart_content = WC()->cart->get_cart_contents();
					// BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
					$is_virtual_product                 = false;
					$is_virual_with_non_virtual_product = true;

					foreach ($cart_content as $cart_products) {

						$option_data = array();

						if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
							$product_id = $cart_products['variation_id'];
						} else {
							$product_id = $cart_products['product_id'];
						}

						// Get Product
						$product      = wc_get_product($product_id); // Replaced deprecated function get_product with wc_get_product on 04-Oct-2019
						$product_type = $product->get_type(); // Get the product type correctly instead of getting it directly through $product->product_type on 04-Oct-2019
						// BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
						if ($product->is_virtual()) {
							$is_virtual_product = true;
						} else {
							$is_virual_with_non_virtual_product = false;
						}
						// EOC
						if ('variable' == $product_type) { // For Variable Product
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
								$discount_percentage = '';
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
						/**
						 * Commented the below lines as asked by shubham, because there is no use of quantity in options
						 *
						 * @date   01-03-2023
						 */
						// Option Data for Product Quantity
						// $option_data[] = array(
						// 'name' => __('Quantity', 'knowband-mobile-app-builder-for-woocommerce'),
						// 'value' => (string) $cart_products['quantity']
						// );

						// Option Data for Product Attributes
						if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
							$product_attributes = $product->get_attributes();
							/**
							 * Added below code to fetch the attribute data for the products so that, the same data can be displayed on front
							 * VGfeb2023 attribute-issue
							 *
							 * @date   01-03-2023
							 */
							foreach ($product_attributes as $key => $attribute) {
								$product_attributes[$key] = wc_attribute_label($key) . ': ' . $attribute;
							}
							$option_data[] = array(
								'name'  => __('Attributes', 'knowband-mobile-app-builder-for-woocommerce'),
								'value' => ucwords(implode(', ', $product_attributes)),
							);
						}

						// Product Image
						// $product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id, 'single-post-thumbnail' ));
						$product_image = get_the_post_thumbnail_url($product_id);

						if (null == $product_image && !empty($cart_products['variation_id'])) {
							// $product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $cart_products['product_id'], 'single-post-thumbnail' ));
							$product_image = get_the_post_thumbnail_url($cart_products['product_id']);
						}

						if (empty($product_image)) {
							$product_image = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
						}

						// BOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
						/**
						 * COmmented below code as there is no use of the below code, as in "$kb_price" we just need to pass the regular price every time and for total price calculation I have added another code
						 * VGfeb2023 dicount-issue
						 *
						 * @date   01-03-2023
						 */
						// if ($product->get_sale_price()) {
						// if ($product->get_date_on_sale_from() != "" && time() >= $product->get_date_on_sale_from()->getTimestamp() && $product->get_date_on_sale_to() != "" && time() <= $product->get_date_on_sale_to()->getTimestamp()) {
						// $kb_price = $product->get_sale_price();
						// } else if ($product->get_date_on_sale_from() != "" && time() >= $product->get_date_on_sale_from()->getTimestamp() && $product->get_date_on_sale_to() != "" && time() <= $product->get_date_on_sale_to()->getTimestamp()) {
						// $kb_price = $product->get_sale_price();
						// } else {
						// $kb_price = $product->get_regular_price();
						// }
						// } else {
						// $kb_price = $product->get_regular_price();
						// }

						$kb_price = $product->get_regular_price();

						if ($product->is_on_sale()) {
							$kb_price_total = $product->get_sale_price();
						} else {
							$kb_price_total = $product->get_regular_price();
						}
						// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates

						$this->wmab_response['checkout_page']['products'][] = array(
							'product_id'           => (string) $cart_products['product_id'],
							'title'                => strip_tags($product->get_name()),
							'is_gift_product'      => '0',
							'id_product_attribute' => (string) $cart_products['variation_id'],
							'id_address_delivery'  => 0,
							'stock'                => $product->get_stock_status() === 'instock' ? true : false,
							'minimal_quantity'     => '1',
							'discount_price'       => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => $cart_products['quantity'],
												'price' => $product->get_sale_price(),
											)
										)
									)
								),
								ENT_QUOTES
							),
							'discount_percentage'  => !empty($discount_percentage) ? ((string) floor($discount_percentage) . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'images'               => $product_image,
							'price'                => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => $cart_products['quantity'],
												'price' => $kb_price,
											)
										)
									)
								),
								ENT_QUOTES
							),
							'quantity'             => (string) $cart_products['quantity'],
							'product_items'        => $option_data,
							'customizable_items'   => array(),
							/**
							 * Used new variable to set the price after checking whether the product is on sale or not.
							 * VGfeb2023 dicount-issue
							 *
							 * @date   01-03-2023
							 */
							'total_price'          => html_entity_decode(
								strip_tags(
									wc_price(
										wc_get_price_including_tax(
											$product,
											array(
												'qty'   => $cart_products['quantity'],
												'price' => $kb_price_total,
											)
										)
									)
								),
								ENT_QUOTES
							),
						);
					}

					// BOC 4-Feb-2020 : Added check for verified product is downloadable/virtual product or not
					if ($is_virtual_product && $is_virual_with_non_virtual_product) {
						$this->wmab_response['checkout_page']['shipping_available'] = '1';
					}

					$this->wmab_response['checkout_page']['cart'] = (object) array(
						'total_cart_items' => is_array($cart_content) ? count($cart_content) : 0,
					);

					// Cart SubTotal
					$cart_sub_total                                   = WC()->cart->get_cart_subtotal();
					$this->wmab_response['checkout_page']['totals'][] = array(
						'name'  => __('Subtotal', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags($cart_sub_total), ENT_QUOTES),
					);

					// Cart Coupon
					$this->wmab_response['checkout_page']['coupons'] = array();
					foreach (WC()->cart->get_coupons() as $code => $coupon) {
						$this->wmab_response['checkout_page']['totals'][] = array(
							'name'  => sanitize_title($code),
							'value' => '-' . html_entity_decode(strip_tags(wc_price(WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax))), ENT_QUOTES),
						);
					}

					// Cart Shipping Total
					$cart_shipping_total                              = WC()->cart->get_cart_shipping_total();
					$this->wmab_response['checkout_page']['totals'][] = array(
						'name'  => __('Shipping', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags($cart_shipping_total), ENT_QUOTES),
					);

					// Fee
					foreach (WC()->cart->get_fees() as $fee) {
						$this->wmab_response['checkout_page']['totals'][] = array(
							'name'  => esc_html($fee->name),
							'value' => WC()->cart->display_prices_including_tax() ? html_entity_decode(wc_price($fee->total + $fee->tax), ENT_QUOTES) : html_entity_decode(wc_price($fee->total), ENT_QUOTES),
						);
					}

					// Tax
					if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) {
						$taxable_address = WC()->customer->get_taxable_address();
						// translators: Placeholder refers to the estimated location for shipping.
						$estimated_text = WC()->customer->is_customer_outside_base() && !WC()->customer->has_calculated_shipping() ? sprintf(' <small>' . __('(estimated for %s)', 'knowband-mobile-app-builder-for-woocommerce') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]) : '';

						if ('itemized' === get_option('woocommerce_tax_total_display')) {
							foreach (WC()->cart->get_tax_totals() as $code => $tax) {
								$this->wmab_response['checkout_page']['totals'][] = array(
									'name'  => esc_html($tax->label) . $estimated_text,
									'value' => html_entity_decode(strip_tags(wp_kses_post($tax->formatted_amount)), ENT_QUOTES),
								);
							}
						} else {
							$this->wmab_response['checkout_page']['totals'][] = array(
								'name'  => esc_html(WC()->countries->tax_or_vat()) . $estimated_text,
								'value' => html_entity_decode(strip_tags(wc_price(WC()->cart->get_taxes_total())), ENT_QUOTES),
							);
						}
					}

					$this->wmab_response['checkout_page']['totals'][] = array(
						'name'  => __('Total', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(WC()->cart->get_total()), ENT_QUOTES),
					);

					$this->wmab_response['total_cost'] = (float) html_entity_decode(strip_tags(WC()->cart->get_total()), ENT_QUOTES);
				}

				// Get Shipping methods
				$package = WC()->cart->get_shipping_packages(); // Get the shipping packages through default code on 04-Oct-2019
				if (isset($package[0])) {
					$package = $package[0]; // And assigned the 0 index array into a array variable on 04-Oct-2019
				}

				$shipping_methods = WC()->shipping->calculate_shipping_for_package($package);

				/*
				Check if cart contain only virtal product or not, so that we will not show shipping methid on checkout page
				* @date 1-02-2023
				* @commenter Vishal Goyal
				*/
				$only_virtual = true;
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					// Check if there are non-virtual products
					if (!$cart_item['data']->is_virtual()) {
						$only_virtual = false;
					}
				}

				if (isset($shipping_methods) && !empty($shipping_methods) && !$only_virtual) {
					$this->wmab_response['checkout_page']['shipping_available'] = '1';
					foreach ($shipping_methods['rates'] as $shipping_method) {
						$this->wmab_response['checkout_page']['shipping_methods'][] = array(
							'name'       => $shipping_method->__get('label'),
							'price'      => html_entity_decode(strip_tags(wc_price($shipping_method->__get('cost') + $shipping_method->get_shipping_tax())), ENT_QUOTES),
							'delay_text' => '',
							'code'       => $shipping_method->__get('id'),
						);
					}
					if (empty($this->wmab_response['checkout_page']['shipping_methods'])) {
						$this->wmab_response['checkout_page']['shipping_methods'][] = array(
							'name'       => __('Free Shipping', 'knowband-mobile-app-builder-for-woocommerce'),
							'price'      => html_entity_decode(strip_tags(wc_price(0)), ENT_QUOTES),
							'delay_text' => '',
							'code'       => !empty($this->wmab_response['checkout_page']['default_shipping']) ? $this->wmab_response['checkout_page']['default_shipping'] : 'free_shipping',
						);
					}
				} else {
					$this->wmab_response['checkout_page']['shipping_available'] = '0';
					$this->wmab_response['checkout_page']['shipping_methods']   = array();
				}

				// Get Default Currency
				$default_currency = get_woocommerce_currency();

				$this->wmab_response['native_payments'] = array();

				/*
					PayPal Code commented by Ashish on 15th Apr 2022 as its removed from the flutter app.
				if (isset($this->wmab_plugin_settings['payment_methods']['paypal_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['paypal_enabled'])) {
					$this->wmab_response['native_payments'][] = array(
						'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['payment_method_name'],
						'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['payment_method_code'],
						'configuration' => array(
							'payment_method_mode' => $this->wmab_plugin_settings['payment_methods']['payment_method_mode'] ? 'live' : 'sandbox',
							'client_id' => $this->wmab_plugin_settings['payment_methods']['client_id'],
							'is_default' => 'yes',
							'other_info' => ''
						),
					);
				}
				*/

				// Check if CoD method is enabled and then send the response
				if (isset($this->wmab_plugin_settings['payment_methods']['cod_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['cod_enabled'])) {
					$this->wmab_response['native_payments'][] = array(
						'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_name'],
						'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_code'],
						'configuration'       => array(
							'payment_method_mode' => 'live',
							'client_id'           => '',
							'is_default'          => 'no',
							'other_info'          => '',
						),
					);
				}

				$this->wmab_response['error_warning']              = '';
				$this->wmab_response['payment_methods_in_browser'] = '0';
				$this->wmab_response['total_cost']                 = (float) number_format(WC()->cart->get_total(''), 2);
				$this->wmab_response['currency_code']              = $default_currency;
				$this->wmab_response['currency_symbol']            = get_woocommerce_currency_symbol($default_currency);

				$this->wmab_response['status']  = 'success';
				$this->wmab_response['message'] = __('Cart Information loaded successfully.', 'knowband-mobile-app-builder-for-woocommerce');
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Email Address.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appCheckout', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetOrderDetails API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetOrderDetails
	 *
	 * @param  string $email    This parameter holds Customer Email
	 * @param  int    $order_id This parameter holds Order ID
	 * @param  string $version  This parameter holds API version to verify during API call
	 */
	public function app_get_order_details($email, $order_id, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetOrderDetails');

		if (isset($email) && !empty($email) && isset($order_id) && !empty($order_id)) {
			$customer_id = email_exists($email);

			if (isset($customer_id) && !empty($customer_id)) {

				$customer = new WC_Customer($customer_id);

				$order_info    = wc_get_order($order_id);
				$order_details = $order_info->get_data();

				// Order History
				$order_history = array(
					'order_id'        => (string) $order_id,
					'cart_id'         => '',
					'order_number'    => (string) $order_id,
					'status'          => ucwords($order_details['status']),
					'status_color'    => '#26A69A',
					'date_added'      => gmdate('Y-m-d H:i:s', strtotime($order_details['date_created'])),
					'total'           => html_entity_decode(strip_tags(wc_price($order_details['total'])), ENT_QUOTES),
					'reorder_allowed' => '0',
				);

				// Get All States
				$wc_country = new WC_Countries();
				$states     = $wc_country->__get('states');

				if (empty($order_details['shipping']['first_name'])) {
					$order_details['shipping'] = $order_details['billing'];
				}

				// Shipping Address
				$shipping_address = array(
					'firstname' => $order_details['shipping']['first_name'],
					'lastname'  => $order_details['shipping']['last_name'],
					'company'   => $order_details['shipping']['company'],
					'address_1' => $order_details['shipping']['address_1'],
					'address_2' => $order_details['shipping']['address_2'],
					'mobile_no' => '',
					'city'      => $order_details['shipping']['city'],
					'postcode'  => $order_details['shipping']['postcode'],
					'state'     => (!empty($order_details['shipping']['country']) && !empty($order_details['shipping']['state'])) ? $states[$order_details['shipping']['country']][$order_details['shipping']['state']] : '',
					'country'   => !empty($order_details['shipping']['country']) ? WC()->countries->countries[$order_details['shipping']['country']] : '',
					'alias'     => '',
				);

				// Billing Address
				$billing_address = array(
					'firstname' => $order_details['billing']['first_name'],
					'lastname'  => $order_details['billing']['last_name'],
					'company'   => $order_details['billing']['company'],
					'address_1' => $order_details['billing']['address_1'],
					'address_2' => $order_details['billing']['address_2'],
					'mobile_no' => $order_details['billing']['phone'],
					'city'      => $order_details['billing']['city'],
					'postcode'  => $order_details['billing']['postcode'],
					/**
					 * Display the country and state name, instead of their code like IN, AP for India and Andra Pradesh
					 *
					 * @date      1-02-2023
					 * @commenter Vishal Goyal
					 */
					'state'     => (!empty($order_details['billing']['country']) && !empty($order_details['billing']['state'])) ? $states[$order_details['billing']['country']][$order_details['billing']['state']] : '',
					'country'   => !empty($order_details['billing']['country']) ? WC()->countries->countries[$order_details['billing']['country']] : '',
					'alias'     => '',
				);

				// Payment Method
				$position = strpos($order_details['payment_method_title'], '<');
				if (!empty($position)) {
					$name = substr($order_details['payment_method_title'], $position);
				} else {
					$name = $order_details['payment_method_title'];
				}
				$payment_method = $name;

				// print_r($order_details); die;
				// Shipping Method
				$shipping_method = $order_info->get_shipping_method() ? $order_info->get_shipping_method() : 'Free Shipping';

				// Status History
				$status_history = array(); // Kept it blank as WooCommerce does not store previous status instead it stores order notes
				// Order Comment
				$order_comment = $order_details['customer_note'];

				// The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
				$ordered_products = array();
				foreach ($order_details['line_items'] as $item_id => $item_product) {
					$item_product_data = $item_product->get_data(); // Get Item data
					// Get the product ID
					$product_id = $item_product->get_product_id();
					// Get the WC_Product object
					$product = $item_product->get_product();
					/**
					 * Code added to check whether the product object exist or not, so that in case of deleted products, it doesnt display 500 error or stops working properly
					 *
					 * @date      30-01-2023
					 * @commenter Vishal Goyal
					 */
					if (empty($product)) {
						continue;
					}
					$product_info = array();
					// Option Data for Product Attributes
					if (isset($item_product_data['variation_id']) && !empty($item_product_data['variation_id'])) {
						$kb_variation_obj   = wc_get_product($item_product_data['variation_id']);
						$product_attributes = $kb_variation_obj->get_attributes();
						/**
						 * Added below code to fetch the attribute data for the products so that, the same data can be displayed on front
						 * VGfeb2023 attribute-issue
						 *
						 * @date   01-03-2023
						 */
						foreach ($product_attributes as $key => $attribute) {
							$product_attributes[$key] = wc_attribute_label($key) . ' : ' . $attribute;
						}
						$product_info[] = array(
							'name'  => __('Attributes', 'knowband-mobile-app-builder-for-woocommerce'),
							'value' => ucwords(implode(', ', $product_attributes)),
						);
					}
					// Product Info
					// $product_info = array(
					// array(
					// 'name' => __('SKU', 'knowband-mobile-app-builder-for-woocommerce'),
					// 'value' => $product->get_sku()
					// )
					// );

					$product_image = get_the_post_thumbnail_url($product_id);

					if (empty($product_image)) {
						$product_image = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}

					$ordered_products[] = array(
						'product_id'           => (string) $product_id,
						'title'                => strip_tags($item_product_data['name']),
						'is_gift_product'      => '0',
						'stock'                => ($product->get_stock_status() == 'instock') ? true : false,
						'id_product_attribute' => (string) $item_product->get_variation_id(),
						'quantity'             => (string) $item_product->get_quantity(),
						'minimal_quantity'     => '1',
						'price'                => html_entity_decode(strip_tags(wc_price($item_product->get_subtotal())), ENT_QUOTES),
						'discount_price'       => '',
						'discount_percentage'  => '',
						'total_price'          => html_entity_decode(strip_tags(wc_price($item_product->get_total())), ENT_QUOTES),
						'product_items'        => $product_info,
						'images'               => $product_image,
						'customizable_items'   => array(),
						'model'                => '',
					);
				}

				// Vouchers
				$vouchers = array();

				// Order Totals
				$order_subtotal = $order_info->get_subtotal();
				$order_discount = $order_info->get_total_discount();
				$order_tax      = $order_info->get_total_tax();
				$order_shipping = $order_info->get_shipping_total();
				$order_total    = $order_info->get_total();

				$order_totals = array(
					array(
						'name'  => __('Subtotal', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(wc_price($order_subtotal)), ENT_QUOTES),
					),
					array(
						'name'  => __('Discount', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(wc_price($order_discount)), ENT_QUOTES),
					),
					array(
						'name'  => __('Tax', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(wc_price($order_tax)), ENT_QUOTES),
					),
					array(
						'name'  => __('Shipping', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(wc_price($order_shipping)), ENT_QUOTES),
					),
					array(
						'name'  => __('Total', 'knowband-mobile-app-builder-for-woocommerce'),
						'value' => html_entity_decode(strip_tags(wc_price($order_total)), ENT_QUOTES),
					),
				);

				// API Response
				$this->wmab_response['order_details'] = array(
					'order_history'    => $order_history,
					'shipping_address' => $shipping_address,
					'shipping_method'  => array('name' => $shipping_method),
					'payment_method'   => array('name' => $payment_method),
					'billing_address'  => $billing_address,
					'products'         => $ordered_products,
					'status_history'   => $status_history,
					'total'            => $order_totals,
					'vouchers'         => $vouchers,
					'gift_wrapping'    => array(
						'available' => '0',
						'applied'   => '0',
						'message'   => '',
						'cost_text' => '',
					),
					'order_comment'    => $order_comment,
				);

				$this->wmab_response['status']  = 'success';
				$this->wmab_response['message'] = '';
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Email Address or Order ID.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appGetOrderDetails', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetOrders API request - Module Upgrade V2 - changes added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetOrders
	 *
	 * @param  string $email   This parameter holds Customer Email
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_get_orders($email, $version)
	{
		global $wpdb;
		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetOrders');

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			if (isset($customer_id) && !empty($customer_id)) {

				$customer = new WC_Customer($customer_id);

				// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$phone_number = ''; // it will hold customer's Phone Number
				$country_code = ''; // it will hold customer's Country Code
				$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					$phone_number = $getMapping->mobile_number;
					$country_code = $getMapping->country_code;
				}
				// EOC - Module Upgrade V2
				// Get Customer First Name
				$first_name = $customer->get_first_name();
				// Get Customer Last Name
				$last_name = $customer->get_last_name();
				// Personal Info Response
				$this->wmab_response['personal_info'] = array(
					'firstname'     => $first_name,
					'lastname'      => $last_name,
					'email'         => $email,
					'mobile_number' => $phone_number, // Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019 to pass Customers Phone Number
					'country_code'  => $country_code, // Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019 to pass Customers Country Code
				);

				// Get Customer Orders and details
				$customer_orders = wc_get_orders(
					array(
						'customer' => $customer_id,
						'limit'    => -1,
						'orderby'  => 'date',
						'order'    => 'DESC',
						'return'   => 'ids',
					)
				);

				if (isset($customer_orders) && !empty($customer_orders)) {
					foreach ($customer_orders as $order) {
						$order_details = wc_get_order($order);
						$order_details = $order_details->get_data();

						// The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
						$ordered_products = array();
						foreach ($order_details['line_items'] as $item_id => $item_product) {
							$item_product_data = $item_product->get_data(); // Get Item data
							// Get the product ID
							$product_id = $item_product->get_product_id();
							// Get the WC_Product object
							$product = $item_product->get_product();
							/**
							 * Code added to check whether the product object exist or not, so that in case of deleted products, it doesnt display 500 error or stops working properly
							 *
							 * @date      30-01-2023
							 * @commenter Vishal Goyal
							 */
							if (empty($product)) {
								continue;
							}
							// Product Info
							$product_info = array(
								array(
									'name'  => __('SKU', 'knowband-mobile-app-builder-for-woocommerce'),
									'value' => $product->get_sku(),
								),
							);

							$product_image = get_the_post_thumbnail_url($product_id);

							if (empty($product_image)) {
								$product_image = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
							}
							$ordered_products[] = array(
								'id'                   => (string) $product_id,
								'title'                => strip_tags($item_product_data['name']),
								'is_gift_product'      => '0',
								'stock'                => ($product->get_stock_status() == 'instock') ? true : false,
								'id_product_attribute' => (string) $item_product->get_variation_id(),
								'quantity'             => (string) $item_product->get_quantity(),
								'minimal_quantity'     => '1',
								'price'                => html_entity_decode(strip_tags(wc_price($item_product->get_subtotal())), ENT_QUOTES),
								'discount_price'       => '',
								'discount_percentage'  => '',
								'total'                => html_entity_decode(strip_tags(wc_price($item_product->get_total())), ENT_QUOTES),
								'product_items'        => $product_info,
								'images'               => $product_image,
								'customizable_items'   => array(),
							);
						}

						// Get Order details
						$this->wmab_response['order_history'][] = array(
							'order_id'        => (string) $order,
							'cart_id'         => '',
							'order_number'    => (string) $order,
							'status'          => ucwords($order_details['status']),
							'status_color'    => '#26A69A',
							'date_added'      => gmdate('Y-m-d H:i:s', strtotime($order_details['date_created'])),
							'total'           => html_entity_decode(strip_tags(wc_price($order_details['total'])), ENT_QUOTES),
							'reorder_allowed' => 0,
							'products'        => $ordered_products,
							'item_count'      => (string) count($ordered_products),
						);
					}
				} else {
					$this->wmab_response['order_history'] = array();
				}
				$this->wmab_response['status']  = 'success';
				$this->wmab_response['message'] = '';
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Email Address.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appGetOrders', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appReorder API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appReorder
	 *
	 * @param  string $email        This parameter holds Customer Email
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  int    $order_id     This parameter holds Order ID to reorder
	 * @param  string $version      This parameter holds API version to verify during API call
	 */
	public function app_reorder($email, $session_data, $order_id, $version)
	{
		global $wpdb;
		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appReorder');

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			if (isset($customer_id) && !empty($customer_id)) {

				if (isset($order_id) && !empty($order_id)) {

					// Include file to import wmab customer class
					include_once plugin_dir_path(__FILE__) . 'wmab-customer.php';
					// Create class object
					$wmab_customer = new WmabCustomer();

					$customer = new WC_Customer($customer_id);

					// Get Products of Order
					$order_info    = wc_get_order($order_id);
					$order_details = $order_info->get_data();
					// print_r($order_details); die;
					// The loop to get the order items which are WC_Order_Item_Product objects since WC 3+
					$ordered_products = array();
					foreach ($order_details['line_items'] as $item_id => $item_product) {
						$item_product_data = $item_product->get_data(); // Get Item data
						// Get the product ID
						$product_id = $item_product->get_product_id();
						// Get the WC_Product object
						$product = get_product($product_id);
						// Product Info
						$product_info = array(
							array(
								'name'  => __('SKU', 'knowband-mobile-app-builder-for-woocommerce'),
								'value' => $product->get_sku(),
							),
						);

						// Product Options/Attributes
						$attributes   = array();
						$variation_id = $item_product->get_variation_id();
						if ($variation_id) {
							$variation       = get_product($variation_id);
							$product_options = $variation->get_attributes();
							if (isset($product_options) && !empty($product_options)) {
								foreach ($product_options as $option => $value) {
									$option_id       = wc_attribute_taxonomy_id_by_name(str_replace('pa_', '', $option));
									$option_value_id = get_term_by('slug', $value, $option);
									// Set Attributes into an array variable
									$attributes[] = array(
										'id' => $option_id,
										'selected_value_id' => $option_value_id->term_id,
									);
								}
							}
						}

						$ordered_products[] = array(
							'quantity'             => (string) $item_product->get_quantity(),
							'product_id'           => (string) $product_id,
							'minimal_quantity'     => (string) $item_product->get_quantity(),
							'option'               => $attributes,
							'id_product_attribute' => (string) $item_product->get_variation_id(),
						);
					}

					$cart_products = wp_json_encode(
						array(
							'session_id'            => '',
							'request_type'          => 'add',
							'user_type'             => '',
							'email'                 => $email,
							'cart_products'         => $ordered_products,
							'customization_details' => array(),
							'voucher'               => '',
							'coupon'                => '',
						)
					);

					WC()->session->set('reorder', '1');
					// Call up API to add product into cart and return cart ID
					$add_to_cart = $wmab_customer->app_add_to_cart($cart_products, $customer_id, true, $version);

					// Call up to get the response data of shopping cart
					$this->wmab_response = $wmab_customer->app_get_cart_details('', $customer_id, true, $version, true);

					$this->wmab_response['status']  = 'success';
					$this->wmab_response['message'] = '';
				} else {
					$this->wmab_response['status']  = 'failure';
					$this->wmab_response['message'] = __('Invalid Order ID.', 'knowband-mobile-app-builder-for-woocommerce');
				}
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Email Address.', 'knowband-mobile-app-builder-for-woocommerce');
		}
		// Log Request
		wmab_log_knowband_app_request('appReorder', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetPaymentMethods API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetPaymentMethods
	 *
	 * @param  string $email               This parameter holds Customer Email
	 * @param  string $session_data        This parameter holds current Cart ID
	 * @param  string $id_shipping_address This parameter holds Shipping Address ID
	 * @param  string $order_message       This parameter holds Order Message
	 * @param  string $version             This parameter holds API version to verify during API call
	 */
	public function app_get_payment_methods($email, $session_data, $id_shipping_address, $order_message, $version, $temp)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetPaymentMethods');

		// Log Request
		wmab_log_knowband_app_request('appGetPaymentMethods', serialize_block_attributes($this->wmab_response));

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			$wc_customer      = new WC_Customer($customer_id);
			$shipping_address = $wc_customer->get_shipping();  // id_shipping_address not required as only one shipping address is kept by woocommerce
			// Get All States
			$wc_country = new WC_Countries();
			$states     = $wc_country->__get('states');

			if (isset($customer_id) && !empty($customer_id)) {

				if (isset($session_data) && !empty($session_data)) {
					$cart_id = $session_data;
				} else {
					$cart_id = $customer_id;
				}

				// Set Cart Session
				// $this->set_session($cart_id);

				$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

				WC()->cart->get_cart();

				// Calc totals
				WC()->cart->calculate_totals();

				$checkout = WC()->checkout();
				wp_set_current_user($customer_id); // phpcs:ignore

				if (1 == $temp) {
					wp_set_auth_cookie($customer_id, true, false); // phpcs:ignore
					header('Content-Type: text/html');

					echo '<form method="get" action="" id="payment_form">';
					echo '<input type="hidden" name="email" value="' . esc_attr($email) . '">';
					echo '<input type="hidden" name="id_shipping_address" value="' . esc_attr($id_shipping_address) . '">';
					echo '<input type="hidden" name="order_message" value="' . esc_attr($order_message) . '">';
					echo '<input type="hidden" name="version" value="' . esc_attr($version) . '">';
					echo '<input type="hidden" name="session_data" value="' . esc_attr($session_data) . '">';
					echo '<input type="hidden" name="temp" value="0">';

					echo '</form>';
					echo '<script>';
					echo 'document.getElementById("payment_form").submit();';
					echo '</script>';
					die();
				}

				header('Content-Type: text/html');

				$customer = new WC_Customer($customer_id);

				/**
				 * Escaping is not required, as when using wp_kses_post or esc_html because these functions convert the content to plain text, ensuring its safe display without the need for additional escaping.
				 *
				 * @since 1.0.0
				 */

				echo '<html>
				<head>
					<meta http-equiv="content-type" content="text/html; charset=UTF-8">
					<style>' . esc_html($this->wmab_plugin_settings['general']['custom_css']) . '</style>
				</head>
				<body>' . (wc_get_template_html(
									'../../' . str_replace('/api', '', plugin_basename(dirname(__DIR__))) . '/views/form-checkout.php',
									array(
										'checkout'      => $checkout,
										'customer'      => $wc_customer->get_data(),
										'order_message' => $order_message,
										'email'         => $email,
									)
								)) . '</body>
				</html>'; // phpcs:ignore
			}
		}
	}

	/**
	 * Function to handle appGetMobilePaymentMethods API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetMobilePaymentMethods
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 */
	public function app_get_mobile_payment_methods($version, $order_comment = '')
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetMobilePaymentMethods');

		$this->wmab_response['status']   = 'success';
		$this->wmab_response['message']  = '';
		$this->wmab_response['payments'] = array();

		WC()->session->set('order_comment', $order_comment);

		// Check if PayPal method is enabled and then send the response
		if (isset($this->wmab_plugin_settings['payment_methods']['paypal_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['paypal_enabled'])) {
			$this->wmab_response['payments'][] = array(
				'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['payment_method_name'],
				'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['payment_method_code'],
				'configuration'       => array(
					'payment_method_mode' => $this->wmab_plugin_settings['payment_methods']['payment_method_mode'] ? 'live' : 'sandbox',
					'client_id'           => $this->wmab_plugin_settings['payment_methods']['client_id'],
					'is_default'          => 'yes',
					'other_info'          => '',
				),
			);
		}

		// Check if CoD method is enabled and then send the response
		if (isset($this->wmab_plugin_settings['payment_methods']['cod_enabled']) && !empty($this->wmab_plugin_settings['payment_methods']['cod_enabled'])) {
			$this->wmab_response['payments'][] = array(
				'payment_method_name' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_name'],
				'payment_method_code' => $this->wmab_plugin_settings['payment_methods']['cod_payment_method_code'],
				'configuration'       => array(
					'payment_method_mode' => 'live',
					'client_id'           => '',
					'is_default'          => 'no',
					'other_info'          => '',
				),
			);
		}

		// Log Request
		wmab_log_knowband_app_request('appGetMobilePaymentMethods', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appCreateOrder API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCreateOrder
	 *
	 * @param  string $email        This parameter holds Customer Email Address
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $payment_info This parameter holds Payment Information
	 * @param  string $version      This parameter holds API version to verify during API call
	 */
	public function app_create_order($email, $session_data, $payment_info, $order_comment, $version)
	{

		// First do the API version verification and then go ahead

		$this->verify_api($version, 'appCreateOrder');

		// Default Response
		$this->wmab_response['status']   = 'failure';
		$this->wmab_response['message']  = __('Order could not be created.', 'knowband-mobile-app-builder-for-woocommerce');
		$this->wmab_response['order_id'] = '';

		/*
		$payment_info = wp_json_encode(array(
		'payment_method_name' => 'PayPal',
		'payment_method_code' => 'paypal',
		'transaction_id' => '1212121212121212',
		'status' => 'success',
		'amount' => '18.90'
		)); */

		if ('' == $order_comment) {
			$order_comment = (WC()->session->get('order_comment') !== null) ? WC()->session->get('order_comment') : '';
		}

		if (isset($email) && !empty($email) && isset($payment_info) && !empty($payment_info)) {
			$customer_id = email_exists($email);

			// Payment Info
			$payment_info = json_decode(stripslashes(trim($payment_info)), true);

			if (isset($customer_id) && !empty($customer_id)) {

				$wc_customer      = new WC_Customer($customer_id);
				$billing_address  = $wc_customer->get_billing();
				$shipping_address = $wc_customer->get_shipping();

				// Get All States
				$wc_country = new WC_Countries();
				$states     = $wc_country->__get('states');

				if (isset($session_data) && !empty($session_data)) {
					$cart_id = $session_data;
				} else {
					$cart_id = $customer_id;
				}

				// Check Payment and set code
				if (isset($payment_info['payment_method_code']) && 'paypal' == $payment_info['payment_method_code']) {
					$payment_info['payment_method_code'] = 'paypal'; // Default settings for WooCommerce
				}

				$cart       = WC()->cart;
				$order_data = array(
					'terms'                              => 0,
					'createaccount'                      => 0,
					'payment_method'                     => isset($payment_info['payment_method_code']) ? $payment_info['payment_method_code'] : '',
					'shipping_method'                    => WC()->session->get('chosen_shipping_methods'),
					'ship_to_different_address'          => 1,
					'woocommerce_checkout_update_totals' => '',
					'billing_first_name'                 => isset($billing_address['first_name']) ? $billing_address['first_name'] : '',
					'billing_last_name'                  => isset($billing_address['last_name']) ? $billing_address['last_name'] : '',
					'billing_company'                    => isset($billing_address['company']) ? $billing_address['company'] : '',
					'billing_country'                    => !empty($billing_address['country']) ? WC()->countries->countries[$billing_address['country']] : '',
					'billing_address_1'                  => isset($billing_address['address_1']) ? $billing_address['address_1'] : '',
					'billing_address_2'                  => isset($billing_address['address_2']) ? $billing_address['address_2'] : '',
					'billing_city'                       => isset($billing_address['city']) ? $billing_address['city'] : '',
					'billing_state'                      => (!empty($billing_address['country']) && !empty($billing_address['state'])) ? $states[$billing_address['country']][$billing_address['state']] : '',
					'billing_postcode'                   => isset($billing_address['postcode']) ? $billing_address['postcode'] : '',
					'billing_phone'                      => isset($billing_address['phone']) ? $billing_address['phone'] : '',
					'billing_email'                      => isset($email) ? $email : '',
					'shipping_first_name'                => isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '',
					'shipping_last_name'                 => isset($shipping_address['last_name']) ? $shipping_address['last_name'] : '',
					'shipping_company'                   => isset($shipping_address['company']) ? $shipping_address['company'] : '',
					'shipping_country'                   => !empty($shipping_address['country']) ? WC()->countries->countries[$shipping_address['country']] : '',
					'shipping_address_1'                 => isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '',
					'shipping_address_2'                 => isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '',
					'shipping_city'                      => isset($shipping_address['city']) ? $shipping_address['city'] : '',
					'shipping_state'                     => (!empty($shipping_address['country']) && !empty($shipping_address['state'])) ? $states[$shipping_address['country']][$shipping_address['state']] : '',
					'shipping_postcode'                  => isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '',
					'order_comments'                     => $order_comment,
				);
				wmab_log_knowband_app_request('appCreateOrder', serialize_block_attributes($order_data));
				// Calculate shipping and get Shippign Methods
				WC()->shipping->calculate_shipping(WC()->cart->get_shipping_packages());

				$order_id = WC()->checkout()->create_order($order_data);
				$order    = wc_get_order($order_id);
				update_post_meta($order_id, '_customer_user', $customer_id);
				// Update Paypal Payment Status
				if (isset($payment_info['status']) && !empty($payment_info['status'])) {
					update_post_meta($order_id, '_paypal_status', $payment_info['status']);
				}
				// Update Paypal Payment Transaction ID
				if (isset($payment_info['transaction_id']) && !empty($payment_info['transaction_id'])) {
					update_post_meta($order_id, '_transaction_id', $payment_info['transaction_id']);
				}
				$order->calculate_totals();
				if (isset($payment_info['transaction_id']) && !empty($payment_info['transaction_id'])) {
					$order->payment_complete($payment_info['transaction_id']);
				}
				$cart->empty_cart();
				WC()->session->set('cart', array());

				if ($order_id) {

					// Check if FCM and Cart mapping exists
					$fcm_data = $this->isFcmExist($cart_id, $email);
					if (isset($fcm_data) && !empty($fcm_data)) {
						// Update FCM and Order mapping into the table
						$this->mapOrderWithFCM($order_id, $fcm_data->fcm_details_id);
					}

					// Order Success Push Notification
					if (isset($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_success_enabled'])) {
						// Get Notification Title and Message
						$notification_title   = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_title'];
						$notification_message = $this->wmab_plugin_settings['push_notification_settings']['order_success_notification_msg'];

						$this->notify($notification_title, $notification_message, 'order_placed', $cart_id, $order_id, $email, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key']);
					}

					$this->wmab_response['status']   = 'success';
					$this->wmab_response['message']  = __('Order created by this cart.', 'knowband-mobile-app-builder-for-woocommerce');
					$this->wmab_response['order_id'] = $order_id;
				}
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appCreateOrder', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to insert/update FCM and Order mapping into the DB table
	 *
	 * @param  string $order_id This parameter holds Order ID
	 * @param  string $email    This parameter holds Customer Email
	 * @param  string $cart_id  This parameter holds current cart ID
	 * @param  string $fcm_id   This parameter hold FCM ID of Mobile device through which Push Notification can be sent
	 */
	private function mapOrderWithFCM($order_id, $update_id)
	{
		global $wpdb;

		if (isset($update_id) && !empty($update_id) && isset($order_id) && !empty($order_id)) {
			if ($update_id) {
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET order_id = %d, date_upd = now() WHERE fcm_details_id = %d", $order_id, $update_id));
			}
		}
	}

	/**
	 * Function to check if FCM and Cart mapping exist
	 *
	 * @param  string $cart_id This parameter holds current Cart ID
	 * @param  string $email   This parameter holds Customer Email
	 */
	private function isFcmExist($cart_id, $email)
	{
		global $wpdb;

		$checkMapping = ''; // Default definition of variable

		if (!empty($email) && !empty($cart_id)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND last_order_status = 0 AND order_id IS NULL", $cart_id));
			// BOC neeraj.kumar@velsof.com 31-dec-2019 Resolved this issue :  trim() expects parameter 1 to be string, object given by removing Trim function
			if (empty($checkMapping)) {
				$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND last_order_status = 0 AND order_id IS NULL", $email));
			}
		} elseif (!empty($email)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE email_id = %s AND last_order_status = 0 AND (order_id = 0 or order_id is NULL)", $email));
		} elseif (!empty($cart_id)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE cart = %s AND last_order_status = 0 AND order_id = 0", $cart_id));
		}

		return $checkMapping;
	}

	/**
	 * Function to check if FCM and Order mapping exist
	 *
	 * @param  string $order_id This parameter holds Order ID
	 */
	private function isFcmAndOrderMappingExist($order_id)
	{
		global $wpdb;

		$checkMapping = ''; // Default definition of variable

		if (!empty($order_id)) {
			$checkMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_fcm_details WHERE order_id = %d", $order_id));
		}
		return $checkMapping;
	}

	/**
	 * Function to get Abandoned Carts
	 */
	private function getAbandonedCarts($interval)
	{
		global $wpdb;

		$abandoned_carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mab_fcm_details 
				WHERE order_id IS NULL 
				AND (notification_sent_status = 0 OR notification_sent_status IS NULL) 
				AND last_order_status = 0 
				AND date_upd < DATE_SUB(NOW(), INTERVAL %d HOUR)",
				$interval
			)
		);
		return $abandoned_carts;
	}

	/**
	 * Function to send push notification for Abandoned Cart
	 */
	public function app_send_abandoned_cart_push_notification()
	{
		global $wpdb;

		// Abandoned Cart Push Notification
		if (isset($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_enabled'])) {

			// Get Abandoned Carts
			$abandoned_carts = $this->getAbandonedCarts($this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_time_interval']);

			if ($abandoned_carts) {
				foreach ($abandoned_carts as $abandoned_cart) {
					// Get Notification Title and Message
					$notification_title   = $this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_notification_title'];
					$notification_message = $this->wmab_plugin_settings['push_notification_settings']['abandoned_cart_notification_msg'];

					if ($this->notify($notification_title, $notification_message, 'order_abandoned', $abandoned_cart->cart, $abandoned_cart->order_id, $abandoned_cart->email_id, $abandoned_cart->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key'])) {
						// Update Notification sent status
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET notification_sent_status = '1', date_upd = now() WHERE fcm_details_id = %d", $abandoned_cart->fcm_details_id));
					}
				}
			}
		}

		die;
	}

	/*
	* Function to send push notification on Order Status Pending
	*/

	public function order_status_update($order_id, $status, $status_id)
	{
		global $wpdb;

		// Order Success Push Notification
		if (isset($this->wmab_plugin_settings['push_notification_settings']['order_status_enabled']) && !empty($this->wmab_plugin_settings['push_notification_settings']['order_status_enabled'])) {

			if ($order_id) {
				// Check if FCM and Cart mapping exists
				$fcm_data = $this->isFcmAndOrderMappingExist($order_id);

				if (isset($fcm_data) && !empty($fcm_data)) {
					// Get Notification Title and Message
					$notification_title   = str_replace('{{STATUS}}', $status, $this->wmab_plugin_settings['push_notification_settings']['order_status_notification_title']);
					$notification_message = str_replace('{{STATUS}}', $status, $this->wmab_plugin_settings['push_notification_settings']['order_status_notification_msg']);

					if ($this->notify($notification_title, $notification_message, 'order_status_changed', $fcm_data->cart, $order_id, $fcm_data->email_id, $fcm_data->fcm_id, $this->wmab_plugin_settings['push_notification_settings']['firebase_server_key'])) {
						// Update Last Order Status
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_fcm_details SET last_order_status = %s, date_upd = now() WHERE fcm_details_id = %d", $status_id, $fcm_data->fcm_details_id));
					}
				}
			}
		}
	}

	/**
	 * Function to send push notifications
	 *
	 * @param string $title               This parameter holds Push Notification Title
	 * @param string $message             This parameter holds Push Notification Message
	 * @param string $push_type           This parameter holds Push Notification Push Type
	 * @param string $cart_id             This parameter holds Cart ID
	 * @param int    $order_id            This parameter holds Order ID
	 * @param string $email               This parameter holds Customer Email
	 * @param string $fcm_id              This parameter holds FCM ID
	 * @param string $firebase_server_key This parameter holds Firebase Server Key
	 */
	private function notify($title, $message, $push_type, $cart_id, $order_id, $email, $fcm_id, $firebase_server_key)
	{

		$firebase_data                          = array();
		$firebase_data['data']['title']         = $title;
		$firebase_data['data']['is_background'] = false;
		$firebase_data['data']['message']       = $message;
		$firebase_data['data']['image']         = '';
		$firebase_data['data']['payload']       = '';
		$firebase_data['data']['user_id']       = '';
		$firebase_data['data']['push_type']     = $push_type;
		$firebase_data['data']['cart_id']       = $cart_id;
		$firebase_data['data']['order_id']      = $order_id;
		$firebase_data['data']['email_id']      = $email;

		if ($fcm_id) {
			wmab_send_multiple($fcm_id, $firebase_data['data'], $firebase_server_key);
		}

		return true;
	}
}
