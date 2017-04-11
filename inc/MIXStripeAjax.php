<?php

class MIXStripeAjax
{
    public function __construct() {
        add_action('wp_ajax_getStripePlans', array($this, 'getStripePlans'));
        add_action('wp_ajax_updateStripePlan', array($this, 'updateStripePlan'));
    }
    
    public function getStripePlans(){
        $MIXStripeApi = new MIXStripeApi();
        $plansResponse = $MIXStripeApi->getPlans();
        if(is_wp_error($plansResponse)){
            echo json_encode(array("success" => false, "error_message" => $plansResponse->get_error_message()));
            die();
        }
        global $wpdb;
        $table = $wpdb->prefix. 'mix_stripe_plans';
        $wpdb->query("TRUNCATE TABLE $table");
        foreach($plansResponse as $plan){
            $wpdb->insert($table, array(
                'plan_id' => $plan->id,
                'name' => $plan->name,
                'amount' => $plan->amount/100,
                'interv' => $plan->interval,
                'currency' => $plan->currency
            ), array(
                '%s', '%s', '%d', '%s', '%s'
            ));
        }
        echo json_encode(array("success" => true));
        die();
    }
    
    public function updateStripePlan(){
        global $wpdb;
        $id = (int) strip_tags($_POST['id']);
        $description = $_POST['description'];
        $table = $wpdb->prefix. 'mix_stripe_plans';
        $res = $wpdb->update($table, array(
            'description' => $description
        ), array(
            'ID' => $id
        ));
        if(!$res){
            echo json_encode(array('success' => false));
            die();
        }
        echo json_encode(array('success' => true));
        die();
    }
}

