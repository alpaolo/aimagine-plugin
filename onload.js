jQuery(document).ready(function($) {
  img_el = document.getElementById('test_img')
  im_elem = $('#test_img').find('img');
  im_elem.attr('src', "");
  console.log (im_elem.attr('src'));
 });


