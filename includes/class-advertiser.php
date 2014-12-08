<?php
/**
 * Advertiser Module
 *
 */
class AAS_Advertiser{

	/**
	 * Construct backend for advertiser
	 */
	function __construct(){
		
		add_action('init' , array(&$this,'advertiser_register'));
		add_action( 'save_post', array(&$this,'advertiser_save_meta') );
		add_filter('post_updated_messages', array(&$this,'advertiser_updated_messages') );
		add_filter( 'manage_edit-advertiser_columns', array(&$this, 'set_custom_edit_advertiser_columns' ) );
		add_action( 'manage_advertiser_posts_custom_column' , array(&$this, 'custom_advertiser_column' ), 10, 2 );
		add_filter( 'manage_edit-advertiser_sortable_columns', array(&$this,'adv_manage_sortable_columns') );
	}

	function advertiser_register(){
	
	$labels = array(
		'name'               => _x( 'Advertisers', 'post type general name', AAS_TEXT_DOMAIN ),
		'singular_name'      => _x( 'Advertiser', 'post type singular name', AAS_TEXT_DOMAIN ),
		'menu_name'          => _x( 'Advertisers', 'admin menu', AAS_TEXT_DOMAIN ),
		'name_admin_bar'     => _x( 'Advertiser', 'add new on admin bar', AAS_TEXT_DOMAIN ),
		'add_new'            => _x( 'Add New', 'book', AAS_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Add New Advertiser', AAS_TEXT_DOMAIN ),
		'new_item'           => __( 'New Advertiser', AAS_TEXT_DOMAIN ),
		'edit_item'          => __( 'Edit Advertiser', AAS_TEXT_DOMAIN ),
		'view_item'          => __( 'View Advertiser', AAS_TEXT_DOMAIN),
		'all_items'          => __( 'All Advertisers', AAS_TEXT_DOMAIN ),
		'search_items'       => __( 'Search Advertisers', AAS_TEXT_DOMAIN ),
		'parent_item_colon'  => __( 'Parent Advertisers:', AAS_TEXT_DOMAIN ),
		'not_found'          => __( 'No advertisers found.', AAS_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'No advertisers found in Trash.', AAS_TEXT_DOMAIN )
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
		'register_meta_box_cb' => array(&$this , 'advertiser_add_meta_box'),
		'supports'           => array( 'title' , 'thumbnail' )
	);

	register_post_type( 'advertiser', $args );
	
	
	}
	
	
	function advertiser_updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages['advertiser'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __('Advertiser updated.' , AAS_TEXT_DOMAIN) ,
		6 => __('Advertiser published.' , AAS_TEXT_DOMAIN) ,
		8 => __('Advertiser submitted.' , AAS_TEXT_DOMAIN),
		9 => sprintf( __('Advertiser scheduled for: <strong>%1$s</strong>.' , AAS_TEXT_DOMAIN),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 =>  __('Advertiser draft updated.', AAS_TEXT_DOMAIN)
	  );

	  return $messages;
	}

	function advertiser_add_meta_box(){
	
	add_meta_box(
			'aas_advertiser',
			__( 'Advertiser Details', AAS_TEXT_DOMAIN ),
			array(&$this,'advertiser_meta_box'),
			'advertiser' , 'normal' , 'high'
		);
	add_meta_box(
			'aas_advertiser_overview',
			__( 'Advertiser Overview', AAS_TEXT_DOMAIN ),
			array(&$this,'advertiser_overview_box'),
			'advertiser' , 'side' , 'high'
		);
	add_meta_box(
			'aas_advertiser_campaign',
			__( 'Campaign List', AAS_TEXT_DOMAIN ),
			array(&$this,'advertiser_campaign_box'),
			'advertiser' , 'side' , 'default'
		);
	
	}

	function advertiser_meta_box($post){
		wp_nonce_field( 'advertiser_meta_box', 'advertiser_meta_box_nonce' );
		$company = get_post_meta($post->ID , 'advertiser_company' , true);
		$email = get_post_meta($post->ID , 'advertiser_email' , true);
		$telephone = get_post_meta($post->ID , 'advertiser_telephone' , true);
		$note = get_post_meta($post->ID , 'advertiser_note' , true);
	?>
	<style>.no_underline{text-decoration:none;}.meta_text{width:98%;}.error{color:red;}.f-right{float:right;}.red{color:red;}.green{color:green;}</style>
	<script>jQuery(document).ready(function() {
    jQuery("#post").validate();
	jQuery("#reset_payment").live("click",function(e){
	e.preventDefault();
	
    var r = confirm("<?php _e('Are you sure to reset the payment for this advertiser ?', AAS_TEXT_DOMAIN);?>");
    if (r == true) {
		var data = {action:'reset_payment', id: jQuery(this).data('id') , nonce : jQuery(this).data('nonce')};
        jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>" , data , function(response){
			if(response)
			location.reload();
		});
    } 
	});
});</script>
	<p>
	<label for="company"><strong><?php _e('Company Name', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<input class="meta_text" id="company" type="text" value="<?php echo $company; ?>" name="advertiser_company"/>
	</p>
	<p>
	<label for="email"><strong><?php _e('Email', AAS_TEXT_DOMAIN);?> *</strong></label><br/>
	<input class="meta_text" id="email" type="email" value="<?php echo $email; ?>" name="advertiser_email" required/>
	</p>
	<p>
	<label for="telephone"><strong><?php _e('Telephone Number', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<input class="meta_text" id="telephone" type="text" value="<?php echo $telephone; ?>"  name="advertiser_telephone"/>
	</p>
	<p>
	<label for="note"><strong><?php _e('Note', AAS_TEXT_DOMAIN);?></strong></label><br/>
	<textarea class="meta_text" id="note" name="advertiser_note" rows="5"><?php echo $note; ?></textarea>
	</p>
	<?php
	}

	function advertiser_campaign_box($post){
	?>
	<a target="_blank" title="<?php _e('Add new campaign for this advertiser' ,AAS_TEXT_DOMAIN) ;?>" href="<?php echo admin_url(). 'post-new.php?post_type=campaign&owner='. $post->ID ;?>"><?php _e('Add new campaign',AAS_TEXT_DOMAIN);?></a>
	<?php
		$campaigns = get_posts(array('post_type' => 'campaign' , 'posts_per_page' => -1 , 'post_status' => 'any' , 'post_parent' => $post->ID));
	foreach($campaigns as $c){
		$status = $c->post_status == 'publish' ? 'green' : 'red';
	?>
	 <p><a class="no_underline" href="<?php echo  get_edit_post_link( $c->ID ); ?>"><?php echo __('Campaign : ',AAS_TEXT_DOMAIN).$c->post_title;?></a><span title="<?php _e('Campaign status' ,AAS_TEXT_DOMAIN) ;?>" class="f-right <?php echo $status ;?>"><?php echo $status=='green' ? __('Active',AAS_TEXT_DOMAIN) : __('Inactive',AAS_TEXT_DOMAIN);?></span></p>
	<?php
		}
	}

	function advertiser_overview_box($post){
	global $wpdb;
	$banner_num=$wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent IN (SELECT ID from $wpdb->posts WHERE post_parent = {$post->ID} AND post_type = 'campaign' ) AND post_type = 'ads_banner'");
	?>
	<p><strong><?php _e('Total Payment: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (float)get_post_meta($post->ID, '_total_payment',true);?></span><button data-id="<?php echo $post->ID;?>" data-nonce="<?php echo wp_create_nonce('reset-payment-'.$post->ID);?>" id="reset_payment" class="button button-small" style="float:right;"><?php _e('Reset Payment', AAS_TEXT_DOMAIN);?></button></p>
	<p><strong><?php _e('CTR Rate: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (float)get_post_meta($post->ID, '_ctr',true) . '%';?></span></p>
	<p><strong><?php _e('Total Clicks: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_click',true);?></span></p>
	<p><strong><?php _e('Total Impressions: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo (int)get_post_meta($post->ID, '_total_view',true);?></span></p>
	<p><strong><?php _e('Total Banners: ', AAS_TEXT_DOMAIN)?></strong><span><?php echo $banner_num;?></span></p>
	<?php
	}
	function advertiser_save_meta($post_id){
	
	if ( ! isset( $_POST['advertiser_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['advertiser_meta_box_nonce'], 'advertiser_meta_box' ) ) {
		return;
	}

	
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	}
		$my_data = sanitize_text_field( $_POST['advertiser_company'] );
		update_post_meta( $post_id, 'advertiser_company' , $my_data );
		$my_data = sanitize_text_field( $_POST['advertiser_telephone'] );
		update_post_meta( $post_id, 'advertiser_telephone' , $my_data );
		$my_data = sanitize_text_field( $_POST['advertiser_note'] );
		update_post_meta( $post_id, 'advertiser_note' , $my_data );
		if( is_email($_POST['advertiser_email']) )
		update_post_meta( $post_id, 'advertiser_email' , $_POST['advertiser_email'] );

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
	
	

	function set_custom_edit_advertiser_columns($columns) {
		$date =  $columns['date'];
		unset( $columns['date'] );
		$title =  $columns['title'];
		unset( $columns['title'] );
		
		$columns['avatar'] = __( 'Avatar', AAS_TEXT_DOMAIN );
		$columns['title']=$title;
		$columns['company'] = __( 'Company', AAS_TEXT_DOMAIN );
		$columns['email'] = __( 'Email', AAS_TEXT_DOMAIN );
		$columns['campaign'] = __( 'Campaign List', AAS_TEXT_DOMAIN );
		$columns['payment'] = __( 'Total Payment', AAS_TEXT_DOMAIN );
		$columns['date'] = $date;
		return $columns;
	}

	function custom_advertiser_column( $column, $post_id ) {
		switch ( $column ) {
			case 'avatar' :
				if($thumb_id = get_post_thumbnail_id($post_id)){
				$src = wp_get_attachment_image_src($thumb_id);
				echo '<img src="'.$src[0].'" class="avatar avatar-32 photo" height="32" width="32">';
				}
				else
				echo '<img src="'.AAS_PLUGIN_URL.'image/default_avatar.png"  class="avatar avatar-32 photo" height="32" width="32">';
			break;
			case 'company' :
				echo get_post_meta( $post_id , 'advertiser_company' , true ); 
			break;
			case 'email' :
				echo get_post_meta( $post_id , 'advertiser_email' , true ); 
			break;
			case 'campaign' :
				$campaigns = get_posts(array('post_type' => 'campaign' , 'posts_per_page' => -1 , 'post_status' => 'any' , 'post_parent' => $post_id));
				foreach($campaigns as $c){
				?>
				 <a class="no_underline" target="_blank" href="<?php echo  get_edit_post_link( $c->ID ); ?>">• <?php echo $c->post_title;?></a><br/>
				<?php
					}
			break;
			case 'payment':
			echo (float)get_post_meta($post_id, '_total_payment',true);
			break;

		}
	}
function adv_manage_sortable_columns( $sortable_columns ) {

		$sortable_columns[ 'payment' ] = 'payment';
		return $sortable_columns;
	}
}
new AAS_Advertiser;

function aas_reset_payment(){
if(!wp_verify_nonce($_POST['nonce'],'reset-payment-'.$_POST['id']))
wp_die(0);
if(update_post_meta($_POST['id'], '_total_payment', 0))
wp_die(1);
wp_die(0);
}
add_action('wp_ajax_reset_payment' , 'aas_reset_payment');