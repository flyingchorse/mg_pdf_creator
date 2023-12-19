<?php

 include('../inc/db.inc.php');
 include('../inc/functions.inc.php');

$doc_root = realpath(dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']).'/..') ;


 $prop_names = array('pdf_page_width', 
                'pdf_page_height',
                'pdf_use_cover_image',
                'pdf_cover_text',
                'pdf_cover_bg_color',
                'pdf_cover_text_color');

 $should_clear_cache = false;
 $pdf_settings = '';
 $cover_gallery = $doc_root . "/gallery/pdfcovers/";  
 $uploaded_file_name = $cover_gallery . "cover.jpg";
 $pdf_image_gallery = $doc_root . "/gallery/pdf_images/";
 $other_settings = get_settings();

 // $pdf_settings = {};


 $select_st = "SELECT * from PROPERTIES WHERE property_id like 'pdf_%' order by property_id ";
 $settings_result = mysql_query($select_st);
 while ($row = mysql_fetch_assoc($settings_result)){
   $pdf_settings[$row['property_id']]=$row['property_value']; 
 }

 if ($_POST){
   foreach (array('pdf_cover_page_bg_color','pdf_cover_page_text_color') as $prop){
     if (!preg_match("/^\#[0-9A-F]{6}/i",$_POST[$prop])) {
       if (preg_match('/^[0-9A-F]{6}/i',$_POST[$prop])){
         $_POST[$prop]='#'.$_POST[$prop];
       } else {
         $errors[$prop] = "Please enter the color in the format: #12ABC6 ";
       }
     }
   }

   // echo("<br/>Errors = " . explode("<br/>",$errors));
   foreach ($prop_names as $prop) {
        if (substr($prop,0,4) == 'pdf_'){
               // echo ("<br/>checking: " . $prop );

               $insert_value = mysql_real_escape_string($_POST[$prop]);

               if ($insert_value != $pdf_settings[$prop]){
                 $should_clear_cache = true;
               }

                $qry = sprintf("insert into properties (property_id,property_value) values ('%s', '%s') on duplicate key update property_value = '%s'", $prop, $insert_value, $insert_value);

 
		$result = mysql_query($qry);		 
                $rows_affected = mysql_affected_rows();
                // echo ("<br/>Rows Affected: {$rows_affected}");

        }
   }

   // File upload:
   
   if ($_FILES['cover_image']['error'] == 0 ){

     //echo("<br>Found cover file");

     //echo("<br/>cover_gallery = {$cover_gallery}");

     if (!file_exists($cover_gallery)){
       //echo("<br/>!file_exists: {$cover_gallery}, making a new one");
       mkdir($cover_gallery,0777);
     }
	if (!is_readable($cover_gallery))
	{
		header('HTTP/1.1 500 Internal Server Error');
		echo 'Error: the cache directory is not readable';
		exit();
	}

	else if (!is_writable($cover_gallery))
	{
		header('HTTP/1.1 500 Internal Server Error');
		echo 'Error: the cache directory is not writable';
		exit();
	}

     //echo("<br/>cover_image: {$_FILES['cover_image']}");
     //echo("<br/>file error: " . $_FILES['cover_image']['error']);
     if ($_FILES['cover_image']['error'] == UPLOAD_ERR_OK){
             // echo("<br/>UPLOAD_ERR_OK");

	     if (file_exists($cover_gallery . "cover.jpg")){
	       rename ($cover_gallery . "cover.jpg", $cover_gallery . "cover" . date("Ymdgis") . ".jpg");
	     }
	     if (file_exists($cover_gallery . "cover_thumb.jpg")){
	       rename ($cover_gallery . "cover_thumb.jpg", $cover_gallery . "cover_thumb" . date("Ymdgis") . ".jpg");
             }

             // echo("<br/>uploaded file name = " . $uploaded_file_name);

             if ( preg_match('/\.jpg$/i',$_FILES['cover_image']['name']))  {
               // echo("<br/>preg_matched");
               if (move_uploaded_file($_FILES['cover_image']['tmp_name'],$uploaded_file_name)){
                 //echo("<br/>moved uploaded file to $uploaded_file_name"); 
                 
                 $thumb = $cover_gallery."cover_thumb.jpg";
                 thumbnail($uploaded_file_name,$thumb,200);

               } else {
                 // echo("<br/>Problem moving the uploaded file!"); 
                 $errors['cover_file'] = "Sorry there was an error uploading you file. Please make sure it a .jpg file with the same dimensions as your pdf pages.";
               }
             } else {
               //echo("<br/>preg didn't match");
               //echo("<br/>  : {$_FILES['cover_image']['name']}");
             }
     } else {
       //echo("<br/> not upload err ok");
     }
   } else {
     //echo("<br/>File Upload error: {$_FILES['cover_image']['error']}");
   }
 }

 $select_st = "SELECT property_id, property_value from properties WHERE property_id like 'pdf_%' order by property_id ";
 $settings_result = mysql_query($select_st);
 while ($row = mysql_fetch_assoc($settings_result)){
   $pdf_settings[$row['property_id']]=$row['property_value']; 
 }

 if ($should_clear_cache){
   if (file_exists($pdf_image_gallery)){
     foreach (glob($pdf_image_gallery . "*.jpg") as $jpg){
       unlink($jpg);
     }
   }
 }

?>

<html>
 <head>
	<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAiBn3STZBqCouz6R6T3PkExRc-Y08szc8CsUewwfvHSLta8zsVBSlfNScgX-IgHecbhespHpYjM2fmQ"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" ></script>
	<script type="text/javascript" src="javascript/jpicker-1.1.5.min.js" ></script>
	<link type="text/css" href="css/jPicker-1.1.5.min.css" rel="stylesheet" media="screen" ></link>
	<link type="text/css" href="css/base.css" rel="stylesheet" media="screen" ></link>
        <script type="text/javascript" >

           $(document).ready(
             function(){

               $.fn.jPicker.defaults.images.clientPath='images/';
               $('.color_picker').jPicker();
             }
           );
        </script>
 </head>
 <body>
<div id="title_contain">
  <div id="title">
    <h1>
  Custom PDF Portfolio Configuration Options
    </h1>
  </div>
</div>
<div id="container">
<div id="side">
</div>
<div id="main">
  <div class="tip_message">
    <h2>
    You can personalize your custom PDF portfolios with the following options:
    </h2>
  </div>
<div id="editPane" >    

<form action="<?= $PHP_SELF ?>" method="POST" enctype="multipart/form-data" >
<fieldset> 
  <legend>PDF Page size</legend> 
    <div class="tip_message">
      Choose the most common dimensions for your images. All pages will have the same dimensions. Any image that does not fit the page size will be shrunk to fit.
    </div>

    <ul> 
      <li>
        <label for="pdf_page_width">Page Width in pixels</label>
        <input class="i-text" name="pdf_page_width" type="text" id="width" value="<?= empty($pdf_settings['pdf_page_width']) ? '1200' : $pdf_settings['pdf_page_width']  ?>" size='5' /> 
      </li>
      <li>
        <label for="pdf_page_height">Page Height in pixels</label>
        <input class="i-text" name="pdf_page_height" type="text" id="width" value="<?= empty($pdf_settings['pdf_page_height']) ? '800' : $pdf_settings['pdf_page_height']   ?>" size='5' />
      </li> 
    </ul>
</fieldset>
<fieldset>
  <legend>
    Cover page
  </legend>
  <div class="tip_message">
    Your generated PDF's will include a cover page. You may choose to upload a special cover image or have us generate a cover page with a solid cover and the name of your site.<br/>
    If you upload your own image, please make sure it matches your chosen page size, and that it is in the .jpg format.
  </div>
  <ul>
    <li>
      <label for="pdf_use_cover_image">Use uploaded cover page?
      <input class="i-text" name="pdf_use_cover_image" type="radio" value="yes" <?= $pdf_settings['pdf_use_cover_image']=='yes' ? 'checked' : '' ?> />Yes<br/>
      <input class="i-text" name="pdf_use_cover_image" type="radio" value="no" <?= $pdf_settings['pdf_use_cover_image']!='yes' ? 'checked' : '' ?> />No, generate one with my site name<br/>
    </li>

    <?php
        if (file_exists($uploaded_file_name)) {
    ?>
    <li>
      <label>Your current cover page image (if you choose to use it):</label>
      <img src="../gallery/pdfcovers/cover_thumb.jpg"  />
    </li>
    <?php 
        } 
    ?>

    <li>
      <label for="upload_cover_image">Upload a custom cover image. Make sure it matches your cover page dimensions.</label>
<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
      <input class="i-text" name="cover_image" type="file" />
    </li>

    <li>
      <label for="pdf_cover_bg_color" >Background color of cover page</label>
       <input class="color_picker i-text" name="pdf_cover_bg_color" type="text" id="pdf_cover_bg_color" value="<?= $pdf_settings['pdf_cover_bg_color'] ? $pdf_settings['pdf_cover_bg_color'] : '#32465A' ?>" size='7' />
    </li>

    <li>
      <label for="pdf_cover_text_color" >Text color of cover page</label>
       <input class="color_picker i-text" name="pdf_cover_text_color" type="text" id="pdf_cover_text_color" value="<?= $pdf_settings['pdf_cover_text_color'] ? $pdf_settings['pdf_cover_text_color'] : '#C8C8D2' ?>" size='7' />
    </li>

    <li>
      <label for="pdf_cover_text" >Text of cover page</label>
       <input class="i-text" name="pdf_cover_text" type="text" id="pdf_cover_text" value="<?= empty($pdf_settings['pdf_cover_text']) ? $other_settings['site_title'] : $pdf_settings['pdf_cover_text'] ?>"  />
    </li>
  </ul>
</fieldset> 

<fieldset>
  <ul>
    <li>
       <input name="submit" value="submit" type="submit" />
    </li>
  </ul>
</fieldset>
</form> 
</div><!-- edit pane -->
</div><!-- main -->
</div><!--container-->
 </body>
</html>


