<?php
/*
Plugin Name: TRANZZO Gateway
Description: Платіжний шлюз "TRANZZO" для сайтів WordPress.
Version: 2.0.1
Last Update: 30.01.2024
Author: TRANZZO
Author URI: https://tranzzo.com
*/

add_action('plugins_loaded', 'woocommerceMyPlugin', 0);
function woocommerceMyPlugin()
{
    load_plugin_textdomain( 'tranzzo_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    if (!class_exists('WC_Payment_Gateway'))
        return;

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}


add_filter('woocommerce_payment_gateways', 'addMyCustomGateway');

function addMyCustomGateway($gateways)
{
    $gateways[] = 'My_Custom_Gateway';
    return $gateways;
}


function declareCartCheckoutBlocksCompatibility()
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
}

add_action('before_woocommerce_init', 'declareCartCheckoutBlocksCompatibility');

add_action('woocommerce_blocks_loaded', 'wooRegisterOrderApprovalPaymentMethodType');
function wooRegisterOrderApprovalPaymentMethodType()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new My_Custom_Gateway_Blocks);
        }
    );
}
?>
