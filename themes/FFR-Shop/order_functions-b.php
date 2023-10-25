<?php
//*****************************************************************************************************************/
// functions to work with orders in WooCommerce
//*****************************************************************************************************************/


// create a shortcode at system init to list the orders on a page or post
add_action ('init', 'add_order_shortcodes');
function add_order_shortcodes() {
    add_shortcode('write_all_orders_to_file', 'write_all_orders_to_file');
    add_shortcode('show_all_orders_on_screen', 'show_all_orders_on_screen');
}

//*****************************************************************************************************************/
// add an action to fire after WPALLEXPORT runs to send the orders (line by line) 
//  that are now in file to GSS Order Staging Table
//*****************************************************************************************************************/

add_action('pmxe_after_export', 'export_orders_and_send_to_staging', 10, 2);

function export_orders_and_send_to_staging ($export_id, $exportObj){

    echo "<p>Filename = $filename</p>";
    // first move the export file 
    $filename = move_export_file($export_id, $exportObj);

    /* Map Rows and Loop Through Them */
    $rows   = array_map('str_getcsv', file($filename));
    $header = array_shift($rows);

    foreach($rows as $row) {
        $lines[] = array_combine($header, $row);
    }

    echo "ORDER LINES<pre>";print_r ($lines);echo "</pre>";
    echo "<ul>";
    $prev_order_id = "";
    $curr_order_id = "";
    $linenumber = 1;

    //echo ("<pre>"); var_dump ($lines); echo ("</pre>");
    //echo ("<pre>"); print_r ($lines); echo ("</pre>");
    

    foreach ($lines as $line => $contents) {
        $curr_order_id = $contents ['Order ID'];
        echo ("prevOrderID = $prev_order_id, current order ID = $curr_order_id ");
        if ( strcmp ($prev_order_id, $curr_order_id) == 0 ) {   // order id is the same as the one before
            $linenumber = $linenumber + 1; // same order, so increment linenumber
        } else {
            $linenumber = 1;            // new order, reset linenumber to 1
        }
        echo ("LineNumber = $linenumber   ");

        $sql_returned = create_sql_for_order_staging($contents, $linenumber);

        $prev_order_id = $curr_order_id; 
        // echo "sending to staging - SQL: $sql_returned</li>";
        gss_send_order_line_to_staging ($sql_returned);
    }
    echo "</ul>";
}

//*****************************************************************************************************************/
//            THIS IS THE CODE to create the SQL to INSERT the Order Line into the Order Staging Table
//*****************************************************************************************************************/

function create_sql_for_order_staging ($order, $linenumber) {
    $Order_ID = $order ['Order ID'];
    $Customer_ID = $order ['Customer User ID'];
    $Linenumber = $linenumber;  
    $Order_Date = $order ['Order Date'];
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

    $order_sql = 
        "INSERT INTO GCG_5807_ORDER_STAGE (  
            Order_No_External,
            Ext_CustomerNo,
            LineNumber,
            OrderDate,
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
            BillToPhone)
        VALUES (
            '$Order_ID',
            '$Customer_ID',
            $Linenumber,
            '$Order_Date',
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
            '$Billing_Phone'
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
    $Current_company = get_current_company();
    $Company_API_ID = get_company_API_ID ($Current_company);
  
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
      echo "cURL error ({$errno}):\n {$error_message}";
    }
    echo "<p>Curl response = $response, errno = $errno</p>";
    curl_close($curl); 
    echo '<H4>Done - Now run All Export > Manage Exports > Orders Export template</H4>';
  }
  

?>