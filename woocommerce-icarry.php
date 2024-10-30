<?php
/*
Plugin Name: iCarry.in Courier Aggrgator
Plugin URI: https://www.icarry.in/plugins/woocommerce
Description: iCarry.in Courier Aggregator Plugin for managing shipping & tracking of your orders
Author: iCarry
Author URI: https://www.icarry.in
Version: 2.0.0

	Copyright: © 2019 iCarry & ShopHealthy (email : support@icarry.in)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
session_start();
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Icarry_Plugin' ) ) {
	
class Icarry_Plugin {	

	public function __construct() {
		/**
		 * WooCommerce fallback notice.
		 *
		 * @since 2.0.0
		 * @return string
		 */
		function woocommerce_icarry_missing_wc_notice() {
			/* translators: %s WC download URL link. */
			echo esc_html('<div class="error"><p><strong>' . sprintf( 'iCarry Shipping requires WooCommerce to be installed and active. You can download %s here.', '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>');
		}
		
        if (is_admin()) {
            register_activation_hook(__FILE__, array(&$this, 'icarry_install'));
            register_deactivation_hook( __FILE__, array(&$this, 'icarry_uninstall'));
        }		

		add_filter( 'manage_edit-shop_order_columns', 'ICARRY_SHIPMENTS_COLUMNS_FUNCTION' );
		function ICARRY_SHIPMENTS_COLUMNS_FUNCTION( $columns ) {
			$new_columns = ( is_array( $columns ) ) ? $columns : array();
			unset( $new_columns[ 'order_actions' ] );
			
			//edit this for your column(s)
			//all of your columns will be added before the actions column
			$new_columns['shipment_awb'] = 'Tracking Number';
			$new_columns['shipment_type'] = 'Shipment Type';
			$new_columns['shipment_mode'] = 'Shipment Mode';
			$new_columns['shipment_status'] = 'Shipment Status';	
			$new_columns['shipment_picked_date'] = 'Picked On';
			$new_columns['shipment_delivered_date'] = 'Delivered On';
			
			return $new_columns;
		}

		add_filter( "manage_edit-shop_order_sortable_columns", 'ICARRY_SHIPMENTS_COLUMNS_SORT_FUNCTION' );
		function ICARRY_SHIPMENTS_COLUMNS_SORT_FUNCTION( $columns ) 
		{
			$custom = array(
					'shipment_awb'    => 'shipment_awb_POST_META_ID',			
					'shipment_type'    => 'shipment_type_POST_META_ID',
					'shipment_mode'    => 'shipment_mode_POST_META_ID',
					'shipment_status'    => 'shipment_status_POST_META_ID', 			
					'shipment_picked_date'    => 'shipment_picked_date_POST_META_ID',
					'shipment_delivered_date'    => 'shipment_delivered_date_POST_META_ID'
					);
			return wp_parse_args( $custom, $columns );
		}

		add_action( 'manage_shop_order_posts_custom_column', 'ICARRY_SHIPMENTS_COLUMNS_VALUES_FUNCTION', 2 );
		function ICARRY_SHIPMENTS_COLUMNS_VALUES_FUNCTION( $column ) {
			global $post;
			$data = get_post_meta( $post->ID );
			//error_log(print_r($data,1));	
			if ( $column == 'shipment_status' ) {
				$shipment_status = isset( $data[ 'shipment_status_POST_META_ID' ] ) ? esc_attr($data[ 'shipment_status_POST_META_ID' ][0]) : '';
				$shipment_status_id = isset( $data[ 'shipment_status_id_POST_META_ID' ] ) ? $data[ 'shipment_status_id_POST_META_ID' ][0] : '';
				if ($shipment_status_id == 21 || $shipment_status_id == 23 || $shipment_status_id == 26) {
					echo '<p style="color:green;font-size:16px">'.$shipment_status.'</p>';				
				} else if ($shipment_status_id == 1 || $shipment_status_id == 2 || $shipment_status_id == 24 || $shipment_status_id == 25) {
					echo '<p style="color:red;font-size:16px">'.$shipment_status.'</p>';			
				} else {
					echo '<p style="color:blue;font-size:16px">'.$shipment_status.'</p>';					
				}
			}
			
			if ( $column == 'shipment_awb' ) {
				$awb = '';
				if (isset( $data[ 'shipment_awb_POST_META_ID' ] ) && $data[ 'shipment_awb_POST_META_ID' ][0] != '' ) {
					$shipment_id = $data[ 'shipment_id_POST_META_ID' ][0];
					$courier_id = $data[ 'shipment_courier_id_POST_META_ID' ][0];
					$awb = esc_attr($data[ 'shipment_awb_POST_META_ID' ][0]);
					$track_url = esc_url('https://www.icarry.in/track-shipment?shipment_id='.$shipment_id.'&courier_id='.$courier_id.'&awb='.$awb);
					echo '<a href="'.$track_url.'" target="_blank">'.$awb.'</a>';
				} else {
					echo $awb;
				}
			}

			if ( $column == 'shipment_type' ) {
				echo ( isset( $data[ 'shipment_type_POST_META_ID' ] ) ? esc_attr($data[ 'shipment_type_POST_META_ID' ][0]) : '' );
			}

			if ( $column == 'shipment_mode' ) {
				echo ( isset( $data[ 'shipment_mode_POST_META_ID' ] ) ? esc_attr($data[ 'shipment_mode_POST_META_ID' ][0]) : '' );
			}

			if ( $column == 'shipment_picked_date' ) {
				echo ( isset( $data[ 'shipment_picked_date_POST_META_ID' ] ) ? esc_attr($data[ 'shipment_picked_date_POST_META_ID' ][0]) : '' );
			}

			if ( $column == 'shipment_delivered_date' ) {
				echo ( isset( $data[ 'shipment_delivered_date_POST_META_ID' ] ) ? esc_attr($data[ 'shipment_delivered_date_POST_META_ID' ][0]) : '' );
			}
		}

		
		// Add Admin Menu options 
		if ( in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {     
			//add_action('admin_menu', 'icarry_admin_menu'); 
			add_action('admin_menu',  array(&$this,'icarry_admin_menu'), 10);			
		} 

		add_action('admin_enqueue_scripts', 'ela_script_list');   
		function ela_script_list() {
			wp_enqueue_style('custom',  plugins_url('icarry-in-shipping-tracking/css/all.css') );
			wp_enqueue_style('font', plugins_url('icarry-in-shipping-tracking/css/font.css') );
			wp_enqueue_style('owl.carousel.min', plugins_url('icarry-in-shipping-tracking/css/owl.carousel.min.css') );
			wp_enqueue_style('datetimepicker',  plugins_url('icarry-in-shipping-tracking/css/jquery.datetimepicker.min.css') );
			wp_enqueue_script('jQuery');
			wp_enqueue_script('owl.carousel_min_js', plugins_url('icarry-in-shipping-tracking/js/owl.carousel.min.js'));
			wp_enqueue_script('popper.min_js', plugins_url('icarry-in-shipping-tracking/js/popper.min.js'));  
		}

		add_action( 'wp_ajax_fetch_icarry_shipment_list', 'fetch_icarry_shipment_list_callback' );
		function fetch_icarry_shipment_list_callback() {
			require_once('api_token.php');
			$icarry_session_key = $_SESSION['icarry_api_token'];
			global $wpdb;
			$startPage = sanitize_text_field($_POST['getresult']);
			$PERPAGE_LIMIT = 20;
			$myrow = $wpdb->get_results("SELECT a.order_id, e.meta_value as shipping_pincode , f.meta_value as billing_pincode FROM ".$wpdb->prefix."woocommerce_order_items a  JOIN  ".$wpdb->prefix."postmeta e  ON a.order_id = e.post_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id WHERE a.order_item_type = 'line_item' AND  e.meta_key='_shipping_postcode' AND f.meta_key='_billing_postcode' AND a.order_item_type='line_item' group by a.order_id ORDER BY a.order_id ASC");
			$count = 0;
			foreach ($myrow as $row) {
				$o_id = $row->order_id;
				$order = wc_get_order( $o_id );
				$order_status  = $order->get_status();
				if ($order_status=='processing' || $order_status=='pending') {  
					$count++; 
				}
			}
			
			$orders = $wpdb->get_results("SELECT a.order_id,e.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount ,i.meta_value as payment_method ,j.meta_value as sname,k.meta_value as bname,l.meta_value as phoneno FROM ".$wpdb->prefix."woocommerce_order_items a JOIN   ".$wpdb->prefix."postmeta e  ON a.order_id = e.post_id  JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i ON a.order_id = i.post_id JOIN  ".$wpdb->prefix."postmeta j  ON a.order_id = j.post_id JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id WHERE a.order_item_type = 'line_item'  AND e.meta_key='_shipping_postcode' AND f.meta_key='_billing_postcode' AND g.meta_key='_order_total' and j.meta_key ='_shipping_first_name' and k.meta_key ='_billing_first_name' AND l.meta_key ='_billing_phone' AND a.order_item_type='line_item'  and i.meta_key ='_payment_method' group by a.order_id ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT");
			$datas='<table class="table table-bordered table-hover">
					<thead>
					 <tr>
						<th>
						  <label class="checkbox">
						  <input type="checkbox" class="selectall" />
						  <span class="checkmark all-select-checkbox"></span>
						</th>
						<th>Actions</th>
						<th>Order ID</th>
						<th>Order Status</th>
						<th>Customer Name</th>
						<th>Product(s)</th>
						<th>Quantity</th>
						<th>Order Total</th>
						<th>Shipment Value</th>
						<th>Shipment Type</th>
						<th>Shipment Mode</th>
						<th>Pickup Point</th>
						<th>Tracking Number</th>
						<th>Courier Name</th>
						<th>Shipment Status</th>
					 </tr>
				    </thead><tbody>';
			foreach ($orders as $data) {
				$scost_detail = $wpdb->get_row("select count(id) as cnt,shipment_cost,shipping_name,shipping_address,shipping_city,shipping_state,shipping_phone,shipment_details,shipping_pincode,shipment_type,shipment_mode,shipment_total,pickup_address_id,shipment_id,awb,courier_id,courier_name,shipment_status_id,(SELECT status_name FROM ".$wpdb->prefix."ela_shipment_status WHERE status_id=shipment_status_id) AS status_name, invoice_name,ewbn from ".$wpdb->prefix."ela_order_shipment where order_id='$data->order_id'");
				$shipment_cost = $scost_detail->shipment_cost; 
				$awbno = $scost_detail->awb; 
				$cooid = $scost_detail->courier_id;
				$courier_name = $scost_detail->courier_name;
				$shipment_status_id = $scost_detail->shipment_status_id;
				$ewbn = $scost_detail->ewbn;
				$invoice = $scost_detail->invoice_name;
				$cnt = $scost_detail->cnt;
				$shipment_type = $scost_detail->shipment_type;
				$shipment_mode = $scost_detail->shipment_mode;
				$shipment_status = $scost_detail->status_name;
				$shipment_status_id = $scost_detail->shipment_status_id;
				$shipment_total = $scost_detail->shipment_total;
				$pickup_address_id = $scost_detail->pickup_address_id;
				$shipment_id = $scost_detail->shipment_id;								
				$customer_name = $scost_detail->shipping_name;
				$pincode = $scost_detail->shipping_pincode;
				$address = $scost_detail->shipping_address;
				$city = $scost_detail->shipping_city;
				$state = $scost_detail->shipping_state;
				$phone = $scost_detail->shipping_phone;
				$shipment_details = $scost_detail->shipment_details;
				$order_total = $data->total_amount;
				if ($awbno == '' && $shipment_id == '') {
					$shipment_total = 0;
					$module_setting = get_option('icarry_shipping');
					$s_cod_payment_code=$module_setting['cod_payment_code'];
					if (stripos($data->payment_method,$s_cod_payment_code)!==false) $shipment_type = 'C';
					else $shipment_type = 'P';
					if (@$data->shipping_pincode=='') {
						$pincode = $data->billing_pincode;
					} else {
						$pincode = $data->shipping_pincode;
					}
					if (@$data->sname=='') {
						$customer_fname = $data->bname;
					} else {
						$customer_fname = $data->sname;
					} 
					if (@$data->slname=='') {
						$customer_lname = $data->blname;
					} else {
						$customer_lname = $data->slname;
					} 
					$customer_name = $customer_fname.' '.$customer_lname;
					$phone = $data->phoneno;
					$orderr = wc_get_order( $data->order_id );
					$orderdata = $orderr->get_data();
					$address = $orderdata['shipping']['address_1'].' '.$orderdata['shipping']['address_2'];
					$city = $orderdata['shipping']['city'];
					$state = $orderdata['shipping']['state'];
				}
				$item_values = "";
				$get_item =  $wpdb->get_results("select order_item_name from ".$wpdb->prefix."woocommerce_order_items where order_id=$data->order_id and order_item_type='line_item'");
				foreach ($get_item as $get_items) {
					//$prod_details = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix  ."posts where post_title='$get_items->order_item_name' and post_status='publish'");
					//$prod_id = $prod_details->ID;
					//$prod = wc_get_product($prod_id);
					$pro_details = $get_items->order_item_name;
					$item_values != "" && $item_values .= " | ";
					$item_values .= $pro_details;
				}

				$quantity = 0;
				$get_item_qty =  $wpdb->get_results("select b.meta_value as qty FROM ".$wpdb->prefix."woocommerce_order_items a JOIN ".$wpdb->prefix."woocommerce_order_itemmeta b ON a.order_item_id = b.order_item_id where a.order_id=$data->order_id and meta_key='_qty'");
				foreach ($get_item_qty as $get_item_qtys) {
					$quantity = $quantity+$get_item_qtys->qty;
				}
				$item_total_amount = 0;
				$get_item_price =  $wpdb->get_results("select b.meta_value as prc FROM ".$wpdb->prefix."woocommerce_order_items a JOIN ".$wpdb->prefix."woocommerce_order_itemmeta b ON a.order_item_id = b.order_item_id where a.order_id=$data->order_id and meta_key='_line_subtotal'");
				foreach ($get_item_price as $get_item_prices) {
					$item_total_amount = $item_total_amount+$get_item_prices->prc;
				}				
				if ($awbno != '' && $shipment_id != '') { 
					$track_url = "https://www.icarry.in/track-shipment&shipment_id=".$shipment_id."&awb=".$awbno;
					$print_url = "https://www.icarry.in/print-shipment&shipment_id=".$shipment_id."&awb=".$awbno."&courier_id=".$cooid;
				} else {
					$print_url = $track_url = '';
				}
				
				if ($shipment_type == 'P') { $shipment_type = 'Prepaid'; }
				else if ($shipment_type == 'C') { $shipment_type = 'COD'; }	
				if ($shipment_mode == 'S') { $shipment_mode = 'Standard'; }
				else if ($shipment_mode == 'E') { $shipment_mode = 'Express'; }

				$action='';
				if ($awbno != '' && $shipment_id != '' && !in_array($shipment_status_id, array(7))) {
					$action .= '<p>[ <a href="'.$track_url.'" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="top" title="Track" target="_blank"><i class="fas fa-search gray-icon"></i> Track</a> ]</p>';			
				}
				if (in_array($shipment_status_id, array(1,2))) {
					$action .= '<p>[ <a href="#" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Cancel" onclick="ela_cancel_shipment(\''.$data->order_id.'\',\''.$shipment_id.'\',\''.$icarry_session_key.'\');"><i class="fas fa-trash-alt gray-icon"></i></a></p>';
				}
				if (in_array($shipment_status_id, array(1))) {
					$action .= '<p>[ <a href="#" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Print Label" target="_blank"><i class="fas fa-print gray-icon"></i> Print Label</a> ]</p>';
				}
				$action .= '<p>[ <a href="#"class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Book Shipment" data-toggle="modal" data-target="#ship-order-Modal" onclick="show_modal(\''.esc_html($customer_name).'\',\''.$phone.'\',\''.esc_html($address).'\',\''.esc_html($city).'\',\''.esc_html($state).'\',\''.$pincode.'\',\''.$data->order_id.'\',\''.esc_html($item_values).'\',\''.$ewbn.'\',\''.$shipment_type.'\',\''.$shipment_total.'\');"><i class="fas fa-truck gray-icon"></i> Book Shipment</a> ]</p>'; 
				  
				$order = wc_get_order( $data->order_id );
				$order_status  = $order->get_status();
				$order_edit_url = site_url().'/wp-admin/post.php?post='.$data->order_id.'&action=edit';
				if ($order_status != '') {
				//if ($order_status=='processing' || $order_status=='pending') {  
				  $datas .= '<tr>
					  <td><label class="checkbox">
						 <input type="checkbox"  name="order_id"  id="order_id[]" 
						 value="'.esc_html($shipment_id).'">
						<span class="checkmark"></span>
					  </td>
					  <td><div id="actioncls'.esc_html($data->order_id).'" >'.$action.'
					  </div>
					  <div id="ajax_actioncls'.esc_html($data->order_id).'" style="display:none;"></div>
					  </td>
					  <td><a href="'.$order_edit_url.'">'.esc_html($data->order_id).'</a></td>
					  <td>'.esc_html($order_status).'</td>
					  <td>'.esc_html($customer_name).'</td>
					  <td>'.esc_html(get_woocommerce_currency_symbol()).esc_html($order_total).'</td>
					  <td>'.esc_html(get_woocommerce_currency_symbol()).esc_html($shipment_total).'</td>
					  <td>'.esc_html($shipment_type).'</td>
					  <td>'.esc_html($shipment_mode).'</td>
					  <td>'.esc_html($pickup_address_id).'</td>';
				  if ($awbno != '' && $shipment_id != '') {
						$datas .= '<td><a class="tooltip-hover" tooltip-toggle="tooltip" data-placement="top" title="Track" href="'.$track_url.'" target="_blank">'.esc_html($awbno).'</a></td>';
				  } else { 
						$datas .= '<td>'.esc_html($awbno).'</td>';
				  }
				  $datas .= '<td>'.esc_html($courier_name).'</td>
							<td>'.esc_html($shipment_status).'</td>	
							</tr>';
				}
			}  
			$datas .='</tbody></table>'; 

			$jdatas['status'] = 1;
			$jdatas['total_count'] = $count;
			$jdatas['fetchdata'] = $datas;
			echo  json_encode($jdatas);
			wp_die();
		}

		add_action('wp_ajax_icarry_get_courier_estimate', 'icarry_get_courier_estimate_callback');
		function icarry_get_courier_estimate_callback() {
			global $wpdb;
			$jdata=array();
			$icarry_session_key = sanitize_text_field($_POST['api_token']);
			$weight = sanitize_text_field($_POST['weight']);
			$length = sanitize_text_field($_POST['length']);
			$breadth = sanitize_text_field($_POST['breadth']);
			$height = sanitize_text_field($_POST['height']);
			$shipment_type = sanitize_text_field($_POST['shipment_type']);
			$shipment_mode = sanitize_text_field($_POST['shipment_mode']);
			$d_pincode = sanitize_text_field($_POST['d_pincode']);
			$shipment_value = sanitize_text_field($_POST['shipment_value']);
			$o_country_code = sanitize_text_field($_POST['o_country_code']);
			$d_country_code = sanitize_text_field($_POST['d_country_code']);
			$o_pkup_address_id = sanitize_text_field($_POST['o_pkup_address_id']);
			$qry = $wpdb->get_row("SELECT pin FROM ".$wpdb->prefix."ela_pickup_address WHERE pickup_address_id='".$o_pkup_address_id."'");
			$o_pincode = $qry->pin;
			
			$shipment_data = array();
			$shipment_data['origin_pincode'] = $o_pincode;
			$shipment_data['destination_pincode'] = $d_pincode;
			$shipment_data['weight'] = $weight;
			$shipment_data['length'] = $length;
			$shipment_data['breadth'] = $breadth;
			$shipment_data['height'] = $height;
			$shipment_data['shipment_mode'] = $shipment_mode;
			$shipment_data['shipment_type'] = $shipment_type;
			$shipment_data['shipment_value'] = $shipment_value;
			$shipment_data['origin_country_code'] = $o_country_code;
			$shipment_data['destination_country_code'] = $d_country_code;			
		
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/get_estimate_by_courier&api_token=".$icarry_session_key;
			$qs = $shipment_data;
			$NUM_OF_ATTEMPTS = 1;
			$attempts = 0;	
			do {
				try {
					$o = wp_remote_post( $icarry_url, array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(),
								'body'        => $qs) );
					if (is_wp_error($o)) {
					  $sad = $o->get_error_message();
					  throw new Exception($sad);
					}
				} catch (Exception $e) {
					$attempts++;
					$jdata['status'] = 0;
					$jdata['data'] = '<pre>Could not get shipment cost estimate. Message: '.$e->getMessage().'</pre>'; 
					echo json_encode($jdata);
					wp_die();
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);			
			if (!isset($response['success']) || $response['success'] == 0) {
				// show error that icarry estimate api call was not successful
				$data = '<div class="row"><pre>'.print_r($response,1).'</pre></div>';
				$jdata['data'] = $data; 
				$jdata['status'] = 0;	
			} else {
				if (isset($response['estimate'])) {
					$estimate = $response['estimate'];
					if (sizeof($estimate)) {
						$curncy = esc_html(get_woocommerce_currency_symbol());	
						$data = '<div class="row">
									<div class="col-md-12">
										<div class="data-card">
											<table class="table table-bordered">
												<thead>
													<tr>
														<th>Select</th>
														<th>Courier Name</th>
														<th>Courier Cost</th>
													</tr>
												</thead>
												<tbody>';

						foreach ($estimate AS $est) {
							$data .= '<tr>
										<td><input type="radio" onclick="validate(\'cr\')" name="chosen_courier" id="courier_id_'.$est['courier_id'].'" value="'.$est['courier_id'].'"></td>
										<td>'.esc_html($est['courier_group_name']).'</td>		
										<td>'.$curncy.' '.esc_html($est['courier_cost']).'</td>
									  </tr>';
						}
					
						$data .= '				</tbody>
											</table>
										</div>
									</div>
								 </div>';
					} else {
						$data = 'No supported couriers found';
					}
					$jdata['data'] = $data; 
					$jdata['status'] = 1;
				}
			}
			
			echo json_encode($jdata);
			wp_die();
		}

		add_action('wp_footer', 'icarry_add_gooter'); 
		function icarry_add_gooter() { 
			echo '<div style="display:none">Shipping Powered by <a href="https://www.icarry.in">iCarry.in</a></div>'; 
		}
	
		add_action('wp_ajax_fetch_pickup_address_list', 'fetch_pickup_address_list_callback');
		function fetch_pickup_address_list_callback() {
			global $wpdb;
			$startPage = sanitize_text_field($_POST['getresult']);
			$PERPAGE_LIMIT = 50;
			$table_name = $table_name = $wpdb->prefix . 'ela_pickup_address';
			$myrow = $wpdb->get_results("SELECT id,pickup_address_id,nickname,phone,city,state,pin,address,country,contact_person,status from $table_name ORDER BY id ASC " );
			$count = count($myrow);
			$myrows = $wpdb->get_results("SELECT id,pickup_address_id,nickname,phone,city,state,pin,address,country,contact_person,status from $table_name limit $startPage,$PERPAGE_LIMIT" );
			$datas='<table class="table table-bordered table-hover">
					  <thead>
						 <tr>
							  <th>Pickup Address Id</th>
							  <th>Nickname</th>
							  <th>Contact Person</th>
							  <th>Email/Phone</th>
							  <th>Address</th>
							  <th>State/City</th>
							  <th>Country</th>
							  <th>Status</th>
						 </tr>
					  </thead>
					  <tbody>';
			foreach($myrows as $data) {
				if ($data->contact_person=='') { 
				  $nickname = 'NA'; 
				} else { 
				  $nickname = $data->nickname; 
				}				  
				if ($data->contact_person=='') { 
				  $contact_person = 'NA'; 
				} else { 
				  $contact_person = $data->contact_person; 
				}
				if ($data->country=='') { 
				  $country = 'NA'; 
				} else { 
				  $country = $data->country; 
				}
				if($data->state=='') { 
				  $state = 'NA'; 
				} else { 
				  $state = $data->state; 
				}
				if ($data->status=='1' ) {  
				  $status= '<span style="color:green">Active</span>'; 
				} else {  
				  $status = '<span style="color:red">Inactive</span>';
				}
			  
				$datas .= '<tr>
				  <td>'.esc_html($data->pickup_address_id).'</td>
				  <td>'.esc_html($nickname).'</td>
				  <td>'.esc_html($contact_person).'</td>
				  <td>'.esc_html($data->email).'&nbsp;/&nbsp'.esc_html($data->phone).'</td>
				  <td>'.esc_html($data->address).'</td>
				  <td>'.esc_html($state).'&nbsp;/&nbsp'.esc_html($data->city).'</td>				  
				  <td>'.esc_html($country).'</td>
				  <td>'.esc_html($status).'</td>
				  </tr>';
			}
			$datas .='</tbody></table>';
			$jdatas['status'] = 1;
			$jdatas['total_count'] = $count;
			$jdatas['fetchdata'] = $datas;
			echo  json_encode($jdatas);
			wp_die();
		}

		add_action( 'wp_ajax_get_estimate_domestic', 'get_estimate_domestic_callback' );
		function get_estimate_domestic_callback() {	
			global $wpdb;
			$icarry_session_key = sanitize_text_field($_GET['api_token']);
			$o_pincode = sanitize_text_field($_GET['org_pincode']);
			$d_pincode = sanitize_text_field($_GET['d_pincode']); 
			$wgt_in_gram = sanitize_text_field($_GET['wgt_in_gram']);
			if ($wgt_in_gram == '') {
				$wgt_in_gram = 500;
			}
			$length_in_cm = sanitize_text_field($_GET['length_in_cm']);
			if ($length_in_cm == '') {
				$length_in_cm = 10;
			}
			$breadth_in_cm = sanitize_text_field($_GET['breadth_in_cm']);
			if ($breadth_in_cm == '') {
				$breadth_in_cm = 10;
			}
			$height_in_cm = sanitize_text_field($_GET['height_in_cm']);
			if ($height_in_cm == '') {
				$height_in_cm = 10;
			}
			$shipment_value = sanitize_text_field($_GET['shipment_value']);
			if ($shipment_value == '') {
				$shipment_value = 10;
			}			
			$shipment_mode = sanitize_text_field($_GET['shipment_mode']);
			$shipment_type = sanitize_text_field($_GET['shipment_type']);

			$shipment_data = array();
			$shipment_data['origin_pincode'] = $_SESSION['o_pincode'] = $o_pincode;
			$shipment_data['destination_pincode'] = $_SESSION['d_pincode'] = $d_pincode;
			$shipment_data['weight'] = $_SESSION['wgt_in_gram'] = $wgt_in_gram;
			$shipment_data['length'] = $_SESSION['length_in_cm'] = $length_in_cm;
			$shipment_data['breadth'] = $_SESSION['breadth_in_cm'] = $breadth_in_cm;
			$shipment_data['height'] = $_SESSION['height_in_cm'] = $height_in_cm;
			$shipment_data['shipment_mode'] = $_SESSION['shipment_mode'] = $shipment_mode;
			$shipment_data['shipment_type'] = $_SESSION['shipment_type'] = $shipment_type;
			$shipment_data['shipment_value'] = $_SESSION['shipment_value'] = $shipment_value;
			$shipment_data['origin_country_code'] = $shipment_data['destination_country_code'] = 'IN';
			
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/get_estimate&api_token=".$icarry_session_key;
			$qs = $shipment_data;
			$NUM_OF_ATTEMPTS = 1;
			$attempts = 0;	
			do {
				try {
					$o = wp_remote_post( $icarry_url, array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(),
								'body'        => $qs) );
					if (is_wp_error($o)) {
					  $sad = $o->get_error_message();
					  throw new Exception($sad);
					}
				} catch (Exception $e) {
					$attempts++;
					show_message('Could not get shipment cost estimate. Message: '.$e->getMessage());
					exit;
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			
			if (!isset($response['success']) || $response['success']==0) {
				// show error that icarry estimate api call was not successful
				$data = '<div class="row"><pre>'.print_r($response,1).'</pre></div>';
				$jdata['data'] = $data; 
				$jdata['status'] = 0;	
			} else {
				if (isset($response['estimate'])) {
					$estimate = $response['estimate'];
					$estimate_subitems = $estimate['total_subitems'];
					
					$curncy = esc_html(get_woocommerce_currency_symbol());	
					$data = '<div class="row">
								   <div class="col-md-12">
										 <div class="data-card"><table class="table table-bordered">
												<thead>
												  <tr>
													  <th>Total</th>
													  <td>&nbsp;&nbsp;</td>	
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate['total'],2)).'</td>
												  </tr>
												  <tr>
													  <td>&nbsp;&nbsp;</td>	
													  <th>Shipping</th>
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate_subitems['shipping'],2)).'</td>
												  </tr>
												  <tr>
													  <td>&nbsp;&nbsp;</td>
													  <th>Surcharge</th>
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate_subitems['surcharge'],2)).'</td>
												  </tr>										  
												  <tr>
													  <td>&nbsp;&nbsp;</td> 
													  <th>Fuel Surcharge</th>
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate_subitems['fsc'],2)).'</td>
												  </tr>
												  <tr>
													  <td>&nbsp;&nbsp;</td>
													  <th>COD Fee</th>
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate_subitems['cod'],2)).'</td>
												  </tr>										  
												  <tr>
													  <td>&nbsp;&nbsp;</td>	
													  <th>GST</th>
													  <td>'.$curncy.' '.esc_html(number_format_i18n($estimate_subitems['tax'],2)).'</td>
												  </tr>
												  <tr>
													  <th>Estimated Days for Delivery</th>
													  <td>&nbsp;&nbsp;</td>	
													  <td>'.esc_html($estimate['estimated_days']).'</td>
												  </tr>										  
												  </thead>
											  </table>
										  </div>
										</div>
								   </div>';
					$jdata['data'] = $data; 
					$jdata['status'] = 1;
				}
			}
			
			echo json_encode($jdata);
			wp_die();
		}
			

		function icarry_home() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			ob_start();
			include('admin/icarry_home.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;			
		}

		function estimate_shipment_cost_international() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			ob_start();
			include('admin/estimate_international.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;			
		}

		function faq() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			ob_start();
			include('admin/faq.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;			
		}
		
		function estimate_shipment_cost_domestic() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			ob_start();
			include('admin/estimate_domestic.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		}
		
		function icarry_plugin_options() {
			if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			// variables for the field and option names 
			$hidden_field_name = 'mt_submit_hidden';
			
			$icarry_setting = get_option('icarry_shipping');
			$api_username = esc_attr($icarry_setting['api_username']);
			$api_key = esc_attr($icarry_setting['api_key']);
			$pickup_address_id = esc_attr($icarry_setting['pickup_address_id']);
			$return_address_id = esc_attr($icarry_setting['return_address_id']);
			$cod_payment_code = esc_attr($icarry_setting['cod_payment_code']);
			$shipped_order_status = esc_attr($icarry_setting['shipped_order_status']);
			$cancelled_order_status = esc_attr($icarry_setting['cancelled_order_status']);
			$delivered_order_status = esc_attr($icarry_setting['delivered_order_status']);
			$booking_notify = esc_attr($icarry_setting['booking_notify']);
			$product_descriptor = esc_attr($icarry_setting['product_descriptor']);
			if (isset($icarry_setting['shipment_weight'])) $shipment_weight = esc_attr($icarry_setting['shipment_weight']);
			else $shipment_weight = 500;
			if (isset($icarry_setting['shipment_length'])) $shipment_length = esc_attr($icarry_setting['shipment_length']);
			else $shipment_length = 10;
			if (isset($icarry_setting['shipment_breadth'])) $shipment_breadth = esc_attr($icarry_setting['shipment_breadth']);
			else $shipment_breadth = 10;
			if (isset($icarry_setting['shipment_height'])) $shipment_height = esc_attr($icarry_setting['shipment_height']);
			else $shipment_height = 10;
			if (isset($icarry_setting['shipment_type'])) $shipment_type = esc_attr($icarry_setting['shipment_type']);
			else $shipment_type = 'P';
			if (isset($icarry_setting['shipment_mode'])) $shipment_mode = esc_attr($icarry_setting['shipment_mode']);
			else $shipment_mode = 'S';
				
			// See if the user has updated settings
			// If they did, this hidden field will be set to 'Y'
			if( isset($_POST[ $hidden_field_name ]) && sanitize_text_field($_POST[ $hidden_field_name ]) == 'Y' ) {
				// Read their posted value
				$icarry_setting['api_username'] = sanitize_text_field($_POST['api_username']);
				$icarry_setting['api_key'] = sanitize_text_field($_POST['api_key']);
				$icarry_setting['pickup_address_id'] = sanitize_text_field($_POST['pickup_address_id']);
				$icarry_setting['return_address_id'] = sanitize_text_field($_POST['return_address_id']);
				$icarry_setting['cod_payment_code'] = sanitize_text_field($_POST['cod_payment_code']);
				$icarry_setting['shipped_order_status'] = sanitize_text_field($_POST['shipped_order_status']);
				$icarry_setting['cancelled_order_status'] = sanitize_text_field($_POST['cancelled_order_status']);
				$icarry_setting['delivered_order_status'] = sanitize_text_field($_POST['delivered_order_status']);
				$icarry_setting['product_descriptor'] = sanitize_text_field($_POST['product_descriptor']);
				$icarry_setting['shipment_weight'] = sanitize_text_field($_POST['shipment_weight']);
				$icarry_setting['shipment_length'] = sanitize_text_field($_POST['shipment_length']);
				$icarry_setting['shipment_breadth'] = sanitize_text_field($_POST['shipment_breadth']);
				$icarry_setting['shipment_height'] = sanitize_text_field($_POST['shipment_height']);
				$icarry_setting['shipment_type'] = sanitize_text_field($_POST['shipment_type']);
				$icarry_setting['shipment_mode'] = sanitize_text_field($_POST['shipment_mode']);

				// Save the posted value in the database
				update_option( 'icarry_shipping', $icarry_setting);

				// Put a "settings saved" message on the screen
				echo '<div class="updated"><p><strong> Settings Saved. </strong></p></div>';
			} else {
				// Now display the settings editing screen
				echo '<div class="wrap">
				<h2> iCarry.in Plugin Settings </h2>
				
				<form name="form1" method="post" action="">
				<input type="hidden" name="'.$hidden_field_name.'" value="Y">

				<table class="form-table" role="presentation">
				<tbody>
					<tr>
					<th scope="row"><label>API username: </label></th>
					<td><input name="api_username" type="text" value="'.$api_username.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>API Secret Key: </label></th>
					<td><input name="api_key" type="text" value="'.$api_key.'" class="regular-text"></td>
					</tr>
					
					<tr>
					<th scope="row"><label>Pickup Address Id: </label></th>
					<td><input name="pickup_address_id" type="text" value="'. $pickup_address_id.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Return Address Id: </label></th>
					<td><input name="return_address_id" type="text" value="'.$return_address_id.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Payment Code for COD Payment: </label></th>
					<td><input name="cod_payment_code" type="text" value="'. $cod_payment_code.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>post_status When Shipped:</label></th>
					<td><input name="shipped_order_status" type="text" value="'. $shipped_order_status.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>post_status When Cancelled:</label></th>
					<td><input name="cancelled_order_status" type="text" value="'. $cancelled_order_status.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>post_status When Delivered: </label></th>
					<td><input name="delivered_order_status" type="text" value="'. $delivered_order_status.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Shipment Descriptor: </label></th>
					<td><input name="product_descriptor" type="text" value="'.$product_descriptor.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Default Shipment Weight (in grams): </label></th>
					<td><input name="shipment_weight" type="text" value="'.$shipment_weight.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Default Shipment Length (in centimetres): </label></th>
					<td><input name="shipment_length" type="text" value="'.$shipment_length.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Default Shipment Breadth (in centimetres): </label></th>
					<td><input name="shipment_breadth" type="text" value="'.$shipment_breadth.'" class="regular-text"></td>
					</tr>
					
					<tr>
					<th scope="row"><label>Default Shipment Height (in centimetres): </label></th>
					<td><input name="shipment_height" type="text" value="'.$shipment_height.'" class="regular-text"></td>
					</tr>

					<tr>
					<th scope="row"><label>Default Shipment Type (P=Prepaid | C=COD): </label></th>
					<td><input name="shipment_type" type="text" value="'.$shipment_type.'" class="regular-text"></td>
					</tr>
					
					<tr>
					<th scope="row"><label>Default Shipment Mode (S=Standard | E=Express): </label></th>
					<td><input name="shipment_mode" type="text" value="'.$shipment_mode.'" class="regular-text"></td>
					</tr>					
				</tbody>
				</table>
				<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Save Changes" />
				</p>

				</form>
				</div>';
			}
		}

		// run the code that should execute with this action is triggered
		add_action( 'wp_ajax_icarry_cancel_shipment', 'icarry_cancel_shipment_callback' );
		function icarry_cancel_shipment_callback() {
			global $wpdb;
			$icarry_session_key = sanitize_text_field($_POST['api_token']);
			$shipment_id = sanitize_text_field($_POST['shipment_id']);
			$order_id = sanitize_text_field($_POST['order_id']);			
		
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/cancel&api_token=".$icarry_session_key;				
			$qs = array();
			$qs['shipment_id'] = $shipment_id;
			
			$NUM_OF_ATTEMPTS = 1;
			$attempts = 0;	
			do {
				try {
					$o = wp_remote_post( $icarry_url, array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(),
								'body'        => $qs) );
					if (is_wp_error($o)) {
					  $sad = $o->get_error_message();
					  throw new Exception($sad);
					}
				} catch (Exception $e) {
					$attempts++;
					
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			if (!isset($response['success'])) {
				$data = '<div class="row"><pre>'.print_r($response,1).'</pre></div>';
				$jdata['data'] = $data; 
				$jdata['status'] = 0;
			} else {
				delete_post_meta($order_id, 'shipment_id_POST_META_ID');
				delete_post_meta($order_id, 'shipment_awb_POST_META_ID');
				delete_post_meta($order_id, 'shipment_courier_id_POST_META_ID');
				delete_post_meta($order_id, 'shipment_type_POST_META_ID');
				delete_post_meta($order_id, 'shipment_mode_POST_META_ID');
				delete_post_meta($order_id, 'shipment_status_POST_META_ID');
				delete_post_meta($order_id, 'shipment_status_id_POST_META_ID');
				delete_post_meta($order_id, 'shipment_added_date_POST_META_ID');
				delete_post_meta($order_id, 'shipment_picked_date_POST_META_ID');
				delete_post_meta($order_id, 'shipment_delivered_date_POST_META_ID');
				delete_post_meta($order_id, 'shipment_pickup_id_POST_META_ID');
			
				$wpdb->query("UPDATE ".$wpdb->prefix."ela_order_shipment SET shipment_status_id=7,updated_at=NOW() WHERE shipment_id='".$shipment_id."' AND order_id='".$order_id."'");
			
				$note = sprintf('Shipment Cancellation successful for Order Id %s :: iCarry.in Shipment Id %s', $order_id, $shipment_id);
				$order = wc_get_order( $order_id );	
				$order->add_order_note( $note );
				add_post_meta($order_id, 'icarry_shipment_action_message', $note);
				
				$data = '<div class="row"><pre>'.$note.'</pre></div>';
				$jdata['data'] = $data; 
				$jdata['status'] = 1;					
			}
			echo json_encode($jdata);
			wp_die();			
		}
		
		
	}

	function icarry_install() {
		$icarry_module_version = '2.0';
		
		$DEFAULT_MODULE_SETTINGS = [
			'name' => 'iCarry.in Shipping',
			'api_username' => '',
			'api_key' => '',
			'pickup_address_id' => '',
			'return_address_id' => '',
			'registration_url' => 'https://www.icarry.in/register',
			'shipped_order_status' => 'wc-pending',
			'cancelled_order_status' => 'wc-cancelled',
			'delivered_order_status' => 'wc-completed',
			'cod_payment_code' => 'cod',
			'product_descriptor' => 'Health, Beauty, Fashion',
			'booking_notify' => 1,
			'status' => 1,
			'shipment_weight' => 500,
			'shipment_length' => 10,
			'shipment_breadth' => 10,
			'shipment_height' => 10,
			'shipment_type' => 'P',
			'shipment_mode' => 'S'
		];
		
		add_option( 'icarry_module_version', $icarry_module_version );
		add_option( 'icarry_language_id', '1');
		add_option( 'icarry_shipping', $DEFAULT_MODULE_SETTINGS);
			
		global $wpdb;
		$table1 = $wpdb->prefix . 'ela_pickup_address';
		$table2 = $wpdb->prefix . 'ela_order_shipment';
		$table3 = $wpdb->prefix . 'ela_shipment_status';
		$charset = $wpdb->get_charset_collate();
		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql1 = "CREATE TABLE $table1 (
			`pickup_address_id` INT NOT NULL, 
			`nickname` VARCHAR(25), 
			`phone` VARCHAR(12) NOT NULL , 
			`city` VARCHAR(20) NOT NULL , 
			`state` VARCHAR(20) NOT NULL , 
			`pin` VARCHAR(10) NOT NULL , 
			`address` TEXT NOT NULL , 
			`country` VARCHAR(50) NOT NULL , 
			`contact_person` VARCHAR(50) NOT NULL , 
			`email` VARCHAR(50) NOT NULL , 
			`status` INT NOT NULL , 
			PRIMARY KEY (`pickup_address_id`)
		) $charset_collate;";
		dbDelta( $sql1 );
		
		$sql2 = "CREATE TABLE $table2 (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`shipping_name` varchar(255) NOT NULL DEFAULT '',
			`shipping_phone` varchar(25) NOT NULL DEFAULT '',
			`shipping_pincode` varchar(10) NOT NULL DEFAULT '',
			`shipping_address` varchar(500) NOT NULL DEFAULT '',
			`shipping_city` VARCHAR(100) NOT NULL DEFAULT '',
			`shipping_state` VARCHAR(30) NOT NULL DEFAULT '',
			`shipping_payment_method` varchar(10),
			`shipment_total` decimal(15,2) DEFAULT '0',
			`shipment_weight` decimal(15,2) DEFAULT '500',
			`shipment_length` decimal(15,2) DEFAULT '10',
			`shipment_breadth` decimal(15,2) DEFAULT '10',
			`shipment_height` decimal(15,2) DEFAULT '10',
			`shipment_cost` decimal(15,2) DEFAULT '0',
			`shipment_type` varchar(10) NOT NULL DEFAULT 'Prepaid',
			`shipment_mode` varchar(20) NOT NULL DEFAULT 'Standard',
			`shipment_id` int(11) NOT NULL,
			`awb` varchar(50) NOT NULL,
			`courier_id` varchar(10) NOT NULL,
			`courier_name` varchar(50) NOT NULL,
			`shipment_status_id` int(11) NOT NULL,
			`ewbn` varchar(100) NOT NULL DEFAULT '',
			`invoice_name` varchar(100) NOT NULL DEFAULT '',
			`pickup_address_id` varchar(100) NOT NULL,
			`shipment_details` varchar(200) NOT NULL DEFAULT '',
			`custom_1` varchar(50) DEFAULT '',
			`custom_2` varchar(50) DEFAULT '',
			`custom_3` varchar(50) DEFAULT '',
			`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
			`picked_on` datetime,
			`delivered_on` datetime,
			`my_notes` VARCHAR(500) DEFAULT '',
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql2 );
		
		$sql3 = "CREATE TABLE $table3 (
			`status_id` int(11) NOT NULL,
			`status_name` varchar(50) NOT NULL,
			PRIMARY KEY (status_id)
		) $charset_collate;";
		dbDelta( $sql3 );
		
		$sql4 = "INSERT INTO $table3 (`status_id`, `status_name`) 
					VALUES ('1','Pending Pickup'), ('2','Processing'),('3','Shipped'),
						('7','Cancelled'),('12','Damaged'),('14','Lost'),('21','Delivered'),
						('22','In Transit'),('23','Returned to Origin'),('24','Manifested'),
						('25','Waiting Pickup'),('26','Out For Delivery'),('27','Pending Return')";
		$wpdb->query($sql4);
	}

	function icarry_install_data() {}

	function icarry_uninstall() {
		/* delete saved options 
		delete_option('icarry_module_version');
		delete_option('icarry_language_id');
		delete_option('icarry_shipping');
		
		global $wpdb;
		$table1 = $wpdb->prefix . 'ela_pickup_address';
		$table2 = $wpdb->prefix . 'ela_order_shipment';
		$table3 = $wpdb->prefix . 'ela_shipment_status';
		$wpdb->query("DROP TABLE ".$table1);
		$wpdb->query("DROP TABLE ".$table2);
		$wpdb->query("DROP TABLE ".$table3);
		*/
	}
	
	function icarry_admin_menu() { 
		add_menu_page('ICARRY', 'ICARRY', 'manage_options', 'icarry_home','icarry_home',plugins_url( 'icarry-in-shipping-tracking/images/icarry-icon.png' ),4);
		add_submenu_page( 'icarry_home', 'My Settings', 'My Settings', 'manage_options', 'icarry-shipping-config', 'icarry_plugin_options' );
		add_submenu_page( 'icarry_home', __('Estimate Cost (India)', ''), __('Estimate Cost (India)', ''), 'manage_options', 'estimate_cost_domestic','estimate_shipment_cost_domestic');
		add_submenu_page( 'icarry_home', __('Estimate Cost (International)', ''), __('Estimate Cost (International)', ''), 'manage_options', 'estimate_cost_international','estimate_shipment_cost_international');
		add_submenu_page( 'icarry_home', __('My Shipments', ''), __('My Shipments', ''), 'manage_options', 'my_icarry_shipments',array(&$this,'list_order'));
		add_submenu_page( 'icarry_home', __('My Pickup Points', ''), __('My Pickup Points', ''), 'manage_options', 'my_pickup_address',array(&$this,'list_my_pickup_points'));
		add_submenu_page( 'icarry_home', 'FAQ', 'FAQ', 'manage_options', 'icarry_faq', 'faq');
	}

	function list_order() {
		global $wpdb;
		$currentPage = 1;
		$PERPAGE_LIMIT = 20;
		$startPage = 0;
		if (isset($_GET['pageNumber'])) {
			$currentPage = sanitize_text_field($_GET['pageNumber']);
			$startPage = ($currentPage-1)*$PERPAGE_LIMIT;
			if($startPage < 0) $startPage = 0;
		}
    
		if(isset($_REQUEST['action'])) {
			$action = sanitize_text_field($_REQUEST['action']);
		} else {
			$action = '';
		}
    
		if (isset($_GET['search_order'])) {
			$search_order = sanitize_text_field($_GET['search_order']);
			$query ="SELECT a.order_id,f.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount,i.meta_value as payment_method,k.meta_value as bname,l.meta_value as phoneno ,n.meta_value as blname FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_id='".$search_order."' AND a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' group by a.order_id ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
		} else if (isset($_GET['search_awb'])) {
			$search_awb = sanitize_text_field($_GET['search_awb']);
			$query ="SELECT a.order_id,a.shipping_pincode,'',shipment_total as total_amount,shipment_type as payment_method, shipping_name,shipping_phone as phoneno FROM ".$wpdb->prefix."ela_order_shipment a WHERE a.awb='".$search_awb."' ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
		} else if (isset($_GET['filter_status'])) {
			$filter_status = sanitize_text_field($_GET['filter_status']);
			if (in_array($filter_status,array(1,2,3,7,12,14,21,22,23,24,25,26,27))) {
				$query ="SELECT a.order_id,f.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount,i.meta_value as payment_method,k.meta_value as bname,l.meta_value as phoneno,n.meta_value as blname FROM ".$wpdb->prefix."ela_order_shipment a  JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method' and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.shipment_status_id='".$filter_status."' ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
			} else {
				$query ="SELECT a.order_id,f.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount,i.meta_value as payment_method,k.meta_value as bname,l.meta_value as phoneno ,n.meta_value as blname FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' and a.order_id NOT IN (SELECT order_id FROM ".$wpdb->prefix."ela_order_shipment ) ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
			}
		} else {
			//$query ="SELECT a.order_id,f.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount,i.meta_value as payment_method,k.meta_value as bname,l.meta_value as phoneno ,n.meta_value as blname FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' AND ps.post_status in ('wc-processing') group by a.order_id ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
			$query ="SELECT a.order_id,f.meta_value as shipping_pincode,f.meta_value as billing_pincode,g.meta_value as total_amount,i.meta_value as payment_method,k.meta_value as bname,l.meta_value as phoneno ,n.meta_value as blname FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' GROUP BY a.order_id ORDER BY a.order_id DESC limit $startPage,$PERPAGE_LIMIT";
		}
		//show_message($query);
		$myrows = $wpdb->get_results($query);    
		extract($myrows);
		ob_start();
		include('admin/order.php');
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;		
	}
	
	function list_my_pickup_points() {
		global $wpdb;
		$startPage = 0;
		$currentPage = 1;
		$PERPAGE_LIMIT = 50;
		if (isset($_GET['pageNumber'])) {
			$currentPage = sanitize_text_field($_GET['pageNumber']);
			$startPage = ($currentPage-1)*$PERPAGE_LIMIT;
			if($startPage < 0) $startPage = 0;
		}
		$table_name = $wpdb->prefix . 'ela_pickup_address';
		if (isset($_REQUEST['action'])) {
		  $action = sanitize_text_field($_REQUEST['action']);
		} else {
		  $action = '';
		}

		if ($action=='create') {
			// TBD
		} else if ($action=='edit'){
			// TBD
		} else if ($action=='refresh') {
			$module_setting = get_option('icarry_shipping');;	
			$api_username = $module_setting['api_username'];
			$api_key = $module_setting['api_key'];

			$icarry_session_key = '';
			
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_login";			
			$qs = array('username' => $api_username, 'key' => $api_key);

			$NUM_OF_ATTEMPTS = 1;
			$attempts = 0;	
			do {
				try {
					$o = wp_remote_post( $icarry_url, array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(),
								'body'        => $qs) );
					if (is_wp_error($o)) {
					  $sad = $o->get_error_message();
					  throw new Exception($sad);
					}
				} catch (Exception $e) {
					$attempts++;
					exit;
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			error_log("Login response: ".print_r($response,1));

			if (!isset($response['success']) && !isset($response['api_token'])) {
				/* show error that icarry api login was not successful */
				show_message('iCarry.in API Login Failed : '.print_r($response,1));		
				exit;
			}
			$icarry_session_key = $response['api_token'];
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_pickup/list&api_token=".$icarry_session_key;
			$qs = array();			
			$NUM_OF_ATTEMPTS = 1;
			$attempts = 0;	
			do {
				try {
					$o = wp_remote_post( $icarry_url, array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(),
								'body'        => $qs) );
					if (is_wp_error($o)) {
					  $sad = $o->get_error_message();
					  throw new Exception($sad);
					}
				} catch (Exception $e) {
					$attempts++;
					show_message('iCarry.in Pickup Address Sync Failed : '.$e->getMessage());		
					exit;
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			error_log("Pickup Address Sync response: ".print_r($response,1));
			
			if (isset($response['success'])) {
				if (isset($response['msg'])) {
					foreach ($response['msg'] AS $addr) {
						$pkup_id = $addr['pickup_address_id'];
						$nickname = esc_attr($addr['nickname']);
						$contact_name = esc_attr($addr['contact_name']);
						$address = esc_attr($addr['address']);
						$city = esc_attr($addr['city']);
						$state = esc_attr($addr['state']);
						$postcode = esc_attr($addr['postcode']);
						$country = esc_attr($addr['country']);
						$contact_phone = $addr['contact_phone'];
						$contact_email = $addr['contact_email'];
						$status = esc_attr($addr['status']);
						$sql20 = "INSERT INTO " . $wpdb->prefix . "ela_pickup_address SET pickup_address_id='".$pkup_id."',nickname='".$nickname."',phone='".$contact_phone."',email='".$contact_email."',city='".$city."',state='".$state."',pin='".$postcode."',country='".$country."',contact_person='".$contact_name."',address='".$address."',status='".$status."' ON DUPLICATE KEY UPDATE nickname='".$nickname."',phone='".$contact_phone."',email='".$contact_email."',city='".$city."',state='".$state."',pin='".$postcode."',country='".$country."',contact_person='".$contact_name."',address='".$address."',status='".$status."'";
						$wpdb->query( $sql20 );					
					}
				}				
			}
			$myrows = $wpdb->get_results("SELECT pickup_address_id,nickname,phone,city,state,pin,address,country,contact_person,email,status from $table_name limit $startPage,$PERPAGE_LIMIT" );
			extract($myrows);
			ob_start();
			include('admin/pickup_address.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		} else {
			$myrows = $wpdb->get_results("SELECT pickup_address_id,nickname,phone,city,state,pin,address,country,contact_person,email,status from $table_name limit $startPage,$PERPAGE_LIMIT" );
			extract($myrows);
			ob_start();
			include('admin/pickup_address.php');
			$output = ob_get_contents();
			ob_end_clean();
			echo $output;
		}
	}	
}

$GLOBALS['icarry_courier'] = new Icarry_Plugin();
}