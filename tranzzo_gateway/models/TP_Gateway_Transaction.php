<?php


class TP_Gateway_Transaction {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tp_gateway_transactions';
    }

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

    public function delete_transaction($id) {
        global $wpdb;
        $wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }

    public function get_transaction($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id),
            ARRAY_A
        );
    }

    public function search_transactions_by_order_id($order_id) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE order_id = %d ORDER BY date DESC", $order_id),
            ARRAY_A
        );
    }

    public function get_total_captured_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'capture'", $order_id)
        );
        return $result ? $result : 0;
    }

    public function get_total_refunded_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'refund'", $order_id)
        );
        return $result ? $result : 0;
    }

    public function get_total_voided_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'void'", $order_id)
        );
        return $result ? $result : 0;
    }

    public function get_total_purchased_amount($order_id) {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare("SELECT SUM(amount) FROM $this->table_name WHERE order_id = %d AND type = 'purchase'", $order_id)
        );
        return $result ? $result : 0;
    }
}
