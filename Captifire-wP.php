<?php

/*
Plugin Name: Captifire WP Integration
Plugin URI: http://captifire.com
Description: Captifire WP Integration.
Version: 1.5
Author: WishLoop
Author URI: http://captifire.com
License: GPL2
*/

define('CAPTIFIRE_HOST','app.wishloop.com');
define('CAPTIFIRE_PATH', plugin_dir_path( __FILE__ ));




add_action( 'init', 'captifire_cpt', 0 ); // registring cpt
add_action( 'init', 'captifire_enqueue', 0 ); // enqueue js
add_action( 'admin_menu', 'captifire_menu' ); // menu
add_action( 'admin_init', 'captifire_register_options'); // options
//add_filter( 'single_template', 'captifire_page_template', 11); // forces page template
add_action( 'template_redirect', 'captifire_check_slug', 10, 2 ); // checks if the slug is wanted!
add_filter( 'manage_edit-captifire_cpt_columns', 'captifire_custom_columns' ) ; // custom cols
add_action( 'manage_captifire_cpt_posts_custom_column', 'captifire_manage_captifire_cpt_columns', 10, 2 ); // custom cols content
add_filter( 'post_updated_messages', 'captifire_custom_update_msg');
add_action('admin_head', 'captifire_custom_css');
add_action( 'save_post', 'cd_meta_box_save',10,1 );
add_action( 'wp_ajax_captifire_homeCheck', 'captifire_homeCheck' );
add_action( 'wp_ajax_nopriv_captifire_homeCheck', 'captifire_homeCheck' );


add_action( 'add_meta_boxes', 'captifire_add_metabox' );
function captifire_add_metabox()
{
	add_meta_box( 'captifire_metabox', 'Page Settings', 'captifire_metabox_render', 'captifire_cpt', 'normal', 'high' );
}
function captifire_enqueue(){
	wp_enqueue_script( "captiFirejs", '/wp-content/plugins/CaptifireWP/captiFirejs.js', array(), '1.0.0', true );
}
function captifire_homeCheck(){
	global $wpdb;
	global $post;
	$defaults = array(
	  'post_status'           => 'publish', 
	  'post_type'             => 'captifire_cpt',
	  'post_title'            => $_POST['page_title'],
		
	  
	);
	
	$table = $wpdb->prefix . 'postmeta';
	$getdata = $wpdb->get_results("SELECT * FROM ".$table." WHERE meta_value = 'home' ",OBJECT);
	if(count($getdata)>0){
		
	}else{
		echo "save";
		$post_id = wp_insert_post( $defaults );
		update_post_meta( $post_id, 'captifire_page_path', esc_attr( $_POST['page_path'] ) );
		update_post_meta( $post_id, 'captifire_page_id', esc_attr( $_POST['page_title'] ) );
		update_post_meta( $post_id, 'captifire_page_type', esc_attr( $_POST['data'] ) );
	}
	
	// $tbl_postmeta = $wpdb->prefix . 'postmeta';
	// $page_type_id = $wpdb->get_results("SELECT * FROM $tbl_postmeta WHERE meta_key='captifire_page_type' ",OBJECT);
}

function captifire_metabox_render()
{
	global $post;
	$values = get_post_custom( $post->ID );
	
	//die('OK');
	$page_path = isset( $values['captifire_page_path'][0] ) ? $values['captifire_page_path'][0] : '';
	$page_id = isset( $values['captifire_page_id'][0] ) ? $values['captifire_page_id'][0] : '';
	$page_type = isset( $values['captifire_page_type'][0] ) ? $values['captifire_page_type'][0] : '';

	$pages = captifire_get_pages();
	$page_type_name = array(
		'regular' => __( 'Regular Page' ),
		'home' => __( 'Home Page' ),
		'error_404' => __( '404 Page' )
	);

	
	wp_nonce_field( 'captifire_metabox_nonce', 'captifire_metabox_hidden_nonce' );
	?>

		<table width="100%">
			<tr>
				<th width="120" align="left">
					<label for="my_meta_box_text">Custom URL</label>
				</th>
				<td width="20">&nbsp;:&nbsp;</td>
				<td>
					<?php echo site_url().'/'; ?><input type="text" name="captifire_page_path" id="my_meta_box_text" value="<?php echo $page_path; ?>" />
				</td>
			</tr>


			<tr>
				<th width="120" align="left">
					<label for="my_meta_box_select">CaptiFire Page</label>
				</th>
				<td width="20">&nbsp;:&nbsp;</td>
				<td>
					<select name="captifire_page_id" id="my_meta_box_select">
						<?php
							foreach ($pages as $id => $val) :
						?>
								<option value="<?php echo $id;?>" <?php selected( $page_id, $id ); ?>><?php echo $val;?></option>
						<?php
							endforeach;
						?>
					</select>
					<br/>
					If you need to refresh this list, go to the <a href="edit.php?post_type=captifire_cpt&page=Captifire-wP.php">settings</a> and click <b>"Connect & Sync Pages"</b> button.
				</td>
			</tr>
			<tr>
				<th width="120" align="left">
					<label for="captifire_page_type">Page Type</label>
				</th>
				<td width="20">&nbsp;:&nbsp;</td>
				<td>
					<select name="captifire_page_type" id="captifire_page_type">
						<?php
							foreach ($page_type_name as $id => $val) :
						?>
					<option value="<?php echo $id;?>" <?php selected( $page_type, $id ); ?>><?php echo $val;?></option>
						<?php
							endforeach;
						?>
					</select>
				</td>
				<?php
				// global $wpdb;
				// $table = $wpdb->prefix . 'postmeta';
				// $getdata = $wpdb->get_results("SELECT * FROM ".$table." WHERE meta_value = 'home' ",OBJECT);
				?>
			</tr>
		</table>

		&nbsp;&nbsp;&nbsp;
	<?php
}

function cd_meta_box_save( $post_id ){
	
	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

	// if our nonce isn't there, or we can't verify it, bail
	if( !isset( $_POST['captifire_metabox_hidden_nonce'] ) || !wp_verify_nonce( $_POST['captifire_metabox_hidden_nonce'], 'captifire_metabox_nonce' ) ) return;

	// if our current user can't edit this post, bail
	if( !current_user_can( 'edit_post', $post_id ) ) return;

	// Make sure your data is set before trying to save it
	if( isset( $_POST['captifire_page_path'] ) )
		update_post_meta( $post_id, 'captifire_page_path', esc_attr( $_POST['captifire_page_path'] ) );

	if( isset( $_POST['captifire_page_id'] ) )
		update_post_meta( $post_id, 'captifire_page_id', esc_attr( $_POST['captifire_page_id'] ) );
	
	if( isset( $_POST['captifire_page_type'] ) )
		update_post_meta( $post_id, 'captifire_page_type', esc_attr( $_POST['captifire_page_type'] ) );

	
}

function captifire_custom_css() {

	$screen = get_current_screen();
	if ($screen->post_type == 'captifire_cpt'){
		echo '<style type="text/css">';
		echo '.subsubsub li.trash, .subsubsub li.publish { display:none }';
		echo '</style>';
	}

}

function captifire_custom_update_msg($messages){

	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$messages['captifire_cpt'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Page updated.', 'captifire_txtdomain' ),
		2  => __( 'Custom field updated.', 'captifire_txtdomain' ),
		3  => __( 'Custom field deleted.', 'captifire_txtdomain' ),
		4  => __( 'Page updated.', 'captifire_txtdomain' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Book restored to revision from %s', 'captifire_txtdomain' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Page published.', 'captifire_txtdomain' ),
		7  => __( 'Page saved.', 'captifire_txtdomain' ),
		8  => __( 'Page submitted.', 'captifire_txtdomain' ),
		9  => sprintf(
			__( 'Book scheduled for: <strong>%1$s</strong>.', 'captifire_txtdomain' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i', 'captifire_txtdomain' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Page draft updated.', 'captifire_txtdomain' )
	);

	if ( $post_type_object->publicly_queryable ) {
		$permalink = site_url(get_post_meta( $post->ID, 'captifire_page_path', true ));

		$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View Page', 'captifire_txtdomain' ) );
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview Page', 'captifire_txtdomain' ) );
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}

	return $messages;


}

function captifire_custom_columns($columns)
{
	$columns = array(
		'cb' => '<input type="checkbox" />',
		//'title' => __( 'Title' ),
		'titre' => __( 'Title' ),
		'url' => __( 'URL' ),
		'type' => __( 'Type' ),
		'date' => __( 'Date' )
	);

	return $columns;
}

function captifire_manage_captifire_cpt_columns($column, $post_id)
{
	global $post;

	switch( $column ) {

		/* If displaying the 'duration' column. */
		case 'titre' :

			/* Get the post meta. */
			$sqpage_id = get_post_meta( $post_id, 'captifire_page_id', true );

			$pages = captifire_get_pages();

			foreach ($pages as $id => $title)
			{
				if ($id == $sqpage_id){
					echo '<a href="post.php?post='.$post->ID.'&action=edit">'.$title.'</a>
							<div class="row-actions">
							<span class="edit">
								<a href="post.php?post='.$post->ID.'&amp;action=edit" title="Edit this item">Edit</a> |
							</span>
							<span class="trash">
								<a class="submitdelete" title="Move this item to the Trash" href="'.wp_nonce_url( 'post.php?post='.$post->ID.'&amp;action=trash', 'trash-post_'.$post->ID).'">Trash</a> |
							</span>';
					break;
				}
				//else
				//	echo $sqpage_id;
			}

			break;

		/* If displaying the 'genre' column. */
		case 'url' :

			$path = $sqpage_id = get_post_meta( $post_id, 'captifire_page_path', true );
			$page_url = site_url( $path );
			echo '<a href="'. $page_url .'" target="_blank">'. $page_url .'</a>';

			break;
			
		case 'type' :
			$type = get_post_meta( $post_id, 'captifire_page_type', true );
			if($type == 'home'){
					echo 'Home Page';
			}else if($type == 'error_404'){
					echo '404 Page';
			}else{
				echo 'Regular Page';
			}
			
		/* Just break out of the switch statement for everything else. */
		default :
			break;
	}
}


// to see if we should load a cf page or not
function captifire_check_slug()
{
	global $wpdb;
	
	$current = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current = explode( "?", $current );
	$current = $current[0];
	$home_url = get_home_url()."/";
	$slug = str_replace( $home_url, "", $current );
	$slug= rtrim( $slug, '/' );
	
	//die ($slug);
	// let's check if a page has this slug
	$args = array(
		'orderby' => 'title',
		'order' => 'ASC',
		'post_type' => 'captifire_cpt',
		'meta_query' => array(
			array  (
				'key'  => 'captifire_page_path',
				'value'=> $slug
			)
		)
	);
	
	$res = get_posts( $args );
	$tbl_postmeta = $wpdb->prefix . 'postmeta';
	$page_type_id = $wpdb->get_results("SELECT * FROM $tbl_postmeta WHERE meta_key='captifire_page_type' ",OBJECT);
	
	if (count($res) > 0)
	{
		// get the first result THEN inject the JS FILE & exit
		$cf_cpt = $res[0];
		$cf_page_id = get_post_meta($cf_cpt->ID, 'captifire_page_id', true);

		header("HTTP/1.1 200 OK");

		// injecting the js file
		echo '<script data-cfasync="false" src="//'.CAPTIFIRE_HOST.'/embed_page/'.$cf_page_id.'.js"></script>';
		exit();
		
		// Rob Edited
	}else{
		foreach($page_type_id as $wew){
			if($wew->meta_value == 'home'){
				if(is_front_page()){
				// get the first result THEN inject the JS FILE & exit
				$cf_page_id = get_post_meta($wew->post_id, 'captifire_page_id', true);

				header("HTTP/1.1 200 OK");
				// injecting the js file.\
						echo '<script data-cfasync="false" src="//'.CAPTIFIRE_HOST.'/embed_page/'.$cf_page_id.'.js"></script>';
						exit();
					
				
				
				}else{}
			}elseif($wew->meta_value == 'error_404' && is_404()){
				$cf_page_id = get_post_meta($wew->post_id, 'captifire_page_id', true);

				header("HTTP/1.1 200 OK");
				// injecting the js file
				echo '<script data-cfasync="false" src="//'.CAPTIFIRE_HOST.'/embed_page/'.$cf_page_id.'.js"></script>';
				exit();
			}
			else{}
		}
	}
}

function captifire_get_pages(){
	$json = json_decode(get_option('captifire_pages'));
	$res  = array();
	if (count($json) > 0)
	{
		foreach ($json as $page)
		{
			if (!is_null($page->randId))
			{
				$res[$page->randId] = $page->label;
			}
		}
	}
	return $res;
}

// Register Custom Post Type
function captifire_cpt() {

	$labels = array(
		'name'                => _x( 'Captifire Pages', 'Post Type General Name', 'captifire_pages' ),
		'singular_name'       => _x( 'Captifire', 'Post Type Singular Name', 'captifire_pages' ),
		'menu_name'           => __( 'Captifire', 'captifire_pages' ),
		'name_admin_bar'      => __( 'Captifire Pages', 'captifire_pages' ),
		'parent_item_colon'   => __( 'Parent Page:', 'captifire_pages' ),
		'all_items'           => __( 'All Page', 'captifire_pages' ),
		'add_new_item'        => __( 'Add New Page', 'captifire_pages' ),
		'add_new'             => __( 'Add New', 'captifire_pages' ),
		'new_item'            => __( 'New Page', 'captifire_pages' ),
		'edit_item'           => __( 'Edit Page', 'captifire_pages' ),
		'update_item'         => __( 'Update Page', 'captifire_pages' ),
		'view_item'           => __( 'View Page', 'captifire_pages' ),
		'search_items'        => __( 'Search Page', 'captifire_pages' ),
		'not_found'           => __( 'Not found', 'captifire_pages' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'captifire_pages' ),
	);
	$args = array(
		'label'               => __( 'captifire_cpt', 'captifire_pages' ),
		'description'         => __( 'Captifire Pages', 'captifire_pages' ),
		'labels'              => $labels,
		'supports'            => array( 'page_attributes' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'rewrite'             => array( 'slug' => 'captifire', 'with_front' => FALSE ),
	);
	register_post_type( 'captifire_cpt', $args );
}

// page template
function captifire_page_template($single)
{
	global $wp_query, $post;
	/* Checks for single template by post type */
	if ($post->post_type == "captifire_cpt"){
		if(file_exists(CAPTIFIRE_PATH. '/inc/page_template.php'))
			return CAPTIFIRE_PATH . '/inc/page_template.php';
	}

	return $single;
}

// admin menu
function captifire_menu() {
	add_submenu_page('edit.php?post_type=captifire_cpt', 'Captifire Settings', 'Settings', 'manage_options', basename(__FILE__), 'captifire_options');
}
// registering engagifire options
function captifire_register_options() {
	register_setting('captifire_options', 'captifire_apikey' );
	register_setting('captifire_options', 'captifire_pages' );
}

// captifire options page
function captifire_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	// condition : eq (equals), gt(greater than), lt(less than), df(different de)
	// pre_callback : func to execute on value bfr going any further
	// choices : array of values / first = default
	// type : text, text_simple, text_licensekey, text_licensestatut, single_checkbox, radio, dropdown , textarea
	$lippsi_options =  array(
		'license' => array(
			'label'=> 'Settings',
			'desc'=> 'By using this plugin, CaptiFire is easily integrated into your website!',
			'fields' => array(
				array(
					'type'	 	=> 'text',
					'name' 		=> 'captifire_apikey',
					'value'     => '',
					'label'		=> 'Your APIKEY',
					'desc'      => 'You can get your APIKEY from your CaptiFire dashboard, by clicking on "Publish" > "Integrations" of any page you have created.'
				),

				array(
					'type'	 	=> 'button',
					'name' 		=> 'sync',
					'value'     => 'Get Pages from Captifire Dashboard',
					'label'		=> 'Connect & Sync Pages',
				),

				array(
					'type'	 	=> 'hidden',
					'name' 		=> 'captifire_pages',
					'value'     => '',
					'label'		=> 'Captifire Pages',
				),

			),
		)
	);
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			$active = key($lippsi_options);
			// $status = base64_decode( get_option( 'widget_api_endpoint' ));

			foreach ($lippsi_options as $tab_id => $tab_content) {
				if($active == $tab_id)
					$active_class = 'nav-tab-active';
				else
					$active_class = '';

				?>
				<a href="#"
				   id="<?php echo $tab_id; ?>"
				   class="nav-tab <?php echo $active_class; ?>"><?php echo $tab_content['label']; ?></a>
			<?php
			}
			?>
		</h2>
		<form method="post" action="options.php">
			<?php
			settings_fields('captifire_options');
			// Printing tabs contents
			foreach ($lippsi_options as $tab_id => $tab_content)
			{
				if ($tab_id == $active)
					$display = "display:block;";
				else
					$display = "display:none;";

				?>
				<div class="tab-content <?php echo $tab_id; ?>" style="<?php echo $display; ?>">
					<div class="manage-menus"> <?php echo $tab_content['desc']; ?>	</div>
					<table class="form-table">
						<tbody>
						<?php
						foreach ($tab_content['fields'] as $field)
						{
							captifire_render_setting_field($field);
						}
						?>
						</tbody>
					</table>
				</div>
			<?php
			} // END FOREACH

			submit_button();
			?>
		</form>

	</div>
<?php
}
// rendering the options page fields
function captifire_render_setting_field($data)
{

	$bfr_label = '<tr valign="top">
					<th scope="row" valign="top">';
	$aftr_label = '</th>
					<td>';
	$aftr_field = '</td>
					</tr>';

	// eq (equals), gt(greater than), lt(less than), df(different de)
	if (isset($data['condition']))
	{
		$condition = explode(':', $data['condition']);
		$id_to_compare = $condition[0];

		$operator = substr($condition[1], 0, 2);
		$value    = str_replace($operator, '', $condition[1]);
		$value    = str_replace('(', '', $value);
		$value    = str_replace(')', '', $value);

		$attrs = 'data-condition="true" data-id_to_compare="'.$id_to_compare.'" data-operator="'.$operator.'" data-value="'.$value.'"';
	}
	else
		$attrs = '';

	?>

	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('.sync_btn').click(function(){

				var apikey = $('#captifire_apikey').val();

				if (typeof apikey != 'undefined' && apikey.length > 0)
				{
					var $btnResults = $('.btn_results');
					$btnResults.html('Loading...');

					$.getJSON('//<?php echo CAPTIFIRE_HOST ?>/api/getPages/'+apikey, {}, function(data)
					{
						//var rep = JSON.parse(data);

						//console.debug(data);

						// cas d'erreur
						if (typeof data.error != 'undefined')
						{
							$btnResults.html(data.error);
						}
						else
						{
							$btnResults.html('Great! Connected Successfully and Grabbed <b>'+data.length+'</b> pages! <br/>Now, click the <b>"Save changes"</b> button');
							$('input[name=captifire_pages]').val(JSON.stringify(data));
						}
					});
				}

				return false;
			});
		});
	</script>

	<?php
	if ($data['type'] != 'hidden')
	{
	?>
	<tr valign="top">
		<th scope="row" valign="top">
			<label for="<?php echo $data['name']; ?>"><?php _e($data['label']); ?></label>
		</th>
		<td>
	<?php }
			// the field
			$stored_value = esc_attr(get_option($data['name'], @$data['value'] ) );

			// Check if there is a function to execute before showing the value
			if (isset($data['pre_callback']))
			{
				$stored_value = call_user_func($data['pre_callback'], $stored_value);
			}

			switch ($data['type']) {

				case 'text_simple':
					echo '<p '.$attrs.' >'.$data['value'].'</p>';
					break;

				case 'button':
					echo '<button '.$attrs.' id="'.$data['name'].'" name="'.$data['name'].'" type="text" class="button sync_btn" value="'.$stored_value.'">'.$data['value'].'</button> <p class="btn_results"></p>';
					break;

				case 'text_licensestatut':
					if (@$status == 'valid')
						echo('<span style="color:green">'.__('Activated').'</span>');
					else if (@$status == 'invalid' || @$status == 'missing' || @$status == 'deactivated' || $status == 'invalid')
						echo ('<span style="color:red">'.__('Deactivated').'</span>');
					else if (@$status == 'expired')
						echo ('<span style="color:red">'.__('Expired. Please Renew to receive security patchs, features updates & technical support.').'</span>');
					else if (@$status == 'revoked' || @$status == 'disabled')
						echo ('<span style="color:red">Revoked. Please contact the support to know why.</span>');
					else if (@$status == 'no_activations_left')
						echo ('<span style="color:red">No Activations Left. You are trying to activate this license more than allowed.</span>');
					else
						echo ('<span style="color:red">No statut.</div>');
					break;

				case 'text':
					echo '<input '.$attrs.' id="'.$data['name'].'" name="'.$data['name'].'" type="text" class="regular-text" value="'.$stored_value.'" />';
					break;

				case 'hidden':
					echo '<input '.$attrs.' id="'.$data['name'].'" name="'.$data['name'].'" type="hidden" class="regular-text" value="'.$stored_value.'" />';
					break;

				case 'divider':
					echo '<hr style="margin-top:10px"/>';
					break;

				case 'text_licensekey':

					$status   = base64_decode( get_option( 'widget_api_endpoint' ));

					echo '<input '.$attrs.' id="'.$data['name'].'" name="'.$data['name'].'" type="text" class="regular-text" value="'.$stored_value.'" /> &nbsp;';

					if( $status !== false && ($status == 'valid' || $status == 'expired'))
					{
						echo '<input type="submit" name="deactivate_license" class="button button-secondary" value="'.__('Deactivate').'"/>';
					}
					else
					{
						echo '<input type="submit" name="activate_license" class="button button-secondary" value="'.__('Activate').'"/>';
					}
					break;

				case 'single_checkbox':
					if ($stored_value == key($data['choices']))
					{
						$checked = "checked='checked'";
					}
					else
					{
						$checked = "";
					}

					echo ' <input '.$attrs.' id="'.$data['name'].'" name="'.$data['name'].'" type="checkbox" value="'.key($data['choices']).'"  '.$checked.'/> <label for="'.$data['name'].'">'.$data['choices'][key($data['choices'])].'</label>';
					break;

				case 'radio':
					foreach ($data['choices'] as $key => $value)
					{
						if ($key == $stored_value )
							$checked = 'checked="checked"';
						else
							$checked = '';

						echo '<input '.$attrs.' type="radio" id="'.$data['name'].'_'.$key.'" name="'.$data['name'].'" value="'.$key.'" '.$checked.'/> <label for="'.$data['name'].'_'.$key.'">'.$value.'</label> &nbsp; ';
					}
					break;

				case 'textarea':
					echo "<textarea ".$attrs." name='".$data["name"]."' id='".$data["name"]."' rows=6 cols=40 class='".$data["name"]." form-control'>".$stored_value."</textarea>";
					break;

				case 'dropdown':
					echo '<select '.$attrs.' name="'.$data['name'].'" id="'.$data['name'].'">';
					foreach ($data['choices'] as $key => $value)
					{
						if ($key == $stored_value )
							$checked = 'selected="selected"';
						else
							$checked = '';

						echo ' <option id="'.$data['name'].'_'.$key.'" name="'.$data['name'].'" value="'.$key.'" '.$checked.'/> '.$value.'  </option> ';
					}
					echo '</select>';
					break;
			}
			// the description

		if ($data['type'] != 'hidden')
		{
	?>
			<br/><p class="description"><?php _e(@$data['desc']); ?></p>
		</td>
	</tr>
	<?php }

}
?>