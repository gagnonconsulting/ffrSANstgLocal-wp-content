<?php
//*****************************************************************************************************************/
//  THIS IS THE CODE to GET a FULL INVENTORY from GSS and put them into a file for WP_ALL_IMPORT to send to Woo
//*****************************************************************************************************************/

// first the shortcode
add_shortcode ('gss_full_inventory_refresh', 'gss_refresh_inventory_from_server');

function gss_refresh_inventory_from_server () {
  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);

  $inventory_refresh_sql = "select IA.PART, IA.DESCRIPTION, AP.ALT_PRICE_1 as 'Retail_Price', 
                                    IA.QTY_ONHAND, IA.LENGTH, IA.WIDTH, IA.THICKNESS, I2.LBS 
                            from V_INVENTORY_ALL as IA, V_INV_ALT_PRICE as AP, V_INVENTORY_MST2 as I2
                            where IA.PART = AP.PART and IA.PART = I2.PART and IA.PART not like '50%'"; // 50% is wildcard for 50012, etc, which are kits

  $curl_query = 'ExecuteQueryWithReturn*!*'.$inventory_refresh_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
  echo "<p>$curl_query</p>";

  $curl = curl_init();
  curl_setopt_array($curl, 
    array(
      CURLOPT_URL => 'http://factoryfive.net:6000',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_POSTFIELDS =>$curl_query,
    )
  );
  $curl_result = curl_exec($curl);
  if($curl_result === FALSE) {
    die(curl_error($curl));
  }
  curl_close($curl);

  // write the curl_result to the updagte_inventory.xml file to use in the WP_ALL_IMPORT script 

  $upload_dir_info = wp_upload_dir();
  $upload_path = $upload_dir_info['basedir']; // Full local path to the uploads directory

  $update_inventory_file = $upload_path."/GSS-XFER-Files/Incoming/".$GLOBALS['Current_ENV']."/INVENTORY/refresh_inventory.xml";
  echo '<p>putting inventory update file into <br/>'.$update_inventory_file.'</p>';
  file_put_contents($update_inventory_file, $curl_result );
  echo '<H4>Done - Now run All Import > Manage Imports > refresh_inventory template</H4>';
  return;
}
//*****************************************************************************************************************/
//  THIS IS THE CODE to GET INVENTORY UPDATES from GSS and put them into a file for WP_ALL_IMPORT to send to Woo
//*****************************************************************************************************************/

// first the shortcode
add_shortcode ('gss_get_inventory_updates', 'gss_get_inventory_updates_from_server');

// and the shortcode function
function gss_get_inventory_updates_from_server () {

  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);

  $inventory_update_sql =  "select IA.PART, IA.DESCRIPTION, AP.ALT_PRICE_1 as 'Retail_Price', 
                                IA.QTY_ONHAND, IA.LENGTH, IA.WIDTH, IA.THICKNESS, I2.LBS 
                            from V_INVENTORY_ALL as IA, V_INV_ALT_PRICE as AP, V_INVENTORY_MST2 as I2
                            where IA.PART = AP.PART and IA.PART = I2.PART and IA.PART not like '50%'"; // 50% is wildcard for 50012, etc, which are kits

  $curl_query = 'ExecuteQueryWithReturn*!*'.$inventory_update_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
  echo "<p>$curl_query</p>";

  $curl = curl_init();
  curl_setopt_array($curl, 
    array(
      CURLOPT_URL => 'http://factoryfive.net:6000',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_POSTFIELDS =>$curl_query,
    )
  );
  $curl_result = curl_exec($curl);
  if($curl_result === FALSE) {
    die(curl_error($curl));
  }
  curl_close($curl);

  // write the curl_result to the updagte_inventory.xml file to use in the WP_ALL_IMPORT script 

  $upload_dir_info = wp_upload_dir();
  $upload_path = $upload_dir_info['basedir']; // Full local path to the uploads directory

  $update_inventory_file = $upload_path."/GSS-XFER-Files/Incoming/".$GLOBALS['Current_ENV']."/INVENTORY/update_inventory.xml";
  echo '<p>putting inventory update file into <br/>'.$update_inventory_file.'</p>';
  file_put_contents($update_inventory_file, $curl_result );
  echo '<H4>Done - Now run All Import > Manage Imports > update_inventory template</H4>';
  https://factfivesanstg.wpengine.com/wp-admin/admin.php?page=pmxi-admin-manage&id=39&action=update
  return;
}

// functions to work with inventory in WooCommerce

// No manage stock for parent variable products. Keep manage stock for simple products.
// Do not set parent Stock
add_action( 'wp_all_import_variable_product_imported', 'parent_no_stock', 10, 1 );
function parent_no_stock( $id ) {
    // Optional: only run for certain import ID
    // $import_id = wp_all_import_get_import_id(); 
    // if ( $import_id != '25' ) return;

	$is_converted = get_post_meta($id, '_wpai_converted_to_simple', true);	
	if ( empty($is_converted) ) {
		$prod = wc_get_product( $id );
		$prod->set_manage_stock( false );
		$prod->save();
	}
}

add_action( 'wp_all_import_make_product_simple', 'my_wpai_simple_product_func', 10, 2 );
function my_wpai_simple_product_func( $prod_id, $import_id ) {
    // Optional: only run for certain import ID
    // if ( $import_id != '25' ) return;	
	update_post_meta($prod_id, '_wpai_converted_to_simple', 1);
}

?>