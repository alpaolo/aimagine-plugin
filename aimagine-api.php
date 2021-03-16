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


/***************************************************************************************************************************************************************
 * CURL API call function ( ***** Is better to use wp functions )
 * 
 */
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


/***************************************************************************************************************************************************************
 * Plugin panel menu
 * 
 */ 
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


/***************************************************************************************************************************************************************
 * Menu Callback - Print the markup for the page 
 * 
 */
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

   //
   $images = array();
   foreach ( $query_images->posts as $image ) {
      $images[] = wp_get_attachment_url( $image->ID );
   }
      $data['images']=$images;
      Timber::render('twig/setup.html.twig', $data);
}


/***************************************************************************************************************************************************************
 * Action: Process file upload when post is ready
 * 
 */ 
function custom_function() {
   $post = get_post();
   if( isset($_POST) ) {
      if ( is_page('apiok') ) {
         if(!empty($_FILES['fileToUpload'])) {
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
         else { // passa questo Bha ??
            $res = alt_get_attached_media( 'image/jpeg', $post->ID ); 
            //print_r($res);
            $args = array( 
               'post_type' => 'attachment', 
               'post_mime_type' => 'image/png',
               'numberposts' => -1, 
               'post_status' => 'inherit', 
               'post_parent' => 339
            ); 
            $images = get_children($args );
            
            
            $content = $post->post_content;
           /*
            $content = apply_filters( 'avia_builder_precompile', get_post_meta( 247, '_aviaLayoutBuilderCleanData', true ) );
            $content = apply_filters( 'the_content', $content );
            $content = apply_filters('avf_template_builder_content', $content);
            */
            //$content = Avia_Builder()->compile_post_content( $post );
            
            $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches); // cattura tutto il tag img
            $output = preg_match_all('/src=[\'"]([^\'"]+)*/i', $content, $matches);
            //print_r($matches[0]);
            //$matches = ["pippo", "pluto"];
            $c = count($matches[1]);
            for ($i=0; $i<$c; $i++){
               print_r ($matches[1][$i]);
               echo "<br>";
            }   
            
         }    
      }
   }
   else {
      //alt_get_attached_media( 'image', $post->ID );
   }
}
add_action( 'template_redirect', 'custom_function' );


/***************************************************************************************************************************************************************
 * Shortcode: Process image
 * 
 */ 
function process_image($atts, $content = null) {
   extract(shortcode_atts(array(
      'type' => 'blur'
   ), $atts));
   wp_localize_script( 'imagine-my-js', 'my_js', 
      array( 
      'user_name' => 'pluto'
      ) 
   );
}
add_shortcode('process', 'process_image');


/***************************************************************************************************************************************************************
 * BLURFACE Shortcode function
 * 
 */
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
add_shortcode( "blurface", "detect_face_func" );


/***************************************************************************************************************************************************************
 * Action: Enqueue custom script when body is open ( is possible to change the hook )
 * 
 */ 
function onloadscript() {
   global $uploaded_file_name;
   $uploadurl = wp_upload_dir()['url']."/";
   $fileurl = $uploadurl.$uploaded_file_name;
   wp_register_script('imagine-my-js', plugin_dir_url( __FILE__ ).'js/onload.js', array( 'jquery' ), '0.1', false  );
   wp_enqueue_script('imagine-my-js');
   wp_localize_script( 'imagine-my-js', 'my_js', 
        array( 
         'user_name' => 'pippo' // Test
      ) 
   );
}
add_action( 'wp_body_open', 'onloadscript' );


/***************************************************************************************************************************************************************
 * Enqueue bootstrap scripts and styles
 * 
 */
function your_theme_enqueue_scripts() {
   // all styles
   wp_enqueue_style( 'bootstrap', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css', '4.0', false);
   //wp_enqueue_style( 'theme-style', plugin_dir_path( __FILE__ ); . '/css/style.css', array(), 20141119 );
   // all scripts
   wp_enqueue_script( 'bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', '4.0', false );
   //wp_enqueue_script( 'theme-script', plugin_dir_path( __FILE__ ); . '/js/scripts.js', array('jquery'), '20120206', true );
}
add_action( 'wp_enqueue_scripts', 'your_theme_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'your_theme_enqueue_scripts' ); // utilizzarlo solo per la pagina del plugin

/***************************************************************************************************************************************************************
 * SPARE FUNCTIONS
 ***************************************************************************************************************************************************************/

/***************************************************************************************************************************************************************
 * .......
 * 
 */
function alt_get_attached_media( $type, $post_id = 0 ) {
   $post = get_post( $post_id );
   if ( ! $post ) {
       return array();
   }

   $args = array(
       'post_parent'    => $post->ID,
       'post_type'      => 'attachment',
       'post_mime_type' => $type,
       'posts_per_page' => -1,
       'orderby'        => 'menu_order',
       'order'          => 'ASC',
   );

   /**
    * Filters arguments used to retrieve media attached to the given post.
    *
    * @since 3.6.0
    *
    * @param array   $args Post query arguments.
    * @param string  $type Mime type of the desired media.
    * @param WP_Post $post Post object.
    */
   $args = apply_filters( 'get_attached_media_args', $args, $type, $post );

   $children = get_children( $args );

   /**
    * Filters the list of media attached to the given post.
    *
    * @since 3.6.0
    *
    * @param WP_Post[] $children Array of media attached to the given post.
    * @param string    $type     Mime type of the media desired.
    * @param WP_Post   $post     Post object.
    */
   return (array) apply_filters( 'get_attached_media', $children, $type, $post );
}









