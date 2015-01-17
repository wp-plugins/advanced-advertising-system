<?php
//Include scripts for front-end display.
function aas_enqueue_jquery_slide(){

wp_enqueue_script('caroufredsel' , AAS_PLUGIN_URL . 'js/carouFredSel-6.2.1/jquery.carouFredSel-6.2.1-packed.js' ,array('jquery'));
wp_register_script( 'aas_frontend',  AAS_PLUGIN_URL . 'js/frontend.js' ,array('jquery'));
$url = array( 'url' => admin_url('admin-ajax.php') );
wp_localize_script( 'aas_frontend', 'ajax', $url );
wp_enqueue_script( 'aas_frontend' );
wp_enqueue_script('aas_frontend' , AAS_PLUGIN_URL . 'js/frontend.js');

}
add_action('wp_enqueue_scripts' , 'aas_enqueue_jquery_slide');

	/**
	 * Shortcode for displaying ads
	 *
	 */
function aas_zone_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'zone_id' => 0,
    ), $atts );
	$checker = get_post($a['zone_id']);
	if(!$checker || $checker->post_status != 'publish')
	return;
	$zone = new AAS_Shortcode($a['zone_id']);
    return $zone->html;
}
add_shortcode( 'aas_zone', 'aas_zone_shortcode' );

	/**
	 * Shortcode Class
	 *
	 */
class AAS_Shortcode{

	public $html;
	protected $zone_id;
	public $banners;
	protected $banner_advertiser;
	public $campaigns;
	protected $zone;
	public $zone_meta;
	protected static $zone_meta_key = array('zone_size','zone_rotation','zone_defaut_price' , 'zone_special_price');
	public static $cam_meta = array('campaign_end_date','budget_value','budget_type','total_impressions','person_impressions');
	protected $zone_order = 1;
	
	/**
	 * Constructor of shortcode class.
	 *
	 * @param int $zone_id ID of zone from a shortcode.
	 */

	function __construct($zone_id){
	$this->zone_id = $zone_id;
	$this->get_zone();
	$this->get_campaigns();
	$this->get_banners();
	$this->get_html();
	}

	function get_zone(){
	$this->zone = get_post($this->zone_id);
	foreach(self::$zone_meta_key as $key)
	$this->zone_meta[$key] = get_post_meta($this->zone->ID , $key , true);
	}

	function get_campaigns(){

	$query = new WP_Query(
		array('post_type'=>'campaign','posts_per_page'=> -1,'meta_key' => 'priority','orderby' => 'meta_value_num','order' => 'DESC' , 'meta_query' => array(
		array(
			'key'     => 'campaign_displaying',
			'value'   => $this->zone_id,
			'compare' => '=',
		),
	)
	));
	$this->campaigns = $query->posts;
	
	}

	// get banners from $this->campaigns.
	// Checking if each banner should be display.
	function get_banners(){
		$this->banners = array();
		foreach((array)$this->campaigns as $cam){
		if(self::is_available($cam->ID)){
			if($banner = get_posts(array('post_type'=>'ads_banner','posts_per_page'=> -1,'post_parent' => $cam->ID,'meta_key' => 'priority','orderby' => 'meta_value_num' , 'order' => 'DESC'))){
			$this->banners = array_merge($this->banners , $banner);
			$this->banner_advertiser[$cam->ID] = $cam->post_parent;
				}
			}
		}
		//if rotation type is random, then shuffle them.
		if($this->zone_meta['zone_rotation']['type']=='random')
		shuffle($this->banners);
	}

	function get_html(){
	$zone_size = explode('x',$this->zone_meta['zone_size']);
	$wrapper = '<div class="aas_zone" style="visibility:hidden" data-w="'.$zone_size[0].'" data-h="'.$zone_size[1].'" data-t="'. $this->zone_meta['zone_rotation']['timeout'].'">';
	$html = '';
	foreach((array)$this->banners as $banner){
		$custom = get_post_meta($banner->ID , 'custom_html' , true);
		if(empty($custom['enable']) || empty($custom['html']))
		$html .= '<a style="width:'.$zone_size[0].'px;height:'.$zone_size[1].'px;float:left;" class="aas_wrapper" href="'. $this->get_banner_link($banner) .'" style="overflow:hidden;" target="'.get_post_meta($banner->ID,'banner_target',true).'" data-ads="'.$banner->ID . '-'. $banner->post_parent .'-'.$this->banner_advertiser[$banner->post_parent] . '-' . $this->zone->ID  . '-' . $this->zone_order .'"  data-nonce="'.wp_create_nonce($banner->ID . '-'. $banner->post_parent .'-'.$this->banner_advertiser[$banner->post_parent].'-'.$this->zone->ID . '-' . $this->zone_order).'">';
		else
		$html .= '<div style="width:'.$zone_size[0].'px;height:'.$zone_size[1].'px;float:left;" class="aas_wrapper" style="overflow:hidden;" data-ads="'.$banner->ID . '-'. $banner->post_parent .'-'.$this->banner_advertiser[$banner->post_parent] . '-' . $this->zone->ID  . '-' . $this->zone_order .'"  data-nonce="'.wp_create_nonce($banner->ID . '-'. $banner->post_parent .'-'.$this->banner_advertiser[$banner->post_parent].'-'.$this->zone->ID . '-' . $this->zone_order).'">';


		if(empty($custom['enable']) || empty($custom['html']))
		$html .= wp_get_attachment_image(get_post_thumbnail_id($banner->ID) , 'full');
		elseif(!empty($custom['html']))
		$html .= str_replace('%link%', $this->get_banner_link($banner) , $custom['html']);

		if(empty($custom['enable']) || empty($custom['html']))
		$html .= '</a>';
		else
		$html .= '</div>';

		$this->zone_order = $this->zone_order + 1 ;
		}
	$wrapper_end = '</div>';
	$this->html = $wrapper . $html . $wrapper_end ;

	}

	function get_banner_link($banner){
	$query_args['ads_click'] = '1';
	$query_args['data'] = $banner->ID . '-' . $banner->post_parent . '-' . $this->banner_advertiser[$banner->post_parent] . '-' . $this->zone->ID . '-' . $this->zone_order;
	$query_args['nonce'] = wp_create_nonce($query_args['data']); // create nonce can reduce some spammed log
	$query_args['redir'] = urlencode(get_post_meta($banner->ID,'banner_link',true));
	return add_query_arg($query_args, home_url());
	}

	/**
	 * Check if campaign is available or shouldbe display.
	 *
	 * @param int $campaign_id ID of campaign.
	 */
	static function is_available($campaign_id){
		if($campaign_id==0)
			return true;
		if(!($campaign = get_post($campaign_id)))
			return false;
		$now = current_time('mysql');
		foreach(self::$cam_meta as $data){
		$meta[$data] = get_post_meta($campaign_id, $data ,true);
		}
		if($campaign->post_status != 'publish')
		return false;
		if(!empty($meta['campaign_end_date']) && strtotime($meta['campaign_end_date']) < strtotime($now))
		return false;

		$log = AAS_Log::get_log_by('cam_id',$campaign_id);
		$person = unserialize(stripslashes($_COOKIE['view_aas_campaigns'])); // Check a person from his cookie.
		if($log->num >= $meta['total_impressions'] && $meta['total_impressions'] > 0)
		return false;
		if(isset($person['cam_id']) && isset($person[$campaign_id]) && $person[$campaign_id] >= $meta['person_impressions'] && $meta['person_impressions'] > 0)
		return false;

		if($meta['budget_type']=='life_time'){
			if($meta['budget_value'] > 0 && $log->payment >= $meta['budget_value'])
			return false;
		}
		elseif($meta['budget_type']=='per_day'){
			$con = date('Y-m-d 00:00:00',strtotime($now));
			$log_perday = AAS_Log::get_log_by('cam_id',$campaign_id,'i',$con);
			if($meta['budget_value'] > 0  && $log_perday->payment >= $meta['budget_value'])
			return false;
		}
		
		return true;
		
	}
}