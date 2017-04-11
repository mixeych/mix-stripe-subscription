<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MIXStripeOrderTable extends WP_List_Table
{
    public function __construct() {
        parent::__construct( [
            'singular' => 'Order',
            'plural'   => 'Orders',
            'ajax'     => false
        ] );

    }

    public function no_items() {
        echo  'No orders.';
    }

    function column_name( $item ) {

        $title = '<strong>' . $item['name'] . '</strong>';

        return $title;
    }

    public function column_default( $item, $column_name ) {
        switch($column_name){
            case 'amount': return $item[ $column_name ]/100;
                break;

            default: return $item[ $column_name ];
                break;
        }
    }

    public function get_columns() {
        $columns = [
            'ID'      => 'Order id',
            'name'    => 'Plan',
            'user_email' => 'User',
            'amount'    => 'Amount',
            'created_at'    => 'Date'
        ];

        return $columns;
    }

    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page     = 15;
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );


        $this->items = $this->getOrders( $per_page, $current_page );
    }

    private function getOrders( $per_page = 15, $page_number = 1 ) {

        global $wpdb;

        $sql = "SELECT p.name, p.interv, p.currency, u.user_email, o.amount, o.created_at, o.ID FROM {$wpdb->prefix}mix_stripe_orders as o
                JOIN {$wpdb->prefix}mix_stripe_plans as p ON p.plan_id = o.plan_id
                JOIN {$wpdb->users} as u ON o.user_id = u.ID
                ORDER BY o.created_at DESC";

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        return $result;
    }

    private function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}mix_stripe_orders";

        return $wpdb->get_var( $sql );
    }
}