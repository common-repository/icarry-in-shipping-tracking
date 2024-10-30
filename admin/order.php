<?php
//session_start();
require_once('api_token.php');
$icarry_session_key = $_SESSION['icarry_api_token'];
global $wpdb;
$shipping_sync_status_url = site_url().'/wp-admin/admin.php?page=my_icarry_shipments&action=sync_status';
$print_labels_url = "https://www.icarry.in/index.php?route=account/ela_shipment/print_all_labels&api_token=".$icarry_session_key;
$refresh_url = site_url().'/wp-admin/admin.php?page=my_icarry_shipments&action=refresh';
$filter_url = site_url().'/wp-admin/admin.php?page=my_icarry_shipments&filter_status=';
$succ_alert_img = esc_url( plugins_url( '../images/checked.png', __FILE__ ) );

//show_message(print_r($_POST,1));
if (isset($_POST['cmd'])) {	
	if (isset($_POST['cmd']) && $_POST['cmd']=='create_shipment') {

		if (isset($_SESSION['icarry_api_token']) && $_SESSION['icarry_api_token'] != '') {
			$oid = $_POST['order_id'];			
			$shipment_data = array();
			$shipment_data['channel_id'] = 1;
			$shipment_data['pickup_address_id'] = $_POST['pickup_address_id'];
			$shipment_data['return_address_id'] = $_POST['return_address_id'];
			$shipment_data['consignee']['name'] = $_POST['name'];
			$shipment_data['consignee']['mobile'] = $_POST['phone'];
			$shipment_data['consignee']['address'] = $_POST['address'];
			$shipment_data['consignee']['city'] = $_POST['city'];
			$shipment_data['consignee']['pincode'] = $_POST['pincode'];
			$shipment_data['consignee']['country_code'] = 'IN';
			$shipment_data['consignee']['state'] = $_POST['state'];
			$type = $shipment_data['parcel']['type'] = $_POST['shipment_type'];
			$shipment_data['parcel']['value'] = $_POST['shipment_value'];
			$shipment_data['parcel']['contents'] = $_POST['shipment_details'];		
			$shipment_data['parcel']['ewbn'] = $_POST['ewbn'];	
			$shipment_data['parcel']['dimensions']['length'] = $_POST['length_in_cm'];
			$shipment_data['parcel']['dimensions']['breadth'] = $_POST['breadth_in_cm'];
			$shipment_data['parcel']['dimensions']['height'] = $_POST['height_in_cm'];
			$shipment_data['parcel']['dimensions']['unit'] = 'cm';
			$shipment_data['parcel']['weight']['unit'] = 'gm';
			$shipment_data['parcel']['weight']['weight'] = $_POST['wgt_in_gram'];
			if (isset($_POST['booking_option']) && $_POST['booking_option']=='choose_courier') $shipment_data['courier_id'] = $_POST['chosen_courier'];
				
			if ($_POST['shipment_mode'] == 'S') {
				$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/add&api_token=".$icarry_session_key;
				$mode = 'Standard';	
			} else if ($_POST['shipment_mode'] == 'E') {
				$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/add_express&api_token=".$icarry_session_key;
				$mode = 'Express';
			}
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
					//show_message("Shipment booking failed. Error = ".$e->getMessage());
					$_SESSION["errmsg"]="Shipment booking failed. Error = ".$e->getMessage();
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			//show_message(print_r($response,1));
			if (!isset($response['success'])) {
				/* show error that icarry api shipment creation was not successful */
				if (isset($response['error'])) {
					//show_message('iCarry.in Shipment Booking Failed for Order Id '.$oid.': '.$response['error']);
					$_SESSION["errmsg"]='iCarry.in Shipment Booking Failed for Order Id '.$oid.': '.$response['error'];
				}	
			} else {
				/* icarry shipment creation was successful */
				add_post_meta($oid, 'shipment_id_POST_META_ID', $response['shipment_id'],true);
				add_post_meta($oid, 'shipment_awb_POST_META_ID', $response['awb'],true);
				add_post_meta($oid, 'shipment_courier_id_POST_META_ID', $response['courier_id'],true);
				if ($type == 'C') $type = 'COD';
				else if ($type == 'P') $type = 'Prepaid';
				add_post_meta($oid, 'shipment_type_POST_META_ID', $type,true);
				add_post_meta($oid, 'shipment_mode_POST_META_ID', $mode,true);
				add_post_meta($oid, 'shipment_status_POST_META_ID', 'Pending Pickup',true);
				add_post_meta($oid, 'shipment_status_id_POST_META_ID', '1',true);
				add_post_meta($oid, 'shipment_added_date_POST_META_ID', date('d/m/Y'),true);
				add_post_meta($oid, 'shipment_picked_date_POST_META_ID', '',true);
				add_post_meta($oid, 'shipment_delivered_date_POST_META_ID', '',true);
				add_post_meta($oid, 'shipment_pickup_id_POST_META_ID', $response['pickup_id'],true);
				$sql = "INSERT INTO ".$wpdb->prefix."ela_order_shipment 
							SET order_id='".$oid."',shipping_name='".$_POST['name']."',
								shipping_phone='".$_POST['phone']."',shipping_pincode='".$_POST['pincode']."',
								shipping_address='".$_POST['address']."',shipping_city='".$_POST['city']."',
								shipping_state='".$_POST['state']."',shipping_payment_method='',
								shipment_total='".$_POST['shipment_value']."',shipment_weight='".$_POST['wgt_in_gram']."',
								shipment_length='".$_POST['length_in_cm']."',shipment_breadth='".$_POST['breadth_in_cm']."',
								shipment_height='".$_POST['height_in_cm']."',shipment_cost='".$response['cost_estimate']."',
								shipment_type='".$type."',shipment_mode='".$mode."',
								shipment_id='".$response['shipment_id']."',awb='".$response['awb']."',
								courier_name='".$response['courier_name']."',shipment_status_id='1',
								courier_id='".$response['courier_id']."',ewbn='".$_POST['ewbn']."',invoice_name='',
								pickup_address_id='".$_POST['pickup_address_id']."',shipment_details='".$_POST['shipment_details']."',
								created_at=NOW(),updated_at=NOW(),my_notes='".esc_html($_POST['my_notes'])."'";
				$wpdb->query($sql);
				
				$note = sprintf('Your Order Id %d has been successfully booked for shipping. Tracking url - %s', $oid, $response['tracking_url']);	
				$orderr = wc_get_order( $oid );	
				$orderr->add_order_note( $note );			
				add_post_meta($oid, 'icarry_shipment_action_message', $note);
				//show_message($note);
				$_SESSION["succmsg"]=$note;
			}		
		} else {
			//show_message("Icarry Login Failed due to empty API TOKEN. Shipment creation for Order Id ".$oid." was not attempted.");
			$_SESSION["errmsg"]="Icarry Login Failed due to empty API TOKEN. Shipment creation for Order Id ".$oid." was not attempted.";
		}
	}
}

if (isset($_GET['action'])) {
	$actn = sanitize_text_field($_GET['action']);
	if ($actn=='sync_status') {
		$ids_arr = explode(",",sanitize_text_field($_GET['shipment_id']));
		$icarry_shipments = array();
		foreach ( $ids_arr as $order_id ) {
			$icarry_shipments[] = $order_id;			
		}		
		
		if (sizeof($icarry_shipments) > 0) {
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_shipment/shipment_sync&api_token=".$icarry_session_key;
			$qs = array();
			$qs['shipment_ids'] = $icarry_shipments;
				
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
					show_message($e->getMessage());
				}
				break;
			} while($attempts < $NUM_OF_ATTEMPTS);	
			$response = json_decode($o['body'], true);
			//error_log("Sync response: ".print_r($response,1));
				
			$processed_ids = array();
			if (isset($response['success'])) {
				if (isset($response['msg'])) {
					foreach ($response['msg'] AS $shipment) {
						if ($shipment['status'] != 0) {
							$order_id = 0;	
							$rrow = $wpdb->get_row("SELECT order_id, awb FROM ".$wpdb->prefix."ela_order_shipment WHERE shipment_id='".$shipment['shipment_id']."'");
							$order_id = $rrow->order_id;
							$awb = $rrow->awb;
							if ($order_id) {
								$sqladd = '';		
								if (isset($shipment['date_delivered'])) {
									update_post_meta($order_id, 'shipment_delivered_date_POST_META_ID', $shipment['date_delivered']);
									$note = sprintf('iCarry.in AWB %s DELIVERED for Order Id %s', $awb, $order_id);
									$order = wc_get_order( $order_id );
									$order->add_order_note( $note );
									$sqladd .= "delivered_on='".$shipment['date_delivered']."',";
								}
								if (isset($shipment['date_picked'])) {
									update_post_meta($order_id, 'shipment_picked_date_POST_META_ID', $shipment['date_picked']);
									$sqladd .= "picked_on='".$shipment['date_picked']."',";
								}
								if (isset($shipment['status'])) {
									update_post_meta($order_id, 'shipment_status_id_POST_META_ID', $shipment['status']);
									$status = '';
									if ( $shipment['status'] == 1 ) $status = 'Pending Pickup';
									else if ( $shipment['status'] == 2 ) $status = 'Processing';
									else if ( $shipment['status'] == 3 ) $status = 'Shipped';
									else if ( $shipment['status'] == 7 ) $status = 'Cancelled';
									else if ( $shipment['status'] == 12 ) $status = 'Damaged';
									else if ( $shipment['status'] == 14 ) $status = 'Lost';
									else if ( $shipment['status'] == 21 ) $status = 'Delivered';
									else if ( $shipment['status'] == 22 ) $status = 'In Transit';
									else if ( $shipment['status'] == 23 ) $status = 'Returned to Origin';
									else if ( $shipment['status'] == 24 ) $status = 'Manifested';
									else if ( $shipment['status'] == 25 ) $status = 'Waiting Pickup';
									else if ( $shipment['status'] == 26 ) $status = 'Out For Delivery';
									else if ( $shipment['status'] == 27 ) $status = 'Pending Return';
									update_post_meta($order_id, 'shipment_status_POST_META_ID', $status);
									$wpdb->query("UPDATE ".$wpdb->prefix."ela_order_shipment SET ".$sqladd."shipment_status_id='".$shipment['status']."' WHERE shipment_id='".$shipment['shipment_id']."'");
								}
								$processed_ids[] = $shipment['shipment_id'];
								$_SESSION["succmsg"]='Sync Status completed.';
							}							
						}
					}
				}				
			}		
		}
	}	
}


$pageNumber=1;
if (isset($_GET['pageNumber'])) {
	$pageNumber = sanitize_text_field($_GET['pageNumber']);
	$filter_url = site_url().'/wp-admin/admin.php?page=my_icarry_shipments&pageNumber='.sanitize_text_field($_GET['pageNumber']).'&filter_status=';
}
$search_url = site_url().'/wp-admin/admin.php?page=my_icarry_shipments';

$link ='';
$total = 0;
if (isset($_GET['search_order'])) {
	$search_order = sanitize_text_field($_GET['search_order']);
	$link ='&search_order='.$search_order;	
	$post_qry = $wpdb->get_row("SELECT count(a.order_id) as cnt FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_id='".$search_order."' AND a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' group by a.order_id");		
} else if (isset($_GET['search_awb'])) {
	$search_awb = sanitize_text_field($_GET['search_awb']);
	$link ='&search_awb='.$search_awb;
	$post_qry = $wpdb->get_row("SELECT count(a.order_id) as cnt FROM ".$wpdb->prefix."ela_order_shipment a WHERE a.awb='".$search_awb."'");
} else if (isset($_GET['filter_status'])) {
	$filter_status = sanitize_text_field($_GET['filter_status']);
	$link ='&filter_status='.$filter_status;
	if (in_array($filter_status,array(1,2,3,7,12,14,21,22,23,24,25,26,27))) {
		$post_qry = $wpdb->get_row("SELECT count(a.order_id) as cnt FROM ".$wpdb->prefix."ela_order_shipment a  JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method' and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.shipment_status_id='".$filter_status."'");
	} else {
		$post_qry = $wpdb->get_row("SELECT count(a.order_id) as cnt FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' and a.order_id NOT IN (SELECT order_id FROM ".$wpdb->prefix."ela_order_shipment )");
	}
} else {
	//$post_qry = $wpdb->get_row("SELECT COUNT(DISTINCT a.order_id) AS cnt FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item' AND ps.post_status in ('wc-processing')");
	$post_qry = $wpdb->get_row("SELECT COUNT(DISTINCT a.order_id) AS cnt FROM  ".$wpdb->prefix."posts ps JOIN ".$wpdb->prefix."woocommerce_order_items a on ps.ID=a.order_id JOIN  ".$wpdb->prefix."postmeta f ON a.order_id = f.post_id JOIN  ".$wpdb->prefix."postmeta g ON a.order_id = g.post_id JOIN ".$wpdb->prefix."postmeta i  ON a.order_id = i.post_id  JOIN  ".$wpdb->prefix."postmeta k ON a.order_id = k.post_id JOIN  ".$wpdb->prefix."postmeta l ON a.order_id = l.post_id  JOIN  ".$wpdb->prefix."postmeta n ON a.order_id = n.post_id WHERE a.order_item_type = 'line_item' AND f.meta_key='_shipping_postcode' AND g.meta_key='_order_total' and i.meta_key ='_payment_method'  and k.meta_key ='_shipping_first_name' AND l.meta_key ='_billing_phone' AND n.meta_key ='_shipping_last_name' AND a.order_item_type='line_item'");
}
$total = $post_qry->cnt;
$perpage = 20;
if (!isset($_REQUEST['pageNumber'])) {
	$page=1;
	$currentPage=1;
} else {
	$page = sanitize_text_field($_REQUEST['pageNumber']);
	$currentPage = sanitize_text_field($_REQUEST['pageNumber']);
}
if (!isset( $_GET['pageNumber'])){
	$_GET['pageNumber'] = 1;
}
$totalPages = ceil($total / $perpage);
$pagination_link = site_url().'/wp-admin/admin.php?page=my_icarry_shipments';

$warehouse = $wpdb->get_results("SELECT nickname, pickup_address_id, address, city, pin from ".$wpdb->prefix.'ela_pickup_address WHERE status>0 ORDER BY pickup_address_id ASC' );
$ii=0;
foreach ($warehouse AS &$wh) {
	$wh->entry_name = $wh->pickup_address_id.' ('.$wh->nickname.'):'.$wh->address.', '.$wh->city.' '.$wh->pin;
	$s_pickup_address_id=$wh->pickup_address_id;
	$ii++;
}

// Shipment defaults
$module_setting = get_option('icarry_shipping');;	
$s_wgt_in_gram=$module_setting['shipment_weight'];
$s_length_in_cm=$module_setting['shipment_length'];
$s_breadth_in_cm=$module_setting['shipment_breadth'];
$s_height_in_cm=$module_setting['shipment_height'];
$s_shipment_type=$module_setting['shipment_type'];
$s_shipment_mode=$module_setting['shipment_mode'];
$s_shipment_details=$module_setting['product_descriptor'];
$s_return_address_id=$module_setting['return_address_id'];
$s_cod_payment_code=$module_setting['cod_payment_code'];
if ($ii!=1) $s_pickup_address_id='';
$s_shipment_value=10;
?>

<!DOCTYPE html>
<html lang="en">
	<body class="bg-color">
		<div class="main-woocom-wrapper">
			<div class="container-fluid">
				<div style="display:none" id="loader_rate">
					<div class="woocom-loader">
						<div class="loader" id="loader-1"></div>
					</div>
				</div>
                <div class="row">
					<div class="col-md-12 col-sm-12 col-xs-12">
						<div>
							<div>
								<h2>My Shipments (<?=esc_html($total)?>)</h2>
								<!--<span  class="tooltip-hover" id="refresh_orders" tooltip-toggle="tooltip" data-placement="bottom" title="Refresh"><i class="fas fa-redo-alt refresh-icon"></i></span> -->
								<a href="#" onclick="ela_bulk_sync_status()" class="btn btn-primary">Sync Shipment Status <i class="fas fa-redo-alt refresh-icon"></i></a>
								<a href="#" onclick="ela_bulk_print_labels()" class="btn btn-primary">Bulk Print Labels <i class="fas fa-print print-icon"></i></a>
							</div>
							<br/>
							<div>
									<select name="status_filter" id="status_filter" onchange="ela_filter();" style="height: 38px;">
										<option value=''>Filter</option>
										<option value='1' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='1'){ echo esc_html('selected'); }  ?>>Pending Pickup</option>
										<option value='2' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='2'){ echo esc_html('selected'); }  ?> >Processing</option>
										<option value='3' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='3'){ echo esc_html('selected'); }  ?> >Shipped</option>
										<option value='7' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='7'){ echo esc_html('selected'); }  ?> >Cancelled</option>
										<option value='12' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='12'){ echo esc_html('selected'); }  ?> >Damaged</option>
										<option value='14' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='14'){ echo esc_html('selected'); }  ?> >Lost</option>          
										<option value='21' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='21'){ echo esc_html('selected'); }  ?> >Delivered</option>
										<option value='23' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='23'){ echo esc_html('selected'); }  ?> >Returned To Origin</option>
										<option value='26' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='26'){ echo esc_html('selected'); }  ?> >Out For Delivery</option>
										<option value='27' <?php if (isset($_GET['filter_status']) && sanitize_text_field($_GET['filter_status'])=='27'){ echo esc_html('selected'); }  ?> >Pending Return</option>
									</select>
								<input type="text" placeholder="Order Id" onfocus="this.placeholder=''" name="search_order" id="search_order" onblur="ela_search();">
								<input type="text" placeholder="AWB / Tracking Number" onfocus="this.placeholder=''" name="search_awb" id="search_awb" onblur="ela_search_awb();">
								<button onclick="ela_reset();">Reset</button>
							</div>
						</div>
						<div id="msg"></div>
						<div id="message">
							<?php 
							  if (isset($_SESSION["succmsg"]) && $_SESSION["succmsg"]!='') {
								$succmsg = $_SESSION["succmsg"];
								echo '
								 <div class="shopify-sucess-msg" id="smsg">
								  <img src="' . esc_url( plugins_url( '../images/checked.png', __FILE__ ) ) . '" ><p>'.esc_html($succmsg).'</p></div>';
								$_SESSION["succmsg"] = '';
							  } else if (isset($_SESSION["errmsg"]) && $_SESSION["errmsg"]!='') {
								$errmsg = $_SESSION["errmsg"];
								echo '
								  <div id="woocommerce_errors" class="error"><div class="shopify-error">
								  <img src="' . esc_url( plugins_url( '../images/alert.png', __FILE__ ) ) . '" ><p>'.esc_html($errmsg).'
								  </p></div></div>';
								$_SESSION["errmsg"] = '';
							  }
							?>
						</div>
						<div id="woocommerce_errors" class="error" style="display:none;">
							<div class="shopify-error">
								<?php echo '<img src="' . esc_url( plugins_url( '../images/alert.png', __FILE__ ) ) . '" >'; ?>
								<p id="err_msg"></p>
							</div>
						</div>
                    
						<div class="table-responsive" id="fetch_order">
							<table class="table table-bordered table-hover">
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
										<th>Estimated Cost</th>
										<th>Shipment Status</th>
										<th>Created On</th>
										<th>Picked On</th>
										<th>Delivered On</th>
										<th>My Notes</th>										
									</tr>
								</thead>
								<tbody>                  
									<?php foreach ($myrows as $data) {
										global $wpdb;
										$addin='';
										if (isset($_GET['search_awb'])) {
											$search_awb = sanitize_text_field($_GET['search_awb']);
											$addin .= " AND awb='".$search_awb."'";
										} else if (isset($_GET['filter_status'])) {
											$filter_status = sanitize_text_field($_GET['filter_status']);
											$addin .= " AND shipment_status_id='".$filter_status."'";
										}
										$orderr = wc_get_order( $data->order_id );
										
										$item_values = "";
										$get_item =  $wpdb->get_results("select order_item_name from ".$wpdb->prefix."woocommerce_order_items where order_id=$data->order_id and order_item_type='line_item'");
										foreach ($get_item as $get_items) {
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
										$order_total = $data->total_amount;
										$order_status  = $orderr->get_status();
										$order_edit_url = site_url().'/wp-admin/post.php?post='.$data->order_id.'&action=edit';
										
										$row_obj = array();
										$created_on=$updated_on=$picked_on=$delivered_on=$my_notes=$shipment_details='';
										$print_url=$track_url=$shipment_mode=$shipment_type=$shipment_id=$pickup_address_id='';
										$cooid=$courier_name=$shipment_status_id=$shipment_status=$shipment_cost=$awbno=$ewbn=$invoice='';
										
										$icry_shps = $wpdb->get_row("SELECT COUNT(id) AS cnt FROM ".$wpdb->prefix."ela_order_shipment WHERE order_id='$data->order_id'".$addin);
										if ($icry_shps->cnt == 0) {
											$shipment_total = 0;
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
											$orderdata = $orderr->get_data();
											$address = $orderdata['shipping']['address_1'].' '.$orderdata['shipping']['address_2'];
											$city = $orderdata['shipping']['city'];
											$state = $orderdata['shipping']['state'];
											$row_obj[] = array(
															'created_on' => $created_on,
															'updated_on' => $updated_on,
															'picked_on' => $picked_on,
															'delivered_on' => $delivered_on,
															'my_notes' => $my_notes,
															'print_url' => $print_url,
															'track_url' => $track_url,
															'cooid' => $cooid,
															'courier_name' => $courier_name,
															'shipment_status_id' => $shipment_status_id,
															'shipment_cost' => $shipment_cost,
															'awbno' => $awbno,
															'ewbn' => $ewbn,
															'invoice' => $invoice,
															'shipment_status' => $shipment_status,
															'shipment_type' => $shipment_type,
															'shipment_mode' => $shipment_mode,
															'shipment_total' => $shipment_total,
															'pickup_address_id' => $pickup_address_id,
															'shipment_id' => $shipment_id,
															'customer_name' => $customer_name,
															'pincode' => $pincode,
															'address' => $address,
															'city' => $city,
															'state' => $state,
															'phone' => $phone,
															'shipment_details' => $shipment_details,
															'order_total' => $order_total,
															'order_status' => $order_status,
															);
										} else {
											$scost_details = $wpdb->get_results("select id,shipment_cost,shipping_name,shipping_address,shipping_city,shipping_state,shipping_phone,shipment_details,shipping_pincode,shipment_type,shipment_mode,shipment_total,pickup_address_id,shipment_id,awb,courier_id,courier_name,shipment_status_id,(SELECT status_name FROM ".$wpdb->prefix."ela_shipment_status WHERE status_id=shipment_status_id) AS status_name,invoice_name,ewbn,created_at,updated_at,picked_on,delivered_on,my_notes from ".$wpdb->prefix."ela_order_shipment where order_id='$data->order_id' ".$addin." ORDER BY created_at DESC");
											foreach ($scost_details AS &$scost_detail) {
												$shipment_cost = $scost_detail->shipment_cost;  
												$awbno = $scost_detail->awb; 
												$cooid = $scost_detail->courier_id; 
												$courier_name = $scost_detail->courier_name;
												$shipment_status_id = $scost_detail->shipment_status_id;
												$ewbn = $scost_detail->ewbn;
												$invoice = $scost_detail->invoice_name;
												$shipment_type = $scost_detail->shipment_type;
												$shipment_mode = $scost_detail->shipment_mode;
												$shipment_status = $scost_detail->status_name;
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
												$created_on = $scost_detail->created_at;
												$updated_on = $scost_detail->updated_at;
												$picked_on = $scost_detail->picked_on;
												if ($picked_on == '0000-00-00 00:00:00') $picked_on = '';
												$delivered_on = $scost_detail->delivered_on;
												if ($delivered_on == '0000-00-00 00:00:00') $delivered_on = '';
												$my_notes = $scost_detail->my_notes;
								
												if ($awbno != '' && $shipment_id != '') { 
													$track_url = "https://www.icarry.in/track-shipment&shipment_id=".$shipment_id."&awb=".$awbno;
													$print_url = "https://www.icarry.in/print-shipment&shipment_id=".$shipment_id."&awb=".$awbno;	
												} else {
													$print_url = $track_url = '';
												}
												
												$row_obj[] = array(
															'created_on' => $created_on,
															'updated_on' => $updated_on,
															'picked_on' => $picked_on,
															'delivered_on' => $delivered_on,
															'my_notes' => $my_notes,
															'print_url' => $print_url,
															'track_url' => $track_url,
															'cooid' => $cooid,
															'courier_name' => $courier_name,
															'shipment_status_id' => $shipment_status_id,
															'shipment_cost' => $shipment_cost,
															'awbno' => $awbno,
															'ewbn' => $ewbn,
															'invoice' => $invoice,
															'shipment_status' => $shipment_status,
															'shipment_type' => $shipment_type,
															'shipment_mode' => $shipment_mode,
															'shipment_total' => $shipment_total,
															'pickup_address_id' => $pickup_address_id,
															'shipment_id' => $shipment_id,
															'customer_name' => $customer_name,
															'pincode' => $pincode,
															'address' => $address,
															'city' => $city,
															'state' => $state,
															'phone' => $phone,
															'shipment_details' => $shipment_details,
															'order_total' => $order_total,
															'order_status' => $order_status,
															);
											}
										}
										foreach ($row_obj AS $roow) {
											$action='';
											if ($roow['awbno'] != '' && $roow['shipment_id'] != '' && !in_array($roow['shipment_status_id'], array(7))) {
												$action .= '<p>[ <a href="'.$roow['track_url'].'" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="top" title="Track" target="_blank"><i class="fas fa-search gray-icon"></i> Track</a> ]</p>';			
											}
											if (in_array($roow['shipment_status_id'], array(1,2))) {
												$action .= '<p>[ <a href="#" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Cancel" onclick="ela_cancel_shipment(\''.$data->order_id.'\',\''.$roow['shipment_id'].'\',\''.$icarry_session_key.'\');"><i class="fas fa-trash-alt gray-icon"></i> Cancel</a> ]</p>';
											}
											if (in_array($roow['shipment_status_id'], array(1))) {
												$action .= '<p>[ <a href="'.$roow['print_url'].'" class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Print Label" target="_blank"><i class="fas fa-print gray-icon"></i> Print Label</a> ]</p>';
											}					
											$action .= '<p>[ <a href="#"class="tooltip-hover" tooltip-toggle="tooltip" data-placement="bottom" title="Book Shipment" data-toggle="modal" data-target="#ship-order-Modal" onclick="show_modal(\''.esc_html($roow['customer_name']).'\',\''.$roow['phone'].'\',\''.esc_html($roow['address']).'\',\''.esc_html($roow['city']).'\',\''.esc_html($roow['state']).'\',\''.$roow['pincode'].'\',\''.$data->order_id.'\',\''.esc_html($item_values).'\',\''.$roow['ewbn'].'\',\''.$roow['shipment_type'].'\',\''.$roow['shipment_total'].'\');"><i class="fas fa-truck gray-icon"></i> Book Shipment</a> ]</p>';  
										
											//if ($order_status=='processing' || $order_status=='pending') { 
											if ($roow['order_status']!='') { 
											?>
												<tr>
													<td>
													   <label class="checkbox">
													   <input type="checkbox"  name='order_id'  id="order_id[]" value="<?= esc_html($roow['shipment_id'])?>" <?php if($roow['shipment_status_id']=='') { ?>disabled="disabled" <?php } ?> >
													   <span class="checkmark"></span> 
													</td>
													<td>
														<div id="actioncls<?=esc_html($data->order_id)?>" ><?=$action?></div>
														<div id="ajax_actioncls<?=esc_html($data->order_id)?>" style="display:none;"></div>
													</td>
													<td><a href="<?=$order_edit_url?>"><?=esc_html($data->order_id)?></a></td>
													<td><?=esc_html($roow['order_status'])?></td>
													<td><?=esc_html($roow['customer_name'])?></td>
													<td><?=esc_html($item_values)?></td>
													<td><?=esc_html($quantity)?></td>
													<td><?=esc_html(get_woocommerce_currency_symbol())?><?=esc_html($roow['order_total'])?></td>
													<td><?=esc_html(get_woocommerce_currency_symbol())?><?=esc_html($roow['shipment_total'])?></td>
													<?php if ($roow['shipment_type'] == 'P') { ?>
														<td>Prepaid</td>
													<?php } else if ($roow['shipment_type'] == 'C') { ?>
														<td>COD</td>
													<?php } else { ?>
														<td><?=esc_html($roow['shipment_type'])?></td>	
													<?php } ?>
													<?php if ($roow['shipment_mode'] == 'S') { ?>
														<td>Standard</td>
													<?php } else if ($roow['shipment_mode'] == 'E') { ?>
														<td>Express</td>
													<?php } else { ?>
														<td><?=esc_html($roow['shipment_mode'])?></td>	
													<?php } ?>									
													<td><?=esc_html($roow['pickup_address_id'])?></td>
													<?php if ($roow['awbno'] != '' && $roow['shipment_id'] != '') { ?>
														<td><a class="tooltip-hover" tooltip-toggle="tooltip" data-placement="top" title="Track" href="<?php echo $roow['track_url']; ?>" target="_blank"><?=esc_html($roow['awbno'])?></a></td>
													<?php } else { ?>
														<td><?=esc_html($roow['awbno'])?></td>
													<?php } ?>
													<td><?=esc_html($roow['courier_name'])?></td>
													<td><?=esc_html(get_woocommerce_currency_symbol())?><?=esc_html($roow['shipment_cost'])?></td>
													<?php if ($roow['shipment_status_id'] == 21 || $roow['shipment_status_id'] == 23 || $roow['shipment_status_id'] == 26) {
														echo '<td><p style="color:green;font-size:16px">'.esc_html($roow['shipment_status']).'</p></td>';				
														} else if ($roow['shipment_status_id'] == 1 || $roow['shipment_status_id'] == 2 || $roow['shipment_status_id'] == 24 || $roow['shipment_status_id'] == 25) {
															echo '<td><p style="color:red;font-size:16px">'.esc_html($roow['shipment_status']).'</p></td>';			
														} else {
															echo '<td><p style="color:blue;font-size:16px">'.esc_html($roow['shipment_status']).'</p></td>';					
														}
													?>
													<td><?=esc_html($roow['created_on'])?></td>
													<td><?=esc_html($roow['picked_on'])?></td>
													<td><?=esc_html($roow['delivered_on'])?></td>
													<td><?=esc_html($roow['my_notes'])?></td>
												</tr>
											<?php }
										}											
									} ?>	
								</tbody>
							</table>
						</div>
						<?php if ($totalPages>1) { ?>
							<div class="loadmore-wrapper" id="loadmore_wrapper">
								<input type="hidden" id="result_no" value="20">
								<a href="#" onclick="do_next_page();">loadmore</a>
							</div>
						<?php } ?>

						<?php if ($totalPages>1) { 
							if (isset($_GET['pageNumber'])) {
								$j = sanitize_text_field($_GET['pageNumber']);
								if ($j>1) {
									$i = ($j-1);
									$k = ($j+1);
								} else {
									$i = $j;
									$k = ($j+1);
								}
							}
						?>
						<div class="row">
							<div class="comman-btn-div">
								<div class="pagination-wrapper">
									<nav aria-label="Page navigation example">
										<ul class="pagination">
											<li class="page-item">
												<a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber=1'.$link);?>" aria-label="Previous">
													<span aria-hidden="true"><i class="fa fa-step-backward color-darkgray" aria-hidden="true"></i>
													</span>
												</a>
											</li>
											<li class="page-item">
												<a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$i.$link);?>" aria-label="Previous">
													<span aria-hidden="true"><i class="fa fa-chevron-left color-darkgray" aria-hidden="true"></i></span>
												</a>
											</li>
											<?php for ($n=1;$n<=$totalPages;$n++) { 
												if (!isset( $_GET['pageNumber'])) {
													$_GET['pageNumber'] = 1;
												}
												?>
												<li class="page-item"><a class="page-link <?php if(sanitize_text_field($_GET['pageNumber'])==$n) { echo esc_html('active'); } ?>" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$n.$link);?>"><?=$n?></a></li>
											<?php } ?>
                        
											<li class="page-item">
											   <a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$k.$link);?>" aria-label="Next">
													<span aria-hidden="true"><i class="fa fa-chevron-right color-darkgray" aria-hidden="true"></i></span>
											   </a>
											</li>
											<li class="page-item">
											   <a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$totalPages.$link);?>" aria-label="Next">
													<span aria-hidden="true"><i class="fa fa-step-backward color-darkgray" aria-hidden="true" style="transform: rotate(180deg);"></i></span>
											   </a>
											</li>
										</ul>
									</nav>
								</div>
							</div>
						</div>
						<?php } ?>
                    </div>
                </div>
            </div>
		</div>

		<!-- ********************************************************* Start ship-order button pop-up ******************************************** -->
		<div id="myModal" class="shopify-modal">
            <div class="modal fade my-order-modal" id="ship-order-Modal" tabindex="-1" role="dialog" aria-labelledby="ShipOrderModal" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<div class="table-top-section ml-0">
								<h2 id="create-shipment-modal">Book Shipment</h2>
							</div>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						
						<div class="modal-body">
							<form action="" method="post" class="form-wrapper" id="modalform">
								<input type="hidden" name="order_item_id" id="order_item_id">
								<input type="hidden" name="order_id" id="order_id">
								<input type="hidden" name="cmd" value="create_shipment">
								<input type="hidden" name="apitok" id="apitok" value="<?=$icarry_session_key?>">
								<input type="hidden" name="return_address_id" id="return_address_id" value="<?=esc_html($s_return_address_id)?>"> 
                                <div class="row">
                                    <div class="col-md-12">
										<div class="form-group">	
											<label>Shipment Booking Options</label><br/>
											<input type="radio" onclick="validate('0')" class="form-control" name="booking_option" id="auto" value="auto" checked>Auto Choose Courier</br>
											<input type="radio" onclick="validate('0')" class="form-control" name="booking_option" id="choose_courier" value="choose_courier">Manually Choose Courier
										</div>
										<div class="form-group">
											<label>Consignee Details</label>
											<input type="text" class="form-control" name="name" id="name" value="" />
											<input type="text" class="form-control" name="phone" id="phone" value="" />
											<input type="text" class="form-control" name="address" id="address" value="" />
											<input type="text" class="form-control" name="city" id="city" value="" />
											<input type="text" class="form-control" name="pincode" id="pincode" value="" />
											<input type="text" class="form-control" name="state" id="state" value="" />
											<input type="text" class="form-control" name="country" id="country" value="IN" disabled />
										</div>
										<div class="form-group">	
											<label>My Notes</label>
											<input type="text" class="form-control" name="my_notes" id="my_notes" value="" />
										</div>
										<div class="form-group">	
											<label>Shipment Contents</label>
											<input type="text" class="form-control" name="shipment_details" id="shipment_details" value="" />
										</div>
                                        <div class="form-group">
											<label for="SelectWarehouse">Select Pickup Point</label>
											<select name="pickup_address_id" id="ware_house" onchange="validate('wh');">
												<option value="" selected>Select</option>
												<?php foreach($warehouse as $pa) { ?>
													<option value="<?=$pa->pickup_address_id?>" <?php if($s_pickup_address_id==$pa->pickup_address_id) { 
													  echo esc_html('selected'); } ?>><?=esc_html($pa->entry_name)?>
													</option>
												<?php } ?>
											</select>
											<div class="text-danger" id="wh_err"></div>
										</div>	
										<div class="form-group">
										   <label for="weight">Parcel Weight (in grams)</label>
										   <input type="number" class="form-control" name="wgt_in_gram" id="wgt_in_gram" onkeyup="remove_err_msg('wgt_in_gram_err')" onblur = "return(validate('wgtgm'));" value="<?=esc_html($s_wgt_in_gram)?>">
										   <div class="text-danger" id="wgt_in_gram_err"></div>
										</div>
										<div class="form-group">
										   <label for="length">Parcel Length (in centimetres)</label>
										   <input type="number" class="form-control" name="length_in_cm" id="length_in_cm" onkeyup="remove_err_msg('len_in_cm_err')" onblur = "return(validate('lencm'));" value="<?=esc_html($s_length_in_cm)?>">
										   <div class="text-danger" id="len_in_cm_err"></div>
										</div>
										<div class="form-group">
										   <label for="breadth">Parcel Breadth (in centimetres)</label>
										   <input type="number" class="form-control" name="breadth_in_cm" id="breadth_in_cm" onkeyup="remove_err_msg('brd_in_cm_err')" onblur = "return(validate('brdcm'));" value="<?=esc_html($s_breadth_in_cm)?>">
										   <div class="text-danger" id="brd_in_cm_err"></div>
										</div>											
										<div class="form-group">
										   <label for="height">Parcel Height (in centimetres)</label>
										   <input type="number" class="form-control" name="height_in_cm" id="height_in_cm" onkeyup="remove_err_msg('hgt_in_cm_err')" onblur = "return(validate('hgtcm'));" value="<?=esc_html($s_height_in_cm)?>">
										   <div class="text-danger" id="hgt_in_cm_err"></div>
										</div>
										<div class="form-group">
										   <label for="shipmentMode">Shipment Mode</label>
												<select name="shipment_mode" id="shipment_mode" class="form-control" style="max-width:none" onclick="remove_err_msg('shipment_mode_err')" onblur = "return(validate('smode'));">
												   <option value="" >Select</option>
												   <option value="E" <?php if($s_shipment_mode=='E') { 
													  echo esc_html('selected'); } ?>>Air</option>
												   <option value="S" <?php if($s_shipment_mode=='S') { 
													  echo esc_html('selected'); } ?>>Surface</option>
												</select>
											<div class="text-danger" id="shipment_mode_err"></div>        
										</div>
										<div class="form-group">
										   <label for="shipmentType">Shipment Type</label>
												<select name="shipment_type" id="input-payment_mode" class="form-control" style="max-width:none" onclick="remove_err_msg('payment_mode_err')" onblur = "return(validate('pmd'));">
												   <option value="" >Select</option>
												   <option value="P" <?php if ($s_shipment_type=='P') { 
													  echo esc_html('selected'); } ?>>Prepaid</option>
												   <option value="C" <?php if ($s_shipment_type=='C') { 
													  echo esc_html('selected'); } ?>>COD</option>                                   
												</select>
											<div class="text-danger" id="payment_mode_err"></div>
										</div>						
										<div class="form-group" id="cash_col">
										   <label for="shipment_value">Shipment Value ₹</label>
										   <input type="text" class="form-control" name="shipment_value" id="shipment_value" onclick="remove_err_msg('cash_collected_err')"  maxlength="10" value="<?=esc_html($s_shipment_value)?>" onblur = "return(validate('csh_col'));">
										   <div class="text-danger" id="cash_collected_err"></div>
										</div>
										<div class="form-group" id="ewbn_blk" style="display:none">
										   <label for="ewbns">Eway Bill Number</label>
										   <input type="text" class="form-control" name="ewbn" id="ewbn" onclick="remove_err_msg('ewbn_err')"  maxlength="20" value="" onblur = "return(validate('ewbn_col'));">
										   <div class="text-danger" id="ewbn_err"></div>
										</div>										
                                    </div>
                                </div>
                                <div id="courier_tbl" style="display:none"><p>Courier Options to be presented here</p></div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="button-right mt-0">
                                            <button class="btn btn-primary btn-lg btn-block btn-submit m-0 " name="proceed" id="proceed" disabled onclick="create_shipment();" >Complete Booking<div style="display:none" id="loader_rate1" >
												<div class="shopify-loader" >
													<div class="loader" id="loader-1"></div>
                                                </div>
                                                </div>
											</button>
                                            <div id="loader"></div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
				</div>
            </div>
		</div>
		<!--******************************************************** end ship-order button pop-up ******************************************** -->
    </body>
</html>
<?php
wp_enqueue_style( 'bootstrap.min', plugins_url('icarry-in-shipping-tracking/css/bootstrap.min.css') );
wp_enqueue_style( 'ela_stylees', plugins_url('icarry-in-shipping-tracking/css/ela_stylesheet.css') );
wp_enqueue_script( 'bootstrap.min_js', plugins_url('icarry-in-shipping-tracking/js/bootstrap.min.js')); 
wp_enqueue_script( 'custom_js', plugins_url('icarry-in-shipping-tracking/js/custom.js'), array( 'jquery' ), null, true );
?>
<script type = "text/javascript">
function show_modal(sname,sphone,saddress,scity,sstate,spincode,order_id,sdetails,ewbn2,sshipment_type,sshipment_val) {
	document.getElementById('woocommerce_errors').style.display = "none";
	document.getElementById('message').innerHTML = "";
	var name = sname;
	var phone = sphone;
	var address = saddress;
	var city = scity;
	var state = sstate;
	var pin = spincode;
	var order_ids = order_id;
	var details = sdetails;
	var ewbn = ewbn2;
	var shipment_type = sshipment_type;
	var shipment_value = sshipment_val;
	if (pin=='') {
		alert("Shipping pincode not available");
		return false;
	} else {
		jQuery('#ship-order-Modal').modal('show');
		var pincode = document.getElementById('pincode');
		pincode.value= pin;
		var order_id = document.getElementById('order_id');
		order_id.value= order_ids;
		var e_waybill = document.getElementById('ewbn');
		e_waybill.value= ewbn;
		
		document.getElementById('name').value = name;
		document.getElementById('phone').value = phone;
		document.getElementById('address').value = address;
		document.getElementById('city').value = city;
		document.getElementById('state').value = state;
		document.getElementById('shipment_value').value = shipment_value;
		document.getElementById('input-payment_mode').value = shipment_type;
		document.getElementById('shipment_details').value = details;
	}
	if (validate(0) == false) return false;
}
function close_modal() {
	jQuery('#ship-order-Modal').modal('hide');
	document.getElementById("loadimg").style.visibility = "hidden";
	document.getElementById('modalform').reset();
}
</script>

<script>
function create_shipment() {
	var id = 'auto';
	if (document.getElementById('auto').checked) {
		id = 'auto';
	} else if (document.getElementById('choose_courier').checked) {
		id = 'choose_courier';
	}
	if (id=='auto') { 	
		document.getElementById("loader_rate1").style.display = "block";
		jQuery('#proceed').attr("disabled", true);
		document.getElementById("modalform").submit();
	} else if (id=='choose_courier') {
		document.getElementById("loader_rate1").style.display = "block";
		jQuery('#proceed').attr("disabled", true);
		var chosen_courier = document.querySelector('input[name="chosen_courier"]:checked'); 
		if (chosen_courier != null) {
			document.getElementById("modalform").submit();	
		} else {				
			var api_token = document.getElementById('apitok').value;
			var weight = document.getElementById('wgt_in_gram').value;
			var length = document.getElementById('length_in_cm').value;
			var breadth = document.getElementById('breadth_in_cm').value;
			var height = document.getElementById('height_in_cm').value;
			var shipment_type = document.getElementById('input-payment_mode').value;
			var shipment_mode = document.getElementById('shipment_mode').value;
			var d_pincode = document.getElementById('pincode').value;
			var s_pkup_address_id = document.getElementById('ware_house').value;
			var shipment_value = document.getElementById('shipment_value').value;
			var o_country_code = "IN";
			var d_country_code = "IN";
			
			var data = {
			  'action': 'icarry_get_courier_estimate',
			  'api_token' : api_token,
			  'weight' : weight,
			  'length' : length,
			  'breadth' : breadth,
			  'height' : height,
			  'shipment_type' : shipment_type,
			  'shipment_mode' : shipment_mode,
			  'd_pincode' : d_pincode,
			  'o_pkup_address_id' : s_pkup_address_id,
			  'shipment_value' : shipment_value,
			  'o_country_code' : o_country_code,
			  'd_country_code' : d_country_code,
			};
			
			jQuery.ajax({
				  type: "POST",
				  url:ajaxurl,
				  data: data,
				  dataType: "json",
				  success: function( response ) {
					  //console.log(response);
					  if (response['status']==1) {
							document.getElementById("loader_rate1").style.display = "none";
							document.getElementById("courier_tbl").style.display = "block";	
							document.getElementById("courier_tbl").innerHTML=response['data'];
					  } else {
							document.getElementById("loader_rate1").style.display = "none";
							document.getElementById('woocommerce_errors').style.display = "block";
							document.getElementById("err_msg").innerHTML=response['err_msg'];
					  }
				  }
			});
		}
	}
}
function ela_search() {
	var search_url = "<?php echo $search_url; ?>";
	var search_order = document.getElementById("search_order").value;

	document.getElementById("search_order").placeholder = "Order Id";
	if (search_order!='') {
		search_url = search_url+'&search_order='+search_order;
		window.location.href = search_url;
	}  
}
function ela_search_awb() {
	var search_url = "<?php echo $search_url; ?>";
	var search_awb = document.getElementById("search_awb").value;

	document.getElementById("search_awb").placeholder = "AWB / Tracking Num";
	if (search_awb!='') {
		search_url = search_url+'&search_awb='+search_awb;
		window.location.href = search_url;
	}  
}
function ela_reset() {
	var url = "<?php echo $search_url; ?>";
	window.location.href = url;
}
function ela_cancel_shipment(order_id,shipmnt_id,apitok) {  
    document.getElementById("loader_rate").style.display = "block";
    var result = confirm("Do you want to cancel shipment?");
    if (result) {
		var api_token = apitok;
        var shipment_id = shipmnt_id;
		var oid = order_id;
        var succ_err = '<?=$succ_alert_img?>';
         
        var data = {
          'action': 'icarry_cancel_shipment',
          'api_token' : api_token,
          'shipment_id' : shipment_id,
		  'order_id' : oid
        };
          
		jQuery.ajax({
			  type: "POST",
			  url:ajaxurl,
			  data: data,
			  dataType: "json",
			  success: function( response ) {
				  if (response['status']==1) {
					document.getElementById("loader_rate").style.display = "none";
					document.getElementById("message").innerHTML='<div class="shopify-sucess-msg" id="smsg"><img src="'+succ_err+'"><p>Shipment is cancelled succefully</p></div>';
				  } else {
					document.getElementById("loader_rate").style.display = "none";
					document.getElementById('woocommerce_errors').style.display = "block";
					document.getElementById("err_msg").innerHTML=response['err_msg'];
				  }
				  window.location.reload();
			  }
		});
    }
}
function ela_show_err(pin,phone,payment_mode) {
    if (pin == '') { 
		var errmsg = 'Shipping Pincode is missing.';
		document.getElementById('woocommerce_errors').style.display = "block";
		document.getElementById("err_msg").innerHTML=errmsg;
		return false;
    } else if (phone == '') {
		var errmsg = 'Mobile number is missing.';
		document.getElementById('woocommerce_errors').style.display = "block";
		document.getElementById("err_msg").innerHTML=errmsg;
		return false;
    } else if (payment_mode == '') {
		var errmsg = 'Payment mode is missing.';
		document.getElementById('woocommerce_errors').style.display = "block";
		document.getElementById("err_msg").innerHTML=errmsg;
    }
}
jQuery( document ).ready(function() {   
    jQuery(".selectall").click(function () {
    document.getElementById('woocommerce_errors').style.display = "none";
    jQuery('input:checkbox:enabled').not(this).prop('checked', this.checked);
   });
});

function ela_filter() {
  var filter_url ="<?php echo $filter_url; ?>";
  var status_filter = document.getElementById("status_filter").value;
  if (status_filter == '') {
	var filter_url ="<?php echo $search_url; ?>";
  } else {
    filter_url = filter_url+status_filter;
  }
  window.open(filter_url,'_top');
}
function do_next_page() {
    var val = document.getElementById("result_no").value;
    document.getElementById("loader_rate").style.display = "block";
      var data = {
          'action': 'fetch_icarry_shipment_list',
          'getresult' : val

        };
            jQuery.jQueryajax({
                type: "POST",
                url:ajaxurl,
                data: data,
                dataType: "json",
                success: function( response ) {
                    
                  if(response['status']==1)
                  {
                    var count = Number(val)+20
                    if(response['total_count']<=count)
                    {
                      document.getElementById('loadmore_wrapper').style.display = "none";
                    }
                    var fetchdata = response['fetchdata'];
                    document.getElementById("fetch_order").innerHTML=fetchdata;
                    document.getElementById("result_no").value = Number(val)+20;
                    document.getElementById("loader_rate").style.display = "none";
                  }
                  else
                  {
                   var errmsg = response['err_msg'];
                   document.getElementById('woocommerce_errors').style.display = "block";
                   document.getElementById("err_msg").innerHTML=errmsg;

                  }

                }
            });

}
jQuery("#search_order").keypress(function(event) {
    if (event.which == 13) {
      var search_url = "<?php echo $search_url; ?>";
      var search_order = document.getElementById("search_order").value;
      
      document.getElementById("search_order").placeholder = "Order Id";
      if(search_order!='')
      {
        search_url = search_url+'&search_order='+search_order;
        window.location.href = search_url;
      }

  }
});
jQuery("#search_awb").keypress(function(event) {
    if (event.which == 13) {
      var search_url = "<?php echo $search_url; ?>";
      var search_awb = document.getElementById("search_awb").value;
      
      document.getElementById("search_awb").placeholder = "AWB / Tracking Num";
      if(search_order!='')
      {
        search_url = search_url+'&search_awb='+search_awb;
        window.location.href = search_url;
      }

  }
});
</script>

<script>
function remove_err_msg(id) {
	document.getElementById(id).innerHTML = "";
	var shipment_value = document.getElementById('shipment_value').value;
	if (shipment_value>=50000) {
		document.getElementById("ewbn_blk").style.display = "block";
	} else {
		document.getElementById("ewbn").value='';
		document.getElementById("ewbn_blk").style.display = "none";
	}
}
function ela_bulk_sync_status() {
  ( function( $ ) {
		var myCheckboxes = new Array();
		var checkboxes = document.getElementsByName('order_id');
		var selected = [];
		var count_checked = $("[name='order_id']:checked").length;
		for (var i=0; i<checkboxes.length; i++) {
			if (checkboxes[i].checked) {
				selected.push(checkboxes[i].value);
			}
		}
		if (count_checked>0) {
				var sync_status_url = "<?php echo $shipping_sync_status_url; ?>";
				var sync_status_url = sync_status_url+'&shipment_id='+selected;
				window.open(sync_status_url,'_top');
		} else {
			var errmsg = 'Please check at least one order';
			document.getElementById('woocommerce_errors').style.display = "block";
			document.getElementById("err_msg").innerHTML=errmsg;
		}
  } )( jQuery );
  //var search_url = "<?php echo $search_url; ?>";
  //window.location.href = search_url;
}
function ela_bulk_print_labels() {
	var myCheckboxes = new Array();
	var checkboxes = document.getElementsByName('order_id');
	var selected = [];
	var count_checked = 0;
	for (var i=0; i<checkboxes.length; i++) {
		if (checkboxes[i].checked) {
			selected.push(checkboxes[i].value);
			count_checked = count_checked+1;
		}
	}
	if (count_checked>0) {
		var print_labels_url = "<?php echo $print_labels_url; ?>";
		var print_labels_url = print_labels_url+'&shipment_ids='+selected;
		window.open(print_labels_url,'_blank');
	} else {
		var errmsg = 'Please check at least one order';
		document.getElementById('woocommerce_errors').style.display = "block";
		document.getElementById("err_msg").innerHTML=errmsg;
	}
}
function validate($id) {   
	var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	var pat1=/^([0-9](6,6))+$/;
	var iChars = ";\\#%&";
	var alpha = /^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/;
	var zip=/^[a-z0-9][a-z0-9\- ]{0,10}[a-z0-9]*$/g;
	var dzip=/^[a-z0-9][a-z0-9\- ]{0,10}[a-z0-9]*$/g;
	var rtn=true;

	document.getElementById("loader_rate1").style.display = "none";
	jQuery('#proceed').prop("disabled", true); 

	var wh = document.getElementById('ware_house').value;
	if ( wh == "" ) {
	   document.getElementById("wh_err").innerHTML = "Please select Pickup Address";
	   document.getElementById('modalform').ware_house.focus() ;
	   rtn = false;
	} else {
	   document.getElementById("wh_err").innerHTML = '';
	}
	
	var wgt_in_gram = document.getElementById('wgt_in_gram').value;
	if ($id=='wgtgm' || $id=='0') {
		if ( wgt_in_gram == "" ) {
			document.getElementById("wgt_in_gram_err").innerHTML = "Enter numeric value greater than zero for weight in gram";
			document.getElementById('modalform').wgt_in_gram.focus();
			rtn = false;
		}				
		if ( wgt_in_gram != "" ) {
			if (isNaN( wgt_in_gram ) ) {
				document.getElementById("wgt_in_gram_err").innerHTML = "Enter numeric value for weight in gram";
				document.getElementById('modalform').wgt_in_gram.focus();
				rtn = false;
			}
			if ( wgt_in_gram <= 0 ) {
				document.getElementById("wgt_in_gram_err").innerHTML = "Enter more than 0 for weight in gram";
				document.getElementById('modalform').wgt_in_gram.focus();
				rtn = false;
			}
		}  
	}

	var length_in_cm = document.getElementById('length_in_cm').value;
	if ($id=='lencm' || $id=='0') {
		if ( length_in_cm == "" ) {
			document.getElementById("len_in_cm_err").innerHTML = "Enter numeric value greater than zero for length in centimetres";
			document.getElementById('modalform').length_in_cm.focus();
			rtn = false;
		}
		if ( length_in_cm != "" ) {
			if (isNaN( length_in_cm ) ) {
				document.getElementById("len_in_cm_err").innerHTML = "Enter numeric value for length in centimetres";
				document.getElementById('modalform').length_in_cm.focus() ;
				rtn = false;
			}
			if ( length_in_cm <= 0 ) {
				document.getElementById("len_in_cm_err").innerHTML = "Enter more than 0 for length in centimetres";
				document.getElementById('modalform').length_in_cm.focus() ;
				rtn = false;
			}	
		}  
	}

	var breadth_in_cm = document.getElementById('breadth_in_cm').value;
	if ($id=='brdcm' || $id=='0') {
		if ( breadth_in_cm == "" ) {
			document.getElementById("brd_in_cm_err").innerHTML = "Enter numeric value greater than zero for breadth in centimetres";
			document.getElementById('modalform').breadth_in_cm.focus();
			rtn = false;
		}			
		if ( breadth_in_cm != "" ) {
			if (isNaN( breadth_in_cm ) ) {
				document.getElementById("brd_in_cm_err").innerHTML = "Enter numeric value for breadth in centimetres";
				document.getElementById('modalform').breadth_in_cm.focus() ;
				rtn = false;
			}
			if ( breadth_in_cm <= 0 ) {
				document.getElementById("brd_in_cm_err").innerHTML = "Enter more than 0 for breadth in centimetres";
				document.getElementById('modalform').breadth_in_cm.focus() ;
				rtn = false;
			}
		}  
	}
	
	var height_in_cm = document.getElementById('height_in_cm').value;
	if ($id=='hgtcm' || $id=='0') {
		if ( height_in_cm == "" ) {
			document.getElementById("hgt_in_cm_err").innerHTML = "Enter numeric value greater than zero for height in centimetres";
			document.getElementById('modalform').height_in_cm.focus();
			rtn = false;
		}				
		if ( height_in_cm != "" ) {
			if (isNaN( height_in_cm ) ) {
				document.getElementById("hgt_in_cm_err").innerHTML = "Enter numeric value for height in centimetres";
				document.getElementById('modalform').height_in_cm.focus() ;
				rtn = false;
			}
			if ( height_in_cm <= 0 ) {
				document.getElementById("hgt_in_cm_err").innerHTML = "Enter more than 0 for height in centimetres";
				document.getElementById('modalform').height_in_cm.focus() ;
				rtn = false;
			}
		}  
	}	

	var shipment_mode = document.getElementById('shipment_mode').value;
	if ($id=='smode' || $id=='0') {
	  if ( shipment_mode == "" ) {
		   document.getElementById("shipment_mode_err").innerHTML = "Select Shipment Mode (Air or Surface)";
		   document.getElementById('modalform').shipment_mode.focus() ;
		   rtn = false;
	  }
	}

	var shipment_type = document.getElementById('input-payment_mode').value;
	if ($id=='pmd' || $id=='0') {
	  if ( shipment_type == "" ) {
		   document.getElementById("payment_mode_err").innerHTML = "Select Shipment Type (Prepaid or COD)";
		   document.getElementById('modalform').shipment_type.focus() ;
		   rtn = false;
	  }
	}
	
	var shipment_value = document.getElementById('shipment_value').value;
	if (($id=='csh_col' || $id=='0')) {
		if ( shipment_value == "" ) {
		   document.getElementById("cash_collected_err").innerHTML = "Please enter shipment value. In case of COD order please enter value for amount to be collected";
		   document.getElementById('modalform').shipment_value.focus() ;
		   rtn = false;
		}
		if (isNaN( shipment_value ) ) {
		  document.getElementById("cash_collected_err").innerHTML = "Enter numeric value for shipment value";
		  document.getElementById('modalform').shipment_value.focus() ;
		  rtn = false;
		}
		if ( shipment_value <= 0 ) {
		  document.getElementById("cash_collected_err").innerHTML = "Enter more than 0 for shipment value";
		  document.getElementById('modalform').shipment_value.focus() ;
		  rtn = false;
		}
	}

	if (shipment_value>=50000) {
		document.getElementById("ewbn_blk").style.display = "block";
	} else {
		document.getElementById("ewbn").value='';
		document.getElementById("ewbn_blk").style.display = "none";
	}
	
	var ewbn = document.getElementById('ewbn').value;	
	if (($id=='ewbn_col' || $id=='0')) {
		if ( ewbn == "" && shipment_value >= 50000) {
		   document.getElementById("ewbn_err").innerHTML = "Eway Bill is mandatory for shipment value over Rs.50000";		   
		   rtn = false;
		}
	}

	if ($id!='cr') document.getElementById("courier_tbl").innerHTML="";

    if (rtn == true) {
		document.getElementById("loader_rate1").style.display = "none";
		jQuery('#proceed').removeAttr('disabled');
	}
	return rtn;
}
</script>
