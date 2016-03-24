<?php
/*
  Plugin Name: Display All WooCommerce Products 
  Plugin URI: http://drakotek.com
  Description: This plugin displays a list of all WooCommerce prodcts in a separate Admin Tab.
  Version: 1.0.0
  Author: Kadon Hodson
  Author URI: http://drakotek.com
  License: GPL2
 */
 /**
 	Developed using code from Neil @ http://www.functionsphp.com/get-list-of-all-woocommerce-products/
**/	
// ********* Get all products and variations and sort alphbetically, return in array (title, sku, id)*******
function get_woocommerce_product_list() {
	$full_product_list = array();
	$loop = new WP_Query( array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 ) );
 
	while ( $loop->have_posts() ) : $loop->the_post();
		$theid = get_the_ID();
		$_pf = new WC_Product_Factory();
		$product = $_pf->get_product($theid);
		
			$varis = array();
			$cart_url = $product->add_to_cart_url();
			$arr1 = explode('?',$cart_url,2);
			$arr2 = explode('&',$arr1[1],2);
			$_url = $arr2[1];
			$att1 = explode('&attribute_',$_url);
			$att_count = count($att1);
			$my_attr = array();
				for ($x = 1; $x <= ($att_count-1); $x++) {
					//$att2 = explode('=',$att1[$x]);
					$my_attr[] = $att1[$x];
				}
		// its a variable product
		if( get_post_type() == 'product_variation' ){
			$parent_id = wp_get_post_parent_id($theid );
			$sku = get_post_meta($theid, '_sku', true );
			$thetitle = get_the_title( $parent_id);
			$vari = 1;
			if ($product->has_attributes()) {
				
				$varis = $product->get_attributes();
				if (is_array($varis))  {
					$myattr = array();
					foreach ($varis as $single_att) :
						//$myattr[] = $product->get_attribute($single_att('name'));
						$att_name = $single_att['name'];
						$myattr[] = $product->get_attribute($att_name);
					endforeach;
				} else {$myattr = $product->get_attribute($varis['name']);}
			} else {
					
					$varis = $product->get_attributes();}
			
 
    // ****** Some error checking for product database *******
            // check if variation sku is set
            if ($sku == '') {
                if ($parent_id == 0) {
            		// Remove unexpected orphaned variations.. set to auto-draft
            		$false_post = array();
                    $false_post['ID'] = $theid;
                    $false_post['post_status'] = 'auto-draft';
                    wp_update_post( $false_post );
                    if (function_exists(add_to_debug)) add_to_debug('false post_type set to auto-draft. id='.$theid);
                } else {
                    // there's no sku for this variation > copy parent sku to variation sku
                    // & remove the parent sku so the parent check below triggers
                    $sku = get_post_meta($parent_id, '_sku', true );
                    if (function_exists(add_to_debug)) add_to_debug('empty sku id='.$theid.'parent='.$parent_id.'setting sku to '.$sku);
                    update_post_meta($theid, '_sku', $sku );
                    update_post_meta($parent_id, '_sku', '' );
                }
            }
 	// ****************** end error checking *****************
 
        // its a simple product
        } else {
            $sku = get_post_meta($theid, '_sku', true );
            $thetitle = get_the_title();
			$parent_id = 'none';
			$vari = 0;
			if ($product->has_attributes()) {
				$varis = $product->get_attributes();
				
			} else {
				
				$varis = $product->get_attributes();}
							
        }
        // add product to array but !!!don't add the parent of product variations!!! Include Parent IDs
        //if (!empty($sku)) 
		if ($product->is_purchasable()) {	
			$purchasable = 'color:default;';		
			$full_product_list[] = array($thetitle, $sku, $theid, $parent_id, $_url, $varis, $vari, $my_attr, $purchasable);
		} else {
			$purchasable = 'color:#f00;';		
			$full_product_list[] = array($thetitle . ' NOT PURCHASABLE', $sku, $theid, $parent_id, $_url, 'Not Purchasable', 'not purchasable', 'Not Purchasable!', $purchasable);
		}
    endwhile; wp_reset_query();
    // sort into alphabetical order, by title
    sort($full_product_list);
    return $full_product_list;
}

add_action('admin_menu', 'woo_instaurls_settings_menu');

function woo_instaurls_settings_menu() {
	add_menu_page('WooCommerce Product InstaURLs', 'Woo InstaURLs', 'manage_options', 'woo-instaurls-settings', 'woo_instaurls_settings_page', 'dashicons-smiley');
}

add_action( 'admin_init', 'woo_instaurls_settings' );

function woo_instaurls_settings() {
	register_setting( 'woo_intsaurls_settings_group', 'product_id' );
}

function woo_instaurls_settings_page() {
?>
<div class="wrap">
<h2>Woo Products Checkout URLs</h2>


    
    
    <table>        
        <tr valign="top">
        <th>Product ID</th>
        <th>Parent ID</th>
        <th>Product or Attribute Title</th>
        <th>URL</th>
        
        </tr>
        <?php
			$_site_url = get_site_url();
			$list = array();
			$list = get_woocommerce_product_list();
			foreach ($list as $prod) : 
				$font_color = $prod[8];?>
				<tr style=" <?php echo $font_color; ?> ">
                <td style="white-space:nowrap;"> <?php echo $prod[2]; ?> </td>
                <td style="white-space:nowrap;"> <?php echo $prod[3]; ?> </td>
                <?php if ($prod[6] == 0) : ?>
                	<td style="white-space:nowrap;"> <?php echo $prod[0]; ?> </td>
                <?php else : ?>
					<td style="white-space:nowrap;color:#00f;">
                        <?php foreach ($prod[7] as $prod_attr) : 
							echo $prod_attr . '<br />';
                    	endforeach; ?>
                	</td>
                <?php endif; ?>
                <td style="white-space: nowrap; width: 100%;"> <?php echo $_site_url . '/checkout/?' . $prod[4]; ?> </td>
                
                </tr>
			
			<?php endforeach; ?>
    </table>
    


</div>

<?php
}
?>