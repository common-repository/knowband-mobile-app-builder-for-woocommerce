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


var viewPortWidthVelsof  = Math.max( document.documentElement.clientWidth, window.innerWidth || 0 );
var viewPortHeightVelsof = Math.max( document.documentElement.clientHeight, window.innerHeight || 0 );

window.onload = function (e) {
	// var velsofWheelHexCode = '';
	var velsofWheelHexCode = "#4497bb";
	var colorRGB           = hexToRgb( velsofWheelHexCode );
	var hslColorCode       = rgb2hsb( colorRGB.r, colorRGB.g, colorRGB.b );
}

window.onresize = function () {

}

function hexToRgb(hex)
{
	// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
	var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	hex                = hex.replace(
		shorthandRegex,
		function (m, r, g, b) {
			return r + r + g + g + b + b;
		}
	);

	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec( hex );
	return result ? {
		r: parseInt( result[1], 16 ),
		g: parseInt( result[2], 16 ),
		b: parseInt( result[3], 16 )
	} : null;
}

function rgb2hsb(r, g, b)
{
	r             /= 255;
	g             /= 255;
	b             /= 255; // Scale to unity.
	var minVal     = Math.min( r, g, b ),
			maxVal = Math.max( r, g, b ),
			delta  = maxVal - minVal,
			HSB    = {hue: 0, sat: 0, bri: maxVal},
	del_R, del_G, del_B;

	if (delta !== 0) {
		HSB.sat = delta / maxVal;
		del_R   = (((maxVal - r) / 6) + (delta / 2)) / delta;
		del_G   = (((maxVal - g) / 6) + (delta / 2)) / delta;
		del_B   = (((maxVal - b) / 6) + (delta / 2)) / delta;

		if (r === maxVal) {
			HSB.hue = del_B - del_G;
		} else if (g === maxVal) {
			HSB.hue = (1 / 3) + del_R - del_B;
		} else if (b === maxVal) {
			HSB.hue = (2 / 3) + del_G - del_R;
		}

		if (HSB.hue < 0) {
			HSB.hue += 1;
		}
		if (HSB.hue > 1) {
			HSB.hue -= 1;
		}
	}

	HSB.hue *= 360;
	HSB.sat *= 100;
	HSB.bri *= 100;
	return HSB;
}
