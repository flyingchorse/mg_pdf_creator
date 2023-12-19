<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">

<?
include('../inc/db.inc.php');
include('../inc/functions.inc.php');
$settings = get_settings();
?>
<html>
<head>
<title><?= $settings['browser_title'] ?>: Create a PDF Portfolio</title> 
<script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAiBn3STZBqCouz6R6T3PkExRc-Y08szc8CsUewwfvHSLta8zsVBSlfNScgX-IgHecbhespHpYjM2fmQ"></script>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" ></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js" ></script>

<script type="text/javascript">
(function($) {
    $.extend({
        doGet: function(url, params) {
            document.location = url + '?' + $.param(params);
        },
        doPost: function(url, params) {
            var $div = $("<div>").css("display", "none");
            var $form = $("<form method='POST'>").attr("action", url);
            $.each(params, function(name, value) {
                $("<input type='hidden'>")
                    .attr("name", name)
                    .attr("value", value)
                    .appendTo($form);
            });
            $form.appendTo($div);
            $div.appendTo("body");
            $form.submit();
        }
    });
})(jQuery);

  var imgs=[];
  function setOpacity(elem){
    var positionInArray = $.inArray(elem.id,imgs); 
    opac = (positionInArray > -1) ? 0.6 : 1.0;
    $(elem).css('opacity',opac);
    if (imgs.length>0){
      $("#downloadlink").removeClass("downloadoff downloadhover").addClass("downloadon");
    } else {
      $("#downloadlink").removeClass("downloadon downloadhover").addClass("downloadoff");
    }
  } 

  function toggleElem(elem){
    var elemId = elem.id;
    var inImgs = $.inArray(elemId,imgs);
    if (inImgs > -1){
      imgs.splice(inImgs,1);
      $("ul#portfolio_ul #portfolio_"+elemId).remove();
      $(elem).siblings("img.check").hide();
    } else {
      imgs.push(elemId);
      $("ul#portfolio_ul").append("<li id='portfolio_"+elemId+"' >" + 
         "<img src='" + elem.src.replace('/small/','/thumb/') + "' /><a href='#' class='remove_from_portfolio'>[x]</a>" + 
         "<input type='hidden' name='img[]' value='" + elemId + "' />" + 
         "</li>");
      $(elem).siblings("img.check").show();
    }
    setOpacity(elem);
  }

  $(document).ready(function(){

    $("#portfolio_ul").sortable({
      placeholder: 'ui-state-highlight',
      handle: 'img',
      opacity: 0.7
    });

    $('#portfolio_ul li a.remove_from_portfolio').live('click', function(){
      var li = this.parentNode;
      var thumbId = li.id.substring(10);
      toggleElem($('#' + thumbId)[0]);
      return false;
    });


    $("ul.thumb_ul img.thumb").each(function(){
      $(this).
//      hover(
//        function(){
//          $(this).stop().animate({opacity: 1.0},100);
//        },
//        function(){
//          $(this).stop();
//          setOpacity(this);
//        }
//      )
      click(function(){
        toggleElem(this);
      });
    });

    $("a#downloadlink").hover(function(){
        if (imgs.length > 0){
          $("a#downloadlink").removeClass("downloadon downloadoff").addClass("downloadhover"); 
        } 
      },
      function(){
        if (imgs.length > 0){
          $("a#downloadlink").removeClass("downloadhover").addClass("downloadon"); 
        }
      } 
    ).click(function(evt){
      if (imgs.length>0){
        $("form#pdfform").submit();
      } 
      evt.preventDefault();
      return false; 
    });

  });

</script>

<style type="text/css">
<? $text_color = "#797979" ?>

//@font-face {
//  font-family: Knockout;
//  src: url(/images/Knockout-50.otf)  format("opentype");  
//}

//@font-face {
//  font-family: KnockoutEOT;
//  src: url(/images/Knockout-50Welterweight.eot)  format("embedded-opentype");  
//}

body {
  //font-family: "Knockout", "KnockoutEOT", Veranda, Arial;
  font-family: Veranda, Arial;
  font-size: 14pt;
  color: <?= $text_color ?>;
}


div#left {
  position: absolute;
  margin-left: 0;
  margin-right: 0;
  margin-top: 0;
  top: 40px;
  left: 40px;
  width: 200px;
}

div#left h4{
  margin-bottom: 10px;
  line-height: 1.1em;
}

div#portfolio {
  //border: 1px solid black;
  width: 120px;
  margin-top: 20px;
}

div#portfolio ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

div#portfolio li {
  margin-left 0;
  margin-top 10px;
  position: relative;;
}

div#portfolio li a.remove_from_portfolio{
  position: absolute;
  top: 0;
  right: 0;
  font-size: 11px;
  color: red;
  text-decoration: none
}

div#right {
  margin-left: 260px; 
  margin-right: 30px;
  margin-top: 40px;
  margin-bottom: 40px;
  padding: 0;
}

div#right ul {
  list-style-type: none;
  margin: 0;
  padding: 0 10px 10px 10px;
} 

div#right li {
  float: left;
  width: 306px;
  height:206px;
  margin: 0;
  padding: 3;
  position: relative;
  overflow:hidden;
  text-align: center;
}

div#right li img.check{
  position: absolute;
  z-index: -10;
  display: none;
  left: 20px;
  top: 0;
}

div#right li img.thumb {
  cursor: pointer;
 // opacity: 0.4;
 // filter: alpha(opacity=40);
}
.ui-state-highlight { 
  //height: 200px;
  //width: 300px;

  height: 80px; 
  width: 100px;
  background-color: #DDD;
}

div.gallery h5 {
  //color: #900;
  color: <?= $text_color ?>;
  font-size: 13pt;
  font-weight: normal;
  text-transform: uppercase;
  padding: 3px;
  margin: 0 10px 0 5px;
  line-height: 1em;
}

div.gallery {
  margin: 10px;
  float: left;
}

div.gallery hr {
  margin-top: 0;
  margin-left: 8px; 
  margin-right: 30px;
  //color: #995555;
  //color: #db7c7c;
  color: <?= $text_color ?>;
  //border: thin solid #db7c7c;
  border: 0 none;
  //border-top: thin solid #db7c7c;
  border-top: thin solid <?= $text_color ?>;
  height: 1px;
}

div.gallery .galcontainer {
}

div#left a#logolink {
  color: inherit;
  text-decoration: none;
}

div#left a#logolink img {
  border: none;
}

div#left a#texthomepagelink {
  font-size: 16pt;
  color: <?= $text_color ?>;
  text-decoration: none;
}
div#left a#texthomepagelink:visited, div#left a#texthomepagelink:hover {
  color: <?= $text_color ?>;
  text-decoration: none;
}



.instructions {
  font-size: 11pt;
  line-height: 1.2em;
  margin-bottom: 10px;
}

form#pdfform a#downloadlink {
  //font-weight: bold;
  margin-bottom: 10px;
  padding-left: 25px;
  font-size: 12pt;
  line-height: 22px;
  display: block;
  text-decoration: none;
}

.download_copyright {
  font-size: 9pt;
  line-height: 1.2em;
  margin-bottom: 10px;
}

.downloadon {
  color: <?= $text_color ?> ;
  background: transparent url(images/download.png) no-repeat left bottom;
}

.downloadoff {
  color: #EEEEEE;
  background: transparent url(images/downloadoff.png) no-repeat left bottom;
}

.downloadhover {
  color: #FFCC00;
  background: transparent url(images/downloadhover.png) no-repeat left bottom;
}

</style>
</head>
<body>
<div id="left">
	<? if ($settings['title_mode'] == 'LOGO') {  ?>
	       <a href="/" id="logolink"><img src="../images/<?= $settings['logo_file'] ?>" title="<?= $settings['site_title'] ?>" alt="<?= $settings['site_title'] ?>" /></a>
	       <?   } else { ?>
	       <a href="/" id="texthomepagelink"><?= $settings['site_title'] ?></a>
	<?   }  ?>

         <!--
             <a href="/" id="logolink"><img src="images/logo200w72dpi.jpg" width="200px" height="235px" alt="Timothy Devine Photography" /></a>
         -->

		<h4>Create Your Own<br/>PDF Portfolio</h4>
                <div class="instructions">Select as many images as you wish by 
                     clicking the thumbnails.<br/>
                     Click and drag your selections below to reorder.
                </div>                  
               
                <form action="createpdf.php" method="post" id="pdfform">
                <a href="#" id="downloadlink" class="downloadoff" >Download</a>
                <div class="download_copyright">All Images Copyright of <?= $settings['owner_name'] ?></div>
                <!--
                <input type="submit" value="create" disabled="disabled"></input>
                -->
		<div id="portfolio">
			<ul id="portfolio_ul">
			</ul>
		</div>
                
                </form>
                

	</div>
	<div id="right">
	<? 
		$im = array();
		$cats = get_categories();
		foreach ($cats as $c) {
			$gals = get_galleries_by_category($c['category_id']);
			foreach ($gals as $g) {
                                if ($g['gallery_hidden'] || !empty($g['password']) ){
                                  continue;
                                }
				$images = get_gallery_files($g['gallery_id']);
				echo("<div class='gallery'>");
				echo("<h5>{$g['gallery_name']}</h5>");
                                echo("<hr/>");
                                echo("<div class='galcontainer'>");
				echo("  <ul class='thumb_ul'>");
				foreach ($images as $image) {
                                          $galId= "g" . $image['gallery_id']."i".$image["image_id"];
					  echo("<li>");
					  echo("<img class='thumb' src='../gallery/small/" . $image['image_file'] . 
						 "' border='0' id='{$galId}' />");
                                          echo("<img src='images/check300.png' class='check'/>");
					  echo("</li>");
					  //echo("<li>" . print_r($image) . "</li>");
				}
				echo("  </ul>");
                           
				echo("</div></div>");
			}
		}
	?> 
	</div>

</body>
