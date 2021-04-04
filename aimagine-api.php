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
 * *** WP Plugin panel menu
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
 * *** WP Menu Callback - Print the markup for the page 
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
   $images = array();
   foreach ( $query_images->posts as $image ) {
      $images[] = wp_get_attachment_url( $image->ID );
   }
      $data['images']=$images;
      Timber::render('twig/setup.html.twig', $data);
}

/***************************************************************************************************************************************************************
 * *** WP ACTION: Process file upload when post is ready
 *    Questa funzione è attivata dall'accesso alla pagina e trova le immagini contenute tramite le regex, trovando la sorgente è possibile caricare l'array di byte e mandarlo
 *    alle funzioni di elaborazione.
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
         else { 
            $content = $post->post_content;
            $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches); // cattura tutto il tag img
            $output = preg_match_all('/src=[\'"]([^\'"]+)*/i', $content, $matches);
            //$output = preg_match_all('/class=[\'"]([^\'"]+)*/i', $content, $matches);
            $c = count($matches[1]); // ##### Indice 1 per prelevare il nome
            for ($i=0; $i<$c; $i++) { // ***** E' il caso di fare un loop ?
               /*
               * Get image absolute path 
               */
               $img_path = $matches[1][$i];
               $site_path = ABSPATH;
               $filename = basename($img_path); // ***** Preleva solo il nome
               $split_path = parse_url($img_path); // ***** Splitta l'url in segmenti
               $abs_path = str_replace('\\','/', $site_path.substr($split_path['path'], strpos($split_path['path'], '/', 1)+1)); 
               // ***** Inserire il processo per ogni immagine 
            }
            /*
            * Get image absolute path 
            */
            $img_path = $matches[1][0]; // ***** Get the first image
            $site_path = ABSPATH;
            $filename = basename($img_path); // ***** Preleva solo il nome
            $split_path = parse_url($img_path); // ***** Splitta l'url in segmenti
            $abs_path = str_replace('\\','/', $site_path.substr($split_path['path'], strpos($split_path['path'], '/', 1)+1));
            //$results = detect_face_func($abs_path, NULL, Null);
            
            $res = CallAPI('127.0.0.1:8000/api/v1/blur', $abs_path /*, $image_data*/);
         }    
      }
   }
   else {
      // ***** Bha! Da capire
   }
}
add_action( 'template_redirect', 'custom_function' );


/***************************************************************************************************************************************************************
 * *** WP SHORTCODE: BLURFACE function ( call remote API )
 * 
 */
function detect_face_func( $path, $atts, $aimagine ) {
   
   $aimagine = ( $aimagine ) ? $aimagine : new Aimagine();
   //$path = $uploadfile; // *** Inserire upload
   /*
   $file_size = filesize ($path);
   $handle = fopen($path, "rb");
   $image_data = stream_get_contents($handle);
   fclose($handle);
   $uploadRequest = array(
      'file' => $image_data
   );
   */
   $res = CallAPI('127.0.0.1:8000/api/v1/blur', $path /*, $image_data*/);
      //$response = file_get_contents('http://127.0.0.1:8000/api/v1');
      $res = json_decode($res, true);
      $image_element = '<img src = "'.$path.'" /><br/>';
      //return $response;
   }
add_shortcode( "blurface", "detect_face_func" );


/***************************************************************************************************************************************************************
 * *** WP ACTION: Enqueue custom script when body is open ( is possible to change the hook )
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
//add_action( 'wp_body_open', 'onloadscript' );

/***************************************************************************************************************************************************************
 * *** WP ENQUEUE: bootstrap scripts and styles
 * 
 */
function your_theme_enqueue_scripts() {
     // jquery
   wp_deregister_script( 'jquery' );
   wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js');
   wp_enqueue_script( 'jquery' );
   // all styles
   wp_enqueue_style( 'bootstrapjs', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
   // all scripts
   wp_enqueue_script( 'bootstrapcss', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js' );
}
add_action( 'wp_enqueue_scripts', 'your_theme_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'your_theme_enqueue_scripts' ); // utilizzarlo solo per la pagina del plugin

/***************************************************************************************************************************************************************
 * *** WP DEQUEUE: jQuery (This function isn't called)
 * 
 */
function my_scripts_method() { wp_deregister_script( 'jquery' );}
//add_action('wp_enqueue_scripts', 'my_scripts_method');


/***************************************************************************************************************************************************************
 * 
 * SPARE FUNCTIONS
 * 
 ***************************************************************************************************************************************************************/


/***************************************************************************************************************************************************************
 * CURL API call function ( ***** Is better to use wp functions )
 * 
 */
function CallAPI($url,$path /*,$data_to_send*/)
{
   $file = new \CURLFile($path); 
   $data_to_send = ['file' => $file];
   //$data_to_send = array('file' => $file);
   $ch = curl_init();
   curl_setopt_array($ch, array(
      CURLOPT_URL => $url,
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS =>  $data_to_send,
      //CURLOPT_INFILE => $fp,
  ));
   //----Execute
   $result=curl_exec($ch);
   //----Closing
   curl_close($ch);
   //----Subtracts header size to get only body
   $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
   $result = substr($result, $header_size);
   //return $result;
}
