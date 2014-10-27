<?php
/**
 * Zone Module
 *
 */
class AAS_Zone{
	
	protected $rotation_type; //priority and random

	/**
	 * Construct backend for zone
	 */
	function __construct(){

		$this->rotation_type = array('priority' => __('Priority', AAS_TEXT_DOMAIN) , 'random' => __('Random',AAS_TEXT_DOMAIN) );
		add_action('init' , array(&$this,'zone_register'));
		add_action( 'save_post', array(&$this,'zone_save_meta') );
		add_filter('post_updated_messages', array(&$this,'zone_updated_messages') );
		add_filter( 'manage_edit-zone_columns', array(&$this, 'set_custom_edit_zone_columns' ) );
		add_filter( 'manage_edit-zone_sortable_columns', array(&$this,'zone_manage_sortable_columns') );
		add_action( 'manage_zone_posts_custom_column' , array(&$this, 'custom_zone_column' ), 10, 2 );
	
	}

	function zone_register(){
	
	$labels = array(
		'name'               => _x( 'Zones', 'post type general name', AAS_TEXT_DOMAIN ),
		'singular_name'      => _x( 'Zone', 'post type singular name', AAS_TEXT_DOMAIN ),
		'menu_name'          => _x( 'Zones', 'admin menu', AAS_TEXT_DOMAIN ),
		'name_admin_bar'     => _x( 'Zone', 'add new on admin bar', AAS_TEXT_DOMAIN ),
		'add_new'            => _x( 'Add New', 'book', AAS_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Add New Zone', AAS_TEXT_DOMAIN ),
		'new_item'           => __( 'New Zone', AAS_TEXT_DOMAIN ),
		'edit_item'          => __( 'Edit Zone', AAS_TEXT_DOMAIN ),
		'view_item'          => __( 'View Zone', AAS_TEXT_DOMAIN),
		'all_items'          => __( 'All Zones', AAS_TEXT_DOMAIN ),
		'search_items'       => __( 'Search Zones', AAS_TEXT_DOMAIN ),
		'parent_item_colon'  => __( 'Parent Zones:', AAS_TEXT_DOMAIN ),
		'not_found'          => __( 'No zones found.', AAS_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'No zones found in Trash.', AAS_TEXT_DOMAIN )
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'exclude_from_search' => true,
		'register_meta_box_cb' => array(&$this , 'zone_add_meta_box'),
		'supports'           => array( 'title' )
	);

	register_post_type( 'zone', $args );
	
	
	}
	
	
	function zone_updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages['zone'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Zone updated.' , AAS_TEXT_DOMAIN) ,
		6 => __('Zone published.' , AAS_TEXT_DOMAIN) ,
		8 => __('Zone submitted.' , AAS_TEXT_DOMAIN),
		9 => sprintf( __('Zone scheduled for: <strong>%1$s</strong>.' , AAS_TEXT_DOMAIN),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 =>  __('Zone draft updated.', AAS_TEXT_DOMAIN)
	  );

	  return $messages;
	}

	function zone_add_meta_box(){
	


	add_meta_box(
			'aas_zone',
			__( 'Zone Details', AAS_TEXT_DOMAIN ),
			array(&$this,'zone_meta_box'),
			'zone' , 'normal' , 'high'
		);
	add_meta_box(
			'aas_zone_overview',
			__( 'Zone Overview', AAS_TEXT_DOMAIN ),
			array(&$this,'zone_overview_box'),
			'zone' , 'side' , 'high'
		);
	add_meta_box(
			'aas_zone_campaign',
			__( 'Attached Campaigns', AAS_TEXT_DOMAIN ),
			array(&$this,'zone_campaign_box'),
			'zone' , 'side' , 'default'
		);
	
	}

	function zone_meta_box($post){
		wp_nonce_field( 'zone_meta_box', 'zone_meta_box_nonce' );
		$size = explode('x',get_post_meta($post->ID , 'zone_size' ,true));
		$zone_size['width'] = isset($size[0]) ? $size[0] : '';
		$zone_size['height'] = isset($size[1]) ? $size[1] : '';
		$zone_d_price = get_post_meta($post->ID , 'zone_default_price' ,true);
		$zone_s_price = get_post_meta($post->ID , 'zone_special_price' ,true);
		$zone_rotation = get_post_meta($post->ID , 'zone_rotation' ,true);
	?>
	<style>.no_underline{text-decoration:none;}.slot_label{margin-right:4px;}.meta_text{width:98%;}.error{color:red;}.aas_description{color:gray;font-size:smaller;font-style:italic;}.f-right{float:right;}.red{color:red;}.green{color:green;}</style>
	<script>
	jQuery(document).ready(function(){
			jQuery("#post").validate();
		jQuery("#add_s_price").live('click',function(e){
			e.preventDefault();
			var n = jQuery("#s_price_wrapper > p").length;
		var s_price = '<p><span class="slot_label"><?php _e('Slot',AAS_TEXT_DOMAIN) ;?> #'+ (n+1) +'</span><input type="number" min="0" name="zone_special_price['+ (n+1) +'][cpc]" step="0.05" placeholder="<?php _e('CPC' , AAS_TEXT_DOMAIN); ?>" required/><input type="number" min="0" name="zone_special_price['+ (n+1) +'][cpm]" step="0.05" placeholder="<?php _e('CPM' , AAS_TEXT_DOMAIN); ?>" required/></p>';
		jQuery("#s_price_wrapper").append(s_price);
		});
		jQuery("#remove_s_price").live('click',function(e){
		e.preventDefault();
		jQuery("#s_price_wrapper > p").last().remove();
		});
		jQuery('#zone_rotation-type').live('change',function(){
			if(jQuery(this).val() == 'random')
			jQuery("#sprice").fadeOut();
			else
			jQuery("#sprice").fadeIn();
		
		});
	});	
	</script>
	<p>
	<label><strong><?php _e('Zone Size',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<input type="number" min="1" name="zone_size[width]" value="<?php echo isset($zone_size['width']) ? $zone_size['width'] : '';?>" placeholder="<?php _e('Width' , AAS_TEXT_DOMAIN); ?>" required/>
	<input type="number" min="1" name="zone_size[height]" value="<?php echo isset($zone_size['height']) ? $zone_size['height'] : '';?>" placeholder="<?php _e('Height' , AAS_TEXT_DOMAIN); ?>" required/>
	</p>
	<p>
	<label for="zone_rotation-type"><strong><?php _e('Rotation Type',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<select name="zone_rotation[type]" id="zone_rotation-type">
	<?php foreach($this->rotation_type as $value => $type){?>
	<option value="<?php echo $value ?>" <?php if(isset($zone_rotation['type']))selected($zone_rotation['type'],$value);?> ><?php echo $type;?></option>
	<?php }?>
	</select>
	</p>
	<p>
	<label><strong><?php _e('Rotation Timeout',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('Timeout when rotating a banner in seconds',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="number" min="1" name="zone_rotation[timeout]" value="<?php echo isset($zone_rotation['timeout']) ? $zone_rotation['timeout'] : 10;?>" placeholder="<?php _e('Width' , AAS_TEXT_DOMAIN); ?>" required />
	</p>
	<p>
	<label><strong><?php _e('Default Price', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('If this zone has no special price, this price will be used as default.',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="number" min="0" step="0.05" name="zone_default_price[cpc]" value="<?php echo isset($zone_d_price['cpc']) ? $zone_d_price['cpc'] : '';?>" placeholder="<?php _e('CPC' , AAS_TEXT_DOMAIN); ?>" required/>
	<input type="number" min="0" step="0.05" name="zone_default_price[cpm]" value="<?php echo isset($zone_d_price['cpm']) ? $zone_d_price['cpm'] : '';?>" placeholder="<?php _e('CPM' , AAS_TEXT_DOMAIN); ?>" required/>
	</p>
	<p id="sprice" <?php echo isset($zone_rotation['type']) && $zone_rotation['type']=='random' ? 'style="display:none;"' : '';?>>
	<label><strong><?php _e('Special Price', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('If slot #1,#2,#3,...,#n have different prices then you should specify the special price.',AAS_TEXT_DOMAIN);?></span><br/>
	<button id="add_s_price">+</button><button id="remove_s_price" style="">-</button>
	<div id="s_price_wrapper">
	<?php if(is_array($zone_s_price)){
	foreach($zone_s_price as $key=>$price):	
	?>
	<p><span class="slot_label"><?php _e('Slot',AAS_TEXT_DOMAIN) ;?> #<?php echo $key;?></span><input type="number" min="0" step="0.05" name="zone_special_price[<?php echo $key;?>][cpc]" value="<?php echo isset($price['cpc']) ? $price['cpc'] : '';?>" placeholder="<?php _e('CPC' , AAS_TEXT_DOMAIN); ?>" required/>
	<input type="number" min="0" step="0.05" name="zone_special_price[<?php echo $key;?>][cpm]" value="<?php echo isset($price['cpm']) ? $price['cpm'] : '';?>" placeholder="<?php _e('CPM' , AAS_TEXT_DOMAIN); ?>" required/></p>
	<?php endforeach; 
	} ?>
	</div>
	</p>
	<?php
	}

	function zone_overview_box($post){
	global $wpdb;
	$banner_num=$wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent IN (SELECT post_id from {$wpdb->prefix}postmeta WHERE meta_key = 'campaign_displaying' AND meta_value={$post->ID}) AND post_type = 'ads_banner'");
	?>
	<p><strong><?php _e('Costs occured in zone: ' , AAS_TEXT_DOMAIN)?></strong><span><?php echo @AAS_Log::get_log_by('zone_id' , $post->ID)->payment + @AAS_Log::get_log_by('zone_id' , $post->ID,'c')->payment;?></span></p>
	<p><strong><?php _e('CTR Rate: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (float)get_post_meta($post->ID, '_ctr',true) . '%';?></span></p>
	<p><strong><?php _e('Total Clicks: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_click',true);?></span></p>
	<p><strong><?php _e('Total Impressions: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_view',true);?></span></p>
	<p><strong><?php _e('Total Banners: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo $banner_num;?></span></p>
	<p><strong><?php _e('Shortcode: ', AAS_TEXT_DOMAIN)?><?php echo '[aas_zone zone_id="'.$post->ID.'"]';?></strong></p>
	<?php
	}

	function zone_campaign_box($post){
		global $wpdb;
		$camIds=$wpdb->get_col("SELECT post_id from {$wpdb->prefix}postmeta WHERE meta_key = 'campaign_displaying' AND meta_value={$post->ID}");
		foreach($camIds as $id):
			$cam = get_post($id);
		$status = $cam->post_status == 'publish' ? 'green' : 'red';
	?>
	<p><a target="_blank" href="<?php echo  get_edit_post_link( $cam->ID ); ?>" class="no_underline"><?php echo __('Campaign : ',AAS_TEXT_DOMAIN) . $cam->post_title;?></a><span title="<?php _e('Campaign status' ,AAS_TEXT_DOMAIN) ;?>" class="f-right <?php echo $status ;?>"><?php echo $status=='green' ? __('Active',AAS_TEXT_DOMAIN) : __('Inactive',AAS_TEXT_DOMAIN);?></span></p>
	<?php
			endforeach;
	}

	function zone_save_meta($post_id){
	
	if ( ! isset( $_POST['zone_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['zone_meta_box_nonce'], 'zone_meta_box' ) ) {
		return;
	}

	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	}
		if(is_numeric($_POST['zone_size']['width']) && is_numeric($_POST['zone_size']['height']))
		update_post_meta( $post_id, 'zone_size' , $_POST['zone_size']['width'] . 'x' . $_POST['zone_size']['height'] );
		update_post_meta( $post_id, 'zone_default_price' , $_POST['zone_default_price'] );
		update_post_meta( $post_id, 'zone_special_price' , $_POST['zone_special_price'] );
		update_post_meta( $post_id, 'zone_rotation' , $_POST['zone_rotation'] );
		$d_types = array('_total_payment', '_total_view', '_total_click');
		foreach($d_types as $t){
		if(!is_numeric( $$t = get_post_meta( $post_id, $t, true)))
		update_post_meta( $post_id, $t ,0 );
		}
		if($_total_view > 0)
		update_post_meta( $post_id, '_ctr' , round($_total_click*100/$_total_view, 2 ) );
		else
		update_post_meta( $post_id, '_ctr' , 0  );

	}
	function set_custom_edit_zone_columns($columns) {
		$date =  $columns['date'];
		unset( $columns['date'] );
		$columns['width'] = __('Width',AAS_TEXT_DOMAIN);
		$columns['height'] = __('Height',AAS_TEXT_DOMAIN);
		$columns['ctr'] = __('CTR',AAS_TEXT_DOMAIN);
		$columns['click'] = __('Clicks',AAS_TEXT_DOMAIN);
		$columns['impression'] = __('Impressions',AAS_TEXT_DOMAIN);
		$columns['campaign'] = __('Attached Campaign',AAS_TEXT_DOMAIN);
		$columns['shortcode'] = __('Shortcode',AAS_TEXT_DOMAIN);
		$columns['date'] = $date;
		return $columns;
	}

	function custom_zone_column( $column, $post_id ) {
	
		switch ( $column ) {
			case 'width':
				$size = explode('x',get_post_meta($post_id, 'zone_size', true));
				echo $size[0];
			break;
			case 'height':
				$size = explode('x',get_post_meta($post_id, 'zone_size', true));
				echo $size[1];
			break;
			case 'ctr':
				echo (float)get_post_meta($post_id , '_ctr' , true) . '%';
			break;
			case 'click':
			echo (int)get_post_meta($post_id , '_total_click' , true);
			break;
			case 'impression':
			echo (int)get_post_meta($post_id , '_total_view' , true);
			break;
			case 'campaign' :
			global $wpdb;
			$camIds=$wpdb->get_col("SELECT post_id from {$wpdb->prefix}postmeta WHERE meta_key = 'campaign_displaying' AND meta_value={$post_id}");
			foreach($camIds as $id):
			$cam = get_post($id);
			?>
		<a target="_blank" href="<?php echo  get_edit_post_link( $cam->ID ); ?>" class="no_underline">• <?php echo $cam->post_title;?></a></br>
		<?php
				endforeach;
			break;
			case 'shortcode':
			echo '[aas_zone zone_id="'.$post_id.'"]';
			break;
		}
	}
	function zone_manage_sortable_columns( $sortable_columns ) {

		$sortable_columns[ 'width' ] = 'width';
		$sortable_columns[ 'height' ] = 'height';
		$sortable_columns[ 'ctr' ] = 'ctr';
		$sortable_columns[ 'click' ] = 'click';
		$sortable_columns[ 'impression' ] = 'impression';
		return $sortable_columns;
	}
	
}
new AAS_Zone;