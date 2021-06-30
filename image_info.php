<?php

include_once('config.php');

$image_url = $base_url . $_GET['barcode']; // may be file name


$props = get_image_properties($barcode);
$scale_factors = $props['layers'];

$tiles = new stdClass();
$tiles->width = 256;
$tiles->height = 256;
$tiles->scaleFactors = $scale_factors;

// generate the json
$out = new stdClass();
$out->__at__context = "http://iiif.io/api/image/3/context.json";
$out->id = "$image_url";
$out->__at__id = "$image_url";
$out->type = "ImageService3";
$out->protocol = "http://iiif.io/api/image"; 
$out->profile = "level0"; // what features are supported
$out->width = $props['width'];
$out->height = $props['height'];
$out->tiles = array($tiles);

$out->sizes = array();

foreach($props['zoomify_layers'] as $layer){
	$size = new stdClass();
	$size->width = $layer["width"];
	$size->height = $layer["height"];
	$out->sizes[] = $size;
	
}

//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('__at__','@', $json);

header('Content-Type: application/json');
echo $json;

?>