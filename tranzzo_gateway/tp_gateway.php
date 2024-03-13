<?php
/*
Plugin Name: Платіжний шлюз
Description: Платіжний шлюз для сайтів WordPress.
Version: 2.0.1
Last Update: 30.01.2024
Author: TRANZZO
Author URI: https://docs.tranzzo.com/uk/
Text Domain: tp_gateway
Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include(plugin_dir_path(__FILE__) . 'config.php');
include(plugin_dir_path(__FILE__) . '/models/TP_Gateway_Transaction.php');

add_action('plugins_loaded', 'woocommerceMyPlugin', 0);
function woocommerceMyPlugin()
{
    load_plugin_textdomain( 'tp_gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

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

function activate_tp_gateway_plugin() {
    create_table_in_db();
}
register_activation_hook(__FILE__, 'activate_tp_gateway_plugin');

function create_table_in_db() {
    global $wpdb;

    $prefix = $wpdb->prefix;

    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}tp_gateway_transactions (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        type ENUM('purchase','auth','capture','void','refund') NOT NULL,
        amount DECIMAL(26,8) DEFAULT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        order_id BIGINT(20) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY order_id_key (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

include(plugin_dir_path(__FILE__) . 'actions.php');
?>
