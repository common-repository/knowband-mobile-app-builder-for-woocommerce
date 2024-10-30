<?php

/**
 * Plugin Name: Knowband Mobile App Builder for wooCommerce
 * Plugin URI: http://woocommerce.com/products/woocommerce-extension/
 * Description: Mobile App Builder
 * Version: 2.0.7
 * Author: Knowband
 * Author URI: https://www.knowband.com/
 * Developer: Knowband
 * Developer URI: https://www.knowband.com/
 * Text Domain: knowband-mobile-app-builder-for-woocommerce
 * Domain Path: /languages
 *
 * WC requires at least: 3.3.3
 * WC tested up to: 8.8.2
 *
 * Copyright: 2009-2023 Knowband.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) {
	exit;  // Exit if access directly
}

class WoocommerceMobileAppBuilder
{

	public function __construct()
	{
		global $wpdb;

		// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
		$kb_nonce_verification = 0;

		if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
			$kb_nonce_verification = 1;
		}

		add_action('plugins_loaded', array($this, 'wmab_load_textdomain'));

		register_activation_hook(__FILE__, array($this, 'wmab_install'));
		register_deactivation_hook(__FILE__, array($this, 'wmab_uninstall'));

		// Get Mobile App Builder settings from database
		$settings = get_option('wmab_settings');
		if (isset($settings) && !empty($settings)) {
			$settings = unserialize($settings);
		}
	}

	/*
	 * Function definition to install the plugin
	 */

	public function wmab_install()
	{
		/**
		 * Filters the name of the WooCommerce cookie.
		 *
		 * @param string $cookie_name The name of the WooCommerce cookie.
		 * @return string The filtered cookie name.
		 * @since 1.0.0
		 */
		do_action('wmab_action_install');
		global $wpdb;

		/**
		 * BOC neeraj.kumar@velsof.com 21-Dec-2019 Module Upgrade V2
		 * create new tables
		 */
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}mabmobileapp_component_types`");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mabmobileapp_component_types` (`id` int(11) NOT NULL AUTO_INCREMENT, `component_name` varchar(200) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");

		$wpdb->query("INSERT INTO `{$wpdb->prefix}mabmobileapp_component_types` (`id`, `component_name`) VALUES (1, 'Top Categories'), (2, 'Banner-Square'), (3, 'Banner-Horizontal Sliding'), (4, 'Banner-Grid'), (5, 'Banner-With Countdown Timer'), (6, 'Products-Square'), (7, 'Products-Horizontal Sliding'), (8, 'Products-Grid'), (9, 'Products-Last Accessed'), (10, 'Banner-Custom')");

		// Layout table Create
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_layouts` (`id_layout` int(11) NOT NULL AUTO_INCREMENT, `layout_name` varchar(200) NOT NULL, PRIMARY KEY (`id_layout`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");

		// Banner Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_banner` (`banner_id` int(10) NOT NULL AUTO_INCREMENT, `name` varchar(40) NOT NULL, `image_width` int(3) NOT NULL, `image_height` int(3) NOT NULL, `status` tinyint(1) NOT NULL, `banner_limit` int(10) NOT NULL, PRIMARY KEY (`banner_id`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");

		// Layout Component Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_layout_component` (`id_component` int(11) NOT NULL AUTO_INCREMENT, `id_layout` int(11) NOT NULL, `id_component_type` int(11) NOT NULL, `position` int(11) NOT NULL, `component_heading` varchar(200) CHARACTER SET utf32 DEFAULT NULL, `component_title` varchar(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL, PRIMARY KEY (`id_component`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");

		// Product Data Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_product_data` (`id` int(11) NOT NULL AUTO_INCREMENT, `product_type` varchar(200) NOT NULL, `category_products` text, `custom_products` text, `image_content_mode` varchar(200) NOT NULL, `number_of_products` int(11) NOT NULL, `id_category` int(11) DEFAULT NULL, `id_component` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1");

		// Top Category Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_top_category` (`id` int(11) NOT NULL AUTO_INCREMENT, `id_component` int(11) NOT NULL, `id_category` varchar(200) NOT NULL, `image_url` longtext, `image_content_mode` varchar(200) DEFAULT NULL, `category_heading` varchar(500) CHARACTER SET utf32 DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");

		// Mab Banners
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_banners` (`id` int(11) NOT NULL AUTO_INCREMENT, `id_component` int(11) NOT NULL, `id_banner_type` int(11) NOT NULL, `countdown` varchar(200) DEFAULT NULL, `product_id` int(10) DEFAULT NULL, `category_id` int(10) DEFAULT NULL, `redirect_activity` varchar(100) DEFAULT NULL, `image_url` longtext, `product_name` varchar(200) DEFAULT NULL, `image_contentMode` varchar(200) NOT NULL, `banner_heading` varchar(200) DEFAULT NULL, `background_color` varchar(11) DEFAULT NULL, `is_enabled_background_color` int(10) NOT NULL DEFAULT '1', `text_color` varchar(11) DEFAULT NULL, `banner_custom_background_color` varchar(11) DEFAULT NULL, `inset_top` varchar(11) DEFAULT NULL, `inset_bottom` varchar(11) DEFAULT NULL, `inset_left` varchar(11) DEFAULT NULL, `inset_right` varchar(11) DEFAULT NULL, `banner_width` varchar(11) DEFAULT NULL, `banner_height` varchar(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1");

		// BOC neeraj.kumar@velsof.com 29-Jan-2020 : Existing plugin : alter table column
		$wpdb->query("ALTER TABLE `{$wpdb->prefix}mab_mobileapp_banners` CHANGE `redirect_activity` `redirect_activity` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_mobileapp_tab_bar` (`tab_icon_id` int(11) NOT NULL AUTO_INCREMENT, `tab_icon_text` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `tab_icon_image` varchar(255) NOT NULL, `tab_icon_redirect_activity` varchar(80) NOT NULL, `tab_bar_date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`tab_icon_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		// Unique Verification Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_unique_verification` (`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` bigint(20) UNSIGNED NOT NULL, `mobile_number` varchar(15) DEFAULT NULL, `country_code` varchar(10) DEFAULT NULL, `unique_id` varchar(100) DEFAULT NULL, `date_added` datetime NOT NULL, `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`))");

		// Banner/Slideshow Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_banner` (`banner_id` int(10) NOT NULL AUTO_INCREMENT, `name` varchar(40) NOT NULL, `image_width` INT(3) NOT NULL, `image_height` INT(3) NOT NULL, `status` TINYINT(1) NOT NULL, `banner_limit` INT(10) NOT NULL, PRIMARY KEY (`banner_id`))");
		// Banner/Slideshow Image Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_banner_image` (`banner_image_id` int(10) NOT NULL AUTO_INCREMENT, `banner_id` int(10) NOT NULL, `banner_title` varchar(255) NOT NULL, `link_type` TINYINT(1) NOT NULL, `link_to` int(10) NOT NULL, `image` varchar(255) NOT NULL, `sort_order` INT(3) NOT NULL DEFAULT '0', PRIMARY KEY (`banner_image_id`))");
		// CMS Page data Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_cms_page_data` (`cms_id` int(10) NOT NULL AUTO_INCREMENT, `page_title` varchar(255) NOT NULL, `link_to` int(10) NOT NULL, `status` TINYINT(2) NOT NULL, `sort_order` INT(3) NOT NULL DEFAULT '0', `date_added` DATETIME NOT NULL, `date_modified` TIMESTAMP NOT NULL, PRIMARY KEY (`cms_id`))");
		// Push Notifications Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_push_notification_history` (`notification_id` int(10) NOT NULL AUTO_INCREMENT, `title` varchar(255) NOT NULL, `message` varchar(500) DEFAULT NULL, `image_url` text NOT NULL, `redirect_activity` enum('home', 'category', 'product') NOT NULL, `category_id` int(10) DEFAULT NULL, `product_id` int(10) DEFAULT NULL, `date_added` datetime NOT NULL, PRIMARY KEY (`notification_id`))");
		// FCM ID and Email ID mapping Table
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_fcm_details` (`fcm_details_id` int(10) NOT NULL AUTO_INCREMENT, `email_id` varchar(255) DEFAULT NULL, `order_id` int(10) DEFAULT NULL, `cart` text, `fcm_id` text NOT NULL, `notification_sent_status` int(2) DEFAULT NULL, `last_order_status` int(11) NOT NULL DEFAULT '0', `date_add` datetime NOT NULL, `date_upd` datetime NOT NULL, PRIMARY KEY (`fcm_details_id`))");

		$mab_cms_page_entries = $wpdb->get_row("SELECT COUNT(cms_id) AS total FROM `{$wpdb->prefix}mab_cms_page_data");

		if ($mab_cms_page_entries->total == 0) {
			/**
			 * Adding Default pages entry in the DB for the Information Pages
			 * WCMAB001-Default-Entry-MAB
			 * Date: 24-04-2024
			 *
			 * @author Satyam
			 */
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Cart',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Checkout',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Shop',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Sample Page',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'My Account',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Reviews',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Home',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO `{$wpdb->prefix}mab_cms_page_data` (`page_title`, `link_to`, `status`, `sort_order`, `date_added`, `date_modified`) VALUES (%s, %d, %d, %d, %s, %s)",
					'Home',
					0,
					0,
					0,
					current_time('mysql'),
					current_time('mysql')
				)
			);
		}

		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}mab_login_sessions` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`session_key` varchar(255) NOT NULL,
				`session_value` text,
				`reorder_direct` int(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
		);

		/*
		* BOC neeraj.kumar@velsof.com 21-Dec-2019 Module Upgrade V2
		* create new tables
		*/
		// Check categories already exists or not
		$mab_layout_exists_entries = $wpdb->get_row("SELECT COUNT(id_layout) AS total FROM `{$wpdb->prefix}mab_mobileapp_layouts`");

		if (isset($mab_layout_exists_entries->total) && !$mab_layout_exists_entries->total) {
			// Insert hardcoded default layout
			$wpdb->query($wpdb->prepare("INSERT INTO `{$wpdb->prefix}mab_mobileapp_layouts` (`layout_name`) VALUES (%s)", 'Default'));
		}

		// neeraj.kumar Module Upgrade V2 7-Jan-2020 updrade DB by validating specific column exists or not
		$banner_custom_background_color_coulmn_results = $wpdb->get_row($wpdb->prepare('SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND column_name="banner_custom_background_color"', DB_NAME, $wpdb->prefix."mab_mobileapp_banners"));
		// Add column if not present.
        if (empty($banner_custom_background_color_coulmn_results)) {
			$wpdb->query("ALTER TABLE `{$wpdb->prefix}mab_mobileapp_banners` ADD `banner_custom_background_color` VARCHAR(11) NULL DEFAULT NULL AFTER `text_color`, ADD `inset_top` VARCHAR(11) NULL DEFAULT NULL AFTER `banner_custom_background_color`, ADD `inset_bottom` VARCHAR(11) NULL DEFAULT NULL AFTER `inset_top`, ADD `inset_left` VARCHAR(11) NULL DEFAULT NULL AFTER `inset_bottom`, ADD `inset_right` VARCHAR(11) NULL DEFAULT NULL AFTER `inset_left`, ADD `banner_width` VARCHAR(11) NULL DEFAULT NULL AFTER `inset_right`, ADD `banner_height` VARCHAR(11) NULL DEFAULT NULL AFTER `banner_width`;");
        }


		$component_title_coulmn_coulmn_results = $wpdb->get_row($wpdb->prepare('SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND column_name="component_title"', DB_NAME, $wpdb->prefix."mab_mobileapp_layout_component"));
		// Add column if not present.
        if (empty($component_title_coulmn_coulmn_results)) {
			$wpdb->query("ALTER TABLE `{$wpdb->prefix}mab_mobileapp_layout_component` ADD `component_title` VARCHAR(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci NULL DEFAULT NULL AFTER `component_heading`;");
        }

		// Queries added by Harsh Agarwal on 28-Apr-2020 to set default layout at the time of plugin installation
		$mab_get_layout = $wpdb->get_row("SELECT id_layout FROM `{$wpdb->prefix}mab_mobileapp_layouts` WHERE layout_name = 'Default'");

		if (isset($mab_get_layout->id_layout) && $mab_get_layout->id_layout) {
			if (isset($mab_layout_exists_entries->total) && !$mab_layout_exists_entries->total) {
				// Insert Layout Components
				$query = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->prefix}mab_mobileapp_layout_component` (`id_component`, `id_layout`, `id_component_type`, `position`, `component_heading`, `component_title`) VALUES
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s),
					('', %d, %d, %d, %s, %s);",
						$mab_get_layout->id_layout,
						1,
						1,
						null,
						null,
						$mab_get_layout->id_layout,
						3,
						2,
						'Deals Of The Day',
						null,
						$mab_get_layout->id_layout,
						4,
						3,
						'Last Day Sale',
						null,
						$mab_get_layout->id_layout,
						1,
						4,
						null,
						null,
						$mab_get_layout->id_layout,
						2,
						5,
						'Flat 50% Off',
						null,
						$mab_get_layout->id_layout,
						8,
						6,
						'New Arrivals',
						null,
						$mab_get_layout->id_layout,
						6,
						7,
						'Latest Products',
						null,
						$mab_get_layout->id_layout,
						7,
						8,
						'Best Selling Products',
						null
					)
				);

				// Get Component ID based on Layout, Component Type and Position
				// Top Categories Components
				$component_1 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						1,
						1
					)
				);

				$component_2 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						1,
						4
					)
				);

				// Product Data Components
				$component_3 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						8,
						6
					)
				);

				$component_4 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						6,
						7
					)
				);

				$component_5 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						7,
						8
					)
				);
				// Banners Components

				$component_6 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						2,
						5
					)
				);

				$component_7 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						3,
						2
					)
				);

				$component_8 = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT id_component FROM `{$wpdb->prefix}mab_mobileapp_layout_component` WHERE id_layout = %d AND id_component_type = %d AND position = %d",
						$mab_get_layout->id_layout,
						4,
						3
					)
				);

				// Insert Top Categories

				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->prefix}mab_mobileapp_top_category` (`id`, `id_component`, `id_category`, `image_url`, `image_content_mode`, `category_heading`) VALUES
					('', %d, %s, %s, %s, %s),
					('', %d, %s, %s, %s, %s),
					('', %d, %s, %s, %s, %s),
					('', %d, %s, %s, %s, %s),
					('', %d, %s, %s, %s, %s),
					('', %d, %s, %s, %s, ''),
					('', %d, %s, %s, %s, ''),
					('', %d, %s, %s, %s, ''),
					('', %d, %s, %s, %s, ''),
					('', %d, %s, %s, %s, '');",
						$component_1->id_component,
						'1',
						'top_category_1.png',
						'scaleAspectFill',
						'Bags',
						$component_1->id_component,
						'2',
						'top_category_2.png',
						'scaleAspectFill',
						'Dress',
						$component_1->id_component,
						'3',
						'top_category_3.png',
						'scaleAspectFill',
						'Footwear',
						$component_1->id_component,
						'4',
						'top_category_4.png',
						'scaleAspectFill',
						'Caps',
						$component_1->id_component,
						'5',
						'top_category_5.png',
						'scaleAspectFill',
						'Sunglasses',
						$component_2->id_component,
						'1',
						'top_category_1.png',
						'scaleAspectFill',
						$component_2->id_component,
						'2',
						'top_category_2.png',
						'scaleAspectFill',
						$component_2->id_component,
						'3',
						'top_category_3.png',
						'scaleAspectFill',
						$component_2->id_component,
						'4',
						'top_category_4.png',
						'scaleAspectFill',
						$component_2->id_component,
						'5',
						'top_category_5.png',
						'scaleAspectFill'
					)
				);

				// Insert Product Data
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->prefix}mab_mobileapp_product_data` (`id`, `product_type`, `category_products`, `custom_products`, `image_content_mode`, `number_of_products`, `id_category`, `id_component`) VALUES
					('', %s, %s, %s, %s, %d, %d, %d),
					('', %s, %s, %s, %s, %d, %d, %d),
					('', %s, %s, %s, %s, %d, %d, %d);",
						'new_products',
						null,
						null,
						'scaleAspectFill',
						4,
						0,
						$component_3->id_component,
						'featured_products',
						null,
						null,
						'scaleAspectFill',
						2,
						0,
						$component_4->id_component,
						'new_products',
						null,
						null,
						'scaleAspectFill',
						8,
						0,
						$component_5->id_component
					)
				);

				// Insert Banenrs

				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO `{$wpdb->prefix}mab_mobileapp_banners` (`id_component`, `id_banner_type`, `countdown`, `product_id`, `category_id`, `redirect_activity`, `image_url`, `product_name`, `image_contentMode`, `banner_heading`, `background_color`, `is_enabled_background_color`, `text_color`, `banner_custom_background_color`, `inset_top`, `inset_bottom`, `inset_left`, `inset_right`, `banner_width`, `banner_height`) VALUES
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s),
						(%d, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s);",
						$component_6->id_component,
						1,
						null,
						0,
						0,
						'category',
						'banner_options_1.png',
						null,
						'scaleAspectFill',
						'',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_7->id_component,
						2,
						null,
						0,
						0,
						'product',
						'banner_options_2.png',
						null,
						'scaleAspectFill',
						'Banner 3',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_7->id_component,
						1,
						null,
						0,
						0,
						'category',
						'banner_options_3.png',
						null,
						'scaleAspectFill',
						'Banner 2',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_7->id_component,
						1,
						null,
						0,
						3,
						'category',
						'banner_options_4.png',
						null,
						'scaleAspectFill',
						'Banner 1',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_8->id_component,
						1,
						null,
						0,
						0,
						'category',
						'banner_options_5.png',
						null,
						'scaleAspectFill',
						'',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_8->id_component,
						1,
						null,
						0,
						0,
						'category',
						'banner_options_6.png',
						null,
						'scaleAspectFill',
						'',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_8->id_component,
						1,
						null,
						0,
						3,
						'category',
						'banner_options_7.png',
						null,
						'scaleAspectFill',
						'',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						$component_8->id_component,
						1,
						null,
						0,
						4,
						'category',
						'banner_options_8.png',
						null,
						'scaleAspectFill',
						'',
						null,
						1,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
					)
				);
			}
		}
	}

	/*
	 * Function definition to uninstall the plugin
	 */

	public function wmab_uninstall()
	{
		// Callback function for the "wmab_action_uninstall" action hook. Performs cleanup tasks when the plugin is being uninstalled.
		/**
		 * Filters the name of the WooCommerce cookie.
		 *
		 * @param string $cookie_name The name of the WooCommerce cookie.
		 * @return string The filtered cookie name.
		 * @since 1.0.0
		 */
		do_action('wmab_action_uninstall');
	}

	/*
	 * Function definition to deactivate the plugin
	 */

	public function wmab_deactivation()
	{
		// Callback function for the "wmab_action_deactivation" action hook. Performs tasks when the plugin is being deactivated.
		/**
		 * Filters the name of the WooCommerce cookie.
		 *
		 * @param string $cookie_name The name of the WooCommerce cookie.
		 * @return string The filtered cookie name.
		 * @since 1.0.0
		 */
		do_action('wmab_action_deactivation');
	}

	public function wmab_load_textdomain()
	{
		load_plugin_textdomain('knowband-mobile-app-builder-for-woocommerce', false, plugin_basename(__DIR__) . '/languages/');
	}
}

new WoocommerceMobileAppBuilder();

// Call Action to show Menu option in Admin
add_action('admin_menu', 'wmab_mobile_app_builder_menu');

// Set Ajax Handler Action
add_action('wp_ajax_wmab_insert_sample_data', 'wmab_insert_sample_data');
add_action('wp_ajax_wmab_get_link_to_options', 'wmab_get_link_to_options');
add_action('wp_ajax_wmab_delete_banner', 'wmab_delete_banner');
// BOC neeraj.kumar@velsof.com Module Upgrade V2 add custom ajax function
add_action('wp_ajax_wmab_change_layout_details', 'wmab_change_layout_details');
add_action('wp_ajax_wmab_save_layout_component_order', 'wmab_save_layout_component_order');
add_action('wp_ajax_wmab_delete_layout_component_order', 'wmab_delete_layout_component_order');
add_action('wp_ajax_wmab_get_product_details', 'wmab_get_product_details');
add_action('wp_ajax_wmab_delete_layout', 'wmab_delete_layout');
add_action('wp_ajax_wmab_get_tab_bar_detail', 'wmab_get_tab_bar_detail');
add_action('wp_ajax_wmab_delete_tab_bar', 'wmab_delete_tab_bar');
add_action('wp_ajax_wmab_save_tab_bar_form', 'wmab_save_tab_bar_form');
// Spin and Win
add_action('wp_ajax_email_recheck', 'email_recheck');
add_action('wp_ajax_send_email', 'send_email');
add_action('wp_ajax_spin_wheel_ajax', 'spin_wheel_ajax');
add_action('wp_ajax_send_email', 'send_email');
// EOC
/*
 * Function definition to add Spin and Win Menu option in admin menu bar
 */
add_action('woocommerce_thankyou', 'wmab_send_notification', 10, 1);

// Woocommerce Compatablility with the HPOA
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);

function wmab_send_notification($order_id)
{
	if (!$order_id) {
		return;
	}

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	$wmab_checkout = new WmabCheckout('sendOrderNotification');
	return $wmab_checkout->app_send_order_notification($order_id);
}

function wmab_mobile_app_builder_menu()
{
	$page_title = 'Knowband Mobile App Builder';
	$menu_title = 'Mobile App Builder';
	$capability = 'manage_options';
	$menu_slug  = 'mobile-app-builder';
	$function   = 'wmab_mobile_app_builder_settings';
	$icon_url   = 'dashicons-media-code';
	$position   = 4;

	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
}

function wmab_upload_dir_1($dirs)
{
	$dirs['baseurl'] = plugin_dir_url(__FILE__);
	$dirs['basedir'] = plugin_dir_path(__FILE__);
	$dirs['subdir']  = '/views/images/';
	$dirs['path']    = $dirs['basedir'] . '/views/images/';
	$dirs['url']     = $dirs['baseurl'] . '/views/images/';

	return $dirs;
}

function wmab_upload_dir_2($dirs)
{
	$dirs['baseurl'] = plugin_dir_url(__FILE__);
	$dirs['basedir'] = plugin_dir_path(__FILE__);
	$dirs['subdir']  = '/views/images/banners/';
	$dirs['path']    = $dirs['basedir'] . '/views/images/banners/';
	$dirs['url']     = $dirs['baseurl'] . '/views/images/banners/';

	return $dirs;
}

function wmab_upload_dir_3($dirs)
{
	$dirs['baseurl'] = plugin_dir_url(__FILE__);
	$dirs['basedir'] = plugin_dir_path(__FILE__);
	$dirs['subdir']  = '/views/images/home_page_layout/';
	$dirs['path']    = $dirs['basedir'] . '/views/images/home_page_layout/';
	$dirs['url']     = $dirs['baseurl'] . '/views/images/home_page_layout/';

	return $dirs;
}

function wmab_upload_dir_4($dirs)
{
	$dirs['baseurl'] = plugin_dir_url(__FILE__);
	$dirs['basedir'] = plugin_dir_path(__FILE__);
	$dirs['subdir']  = '/views/images/push_notifications/';
	$dirs['path']    = $dirs['basedir'] . '/views/images/push_notifications/';
	$dirs['url']     = $dirs['baseurl'] . '/views/images/push_notifications/';

	return $dirs;
}

function wmab_handle_logo_upload_main($file, $path)
{
	include_once ABSPATH . 'wp-admin/includes/file.php';
	$uploadedfile = $file;
	if (1 == $path) {
		add_filter('upload_dir', 'wmab_upload_dir_1');
	} elseif (2 == $path) {
		add_filter('upload_dir', 'wmab_upload_dir_2');
	} elseif (3 == $path) {
		add_filter('upload_dir', 'wmab_upload_dir_3');
	} elseif (4 == $path) {
		add_filter('upload_dir', 'wmab_upload_dir_4');
	}

	$movefile = wp_handle_upload($uploadedfile, array('test_form' => false));

	if (1 == $path) {
		remove_filter('upload_dir', 'wmab_upload_dir_1');
	} elseif (2 == $path) {
		remove_filter('upload_dir', 'wmab_upload_dir_2');
	} elseif (3 == $path) {
		remove_filter('upload_dir', 'wmab_upload_dir_3');
	} elseif (4 == $path) {
		remove_filter('upload_dir', 'wmab_upload_dir_4');
	}

	if ($movefile) {
		return true;
	} else {
		return false;
	}
}

/*
 * Function definition to show settings form
 */

function wmab_mobile_app_builder_settings()
{

	global $wpdb;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Save settings into options table
	if (isset($_POST['submit']) && !empty($_POST['submit'])) {
		$submit = true;
		$values = sanitize_post($_POST);
		// Spin And Win status check
		$spin_win_error = false;
		if (isset($values['vss_mab']['general']['spin_win_status']) && $values['vss_mab']['general']['spin_win_status']) {
			$spin_win_error        = true;
			$wmab_spin_win_options = get_option('wsaw_settings');
			if (isset($wmab_spin_win_options) && !empty($wmab_spin_win_options)) {
				$wmab_spin_win_settings = unserialize($wmab_spin_win_options);
				if (isset($wmab_spin_win_settings['general']['enabled']) && $wmab_spin_win_settings['general']['enabled']) {
					$spin_win_error = false;
				} else {
					$spin_win_error = true;
				}
			}
		}
		// Validate Google Login and FaceBook Login Settings
		// Validation for Google JSON file
		if (isset($_FILES['vss_mab_upload_file'])) {
			if (isset($_FILES['vss_mab_upload_file']['name']) && !empty($_FILES['vss_mab_upload_file']['name'])) {
				$ext = pathinfo(sanitize_text_field($_FILES['vss_mab_upload_file']['name']), PATHINFO_EXTENSION);
				if (strtolower($ext) != 'json') {
					// Google JSON File Type Error
					$submit                        = false;
					$_SESSION['wmab_google_error'] = true;
				} else {
					// Upload Google JSON File
					$google_file    = 'google/google_service.json';
					$document_path  = plugin_dir_path(__FILE__) . 'views/images/';
					$file_new       = array(
						'name'     => sanitize_text_field($_FILES['vss_mab_upload_file']['name']),
						'type'     => sanitize_text_field(isset($_FILES['vss_mab_upload_file']['type']) ? $_FILES['vss_mab_upload_file']['type'] : ''),
						'tmp_name' => sanitize_text_field(isset($_FILES['vss_mab_upload_file']['tmp_name']) ? $_FILES['vss_mab_upload_file']['tmp_name'] : ''),
						'error'    => sanitize_text_field(isset($_FILES['vss_mab_upload_file']['error']) ? $_FILES['vss_mab_upload_file']['error'] : ''),
						'size'     => sanitize_text_field(isset($_FILES['vss_mab_upload_file']['size']) ? $_FILES['vss_mab_upload_file']['size'] : ''),
					);
					$uploaded_files = wmab_handle_logo_upload_main($file_new, 1);
					if ($uploaded_files) {
						$values['vss_mab']['google_login_settings']['google_json'] = $google_file;
					} else {
						$submit                        = false;
						$_SESSION['wmab_google_error'] = true;
					}
				}
			} elseif (isset($values['vss_mab_upload_file_hidden']) && !empty($values['vss_mab_upload_file_hidden'])) {
				$values['vss_mab']['google_login_settings']['google_json'] = $values['vss_mab_upload_file_hidden'];
				unset($values['vss_mab_upload_file_hidden']);
			}
		} elseif (isset($values['vss_mab_upload_file_hidden']) && !empty($values['vss_mab_upload_file_hidden'])) {
			$values['vss_mab']['google_login_settings']['google_json'] = $values['vss_mab_upload_file_hidden'];
			unset($values['vss_mab_upload_file_hidden']);
		}
		// Ends

		// BOC neeraj.kumar@velsof.com 19-Dec-2019 Module Upgrade V2 upload app logo image and saved into db and move file to image folder
		if (isset($_FILES['vss_mab_app_logo_image_path'])) {
			if (isset($_FILES['vss_mab_app_logo_image_path']['name']) && !empty($_FILES['vss_mab_app_logo_image_path']['name'])) {
				$ext           = pathinfo(sanitize_text_field($_FILES['vss_mab_app_logo_image_path']['name']), PATHINFO_EXTENSION);
				$allowed       = array(
					'jpg'  => array('image/jpeg', 'image/jpg'),
					'jpeg' => array('image/jpg', 'image/jpeg'),
					'png'  => array('image/png'),
				);
				$file_name     = sanitize_text_field($_FILES['vss_mab_app_logo_image_path']['name']);
				$document_path = plugin_dir_path(__FILE__) . 'views/images/';
				if (isset($allowed[$ext]) && in_array(isset($_FILES['vss_mab_app_logo_image_path']['type']) ? $_FILES['vss_mab_app_logo_image_path']['type'] : '', $allowed[$ext])) {
					$file_new       = array(
						'name'     => sanitize_text_field(isset($_FILES['vss_mab_app_logo_image_path']['name']) ? $_FILES['vss_mab_app_logo_image_path']['name'] : ''),
						'type'     => sanitize_text_field(isset($_FILES['vss_mab_app_logo_image_path']['type']) ? $_FILES['vss_mab_app_logo_image_path']['type'] : ''),
						'tmp_name' => sanitize_text_field(isset($_FILES['vss_mab_app_logo_image_path']['tmp_name']) ? $_FILES['vss_mab_app_logo_image_path']['tmp_name'] : ''),
						'error'    => sanitize_text_field(isset($_FILES['vss_mab_app_logo_image_path']['error']) ? $_FILES['vss_mab_app_logo_image_path']['error'] : ''),
						'size'     => sanitize_text_field(isset($_FILES['vss_mab_app_logo_image_path']['size']) ? $_FILES['vss_mab_app_logo_image_path']['size'] : ''),
					);
					$uploaded_files = wmab_handle_logo_upload_main($file_new, 1);
					if ($uploaded_files) {
						$values['vss_mab']['general']['vss_mab_app_logo_image_path'] = $file_name;
					}
				} else {
					// Logo Image Type Error
					$submit                          = false;
					$_SESSION['wmab_app_logo_error'] = true;
				}
			} elseif (isset($values['vss_mab']['general']['image_logo_hidden']) && !empty($values['vss_mab']['general']['image_logo_hidden'])) {
				$values['vss_mab']['general']['vss_mab_app_logo_image_path'] = $values['vss_mab']['general']['image_logo_hidden'];
			}
		} elseif (isset($values['vss_mab']['general']['image_logo_hidden']) && !empty($values['vss_mab']['general']['image_logo_hidden'])) {
			$values['vss_mab']['general']['vss_mab_app_logo_image_path'] = $values['vss_mab']['general']['image_logo_hidden'];
		}
		// EOC Module Upgrade V2 neeraj.kumar@velsof.com

		// Set Custom CSS values
		$values['vss_mab']['general']['custom_css'] = trim($values['vss_mab']['general']['custom_css']);

		if ($submit) {
			// Code to add Information pages details into database
			if (isset($values['page_title']) && !empty($values['page_title'])) {
				foreach ($values['page_title'] as $page_key => $page_title) {
					$title           = $page_title;
					$page_id         = $values['information_page'][$page_key];
					$page_status     = isset($values['page_status'][$page_key]) ? $values['page_status'][$page_key] : 0;
					$page_sort_order = $values['page_sort_order'][$page_key];

					/**
					 * CMS Page Link Fixes
					 * WCMAB001-Default-Entry-MAB
					 * Date: 24-04-2024
					 *
					 * @author Satyam
					 */
					$query = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_cms_page_data WHERE cms_id = %d", $page_key));
					if (isset($query->cms_id) && !empty($query->cms_id)) {
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_cms_page_data SET page_title = %s, link_to = %d, status = %s, sort_order = %s WHERE cms_id = %d", $title, $page_id, $page_status, $page_sort_order, $query->cms_id));
					} else {
						$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_cms_page_data SET cms_id = '', page_title = %s, link_to = %d, status = %s, sort_order = %s, date_added = now()", $title, $page_id, $page_status, $page_sort_order));
					}
				}
			}

			// Save Slideshow Settings
			if (isset($values['vss_mab']['slideshow_id']) && !empty($values['vss_mab']['slideshow_id'])) {
				// Update the details
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_banner SET name = %s, image_width = %d, image_height = %s, status = %s, banner_limit = %s WHERE banner_id = %d", $values['vss_mab']['slideshow_settings']['slideshow_name'], $values['vss_mab']['slideshow_settings']['image_width'], $values['vss_mab']['slideshow_settings']['image_height'], $values['vss_mab']['slideshow_settings']['enabled'], $values['vss_mab']['slideshow_settings']['limit'], $values['vss_mab']['slideshow_id']));
			} else {
				// Insert the details
				$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_banner SET banner_id = '', name = %s, image_width = %d, image_height = %d, status = %s, banner_limit = %s", $values['vss_mab']['slideshow_settings']['slideshow_name'], $values['vss_mab']['slideshow_settings']['image_width'], $values['vss_mab']['slideshow_settings']['image_height'], $values['vss_mab']['slideshow_settings']['enabled'], $values['vss_mab']['slideshow_settings']['limit']));
				$values['vss_mab']['slideshow_id'] = $wpdb->insert_id;
			}

			// Save Banner Settings
			if (isset($values['vss_mab']['banner_id']) && !empty($values['vss_mab']['banner_id'])) {
				// Update the details

				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_banner SET name = %s, image_width = %d, image_height = %d, status = %s, banner_limit = %s WHERE banner_id = %d", $values['vss_mab']['banners_settings']['banner_name'], $values['vss_mab']['banners_settings']['image_width'], $values['vss_mab']['banners_settings']['image_height'], $values['vss_mab']['banners_settings']['enabled'], $values['vss_mab']['banners_settings']['limit'], $values['vss_mab']['banner_id']));
			} else {
				// Insert the details
				$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_banner SET banner_id = '', name = %s, image_width = %d, image_height = %d, status = %s, banner_limit = %s", $values['vss_mab']['banners_settings']['banner_name'], $values['vss_mab']['banners_settings']['image_width'], $values['vss_mab']['banners_settings']['image_height'], $values['vss_mab']['banners_settings']['enabled'], $values['vss_mab']['banners_settings']['limit']));
				$values['vss_mab']['banner_id'] = $wpdb->insert_id;
			}

			// Unset Slideshow and Banners settings as details are saved in separate tables
			unset($values['vss_mab']['slideshow_settings']);
			unset($values['vss_mab']['banners_settings']);

			// Add Slideshow Images
			if (isset($values['slide_name']) && !empty($values['slide_name'])) {
				$image_counter = 0;
				for ($slide = 0; $slide < count($values['slide_name']); $slide++) {
					if (isset($values['slide_hidden_image'][$slide]) && !empty($values['slide_hidden_image'][$slide])) {
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_banner_image SET banner_id = %d, banner_title = %s, link_type = %d, link_to = %d, sort_order = %d WHERE banner_image_id = %d", $values['vss_mab']['slideshow_id'], addslashes($values['slide_name'][$slide]), $values['slide_link_type'][$slide], $values['slide_link_to'][$slide], $values['slide_sort_order'][$slide], $values['slide_hidden_image'][$slide]));
					} elseif (isset($_FILES['slide_image']['name'][$image_counter]) && !empty($_FILES['slide_image']['name'][$image_counter])) {
						$allowed          = array(
							'jpg'  => array('image/jpg', 'image/jpeg'),
							'jpeg' => array('image/jpg', 'image/jpeg'),
							'png'  => array('image/png'),
						);
						$maxSize          = '2097152'; // 2 MB
						$document_path    = plugin_dir_path(__FILE__) . 'views/images/banners/';
						$filename         = strtolower(sanitize_text_field($_FILES['slide_image']['name'][$image_counter]));
						$filetype         = sanitize_text_field(isset($_FILES['slide_image']['type'][$image_counter]) ? $_FILES['slide_image']['type'][$image_counter] : '');
						$filesize         = sanitize_text_field(isset($_FILES['slide_image']['size'][$image_counter]) ? $_FILES['slide_image']['size'][$image_counter] : '');
						$ext              = pathinfo($filename, PATHINFO_EXTENSION);
						$upload_file_name = 'slide_' . time() . $image_counter . '.' . $ext;
						$upload_file_path = $document_path . $upload_file_name;

						if (isset($allowed[$ext]) && in_array($filetype, $allowed[$ext])) {
							if ($filesize <= $maxSize) {
								$file_new       = array(
									'name'     => sanitize_text_field(isset($_FILES['slide_image']['name']) ? $_FILES['slide_image']['name'] : ''),
									'type'     => sanitize_text_field(isset($_FILES['slide_image']['type']) ? $_FILES['slide_image']['type'] : ''),
									'tmp_name' => sanitize_text_field(isset($_FILES['slide_image']['tmp_name']) ? $_FILES['slide_image']['tmp_name'] : ''),
									'error'    => sanitize_text_field(isset($_FILES['slide_image']['error']) ? $_FILES['slide_image']['error'] : ''),
									'size'     => sanitize_text_field(isset($_FILES['slide_image']['size']) ? $_FILES['slide_image']['size'] : ''),
								);
								$uploaded_files = wmab_handle_logo_upload_main($file_new, 2);
								if ($uploaded_files) {
									$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_banner_image SET banner_image_id = '', banner_id = %d, banner_title = %s, link_type = %d, link_to = %d, image = %s, sort_order = %d", $values['vss_mab']['slideshow_id'], addslashes($values['slide_name'][$slide]), $values['slide_link_type'][$slide], $values['slide_link_to'][$slide], addslashes($upload_file_name), $values['slide_sort_order'][$slide]));
								}
							}
						}
						++$image_counter;
					}
				}
			}

			// Add Banner Images
			if (isset($values['banner_name']) && !empty($values['banner_name'])) {
				$image_counter = 0;
				for ($banner = 0; $banner < count($values['banner_name']); $banner++) {
					if (isset($values['banner_hidden_image'][$banner]) && !empty($values['banner_hidden_image'][$banner])) {
						$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_banner_image SET banner_id = %d, banner_title = %s, link_type = %d, link_to = %d, sort_order = %d WHERE banner_image_id = %d", $values['vss_mab']['banner_id'], addslashes($values['banner_name'][$banner]), $values['banner_link_type'][$banner], $values['banner_link_to'][$banner], $values['banner_sort_order'][$banner], $values['banner_hidden_image'][$banner]));
					} elseif (isset($_FILES['banner_image']['name'][$image_counter]) && !empty($_FILES['banner_image']['name'][$image_counter])) {
						$allowed          = array(
							'jpg'  => array('image/jpg', 'image/jpeg'),
							'jpeg' => array('image/jpg', 'image/jpeg'),
							'png'  => array('image/png'),
						);
						$maxSize          = '2097152'; // 2 MB
						$document_path    = plugin_dir_path(__FILE__) . 'views/images/banners/';
						$filename         = strtolower(sanitize_text_field($_FILES['banner_image']['name'][$image_counter]));
						$filetype         = sanitize_text_field(isset($_FILES['banner_image']['type'][$image_counter]) ? $_FILES['banner_image']['type'][$image_counter] : '');
						$filesize         = sanitize_text_field(isset($_FILES['banner_image']['size'][$image_counter]) ? $_FILES['banner_image']['size'][$image_counter] : '');
						$ext              = pathinfo($filename, PATHINFO_EXTENSION);
						$upload_file_name = 'banner_' . time() . $image_counter . '.' . $ext;
						$upload_file_path = $document_path . $upload_file_name;

						if (isset($allowed[$ext]) && in_array($filetype, $allowed[$ext])) {
							if ($filesize <= $maxSize) {
								$file_new       = array(
									'name'     => sanitize_text_field(isset($_FILES['banner_image']['name'][$image_counter]) ? $_FILES['banner_image']['name'][$image_counter] : ''),
									'type'     => sanitize_text_field(isset($_FILES['banner_image']['type'][$image_counter]) ? $_FILES['banner_image']['type'][$image_counter] : ''),
									'tmp_name' => sanitize_text_field(isset($_FILES['banner_image']['tmp_name'][$image_counter]) ? $_FILES['banner_image']['tmp_name'][$image_counter] : ''),
									'error'    => sanitize_text_field(isset($_FILES['banner_image']['error'][$image_counter]) ? $_FILES['banner_image']['error'][$image_counter] : ''),
									'size'     => sanitize_text_field(isset($_FILES['banner_image']['size'][$image_counter]) ? $_FILES['banner_image']['size'][$image_counter] : ''),
								);
								$uploaded_files = wmab_handle_logo_upload_main($file_new, 2);
								if ($uploaded_files) {
									$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_banner_image SET banner_image_id = '', banner_id = %d, banner_title = %s, link_type = %d, link_to = %d, image = %s, sort_order = %d", $values['vss_mab']['banner_id'], addslashes($values['banner_name'][$banner]), $values['banner_link_type'][$banner], $values['banner_link_to'][$banner], addslashes($upload_file_name), $values['banner_sort_order'][$banner]));
								}
							}
						}
						++$image_counter;
					}
				}
			}

			// Only Update only If Spin and win plugin enable in case of spin and win status enable
			if (!$spin_win_error) {
				update_option('wmab_settings', serialize($values['vss_mab']));
				wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_success=1');
				exit();
			} else {
				wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_error=1');
				exit();
			}
		}
	}

	// Submit action to send push notification
	if (isset($_POST['send_notification_submit']) && !empty($_POST['send_notification_submit'])) {

		$values = sanitize_post($_POST);

		// Code to send Push Notification and save details in History
		if (isset($values['notification_title']) && !empty($values['notification_title'])) {
			$notification_title = $values['notification_title'];
			$notification_msg   = $values['notification_msg'];
			$redirect_activity  = $values['notification_redirect_type'];
			$deviceType         = $values['notification_device_type'];

			$redirect_link         = '';
			$notification_category = 0;
			$notification_product  = 0;
			if (isset($redirect_activity) && !empty($redirect_activity)) {
				switch ($redirect_activity) {
					case 'home':
						$redirect_link = get_home_url();
						break;
					case 'category':
						$notification_category = $values['notification_category'];
						$redirect_link         = get_term_link((int) $notification_category, 'product_cat');
						break;
					case 'product':
						$notification_product = $values['notification_product'];
						$redirect_link        = get_permalink($notification_product);
						break;
				}
			}

			// Upload Image
			$notification_image = '';
			if (isset($_FILES['notification_image']['name']) && !empty($_FILES['notification_image']['name'])) {
				$allowed          = array(
					'jpg'  => array('image/jpg', 'image/jpeg'),
					'jpeg' => array('image/jpg', 'image/jpeg'),
					'png'  => array('image/png'),
				);
				$maxSize          = '2097152'; // 2 MB
				$document_path    = plugin_dir_path(__FILE__) . 'views/images/push_notifications/';
				$filename         = strtolower(sanitize_text_field($_FILES['notification_image']['name']));
				$filetype         = sanitize_text_field(isset($_FILES['notification_image']['type']) ? $_FILES['notification_image']['type'] : '');
				$filesize         = sanitize_text_field(isset($_FILES['notification_image']['size']) ? $_FILES['notification_image']['size'] : '');
				$ext              = pathinfo($filename, PATHINFO_EXTENSION);
				$upload_file_name = time() . '.' . $ext;
				$upload_file_path = $document_path . $upload_file_name;

				if (isset($allowed[$ext]) && in_array($filetype, $allowed[$ext])) {
					if ($filesize <= $maxSize) {
						$file_new       = array(
							'name'     => $upload_file_name,
							'type'     => sanitize_text_field(isset($_FILES['notification_image']['type']) ? $_FILES['notification_image']['type'] : ''),
							'tmp_name' => sanitize_text_field(isset($_FILES['notification_image']['tmp_name']) ? $_FILES['notification_image']['tmp_name'] : ''),
							'error'    => sanitize_text_field(isset($_FILES['notification_image']['error']) ? $_FILES['notification_image']['error'] : ''),
							'size'     => sanitize_text_field(isset($_FILES['notification_image']['size']) ? $_FILES['notification_image']['size'] : ''),
						);
						$uploaded_files = wmab_handle_logo_upload_main($file_new, 4);
						if ($uploaded_files) {
							$notification_image = $upload_file_name;
						}
					}
				}
			}

			// Prepare Push Notification data and send it
			$firebase_data                          = array();
			$firebase_data['data']['title']         = $notification_title;
			$firebase_data['data']['is_background'] = false;
			$firebase_data['data']['message']       = $notification_msg;
			$firebase_data['data']['image']         = plugins_url('/', __FILE__) . 'views/images/push_notifications/' . $notification_image;
			$firebase_data['data']['payload']       = '';
			$firebase_data['data']['user_id']       = '';
			$firebase_data['data']['push_type']     = 'promotional_' . $redirect_activity;
			$firebase_data['data']['category_id']   = $notification_category;
			$firebase_data['data']['product_id']    = $notification_product;
			$firebase_data['data']['filters']       = '';
			$firebase_data['data']['category_name'] = get_term_by('id', isset($_POST['notification_category']) ? sanitize_text_field($_POST['notification_category']) : '', 'product_cat')->name;
			$firebase_data['data']['click_action']  = $redirect_link;
			$firebase_data['data']['sound']         = 'default';
			$firebase_data['data']['alert']         = 'New';

			$firebase_server_key = $values['vss_mab']['push_notification_settings']['firebase_server_key'];

			/**
			 * Add functionality to send the notification to specific broadcast devices
			 *
			 * @date      1-02-2023
			 * @commenter Vishal Goyal
			 */
			if ('android' == $deviceType) {
				wmab_send_multiple('/topics/FLUTTER_ANDROID_USERS', $firebase_data['data'], $firebase_server_key);
			} elseif ('ios' == $deviceType) {
				wmab_send_multiple('/topics/FLUTTER_IOS_USERS', $firebase_data['data'], $firebase_server_key);
			} else {
				// For iOS
				wmab_send_multiple('/topics/FLUTTER_IOS_USERS', $firebase_data['data'], $firebase_server_key);
				// For Android
				wmab_send_multiple('/topics/FLUTTER_ANDROID_USERS', $firebase_data['data'], $firebase_server_key);
			}
			// Insert Push Notification details in the history table
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_push_notification_history SET notification_id = '', title = %s, message = %s, image_url = %s, redirect_activity = %s, category_id = %s, product_id = %s, date_added = now()", $notification_title, $notification_msg, $notification_image, $redirect_activity, $notification_category, $notification_product));
		}

		wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_notification_success=1');
		exit();
	}
	// Submit Tab Bar Form Details
	if (isset($_POST['tab_bar_form_submit']) && !empty($_POST['tab_bar_form_submit'])) {
		$post_data      = sanitize_post($_POST);
		$file_data      = rest_sanitize_array($_FILES);
		$tab_bar_image  = '';
		$update_tab_bar = false;
		$validate       = true;
		if (isset($post_data['tab_bar_id']) && !empty($post_data['tab_bar_id'])) {
			$update_tab_bar = true;
		}
		// Upload Image
		if (isset($file_data['tab_bar_images']['name']) && !empty($file_data['tab_bar_images']['name'])) {
			$allowed          = array(
				'jpg'  => array('image/jpg', 'image/jpeg'),
				'jpeg' => array('image/jpg', 'image/jpeg'),
				'png'  => array('image/png'),
			);
			$maxSize          = '2097152'; // 2 MB
			$document_path    = plugin_dir_path(__FILE__) . 'views/images/home_page_layout/';
			$filename         = strtolower($file_data['tab_bar_images']['name']);
			$filetype         = $file_data['tab_bar_images']['type'];
			$filesize         = $file_data['tab_bar_images']['size'];
			$ext              = pathinfo($filename, PATHINFO_EXTENSION);
			$upload_file_name = 'tab_bar_image_' . time() . '.' . $ext;
			$upload_file_path = $document_path . $upload_file_name;

			if (isset($allowed[$ext]) && in_array($filetype, $allowed[$ext])) {
				if ($filesize <= $maxSize) {
					$file_new       = array(
						'name'     => sanitize_text_field(isset($_FILES['tab_bar_images']['name']) ? $_FILES['tab_bar_images']['name'] : ''),
						'type'     => sanitize_text_field(isset($_FILES['tab_bar_images']['type']) ? $_FILES['tab_bar_images']['type'] : ''),
						'tmp_name' => sanitize_text_field(isset($_FILES['tab_bar_images']['tmp_name']) ? $_FILES['tab_bar_images']['tmp_name'] : ''),
						'error'    => sanitize_text_field(isset($_FILES['tab_bar_images']['error']) ? $_FILES['tab_bar_images']['error'] : ''),
						'size'     => sanitize_text_field(isset($_FILES['tab_bar_images']['size']) ? $_FILES['tab_bar_images']['size'] : ''),
					);
					$uploaded_files = wmab_handle_logo_upload_main($file_new, 3);
					if ($uploaded_files) {
						$tab_bar_image = $upload_file_name;
						$sql           = '';
						if ($update_tab_bar) {
							// Update Query with Image Update
							$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_mobileapp_tab_bar SET tab_icon_text = %s, tab_icon_image = %s, tab_icon_redirect_activity = %s WHERE tab_icon_id = %s;", $post_data['tab_icon_text_1'], $tab_bar_image, $post_data['tab_icon_redirect_type'], $post_data['tab_bar_id']));
						} else {
							// Insert Query with Image Insert

							$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_mobileapp_tab_bar SET tab_icon_text = %s, tab_icon_image = %s, tab_icon_redirect_activity = %s;", $post_data['tab_icon_text_1'], $tab_bar_image, $post_data['tab_icon_redirect_type']));
						}
						// Execute Sql query

						wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_tab_bar_success=1');
						exit();
					} else {
						$validate = false;
					}
				} else {
					$validate = false;
				}
			} else {
				$validate = false;
			}
		} elseif ($update_tab_bar) {
			// Update Query with Image Update
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_mobileapp_tab_bar SET tab_icon_text = %s, tab_icon_redirect_activity = %s WHERE tab_icon_id = %s;", $post_data['tab_icon_text_1'], $post_data['tab_icon_redirect_type'], $post_data['tab_bar_id']));
			// Update Without Image
			wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_tab_bar_success=1');
			exit();
		}
		if (!$validate) {
			wp_safe_redirect('admin.php?page=mobile-app-builder&wmab_tab_bar_error=1');
			exit();
		}
	}

	// Date Picker JS and CSS
	wp_enqueue_style('jquery-ui');
	wp_enqueue_script('jquery-ui-datepicker');

	if (!isset($_GET['render_page']) || isset($_GET['render_page']) && 'mab-home-layout-page' != $_GET['render_page']) {
		wp_enqueue_style('custom_bootscrap', plugins_url('/', __FILE__) . 'views/css/custom_bootscrap.css', array(), wp_get_theme()->get('Version'));
		// Calling File contains HTML script of General Settings
		include plugin_dir_path(__FILE__) . 'views/settings.php';
	} elseif (isset($_GET['render_page']) && 'mab-home-layout-page' == $_GET['render_page']) {
		/**
		 * BOC neeraj.kumar@velsof.com Module Upgrade V2
		 *
		 * @date  : 20-Dec-2019
		 * @param : GET : mab-home-layout-page
		 * @desc  : If mab-home-layout-page paramter get from Get request then change page view
		 */
		// CSS Includes
		wp_enqueue_style('bootstrap.min', plugins_url('/', __FILE__) . 'views/css/bootstrap.min.css', array(), wp_get_theme()->get('Version'));
		wp_enqueue_style('scroll-bar-css', plugins_url('/', __FILE__) . 'views/css/jquery.mCustomScrollbar.css', array(), wp_get_theme()->get('Version'));
		// JS Includes
		wp_enqueue_script('scroll-bar-js', plugins_url('/', __FILE__) . 'views/js/jquery.mCustomScrollbar.min.js', array(), wp_get_theme()->get('Version'));

		// Time Picker Add-on
		wp_enqueue_script('jquery-ui-timepicker-addon', plugins_url('/', __FILE__) . 'views/js/jquery-ui-timepicker-addon.js', array(), wp_get_theme()->get('Version'));
		wp_enqueue_style('jquery-ui-timepicker-addon', plugins_url('/', __FILE__) . 'views/css/jquery-ui-timepicker-addon.css', array(), wp_get_theme()->get('Version'));
		// MAB CSS for preview
		wp_enqueue_style('mab', plugins_url('/', __FILE__) . 'views/css/mab.css', array(), wp_get_theme()->get('Version'));

		/**
		 * JQUERY UI CSS for the datepicker
		 * WCMAB002-Calendar-UI-Fixes
		 * Date: 24-04-2024
		 *
		 * @author Satyam
		 */
		wp_enqueue_style('jquery-ui-css', plugins_url('/', __FILE__) . 'views/css/jquery-ui.css', array(), wp_get_theme()->get('Version'));
		// Calling File contains HTML script of General Settings
		include plugin_dir_path(__FILE__) . 'views/home-page-layout.php';
	}

	// Include Plugin CSS
	// wp_enqueue_style('bootstrap-datetimepicker', plugins_url('/', __FILE__) . 'views/css/bootstrap-datetimepicker.min.css');
	wp_enqueue_style('plugin', plugins_url('/', __FILE__) . 'views/css/plugin.css', array(), wp_get_theme()->get('Version'));
	wp_enqueue_style('font-awesome.min', plugins_url('/', __FILE__) . 'views/css/font-awesome.min.css', array(), wp_get_theme()->get('Version'));

	// Include Plugin JS
	// wp_enqueue_script('moment.min', plugins_url('/', __FILE__) . 'views/js/moment.min.js' );
	// wp_enqueue_script('bootstrap-datetimepicker.min', plugins_url('/', __FILE__) . 'views/js/bootstrap-datetimepicker.min.js' );

	// Color Picker JS and CSS
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wp-color-picker');

	wp_enqueue_script('plugin', plugins_url('/', __FILE__) . 'views/js/plugin.js', array(), wp_get_theme()->get('Version'));
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 *
 * @date  : 20-Dec-2019
 * @param : KEY : layout id in case of edit layout , name : new/edit layout details
 */
// Function to add/edit layout details
function wmab_change_layout_details()
{
	global $wpdb;
	$response = 'false';
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Without Layout name : nothing do
	if (isset($_POST['layout_name']) && !empty($_POST['layout_name'])) {
		// Check if Layout Id set that means we need to edit layout name else add new layout
		if (isset($_POST['layout_id']) && !empty($_POST['layout_id'])) {
			// Edit details
			$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_mobileapp_layouts SET layout_name = %s WHERE {$wpdb->prefix}mab_mobileapp_layouts.id_layout = %d", sanitize_text_field($_POST['layout_name']), sanitize_text_field($_POST['layout_id'])));
		} else {
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_mobileapp_layouts (layout_name) VALUES (%s)", sanitize_text_field($_POST['layout_name'])));
		}

		$response = 'true';
	}
	echo wp_json_encode($response); // phpcs:ignore
	die();
}

function wmab_save_layout_component_order()
{
	global $wpdb;
	$return_component_id             = array();
	$return_component_id['response'] = false;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['layout_category_type']) && !empty($_POST['layout_category_type']) && isset($_POST['layout_id']) && !empty($_POST['layout_id'])) {
		$post_data = $_POST['layout_category_type'];

		$positions = 1;
		// Layout Category Page
		foreach ($post_data as $key => $value) {
			// check component id is already set or not if set then we edit position else create new entries and saved component id
			$lastid = '';
			if (isset($value['component_id']) && !empty($value['component_id'])) {
				$lastid = sanitize_text_field($value['component_id']);
				$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mab_mobileapp_layout_component SET position = %d WHERE {$wpdb->prefix}mab_mobileapp_layout_component.id_component = %s", $positions, sanitize_text_field($value['component_id'])));
			} else {
				$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}mab_mobileapp_layout_component (id_layout, id_component_type, position) VALUES (%d, %d, %d)", sanitize_text_field($_POST['layout_id']), sanitize_text_field($value['component_type']), $positions));
				$lastid = $wpdb->insert_id;
			}
			$return_component_id['component_ids'][] = $lastid;
			++$positions;
		}
		$return_component_id['response'] = true;
		echo wp_json_encode($return_component_id); // phpcs:ignore
		die();
	} else {
		$return_component_id['response'] = true;
		echo wp_json_encode($return_component_id); // phpcs:ignore
		die();
	}
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 *
 * @date : 21-dec-2019
 */
function wmab_delete_layout_component_order()
{
	global $wpdb;
	$response = false;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['component_id_delete']) && !empty($_POST['component_id_delete'])) {
		$response = true;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE {$wpdb->prefix}mab_mobileapp_layout_component.id_component = %d", sanitize_text_field($_POST['component_id_delete'])));
		echo wp_json_encode($response); // phpcs:ignore
		die();
	} else {
		echo wp_json_encode($response); // phpcs:ignore
		die();
	}
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 *
 * @date : 23-dec-2019
 */
function wmab_get_product_details()
{
	global $wpdb;
	$response             = array();
	$response['response'] = false;

	// Get Upload folder path and URL
	$default_upload_dirs  = wp_upload_dir();
	$upload_directory     = '';
	$upload_directory_url = '';
	if (!empty($default_upload_dirs['basedir'])) {
		$upload_directory     = $default_upload_dirs['basedir'] . '/knowband';
		$upload_directory_url = $default_upload_dirs['baseurl'] . '/knowband/';
	}
	// Ends
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['component_id']) && !empty($_POST['component_id']) && isset($_POST['modal_id']) && !empty($_POST['modal_id'])) {
		if ('product-grid' == $_POST['modal_id']) {
			$heading         = '';
			$component_title = '';
			$result_row      = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			if (isset($result_row) && !empty($result_row)) {
				$heading         = $result_row->component_heading;
				$component_title = $result_row->component_title;
			}
			// get category_id of a corresponding component id
			$result_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_product_data WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			if (isset($result_row) && !empty($result_row)) {
				$response['response']     = true;
				$response['product_data'] = array(
					'product_type'       => $result_row->product_type,
					'image_content_mode' => $result_row->image_content_mode,
					'number_of_products' => $result_row->number_of_products,
					'component_heading'  => $heading,
					'component_title'    => $component_title,
					// BOC neeraj.kumar@velsof.com : 28-Jan-2020 Added custom product and category product changes.
					'category_products'  => $result_row->category_products,
					'custom_products'    => $result_row->custom_products,
					'id_category'        => $result_row->id_category,
					// EOC
				);
			} else {
				// If corresponding component id entries not found then form perisistence not required
				$response['response'] = false;
			}
		} elseif ('top-category' == $_POST['modal_id']) {
			// Form Persistence: Top category
			// Get Component title
			$component_title = '';
			$result_row      = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			if (isset($result_row->component_title) && !empty($result_row->component_title)) {
				$component_title = $result_row->component_title;
			}
			// get category_id of a corresponding component id

			$result_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_top_category WHERE id_component = %d ORDER BY id ASC", sanitize_text_field($_POST['component_id'])));
			if (isset($result_rows) && !empty($result_rows)) {
				$response['response'] = true;
				foreach ($result_rows as $keyTopCategory => $valueTopCategory) {
					if (file_exists($upload_directory . '/' . $valueTopCategory->image_url)) {
						$uploaded_image_path = $upload_directory_url . $valueTopCategory->image_url;
					} else {
						$uploaded_image_path = plugins_url('/', __FILE__) . 'views/images/home_page_layout/' . $valueTopCategory->image_url;
					}
					$response['top_product_data'][] = array(
						'id_component'       => $valueTopCategory->id_component,
						'id_category'        => $valueTopCategory->id_category,
						'image_url'          => $uploaded_image_path,
						'hidden_image_url'   => $valueTopCategory->image_url,
						'image_content_mode' => $valueTopCategory->image_content_mode,
						'category_heading'   => $valueTopCategory->category_heading,
						'component_title'    => $component_title,
					);
				}
			} else {
				$response['response'] = false;
			}
		} elseif ('banner-sqaure' == $_POST['modal_id']) {
			// Banner form
			// Get Component heading
			// get category_id of a corresponding component id
			$result_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			// get category_id of a corresponding component id
			$result_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d ORDER BY id ASC", sanitize_text_field($_POST['component_id'])));
			if (isset($result_rows) && !empty($result_rows)) {
				$response['response'] = true;
				foreach ($result_rows as $keyBanner => $valueBanner) {
					if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
						$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
					} else {
						$uploaded_image_path = plugins_url('/', __FILE__) . 'views/images/home_page_layout/' . $valueBanner->image_url;
					}
					$response['banner_options'][] = array(
						'id_component'      => $valueBanner->id_component,
						'id_banner_type'    => $valueBanner->id_banner_type,
						'product_id'        => $valueBanner->product_id,
						'category_id'       => $valueBanner->category_id,
						'redirect_activity' => $valueBanner->redirect_activity,
						'image_url'         => $uploaded_image_path,
						'hidden_image_url'  => $valueBanner->image_url,
						'image_contentMode' => $valueBanner->image_contentMode,
						'banner_heading'    => $valueBanner->banner_heading,
					);
				}
				if (isset($result_row->component_heading) && !empty($result_row->component_heading)) {
					$response['banner_options'][0]['component_heading'] = $result_row->component_heading;
				} else {
					$response['banner_options'][0]['component_heading'] = '';
				}
				// Banner Title
				if (isset($result_row->component_title) && !empty($result_row->component_title)) {
					$response['banner_options'][0]['component_title'] = $result_row->component_title;
				} else {
					$response['banner_options'][0]['component_title'] = '';
				}
			} else {
				$response['response'] = false;
			}
		} elseif ('banner-custom' == $_POST['modal_id']) {
			// Custom Banner
			// Get Component heading
			// get category_id of a corresponding component id
			$result_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			// get category_id of a corresponding component id
			$result_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d ORDER BY id ASC", sanitize_text_field($_POST['component_id'])));
			if (isset($result_rows) && !empty($result_rows)) {
				$response['response'] = true;
				foreach ($result_rows as $keyBanner => $valueBanner) {
					if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
						$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
					} else {
						$uploaded_image_path = plugins_url('/', __FILE__) . 'views/images/home_page_layout/' . $valueBanner->image_url;
					}
					$response['banner_options'][] = array(
						'id_component'                   => $valueBanner->id_component,
						'id_banner_type'                 => $valueBanner->id_banner_type,
						'product_id'                     => $valueBanner->product_id,
						'category_id'                    => $valueBanner->category_id,
						'redirect_activity'              => $valueBanner->redirect_activity,
						'image_url'                      => $uploaded_image_path,
						'hidden_image_url'               => $valueBanner->image_url,
						'image_contentMode'              => $valueBanner->image_contentMode,
						'banner_heading'                 => $valueBanner->banner_heading,
						// Custom Field
						'banner_custom_background_color' => isset($valueBanner->banner_custom_background_color) && !empty($valueBanner->banner_custom_background_color) ? $valueBanner->banner_custom_background_color : '#ffffff',
						'inset_top'                      => isset($valueBanner->inset_top) && !empty($valueBanner->inset_top) ? $valueBanner->inset_top : 0,
						'inset_bottom'                   => isset($valueBanner->inset_bottom) && !empty($valueBanner->inset_bottom) ? $valueBanner->inset_bottom : 0,
						'inset_left'                     => isset($valueBanner->inset_left) && !empty($valueBanner->inset_left) ? $valueBanner->inset_left : 0,
						'inset_right'                    => isset($valueBanner->inset_right) && !empty($valueBanner->inset_right) ? $valueBanner->inset_right : 0,
						'banner_width'                   => isset($valueBanner->banner_width) && !empty($valueBanner->banner_width) ? $valueBanner->banner_width : 0,
						'banner_height'                  => isset($valueBanner->banner_height) && !empty($valueBanner->banner_height) ? $valueBanner->banner_height : 0,
					);
				}
				if (isset($result_row->component_heading) && !empty($result_row->component_heading)) {
					$response['banner_options'][0]['component_heading'] = $result_row->component_heading;
				} else {
					$response['banner_options'][0]['component_heading'] = '';
				}
				// Banner Title
				if (isset($result_row->component_title) && !empty($result_row->component_title)) {
					$response['banner_options'][0]['component_title'] = $result_row->component_title;
				} else {
					$response['banner_options'][0]['component_title'] = '';
				}
			} else {
				$response['response'] = false;
			}
		} elseif ('banner-countdown-timer' == $_POST['modal_id']) {
			// Banner CountDown
			// Get Component heading
			// get category_id of a corresponding component id
			$result_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_layout_component WHERE id_component = %d", sanitize_text_field($_POST['component_id'])));
			// get category_id of a corresponding component id
			$result_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_banners WHERE id_component = %d ORDER BY id ASC", sanitize_text_field($_POST['component_id'])));
			if (isset($result_rows) && !empty($result_rows)) {
				$response['response'] = true;
				foreach ($result_rows as $keyBanner => $valueBanner) {
					if (file_exists($upload_directory . '/' . $valueBanner->image_url)) {
						$uploaded_image_path = $upload_directory_url . $valueBanner->image_url;
					} else {
						$uploaded_image_path = plugins_url('/', __FILE__) . 'views/images/home_page_layout/' . $valueBanner->image_url;
					}
					$response['banner_countdown_options'][] = array(
						'id_component'            => $valueBanner->id_component,
						'id_banner_type'          => $valueBanner->id_banner_type,
						'product_id'              => $valueBanner->product_id,
						'category_id'             => $valueBanner->category_id,
						'redirect_activity'       => $valueBanner->redirect_activity,
						'image_url'               => $uploaded_image_path,
						'hidden_image_url'        => $valueBanner->image_url,
						'image_contentMode'       => $valueBanner->image_contentMode,
						'banner_heading'          => $valueBanner->banner_heading,
						'timer_text_color'        => $valueBanner->text_color,
						'timer_background_color'  => $valueBanner->background_color,
						'timer_background_status' => $valueBanner->is_enabled_background_color,
						'timer_validity'          => gmdate('m/d/Y H:i', strtotime($valueBanner->countdown)),
					);
				}
				if (isset($result_row->component_heading) && !empty($result_row->component_heading)) {
					$response['banner_countdown_options'][0]['component_heading'] = $result_row->component_heading;
				} else {
					$response['banner_countdown_options'][0]['component_heading'] = '';
				}
				// Banner Title
				if (isset($result_row->component_title) && !empty($result_row->component_title)) {
					$response['banner_countdown_options'][0]['component_title'] = $result_row->component_title;
				} else {
					$response['banner_countdown_options'][0]['component_title'] = '';
				}
			} else {
				$response['response'] = false;
			}
		}
	} else {
		$response['response'] = false;
	}
	echo wp_json_encode($response); // phpcs:ignore
	die();
}

/**
 * Delete Layout Functionality
 */
function wmab_delete_layout()
{
	global $wpdb;
	$response = false;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['layout_id']) && !empty($_POST['layout_id'])) {
		$response = true;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_layouts WHERE id_layout = %d", sanitize_text_field($_POST['layout_id'])));
		
		echo wp_json_encode($response); // phpcs:ignore
		die();
	} else {
		echo wp_json_encode($response); // phpcs:ignore
		die();
	}
}

/**
 * Get Tab Bar detail from tab bar id
 * neeraj.kumar@velsof.com : 23-Jan-2020 : Module Upgrade V2
 */
function wmab_get_tab_bar_detail()
{
	global $wpdb;
	$response             = array();
	$response['response'] = false;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['tab_bar_id']) && !empty($_POST['tab_bar_id'])) {
		// Get Tab Bar Detail of a corresponding tab bar id
		$result_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mab_mobileapp_tab_bar WHERE tab_icon_id = %d", sanitize_text_field($_POST['tab_bar_id'])));
		if (isset($result_row) && !empty($result_row)) {
			$response['response']                   = true;
			$response['tab_icon_text']              = $result_row->tab_icon_text;
			$response['tab_icon_image']             = $result_row->tab_icon_image;
			$response['tab_icon_redirect_activity'] = $result_row->tab_icon_redirect_activity;
			$response['tab_bar_redirect_id']        = $result_row->tab_bar_redirect_id;
		}
	}
	echo wp_json_encode($response); // phpcs:ignore
	die();
}

/**
 * Save Tab Bar Layout Form Detail
 * neeraj.kumar@velsof.com : 23-Jan-2020
 */
function wmab_save_tab_bar_form()
{
	global $wpdb;
	$response             = array();
	$response['response'] = true;

	echo wp_json_encode($response); // phpcs:ignore
	die();
}

/**
 * Delete Tab Layout from Ajax request
 */
function wmab_delete_tab_bar()
{
	global $wpdb;
	$response             = array();
	$response['response'] = false;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['tab_bar_id']) && !empty($_POST['tab_bar_id'])) {
		$response['response'] = true;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}mab_mobileapp_tab_bar WHERE tab_icon_id = %s", sanitize_text_field($_POST['tab_bar_id'])));
	}

	echo wp_json_encode($response); // phpcs:ignore
	die();
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 * added update notification
 */
function wmab_home_layout_notice()
{
?>
	<div class="notice notice-success">
		<p><?php esc_attr_e('Home Layout successfully updated.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
<?php
}

if (isset($_GET['wmab_home_layout_success']) && sanitize_text_field($_GET['wmab_home_layout_success'])) {
	add_action('admin_notices', 'wmab_home_layout_notice');
}

/*
 * Function to get all FCM Data
 */
function wmab_get_fcm_data()
{
	global $wpdb;

	$query = $wpdb->get_results("SELECT DISTINCT(fcm_id) FROM {$wpdb->prefix}mab_fcm_details");

	return $query;
}

/*
 * Function to insert Sample Data
 */
function wmab_insert_sample_data()
{
	global $wpdb;

	$response = 'false';

	$default_data = array(
		'general'                    => array(
			'enabled'               => '1',
			'social_login_status'   => '1',
			'live_chat'             => '',
			'live_chat_api_key'     => '',
			'custom_css'            => '',
			'category_image_width'  => '200',
			'category_image_height' => '200',
			'product_image_width'   => '200',
			'product_image_height'  => '200',
			'product_new_date'      => '',
			'product_new_number'    => '10',
			'show_price'            => '1',
		),
		'push_notification_settings' => array(
			'firebase_server_key'               => '',
			'order_success_enabled'             => '0',
			'order_success_notification_title'  => '',
			'order_success_notification_msg'    => '',
			'order_status_enabled'              => '0',
			'order_status_notification_title'   => '',
			'order_status_notification_msg'     => '',
			'abandoned_cart_enabled'            => '0',
			'abandoned_cart_notification_title' => '',
			'abandoned_cart_notification_msg'   => '',
			'abandoned_cart_time_interval'      => '1',
		),
		'slideshow_id'               => '2',
		'banner_id'                  => '1',
		'payment_methods'            => array(
			'paypal_enabled'          => '',
			'payment_method_name'     => '',
			'payment_method_code'     => '',
			'payment_method_mode'     => '0',
			'client_id'               => '',
			'cod_enabled'             => '',
			'cod_payment_method_name' => '',
			'cod_payment_method_code' => '',
		),
		'featured'                   => array(
			'enabled'              => '1',
			'limit'                => '10',
			'product_image_width'  => '200',
			'product_image_height' => '200',
		),
		'specials'                   => array(
			'enabled'              => '1',
			'limit'                => '10',
			'product_image_width'  => '200',
			'product_image_height' => '200',
		),
		'bestsellers'                => array(
			'enabled'              => '1',
			'limit'                => '10',
			'product_image_width'  => '200',
			'product_image_height' => '200',
		),
		'latest'                     => array(
			'enabled'              => '1',
			'limit'                => '10',
			'product_image_width'  => '200',
			'product_image_height' => '200',
		),
		'google_login_settings'      => array(
			'enabled'     => '0',
			'google_json' => '',
		),
		'fb_login_settings'          => array(
			'enabled' => '0',
			'app_id'  => '',
		),
	);

	if (update_option('wmab_settings', serialize($default_data))) {
		// Banner/Slideshow Table
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner` SET `banner_id` = 1, `name` = 'Banner', `image_width` = '600', `image_height` = '200', `status` = '1', `banner_limit` = '5'");
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner` SET `banner_id` = 2, `name` = 'SlideShow', `image_width` = '600', `image_height` = '200', `status` = '1', `banner_limit` = '5'");

		// Banner/Slideshow Image Table
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner_image` SET `banner_id`  = '1', `banner_title` = 'Banner 1', `link_type` = '0', `link_to` = '0', `image` = 'banner1.jpg', `sort_order` = '1'");
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner_image` SET `banner_id`  = '1', `banner_title` = 'Banner 2', `link_type` = '0', `link_to` = '0', `image` = 'banner2.jpg', `sort_order` = '2'");
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner_image` SET `banner_id`  = '2', `banner_title` = 'Slide 1', `link_type` = '0', `link_to` = '0', `image` = 'slide1.jpg', `sort_order` = '1'");
		$wpdb->query("INSERT INTO `{$wpdb->prefix}mab_banner_image` SET `banner_id`  = '2', `banner_title` = 'Slide 2', `link_type` = '0', `link_to` = '0', `image` = 'slide2.jpg', `sort_order` = '2'");
		$response = 'true';
	}

	echo wp_json_encode($response); // phpcs:ignore
	die();
}

// Function to handle ajax request and respond with dropdown html
function wmab_get_link_to_options()
{
	$html = '';
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	if (isset($_POST['key']) && !empty($_POST['key'])) {
		switch ($_POST['key']) {
			case 1:
				// Get Product Categories
				$cat_args           = array(
					'taxonomy'   => 'product_cat',
					'orderby'    => 'name',
					'order'      => 'asc',
					'hide_empty' => false,
				);
				$product_categories = get_terms($cat_args);
				if (isset($product_categories) && !empty($product_categories)) {
					// explode array
					$selected_ids = array();
					if (isset($_POST['selected_id']) && !empty($_POST['selected_id'])) {
						$selected_ids = explode(',', sanitize_text_field($_POST['selected_id']));
					}
					foreach ($product_categories as $product_category) {
						// BOC neeraj.kumar@velsof.com Module Upgrade V2 check custom selected id isset or not
						if (isset($selected_ids) && !empty($selected_ids) && in_array($product_category->term_id, $selected_ids)) {
							$html .= '<option value="' . esc_attr($product_category->term_id) . '" selected>' . $product_category->name . '</option>';
						} else {
							$html .= '<option value="' . esc_attr($product_category->term_id) . '">' . $product_category->name . '</option>';
						}
					}
				}
				break;
			case 2:
				// Get All Products
				$args     = array(
					'status'  => 'publish',
					'orderby' => 'name',
					'limit'   => -1,
				);
				$products = wc_get_products($args);
				if (isset($products) && !empty($products)) {
					// explode array
					$selected_ids = array();
					if (isset($_POST['selected_id']) && !empty($_POST['selected_id'])) {
						$selected_ids = explode(',', sanitize_text_field($_POST['selected_id']));
					}
					foreach ($products as $product) {
						// BOC neeraj.kumar@velsof.com Module Upgrade V2 check custom selected id isset or not
						if (isset($selected_ids) && !empty($selected_ids) && in_array($product->id, $selected_ids)) {
							$html .= '<option value="' . esc_attr($product->id) . '" selected>' . $product->name . '</option>';
						} else {
							$html .= '<option value="' . esc_attr($product->id) . '">' . $product->name . '</option>';
						}
						// EOC
					}
				}
				break;
			case 3:
				// Get Produuct By Category Id:
				if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
					// Get All Products
					$args     = array(
						'status'    => 'publish',
						'post_type' => 'product',
						'orderby'   => 'name',
						'limit'     => -1,
						'tax_query' => array(
							array(
								'taxonomy' => 'product_cat',
								'terms'    => sanitize_text_field($_POST['category_id']),
								'operator' => 'IN',
							),
						),
					);
					$products = wc_get_products($args);

					if (isset($products) && !empty($products)) {
						// explode array
						$selected_ids = array();
						if (isset($_POST['selected_id']) && !empty($_POST['selected_id'])) {
							$selected_ids = explode(',', sanitize_text_field($_POST['selected_id']));
						}
						foreach ($products as $product) {
							// BOC neeraj.kumar@velsof.com Module Upgrade V2 check custom selected id isset or not
							if (isset($selected_ids) && !empty($selected_ids) && in_array($product->id, $selected_ids)) {
								$html .= '<option value="' . esc_attr($product->id) . '" selected>' . $product->name . '</option>';
							} else {
								$html .= '<option value="' . esc_attr($product->id) . '">' . $product->name . '</option>';
							}
							// EOC
						}
					} else {
						$html = '';
					}
				} else {
					$html = '';
				}
				break;
		}
	}
	$arr = array(
		'option' => array(
			'value'    => array(),
			'selected' => array(),
		),
	);
	echo wp_kses($html, $arr);
	die;
}

// Function to delete banner/slide details and image
function wmab_delete_banner()
{
	global $wpdb;
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$response = 'false';
	if (isset($_POST['key']) && !empty($_POST['key'])) {
		$banner_details = $wpdb->get_row($wpdb->prepare("SELECT image FROM {$wpdb->prefix}mab_banner_image WHERE banner_image_id = %d", sanitize_text_field($_POST['key'])));

		if (isset($banner_details->image) && !empty($banner_details->image)) {
			$image_path = plugin_dir_path(__FILE__) . 'views/images/banners/' . $banner_details->image;

			if (file_exists($image_path)) {
				unlink($image_path);
			}

			$wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->prefix}mab_banner_image` WHERE banner_image_id = %d", sanitize_text_field($_POST['key'])));
			$response = 'true';
		}
	}
	echo wp_json_encode($response); // phpcs:ignore
	die();
}

// Function to send push notifications
function wmab_send_multiple($registration_ids, $message, $server_key)
{
	// notification for iOS
	if ('/topics/FLUTTER_IOS_USERS' == $registration_ids) {
		$fields = array(
			'to'                => $registration_ids,
			'data'              => $message,
			'priority'          => 'high',
			'mutable_content'   => true,
			'content_available' => true,
			'notification'      => array(
				'title'     => $message['title'],
				'body'      => $message['message'],
				'image'     => $message['image'],
				'push_type' => $message['push_type'],
				'sound'     => $message['sound'],
				'alert'     => $message['alert'],
			),
		);
	} else {
		// notification for android
		$fields = array(
			'to'           => $registration_ids,
			'data'         => $message,
			'notification' => array(
				'title'     => $message['title'],
				'body'      => $message['message'],
				'image'     => $message['image'],
				'push_type' => $message['push_type'],
				'sound'     => $message['sound'],
				'alert'     => $message['alert'],
			),
		);
	}
	return wmab_send_push_notification($fields, $server_key);
}

/**
 * Function to send push notification
 *
 * @param array $fields	notification data
 * @param string $server_key firebase server key
 *
 * @return mixed
 */
function wmab_send_push_notification($fields, $server_key = '')
{

	// Set POST variables
	$url = 'https://fcm.googleapis.com/fcm/send';

	$headers = array(
		'Authorization' => 'key=' . $server_key,
		'Content-Type'  => 'application/json',
	);
	$args    = array(
		'headers'   => $headers,
		'body'      => json_encode($fields),
		'sslverify' => false,
	);
	$result  = wp_remote_post($url, $args);
	return $result;
}

function wmab_success_notice()
{
?>
	<div class="notice notice-success">
		<p><?php esc_attr_e('Configuration values have been saved successfully.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
<?php
}

if (isset($_GET['wmab_success']) && sanitize_text_field($_GET['wmab_success'])) {
	add_action('admin_notices', 'wmab_success_notice');
}
// Display Error message when spin and win module status disable and we tring to add itegration with enable status
if (isset($_GET['wmab_error']) && sanitize_text_field($_GET['wmab_error'])) {
	add_action('admin_notices', 'wmab_error_notice');
}

function wmab_error_notice()
{
?>
	<div class="notice notice-error">
		<p><?php esc_attr_e('Kindly install and enable the Spin Win module first to enable its functionality in the app.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
<?php
}

function wmab_notification_success_notice()
{
?>
	<div class="notice notice-success">
		<p><?php esc_attr_e('Push Notification has been sent successfully.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
<?php
}

if (isset($_GET['wmab_notification_success']) && sanitize_text_field($_GET['wmab_notification_success'])) {
	add_action('admin_notices', 'wmab_notification_success_notice');
}
/**
 * Tab Bar Success Or Error Message
 */
if (isset($_GET['wmab_tab_bar_success']) && sanitize_text_field($_GET['wmab_tab_bar_success'])) {
	add_action('admin_notices', 'wmab_tab_bar_success_notice');
}
if (isset($_GET['wmab_tab_bar_error']) && sanitize_text_field($_GET['wmab_tab_bar_error'])) {
	add_action('admin_notices', 'wmab_tab_bar_error_notice');
}

function wmab_tab_bar_success_notice()
{
?>
	<div class="notice notice-success">
		<p><?php esc_attr_e('Tab Bar saved successfully.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
<?php
}

function wmab_tab_bar_error_notice()
{
?>
	<div class="notice notice-error">
		<p><?php esc_attr_e('Tab Bar not saved successfully.', 'knowband-mobile-app-builder-for-woocommerce'); ?></p>
	</div>
	<?php
}

/**
 * Error Message Display : Module Upgrade V2
 *
 * @date   : 23-Dec-2019
 */
if (isset($_SESSION['wmab_form_save_error']) && $_SESSION['wmab_form_save_error']) {
	add_action('admin_notices', 'wmab_form_save_error');
}
if (isset($_SESSION['wmab_form_save_success']) && $_SESSION['wmab_form_save_success']) {
	add_action('admin_notices', 'wmab_form_save_success');
}

function wmab_form_save_error()
{
	if (isset($_SESSION['wmab_form_save_error']) && !empty($_SESSION['wmab_form_save_error'])) {
	?>
		<div class="notice notice-error">
			<p><?php echo esc_html(sanitize_text_field($_SESSION['wmab_form_save_error'])); ?></p>
		</div>
	<?php
		unset($_SESSION['wmab_form_save_error']);
	}
}

function wmab_form_save_success()
{
	if (isset($_SESSION['wmab_form_save_success']) && !empty($_SESSION['wmab_form_save_success'])) {
	?>
		<div class="notice notice-success">
			<p><?php echo esc_html(sanitize_text_field($_SESSION['wmab_form_save_success'])); ?></p>
		</div>
<?php
		unset($_SESSION['wmab_form_save_success']);
	}
}

// EOC Module Upgrade V2

/*
 * Function to define AJAX URL in Plugin scope of front end
 */
function wmab_ajaxurl()
{
	echo '<script type="text/javascript">
           var ajaxurl = "' . esc_url(admin_url('admin-ajax.php')) . '";
         </script>';
}

add_action('wp_head', 'wmab_ajaxurl');

/*
 * Function to add/enqueue all the JS and CSS on front
 */

function wmab_enqueue_js_css_admin()
{
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('velovalidation', plugins_url('/', __FILE__) . 'views/js/velovalidation.js', array(), wp_get_theme()->get('Version'));
}

add_action('admin_enqueue_scripts', 'wmab_enqueue_js_css_admin');

/*
 * API Handler
 */
// appGetConfig - Endpoint added for Config API to get configuration parameters - Module Upgrade V2 - Added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetConfig/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_config',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetConfig/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_config',
			)
		);
	}
);

// appGetCheck - Endpoint added for Config API to get configuration parameters - Module Upgrade V2 - Added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetCheck/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_check',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetCheck/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_check',
			)
		);
	}
);


// appGetCategoryDetails
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetCategoryDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_category_details',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetCategoryDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_category_details',
			)
		);
	}
);
// appGetFilters
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetFilters/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_filters',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetFilters/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_filters',
			)
		);
	}
);
// appGetProductDetails
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetProductDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_product_details',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetProductDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_product_details',
			)
		);
	}
);
// appGetProductReviews
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetProductReviews/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_product_reviews',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetProductReviews/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_product_reviews',
			)
		);
	}
);
// appAddToCart
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appAddToCart/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_to_cart',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appAddToCart/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_to_cart',
			)
		);
	}
);
// appGetCartDetails
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetCartDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_cart_details',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetCartDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_cart_details',
			)
		);
	}
);
// appGetCustomerAddress
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetCustomerAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_customer_address',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetCustomerAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_customer_address',
			)
		);
	}
);
// appGetAddressForm
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetAddressForm/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_address_form',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetAddressForm/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_address_form',
			)
		);
	}
);
// appAddAddress
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appAddAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_address',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appAddAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_address',
			)
		);
	}
);
// appAddReview
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appSaveProductReview/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_review',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appSaveProductReview/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_add_review',
			)
		);
	}
);
// appUpdateAddress
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appUpdateAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_address',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appUpdateAddress/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_address',
			)
		);
	}
);
// appSocialLogin
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appSocialLogin/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_social_login',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appSocialLogin/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_social_login',
			)
		);
	}
);
// appLogin
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appLogin/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appLogin/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login',
			)
		);
	}
);
// appLoginViaPhone - Endpoint added for Login Via Phone API to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appLoginViaPhone/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login_via_phone',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appLoginViaPhone/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login_via_phone',
			)
		);
	}
);
// appLoginViaEmail - Endpoint added for Login Via Email/FingerPrint API to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appLoginViaEmail/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login_via_email',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appLoginViaEmail/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_login_via_email',
			)
		);
	}
);
// appMapEmailWithUUID - Endpoint added for mapping Email and Unique ID API to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appMapEmailWithUUID/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_map_email_with_uuid',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appMapEmailWithUUID/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_map_email_with_uuid',
			)
		);
	}
);
// appCheckIfContactNumberExists - Endpoint added for Phone Number verification API to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCheckIfContactNumberExists/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_if_contact_number_exists',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCheckIfContactNumberExists/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_if_contact_number_exists',
			)
		);
	}
);
// appRegisterUser
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appRegisterUser/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_register_user',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appRegisterUser/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_register_user',
			)
		);
	}
);
// appForgotPassword
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appForgotPassword/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_forgot_password',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appForgotPassword/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_forgot_password',
			)
		);
	}
);
// appGetRegions
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetRegions/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_regions',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetRegions/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_regions',
			)
		);
	}
);
// appApplyVoucher
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appApplyVoucher/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_apply_voucher',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appApplyVoucher/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_apply_voucher',
			)
		);
	}
);
// appRemoveVoucher
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appRemoveVoucher/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_remove_voucher',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appRemoveVoucher/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_remove_voucher',
			)
		);
	}
);
// appRemoveProduct
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appRemoveProduct/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_remove_product',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appRemoveProduct/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_remove_product',
			)
		);
	}
);
// appUpdateCartQuantity
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appUpdateCartQuantity/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_cart_quantity',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appUpdateCartQuantity/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_cart_quantity',
			)
		);
	}
);
// appGetRegistrationForm
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetRegistrationForm/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_registration_form',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetRegistrationForm/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_registration_form',
			)
		);
	}
);
// appUpdateProfile
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appUpdateProfile/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_profile',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appUpdateProfile/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_profile',
			)
		);
	}
);
// appUpdatePassword - Endpoint for Customer Password Update API - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appUpdatePassword/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_password',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appUpdatePassword/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_update_password',
			)
		);
	}
);
// appGuestRegistration
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGuestRegistration/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_guest_registration',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGuestRegistration/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_guest_registration',
			)
		);
	}
);
// appCheckout
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCheckout/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_checkout',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCheckout/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_checkout',
			)
		);
	}
);
// appGetOrderDetails
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetOrderDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_order_details',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetOrderDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_order_details',
			)
		);
	}
);
// appGetOrders
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetOrders/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_orders',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetOrders/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_orders',
			)
		);
	}
);
// appSetShippingMethod
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appSetShippingMethod/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_set_shipping_method',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appSetShippingMethod/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_set_shipping_method',
			)
		);
	}
);
// appReorder
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appReorder/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_reorder',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appReorder/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_reorder',
			)
		);
	}
);
// appGetPaymentMethods
add_action(
	'rest_api_init',
	function () {
		// wmab_app_payment();
		register_rest_route(
			'wmab/v1.2',
			'/AppPayment/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_payment',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/AppPayment/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_payment',
			)
		);
	}
);
// appGetMobilePaymentMethods
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetMobilePaymentMethods/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_mobile_payment_methods',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetMobilePaymentMethods/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_mobile_payment_methods',
			)
		);
	}
);
// appCreateOrder
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCreateOrder/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_create_order',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCreateOrder/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_create_order',
			)
		);
	}
);
// appGetHome
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetHome/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_home',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetHome/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_home',
			)
		);
	}
);
// appCheckLogStatus
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCheckLogStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_log_status',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCheckLogStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_log_status',
			)
		);
	}
);
// appCheckLiveChatSupportStatus
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCheckLiveChatSupportStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_live_chat_support_status',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCheckLiveChatSupportStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_live_chat_support_status',
			)
		);
	}
);
// appFCMregister
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appFCMregister/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_fcm_register',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appFCMregister/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_fcm_register',
			)
		);
	}
);
// appGetTranslations
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appGetTranslations/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_translations',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetTranslations/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_translations',
			)
		);
	}
);
// appSendAbandonedCartPushNotification
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appSendAbandonedCartPushNotification/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_send_abandoned_cart_push_notification',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appSendAbandonedCartPushNotification/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_send_abandoned_cart_push_notification',
			)
		);
	}
);
// appCheckOrderStatus
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appCheckOrderStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_order_status',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appCheckOrderStatus/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_check_order_status',
			)
		);
	}
);
// appLogout
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appLogout/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_logout',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appLogout/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_logout',
			)
		);
	}
);
// appGetSpinWin
add_action(
	'rest_api_init',
	function () {
		// wmab_app_get_spin_win();
		register_rest_route(
			'wmab/v1.2',
			'/appGetSpinWin/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_get_spin_win',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appGetSpinWin/',
			array(
				'methods'  => 'GET',
				'callback' => 'wmab_app_get_spin_win',
			)
		);
	}
);

// appHandleDeepLink
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appHandleDeepLink/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_handle_deep_link',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appHandleDeepLink/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_handle_deep_link',
			)
		);
	}
);

// appValidateSignUpFormDetails
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v2.0',
			'/appValidateSignUpFormDetails/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_valide_signup',
			)
		);
	}
);

// appDeleteUser
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wmab/v1.2',
			'/appDeleteUser/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_delete_user',
			)
		);
		register_rest_route(
			'wmab/v2.0',
			'/appDeleteUser/',
			array(
				'methods'  => 'POST',
				'callback' => 'wmab_app_delete_user',
			)
		);
	}
);

// Define API Version
define('WMAB_API_VERSION', '2.0');
define('WMAB_URL', plugins_url('/', __FILE__));
define('WMAB_DIR', plugin_dir_path(__FILE__));

// BOC - hagarwal@velsof.com - 07-May-2019 - Load Text Domain and Mo Files for translations
if (isset($_REQUEST['iso_code']) && !empty($_REQUEST['iso_code'])) {
	$locale_iso = sanitize_text_field($_REQUEST['iso_code']);
	$mofile     = 'knowband-mobile-app-builder-for-woocommerce-' . $locale_iso . '.mo';
	load_textdomain('knowband-mobile-app-builder-for-woocommerce', dirname(__FILE__) . '/languages/' . $mofile);
}

// API Call for appGetConfig to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_get_check($api_object = array())
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';
	
	// Create class object
	$wmab_home = new WmabHome('appGetCheck');

	return $wmab_home->app_get_check();
}

// API Call for appGetConfig to get configuration parameters - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_get_config($api_object = array())
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab category class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appGetConfig');

	$session_data  = !empty($_REQUEST['session_data']) ? sanitize_text_field($_REQUEST['session_data']) : '';
	$all_app_texts = !empty($_POST['all_app_texts']) ? sanitize_text_field($_POST['all_app_texts']) : '0';
	$iso_code      = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : ''; // Added for WPML compatibility by Harsh on 16-Apr-2019
	$is_demo       = !empty($_REQUEST['is_demo']) ? sanitize_text_field($_REQUEST['is_demo']) : ''; // Added for WPML compatibility by Harsh on 16-Apr-2019
	$email         = !empty($_REQUEST['email']) ? sanitize_text_field($_REQUEST['email']) : '';

	return $wmab_home->app_get_config($version, $iso_code, $is_demo, $all_app_texts, $session_data, $email);
}

// API Call for appGetCategoryDetails to get category details and products based on category id
function wmab_app_get_category_details()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}

	$version = WMAB_API_VERSION;

	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-category.php';

	// Create class object
	$wmab_category = new WmabCategory('appGetCategoryDetails');

	$page_number = !empty($_POST['page_number']) ? sanitize_text_field($_POST['page_number']) : '1';
	$item_count  = !empty($_POST['item_count']) ? sanitize_text_field($_POST['item_count']) : '20';
	$sort_by     = !empty($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'relevance';
	$filter      = !empty($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
	$category_id = !empty($_POST['category_id']) ? sanitize_text_field($_POST['category_id']) : '';
	$search_term = !empty($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
	$iso_code    = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : ''; // Added for WPML compatibility by Harsh on 16-Apr-2019
	// Session_data and email parameters added to get cart details for Home Page - added by Harsh Agarwal on 03-Sep-2020
	$session_data = !empty($_REQUEST['session_data']) ? sanitize_text_field($_REQUEST['session_data']) : '';
	$email        = !empty($_REQUEST['email']) ? sanitize_text_field($_REQUEST['email']) : '';

	return $wmab_category->app_get_category_details($category_id, $search_term, $filter, $sort_by, $item_count, $page_number, $version, $iso_code, $session_data, $email);
}

// API Call for appGetFilters to get filters based on category id
function wmab_app_get_filters()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Include file to import wmab category class
	$version = WMAB_API_VERSION;
	
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-category.php';

	// Create class object
	$wmab_category = new WmabCategory('appGetFilters');

	$category_id = !empty($_POST['category_id']) ? sanitize_text_field($_POST['category_id']) : '';
	$search_term = !empty($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

	return $wmab_category->app_get_filters($category_id, $search_term, $version);
}

// API Call for appGetProductDetails to get Product details
function wmab_app_get_product_details()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Include file to import wmab product class
	$version = WMAB_API_VERSION;
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-product.php';

	// Create class object
	$wmab_product = new WmabProduct('appGetProductDetails');

	$product_id = !empty($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';

	return $wmab_product->app_get_product_details($product_id, $version);
}

// API Call for appGetProductDetails to get Product details
function wmab_app_get_product_reviews()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Include file to import wmab product class
	$version = WMAB_API_VERSION;
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-product.php';

	// Create class object
	$wmab_product = new WmabProduct('appGetProductReviews');

	$product_id = !empty($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';

	return $wmab_product->app_get_product_reviews($product_id, $version);
}

// API Call for appAddToCart to Add Product into Cart
function wmab_app_add_to_cart()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appAddToCart');

	$cart_products = !empty($_POST['cart_products']) ? sanitize_text_field($_POST['cart_products']) : '';
	$session_data  = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_add_to_cart($cart_products, $session_data, false, $version);
}

// API Call for appGetCartDetails to get Cart details
function wmab_app_get_cart_details()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appGetCartDetails');

	$email        = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_get_cart_details($email, $session_data, false, $version);
}

// API Call for appCheckOrderStatus to check if order created by cart
function wmab_app_check_order_status()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appCheckOrderStatus');

	$email        = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_check_order_status($email, $session_data, $version);
}

// API Call for appGetCustomerAddress to get Customer Address details
function wmab_app_get_customer_address()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appGetCustomerAddress');

	$email        = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_get_customer_address($email, $session_data, $version);
}

// API Call for appGetAddressForm to get Customer Address Form
function wmab_app_get_address_form()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appGetAddressForm');

	$version             = !empty($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
	$email               = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$id_shipping_address = !empty($_POST['id_shipping_address']) ? sanitize_text_field($_POST['id_shipping_address']) : '';

	return $wmab_customer->app_get_address_form($email, $id_shipping_address, $version);
}

// API Call for appAddAddress to add Customer Address
function wmab_app_add_address()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appAddAddress');

	$email           = !empty($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
	$session_data    = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$shiping_address = !empty($_POST['shipping_address']) ? sanitize_text_field($_POST['shipping_address']) : '';

	return $wmab_customer->app_add_address($shiping_address, $email, $session_data, $version);
}

// API Call for appAddReview to add Customer Review
function wmab_app_add_review()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appSaveProductReview');

	$email         = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$product_id    = !empty($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
	$title         = !empty($_POST['title']) ? sanitize_email($_POST['title']) : '';
	$content       = !empty($_POST['content']) ? sanitize_text_field($_POST['content']) : '';
	$rating        = !empty($_POST['rating']) ? sanitize_text_field($_POST['rating']) : '';
	$customer_name = !empty($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';

	return $wmab_customer->app_add_review($product_id, $title, $content, $customer_name, $rating, $email, $version);
}

// API Call for appUpdateAddress to update Customer Address
function wmab_app_update_address()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appUpdateAddress');

	$email              = !empty($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
	$session_data       = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$shiping_address    = !empty($_POST['shipping_address']) ? sanitize_text_field($_POST['shipping_address']) : '';
	$id_shiping_address = !empty($_POST['id_shipping_address']) ? sanitize_text_field($_POST['id_shipping_address']) : '';

	return $wmab_customer->app_update_address($id_shiping_address, $shiping_address, $email, $session_data, $version);
}

// API Call for appSocialLogin to register/login user through social login details
function wmab_app_social_login()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appSocialLogin');

	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$user_details = !empty($_POST['login']) ? sanitize_text_field($_POST['login']) : '';

	return $wmab_customer->app_social_login($user_details, $session_data, $version);
}

// API Call for appLogin to verify customer login credentials
function wmab_app_login()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}

	$version = WMAB_API_VERSION;
	$_POST['login_email'] = '1';

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appLogin');

	$username     = !empty($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
	$password     = !empty($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_login($username, $password, $session_data, $version);
}

// API Call for appValidateSignUpFormDetails to validate the register form
function wmab_app_valide_signup()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appRegisterUser');

	$sign_up      = !empty($_POST['signup']) ? sanitize_text_field($_POST['signup']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_valide_signup($sign_up, $session_data, $version);
}

// API Call for appRegisterUser to register customer
function wmab_app_register_user()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appRegisterUser');

	$sign_up      = !empty($_POST['signup']) ? sanitize_text_field($_POST['signup']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_register_user($sign_up, $session_data, $version);
}

// API Call for appLoginViaPhone to verify customer login credentials with Phone Number - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_login_via_phone()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appLoginViaPhone');

	$phone_number = !empty($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
	$country_code = !empty($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$iso_code     = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : '';

	return $wmab_customer->app_login_via_phone($phone_number, $country_code, $session_data, $version, $iso_code);
}

// API Call for appLoginViaEmail to verify customer login credentials with Email and FingerPrint - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_login_via_email()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appLoginViaEmail');

	$email_id     = !empty($_POST['email_id']) ? sanitize_email($_POST['email_id']) : '';
	$unique_id    = !empty($_POST['unique_id']) ? sanitize_text_field($_POST['unique_id']) : '';
	$iso_code     = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	if ($version < 2.0) {
		return $wmab_customer->app_login_via_email($email_id, $unique_id, $version, $iso_code);
	} else {
		return $wmab_customer->app_login_via_email($email_id, $unique_id, $version, $iso_code, $session_data);
	}
}

// API Call for appMapEmailWithUUID to map Email ID and Unique ID for FingerPrint login - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_map_email_with_uuid()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appMapEmailWithUUID');

	$email_id  = !empty($_POST['email_id']) ? sanitize_email($_POST['email_id']) : '';
	$unique_id = !empty($_POST['unique_id']) ? sanitize_text_field($_POST['unique_id']) : '';
	$iso_code  = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : '';

	return $wmab_customer->app_map_email_with_uuid($email_id, $unique_id, $version, $iso_code);
}

// API Call for appCheckIfContactNumberExists to verify Phone Number - Module Upgrade V2 - by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_check_if_contact_number_exists()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Include file to import wmab customer class
	$version = WMAB_API_VERSION;
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appCheckIfContactNumberExists');

	$phone_number = !empty($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
	$country_code = !empty($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';

	return $wmab_customer->app_check_if_contact_number_exists($phone_number, $country_code, false, '', $version);
}

// API Call for appForgotPassword to handle customer's forgot password request
function wmab_app_forgot_password()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appForgotPassword');

	$email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';

	return $wmab_customer->app_forgot_password($email, $version);
}

// API Call for appGetRegions to get Regions
function wmab_app_get_regions()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appGetRegions');

	$country = !empty($_POST['country_id']) ? sanitize_text_field($_POST['country_id']) : '';

	return $wmab_customer->app_get_regions($country, $version);
}

// API Call for appApplyVoucher to apply Voucher on Cart
function wmab_app_apply_voucher()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appApplyVoucher');

	$voucher      = !empty($_POST['voucher']) ? sanitize_text_field($_POST['voucher']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_apply_voucher($voucher, $session_data, $version);
}

// API Call for appRemoveVoucher to remove Voucher from Cart
function wmab_app_remove_voucher()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appRemoveVoucher');

	$voucher      = !empty($_POST['voucher']) ? sanitize_text_field($_POST['voucher']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_remove_voucher($voucher, $session_data, $version);
}

// API Call for appRemoveProduct to remove Product from Cart
function wmab_app_remove_product()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appRemoveProduct');

	$cart_products = !empty($_POST['cart_products']) ? sanitize_text_field($_POST['cart_products']) : '';
	$email         = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data  = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_remove_product($cart_products, $email, $session_data, $version);
}

// API Call for appUpdateCartQuantity to update cart quantity
function wmab_app_update_cart_quantity()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appUpdateCartQuantity');

	$cart_products = !empty($_POST['cart_products']) ? sanitize_text_field($_POST['cart_products']) : '';
	$email         = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data  = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_update_cart_quantity($cart_products, $email, $session_data, $version);
}

// API Call for appGetRegistrationForm to get customer registration form
function wmab_app_get_registration_form()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appGetRegistrationForm');

	return $wmab_customer->app_get_registration_form($version);
}

// API Call for appUpdateProfile to update customer profile
function wmab_app_update_profile()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appUpdateProfile');

	$email         = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$personal_info = !empty($_POST['personal_info']) ? sanitize_text_field($_POST['personal_info']) : '';

	return $wmab_customer->app_update_profile($email, $personal_info, $version);
}

// API Call for appUpdatePassword to update customer account password - Module Upgrade V2 - added by Harsh (hagarwal@velsof.com) on 20-Dec-2019
function wmab_app_update_password()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appUpdatePassword');

	$phone_number = !empty($_POST['mobile_number']) ? sanitize_text_field($_POST['mobile_number']) : '';
	$country_code = !empty($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
	$new_password = !empty($_POST['new_password']) ? sanitize_text_field($_POST['new_password']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';

	return $wmab_customer->app_update_profile($phone_number, $country_code, $new_password, $version);
}

// API Call for appCheckout to checkout
function wmab_app_checkout()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appCheckout');

	$email               = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data        = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$id_billing_address  = !empty($_POST['id_billing_address']) ? sanitize_text_field($_POST['id_billing_address']) : '';
	$id_shipping_address = !empty($_POST['id_shipping_address']) ? sanitize_text_field($_POST['id_shipping_address']) : '';
	$set_shipping_method = !empty($_POST['shipping_method']) ? sanitize_text_field($_POST['shipping_method']) : '';

	return $wmab_checkout->app_checkout($email, $session_data, $id_billing_address, $id_shipping_address, $set_shipping_method, $version);
}

// API Call for appGetOrderDetails to get order details
function wmab_app_get_order_details()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appGetOrderDetails');

	$email    = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$order_id = !empty($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

	return $wmab_checkout->app_get_order_details($email, $order_id, $version);
}

// API Call for appGetOrders to get orders list
function wmab_app_get_orders()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appGetOrders');

	$email = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';

	return $wmab_checkout->app_get_orders($email, $version);
}

// API Call for appSetShippingMethod to set Shipping Method
function wmab_app_set_shipping_method()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appSetShippingMethod');

	$email               = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data        = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$id_billing_address  = !empty($_POST['id_billing_address']) ? sanitize_text_field($_POST['id_billing_address']) : '';
	$id_shipping_address = !empty($_POST['id_shipping_address']) ? sanitize_text_field($_POST['id_shipping_address']) : '';
	$set_shipping_method = !empty($_POST['shipping_method']) ? sanitize_text_field($_POST['shipping_method']) : '';

	return $wmab_checkout->app_checkout($email, $session_data, $id_billing_address, $id_shipping_address, $set_shipping_method, $version);
}

// API Call for appReorder to reorder already placed order
function wmab_app_reorder()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appReorder');

	$email        = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$order_id     = !empty($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';

	return $wmab_checkout->app_reorder($email, $session_data, $order_id, $version);
}

// API Call for appGetPaymentMethods to get Payment Methods
function wmab_app_payment()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appGetPaymentMethods');

	$email               = !empty($_REQUEST['email']) ? sanitize_email($_REQUEST['email']) : '';
	$session_data        = !empty($_REQUEST['session_data']) ? sanitize_text_field($_REQUEST['session_data']) : '';
	$id_shipping_address = !empty($_REQUEST['id_shipping_address']) ? sanitize_text_field($_REQUEST['id_shipping_address']) : '';
	$order_message       = !empty($_REQUEST['order_message']) ? sanitize_text_field($_REQUEST['order_message']) : '';

	$temp = !empty($_REQUEST['temp']) ? sanitize_text_field($_REQUEST['temp']) : 0;

	return $wmab_checkout->app_get_payment_methods($email, $session_data, $id_shipping_address, $order_message, $version, $temp);
}

// API Call for appGetMobilePaymentMethods to get Mobile Payment Methods added through module/plugin
function wmab_app_get_mobile_payment_methods()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appGetMobilePaymentMethods');
	$order_comment = !empty($_POST['order_comment']) ? sanitize_text_field($_POST['order_comment']) : '';

	return $wmab_checkout->app_get_mobile_payment_methods($version, $order_comment);
}

// API Call for appCreateOrder to create Order through module/plugin
function wmab_app_create_order()
{
	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appCreateOrder');
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$email         = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data  = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$payment_info  = !empty($_POST['payment_info']) ? sanitize_text_field($_POST['payment_info']) : '';
	$order_comment = !empty($_POST['order_message']) ? sanitize_text_field($_POST['order_message']) : '';

	return $wmab_checkout->app_create_order($email, $session_data, $payment_info, $order_comment, $version);
}

// API Call for appGetHome to get details for home page of Mobile App
function wmab_app_get_home()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appGetHome');
	// Session_data and email parameters added to get cart details for Home Page - added by Harsh Agarwal on 03-Sep-2020
	$email        = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$session_data = !empty($_POST['session_data']) ? sanitize_text_field($_POST['session_data']) : '';
	$iso_code     = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : ''; // Added for WPML compatibility by Harsh on 16-Apr-2019

	return $wmab_home->app_get_home($version, $iso_code, $session_data, $email);
}

// API Call for appCheckLogStatus to get log status
function wmab_app_check_log_status()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appCheckLogStatus');

	return $wmab_home->app_check_log_status($version);
}

// API Call for appCheckLiveChatSupportStatus to check Live Chat Support status
function wmab_app_check_live_chat_support_status()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appCheckLiveChatSupportStatus');

	return $wmab_home->app_check_live_chat_support_status($version);
}

// API Call for appFCMregister to register mobile device where App is installed
function wmab_app_fcm_register()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	// Include file to import wmab home class
	$version = WMAB_API_VERSION;

	// Include file to import wmab category class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appFCMregister');

	$email   = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$cart_id = !empty($_POST['cart_id']) ? sanitize_text_field($_POST['cart_id']) : '';
	$fcm_id  = !empty($_POST['fcm_id']) ? sanitize_text_field($_POST['fcm_id']) : '';

	return $wmab_home->app_fcm_register($email, $cart_id, $fcm_id, $version);
}

// API Call for appGetTranslations to get Translations
function wmab_app_get_translations()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appGetTranslations');

	$all_app_texts = !empty($_POST['all_app_texts']) ? sanitize_text_field($_POST['all_app_texts']) : '';
	$iso_code      = !empty($_REQUEST['iso_code']) ? sanitize_text_field($_REQUEST['iso_code']) : '';

	return $wmab_home->app_get_translations($all_app_texts, $iso_code, $version);
}

// API Call for appSendAbandonedCartPushNotification to get Translations
function wmab_app_send_abandoned_cart_push_notification()
{
	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout('appSendAbandonedCartPushNotification');

	$wmab_checkout->app_send_abandoned_cart_push_notification();

	die;
}

// API Call for appLogout to logout webview
function wmab_app_logout()
{
	wp_logout();
	wmab_log_knowband_app_request('appLogout', array());
	die;
}

// API Call for Spin And Win
function wmab_app_get_spin_win()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-spin-win.php';

	// to load meta tag for responsive css when using spin-win
	add_action('wp_head', 'wmab_add_meta_tags');
	// Create class object
	$wmab_spin_win = new WmabSpinWin('appGetSpinWin');
	return $wmab_spin_win->app_get_spin_win($version);
}

// API Call for appHandleDeepLink
function wmab_app_handle_deep_link()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-home.php';

	// Create class object
	$wmab_home = new WmabHome('appGetConfig');

	$full_url_of_page = !empty($_POST['full_url_of_page']) ? sanitize_text_field($_POST['full_url_of_page']) : '';

	return $wmab_home->app_handle_deep_link($full_url_of_page, $version);
}

// API Call for appDeleteUser to delete the current user
function wmab_app_delete_user()
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}
	$version = WMAB_API_VERSION;

	// Include file to import wmab customer class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-customer.php';

	// Create class object
	$wmab_customer = new WmabCustomer('appDeleteUser');

	$email = !empty($_POST['email']) ? sanitize_text_field($_POST['email']) : '';

	$wmab_customer->app_delete_user($email, $version);

	die;
}

// Changes added by Harsh Agarwal on 08-May-2020 to check and allow hook to run only if plugin is enabled
$settings = get_option('wmab_settings');
if (isset($settings) && !empty($settings)) {
	$settings = unserialize($settings);
}
if (isset($settings['general']['enabled']) && !empty($settings['general']['enabled'])) {
	// Hook to get Order Status Update
	add_action('woocommerce_order_status_pending', 'wmab_order_status_pending');
	add_action('woocommerce_order_status_processing', 'wmab_order_status_processing');
	add_action('woocommerce_order_status_on-hold', 'wmab_order_status_on_hold');
	add_action('woocommerce_order_status_completed', 'wmab_order_status_completed');
	add_action('woocommerce_order_status_cancelled', 'wmab_order_status_cancelled');
	add_action('woocommerce_order_status_refunded', 'wmab_order_status_refunded');
	add_action('woocommerce_order_status_failed', 'wmab_order_status_failed');
}

// API Call for sedning push notification to customer on Order Status update to Pending
function wmab_order_status_pending($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Pending payment', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 1);
}

// API Call for sedning push notification to customer on Order Status update to Processing
function wmab_order_status_processing($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Processing', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 2);
}

// API Call for sedning push notification to customer on Order Status update to On Hold
function wmab_order_status_on_hold($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('On hold', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 3);
}

// API Call for sedning push notification to customer on Order Status update to Completed
function wmab_order_status_completed($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Completed', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 4);
}

// API Call for sedning push notification to customer on Order Status update to Cancelled
function wmab_order_status_cancelled($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Cancelled', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 5);
}

// API Call for sedning push notification to customer on Order Status update to Refunded
function wmab_order_status_refunded($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Refunded', 'Order status',);

	return $wmab_checkout->order_status_update($order_id, $status, 6);
}

// API Call for sedning push notification to customer on Order Status update to Failed
function wmab_order_status_failed($order_id)
{

	$version = WMAB_API_VERSION;
	// Include file to import wmab checkout class
	include_once plugin_dir_path(__FILE__) . 'api/' . $version . '/wmab-checkout.php';

	// Create class object
	$wmab_checkout = new WmabCheckout();

	$status = _x('Failed', 'Order status', 'knowband-mobile-app-builder-for-woocommerce');

	return $wmab_checkout->order_status_update($order_id, $status, 7);
}

function wmab_get_related_products($product_ids)
{

	$related_products_content['has_related_products']   = '0';
	$related_products_content['related_products_items'] = array();

	if (!empty($product_ids)) {
		$related_products_content['has_related_products'] = '1';
		foreach ($product_ids as $product_id) {
			$product       = wc_get_product($product_id);
			$wmab_settings = get_option('wmab_settings');
			if (isset($wmab_settings) && !empty($wmab_settings)) {
				$wmab_plugin_settings = unserialize($wmab_settings);
			}
			if ($product->is_visible()) {

				if ('variable' == $product->product_type) { // For Variable Product
					$has_attributes       = '1';
					$available_variations = $product->get_available_variations();
					$variation_id         = $available_variations[0]['variation_id']; // Getting the variable id of just the 1st product.
					$variable_product     = new WC_Product_Variation($variation_id);
					$regular_price        = $variable_product->regular_price;
					$sale_price           = $variable_product->sale_price;

					if ($sale_price) {
						$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
					} else {
						$discount_percentage = 0;
					}
				} else {
					// For Simple Products
					if ($product->get_sale_price()) {
						$sale_price          = $product->get_sale_price();
						$regular_price       = $product->get_regular_price();
						$discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2);
					} else {
						$sale_price          = $product->get_sale_price();
						$regular_price       = $product->get_regular_price();
						$discount_percentage = 0;
					}
				}

				// Check if image exists otherwise send dummy image - changes made by Saurav (25-Jun-2020)
				$product_thumbnail = get_the_post_thumbnail_url($product_id, array($wmab_plugin_settings['general']['product_image_width'], $wmab_plugin_settings['general']['product_image_height']));
				if (isset($product_thumbnail) && !empty($product_thumbnail)) {
					$image_path = $product_thumbnail;
				} else {
					$image_path = WMAB_URL . 'views/images/home_page_layout/noimage.png'; // No-Image
				}

				$related_products_content['related_products_items'][] = array(
					'id'                  => (string) $product_id,
					'name'                => $product->get_name(),
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
						ENT_COMPAT 
					),
					'src'                 => $image_path,
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
						ENT_COMPAT
					),
					'discount_percentage' => $discount_percentage,
					'ClickActivityName'   => 'ProductActivity',
				);
			}
		}
	}

	return $related_products_content;
}

/**
 * In WordPress, there is no built-in function specifically designed to escape a multidimensional array as a whole.So, we have Used a custom function allows us to maintain the security and integrity of the data while working with multidimensional arrays in WordPress
 *
 * @param array Array data which need to be escaped.
 * @return Array Escapped array data.
 * @since 1.0.0
 */
function wmab_escape_data($data)
{
	if (is_array($data)) {
		$escaped_data = array();
		foreach ($data as $key => $value) {
			$escaped_data[$key] = wmab_escape_data($value);
		}
		return $escaped_data;
	} elseif (is_string($data)) {
		return esc_attr($data);
	} elseif (is_int($data) || is_float($data)) {
		return ($data);
	} else {
		return $data;
	}
}

// Log API Request and Response
function wmab_log_knowband_app_request($api_name, $response)
{
	// To resolve the WordPress validation error related to processing form data without nonce verification, implemented the below code. Although the code serves solely for code validation purposes and does not have any functional use, it effectively addresses the issue.
	$kb_nonce_verification = 0;

	if (isset($_POST['my_nonce']) && wp_verify_nonce(sanitize_text_field(isset($_POST['kb_nonce'])), 'kbmabverify')) {
		$kb_nonce_verification = 1;
	}

	$upload_dir   = wp_upload_dir();

	if (!is_dir($upload_dir['basedir'] . '/kb_app_logs')) {
		mkdir($upload_dir['basedir'] . '/kb_app_logs', 0775, true);
	}

	$logfile = fopen($upload_dir['basedir'] . '/kb_app_logs/wmab_app.log', 'a+');
	if ($logfile) {

		$corelationId = wmab_get_corelation_id();
		$postParams   = array();
		$post_params  = sanitize_post($_POST, $context = 'display');
		if (isset($post_params)) {
			foreach ($post_params as $key => $value) {
				$postParams[$key] = $value;
			}
		}
		$getParams  = array();
		$get_params = sanitize_post($_GET, $context = 'display');
		foreach ($get_params as $key => $value) {
			$getParams[$key] = $value;
		}
		$message = gmdate('Y-m-d H:i:s') . ' ' . $corelationId . ' ' . $api_name . ' ' . (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '') . ' ' . (isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '') . ' ' . json_encode($getParams) . ' ' . json_encode($postParams) . ' ' . $response . "\n";
		fwrite($logfile, $message);
		fclose($logfile);
	}
}

function wmab_get_corelation_id()
{
	if (function_exists('apache_request_headers')) {
		$headers = apache_request_headers();
		if (!empty($headers)) {
			foreach ($headers as $header => $value) {
				if ('CorelationId' == $header) {
					return $value;
				}
			}
		}
	}
	return '';
}

/**
 * Function added to get the WooCommerce Version Number
 * Author: Harsh Agarwal (hagarwal@velsof.com)
 * Date: 03-Oct-2019
 */
function wmab_get_woocommerce_version_number()
{
	// If get_plugins() isn't available, require it
	if (!function_exists('get_plugins')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Create the plugins folder and file variables
	$plugin_folder = get_plugins('/woocommerce');
	$plugin_file   = 'woocommerce.php';

	// If the plugin version number is set, return it
	if (isset($plugin_folder[$plugin_file]['Version'])) {
		return $plugin_folder[$plugin_file]['Version'];
	} else {
		// Otherwise return null
		return null;
	}
}

function wmab_add_meta_tags()
{
	include_once plugin_dir_path(__FILE__) . 'views/meta.php';
}

add_action('wp_head', 'wmab_get_mab_custom_css');

function wmab_get_mab_custom_css()
{
	// Hide Header and Footer on mobile App - for CMS Pages
	if ((isset($_GET['wmab']) && !empty($_GET['wmab'])) || (isset($_GET['request_type']) && !empty($_GET['request_type']) && 'kb_contact_us' == $_GET['request_type'])) {
		$settings = get_option('wmab_settings');
		if (isset($settings) && !empty($settings)) {
			$settings = unserialize($settings);
		}
		include_once plugin_dir_path(__FILE__) . 'views/style.php';
	}
}
