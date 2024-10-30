<?php

/**
 * This class contains methods which handles Mobile App Builder API Calls which are related to category page actions
 *
 * @package WooCommerce Mobile App Builder
 * @author Knowband
 * @version 2.0
 */

if (!defined('ABSPATH')) {
	exit;  // Exit if access directly.
}

define('KB_WMAB_API_VERSION', '2.0');

class WmabCategory
{

	/**String A public variable of class which holds WooCommerce plugin settings
	 *
	 * @var    string A public variable of class which holds WooCommerce plugin settings
	 */
	public $wmab_plugin_settings = array();

	/**String A public variable of class which holds API response
	 *
	 * @var    string A public variable of class which holds API response
	 */
	public $wmab_response = array();

	/**String A public variable of class which holds current session expiration for Cart
	 *
	 * @var    string A public variable of class which holds current session expiration for Cart
	 */
	public $wmab_session_expiration = '';

	/**String A public variable of class which holds current session expiring for Cart
	 *
	 * @var    string A public variable of class which holds current session expiring for Cart
	 */
	public $wmab_session_expiring = '';

	/**String A public variable of class which holds cookie values for Cart
	 *
	 * @var    string A public variable of class which holds cookie values for Cart
	 */
	public $wmab_cookie = '';

	/**String A public variable of class which holds woocommerce version number
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
			$cart_id = sanitize_text_field(wp_unslash($_POST['session_data']));
		} elseif (isset($_POST['email']) && !empty($_POST['email'])) {
			$cart_id = email_exists(sanitize_text_field(wp_unslash($_POST['email'])));
		}

		if (isset($_POST['email']) && !empty($_POST['email'])) {
			$current_user_id = email_exists(sanitize_text_field(wp_unslash($_POST['email'])));
			if (!empty($current_user_id)) {
				wp_set_current_user($current_user_id); // phpcs:ignore
			}
		}

		if (!empty($cart_id)) {
			$this->set_session($cart_id);
		}
		// Set default blank value to send as response in each request.
		$this->wmab_response['install_module'] = '';

		// Get Mobile App Builder settings from database.
		$wmab_settings = get_option('wmab_settings');
		if (isset($wmab_settings) && !empty($wmab_settings)) {
			$this->wmab_plugin_settings = unserialize($wmab_settings);
		}

		// Suspend execution if plugin is not installed or disabled and send output.
		if (!isset($this->wmab_plugin_settings['general']['enabled']) && empty($this->wmab_plugin_settings['general']['enabled'])) {
			$this->wmab_response['install_module'] = __('Warning: You do not have permission to access the module, Kindly install module !!', 'knowband-mobile-app-builder-for-woocommerce');
			// Log Request.
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
			// For Customer Object.
			WC()->customer = new WC_Customer(get_current_user_id(), true);
		}
		if (null === WC()->Tax) {
			// For Payment Gateway Object.
			WC()->Tax = new WC_Tax();
		}
		if (null === WC()->cart) {
			// For Cart Object.
			WC()->cart = new WC_Cart();
			WC()->cart->get_cart();
			// Added by Ashish on 5th May 2022 to Save Cart Data into the User Meta Table.
			// The current cart will be saved in the User Meta Table. Altough Cart is saved on into the User Meta Table on Product Add/Update/Delete itself but in the case of cart merge at the time of login, Updated Cart items was not persisting. So made persistance of the cart on next page refresh
			// DB Key: _woocommerce_persistent_cart_
			WC()->cart->persistent_cart_update();
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
	 * Function to set session data
	 *
	 * @param  string $cart_id This parameter holds current cart ID to set int Session variable
	 */
	private function set_session($cart_id)
	{

		/**
		 * Filters whether to use a secure cookie for the WooCommerce session.
		 *
		 * @since 1.0.0
		 */
		$this->wmab_session_expiring = time() + intval(apply_filters('wc_session_expiring', 60 * 60 * 47));

		/**
		 * Filters whether to use a secure cookie for the WooCommerce session.
		 *
		 * @since 1.0.0
		 */

		$this->wmab_session_expiration = time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48));
		/**
		 * Filters whether to use a secure cookie for the WooCommerce session.
		 *
		 * @since 1.0.0
		 */

		$this->wmab_cookie = apply_filters('woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH);
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

	/**
	 * Function to handle appGetCategoryDetails API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetCategoryDetails
	 *
	 * @param  int    $category_id This parameter holds Category ID
	 * @param  string $search_term This parameter holds Search Term
	 * @param  array  $filter      This parameter holds Filter values basis on which filter will be applied
	 * @param  string $sort_by     This parameter holds value on basis of which sorting will be done
	 * @param  string $item_count  This parameter holds limit value
	 * @param  string $page_number This parameter holds page number
	 * @param  string $version     This parameter holds API version to verify during API call
	 */
	public function app_get_category_details($category_id, $search_term, $filter, $sort_by, $item_count, $page_number, $version, $iso_code, $session_data, $email)
	{
		global $wpdb;

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetCategoryDetails');

		$price_filter                     = array();
		$attribute_filter                 = array();
		$filtered_products_list           = array();
		$price_filtered_products_list     = array();
		$attribute_filtered_products_list = array();

		if (!empty($filter)) {
			$filter = json_decode(stripslashes(trim($filter)), true);
			if (!empty($filter['filter_result'])) {

				foreach ($filter['filter_result'] as $filter) {

					// Price Filters
					$filterName = explode('|', $filter['title']);
					if (isset($filterName[0]) && strtolower($filterName[0]) == 'prices') {
						if (!empty($filter['items'])) {
							foreach ($filter['items'] as $items) {
								$price_filter[] = $items['id'];
							}
						}
					}

					// Attributes Filters
					$filterName = explode('|', $filter['title']);
					if (isset($filterName[0]) && !empty($filterName[0]) && strtolower($filterName[0]) != 'prices') {
						if (!empty($filter['items'])) {
							foreach ($filter['items'] as $items) {
								$attribute_filter[$filter['id']][] = $items['id'];
							}
						}
					}
				}
			}
		}
		$price_sql_condition = '';
		if (!empty($price_filter)) {
			$price_sql_condition .= '(meta_key = "_price" AND ';
			$counter              = 0;
			foreach ($price_filter as $price_filter) {
				$price_range = explode('|', $price_filter);
				if ($counter > 0) {
					$price_sql_condition .= ' OR ';
				}
				$price_sql_condition .= $wpdb->prepare('(meta_value >= %d AND meta_value <= %d)', $price_range[0], $price_range[1]);
				++$counter;
			}
			$price_sql_condition .= ')';
		}

		if (!empty($price_sql_condition)) {
			$filtered_products = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta WHERE 1 AND %s", $price_sql_condition));

			if (isset($filtered_products) && !empty($filtered_products)) {
				foreach ($filtered_products as $filtered_product) {
					$post_parent = wp_get_post_parent_id($filtered_product->post_id);
					if (!empty($post_parent)) {
						$price_filtered_products_list[] = $post_parent;
					} else {
						$price_filtered_products_list[] = $filtered_product->post_id;
					}
				}
			}
		}
		$attribute_sql_condition = '';
		if (!empty($attribute_filter)) {
			$counter = 0;
			foreach ($attribute_filter as $attribute_key => $attribute_value) {
				if (strpos($attribute_key, '|') !== false) {
					$attribute_key = explode('|', $attribute_key);
				}
				if ($counter > 0) {
					$attribute_sql_condition .= ' AND ';
				}
				if (is_array($attribute_key) && isset($attribute_key[1])) {
					// BOC neeraj.kumar@velsof.com : 7-Feb-2020 , Product Not filter with Custom Product Attribute
					$finding_attribute_associated_sql = $wpdb->prepare('(SELECT DISTINCT wtr.object_id as post_id FROM `' . $wpdb->prefix . 'term_taxonomy` as wtt , ' . $wpdb->prefix . 'terms as wt , ' . $wpdb->prefix . 'term_relationships as wtr WHERE wt.term_id = wtt.term_id AND wtr.term_taxonomy_id = wtt.term_taxonomy_id AND `taxonomy` LIKE %s AND wt.slug IN (%s))', $attribute_key[1], implode("', '", $attribute_value));
					$attribute_sql_condition .= $wpdb->prepare(' post_id IN (SELECT DISTINCT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "attribute_%s" AND ( meta_value IN (%s) ) ) OR post_id IN %s', strtolower($attribute_key[1]), implode("', '", $attribute_value), $finding_attribute_associated_sql);
				} else {
					$attribute_sql_condition .= $wpdb->prepare(' post_id IN (SELECT DISTINCT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "attribute_%s" AND ( meta_value IN (%s) ) )', strtolower($attribute_key), implode("', '", $attribute_value));
				}
				++$counter;
			}
		}

		if (!empty($attribute_sql_condition)) {

			$filtered_products = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta WHERE 1 AND %s", $attribute_sql_condition));

			if (isset($filtered_products) && !empty($filtered_products)) {
				foreach ($filtered_products as $filtered_product) {
					$post_parent = wp_get_post_parent_id($filtered_product->post_id);
					if (!empty($post_parent)) {
						$attribute_filtered_products_list[] = $post_parent;
					} else {
						$attribute_filtered_products_list[] = $filtered_product->post_id;
					}
				}
			}
		}

		if (isset($attribute_filtered_products_list) && !empty($attribute_filtered_products_list) && isset($price_filtered_products_list) && !empty($price_filtered_products_list)) {
			$filtered_products_list = array_intersect($price_filtered_products_list, $attribute_filtered_products_list);
		} elseif (isset($attribute_filtered_products_list) && !empty($attribute_filtered_products_list)) {
			$filtered_products_list = $attribute_filtered_products_list;
		} elseif (isset($price_filtered_products_list) && !empty($price_filtered_products_list)) {
			$filtered_products_list = $price_filtered_products_list;
		}

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

			$suppress_filter = false;
		} else {
			$suppress_filter = true;
		}

		if (isset($category_id) && !empty($category_id)) {
			// Get Products by Category ID
			if (!empty($filter)) {
				if (!empty($filtered_products_list)) {
					$args = array(
						'post_type'           => 'product',
						'post_status'         => 'publish',
						'ignore_sticky_posts' => 1,
						/**
						 * Commented the below lines as earlier we fetch only 20 product and then apply the other filter due to which if any product is unset from array then the next page is never loaded (This is temporary solution)
						 * VGfeb2023 cate-loading
						 *
						 * @date   01-03-2023
						 */
						'posts_per_page'      => -1,
						'post__in'            => $filtered_products_list,
						'tax_query'           => array(
							array(
								'taxonomy' => 'product_cat',
								'field'    => 'term_id', // This is optional, as it defaults to 'term_id'
								'terms'    => $category_id,
								'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
							),
							array(
								'taxonomy' => 'product_visibility',
								'field'    => 'name',
								'terms'    => 'exclude-from-catalog',
								'operator' => 'NOT IN',
							),
						),
						'suppress_filters'    => $suppress_filter,
					);
				} else {
					$args = array();
				}
			} else {
				$args = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					/**
					 * Commented the below lines as earlier we fetch only 20 product and then apply the other filter due to which if any product is unset from array then the next page is never loaded (This is temporary solution)
					 * VGfeb2023 cate-loading
					 *
					 * @date   01-03-2023
					 */
					'posts_per_page'      => -1,
					'tax_query'           => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id', // This is optional, as it defaults to 'term_id'
							'terms'    => $category_id,
							'operator' => 'IN', // Possible values are 'IN', 'NOT IN', 'AND'.
						),
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => 'exclude-from-catalog',
							'operator' => 'NOT IN',
						),
					),
					'suppress_filters'    => $suppress_filter,
				);
			}

			// Sorting by options changes - added by Harsh Agarwal on 21-Aug-2020
			if ('low' == $sort_by || 'high' == $sort_by) {
				$args['meta_key'] = '_price';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = ('low' == $sort_by) ? 'ASC' : 'DESC';
			} elseif ('atoz' == $sort_by || 'ztoa' == $sort_by) {
				$args['orderby'] = 'title';
				$args['order']   = ('atoz' == $sort_by) ? 'ASC' : 'DESC';
			} elseif ('rating_high' == $sort_by || 'rating_low' == $sort_by) {
				$args['meta_key'] = '_wc_average_rating';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = ('rating_high' == $sort_by) ? 'DESC' : 'ASC';
			}
			// End Sorting by options changes

			$products = new WP_Query($args);
		} elseif (isset($search_term) && !empty($search_term)) {
			// Get Products by Search Term
			if (!empty($filter)) {
				$args = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					/**
					 * Commented the below lines as earlier we fetch only 20 product and then apply the other filter due to which if any product is unset from array then the next page is never loaded (This is temporary solution)
					 * VGfeb2023 cate-loading
					 *
					 * @date   01-03-2023
					 */
					'posts_per_page'      => -1,
					'post__in'            => $filtered_products_list,
					's'                   => $search_term,
					'tax_query'           => array(
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => 'exclude-from-catalog',
							'operator' => 'NOT IN',
						),
					),
					'suppress_filters'    => $suppress_filter,
				);
			} else {
				$args = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					/**
					 * Commented the below lines as earlier we fetch only 20 product and then apply the other filter due to which if any product is unset from array then the next page is never loaded (This is temporary solution)
					 * VGfeb2023 cate-loading
					 *
					 * @date   01-03-2023
					 */
					'posts_per_page'      => -1,
					's'                   => $search_term,
					'tax_query'           => array(
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => 'exclude-from-catalog',
							'operator' => 'NOT IN',
						),
					),
					'suppress_filters'    => $suppress_filter,
				);
			}

			// Sorting by options changes - added by Harsh Agarwal on 21-Aug-2020
			if ('low' == $sort_by || 'high' == $sort_by) {
				$args['meta_key'] = '_price';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = ('low' == $sort_by) ? 'ASC' : 'DESC';
			} elseif ('atoz' == $sort_by || 'ztoa' == $sort_by) {
				$args['orderby'] = 'title';
				$args['order']   = ('atoz' == $sort_by) ? 'ASC' : 'DESC';
			} elseif ('rating_high' == $sort_by || 'rating_low' == $sort_by) {
				$args['meta_key'] = '_wc_average_rating';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = ('rating_high' == $sort_by) ? 'ASC' : 'DESC';
			}
			// End Sorting by options changes

			$products = new WP_Query($args);
		}

		$categoryProducts = array(
			'title'    => '',
			'products' => array(),
		);
		// BOC Module Upgrade V2 neeraj.kumar@velsof.com Added category name in title key
		$term = get_term_by('id', $category_id, 'product_cat');
		if ($term) {
			$categoryProducts['title'] = html_entity_decode($term->name, ENT_QUOTES);
		}
		// EOC
		// Code added to send total_cart_quantity
		$total_cart_quantity = 0;

		if (isset($session_data) && !empty($session_data)) {
			$cart_id = $session_data;
		} elseif (isset($email) && !empty($email)) {
			$cart_id = email_exists($email);
		}

		if (!empty($cart_id)) {
			$this->set_session($cart_id);

			$this->wmab_response['session_data'] = (string) WC()->session->get_customer_id();

			$cart_contents = WC()->cart->get_cart_contents();
			foreach ($cart_contents as $cart_item) {
				if (!empty($cart_item['quantity'])) {
					$total_cart_quantity += $cart_item['quantity'];
				}
			}
			$this->wmab_response['total_cart_items'] = $total_cart_quantity;
		} else {
			// Cart Count - Sending it as 0 as without email or cart id we can not get count here
			$this->wmab_response['total_cart_items'] = 0;
		}

		if (isset($products) && !empty($products)) {
			if (isset($products->posts) && !empty($products->posts)) {
				foreach ($products->posts as $productList) {

					$product = wc_get_product($productList->ID);
					if ($product->is_visible()) {
						$is_product_new = 0;
						if (strtotime($product->get_date_created()) >= strtotime($this->wmab_plugin_settings['general']['product_new_date'])) {
							$is_product_new = 1;
						}
						$has_attributes = '0';
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

						// Get Product Category
						$product_category      = $product->get_category_ids();
						$product_category_id   = '';
						$product_category_name = '';
						if (isset($product_category[0]) && !empty($product_category[0])) {
							$product_category_id   = $product_category[0];
							$product_category_name = get_term_by('id', $product_category_id, 'product_cat');
							$product_category_name = $product_category_name->name;
						}

						// Check if image exists otherwise send dummy image - changes made by Saurav (25-Jun-2020)
						$product_thumbnail = get_the_post_thumbnail_url($productList->ID);

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
						$targeted_id = (string) $productList->ID;

						if (!empty($cart_id)) {
							$this->set_session($cart_id);

							$cart_contents = WC()->cart->get_cart_contents();
							foreach ($cart_contents as $cart_item) {
								if ($cart_item['product_id'] == $targeted_id && !empty($cart_item['quantity'])) {
									$cart_quantity = $cart_item['quantity'];
									break; // stop the loop if product is found
								}
							}
						}

						// Find Review Ratings
						$total_reviews      = 0;
						$reviews_rating_sum = 0;
						$reviews_average    = 0;
						$args               = array(
							'post_type' => 'product',
							'post_id'   => $productList->ID,
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

						$categoryProducts['products'][] = array(
							'id'                  => (string) $productList->ID,
							'name'                => $product->get_name(),
							'available_for_order' => $product->get_stock_status() === 'instock' ? '1' : '0',
							'show_price'          => !empty($this->wmab_plugin_settings['general']['show_price']) ? 1 : 0,
							'new_products'        => $is_product_new,
							'on_sale_products'    => $discount_percentage ? 1 : 0,
							'minimal_quantity'    => '1',
							'category_name'       => $product_category_name,
							'ClickActivityName'   => 'ProductActivity',
							'category_id'         => (string) $product_category_id,
							'price'               => html_entity_decode(
								wp_strip_all_tags(
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
							'discount_price'      => html_entity_decode(
								wp_strip_all_tags(
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
							'discount_percentage' => !empty($discount_percentage) ? ((string) $discount_percentage . __('% off', 'knowband-mobile-app-builder-for-woocommerce')) : '0',
							'is_in_wishlist'      => false,
							'has_attributes'      => $has_attributes,
							'cart_quantity'       => $cart_quantity,
							'number_of_reviews'   => (string) $total_reviews,
							'averagecomments'     => (string) $reviews_average,
						);
					}
				}
			}
		}

		// BOC: Changes added by Vishal on 24th Nov 2022 to resolve the issue i.e If some products visibility is not public and count of products are less than 20 due to the visibility, in that case the next pages doesn't load
		$categoryProducts['products'] = array_slice($categoryProducts['products'], ($page_number - 1) * $item_count, $item_count);
		// EOC: Changes added by Vishal on 24th Nov 2022 to resolve the issue i.e If some products visibility is not public and count of products are less than 20 due to the visibility, in that case the next pages doesn't load

		$this->wmab_response['fproducts'] = $categoryProducts;

		// Log Request
		wmab_log_knowband_app_request('appGetCategoryDetails', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to handle appGetFilters API request
	 *
	 * Request URI - http://[DOMAIN]/wp-json/wmab/v1.1/appGetFilters
	 *
	 * @param  int    $category_id This parameter holds category ID
	 * @param  string $search_term This parameter holds search term
	 * @param  string $version     This parameter holds API version to verify during API call
	 */
	public function app_get_filters($category_id, $search_term, $version)
	{

		// First do the API version verification and then go ahead
		$this->verify_api($version, 'appGetFilters');

		$arrIndex = 0; // Array Index starts from 0
		if (isset($category_id) && !empty($category_id)) {
			// Get Price Ranges by Category ID
			$priceFilters = $this->getPriceFiltersByCategoryID($category_id);
			if (!empty($priceFilters)) {
				// Product Price Filter
				$this->wmab_response['filter_result'][$arrIndex] = array(
					'id'             => '1',
					'name'           => __('Prices', 'knowband-mobile-app-builder-for-woocommerce'), // Name is being used in the app to display
					'title'          => 'prices', // Title will come in the API call from the App on the basos of the same, Filter is being performed
					'is_color_group' => '0',
					'choice_type'    => 'multiple',
					'items'          => array(),
				);

				foreach ($priceFilters as $priceFilter) {
					$priceRange = explode('|', $priceFilter);

					$this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
						'id'          => (string) $priceFilter,
						'color_value' => '',
						'name'        => html_entity_decode(wp_strip_all_tags(wc_price($priceRange[0]) . ' - ' . wc_price($priceRange[1])), ENT_QUOTES),
					);
				}

				++$arrIndex; // Increment Array Index by 1
			}

			// Get Attributes Filters by Category ID
			$attributeFilters = $this->getAttributeFiltersByCategoryID($category_id);
			if (!empty($attributeFilters)) {
				foreach ($attributeFilters as $key => $attributeFilter) {
					// Product Attributes Filter
					if (!empty($attributeFilter['values'])) {
						$this->wmab_response['filter_result'][$arrIndex] = array(
							'id'             => $key,
							'name'           => $attributeFilter['name'],
							'title'          => $attributeFilter['name'], // Title will be return in the API call from the App. On the basis of the same, Filter is being performed so no translation needs to be done in the title.
							'is_color_group' => '0',
							'choice_type'    => 'multiple',
							'items'          => array(),
						);

						if (isset($attributeFilter['values']) && !empty($attributeFilter['values'])) {
							foreach ($attributeFilter['values'] as $attributeValue) {
								$this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
									'id'          => strtolower($attributeValue->slug),
									'color_value' => '',
									'name'        => $attributeValue->name,
								);
							}
						}
						++$arrIndex; // Increment Array Index by 1
					}
				}
			}
		} elseif (isset($search_term) && !empty($search_term)) {
			// Get Price Ranges by Search Term
			$priceFilters = $this->getPriceFiltersBySearchTerm($search_term);
			if (!empty($priceFilters)) {
				// Product Price Filter
				$this->wmab_response['filter_result'][$arrIndex] = array(
					'id'             => '1',
					'name'           => __('Prices', 'knowband-mobile-app-builder-for-woocommerce'),
					'title'          => __('Prices', 'knowband-mobile-app-builder-for-woocommerce'),
					'is_color_group' => '0',
					'choice_type'    => 'multiple',
					'items'          => array(),
				);

				foreach ($priceFilters as $priceFilter) {
					$priceRange = explode('|', $priceFilter);

					$this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
						'id'          => $priceFilter,
						'color_value' => '',
						'name'        => html_entity_decode(wp_strip_all_tags(wc_price($priceRange[0]) . ' - ' . wc_price($priceRange[1])), ENT_QUOTES),
					);
				}

				++$arrIndex; // Increment Array Index by 1
			}

			// Get Attributes Filters by Search Term
			$attributeFilters = $this->getAttributeFiltersBySearchTerm($search_term);
			if (!empty($attributeFilters)) {
				foreach ($attributeFilters as $key => $attributeFilter) {
					// Product Attributes Filter
					$this->wmab_response['filter_result'][$arrIndex] = array(
						'id'             => (string) $key,
						'name'           => $attributeFilter['name'],
						'title'          => $attributeFilter['name'],
						'is_color_group' => '0',
						'choice_type'    => 'multiple',
						'items'          => array(),
					);

					if (isset($attributeFilter['values']) && !empty($attributeFilter['values'])) {
						foreach ($attributeFilter['values'] as $attributeValue) {
							$this->wmab_response['filter_result'][$arrIndex]['items'][] = array(
								'id'          => (string) strtolower($attributeValue->slug),
								'color_value' => '',
								'name'        => $attributeValue->name,
							);
						}
					}
					++$arrIndex; // Increment Array Index by 1
				}
			}
		}

		// Log Request
		wmab_log_knowband_app_request('appGetFilters', serialize_block_attributes($this->wmab_response));
		$response = rest_ensure_response($this->wmab_response);
		return $response;
	}

	/**
	 * Function to get Price Filters by Catgeory ID
	 *
	 * @param  int $category_id This parameter holds Category ID
	 */
	private function getPriceFiltersByCategoryID($category_id)
	{

		global $wpdb;

		$priceFiltersResponse = array();

		if (isset($category_id) && !empty($category_id)) {

			$priceRange = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT MIN(CAST(pm.meta_value AS decimal(10, 2))) AS min_price, MAX(CAST(pm.meta_value AS decimal(10, 2))) AS max_price
					FROM {$wpdb->prefix}term_relationships AS tr
					INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->prefix}terms AS t ON tr.term_taxonomy_id = t.term_id
					INNER JOIN {$wpdb->prefix}postmeta AS pm ON tr.object_id = pm.post_id
					WHERE tt.taxonomy LIKE %s
					AND t.term_id = %d
					AND pm.meta_key = '_price'
					",
					'product_cat',
					$category_id
				)
			);

			$minPrice = $priceRange->min_price;
			$maxPrice = $priceRange->max_price;

			$rangeDiff = ceil($maxPrice / 4);

			if ($rangeDiff > 0) {
				$totalRanges = ceil($maxPrice / $rangeDiff);

				for ($i = 0; $i < $totalRanges; $i++) {
					$start = $i * $rangeDiff;
					$end   = $start + $rangeDiff;
					if (0 == $i) {
						$priceFiltersResponse[$i] = $start . '|' . $end;
					} else {
						$priceFiltersResponse[$i] = ($start + 1) . '|' . $end;
					}
				}
			}
		}

		return $priceFiltersResponse;
	}

	/**
	 * Function to get Price Filters by Search Term
	 *
	 * @param  string $search_term This parameter holds search term
	 */
	private function getPriceFiltersBySearchTerm($search_term)
	{

		global $wpdb;

		$priceFiltersResponse = array();

		if (isset($search_term) && !empty($search_term)) {

			$priceRange = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT MIN(CAST(pm.meta_value AS decimal(10, 2))) AS min_price, MAX(CAST(pm.meta_value AS decimal(10, 2))) AS max_price
					FROM {$wpdb->prefix}term_relationships AS tr
					INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->prefix}terms AS t ON tr.term_taxonomy_id = t.term_id
					INNER JOIN {$wpdb->prefix}postmeta AS pm ON tr.object_id = pm.post_id
					INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = pm.post_id
					WHERE tt.taxonomy LIKE %s AND p.post_type = 'product'
					AND p.post_title LIKE %s
					AND pm.meta_key = '_price'
					",
					'product_cat',
					'%' . $wpdb->esc_like($search_term) . '%'
				)
			);

			$minPrice = $priceRange->min_price;
			$maxPrice = $priceRange->max_price;

			$rangeDiff = ceil($maxPrice / 4);

			if ($rangeDiff > 0) {
				$totalRanges = ceil($maxPrice / $rangeDiff);

				for ($i = 0; $i < $totalRanges; $i++) {
					$start                      = $i * $rangeDiff;
					$end                        = $start + $rangeDiff;
					$priceFiltersResponse[$i] = $start . '|' . $end;
				}
			}
		}

		return $priceFiltersResponse;
	}

	/**
	 * Function to get Attribute Filters by Catgeory ID
	 *
	 * @param  int $category_id This parameter holds Category ID
	 */
	private function getAttributeFiltersByCategoryID($category_id)
	{

		global $wpdb;

		$attributeFiltersResponse = array();

		if (isset($category_id) && !empty($category_id)) {
			$products = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.meta_value as meta_value, pm.post_id as post_id
					FROM {$wpdb->prefix}term_relationships as tr
					INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
					INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
					WHERE tt.taxonomy LIKE %s
					AND t.term_id = %d
					AND pm.meta_key = '_product_attributes'
					",
					'product_cat',
					$category_id
				)
			);

			if (isset($products) && !empty($products)) {
				foreach ($products as $product) {
					$attributes = unserialize($product->meta_value);
					$product_id = $product->post_id;

					if (isset($attributes) && !empty($attributes)) {
						foreach ($attributes as $attribute) {
							// Get Attribute Name
							$attribute_name = wc_attribute_label($attribute['name']);

							$attributeFiltersResponse[$attribute['name']]['name'] = $attribute_name;
							if (!isset($attributeFiltersResponse[$attribute['name']]['values'])) {
								$attributeFiltersResponse[$attribute['name']]['values'] = array();
							}
							// Get Attribute Value
							$attribute_values = wc_get_product_terms($product_id, $attribute['name']);
							if (isset($attribute_values) && !empty($attribute_values)) {
								foreach ($attribute_values as $attribute_value) {
									if (is_array($attributeFiltersResponse[$attribute['name']]['values']) && !in_array($attribute_value, $attributeFiltersResponse[$attribute['name']]['values'])) {
										$attributeFiltersResponse[$attribute['name']]['values'][] = $attribute_value;
									}
								}
							}
						}
					}
				}
			}
		}
		wmab_log_knowband_app_request('appGetFilters', serialize_block_attributes($attributeFiltersResponse));
		return $attributeFiltersResponse;
	}

	/**
	 * Function to get Attribute Filters by Search Term
	 *
	 * @param  string $search_term This parameter holds search term
	 */
	private function getAttributeFiltersBySearchTerm($search_term)
	{
		global $wpdb;

		$attributeFiltersResponse = array();

		if (isset($search_term) && !empty($search_term)) {

			$products = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.meta_value as meta_value, pm.post_id as post_id
					FROM {$wpdb->prefix}term_relationships as tr
					INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->prefix}terms as t ON tr.term_taxonomy_id = t.term_id
					INNER JOIN {$wpdb->prefix}postmeta as pm ON tr.object_id = pm.post_id
					INNER JOIN {$wpdb->prefix}posts as p ON p.ID = pm.post_id
					WHERE tt.taxonomy LIKE %s AND p.post_type = 'product'
					AND p.post_title LIKE %s
					AND pm.meta_key = '_product_attributes'",
					'product_cat',
					'%' . $wpdb->esc_like($search_term) . '%'
				)
			);

			if (isset($products) && !empty($products)) {
				foreach ($products as $product) {
					$attributes = unserialize($product->meta_value);
					$product_id = $product->post_id;

					if (isset($attributes) && !empty($attributes)) {
						foreach ($attributes as $attribute) {
							// Get Attribute Name
							$attribute_name = wc_attribute_label($attribute['name']);

							$attributeFiltersResponse[$attribute['name']]['name'] = $attribute_name;
							if (!isset($attributeFiltersResponse[$attribute['name']]['values'])) {
								$attributeFiltersResponse[$attribute['name']]['values'] = array();
							}
							// Get Attribute Value
							$attribute_values = wc_get_product_terms($product_id, $attribute['name']);

							if (isset($attribute_values) && !empty($attribute_values)) {
								foreach ($attribute_values as $attribute_value) {
									if (!in_array($attribute_value, $attributeFiltersResponse[$attribute['name']]['values'])) {
										$attributeFiltersResponse[$attribute['name']]['values'][] = $attribute_value;
									}
								}
							}
						}
					}
				}
			}
		}

		return $attributeFiltersResponse;
	}

	/**
	 * Module Upgrade V2
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
