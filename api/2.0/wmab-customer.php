<?php

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly
}

define('KB_WMAB_API_VERSION', '2.0');

/**
 * Class - WmabCustomer
 *
 * This class contains constructor and other methods which are actually related to customer information or cart information
 *
 * @version v2.0
 * @Date    10-Jun-2022
 */
class WmabCustomer
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
			// In the case of the app login, Email ID is coming in the post which causing the set the session without the login. So added $_POST['login_email'] in wmab_app_login function in woocommerce-mobile-app-builder.php so identify whether email is coming for login OR not
			if (empty($_POST['login_email'])) {
				$current_user_id = email_exists(sanitize_text_field($_POST['email']));
				if (!empty($current_user_id)) {
					wp_set_current_user($current_user_id); // phpcs:ignore
				}
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
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		if (null === WC()->session) {
			/**
			 * Customize the session handler used by WooCommerce.
			 *
			 * This hook allows you to modify the session handler used by WooCommerce
			 * for managing user sessions. You can use this hook to implement custom
			 * session handling logic or integrate with third-party session management solutions.
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

		//Fires when the WooCommerce session is about to expire.
		$this->wmab_session_expiring = time() + intval(apply_filters('wc_session_expiring', 60 * 60 * 47));

		// Filters the expiration time for the WooCommerce session.
		$this->wmab_session_expiration = time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48));

		// Filters whether to use a secure cookie for the WooCommerce session.
		$this->wmab_cookie = apply_filters('woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH);
		$to_hash           = $cart_id . '|' . $this->wmab_session_expiration;
		$cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
		$cookie_value      = $cart_id . '||' . $this->wmab_session_expiration . '||' . $this->wmab_session_expiring . '||' . $cookie_hash;

		// Filters whether to use a secure cookie for the WooCommerce session.
		wc_setcookie($this->wmab_cookie, $cookie_value, $this->wmab_session_expiration, apply_filters('wc_session_use_secure_cookie', false));
		$_COOKIE[$this->wmab_cookie] = $cookie_value;
	}

	/**
	 * Function to handle appAddToCart API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appAddToCart
	 *
	 * @param  string $cart_products This parameter holds json string of cart products
	 * @param  string $session_data  This parameter holds cart id or session id
	 * @param  string $version       This parameter holds API ve1rsion to verify during API call
	 */
	public function app_add_to_cart($cart_products, $session_data, $return = false, $version)
	{

		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appAddToCart');

		if (isset($cart_products) && !empty($cart_products)) {
			$cart_products = json_decode(stripslashes($cart_products));
			$email         = $cart_products->email;

			if (isset($session_data) && !empty($session_data)) {
				$cart_id = $session_data;
			} elseif (isset($email) && !empty($email)) {
				$cart_id = email_exists($email);
			} else {
				$cart_id = WC()->session->get_customer_id();
			}

			if (isset($cart_id) && !empty($cart_id)) {
				// Set Cart Session
				$this->set_session($cart_id);

				$products_to_add = $cart_products->cart_products;

				if (isset($products_to_add) && !empty($products_to_add)) {
					foreach ($products_to_add as $product) {
						$variation_id       = !empty($product->id_product_attribute) ? $product->id_product_attribute : '';
						$attributes         = $product->option;
						$product_attributes = array();
						if (isset($attributes) && !empty($attributes)) {
							foreach ($attributes as $attribute) {
								// Get Attribute Taxonomy name by ID
								$attribute_name                                       = wc_attribute_taxonomy_name_by_id($attribute->id);
								$attribute_value                                      = get_term_by('term_taxonomy_id', $attribute->selected_value_id);
								$product_attributes['attribute_' . $attribute_name] = isset($attribute_value->name) ? $attribute_value->name : '';
							}
						}
						$qty = !empty($product->minimal_quantity) ? $product->minimal_quantity : $product->quantity;
						// Add Product into the cart

						if (WC()->cart->add_to_cart($product->product_id, $qty, $variation_id, $product_attributes, array())) {
							if (isset($cart_products->coupon) && !empty($cart_products->coupon)) {
								// Check if woocommerce allowed to redeem coupon
								if (get_option('woocommerce_enable_coupons') === 'yes') {
									WC()->cart->apply_coupon($cart_products->coupon);
								}
							}

							if (!$return) {
								// Success Reponse
								$this->wmab_response['status']       = 'success';
								$this->wmab_response['message']      = __('Product successfully added into the cart.', 'knowband-mobile-app-builder-for-woocommerce');
								$this->wmab_response['session_data'] = WC()->session->get_customer_id();
								// BOC Module Upgrade V2 neeraj.kumar@velsof.com return total cart count when user click add to cart.
								$this->wmab_response['total_cart_items'] = WC()->cart->get_cart_contents_count();
								$this->wmab_response['total_cart_count'] = WC()->cart->get_cart_contents_count();
								// Log Request
								wmab_log_knowband_app_request('appAddToCart', serialize_block_attributes($this->wmab_response));

								$response = rest_ensure_response($this->wmab_response);
								return $response;
							}
						}
					}
				}
			}
		}

		if (!$return) {
			// Failure Reponse
			$this->wmab_response['status']           = 'failure';
			$this->wmab_response['message']          = __('Product cannot be added into the cart.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data']     = WC()->session->get_customer_id();
			$this->wmab_response['total_cart_items'] = WC()->cart->get_cart_contents_count();
			$this->wmab_response['total_cart_count'] = WC()->cart->get_cart_contents_count();
			// Log Request
			wmab_log_knowband_app_request('appAddToCart', serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}
	}

	/**
	 * Function to handle appGetCartDetails API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCartDetails
	 *
	 * @param  string  $email        This parameter holds customer email address
	 * @param  string  $session_data This parameter holds cart id or session id
	 * @param  boolean $return       This parameter holds true of false to return value
	 * @param  string  $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_get_cart_details($email, $session_data, $return = false, $version, $reorder = false)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetCartDetails');

		$this->wmab_response['checkout_page'] = (object) array(
			'per_products_shipping' => 0,
		);

		$this->wmab_response['products'] = array();
		$this->wmab_response['vouchers'] = array();
		// Gift Wrapping is not available in WooCommerce so it would be send as default response
		$this->wmab_response['gift_wrapping']            = (object) array(
			'available' => '',
			'applied'   => '',
			'message'   => '',
			'cost_text' => '',
		);
		$this->wmab_response['guest_checkout_enabled']   = (string) get_option('woocommerce_enable_guest_checkout') === 'yes' ? '0' : '0'; // Set it as disabled
		$this->wmab_response['cart']                     = (object) array(
			'total_cart_items' => 0,
			'total_cart_count' => 0,
		);
		$this->wmab_response['voucher_allowed']          = (string) (get_option('woocommerce_enable_coupons') === 'yes' ? 1 : 0);
		$this->wmab_response['minimum_purchase_message'] = '';
		$this->wmab_response['totals']                   = array();
		$this->wmab_response['voucher_html']             = '';
		// Delay Shipping is not available in WooCommerce so it would be send as default response
		$this->wmab_response['delay_shipping'] = (object) array(
			'available' => (string) 0,
			'applied'   => (string) 0,
		);
		$this->wmab_response['cart_id']        = '';
		$this->wmab_response['coupon_allowed'] = (string) get_option('woocommerce_enable_coupons') === 'yes' ? 1 : 0;

		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} elseif (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		}

		$this->set_session($cart_id);

		if (isset($cart_id) && !empty($cart_id)) {
			// Set Cart Session

			$cart_content = WC()->cart->get_cart_contents();

			$total_cart_quantity = 0;
			if (empty($cart_content) && isset($email) && !empty($email)) {
				$session_value = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $cart_id));
				$cart_value    = unserialize($session_value);
				$cart_content  = unserialize($cart_value['cart']);
				if (!empty($cart_content)) {
					foreach ($cart_content as $cart_item) {
						if (!empty($cart_item['quantity'])) {
							$total_cart_quantity += $cart_item['quantity'];
						}
					}
				}
			}

			// Get Cart Coupons
			$coupons = WC()->cart->get_coupons();
			if (isset($coupons) && !empty($coupons)) {
				foreach ($coupons as $coupon) {
					$coupon_data                       = $coupon->get_data();
					$this->wmab_response['vouchers'][] = array(
						'id'    => (string) $coupon_data['id'],
						'name'  => $coupon_data['code'],
						'value' => '-' . html_entity_decode(strip_tags(wc_price(WC()->cart->get_coupon_discount_amount($coupon_data['code'], WC()->cart->display_cart_ex_tax))), ENT_QUOTES),
					);
				}
			}

			$cart_counter = 0;
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
					 *  Vishal Goyal
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
					 *  Vishal Goyal
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
				 *  Vishal Goyal
				 */
				// Option Data for Product Quantity
				// $option_data[] = array(
				// 'name' => __('Quantity', 'knowband-mobile-app-builder-for-woocommerce'),
				// 'value' => (string) $cart_products['quantity']
				// );

				// Option Data for Product SKU
				// $option_data[] = array(
				// 'name' => __('SKU', 'knowband-mobile-app-builder-for-woocommerce'),
				// 'value' => $product->get_sku()
				// );

				// Option Data for Product Attributes
				if (isset($cart_products['variation_id']) && !empty($cart_products['variation_id'])) {
					$product_attributes = $product->get_attributes();
					/**
					 * Added below code to fetch the attribute data for the products so that, the same data can be displayed on front
					 * VGfeb2023 attribute-issue
					 *
					 * @date   01-03-2023
					 *  Vishal Goyal
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
				$product_image = get_the_post_thumbnail_url($product_id);

				if (null == $product_image[0] && !empty($cart_products['variation_id'])) {
					$product_image = get_the_post_thumbnail_url($cart_products['product_id']);
				}

				if (empty($product_image)) {
					$product_image = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
				}

				$kb_price = $product->get_regular_price();

				if ($product->is_on_sale()) {
					$kb_price_total = $product->get_sale_price();
				} else {
					$kb_price_total = $product->get_regular_price();
				}

				// EOC: Changes added by Vishal on 11th Nov 2022 to set the prices based on Sale price dates
				if ('000' != $cart_products['quantity']) { // Condition added to ignore products which are removed by updating quantity as 000 through appRemoveProduct API
					$this->wmab_response['products'][] = array(
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
						'images'               => (string) $product_image,
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
						 *  Vishal Goyal
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
					++$cart_counter;
				}
			}

			if (WC()->cart->get_cart_contents_count()) {
				$this->wmab_response['cart'] = (object) array(
					'total_cart_items' => WC()->cart->get_cart_contents_count(),
					'total_cart_count' => WC()->cart->get_cart_contents_count(),
				);
			} else {
				$this->wmab_response['cart'] = (object) array(
					'total_cart_items' => $total_cart_quantity,
					'total_cart_count' => (int) $total_cart_quantity,
				);
			}

			WC()->cart->calculate_totals();

			// Cart SubTotal
			$cart_sub_total                  = WC()->cart->get_cart_subtotal();
			$this->wmab_response['totals'][] = array(
				'name'  => __('Subtotal', 'knowband-mobile-app-builder-for-woocommerce'),
				'value' => html_entity_decode(strip_tags($cart_sub_total), ENT_QUOTES),
			);

			$this->wmab_response['totals'][] = array(
				'name'  => __('Shipping', 'knowband-mobile-app-builder-for-woocommerce'),
				'value' => html_entity_decode(strip_tags(WC()->cart->get_cart_shipping_total()), ENT_QUOTES),
			);

			foreach (WC()->cart->get_fees() as $fee) {
				$this->wmab_response['totals'][] = array(
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
						$this->wmab_response['totals'][] = array(
							'name'  => esc_html($tax->label) . $estimated_text,
							'value' => html_entity_decode(strip_tags(wp_kses_post($tax->formatted_amount)), ENT_QUOTES),
						);
					}
				} else {
					$this->wmab_response['totals'][] = array(
						'name'  => esc_html(WC()->countries->tax_or_vat()) . $estimated_text,
						'value' => html_entity_decode(strip_tags(wc_price(WC()->cart->get_taxes_total())), ENT_QUOTES),
					);
				}
			}

			$this->wmab_response['totals'][] = array(
				'name'  => __('Total', 'knowband-mobile-app-builder-for-woocommerce'),
				'value' => html_entity_decode(strip_tags(WC()->cart->get_total()), ENT_QUOTES),
			);

			if (isset($reorder) && $reorder) {
				$this->wmab_response['cart_id'] = (string) $cart_id;
			} else {
				$this->wmab_response['cart_id'] = (string) $cart_id;
			}
		}

		if (!$return) {
			// Log Request
			wmab_log_knowband_app_request('appGetCartDetails', serialize_block_attributes($this->wmab_response));

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		} else {
			return $this->wmab_response;
		}
	}

	/**
	 * Function to handle appCheckOrderStatus API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckOrderStatus
	 *
	 * @param  string $email        This parameter holds customer email address
	 * @param  string $session_data This parameter holds cart id or session id
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_check_order_status($email, $session_data, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appCheckOrderStatus');

		// Default Response Parameters
		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = '';
		$this->wmab_response['cart_id'] = $session_data;

		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} elseif (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		}

		if (isset($cart_id) && !empty($cart_id)) {
			// Set Cart Session
			$this->set_session($cart_id);

			if (WC()->cart->is_empty()) {
				$this->wmab_response['status']  = 'success';
				$this->wmab_response['message'] = __('Order created by this cart.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['cart_id'] = $cart_id;
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appCheckOrderStatus', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetCustomerAddress API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCustomerAddress
	 *
	 * @param  string $email        This parameter holds customer email
	 * @param  string $session_data This parameter holds cart id or session id
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_get_customer_address($email, $session_data, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetCustomerAddress');

		$this->wmab_response['default_address']  = '';
		$this->wmab_response['shipping_address'] = array();

		// Get Customer ID by email
		$customer_id = email_exists($email);

		// Get All States
		$wc_country = new WC_Countries();
		$states     = $wc_country->__get('states');

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

		$session_id = $wpdb->get_var($wpdb->prepare("SELECT session_key FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %s and reorder_direct='0'", (int) $customer_id));
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
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}mab_login_sessions WHERE user_id = %d",
					(int) $customer_id
				)
			);
		}

		// Get Customer Shipping Address details
		$wc_customer      = new WC_Customer($customer_id);
		$customer         = $wc_customer->get_shipping();
		$customer_billing = $wc_customer->get_billing();

		if (isset($customer['first_name']) && !empty($customer['first_name'])) {
			$this->wmab_response['default_address']    = '1';   // Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available
			$this->wmab_response['shipping_address'][] = array(
				'id_shipping_address' => '1',
				'firstname'           => isset($customer['first_name']) ? $customer['first_name'] : '',
				'lastname'            => isset($customer['last_name']) ? $customer['last_name'] : '',
				'company'             => isset($customer['company']) ? $customer['company'] : '',
				'address_1'           => isset($customer['address_1']) ? $customer['address_1'] : '',
				'address_2'           => isset($customer['address_2']) ? $customer['address_2'] : '',
				'city'                => isset($customer['city']) ? $customer['city'] : '',
				'state'               => !empty($states[$customer['country']][$customer['state']]) ? $states[$customer['country']][$customer['state']] : $customer['state'],
				'country'             => !empty($customer['country']) ? WC()->countries->countries[$customer['country']] : '',
				'postcode'            => isset($customer['postcode']) ? $customer['postcode'] : '',
				'mobile_no'           => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
			);
		}

		// Log Request
		wmab_log_knowband_app_request('appGetCustomerAddress', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetAddressForm API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetAddressForm
	 *
	 * @param  string $email               This parameter holds customer email
	 * @param  int    $id_shipping_address This parameter holds shipping address id
	 * @param  string $version             This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_get_address_form($email, $id_shipping_address, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetAddressForm');

		// Get All Countries
		$wc_country = new WC_Countries();
		$countries  = $wc_country->__get('countries');

		$this->wmab_response['countries'] = array();
		if (isset($countries) && !empty($countries)) {
			foreach ($countries as $code => $name) {
				$this->wmab_response['countries'][] = array(
					'id'   => $code,
					'name' => $name,
				);
			}
		}
		$checkout        = WC()->checkout();
		$billing_fields  = $checkout->get_checkout_fields('billing');
		$shipping_fields = $checkout->get_checkout_fields('shipping');
		/*
		* For now $id_shipping_address is unused as WooCommerce does nnot support multiple address feature
		* Address is being fetched on the basis of customer email ID
		*/

		// Get Customer ID by email
		$customer_id = email_exists($email);

		// Get Customer Shipping Address details
		$wc_customer      = new WC_Customer($customer_id);
		$customer         = $wc_customer->get_shipping();
		$customer_billing = $wc_customer->get_billing();

		// Get Shipping Address Form
		$this->wmab_response['shipping_address_items'] = array(
			array(
				'label'       => __('First Name', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'first_name',
				'value'       => isset($customer['first_name']) ? $customer['first_name'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Last Name', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'last_name',
				'value'       => isset($customer['last_name']) ? $customer['last_name'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Company', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'company',
				'value'       => isset($customer['company']) ? $customer['company'] : '',
				'required'    => '0',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Address Line 1', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'address_1',
				'value'       => isset($customer['address_1']) ? $customer['address_1'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Address Line 2', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'address_2',
				'value'       => isset($customer['address_2']) ? $customer['address_2'] : '',
				'required'    => '0',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Town/City', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'city',
				'value'       => isset($customer['city']) ? $customer['city'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('State/County', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'dropdownfield',
				'name'        => 'state',
				'value'       => isset($customer['state']) ? $customer['state'] : '',
				'required'    => isset($billing_fields['billing_state']['required']) ? '1' : '0',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Postcode/Zip', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'postcode',
				'value'       => isset($customer['postcode']) ? $customer['postcode'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Country', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'dropdownfield',
				'name'        => 'country',
				'value'       => isset($customer['country']) ? $customer['country'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
			array(
				'label'       => __('Phone', 'knowband-mobile-app-builder-for-woocommerce'),
				'type'        => 'textfield',
				'name'        => 'phone',
				'value'       => isset($customer_billing['phone']) ? $customer_billing['phone'] : '',
				'required'    => '1',
				'validation'  => '',
				'group_items' => array(),
			),
		);

		// Get Default Country
		$default_country = get_option('woocommerce_default_country');
		$country_string  = wc_format_country_state_string($default_country);

		$this->wmab_response['default_state_id']   = !empty($customer['state']) ? $customer['state'] : '';
		$this->wmab_response['default_country_id'] = !empty($customer['country']) ? $customer['country'] : $country_string['country'];

		// Log Request
		wmab_log_knowband_app_request('appGetAddressForm', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appAddAddress API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appAddAddress
	 *
	 * @param  string $shipping_address This parameter holds json string of customer shipping address
	 * @param  string $email            This parameter hold customer email
	 * @param  string $session_data     This parameter holds cart id or session id
	 * @param  string $version          This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_add_address($shipping_address, $email, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appAddAddress');

		// Get Customer ID by email
		$customer_id = email_exists($email);

		if (isset($customer_id) && !empty($customer_id)) {
			$shipping_address = json_decode(str_replace('\\', '', str_replace('&quot;', '"', (trim($shipping_address)))), true);

			// Updating both Billing and shipping as only one address is being allowed by WooCommerce
			// Update First Name
			update_user_meta($customer_id, 'shipping_first_name', $shipping_address['first_name']);
			update_user_meta($customer_id, 'billing_first_name', $shipping_address['first_name']);
			// Update Last Name
			update_user_meta($customer_id, 'shipping_last_name', $shipping_address['last_name']);
			update_user_meta($customer_id, 'billing_last_name', $shipping_address['last_name']);
			// Update Company
			update_user_meta($customer_id, 'shipping_company', $shipping_address['company']);
			update_user_meta($customer_id, 'billing_company', $shipping_address['company']);
			// Update Address 1
			update_user_meta($customer_id, 'shipping_address_1', $shipping_address['address_1']);
			update_user_meta($customer_id, 'billing_address_1', $shipping_address['address_1']);
			// Update Address 2
			update_user_meta($customer_id, 'shipping_address_2', $shipping_address['address_2']);
			update_user_meta($customer_id, 'billing_address_2', $shipping_address['address_2']);
			// Update City
			update_user_meta($customer_id, 'shipping_city', $shipping_address['city']);
			update_user_meta($customer_id, 'billing_city', $shipping_address['city']);
			// Update State
			update_user_meta($customer_id, 'shipping_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
			update_user_meta($customer_id, 'billing_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
			// Update PostCode
			update_user_meta($customer_id, 'shipping_postcode', $shipping_address['postcode']);
			update_user_meta($customer_id, 'billing_postcode', $shipping_address['postcode']);
			// Update Country
			update_user_meta($customer_id, 'shipping_country', $shipping_address['country']);
			update_user_meta($customer_id, 'billing_country', $shipping_address['country']);

			update_user_meta($customer_id, 'billing_phone', $shipping_address['phone']);

			$this->wmab_response['shipping_address_reponse'] = array(
				'status'  => 'success',
				'message' => __('Shipping address has been added successfully.', 'knowband-mobile-app-builder-for-woocommerce'),
			);
		} else {
			$this->wmab_response['shipping_address_reponse'] = array(
				'status'  => 'failure',
				'message' => __('Shipping address could not be added successfully.', 'knowband-mobile-app-builder-for-woocommerce'),
			);
		}

		$this->wmab_response['cart_id']                = $session_data;
		$this->wmab_response['shipping_address_count'] = 1;
		$this->wmab_response['id_shipping_address']    = 1;  // Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available
		// Log Request
		wmab_log_knowband_app_request('appAddAddress', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appSaveProductReview API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.2/appAddReview
	 *
	 * @param  string $customer_review This parameter holds json string of customer review
	 * @param  string $email           This parameter hold customer email
	 * @param  string $version         This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_add_review($product_id, $title, $content, $customer_name, $rating, $email, $version)
	{
		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appSaveProductReview');

		// Get Customer ID by email
		$customer_id = email_exists($email);

		if ($customer_id > 0) {
			$customer_approved = 1;
		} else {
			$customer_approved = 0;
		}

		$args          = array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $customer_name,
			'comment_author_email' => $email,
			'comment_author_IP'    => preg_replace('/[^0-9a-fA-F:., ]/', '', isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : ''),
			'comment_date'         => gmdate('Y-m-d H:i:s', strtotime('+5 Hours 30 Minutes')),
			'comment_date_gmt'     => gmdate('Y-m-d H:i:s'),
			'comment_content'      => $content,
			'comment_approved'     => $customer_approved,
			'comment_agent'        => substr(isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '', 0, 254),
			'comment_type'         => 'review',
			'user_id'              => $customer_id,
			'comment_meta'         => array(
				'rating'   => $rating,
				'verified' => 0,
			),
		);
		$comment_wp_id = wp_insert_comment($args);

		if ($comment_wp_id) {
			$this->wmab_response['status']  = 'success';
			$this->wmab_response['message'] = __('Review has been added successfully.', 'knowband-mobile-app-builder-for-woocommerce');
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Review could not be added successfully.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appSaveProductReview', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appUpdateAddress API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateAddAddress
	 *
	 * @param  int    $id_shipping_address This parameter holds ID of shipping address to be updated
	 * @param  string $shiping_address     This parameter holds customer shipping address details
	 * @param  string $email               This parameter holds customer email
	 * @param  string $session_data        This parameter holds current cart ID
	 * @param  string $version             This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_update_address($id_shipping_address, $shipping_address, $email, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appUpdateAddress');

		// Get Customer ID by email
		$customer_id = email_exists($email);

		if (isset($customer_id) && !empty($customer_id)) {
			$shipping_address = json_decode(str_replace('\\', '', str_replace('&quot;', '"', (trim($shipping_address)))), true);

			// Update First Name
			update_user_meta($customer_id, 'shipping_first_name', $shipping_address['first_name']);
			update_user_meta($customer_id, 'billing_first_name', $shipping_address['first_name']);
			// Update Last Name
			update_user_meta($customer_id, 'shipping_last_name', $shipping_address['last_name']);
			update_user_meta($customer_id, 'billing_last_name', $shipping_address['last_name']);
			// Update Company
			update_user_meta($customer_id, 'shipping_company', $shipping_address['company']);
			update_user_meta($customer_id, 'billing_company', $shipping_address['company']);
			// Update Address 1
			update_user_meta($customer_id, 'shipping_address_1', $shipping_address['address_1']);
			update_user_meta($customer_id, 'billing_address_1', $shipping_address['address_1']);
			// Update Address 2
			update_user_meta($customer_id, 'shipping_address_2', $shipping_address['address_2']);
			update_user_meta($customer_id, 'billing_address_2', $shipping_address['address_2']);
			// Update City
			update_user_meta($customer_id, 'shipping_city', $shipping_address['city']);
			update_user_meta($customer_id, 'billing_city', $shipping_address['city']);
			// Update State
			update_user_meta($customer_id, 'shipping_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
			update_user_meta($customer_id, 'billing_state', isset($shipping_address['state']) ? $shipping_address['state'] : '');
			// Update PostCode
			update_user_meta($customer_id, 'shipping_postcode', $shipping_address['postcode']);
			update_user_meta($customer_id, 'billing_postcode', $shipping_address['postcode']);
			// Update Country
			update_user_meta($customer_id, 'shipping_country', $shipping_address['country']);
			update_user_meta($customer_id, 'billing_country', $shipping_address['country']);
			// Update Phone
			update_user_meta($customer_id, 'billing_phone', $shipping_address['phone']);

			$this->wmab_response['shipping_address_reponse'] = array(
				'status'  => 'success',
				'message' => __('Shipping address has been updated successfully.', 'knowband-mobile-app-builder-for-woocommerce'),
			);
		} else {
			$this->wmab_response['shipping_address_reponse'] = array(
				'status'  => 'failure',
				'message' => __('Shipping address could not be updated successfully.', 'knowband-mobile-app-builder-for-woocommerce'),
			);
		}

		$this->wmab_response['cart_id']                = $session_data;
		$this->wmab_response['shipping_address_count'] = 1;
		$this->wmab_response['id_shipping_address']    = 1;  // Hard-coded value as WC by default allows to keep only one address in customer table - No Shiping Address ID is available
		// Log Request
		wmab_log_knowband_app_request('appUpdateAddress', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appSocialLogin API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appSocialLogin
	 *
	 * @param  string $user_details This parameter holds customer details
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_social_login($user_details, $session_data, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appSocialLogin');

		if (isset($user_details) && !empty($user_details)) {
			$user_details = json_decode(stripslashes($user_details));

			if (isset($user_details->email) && !empty($user_details->email)) {
				// Check if user already exists
				$customer = email_exists($user_details->email);
				if ($customer) {

					// Perform the Login Operation
					wp_clear_auth_cookie();
					$current_user = wp_set_current_user($customer); // phpcs:ignore
					wp_set_auth_cookie($customer, true, true); // phpcs:ignore

					// Do Action WP Login to add the _woocommerce_load_saved_cart_after_login key into the user meta table. If its value is 1 for the customer then cart will be merged via WC Cart
					/**
					 * Fires after a user logs in successfully.
					 *
					 * @since 1.0.0
					 * @param string $user_login The user login name.
					 * @param object $current_user The current user object.
					 */
					do_action('wp_login', $current_user->user_login, $current_user);

					$this->set_session($customer);

					// Comment by Ashish.
					// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
					// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
					$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $customer));
					$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $customer, (string) $session_data));

					// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
					/**
					 * Filters the session handler class used in WooCommerce.
					 * @since 1.0.0
					 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
					 * @return string The filtered session handler class name.
					 */

					$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
					WC()->session                      = new $session_class(); // For Session Object
					WC()->session->init();

					WC()->cart = new WC_Cart(); // For Cart Object
					WC()->cart->get_cart();

					// Get the correct total cart quantity
					$total_cart_quantity = WC()->cart->get_cart_contents_count();

					// GET Customer Data
					$customer_data = new WC_Customer($customer);

					// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
					$phone_number = ''; // it will hold customer's Phone Number
					$country_code = ''; // it will hold customer's Country Code
					$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer));
					if (isset($getMapping) && !empty($getMapping)) {
						$phone_number = $getMapping->mobile_number;
						$country_code = $getMapping->country_code;
					}
					$this->wmab_response['status']       = 'success';
					$this->wmab_response['message']      = __('User login successfully.', 'knowband-mobile-app-builder-for-woocommerce');
					$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();
					$this->wmab_response['login_user']   = array(
						'customer_id'    => (string) $customer,
						'firstname'      => $customer_data->get_first_name(),
						'lastname'       => $customer_data->get_last_name(),
						'mobile_number'  => $country_code . $phone_number,
						'email'          => $customer_data->get_email(),
						'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
						'cart_count'     => (int) $total_cart_quantity,
					);

					// Log Request
					wmab_log_knowband_app_request('appSocialLogin', serialize_block_attributes($this->wmab_response));
					$response = rest_ensure_response($this->wmab_response);
					return $response;
				} else {
					// Reister user with given details
					$registration = false;

					if (get_option('woocommerce_registration_generate_username') === 'no') {
						$username = $user_details->email;
					} else {
						$username = ''; // Set blank in case of registration through social login function
					}

					if (get_option('woocommerce_registration_generate_password') === 'no') {
						$password = rand(00000, 99999); // Random password is generating as we do not have password value through Socia Login
					} else {
						$password = '';  // Set blank in case of registration through social login function
					}

					$first_name = '';
					if (!empty($user_details->first_name)) {
						$first_name = $user_details->first_name;
					}

					$last_name = '';
					if (!empty($user_details->last_name)) {
						$last_name = $user_details->last_name;
					}

					if (empty($last_name)) {
						$first_name = $user_details->first_name;
						$full_name  = explode(' ', $first_name);

						if (count($full_name) > 1) {
							$first_name = $full_name[0];
							unset($full_name[0]);
							$last_name_array = implode(' ', $full_name);
							$last_name       = $last_name_array;
						} else {
							$first_name = $full_name[0];
							$last_name  = '.';
						}
					}

					$validation_error = new WP_Error();
					/**
					 * Filters the error messages during the WooCommerce registration process.
					 *
					 *@since 1.0.0
					 * @param string|WP_Error $validation_error The error message or WP_Error object.
					 * @param string          $username         The username.
					 * @param string          $password         The password.
					 * @param string          $email            The email address.
					 * @return string|WP_Error The filtered error message or WP_Error object.
					 */
					$validation_error = apply_filters('woocommerce_process_registration_errors', $validation_error, $username, $password, $user_details->email);

					if (!$validation_error->get_error_code()) {

						$new_customer = wc_create_new_customer(
							sanitize_email($user_details->email),
							wc_clean($username),
							$password,
							array(
								'first_name' => trim($first_name),
								'last_name'  => trim($last_name),
							)
						);

						if (!is_wp_error($new_customer)) {

							$registration = true;
						}
					}

					if ($registration) {
						// Registration successful
						wp_clear_auth_cookie();
						wp_set_current_user($new_customer); // phpcs:ignore
						wp_set_auth_cookie($new_customer, true, true); // phpcs:ignore

						$this->set_session($new_customer);

						// Comment by Ashish.
						// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
						// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
						$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $new_customer));
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $new_customer, (string) $session_data));

						// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
						/**
						 * Filters the session handler class used in WooCommerce.
						 *
						 *@since 1.0.0
						 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
						 * @return string The filtered session handler class name.
						 */

						$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
						WC()->session                          = new $session_class(); // For Session Object
						WC()->session->init();

						WC()->cart = new WC_Cart(); // For Cart Object
						WC()->cart->get_cart();

						// Get the correct total cart quantity
						$total_cart_quantity = WC()->cart->get_cart_contents_count();

						// GET Customer Data
						$customer = new WC_Customer($new_customer);

						// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
						$phone_number = ''; // it will hold customer's Phone Number
						$country_code = ''; // it will hold customer's Country Code
						$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $new_customer));
						if (isset($getMapping) && !empty($getMapping)) {
							$phone_number = $getMapping->mobile_number;
							$country_code = $getMapping->country_code;
						}

						$this->wmab_response['status']       = 'success';
						$this->wmab_response['message']      = __('User login successfully.', 'knowband-mobile-app-builder-for-woocommerce');
						$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();
						$this->wmab_response['login_user']   = array(
							'customer_id'    => (string) $new_customer,
							'firstname'      => $customer->get_first_name(),
							'lastname'       => $customer->get_last_name(),
							'mobile_number'  => $country_code . $phone_number,
							'email'          => $customer->get_email(),
							'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
							'cart_count'     => (int) $total_cart_quantity,
						);

						// Log Request
						wmab_log_knowband_app_request('appSocialLogin', serialize_block_attributes($this->wmab_response));

						$response = rest_ensure_response($this->wmab_response);
						return $response;
					}
				}
			}
		}

		// Login and customer details verification Failed
		$this->wmab_response['status']       = 'failure';
		$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
		$this->wmab_response['session_data'] = '';
		$this->wmab_response['login_user']   = array(
			'customer_id'    => '0',
			'firstname'      => '',
			'lastname'       => '',
			'mobile_number'  => '',
			'email'          => '',
			'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
			'cart_count'     => 0,
		);
		// Log Request
		wmab_log_knowband_app_request('appSocialLogin', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appLogin API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLogin
	 *
	 * @param  string $username     This parameter holds Customer username
	 * @param  string $password     This parameter holds Customer account password
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_login($username, $password, $session_data, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appLogin');

		$creds = array(
			'user_login'    => trim($username),
			'user_password' => $password,
			'remember'      => 1, // Keep it by default 1
		);

		// Perform the login
		/**
		 * Filters the login credentials used for WooCommerce login.
		 *
		 * @since 1.0.0
		 * @param array $creds The login credentials.
		 * @return array The filtered login credentials.
		 */
		$user = wp_signon(apply_filters('woocommerce_login_credentials', $creds), is_ssl());

		wmab_log_knowband_app_request('appLogin', serialize_block_attributes($user));

		if (is_wp_error($user)) {
			// Login Failed
			$this->wmab_response['status']       = 'failure';
			$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = '';
			$this->wmab_response['login_user']   = array(
				'customer_id'    => '0',
				'firstname'      => '',
				'lastname'       => '',
				'mobile_number'  => '',
				'email'          => '',
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => 0,
			);
		} else {
			wp_clear_auth_cookie();
			wp_set_current_user($user->data->ID); // phpcs:ignore
			wp_set_auth_cookie($user->data->ID, true, true); // phpcs:ignore

			$this->set_session($user->data->ID);

			// Comment by Ashish.
			// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
			// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $user->data->ID));
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $user->data->ID, (string) $session_data));

			// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
			/**
			 * Filters the session handler class used in WooCommerce.
			 *
			 *@since 1.0.0
			 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
			 * @return string The filtered session handler class name.
			 */
			$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
			WC()->session              = new $session_class(); // For Session Object
			WC()->session->init();

			WC()->cart = new WC_Cart(); // For Cart Object
			WC()->cart->get_cart();

			$total_cart_quantity = WC()->cart->get_cart_contents_count();

			// GET CUSTOMER DATA
			$customer = new WC_Customer($user->data->ID);

			// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
			$phone_number = ''; // it will hold customer's Phone Number
			$country_code = ''; // it will hold customer's Country Code
			$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $user->data->ID));
			if (isset($getMapping) && !empty($getMapping)) {
				$phone_number = $getMapping->mobile_number;
				$country_code = $getMapping->country_code;
			}
			// EOC - Module Upgrade V2
			// Login Successful
			$this->wmab_response['status']       = 'success';
			$this->wmab_response['message']      = __('User login successfully.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();
			$this->wmab_response['login_user']   = array(
				'customer_id'    => (string) $user->data->ID,
				'firstname'      => $customer->get_first_name(),
				'lastname'       => $customer->get_last_name(),
				'mobile_number'  => $country_code . $phone_number,
				'email'          => $customer->get_email(),
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => (int) $total_cart_quantity,
			);
		}

		// Log Request
		wmab_log_knowband_app_request('appLogin', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appLoginViaPhone API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLoginViaPhone
	 *
	 * @param  string $phone_number This parameter holds Customer Phone Number
	 * @param  string $country_code This parameter holds Country Code
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 * @param  string $iso_code     This parameter holds Language ISO code
	 *  Knowband <support@knowband.com>
	 */
	public function app_login_via_phone($phone_number, $country_code, $session_data, $version, $iso_code)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appLoginViaPhone');

		// Verify Phone Number for Login
		$verify_phone = false;
		// Get Customer ID from unique verification table of MAB

		$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND country_code = %s", $phone_number, $country_code));
		if (isset($getMapping) && !empty($getMapping)) {
			$verify_phone = true;
			$customer_id  = $getMapping->user_id;
			// Get User details by ID
			$customer       = get_user_by('id', $customer_id);
			$customer_email = '';
			if (isset($customer->data->user_email) && !empty($customer->data->user_email)) {
				$customer_email = $customer->data->user_email;
			}
		}

		if (!$verify_phone) {
			// Login Failed
			$this->wmab_response['status']       = 'failure';
			$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = '';
			$this->wmab_response['login_user']   = array(
				'customer_id'    => '0',
				'firstname'      => '',
				'lastname'       => '',
				'mobile_number'  => '',
				'email'          => '',
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => 0,
			);
		} else {

			// GET CUSTOMER DATA
			$customer = new WC_Customer($customer_id);
			if (!empty($customer->get_id())) {

				// Perform the Login Operation
				wp_clear_auth_cookie();
				$current_user = wp_set_current_user($customer_id); // phpcs:ignore
				wp_set_auth_cookie($customer, true, true); // phpcs:ignore

				// Added by Ashish on 5th May 2022 to fix the cart restore issue at time of login.
				// Do Action WP Login to add the _woocommerce_load_saved_cart_after_login key into the user meta table. If its value is 1 for the customer then cart will be merged via WC Cart
				/**
				 * Fires after a user logs in successfully.
				 *
				 *@since 1.0.0
				 * @param string       $user_login The user login name.
				 * @param WP_User|bool $user       WP_User object if login was successful, or false on login failure.
				 */
				do_action('wp_login', $current_user->user_login, $current_user);

				$this->set_session($customer_id);

				// Comment by Ashish.
				// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
				// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
				$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $customer_id));
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $customer_id, (string) $session_data));

				// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
				/**
				 * Filters the session handler class used in WooCommerce.
				 *
				 * @since 1.0.0
				 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
				 * @return string The filtered session handler class name.
				 */
				$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
				WC()->session                  = new $session_class(); // For Session Object
				WC()->session->init();

				WC()->cart = new WC_Cart(); // For Cart Object
				WC()->cart->get_cart();

				// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$phone_number = ''; // it will hold customer's Phone Number
				$country_code = ''; // it will hold customer's Country Code
				$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					$phone_number = $getMapping->mobile_number;
					$country_code = $getMapping->country_code;
				}
				// EOC - Module Upgrade V2
				// Login Successful
				$this->wmab_response['status']       = 'success';
				$this->wmab_response['message']      = __('User login successfully.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();
				$this->wmab_response['login_user']   = array(
					'customer_id'    => (string) $customer_id,
					'firstname'      => $customer->get_first_name(),
					'lastname'       => $customer->get_last_name(),
					'mobile_number'  => $country_code . $phone_number,
					'email'          => $customer->get_email(),
					'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
					'cart_count'     => (int) WC()->cart->get_cart_contents_count(),
				);
			} else {
				// If Customer doesnt exits in WooCommerce.
				$this->wmab_response['status']       = 'failure';
				$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['session_data'] = '';
				$this->wmab_response['login_user']   = array(
					'customer_id'    => '0',
					'firstname'      => '',
					'lastname'       => '',
					'mobile_number'  => '',
					'email'          => '',
					'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
					'cart_count'     => 0,
				);
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appLoginViaPhone', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appLoginViaEmail API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLoginViaEmail
	 *
	 * @param  string $email_id  This parameter holds Customer Email ID
	 * @param  string $unique_id This parameter holds Unique ID for FingerPrint Matching
	 * @param  string $version   This parameter holds API version to verify during API call
	 * @param  string $iso_code  This parameter holds Language ISO code
	 *  Knowband <support@knowband.com>
	 */
	public function app_login_via_email($email_id, $unique_id, $version, $iso_code, $session_data)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appLoginViaEmail');

		// Verify Email ID and Unique ID for FingerPrint Login
		$verify_email = false;

		$customer_id = email_exists($email_id);

		if (isset($customer_id) && !empty($customer_id)) {
			$verification = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d AND unique_id = %s", $customer_id, $unique_id));
			if (isset($verification) && count($verification) > 0) {
				$verify_email = true;
			}
		}

		if (!$verify_email) {
			// Login Failed
			$this->wmab_response['status']       = 'failure';
			$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = '';
			$this->wmab_response['login_user']   = array(
				'customer_id'    => '0',
				'firstname'      => '',
				'lastname'       => '',
				'mobile_number'  => '',
				'email'          => '',
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => 0,
			);
		} else {
			// Replcae Cart ID with Customer ID
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", $customer_id, $session_data));

			// GET CUSTOMER DATA
			$customer = new WC_Customer($customer_id);
			if (!empty($customer->get_id())) {

				// Perform the Login Operation
				wp_clear_auth_cookie();
				$current_user = wp_set_current_user($customer_id); // phpcs:ignore
				wp_set_auth_cookie($customer, true, true); // phpcs:ignore

				// Added by Ashish on 5th May 2022 to fix the cart restore issue at time of login.
				// Do Action WP Login to add the _woocommerce_load_saved_cart_after_login key into the user meta table. If its value is 1 for the customer then cart will be merged via WC Cart
				/**
				 * Fires after a user logs in successfully.
				 *
				 * @since 1.0.0
				 * @param string       $user_login The user login name.
				 * @param WP_User|bool $user       WP_User object if login was successful, or false on login failure.
				 */
				do_action('wp_login', $current_user->user_login, $current_user);

				$this->set_session($customer_id);

				// Comment by Ashish.
				// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
				// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
				$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $customer_id));
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $customer_id, (string) $session_data));

				// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
				/**
				 * Filters the session handler class used in WooCommerce.
				 *
				 * @since 1.0.0
				 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
				 * @return string The filtered session handler class name.
				 */
				$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
				WC()->session  = new $session_class(); // For Session Object
				WC()->session->init();

				WC()->cart = new WC_Cart(); // For Cart Object
				WC()->cart->get_cart();

				// GET CUSTOMER DATA
				// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$phone_number = ''; // it will hold customer's Phone Number
				$country_code = ''; // it will hold customer's Country Code
				$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					$phone_number = $getMapping->mobile_number;
					$country_code = $getMapping->country_code;
				}
				// EOC - Module Upgrade V2
				// Login Successful
				$this->wmab_response['status']       = 'success';
				$this->wmab_response['message']      = __('User login successfully.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();
				$this->wmab_response['login_user']   = array(
					'customer_id'    => (string) $customer_id,
					'firstname'      => $customer->get_first_name(),
					'lastname'       => $customer->get_last_name(),
					'mobile_number'  => $country_code . $phone_number,
					'email'          => $customer->get_email(),
					'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
					'cart_count'     => (int) WC()->cart->get_cart_contents_count(),
				);
			} else {
				$this->wmab_response['status']       = 'failure';
				$this->wmab_response['message']      = __('User login failed.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['session_data'] = '';
				$this->wmab_response['login_user']   = array(
					'customer_id'    => '0',
					'firstname'      => '',
					'lastname'       => '',
					'mobile_number'  => '',
					'email'          => '',
					'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
					'cart_count'     => 0,
				);
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appLoginViaEmail', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appMapEmailWithUUID API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appMapEmailWithUUID
	 *
	 * @param  string $email_id  This parameter holds Customer Email ID
	 * @param  string $unique_id This parameter holds Unique ID for FingerPrint Matching
	 * @param  string $version   This parameter holds API version to verify during API call
	 * @param  string $iso_code  This parameter holds Language ISO code
	 *  Knowband <support@knowband.com>
	 */
	public function app_map_email_with_uuid($email_id, $unique_id, $version, $iso_code)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appMapEmailWithUUID');

		if (isset($email_id) && !empty($email_id) && isset($unique_id) && !empty($unique_id)) {
			// Map Email ID with Unique ID
			$is_mapped = false;

			$customer_id = email_exists($email_id);
			if (isset($customer_id) && !empty($customer_id)) {
				$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
				if (isset($getMapping) && !empty($getMapping)) {
					// Update Unique ID for existing User record
					$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET unique_id = %s WHERE id = %d", $unique_id, $getMapping->id));
					$is_mapped = true;
				} else {
					// Insert Customer and Unique ID mapping
					$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, unique_id = %s, date_added = now()", $customer_id, $unique_id));
					$is_mapped = true;
				}
			}

			if ($is_mapped) {
				$this->wmab_response['status']  = 'success';
				$this->wmab_response['message'] = __('Account has been mapped for the fingerprint login in this device', 'knowband-mobile-app-builder-for-woocommerce');
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Account could not be mapped for fingerprint login. Please try again.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Email ID or Unique ID.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		// Log Request
		wmab_log_knowband_app_request('appMapEmailWithUUID', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appCheckIfContactNumberExists API request - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appCheckIfContactNumberExists
	 *
	 * @param  string $phone_number This parameter holds Customer Phone Number
	 * @param  string $country_code This parameter holds Country Code
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_check_if_contact_number_exists($phone_number, $country_code, $exclude_customer = false, $customer_id = '', $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appCheckIfContactNumberExists');

		// Verify Phone Number for Login
		$verify_phone_exists = false;
		if (!$exclude_customer) {
			$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND country_code = %s", $phone_number, $country_code));
		} elseif (!empty($customer_id)) {
			$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE (mobile_number = %s AND country_code = %s) AND user_id != %d", $phone_number, $country_code, $customer_id));
		}
		if (isset($getMapping) && !empty($getMapping)) {
			// Check if Corrospodnig User Exist in the WooCommerce
			$customer = new WC_Customer($getMapping->user_id);
			if (!empty($customer->get_id())) {
				$verify_phone_exists = true;
			} else {
				$verify_phone_exists = false;
			}
		}

		if ($exclude_customer) {
			if ($verify_phone_exists) {
				// Phone Number Exists
				$this->wmab_response['status']                    = 'failure';
				$this->wmab_response['message']                   = __('Mobile number exists into the database.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['does_mobile_number_exists'] = true;
				$this->wmab_response['session_data']              = '';

				// Log Request
				wmab_log_knowband_app_request('appCheckIfContactNumberExists', serialize_block_attributes($this->wmab_response));
				$response = rest_ensure_response($this->wmab_response);
				return $response;
			}
		} else {

			if ($verify_phone_exists) {
				// Phone Number Exists
				$this->wmab_response['status']                    = 'failure';
				$this->wmab_response['message']                   = __('Mobile number exists into the database.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['does_mobile_number_exists'] = true;
				$this->wmab_response['session_data']              = '';
			} else {
				// Phone Number not Exists
				$this->wmab_response['status']                    = 'success';
				$this->wmab_response['message']                   = __('Mobile number does not exist into the database.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['does_mobile_number_exists'] = false;
				$this->wmab_response['session_data']              = '';
			}

			// Log Request
			wmab_log_knowband_app_request('appCheckIfContactNumberExists', serialize_block_attributes($this->wmab_response));
			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}
	}

	/**
	 * Function to handle appRegisterUser API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRegisterUser
	 *
	 * @param  string $signup       This parameter holds JSON string of Registration Form input
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_register_user($sign_up, $session_data, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appRegisterUser');

		$registration = false;

		$sign_up = json_decode(stripslashes($sign_up));

		if (isset($sign_up->username)) {
			$username = $sign_up->username;
		} else {
			$username = '';
		}
		$email = $sign_up->email;

		$first_name = '';
		if (!empty($sign_up->first_name)) {
			$first_name = $sign_up->first_name;
		}

		$last_name = '';
		if (!empty($sign_up->last_name)) {
			$last_name = $sign_up->last_name;
		}

		$password     = $sign_up->password;
		$phone_number = $sign_up->phone_number; // Module Upgrade V2 - Added new parameter by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		if (!empty($sign_up->mobile_number)) {
			$phone_number = $sign_up->mobile_number;
		}
		$country_code = str_replace('+', '', trim($sign_up->country_code)); // Module Upgrade V2 - Added new parameter by Harsh (hagarwal@velsof.com) on 20-Dec-2019
		if (!empty($country_code)) {
			$country_code = '+' . $country_code;
		}

		$username = 'no' === get_option('woocommerce_registration_generate_username') ? $username : '';
		$password = 'no' === get_option('woocommerce_registration_generate_password') ? $password : '';

		$validation_error = new WP_Error();
		/**
		 * Filters the registration validation errors in WooCommerce.
		 *
		 * @since 1.0.0
		 * @param WP_Error $validation_error The validation errors.
		 * @param string   $username         The user's chosen username during registration.
		 * @param string   $password         The user's chosen password during registration.
		 * @param string   $email            The user's email address during registration.
		 * @return WP_Error Modified validation errors.
		 */
		$validation_error = apply_filters('woocommerce_process_registration_errors', $validation_error, $username, $password, $email);

		if (!$validation_error->get_error_code()) {

			if (!empty($phone_number)) {
				// Check if Mobile Number already exists into the database of MAB - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				$verify_phone_exists = false;
				$getMapping          = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND country_code = %s", $phone_number, $country_code));

				if (isset($getMapping) && !empty($getMapping)) {
					// Check if Corrospodnig User Exist in the WooCommerce
					$customerInfo = new WC_Customer($getMapping->user_id);
					if (!empty($customerInfo->get_id())) {
						$verify_phone_exists = true;
					} else {
						// If customer is deleted from the woocommerce and data exist in the mab_unique_verification then delete the data from the mab_unique_verification so that phone number can be used again
						$getMapping          = $wpdb->get_row($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND country_code = %s", $phone_number, $country_code));
						$verify_phone_exists = false;
					}
				}

				if ($verify_phone_exists) {
					// Phone Number Exists
					$this->wmab_response['status']                    = 'failure';
					$this->wmab_response['message']                   = __('Mobile number exists into the database.', 'knowband-mobile-app-builder-for-woocommerce');
					$this->wmab_response['does_mobile_number_exists'] = true;
					$this->wmab_response['session_data']              = '';
					$this->wmab_response['signup_user']               = array(
						'customer_id'    => '0',
						'firstname'      => '',
						'lastname'       => '',
						'mobile_number'  => '',
						'email'          => '',
						'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
						'cart_count'     => 0,
					);
					// Log Request
					wmab_log_knowband_app_request('appRegisterUser', serialize_block_attributes($this->wmab_response));
					$response = rest_ensure_response($this->wmab_response);
					return $response;
				}
			}

			$new_customer = wc_create_new_customer(
				sanitize_email($email),
				wc_clean($username),
				$password,
				array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
				)
			);

			if (!is_wp_error($new_customer)) {

				$registration = true;

				/*
				* Create User ID and Phone Number mapping MAB DB Table for future references at the time of login
				* via Phone Number - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
				*/
				if (isset($new_customer) && !empty($new_customer)) {
					$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $new_customer));
					if (isset($getMapping) && !empty($getMapping)) {
						// Update details for existing User record
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET mobile_number = %s, country_code = %s WHERE id = %d", $phone_number, $country_code, $getMapping->id));
					} else {
						// Insert Customer and Mobile Number mapping
						$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, mobile_number = %s, country_code = %s, date_added = now()", $new_customer, $phone_number, $country_code));
					}
				}
				/**
				 * EOC - Module Upgrade V2
				 */
			}
		}

		// Check Registration process status and send response accordingly
		if (!$registration) {
			// Registration Failed
			$this->wmab_response['session_data'] = ''; // WooCommerce saves customer ID as session key in session table to refer cart and other details
			$this->wmab_response['status']       = 'failure';
			$this->wmab_response['message']      = (isset($new_customer) && !empty($new_customer)) ? strip_tags($new_customer->get_error_message()) : strip_tags($validation_error->get_error_message());
			$this->wmab_response['signup_user']  = array(
				'customer_id'    => '0',
				'firstname'      => '',
				'lastname'       => '',
				'mobile_number'  => '',
				'email'          => '',
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => 0,
			);
		} else {
			wp_clear_auth_cookie();
			wp_set_current_user($new_customer); // phpcs:ignore
			wp_set_auth_cookie($new_customer, true, true); // phpcs:ignore

			$this->set_session($new_customer);

			// Comment by Ashish.
			// Its mandatory to use these 2 line to merge the cart. Without login, Session key will is random number. If there are items into the cart (Without login) then we need to keep the same after login as well.
			// In such case, We are updating the new session key i.e. Customer ID (For logged in user, customer ID is the session key) into the previous session (Without logged in session). So current cart will be restored and to merge the cart, _woocommerce_persistent_cart_ concept will be used.
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", (int) $new_customer));
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_sessions SET session_key = %s WHERE session_key = %s", (string) $new_customer, (string) $session_data));

			// Intialze the session object & cart object again to merge the cart (Restore Cart + Current cart on login
			/**
			 * Filters the session handler class used in WooCommerce.
			 *
			 * @since 1.0.0
			 * @param string $session_class The session handler class name. Default is 'WC_Session_Handler'.
			 * @return string The filtered session handler class name.
			 */
			$session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
			WC()->session  = new $session_class(); // For Session Object
			WC()->session->init();

			WC()->cart = new WC_Cart(); // For Cart Object
			WC()->cart->get_cart();

			// GET CUSTOMER DATA
			$customer = new WC_Customer($new_customer);

			// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
			$phone_number = ''; // it will hold customer's Phone Number
			$country_code = ''; // it will hold customer's Country Code
			$getMapping   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $new_customer));
			if (isset($getMapping) && !empty($getMapping)) {
				$phone_number = $getMapping->mobile_number;
				$country_code = $getMapping->country_code;
			}

			// Registration Successful
			$this->wmab_response['status']       = 'success';
			$this->wmab_response['message']      = __('Customer successfully created.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id(); // WooCommerce saves customer ID as session key in session table to refer cart and other details
			$this->wmab_response['signup_user']  = array(
				'customer_id'    => (string) $new_customer,
				'firstname'      => $customer->get_first_name(),
				'lastname'       => $customer->get_last_name(),
				'mobile_number'  => $country_code . $phone_number,
				'email'          => $customer->get_email(),
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => (int) is_array($cart_content) ? count($cart_content) : 0,
			);
		}

		// Log Request
		wmab_log_knowband_app_request('appRegisterUser', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appForgotPassword API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appForgotPassword
	 *
	 * @param  string $email   This parameter holds customer email
	 * @param  string $version This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_forgot_password($email, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appForgotPassword');

		if (empty($email)) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Enter a username or email address.', 'knowband-mobile-app-builder-for-woocommerce');

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		} else {
			// Check on username first, as customers can use emails as usernames.
			$user_data = get_user_by('login', $email);
		}

		// If no user found, check if it login is email and lookup user based on email.
		/**
		 * Filters whether to retrieve the username based on the email address in WooCommerce.
		 *
		 * @since 1.0.0
		 * @param bool $retrieve_username Whether to retrieve the username from the email address. Default is true.
		 * @return bool Whether to retrieve the username from the email address.
		 */
		if (!$user_data && is_email($email) && apply_filters('woocommerce_get_username_from_email', true)) {
			$user_data = get_user_by('email', $email);
		}

		$errors = new WP_Error();
		/**
		 * Trigger the 'lostpassword_post' action hook after processing the lost password form submission.
		 *
		 * @since 1.0.0
		 * @param WP_Error $errors An instance of WP_Error containing any errors encountered during the password recovery process.
		 */
		do_action('lostpassword_post', $errors);

		if ($errors->get_error_code()) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __($errors->get_error_message(), 'knowband-mobile-app-builder-for-woocommerce');

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		if (!$user_data) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid username or email.', 'knowband-mobile-app-builder-for-woocommerce');

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		if (is_multisite() && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid username or email.', 'knowband-mobile-app-builder-for-woocommerce');

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		// redefining user_login ensures we return the right case in the email
		$user_login = $user_data->user_login;
		/**
		 * Trigger the 'retrieve_password' action hook to initiate the password retrieval process.
		 *
		 * @since 1.0.0
		 * @param string $user_login The user login name for password retrieval.
		 */
		do_action('retrieve_password', $suer_login);
		/**
		 * Filters whether to allow password resetting for a specific user.
		 *
		 * @since 1.0.0
		 * @param bool   $allow     Whether password reset is allowed. Default is true.
		 * @param int    $user_id   The ID of the user whose password is being reset.
		 * @param object $user_data The user data object.
		 * @return bool Whether password reset is allowed for the user.
		 */
		$allow = apply_filters('allow_password_reset', true, $user_data->ID);

		if (!$allow) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Password reset is not allowed for this user.', 'knowband-mobile-app-builder-for-woocommerce');

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		} elseif (is_wp_error($allow)) {

			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = $allow->get_error_message();

			$response = rest_ensure_response($this->wmab_response);
			return $response;
		}

		// Get password reset key (function introduced in WordPress 4.4).
		$key = get_password_reset_key($user_data);

		// Send email notification
		WC()->mailer(); // load email classes
		/**
		 * Trigger the 'woocommerce_reset_password_notification' action hook to send a password reset notification.
		 *
		 * @since 1.0.0
		 * @param string $user_login The user login name.
		 * @param string $key        The password reset key.
		 */
		do_action('woocommerce_reset_password_notification', $user_login, $key);

		$this->wmab_response['status']  = 'success';
		$this->wmab_response['message'] = 'An email with reset password link has been sent to your email address.';

		// Log Request
		wmab_log_knowband_app_request('appForgotPassword', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetRegions API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetRegions
	 *
	 * @param  string $country This parameter holds countr ISO Code
	 * @param  string $version This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_get_regions($country, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetRegions');

		$this->wmab_response['zipcode_required'] = '1';
		$this->wmab_response['dni_required']     = 0;
		$this->wmab_response['states']           = array();
		if (isset($country) && !empty($country)) {
			$wc_country = new WC_Countries();
			$states     = $wc_country->get_states($country);

			if (isset($states) && !empty($states)) {
				foreach ($states as $code => $name) {
					$this->wmab_response['states'][] = array(
						'country_id' => $country,
						'state_id'   => $code,
						'name'       => $name,
					);
				}
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appGetRegions', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appApplyVoucher API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appApplyVoucher
	 *
	 * @param  string $voucher      This parameter holds Coupon Code to be applied
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_apply_voucher($voucher, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appApplyVoucher');

		// Default Response
		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = __('Coupon could not be applied.', 'knowband-mobile-app-builder-for-woocommerce');

		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
			// Set Cart Session
			$this->set_session($cart_id);

			if (isset($voucher) && !empty($voucher)) {
				// Check if woocommerce allowed to redeem coupon
				if (get_option('woocommerce_enable_coupons') === 'yes') {
					if (WC()->cart->apply_coupon($voucher)) {
						WC()->cart->calculate_totals();
						$this->wmab_response['status']  = 'success';
						$this->wmab_response['message'] = __('Coupon applied successfully.', 'knowband-mobile-app-builder-for-woocommerce');
					}
				}
			}
		}

		// Call up to get the response data of shopping cart
		$cart_details_data = $this->app_get_cart_details('', WC()->session->get_customer_id(), true, $version);

		$wmab_response = array_merge($this->wmab_response, $cart_details_data);

		// Log Request
		wmab_log_knowband_app_request('appApplyVoucher', serialize_block_attributes($wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appRemoveVoucher API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRemoveVoucher
	 *
	 * @param  string $voucher      This parameter holds Coupon Code to be removed
	 * @param  string $session_data This parameter holds current cart ID
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_remove_voucher($voucher, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appRemoveVoucher');

		// Default Response
		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = __('Coupon could not be removed.', 'knowband-mobile-app-builder-for-woocommerce');

		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
			// Set Cart Session
			$this->set_session($cart_id);

			if (isset($voucher) && !empty($voucher)) {
				// Check if woocommerce allowed to redeem coupon
				if (get_option('woocommerce_enable_coupons') === 'yes') {
					$coupon_code = wc_get_coupon_code_by_id($voucher);
					if (WC()->cart->remove_coupon($coupon_code)) {
						WC()->cart->calculate_totals();
						$this->wmab_response['status']  = 'success';
						$this->wmab_response['message'] = __('Coupon removed successfully.', 'knowband-mobile-app-builder-for-woocommerce');
					}
				}
			}
		}

		// Call up to get the response data of shopping cart
		$cart_details_data = $this->app_get_cart_details('', WC()->session->get_customer_id(), true, $version);

		$wmab_response = array_merge($this->wmab_response, $cart_details_data);

		// Log Request
		wmab_log_knowband_app_request('appRemoveVoucher', serialize_block_attributes($wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appRemoveProduct API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appRemoveProduct
	 *
	 * @param  string $cart_products This parameter holds JSON string of Product to be removed
	 * @param  string $email         This parameter holds Customer Email
	 * @param  string $session_data  This parameter holds current cart ID
	 * @param  string $version       This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_remove_product($cart_products, $email, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appRemoveProduct');

		// Default Response
		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = __('Product could not be removed.', 'knowband-mobile-app-builder-for-woocommerce');

		/*
		$cart_products = wp_json_encode(array(
		'cart_products' => array(
		array(
		'product_id' => '125',
		'quantity' => '2',
		'id_product_attribute' => '159',
		'id_customization_field' => ''
		)
		)
		)); */

		if (isset($cart_products) && !empty($cart_products)) {
			$cart_products = json_decode(stripslashes($cart_products));
			if (isset($session_data) && !empty($session_data)) {
				$cart_id = $session_data;
			} elseif (isset($email) && !empty($email)) {
				$cart_id = email_exists($email);
			}

			// Set Cart Session
			$this->set_session($cart_id);

			$cart_content = WC()->cart->get_cart_contents();

			if (isset($cart_content) && !empty($cart_content)) {
				foreach ($cart_products->cart_products as $cart_product) {
					foreach ($cart_content as $cart_item_id => $cart_item) {
						if ($cart_item['product_id'] == $cart_product->product_id) {
							if (isset($cart_product->id_product_attribute) && $cart_product->id_product_attribute == $cart_item['variation_id']) {
								if (WC()->cart->remove_cart_item($cart_item_id)) {
									// if (WC()->cart->set_quantity($cart_item['key'], '000', true)) { //This is used to remove product from cart as actual function which removes product from cart is not working at the moment - done by Harsh
									// WC()->cart->calculate_totals();
									// WC()->session->set( 'cart', WC()->session->get( 'cart' ) );
									$this->wmab_response['status']  = 'success';
									$this->wmab_response['message'] = __('Product successfully removed.', 'knowband-mobile-app-builder-for-woocommerce');
									break;
								}
							}
						}
					}
				}
			}
		}

		// Call up to get the response data of shopping cart
		$cart_details_data = $this->app_get_cart_details('', WC()->session->get_customer_id(), true, $version);

		$wmab_response = array_merge($this->wmab_response, $cart_details_data);

		// Log Request
		wmab_log_knowband_app_request('appRemoveProduct', serialize_block_attributes($wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appUpdateCartQuantity API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateCartQuantity
	 *
	 * @param  string $cate_products This parameter holds JSON string of cart products
	 * @param  string $email         This parameter holds customer email
	 * @param  string $session_data  This parameter holds customer cart ID
	 * @param  string $version       This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_update_cart_quantity($cart_products, $email, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appUpdateCartQuantity');

		// Default Response
		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = __('Product could not be updated.', 'knowband-mobile-app-builder-for-woocommerce');

		/*
		$cart_products = wp_json_encode(array(
		'cart_products' => array(
		array(
		'product_id' => '125',
		'quantity' => '2',
		'id_product_attribute' => '159',
		'id_customization_field' => ''
		)
		)
		)); */

		if (isset($cart_products) && !empty($cart_products)) {
			$cart_products = json_decode(stripslashes($cart_products));
			if (isset($session_data) && !empty($session_data)) {
				$cart_id = $session_data;
			} elseif (isset($email) && !empty($email)) {
				$cart_id = email_exists($email);
			}

			// Set Cart Session
			$this->set_session($cart_id);

			$cart_content = WC()->cart->get_cart_contents();

			if (isset($cart_content) && !empty($cart_content)) {
				foreach ($cart_products->cart_products as $cart_product) {
					// print_r($cart_product); die;
					foreach ($cart_content as $cart_content) {
						if ($cart_content['product_id'] == $cart_product->product_id) {
							if (isset($cart_product->id_product_attribute) && $cart_product->id_product_attribute == $cart_content['variation_id']) {
								/**
								 * CHeck if product attribute exist or not, if exist then cretae the object wrt to attribute otherwise create object wrt product so that while updating the stock, specific product object quantity is checked
								 *
								 * @date      1-02-2023
								 * @commenter Vishal Goyal
								 */
								if (0 != $cart_product->id_product_attribute) {
									$product_to_update = wc_get_product($cart_product->id_product_attribute);
								} else {
									$product_to_update = wc_get_product($cart_product->product_id);
								}
								if (($product_to_update->managing_stock() && $product_to_update->get_stock_quantity() >= $cart_product->quantity) || (!$product_to_update->managing_stock() && $product_to_update->is_in_stock())) {
									if (WC()->cart->set_quantity($cart_content['key'], $cart_product->quantity, true)) {
										$this->wmab_response['status']  = 'success';
										$this->wmab_response['message'] = __('Product successfully updated.', 'knowband-mobile-app-builder-for-woocommerce');
									}
								}
							}
						}
					}
				}
			}
		}

		// Call up to get the response data of shopping cart
		$cart_details_data = $this->app_get_cart_details('', WC()->session->get_customer_id(), true, $version);

		$wmab_response = array_merge($this->wmab_response, $cart_details_data);

		// Log Request
		wmab_log_knowband_app_request('appUpdateCartQuantity', serialize_block_attributes($wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetRegistrationForm API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetRegistrationForm
	 *
	 * @param  string $version This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_get_registration_form($version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetRegistrationForm');

		if (get_option('woocommerce_registration_generate_username') === 'no') {
			if (get_option('woocommerce_registration_generate_password') === 'no') {
				$this->wmab_response['signup_details'] = array(
					'username' => '',
					'password' => '',
				);
			} else {
				$this->wmab_response['signup_details'] = array(
					'username' => '',
				);
			}
		} elseif (get_option('woocommerce_registration_generate_password') === 'no') {
			$this->wmab_response['signup_details'] = array(
				'email'    => '',
				'password' => '',
			);
		} else {
			$this->wmab_response['signup_details'] = array(
				'email' => '',
			);
		}

		// Add a parameter to tell mobile app that password field should display or not
		$this->wmab_response['password'] = (get_option('woocommerce_registration_generate_password') === 'no') ? 'yes' : 'no';
		$this->wmab_response['username'] = (get_option('woocommerce_registration_generate_username') === 'no') ? 'yes' : 'no';
		$this->wmab_response['email']    = 'yes';

		// Log Request
		wmab_log_knowband_app_request('appGetRegistrationForm', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appUpdateProfile API request - Module Upgrade V2 - changes added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdateProfile
	 *
	 * @param  string $email         This parameter holds Customer Email
	 * @param  string $personal_info This parameter holds Customer profile information in JSON format
	 * @param  string $version       This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_update_profile($email, $personal_info, $version)
	{
		global $wpdb;
		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appUpdateProfile');

		/*
		$personal_info = wp_json_encode(array(
		'first_name' => 'Harsh',
		'last_name' => 'Agarwal',
		'password' => 'Admin@123',
		'new_password' => 'Admin@123',
		'cnfrm_password' => 'Admin@123',
		'mobile_number' => '7676767666',
		'country_code' => '+91'
		)); */

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			if (isset($customer_id) && !empty($customer_id)) {
				$personal_info = json_decode(stripslashes($personal_info));
				// Adding '+' symbol in Country Code
				$personal_info->country_code = str_replace('+', '', trim($personal_info->country_code));
				if (!empty($personal_info->country_code)) {
					$personal_info->country_code = '+' . $personal_info->country_code;
				}

				$customer = new WC_Customer($customer_id);

				// Validate customer login
				$creds = array(
					'user_login'    => trim($email),
					'user_password' => $personal_info->password,
					'remember'      => 1, // Keep it by default 1
				);

				/**
				 * Hook into the 'woocommerce_login_credentials' filter to modify the login credentials before signing in.
				 *
				 * @since 1.0.0
				 * @param array $credentials The login credentials.
				 * @return array Modified login credentials.
				 */
				$user = wp_signon(apply_filters('woocommerce_login_credentials', $creds), is_ssl());

				if (is_wp_error($user)) {
					$this->wmab_response['status']       = 'failure';
					$this->wmab_response['message']      = __('Invalid email or wrong password.', 'knowband-mobile-app-builder-for-woocommerce');
					$this->wmab_response['session_data'] = '';
					$this->wmab_response['user_details'] = array(
						'customer_id'    => '0',
						'firstname'      => '',
						'lastname'       => '',
						'mobile_number'  => '',
						'email'          => '',
						'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
						'cart_count'     => 0,
					);
				} else {
					// Check if MObile Number already exists into the database of MAB - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
					// $this->app_check_if_contact_number_exists($personal_info->mobile_number, $personal_info->country_code, true, $customer_id, $version);
					// Update Customer First Name
					if (isset($personal_info->first_name) && !empty($personal_info->first_name)) {
						$customer->set_first_name($personal_info->first_name);
					}

					// Update Customer Last Name
					if (isset($personal_info->last_name) && !empty($personal_info->last_name)) {
						$customer->set_last_name($personal_info->last_name);
					}

					// Update Customer Account Password
					if (isset($personal_info->new_password) && !empty($personal_info->new_password)) {
						$customer->set_password($personal_info->new_password);
					}

					$customer->save();

					// Update Customer Phone Number in MAB DB Tables - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
					$getMapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %d", $customer_id));
					if (isset($getMapping) && !empty($getMapping)) {
						// Update Unique ID for existing User record
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_unique_verification SET mobile_number = %s, country_code = %s WHERE id = %d", $personal_info->mobile_number, $personal_info->country_code, $getMapping->id));
					} else {
						// Insert Customer and Unique ID mapping
						$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_unique_verification SET id = '', user_id = %d, mobile_number = %s, country_code = %s, date_added = now()", $customer_id, $personal_info->mobile_number, $personal_info->country_code));
					}
					// EOC - Module Upgrade V2
					// Get the Phone Number and Country Code of Customer based on Customer ID - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
					$phone_number = $personal_info->mobile_number; // it will hold customer's Phone Number
					$country_code = $personal_info->country_code; // it will hold customer's Country Code

					$this->wmab_response['status']       = 'success';
					$this->wmab_response['message']      = __('Customer profile updated successfully.', 'knowband-mobile-app-builder-for-woocommerce');
					$this->wmab_response['session_data'] = '';
					$this->wmab_response['user_details'] = array(
						'customer_id'    => (string) $customer_id,
						'firstname'      => $customer->get_first_name(),
						'lastname'       => $customer->get_last_name(),
						'mobile_number'  => $country_code . $phone_number,
						'email'          => $customer->get_email(),
						'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
						'cart_count'     => (int) WC()->cart->get_cart_contents_count(),
					);
				}
			} else {
				$this->wmab_response['status']       = 'failure';
				$this->wmab_response['message']      = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
				$this->wmab_response['session_data'] = '';
				$this->wmab_response['user_details'] = array(
					'customer_id'    => '0',
					'firstname'      => '',
					'lastname'       => '',
					'mobile_number'  => '',
					'email'          => '',
					'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
					'cart_count'     => 0,
				);
			}
		} else {
			$this->wmab_response['status']       = 'failure';
			$this->wmab_response['message']      = __('Invalid Email Address.', 'knowband-mobile-app-builder-for-woocommerce');
			$this->wmab_response['session_data'] = '';
			$this->wmab_response['user_details'] = array(
				'customer_id'    => '0',
				'firstname'      => '',
				'lastname'       => '',
				'mobile_number'  => '',
				'email'          => '',
				'wishlist_count' => 0, // Set to 0 as wishlist fubnctionality is not available in WooCommerce by default
				'cart_count'     => 0,
			);
		}

		// Log Request
		wmab_log_knowband_app_request('appUpdateProfile', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appUpdatePassword API request - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appUpdatePassword
	 *
	 * @param  string $phone_number This parameter holds Customer Mobile Number
	 * @param  string $country_code This parameter holds Customer Country Code
	 * @param  string $new_password This parameter holds Customer New Password
	 * @param  string $session_data This parameter holds Session Data
	 * @param  string $version      This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_update_password($phone_number, $country_code, $new_password, $session_data, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appUpdatePassword');

		if (isset($phone_number) && !empty($phone_number)) {
			// Get Customer ID by Phone Number
			$customer_id = '';
			$getMapping  = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_unique_verification WHERE mobile_number = %s AND country_code = %s", $phone_number, $country_code));
			if (isset($getMapping) && !empty($getMapping)) {
				$customer_id = $getMapping->user_id; // Set Customer ID here based on Phone Number
			}

			if (isset($customer_id) && !empty($customer_id)) {

				$customer = new WC_Customer($customer_id);

				// Checking if the customer exist in the WooCommerce
				if (!empty($customer->get_id())) {
					// Update Customer Account Password
					if (isset($new_password) && !empty($new_password)) {
						$customer->set_password($new_password);
					}
					$customer->save();

					$this->wmab_response['status']  = 'success';
					$this->wmab_response['message'] = __('The password has been changed successfully.', 'knowband-mobile-app-builder-for-woocommerce');
				} else {
					$this->wmab_response['status']  = 'failure';
					$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
				}
			} else {
				$this->wmab_response['status']  = 'failure';
				$this->wmab_response['message'] = __('Customer does not exist.', 'knowband-mobile-app-builder-for-woocommerce');
			}
		} else {
			$this->wmab_response['status']  = 'failure';
			$this->wmab_response['message'] = __('Invalid Phone Number.', 'knowband-mobile-app-builder-for-woocommerce');
		}

		$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();

		// Log Request
		wmab_log_knowband_app_request('appUpdatePassword', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appDeleteUser API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appLogin
	 *
	 * @param  string $email   This parameter holds Customer Email
	 * @param  string $version This parameter holds API version to verify during API call
	 *  Knowband <support@knowband.com>
	 */
	public function app_delete_user($email, $version)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appDeleteUser');

		$this->wmab_response['status']  = 'failure';
		$this->wmab_response['message'] = '';

		if (isset($email) && !empty($email)) {
			$customer_id = email_exists($email);

			if (isset($customer_id) && !empty($customer_id)) {
				include_once ABSPATH . 'wp-admin/includes/user.php';
				$msg = wp_delete_user($customer_id);
				$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_unique_verification WHERE user_id = %s", $customer_id));
				if ($msg) {
					$this->wmab_response['status']  = 'success';
					$this->wmab_response['message'] = __('User deleted successfully.', 'knowband-mobile-app-builder-for-woocommerce');
				} else {
					$this->wmab_response['status']  = 'failure';
					$this->wmab_response['message'] = __('User delete failed.', 'knowband-mobile-app-builder-for-woocommerce');
				}
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appDeleteUser', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}
}
