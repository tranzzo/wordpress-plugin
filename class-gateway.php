<?php

class My_Custom_Gateway extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $methodTitle;
    /**
     * @var string
     */
    public $methodDescription;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $language;
    /**
     * @var string
     */
    public $paymentTime;
    /**
     * @var string
     */
    public $payment_method;
    /**
     * @var int
     */
    public $typePayment;
    /**
     * @var int
     */
    public $testMode;
    /**
     * @var string
     */
    private $POS_ID;
    /**
     * @var string
     */
    private $API_KEY;
    /**
     * @var string
     */
    private $API_SECRET;
    /**
     * @var string
     */
    private $ENDPOINTS_KEY;
    /**
     * @var string
     */
    public $icon;

    /**
     * My_Custom_Gateway constructor.
     */
    public function __construct()
    {
        $this->id = "my_custom_gateway";
        $this->methodTitle = __("Tranzzo Gateway", "tranzzo");
        $this->methodDescription = __("Приймайте платежі через Tranzzo Gateway", "tranzzo");

        $this->title = $this->get_option("title");
        $this->description = $this->get_option("description");

        $this->language = $this->get_option("language");
        $this->paymentTime = $this->get_option("paymentTime");
        $this->payment_method = $this->get_option("payment_method");
        $this->typePayment = $this->get_option("typePayment") == "yes" ? 1 : 0;
        $this->testMode = $this->get_option("test_mode") == "yes" ? 1 : 0;
        $this->POS_ID = trim($this->get_option("POS_ID"));
        $this->API_KEY = trim($this->get_option("API_KEY"));
        $this->API_SECRET = trim($this->get_option("API_SECRET", "PAY ONLINE"));
        $this->ENDPOINTS_KEY = trim($this->get_option("ENDPOINTS_KEY"));
        $this->icon = apply_filters(
            "woocommerce_tranzzo_icon",
            plugin_dir_url(__FILE__) . "/images/logo.png"
        );

        if (!$this->supportCurrencyTRANZZO()) {
            $this->enabled = "no";
        }

        $this->supports[] = "refunds";

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);

        add_action("woocommerce_api_wc_gateway_" . $this->id, [
            $this,
            "checkTranzzoResponse",
        ]);

        //API Callback function
        add_action('woocommerce_api_'.strtolower(get_class($this)), array(&$this, 'checkTranzzoResponse'));

        add_action("woocommerce_order_status_on-hold_to_processing", [
            $this,
            "capturePayment",
        ]);

        $this->init_form_fields();
        $this->init_settings();

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);
    }

    /**
     *
     */
    public function admin_options()
    {
        if ($this->supportCurrencyTRANZZO()) { ?>
            <h3><?php _e("TRANZZO", "tranzzo"); ?></h3>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
        <?php } else { ?>
            <div class="inline error">
                <p>
                    <strong><?php _e(
                            "Платіжний шлюз вимкнено.",
                            "tranzzo"
                        ); ?></strong>: <?php _e(
                        "TRANZZO не підтримує валюту Вашого магазину!",
                        "tranzzo"
                    ); ?>
                </p>
            </div>
        <?php }
    }

    /**
     *
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            "enabled" => [
                "title" => __("Enable/Disable", "tranzzo"),
                "type" => "checkbox",
                "label" => __("Увімкнути TRANZZO Gateway", "tranzzo"),
                "default" => "yes",
            ],
            "test_mode" => [
                "title" => __("Тестовий режим", "tranzzo"),
                "type" => "checkbox",
                "label" => __("Увімкнути тестовий режим", "tranzzo"),
                "default" => "yes",
            ],
            "title" => [
                "title" => __("Заголовок", "tranzzo"),
                "type" => "text",
                "description" => __(
                    "Заголовок, що відображається на сторінці оформлення замовлення",
                    "tranzzo"
                ),
                "default" => "TRANZZO",
                "desc_tip" => true,
            ],
            "description" => [
                "title" => __("Опис", "tranzzo"),
                "type" => "textarea",
                "description" => __(
                    "Опис, який відображається в процесі вибору форми оплати",
                    "tranzzo"
                ),
                "default" => __(
                    "Сплатити через платіжну систему TRANZZO",
                    "tranzzo"
                ),
            ],
            "typePayment" => [
                "title" => __("Блокування коштів", "tranzzo"),
                "type" => "checkbox",
                "label" => __("Увімкнути", "tranzzo"),
                "default" => "no",
            ],
            "POS_ID" => [
                "title" => "POS_ID",
                "type" => "text",
                "description" => __("POS_ID TRANZZO", "tranzzo"),
            ],
            "API_KEY" => [
                "title" => "API_KEY",
                "type" => "password",
                "description" => __("API_KEY TRANZZO", "tranzzo"),
            ],
            "API_SECRET" => [
                "title" => "API_SECRET",
                "type" => "password",
                "description" => __("API_SECRET TRANZZO", "tranzzo"),
            ],
            "ENDPOINTS_KEY" => [
                "title" => "ENDPOINTS_KEY",
                "type" => "password",
                "description" => __("ENDPOINTS_KEY TRANZZO", "tranzzo"),
            ],
        ];
    }

    /**
     * @return bool
     */
    function supportCurrencyTRANZZO()
    {
        if (!in_array(get_option("woocommerce_currency"), ["USD", "EUR", "UAH", "RUB",])) {
            return false;
        }

        return true;
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        return [
            "result" => "success",
            "redirect" => add_query_arg(
                "order_id",
                $order_id,
                home_url("tranzzo-redirect")
            ),
        ];
    }

    /**
     * @param $order_id
     */
    public function generate_form($order_id)
    {
        self::writeLog("generate_form", "1");
        global $woocommerce;
        $order = new WC_Order($order_id);
        $data_order = $order->get_data();

        if (!empty($data_order)) {
            require_once __DIR__ . "/TranzzoApi.php";

            $tranzzo = new TranzzoApi(
                $this->POS_ID,
                $this->API_KEY,
                $this->API_SECRET,
                $this->ENDPOINTS_KEY
            );
            $tranzzo->setServerUrl(
                add_query_arg("wc-api", __CLASS__, home_url("/"))
            );
            $tranzzo->setResultUrl($this->get_return_url($order));
            $tranzzo->setOrderId($order_id);
            $tranzzo->setAmount($this->testMode ? 1 : $data_order["total"]);
            $tranzzo->setCurrency($this->testMode ? "XTS" : $data_order["currency"]);
            $tranzzo->setDescription("Order #{$order_id}");

            if (!empty($data_order["customer_id"])) {
                $tranzzo->setCustomerId($data_order["customer_id"]);
            } else {
                $tranzzo->setCustomerId($data_order["billing"]["email"]);
            }

            $tranzzo->setCustomerEmail($data_order["billing"]["email"]);
            $tranzzo->setCustomerFirstName(
                $data_order["billing"]["first_name"]
            );
            $tranzzo->setCustomerLastName($data_order["billing"]["last_name"]);
            $tranzzo->setCustomerPhone($data_order["billing"]["phone"]);
            $tranzzo->setProducts();

            if (count($data_order["line_items"]) > 0) {
                $products = [];
                foreach ($data_order["line_items"] as $item) {
                    $product = new WC_Order_Item_Product($item);
                    $products[] = [
                        "id" => strval($product->get_id()),
                        "name" => $product->get_name(),
                        "url" => $product->get_product()->get_permalink(),
                        "currency" => $this->testMode ? "XTS" : $data_order["currency"],
                        "amount" => TranzzoApi::amountToDouble(
                            $product->get_total()
                        ),
                        "qty" => $product->get_quantity(),
                    ];
                }

                $tranzzo->setProducts($products);
            }

            self::writeLog(add_query_arg("wc-api", __CLASS__, home_url("/")), "1");

            $response = $tranzzo->createPaymentHosted($this->typePayment);

            self::writeLog($response, '$response');

            if (!empty($response["redirect_url"])) {
                $woocommerce->cart->empty_cart();
                wp_redirect($response["redirect_url"]);
                exit();
            }

            wp_redirect($order->get_cancel_order_url());
        }

        wp_redirect(home_url("/"));
    }

    /**
     * @throws WC_Data_Exception
     */
    public function checkTranzzoResponse()
    {
        global $wpdb;

        self::writeLog(__METHOD__, "check", "check_response");

        self::writeLog($_POST, '$_POST', "check_response");
        self::writeLog($_GET, '$_GET', "check_response");

        if (version_compare(phpversion(), "7.1", ">=")) {
            ini_set("serialize_precision", -1);
        }

        $data = $_POST["data"];
        $signature = $_POST["signature"];
        if (empty($data) && empty($signature)) {
            die("LOL! Bad Request!!!");
        }

        require_once __DIR__ . "/TranzzoApi.php";

        $tranzzo = new TranzzoApi(
            $this->POS_ID,
            $this->API_KEY,
            $this->API_SECRET,
            $this->ENDPOINTS_KEY
        );
        $data_response = TranzzoApi::parseDataResponse($data);

        $method_response = $data_response[TranzzoApi::P_REQ_METHOD];
        self::writeLog($method_response, '$method_response', "check_response");
        self::writeLog($data_response, '$data_response', "check_response");

        if (
            $method_response == TranzzoApi::P_METHOD_AUTH ||
            $method_response == TranzzoApi::P_METHOD_PURCHASE
        ) {
            $order_id = (int)$data_response[TranzzoApi::P_RES_PROV_ORDER];
            $tranzzo_order_id = (int)$data_response[TranzzoApi::P_RES_ORDER];

            self::writeLog(1, 'get_$order_id', "check_response");
        } else {
            $tranzzo_order_id = (int)$data_response[TranzzoApi::P_RES_ORDER];
            $order_id = $wpdb->get_var(
                "SELECT post_id as count FROM {$wpdb->postmeta} WHERE meta_key = 'tranzzo_order_id' AND meta_value = $tranzzo_order_id"
            );

            self::writeLog(2, 'get_$order_id', "check_response");
        }

        self::writeLog($order_id, '$order_id');
        self::writeLog($tranzzo_order_id, '$tranzzo_order_id');

        if ($tranzzo->validateSignature($data, $signature) && $order_id) {
            $order = wc_get_order($order_id);
            self::writeLog("sign valid", "check_response");

            $amount_payment = TranzzoApi::amountToDouble(
                $data_response[TranzzoApi::P_RES_AMOUNT]
            );

            $woocommerceOrderTotal = $this->testMode ? 1 : $order->get_total();
            $amount_order = TranzzoApi::amountToDouble($woocommerceOrderTotal);

            if (
                $data_response[TranzzoApi::P_RES_RESP_CODE] == 1000 &&
                $amount_payment >= $amount_order
            ) {
                self::writeLog("Pay", "ok", "check_response");
                $order->set_transaction_id(
                    $data_response[TranzzoApi::P_RES_TRSACT_ID]
                );
                $order->payment_complete();
                $order->add_order_note(
                    __("Заказ успішно оплачений через TRANZZO", "tranzzo")
                );
                $order->add_order_note(
                    "ID платежу (payment id): " .
                    $data_response[TranzzoApi::P_RES_PAYMENT_ID]
                );
                $order->add_order_note(
                    "ID транзакції (transaction id): " .
                    $data_response[TranzzoApi::P_RES_TRSACT_ID]
                );
                $order->save();
                update_post_meta(
                    $order_id,
                    "tranzzo_response",
                    json_encode($data_response)
                );
                update_post_meta(
                    $order_id,
                    "tranzzo_order_id",
                    $tranzzo_order_id
                );

                self::writeLog("Pay", "end", "check_response");
                exit();
            } elseif (
                $data_response[TranzzoApi::P_RES_RESP_CODE] == 1002 &&
                $amount_payment >= $amount_order
            ) {
                self::writeLog("pay", "auth", "check_response");
                $order->set_transaction_id(
                    $data_response[TranzzoApi::P_RES_TRSACT_ID]
                );
                $order->update_status("on-hold", __("TRANZZO", "tranzzo"));

                $order->add_order_note(
                    "ID платежу (payment id): " .
                    $data_response[TranzzoApi::P_RES_PAYMENT_ID]
                );
                $order->add_order_note(
                    "ID транзакції (transaction id): " .
                    $data_response[TranzzoApi::P_RES_TRSACT_ID]
                );
                $order->add_order_note(
                    __(
                        'Сума платежу зарезервована через TRANZZO, необхідно змінити статус замовлення на "Обробка" для зарахування коштів',
                        "tranzzo"
                    )
                );
                $order->save();
                update_post_meta(
                    $order_id,
                    "tranzzo_response",
                    json_encode($data_response)
                );
                update_post_meta(
                    $order_id,
                    "tranzzo_order_id",
                    $tranzzo_order_id
                );

                return;
                exit();
            } elseif (
                $method_response == TranzzoApi::U_METHOD_VOID &&
                $data_response[TranzzoApi::P_RES_STATUS] ==
                TranzzoApi::P_TRZ_ST_SUCCESS
            ) {
                self::writeLog("!!!!!!!!! void", "", "check_response");
                $order->update_status("cancelled", __("TRANZZO", "tranzzo"));

                $order->add_order_note(
                    __(
                        "Сума платежу успішно повернута через TRANZZO",
                        "tranzzo"
                    )
                );
                $order->save();
                update_post_meta(
                    $order_id,
                    "tranzzo_response",
                    json_encode($data_response)
                );

                return;
                exit();
            } elseif (
                $method_response == TranzzoApi::U_METHOD_CAPTURE &&
                $data_response[TranzzoApi::P_RES_STATUS] ==
                TranzzoApi::P_TRZ_ST_SUCCESS
            ) {
                self::writeLog("!!!!!!!!! capture", "", "check_response");
                $order->add_order_note(
                    __(
                        "Зарезервована сума платежу зарахована через TRANZZO",
                        "tranzzo"
                    )
                );
                $order->save();
                update_post_meta(
                    $order_id,
                    "tranzzo_response",
                    json_encode($data_response)
                );

                return;
                exit();
            } elseif (
                $method_response == TranzzoApi::U_METHOD_REFUND &&
                $data_response[TranzzoApi::P_RES_STATUS] ==
                TranzzoApi::P_TRZ_ST_SUCCESS
            ) {
                $order->add_order_note(
                    __("Заказ успішно повернуто через TRANZZO", "tranzzo")
                );
                $order->add_order_note(
                    "ID платежу (payment id): " .
                    $data_response[TranzzoApi::P_RES_PAYMENT_ID]
                );

                $order->save();
                update_post_meta(
                    $order_id,
                    "tranzzo_response",
                    json_encode($data_response)
                );

                return;
            } elseif ($order->get_status() == "pending") {
                self::writeLog(
                    "pen",
                    "Заказ в очікуванні оплати",
                    "check_response"
                );

                $order->add_order_note(
                    __("Заказ в очікуванні оплати", "tranzzo")
                );
                $order->save();
            }

            self::writeLog("response not detect", "", "check_response");

            exit();
        }

        self::writeLog("sign NOT valid", "", "check_response");

        exit();
    }

    /**
     * @param int $order_id
     * @param null $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = "")
    {
        self::writeLog("process_refund", "");
        self::writeLog('$order_id', $order_id);
        $order = wc_get_order($order_id);
        self::writeLog(['$order' => (array)$order]);

        if (!$order || !$order->get_transaction_id()) {
            return new WP_Error(
                "tranzzo_refund_error",
                __(
                    "Refund Error: Payment for this order has not been determined.",
                    "tranzzo"
                )
            );
        }
        if (0 == $amount || null == $amount) {
            return new WP_Error(
                "tranzzo_refund_error",
                __(
                    "Refund Error: You need to specify a refund amount.",
                    "tranzzo"
                )
            );
        }

        $old_wc = version_compare(WC_VERSION, "3.0", "<");

        if ($old_wc) {
            $order_currency = get_post_meta($order_id, "_order_currency", true);
        } else {
            $order_currency = $order->get_currency();
        }

        if($this->testMode){
            $order_currency = "XTS";
        }

        require_once __DIR__ . "/TranzzoApi.php";
        $tranzzo_response = get_post_custom_values(
            "tranzzo_response",
            $order_id
        );
        $tranzzo_response = json_decode($tranzzo_response[0], true);

        $order_total = get_post_meta($order_id, "_order_total", true);
        if ($amount < $order_total) {

            return new WP_Error(
                "tranzzo_refund_error",
                __(
                    "Refund Error: You need to specify the total refund amount - " .
                    $order_total .
                    " " .
                    $order_currency .
                    ".",
                    "tranzzo"
                )
            );
        }

        $tranzzo = new TranzzoApi(
            $this->POS_ID,
            $this->API_KEY,
            $this->API_SECRET,
            $this->ENDPOINTS_KEY
        );
        $data = [
            "order_currency" => $order_currency,
            "refund_date" => date("Y-m-d H:i:s"),
            "order_id" => strval($tranzzo_response["order_id"]),
            "order_amount" => strval($tranzzo_response["amount"]),
            "provider_order_id" => strval(
                $tranzzo_response["provider_order_id"]
            ),
            "server_url" => add_query_arg("wc-api", __CLASS__, home_url("/")),
        ];

        self::writeLog(["data" => $data]);

        switch ($tranzzo_response["method"]) {
            case TranzzoApi::P_METHOD_AUTH:
                $response = $tranzzo->createVoid($data);
                break;
            case TranzzoApi::P_METHOD_CAPTURE:
            case TranzzoApi::P_METHOD_PURCHASE:
                $response = $tranzzo->createRefund($data);
                break;
        }

        self::writeLog(["response" => $response]);
        self::writeLog("status", $response["status"]);

        if ($response["status"] != "success") {
            return new WP_Error(
                "tranzzo_refund_error",
                __($response["message"], "tranzzo")
            );
        } else {
            self::writeLog("success", "");
            $refund_message = sprintf(
                __('Refunded %1$s - Reason: %3$s', "tranzzo"),
                $amount,
                $reason
            );
            self::writeLog('$refund_message', $refund_message);

            $order->add_order_note($refund_message);
            $order->save();
            self::writeLog(['$order222' => (array)$order]);

            return true;
        }
    }

    /**
     * @param $order_id
     * @return bool|WP_Error
     */
    public function capturePayment($order_id)
    {
        self::writeLog("capture", $order_id);

        $order = wc_get_order($order_id);
        require_once __DIR__ . "/TranzzoApi.php";
        $tranzzo_response = get_post_custom_values(
            "tranzzo_response",
            $order_id
        );
        $tranzzo_response = json_decode($tranzzo_response[0], true);

        if (
            $this->id === $order->get_payment_method() &&
            TranzzoApi::P_METHOD_AUTH === $tranzzo_response["method"] &&
            $order->get_transaction_id()
        ) {
            $old_wc = version_compare(WC_VERSION, "3.0", "<");
            if ($old_wc) {
                $order_currency = get_post_meta(
                    $order_id,
                    "_order_currency",
                    true
                );
            } else {
                $order_currency = $order->get_currency();
            }

            $tranzzo = new TranzzoApi(
                $this->POS_ID,
                $this->API_KEY,
                $this->API_SECRET,
                $this->ENDPOINTS_KEY
            );
            $data = [
                "order_currency" => $order_currency,
                "refund_date" => date("Y-m-d H:i:s"),
                "order_id" => strval($tranzzo_response["order_id"]),
                "order_amount" => strval($tranzzo_response["amount"]),
                "provider_order_id" => strval(
                    $tranzzo_response["provider_order_id"]
                ),
                "server_url" => add_query_arg(
                    "wc-api",
                    __CLASS__,
                    home_url("/")
                ),
            ];
            $response = $tranzzo->createCapture($data);

            self::writeLog(["response" => $response]);
            self::writeLog("status", $response["status"]);

            if ($response["status"] != "success") {

                return new WP_Error(
                    "tranzzo_refund_error",
                    __($response["message"], "tranzzo")
                );
            } else {
                self::writeLog("success", "");
                self::writeLog(['$order222' => (array)$order]);

                return true;
            }
        }
    }

    /**
     * @param $data
     * @param string $flag
     * @param string $filename
     */
    static function writeLog($data, $flag = "", $filename = "info")
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            file_put_contents(
                __DIR__ . "/class-gateway.php",
                "\n\n" .
                date("Y-m-d H:i:s") .
                " - $flag \n" .
                (is_array($data)
                    ? json_encode($data, JSON_PRETTY_PRINT)
                    : $data),
                FILE_APPEND
            );
        }
    }
}
?>
