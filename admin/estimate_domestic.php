<?php
require_once('api_token.php');

if (isset($_SESSION['icarry_api_token']) && $_SESSION['icarry_api_token'] != '') {
	global $wpdb;

	if (isset($_POST['reset'])) {
		$_SESSION['o_pincode'] = '';
		$_SESSION['d_pincode'] = '';
		$_SESSION['wgt_in_gram'] = '';
		$_SESSION['length_in_cm'] = '10';
		$_SESSION['breadth_in_cm'] = '10';
		$_SESSION['height_in_cm'] = '10';
		$_SESSION['shipment_mode'] = 'S';
		$_SESSION['shipment_type'] = 'P';
		$_SESSION['shipment_value'] = '100';
	}

	if (isset($_SESSION['icarry_api_token'])) { 
	   $icarry_api_token = $_SESSION['icarry_api_token']; 
	} else {
	   $icarry_api_token='';
	}

	if (isset($_SESSION['o_pincode'])) { 
	   $s_o_pincode = $_SESSION['o_pincode']; 
	} else {
	   $s_o_pincode='';
	}

	if (isset($_SESSION['d_pincode'])) { 
	   $s_d_pincode = $_SESSION['d_pincode']; 
	} else {
	   $s_d_pincode='';
	}

	if (isset($_SESSION['wgt_in_gram'])) { 
	   $s_wgt_in_gram = $_SESSION['wgt_in_gram']; 
	} else {
	   $s_wgt_in_gram='';
	}

	if (isset($_SESSION['length_in_cm'])) { 
	   $s_length_in_cm = $_SESSION['length_in_cm']; 
	} else {
	   $s_length_in_cm='10';
	}

	if (isset($_SESSION['breadth_in_cm'])) { 
	   $s_breadth_in_cm = $_SESSION['breadth_in_cm']; 
	} else {
	   $s_breadth_in_cm='10';
	}

	if (isset($_SESSION['height_in_cm'])) { 
	   $s_height_in_cm = $_SESSION['height_in_cm']; 
	} else {
	   $s_height_in_cm='10';
	}

	if (isset($_SESSION['shipment_mode'])) { 
	   $s_shipment_mode = $_SESSION['shipment_mode']; 
	} else {
	   $s_shipment_mode='S';
	}

	if(isset($_SESSION['shipment_type'])) { 
	   $s_shipment_type = $_SESSION['shipment_type']; 
	} else {
	   $s_shipment_type='P';
	}

	if(isset($_SESSION['shipment_value'])) { 
	   $s_shipment_value = $_SESSION['shipment_value']; 
	} else {
	   $s_shipment_value='100';
	}
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
                     <h2>Shipment Cost Calculator</h2>
                     <form name = "myForm" id="myForm"  method="post" enctype="multipart/form-data" id="form-module" class="form-wrapper" >
                     <input type="hidden" value="<?=$icarry_api_token?>" readonly="readonly" name="api_token" id="api_token">
				  
                  <div class="row">
                     <div class="col-md-4">
                        <div class="form-group">
                           <label for="weight">Weight (in grams)</label>
                           <input type="number" class="form-control" name="wgt_in_gram" id="wgt_in_gram" onkeyup="remove_err_msg('wgt_in_gram_err')" onblur = "return(validate('wgtgm'));" value="<?=esc_html($s_wgt_in_gram)?>">
                           <div class="text-danger" id="wgt_in_gram_err"></div>
                        </div>
                     </div>

                     <div class="col-md-4">
                        <div class="form-group">
                           <label for="length">Length (in centimetres)</label>
                           <input type="number" class="form-control" name="length_in_cm" id="length_in_cm" onkeyup="remove_err_msg('len_in_cm_err')" onblur = "return(validate('lencm'));" value="<?=esc_html($s_length_in_cm)?>">
                           <div class="text-danger" id="len_in_cm_err"></div>
                        </div>
                     </div>
                  </div>
				  
                  <div class="row">
                     <div class="col-md-4">
                        <div class="form-group">
                           <label for="breadth">Breadth (in centimetres)</label>
                           <input type="number" class="form-control" name="breadth_in_cm" id="breadth_in_cm" onkeyup="remove_err_msg('brd_in_cm_err')" onblur = "return(validate('brdcm'));" value="<?=esc_html($s_breadth_in_cm)?>">
                           <div class="text-danger" id="brd_in_cm_err"></div>
                        </div>
                     </div>

                     <div class="col-md-4">
                        <div class="form-group">
                           <label for="height">Height (in centimetres)</label>
                           <input type="number" class="form-control" name="height_in_cm" id="height_in_cm" onkeyup="remove_err_msg('hgt_in_cm_err')" onblur = "return(validate('hgtcm'));" value="<?=esc_html($s_height_in_cm)?>">
                           <div class="text-danger" id="hgt_in_cm_err"></div>
                        </div>
                     </div>
                  </div>				  
				  
                  <div class="row">
				     <div class="col-md-4">
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
                     </div>
                     <div class="col-md-4">
                        <div class="form-group">
                           <label for="shipmentType">Shipment Type</label>
                                <select name="shipment_type" id="input-payment_mode" class="form-control" style="max-width:none" onclick="remove_err_msg('payment_mode_err')" onblur = "return(validate('pmd'));" onclick="remove_err_msg('payment_mode_err')">
                                   <option value="" >Select</option>
								   <option value="P" <?php if($s_shipment_type=='P') { 
                                      echo esc_html('selected'); } ?>>Prepaid</option>
                                   <option value="C" <?php if($s_shipment_type=='C') { 
                                      echo esc_html('selected'); } ?>>COD</option>                                   
                                </select>
                            <div class="text-danger" id="payment_mode_err"></div>
                        </div>
                    </div>
                  </div>				  

                  <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                           <label for="originPincode">Pincode of Sender</label>
                           <input type="name" class="form-control" name="org_pincode" id="org_pincode" onkeyup="remove_err_msg('org_pincode_err')" onblur = "return(validate('opin'));" maxlength="10" value="<?=esc_html($s_o_pincode)?>">
                           <div class="text-danger" id="org_pincode_err"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                           <label for="destinationPincode">Pincode of Receiver</label>
                           <input type="name" class="form-control" name="d_pincode" id="d_pincode" onkeyup="remove_err_msg('d_pincode_err')" onblur = "return(validate('dpin'));" maxlength="10" value="<?=esc_html($s_d_pincode)?>">
                           <div class="text-danger" id="d_pincode_err"></div>
                        </div>
                     </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                        <div class="form-group" id="cash_col" style="display:none">
                           <label for="shipment_value">Amount to be Collected</label>
                           <input type="name" class="form-control" name="shipment_value" id="shipment_value" onkeyup="remove_err_msg('cash_collected_err')"  maxlength="10" value="<?=esc_html($s_shipment_value)?>" onblur = "return(validate('csh_col'));">
                           <div class="text-danger" id="cash_collected_err"></div>
                        </div>
                    </div>
                  </div>
                  <div class="row">
                     <div class="col-md-8">
                        <div class="button-right float-right">
                           <button class="btn btn-link btn-reset" name="reset" >Reset</button>
                           <button type="button" class="btn btn-primary btn-submit" name="save" onclick = "validate('0');">Submit</button>
                        </div>
                     </div>
                  </div>
               </form>
                  </div>
               </div>
            </div>
         </div>
         <div class="shopify-modal">
            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
               <div class="modal-dialog" role="document">
                  <div class="modal-content">
                     <div class="modal-header">
                        <table>
                           <tr><td><h4 id="exampleModalLabel">Estimated Shipment Cost</h4></td>
						   <td>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						   </td>
						   </tr>
                        </table>
                     </div>
                     <div class="modal-body" id="rate_output">
                        
                     </div>
                  </div>
               </div>
            </div>
         </div>
    
      
   </body>
</html>
<?php 
wp_enqueue_style( 'bootstrap.min', plugins_url('icarry-in-shipping-tracking/css/bootstrap.min.css') );
wp_enqueue_style( 'ela_stylees', plugins_url('icarry-in-shipping-tracking/css/ela_stylesheet.css') );
wp_enqueue_script( 'bootstrap.min_js', plugins_url('icarry-in-shipping-tracking/js/bootstrap.min.js'));
wp_enqueue_script( 'custom_js', plugins_url('icarry-in-shipping-tracking/js/custom.js'), array( 'jquery' ), null, true ); ?>
<script type = "text/javascript">
   <!--
    // Form validation code will come here.
    function validate($id) {   
        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
        var pat1=/^([0-9](6,6))+$/;
        var iChars = ";\\#%&";
        var alpha = /^[a-zA-Z-,]+(\s{0,1}[a-zA-Z-, ])*$/;
        var zip=/^[a-z0-9][a-z0-9\- ]{0,10}[a-z0-9]*$/g;
        var dzip=/^[a-z0-9][a-z0-9\- ]{0,10}[a-z0-9]*$/g;
 
        var wgt_in_gram = document.myForm.wgt_in_gram.value;
        if ($id=='wgtgm' || $id=='0') {
			if ( document.myForm.wgt_in_gram.value == "" ) {
				document.getElementById("wgt_in_gram_err").innerHTML = "Enter numeric value greater than zero for weight in gram";
				document.myForm.wgt_in_gram.focus();
				return false;
			}				
			if ( document.myForm.wgt_in_gram.value != "" ) {
				if (isNaN( document.myForm.wgt_in_gram.value ) ) {
					document.getElementById("wgt_in_gram_err").innerHTML = "Enter numeric value for weight in gram";
					document.myForm.wgt_in_gram.focus();
					return false;
				}
				if ( document.myForm.wgt_in_gram.value <= 0 ) {
					document.getElementById("wgt_in_gram_err").innerHTML = "Enter more than 0 for weight in gram";
					document.myForm.wgt_in_gram.focus();
					return false;
				}
			}  
        }

        var length_in_cm = document.myForm.length_in_cm.value;
        if ($id=='lencm' || $id=='0') {
			if ( document.myForm.length_in_cm.value == "" ) {
				document.getElementById("len_in_cm_err").innerHTML = "Enter numeric value greater than zero for length in centimetres";
				document.myForm.length_in_cm.focus();
				return false;
			}
			if ( document.myForm.length_in_cm.value != "" ) {
				if (isNaN( document.myForm.length_in_cm.value ) ) {
					document.getElementById("len_in_cm_err").innerHTML = "Enter numeric value for length in centimetres";
					document.myForm.length_in_cm.focus() ;
					return false;
				}
				if ( document.myForm.length_in_cm.value <= 0 ) {
					document.getElementById("len_in_cm_err").innerHTML = "Enter more than 0 for length in centimetres";
					document.myForm.length_in_cm.focus() ;
					return false;
				}	
			}  
        }

        var breadth_in_cm = document.myForm.breadth_in_cm.value;
        if ($id=='brdcm' || $id=='0') {
			if ( breadth_in_cm == "" ) {
				document.getElementById("brd_in_cm_err").innerHTML = "Enter numeric value greater than zero for breadth in centimetres";
				document.myForm.breadth_in_cm.focus();
				return false;
			}			
			if ( document.myForm.breadth_in_cm.value != "" ) {
				if (isNaN( document.myForm.breadth_in_cm.value ) ) {
					document.getElementById("brd_in_cm_err").innerHTML = "Enter numeric value for breadth in centimetres";
					document.myForm.breadth_in_cm.focus() ;
					return false;
				}
				if ( document.myForm.breadth_in_cm.value <= 0 ) {
					document.getElementById("brd_in_cm_err").innerHTML = "Enter more than 0 for breadth in centimetres";
					document.myForm.breadth_in_cm.focus() ;
					return false;
				}
			}  
        }
		
        var height_in_cm = document.myForm.height_in_cm.value;
        if ($id=='hgtcm' || $id=='0') {
			if ( document.myForm.height_in_cm.value == "" ) {
				document.getElementById("hgt_in_cm_err").innerHTML = "Enter numeric value greater than zero for height in centimetres";
				document.myForm.height_in_cm.focus();
				return false;
			}				
			if ( document.myForm.height_in_cm.value != "" ) {
				if (isNaN( document.myForm.height_in_cm.value ) ) {
					document.getElementById("hgt_in_cm_err").innerHTML = "Enter numeric value for height in centimetres";
					document.myForm.height_in_cm.focus() ;
					return false;
				}
				if ( document.myForm.height_in_cm.value <= 0 ) {
					document.getElementById("hgt_in_cm_err").innerHTML = "Enter more than 0 for height in centimetres";
					document.myForm.height_in_cm.focus() ;
					return false;
				}
            }  
        }	
 
        var shipment_mode = document.myForm.shipment_mode.value;
        if ($id=='smode' || $id=='0') {
          if ( document.myForm.shipment_mode.value == "" ) {
               document.getElementById("shipment_mode_err").innerHTML = "Select Shipment Mode (Air or Surface)";
               document.myForm.shipment_mode.focus() ;
               return false;
          }
        }

        var shipment_type = document.myForm.shipment_type.value;
        if ($id=='pmd' || $id=='0') {
          if ( document.myForm.shipment_type.value == "" ) {
               document.getElementById("payment_mode_err").innerHTML = "Select Shipment Type (Prepaid or COD)";
               document.myForm.shipment_type.focus() ;
               return false;
          }
        }
 
        if ($id=='opin' || $id=='0') {
          if ( document.myForm.org_pincode.value == "" ) {
               document.getElementById("org_pincode_err").innerHTML = "Select sender pincode";
               document.myForm.org_pincode.focus();
               return false;
          }
          
          var opin= document.myForm.org_pincode.value;
          if (zip.test(opin) == false) {
              document.getElementById("org_pincode_err").innerHTML = "Enter valid sender pincode";
                  document.myForm.org_pincode.focus();
              return false;
          }
          if ((document.myForm.org_pincode.value).length != 6)  {
              document.getElementById("org_pincode_err").innerHTML = "Sender pincode should be 6 digits";
              document.myForm.org_pincode.focus();
              return false;
          }
        }

        var dpin = document.myForm.d_pincode.value;
        if ($id=='dpin' || $id=='0') {
          if ( document.myForm.d_pincode.value == "" ) {
               document.getElementById("d_pincode_err").innerHTML = "Enter receiver pincode";
               document.myForm.d_pincode.focus();
               return false;
          }
          
          dpin = document.myForm.d_pincode.value;
          if (dzip.test(dpin) == false) {
              document.getElementById("d_pincode_err").innerHTML = "Enter valid receiver pincode";
                  document.myForm.d_pincode.focus();
              return false;
          }
          if ((document.myForm.d_pincode.value).length != 6)  {
              document.getElementById("d_pincode_err").innerHTML = "Receiver pincode should 6 digits";
              document.myForm.d_pincode.focus();
              return false;
          }
        }
        
        var shipment_value = document.myForm.shipment_value.value;
        if (($id=='csh_col' || $id=='0') && shipment_type=='C' ) {
            if ( document.myForm.shipment_value.value == "" ) {
               document.getElementById("cash_collected_err").innerHTML = "Please enter value for amount to be collected";
               document.myForm.shipment_value.focus() ;
               return false;
            }
            if (isNaN( document.myForm.shipment_value.value ) ) {
              document.getElementById("cash_collected_err").innerHTML = "Enter numeric value for amount to be collected";
              document.myForm.shipment_value.focus() ;
              return false;
            }
            if ( document.myForm.shipment_value.value <= 0 ) {
              document.getElementById("cash_collected_err").innerHTML = "Enter more than 0 for amount to be collected";
              document.myForm.shipment_value.focus() ;
              return false;
            }
        }
          
		var api_token = document.myForm.api_token.value;
		
		var data = {
		'action': 'get_estimate_domestic',
		'api_token': api_token,
		'org_pincode' : opin,
		'd_pincode' : dpin,
		'wgt_in_gram' : wgt_in_gram,
		'length_in_cm' : length_in_cm,
		'breadth_in_cm' : breadth_in_cm,
		'height_in_cm' : height_in_cm,
		'shipment_type' : shipment_type,
		'shipment_mode' : shipment_mode,
		'shipment_value' : shipment_value
		};
         
        if ($id=='0') {
          document.getElementById("loader_rate").style.display = "block";
          jQuery.ajax({
				type: "GET",
				url:ajaxurl,
				data: data,
				dataType: "json",
				success: function( data ) {
					if (data['status']==1) {
						document.getElementById("loader_rate").style.display = "none";
						document.getElementById("rate_output").innerHTML=data['data'];
						jQuery('#exampleModal').modal('show');
					}
					else {
						var errmsg = data['err_msg'];
						alert(errmsg);
					}
				}
			});
        }
    }
      
	function remove_err_msg(id) {
        document.getElementById(id).innerHTML = "";
        var payment_mode = document.myForm.shipment_type.value;
    
        if (payment_mode=='C') {
          document.getElementById("cash_col").style.display = "block";
        } else {
          document.getElementById("cash_col").style.display = "none";
        }
    }
      
	function reset() {
        document.getElementById("myForm").reset();
    }
</script>
<?php } else { ?>
<!DOCTYPE html>
<html lang="en">
	<body class="bg-color"> 
		<iframe src="https://www.icarry.in/estimate" style="border:none;height:100vh;width:100%;" title="iCarry.in Shipment Cost Calculator"></iframe>
	</body>   
</html>	
<?php } ?>