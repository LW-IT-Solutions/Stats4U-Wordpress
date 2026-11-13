<?php
if (!defined('ABSPATH')) { exit; }
function stats4u_shop_defaults() { return array('enabled'=>false,'id'=>'','secret'=>''); }
function stats4u_shop_settings() { return wp_parse_args(get_option('stats4u_shop',array()),stats4u_shop_defaults()); }
add_action('admin_menu',function(){add_options_page(__('Stats4U shop','stats4u'),__('Stats4U shop','stats4u'),'manage_options','stats4u-shop','stats4u_shop_page');});
function stats4u_shop_page() {
    if (!current_user_can('manage_options')) { return; }
    $cfg=stats4u_shop_settings();$error=false;
    $post=isset($_SERVER['REQUEST_METHOD'])&&'POST'===sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']));
    if ($post&&isset($_POST['s4u_retry'])) {
        check_admin_referer('stats4u-shop');
        foreach(array_keys((array)get_option('stats4u_shop_error',array())) as $oid) {
            $o=function_exists('wc_get_order')?wc_get_order((int)$oid):false;
            if($o){$o->delete_meta_data('_s4u_retry_count');$o->save_meta_data();stats4u_shop_report((int)$oid);}
        }
        wp_safe_redirect(admin_url('options-general.php?page=stats4u-shop'));exit;
    }
    if ($post) {
        check_admin_referer('stats4u-shop');
        $id=isset($_POST['s4u_id'])?sanitize_text_field(wp_unslash($_POST['s4u_id'])):'';
        $secret=isset($_POST['s4u_secret'])?sanitize_text_field(wp_unslash($_POST['s4u_secret'])):'';
        if (!preg_match('/^[1-9][0-9]{0,17}$/D',$id)||($secret!==''&&!preg_match('/^[a-f0-9]{64}$/D',$secret))) { $error=true; }
        else {
            if($secret===''){$secret=$cfg['secret'];}
            $cfg=array('enabled'=>!empty($_POST['s4u_enabled'])&&$secret!=='','id'=>$id,'secret'=>$secret);
            update_option('stats4u_shop',$cfg,false);
        }
    }
    echo '<div class="wrap"><h1>'.esc_html__('Stats4U shop','stats4u').'</h1>';
    echo '<p>'.esc_html__('Enable shop reporting in your Stats4U owner link, create a shop secret and enter it here. Only confirmed payments and refunds are sent, with currency and an anonymous order reference. No customer details are sent.','stats4u').'</p>';
    if($error){echo '<div class="notice notice-error"><p>'.esc_html__('Check the counter number and shop secret.','stats4u').'</p></div>';}
    if(!class_exists('WooCommerce')){echo '<div class="notice notice-warning"><p>'.esc_html__('WooCommerce is required for shop reporting.','stats4u').'</p></div>';}
    if(get_option('stats4u_shop_error')){echo '<div class="notice notice-error"><p>'.esc_html__('A shop report failed. Stats4U retries automatically; check your settings and the server connection.','stats4u').'</p></div>';}
    echo '<form method="post">';wp_nonce_field('stats4u-shop');
    echo '<p><label><input type="checkbox" name="s4u_enabled" value="1" '.checked($cfg['enabled'],true,false).'> '.esc_html__('Enable shop reporting','stats4u').'</label></p>';
    echo '<p><label>'.esc_html__('Counter number','stats4u').' <input name="s4u_id" inputmode="numeric" value="'.esc_attr($cfg['id']).'" required></label></p>';
    echo '<p><label>'.esc_html__('Shop secret','stats4u').' <input type="password" name="s4u_secret" value="" autocomplete="new-password" maxlength="64"></label></p><p>'.esc_html__('Leave the secret blank to keep the existing one.','stats4u').'</p>';
    submit_button();echo '</form>';
    if(get_option('stats4u_shop_error')){echo '<form method="post">';wp_nonce_field('stats4u-shop');echo '<button class="button" name="s4u_retry" value="1">'.esc_html__('Retry failed reports','stats4u').'</button></form>';}
    echo '<p><a href="https://www.stats4u.net/guide/wordpress">'.esc_html__('Integration guide','stats4u').'</a></p></div>';
}
function stats4u_shop_cents($amount) {
    $s=wc_format_decimal($amount,2);
    if(!preg_match('/^([0-9]{1,10})\.([0-9]{2})$/D',$s,$m)){return null;}
    $n=(int)$m[1]*100+(int)$m[2];return $n<=100000000000?$n:null;
}
function stats4u_shop_error($id,$failed) {
    $a=(array)get_option('stats4u_shop_error',array());unset($a[0]);
    if($failed){$a[(int)$id]=true;}else{unset($a[(int)$id]);}
    if(count($a)>100){$a=array_slice($a,-100,null,true);}
    if($a){update_option('stats4u_shop_error',$a,false);}else{delete_option('stats4u_shop_error');}
}
function stats4u_shop_report($order_id,$attempt=0) {
    $cfg=stats4u_shop_settings();
    if(!$cfg['enabled']||!function_exists('wc_get_order')){return;}
    $order=wc_get_order($order_id);
    if(!$order||!$order->get_date_paid()){return;}
    $paidAt=$order->get_date_paid()->getTimestamp();
    if($paidAt<time()-400*86400){return;}
    $currency=$order->get_currency();
    if(!in_array($currency,array('EUR','PLN','USD','GBP','CHF','CZK','SEK','HUF','RON'),true)){stats4u_shop_error($order_id,true);return;}
    $paid=stats4u_shop_cents($order->get_total());$refunded=stats4u_shop_cents($order->get_total_refunded());
    if($paid===null||$refunded===null||$paid<=0||$refunded>$paid){stats4u_shop_error($order_id,true);return;}
    $reference=(string)$order->get_meta('_s4u_order_reference');
    if(!preg_match('/^[a-f0-9]{64}$/D',$reference)){
        // Deterministic reference survives secret rotation, concurrent hooks and retries.
        $reference=hash_hmac('sha256',home_url('/').'|'.$order->get_id(),wp_salt('auth'));
        $order->update_meta_data('_s4u_order_reference',$reference);$order->save_meta_data();
    }
    $body=wp_json_encode(array('order'=>$reference,'paid'=>$paid,'refunded'=>$refunded,'currency'=>$currency,'paid_at'=>$paidAt));
    $stamp=time();$signature=hash_hmac('sha256',$stamp.'.'.$body,$cfg['secret']);
    $response=wp_remote_post('https://www.stats4u.net/index.php?action=insights_sale&s4uid='.rawurlencode($cfg['id']),array(
        'timeout'=>10,'redirection'=>0,'headers'=>array('Content-Type'=>'application/json','X-S4U-Timestamp'=>(string)$stamp,'X-S4U-Signature'=>$signature),'body'=>$body));
    $ok=!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200;
    if($ok){
        stats4u_shop_error($order_id,false);wp_clear_scheduled_hook('stats4u_shop_retry',array((int)$order_id));
        $order->delete_meta_data('_s4u_retry_count');$order->save_meta_data();return;
    }
    stats4u_shop_error($order_id,true);
    $count=max((int)$attempt,(int)$order->get_meta('_s4u_retry_count'))+1;
    $order->update_meta_data('_s4u_retry_count',$count);$order->save_meta_data();
    if($count<=8&&!wp_next_scheduled('stats4u_shop_retry',array((int)$order_id))){
        wp_schedule_single_event(time()+min(21600,60*(2**$count)),'stats4u_shop_retry',array((int)$order_id));
    }
}
add_action('woocommerce_payment_complete','stats4u_shop_report',20,1);
add_action('woocommerce_order_status_processing','stats4u_shop_report',20,1);
add_action('woocommerce_order_status_completed','stats4u_shop_report',20,1);
add_action('woocommerce_order_refunded','stats4u_shop_report',20,1);
add_action('stats4u_shop_retry','stats4u_shop_report',10,1);
add_action('admin_notices',function(){
    if(current_user_can('manage_options')&&get_option('stats4u_shop_error')){
        echo '<div class="notice notice-error"><p><a href="'.esc_url(admin_url('options-general.php?page=stats4u-shop')).'">'.esc_html__('Stats4U: shop reporting needs attention.','stats4u').'</a></p></div>';
    }
});
