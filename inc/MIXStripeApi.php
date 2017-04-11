<?php
if ( ! class_exists( '\Stripe\Stripe' ) ) {
    require_once( dirname( __FILE__ ) . '/vendor/stripe/stripe-php/init.php' );
}

class MIXStripeApi
{
    private $options = null;
    
    private $apiKey = '';

    private $stripeInfo = array();

    public function getStripeInfo()
    {
        return $this->stripeInfo;
    }

    public function __construct() {
        $this->options = get_option( 'fullstripe_options_f' );
        switch($this->options['apiMode']){
            case 'test':
                $this->apiKey = $this->options['secretKey_test'];
                break;
            case 'live':
                $this->apiKey = $this->options['secretKey_live'];
                break;
            default:
                $this->apiKey = $this->options['secretKey_test'];
                break;
        }
        \Stripe\Stripe::setApiKey($this->apiKey);
    }
    
    public function getPlans(){
        $res = '';
        try{
            $res = \Stripe\Plan::all(array("limit" => 3));
            $res = $res->data;
        }catch(Exception $ex){
            $res = new WP_Error(500, "Something went wrong");
        }
        return $res;
        
    }

    public function createCustomer($email, $token){
        try{
            return \Stripe\Customer::create(array(
                "email" => $email,
                "source" => $token,
            ));
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }

    }

    public function updateCustomerCard($customerId, $token)
    {
        try{
            $customer = \Stripe\Customer::retrieve($customerId);
            $customer->source = $token;
            $customer->save();
            return $customer;
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function createToken($card, $expMonth, $expYear, $cvv){
        try{
            $token = \Stripe\Token::create(array(
                "card" => array(
                    "number" => $card,
                    "exp_month" => $expMonth,
                    "exp_year" => $expYear,
                    "cvc" => $cvv
                )
            ));
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
        return $token;

    }

    public function subscribeNewUser($planId, $email, $card, $expMonth, $expYear, $cvv, $coupon = "")
    {
        $token = $this->createToken($card, $expMonth, $expYear, $cvv);
        if(is_wp_error($token)){
            return $token;
        }
        $this->stripeInfo['token'] = $token->id;
        $customer = $this->createCustomer($email, $token->id);
        $this->stripeInfo['customer'] = $customer->id;
        $this->stripeInfo['last4'] = $token->card->last4;
        $this->stripeInfo['card'] = $token->card->id;
        $this->stripeInfo['plan'] = $planId;

        if(is_wp_error($customer)){
            return $customer;
        }
        $subs = $this->subscribe($planId, $customer->id, $coupon);
        if(is_wp_error($subs)){
            return $subs;
        }

        $this->stripeInfo['subscription'] = $subs->id;
        $this->stripeInfo['tax_percent'] = $subs->tax_percent;
        $this->stripeInfo['created'] = $subs->created;
        return $subs;
    }

    public function updateCard($card, $expMonth, $expYear, $cvv){
        global $current_user;
        $stripeInfo = unserialize(get_user_meta($current_user->ID, 'stripeInfo', true));
        if(!is_array($stripeInfo)){
            $stripeInfo = array();
        }
        $oldCustomerId = is_array($stripeInfo)&&isset($stripeInfo['customer'])?$stripeInfo['customer']:'';
        \Stripe\Stripe::setApiKey($this->apiKey);

        $token = $this->createToken($card, $expMonth, $expYear, $cvv);
        if(is_wp_error($token)){
            echo $token->get_error_message();
            return false;
        }
        $ccno = $token->card->last4;
        $email = $current_user->user_email;
        if(!$oldCustomerId){
            $customer = $this->createCustomer($email, $token->id);
        }else{
            $customer = $this->updateCustomerCard($oldCustomerId, $token->id);
        }

        if(is_wp_error($customer)){
            echo $customer->get_error_message();
            return false;
        }

        $stripeInfo['token'] = $token->id;
        $stripeInfo['customer'] = $customer->id;
        $stripeInfo['last4'] = $ccno;

        $newStripeInfo = serialize($stripeInfo);

        update_user_meta($current_user->ID, 'stripeInfo', $newStripeInfo);
        return true;
    }

    public function subscribe($planId, $subscriberId, $coupon = ""){
        $args = array(
            "customer" => $subscriberId,
            "plan" => $planId,
        );
        if($coupon){
            $args['coupon'] = $coupon;
        }
        try{
            return \Stripe\Subscription::create($args);
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function updatePlan($subscriptionId, $planId)
    {
        try{
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $subscription->plan = $planId;
            $subscription->save();
            return true;
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function cancelPlan($subscriptionId)
    {
        try{
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $subscription->cancel(array('at_period_end' => true));
            return true;
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function getCharges($cutomerId = '', $limit = 0)
    {
        try{
            $args = array(
                'limit' => 100,
            );
            if($limit){
                $args = array(
                    'limit' => $limit,
                );
            }
            if($cutomerId){
                $args['customer'] = $cutomerId;
            }
            return \Stripe\Invoice::all($args);
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function getSubscription($subscriptionId)
    {
        try{
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            return $subscription;
        }catch(Exception $ex){
            return new WP_Error(500, $ex->getMessage());
        }
    }

    public function recieveWebhook()
    {
        $input = @file_get_contents("php://input");
        $event = json_decode($input);
        return $event;
    }
}

