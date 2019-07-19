<?php

require_once('config.php');

$barcode = $_GET['barcode'];
$image_props = get_image_properties($barcode); // gets the details about the image from the Zoomify server

//print_r($image_props);

// the size is the actual dimensions of the image to be returned
$size = explode(',', $_GET['size']);
list($size_w, $size_h) = $size;

// the region of the original is specified at the scale of the whole image
// We are either asked for an x/y/w/h region or possibly 'full'
if($_GET['region'] == 'full'){
	
	// if it is full then we only support returning images of sizes that match the 
	// sizes of complete layers in the zoomify stack.
	for ($i=0; $i < count($image_props['zoomify_layers']); $i++) { 
		$zlayer = $image_props['zoomify_layers'][$i];
		//print_r($zlayer);
		if($zlayer['width'] == $size_w && $zlayer['height'] == $size_h){
			return_full_image($barcode, $i, $image_props);
			exit();
		}
	}
	
	// plus lets do some common thumbnails because these are often hard coded in viewers?
	// plus it makes it easy to give thumbnails without knowing image details.
	if(!$size_h){
		return_thumbnail($barcode, $size_w, 'width', $image_props);
	}
	
	if(!$size_w){
		return_thumbnail($barcode, $size_h, 'height', $image_props);
	}
	
	// got to here so they are not asking for a image size we understand.
	http_response_code(400);
	echo "Sorry: Can only handle full image requests of specific size.";
	exit;
	
}else{
	$region = explode(',', $_GET['region']);
	list($region_x, $region_y, $region_w, $region_h) = $region;
}

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


function return_thumbnail($barcode, $size, $dimension, $image_props){
	
	global $image_url;

	
	// check if we have a cached version of the thumbnail
	$thumb_cached_path = 'cache/' . $barcode . '-thumb-' . $dimension . '-'. $size . '.jpg';
	if(file_exists($thumb_cached_path)){
		header("Content-Type: image/jpeg");
		readfile($thumb_cached_path);
		exit;
	}
	
	$layers = $image_props['zoomify_layers'];
	$level = -1;
	for ($i=0; $i < count($layers); $i++) { 
		if($layers[$i][$dimension] >= $size){
			$level = $i;
			break;
		}
	}

	if($level == -1){
		http_response_code(400);
		echo "Sorry: Can only handle full image requests of specific size. Not width $width";
		exit;
	}
	
	// load the full image 
	$full_cached_path = 'cache/' . $barcode . '-' . $level . '.jpg';
	if(file_exists($full_cached_path)){
		$image = new Imagick($full_cached_path);
	}else{
		$image  = get_full_image($level, $image_props);
		$image->writeImage($full_cached_path);
	}
	
	if($dimension == 'width'){
		$image->scaleImage($size, 0, false);
	}else{
		$image->scaleImage(0, $size, false);
	}
	
	$image->writeImage($thumb_cached_path);
	
	header('Content-Type: image/jpeg');
	echo $image;
	

}


function return_full_image($barcode, $level, $image_props){
		
	// check if we have it cached before we do anything else
	$cached_path = 'cache/' . $barcode . '-' . $level . '.jpg';
	if(file_exists($cached_path)){
		header("Content-Type: image/jpeg");
		readfile($cached_path);
		exit;
	}

	$combined = get_full_image($level, $image_props);
	
	// cache it so we don't have to create it again
	$combined->writeImage($cached_path);

	header('Content-Type: image/jpeg');
	echo $combined;
	
}

function get_full_image($level, $image_props){
	
	global $image_url;
	
	$layers = $image_props['zoomify_layers'];

	$layer = $layers[$level];

	$rows = new Imagick();
	for ($i=0; $i < $layer['rows']; $i++) {
	
		$row = new Imagick();
	
		for ($j=0; $j < $layer['cols']; $j++) {		
			$tile_group = get_tile_group($layers, $level, $j, $i);
			$uri = "$image_url/TileGroup$tile_group/$level-$j-$i.jpg";
			$row->addImage(new Imagick($uri));
		}
	
		// stitch the row into a single image
		$row->resetIterator();
	
		// add it to the rows
		$rows->addImage($row->appendImages(false));
	
	}

	$rows->resetIterator();
	$combined = $rows->appendImages(true); // append them vertically
	$combined->setImageFormat("jpg");
	
	return $combined;
}
	
?>