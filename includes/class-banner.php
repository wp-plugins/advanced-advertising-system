<?php
/**
 * Banner Module
 *
 */
class AAS_Banner{
	
	protected static $target; //target of anchor tag for banner
	
	/**
	 * Construct backend for ads banner
	 */
	function __construct(){
		
		add_action('init' , array(&$this,'banner_register'));
		add_action( 'save_post', array(&$this,'banner_save_meta') );
		add_filter('post_updated_messages', array(&$this,'banner_updated_messages') );
		add_filter('admin_post_thumbnail_html' , array(&$this,'suggest_banner_size') , 10 , 2 );
		add_filter( 'manage_edit-ads_banner_columns', array(&$this, 'set_custom_edit_banner_columns' ) );
		add_filter( 'manage_edit-ads_banner_sortable_columns', array(&$this,'banner_manage_sortable_columns') );
		add_action( 'manage_ads_banner_posts_custom_column' , array(&$this, 'custom_banner_column' ), 10, 2 );
		 self::$target = array('_blank' => __('Blank', AAS_TEXT_DOMAIN) , '_self' => __('Self',AAS_TEXT_DOMAIN) , '_parent' => __('Parent',AAS_TEXT_DOMAIN) , '_top' => __('Top', AAS_TEXT_DOMAIN) );
	
	}

	function banner_register(){
	
	$labels = array(
		'name'               => _x( 'Banners', 'post type general name', AAS_TEXT_DOMAIN ),
		'singular_name'      => _x( 'Banner', 'post type singular name', AAS_TEXT_DOMAIN ),
		'menu_name'          => _x( 'Banners', 'admin menu', AAS_TEXT_DOMAIN ),
		'name_admin_bar'     => _x( 'Banner', 'add new on admin bar', AAS_TEXT_DOMAIN ),
		'add_new'            => _x( 'Add New', 'book', AAS_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Add New Banner', AAS_TEXT_DOMAIN ),
		'new_item'           => __( 'New Banner', AAS_TEXT_DOMAIN ),
		'edit_item'          => __( 'Edit Banner', AAS_TEXT_DOMAIN ),
		'view_item'          => __( 'View Banner', AAS_TEXT_DOMAIN),
		'all_items'          => __( 'All Banners', AAS_TEXT_DOMAIN ),
		'search_items'       => __( 'Search Banners', AAS_TEXT_DOMAIN ),
		'parent_item_colon'  => __( 'Parent Banners:', AAS_TEXT_DOMAIN ),
		'not_found'          => __( 'No banners found.', AAS_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'No banners found in Trash.', AAS_TEXT_DOMAIN )
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
		'register_meta_box_cb' => array(&$this , 'banner_add_meta_box'),
		'supports'           => array( 'title' , 'thumbnail' )
	);

	register_post_type( 'ads_banner', $args );
	
	
	}
	
	
	function banner_updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages['ads_banner'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Banner updated.' , AAS_TEXT_DOMAIN) ,
		6 => __('Banner published.' , AAS_TEXT_DOMAIN) ,
		8 => __('Banner submitted.' , AAS_TEXT_DOMAIN),
		9 => sprintf( __('Banner scheduled for: <strong>%1$s</strong>.' , AAS_TEXT_DOMAIN),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 =>  __('Banner draft updated.', AAS_TEXT_DOMAIN)
	  );

	  return $messages;
	}

	function banner_add_meta_box(){
	
	add_meta_box(
			'aas_banner',
			__( 'Banner Details', AAS_TEXT_DOMAIN ),
			array(&$this,'banner_meta_box'),
			'ads_banner' , 'normal' , 'high'
		);
	add_meta_box(
			'aas_banner_overview',
			__( 'Banner Overview', AAS_TEXT_DOMAIN ),
			array(&$this,'banner_overview_box'),
			'ads_banner' , 'side' , 'high'
		);
	
	
	}

	function banner_meta_box($post){
		wp_nonce_field( 'banner_meta_box', 'banner_meta_box_nonce' );
		$campaigns = get_posts(array('post_type' => 'campaign' , 'posts_per_page' => -1 , 'post_status' => 'any'));
		$banner_parent = $post->post_parent ? $post->post_parent : (isset($_GET['parent']) ? intval($_GET['parent']) : 0);
		$priority = get_post_meta($post->ID ,'priority' , true);
		$priority = $priority  ? $priority  : 5;
		$custom_html = get_post_meta($post->ID ,'custom_html' , true);
	?>
	<style>.meta_text{width:98%;}.error{color:red;}.aas_description{color:gray;font-size:smaller;font-style:italic;}</style>
	<script>jQuery(document).ready(function() {
    jQuery("#post").validate();
	jQuery("#custom_html-enable").live("change",function(){
	if(jQuery(this).is(":checked"))
		jQuery("#custom_html-html").show();
	else
		jQuery("#custom_html-html").hide();
	});
});</script>
	<p>
	<label for="banner_parent"><strong><?php _e('Parent',AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span style="color:gray;font-size:smaller;font-style:italic;"><?php _e('Select the campaign parent of this banner',AAS_TEXT_DOMAIN);?></span><br/>
	<select class="meta_select" id="banner_parent"  name="banner_parent">
		<option value="">None</option>
		<?php foreach($campaigns as $c){?>
		<option value="<?php echo $c->ID;?>" <?php selected($banner_parent,$c->ID);?> ><?php echo $c->post_title;?></option>
		<?php } ?>
	</select>
	</p>
	<p>
	<label for="banner_target"><strong><?php _e('Target', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<select name="banner_target" id="banner_target">
	<?php foreach(self::$target as $v => $t){?>
	<option value="<?php echo $v ;?>" <?php selected(get_post_meta($post->ID , 'banner_target' , true),$v);?>><?php echo $t;?></option>
	<?php } ?>
	</select>
	</p>
	<p>
	<label for="banner_link"><strong><?php _e('Link * (e.g. http://www.example.com)', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<input class="meta_text" id="banner_link" type="text" value="<?php echo get_post_meta($post->ID , 'banner_link' , true); ?>" name="banner_link" required/>
	</p>
	<p>
	<label for="priority"><strong><?php _e('Priority');?></strong></label><br/>
	<span class="aas_description"><?php _e('A greater number will be display first. Default  is 5.',AAS_TEXT_DOMAIN);?></span><br/>
	<select name="priority" id="priority" style="width:50px;"><?php for($i= AAS_Campaign::$max_priority ; $i >= 1 ; $i--){?><option value="<?php echo $i;?>" <?php selected($priority ,$i);?> ><?php echo $i;?></option><?php }?></select>
	</p>
	<p>
	<label for="custom_html"><strong><?php _e('Custom HTML', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<span class="aas_description"><?php _e('if you enale custom HTML, it will override the feature image automatically. If you insert embed such as flash, you can use %link% as the banner link. You have to put %link% somewhere in your html; otherwise, click log won\'t occur.',AAS_TEXT_DOMAIN);?></span><br/>
	<input type="checkbox" name="custom_html[enable]" id="custom_html-enable" value="1" style="margin:5px 0px;" <?php if(isset($custom_html['enable']))checked($custom_html['enable'],1) ;?>/><br/>
	<textarea class="meta_text" id="custom_html-html" name="custom_html[html]" rows="7" <?php echo empty($custom_html['enable']) ? 'style="display:none;"' : '';?>><?php echo isset($custom_html['html']) ? $custom_html['html'] : ''; ?></textarea>
	</p>
	<?php
	}

	function banner_overview_box($post){
	?>
	<p><strong><?php _e('Costs occured from banner: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo @AAS_Log::get_log_by('banner_id' , $post->ID)->payment + @AAS_Log::get_log_by('banner_id' , $post->ID,'c')->payment;?></span></p>
	<p><strong><?php _e('CTR Rate: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (float)get_post_meta($post->ID, '_ctr',true) . '%';?></span></p>
	<p><strong><?php _e('Total Clicks: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_click',true);?></span></p>
	<p><strong><?php _e('Total Impressions: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_view',true);?></span></p>
	<?php
	}

	function banner_save_meta($post_id){
	
	if ( ! isset( $_POST['banner_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['banner_meta_box_nonce'], 'banner_meta_box' ) ) {
		return;
	}

	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	}
		if(isset($_POST['banner_parent'])){
		global $wpdb;
		$wpdb->update($wpdb->posts , array('post_parent' => intval($_POST['banner_parent'])) , array('ID' => $post_id) , array('%d'),array('%d'));
		}
		if( in_array($_POST['banner_target'],array_keys(self::$target) ) )
		update_post_meta( $post_id, 'banner_target' , $_POST['banner_target'] );
		update_post_meta( $post_id, 'banner_link' , sanitize_text_field($_POST['banner_link']) );
		update_post_meta( $post_id, 'priority' , intval($_POST['priority']) );
		update_post_meta( $post_id, 'custom_html' , $_POST['custom_html'] );
		
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

	function suggest_banner_size($html , $post){
		$p = get_post($post);
		$rec = get_post_meta($p->post_parent , 'campaign_size' , true);
	if($p->post_type == 'ads_banner' && $rec)
	$html .= '<span style="color:blue;">'.sprintf(__('* Recommended size is %s', AAS_TEXT_DOMAIN) , $rec ).'</span>';
	elseif(isset($_GET['parent']) && $rec = get_post_meta($_GET['parent'] , 'campaign_size' , true) )
	$html .= '<span style="color:blue;">'.sprintf(__('* Recommended size is %s', AAS_TEXT_DOMAIN) , $rec ).'</span>';
	
	return $html;
	}
	function set_custom_edit_banner_columns($columns) {
		$date =  $columns['date'];
		unset( $columns['date'] );
		$columns['image'] = __('Banner Preview',AAS_TEXT_DOMAIN);
		$columns['ctr'] = __('CTR',AAS_TEXT_DOMAIN);
		$columns['click'] = __('Clicks',AAS_TEXT_DOMAIN);
		$columns['impression'] = __('Impressions',AAS_TEXT_DOMAIN);
		$columns['priority'] = __('Priority',AAS_TEXT_DOMAIN);
		$columns['parent'] = __('Parent',AAS_TEXT_DOMAIN);
		$columns['date'] = $date;
		return $columns;
	}

	function custom_banner_column( $column, $post_id ) {
	
		switch ( $column ) {
			case 'image':
				$custom = get_post_meta($post_id , 'custom_html' , true);
				if(!empty($custom['enable']) && !empty($custom['html'])){
				echo $custom['html'];
				}
				elseif($thumb_id = get_post_thumbnail_id($post_id)){
				$src = wp_get_attachment_image_src($thumb_id , 'full');
				echo '<img src="'.$src[0].'" class="avatar avatar-32 photo" style="max-width:100%;">';
				}
			break;
			case 'priority' :
			echo get_post_meta($post_id , 'priority' , true);
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
			case 'parent' :
				$a = @get_post(get_post($post_id)->post_parent);
				echo '<a target="_blank" href="'.get_edit_post_link($a->ID).'">' . $a->post_title  . '</a>' ;
			break;
			

		}
	}
	function banner_manage_sortable_columns( $sortable_columns ) {

		$sortable_columns[ 'priority' ] = 'priority';
		$sortable_columns[ 'click' ] = 'click';
		$sortable_columns[ 'impression' ] = 'impression';
		$sortable_columns[ 'ctr' ] = 'ctr';
		return $sortable_columns;
	}
	
}
new AAS_Banner;