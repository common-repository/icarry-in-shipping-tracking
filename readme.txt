=== iCarry.in Shipping & Tracking ===
Contributors: manojshanbhag
Plugin Name: iCarry.in Courier Shipping & Tracking
Plugin URI: Plugins Web Page URL
Tags: icarry logistics courier woocommerce
Author URI: https://www.icarry.in/
Requires at least: 5.1
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 2.0.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to ship & track your store orders using iCarry.in. You can also estimate domestic (within India) and international (India to foreign country) shipping cost.

== Description ==

1. This plugin allows you to ship your store orders using iCarry.in. You can also estimate domestic (within India) and international (India to foreign country) shipping cost.
2. Having an account at iCarry.in is a pre-requisite. If you do not have an account then please register at https://www.icarry.in/register
3. After your account is setup please request API credentials from your iCarry.in by sending email to support@icarry.in from your registered e-mail address.

== Installation ==

1. Upload the entire 'icarry-in-shipping-tracking' folder to the '/wp-content/plugins/' directory or upload the plugin zip file.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. After then plugin is activated you will see ICARRY in the main admin menu. If you do not see this then there was a problem in installation. Contact us for help.
4. Navigate to 'ICARRY -> My Settings' . This is a one-time activity. Without completing step 5 below you cannot make shipment bookings. In most cases the defaults will work.
5. Edit the default settings and save the following:
	- API username (you will get this from support@icarry.in on request from your iCarry.in registered email address)
	- API Secret Key (you will get this from support@icarry.in on request from your iCarry.in registered email address)
	- Pickup Address Id (Pickup Address id can be found in your list of My Account -> My Pickup Addresses in your iCarry.in account). This is the default pickup address id. 
	- Return Address Id (You can keep this blank if your return address is same as pickup address. If not then please enter the Registered Address Id. You can find it in My Account -> My Registered Addresses in your iCarry.in account)
	- Payment Code in for Cash on Delivery payment method ( meta_key='_payment_method' in `wp_postmeta` table). The default value is 'cod' and if you are not sure you can keep it as is.
	- Order History Status When Shipped (post_status to update for this order when shipment is booked). If unsure then keep the default.
	- Order History Status When Cancelled (post_status to update for this order when shipment is cancelled). If unsure then keep the default.
	- Order History Status When Delivered (post_status to update for this order when shipment is delivered). If unsure then keep the default.
	- Shipment Description (Brief description of the category of goods you ship. You can give more detailed description while booking each shipment)
	- Shipment Weight in Grams (Default is 500. Depending on category of goods you can change this to the weight of your typical parcel). You can also set a different weight for each shipment while booking. If unsure you can keep the default for completing the initial setup.
	- Shipment Length in Centimetres (Default is 10. Depending on your typical box size you can change this value). You can also set a different value while booking each shipment). If unsure you can keep the default for completing the initial setup.
	- Shipment Breadth in Centimetres (Default is 10. Depending on your typical box size you can change this value). You can also set a different value while booking each shipment). If unsure you can keep the default for completing the initial setup.
	- Shipment Height in Centimetres (Default is 10. Depending on your typical box size you can change this value). You can also set a different value while booking each shipment). If unsure you can keep the default for completing the initial setup.
	- Shipment Type (Default is 'P' for prepaid shipments. You can also set this as 'C' for default COD shipments). You can select shipment type and change it while booking each shipment. If unsure you can keep the default for completing the initial setup.
	- Shipment Mode (Default is 'S' for Surface/Standard shipments. You can also set this as 'E' for Express/Air shipments). You can select shipment mode and change it while booking each shipment. If unsure you can keep the default for completing the initial setup.	
6. Navigate to 'ICARRY -> Estimate Cost (India)' to see what it would cost you to book a shipment to be picked and delivered within India. If you are logged in (API username and key entered in above step and correct) it will show your cost according to your plan in your icarry account. If not logged in it will show you the cost for all plans.
7. Navigate to 'ICARRY -> Estimate Cost (International)' to see what it would cost you to book a shipment to be picked in India and delivered to a supported foreign country.
8. Navigate to 'ICARRY -> My Shipments' to see all shipment details for your orders. This will allow you to book new shipment, cancel shipment, track shipment, track shipment and sync shipment status. 
9. Navigate to 'ICARRY -> My Pickup Points' to see all pickup addresses stored in your account. There is a "Sync" facility which will allow you to import all pickup addresses that you have in your iCarry.in account under My Account > My Pickup Addresses. Sync this periodically to ensure you have latest data on pickup addresses.
10. Navigate to 'ICARRY -> FAQ' to see frequently asked questions.
11. If you Open any order under 'WooCommerce -> Orders' you will see new columns have been added for tracking number, shipment type, shipment mode etc.

Please contact support@icarry.in for questions/problems and for any help needed in configuration. 

== Changelog ==

= 2.0.0 =
* Updated release to support multi-vendor pickup & flexible shipment booking

= 1.0.0 =
* Initial Release