<?php

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly
}

define('KB_WMAB_API_VERSION', '2.0');

/**
 * Class - WmabProduct
 *
 * This class contains constructor and other methods which are actually related to Product Page actions
 *
 * @version v2.0
 * @Date    10-Jun-2022
 */
class WmabProduct
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

	/** String A private variable of class which holds current session expiration for Cart
	 *
	 * @var    string A private variable of class which holds current session expiration for Cart
	 */
	private $wmab_session_expiration = '';

	/** String A private variable of class which holds current session expiring for Cart
	 *
	 * @var    string A private variable of class which holds current session expiring for Cart
	 */
	private $wmab_session_expiring = '';

	/** String A private variable of class which holds cookie values for Cart
	 *
	 * @var    string A private variable of class which holds cookie values for Cart
	 */
	private $wmab_cookie = '';

	/** String A private variable of class which holds woocommerce version number
	 *
	 * @var    string A private variable of class which holds woocommerce version number
	 */
	private $wmab_wc_version = '';

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
		 * Calculates the expiration time for the WooCommerce session.
		 *
		 * @param int $expiration The default expiration time for the session.
		 * @return int The filtered expiration time for the session.
		 * @since 1.0.0
		 */
		$this->wmab_cookie = apply_filters('woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH);
		$to_hash           = $cart_id . '|' . $this->wmab_session_expiration;
		$cookie_hash       = hash_hmac('md5', $to_hash, wp_hash($to_hash));
		$cookie_value      = $cart_id . '||' . $this->wmab_session_expiration . '||' . $this->wmab_session_expiring . '||' . $cookie_hash;

		/**
		 * Calculates the expiration time for the WooCommerce session.
		 *
		 * @param int $expiration The default expiration time for the session.
		 * @return int The filtered expiration time for the session.
		 * @since 1.0.0
		 */
		wc_setcookie($this->wmab_cookie, $cookie_value, $this->wmab_session_expiration, apply_filters('wc_session_use_secure_cookie', false));
		$_COOKIE[$this->wmab_cookie] = $cookie_value;
	}

	/**
	 * Function to handle appGetProductDetails API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetProductDetails
	 *
	 * @param  int    $product_id This parameter holds Product ID
	 * @param  string $version    This parameter holds API version to verify during API call
	 */
	public function app_get_product_details($product_id, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetProductDetails');

		// Get Product details
		$product = wc_get_product($product_id); // Replaced deprecated function get_product with wc_get_product on 04-Oct-2019
		// Get product reviews
		$reviews_data = array();
		$args         = array(
			'post_type' => 'product',
			'post_id'   => $product_id,
			'status'    => 'approve',
		);
		$reviews      = get_comments($args);

		$total_reviews      = 0;
		$reviews_rating_sum = 0;
		foreach ($reviews as $review) {
			++$total_reviews;
			$meta_reviews        = get_comment_meta($review->comment_ID, 'rating', true);
			$reviews_rating_sum += !empty($meta_reviews) ? $meta_reviews : 0;
		}
		if (0 != $total_reviews) {
			$reviews_average = $reviews_rating_sum / $total_reviews;
		} else {
			$reviews_average = 0;
		}

		$is_product_new = 0;
		if (strtotime($product->get_date_created()) >= strtotime($this->wmab_plugin_settings['general']['product_new_date'])) {
			$is_product_new = 1;
		}

		$product_combinations  = array();
		$option_value_list_v   = array();
		$quantity_by_variation = 0;
		// BOC neeraj.kumar@velsof.com Added Checked to matched product attribute with variation or not
		$variation_ids_array = array();
		$product_type        = $product->get_type(); // Get the product type correctly instead of getting it directly through $product->product_type on 04-Oct-2019
		if ('variable' == $product_type) { // For Variable Product
			$available_variations = $product->get_available_variations();
			if (isset($available_variations) && !empty($available_variations)) {
				foreach ($available_variations as $available_variation) {
					if ($available_variation['variation_is_active']) {
						$variation_id     = $available_variation['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product = new WC_Product_Variation($variation_id);
						$regular_price    = $variable_product->get_regular_price();
						$sale_price       = $variable_product->get_sale_price();

						$variation_attributes = $variable_product->get_variation_attributes();

						// Variation Attributes
						$variation_attribute_id = array();

						if (!empty($variation_attributes)) {

							foreach ($variation_attributes as $key => $value) {
								$option_value_id = get_term_by('slug', strtolower($value), str_replace('attribute_', '', $key));

								if (!empty($option_value_id->term_id)) {
									$variation_attribute_id[] = $option_value_id->term_id;
									// $option_value_list_v[str_replace("attribute_", "", $key)][] = array(
									// 'id' => $option_value_id->term_id,
									// 'value' => $value
									// );
									// Push variation_attribute id into custom array :neeraj.kumar@velsof.com
									array_push($variation_ids_array, $option_value_id->term_id);
								} else {
									$variation_attribute_id[] = $value;
									// Push variation_attribute id into custom array :neeraj.kumar@velsof.com
									array_push($variation_ids_array, $value);
								}
							}
						}

						$variation_attribute = '';

						if (isset($available_variation['attributes']) && !empty($variation_attribute_id)) {
							foreach ($available_variation['attributes'] as $key => $value) {
								$variation_attribute = $value;
							}
						}

						/**
						 * Added below code to sort the attribute ID in ascending order, as in web end we will first sort the attribute data and then add the same to cart
						 *
						 * @date      1-02-2023
						 * @commenter Vishal Goyal
						 */
						sort($variation_attribute_id);

						// if (isset($variation_attribute_id) && !empty($variation_attribute_id)) {
						$product_combinations[] = array(
							'id_product_attribute' => $available_variation['variation_id'],
							'quantity'             => !empty($variable_product->get_stock_quantity()) ? $variable_product->get_stock_quantity() : 0,
							'price'                => !empty($sale_price) ? html_entity_decode(
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
							) : html_entity_decode(
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
							'minimal_quantity'     => $available_variation['min_qty'],
							'combination_code'     => (string) implode('_', $variation_attribute_id),
						);
						// }
						// To get highest quantity from variations
						if ($quantity_by_variation < $available_variation['max_qty']) {
							$quantity_by_variation = $available_variation['max_qty'];
						}
					}
				}
			}
			$variation_id     = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
			$variable_product = new WC_Product_Variation($variation_id);
			$regular_price    = $variable_product->get_regular_price();
			$sale_price       = $variable_product->get_sale_price();

			/**
			 * Updated the condition to check whether the product is on sale or not
			 * VGfeb2023 sale-issue
			 *
			 * @date   01-03-2023
			 */
			if ($variable_product->is_on_sale()) {
				$discount_percentage = floor(number_format((($regular_price - $sale_price) / $regular_price) * 100, 2));
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
		// Product Image
		$product_image = get_the_post_thumbnail_url($product_id, 'full');

		// Product Info
		$product_info = array(
			array(
				'name'  => __('SKU', 'knowband-mobile-app-builder-for-woocommerce'),
				'value' => $product->get_sku(),
			),
		);

		// Product Options/Attributes
		$product_options = $product->get_attributes();
		$attributes      = array();

		$set_product_combination = false;
		if (!isset($product_combinations) || count($product_combinations) == 0) {
			$product_combinations    = array();
			$set_product_combination = true;
		}

		if ('variable' == $product_type) { // For Variable Product
			if (isset($product_options) && !empty($product_options)) {
				foreach ($product_options as $product_option) {
					$options = (array) $product_option;
					foreach ($options as $option) {

						$option_value_list = array();

						for ($i = 0; $i < count($option['options']); $i++) {
							$option_value = get_term_by('term_taxonomy_id', $option['options'][$i]);
							// Extra checked added id are present in variation array
							if (in_array($option['options'][$i], $variation_ids_array)) {
								$option_value_list[] = array(
									'id'       => (string) $option['options'][$i],
									/**
									 * Added strval() function in case of option name doesnt exist, as if name doesnt exist in that case, integer values is passed which cause app to to not load product page
									 *
									 * @date      1-02-2023
									 * @commenter Vishal Goyal
									 */
									'value'    => !empty($option_value->name) && is_int($option['options'][$i]) ? $option_value->name : strval($option['options'][$i]),
									'required' => '1', // Hardcode true as all the options are required and key is expected in the app
								);
							}
						}
						// BOC neeraj.kumar@velsof.com only add option when option_value_list have some value
						if (isset($option_value_list) && !empty($option_value_list)) {
							$attributes[] = array(
								'id'              => (string) $option['id'],
								'title'           => ucwords(str_replace('pa_', '', $option['name'])),
								'is_color_option' => 0,
								'items'           => $option_value_list,
								'required'        => '1',
							);
						}
					}
				}
			}
		}

		// BOC - Changes added by Harsh on 04-Sep-2019 to get all product images (added in product gallery)
		$defult_image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image

		$product_images          = array();
		$product_images[]['src'] = !empty($product_image) ? $product_image : $defult_image_path;
		$attachment_ids          = $product->get_gallery_image_ids(); // Replaced the deprecated function get_gallery_attachment_ids with get_gallery_image_ids on 04-Oct-2019
		if (isset($attachment_ids) && !empty($attachment_ids)) {
			foreach ($attachment_ids as $attachment_id) {
				if (!empty(wp_get_attachment_url($attachment_id))) {
					$product_images[]['src'] = wp_get_attachment_url($attachment_id);
				}
			}
		}
		// EOC
		// Changes added to pass default quantity if stock is managing globally instead of product based by Harsh on 04-Sep-2019
		$default_quantity = 0;
		if ($product->get_stock_status() === 'instock') {
			$default_quantity = 1000; // set to 1000 so that quantity stepper can work
		}

		$has_attributes = '0';
		if ('variable' == $product->get_type()) { // For Variable Product
			$has_attributes = '1';
		}

		$product_ids = $product->get_upsell_ids('edit');
		// Check cart quantity
		$cart_quantity = 0;
		// Set here your product ID
		$targeted_id = (string) $product_id;

		// set session data
		$cart_id = 0;
		if (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		} elseif (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		}

		if (!empty($cart_id)) {

			$session_value = $wpdb->get_var($wpdb->prepare("SELECT session_value FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s", $cart_id));
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

		$related_products = $this->getRelatedProducts($product_ids, $cart_id);

		$this->wmab_response['product'] = array(
			'id_product'                => $product_id,
			'name'                      => $product->get_name(),
			'available_for_order'       => $product->get_stock_status() === 'instock' ? '1' : '0',
			'show_price'                => !empty($this->wmab_plugin_settings['general']['show_price']) ? '1' : '0',
			'new_products'              => $is_product_new,
			'on_sale_products'          => $discount_percentage ? 1 : 0,
			'quantity'                  => ($product->get_stock_quantity() != null) ? (string) $product->get_stock_quantity() : (!empty($quantity_by_variation) ? (string) $quantity_by_variation : (string) $default_quantity), // Sending quantity 1 in case stock is managing globally instead of at product level - added by Harsh on 04-Sep-2019
			'minimal_quantity'          => '1',
			'allow_out_of_stock'        => 0,
			'discount_percentage'       => !empty($discount_percentage) ? ((string) $discount_percentage . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
			'price'                     => html_entity_decode(
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
			'discount_price'            => html_entity_decode(
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
			'is_in_wishlist'            => false,
			'has_attributes'            => $has_attributes,
			'cart_quantity'             => $cart_quantity,
			'product_url'               => get_permalink($product_id),
			'images'                    => $product_images,
			'combinations'              => $product_combinations,
			'options'                   => $attributes,
			'description'               => html_entity_decode($product->get_description(), ENT_QUOTES),
			'product_info'              => $product_info,
			'accessories'               => array(
				'has_accessories'   => '0',
				'accessories_items' => array(),
			),
			'customization_fields'      => array(
				'is_customizable'    => '0',
				'customizable_items' => array(),
			),
			'pack_products'             => array(
				'is_pack'    => '0',
				'pack_items' => array(),
			),
			'has_file_customization'    => '0',
			'customization_message'     => '',
			'seller_info'               => array(),
			'product_youtube_url'       => '',
			'product_attachments_array' => array(),
			'display_read_reviews'      => '1',
			'display_write_reviews'     => '1',
			'number_of_reviews'         => (string) $total_reviews,
			'averagecomments'           => (string) number_format($reviews_average, 2),
			'related_products'          => $related_products,
		);

		if (isset($this->wmab_plugin_settings['general']['display_short_desc']) && $this->wmab_plugin_settings['general']['display_short_desc']) {
			$this->wmab_response['product']['short_description'] = $product->get_short_description();
		}

		$this->wmab_response['fproducts'] = ''; // No use of the same. Added the same in 2nd Apr 2022 to keep the app code same.
		// Log Request
		wmab_log_knowband_app_request('appGetProductDetails', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetProductReviews API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetProductReviews
	 *
	 * @param  int    $product_id This parameter holds Product ID
	 * @param  string $version    This parameter holds API version to verify during API call
	 */
	public function app_get_product_reviews($product_id, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetProductDetails');

		$this->wmab_response['status'] = 'success';

		// Get product reviews
		$this->wmab_response['reviews']             = array();
		$args                                       = array(
			'post_type' => 'product',
			'post_id'   => $product_id,
			'status'    => 'approve',
		);
		$reviews                                    = get_comments($args);
		$total_reviews                              = 0;
		$reviews_rating_sum                         = 0;
		$this->wmab_response['reviews']['comments'] = array();
		foreach ($reviews as $review) {
			++$total_reviews;
			$meta_reviews                                 = get_comment_meta($review->comment_ID, 'rating', true);
			$reviews_rating_sum                          += $meta_reviews;
			$this->wmab_response['reviews']['comments'][] = array(
				'id_product_comment' => $review->comment_ID,
				'customer_name'      => $review->comment_author,
				'date_add'           => gmdate('Y-m-d H:i:s', strtotime($review->comment_date)),
				'content'            => $review->comment_content,
				'grade'              => !empty($meta_reviews) ? $meta_reviews : '0',
			);
		}
		$this->wmab_response['reviews']['number_of_reviews'] = (string) $total_reviews;
		$this->wmab_response['reviews']['averagecomments']   = !empty($total_reviews) ? number_format($reviews_rating_sum / $total_reviews, 2) : 0;

		// Log Request
		wmab_log_knowband_app_request('appGetProductReviews', serialize_block_attributes($this->wmab_response));

		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	private function getRelatedProducts($product_ids, $cart_id)
	{

		global $wpdb;

		$related_products_content = array();

		if (!empty($product_ids)) {
			foreach ($product_ids as $product_id) {

				// Check Item in cart
				$cart_quantity = 0;
				if (!empty($cart_id)) {
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
							if ($cart_item['product_id'] == $product_id && !empty($cart_item['quantity'])) {
								$cart_quantity = $cart_item['quantity'];
								break; // stop the loop if product is found
							}
						}
					}
				}

				$product = wc_get_product($product_id);
				if (empty($product)) {
					continue;
				}
				$wmab_settings = get_option('wmab_settings');
				if (isset($wmab_settings) && !empty($wmab_settings)) {
					$wmab_plugin_settings = unserialize($wmab_settings);
				}
				if ($product->is_visible()) {

					if ('variable' == $product->get_type()) { // For Variable Product
						$has_attributes       = '1';
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

					// Check if image exists otherwise send dummy image - changes made by Saurav (25-Jun-2020)
					$product_thumbnail = get_the_post_thumbnail_url($product_id);
					if (isset($product_thumbnail) && !empty($product_thumbnail)) {
						$image_path = $product_thumbnail;
					} else {
						$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
					}
					$has_attributes = '0';
					if ('variable' == $product->get_type()) { // For Variable Product
						$has_attributes = '1';
					}

					$is_product_new = 0;
					if (strtotime($product->get_date_created()) >= strtotime(str_replace('/', '-', $wmab_plugin_settings['general']['product_new_date']))) {
						$is_product_new = 1;
					}
					if ('variable' == $product->get_type()) { // For Variable Product
						$available_variations = $product->get_available_variations();
						$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
						$variable_product     = new WC_Product_Variation($variation_id);
						$regular_price        = $variable_product->get_regular_price();
						$sale_price           = $variable_product->get_sale_price();

						/**
						Updated the condition to check whether the product is on sale or not
						VGfeb2023 sale-issue

						@date   01-03-2023
						 */
						if ($variable_product->is_on_sale()) {
							$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
						} else {
							$discount_percentage = 0;
						}
					} else {
						// For Simple Products
						/**
						Updated the code ot check whether the product is on sale or not, as ear;lier we are using multiple condition to check the same, which can be done by just calling a simple function
						VGfeb2023 sale-issue

						@date   01-03-2023
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

					$related_products_content[] = array(
						'id'                  => (string) $product_id,
						'name'                => $product->get_name(),
						'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
						'on_sale_products'    => $discount_percentage ? 1 : 0,
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
						'image_contentMode'   => 'scaleAspectFill',
						'is_in_wishlist'      => false,
						'has_attributes'      => $has_attributes,
						'cart_quantity'       => $cart_quantity,
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
						'discount_percentage' => (string) $discount_percentage,
						'ClickActivityName'   => 'ProductActivity',
						'new_products'        => (int) $is_product_new,
					);
				}
			}
		}
		return $related_products_content;
	}
}
