<?php

/* Function file */

if(!function_exists('wpvc_is_promo_id')){
	function wpvc_is_promo_id($promo_id)
	{
		$args = array(
			'meta_key' => 'vc_unique_promo_id',
			'meta_value' => $promo_id,
			'post_type' => 'wpcd_coupons',
			'post_status' => 'published',
			'posts_per_page' => -1
		);
		$posts = get_posts($args);
		if($posts){
			return true;
		}else{
			return false;
		}
	}
}

add_action('init', 'wpvc_get_category_id');

if(!function_exists('wpvc_get_category_id')){
	function wpvc_get_category_id($category_name)
	{
		global $wpdb;
		$category_name = htmlspecialchars($category_name);
		$result = $wpdb->get_results( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = 					tt.term_id 	WHERE tt.taxonomy = 'wpcd_coupon_category' AND t.name = '$category_name'"
				);
		if($result){
			return $result[0]->term_id;
		}else{
			$category = wp_insert_term($category_name,'wpcd_coupon_category');
			if ( !is_wp_error($category) ) {
				return $category['term_id'];
			}else{
				return 0;
			}
		}
	}
}
add_action('init', 'wpvc_get_store_id');
	
if(!function_exists('wpvc_get_store_id')){
	function wpvc_get_store_id($store_name)
	{
		global $wpdb;
		$store_name = htmlspecialchars($store_name);
		$result = $wpdb->get_results( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = 					tt.term_id 	WHERE tt.taxonomy = 'wpcd_coupon_vendor' AND t.name = '$store_name'"
				);
		if($result){
			return $result[0]->term_id;
		}else{
			$store = wp_insert_term($store_name,'wpcd_coupon_vendor');
			if ( !is_wp_error($store) ) {
				return $store['term_id'];
			}else{
				return 0;
			}
		}
	}
}

if(!function_exists('wpvc_fetch_coupon_function')){
	
	function wpvc_fetch_coupon_function($api_key = '') 
	{

		global $wpdb,$post;

		$wp_vc_setting_options = get_option( 'wp_vc_setting_option_name' );
		$api_key = $wp_vc_setting_options['api_key_0'];

		if(!isset($api_key)){
			return;
		}
		if(empty($api_key)){
			return;
		}
		$response = wp_remote_get("https://tools.vcommission.com/api/coupons.php?apikey=$api_key");
		$body_response = wp_remote_retrieve_body( $response );
		$coupons = json_decode($body_response,TRUE);

		foreach($coupons as $coupon){
			
			if(wpvc_is_promo_id($coupon['promo_id'])){ // Check if Promo ID already exists.
				continue;
			}
			
			$category_id = wpvc_get_category_id($coupon['category']);
			$store_id = wpvc_get_store_id($coupon['store_name']);

			$coupon_detail = array(
				'post_title'    => wp_strip_all_tags( $coupon['offer_name']),
				'post_type'   => 'wpcd_coupons',
				'post_status'   => 'publish',
				'post_author'   => 1,
			);
			$post_id = wp_insert_post( $coupon_detail,$wp_error );
			
			if($post_id){
				
				wp_set_object_terms( $post_id, intval($category_id), 'wpcd_coupon_category' );
				wp_set_object_terms( $post_id, intval($store_id), 'wpcd_coupon_vendor' );

				add_post_meta($post_id,'vc_unique_promo_id',$coupon['promo_id']);
				
				$coupon_type = $coupon['coupon_type'] == "Coupon" ? 'Coupon' :'Deal';
				if($coupon_type == 'Coupon')
				{
					update_post_meta( $post_id, WPVC_PREFIX.'coupon-code-text',trim($coupon['coupon_code']));
				}else
				{
					update_post_meta( $post_id, WPVC_PREFIX.'deal-button-text', "GET DEAL");
				}
				
				update_post_meta( $post_id, WPVC_PREFIX.'coupon-type', $coupon_type );
				update_post_meta( $post_id, WPVC_PREFIX.'discount-text', $coupon['coupon_title'] );
				update_post_meta( $post_id, WPVC_PREFIX.'hide-coupon',"Yes");
				update_post_meta( $post_id, WPVC_PREFIX.'link',trim($coupon['link']));
				update_post_meta( $post_id, WPVC_PREFIX.'description',$coupon['coupon_description']);
				update_post_meta( $post_id, WPVC_PREFIX.'show-expiration','Show');
				update_post_meta( $post_id, WPVC_PREFIX.'expire-date',date('d-m-Y',strtotime($coupon['coupon_expiry'])));
			}
		}	
	}
}
