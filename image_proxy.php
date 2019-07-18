<?php

require_once('config.php');

$barcode = $_GET['barcode'];
$image_props = get_image_properties($barcode); // gets the details about the image from the Zoomify server

//print_r($image_props);

// the region is specified at the scale of the whole image
// We are either asked for an x/y/w/h region or possibly 'full'
if($_GET['region'] == 'full'){
	
	$region_x = 0;
	$region_y = 0;
	$region_w = $image_props['width'];
	$region_h = $image_props['height'];
	
}else{
	$region = explode(',', $_GET['region']);
	list($region_x, $region_y, $region_w, $region_h) = $region;
}



// the size is the actual dimensions of the returned image
$size = explode(',', $_GET['size']);
list($size_w, $size_h) = $size;

// the scale factor is a whole number because we have specified 
// we only support scaling by different factors
// it can only be 0,1,2,4,8,16,32,64 - does it do 64?
$scale_factor = $region_w/$size_w;
$scale_factor = get_closest($scale_factor, array(1,2,4,8,16,32));

// openseadragon can ask for images that are less than a pixel high (it specifies a width but not a height)
// if it does this we tell it not to be so stupid
if($region_w / $scale_factor < 1 or $region_h / $scale_factor < 1){
		http_response_code(400);
		echo "Sorry: Can't handle requests for images less than 1 pixel in height or width";
		exit;
}

	
// zoomify works the other way around. Layer 0 tiles are lowest magnification (highest scale factor)
// get the closest zoomify layer 

$zoomify_layer = array_search($scale_factor, array_reverse($image_props['layers']));
$zoomify_layer++; // why do I need this?


// which zoomify column and row are we looking at?
// work out the size of the image at this zoom level

$zoomify_col = round(($region_x / $scale_factor) / 256);
$zoomify_row = round(($region_y / $scale_factor) / 256);


// we can get the magnification by comaring the width of the region with the width of the size asked for 
$tile_group = get_tile_group($image_props['zoomify_layers'], $zoomify_layer, $zoomify_col, $zoomify_row);

// example $uri = "http://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=$barcode.zip;file:/ImageProperties.xml&noCacheSfx=1544716865761";
$url = "$image_url/TileGroup$tile_group/$zoomify_layer-$zoomify_col-$zoomify_row.jpg";

//$url = "http://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=E00664331.zip;file:/TileGroup0/1-0-1.jpg";
//echo $url;
//exit;

header('Content-Type: image/jpeg');
header("Access-Control-Allow-Origin: *");
readfile($url);


/* ------------------------------------ */

function get_closest($search, $arr) {
   $closest = null;
   foreach ($arr as $item) {
      if ($closest === null || abs($search - $closest) > abs($item - $search)) {
         $closest = $item;
      }
   }
   return $closest;
}
	
?>