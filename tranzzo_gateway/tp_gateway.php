<?php
/*
Plugin Name: Платіжний шлюз
Description: Платіжний шлюз для сайтів WordPress.
Version: 2.0.1
Last Update: 30.01.2024
Author: TRANZZO
Author URI: https://tranzzo.com
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

add_action('admin_footer', 'custom_payment_gateway_admin_script');
function custom_payment_gateway_admin_script() {
    $current_screen = get_current_screen();
    if ($current_screen->id === 'woocommerce_page_wc-settings') {
        ?>
        <style>
            input[type=text].error-required,
            input[type=password].error-required{
                border: 1px solid #d63638;
            }
            .custom-field-error{
                color: #d63638;
                margin-left: 10px;
            }
        </style>
        <script>
            console.log('test');
            jQuery(document).ready(function ($) {
                const requiredFields = [
                    $('#woocommerce_my_custom_gateway_POS_ID'),
                    $('#woocommerce_my_custom_gateway_API_KEY'),
                    $('#woocommerce_my_custom_gateway_API_SECRET'),
                    $('#woocommerce_my_custom_gateway_ENDPOINTS_KEY'),
                ];
                let formElement = $('.update-form-table').parent('form');
                let saveButton = formElement.find('.woocommerce-save-button');

                requiredFields.forEach(function(requiredField){
                    if(requiredField.length) {
                        requiredField.prop('required', true);
                    }
                });

                if(saveButton.length){
                    saveButton.on('click', function (e) {
                        $('.error-required').removeClass('error-required');
                        $('.custom-field-error').remove();

                        let errorsCountEl = 0;
                        requiredFields.forEach(function(requiredField){
                            if(requiredField.length && requiredField.val() === "") {
                                requiredField.addClass('error-required');

                                let htmlEl = '<span class="custom-field-error">'+
                                    '<?php _e('Полe обов’язкове для заповнення', "tp_gateway"); ?>'+
                                    '</span>';

                                requiredField.next('.description').append(htmlEl);
                                errorsCountEl += 1;
                            }
                        });

                        if(errorsCountEl > 0){
                            return false;
                        }
                    });
                }

                return true;
            });
        </script>
        <?php
    }
}
add_filter( 'manage_edit-shop_order_columns', 'payment_gateway_orders_column' );
function payment_gateway_orders_column( $columns ) {

    $columns['order_transactions'] = __('Транзакції', 'tp_gateway');
    $columns['order_total_custom'] = __('Total', 'woocommerce');
    unset($columns['order_total']);

    return $columns;
}

add_action('manage_shop_order_posts_custom_column' , 'payment_gateway_orders_column_content', 11, 2);
function payment_gateway_orders_column_content($column, $post_id) {
    $payment_method_id = 'my_custom_gateway';
    $order = wc_get_order($post_id);
    $order_id = $order->get_id();
    $output = '';
    $colorArr = array(
        'purchase' => '#1d5e00',
        'capture' => '#1d5e00',
        'auth' => '#2271b1',
        'void' => '#a00',
        'refund' => '#a00'
    );

    if($column  == 'order_transactions') {
        if($order->get_payment_method() === $payment_method_id) {
            $transactions = new TP_Gateway_Transaction();
            $transactionsArray = $transactions->search_transactions_by_order_id($order_id);
            if($transactionsArray && !empty($transactionsArray)){
                foreach ($transactionsArray as $transaction){
                    $output .= '<p>
                                    <span class="woocommerce-Price-amount amount" style="color: '.$colorArr[$transaction['type']].';">
                                        <span>'.$transaction['type'].': </span>
                                        <bdi>' .number_format( (float) $transaction['amount'], 2, ',', ''). '</bdi>
                                    <span class="woocommerce-Price-currencySymbol">'.get_woocommerce_currency_symbol($order->get_currency()).'</span>
                                    </span>
                                </p>';
                }
            }

            echo $output;
        }
    }

    if($column  == 'order_total_custom'){
        echo $order->get_total().' '.get_woocommerce_currency_symbol($order->get_currency());
    }
}

add_action( 'woocommerce_order_details_after_order_table', 'custom_display_order_extra_info', 10, 1 );
function custom_display_order_extra_info($order) {
    $payment_method_id = 'my_custom_gateway';
    $order_id = $order->get_id();
    $output = '';
    $colorArr = array(
        'purchase' => '#1d5e00',
        'capture' => '#1d5e00',
        'auth' => '#2271b1',
        'void' => '#a00',
        'refund' => '#a00'
    );

    if($order->get_payment_method() === $payment_method_id) {
        $transactions = new TP_Gateway_Transaction();
        $transactionsArray = $transactions->search_transactions_by_order_id($order_id);
        if($transactionsArray && !empty($transactionsArray)){
            foreach ($transactionsArray as $transaction){
                $output .= '<p>
                                <span class="woocommerce-Price-amount amount" style="color: '.$colorArr[$transaction['type']].';">
                                    <span>'.$transaction['type'].': </span>
                                    <bdi>' .number_format( (float) $transaction['amount'], 2, ',', ''). '</bdi>
                                    <span class="woocommerce-Price-currencySymbol">'.get_woocommerce_currency_symbol($order->get_currency()).'</span>
                                </span>
                            </p>';
            }

            echo $output;
        }
    }
}

add_action( 'woocommerce_admin_order_totals_after_total', 'custom_display_order_extra_info_admin', 10, 1 );
function custom_display_order_extra_info_admin($order_id) {
    $payment_method_id = 'my_custom_gateway';
    $order = wc_get_order($order_id);
    $output = '';
    $colorArr = array(
        'purchase' => '#1d5e00',
        'capture' => '#1d5e00',
        'auth' => '#2271b1',
        'void' => '#a00',
        'refund' => '#a00'
    );

    if($order->get_payment_method() === $payment_method_id) {
        $transactions = new TP_Gateway_Transaction();
        $transactionsArray = $transactions->search_transactions_by_order_id($order_id);
        if($transactionsArray && !empty($transactionsArray)){
            $output .= '<tr>
                            <td style="border-top: 1px solid #999; margin-top:12px; padding-top:12px"></td>
                            <td width="1%" style="border-top: 1px solid #999; margin-top:12px; padding-top:12px"></td>
                            <td style="border-top: 1px solid #999; margin-top:12px; padding-top:12px"></td>
                        </tr>
                        <tr>
                            <td class="label label-highlight">'.__('Транзакції', 'tp_gateway').'</td>
                            <td width="1%"></td>
                            <td></td>
                        </tr>';

            foreach ($transactionsArray as $transaction){
                $output .= '<tr>
                                <td class="label" style="color: '.$colorArr[$transaction['type']].';">'.$transaction['type'].': </td>
                                <td width="1%"></td>
                                <td class="total">
                                    <span class="woocommerce-Price-amount amount" style="color: '.$colorArr[$transaction['type']].';">
                                        <bdi>'.number_format((float)$transaction['amount'],2,',','').'</bdi>
                                        <span class="woocommerce-Price-currencySymbol">'.get_woocommerce_currency_symbol($order->get_currency()).'</span>
                                    </span>
                                </td>
                             </tr>';
            }

            echo $output;
        }
    }
}

function register_partial_payment_status() {
    register_post_status( 'wc-partial-payment', array(
        'label'                     => __('Часткова оплата','tp_gateway'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Partial payment <span class="count">(%s)</span>', 'Partial payment <span class="count">(%s)</span>' )
    ) );
}
add_action('init', 'register_partial_payment_status');


add_filter( 'wc_order_statuses', 'custom_order_status');
function custom_order_status( $order_statuses ) {
    $order_statuses['wc-partial-payment'] = __('Часткова оплата','tp_gateway');

    return $order_statuses;
}

function activate_tp_gateway_plugin() {
    create_table_in_db();
}
register_activation_hook(__FILE__, 'activate_tp_gateway_plugin');

function create_table_in_db() {
    global $wpdb;

    $prefix = $wpdb->prefix;

    $sql = "CREATE TABLE {$prefix}tp_gateway_transactions (
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
?>
