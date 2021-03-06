<?php
include("config.php");
include("function.php");

if($_GET['file']){
	$getfile = decode_url($_GET['file']);
	$base_file = $base_dir.$getfile;
	dir_check($getfile);
} else {
	echo "정보가 없습니다.";
	die(header("Location: ./"));
}

$base_title = explode("/", $base_file);
$title = $base_title[(count($base_title)-1)];
$base_folder = str_replace($title, "", $base_file);
$link_dir = str_replace("/".$title, "", $getfile);


if(strpos(strtolower($base_file), "zip") !== false || strpos(strtolower($base_file), "cbz") !== false) {
	$type = "zip";
} else {
	$type = "images";
}

$bookmark_arr = array();
$bookmark = 0;
if(is_file($bookmark_file) === true){
	$bookmark_arr = json_decode(file_get_contents($bookmark_file), true);
	if($bookmark_arr[$getfile]) {
		$bookmark = $bookmark_arr[$getfile];
		if(is_array($bookmark) == true){
			$bookmark = $bookmark['bookmark'];
		}
	}
}
$page = ceil(($now+1)/$maxview)-1;  //현재페이지

						if(strpos(strtolower($base_file), ".zip") !== false){
							$json_file = substr($base_file, 0, strpos(strtolower($base_file), ".zip")).".json";
						} elseif(strpos(strtolower($base_file), ".cbz") !== false){
							$json_file = substr($base_file, 0, strpos(strtolower($base_file), ".cbz")).".json";
						} elseif($_GET['filetype'] == "images") {
							$json_file = $base_file."/image_files.json";								
						}
						
						if(is_file($json_file) === true){
							$pageorder = json_decode(file_get_contents($json_file), true);
							if($_GET['pageorder'] !== null){
								$newpageorder = $_GET['pageorder'];
								$pageorder['page_order'] = (string)$newpageorder;
								$json_output = json_encode($pageorder, JSON_UNESCAPED_UNICODE);
								file_put_contents($json_file, $json_output);
							}
							if($_GET['mode'] == "toon"){
								if($pageorder['viewer'] !== "toon" || $pageorder['viewer'] == null){
									$pageorder['viewer'] = "toon";
									$json_output = json_encode($pageorder, JSON_UNESCAPED_UNICODE);
									file_put_contents($json_file, $json_output);
								}
								$mode = $_GET['mode'];
							} elseif ($_GET['mode'] == "book"){
								if($pageorder['viewer'] !== "book" || $pageorder['viewer'] == null){
									$pageorder['viewer'] = "book";
									$json_output = json_encode($pageorder, JSON_UNESCAPED_UNICODE);
									file_put_contents($json_file, $json_output);
								}
								$mode = $_GET['mode'];
							} elseif ($_GET['mode'] == null) {
								if($pageorder['viewer'] !== null){
									$mode = $pageorder['viewer'];
								} else {
									$pageorder['viewer'] = "toon";
									$json_output = json_encode($json_data, JSON_UNESCAPED_UNICODE);
									file_put_contents($json_file, $json_output);
									$mode = $pageorder['viewer'];
								}
							}
						} elseif (is_file($json_file) === false) {
							if(strpos(strtolower($base_file), ".zip") !== false || strpos(strtolower($base_file), ".cbz") !== false){
								$zip = new ZipArchive;
								if ($zip->open($base_file) == TRUE) {
									$thumbnail_index = 0;
									for ($findthumb = 0; $findthumb < $zip->numFiles; $findthumb++) {
										$find_img = $zip->getNameIndex($findthumb);
										if(!strpos(strtolower($find_img), ".jpg") && !strpos(strtolower($find_img), ".jpeg") && !strpos(strtolower($find_img), ".png")){
											continue;
										} elseif (strpos(strtolower($find_img), ".jpg") !== false || strpos(strtolower($find_img), ".jpeg") !== false || strpos(strtolower($find_img), ".png") !== false) {
											$thumbnail_index = $findthumb;
											break;
										}
									}						
									
									$size = getimagesizefromstring($zip->getFromIndex($thumbnail_index));
									if($size[0] > $size[1]) {
										$x_point = ($size[0]/2) - $size[1];
										$originimage = imagecreatefromstring($zip->getFromIndex($thumbnail_index));
											if($x_point > 0){
												$cropimage = imagecrop($originimage, ['x' => $x_point, 'y' => 0, 'width' => $size[1], 'height' => $size[1]]);
											} else {
												$cropimage = imagecrop($originimage, ['x' => 0, 'y' => 0, 'width' => $size[1], 'height' => $size[1]]);
											}
										$originimage = $cropimage;
										$cropimage = imagecreatetruecolor(400, 400);
										imagecopyresampled($cropimage, $originimage, 0, 0, 0, 0, 400, 400, $size[1], $size[1]);
										imagedestroy($originimage);
										ob_start();
										imagejpeg($cropimage, null, 75 );
										imagedestroy($cropimage);
										$cropimage = ob_get_contents();
										ob_end_clean();

									} else {
										$originimage = imagecreatefromstring($zip->getFromIndex($thumbnail_index));
										$y_point = ($size[1] - $size[0])/2;
										$cropimage = imagecrop($originimage, ['x' => 0, 'y' => 0, 'width' => $size[0], 'height' => $size[0]]);
										$originimage = $cropimage;
										$cropimage = imagecreatetruecolor(400, 400);
										imagecopyresampled($cropimage, $originimage, 0, 0, 0, 0, 400, 400, $size[0], $size[0]);
										imagedestroy($originimage);
										ob_start();
										imagejpeg($cropimage, null, 75 );
										imagedestroy($cropimage);
										$cropimage = ob_get_contents();
										ob_end_clean();
									}
								}

								$pageorder = array();
								$pageorder['totalpage'] = $zip->numFiles;
								$pageorder['page_order'] = "0";
								$pageorder['viewer'] = "toon";
								$pageorder['thumbnail'] = base64_encode($cropimage);
								$json_output = json_encode($pageorder, JSON_UNESCAPED_UNICODE);
								file_put_contents($json_file, $json_output);
								$mode = $pageorder['viewer'];
							}
						}
							
?>
<!DOCTYPE html>
<html lang="ko">
   <head>
      <title>myComix - <?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css2?family=Gugi&family=Nanum+Gothic:wght@400;700&display=swap" rel="stylesheet">
	<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script src="https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>

<?php
if($mode == "book") {
?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.8.2/css/lightgallery.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.8.2/js/lightgallery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/lg-fullscreen/dist/lg-fullscreen.min.js"></script>
<?php
}
?>
	<style type="text/css">
		body {
			font-family: 'Nanum Gothic', sans-serif;
			font-size: smaller;
		}
		a:link {text-decoration: none;}
		a:visited {text-decoration: none;}
		a:active {text-decoration: none;}
		a:hover {text-decoration: none;}
		img.lg-image {
			margin: 0;
			padding: 0;
			min-height:100%;
			min-width:100%;
			object-fit:contain;
		}
		.lg-outer .lg-img-wrap {
			position: absolute;
			padding: 0 0px;
			padding-top: 0px;
			padding-right: 0px;
			padding-bottom: 0px;
			padding-left: 0px;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
		}
	</style>
   </head>
<script type="text/javascript">
var scroll_top = 0;
var bright_counter = 0;
<?php
if($mode == "toon"){
?>
					$(document).ready(function(){
						if ($('.navbar').length > 0) {
							 var last_scroll_top = 0;
							$(window).on('scroll', function() {
								scroll_top = $(this).scrollTop();
								if(scroll_top < last_scroll_top) {
									$('.navbar').fadeIn();
								}
								else {
									$('.navbar').fadeOut();
									$('.collapse').fadeOut();
									document.getElementById("info").value = "";
								}
								last_scroll_top = scroll_top;
							});
						}
					});
					function hidenav() {
						$('.navbar').fadeToggle();
						$('.collapse').fadeOut();
					};
<?php
} elseif($mode == "book"){
?>
					$(document).ready(function(){
						run_gallery();
					});
					function run_gallery() {
						$('#lightgallery').lightGallery({
							selector: '.lg-item',
							loop: false,
							hideBarsDelay: 1000,
							controls: false,
							preload:5,
							download: false,
							useLeft: true,
						});
					};
<?php
}
?>
					function sub_toggle() {
						$('.collapse').fadeToggle();
						document.getElementById("info").value = "";
					};
</script>
<body>
<nav class="navbar navbar-light fixed-top bg-white p-1 m-0">
<table class="table table-borderless mb-2 p-0" width=100%>
<tr>
<td class="m-0 p-0 align-middle">
<a OnClick="location.href='./index.php?dir=<?php echo encode_url($link_dir); ?>&page=<?php echo $page; ?>'"><font style="font-family: 'Gugi'; font-size: 2em;">마이코믹스</font></a>
</td>
<td class="m-0 p-0 align-middle" align="right">
<button class="btn btn-sm" onclick="sub_toggle();">
<svg width="2em" height="2em" viewBox="0 0 16 16" class="bi bi-gear" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M8.837 1.626c-.246-.835-1.428-.835-1.674 0l-.094.319A1.873 1.873 0 0 1 4.377 3.06l-.292-.16c-.764-.415-1.6.42-1.184 1.185l.159.292a1.873 1.873 0 0 1-1.115 2.692l-.319.094c-.835.246-.835 1.428 0 1.674l.319.094a1.873 1.873 0 0 1 1.115 2.693l-.16.291c-.415.764.42 1.6 1.185 1.184l.292-.159a1.873 1.873 0 0 1 2.692 1.116l.094.318c.246.835 1.428.835 1.674 0l.094-.319a1.873 1.873 0 0 1 2.693-1.115l.291.16c.764.415 1.6-.42 1.184-1.185l-.159-.291a1.873 1.873 0 0 1 1.116-2.693l.318-.094c.835-.246.835-1.428 0-1.674l-.319-.094a1.873 1.873 0 0 1-1.115-2.692l.16-.292c.415-.764-.42-1.6-1.185-1.184l-.291.159A1.873 1.873 0 0 1 8.93 1.945l-.094-.319zm-2.633-.283c.527-1.79 3.065-1.79 3.592 0l.094.319a.873.873 0 0 0 1.255.52l.292-.16c1.64-.892 3.434.901 2.54 2.541l-.159.292a.873.873 0 0 0 .52 1.255l.319.094c1.79.527 1.79 3.065 0 3.592l-.319.094a.873.873 0 0 0-.52 1.255l.16.292c.893 1.64-.902 3.434-2.541 2.54l-.292-.159a.873.873 0 0 0-1.255.52l-.094.319c-.527 1.79-3.065 1.79-3.592 0l-.094-.319a.873.873 0 0 0-1.255-.52l-.292.16c-1.64.893-3.433-.902-2.54-2.541l.159-.292a.873.873 0 0 0-.52-1.255l-.319-.094c-1.79-.527-1.79-3.065 0-3.592l.319-.094a.873.873 0 0 0 .52-1.255l-.16-.292c-.892-1.64.902-3.433 2.541-2.54l.292.159a.873.873 0 0 0 1.255-.52l.094-.319z"/>
  <path fill-rule="evenodd" d="M8 5.754a2.246 2.246 0 1 0 0 4.492 2.246 2.246 0 0 0 0-4.492zM4.754 8a3.246 3.246 0 1 1 6.492 0 3.246 3.246 0 0 1-6.492 0z"/>
</svg>
</button>
</td></tr>
</table>
<table class="collapse" width="100%">
<tr><td align="right">
	<div class="justify-content-end btn-toolbar" role="toolbar">
		<div>
			<input class="form-control bg-white" align="right" type="text" style="text-align:right;border:none;border-right:0px; border-top:0px; boder-left:0px; boder-bottom:0px;" readonly id="info" value="" size="10"></input>
		</div>
		<div class="btn-group mr-3" role="group">
			<button id="bright-down" class="btn btn-sm text-danger" onclick="bright_down();">
			<svg width="1.5em" height="1.5em" viewBox="0 0 16 16" class="bi bi-brightness-low-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			  <path d="M12 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM8.5 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 11a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm5-5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm-11 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm9.743-4.036a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707zm-7.779 7.779a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707zm7.072 0a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707zM3.757 4.464a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707z"/>
			</svg>
			</button>
			<button id="bright" class="btn btn-sm text-danger" onclick="bright();">
			<svg width="1.5em" height="1.5em" viewBox="0 0 16 16" class="bi bi-brightness-low" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			  <path fill-rule="evenodd" d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
			  <path d="M8.5 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 11a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm5-5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm-11 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm9.743-4.036a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707zm-7.779 7.779a.5.5 0 1 1-.707-.707.5.5 0 0 1 .707.707zm7.072 0a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707zM3.757 4.464a.5.5 0 1 1 .707-.707.5.5 0 0 1-.707.707z"/>
			</svg>
			</button>
			<button id="bright-up" class="btn btn-sm text-danger" onclick="bright_up();">
			<svg width="1.5em" height="1.5em" viewBox="0 0 16 16" class="bi bi-brightness-high" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			  <path fill-rule="evenodd" d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
			</svg>
			</button>
		</div>
		<div class="btn-group" role="group">
			<button class="btn btn-sm" onclick="location.replace('#<?php echo $bookmark; ?>');" id="load" value="위치저장">
			<svg width="1.5em" height="1.5em" viewBox="0 0 16 16" class="bi bi-bookmark-check-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			  <path fill-rule="evenodd" d="M4 0a2 2 0 0 0-2 2v13.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4zm6.854 5.854a.5.5 0 0 0-.708-.708L7.5 7.793 6.354 6.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/>
			</svg>
			</button>
			<button class="btn btn-sm" onclick="save_bookmark();" id="save" value="위치저장">
			<svg width="1.5em" height="1.5em" viewBox="0 0 16 16" class="bi bi-bookmark-plus" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			  <path fill-rule="evenodd" d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/>
			  <path fill-rule="evenodd" d="M8 4a.5.5 0 0 1 .5.5V6H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H6a.5.5 0 0 1 0-1h1.5V4.5A.5.5 0 0 1 8 4z"/>
			</svg>
			</button>
		</div>
	</div>
</td></tr></table>
<span class="text-nowrap d-inline-block text-truncate"><?php echo cut_title($title); ?></span>
</nav>

<?php
		$files = scandir($base_folder);
		$files = n_sort($files);

		$totalfile = array();
		
		foreach ($files as $file) {
			if(strpos($file, "json") !== false){
			} elseif (strpos(strtolower($file), "zip") !== false || strpos(strtolower($file), "cbz") !== false || strpos(strtolower($file), "rar") !== false || strpos(strtolower($file), "cbr") !== false) {
				$totalfile[] = $file;
			}
		}

		$now = array_search ($title, $totalfile);

		$next = $now + 1;
		$pre = $now - 1;
?>
<div>
<nav class="navbar navbar-light fixed-bottom bg-white m-0 p-1 ">
<table width="100%">
<tr><td align="left">
<div class="btn-group justify-content-center" style="font-family: 'Gugi';">
<?php
         if ($now == '0') {
			 ?>
<button type="button" class="btn btn-outline-light btn-sm mr-1">
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-skip-backward-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M.5 3.5A.5.5 0 0 0 0 4v8a.5.5 0 0 0 1 0V4a.5.5 0 0 0-.5-.5z"/>
  <path d="M.904 8.697l6.363 3.692c.54.313 1.233-.066 1.233-.697V4.308c0-.63-.692-1.01-1.233-.696L.904 7.304a.802.802 0 0 0 0 1.393z"/>
  <path d="M8.404 8.697l6.363 3.692c.54.313 1.233-.066 1.233-.697V4.308c0-.63-.693-1.01-1.233-.696L8.404 7.304a.802.802 0 0 0 0 1.393z"/>
</svg></button>
			 <?php
         } else {
			 ?>
<button type="button" class="btn btn-outline-secondary btn-sm mr-1" OnClick="location.replace('./viewer.php?file=<?php echo encode_url($link_dir."/".$totalfile[$pre]); ?>')">
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-skip-backward-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M.5 3.5A.5.5 0 0 0 0 4v8a.5.5 0 0 0 1 0V4a.5.5 0 0 0-.5-.5z"/>
  <path d="M.904 8.697l6.363 3.692c.54.313 1.233-.066 1.233-.697V4.308c0-.63-.692-1.01-1.233-.696L.904 7.304a.802.802 0 0 0 0 1.393z"/>
  <path d="M8.404 8.697l6.363 3.692c.54.313 1.233-.066 1.233-.697V4.308c0-.63-.693-1.01-1.233-.696L8.404 7.304a.802.802 0 0 0 0 1.393z"/>
</svg></button>
			 <?php
         }
?>
<!-- 리스트로 돌아가기 시작 -->
<?php
if($_GET['filetype'] == "images"){
?>	
<button type="button" class="btn btn-outline-secondary btn-sm mr-1" OnClick="location.replace('./index.php?dir=<?php echo encode_url($getfile); ?>&page=<?php echo $page; ?>')">
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-list" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M2.5 11.5A.5.5 0 0 1 3 11h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 3h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
</svg>
</button>
<?php	
} else {
?>
<button type="button" class="btn btn-outline-secondary btn-sm mr-1" OnClick="location.replace('./index.php?dir=<?php echo encode_url($link_dir); ?>&page=<?php echo $page; ?>')">
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-list" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M2.5 11.5A.5.5 0 0 1 3 11h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 3h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
</svg>
</button>
<?php
}
?>
<!-- 리스트로 돌아가기 끝 -->
<?php
         if (count($totalfile) == $next) {
			 ?>
<button type="button" class="btn btn-outline-light btn-sm">
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-skip-forward-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M15.5 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
  <path d="M7.596 8.697l-6.363 3.692C.693 12.702 0 12.322 0 11.692V4.308c0-.63.693-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
  <path d="M15.096 8.697l-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.693-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
</svg></button>
			 <?php
         } else {
			 ?>
<button type="button" class="btn btn-outline-secondary btn-sm" OnClick="location.replace('./viewer.php?file=<?php echo encode_url($link_dir."/".$totalfile[$next]); ?>')"> 
<svg width="3em" height="1em" viewBox="0 0 16 16" class="bi bi-skip-forward-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M15.5 3.5a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-1 0V4a.5.5 0 0 1 .5-.5z"/>
  <path d="M7.596 8.697l-6.363 3.692C.693 12.702 0 12.322 0 11.692V4.308c0-.63.693-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
  <path d="M15.096 8.697l-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.693-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
</svg></button>
			 <?php
		 }
         ?>		 
		 </div>
</td><td align="right">
<div class="btn-group btn-group-toggle" data-toggle="buttons">

<?php
if($mode == "toon"){
?>
<label class="btn btn-outline-secondary btn-sm mr-1">
<input type="radio" name="options" id="rungallery" OnClick="location.replace('./viewer.php?<?php if($_GET['filetype'] == "images") { echo "filetype=images&";} ?>mode=book&file=<?php echo encode_url($getfile); ?>')">
<?php	
} elseif($mode == "book") {
?>
<label class="btn btn-secondary btn-sm mr-1">
<input type="radio" name="options" id="rungallery" OnClick="location.replace('./viewer.php?<?php if($_GET['filetype'] == "images") { echo "filetype=images&";} ?>mode=toon&file=<?php echo encode_url($getfile); ?>')">
<?php
}	
?>
<svg width="2em" height="1em" viewBox="0 0 16 16" class="bi bi-book" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M1 2.828v9.923c.918-.35 2.107-.692 3.287-.81 1.094-.111 2.278-.039 3.213.492V2.687c-.654-.689-1.782-.886-3.112-.752-1.234.124-2.503.523-3.388.893zm7.5-.141v9.746c.935-.53 2.12-.603 3.213-.493 1.18.12 2.37.461 3.287.811V2.828c-.885-.37-2.154-.769-3.388-.893-1.33-.134-2.458.063-3.112.752zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
</svg>
</label>
  <label class="btn btn<?php
  if($pageorder['page_order'] == "0"){
  } else {
	  echo "-outline";
  }
  ?>-secondary btn-sm">
    <input type="radio" name="options" id="option1" OnClick="location.replace('./viewer.php?<?php if($_GET['filetype'] == "images") { echo "filetype=images&";} ?>file=<?php echo encode_url($getfile); ?>&pageorder=0')"> - 
  </label>
  <label class="btn btn<?php
  if($pageorder['page_order'] == "1"){
  } else {
	  echo "-outline";
  }
  ?>-secondary btn-sm">
    <input type="radio" name="options" id="option2" OnClick="location.replace('./viewer.php?<?php if($_GET['filetype'] == "images") { echo "filetype=images&";} ?>file=<?php echo encode_url($getfile); ?>&pageorder=1')">1|2
  </label>
  <label class="btn btn<?php
  if($pageorder['page_order'] == "2"){
  } else {
	  echo "-outline";
  }
  ?>-secondary btn-sm">
    <input type="radio" name="options" id="option3" OnClick="location.replace('./viewer.php?<?php if($_GET['filetype'] == "images") { echo "filetype=images&";} ?>file=<?php echo encode_url($getfile); ?>&pageorder=2')">2|1
  </label>
</div>  
</td></tr></table>  
</nav>
</div>
<?php
if($mode == "toon"){
?>
<div class="container-fluid m-0 p-0" onclick="hidenav();">
<?php
} elseif($mode == "book") {
?>
<div class="container-fluid m-0 p-0" onclick="run_gallery();">
<?php
}
?>
            <p class="m-0 p-0" align='center'>
              <?php
			  $loaded = 0;
			  $image_counter = 0;
					if ($type == "zip") {
						$list = array();
						$zip = new ZipArchive;
						if ($zip->open($base_file) == TRUE) {
							for ($i = 0; $i < $zip->numFiles; $i++) {
								if(!strpos(strtolower($zip->getNameIndex($i)), ".jpg") && !strpos(strtolower($zip->getNameIndex($i)), ".jpeg") && !strpos(strtolower($zip->getNameIndex($i)), ".png")){
									continue;
								} else {
									$list[$i] = $zip->getNameIndex($i);
								}
							}
						}
						$file_type = "";
 					} elseif($type == "images") {
						$list = array();
						$counter = 0;
						$iterator = new DirectoryIterator($base_file);
						foreach ($iterator as $jpgfile) {
							if (strpos(strtolower($jpgfile), ".jpg") !== false || strpos(strtolower($jpgfile), ".jpeg") !== false || strpos(strtolower($jpgfile), ".png") !== false) {
								$list[$counter] = $base_file."/".$jpgfile;
								$counter++;
							}
						}
						$file_type = "filetype=images&";
					}

						$total = count($list);
						sort($list,SORT_NATURAL);
						
						echo "<div class=\"text-center\" id=\"lightgallery\">";
						foreach($list as $imgfile){
							if($pageorder['page_order'] == "0" || $pageorder['page_order'] == null) {
								echo "<img class='lazyload img-fluid lg-item' id=\"image".$image_counter."\" data-src=\"extract.php?".$file_type."file=".encode_url($_GET['file'])."&imgfile=".encode_url($imgfile)."\" src=\"data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=\" style=\"min-height:250px;\" /><br>";
								$loaded++;
								$image_counter++;
							} elseif($pageorder['page_order'] == "1") {
								echo "<img class='lazyload img-fluid lg-item' id=\"image".$image_counter."\" data-src=\"extract.php?".$file_type."order=left&file=".encode_url($_GET['file'])."&imgfile=".encode_url($imgfile)."\" src=\"data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=\" style=\"min-height:250px;\" /><br>";
								$image_counter++;
								echo "<img class='lazyload img-fluid lg-item' id=\"image".$image_counter."\" data-src=\"extract.php?".$file_type."order=right&file=".encode_url($_GET['file'])."&imgfile=".encode_url($imgfile)."\" src=\"data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=\" style=\"min-height:250px;\" /><br>";
								$loaded++;
								$image_counter++;
							} elseif($pageorder['page_order'] == "2") {
								echo "<img class='lazyload img-fluid lg-item' id=\"image".$image_counter."\" data-src=\"extract.php?".$file_type."order=right&file=".encode_url($_GET['file'])."&imgfile=".encode_url($imgfile)."\" src=\"data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=\" style=\"min-height:250px;\" /><br>";
								$image_counter++;
								echo "<img class='lazyload img-fluid lg-item' id=\"image".$image_counter."\" data-src=\"extract.php?".$file_type."order=left&file=".encode_url($_GET['file'])."&imgfile=".encode_url($imgfile)."\" src=\"data:image/gif;base64,R0lGODdhAQABAPAAAMPDwwAAACwAAAAAAQABAAACAkQBADs=\" style=\"min-height:250px;\" /><br>";
								$loaded++;
								$image_counter++;
							}
						}
						echo "</div>";
						if ($type == "zip") {
							$zip->close();
						}
						$image_counter=$image_counter-1;
					if($loaded < $total){
						echo "모든 파일 로딩에 실패했습니다. 인식할 수 없는 파일이 있습니다.";
					}
               ?>
            </p>
</div>

<script type="text/javascript">
var bookmark = "image0";
var bright_value = 1;
var contrast_value = 1;
var img_counter = 0;
var scroll_counter = 0;

function bright_up() {
	if(bright_counter < 5){
	bright_counter = bright_counter + 1;
	change_bright();
	} else {
	}
}
function bright_down() {
	if(bright_counter > -5){
	bright_counter = bright_counter - 1;
	change_bright();
	} else {
	}
}
function bright() {
	bright_counter = 0;
	change_bright();
}
function change_bright(){
	bright_value = "brightness(" + (1 + (bright_counter * 0.04)) + ")";
	contrast_value = "contrast(" + (1 + (bright_counter * 0.1)) + ")";
	$(".img-fluid").css('-webkit-filter', bright_value);
	$(".img-fluid").css('-webkit-filter', contrast_value);
	$(".img-fluid").css('filter', bright_value);
	$(".img-fluid").css('filter', contrast_value);
	$(".lg-image").css('-webkit-filter', bright_value);
	$(".lg-image").css('-webkit-filter', contrast_value);
	$(".lg-image").css('filter', bright_value);
	$(".lg-image").css('filter', contrast_value);
  	document.getElementById("info").value = "밝기 " + bright_counter;
}
$('#lightgallery').on('onAfterOpen.lg',function(event){
    change_bright();
});
$('#lightgallery').on('onAfterSlide.lg',function(event){
    change_bright();
});

function save_bookmark() {
  	document.getElementById("info").value = "저장중";
<?php
if ($mode == "toon"){
?>
for (var i = 0; i <= <?php echo $image_counter; ?>; i++) {
	var j = <?php echo $image_counter; ?> - i;
	var scroll_image = $("#image"+j).position().top;
	if (scroll_top > scroll_image) {
		bookmark= "image" + String(j);
		break;
	}
}
<?php
} else {
?>
for (var i = 0; i <= <?php echo $image_counter; ?>; i++) {
	var j = <?php echo $image_counter; ?> - i;
	var scroll_top = $(this).scrollTop();
	var scroll_image = $("#image"+j).position().top;
	if (scroll_top > scroll_image) {
		scroll_counter = j;
		break;
	}
}
	if(scroll_counter > img_counter){
		bookmark = "image" + scroll_counter;
	} else {
		bookmark = "image" + img_counter;
		location.replace('#' + bookmark);
	}
<?php
}
?>
$.get( "bookmark.php?viewer=<?php echo $mode; ?>&page_order=<?php echo $pageorder['page_order']; ?>&file=<?php echo encode_url($getfile); ?>&bookmark=" + bookmark, function( data ) {
  	document.getElementById("info").value = data;
});
}
<?php
if ($mode == "book"){
?>
$("body").on('DOMSubtreeModified', "#lg-counter-current", function() {
	var new_counter = document.getElementById("lg-counter-current").innerHTML - 1;
	if (new_counter == 0 || new_counter == null){
	} else {
		img_counter = new_counter;
	}
});
<?php
}
?>
const options = { 
  rootMargin: '1000px 0px',
  threshold: 0
};

document.addEventListener("DOMContentLoaded", function() {
  var lazyImages = [].slice.call(document.querySelectorAll("img.lazyload"));

  if ("IntersectionObserver" in window) {
    let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          let lazyImage = entry.target;
          lazyImage.src = lazyImage.dataset.src;		  
          lazyImage.classList.remove("lazyload");
          lazyImageObserver.unobserve(lazyImage);
        }
      });
    }
	, options);

    lazyImages.forEach(function(lazyImage) {
      lazyImageObserver.observe(lazyImage);
    });
  } else {
    // Possibly fall back to event handlers here
  }
});
</script>
</body>
</html>
