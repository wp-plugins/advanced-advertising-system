<?php
/**
 * Adds AAS_Widget widget.
 */
class AAS_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'aas_widget', // Base ID
			__('Advertising Display', AAS_TEXT_DOMAIN), // Name
			array( 'description' => __( 'Display your zone with this widget.', 'text_domain' ) ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$zone_id =$instance['zone_id'] ;
		echo $args['before_widget'];
		if ( is_numeric( $zone_id ) ) {
			echo do_shortcode('[aas_zone zone_id='.$zone_id.']');
		}
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'zone_id' ] ) ) {
			$zone_id = $instance[ 'zone_id' ];
		}
		else {
			$zone_id = 0;
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'zone_id' ); ?>"><?php _e( 'Choose Zone:' ); ?></label>
		<select name="<?php echo $this->get_field_name( 'zone_id' ); ?>" id="<?php echo $this->get_field_id( 'zone_id' ); ?>">
		<?php $zones =get_posts('post_type=zone&posts_per_page=-1&order=ASC&orderby=title');?>
		<?php if(empty($zones)){?>
		<option><?php _e('No zone added',AAS_TEXT_DOMAIN);?></option>
		<?php } else{?>
		<?php foreach($zones as $zone){?>
		<option value="<?php echo $zone->ID;?>" <?php selected($zone_id, $zone->ID);?> ><?php echo $zone->ID.":{$zone->post_title}";?></option>
		<?php  } }?>
		</select>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['zone_id'] = ( is_numeric( $new_instance['zone_id'] ) ) ? $new_instance['zone_id'] : '';

		return $instance;
	}

} // class AAS_Widget

// register AAS_Widget widget
function register_aas_widget() {
    register_widget( 'AAS_Widget' );
}
add_action( 'widgets_init', 'register_aas_widget' );