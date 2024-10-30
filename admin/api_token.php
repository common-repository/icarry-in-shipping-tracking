<?php
	global $wpdb;
	if ((time() - $_SESSION['timestamp'] >= 1800) || (!isset($_SESSION['icarry_api_token'])) || ($_SESSION['icarry_api_token'] == '')) {		
		$module_setting = get_option('icarry_shipping');;	
		$icarry_session_key = '';
		$_SESSION['icarry_api_token'] = $icarry_session_key;
		$api_username = $module_setting['api_username'];
		if ($api_username != '') {
			$api_key = $module_setting['api_key'];			
			$icarry_url = "https://www.icarry.in/index.php?route=api/ela_login";			
			$qs = array('username' => $api_username, 'key' => $api_key);
			
			$NUM_OF_ATTEMPTS = 3;
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
			if (!isset($response['success']) && !isset($response['api_token'])) {
				$_SESSION['icarry_api_token'] = '';
				show_message("icarry login failed: ".print_r($response,1));
				exit;
			} else {
				$icarry_session_key = $response['api_token'];
				$_SESSION['icarry_api_token'] = $icarry_session_key;
			}
		}
		$_SESSION['timestamp'] = time();
	}
?>