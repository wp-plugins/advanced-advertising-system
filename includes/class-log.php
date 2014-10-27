<?php
/**
 * Log System
 *
 */
class AAS_Log{

	

	/**
	 * Holds the lists of recent device and also os.
	 *
	 * @var array
	 * @access protected
	 */
	protected $device_array = array(
						'/windows nt 6.2/i'     =>  'Windows 8',
                        '/windows nt 6.1/i'     =>  'Windows 7',
                        '/windows nt 6.0/i'     =>  'Windows Vista',
                        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                        '/windows nt 5.1/i'     =>  'Windows XP',
                        '/windows xp/i'         =>  'Windows XP',
                        '/windows nt 5.0/i'     =>  'Windows 2000',
                        '/windows me/i'         =>  'Windows ME',
                        '/win98/i'              =>  'Windows 98',
                        '/win95/i'              =>  'Windows 95',
                        '/win16/i'              =>  'Windows 3.11',
                        '/macintosh|mac os x/i' =>  'Mac OS X',
                        '/mac_powerpc/i'        =>  'Mac OS 9',
                        '/linux/i'              =>  'Linux',
                        '/ubuntu/i'             =>  'Ubuntu',
                        '/iphone/i'             =>  'iPhone',
                        '/ipod/i'               =>  'iPod',
                        '/ipad/i'               =>  'iPad',
                        '/android/i'            =>  'Android',
                        '/blackberry/i'         =>  'BlackBerry',
                        '/webos/i'              =>  'Mobile'
	);

	/**
	 * Holds the lists of popular browsers.
	 *
	 * @var array
	 * @access protected
	 */
	protected $browser_array = array(
						'/msie/i'       =>  'Internet Explorer',
                        '/firefox/i'    =>  'Firefox',
                        '/chrome/i'     =>  'Chrome',
                        '/opera/i'      =>  'Opera',
						'/safari/i'     =>  'Safari',
                        '/netscape/i'   =>  'Netscape',
                        '/maxthon/i'    =>  'Maxthon',
                        '/konqueror/i'  =>  'Konqueror',
                        '/mobile/i'     =>  'Handheld Browser'
	);

	protected $device;

	protected $browser;

	/**
	 * Log table name without prefix
	 *
	 * Also use when creating log table.
	 *
	 * @var string
	 * @access public
	 */

	public static $log_table_name = 'aas_logged';
	
	/**
	 * Log Table Name 
	 *
	 * Dynamically change when table prefix has changed.
	 *
	 * @var string
	 * @access protected
	 */

	protected $log_table;

	protected $ads_data;
	
	/**
	 * Log Type 
	 *
	 * Can be 'i' or 'c'
	 *
	 * 'i' stands for impression
	 *
	 * 'c' stands for click
	 *
	 * @var string
	 * @access protected
	 */

	protected $type;

	/**
	 * Constructor of log
	 *
	 * @param string $data The data from either aas_ads_click() or aas_ads_view
	 */

	function __construct($data,$type='i'){
	global $wpdb;
	$this->log_table =  $wpdb->prefix . self::$log_table_name;
	$this->check_browser();
	if($this->manage_data($data,$type))
		$this->add_log();
	}
	
	function manage_data($data,$type){
	$this->type = $type;
	$data = explode('-',$data);
	if(count($data)!= 5)
	return false;
	$this->ads_data['banner_id'] = intval($data[0]); 
	$this->ads_data['cam_id'] = intval($data[1]); 
	$this->ads_data['adv_id'] = intval($data[2]); 
	$this->ads_data['zone_id'] = intval($data[3]); 
	$this->ads_data['zone_order'] = intval($data[4]); 
	return true;
	}
	
	function check_browser(){
	

	foreach($this->device_array as $pattern => $each){
		if(preg_match($pattern , $_SERVER['HTTP_USER_AGENT'])){
		$os = $each;
		break;
		}
	}
	foreach($this->browser_array as $pattern => $each){
		if(preg_match($pattern , $_SERVER['HTTP_USER_AGENT'])){
		$browser = $each;
		break;
		}
	}
		$this->device = $os;
		$this->browser = $browser;
	if(empty($os))
		$this->device = 'Unknown Device';
	if(empty($browser))
		$this->browser = 'Unknown Browser';
	}

	function add_log(){
	global $wpdb;
	if(AAS_Shortcode::is_available($this->ads_data['cam_id'])){ // See AAS_Shortcode::is_available() in shortcode.php
		$log = unserialize(stripslashes($_COOKIE['view_aas_campaigns']));
		$log[$this->ads_data['cam_id']] = isset($log[$this->ads_data['cam_id']]) ? $log[$this->ads_data['cam_id']]+1 : 1;
		setcookie('view_aas_campaigns', serialize($log) , current_time('timestamp') + (86400 * 30), '/');
		$net_price = $this->get_price();
		$modules = array('banner_id', 'zone_id', 'cam_id', 'adv_id');
		$count_type = $this->type == 'i' ? '_total_view' : '_total_click';
		foreach($modules as $module){
			$old = (float)get_post_meta($this->ads_data[$module], '_total_payment', true);
			update_post_meta($this->ads_data[$module], '_total_payment', $old+$net_price );
			$old = (int)get_post_meta($this->ads_data[$module], $count_type, true);
			update_post_meta($this->ads_data[$module], $count_type, $old+1 );
			$v = get_post_meta($this->ads_data[$module], '_total_view', true);
			if($v > 0){
			$c = get_post_meta($this->ads_data[$module], '_total_click', true);
			update_post_meta($this->ads_data[$module], '_ctr', round($c*100/$v,2) );
			}
			else
			update_post_meta($this->ads_data[$module], '_ctr', 0 );
			}

		

		$id = $wpdb->insert($this->log_table, 
		array( 
			'ip_address' => $_SERVER['REMOTE_ADDR'], 
			'browser' => $this->browser,
			'device' => $this->device,
			'type' => $this->type,
			'zone_id' => $this->ads_data['zone_id'],
			'cam_id' => $this->ads_data['cam_id'],
			'banner_id' => $this->ads_data['banner_id'],
			'adv_id' => $this->ads_data['adv_id'],
			'net_price' => $net_price ,
			'time' => current_time('mysql')
		), 
		array( 
			'%s', 
			'%s',
			'%s', 
			'%s',
			'%d', 
			'%d',
			'%d', 
			'%d',
			'%f',
			'%s'
		) );
	


		}
	}

	
	/**
	 * Log query
	 *
	 *
	 * @param string $type Can be 'cam_id', 'zone_id', 'adv_id' or 'banner_id'
	 *
	 * @param int $id The ID of $type
	 *
	 * @param string $log_type 'i' or 'c'
	 *
	 * @param datetime $time_con Add condition since this value
	 *
	 */

	static function get_log_by($type, $id, $log_type='i' , $time_con = ''){
	global $wpdb;
	$log_table = $wpdb->prefix . self::$log_table_name;
	if($time_con)
	$time_con = " AND time >= '$time_con' ";
	return $wpdb->get_row("SELECT COUNT(logged_id) as num , SUM(net_price) as payment FROM $log_table WHERE $type = $id AND type = '{$log_type}' $time_con");
	
	}

	/**
	 * Get price for zone
	 *
	 * Special Price is used for more important campaign or banner
	 *
	 */

	function get_price(){
	$special_price = get_post_meta($this->ads_data['zone_id'],'zone_special_price',true);
	$rotation = get_post_meta($this->ads_data['zone_id'],'rotation',true);
	$model = get_post_meta($this->ads_data['cam_id'],'pricing_model',true);
	$log = self::get_log_by('cam_id',$this->ads_data['cam_id']);
	if(($model == 'cpm' && $this->type == 'i' &&  ($log->num%1000) == 999) || ($model == 'cpc' && $this->type == 'c')){

		if(isset($special_price[$this->ads_data['zone_order']][$model]) && $rotation['type'] != 'random')
		return $special_price[$this->ads_data['zone_order']][$model];
		else{
		$normal_price = get_post_meta($this->ads_data['zone_id'],'zone_default_price',true);
		return isset($normal_price[$model]) ? $normal_price[$model] : 0;
		}

	}
	return 0;

	}

}

// When clicking a banner
function aas_ads_click(){
if(!isset($_GET['ads_click']))
return;
if(wp_verify_nonce($_GET['nonce'],$_GET['data']))
new AAS_Log($_GET['data'],'c');
if(!empty($_GET['redir']))
wp_redirect(urldecode($_GET['redir']));
}
add_action('template_redirect','aas_ads_click');

// When viewing a banner, then sending via ajax for logging.
function aas_ads_view(){
	
if(wp_verify_nonce($_POST['nonce'],$_POST['data']))
new AAS_Log($_POST['data'],'i');


wp_die();

}
add_action('wp_ajax_aas_view_log' , 'aas_ads_view');
add_action('wp_ajax_nopriv_aas_view_log' , 'aas_ads_view');
