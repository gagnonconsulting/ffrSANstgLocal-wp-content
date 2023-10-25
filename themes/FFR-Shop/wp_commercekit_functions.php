<?php 

//FROM Commercekit
function shoptimizer_child_pdp_gallery_thumbnails( $count ){
	return 5;
}
add_filter( 'commercekit_product_gallery_thumbnails', 'shoptimizer_child_pdp_gallery_thumbnails', 10, 1 );

?>