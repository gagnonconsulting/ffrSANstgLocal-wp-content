<?php /*

  This file is part of a child theme called FFR-Shop.
  Functions in this file will be loaded before the parent theme's functions.
  For more information, please read
  https://developer.wordpress.org/themes/advanced-topics/child-themes/

*/
// ADDING THIS LINE TO MAKE A TEST CHANGE
// ADDING THIS LINE TO MAKE A TEST CHANGE
// this code loads the parent's stylesheet (leave it in place unless you know what you're doing)

function your_theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, 
      get_template_directory_uri() . '/style.css'); 

    wp_enqueue_style( 'child-style', 
      get_stylesheet_directory_uri() . '/style.css', 
      array($parent_style), 
      wp_get_theme()->get('Version') 
    );
}

add_action('wp_enqueue_scripts', 'your_theme_enqueue_styles');

/*  Add your own functions below this line.
======================================== */ 

include_once get_stylesheet_directory() . '/wp_sku_functions.php';
include_once get_stylesheet_directory() . '/wp_commercekit_functions.php';
include_once get_stylesheet_directory() . '/gss_functions.php';
include_once get_stylesheet_directory() . '/inventory_functions.php';
include_once get_stylesheet_directory() . '/order_placement_functions.php';
include_once get_stylesheet_directory() . '/order_status_functions.php';
include_once get_stylesheet_directory() . '/customer_functions.php';


function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

add_action ('test_cron_fire', 'test_cron_fire');

function test_cron_fire() {
    write_log('test_cron_fire has run');
    console_log('test_cron_fire has ran');
}

if (!function_exists('write_log')) {
  function write_log($log) {
      if (true === WP_DEBUG) {
          if (is_array($log) || is_object($log)) {
              error_log(print_r($log, true));
          } else {
              error_log($log);
          }
      }
  }
}
//FROM https://digwp.com/2010/04/call-widget-with-shortcode/
function widget($atts) {
    
    global $wp_widget_factory;
    
    extract(shortcode_atts(array(
        'widget_name' => FALSE
    ), $atts));
    
    $widget_name = wp_specialchars($widget_name);
    
    if (!is_a($wp_widget_factory->widgets[$widget_name], 'WP_Widget')):
        $wp_class = 'WP_Widget_'.ucwords(strtolower($class));
        
        if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')):
            return '<p>'.sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"),'<strong>'.$class.'</strong>').'</p>';
        else:
            $class = $wp_class;
        endif;
    endif;
    
    ob_start();
    the_widget($widget_name, $instance, array('widget_id'=>'arbitrary-instance-'.$id,
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => ''
    ));
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
    
}
add_shortcode('widget','widget'); 

// SORT OF WORKS, BUT BREAKS FOOTER
function which_template_is_loaded() {
//	if ( is_super_admin() ) {
//		global $template;
//		print_r( $template );
//  }
}

add_action( 'wp_footer', 'which_template_is_loaded' );

function pretty_dump ($input) {
    echo "<pre>";
    var_dump ($input);
    echo "</pre>";
}
?>