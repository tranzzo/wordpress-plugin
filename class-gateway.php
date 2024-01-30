<?php

class My_Custom_Gateway extends WC_Payment_Gateway
{
    public $id;
    public $method_title;
    public $method_description;
    public $title;
    public $description;
    public $language;
    public $paymenttime;
    public $payment_method;
    public $type_payment;
    public $testMode;
    private $POS_ID;
    private $API_KEY;
    private $API_SECRET;
    private $ENDPOINTS_KEY;
    public $icon;

    public function __construct()
    {
        $this->id = "my_custom_gateway";
        $this->method_title = __("Tranzzo Gateway", "tranzzo");
        $this->method_description = __("Приймайте платежі через Tranzzo Gateway", "tranzzo");

        $this->title = $this->get_option("title");
        $this->description = $this->get_option("description");

        $this->language = $this->get_option("language");
        $this->paymenttime = $this->get_option("paymenttime");
        $this->payment_method = $this->get_option("payment_method");
        $this->type_payment = $this->get_option("type_payment") == "yes" ? 1 : 0;
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
            "check_tranzzo_response",
        ]);

        add_action("woocommerce_order_status_on-hold_to_processing", [
            $this,
            "capture_payment",
        ]);

        $this->init_form_fields();
        $this->init_settings();
    }

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
            "type_payment" => [
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

    function supportCurrencyTRANZZO()
    {
        if (!in_array(get_option("woocommerce_currency"), ["USD", "EUR", "UAH", "RUB",])) {
            return false;
        }

        return true;
    }

    // Process the payment
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
            $tranzzo->setCurrency(
                $this->testMode ? "XTS" : $data_order["currency"]
            );
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
                        "currency" => $this->testMode
                            ? "XTS"
                            : $data_order["currency"],
                        "amount" => TranzzoApi::amountToDouble(
                            $product->get_total()
                        ),
                        "qty" => $product->get_quantity(),
                    ];
                }

                $tranzzo->setProducts($products);
            }

            $response = $tranzzo->createPaymentHosted($this->type_payment);

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

    public function check_tranzzo_response()
    {
        global $wpdb;
        exit();
    }

    public function process_refund($order_id, $amount = null, $reason = "")
    {
        return true;
    }

    public function capture_payment($order_id)
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

    static function writeLog($data, $flag = "", $filename = "info")
    {
        $show = false;
        if ($show) {
            file_put_contents(
                __DIR__ . "/{$filename}.log",
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
