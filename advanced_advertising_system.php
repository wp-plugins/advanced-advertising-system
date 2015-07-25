<?php
/**
 * 
 */
/*
Plugin Name: Advanced Advertising System
Plugin URI: http://www.smartdevth.com/advanced-advertising-system/
Description: Manage your advertiser with many professional features.
Version: 1.2.4
Author: Smartdevth
Author URI: http://www.smartdevth.com/advanced-advertising-system/
License: GPLv2 or later
Text Domain: aas
*/
define('AAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAS_PLUGIN_FILE', basename(__FILE__));
define('AAS_PLUGIN_FULL_PATH', __FILE__);
define('AAS_TEXT_DOMAIN' , 'aas');
if(!class_exists('Mobile_Detect'))
require( AAS_PLUGIN_DIR . 'includes/Mobile-Detect/Mobile_Detect.php');
require( AAS_PLUGIN_DIR . 'includes/class-log.php');
require( AAS_PLUGIN_DIR . 'includes/class-advertiser.php');
require( AAS_PLUGIN_DIR . 'includes/class-campaign.php');
require( AAS_PLUGIN_DIR . 'includes/class-banner.php');
require( AAS_PLUGIN_DIR . 'includes/class-zone.php');
require( AAS_PLUGIN_DIR . 'widget.php');
require( AAS_PLUGIN_DIR . 'shortcode.php');

add_action( 'plugins_loaded', 'aas_load_textdomain' );

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function aas_load_textdomain() {
  load_plugin_textdomain( AAS_TEXT_DOMAIN , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

function aas_admin_menu(){

add_menu_page( 'Advertising', 'Advertising', 'edit_posts', 'edit.php?post_type=zone', '', '' );
add_submenu_page( 'edit.php?post_type=zone', 'Zone', 'Zone', 'edit_posts', 'edit.php?post_type=zone', '' );
add_submenu_page( 'edit.php?post_type=zone', 'Advertiser', 'Advertiser', 'edit_posts', 'edit.php?post_type=advertiser', '' ); 
add_submenu_page( 'edit.php?post_type=zone', 'Campaign', 'Campaign', 'edit_posts', 'edit.php?post_type=campaign', '' ); 
add_submenu_page( 'edit.php?post_type=zone', 'Banner', 'Banner', 'edit_posts', 'edit.php?post_type=ads_banner', '' );


}

//add current class to the menu
function set_aas_admin_menu_class(){
$screen = get_current_screen();
if(!in_array($screen->id , array('advertiser', 'campaign', 'ads_banner' , 'zone')))
return;

global $menu;

foreach($menu as $order => $m){
	if($m[2] == 'edit.php?post_type=zone'){
	$menu[$order][4] .= ' wp-has-current-submenu ';
	break;
	}
}

}
add_action('admin_menu','aas_admin_menu');
add_action('load-post.php', 'set_aas_admin_menu_class');
add_action('load-post-new.php', 'set_aas_admin_menu_class');

//Necessary scripts for backend
function add_aas_script(){   
	wp_enqueue_style( 'datetimepicker', AAS_PLUGIN_URL . 'js/datetimepicker-master/jquery.datetimepicker.css' );
	wp_enqueue_style( 'chosen', AAS_PLUGIN_URL . 'js/chosen/chosen.min.css' );
	wp_enqueue_script('my_validate', AAS_PLUGIN_URL . 'js/validator/jquery.validate.min.js', array('jquery'));
	wp_enqueue_script('datetimepicker', AAS_PLUGIN_URL . 'js/datetimepicker-master/jquery.datetimepicker.js', array('jquery'));
	wp_enqueue_script('chosen', AAS_PLUGIN_URL . 'js/chosen/chosen.jquery.min.js', array('jquery'));
 ?>

  <style>#toplevel_page_edit-post_type-zone .dashicons-admin-generic:before{content:"\f161";}</style>
  
  <?php

}
add_action('admin_enqueue_scripts', 'add_aas_script');  


//Creating log table
function create_aas_ads_log_table(){

global $wpdb;
$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}". AAS_Log::$log_table_name ."` (
  `logged_id` BIGINT NOT NULL AUTO_INCREMENT, 
  `ip_address` VARCHAR(255) NOT NULL, 
  `user_id` BIGINT NOT NULL,
  `referral_url` TEXT NOT NULL,
  `browser` VARCHAR(255) NOT NULL, 
  `device` VARCHAR(255) NOT NULL,
  `type` VARCHAR(1) NOT NULL, 
  `zone_id` INT NOT NULL, 
  `cam_id` INT NOT NULL, 
  `banner_id` INT NOT NULL, 
  `adv_id` INT NOT NULL, 
  `net_price` FLOAT NOT NULL, 
  `time` DATETIME NOT NULL,
  PRIMARY KEY (`logged_id`)
)
ENGINE = myisam;");


if(!$wpdb->query("SHOW COLUMNS FROM `{$wpdb->prefix}". AAS_Log::$log_table_name ."` LIKE 'user_id' "))
$wpdb->query("ALTER TABLE  `{$wpdb->prefix}". AAS_Log::$log_table_name ."` ADD  `user_id` BIGINT NOT NULL AFTER  `ip_address` ,
ADD  `referral_url` TEXT NOT NULL AFTER  `user_id` ;");
}
register_activation_hook( __FILE__, 'create_aas_ads_log_table' );

function aas_column_orderby( $query ) {  
		if( ! is_admin() )  
			return;  
		if(!in_array($query->get('post_type'),array('ads_banner','advertiser','campaign','zone')))
			return;
		$orderby = $query->get( 'orderby');  

		if( 'click' == $orderby ) {  
			$query->set('meta_key','_total_click');  
			$query->set('orderby','meta_value_num');  
		}  
		elseif('impression' == $orderby){
			$query->set('meta_key','_total_view');  
			$query->set('orderby','meta_value_num'); 
		}
		elseif('payment' == $orderby){
			$query->set('meta_key','_total_payment');  
			$query->set('orderby','meta_value_num'); 
		}
		elseif('priority' == $orderby){
			$query->set('meta_key','priority');  
			$query->set('orderby','meta_value_num');
		}
		elseif('ctr' == $orderby){
			$query->set('meta_key','_ctr');  
			$query->set('orderby','meta_value_num');
		}
} 
add_action( 'pre_get_posts', 'aas_column_orderby' );  


add_action('admin_init', 'aas_nag_dismiss');

function aas_nag_dismiss() {
	global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['aas_nag_dismiss']) && 1 == $_GET['aas_nag_dismiss'] ) {
            update_user_meta($user_id, 'aas_nag_dismiss', 1);
		}
}
function aas_pro_version_notices() {
	global $current_user;
$user_id = $current_user->ID;

if((isset($_GET['post_type']) && in_array($_GET['post_type'],array('ads_banner','zone','campaign','advertiser'))) || (isset($_GET['post']) && in_array(get_post_type($_GET['post']),array('ads_banner','zone','campaign','advertiser'))) ):
        /* Check that the user hasn't already clicked to ignore the message */

	if ( !get_user_meta($user_id, 'aas_nag_dismiss',true) ) {
    ?>
	
    <div class="updated" style="border-color:#f8af9b;position:relative;">
        <p><strong><?php _e( 'Advanced Advertising System is now available in PRO Version with many cool features !', AAS_TEXT_DOMAIN); ?></strong></p>
		<p><a href="http://www.smartdevth.com/advanced-advertising-system/" target="_blank" class="button-primary" style="background-color:#ff6b55;border-color:#f8af9b;"><?php _e('Check out PRO Version now  !',AAS_TEXT_DOMAIN);?></a></p>
		<a id="aas_pro_notice" href="<?php echo add_query_arg( array('aas_nag_dismiss' => '1') );?>" title="<?php _e('Dismiss this notice',AAS_TEXT_DOMAIN);?>" style="cursor:pointer;width: 11px;height:10px;background:pink;color: white;padding: 5px;position: absolute;right: 0;top: 0;border-radius: 10px;"><span style="position: relative;top: -5px;left: 2px;">x</span></a>
    </div>
    <?php
	}
	endif;
}
add_action( 'admin_notices', 'aas_pro_version_notices' );

function aas_add_thumbnails_support() {
	$supports = get_theme_support( 'post-thumbnails');
	if(!$supports)
	add_theme_support( 'post-thumbnails' );
	elseif(is_array($supports)){
	$support = current($supports);
	add_theme_support( 'post-thumbnails', array_merge(array('ads_banner','advertiser'),$support));
	}
	
}
add_action( 'after_setup_theme', 'aas_add_thumbnails_support' , 40 );