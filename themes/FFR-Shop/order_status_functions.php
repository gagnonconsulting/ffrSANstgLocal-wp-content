<?php

add_shortcode ('woo_check_order_Processing_status', 'woo_check_order_Processing_status');
add_shortcode ('woo_check_order_Completed_status', 'woo_check_order_Completed_status');
add_shortcode ('woo_get_gss_order_no', 'woo_get_gss_order_no');

function woo_check_order_Processing_status () {

  // Define the order status you want to retrieve
  $order_status = 'processing';

  // Get orders with the specified status
  $orders = wc_get_orders(array(
      'status' => $order_status,
  ));

  // Check if there are any orders
  if (!empty($orders)) {
      foreach ($orders as $order) {
          // Get order data as needed
          $order_id = $order->get_id();
          $order_status = $order->get_status(); // Woo Order status: Completed, Processing, etc.

          // Add more order details as needed (Tracking No and GSS Order No)
          echo 'Order ID: ' . $order_id . '  Status: ' . $order_status . '<br>';

          //echo 'Looking for TRACKING_NO in GSS ...';
          $tracking_no = gss_check_order_tracking_no ($order_id);
          echo "TRACKING_NO = $tracking_no<br/>"; 
          // now update the WooCommerce Order with the Tracking Number
          update_Woo_order_with_tracking_no($order_id, $tracking_no);
 
      }
  } else {
      echo '<h4>No orders with status "Processing" found.</h4>';
  }
}


function woo_check_order_Completed_status () {

  // Define the order status you want to retrieve
  $order_status = 'completed';

  // Get orders with the specified status
  $orders = wc_get_orders(array(
      'status' => $order_status,
  ));

  // Check if there are any orders
  if (!empty($orders)) {
      foreach ($orders as $order) {
          // Get order data as needed
          $order_id = $order->get_id();
          $order_status = $order->get_status();
          // Add more order details as needed
          echo 'Order ID: ' . $order_id . '  Status: ' . $order_status . '<br>';
      }
  } else {
      echo '<h4>No orders with status "Completed" found.</h4>';
  }
}

function woo_get_GSS_order_no() {

  // Define the order status you want to retrieve
  // leaving order_status blank to get any order
  // Get orders with the specified status
  $orders = wc_get_orders(array(
  ));

  // Check if there are any orders
  if (!empty($orders)) {
    foreach ($orders as $order) {
        // Get order data as needed
        $order_id = $order->get_id();
        $order_status = $order->get_status(); // Woo Order status: Completed, Processing, etc.

        echo 'Order ID: ' . $order_id . '  Status: ' . $order_status . '<br>';

        //echo 'Looking for ORDER_NO in GSS ...';
        $gss_order_no = gss_get_order_order_no($order_id);
        echo "ORDER_NO = $gss_order_no<br/>";
        // now update the WooCommerce Order with the GSS_Order Number
        update_Woo_order_with_order_no($order_id, $gss_order_no);
      }
  } else {
      echo '<h4>No GSS_Order_nos found .</h4>';
  }
}


//*****************************************************************************************************************/
//            THIS IS THE CODE to Retrieve the (Tracking_No)
//              in GSS for an order based on GSS Customer_PO
//*****************************************************************************************************************/

function gss_check_order_tracking_no ($orderNo) {

  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);

  $WEBorderNo = "'WEB-$orderNo'";
  $order_status_sql = 'SELECT tracking_no FROM V_ORDER_HEADER WHERE CUSTOMER_PO LIKE '. $WEBorderNo. '';
  $curl_query = 'ExecuteQueryWithReturn*!*'.$order_status_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
  //echo "<p>$curl_query</p>";

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

  $xml = new SimpleXMLElement($response);
  //var_dump ($xml);

  $tracking_no = (string) $xml->TABLE[0]->tracking_no;

  echo "<br/>length of tracking_no is ".strlen($tracking_no)."<br/>";
  //echo "CURL response = $tracking_no<br/>";

  curl_close($curl); 
  
  return $tracking_no;
}

//*****************************************************************************************************************/
//            THIS IS THE CODE to Retrieve the (Order_No)
//              in GSS for an order based on GSS Customer_PO
//*****************************************************************************************************************/

function gss_get_order_order_no ($orderNo) {

  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);


  $WEBorderNo = "'WEB-$orderNo'";
  $order_status_sql = 'SELECT ORDER_NO FROM V_ORDER_HEADER WHERE CUSTOMER_PO LIKE '. $WEBorderNo. '';
  $curl_query = 'ExecuteQueryWithReturn*!*'.$order_status_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
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
  $xml = new SimpleXMLElement($response); 
  //var_dump ($xml);

  $gss_order_no = (string) $xml->TABLE[0]->ORDER_NO;
 
  //echo "CURL response = $gss_order_no<br/>";

  curl_close($curl); 
  
  return $gss_order_no;
}

function update_Woo_order_with_tracking_no($order_id, $tracking_no) {

  echo 'Order ID: ' . $order_id . '  Status: ' . $tracking_no . '<br>';

  $order = new WC_Order($order_id);
  //print_r($order);

  update_field('fedex_tracking', $tracking_no, $order_id);
  $order->set_status('completed');

  $order->save();
}

function update_Woo_order_with_order_no($order_id, $gss_order_no) {

  echo 'Order ID: ' . $order_id . '  Status: ' . $gss_order_no . '<br>';

  $order = new WC_Order($order_id);
  //print_r($order);

  update_field('gss_order_number', $gss_order_no, $order_id);

  $order->save();
}

?>