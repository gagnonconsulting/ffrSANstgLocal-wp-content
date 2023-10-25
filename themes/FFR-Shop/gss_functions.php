<?php
//
// functions speficic to Global Shop Solutions integration (Inmventory, Customers, Orders, etc.)
//

// Manual function to get the latest Invengtory list from GSS (will be used to recreate the Product database in Woo)
// utilizes CURL with an ExecuteQueryWithReturn and SQL into the GSS database V_INVENTORY_ALL view and 
// recreates the full_inventory.xml file used by ALL_IMPORT. If All_IMPORT is used, it should done through the Existing Products option 
// (vs New Products) If there are new products added to the GSS Inventory, a different function should be used to ensure that ALL_IMPORT
// only imports NEW products, and is set to not touch products (edit or delete) already in the Woo database
// NOTE that this is only needed to pupulate the WooCommerce product list, but does not include Woo Taxonomy
// information, so it would be best to use this with the current product load currently in the system.
    

//*****************************************************************************************************************/
//           DEFINE and SET the company_API_ID to the desired Company File 
//                      manually uncomment the desired Company in set_Current_ENV()
//*****************************************************************************************************************/

function before_xml_import( $import_id ) {
    
  if ($import_id == 39) { 
    // Only Run for import ID 39, which is update_inventory template.
      gss_get_inventory_updates_from_server();  
  }
}

//add_action('pmxi_before_xml_import', 'before_xml_import', 10, 1);

$Company_name = "FAC010";

//$Current_ENV = "FFR";
//$Current_ENV = "PLA";
$Current_ENV = "TST";



function get_company_API_ID ($ENV) {
    if  ($ENV == "FFR") {
        $company_API_ID = '0c1b71ff04a2706099ecda64f58640d6'; // FFR Company
    } elseif  ($ENV == "PLA") {
        $company_API_ID = '5adae3cdbb6f5fb492beba67c1aa1712'; // PLA Company
    } elseif  ($ENV == "TST") {
        $company_API_ID = '59a502be9d6964608a9656d981a00497'; // TST Company
    } else {
        echo "No Company selected. Please select a Current_ENV in the get_Current_ENV function";
        die;
    }
    return ($company_API_ID);
 }

 
?>