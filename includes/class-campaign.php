<?php
/**
 * Campaign Module
 *
 */
class AAS_Campaign{
	
	static protected $default_value = array('pricing_model' => 'cpc' , 'priority' => 5 , 'total_impressions' => 0 , 'person_impressions' => 0 , 'hour_reset' => 0 , 'budget_value' => 0 , 'campaign_end_date' => '' , 'budget_type' => 'life_time');
	static public $max_priority = 10; //Mutually use with banner

	/**
	 * Construct backend for campaign
	 */

	function __construct(){
		

		add_action('init' , array(&$this,'campaign_register'));
		add_filter('post_updated_messages', array(&$this,'campaign_updated_messages') );
		add_action( 'save_post', array(&$this,'campaign_save_meta') );
		add_filter( 'manage_edit-campaign_columns', array(&$this, 'set_custom_edit_campaign_columns' ) );
		add_filter( 'manage_edit-campaign_sortable_columns', array(&$this,'campaign_manage_sortable_columns') );
		add_action( 'manage_campaign_posts_custom_column' , array(&$this, 'custom_campaign_column' ), 10, 2 );
		add_action( 'wp_ajax_get_aas_zone_by_size' , array(&$this , 'get_zone_by_size'));
	
	}


	function campaign_register(){
	
	$labels = array(
		'name'               => _x( 'Campaigns', 'post type general name', AAS_TEXT_DOMAIN ),
		'singular_name'      => _x( 'Campaign', 'post type singular name', AAS_TEXT_DOMAIN ),
		'menu_name'          => _x( 'Campaigns', 'admin menu', AAS_TEXT_DOMAIN ),
		'name_admin_bar'     => _x( 'Campaign', 'add new on admin bar', AAS_TEXT_DOMAIN ),
		'add_new'            => _x( 'Add New', 'book', AAS_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Add New Campaign', AAS_TEXT_DOMAIN ),
		'new_item'           => __( 'New Campaign', AAS_TEXT_DOMAIN ),
		'edit_item'          => __( 'Edit Campaign', AAS_TEXT_DOMAIN ),
		'view_item'          => __( 'View Campaign', AAS_TEXT_DOMAIN),
		'all_items'          => __( 'All Campaigns', AAS_TEXT_DOMAIN ),
		'search_items'       => __( 'Search Campaigns', AAS_TEXT_DOMAIN ),
		'parent_item_colon'  => __( 'Parent Campaigns:', AAS_TEXT_DOMAIN ),
		'not_found'          => __( 'No campaigns found.', AAS_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'No campaigns found in Trash.', AAS_TEXT_DOMAIN )
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
		'register_meta_box_cb' => array(&$this , 'campaign_add_meta_box'),
		'supports'           => array( 'title' )
	);

	register_post_type( 'campaign', $args );
	
	
	}
	
	
	function campaign_updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages['campaign'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Campaign updated.' , AAS_TEXT_DOMAIN) ,
		6 => __('Campaign published.' , AAS_TEXT_DOMAIN) ,
		8 => __('Campaign submitted.' , AAS_TEXT_DOMAIN),
		9 => sprintf( __('Campaign scheduled for: <strong>%1$s</strong>.' , AAS_TEXT_DOMAIN),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 =>  __('Campaign draft updated.', AAS_TEXT_DOMAIN)
	  );

	  return $messages;
	}

	function campaign_add_meta_box(){
	
	add_meta_box(
			'aas_campaign',
			__( 'Campaign Details', AAS_TEXT_DOMAIN ),
			array(&$this,'campaign_meta_box'),
			'campaign' , 'normal' , 'high'
		);
	add_meta_box(
			'aas_campaign_overview',
			__( 'Campaign Overview', AAS_TEXT_DOMAIN ),
			array(&$this,'campaign_overview_box'),
			'campaign' , 'side' , 'high'
		);
	add_meta_box(
			'aas_campaign_banner',
			__( 'Banner List', AAS_TEXT_DOMAIN ),
			array(&$this,'campaign_banner_box'),
			'campaign' , 'side' , 'default'
		);
	
	}

	function campaign_meta_box($post){
		wp_nonce_field( 'campaign_meta_box', 'campaign_meta_box_nonce' );
		$advertisers = get_posts(array('post_type' => 'advertiser' , 'posts_per_page' => -1));
		$owner = $post->post_parent ? $post->post_parent : (isset($_GET['owner']) ? intval($_GET['owner']) : 0);
		$zones = get_post_meta($post->ID , 'campaign_displaying');
		$campaign_size = get_post_meta($post->ID , 'campaign_size' , true);
		foreach(self::$default_value as $key => $d){
		$$key = get_post_meta($post->ID ,$key , true);
		$$key = $$key  ? $$key  : $d;
		}
	?>
	<style>.no_underline{text-decoration:none;}.meta_select{width:50%;}.meta_text{width:98%;}.error{color:red;}.chosen-container-multi .chosen-choices li.search-field input[type=text] {height:inherit;}.aas_description{color:gray;font-size:smaller;font-style:italic;}.f-right{float:right;}.red{color:red;}.green{color:green;}</style>
	<script>
	jQuery(document).ready(function() {
		jQuery("#post").validate();
		jQuery("#campaign_end_date").datetimepicker({
			format : 'Y/m/d H:i',
			onShow:function(){
			 this.setOptions({
			 minDate:jQuery("#aa").val() + '/' + jQuery("#mm").val() + '/' + jQuery("#jj").val()
			 });
		  }
		});
		jQuery(".multiple-select").chosen();
		jQuery("#campaign_size").chosen().change(function(){
				var data = {
						  action : 'get_aas_zone_by_size',
						  size : jQuery(this).val(),
						  id : jQuery("#post_ID").val()
						  }
		jQuery('<p id="aas-ajax-loading"><img src="<?php echo AAS_PLUGIN_URL; ?>image/ajax-loader.gif"></p>').insertAfter('#campaign_displaying_chosen');
		jQuery('#campaign_displaying_chosen,#campaign_displaying').remove();
			jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", data, function(response) { 
					jQuery(response).insertBefore('#aas-ajax-loading');
					jQuery(".multiple-select").chosen();
					jQuery("#aas-ajax-loading").remove();
			});	
		
		});
		jQuery("input[name=pricing_model]").live("click",function(){
		if(jQuery(this).val() == 'cpp'){
		jQuery("select[name=budget_type] option[value=per_day]").attr('disabled','disabled');
		jQuery("select[name=budget_type]").val('life_time');
		jQuery("select[name=budget_type]").trigger('change');
		}
		else
		jQuery("select[name=budget_type] option[value=per_day]").removeAttr('disabled');
		});
	});
	</script>
	<p>
	<label for="owner"><strong><?php _e('Campaign Owner',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('Select the owner(advertiser) of this campaign',AAS_TEXT_DOMAIN);?></span><br/>
	<select class="meta_select" id="owner"  name="owner">
		<option value="">None</option>
		<?php foreach($advertisers as $advertiser){?>
		<option value="<?php echo $advertiser->ID;?>" <?php selected($owner,$advertiser->ID);?> ><?php echo $advertiser->post_title;?></option>
		<?php } ?>
	</select>
	</p>
	<p>
	<label for="campaign_displaying"><strong><?php _e('Displaying',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('Attach this campaign to zone. You can filter zone by choosing a size.',AAS_TEXT_DOMAIN);?></span><br/>
	<?php global $wpdb; $sizes = array_unique($wpdb->get_col("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'zone_size' AND post_id IN (SELECT ID FROM $wpdb->posts where post_type = 'zone' AND post_status = 'publish') order by meta_value ASC"));?>
	<?php $all_zones = get_posts(array('posts_per_page' => -1,'post_type' => 'zone','orderby' => 'title','order' => 'ASC','meta_key' => 'zone_size','meta_value' => !empty($campaign_size) && in_array($campaign_size,$sizes) ? $campaign_size : $sizes[0]));?>
	<?php if(!empty($all_zones)){?>
	<select data-placeholder="<?php _e('Size' , AAS_TEXT_DOMAIN);?>"  class="meta_select" name="campaign_size" id="campaign_size" >
	<?php 
		foreach((array)$sizes as $size){?>
		<option value="<?php echo $size;?>" <?php selected($campaign_size,$size);?> ><?php echo $size;?></option>
	<?php } ?>
	</select>
	<select data-placeholder="<?php _e('Choose zones' , AAS_TEXT_DOMAIN);?>" multiple class="meta_select multiple-select" name="campaign_displaying[]" id="campaign_displaying" >
		<?php
	
	foreach($all_zones as $zone){?>
		<option value="<?php echo $zone->ID;?>" <?php echo in_array($zone->ID,$zones) ? 'selected' : '';?> ><?php echo $zone->post_title;?></option>
		<?php } ?>
	</select>
	<?php } else {?>
	<span style="color:red;"><strong><?php _e('No zone published or created. However, you can insert zone and choose this field later.',AAS_TEXT_DOMAIN);?></strong></span>
	<?php } ?>
	</p>
	<p>
	<label><strong><?php _e('Pricing Model',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('Select how cost occurs. The value of cost can be set in a zone section.',AAS_TEXT_DOMAIN);?></span><br/>
	<label for="cpc"><input type="radio" id="cpc" name="pricing_model" value="cpc" <?php checked($pricing_model , 'cpc');?> /><?php _e('CPC - Cost per click',AAS_TEXT_DOMAIN);?></label><br/>
	<label for="cpm"><input type="radio" id="cpm" name="pricing_model" value="cpm" <?php checked($pricing_model , 'cpm');?> /><?php _e('CPM - Cost occurs every 1,000 of impressions',AAS_TEXT_DOMAIN);?></label><br/>
	<label for="cpp"><input type="radio" id="cpp" name="pricing_model" value="cpp" <?php checked($pricing_model , 'cpp');?> /><?php _e('CPP - Cost per period : Costs from clicks or impressions won\'t occur. An end date should be set if you choose this option',AAS_TEXT_DOMAIN);?></label>
	</p>
	<p>
	<label><strong><?php _e('Budget');?></strong></label><br/>
	<span class="aas_description"><?php _e('Select budget type and value. Budget is a limitation of displaying. Whenever the costs reach budget value, the campaign will not display.',AAS_TEXT_DOMAIN);?></span><br/>
	<select name="budget_type"><option value="life_time" <?php selected($budget_type,'life_time');?> ><?php _e('Life Time',AAS_TEXT_DOMAIN);?></option><option value="per_day" <?php echo $pricing_model=='cpp' ? 'disabled' : '';?> <?php selected($budget_type,'per_day');?> ><?php _e('Per Day',AAS_TEXT_DOMAIN);?></option></select>
	<input style="width:135px;position: relative;top: 2px;" type="number" min="0" name="budget_value" placeholder="<?php _e('Budget value' , AAS_TEXT_DOMAIN);?>" value="<?php echo $budget_value;?>" required/>
	</p>
	<p>
	<label for="campaign_end_date"><strong><?php _e('Schedule');?></strong></label><br/>
	<span class="aas_description"><?php _e('Set an end date or leave it empty. A start date is a published date.',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="text" name="campaign_end_date" class="aas_datetimepicker" id="campaign_end_date" value="<?php echo $campaign_end_date;?>" placeholder="<?php _e('End date' , AAS_TEXT_DOMAIN);?>" />
	</p>
	<p>
	<label for="priority"><strong><?php _e('Priority');?></strong></label><br/>
	<span class="aas_description"><?php _e('A greater number will be display first. Default  is 5.',AAS_TEXT_DOMAIN);?></span><br/>
	<select name="priority" id="priority" style="width:50px;"><?php for($i=self::$max_priority ; $i >= 1 ; $i--){?><option value="<?php echo $i;?>" <?php selected($priority ,$i);?> ><?php echo $i;?></option><?php }?></select>
	</p>
	<p>
	<label for="total_impressions"><strong><?php _e('Total Impressions');?></strong></label><br/>
	<span class="aas_description"><?php _e('Set a number of total impressions. If impressions reach this value, the campaign won\'t be display. 0 means no limit',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="number" name="total_impressions" id="total_impressions" min ="0" value="<?php echo $total_impressions;?>" />
	</p>
	<p>
	<label for="person_impressions"><strong><?php _e('Person Impressions');?></strong></label><br/>
	<span class="aas_description"><?php _e('Set a number of impressions per person. If impressions reach this value, the campaign won\'t be display for that person. 0 means no limit',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="number" name="person_impressions" id="person_impressions" min ="0" value="<?php echo $person_impressions;?>" />
	</p>
	<?php
	}

	function campaign_overview_box($post){
	?>
	<p><strong><?php _e('Costs occured from campaign: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo @AAS_Log::get_log_by('cam_id' , $post->ID)->payment + @AAS_Log::get_log_by('cam_id' , $post->ID,'c')->payment;?></span></p>
	<p><strong><?php _e('CTR Rate: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (float)get_post_meta($post->ID, '_ctr',true) . '%';?></span></p>
	<p><strong><?php _e('Total Clicks: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_click',true);?></span></p>
	<p><strong><?php _e('Total Impressions: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_view',true);?></span></p>
	<?php
	}
	function campaign_banner_box($post){
	?>

	<a target="_blank" title="<?php _e('Add new banner for this campaign' ,AAS_TEXT_DOMAIN) ;?>" href="<?php echo admin_url(). 'post-new.php?post_type=ads_banner&parent='. $post->ID ;?>"><?php _e('Add new banner',AAS_TEXT_DOMAIN);?></a>
	<?php
		$banners = get_posts(array('post_type' => 'ads_banner' , 'posts_per_page' => -1 , 'post_status' => 'any' , 'post_parent' => $post->ID));
	foreach($banners as $b){
		$status = $b->post_status == 'publish' ? 'green' : 'red';
	?>
	 <p><a class="no_underline" href="<?php echo  get_edit_post_link( $b->ID ); ?>"><?php echo __('Banner : ',AAS_TEXT_DOMAIN).$b->post_title;?></a><span title="<?php _e('Banner status' ,AAS_TEXT_DOMAIN) ;?>" class="f-right <?php echo $status ;?>"><?php echo $status=='green' ? __('Active',AAS_TEXT_DOMAIN) : __('Inactive',AAS_TEXT_DOMAIN);?></span></p>
	<?php
		}
	}

	function campaign_save_meta($post_id){
	
	if ( ! isset( $_POST['campaign_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['campaign_meta_box_nonce'], 'campaign_meta_box' ) ) {
		return;
	}

	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	}
		if(isset($_POST['owner'])){
		global $wpdb;
		$wpdb->update($wpdb->posts , array('post_parent' => intval($_POST['owner'])) , array('ID' => $post_id) , array('%d'),array('%d'));
		}
		delete_post_meta($post_id , 'campaign_displaying');
		if(	is_array($_POST['campaign_displaying']) ){
			foreach($_POST['campaign_displaying'] as $zone)
			add_post_meta($post_id , 'campaign_displaying' , intval($zone));
		}
		update_post_meta( $post_id, 'campaign_size' , $_POST['campaign_size'] );
		if( in_array($_POST['pricing_model'],array('cpc','cpm' , 'cpp')) )
		update_post_meta( $post_id, 'pricing_model' , $_POST['pricing_model'] );
		if( in_array($_POST['budget_type'],array('per_day','life_time')) )
		update_post_meta( $post_id, 'budget_type' , $_POST['budget_type'] );
		$my_data = sanitize_text_field($_POST['campaign_end_date']);
		update_post_meta( $post_id, 'campaign_end_date' , $my_data);
		$numeric_groups = array('budget_value','priority','total_impressions','person_impressions');
		foreach($numeric_groups as $num){
			if(is_numeric($_POST[$num]))
			update_post_meta( $post_id, $num , $_POST[$num]);
		}
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
	function set_custom_edit_campaign_columns($columns) {
		$date =  $columns['date'];
		unset( $columns['date'] );
		$columns['target_pricing'] = __('Target Pricing',AAS_TEXT_DOMAIN);
		$columns['ctr'] = __('CTR',AAS_TEXT_DOMAIN);
		$columns['click'] = __('Clicks',AAS_TEXT_DOMAIN);
		$columns['impression'] = __('Impressions',AAS_TEXT_DOMAIN);
		$columns['priority'] = __('Priority',AAS_TEXT_DOMAIN);
		$columns['banner'] = __('Banner List',AAS_TEXT_DOMAIN);
		$columns['advertiser'] = __('Owner',AAS_TEXT_DOMAIN);
		$columns['date'] = $date;
		$columns['end_date'] = __('End Date',AAS_TEXT_DOMAIN);
		return $columns;
	}

	function custom_campaign_column( $column, $post_id ) {
	
		switch ( $column ) {
			case 'target_pricing':
			echo sprintf( __('Model: %s<br/>Budget: %s<br/>Value: %s<br/>Limit View: %s',AAS_TEXT_DOMAIN), strtoupper(get_post_meta($post_id,'pricing_model',true)) , get_post_meta($post_id,'budget_type',true)=='life_time' ? __('Life Time',AAS_TEXT_DOMAIN) : __('Per Day',AAS_TEXT_DOMAIN),number_format(get_post_meta($post_id,'budget_value',true) ),get_post_meta($post_id,'total_impressions',true));
			break;
			case 'priority' :
			echo get_post_meta($post_id , 'priority' , true);
			break;
			case 'banner' :
				$banner = get_posts(array('post_type' => 'ads_banner' , 'posts_per_page' => -1 , 'post_status' => 'any' , 'post_parent' => $post_id));
				foreach($banner as $c){
				?>
				 <a class="no_underline" target="_blank" href="<?php echo  get_edit_post_link( $c->ID ); ?>">• <?php echo $c->post_title;?></a><br/>
				<?php
					}
			break;
			case 'advertiser':
			if($a = get_post($post_id)->post_parent){
			$a = get_post($a);
			echo '<a target="_blank" href="'.get_edit_post_link($a->ID).'">' . $a->post_title  . '</a>' ;
			}
			break;
			case 'end_date':
			echo get_post_meta($post_id , 'campaign_end_date' , true);
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

		}
	}
	function campaign_manage_sortable_columns( $sortable_columns ) {

	    $sortable_columns[ 'priority' ] = 'priority';
		$sortable_columns[ 'end_date' ] = 'end_date';
		$sortable_columns[ 'click' ] = 'click';
		$sortable_columns[ 'impression' ] = 'impression';
		$sortable_columns[ 'ctr' ] = 'ctr';
		return $sortable_columns;
	}

	function get_zone_by_size(){
	$all_zones = get_posts(array('posts_per_page' => -1,'post_type' => 'zone','orderby' => 'title','order' => 'ASC','meta_key' => 'zone_size','meta_value' => esc_attr($_POST['size']) ));
	$zones = get_post_meta($_POST['id'] , 'campaign_displaying');
	?>
	<select data-placeholder="<?php _e('Choose zones' , AAS_TEXT_DOMAIN);?>" multiple class="meta_select multiple-select" name="campaign_displaying[]" id="campaign_displaying" >
		<?php
	
	foreach($all_zones as $zone){?>
		<option value="<?php echo $zone->ID;?>" <?php echo in_array($zone->ID,$zones) ? 'selected' : '';?> ><?php echo $zone->post_title;?></option>
		<?php } ?>
	</select>
	<?php
	}
}
new AAS_Campaign;