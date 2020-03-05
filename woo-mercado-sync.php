<?php
/*
* Plugin Name: Woocommerce Product syncronization Plugin
* Description: Import product from Aliexpress and Mercado Libre and export pdoruct to Mercado Libre.
* Version: 1.0.0
* Plugin URI: 
* Author: myhope1227
* 
*/ 
// Exit if accessed directly

if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'woo_product_sync' ) ):
class woo_product_sync{
	public function instance(){
		add_action( 'admin_menu', array( $this, 'plugin_admin_page' ) );
	}

	public function plugin_admin_page() {
		add_menu_page( 'Product Data Sync', 'Product Syncronization', 'manage_options', 'product_sync', array( $this, 'plugin_product_sync' ) );
		add_submenu_page('product_sync', 'Product Syncronization', 'Product Syncronization', 'manage_options', 'product_sync', array($this, 'plugin_product_sync'));
		add_submenu_page('product_sync', 'Setting', 'Setting', 'manage_options', 'mercado_setting', array($this, 'plugin_setting'));
	}

	public function plugin_setting(){
		
		require_once(plugin_dir_path( __FILE__ ).'/lib/Meli/meli.php');
		$appId 			= get_option( 'mercadolib_app_id' );
		$secretKey 		= get_option( 'mercadolib_security_key' );
		$redirectURI 	= get_option( 'mercadolib_redirect_uri' );
		$siteId 		= get_option( 'mercadolib_site_id' );
		$access_token 	= get_option( 'mercadolib_access_token' );
		$expires_in 	= get_option( 'mercadolib_expires_in' );

		if ( isset( $_REQUEST['save'] ) ){
			$appId 			= $_REQUEST['app_id'];
			$secretKey 		= $_REQUEST['security_key'];
			$redirectURI 	= admin_url('admin.php?page=mercado_setting');//$_REQUEST['redirect_uri'];
			$siteId 		= $_REQUEST['site_id'];
			update_option('mercadolib_app_id', $appId);
			update_option('mercadolib_security_key', $secretKey);
			update_option('mercadolib_redirect_uri', $redirectURI);
			update_option('mercadolib_site_id', $siteId);

			$meli = new Meli($appId, $secretKey);
			echo("<script>location.href = '".$meli->getAuthUrl($redirectURI, Meli::$AUTH_URL[$siteId])."'</script>");
		}

		if ( isset( $_GET['code'] ) ){

			$meli = new Meli($appId, $secretKey);
			$user = $meli->authorize($_GET['code'], $redirectURI);

            $access_token 	= $user['body']->access_token;
            $expires_in 	= time() + $user['body']->expires_in;
            $refresh_token 	= $user['body']->refresh_token;
            update_option('mercadolib_access_token', $access_token);
            update_option('mercadolib_expires_in', $expires_in);
            update_option('mercadolib_refresh_token', $refresh_token);
		}
?>
		<div class="wrap">
			<h2>Mercadolib Setting</h2>
			<form action="" method="post" id="mercado_setting">
				<table class="form-table">
					<tbody>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>App ID</label>
							</th>
							<td>
								<input type="text" name="app_id" value="<?php echo get_option('mercadolib_app_id');?>"/>
							</td>
						</tr>
						<tr valign="middle" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Security Key</label>
							</th>
							<td>
								<input type="password" name="security_key" value="<?php echo get_option('mercadolib_security_key');?>"/>
							</td>
						</tr>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Redirect URI</label>
							</th>
							<td>
								<input type="text" name="redirect_uri" value="<?php echo get_option('mercadolib_redirect_uri');?>"/>
							</td>
						</tr>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Site ID</label>
							</th>
							<td>
								<select name="site_id">
									<option value="MLA" <?php echo (get_option('mercadolib_site_id') == "MLA")?'selected':'';?>>Argentina</option>
									<option value="MLB" <?php echo (get_option('mercadolib_site_id') == "MLB")?'selected':'';?>>Brasil</option>
									<option value="MCO" <?php echo (get_option('mercadolib_site_id') == "MCO")?'selected':'';?>>Colombia</option>
									<option value="MCR" <?php echo (get_option('mercadolib_site_id') == "MCR")?'selected':'';?>>Costa Rica</option>
									<option value="MEC" <?php echo (get_option('mercadolib_site_id') == "MEC")?'selected':'';?>>Ecuador</option>
									<option value="MLC" <?php echo (get_option('mercadolib_site_id') == "MLC")?'selected':'';?>>Chile</option>
									<option value="MLM" <?php echo (get_option('mercadolib_site_id') == "MLM")?'selected':'';?>>Mexico</option>
									<option value="MLU" <?php echo (get_option('mercadolib_site_id') == "MLU")?'selected':'';?>>Uruguay</option>
									<option value="MLV" <?php echo (get_option('mercadolib_site_id') == "MLV")?'selected':'';?>>Venezuela</option>
									<option value="MPA" <?php echo (get_option('mercadolib_site_id') == "MPA")?'selected':'';?>>Panama</option>
									<option value="MPE" <?php echo (get_option('mercadolib_site_id') == "MPE")?'selected':'';?>>Peru</option>
									<option value="MPT" <?php echo (get_option('mercadolib_site_id') == "MPT")?'selected':'';?>>Portugal</option>
									<option value="MRD" <?php echo (get_option('mercadolib_site_id') == "MRD")?'selected':'';?>>Dominicana</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p><input type="submit" name="save" value="Save" class="button button-primary button-large"/></p>
			</form>
		</div>
<?php
	}

	public function plugin_product_sync(){
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), '1.0.1');

		require_once(plugin_dir_path( __FILE__ ).'/lib/Meli/meli.php');
		$appId 			= get_option( 'mercadolib_app_id' );
		$secretKey 		= get_option( 'mercadolib_security_key' );
		$redirectURI 	= get_option( 'mercadolib_redirect_uri' );
		$siteId 		= get_option( 'mercadolib_site_id' );
		$access_token 	= get_option( 'mercadolib_access_token' );
		$expires_in 	= get_option( 'mercadolib_expires_in' );
		$refresh_token 	= get_option( 'mercadolib_refresh_token' );

		$meli = new Meli($appId, $secretKey, $access_token, $refresh_token);

		if ( !$access_token ){

			wp_redirect( admin_url('admin.php?page=mercado_setting') );
		    $user = $meli->authorize($_GET['code'], $redirectURI);

            // Now we create the sessions with the authenticated user
            $access_token 	= $user['body']->access_token;
            $expires_in 	= time() + $user['body']->expires_in;
            $refresh_token 	= $user['body']->refresh_token;
            update_option('mercadolib_access_token', $access_token);
            update_option('mercadolib_expires_in', $expires_in);
            update_option('mercadolib_refresh_token', $refresh_token);
        }
    	if($expires_in < time()) {
            try {
                // Make the refresh proccess
                $refresh = $meli->refreshAccessToken();

                // Now we create the sessions with the new parameters
                $access_token 	= $refresh['body']->access_token;
                $expires_in 	= time() + $refresh['body']->expires_in;
                $refresh_token 	= $refresh['body']->refresh_token;
            } catch (Exception $e) {
                echo "Exception: ",  $e->getMessage(), "\n";
            }
        }

		if ( isset( $_REQUEST['import'] ) ){
			if ($_REQUEST['import_source'] == "mercadolibre"){
				if ($_REQUEST['import_type'] == "single_import"){
					$item_id = $_REQUEST['product_id'];
					$result = $meli->get('/items/'.$item_id, array('access_token' => $access_token));
					$product_data = $result['body'];
					//var_dump($product_data);
					$this->plugin_add_product($product_data);
				}else{
					if( ! empty( $_FILES ) ) 
		       		{
		       			$file = $_FILES['product_import_csv'];
		       			
		       			require_once( ABSPATH . 'wp-admin/includes/admin.php' );
		      			$file_result = wp_handle_upload( $file, array('test_form' => false ) );
		      			if( !isset( $file_result['error'] ) && !isset( $file_result['upload_error_handler'] ) ) 
						{
							if (($handle = fopen($file_result['file'], "r")) !== FALSE) {
								while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
									$item_id = $data[0];
									$result = $meli->get('/items/'.$item_id, array('access_token' => $access_token));
									$product_data = $result['body'];
									//var_dump($product_data);
									$this->plugin_add_product($product_data);
								}
								fclose($handle);
							}
						}
					}
					
				}
			}else{

			}
		}

		if ( isset( $_REQUEST['export'] ) ){
			if ($_REQUEST['export_type'] == "single_export"){
				$product_id = $_REQUEST['export_product'];
				$product = wc_get_product($product_id);
				$this->plugin_export_product($product);
			}else{
				if( ! empty( $_FILES ) ) 
	       		{
	       			$file = $_FILES['product_export_csv'];
	       			
	       			require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	      			$file_result = wp_handle_upload( $file, array('test_form' => false ) );
	      			if( !isset( $file_result['error'] ) && !isset( $file_result['upload_error_handler'] ) ) 
					{
						if (($handle = fopen($file_result['file'], "r")) !== FALSE) {
							while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
								$product_id = $data[0];
								$product = wc_get_product($product_id);
								$this->plugin_export_product($product);
							}
							fclose($handle);
						}
					}
				}
			}
		}

		$products = wc_get_products( array('limit' => 2000) );
?>
		<div class="wrap">
			<h2>Product Syncronization</h2>
			<form action="" method="post" id="product_sync">
				<h3>Product Import</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Import Source</label>
							</th>
							<td>
								<select name="import_source" id="import_source" style="width:350px">
									<option value="aliexpress">Aliexpress</option>
									<option value="mercadolibre">Mercado Libre</option>
								</select>
							</td>
						</tr>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Import type</label>
							</th>
							<td>
								<input type="radio" id="single_import" name="import_type" value="single_import" checked>
								<label for="single_import">Single Import</label><br>
								<input type="radio" id="bulk_import" name="import_type" value="bulk_import">
								<label for="bulk_import">Bulk Import</label><br>
							</td>
						</tr>
						<tr valign="middle" class="single_select_page single_import import_type">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Product URL</label>
							</th>
							<td>
								<input type="text" name="product_id" value=""/>
							</td>
						</tr>
						<tr valign="top" class="single_select_page bulk_import import_type" style="display: none;">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>CSV File</label>
							</th>
							<td>
								<input type="file" name="product_import_csv" value=""/>
							</td>
						</tr>
					</tbody>
				</table>
				<p><input type="submit" name="import" value="Import" class="button button-primary button-large"/>
				<h3>Product Export</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Import type</label>
							</th>
							<td>
								<input type="radio" id="single_export" name="export_type" value="single_export" checked>
								<label for="single_export">Single Export</label><br>
								<input type="radio" id="bulk_export" name="export_type" value="bulk_export">
								<label for="bulk_export">Bulk Export</label><br>
							</td>
						</tr>
						<tr valign="middle" class="single_select_page single_export export_type">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>Product</label>
							</th>
							<td>
								<select name="export_product" id="export_product" style="width:350px" data-placeholder="Choose products" aria-label="products" class="wc-enhanced-select" tabindex="-1" aria-hidden="true">
							<?php foreach ($products as $key => $product) { ?>
									<option value="<?php echo $product->get_ID();?>" <?php echo in_array($product->get_ID(), explode(',', get_option('ffl_dealer_products')) )?'selected':'';?>><?php echo $product->get_title();?></option>
							<?php }?>
								</select>
							</td>
						</tr>
						<tr valign="top" class="single_select_page bulk_export export_type" style="display: none;">
							<th scope="row" class="titledesc" style="width:250px;">
								<label>CSV File</label>
							</th>
							<td>
								<input type="file" name="product_export_csv" value=""/>
							</td>
						</tr>
					</tbody>
				</table>
				<p><input type="submit" name="export" value="Export to Mercado Libre" class="button button-primary button-large"/>
			</form>
		</div>
<script>
	jQuery(document).ready(function($){
		$('input[type="radio"]').change(function(){
			var selected_val = $(this).closest('tr').find('input[type="radio"]:checked').val();
			var selected_type = $(this).attr('name');
			$(this).closest('tbody').find('.' + selected_type).hide();
			$(this).closest('tbody').find('.' + selected_val).show();
		});
	});
</script>
<?php
	}

	public function plugin_add_product($product_data){
		$product_title = $product_data->title;
		$price = $product_data->price;
		$base_price = $product_data->base_price;
		$quantity = $product_data->available_quantity;

		$appId 			= get_option( 'mercadolib_app_id' );
		$secretKey 		= get_option( 'mercadolib_security_key' );
		$access_token 	= get_option( 'mercadolib_access_token' );

		$meli = new Meli($appId, $secretKey);

		$desc_result = $meli->get('/items/'.$product_data->id.'/description', array('access_token' => $access_token));

		$description = $desc_result['body']->plain_text;
		$user_id = get_current_user_id();
		
		$post_id = wp_insert_post(array(
			'post_author' 	=> $user_id,
			'post_title' 	=> $product_title,
			'post_type'		=> 'product',
			'post_status'	=> 'publish',
			'post_content'	=> $description,
		));
		wp_set_object_terms($post_id, 'variable', 'product_type');

		$uploads = wp_upload_dir();
        $uploads_dir = $uploads['path'];
        $uploads_url = $uploads['url'];
				
		$product_gallery = array();

		foreach ($product_data->pictures as $ind => $pic) {
			$file_name = basename($pic->url);
			$new_file_path = $uploads_dir . '/' . $file_name;
			$file_content = file_get_contents($pic->url);
			file_put_contents($new_file_path, $file_content);
			$new_file_mime = mime_content_type( $new_file_path );

			$upload_id = wp_insert_attachment( array(
				'guid'           => $new_file_path, 
				'post_mime_type' => $new_file_mime,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			), $new_file_path );

			wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $new_file_path ) );
			if ($ind == 0){
				set_post_thumbnail($post_id, $upload_id);
			}else{
				$product_gallery[] = $upload_id;
			}
		}
		update_post_meta($post_id, '_product_image_gallery', implode(',', $product_gallery));

		update_post_meta( $post_id, '_stock_status', 'instock');
        update_post_meta( $post_id, '_sku', "");
        update_post_meta( $post_id, '_stock', $quantity );
        update_post_meta( $post_id, '_visibility', 'visible' );

        $thedata = array();
        $attributes = array();

        foreach( $product_data->variations as $ind => $variation ){
        	$product_attribute = array();
        	foreach( $variation->attribute_combinations as $attr_ind => $attribute ){
        		if ( !array_key_exists('pa_'.$attribute->name, $thedata )){
        			$thedata['pa_'.$attribute->name] = array(
	                    'name' => 'pa_'.$attribute->name,
	                    'value' => '',
	                    'is_visible' => '1', 
	                    'is_variation' => '1',
	                    'is_taxonomy' => '1'
	                );
        		}
        		$product_attribute[$attribute->name] = $attribute->value_name;
        	}

        	$product = wc_get_product($post_id);

        	$variation_data =  array(
			    'attributes' => $product_attribute,
			    'sku'           => '',
			    'regular_price' => $variation->price,
			    'sale_price'    => '',
			    'stock_qty'     => $variation->available_quantity,
			);

            $this->create_product_variation($post_id, $variation_data);
        }

        /*foreach ($attributes as $attr_name => $attr_values) {
        	wp_set_object_terms($post_id, $attr_values, 'pa_'.$attr_name);	
        }*/

        update_post_meta( $post_id, '_product_attributes', $thedata);
	}

	public function plugin_export_product($product)
	{

		$appId 			= get_option( 'mercadolib_app_id' );
		$secretKey 		= get_option( 'mercadolib_security_key' );
		$access_token 	= get_option( 'mercadolib_access_token' );

		$meli = new Meli($appId, $secretKey);

		$item = array(
			"title" => $product->get_title(),
	        "category_id" => "",
	        "price" => $product->get_price(),
	        "currency_id" => get_woocommerce_currency(),
	        "available_quantity" => $product->get_stock_quantity(),
	        "buying_mode" => "buy_it_now",
	        "listing_type_id" => "bronze",
	        "condition" => "new",
	        "description" => array ("plain_text" => $product->get_description()),
	        "video_id" => "",
	        "warranty" => "12 month",
	        "pictures" => array(),
	        "attributes" => array()
	    );

		$image = wp_get_attachment_image_src( $product->get_image_id(), 'single-post-thumbnail' );
	    $item['pictures'][] = array("source" => $image[0]);

	    foreach ($product->get_gallery_image_ids() as $key => $image_id) {
	    	$image_src = wp_get_attachment_image_src( $image_id, 'single-post-thumbnail' );
	    	$item['pictures'][] = array("source" => $image_src[0]);
	    }
		
		$meli->post('/items', $item, array('access_token' => $access_token));
	}

	public function create_product_variation( $product_id, $variation_data ){
	    // Get the Variable product object (parent)
	    $product = wc_get_product($product_id);

	    $variation_post = array(
	        'post_title'  => $product->get_name(),
	        'post_name'   => 'product-'.$product_id.'-variation',
	        'post_status' => 'publish',
	        'post_parent' => $product_id,
	        'post_type'   => 'product_variation',
	        'guid'        => $product->get_permalink()
	    );

	    // Creating the product variation
	    $variation_id = wp_insert_post( $variation_post );

	    // Get an instance of the WC_Product_Variation object
	    $variation = new WC_Product_Variation( $variation_id );

	    // Iterating through the variations attributes
	    foreach ($variation_data['attributes'] as $attribute => $term_name )
	    {
	        $taxonomy = 'pa_'.$attribute; // The attribute taxonomy

	        // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
	        if( ! taxonomy_exists( $taxonomy ) ){
	            register_taxonomy(
	                $taxonomy,
	               'product_variation',
	                array(
	                    'hierarchical' => false,
	                    'label' => ucfirst( $attribute ),
	                    'query_var' => true,
	                    'rewrite' => array( 'slug' => sanitize_title($attribute) ), // The base slug
	                )
	            );
	        }

	        // Check if the Term name exist and if not we create it.
	        if( ! term_exists( $term_name, $taxonomy ) )
	            wp_insert_term( $term_name, $taxonomy ); // Create the term

	        $term_slug = get_term_by('name', $term_name, $taxonomy )->slug; // Get the term slug

	        // Get the post Terms names from the parent variable product.
	        $post_term_names =  wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );

	        // Check if the post term exist and if not we set it in the parent variable product.
	        if( ! in_array( $term_name, $post_term_names ) )
	            wp_set_object_terms( $product_id, $term_name, $taxonomy, true );

	        // Set/save the attribute data in the product variation
	        update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
	    }

	    ## Set/save all other data

	    // SKU
	    if( ! empty( $variation_data['sku'] ) )
	        $variation->set_sku( $variation_data['sku'] );

	    // Prices
	    if( empty( $variation_data['sale_price'] ) ){
	        $variation->set_price( $variation_data['regular_price'] );
	    } else {
	        $variation->set_price( $variation_data['sale_price'] );
	        $variation->set_sale_price( $variation_data['sale_price'] );
	    }
	    $variation->set_regular_price( $variation_data['regular_price'] );

	    // Stock
	    if( ! empty($variation_data['stock_qty']) ){
	        $variation->set_stock_quantity( $variation_data['stock_qty'] );
	        $variation->set_manage_stock(true);
	        $variation->set_stock_status('');
	    } else {
	        $variation->set_manage_stock(false);
	    }

	    $variation->save(); // Save the data
	}
}

$plugin_obj = new woo_product_sync(); 
$plugin_obj->instance();

endif;