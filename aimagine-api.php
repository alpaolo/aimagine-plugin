<?php
/**
Plugin Name: Aimagine API
description: >-
  Add aimagine project information using shortcode
Version: 1.0
Author: aimagine
License: GPLv2 or later
Text Domain: aimagine-api
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once('Aimagine.php');


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


//=====Shortcode function
function detect_face_func( $atts, $aimagine ) {
   $aimagine = ( $aimagine ) ? $aimagine : new Aimagine();
   $path = 'C:\TOOLS\xampp\htdocs\wordpress\wp-content\plugins\aimagine-api\valentina.jpg'; 
   $handle = fopen($path, "r");
   $image = fread($handle, filesize($path));
   $base64_image = base64_encode($image);
   print(filesize($path)."<br/>");
   print(strlen($image)."<br/>");
   print(strlen($base64_image)."<br/>");
   $uploadRequest = array(
      'file' => base64_encode($image)
  );
   $response = CallAPI('127.0.0.1:8000/api/v1/blur', $uploadRequest);
   //$response = file_get_contents('http://127.0.0.1:8000/api/v1');
   $response = json_decode($response, true);
   return "<p>".$response['message']."</p>"."<p>Bboxes: ".json_encode($response['bboxes'])."</p>";
 }

 //=====Plugin panel
function aimg_plugin_menu_func() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "AIMAGINE",                // Page title
                  "Aimagine",                // Menu title
                  "manage_options",          // Minimum capability (manage_options is an easy way to target administrators)
                  "aimagine",                // Menu slug
                  "aimg_plugin_options"      // Callback that prints the markup
               );
}

//=====Print the markup for the page
function aimg_plugin_options() {
   if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
   }
  //include 'form.html.php';
  $data['title'] = 'Aimagine Setup';
  Timber::render('twig/setup.html.twig', $data);
}

//=====Register shortcode.
add_shortcode( "blurface", "detect_face_func" );

//=====Register the menu.
add_action( "admin_menu", "aimg_plugin_menu_func" );




