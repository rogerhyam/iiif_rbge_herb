<?php

include_once('config.php');

$barcode = $_GET['barcode'];

$props = get_image_properties($barcode);
$scale_factors = $props['layers'];

$tiles = new stdClass();
$tiles->width = 256;
$tiles->height = 256;
$tiles->scaleFactors = $scale_factors;

// generate the json
$out = new stdClass();
$out->__at__context = "http://iiif.io/api/image/3/context.json";
$out->id = "$base_url";
$out->__at__id = "$base_url";
$out->type = "ImageService3";
$out->protocol = "http://iiif.io/api/image"; 
$out->profile = "level0"; // what features are supported
$out->width = $props['width'];
$out->height = $props['height'];
$out->tiles = array($tiles);

$out->sizes = array();

//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('__at__','@', $json);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo $json;

?>