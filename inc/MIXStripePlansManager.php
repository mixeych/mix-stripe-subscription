<?php


class MIXStripePlansManager
{
    public function getPlans()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mix_stripe_plans';
        $query = "SELECT * FROM $table";
        return $wpdb->get_results($query);
    }

    public function getOrders()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mix_stripe_orders';
        $query = "SELECT * FROM $table";
        return $wpdb->get_results($query);
    }

    public function get_plan($id){
        global $wpdb;
        $table = $wpdb->prefix . 'mix_stripe_plans';
        $query = "SELECT * FROM $table WHERE plan_id='$id'";
        return $wpdb->get_row($query);
    }

    /*public function getCurrentUserCharges($limit = 0)
    {
        global $current_user;
        $stripeInfo = unserialize(get_user_meta($current_user->ID, 'stripeInfo', true));
        $api = new MIXStripeApi();
        if(!is_array($stripeInfo)||!isset($stripeInfo['customer'])){
            return false;
        }

        $charges = $api->getCharges($stripeInfo['customer'], $limit);
        if(is_wp_error($charges)){
            return false;
        }
        return $charges;
    }*/

    public function getCurrentUserCharges($limit = 0)
    {
        global $current_user;
        global $wpdb;
        $query = "SELECT p.name, p.interv, p.currency, u.user_email, o.amount, o.created_at, o.ID FROM {$wpdb->prefix}mix_stripe_orders as o
                JOIN {$wpdb->prefix}mix_stripe_plans as p ON p.plan_id = o.plan_id
                JOIN {$wpdb->users} as u ON o.user_id = u.ID
                WHERE u.ID = {$current_user->ID}
                ORDER BY o.created_at DESC";
        if($limit){
            $query .= " LIMIT $limit";
        }
        return $wpdb->get_results($query);
    }

    public function getCurrentSubscription()
    {
        global $current_user;
        $stripeInfo = unserialize(get_user_meta($current_user->ID, 'stripeInfo', true));
        $api = new MIXStripeApi();
        if(!is_array($stripeInfo)||!isset($stripeInfo['subscription'])){
            return false;
        }
        $subs = $api->getSubscription($stripeInfo['subscription']);
        if(is_wp_error($subs)){
            return false;
        }
        return $subs;
    }

    public function updateCurrentSubscription($planId)
    {
        global $current_user;
        $stripeInfo = unserialize(get_user_meta($current_user->ID, 'stripeInfo', true));
        if(!is_array($stripeInfo)||!isset($stripeInfo['subscription'])){
            return false;
        }
        $api = new MIXStripeApi();
        if($stripeInfo['subscription'] == -1){
            if(isset($stripeInfo['customer'])&&!empty($stripeInfo['customer'])){
                $subs = $api->subscribe($planId, $stripeInfo['customer'], '');
                if(is_wp_error($subs)){
                    return false;
                }
                $stripeInfo['subscription'] = $subs->id;
                $stripeInfo['plan'] = $planId;
                update_user_meta($current_user->ID, 'stripeInfo', serialize($stripeInfo));
                return true;
            }
            return false;
        }

        $subs = $api->updatePlan($stripeInfo['subscription'], $planId);
        if(is_wp_error($subs)){
            echo $subs->get_error_message();
            return false;
        }

        $stripeInfo['plan'] = $planId;
        update_user_meta($current_user->ID, 'stripeInfo', serialize($stripeInfo));
        return true;
    }

    public function cancelCurrentSubscription()
    {
        global $current_user;
        $stripeInfo = unserialize(get_user_meta($current_user->ID, 'stripeInfo', true));
        if(!is_array($stripeInfo)||!isset($stripeInfo['subscription'])||$stripeInfo['subscription']==-1){
            return false;
        }
        $api = new MIXStripeApi();
        $res = $api->cancelPlan($stripeInfo['subscription']);
        if(is_wp_error($res)){
            echo $res->get_error_message();
            return false;
        }
        $stripeInfo['subscription'] = -1;
        $stripeInfo['plan'] = '';
        update_user_meta($current_user->ID, 'stripeInfo', serialize($stripeInfo));
        return true;
    }

    public function recieveStripeWebHook()
    {
        $api = new MIXStripeApi();
        $event = $api->recieveWebhook();
        try{
            $cusId = $event->data->object->customer;
            $amount = $event->data->object->amount;
        }catch(Exception $ex){
            return false;
        }
        $args = array(
            'meta_key' => 'customer_id',
            'meta_value' => $cusId
        );
        $users = get_users($args);

        if(empty($users)){
            return false;
        }
        $user = $users[0];

        $stripeInfo = unserialize(get_user_meta($user->ID, 'stripeInfo', true));
        $args = array(
            'plan_id' => $stripeInfo['plan'],
            'user_id' => $user->ID,
            'amount' => $amount
        );
        $this->insertOrder($args);
    }

    public function insertOrder($args)
    {
        global $wpdb;
        $planId = $args['plan_id'];
        $userId = (int) $args['user_id'];
        $amount = (int) $args['amount'];
        $tableOrders = $wpdb->prefix . 'mix_stripe_orders';
        $wpdb->insert(
            $tableOrders,
            array( 'plan_id' => $planId, 'user_id' => $userId, 'amount' => $amount ),
            array( '%s', '%d', '%d' )
        );
    }
}

global $MIXStripePlansManager;
$MIXStripePlansManager = new MIXStripePlansManager();