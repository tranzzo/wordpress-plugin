<?php


/**
 * Class TP_Gateway_Transaction
 */
class TP_Gateway_Transaction {
    private $table_name;

    /**
     * TP_Gateway_Transaction constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tp_gateway_transactions';
    }

    /**
     * @param $type
     * @param $amount
     * @param $order_id
     * @return mixed
     */
    public function create_transaction($type, $amount, $order_id) {
        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            array(
                'type' => $type,
                'amount' => $amount,
                'order_id' => $order_id
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * @param $id
     * @param $type
     * @param $amount
     * @param $order_id
     */
    public function update_transaction($id, $type, $amount, $order_id) {
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'type' => $type,
                'amount' => $amount,
                'order_id' => $order_id
            ),
            array('id' => $id)
        );
    }

    /**
     * @param $id
     */
    public function delete_transaction($id) {
        global $wpdb;
        $wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_transaction($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function search_transactions_by_order_id($order_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE order_id = %d ORDER BY date DESC", $order_id),
            ARRAY_A
        );
    }


    /**
     * @param $order_id
     * @return float|int
     */
    public function get_total_captured_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'capture'", $order_id)
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * @param $order_id
     * @return float|int
     */
    public function get_total_refunded_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'refund'", $order_id)
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * @param $order_id
     * @return float|int
     */
    public function get_total_voided_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'void'", $order_id)
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * @param $order_id
     * @return float|int
     */
    public function get_total_purchased_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'purchase'", $order_id)
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * @param $order_id
     * @return float|int
     */
    public function get_total_hold_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'auth'", $order_id)
        );

        return $result ? floatval($result) : 0;
    }
}
