var $ = jQuery.noConflict();

// Function to change/switch tab
function change_tab(id, tab)
{
	// Hide all tabs
	$( "#general_settings" ).hide();
	$( "#push_notification_settings" ).hide();
	$( "#push_notification_history" ).hide();
	$( "#slideshow_settings" ).hide();
	$( "#banners_settings" ).hide();
	$( "#payment_methods" ).hide();
	$( "#featured" ).hide();
	$( "#specials" ).hide();
	$( "#bestsellers" ).hide();
	$( "#latest" ).hide();
	$( "#information_pages" ).hide();
	$( "#google_login_settings" ).hide();
	$( "#fb_login_settings" ).hide();
	$( "#sample_settings" ).hide();
	$( "#contact_us" ).hide();

	// BOC neeraj.kumar@velsof.com Module Upgrade V2 20-Dec-2019 hide new tab when we click/change tab
	$( "#home_layout_tab" ).hide();
	$( "#home-layout-page" ).removeClass( 'active' );
	// hide tab bar layout :
	$( "#tab-bar-layout-tab" ).hide();
	$( "#tab-bar-layout-page" ).removeClass( 'active' );
	// EOC Module Upgrade V2
	// Remove active class from all tab options
	$( "#link-general_settings" ).removeClass( 'active' );
	$( "#link-push_notification_settings" ).removeClass( 'active' );
	$( "#link-push_notification_history" ).removeClass( 'active' );
	$( "#link-slideshow_settings" ).removeClass( 'active' );
	$( "#link-banners_settings" ).removeClass( 'active' );
	$( "#link-payment_methods" ).removeClass( 'active' );
	$( "#link-featured" ).removeClass( 'active' );
	$( "#link-specials" ).removeClass( 'active' );
	$( "#link-bestsellers" ).removeClass( 'active' );
	$( "#link-latest" ).removeClass( 'active' );
	$( "#link-information_pages" ).removeClass( 'active' );
	$( "#link-google_login_settings" ).removeClass( 'active' );
	$( "#link-fb_login_settings" ).removeClass( 'active' );
	$( "#link-sample_settings" ).removeClass( 'active' );
	$( "#link-contact-us" ).removeClass( 'active' );
	// Display Selected Tab
	$( "#" + tab ).show();
	$( "#" + id ).addClass( "active" );
	// hide save button on contact-us page
	if (tab == "contact_us") {
		$( "#submit_button" ).hide();
	} else {
		$( "#submit_button" ).show();
	}
}

// Function to show/hide live chant API box
function mab_show_chat_api_box()
{
	$( "#live_chat_support" ).hide();
	if ($( "#live_chat" ).is( ":checked" )) {
		$( "#live_chat_support" ).show();
	}
}

// Function to show/hide Google box
function mab_show_google_box()
{
	$( "#google_upload_file" ).hide();
	if ($( "#google_login" ).is( ":checked" )) {
		$( "#google_upload_file" ).show();
	}
}

// Function to show/hide FB App ID box
function mab_show_fb_app_id_box()
{
	$( "#fb_app_id" ).hide();
	if ($( "#fb_app" ).is( ":checked" )) {
		$( "#fb_app_id" ).show();
	}
}

$( document ).ready(
	function ($) {

		$( '.mab_product_new_date' ).datepicker( {dateFormat : 'dd/mm/yy'} );

		$( '#color_1, #color_3, #color_4, #color_5, #color_6' ).wpColorPicker();

		$( "#color_2" ).wpColorPicker(
			{
				change: function (event, ui) {
					var color = ui.color.toString();
					$( '.topHeader' ).css( 'background', color );
					$( '.chatBoxIcon' ).css( 'background', color );
				},
			}
		);
	}
);

// Click event to hide/show mobile preview
$( ".mobile_preview_button" ).click(
	function () {
		$( "#general_settings_mobile_preview" ).toggle();
	}
);


// Function to submit form after validation
function mabsubmission()
{

	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();

	var general_settings_tab           = 0;
	var push_notification_settings_tab = 0;
	var push_notification_history_tab  = 0;
	var slideshow_settings_tab         = 0;
	var banners_settings_tab           = 0;
	var payment_methods_tab            = 0;
	var featured_tab                   = 0;
	var specials_tab                   = 0;
	var bestsellers_tab                = 0;
	var latest_tab                     = 0;
	var information_pages_tab          = 0;
	var google_login_settings_tab      = 0;
	var fb_login_settings_tab          = 0;

	var error = false;

	// BOC neeraj.kumar@velsof.com 19-Dec-2019 Module Upgrade V2 added validation for new field which added in general form
	// Both Live Chat and Whatsup Chat not enabled at a time
	// Live Chat API Ke Validation
	if ($( "#live_chat" ).is( ":checked" ) && $( "#whatsup_chat_support_status" ).is( ":checked" )) {
		error = true;
		// Live Chat Api
		$( "input[name='vss_mab[general][live_chat_api_key]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[general][live_chat_api_key]']" ).after( $( '<p class="mab_validation_error">Only one chat option can be enabled at a time.</p>' ) );
		// Whatup Chat Number
		$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).after( $( '<p class="mab_validation_error">Only one chat option can be enabled at a time.</p>' ) );
		general_settings_tab = 1;
	} else {
		// Live Chat API Ke Validation
		if ($( "#live_chat" ).is( ":checked" )) {
			if ($( "input[name='vss_mab[general][live_chat_api_key]']" ).length > 0) {
				var live_chat_api_key = velovalidation.checkMandatory( $( "input[name='vss_mab[general][live_chat_api_key]']" ) );
				if (live_chat_api_key !== true) {
					remove_duplicate_chat_error = false;
					error                       = true;
					$( "input[name='vss_mab[general][live_chat_api_key]']" ).addClass( 'error_field' );
					$( "input[name='vss_mab[general][live_chat_api_key]']" ).after( $( '<p class="mab_validation_error">' + live_chat_api_key + '</p>' ) );
					general_settings_tab = 1;
				}
			}
		}

		// Whatsup Chat Number Validation
		if ($( "#whatsup_chat_support_status" ).is( ":checked" )) {
			if ($( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).length > 0) {
				var whatsup_box_message = velovalidation.checkMandatory( $( "input[name='vss_mab[general][whatsup_chat_number_key]']" ) );
				if (whatsup_box_message !== true) {
					error = true;
					$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).addClass( 'error_field' );
					$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).after( $( '<p class="mab_validation_error">' + whatsup_box_message + '</p>' ) );
					general_settings_tab = 1;
				} else {
					var whatsup_box_message = velovalidation.checkPhoneNumber( $( "input[name='vss_mab[general][whatsup_chat_number_key]']" ) );
					if (whatsup_box_message !== true) {
						error = true;
						$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).addClass( 'error_field' );
						$( "input[name='vss_mab[general][whatsup_chat_number_key]']" ).after( $( '<p class="mab_validation_error">' + whatsup_box_message + '</p>' ) );
						general_settings_tab = 1;
					}
				}
			}
		}
	}
	// App Button Color validation
	var app_button_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][app_button_color]']" ) );
	if (app_button_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][app_button_color]']" ).addClass( 'error_field' );
		$( "#app_button_color" ).after( $( '<p class="mab_validation_error">' + app_button_color + '</p>' ) );
		general_settings_tab = 1;
	}
	// App Background Color validation
	var app_background_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][app_background_color]']" ) );
	if (app_background_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][app_background_color]']" ).addClass( 'error_field' );
		$( "#app_background_color" ).after( $( '<p class="mab_validation_error">' + app_background_color + '</p>' ) );
		general_settings_tab = 1;
	}
	// App Theme Color validation
	var app_theme_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][app_theme_color]']" ) );
	if (app_theme_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][app_theme_color]']" ).addClass( 'error_field' );
		$( "#app_theme_color" ).after( $( '<p class="mab_validation_error">' + app_theme_color + '</p>' ) );
		general_settings_tab = 1;
	}
	// Button Text Color validation
	var app_button_text_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][app_button_text_color]']" ) );
	if (app_button_text_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][app_button_text_color]']" ).addClass( 'error_field' );
		$( "#app_button_text_color" ).after( $( '<p class="mab_validation_error">' + app_button_text_color + '</p>' ) );
		general_settings_tab = 1;
	}
	// Toast Message Text Color validation
	var snackbar_text_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][snackbar_text_color]']" ) );
	if (snackbar_text_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][snackbar_text_color]']" ).addClass( 'error_field' );
		$( "#app_theme_color" ).after( $( '<p class="mab_validation_error">' + snackbar_text_color + '</p>' ) );
		general_settings_tab = 1;
	}
	// Toast Message Background Color validation
	var snackbar_background_color = velovalidation.checkMandatory( $( "input[name='vss_mab[general][snackbar_background_color]']" ) );
	if (snackbar_background_color !== true) {
		error = true;
		$( "input[name='vss_mab[general][snackbar_background_color]']" ).addClass( 'error_field' );
		$( "#app_theme_color" ).after( $( '<p class="mab_validation_error">' + snackbar_background_color + '</p>' ) );
		general_settings_tab = 1;
	}

	// Validation work only when image logo status is checked
	if ($( '#logo_status' ).is( ":checked" )) {
		// App Logo validation
		if ($( "input[name='vss_mab_app_logo_image_path']" ).val() != '') {
			var app_logo_image = velovalidation.checkImage( $( "input[name='vss_mab_app_logo_image_path']" ) );
			if (app_logo_image !== true) {
				error = true;
				$( "input[name='vss_mab_app_logo_image_path']" ).addClass( 'error_field' );
				$( "input[name='vss_mab_app_logo_image_path']" ).after( $( '<p class="mab_validation_error">' + app_logo_image + '</p>' ) );
				general_settings_tab = 1;
			}
		} else if ($( "input[name='vss_mab[general][image_logo_hidden]']" ).val() == '') {
			var app_logo_image = velovalidation.checkMandatory( $( "input[name='vss_mab[general][image_logo_hidden]']" ) );
			if (app_logo_image !== true) {
				error = true;
				$( "input[name='vss_mab[general][image_logo_hidden]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[general][image_logo_hidden]']" ).after( $( '<p class="mab_validation_error">' + app_logo_image + '</p>' ) );
				general_settings_tab = 1;
			}
		}

	}

	// EOC Module Upgrade V2 neeraj.kumar@velsof.com

	// Category Image Width Validation
	// var category_image_width = velovalidation.checkMandatory($("input[name='vss_mab[general][category_image_width]']"));
	// if (category_image_width !== true) {
	// error = true;
	// $("input[name='vss_mab[general][category_image_width]']").addClass('error_field');
	// $("input[name='vss_mab[general][category_image_width]']").after($('<p class="mab_validation_error">' + category_image_width + '</p>'));
	// general_settings_tab = 1;
	// } else {
	// var category_image_width = velovalidation.isBetween($("input[name='vss_mab[general][category_image_width]']"), 1, 999);
	// if (category_image_width !== true) {
	// error = true;
	// $("input[name='vss_mab[general][category_image_width]']").addClass('error_field');
	// $("input[name='vss_mab[general][category_image_width]']").after($('<p class="mab_validation_error">' + category_image_width + '</p>'));
	// general_settings_tab = 1;
	// }
	// }

	// Category Image Height Validation
	// var category_image_height = velovalidation.checkMandatory($("input[name='vss_mab[general][category_image_height]']"));
	// if (category_image_height !== true) {
	// error = true;
	// $("input[name='vss_mab[general][category_image_height]']").addClass('error_field');
	// $("input[name='vss_mab[general][category_image_height]']").after($('<p class="mab_validation_error">' + category_image_height + '</p>'));
	// general_settings_tab = 1;
	// } else {
	// var category_image_height = velovalidation.isBetween($("input[name='vss_mab[general][category_image_height]']"), 1, 999);
	// if (category_image_height !== true) {
	// error = true;
	// $("input[name='vss_mab[general][category_image_height]']").addClass('error_field');
	// $("input[name='vss_mab[general][category_image_height]']").after($('<p class="mab_validation_error">' + category_image_height + '</p>'));
	// general_settings_tab = 1;
	// }
	// }

	// //Product Image Width Validation
	// var product_image_width = velovalidation.checkMandatory($("input[name='vss_mab[general][product_image_width]']"));
	// if (product_image_width !== true) {
	// error = true;
	// $("input[name='vss_mab[general][product_image_width]']").addClass('error_field');
	// $("input[name='vss_mab[general][product_image_width]']").after($('<p class="mab_validation_error">' + product_image_width + '</p>'));
	// general_settings_tab = 1;
	// } else {
	// var product_image_width = velovalidation.isBetween($("input[name='vss_mab[general][product_image_width]']"), 1, 999);
	// if (product_image_width !== true) {
	// error = true;
	// $("input[name='vss_mab[general][product_image_width]']").addClass('error_field');
	// $("input[name='vss_mab[general][product_image_width]']").after($('<p class="mab_validation_error">' + product_image_width + '</p>'));
	// general_settings_tab = 1;
	// }
	// }

	// //Product Image Height Validation
	// var product_image_height = velovalidation.checkMandatory($("input[name='vss_mab[general][product_image_height]']"));
	// if (product_image_height !== true) {
	// error = true;
	// $("input[name='vss_mab[general][product_image_height]']").addClass('error_field');
	// $("input[name='vss_mab[general][product_image_height]']").after($('<p class="mab_validation_error">' + product_image_height + '</p>'));
	// general_settings_tab = 1;
	// } else {
	// var product_image_height = velovalidation.isBetween($("input[name='vss_mab[general][product_image_height]']"), 1, 999);
	// if (product_image_height !== true) {
	// error = true;
	// $("input[name='vss_mab[general][product_image_height]']").addClass('error_field');
	// $("input[name='vss_mab[general][product_image_height]']").after($('<p class="mab_validation_error">' + product_image_height + '</p>'));
	// general_settings_tab = 1;
	// }
	// }

	// Product New Date Validation
	var product_new_date = velovalidation.checkMandatory( $( "input[name='vss_mab[general][product_new_date]']" ) );
	if (product_new_date !== true) {
		error = true;
		$( "input[name='vss_mab[general][product_new_date]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[general][product_new_date]']" ).after( $( '<p class="mab_validation_error">' + product_new_date + '</p>' ) );
		general_settings_tab = 1;
	} else {
		var product_new_date = velovalidation.checkDateddmmyy( $( "input[name='vss_mab[general][product_new_date]']" ) );
		if (product_new_date !== true) {
			error = true;
			$( "input[name='vss_mab[general][product_new_date]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[general][product_new_date]']" ).after( $( '<p class="mab_validation_error">' + product_new_date + '</p>' ) );
			general_settings_tab = 1;
		}
	}

	// Custom CSS Validation
	var custom_css_tag = velovalidation.checkHtmlTags( $( "textarea[name='vss_mab[general][custom_css]']" ) );
	if (custom_css_tag !== true) {
		error = true;
		$( "textarea[name='vss_mab[general][custom_css]']" ).addClass( 'error_field' );
		$( "textarea[name='vss_mab[general][custom_css]']" ).after( $( '<p class="mab_validation_error">' + custom_css_tag + '</p>' ) );
		general_settings_tab = 1;
	}

	// Number of Product for New Validation
	var product_new_number = velovalidation.checkMandatory( $( "input[name='vss_mab[general][product_new_number]']" ) );
	if (product_new_number !== true) {
		error = true;
		$( "input[name='vss_mab[general][product_new_number]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[general][product_new_number]']" ).after( $( '<p class="mab_validation_error">' + product_new_number + '</p>' ) );
		general_settings_tab = 1;
	} else {
		var product_new_number = velovalidation.isBetween( $( "input[name='vss_mab[general][product_new_number]']" ), 1, 999 );
		if (product_new_number !== true) {
			error = true;
			$( "input[name='vss_mab[general][product_new_number]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[general][product_new_number]']" ).after( $( '<p class="mab_validation_error">' + product_new_number + '</p>' ) );
			general_settings_tab = 1;
		}
	}

	// Firebase Key Validation
	var firebase_server_key = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][firebase_server_key]']" ) );
	if (firebase_server_key !== true) {
		error = true;
		$( "input[name='vss_mab[push_notification_settings][firebase_server_key]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[push_notification_settings][firebase_server_key]']" ).after( $( '<p class="mab_validation_error">' + firebase_server_key + '</p>' ) );
		push_notification_settings_tab = 1;
	}

	// Order Success Notification Title Validation
	if ($( "input[name='vss_mab[push_notification_settings][order_success_enabled]']" ).is( ":checked" )) {
		var order_success_notification_title = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][order_success_notification_title]']" ), 255, 1 );
		if (order_success_notification_title !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][order_success_notification_title]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][order_success_notification_title]']" ).after( $( '<p class="mab_validation_error">' + order_success_notification_title + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Order Success Notification Message Validation
	if ($( "input[name='vss_mab[push_notification_settings][order_success_enabled]']" ).is( ":checked" )) {
		var order_success_notification_msg = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][order_success_notification_msg]']" ), 255, 0 );
		if (order_success_notification_msg !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][order_success_notification_msg]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][order_success_notification_msg]']" ).after( $( '<p class="mab_validation_error">' + order_success_notification_msg + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Order Status Update Notification Title Validation
	if ($( "input[name='vss_mab[push_notification_settings][order_status_enabled]']" ).is( ":checked" )) {
		var order_status_notification_title = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][order_status_notification_title]']" ), 255, 0 );
		if (order_status_notification_title !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][order_status_notification_title]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][order_status_notification_title]']" ).after( $( '<p class="mab_validation_error">' + order_status_notification_title + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Order Status Update Notification Message Validation
	if ($( "input[name='vss_mab[push_notification_settings][order_status_enabled]']" ).is( ":checked" )) {
		var order_status_notification_msg = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][order_status_notification_msg]']" ), 255, 0 );
		if (order_status_notification_msg !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][order_status_notification_msg]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][order_status_notification_msg]']" ).after( $( '<p class="mab_validation_error">' + order_status_notification_msg + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Abandoned Cart Notification Title Validation
	if ($( "input[name='vss_mab[push_notification_settings][abandoned_cart_enabled]']" ).is( ":checked" )) {
		var abandoned_cart_notification_title = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_title]']" ), 255, 0 );
		if (abandoned_cart_notification_title !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_title]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_title]']" ).after( $( '<p class="mab_validation_error">' + abandoned_cart_notification_title + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Abandoned Cart Notification Message Validation
	if ($( "input[name='vss_mab[push_notification_settings][abandoned_cart_enabled]']" ).is( ":checked" )) {
		var abandoned_cart_notification_msg = velovalidation.checkMandatory( $( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_msg]']" ), 255, 0 );
		if (abandoned_cart_notification_msg !== true) {
			error = true;
			$( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_msg]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[push_notification_settings][abandoned_cart_notification_msg]']" ).after( $( '<p class="mab_validation_error">' + abandoned_cart_notification_msg + '</p>' ) );
			push_notification_settings_tab = 1;
		}
	}

	// Abandoned Cart Time Interval Validation
	var abandoned_cart_time_interval = velovalidation.isBetween( $( "input[name='vss_mab[push_notification_settings][abandoned_cart_time_interval]']" ), 1, 24 );
	if (abandoned_cart_time_interval !== true) {
		error = true;
		$( "input[name='vss_mab[push_notification_settings][abandoned_cart_time_interval]']" ).addClass( 'error_field' );
		$( "input[name='vss_mab[push_notification_settings][abandoned_cart_time_interval]']" ).after( $( '<p class="mab_validation_error">' + abandoned_cart_time_interval + '</p>' ) );
		push_notification_settings_tab = 1;
	}

	// Send Push Notification Validations
	if ($( "#send_notification_panel" ).is( ":visible" )) {
		// Push Notification Title Validation
		var notification_title = velovalidation.checkMandatory( $( "input[name='notification_title']" ), 255, 1 );
		if (notification_title !== true) {
			error = true;
			$( "input[name='notification_title']" ).addClass( 'error_field' );
			$( "input[name='notification_title']" ).after( $( '<p class="mab_validation_error">' + notification_title + '</p>' ) );
			push_notification_history_tab = 1;
		}

		// Push Notification Message Validation
		var notification_msg = velovalidation.checkMandatory( $( "textarea[name='notification_msg']" ), 500, 1 );
		if (notification_msg !== true) {
			error = true;
			$( "textarea[name='notification_msg']" ).addClass( 'error_field' );
			$( "textarea[name='notification_msg']" ).after( $( '<p class="mab_validation_error">' + notification_msg + '</p>' ) );
			push_notification_history_tab = 1;
		}

		// Push Notification Image Validation
		var notification_image = velovalidation.checkMandatory( $( "input[name='notification_image']" ) );
		if (notification_image !== true) {
			error = true;
			$( "input[name='notification_image']" ).addClass( 'error_field' );
			$( "input[name='notification_image']" ).after( $( '<p class="mab_validation_error">' + notification_image + '</p>' ) );
			push_notification_history_tab = 1;
		} else {
			var notification_image = velovalidation.checkImage( $( "input[name='notification_image']" ) );
			if (notification_image !== true) {
				error = true;
				$( "input[name='notification_image']" ).addClass( 'error_field' );
				$( "input[name='notification_image']" ).after( $( '<p class="mab_validation_error">' + notification_image + '</p>' ) );
				push_notification_history_tab = 1;
			}
		}

		if ($( "#notification_category_list" ).is( ":visible" )) {
			// Push Notification Category Validation
			var notification_category = velovalidation.checkMandatory( $( "select[name='notification_category']" ) );
			if (notification_category !== true) {
				error = true;
				$( "select[name='notification_category']" ).addClass( 'error_field' );
				$( "select[name='notification_category']" ).after( $( '<p class="mab_validation_error">' + notification_category + '</p>' ) );
				push_notification_history_tab = 1;
			}
		}

		if ($( "#notification_product_list" ).is( ":visible" )) {
			// Push Notification Product Validation
			var notification_product = velovalidation.checkMandatory( $( "select[name='notification_product']" ) );
			if (notification_product !== true) {
				error = true;
				$( "select[name='notification_product']" ).addClass( 'error_field' );
				$( "select[name='notification_product']" ).after( $( '<p class="mab_validation_error">' + notification_product + '</p>' ) );
				push_notification_history_tab = 1;
			}
		}
	}

	// Slideshow Name Validation
	if (($( "input[name='vss_mab[slideshow_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[slideshow_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[slideshow_settings][slideshow_name]']" ).val().trim() != '') {
		var slideshow_name = velovalidation.checkMandatory( $( "input[name='vss_mab[slideshow_settings][slideshow_name]']" ), 255, 1 );
		if (slideshow_name !== true) {
			error = true;
			$( "input[name='vss_mab[slideshow_settings][slideshow_name]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[slideshow_settings][slideshow_name]']" ).after( $( '<p class="mab_validation_error">' + slideshow_name + '</p>' ) );
			slideshow_settings_tab = 1;
		}
	}

	// Slideshow Limit Validation
	if (($( "input[name='vss_mab[slideshow_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[slideshow_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[slideshow_settings][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[slideshow_settings][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[slideshow_settings][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[slideshow_settings][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			slideshow_settings_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[slideshow_settings][limit]']" ), 1, 5 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[slideshow_settings][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[slideshow_settings][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	}

	// Slideshow Image Width Validation
	if (($( "input[name='vss_mab[slideshow_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[slideshow_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[slideshow_settings][image_width]']" ).val().trim() != '') {
		var image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[slideshow_settings][image_width]']" ) );
		if (image_width !== true) {
			error = true;
			$( "input[name='vss_mab[slideshow_settings][image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[slideshow_settings][image_width]']" ).after( $( '<p class="mab_validation_error">' + image_width + '</p>' ) );
			slideshow_settings_tab = 1;
		} else {
			var image_width = velovalidation.isBetween( $( "input[name='vss_mab[slideshow_settings][image_width]']" ), 600, 999 );
			if (image_width !== true) {
				error = true;
				$( "input[name='vss_mab[slideshow_settings][image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[slideshow_settings][image_width]']" ).after( $( '<p class="mab_validation_error">' + image_width + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	}

	// Slideshow Image Height Validation
	if (($( "input[name='vss_mab[slideshow_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[slideshow_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[slideshow_settings][image_height]']" ).val().trim() != '') {
		var image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[slideshow_settings][image_height]']" ) );
		if (image_height !== true) {
			error = true;
			$( "input[name='vss_mab[slideshow_settings][image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[slideshow_settings][image_height]']" ).after( $( '<p class="mab_validation_error">' + image_height + '</p>' ) );
			slideshow_settings_tab = 1;
		} else {
			var image_height = velovalidation.isBetween( $( "input[name='vss_mab[slideshow_settings][image_height]']" ), 200, 999 );
			if (image_height !== true) {
				error = true;
				$( "input[name='vss_mab[slideshow_settings][image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[slideshow_settings][image_height]']" ).after( $( '<p class="mab_validation_error">' + image_height + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	}

	// Slideshow Title validation
	$( "input[name^=slide_name]" ).each(
		function () {
			var slideshow_title = velovalidation.checkMandatory( $( this ), 255, 1 );
			if (slideshow_title !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + slideshow_title + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	);

	// Slideshow Link Type Validation
	$( "select[name^=slide_link_type]" ).each(
		function () {
			var slide_link_type = velovalidation.checkMandatory( $( this ) );
			if (slide_link_type !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + slide_link_type + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	);

	// Slideshow Link To Validation
	$( "select[name^=slide_link_to]" ).each(
		function () {
			var slide_link_to = velovalidation.checkMandatory( $( this ) );
			if (slide_link_to !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + slide_link_to + '</p>' ) );
				slideshow_settings_tab = 1;
			}
		}
	);

	// Slideshow Image Validation
	if ($( "input[name^=slide_image]" ).length > 0) {
		$( "input[name^=slide_image]" ).each(
			function () {
				var slide_image = velovalidation.checkMandatory( $( this ) );
				if (slide_image !== true) {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + slide_image + '</p>' ) );
					slideshow_settings_tab = 1;
				} else {
					var slide_image = velovalidation.checkImage( $( this ) );
					if (slide_image !== true) {
						error = true;
						$( this ).addClass( 'error_field' );
						$( this ).after( $( '<p class="mab_validation_error">' + slide_image + '</p>' ) );
						slideshow_settings_tab = 1;
					}
				}
			}
		);
	}

	// Slideshow Sort Order Validation
	var sort_order_value = [];
	$( "input[name^=slide_sort_order]" ).each(
		function () {

			var slide_sort_order_duplicate = $( "#sort_order_duplicate_error" ).val();
			if ($.inArray( $( this ).val(), sort_order_value ) != '-1') {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + slide_sort_order_duplicate + '</p>' ) );
				slideshow_settings_tab = 1;
			} else {
				sort_order_value.push( $( this ).val() );
			}

			var slide_sort_order = velovalidation.checkMandatory( $( this ) );
			if (slide_sort_order !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + slide_sort_order + '</p>' ) );
				slideshow_settings_tab = 1;
			} else {
				var slide_sort_order = velovalidation.isNumeric( $( this ), true );
				if (slide_sort_order !== true) {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + slide_sort_order + '</p>' ) );
					slideshow_settings_tab = 1;
				} else {
					var slide_sort_order = velovalidation.isBetween( $( this ), 0, 999 );
					if (slide_sort_order !== true) {
						error = true;
						$( this ).addClass( 'error_field' );
						$( this ).after( $( '<p class="mab_validation_error">' + slide_sort_order + '</p>' ) );
						slideshow_settings_tab = 1;
					}
				}
			}
		}
	);

	// Banner Name Validation
	if (($( "input[name='vss_mab[banners_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[banners_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[banners_settings][banner_name]']" ).val().trim() != '') {
		var banner_name = velovalidation.checkMandatory( $( "input[name='vss_mab[banners_settings][banner_name]']" ), 255, 1 );
		if (banner_name !== true) {
			error = true;
			$( "input[name='vss_mab[banners_settings][banner_name]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[banners_settings][banner_name]']" ).after( $( '<p class="mab_validation_error">' + banner_name + '</p>' ) );
			banners_settings_tab = 1;
		}
	}

	// Banner Limit Validation
	if (($( "input[name='vss_mab[banners_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[banners_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[banners_settings][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[banners_settings][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[banners_settings][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[banners_settings][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			banners_settings_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[banners_settings][limit]']" ), 1, 5 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[banners_settings][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[banners_settings][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	}

	// Banner Image Width Validation
	if (($( "input[name='vss_mab[banners_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[banners_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[banners_settings][image_width]']" ).val().trim() != '') {
		var image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[banners_settings][image_width]']" ) );
		if (image_width !== true) {
			error = true;
			$( "input[name='vss_mab[banners_settings][image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[banners_settings][image_width]']" ).after( $( '<p class="mab_validation_error">' + image_width + '</p>' ) );
			banners_settings_tab = 1;
		} else {
			var image_width = velovalidation.isBetween( $( "input[name='vss_mab[banners_settings][image_width]']" ), 600, 999 );
			if (image_width !== true) {
				error = true;
				$( "input[name='vss_mab[banners_settings][image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[banners_settings][image_width]']" ).after( $( '<p class="mab_validation_error">' + image_width + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	}

	// Banner Image Height Validation
	if (($( "input[name='vss_mab[banners_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[banners_settings][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[banners_settings][image_height]']" ).val().trim() != '') {
		var image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[banners_settings][image_height]']" ) );
		if (image_height !== true) {
			error = true;
			$( "input[name='vss_mab[banners_settings][image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[banners_settings][image_height]']" ).after( $( '<p class="mab_validation_error">' + image_height + '</p>' ) );
			banners_settings_tab = 1;
		} else {
			var image_height = velovalidation.isBetween( $( "input[name='vss_mab[banners_settings][image_height]']" ), 200, 999 );
			if (image_height !== true) {
				error = true;
				$( "input[name='vss_mab[banners_settings][image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[banners_settings][image_height]']" ).after( $( '<p class="mab_validation_error">' + image_height + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	}

	// Banner Title validation
	$( "input[name^=banner_name]" ).each(
		function () {
			var banner_name = velovalidation.checkMandatory( $( this ), 255, 1 );
			if (banner_name !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + banner_name + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	);

	// Banner Link Type Validation
	$( "select[name^=banner_link_type]" ).each(
		function () {
			var banner_link_type = velovalidation.checkMandatory( $( this ) );
			if (banner_link_type !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + banner_link_type + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	);

	// Banner Link To Validation
	$( "select[name^=banner_link_to]" ).each(
		function () {
			var banner_link_to = velovalidation.checkMandatory( $( this ) );
			if (banner_link_to !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + banner_link_to + '</p>' ) );
				banners_settings_tab = 1;
			}
		}
	);

	// Banner Image Validation
	if ($( "input[name^=banner_image]" ).length > 0) {
		$( "input[name^=banner_image]" ).each(
			function () {
				var banner_image = velovalidation.checkMandatory( $( this ) );
				if (banner_image !== true) {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
					banners_settings_tab = 1;
				} else {
					var banner_image = velovalidation.checkImage( $( this ) );
					if (banner_image !== true) {
						error = true;
						$( this ).addClass( 'error_field' );
						$( this ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
						banners_settings_tab = 1;
					}
				}
			}
		);
	}

	// Banner Sort Order Validation
	var sort_order_value = [];
	$( "input[name^=banner_sort_order]" ).each(
		function () {
			var banner_sort_order_duplicate = $( "#sort_order_duplicate_error" ).val();
			if ($.inArray( $( this ).val(), sort_order_value ) != '-1') {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + banner_sort_order_duplicate + '</p>' ) );
				banners_settings_tab = 1;
			} else {
				sort_order_value.push( $( this ).val() );
			}

			var banner_sort_order = velovalidation.checkMandatory( $( this ) );
			if (banner_sort_order !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + banner_sort_order + '</p>' ) );
				banners_settings_tab = 1;
			} else {
				var banner_sort_order = velovalidation.isNumeric( $( this ), true );
				if (banner_sort_order !== true) {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + banner_sort_order + '</p>' ) );
					banners_settings_tab = 1;
				} else {
					var banner_sort_order = velovalidation.isBetween( $( this ), 0, 999 );
					if (banner_sort_order !== true) {
						error = true;
						$( this ).addClass( 'error_field' );
						$( this ).after( $( '<p class="mab_validation_error">' + banner_sort_order + '</p>' ) );
						banners_settings_tab = 1;
					}
				}
			}
		}
	);

	// Payment Method PayPal Name Validation
	// if (($("input[name='vss_mab[payment_methods][paypal_enabled]']").length > 0 && $("input[name='vss_mab[payment_methods][paypal_enabled]']").is(":checked")) || $("input[name='vss_mab[payment_methods][payment_method_name]']").val().trim() != '') {
	// var payment_method_name = velovalidation.checkMandatory($("input[name='vss_mab[payment_methods][payment_method_name]']"), 255, 1);
	// if (payment_method_name !== true) {
	// error = true;
	// $("input[name='vss_mab[payment_methods][payment_method_name]']").addClass('error_field');
	// $("input[name='vss_mab[payment_methods][payment_method_name]']").after($('<p class="mab_validation_error">' + payment_method_name + '</p>'));
	// payment_methods_tab = 1;
	// }
	// }

	// Payment Method PayPal Code Validation
	// if (($("input[name='vss_mab[payment_methods][paypal_enabled]']").length > 0 && $("input[name='vss_mab[payment_methods][paypal_enabled]']").is(":checked")) || $("input[name='vss_mab[payment_methods][payment_method_code]']").val().trim() != '') {
	// var payment_method_code = velovalidation.checkMandatory($("input[name='vss_mab[payment_methods][payment_method_code]']"), 10, 1);
	// if (payment_method_code !== true) {
	// error = true;
	// $("input[name='vss_mab[payment_methods][payment_method_code]']").addClass('error_field');
	// $("input[name='vss_mab[payment_methods][payment_method_code]']").after($('<p class="mab_validation_error">' + payment_method_code + '</p>'));
	// payment_methods_tab = 1;
	// }
	// }

	// Payment Method PayPal Client ID Validation
	// if (($("input[name='vss_mab[payment_methods][paypal_enabled]']").length > 0 && $("input[name='vss_mab[payment_methods][paypal_enabled]']").is(":checked")) || $("input[name='vss_mab[payment_methods][client_id]']").val().trim() != '') {
	// var client_id = velovalidation.checkMandatory($("input[name='vss_mab[payment_methods][client_id]']"), 255, 1);
	// if (client_id !== true) {
	// error = true;
	// $("input[name='vss_mab[payment_methods][client_id]']").addClass('error_field');
	// $("input[name='vss_mab[payment_methods][client_id]']").after($('<p class="mab_validation_error">' + client_id + '</p>'));
	// payment_methods_tab = 1;
	// }
	// }

	// Payment Method CoD Name Validation
	if (($( "input[name='vss_mab[payment_methods][cod_enabled]']" ).length > 0 && $( "input[name='vss_mab[payment_methods][cod_enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[payment_methods][cod_payment_method_name]']" ).val().trim() != '') {
		var cod_payment_method_name = velovalidation.checkMandatory( $( "input[name='vss_mab[payment_methods][cod_payment_method_name]']" ), 255, 1 );
		if (cod_payment_method_name !== true) {
			error = true;
			$( "input[name='vss_mab[payment_methods][cod_payment_method_name]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[payment_methods][cod_payment_method_name]']" ).after( $( '<p class="mab_validation_error">' + cod_payment_method_name + '</p>' ) );
			payment_methods_tab = 1;
		}
	}

	// Payment Method CoD Code Validation
	if (($( "input[name='vss_mab[payment_methods][cod_enabled]']" ).length > 0 && $( "input[name='vss_mab[payment_methods][cod_enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[payment_methods][cod_payment_method_code]']" ).val().trim() != '') {
		var cod_payment_method_code = velovalidation.checkMandatory( $( "input[name='vss_mab[payment_methods][cod_payment_method_code]']" ), 10, 1 );
		if (cod_payment_method_code !== true) {
			error = true;
			$( "input[name='vss_mab[payment_methods][cod_payment_method_code]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[payment_methods][cod_payment_method_code]']" ).after( $( '<p class="mab_validation_error">' + cod_payment_method_code + '</p>' ) );
			payment_methods_tab = 1;
		}
	}

	// Featured Limit Validation
	if (($( "input[name='vss_mab[featured][enabled]']" ).length > 0 && $( "input[name='vss_mab[featured][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[featured][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[featured][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[featured][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[featured][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			featured_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[featured][limit]']" ), 1, 40 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[featured][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[featured][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Featured Product Image Width Validation
	if (($( "input[name='vss_mab[featured][enabled]']" ).length > 0 && $( "input[name='vss_mab[featured][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[featured][product_image_width]']" ).val().trim() != '') {
		var product_image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[featured][product_image_width]']" ) );
		if (product_image_width !== true) {
			error = true;
			$( "input[name='vss_mab[featured][product_image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[featured][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
			featured_tab = 1;
		} else {
			var product_image_width = velovalidation.isBetween( $( "input[name='vss_mab[featured][product_image_width]']" ), 1, 999 );
			if (product_image_width !== true) {
				error = true;
				$( "input[name='vss_mab[featured][product_image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[featured][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Featured Product Image Height Validation
	if (($( "input[name='vss_mab[featured][enabled]']" ).length > 0 && $( "input[name='vss_mab[featured][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[featured][product_image_height]']" ).val().trim() != '') {
		var product_image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[featured][product_image_height]']" ) );
		if (product_image_height !== true) {
			error = true;
			$( "input[name='vss_mab[featured][product_image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[featured][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
			featured_tab = 1;
		} else {
			var product_image_height = velovalidation.isBetween( $( "input[name='vss_mab[featured][product_image_height]']" ), 1, 999 );
			if (product_image_height !== true) {
				error = true;
				$( "input[name='vss_mab[featured][product_image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[featured][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Specials Limit Validation
	if (($( "input[name='vss_mab[specials][enabled]']" ).length > 0 && $( "input[name='vss_mab[specials][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[specials][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[specials][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[specials][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[specials][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			specials_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[specials][limit]']" ), 1, 40 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[specials][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[specials][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				specials_tab = 1;
			}
		}
	}

	// Specials Product Image Width Validation
	if (($( "input[name='vss_mab[specials][enabled]']" ).length > 0 && $( "input[name='vss_mab[specials][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[specials][product_image_width]']" ).val().trim() != '') {
		var product_image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[specials][product_image_width]']" ) );
		if (product_image_width !== true) {
			error = true;
			$( "input[name='vss_mab[specials][product_image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[specials][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
			specials_tab = 1;
		} else {
			var product_image_width = velovalidation.isBetween( $( "input[name='vss_mab[specials][product_image_width]']" ), 1, 999 );
			if (product_image_width !== true) {
				error = true;
				$( "input[name='vss_mab[specials][product_image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[specials][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Specials Product Image Height Validation
	if (($( "input[name='vss_mab[specials][enabled]']" ).length > 0 && $( "input[name='vss_mab[specials][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[specials][product_image_height]']" ).val().trim() != '') {
		var product_image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[specials][product_image_height]']" ) );
		if (product_image_height !== true) {
			error = true;
			$( "input[name='vss_mab[specials][product_image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[specials][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
			specials_tab = 1;
		} else {
			var product_image_height = velovalidation.isBetween( $( "input[name='vss_mab[specials][product_image_height]']" ), 1, 999 );
			if (product_image_height !== true) {
				error = true;
				$( "input[name='vss_mab[specials][product_image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[specials][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
				specials_tab = 1;
			}
		}
	}

	// Best Sellers Limit Validation
	if (($( "input[name='vss_mab[bestsellers][enabled]']" ).length > 0 && $( "input[name='vss_mab[bestsellers][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[bestsellers][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[bestsellers][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[bestsellers][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[bestsellers][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			bestsellers_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[bestsellers][limit]']" ), 1, 40 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[bestsellers][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[bestsellers][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				bestsellers_tab = 1;
			}
		}
	}

	// Best Sellers Product Image Width Validation
	if (($( "input[name='vss_mab[bestsellers][enabled]']" ).length > 0 && $( "input[name='vss_mab[bestsellers][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[bestsellers][product_image_width]']" ).val().trim() != '') {
		var product_image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[bestsellers][product_image_width]']" ) );
		if (product_image_width !== true) {
			error = true;
			$( "input[name='vss_mab[bestsellers][product_image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[bestsellers][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
			bestsellers_tab = 1;
		} else {
			var product_image_width = velovalidation.isBetween( $( "input[name='vss_mab[bestsellers][product_image_width]']" ), 1, 999 );
			if (product_image_width !== true) {
				error = true;
				$( "input[name='vss_mab[bestsellers][product_image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[bestsellers][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Best Sellers Product Image Height Validation
	if (($( "input[name='vss_mab[bestsellers][enabled]']" ).length > 0 && $( "input[name='vss_mab[bestsellers][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[bestsellers][product_image_height]']" ).val().trim() != '') {
		var product_image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[bestsellers][product_image_height]']" ) );
		if (product_image_height !== true) {
			error = true;
			$( "input[name='vss_mab[bestsellers][product_image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[bestsellers][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
			bestsellers_tab = 1;
		} else {
			var product_image_height = velovalidation.isBetween( $( "input[name='vss_mab[bestsellers][product_image_height]']" ), 1, 999 );
			if (product_image_height !== true) {
				error = true;
				$( "input[name='vss_mab[bestsellers][product_image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[bestsellers][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
				bestsellers_tab = 1;
			}
		}
	}

	// Latest Limit Validation
	if (($( "input[name='vss_mab[latest][enabled]']" ).length > 0 && $( "input[name='vss_mab[latest][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[latest][limit]']" ).val().trim() != '') {
		var limit = velovalidation.checkMandatory( $( "input[name='vss_mab[latest][limit]']" ) );
		if (limit !== true) {
			error = true;
			$( "input[name='vss_mab[latest][limit]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[latest][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
			latest_tab = 1;
		} else {
			var limit = velovalidation.isBetween( $( "input[name='vss_mab[latest][limit]']" ), 1, 40 );
			if (limit !== true) {
				error = true;
				$( "input[name='vss_mab[latest][limit]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[latest][limit]']" ).after( $( '<p class="mab_validation_error">' + limit + '</p>' ) );
				latest_tab = 1;
			}
		}
	}

	// Latest Product Image Width Validation
	if (($( "input[name='vss_mab[latest][enabled]']" ).length > 0 && $( "input[name='vss_mab[latest][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[latest][product_image_width]']" ).val().trim() != '') {
		var product_image_width = velovalidation.checkMandatory( $( "input[name='vss_mab[latest][product_image_width]']" ) );
		if (product_image_width !== true) {
			error = true;
			$( "input[name='vss_mab[latest][product_image_width]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[latest][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
			latest_tab = 1;
		} else {
			var product_image_width = velovalidation.isBetween( $( "input[name='vss_mab[latest][product_image_width]']" ), 1, 999 );
			if (product_image_width !== true) {
				error = true;
				$( "input[name='vss_mab[latest][product_image_width]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[latest][product_image_width]']" ).after( $( '<p class="mab_validation_error">' + product_image_width + '</p>' ) );
				featured_tab = 1;
			}
		}
	}

	// Latest Product Image Height Validation
	if (($( "input[name='vss_mab[latest][enabled]']" ).length > 0 && $( "input[name='vss_mab[latest][enabled]']" ).is( ":checked" )) || $( "input[name='vss_mab[latest][product_image_height]']" ).val().trim() != '') {
		var product_image_height = velovalidation.checkMandatory( $( "input[name='vss_mab[latest][product_image_height]']" ) );
		if (product_image_height !== true) {
			error = true;
			$( "input[name='vss_mab[latest][product_image_height]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[latest][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
			latest_tab = 1;
		} else {
			var product_image_height = velovalidation.isBetween( $( "input[name='vss_mab[latest][product_image_height]']" ), 1, 999 );
			if (product_image_height !== true) {
				error = true;
				$( "input[name='vss_mab[latest][product_image_height]']" ).addClass( 'error_field' );
				$( "input[name='vss_mab[latest][product_image_height]']" ).after( $( '<p class="mab_validation_error">' + product_image_height + '</p>' ) );
				latest_tab = 1;
			}
		}
	}

	// Information Pages List Validation
	$( "input[name^=page_title]" ).each(
		function () {
			var page_title = velovalidation.checkMandatory( $( this ) );
			if (page_title !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + page_title + '</p>' ) );
				information_pages_tab = 1;
			}
		}
	);

	// Information Select Pages List Validation
	$( "select[name^=information_page]" ).each(
		function () {
			var information_page = velovalidation.checkMandatory( $( this ) );
			if (information_page !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + information_page + '</p>' ) );
				information_pages_tab = 1;
			}
		}
	);

	// Information Page Sort Order Validation
	var sort_order_value = [];
	$( "input[name^=page_sort_order]" ).each(
		function () {
			if ($( this ).closest( 'td' ).prev().find( '[type=checkbox]' ).prop( 'checked' )) {
				var page_sort_order_duplicate = $( "#sort_order_duplicate_error" ).val();
				if ($.inArray( $( this ).val(), sort_order_value ) != '-1') {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + page_sort_order_duplicate + '</p>' ) );
					information_pages_tab = 1;
				} else {
					sort_order_value.push( $( this ).val() );
				}
			} else {
				sort_order_value.push( $( this ).val() );
			}

			var page_sort_order = velovalidation.checkMandatory( $( this ) );
			if (page_sort_order !== true) {
				error = true;
				$( this ).addClass( 'error_field' );
				$( this ).after( $( '<p class="mab_validation_error">' + page_sort_order + '</p>' ) );
				information_pages_tab = 1;
			} else {
				var page_sort_order = velovalidation.isNumeric( $( this ), true );
				if (page_sort_order !== true) {
					error = true;
					$( this ).addClass( 'error_field' );
					$( this ).after( $( '<p class="mab_validation_error">' + page_sort_order + '</p>' ) );
					information_pages_tab = 1;
				} else {
					var page_sort_order = velovalidation.isBetween( $( this ), 0, 999 );
					if (page_sort_order !== true) {
						error = true;
						$( this ).addClass( 'error_field' );
						$( this ).after( $( '<p class="mab_validation_error">' + page_sort_order + '</p>' ) );
						information_pages_tab = 1;
					}
				}
			}
		}
	);

	// FaceBook Login Validation
	if (($( "input[name='vss_mab[fb_login_settings][enabled]']" ).length > 0 && $( "input[name='vss_mab[fb_login_settings][enabled]']" ).is( ":checked" ))) {
		var app_id = velovalidation.checkMandatory( $( "input[name='vss_mab[fb_login_settings][app_id]']" ) );
		if (app_id !== true) {
			error = true;
			$( "input[name='vss_mab[fb_login_settings][app_id]']" ).addClass( 'error_field' );
			$( "input[name='vss_mab[fb_login_settings][app_id]']" ).after( $( '<p class="mab_validation_error">' + app_id + '</p>' ) );
			fb_login_settings_tab = 1;
		}
	}

	if (error === true) {
		if (general_settings_tab === 1) {
			$( '#link-general_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-general_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-general_settings' ).children( '.velsof_error_label' ).hide();
		}
		if (push_notification_settings_tab === 1) {
			$( '#link-push_notification_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-push_notification_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-push_notification_settings' ).children( '.velsof_error_label' ).hide();
		}
		if (push_notification_history_tab === 1) {
			$( '#link-push_notification_history' ).children( '.velsof_error_label' ).show();
			$( '#link-push_notification_history' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-push_notification_history' ).children( '.velsof_error_label' ).hide();
		}
		if (slideshow_settings_tab === 1) {
			$( '#link-slideshow_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-slideshow_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-slideshow_settings' ).children( '.velsof_error_label' ).hide();
		}
		if (banners_settings_tab === 1) {
			$( '#link-banners_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-banners_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-banners_settings' ).children( '.velsof_error_label' ).hide();
		}
		if (payment_methods_tab === 1) {
			$( '#link-payment_methods' ).children( '.velsof_error_label' ).show();
			$( '#link-payment_methods' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-payment_methods' ).children( '.velsof_error_label' ).hide();
		}
		if (featured_tab === 1) {
			$( '#link-featured' ).children( '.velsof_error_label' ).show();
			$( '#link-featured' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-featured' ).children( '.velsof_error_label' ).hide();
		}
		if (specials_tab === 1) {
			$( '#link-specials' ).children( '.velsof_error_label' ).show();
			$( '#link-specials' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-specials' ).children( '.velsof_error_label' ).hide();
		}
		if (bestsellers_tab === 1) {
			$( '#link-bestsellers' ).children( '.velsof_error_label' ).show();
			$( '#link-bestsellers' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-bestsellers' ).children( '.velsof_error_label' ).hide();
		}
		if (latest_tab === 1) {
			$( '#link-latest' ).children( '.velsof_error_label' ).show();
			$( '#link-latest' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-latest' ).children( '.velsof_error_label' ).hide();
		}
		if (information_pages_tab === 1) {
			$( '#link-information_pages' ).children( '.velsof_error_label' ).show();
			$( '#link-information_pages' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-information_pages' ).children( '.velsof_error_label' ).hide();
		}
		if (google_login_settings_tab === 1) {
			$( '#link-google_login_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-google_login_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-google_login_settings' ).children( '.velsof_error_label' ).hide();
		}
		if (fb_login_settings_tab === 1) {
			$( '#link-fb_login_settings' ).children( '.velsof_error_label' ).show();
			$( '#link-fb_login_settings' ).children().children( '#velsof_error_icon' ).css( 'display', 'inline' );
		} else {
			$( '#link-fb_login_settings' ).children( '.velsof_error_label' ).hide();
		}
		return false;
	} else {
		$( '#link-general_settings' ).children( '.velsof_error_label' ).hide();
		$( '#link-push_notification_settings' ).children( '.velsof_error_label' ).hide();
		$( '#link-push_notification_history' ).children( '.velsof_error_label' ).hide();
		$( '#link-slideshow_settings' ).children( '.velsof_error_label' ).hide();
		$( '#link-banners_settings' ).children( '.velsof_error_label' ).hide();
		$( '#link-payment_methods' ).children( '.velsof_error_label' ).hide();
		$( '#link-featured' ).children( '.velsof_error_label' ).hide();
		$( '#link-specials' ).children( '.velsof_error_label' ).hide();
		$( '#link-bestsellers' ).children( '.velsof_error_label' ).hide();
		$( '#link-latest' ).children( '.velsof_error_label' ).hide();
		$( '#link-information_pages' ).children( '.velsof_error_label' ).hide();
		$( '#link-google_login_settings' ).children( '.velsof_error_label' ).hide();
		$( '#link-fb_login_settings' ).children( '.velsof_error_label' ).hide();
	}

}

/**
 * Function to validate top category form
 * Module Upgrade V2
 *
 * @date   : 21-Dec-2019
 * @author : neeraj.kumar@velsof.com
 */
function veloValidateTopcategoryForm()
{
	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error          = false;
	var total_category = $( '#mab_total_category' ).val();
	// Loop Upto all category
	for (var category_id = 1; category_id <= total_category; category_id++) {
		// First check mandatory validation
		var category_dropdown = velovalidation.checkMandatory( $( "#category_id_" + category_id ) );
		if (category_dropdown !== true) {
			error = true;
			$( "#category_id_" + category_id ).addClass( 'error_field' );
			$( "#category_id_" + category_id ).after( $( '<p class="mab_validation_error">' + category_dropdown + '</p>' ) );
		}
		// Category heading
		// var category_heading = velovalidation.checkMandatory($("#category_heading_"+category_id));
		// if (category_heading !== true) {
		// error = true;
		// $("#category_heading_"+category_id).addClass('error_field');
		// $("#category_heading_"+category_id).after($('<p class="mab_validation_error">' + category_heading + '</p>'));
		// }
		// Category Image Validation
		// var category_image_mandatory = velovalidation.checkMandatory($("#slideruploadedfile_"+category_id));
		// if (category_image_mandatory !== true) {
		// error = true;
		// $("#slideruploadedfile_"+category_id+"-name").addClass('error_field');
		// $("#image-upload-div-"+category_id).after($('<p class="mab_validation_error">' + category_image_mandatory + '</p>'));
		// }else{
		// var category_image = velovalidation.checkImage($("#slideruploadedfile_"+category_id));
		// if (category_image !== true) {
		// error = true;
		// $("#slideruploadedfile_"+category_id+"-name").addClass('error_field');
		// $("#image-upload-div-"+category_id).after($('<p class="mab_validation_error">' + category_heading + '</p>'));
		// }
		// }
	}
	if (error === true) {
		return false;
	} else {
		return true;
	}
}

/**
 * Function to validate top category form
 * Module Upgrade V2
 *
 * @date   : 21-Dec-2019
 * @author : neeraj.kumar@velsof.com
 */
function veloValidateProductGrid()
{
	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error = false;
	try {
		// First check mandatory validation
		var number_of_product = velovalidation.checkMandatory( $( "#number_of_products" ) );
		if (number_of_product !== true) {
			error = true;
			$( "#number_of_products" ).addClass( 'error_field' );
			$( "#number_of_products" ).after( $( '<p class="mab_validation_error">' + number_of_product + '</p>' ) );
		} else {
			var number_of_product = velovalidation.isNumeric( $( "#number_of_products" ) );
			if (number_of_product !== true) {
				error = true;
				$( "#number_of_products" ).addClass( 'error_field' );
				$( "#number_of_products" ).after( $( '<p class="mab_validation_error">' + number_of_product + '</p>' ) );
			}
		}
		// Check Category Box style display none or block

		if ($( "#category_id" ).is( ':visible' )) {
			// Category Box
			var category_id = velovalidation.checkMandatory( $( "#category_id" ) );
			if (category_id !== true) {
				error = true;
				$( "#category_id" ).addClass( 'error_field' );
				$( "#category_id" ).after( $( '<p class="mab_validation_error">' + category_id + '</p>' ) );
			}
		}

		// Check Category Product: style display none or block
		if ($( "#category_products" ).is( ':visible' )) {
			// Category Product validation
			if ($( "#category_products" ).val() === null || $( "#category_products" ).val() == '') {
				error             = true;
				var error_message = velovalidation.error( 'empty_field' );
				$( "#category_products" ).addClass( 'error_field' );
				$( "#category_products" ).after( $( '<p class="mab_validation_error">' + error_message + '</p>' ) );
			}
		}
		// Check Product List: style display none or block
		if ($( "#product_list" ).is( ':visible' )) {
			// Product list
			if ($( "#product_list" ).val() === null || $( "#product_list" ) == '') {
				error = true;
				$( "#product_list" ).addClass( 'error_field' );
				var error_message = velovalidation.error( 'empty_field' );
				$( "#product_list" ).after( $( '<p class="mab_validation_error">' + error_message + '</p>' ) );
			}
		}
	} catch (exception) {
		error = false;
	}

	if (error === true) {
		return false;
	} else {
		return true;
	}
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 *
 * @date : 23-dec-2019
 * Banner Validation Check
 */
function veloValidateBanner()
{

	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error = false;
	// Component Heading
	// $('input[name="component_heading_name[]"]').each(function(index) {
	// var component_heading = velovalidation.checkMandatory($(this));
	// if (component_heading !== true) {
	// error = true;
	// $($(this)).addClass('error_field');
	// $($(this)).after($('<p class="mab_validation_error">' + component_heading + '</p>'));
	// }
	// });
	// Banner Heading
	// $('input[name="banner_heading_name[]"]').each(function(index) {
	// var banner_heading = velovalidation.checkMandatory($(this));
	// if (banner_heading !== true) {
	// error = true;
	// $($(this)).addClass('error_field');
	// $($(this)).after($('<p class="mab_validation_error">' + banner_heading + '</p>'));
	// }
	// });
	// Redirect Activity
	$( 'select[name="banner_link_type[]"]' ).each(
		function (index) {
			var banner_link_type = velovalidation.checkMandatory( $( this ) );
			if (banner_link_type !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_type + '</p>' ) );
			}
		}
	);
	// Redirect Activity
	$( 'select[name="banner_link_to[]"]' ).each(
		function (index) {
			var banner_link_to = velovalidation.checkMandatory( $( this ) );
			if (banner_link_to !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_to + '</p>' ) );
			}
		}
	);
	// Banner Images
	$( 'select[name="banner_image[]"]' ).each(
		function (index) {
			var banner_image = velovalidation.checkMandatory( $( this ) );
			if (banner_image !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
			} else {
				var banner_image = velovalidation.checkImage( $( this ) );
				if (banner_image !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
				}
			}
		}
	);
	if (error === true) {
		return false;
	} else {
		return true;
	}
}

/**
 * BOC neeraj.kumar@velsof.com Module Upgrade V2
 *
 * @date : 7-Dec-2020
 * Custom Banner Validation Check
 */
function veloValidateCustomBanner()
{

	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error = false;
	// Redirect Activity
	$( 'select[name="banner_link_type[]"]' ).each(
		function (index) {
			var banner_link_type = velovalidation.checkMandatory( $( this ) );
			if (banner_link_type !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_type + '</p>' ) );
			}
		}
	);
	// Redirect Activity
	$( 'select[name="banner_link_to[]"]' ).each(
		function (index) {
			if ($( this ).is( ':visible' )) {
				var banner_link_to = velovalidation.checkMandatory( $( this ) );
				if (banner_link_to !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_to + '</p>' ) );
				}
			}
		}
	);
	// Banner Images
	$( 'select[name="banner_image[]"]' ).each(
		function (index) {
			var banner_image = velovalidation.checkMandatory( $( this ) );
			if (banner_image !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
			} else {
				var banner_image = velovalidation.checkImage( $( this ) );
				if (banner_image !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
				}
			}
		}
	);

	// Banner Width - non-mandatory only interger value accept which is less then or equal to 100
	// Redirect Activity
	$( 'input[name="banner_width[]"]' ).each(
		function (index) {
			var banner_width = velovalidation.checkMandatory( $( this ) );
			if (banner_width) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);

	// Banner Height - non-mandatory only interger value accept which is less then or equal to 100
	$( 'input[name="banner_height[]"]' ).each(
		function (index) {
			var banner_height = velovalidation.checkMandatory( $( this ) );
			if (banner_height) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);

	// inset_top - non-mandatory only interger value accept which is less then or equal to 100
	$( 'input[name="inset_top[]"]' ).each(
		function (index) {
			var inset_top = velovalidation.checkMandatory( $( this ) );
			if (inset_top) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);
	// inset_top - non-mandatory only interger value accept which is less then or equal to 100
	$( 'input[name="inset_bottom[]"]' ).each(
		function (index) {
			var inset_bottom = velovalidation.checkMandatory( $( this ) );
			if (inset_bottom) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);
	// inset_top - non-mandatory only interger value accept which is less then or equal to 100
	$( 'input[name="inset_left[]"]' ).each(
		function (index) {
			var inset_left = velovalidation.checkMandatory( $( this ) );
			if (inset_left) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);
	// inset_top - non-mandatory only interger value accept which is less then or equal to 100
	$( 'input[name="inset_right[]"]' ).each(
		function (index) {
			var inset_right = velovalidation.checkMandatory( $( this ) );
			if (inset_right) {
				// Check valid percentage
				var valid_percentage = velovalidation.checkPercentage( $( this ) );
				if (valid_percentage !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + valid_percentage + '</p>' ) );
				}
			}
		}
	);

	if (error === true) {
		return false;
	} else {
		return true;
	}
}

function veloValidateBannerCountdown()
{
	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error = false;
	// Component Heading
	// $('input[name="component_heading_name[]"]').each(function(index) {
	// var component_heading = velovalidation.checkMandatory($(this));
	// if (component_heading !== true) {
	// error = true;
	// $($(this)).addClass('error_field');
	// $($(this)).after($('<p class="mab_validation_error">' + component_heading + '</p>'));
	// }
	// });
	// Banner Heading
	// $('input[name="banner_heading_name[]"]').each(function(index) {
	// var banner_heading = velovalidation.checkMandatory($(this));
	// if (banner_heading !== true) {
	// error = true;
	// $($(this)).addClass('error_field');
	// $($(this)).after($('<p class="mab_validation_error">' + banner_heading + '</p>'));
	// }
	// });
	// Redirect Activity
	$( 'select[name="banner_link_type[]"]' ).each(
		function (index) {
			var banner_link_type = velovalidation.checkMandatory( $( this ) );
			if (banner_link_type !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_type + '</p>' ) );
			}
		}
	);
	// Redirect Activity
	$( 'select[name="banner_link_to[]"]' ).each(
		function (index) {
			var banner_link_to = velovalidation.checkMandatory( $( this ) );
			if (banner_link_to !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_link_to + '</p>' ) );
			}
		}
	);
	// Timer validity
	$( 'input[name="timer_validity[]"]' ).each(
		function (index) {
			var timer_validity = velovalidation.checkMandatory( $( this ) );
			if (timer_validity !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ).parent() ).after( $( '<p class="mab_validation_error">' + timer_validity + '</p>' ) );
			}
		}
	);
	// Banner Images
	$( 'select[name="banner_image[]"]' ).each(
		function (index) {
			var banner_image = velovalidation.checkMandatory( $( this ) );
			if (banner_image !== true) {
				error = true;
				$( $( this ) ).addClass( 'error_field' );
				$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
			} else {
				var banner_image = velovalidation.checkImage( $( this ) );
				if (banner_image !== true) {
					error = true;
					$( $( this ) ).addClass( 'error_field' );
					$( $( this ) ).after( $( '<p class="mab_validation_error">' + banner_image + '</p>' ) );
				}
			}
		}
	);

	if (error === true) {
		return false;
	} else {
		return true;
	}
}

/**
 * Validation applied in Tab bar layout form :
 * 23-Jan-2020 : Module Upgrade V2 , neeraj.kumar@velsof.com
 */
function veloValidateTabIconForm()
{
	$( '.error_field' ).removeClass( 'error_field' );
	$( '.mab_validation_error' ).remove();
	var error                  = false;
	var tab_icon_redirect_type = velovalidation.checkMandatory( $( '#tab_icon_redirect_type' ) );
	if (tab_icon_redirect_type !== true) {
		error = true;
		$( '#tab_icon_redirect_type' ).addClass( 'error_field' );
		$( '#tab_icon_redirect_type' ).after( $( '<p class="mab_validation_error">' + tab_icon_redirect_type + '</p>' ) );
	}
	var tab_icon_text_1 = velovalidation.checkMandatory( $( '#tab_icon_text_1' ) );
	if (tab_icon_text_1 !== true) {
		error = true;
		$( '#tab_icon_text_1' ).addClass( 'error_field' );
		$( '#tab_icon_text_1' ).after( $( '<p class="mab_validation_error">' + tab_icon_text_1 + '</p>' ) );
	}

	// Tab Bar Images :
	var tab_bar_images = velovalidation.checkMandatory( $( '#tab_bar_images' ) );
	if (tab_bar_images !== true) {
		var tab_bar_images_text = velovalidation.checkMandatory( $( '#tab_bar_images-name' ) );
		if (tab_bar_images_text !== true) {
			error = true;
			$( '#tab_bar_images-name' ).addClass( 'error_field' );
			$( '#image-upload-div-1' ).after( $( '<p class="mab_validation_error">' + tab_bar_images + '</p>' ) );
		}
	} else {
		var tab_bar_images = velovalidation.checkImage( $( '#tab_bar_images' ) );
		if (tab_bar_images !== true) {
			error = true;
			$( '#tab_bar_images-name' ).addClass( 'error_field' );
			$( '#image-upload-div-1' ).after( $( '<p class="mab_validation_error">' + tab_bar_images + '</p>' ) );
		}
	}

	if (error === true) {
		return false;
	} else {
		return true;
		// submit_tab_bar_form();
	}
}
// Close Send Push Notification Block
$( "#send_notification_close" ).click(
	function () {
		$( "#send_notification_panel" ).slideUp();
		$( "#send_notification_close" ).hide();
		$( "#send_notification" ).show();
	}
);

// Open Send Push Notification Block
$( "#send_notification" ).click(
	function () {
		$( "#send_notification_panel" ).slideDown();
		$( "#send_notification_close" ).show();
		$( "#send_notification" ).hide();
	}
);

function wmab_set_redirect_type(val)
{
	$( "#notification_category_list" ).hide();
	$( "#notification_product_list" ).hide();
	if (val == 'category') {
		$( "#notification_category_list" ).show();
	} else if (val == 'product') {
		$( "#notification_product_list" ).show();
	}
}
// BOC neeraj.kumar@velsof.com 19-Dec-2019 show upload image section in general setting page
// Function to show/hide upload image section
function mab_logo_upload_image()
{
	$( "#logo_upload_image" ).hide();
	// Mobile Preview Logo Image
	$( '.mCS_img_loaded' ).hide();
	$( '.logo p' ).show();
	$( '.logo' ).css( 'margin-right', '2.5em' );

	if ($( "#logo_status" ).is( ":checked" )) {
		$( "#logo_upload_image" ).show();

		// Mobile Preview Logo Image
		$( '.mCS_img_loaded' ).show();
		$( '.logo p' ).hide();
		$( '.logo' ).css( 'margin-right', '1em' );
	}
}
/**
 *
 * @param {type} input
 * show image after change input file
 */
function mab_logo_change_image(input)
{
	if (input.files && input.files[0]) {
			var reader    = new FileReader();
			reader.onload = function (e) {
				$( '#logo_image' ).attr( 'src', e.target.result );
				$( '#logo_image' ).css( 'width', '180px' );
				$( '#logo_image' ).show();

				// Mobile Preview Logo Image
				$( '.mCS_img_loaded' ).attr( 'src', e.target.result );
				$( '.mCS_img_loaded' ).show();
			}

		reader.readAsDataURL( input.files[0] );
	}
}
/**
 * show whatsup chat number input box
 */
function mab_show_whatup_chat_box()
{
	$( "#whatsup_chat_support" ).hide();
	$( ".chatBoxIcon" ).hide();
	if ($( "#whatsup_chat_support_status" ).is( ":checked" )) {
		$( "#whatsup_chat_support" ).show();
		$( ".chatBoxIcon" ).show();
	}
}
/**
 * show mandatory number box when registration from number enable
 */
function mab_show_mandatory_number_box()
{
	$( "#number_mandatory_div" ).hide();
	if ($( "#phone_number_registration_status" ).is( ":checked" )) {
		$( "#number_mandatory_div" ).show();
	}
}

$( ".mColorPicker" ).each(
	function () {
		var rgb = $( this ).css( "backgroundColor" );
		rgb     = rgb.replace( /[^\d,]/g, '' ).split( "," );
		var y   = 2.99 * rgb[0] + 5.87 * rgb[1] + 1.14 * rgb[2];
		if (y >= 1275) {
			$( this ).css( 'color','black' );
		} else {
			$( this ).css( 'color','white' );
		}
	}
);
function setCategoryId(a)
{
	if ($( a ).val != '') {
		for (i = 1; i < 8; i++) {
			var cat_id = 'category_id_' + i;
			if ($( a ).attr( 'id' ) != cat_id) {
				if ($( '#category_id_' + i ).val() == $( a ).val()) {
					$( '#category_id_' + i ).val( '' );
				}
			}
		}
	}
}
