<?php
// functions to show SKUs in Cart and Order in WooCommerce

function wtwh_return_sku( $product ) {
   $sku = $product->get_sku();
   if ( ! empty( $sku ) ) {
      return '<p>SKU: ' . $sku . '</p>';
   } else {
      return '';
   }
}
 
// This adds the SKU under cart/checkout Product name
add_filter( 'woocommerce_cart_item_name', 'wtwh_sku_cart_checkout_pages', 9999, 3 );
 
function wtwh_sku_cart_checkout_pages( $item_name, $cart_item, $cart_item_key  ) {
   $product = $cart_item['data'];
   $item_name .= wtwh_return_sku( $product );
   return $item_name;
}
 
// This adds SKU under order Product table name
add_action( 'woocommerce_order_item_meta_start', 'wtwh_sku_thankyou_order_email_pages', 9999, 4 );
 
function wtwh_sku_thankyou_order_email_pages( $item_id, $item, $order, $plain_text ) {
   $product = $item->get_product();
   echo wtwh_return_sku( $product );
}
?>