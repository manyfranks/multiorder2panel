<?php
add_action( 'woocommerce_payment_complete', 'create_order_to_pure', 10, 1 );

function create_order_to_pure($order_id){
    $api_url = ''; /*API URL here*/
    $api_key = ''; /*API Key Here*/
    $link_meta_key_text = 'Enter Your Post or Profile Link';
    
    $wc_order = wc_get_order($order_id); /* Get order */
    
    if($wc_order){
       
        $items = $wc_order->get_items();
        foreach($items as $item_id => $item){
            $smm_order_id_meta_text = 'smm_order_id_'.$item_id; /*order meta key used to store SMM order id */
                        
            $parent_product_id  = $item->get_product_id();
            
            
            if (has_term( 'Glitch', 'product_cat', $parent_product_id && has_term( 'Piece of Cake', 'choose-skill-level', $parent_product_id ) ) ) { /*Check product has category "glitch" and "piece of cake"*/
                $smm_order_id = $wc_order->get_meta($smm_order_id_meta_text, true); /*Create order in PureSMM if it is not created*/
                if(empty($smm_order_id)){
                    $product = $item->get_product();
                    
                    $link = $item->get_meta( $link_meta_key_text, true);
                    
                    $live = array(
                        'key' => $api_key, 
                        'action' => 'add',
                        'service' => '10',                        
                        'link'  => $link,
                        'quantity' => '25',
                    );
                    $follow = array(
                        'key' => $api_key, 
                        'action' => 'add',
                        'service' => '8',                        
                        'link'  => $link,
                        'quantity' => '50',
                    );
                    $channel_views = array(
                        'key' => $api_key, 
                        'action' => 'add',
                        'service' => '3',                        
                        'link'  => $link,
                        'quantity' => '2000',
                    );
                    /*$clip_views = array(
                        'key' => $api_key, 
                        'action' => 'add',
                        'service' => '6',                        
                        'link'  => $link,
                        'quantity' => '1000',
        
                    )*/;
                    /*post on smm */
                    $response = wp_remote_post( $api_url, array(
                            'method' => 'POST',
                            'body' => $live
                        )
                    );
                    $response = wp_remote_post( $api_url, array(
                            'method' => 'POST',
                            'body' => $follow
                        )
                    );
                    $response = wp_remote_post( $api_url, array(
                            'method' => 'POST',
                            'body' => $channel_views
                        )
                    );
                    
                    $error_message = '';
                    if( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                    }
                    else if(isset($response['body'])){
                       $response_body = json_decode($response['body'],true);
                       if(isset($response_body['order']) && !empty($response_body['order'])){
                           $smm_order_id = $response_body['order'];
                       }
                       else{
                           $error_message = $response_body['error'];
                       }
                    }
                    else{
                        $error_message = "something went wrong";
                    }
                    
                    if(!empty($error_message)){
                        /*Add in error logging file if any error*/
                        /*Admin can see error log at WooCommerce -> Status -> Logs -> smm-log(from drop down)*/
                        $log = new WC_Logger();
                        $log_entry = "\n URL : ".$api_url;
                        $log_entry .= "\n Error: ".$error_message;
                        $log_entry .= "\n Request: ".print_r( $body, true );
                        $log_entry .= "\n Response: ". print_r( $response , true );
                        $log->add( 'smm-log', $log_entry );
                    }
                    else{
                        /*Add smm order id if success*/
                        update_post_meta( $order_id, $smm_order_id_meta_text, $smm_order_id);
                    }
                }
            
            }       
        }
    }       
}