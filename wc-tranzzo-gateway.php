<?php
/*
Plugin Name: TRANZZO Gateway
Description: Платіжний шлюз "TRANZZO" для сайтів WordPress.
Version: 2.0
Last Update: 30.01.2024
Author: TRANZZO
Author URI: https://tranzzo.com
*/

add_action('init', 'tranzzo_endpoint');
add_action('pre_get_posts', 'tranzzo_listen_redirect');

function tranzzo_endpoint()
{
    add_rewrite_endpoint('tranzzo-redirect', EP_ROOT);
}

function tranzzo_listen_redirect($query)
{
    if (($query->get('pagename') == 'tranzzo-redirect') || (strpos($_SERVER['REQUEST_URI'], 'tranzzo-redirect') !== false)) {
        if (!class_exists('My_Custom_Gateway')) {
            include(plugin_dir_path(__FILE__) . 'class-gateway.php');
        }

        (new My_Custom_Gateway)->generate_form($_REQUEST['order_id']);
        exit;
    }
}

add_action('plugins_loaded', 'woocommerce_myplugin', 0);
function woocommerce_myplugin()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}


add_filter('woocommerce_payment_gateways', 'add_my_custom_gateway');

function add_my_custom_gateway($gateways)
{
    $gateways[] = 'My_Custom_Gateway';
    return $gateways;
}


function declare_cart_checkout_blocks_compatibility()
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

add_action('woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type');
function oawoo_register_order_approval_payment_method_type()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register(new My_Custom_Gateway_Blocks);
        }
    );
}

?>
