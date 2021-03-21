<?php
/**
Plugin Name: Colibri API
description: Manage data from Aws Lambda using shortcode
Version: 1.0
Author: Clipart Paolo Alberti
License: GPLv2 or later
Text Domain: colibri-api
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('datamodels.php');
include('data\data.php'); 



//=====CURL API call function ( ***** Is better to use wp functions )
//-----Method: POST, PUT, GET etc
//---Data: array("param" => "value") ==> index.php?param=value
function CallAPI($url,$data_to_send)
{
   $headers = array("Content-Type:multipart/form-data");
   $ch = curl_init();
   
   curl_setopt_array($ch, array(
      CURLOPT_URL => $url,
      CURLOPT_HEADER => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_ENCODING => "",
      CURLOPT_TIMEOUT => 30,
      CURLOPT_POST => 1,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>  $data_to_send,
  ));
  
   //----Execute
   $result=curl_exec($ch);

   //----Closing
   curl_close($ch);
   //----Subtracts header size to get only body
   $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
   $result = substr($result, $header_size);
   return $result;
}

/* Plugin ADMIN panel
* 
*/
function clbr_plugin_menu_func() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "COLIBRI",                // Page title
                  "Colibri",                // Menu title
                  "manage_options",          // Minimum capability (manage_options is an easy way to target administrators)
                  "Colibri Aws Lambda supplier tracker",                // Menu slug
                  "clbr_plugin_options"      // Callback that prints the markup
               );
}
add_action( "admin_menu", "clbr_plugin_menu_func" );
/* === Print the markup for the plugin page === */
function clbr_plugin_options() {
   if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
   }
  include 'form.html.php';
  $data['title'] = 'Colibri Setup';
  Timber::render('twig/setup.html.twig', $data);
}


/* View from internal data
* 
*/
function view_products_func(){
   global $data_a;
   $data = $data_a['products'];
   //var_dump($data);
   Timber::render('twig/view_products.html.twig', ['products'=>$data]);
}
add_shortcode( "view_products", "view_products_func" );


/* View from AWS LAMBDA/LOCALHOST data
* 
*/
function api_view_products_func(){
   //$response = CallAPI('https://acbun7s2gc.execute-api.eu-west-1.amazonaws.com/default/getProducts', "");
   $response = file_get_contents('http://localhost:8000/prodotti/biscottone'); 

   $response = json_decode($response, true);
   $data = array(
      'products' =>  $response['products'],
  );
  var_dump($data);
  Timber::render('twig/view_products.html.twig', $data);
}
add_shortcode( "api_view_products", "api_view_products_func" );




/* Enqueue bootstrap scripts and styles
* 
*/
function your_theme_enqueue_scripts() {

   // jquery
   wp_enqueue_script( 'bootstrap', plugin_dir_url( __FILE__ ) . 'js/jquery3.6.0.min.js' );
   // all styles
   wp_enqueue_style( 'bootstrap', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
   //wp_enqueue_style( 'theme-style', plugin_dir_path( __FILE__ ); . '/css/style.css', array(), 20141119 );
   // all scripts
   wp_enqueue_script( 'bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js' );
   //wp_enqueue_script( 'theme-script', plugin_dir_path( __FILE__ ); . '/js/scripts.js', array('jquery'), '20120206', true );

}
add_action( 'wp_enqueue_scripts', 'your_theme_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'your_theme_enqueue_scripts' ); // utilizzarlo solo per la pagina del plugin

function my_scripts_method() {
   wp_deregister_script( 'jquery' );
 }
 //add_action('wp_enqueue_scripts', 'my_scripts_method');
