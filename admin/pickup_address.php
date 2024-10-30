<?php
$refresh_url = site_url().'/wp-admin/admin.php?page=my_pickup_address&action=refresh';
$table_name = $wpdb->prefix . 'ela_pickup_address';
$link='';
$perpage=50;
$pageNumber=1;
if (isset($_GET['pageNumber'])) {
	$pageNumber = sanitize_text_field($_GET['pageNumber']);
}
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
$myrow = $wpdb->get_row("SELECT COUNT(pickup_address_id) AS cnt FROM $table_name");
$total = $myrow->cnt;
$totalPages = ceil($total / $perpage);
$pagination_link = site_url().'/wp-admin/admin.php?page=my_pickup_address';
?>
<!DOCTYPE html>
<html lang="en">
    <body class="bg-color">
      <?php if($total==0){?>
      <div class="main-woocom-wrapper">
      </div>
      <section>
         <h2>No Pickup Address in your account!</h2>
         <h2>Create/Edit new Pickup Address from your <a href="https://www.icarry.in/pickup" target="_blank">iCarry Account</a>.</h2>
		 <a href="#" onclick="refresh_warehouse('<?=$refresh_url?>')" class="waybill-btn">Sync Pickup Points</a> 
	  </section>
      <?php } 
      else
      {?>
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
                     <h2>My Pickup Points (<?=esc_html($total)?>)</h2>
					 <a href="#" onclick="refresh_warehouse('<?=$refresh_url?>')" class="btn btn-primary">Sync Pickup Points</a>
                  </div>
                  <?php
                  if(isset($_SESSION["succmsg"]) && $_SESSION["succmsg"]!='')
                  {
                    $succmsg = $_SESSION["succmsg"];

                    echo '<div id="message" class="" style="color:green;">
                                <div class="shopify-sucess-msg">
                                      <img src="' . esc_url( plugins_url( '../images/checked.png', __FILE__ ) ) . '" >
                                      <p>'.esc_html($succmsg).'</p>
                                </div>
                          </div>';
                    $_SESSION["succmsg"] = '';
                  } 
                  ?>
				  <br/>
                  <div class="table-responsive" id="fetch_pickup_address">
                     <table class="table table-bordered table-hover">
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
                        <tbody>
                           <?php foreach($myrows as $data){ ?>
                            <tr>
                              <td><?=$data->pickup_address_id?></td>
                              <td><?=$data->nickname?></td>
							  <td><?php if($data->contact_person==''){ echo esc_html('NA'); } else { echo esc_html($data->contact_person); }?></td>
                              <td>
                                 <?=esc_html($data->email)?>&nbsp;/&nbsp<?=$data->phone?>
                              </td>
							  <td><?=$data->address?></td>
                              <td>
                                 <?php if($data->state==''){ echo esc_html('NA'); } else { echo esc_html($data->state); }?>&nbsp;/&nbsp
                                 <?=$data->city?>
                              </td>                             
                              <td><?php if($data->country==''){ echo esc_html('NA'); } else { echo esc_html($data->country); }?></td>
                              <td><?php if ($data->status=='1' ){ echo '<span style="color:green">Active</span>'; } else { echo '<span style="color:red">Inactive</span>'; }?></td>
                           </tr>
                            <?php }  
                            ?>
                        </tbody>
                     </table>
                  </div>
                  <?php if ($totalPages>1) { ?>
					<div class="loadmore-wrapper" id="loadmore_wrapper">
						<input type="hidden" id="result_no" value="50">
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
                        <?php 
                        
                        for($n=1;$n<=$totalPages;$n++) 
                        { 
                           if (!isset( $_GET['pageNumber'])){
                              $_GET['pageNumber'] = 1;
                           }
                        ?>
                        <li class="page-item"><a class="page-link <?php if(sanitize_text_field($_GET['pageNumber'])==$n) { echo 'active'; } ?>" target="_top" href="<?php echo $pagination_link.'&pageNumber='.$n;?>"><?=$n?></a></li>
                        <?php } ?>
                        
                        <li class="page-item">
                           <a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$k.$link);?>" aria-label="Next">
                           <span aria-hidden="true"><i class="fa fa-chevron-right color-darkgray" aria-hidden="true"></i></span>
                           </a>
                        </li>
                        <li class="page-item">
                           <a class="page-link" target="_top" href="<?php echo esc_url($pagination_link.'&pageNumber='.$totalPages.$link);?>" aria-label="Next">
                           <span aria-hidden="true"><i class="fa fa-step-backward color-darkgray" aria-hidden="true" style="
                              transform: rotate(180deg);
                              "></i></span>
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
      <?php } ?>

   </body>
</html>
<?php
wp_enqueue_style('bootstrap.min', plugins_url('icarry-in-shipping-tracking/css/bootstrap.min.css'));
wp_enqueue_style('ela_stylees', plugins_url('icarry-in-shipping-tracking/css/ela_stylesheet.css'));
wp_enqueue_script('bootstrap.min_js', plugins_url('icarry-in-shipping-tracking/js/bootstrap.min.js'));
?>
<script type="text/javascript">
function refresh_warehouse(url)
{
   url = url;
   window.open(url,'_self'); 
}
function do_next_page()
   {
      var val = document.getElementById("result_no").value;
      document.getElementById("loader_rate").style.display = "block";
      var data = {
          'action': 'fetch_pickup_address_list',
          'getresult' : val
        };
          $.ajax({
            type: "POST",
            url:ajaxurl,
            data: data,
            dataType: "json",
            success: function(response) {
              if(response['status']==1)
              {
                var count = Number(val)+50
                if(response['total_count']<=count)
                {
                  document.getElementById('loadmore_wrapper').style.display = "none";
                }
                var fetchdata = response['fetchdata'];
                document.getElementById("fetch_pickup_address").innerHTML=fetchdata;
                document.getElementById("result_no").value = Number(val)+50;
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
</script>