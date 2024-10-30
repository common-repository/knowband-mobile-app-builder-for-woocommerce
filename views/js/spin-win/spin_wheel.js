/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 * We offer the best and most useful modules PrestaShop and modifications for your online store.
 *
 * @author    knowband.com <support@knowband.com>
 * @copyright 2017 Knowband
 * @license   see file: LICENSE.txt
 * @category  PrestaShop Module
 *
 *
 * Description
 *
 * Gamification wheel for offering discount coupons.
 */

var copy_msg_show = true;
var globaltime    = 0;
var intervalVariable;
var wheelStartTime       = 0;
var currentRotation      = 0;
var rotationDegree       = 0;
var wheelEndTime         = 0;
var wheelcurrentRotation = 0;
var startTime            = 0;
var winningCode          = "";

function animationFrame(animate)
{
	if (window.requestAnimationFrame) {
		window.requestAnimationFrame( animate );
	} else if (window.webkitRequestAnimationFrame) {
		window.webkitRequestAnimationFrame( animate );
	} else if (window.mozRequestAnimationFrame) {
		window.mozRequestAnimationFrame( animate );
	} else {
		Console.log( 'Sorry! No Supported Browser' );
	}
}

function rotateWheel(degreeToRotate, rotationTime)
{
	currentRotation = 0;
	rotationDegree  = degreeToRotate;
	wheelStartTime  = 0;
	wheelEndTime    = rotationTime;
	startTime       = 0;
	animationFrame( animate );
}

function wheelRotation(movement)
{
	return 1 - Math.pow( 1 - movement, 5 );
}

function pointerMovement(movement)
{
	var n = (-Math.pow( (1 - (movement * 2)), 2 ) + 1);
	if (n < 0) {
		n = 0;
	}
	return n;
}

function animate(timestamp)
{
	if ( ! startTime) {
		startTime = timestamp;
	}

	wheelStartTime = timestamp - startTime;

	if (wheelStartTime > wheelEndTime) {
		wheelStartTime = wheelEndTime;
	}

	wheelcurrentRotation = wheelRotation( ((rotationDegree / wheelEndTime) * wheelStartTime) / rotationDegree );
	currentRotation      = wheelcurrentRotation * rotationDegree;

	/**
 * Stop Pointer Movement if wheel rotation is 1
*/
	if (wheelcurrentRotation > 0.99) {
		if (wheel_design != "1") {
			$( '#velsof_wheel_pointer' ).css( {'transform': 'translateY(0%) rotate3d(0,0,1,0deg)', '-webkit-transform': 'translateY(0%) rotate3d(0,0,1,0deg)'} );
		}
	}

	tickerRotation = currentRotation - Math.floor( currentRotation / 360 ) * 360;
	for (i = 1; i <= 12; i++) {
		if ((tickerRotation >= (i * 30) - 20) && (tickerRotation <= (i * 30))) {
			angleRotation = 0.2;
			if (wheelcurrentRotation > angleRotation) {
				angleRotation = wheelcurrentRotation;
			}
			var pointerAngle = pointerMovement( -(((i * 30) - 20) - tickerRotation) / 10 ) * (30 * angleRotation);
			if (wheel_design != "1") {
				$( '#velsof_wheel_pointer' ).css( {'transform': 'translateY(0%)  rotate3d(0,0,1,' + (0 - pointerAngle) + 'deg)', '-webkit-transform': 'translateY(0%)  rotate3d(0,0,1,' + (0 - pointerAngle) + 'deg)'} );
			}
		}
	}

	// console.log(wheelcurrentRotation);

	if (wheelcurrentRotation < 1) {
		$( '#velsof_spinner' ).css( {'transform': 'rotate3d(0,0,1,' + currentRotation + 'deg)', '-webkit-transform': 'rotate3d(0,0,1,' + currentRotation + 'deg)'} );
		animationFrame( animate );
	}
	if (wheelcurrentRotation > .999) {
		$( '#main_title' ).hide();
		$( '#velsof_description' ).hide();
		$( '.velsof_ul' ).hide();
		$( '#rotate_btn' ).hide();
		$( '#velsof_spin_wheel' ).hide();
		$( '.saving' ).hide();
		$( '#exit' ).hide();
		$( '#suc_msg' ).show();
		$( '#velsof_success_description' ).show();

		if (display_option == 2) {
			if (winningCode !== '') {
				$( '#velsof_spin_wheel' ).val( email_only_msg );
				$( '#velsof_spin_wheel' ).show();
			}
			$( '#continue_btn' ).show();
		} else {
			if (winningCode !== '') {
				copy_msg_show = false;
				$( '#velsof_spin_wheel' ).val( winningCode );
				$( '#velsof_spin_wheel' ).show();
				if (show_fireworks == "1") {
					$( '#velsof_wheel_main_container' ).fireworks();
				}
			}
			$( '#continue_btn' ).show();
		}
	}
}

function checkEnteredEmail(email)
{
	var error = false;
	$( '.spin_error' ).remove();
	$( document ).ready(
		function () {
			$( '#velsof_spin_wheel' ).tooltipster(
				{
					animation: 'swing',
					'theme': ['tooltipster-default', 'tooltipster-velsofspinwheel']
				}
			);
		}
	);
	var email_mand  = velovalidation.checkMandatory( $( "input[name='spin_wheel_email']" ) );
	var email_valid = velovalidation.checkEmail( $( "input[name='spin_wheel_email']" ) );
	if (email_mand !== true) {
		error = true;
		$( '#velsof_spin_wheel' ).tooltipster( 'content', email_mand );
		$( '#velsof_spin_wheel' ).tooltipster( 'show' );
		setTimeout(
			function () {
				$( '#velsof_spin_wheel' ).tooltipster( 'destroy' );
			},
			2000
		);
		return error;
	} else if (email_valid !== true) {
		error = true;
		$( '#velsof_spin_wheel' ).tooltipster( 'content', email_valid );
		$( '#velsof_spin_wheel' ).tooltipster( 'show' );
		setTimeout(
			function () {
				$( '#velsof_spin_wheel' ).tooltipster( 'destroy' );
			},
			2000
		);
		return error;
	} else {
		return error;
	}
}

function getCookie(name)
{
	var dc     = document.cookie;
	var prefix = name + "=";
	var begin  = dc.indexOf( "; " + prefix );
	if (begin == -1) {
		begin = dc.indexOf( prefix );
		if (begin != 0) {
			return null;
		}
	} else {
		begin  += 2;
		var end = document.cookie.indexOf( ";", begin );
		if (end == -1) {
			end = dc.length;
		}
	}
	return decodeURI( dc.substring( begin + prefix.length, end ) );
}
jQuery( document ).ready(
	function ($) {
		var show = true;
		if (typeof hide_after != 'undefined') {
			if (hide_after != '') {
				$( '#pull_out' ).hide();
				setTimeout(
					function () {
						$( '#pull_out' ).hide();
						$( "#velsof_wheel_container" ).hide();
						setTimeout(
							function () {
								if (typeof show_pull_out != 'undefined' && show_pull_out == 1) {
									$( '#pull_out' ).show();
								} else {
									$( '#pull_out' ).hide();
								}
								$( '#velsof_wheel_main_container' ).removeClass( 'transform' );
							},
							500
						);
					},
					hide_after * 1000
				);
			}
		}

		if (typeof min_screen_size != 'undefined') {
			if (min_screen_size !== '') {
				var screen = min_screen_size.split( '_' );
				var width  = screen[0];
				var height = screen[1];
				if (window.screen.width < width) {
					$( '#velsof_wheel_main_container' ).removeClass( 'transform' );
					$( "#velsof_wheel_container" ).hide();
					$( '#pull_out' ).hide();
					show = false;
				}
			}
		}

		if (typeof time_display != 'undefined' && time_display !== '') {

			var time     = Number( time_display ) * 1000;
			var myCookie = getCookie( "velsof_spin_wheel_tab" );
			// var velsof_wheel_used = getCookie('velsof_wheel_used');
			var velsof_wheel_used = null;
			if (myCookie == null && velsof_wheel_used == null) {
				if (typeof show_pull_out != 'undefined' && show_pull_out == 1) {
					$( '#pull_out' ).show();
				} else {
					$( '#pull_out' ).hide();
				}
				setTimeout(
					function () {
						$( '#velsof_wheel_container' ).show();
						$( '#pull_out' ).hide();
						// document.getElementById("velsof_wheel_container").style.display = 'block';
						$( '#velsof_wheel_main_container' ).addClass( 'transform' );
					},
					time
				);
			}
		} else if (typeof scroll_display != 'undefined' && scroll_display !== '') {

			$( document ).on(
				"scroll",
				function () {
					if ( ! window.displayed_through_scroll) {
						var s             = $( window ).scrollTop(),
						d                 = $( document ).height(),
						c                 = $( window ).height();
						var scrollPercent = (s / (d - c)) * 100;
						if (scrollPercent >= scroll_display) {
							var myCookie = getCookie( "velsof_spin_wheel_tab" );
							// var velsof_wheel_used = getCookie('velsof_wheel_used');
							var velsof_wheel_used = null;
							if (myCookie == null && velsof_wheel_used == null) {
								if (typeof show_pull_out != 'undefined' && show_pull_out == 1) {
									$( '#pull_out' ).show();
								} else {
									$( '#pull_out' ).hide();
								}
								setTimeout(
									function () {
										$( '#pull_out' ).hide();
										$( "#velsof_wheel_container" ).show();
										$( "#velsof_wheel_main_container" ).addClass( "transform" );

										window.displayed_through_scroll = true;

									}
									,
									300
								);
							}
						}
					}
				}
			);

		} else if (typeof exit_display != 'undefined' && exit_display == true) {

			var myCookie = getCookie( "velsof_spin_wheel_tab" );
			// var velsof_wheel_used = getCookie('velsof_wheel_used');
			var velsof_wheel_used = null;
			if (myCookie == null && velsof_wheel_used == null) {
				if (typeof show_pull_out != 'undefined' && show_pull_out == 1) {
					$( '#pull_out' ).show();
				} else {
					$( '#pull_out' ).hide();
				}
				setTimeout(
					function () {
						var popup = ouibounce(
							document.getElementById( "velsof_wheel_container" ),
							{
								aggressive: true,
								timer: 0,
								callback: function () {
									$( '#pull_out' ).hide();

									setTimeout(
										function () {
											jQuery( '#velsof_wheel_main_container' ).addClass( 'transform' );
										},
										500
									);

								}
							}
						);
					},
					500
				);
			}
		} else {

			if (show) {
				var myCookie = getCookie( "velsof_spin_wheel_tab" );
				// var velsof_wheel_used = getCookie('velsof_wheel_used');
				var velsof_wheel_used = null;
				if (myCookie == null && velsof_wheel_used == null) {
					$( '#velsof_wheel_container' ).show();
					setTimeout(
						function () {
							$( '#velsof_wheel_main_container' ).addClass( 'transform' );
						},
						500
					);
				} else {
					var cookie = getCookie( 'velsof_wheel_used' );
					if (typeof show_pull_out != 'undefined' && show_pull_out == 1 && cookie == null) {
						$( '#pull_out' ).show();
					}
				}
			}
		}

		$( '.cancel_button' ).on(
			'click',
			function () {
				setCookie( 'velsof_spin_wheel_tab', 3 );
				$( '#velsof_wheel_main_container' ).removeClass( 'transform' );
				setTimeout(
					function () {
						$( '#velsof_wheel_container' ).hide();
						if (typeof show_pull_out != 'undefined' && show_pull_out == 1) {
							$( '#pull_out' ).show();
						}
					},
					500
				);
			}
		);

		$( '#continue_btn' ).on(
			'click',
			function () {
				if (display_option != 2 && copy_msg_show == false) {
					copyToClipboard( document.getElementById( "velsof_spin_wheel" ) );
					$( '#velsof_spin_wheel' ).tooltipster(
						{
							animation: 'swing',
							'theme': ['tooltipster-default', 'tooltipster-velsofspinwheel']
						}
					);

					$( '#velsof_spin_wheel' ).tooltipster( 'content', copy_msg );
					$( '#velsof_spin_wheel' ).tooltipster( 'show' );
					/*
					setTimeout(function () {
						$('#velsof_spin_wheel').tooltipster('destroy');
						$('#velsof_wheel_main_container').removeClass('transform');
						setTimeout(function () {
						$('#velsof_wheel_container').hide();

						$('#pull_out').hide();

						}, 500);

					}, 2000);
					*/
				} else {
					$( '#velsof_wheel_main_container' ).removeClass( 'transform' );
					/*
					setTimeout(function () {
					$('#velsof_wheel_container').hide();
					$('#pull_out').hide();
					}, 500);
					*/
				}
				// Line added by Harsh on 17-Oct-2019 to destroy Fireworks instance initiated on the page to avoid unwanted console errors.
				$( '#velsof_wheel_main_container' ).fireworks( 'destroy' );

			}
		);

		$( '.spin_toggle' ).on(
			'click',
			function () {
				$( '#pull_out' ).hide();
				$( '#velsof_wheel_container' ).show();

				setTimeout(
					function () {
						$( '#velsof_wheel_main_container' ).addClass( 'transform' );
					},
					500
				);
			}
		);

	}
);

function setCookie(cookie_name, cookie_value)
{
	date = new Date();
	date.setTime( date.getTime() + 24 * 60 * 60 * 1000 );
	expires         = "; expires=" + date.toUTCString();
	document.cookie = cookie_name + '=' + cookie_value + expires + '; path=/';
}

function copyToClipboard(elem)
{
	// create hidden text element, if it doesn't already exist
	var targetId = "_hiddenCopyText_";
	var isInput  = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
	var origSelectionStart, origSelectionEnd;
	if (isInput) {
		// can just use the original source element for the selection and copy
		target             = elem;
		origSelectionStart = elem.selectionStart;
		origSelectionEnd   = elem.selectionEnd;
	} else {
		// must use a temporary form element for the selection and copy
		target = document.getElementById( targetId );
		if ( ! target) {
			var target            = document.createElement( "textarea" );
			target.style.position = "absolute";
			target.style.left     = "-9999px";
			target.style.top      = "0";
			target.id             = targetId;
			document.body.appendChild( target );
		}
		target.textContent = elem.textContent;
	}
	// select the content
	var currentFocus = document.activeElement;
	target.focus();
	target.setSelectionRange( 0, target.value.length );

	// copy the selection
	var succeed;
	try {
		succeed = document.execCommand( "copy" );
	} catch (e) {
		succeed = false;
	}
	// restore original focus
	if (currentFocus && typeof currentFocus.focus === "function") {
		currentFocus.focus();
	}

	if (isInput) {
		// restore prior selection
		elem.setSelectionRange( origSelectionStart, origSelectionEnd );
	} else {
		// clear temporary content
		target.textContent = "";
	}
	return succeed;
}
