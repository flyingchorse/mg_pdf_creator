<?php
/**
 * Plugin Name: PDF_Portfolio_builder
 * Plugin URI: http://digidol-media.com
 * Description: A brief description of the plugin.
 * Version: The plugin's version number. Example: 1.0.0
 * Author: Name of the plugin author
 * Author URI: http://URI_Of_The_Plugin_Author
 * Text Domain: Optional. Plugin's text domain for localization. Example: mytextdomain
 * Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 * License: A short license name. Example: GPL2
 */
 


 
require('fpdf.php');
//require('fpdf2file.php');


function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
    //echo("hexStr = $hexStr");
    $rgbArray = array();
    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
        //echo("<br/> was 6 digits");
        $colorVal = hexdec($hexStr);
        $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
        $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
        $rgbArray['blue'] = 0xFF & $colorVal;
    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
        //echo("<br/>shorthand");
        $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
        $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
        $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    } else {
        //echo("was invalid hex color code");
        return false; //Invalid hex color code
    }
    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
}

/**
 * Require functions.inc.php for "get_settings"
 * used for title, colors, etc.
 * 
 * Any new user-defined variables should be added 
 * to "properties" table since it's more dynamic
 *
 **/
add_action('admin_post_submit-form', 'create_digidol_pdf_callback'); // If the user is logged in
add_action('admin_post_nopriv_submit-form', 'create_digidol_pdf_callback');
 
function create_digidol_pdf_callback(){

$settings = "";
$pdf_settings =  array();
$select_st = "";



// set defaults
if (empty($pdf_settings['pdf_page_width'])){
  $pdf_settings['pdf_page_width'] = 1000;
}

if (empty($pdf_settings['pdf_page_height'])){
  $pdf_settings['pdf_page_height'] = 660;
}

if (empty($pdf_settings['pdf_cover_bg_color'])){
  $pdf_settings['pdf_cover_bg_color'] = '#000000';
  //echo("<br/>pdf_settings['pdf_cover_bg_color'] = {$pdf_settings['pdf_cover_bg_color']}");
}

if (empty($pdf_settings['pdf_cover_text_color'])){
  $pdf_settings['pdf_cover_text_color'] = '#C8C8D2';
  //echo("<br/>pdf_settings['pdf_cover_text_color'] = {$pdf_settings['pdf_cover_text_color']}");
}

if (empty($pdf_settings['pdf_cover_text'])){
  $pdf_settings['pdf_cover_text'] = 'MARK GEORGE';
  //echo("<br/>pdf_settings['pdf_cover_text_color'] = {$pdf_settings['pdf_cover_text_color']}");
}
// $doc_root = $_SERVER['DOCUMENT_ROOT'];
$doc_root = realpath(dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']).'/..') ;


/**
 * Directory in which we'll save all size and watermarked images.
 * Even if we don't use watermarks.
 **/

$watermarkedDirectory = $doc_root . "/pdfcover/";

/**
 * If we should want to watermark the images later, we'll use this
 **/
$should_watermark = false;

/**
 * Width/Height of images
 **/
define("MAX_WIDTH",empty($pdf_settings['pdf_page_width']) ? 1200 : $pdf_settings['pdf_page_width']) ;
define("MAX_HEIGHT",empty($pdf_settings['pdf_page_height']) ? 800 : $pdf_settings['pdf_page_height']) ;
define("DEST_RATIO",MAX_WIDTH/MAX_HEIGHT);

/**
 * Again, only used if we start watermarking
 **/
define("WATERMARK_PADDING",10);
define("WATERMARK_IMG_PATH", $doc_root . '/images/watermark3.png');

/**
 * Sanity check
 **/
if (!file_exists($watermarkedDirectory)){
  mkdir($watermarkedDirectory,0755);
}

if (!is_readable($watermarkedDirectory))
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Error: the cache directory is not readable';
	exit();
}

else if (!is_writable($watermarkedDirectory))
{
	header('HTTP/1.1 500 Internal Server Error');
	echo 'Error: the cache directory is not writable';
	exit();
}


function findSharp($orig, $final) // function from Ryan Rud (http://adryrun.com)
{
	$final	= $final * (750.0 / $orig);
	$a		= 52;
	$b		= -0.27810650887573124;
	$c		= .00047337278106508946;
	$result = $a + $b * $final + $c * $final * $final;
	return max(round($result), 0);
} // findSharp()

function imageCreateFromAny($filepath) { 
    $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize() 
    $allowedTypes = array( 
        1,  // [] gif 
        2,  // [] jpg 
        3,  // [] png 
        6   // [] bmp 
    ); 
    if (!in_array($type, $allowedTypes)) { 
        return false; 
    } 
    switch ($type) { 
        case 1 : 
            $im = imageCreateFromGif($filepath); 
        break; 
        case 2 : 
            $im = imageCreateFromJpeg($filepath); 
        break; 
        case 3 : 
            $im = imageCreateFromPng($filepath); 
        break; 
        case 6 : 
            $im = imageCreateFromBmp($filepath); 
        break; 
    }    
    return $im;  
} 

/**
 * Poorly named: Sizes original image to proper size
 * and adds watermark if needed.
 **/
function createWatermarkedImage($imageName){
  global $doc_root, $watermarkedDirectory;

  $originalImagePath = "$doc_root/gallery/original/$imageName"; 
  $image = imagecreatefromjpeg($originalImagePath);
  list($source_w,$source_h) = getimagesize($originalImagePath);
  $source_ratio = $source_w/$source_h;
  $useable_ratio = ($source_ratio >= DEST_RATIO) ? (MAX_WIDTH/$source_w)  : (MAX_HEIGHT/$source_h); 
  $dest_height = round($source_h * $useable_ratio);
  $dest_width = round($source_w * $useable_ratio);
  $destimg = imagecreatetruecolor($dest_width,$dest_height);
  ImageCopyResampled($destimg,$image,0,0,0,0,$dest_width,$dest_height,$source_w,$source_h);

  if ($should_watermark){
    $watermark = imagecreatefrompng(WATERMARK_IMG_PATH);
    $watermarkInfo = getimagesize(WATERMARK_IMG_PATH);
    imagealphablending($destimg,true);
    $wm_w_start = $dest_width - ($watermarkInfo[0] + WATERMARK_PADDING );
    $wm_h_start = $dest_height - ($watermarkInfo[1] + WATERMARK_PADDING ); 
    imagecopy($destimg,$watermark, $wm_w_start, $wm_h_start, 0, 0, $watermarkInfo[0], $watermarkInfo[1]);
  }

  // sharpenImage($destimg, $source_width, $dest_width);  
  $w_img_path = watermarkedImagePath($imageName);
  unset($image);
  ImageJpeg($destimg,$w_img_path,80);
  return $destimg;
}

function sharpenImage($destimg,$source_width, $dest_width){
  	$sharpness	= findSharp($source_width, $dest_width);
	$sharpenMatrix	= array(
		array(-1, -2, -1),
		array(-2, $sharpness + 12, -2),
		array(-1, -2, -1)
	);
	$divisor		= $sharpness;
	$offset			= 0;
	imageconvolution($destimg, $sharpenMatrix, $divisor, $offset);
}

function watermarkedImagePath($imageName){
  global $watermarkedDirectory;
  return $watermarkedDirectory . $imageName;
}

function getWatermarkedImage($imageName){
  $imagePath = watermarkedImagePath($imageName);
  $image = null;
  if (!file_exists($imagePath)){
    $image = createWatermarkedImage($imageName);
  } else {
    $image = imagecreatefromjpeg($imagePath);
  } 
  return $image;
}


/**
 * Begin main function
 *
 **/
if ($_POST){

  /**
   * Get list of images requested
   **/
  $galimgs = $_POST['img'];
  $photographer = $_POST['photographer'];
  

  /**
   *  Code to sent email alert to client of pdf download
   * 
   *  save for later use
   **/
  //$mailmsg = '<html><head><title>PDF Download</title></head><body>There was a PDF request from ' .  $_SERVER['REMOTE_ADDR'];
  //$mailmsg .= ' at host: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']) . '.<br/>';
  //$mailmsg .= 'They requested ' . count($galimgs) . ' images: <br/><br/>';

  $pdf = new FPDF('p','pt',array(MAX_WIDTH,MAX_HEIGHT));
  //$pdf = new FPDF2File('p','pt',array(MAX_WIDTH,MAX_HEIGHT));

  //$tmpname = $watermarkedDirectory . uniqid('pdfportfolio'. $_SERVER['REMOTE_ADDR']) . '.pdf';
  //$tmpname = $watermarkedDirectory . 'doc.pdf';
  //echo("tmpname = $tmpname");
  //$pdf->Open($tmpname);


  $pdf->SetTitle('Mark George'); 
  $pdf->SetMargins(0,0,0);

  /**
   * Use uploaded cover page, or make one with "browser title" and link to home page
   **/
  if (file_exists("$doc_root/gallery/pdfcovers/cover.jpg") && 
      ($pdf_settings['pdf_use_cover_image'] == 'yes') ){
    /**
     *  By rights, this call to AddPage should be above the "file exists"
     *  but it added a blank white page to the pdf when no cover image was present
     *  and I couldn't figure out how to fill the page with color from the beginning...
     * 
     **/
    $pdf->AddPage('P',array(MAX_WIDTH,MAX_HEIGHT));
    $pdf->Image("$doc_root/gallery/pdfcovers/cover.jpg",0,0,MAX_WIDTH,MAX_HEIGHT,'jpg');
  } else {
    $pdf->AddPage('P',array(MAX_WIDTH,MAX_HEIGHT));
       

    /** 
     *
     * colors for cover page: light gray/faint blue for text,
     * masculine, steel blue for background.
     *
     * We might want to pull colors from the site settings.
     *
     **/
    
    $textcolor = hex2RGB($pdf_settings['pdf_cover_text_color']);
    //echo("<br/>pdf_cover_text_color = {$pdf_settings['pdf_cover_text_color']}");
    //echo("<br/>textcolor: {$textcolor['red']},{$textcolor['green']},{$textcolor['blue']}");

    $pdf->SetTextColor($textcolor['red'],$textcolor['green'],$textcolor['blue']); 
    $bgcolor = hex2RGB($pdf_settings['pdf_cover_bg_color']);
    $pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
$pdf->Rect(0,0,MAX_WIDTH,MAX_HEIGHT,F);
    $pdf->SetXY(0,200);

    /**
     * Fill page with FillColor and print title in center:
     **/

    //echo("cover_text: {$pdf_settings['pdf_cover_text']}");
    $fontsize = 58;
    $stringwidth = 100000;
    do { 
      $fontsize = $fontsize - 2;
      $pdf->AddFont('baskerville');
      $pdf->SetFont('Baskerville');
      $pdf->SetFontSize(58);
      $stringwidth = $pdf->GetStringWidth($pdf_settings['pdf_cover_text']);

    } while($stringwidth >= MAX_WIDTH || $fontsize == 12);

	$pdf->SetLeftMargin(100);
    $pdf->Cell(0,0,$pdf_settings['pdf_cover_text'],0,1,'L',false);
    $pdf->SetFontSize(22);
	$pdf->Ln(15);
    $pdf->Cell(0,60,'represents ' . $photographer,0,0,'L',false);
    $pdf->Ln(30);
    $pdf->SetFontSize(14);
    $pdf->Cell(0,60,'mg@markgeorge.com',0,0,'L',false);
    $pdf->Ln(30	);
    $pdf->Cell(0,60,'Phone +44(0) 20 8877 9922',0,0,'L',false);

    /**
     * Link the cover page text to site home page
     **/
    /*
$pdf->Link(round(MAX_WIDTH/2-$stringwidth/2), 
               round(MAX_HEIGHT/2 - $fontsize/2) , 
               $stringwidth, 
               $fontsize, "http://".$_SERVER['SERVER_NAME']); 
*/
  }


  foreach ($galimgs as $galimg){
  
      
      $image_name = wp_get_attachment_image_src($galimg, 'full');
      
      settype($image_url, "string"); 
      $image_url = $image_name[0];
      $image = imageCreateFromAny($image_url);

      $height = imagesy($image);
      $width = imagesx($image);
      $pdf->AddPage('P',array($width/2,$height/2));
      $startx = round((MAX_WIDTH - $width) / 2);
      $starty = round((MAX_HEIGHT - $height) / 2);
      //$wmImagePath =watermarkedImagePath($image_name);       

//      $mailmsg .= "<img src='http://www.timothydevine.com/gallery/small/" . $image_name . "'  /> <br/>\n";
//      $mailmsg .= "<hr/>";
      
       $pdf->Image($image_url,0,0,$width/2,$height/2,'jpg');
      // $pdf->Cell(0,50,$width/2 . '-----' . $height/2 ,0,1,'L',false);
      //$pdf->Image($wmImagePath,$startx,$starty,$width,$height,'jpg');

    
  }      
  /**
  $headers = "MIME-Version: 1.0" . "\r\n"; 
  $headers .= 'Content-type: text/html; charset=iso8859-1' . "\r\n";
  $headers .= 'From: Timothy Devine.com <tim@timothydevine.com>' . "\r\n";
  $headers .= 'Bcc: johnjdevine@yahoo.com' . "\r\n";
  mail("tim@timothydevine.com", "PDF Porfolio Created", $mailmsg, $headers);
  **/
  
  /**
   * Name the file with the "site_title" setting with no spaces.
   **/
   $settings['site_title'] = 'Mark George';
  $pdf->Output( preg_replace("/\s/", "", $settings['site_title']) . "WebsitePortfolio.pdf",  "I");
  //$pdf->Output(); 
  //unlink($tmpname);
} 


}


?>
