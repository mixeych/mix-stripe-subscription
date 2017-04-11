<?php
/*
Plugin Name: MIX Stripe Subscription Add On
Description: WP Full Stripe Free for managing subscriptions and plans
Author: Mikheev Dmitriy
Version: 1.0
*/
require_once dirname( __FILE__ ) . '/inc/MIXStripeApi.php';
require_once dirname( __FILE__ ) . '/inc/MIXStripeAjax.php';
require_once dirname( __FILE__ ). '/inc/MIXStripePlansManager.php';

class MIXStripePlans
{
    function __construct() {
        new MIXStripeAjax();
        add_action('fullstripe_admin_menus', array($this, 'addSubmenus'), 10, 1);
        register_activation_hook( __FILE__, array($this, 'init') );
    }

    function addSubmenus($slug){

        add_submenu_page( $slug, 'Plans', 'Plans', 'manage_options', 'fullstripe-plans', array( $this, 'subscriptionPage' ) );
        add_submenu_page($slug, 'Orders', 'Orders', 'manage_options', 'fullstripe-orders', array( $this, 'ordersPage' ));

    }

    function subscriptionPage(){
        require_once dirname( __FILE__ ) . '/inc/views/plans.php';
    }

    function ordersPage(){

        require_once dirname( __FILE__ ). '/inc/MIXStripeOrderTable.php';
        $table = new MIXStripeOrderTable();

        require_once dirname( __FILE__ ) . '/inc/views/orders.php';
    }

    public function init()
    {
        $this->createTables();
        $this->createPages();
    }

    private function createPages()
    {
        $slug = 'mix-stripe-hooks';
        $args = array(
            'name'        => $slug,
            'post_type'   => 'page',
            'showposts' => 1
        );
        $hooksPage = get_posts($args);
        if(empty($hooksPage)){
            $page = array(
                'post_type' => 'page',
                'post_name' => $slug,
                'post_title' => 'Stripe Hooks',
                'post_status' => 'publish',
            );

            wp_insert_post($page);
        }
    }

    private function createTables(){
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        global $wpdb;

        $table = $wpdb->prefix . 'mix_stripe_plans';

        $sql = "CREATE TABLE IF NOT EXISTS " . $table . " (
            ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plan_id VARCHAR (255) NOT NULL,
            name VARCHAR (255),
            description TEXT NULL,
            amount INT NOT NULL,
            interv VARCHAR (255) NOT NULL,
            currency VARCHAR (100) NOT NULL
            );";

        $tableOrders = $wpdb->prefix . 'mix_stripe_orders';
        $sqlOrders = "CREATE TABLE IF NOT EXISTS " . $tableOrders . " (
            ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plan_id VARCHAR (255) NOT NULL,
            user_id INT NOT NULL,
            amount INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );";

        dbDelta( $sql );
        dbDelta( $sqlOrders );
    }
}

$MIXStripePlans = new MIXStripePlans();
