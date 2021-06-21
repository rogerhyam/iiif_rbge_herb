<?php

// set the error reporting to high - so we can see our problems
// commercial sites this would be set lower for production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('UTC');

define('SOLR_QUERY_URI', "http://webstorage.rbge.org.uk:8983/solr/bgbase/");

// we are always called with a barcode so let's build a base URI for all the subsequent calls
$base_url = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/' . @$_GET['barcode'];

$image_url = "https://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=". @$_GET['barcode'].".zip;file:";

function get_image_properties($barcode){
	
	$uri = "https://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=$barcode.zip;file:/ImageProperties.xml&noCacheSfx=1544716865761";

	// get the image properties

	$handle = curl_init();
	curl_setopt($handle, CURLOPT_URL, $uri);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($handle);
	curl_close($handle);

	$xml=simplexml_load_string($data);
	if ($xml === false) {
		http_response_code(404);
		echo "<h1>Not Found</h1>";
		echo "<p>The barcode $barcode couldn't be found";
		foreach(libxml_get_errors() as $error) {
			echo "<p>";
			echo $error->message;
			echo "</p>";
		}
		exit;
	}
	
	$out['width'] = (int)$xml['WIDTH'];
	$out['height'] = (int)$xml['HEIGHT'];
	$out['number_tiles'] = (int)$xml['NUMTILES'];
		
	
	$largest = $out['width'] > $out['height'] ? $out['width'] : $out['height'];
	$out['largest_dimension'] = $largest;
		
	// these are the Scale Factors
	$layers[] = 1;
	$half = $largest/2;
	while($half > 256){
		$layers[] = end($layers) * 2;
		$half = floor($half / 2);
	}
	
	//array_pop($layers);
	
	$out['layers'] = $layers;
	
	// create a description of the zoomify layers in the image
	$w = $out['width'];
	$h = $out['height'];
	$zlayers = array();
	for ($i=count($out['layers']); $i >= 0 ; $i--) { 
		$layer = array();
		$layer['width'] = $w;
		$layer['height'] = $h;
		$layer['cols'] = ceil(floor($w) / 256);
		$layer['rows'] = ceil(floor($h) / 256);
		$layer['tiles_in_layer'] = $layer['rows'] * $layer['cols'];
		
		// half it for the next time around
		$w = floor($w/2);
		$h = floor($h/2);
	
		$zlayers[] = $layer;
	}
	
	$out['zoomify_layers'] = array_reverse($zlayers);

	return $out;
}
	
function create_key_value_label($key, $val){
	$out = new stdClass();
	$out->label = create_label($key);
	$out->value = new stdClass();
	$out->value->en = array($val);
	return $out;
}

function create_label($txt){
	$out = new stdClass();
	$out->en = array($txt);
	return $out; 
}

function get_tile_group($layers, $level, $col, $row){
	
	// count all the tiles to this point
	$number_tiles = 0;
	
	// add the tiles from previous layers
	for ($i=0; $i < $level; $i++) { 
		$layer = $layers[$i];
		$number_tiles += $layer['cols'] * $layer['rows'];
	}
	
	// add the ones to get to this point in this layer
	
	// all the full columns up to this one
	$current_layer = $layers[$level];
	$number_tiles += $current_layer['cols'] * $row +1 + $col -1;
	
	//return $number_tiles;
	
	return floor($number_tiles/256);
	
}
	
?>