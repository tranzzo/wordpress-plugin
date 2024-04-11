<?php

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
            jQuery(document).ready(function ($) {
                const requiredFields = [
                    $('#woocommerce_my_custom_gateway_POS_ID'),
                    $('#woocommerce_my_custom_gateway_API_KEY'),
                    $('#woocommerce_my_custom_gateway_API_SECRET'),
                    $('#woocommerce_my_custom_gateway_ENDPOINTS_KEY'),
                ];
                let formElement = $('.update-form-table').parent('form');
                let saveButton = formElement.find('.woocommerce-save-button');

                let selectPaymentProcess = $('#woocommerce_my_custom_gateway_typePayment');

                if(selectPaymentProcess.length){
                    let oneStepStatusesFields = [
                        $("#mainform > table > tbody > tr:nth-child(10)"),
                        $("#mainform > table > tbody > tr:nth-child(11)"),
                        $("#mainform > table > tbody > tr:nth-child(12)"),
                        $("#mainform > table > tbody > tr:nth-child(13)")
                    ];

                    let twoStepStatusesFields = [
                        $("#mainform > table > tbody > tr:nth-child(14)"),
                        $("#mainform > table > tbody > tr:nth-child(15)"),
                        $("#mainform > table > tbody > tr:nth-child(16)"),
                        $("#mainform > table > tbody > tr:nth-child(17)"),
                        $("#mainform > table > tbody > tr:nth-child(18)"),
                        $("#mainform > table > tbody > tr:nth-child(19)"),
                        $("#mainform > table > tbody > tr:nth-child(20)"),
                    ]

                    if(isTwoStepPayment == 1){
                        selectPaymentProcess.val('yes');
                        oneStepStatusesFields.forEach(function(oneStepStatusesField){
                            if(oneStepStatusesField.length) {
                                oneStepStatusesField.hide();
                            }
                        });
                        twoStepStatusesFields.forEach(function(twoStepStatusesField){
                            if(twoStepStatusesField.length) {
                                twoStepStatusesField.show();
                            }
                        });
                    }else{
                        selectPaymentProcess.val('no');
                        oneStepStatusesFields.forEach(function(oneStepStatusesField){
                            if(oneStepStatusesField.length) {
                                oneStepStatusesField.show();
                            }
                        });
                        twoStepStatusesFields.forEach(function(twoStepStatusesField){
                            if(twoStepStatusesField.length) {
                                twoStepStatusesField.hide();
                            }
                        });
                    }

                    selectPaymentProcess.trigger('change');

                    selectPaymentProcess.on('change', function (e) {
                        let $this = $(this);
                        let value = $this.val();

                        if(value == 'no'){
                            oneStepStatusesFields.forEach(function(oneStepStatusesField){
                                if(oneStepStatusesField.length) {
                                    oneStepStatusesField.show();
                                }
                            });
                            twoStepStatusesFields.forEach(function(twoStepStatusesField){
                                if(twoStepStatusesField.length) {
                                    twoStepStatusesField.hide();
                                }
                            });
                        }else{
                            oneStepStatusesFields.forEach(function(oneStepStatusesField){
                                if(oneStepStatusesField.length) {
                                    oneStepStatusesField.hide();
                                }
                            });
                            twoStepStatusesFields.forEach(function(twoStepStatusesField){
                                if(twoStepStatusesField.length) {
                                    twoStepStatusesField.show();
                                }
                            });
                        }
                    });
                }

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
                                        -
                                        <span style="color: #000;font-size: 10px;">'.$transaction['date'].'</span>
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

    if($column == 'order_number'){
        $tp_response = get_post_custom_values(
            "_tp_response",
            $order_id
        );
        if($tp_response) {
            $tp_response = json_decode($tp_response[0], true);
            $currency = isset($tp_response['currency']) ? $tp_response['currency'] : null;
            if($currency == 'XTS'){
                $style = "display: block;
                            width: fit-content;
                            padding: 2px 10px;
                            line-height: 1;
                            border: 1px solid #5db92d;
                            background: #5db92d;
                            border-radius: 6px;
                            color: #fff;";

                echo '<strong style="'.$style.'">Test</strong>';
            }
        }
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
        'label_count'               => _n_noop('Partial payment <span class="count">(%s)</span>', 'Partial payment <span class="count">(%s)</span>')
    ) );
}
add_action('init', 'register_partial_payment_status');


add_filter( 'wc_order_statuses', 'custom_order_status');
function custom_order_status( $order_statuses ) {
    $order_statuses['wc-partial-payment'] = __('Часткова оплата','tp_gateway');

    return $order_statuses;
}

add_action( 'woocommerce_admin_order_data_after_payment_info', 'tp_gateway_woocommerce_admin_order_data_after_payment_info_action' );

/**
 * Function for `woocommerce_admin_order_data_after_payment_info` action-hook.
 *
 * @param $order $order WC_Order The order object being displayed.
 *
 * @return void
 */
function tp_gateway_woocommerce_admin_order_data_after_payment_info_action($order){
    require_once __DIR__ . "/ApiService.php";

    $order_id = $order->get_id();

    $tp_response = get_post_custom_values(
        "_tp_response",
        $order_id
    );
    if($tp_response) {
        $tp_response = json_decode($tp_response[0], true);

        $isRefundTransaction = $tp_response["method"] == ApiService::U_METHOD_REFUND;

        if ($order->get_status() != "refunded" && $isRefundTransaction) {
            $transactions = new TP_Gateway_Transaction();
            $refunded = floatval($transactions->get_total_refunded_amount($order_id));
            $captured = floatval($transactions->get_total_captured_amount($order_id));
            $purchased = floatval($transactions->get_total_purchased_amount($order_id));

            if ($refunded <= $captured || $refunded < $purchased) {
                echo '<div class="notice notice-info">
                <p>' . __("Необхідно змінити статус замовлення на 'Повернено'", "tp_gateway") . '</p>
              </div>';
            }
        }

        $currency = isset($tp_response['currency']) ? $tp_response['currency'] : null;

        if($currency == 'XTS'){
            $style = "display: block;
                            width: fit-content;
                            padding: 2px 10px;
                            line-height: 1;
                            border: 1px solid #5db92d;
                            background: #5db92d;
                            border-radius: 6px;
                            color: #fff;";

            echo '<strong style="'.$style.'">Test</strong>';
        }
    }
}
