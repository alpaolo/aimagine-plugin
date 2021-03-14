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

require_once('aimagine.php');

global $uploaded_file_name;

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


//====== Shortcode function =====
function detect_face_func( $atts, $aimagine ) {
   $aimagine = ( $aimagine ) ? $aimagine : new Aimagine();
   $path = $uploadfile; 
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
   $image_element = '<img src = "'.$path.'" /><br/>';
   return $image_element."<p>".$response['message']."</p>"."<p>Bboxes: ".json_encode($response['bboxes'])."</p>";
 }
 //Register shortcode.
add_shortcode( "blurface", "detect_face_func" );

//====== Shortcode function =====
 function test_face_func( $atts, $aimagine ) {
   global $uploaded_file_name;
   $image_element = '<img class="wp-image-308 avia-img-lazy-loading-not-308 avia_image" src = "'.wp_upload_dir()['url'].'/'.$uploaded_file_name.'" ></img><br/>';
   return "<h3>The plugin is running</h3>";
   //return "<img src='https://botservice.it/aimagine/wp-content/plugins/aimagine-api/valentina.jpg' />";
}
//Register shortcode.
add_shortcode( "testaimagine", "test_face_func" );


 //=====Plugin panel menu =======
function aimg_plugin_menu_func() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "AIMAGINE",                // Page title
                  "Aimagine",                // Menu title
                  "manage_options",          // Minimum capability (manage_options is an easy way to target administrators)
                  "aimagine",                // Menu slug
                  "aimg_plugin_options"      // Callback that prints the markup
               );
}
//Register the menu.
add_action( "admin_menu", "aimg_plugin_menu_func" );


//=====Menu Callback - Print the markup for the page 
function aimg_plugin_options() {
   if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
   }
  //include 'form.html.php';
  $data['title'] = 'Aimagine Setup';
  $query_images_args = array(
   'post_type'      => 'attachment',
   'post_mime_type' => 'image',
   'post_status'    => 'inherit',
   'posts_per_page' => - 1,
);

$query_images = new WP_Query( $query_images_args );

$images = array();
foreach ( $query_images->posts as $image ) {
   $images[] = wp_get_attachment_url( $image->ID );
}
   $data['images']=$images;
   Timber::render('twig/setup.html.twig', $data);
}

/* =================================================================================================================== */
function handle_post() {
   if(empty($_FILES['fileToUpload'])) return;
   global $uploaded_file_name;
   $uploaddir = wp_upload_dir()['path']."/";
   $uploaded_file_name = basename($_FILES['fileToUpload']['name']);
   $uploadfile = $uploaddir . $uploaded_file_name;

   if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadfile)) {
      //echo "File is valid, and was successfully uploaded.\n";
   } else {
      //echo "Possible file upload attack!\n";
   }
   
}

//=====Action======  
function custom_function() {
   if ( is_page('api') ) {
       handle_post();
   }
}
//Register the action.
add_action( 'template_redirect', 'custom_function' );

//=====Action====== 
/**
 * Action: Enqueue custom script
 */ 
function onloadscript() {
   global $uploaded_file_name;
   $uploadurl = wp_upload_dir()['url']."/";
   $fileurl = $uploadurl.$uploaded_file_name;
   wp_enqueue_script( 'my-js', plugin_dir_url( __FILE__ ).'onload.js', false );

   ?>
   <script>
   jQuery(document).ready(function($) {
  img_el = document.getElementById('test_img')
  im_elem = $('#test_img').find('img');
  im_elem.attr('src',  <?php $fileurl ?>);
  console.log (im_elem.attr('src'));
 });
   </script>

   <?php
}
//Register the action.
add_action( 'wp_body_open', 'onloadscript' );


/**
 * Enqueue scripts and styles
 */
function your_theme_enqueue_scripts() {
   // all styles
   wp_enqueue_style( 'bootstrap', plugin_dir_path( __FILE__ ) . 'css/bootstrap.min.css');
   //wp_enqueue_style( 'theme-style', plugin_dir_path( __FILE__ ); . '/css/style.css', array(), 20141119 );
   // all scripts
   wp_enqueue_script( 'bootstrap', plugin_dir_path( __FILE__ ) . 'js/bootstrap.min.js' );
   //wp_enqueue_script( 'theme-script', plugin_dir_path( __FILE__ ); . '/js/scripts.js', array('jquery'), '20120206', true );
}
add_action( 'wp_enqueue_scripts', 'your_theme_enqueue_scripts' );





