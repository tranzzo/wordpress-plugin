<?php

if (!defined('ABSPATH')){
    exit;
}

class My_Custom_Gateway extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    public $id;
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
     * @var string
     */
    public $successStatus;
    /**
     * @var string
     */
    public $custom_pending_status;
    /**
     * @var string
     */
    public $custom_failed_status;
    /**
     * @var string
     */
    public $custom_success_status;
    /**
     * @var string
     */
    public $custom_refunded_status;
    /**
     * @var string
     */
    public $custom_auth_pending_status;
    /**
     * @var string
     */
    public $custom_auth_failed_status;
    /**
     * @var string
     */
    public $custom_success_auth_status;
    /**
     * @var string
     */
    public $custom_auth_part_success_status;
    /**
     * @var string
     */
    public $custom_auth_success_status;
    /**
     * @var string
     */
    public $custom_auth_voided_status;
    /**
     * @var string
     */
    public $custom_auth_refunded_status;
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
        $this->method_title = sprintf(__('%s Gateway', "tp_gateway"), TPG_TITLE);
        $this->method_description = sprintf(__('Приймайте платежі через %s Gateway ', "tp_gateway"), TPG_TITLE);

        $this->title = !empty($this->get_option("title")) ? $this->get_option("title") : TPG_TITLE;
        $this->description = $this->get_option("description");

        $this->language = $this->get_option("language");
        $this->paymentTime = $this->get_option("paymentTime");
        $this->payment_method = $this->get_option("payment_method");
        $this->typePayment = $this->get_option("typePayment") == "yes" ? 1 : 0;
        $this->testMode = $this->get_option("test_mode") == "yes" ? 1 : 0;

        $successStatus = str_replace('wc-','', $this->get_option("custom_success_status"));
        $this->successStatus = $successStatus != "processing" ? $successStatus : null;

        $this->custom_pending_status = str_replace('wc-','',$this->get_option("custom_pending_status"));
        $this->custom_failed_status = str_replace('wc-','',$this->get_option("custom_failed_status"));
        $this->custom_success_status = str_replace('wc-','',$this->get_option("custom_success_status"));
        $this->custom_refunded_status = str_replace('wc-','',$this->get_option("custom_refunded_status"));
        $this->custom_auth_pending_status = str_replace('wc-','',$this->get_option("custom_auth_pending_status"));
        $this->custom_auth_failed_status = str_replace('wc-','',$this->get_option("custom_auth_failed_status"));
        $this->custom_success_auth_status = str_replace('wc-','',$this->get_option("custom_success_auth_status"));
        $this->custom_auth_part_success_status = str_replace('wc-','',$this->get_option("custom_auth_part_success_status"));
        $this->custom_auth_success_status = str_replace('wc-','',$this->get_option("custom_auth_success_status"));
        $this->custom_auth_voided_status = str_replace('wc-','',$this->get_option("custom_auth_voided_status"));
        $this->custom_auth_refunded_status = str_replace('wc-','',$this->get_option("custom_auth_refunded_status"));

        $this->POS_ID = trim($this->get_option("POS_ID"));
        $this->API_KEY = trim($this->get_option("API_KEY"));
        $this->API_SECRET = trim($this->get_option("API_SECRET", "PAY ONLINE"));
        $this->ENDPOINTS_KEY = trim($this->get_option("ENDPOINTS_KEY"));
        /*$this->icon = apply_filters(
            "woocommerce_tp_icon",
            plugin_dir_url(__FILE__) . "images/logo.png"
        );*/

        if (!$this->supportCurrencyAPI()) {
            $this->enabled = "no";
        }

        /**
         * Uncomment the code if you want to enable refunds using Tranzzo from the admin panel
         */
        //$this->supports[] = "refunds";

        add_action("woocommerce_update_options_payment_gateways_" . $this->id, [
            $this,
            "process_admin_options",
        ]);

        add_action("woocommerce_api_wc_gateway_" . $this->id, [
            $this,
            "checkTPResponse",
        ]);

        //API Callback function
        add_action('woocommerce_api_'.strtolower(get_class($this)), array(&$this, 'checkTPResponse'));


        /**
         * Uncomment the code if you want to enable the possibility of crediting retained funds
         * using Tranzzo from the admin panel
         */

        /**
        add_action("woocommerce_order_status_on-hold_to_processing", [
            $this,
            "capturePayment",
        ]);
        */

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
        if ($this->supportCurrencyAPI()) { ?>
            <style>
                table.update-form-table{
                    width: 38%;
                }
                @media only screen and (min-width: 360px) and (max-width: 992px) {
                    table.update-form-table{
                        width: 100%;
                    }
                }
            </style>
            <h3><?=TPG_TITLE;?></h3>
            <a href="<?=get_locale() != 'uk' ? TPG_SUPPORT_URL_EN : TPG_SUPPORT_URL_UK;?>"><?=__("Документація","tp_gateway");?></a>
            <p><a
                   class="button-primary"
                   href="mailto:<?=TPG_SUPPORT_EMAIL;?>"><?=__("Звʼязатися з підтримкою","tp_gateway");?>
                </a>
            </p>
            <table class="form-table update-form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <p style="font-size: 12px;">
                <span style="color: #d63638;">*</span> <?php _e("Поля обов’язкові для заповнення","tp_gateway");?>
            <p>
            <script>
                const isTwoStepPayment = <?php echo $this->typePayment;?>
            </script>
        <?php } else { ?>
            <div class="inline error">
            <p>
                <strong><?php _e(
                        "Платіжний шлюз вимкнено.",
                        "tp_gateway"
                    ); ?></strong>: <?php sprintf(_e(
                    '%s не підтримує валюту Вашого магазину!',
                    "tp_gateway"
                ), TPG_TITLE); ?>
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
                "title" => __("Увімкнено / Вимкнено", "tp_gateway"),
                "type" => "checkbox",
                "label" => sprintf(__('Увімкнути %s Gateway ', "tp_gateway"), TPG_TITLE),
                "default" => "yes",
            ],
            "title" => [
                "title" => __("Заголовок", "tp_gateway"),
                "type" => "text",
                "description" => __(
                    "Заголовок, що відображається на сторінці оформлення замовлення",
                    "tp_gateway"
                ),
                "default" => TPG_TITLE,
                "desc_tip" => true,
                'custom_attributes' => array('readonly' => 'readonly')
            ],
            "description" => [
                "title" => __("Опис", "tp_gateway"),
                "type" => "textarea",
                "description" => __(
                    "Опис, який відображається в процесі вибору форми оплати",
                    "tp_gateway"
                ),
                "default" => sprintf(__(
                    'Сплатити через платіжну систему %s ',
                    "tp_gateway"
                ), TPG_TITLE),
            ],
            "POS_ID" => [
                "title" => "POS_ID".'<span style="color: #d63638;">*</span>',
                "type" => "text",
                "description" => sprintf(__('POS_ID %s ', "tp_gateway"), TPG_TITLE),
                "required"    => true,
            ],
            "API_KEY" => [
                "title" => "API_KEY".'<span style="color: #d63638;">*</span>',
                "type" => "password",
                "description" => sprintf(__('API_KEY %s ', "tp_gateway"), TPG_TITLE),
            ],
            "API_SECRET" => [
                "title" => "API_SECRET".'<span style="color: #d63638;">*</span>',
                "type" => "password",
                "description" => sprintf(__('API_SECRET %s ', "tp_gateway"), TPG_TITLE),
            ],
            "ENDPOINTS_KEY" => [
                "title" => "ENDPOINTS_KEY".'<span style="color: #d63638;">*</span>',
                "type" => "password",
                "description" => sprintf(__('ENDPOINTS_KEY %s ', "tp_gateway"), TPG_TITLE),
            ],
            "test_mode" => [
                "title" => __("Тестовий режим", "tp_gateway"),
                "type" => "checkbox",
                "label" => __("Увімкнути тестовий режим", "tp_gateway"),
                "default" => "yes",
            ],
            /*"typePayment" => [
                "title" => __("Холдування коштів", "tp_gateway"),
                "type" => "checkbox",
                "label" => __("Увімкнути", "tp_gateway"),
                "default" => "no",
            ],*/
            "typePayment" => [
                "title" => __("Платіжний процес", "tp_gateway"),
                "type" => "select",
                "description" => __(
                    "За двостадійною оплатою використовується тимчасове резервування коштів на рахунку клієнта для подальшого проведення платежу",
                    "tp_gateway"
                ),
                'options' => array(
                        'no' => __('Одностадійна оплата', "tp_gateway"),
                        'yes' => __('Двостадійна оплата', "tp_gateway"),
                ),
                "default" => $this->typePayment ? "yes" : "no",
            ],
            "custom_pending_status" => [
                "title" => __("Платіж знаходиться в обробці", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_PENDING_STATUS_EN : CUSTOM_PENDING_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-processing",
            ],
            "custom_failed_status" => [
                "title" => __("Помилка при списанні коштів", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_FAILED_STATUS_EN : CUSTOM_FAILED_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-failed",
            ],
            "custom_success_status" => [
                "title" => __("Успішне списання коштів", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_SUCCESS_STATUS_EN : CUSTOM_SUCCESS_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-completed",
                //"desc_tip" => true,
            ],
            "custom_refunded_status" => [
                "title" => __("Успішне повернення всієї суми платежу", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_REFUNDED_STATUS_EN : CUSTOM_REFUNDED_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-refunded",
            ],
            "custom_auth_pending_status" => [
                "title" => __("Резервування коштів знаходиться в обробці", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_PENDING_STATUS_EN : CUSTOM_AUTH_PENDING_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-processing",
            ],
            "custom_auth_failed_status" => [
                "title" => __("Помилка при резервувані коштів", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_FAILED_STATUS_EN : CUSTOM_AUTH_FAILED_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-failed",
            ],
            "custom_success_auth_status" => [
                "title" => __("Успішне резервування коштів", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_SUCCESS_AUTH_STATUS_EN : CUSTOM_SUCCESS_AUTH_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-on-hold",
            ],
            "custom_auth_part_success_status" => [
                "title" => __("Успішне зарахування частини коштів резерву", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_PART_SUCCESS_STATUS_EN : CUSTOM_AUTH_PART_SUCCESS_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-partial-payment",
            ],
            "custom_auth_success_status" => [
                "title" => __("Успішне зарахування всієї суми резерву", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_SUCCESS_STATUS_EN : CUSTOM_AUTH_SUCCESS_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-completed",
            ],
            "custom_auth_voided_status" => [
                "title" => __("Успішне скасування резерву", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_VOIDED_STATUS_EN : CUSTOM_AUTH_VOIDED_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-cancelled",
            ],
            "custom_auth_refunded_status" => [
                "title" => __("Успішне повернення всієї суми платежу", "tp_gateway"),
                "type" => "select",
                "description" => sprintf(
                    __("Встановіть поточний статус вашого замовлення WooCommerce для відповідної транзакції <a href='%s'>Опис</a>", "tp_gateway"),
                    get_locale() != 'uk' ? CUSTOM_AUTH_REFUNDED_STATUS_EN : CUSTOM_AUTH_REFUNDED_STATUS_UK
                ),
                'options' => wc_get_order_statuses(),
                "default" => "wc-refunded",
            ],
        ];
    }

    public function validate_POS_ID_field($key, $value) {
        if ( empty( $value ) ) {
            WC_Admin_Settings::add_error(
                sprintf(__('Поле %s є обов’язковим для заповнення', "tp_gateway"), $key)
            );
            $value = '';
        }

        return $value;
    }

    public function validate_API_KEY_field($key, $value) {
        if ( empty( $value ) ) {
            WC_Admin_Settings::add_error(
                sprintf(__('Поле %s є обов’язковим для заповнення', "tp_gateway"), $key)
            );
            $value = '';
        }

        return $value;
    }

    public function validate_API_SECRET_field($key, $value) {
        if ( empty( $value ) ) {
            WC_Admin_Settings::add_error(
                sprintf(__('Поле %s є обов’язковим для заповнення', "tp_gateway"), $key)
            );
            $value = '';
        }

        return $value;
    }

    public function validate_ENDPOINTS_KEY_field($key, $value) {
        if ( empty( $value ) ) {
            WC_Admin_Settings::add_error(
                sprintf(__('Поле %s є обов’язковим для заповнення', "tp_gateway"), $key)
            );
            $value = '';
        }

        return $value;
    }

    /**
     * @return bool
     */
    function supportCurrencyAPI()
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
        $order = wc_get_order($order_id);

        $redirect = $this->generate_form($order);

        if(is_array($redirect)){
            wc_add_notice($redirect['message'] , 'error');

            if(is_array($redirect['args'])){
                foreach ($redirect['args'] as $key => $arg){
                    if(is_array($arg)){
                        wc_add_notice(
                            $key.': '.http_build_query($arg,'',', '),
                            'error'
                        );
                    }else{
                        wc_add_notice($key.': '.$redirect['message'] , 'error');
                    }
                }
            }

            return array(
                'result'   => 'failure',
                'messages' => $redirect['message']
            );
        }

        return [
            "result" => "success",
            "redirect" => $redirect
        ];
    }


    /**
     * @param $order
     * @return array|mixed
     */
    public function generate_form($order)
    {
        self::writeLog("generate_form", "1");
        global $woocommerce;

        $order_id = $order->get_id();
        $data_order = $order->get_data();

        if (!empty($data_order)) {
            require_once __DIR__ . "/ApiService.php";

            $apiService = new ApiService(
                $this->POS_ID,
                $this->API_KEY,
                $this->API_SECRET,
                $this->ENDPOINTS_KEY
            );
            $apiService->setServerUrl(
                add_query_arg("wc-api", __CLASS__, home_url("/"))
            );
            $apiService->setResultUrl($this->get_return_url($order));
            $apiService->setOrderId($order_id);
            //$apiService->setAmount($this->testMode ? 2 : $data_order["total"]);
            $apiService->setAmount($data_order["total"]);
            $apiService->setCurrency($this->testMode ? "XTS" : $data_order["currency"]);
            $apiService->setDescription("Order #{$order_id}");

            if (!empty($data_order["customer_id"])) {
                $apiService->setCustomerId($data_order["customer_id"]);
            } else {
                $apiService->setCustomerId($data_order["billing"]["email"]);
            }

            $apiService->setCustomerEmail($data_order["billing"]["email"]);
            $apiService->setCustomerFirstName(
                $data_order["billing"]["first_name"]
            );
            $apiService->setCustomerLastName($data_order["billing"]["last_name"]);
            $apiService->setCustomerPhone($data_order["billing"]["phone"]);
            $apiService->setProducts();

            if (count($data_order["line_items"]) > 0) {
                $products = [];
                foreach ($data_order["line_items"] as $item) {
                    $product = new WC_Order_Item_Product($item);
                    $products[] = [
                        "id" => strval($product->get_id()),
                        "name" => $product->get_name(),
                        "url" => $product->get_product()->get_permalink(),
                        "currency" => $this->testMode ? "XTS" : $data_order["currency"],
                        "amount" => ApiService::amountToDouble(
                            $product->get_total()
                        ),
                        "qty" => $product->get_quantity(),
                    ];
                }

                $apiService->setProducts($products);
            }

            self::writeLog(add_query_arg("wc-api", __CLASS__, home_url("/")), "1");

            $response = $apiService->createPaymentHosted($this->typePayment);

            self::writeLog($response, '$response');

            if(isset($response['args']['code']) && $response['args']['code'] == 'P-409'){
                $redirectUrl = get_post_meta($order_id, 'redirect_url', true);

                $response['currency'] = $this->testMode ? "XTS" : $data_order["currency"];
                $response['method'] = $this->typePayment == 1 ? 'auth' : 'purchase';

                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($response)
                );

                $woocommerce->cart->empty_cart();
                WC()->session->destroy_session();

                $order->update_status('pending', TPG_TITLE);
                $order->add_order_note(
                    __("Заказ в очікуванні оплати", "tp_gateway")
                );

                return $redirectUrl;
                exit();
            }

            if (isset($response["redirect_url"]) && !empty($response["redirect_url"])) {
                $woocommerce->cart->empty_cart();
                update_post_meta($order_id, 'redirect_url', $response["redirect_url"]);

                $response['currency'] = $this->testMode ? "XTS" : $data_order["currency"];
                $response['method'] = $this->typePayment == 1 ? 'auth' : 'purchase';

                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($response)
                );

                $order->update_status('pending');
                $order->add_order_note(
                    __("Заказ в очікуванні оплати", "tp_gateway")
                );

                return $response["redirect_url"];
                exit();
            }

            if(isset($response['message'])) {

                return array(
                    'message' => $response['message'],
                    'args' => $response['args']
                );
                exit();
            }
        }

        return home_url("/");
    }

    /**
     * @throws WC_Data_Exception
     */
    public function checkTPResponse()
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

        require_once __DIR__ . "/ApiService.php";

        $apiService = new ApiService(
            $this->POS_ID,
            $this->API_KEY,
            $this->API_SECRET,
            $this->ENDPOINTS_KEY
        );
        $data_response = ApiService::parseDataResponse($data);

        $method_response = $data_response[ApiService::P_REQ_METHOD];
        self::writeLog($method_response, '$method_response', "check_response");
        self::writeLog($data_response, '$data_response', "check_response");

        if (
            $method_response == ApiService::P_METHOD_AUTH ||
            $method_response == ApiService::P_METHOD_PURCHASE
        ) {
            $order_id = (int)$data_response[ApiService::P_RES_PROV_ORDER];
            $TPOrderId = (int)$data_response[ApiService::P_RES_ORDER];

            self::writeLog(1, 'get_$order_id', "check_response");
        } else {
            $TPOrderId = (int)$data_response[ApiService::P_RES_ORDER];
            $order_id = $wpdb->get_var(
                "SELECT post_id as count FROM {$wpdb->postmeta} WHERE meta_key = '_TPOrderId' AND meta_value = $TPOrderId"
            );

            self::writeLog(2, 'get_$order_id', "check_response");
        }

        self::writeLog($order_id, '$order_id');
        self::writeLog($TPOrderId, '$TPOrderId');

        if ($apiService->validateSignature($data, $signature) && $order_id) {
            $transaction = new TP_Gateway_Transaction();

            $order = wc_get_order($order_id);
            self::writeLog("sign valid", "check_response");

            $amount_payment = ApiService::amountToDouble(
                $data_response[ApiService::P_RES_AMOUNT]
            );

            //$woocommerceOrderTotal = $this->testMode ? 2 : $order->get_total();
            $woocommerceOrderTotal = $order->get_total();
            $amount_order = ApiService::amountToDouble($woocommerceOrderTotal);

            if (
                $data_response[ApiService::P_RES_RESP_CODE] == 1000 &&
                $amount_payment >= $amount_order
            ) {
                self::writeLog("Pay", "ok", "check_response");

                update_post_meta(
                    $order_id,
                    "_tp_order_is_payment",
                    1
                );

                $order->set_transaction_id(
                    $data_response[ApiService::P_RES_TRSACT_ID]
                );
                $order->payment_complete();

                //if set custom status
                if($this->successStatus){
                    $order->update_status($this->successStatus);
                }

                $order->add_order_note(
                    sprintf(__('Заказ успішно оплачений через %s ', "tp_gateway"), TPG_TITLE)
                );
                $order->add_order_note(
                    __("ID платежу (payment id): ") .
                    $data_response[ApiService::P_RES_PAYMENT_ID]
                );
                $order->add_order_note(
                    __("ID транзакції (transaction id): ") .
                    $data_response[ApiService::P_RES_TRSACT_ID]
                );
                $order->add_order_note(__('Повернення коштів можна провести у мерчант-порталі', "tp_gateway"));

                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($data_response)
                );
                update_post_meta(
                    $order_id,
                    "_TPOrderId",
                    $TPOrderId
                );

                $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                self::writeLog("Pay", "end", "check_response");
                exit();
            } elseif (
                $data_response[ApiService::P_RES_RESP_CODE] == 1002 &&
                $amount_payment >= $amount_order
            ) {
                self::writeLog("pay", "auth", "check_response");
                $order->set_transaction_id(
                    $data_response[ApiService::P_RES_TRSACT_ID]
                );
                $order->update_status($this->custom_success_auth_status, TPG_TITLE);

                $order->add_order_note(
                    __("ID платежу (payment id): ") .
                    $data_response[ApiService::P_RES_PAYMENT_ID]
                );
                $order->add_order_note(
                    __("ID транзакції (transaction id): ") .
                    $data_response[ApiService::P_RES_TRSACT_ID]
                );

                /**Uncomment the code if the ability to deposit funds through the admin panel is enabled*/

                /**
                $order->add_order_note(
                    sprintf(__('Сума платежу зарезервована через %s, необхідно змінити статус замовлення на "Обробка" для зарахування коштів', "tp_gateway"), TPG_TITLE)
                );
                 */

                $order->add_order_note(__('Зарахування резерву можна провести у мерчант-порталі', "tp_gateway"));
                $order->add_order_note(__('Скасування резерву можна провести у мерчант-порталі', "tp_gateway"));

                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($data_response)
                );
                update_post_meta(
                    $order_id,
                    "_TPOrderId",
                    $TPOrderId
                );

                $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                return;
                exit();
            } elseif (
                $method_response == ApiService::U_METHOD_VOID &&
                $data_response[ApiService::P_RES_STATUS] ==
                ApiService::P_TRZ_ST_SUCCESS
            ) {
                self::writeLog("!!!!!!!!! void", "", "check_response");
                $order->update_status($this->custom_auth_voided_status, TPG_TITLE);

                $order->add_order_note(
                    sprintf(__(
                        'Сума платежу успішно повернута через %s ',
                        "tp_gateway"
                    ), TPG_TITLE)
                );
                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($data_response)
                );

                $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                return;
                exit();
            } elseif (
                $method_response == ApiService::U_METHOD_CAPTURE &&
                $data_response[ApiService::P_RES_STATUS] ==
                ApiService::P_TRZ_ST_SUCCESS
            ) {
                self::writeLog("!!!!!!!!! capture", "", "check_response");

                $order->add_order_note(
                    sprintf(__(
                        'Зарезервована сума платежу зарахована через %s ',
                        "tp_gateway"
                    ), TPG_TITLE)
                );
                $order->add_order_note(__('Повернення коштів можна провести у мерчант-порталі', "tp_gateway"));

                if($data_response['amount'] > 0 && $data_response['amount'] < $order->get_total()){
                    $order->update_status(
                            $this->custom_auth_part_success_status,
                            __('Часткову оплату отримано', 'tp_gateway')
                    );
                    $order->save();

                    $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                    update_post_meta(
                        $order_id,
                        "_tp_response",
                        json_encode($data_response)
                    );

                    return;
                    exit();
                }

                $order->update_status($this->custom_auth_success_status, TPG_TITLE);
                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($data_response)
                );

                $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                return;
                exit();
            } elseif (
                $method_response == ApiService::U_METHOD_REFUND &&
                $data_response[ApiService::P_RES_STATUS] ==
                ApiService::P_TRZ_ST_SUCCESS
            ) {
                $order->add_order_note(
                    sprintf(__('Замовлення успішно повернуто через %s ', "tp_gateway"), TPG_TITLE)
                );
                $order->add_order_note(
                    __("ID платежу (payment id): ") .
                    $data_response[ApiService::P_RES_PAYMENT_ID]
                );

                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($data_response)
                );

                $refundAmount = $data_response['amount'];

                /*if($this->testMode){
                    $refundAmount = $data_response['amount'] == 1 ?  ($order->get_total() / 2) : $order->get_total();
                }*/

                $refund = wc_create_refund( array(
                    'amount'         => $refundAmount,
                    'reason'         => '',
                    'order_id'       => $order_id,
                    'line_items'     => array(),
                    'refund_payment' => false
                ));

                $transaction->create_transaction($data_response["method"], $data_response['amount'], $order_id);

                if($this->isFullRefunded($order_id,$order->get_total())){
                    $order->update_status($this->custom_refunded_status, TPG_TITLE);
                    $order->save();
                }

                return;
            }elseif (
                $order->get_status() == "pending" &&
                $data_response[ApiService::P_RES_STATUS] == ApiService::P_TRZ_ST_FAILURE){

                $order->update_status($this->custom_failed_status, TPG_TITLE);
                $order->save();
                self::writeLog("failed payment", "", "check_response");

                return;
                exit();
            } elseif (/*$order->get_status() == "pending"*/$data_response[ApiService::P_RES_STATUS] == ApiService::P_TRZ_ST_PENDING) {

                if($this->typePayment == 1) {
                    $order->update_status($this->custom_auth_pending_status, TPG_TITLE);
                }else{
                    $order->update_status($this->custom_pending_status, TPG_TITLE);
                }
                self::writeLog(
                    "pen",
                    "Заказ в очікуванні оплати",
                    "check_response"
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
        $holdTotal = '';
        $transaction = new TP_Gateway_Transaction();

        if (!$order || !$order->get_transaction_id()) {
            return new WP_Error(
                "tp_refund_error",
                __(
                    "Помилка при поверненні коштів: платіж за цим замовленням не знайдено.",
                    "tp_gateway"
                )
            );
        }
        if (0 == $amount || null == $amount) {
            return new WP_Error(
                "tp_refund_error",
                __(
                    "Помилка при поверненні коштів: потрібно вказати суму повернення.",
                    "tp_gateway"
                )
            );
        }

        $old_wc = version_compare(WC_VERSION, "3.0", "<");

        if ($old_wc) {
            $order_currency = get_post_meta($order_id, "_order_currency", true);
        } else {
            $order_currency = $order->get_currency();
        }

        require_once __DIR__ . "/ApiService.php";
        $tp_response = get_post_custom_values(
            "_tp_response",
            $order_id
        );
        $tp_response = json_decode($tp_response[0], true);

        $order_total = get_post_meta($order_id, "_order_total", true);

        if ($amount > $order_total) {

            return new WP_Error(
                "tp_refund_error",
                __(
                    "Помилка при поверненні коштів: потрібно вказати загальну суму повернення -" .
                    $order_total .
                    " " .
                    $order_currency .
                    ".",
                    "tp_gateway"
                )
            );
        }

        if($tp_response["method"] == ApiService::P_METHOD_AUTH){
            $totalHold = $transaction->get_total_hold_amount($order_id);
            if($amount < $totalHold) {
                return new WP_Error(
                    "tp_refund_error",
                    __(
                        "Помилка при поверненні коштів: потрібно вказати загальну суму повернення -" .
                        number_format( (float) $totalHold, 2, ',', '') .
                        " " .
                        $order_currency .
                        ".",
                        "tp_gateway"
                    )
                );
            }
        }

        if($tp_response["method"] == ApiService::P_METHOD_CAPTURE){
            $captured = $transaction->get_total_captured_amount($order_id);
            if($amount > $captured) {
                return new WP_Error(
                    "tp_refund_error",
                    __(
                        "Помилка при поверненні коштів: потрібно вказати загальну суму повернення -" .
                        number_format( (float) $captured, 2, ',', '') .
                        " " .
                        $order_currency .
                        ".",
                        "tp_gateway"
                    )
                );
            }
        }

        if($this->testMode){
            $order_currency = "XTS";
            //$amount = (int)$amount < (int)$order_total ? 1 : 2;
        }

        $apiService = new ApiService(
            $this->POS_ID,
            $this->API_KEY,
            $this->API_SECRET,
            $this->ENDPOINTS_KEY
        );
        $data = [
            "order_currency" => $order_currency,
            "refund_date" => date("Y-m-d H:i:s"),
            "order_id" => strval($tp_response["order_id"]),
            "refund_amount" => strval($amount),
            'comment' => $reason
        ];

        self::writeLog(["data" => $data]);

        switch ($tp_response["method"]) {
            case ApiService::P_METHOD_AUTH:
                $response = $apiService->createVoid($data);
                break;
            case ApiService::P_METHOD_CAPTURE:
            case ApiService::P_METHOD_PURCHASE:
            case ApiService::U_METHOD_REFUND:
                $response = $apiService->createRefund($data);
                break;
        }

        self::writeLog(["response" => $response]);

        if(isset($response["status"])) {
            self::writeLog("status", $response["status"]);
        }

        if (!isset($response["status"]) || $response["status"] != "success") {

            if(isset($response["status"]) && $response["status"] == 'failure'){
                return new WP_Error(
                    "tp_refund_error",
                    __($response["status_description"], "tp_gateway")
                );
            }

            return new WP_Error(
                "tp_refund_error",
                __($response["message"], "tp_gateway")
            );
        } else {
            self::writeLog("success", "");
            $refund_message = sprintf(
                __('Повернено %1$s - Причина: %2$s', "tp_gateway"),
                $amount,
                $reason
            );
            $order->add_order_note($refund_message);
            self::writeLog('$refund_message', $refund_message);

            $transaction->create_transaction($response["method"], $response['amount'], $order_id);

            //$isCaptureTransaction = $tp_response["method"] == ApiService::P_METHOD_CAPTURE;

            /*if($this->isFullRefunded($order_id, $order->get_total(), $isCaptureTransaction)){
                $order->update_status("partial-refunded", TPG_TITLE);
            }*/

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
        if(get_post_meta($order_id, '_tp_order_is_payment', true ) == 1){
            return true;
        }

        self::writeLog("capture", $order_id);

        $order = wc_get_order($order_id);
        require_once __DIR__ . "/ApiService.php";
        $tp_response = get_post_custom_values(
            "_tp_response",
            $order_id
        );
        $tp_response = json_decode($tp_response[0], true);

        if (
            $this->id === $order->get_payment_method() &&
            ApiService::P_METHOD_AUTH === $tp_response["method"] &&
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

            $apiService = new ApiService(
                $this->POS_ID,
                $this->API_KEY,
                $this->API_SECRET,
                $this->ENDPOINTS_KEY
            );
            $data = [
                "order_currency" => $this->testMode ? "XTS" : $order_currency,
                "refund_date" => date("Y-m-d H:i:s"),
                "order_id" => strval($tp_response["order_id"]),
                "order_amount" => strval($tp_response["amount"]),
            ];
            $response = $apiService->createCapture($data);

            self::writeLog(["response" => $response]);
            self::writeLog("status", $response["status"]);

            if ($response["status"] != "success") {

                return new WP_Error(
                    "tp_refund_error",
                    __($response["message"], "tp_gateway")
                );
            } else {
                self::writeLog("success", "");
                self::writeLog(['$order222' => (array)$order]);

                $order->add_order_note(
                    sprintf(__(
                        'Зарезервована сума платежу зарахована через %s ',
                        "tp_gateway"
                    ), TPG_TITLE)
                );

                $order->update_status($this->custom_auth_success_status, TPG_TITLE);

                $order->save();
                update_post_meta(
                    $order_id,
                    "_tp_response",
                    json_encode($response)
                );

                $transaction = new TP_Gateway_Transaction();
                $transaction->create_transaction($response["method"], $response['amount'], $order_id);

                return true;
            }
        }
    }

    /**
     * @param $order_id
     * @param $order_total
     * @return bool
     */
    public function isFullCaptured($order_id, $order_total)
    {
        $transactions = new TP_Gateway_Transaction();

        $captured = floatval($transactions->get_total_captured_amount($order_id));
        if($captured == $order_total){
            return true;
        }

        return false;
    }

    /**
     * @param $order_id
     * @param $order_total
     * @return bool
     */
    public function isFullVoided($order_id, $order_total)
    {
        $transactions = new TP_Gateway_Transaction();

        $voided = floatval($transactions->get_total_voided_amount($order_id));
        if($voided == $order_total){
            return true;
        }

        return false;
    }

    /**
     * @param $order_id
     * @param $order_total
     * @param bool $isCapture
     * @return bool
     */
    public function isFullRefunded($order_id, $order_total, $isCapture = false)
    {
        $transactions = new TP_Gateway_Transaction();
        $refunded = floatval($transactions->get_total_refunded_amount($order_id));
        $captured = floatval($transactions->get_total_captured_amount($order_id));
        $order_total = $isCapture ? $captured : $order_total;

        if($refunded >= $order_total){
            return true;
        }

        return false;
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
