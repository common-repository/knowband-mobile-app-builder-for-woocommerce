<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 *
 * @package WooCommerce/Templates
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
    #trp-floater-ls {
        display: none !important;
    }

    .demo_store {
        display: none !important;
    }

    #secondary {
        display: none !important;
    }

    .storefront-breadcrumb {
        display: none !important;
    }

    .button {
        display: none !important;
    }

    header,
    footer {
        display: none !important;
    }

    .header,
    .footer {
        display: none !important;
    }

    #velsof_wheel_container {
        display: none !important;
    }

    <?php
    // Custom CSS
    echo esc_html($settings['general']['custom_css']);
    ?>
</style>