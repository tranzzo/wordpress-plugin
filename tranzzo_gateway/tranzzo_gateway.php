<?php
/*
Plugin Name: TRANZZO Gateway
Description: Платіжний шлюз "TRANZZO" для сайтів WordPress.
Version: 2.0.1
Last Update: 30.01.2024
Author: TRANZZO
Author URI: https://tranzzo.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include(plugin_dir_path(__FILE__) . 'config.php');

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
                                    '<?php _e('Полe обов’язкове для заповнення', "tranzzo_gateway"); ?>'+
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
?>
