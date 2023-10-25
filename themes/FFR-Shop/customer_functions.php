<?php

function check_if_new_customer($email) {

  echo '<h4>check_if_new_customer() - Sending with curl...</h4>';
  $Company_API_ID = get_company_API_ID ($GLOBALS['Current_ENV']);
  $query_sql = create_sql_for_check_customer ($email);
  echo "<p>query_sql = $query_sql </p>";

  $curl_query = 'ExecuteQuerywithReturn*!*'.$query_sql.'*!*'.$GLOBALS['Company_name'].'*!*'.$Company_API_ID.'|||EOF|||';
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
  echo '<H4>Done</H4>';
  
}

//*****************************************************************************************************************/
//            THIS IS THE CODE to create the SQL to INSERT the Order Line into the Order Staging Table
//*****************************************************************************************************************/

function create_sql_for_check_customer ($email) {
  //pretty_dump ($email);

    $query_sql = "SELECT m.CUSTOMER, m.NAME_CUSTOMER, m.ADDRESS1, m.ADDRESS, m.CITY, m.STATE, m.COUNTRY, m.INTL_FLAG, m.TELEPHONE, s.EMAIL 
                  FROM V_CUSTOMER_MASTER AS m, V_CUSTOMER_SALES AS s
                  WHERE m.CUSTOMER = s.CUSTOMER AND s.EMAIL LIKE '$Order'";
    return ($query_sql);
}
?>