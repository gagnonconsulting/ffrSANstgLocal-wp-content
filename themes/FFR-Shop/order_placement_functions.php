<?php
//*****************************************************************************************************************/
// functions to work with orders in WooCommerce
//*****************************************************************************************************************/


//*****************************************************************************************************************/
// add an action to fire after WPALLEXPORT runs to send the orders (line by line) 
//  that are now in file to GSS Order Staging Table
//*****************************************************************************************************************/

add_action('woocommerce_order_status_processing', function($order_id){ 	
  // Don't try to call the WP All Export defined function if it's not available. 	
  if(function_exists('pmxe_woocommerce_order_status_completed')){ 		
    // Execute export using provided order ID. 		
    pmxe_woocommerce_order_status_completed($order_id); 	
  } 
});


//*****************************************************************************************************************/
// call the function to read the WP_ALL_EXPORT created file  and send all the order lines to the server 
//*****************************************************************************************************************/


add_action('pmxe_after_export', 'run_after_any_export', 10, 2);

function run_after_any_export ($export_id, $exportObj) {

  //***********************************************************************************/
  //              export_id = 7 : read_orders_and_send_each_line_to_staging
  //***********************************************************************************/
  if ($export_id == 7) {       

      echo "<h4>Exporting Orders (export_id = 7)</h4>";

      // first move the export file that will be read from to a known location (
      $newFilePath = "GSS-XFER-Files/Outgoing/".$GLOBALS['Current_ENV']."/ORDERS/Orders-Export.csv";
      $newOrdersFile = move_export_file($export_id, $exportObj, $newFilePath);
      
      // then read the newOrdersFile into an array with a header row
      /* Map Rows and Loop Through Them */
      $rows   = array_map('str_getcsv', file($newOrdersFile) );
      //pretty_dump ($rows, "rows ");
      $header = array_shift($rows);

      foreach($rows as $row) {
          $lines[] = array_combine($header, $row);
      }

      //echo "<pre>ORDER LINES";print_r ($lines);echo "</pre>";
      $prev_order_id = "";
      $curr_order_id = "";
      $linenumber = 1;

      //echo ("<pre>"); var_dump ($lines); echo ("</pre>");
      //echo ("<pre>"); print_r ($lines); echo ("</pre>");
      
      foreach ($lines as $line => $contents) {
          $curr_order_id = $contents ['Order ID'];
          echo ("prevOrderID = $prev_order_id, current order ID = $curr_order_id ");
          if ( strcmp ($prev_order_id, $curr_order_id) == 0 ) {   // order id is the same as the one before
            $linenumber = $linenumber + 1;                        // same order, so increment linenumber
          } else {
            $linenumber = 1;                                      // else new order, reset linenumber to 1
          }
          echo ("LineNumber = $linenumber   ");

          $sql_returned = create_sql_for_order_staging($contents, $linenumber);

          $prev_order_id = $curr_order_id; 

          // NOW use the sql created for this order line to the Server Staging Table
          gss_send_order_line_to_server_staging_table ($sql_returned);
          //echo "<h4>Orders sent to ".$GLOBALS['Current_ENV']."</h4>";
      }
  }
  //***********************************************************************************/
  //              export_id = 11 : get list of orders with a status of Completed 
  //                                to find their tracking number                         
  //***********************************************************************************/
  else if ($export_id = 11) {

      echo "<h4>Retrieving Tracking Numbers of Completed Orders (export_id = 11)</h4>";

      // first move the export file that will be read from to a known location (wp-content/uploads/GSS-XFER-Files/Outgoing/".$GLOBALS['Current_ENV']."/ORDERS/current-Orders-Tracking-of-Completed.t.csv)
      $newFilePath = "GSS-XFER-Files/Outgoing/".$GLOBALS['Current_ENV']."/ORDERS/current-Orders-Tracking-of-Completed.csv";
      $newOrdersTrackingNoFile = move_export_file($export_id, $exportObj, $newFilePath);
      
      // then read the newOrdersFile into an array with a header row
      /* Map Rows and Loop Through Them */
      $rows   = array_map('str_getcsv', file($newOrdersTrackingNoFile) );
      //pretty_dump ($rows, "rows ");
      $header = array_shift($rows);

      foreach($rows as $row) {
        $lines[] = array_combine($header, $row);
      }
      foreach ($lines as $line => $contents) {
        $curr_order_id = $contents ['Order ID'];
        echo ("prevOrderID = $prev_order_id, current order ID = $curr_order_id ");

        $sql_returned = create_sql_for_order_staging($contents, $linenumber);

        // NOW use the sql created for this order line to the Server Staging Table
        gss_send_order_line_to_server_staging_table ($sql_returned);
        //echo "<h4>Orders sent to $GLOBALS['Current_ENV']</h4>";
    }
  }
}

function move_export_file ($export_id, $exportObj, $newFilePath) {
	
  // Get WordPress's upload directory.
  $upload_dir = wp_get_upload_dir();

  // Check whether "Secure Mode" is enabled in All Export > Settings
  $is_secure_export = PMXE_Plugin::getInstance()-> getOption('secure');


  if ( !$is_secure_export ) {
      // Get filepath when 'Secure Mode' is off.
      $filepath = get_attached_file($exportObj->attch_id);
  } else {
      // Get filepath with 'Secure Mode' on.
      $filepath = wp_all_export_get_absolute_path($exportObj->options['filepath']);
  }

  $newFileName = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $newFilePath;
  //echo ("newFileName = $newFileName</br>");

// Move export file to wp-content/uloads/GSS-XFER-Files/Outgoing/".$GLOBALS['Current_ENV']."/ORDERS/Orders-Export.csv
  rename( $filepath, $newFileName);
  return ($newFileName);
}
  
//*****************************************************************************************************************/
//            THIS IS THE CODE to create the SQL to INSERT the Order Line into the Order Staging Table
//*****************************************************************************************************************/

function create_sql_for_order_staging ($order, $linenumber) {
  pretty_dump ($order);
  $CustomerPO = "WEB-" . $order ['Order ID'];
  $External_Order_ID = "WEB-" . $order ['Order ID'];
  $Order_ID = $order ['Order ID'];
  $Customer_ID = "WEB-" . $order ['Customer User ID'];
  $Linenumber = $linenumber;  
  $OrderDate = $order ['Order Date'];
  $OrderDueDate = $order ['Order Date'];
  $LineOrderDate = $order ['Order Date'];
  $LinePromiseDate = $order ['Order Date'];
  $SKU = $order ['SKU'];
  $Quantity = $order ['Quantity'];
  $Item_Total = $order ['Item Total'];
  $Item_Cost = $order ['Item Cost'];
  $Item_Tax_Total = $order ['Item Tax Total'];
  $Product_Name = $order ['Product Name'];
  $Shipping_Method = $order ['Shipping Method'];
  $Shipping_Cost = $order ['Shipping Cost'];
  $Shipping_Name = $order ['Shipping First Name'] . " ". $order ['Shipping Last Name'];
  $Shipping_Company = $order ['Shipping Company'];
  $Shipping_Address1 = $order ['Shipping Address 1'];
  $Shipping_Address2 = $order ['Shipping Address 2'];
  $Shipping_City = $order ['Shipping City'];
  $Shipping_State = $order ['Shipping State'];
  $Shipping_Postcode = $order ['Shipping Postcode'];
  $Shipping_Country = $order ['Shipping Country'];
  $Billing_Name = $order ['Billing First Name'] . " ". $order ['Billing Last Name'];
  $Billing_Address1 = $order ['Billing Address 1'];
  $Billing_Address2 = $order ['Billing Address 2'];
  $Billing_City = $order ['Billing City'];
  $Billing_State = $order ['Billing State'];
  $Billing_Postcode = $order ['Billing Postcode'];
  $Billing_Country = $order ['Billing Country'];
  $Billing_Phone = $order ['Billing Phone'];
  $Terms = "PrePay";
  $Ext_PartNumber = $order ['SKU'];
  $SalespersonCode = "WEB";
  $NoteContent = $order ['Note Content']; 
  $current_user = wp_get_current_user();
  $ShipContactEmail = $current_user->user_email;
  $CC_AUTH_INFO = $order ['CC_AUTH_INFO'];

  $order_sql = 
      "INSERT INTO GCG_5807_ORDER_STAGE (  
          Order_No_External,
          Ext_CustomerNo,
          LineNumber,
          OrderDate,
          OrderDueDate,
          LineOrderDate,
          LinePromiseDate,
          GSS_PartNumber,
          QtyOrdered,
          LineTotalPrice,
          LineUnitPrice,
          LineTaxes,
          PartDescription,
          ShipVia,
          Freight,
          ShipToName,
          ShipToAddress1,
          ShipToAddress2,
          ShipToCity,
          ShipToState,
          ShipToZip,
          ShipToCountry,
          BillToName,
          BillToAddress1,
          BillToAddress2,
          BillToCity,
          BillToState,
          BillToZip,
          BillToCountry,
          BillToPhone,
          Terms,
          Ext_PartNumber,
          SalespersonCode,
          CustomerPO,
          ShipToContactName,
          ShipToContactEmail,
          UserField1Head
          )
      VALUES (
          '$External_Order_ID',
          '$Customer_ID',
          $Linenumber,
          '$OrderDate',
          '$OrderDueDate',
          '$LineOrderDate',
          '$LinePromiseDate',
          '$SKU',
          $Quantity,
          $Item_Total,
          $Item_Cost,
          $Item_Tax_Total,
          '$Product_Name',
          '$Shipping_Method',
          $Shipping_Cost,
          '$Shipping_Name',
          '$Shipping_Address1',
          '$Shipping_Address2',
          '$Shipping_City',
          '$Shipping_State',
          '$Shipping_Postcode',
          '$Shipping_Country',
          '$Billing_Name',
          '$Billing_Address1',
          '$Billing_Address2',
          '$Billing_City',
          '$Billing_State',
          '$Billing_Postcode',
          '$Billing_Country',
          '$Billing_Phone',
          '$Terms',
          '$Ext_PartNumber',
          '$SalespersonCode',
          '$CustomerPO',
          '$Shipping_Name',
          '$ShipContactEmail',
          '$CC_AUTH_INFO'
      )";
  return ($order_sql);
}

//*****************************************************************************************************************/
//            THIS IS THE CODE to EXPORT ORDERS from Woo via WP_ALL_EXPORT and send to GSS server staging table
//*****************************************************************************************************************/

// Send the latest Orders from Woo to GSS
// utilizes CURL with an ExecuteQuery SQL into the GSS Order Staging table (GCG_5807_ORDER_STAGE)

function gss_send_order_line_to_server_staging_table ($order_sql) {

  echo '<h4>gss_send_order_line_to_server_staging_table() - Sending with curl...</h4>';
  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);

  $curl_query = 'ExecuteQuery*!*'.$order_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
  echo "<p>$curl_query</p>";

  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://factoryfive.net:6000',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $curl_query,
  ));

  //var_dump ( CURLOPT_POSTFIELDS );
  $response = curl_exec($curl);
  // Check HTTP status code
  if (!curl_errno($curl)) {
    switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
      case 200:  # OK
        break;
      default:
        echo 'Unexpected HTTP code: ', $http_code, "\n";
    }
  }
  // Check for errors and display the error message
  if($errno = curl_errno($curl)) {
    $error_message = curl_strerror($errno);
    echo "CURL error ({$errno}):\n {$error_message}";
  }
  echo "<p>CURL response = $response, errno = $errno</p>";
  curl_close($curl); 
  echo '<H4>Done - Now run All Export > Manage Exports > Orders Export template</H4>';
}


?>