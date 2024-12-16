<?php

// set the error reporting to high - so we can see our problems
// commercial sites this would be set lower for production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('UTC');

require_once('SolrConnection.php');

// the first thing we do it check for scrapers as they are causing issues
// Regular expression to match common browsers
$ip_address = @$_SERVER['REMOTE_ADDR'];
$agent = @$_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : 'No agent';
$browserlist = '/(opera|aol|msie|firefox|chrome|konqueror|safari|netscape|navigator|mosaic|lynx|amaya|omniweb|avant|camino|flock|seamonkey|mozilla|gecko)+/i';

// Test for browsers and local servers
if (!preg_match($browserlist, $agent) && !preg_match('/^192\.168\./', $ip_address) && !preg_match('/^193\.62\./', $ip_address)) {

	// they are a simple script not trying to spoof a browser
	
	// do we have a monitor file for them?
	$log_file = 'throttle/' . $ip_address . '.txt';
	if($last_call = @file_get_contents($log_file)){
		$last_call = (int)$last_call;
	}else{
		$last_call = 0;
	}
	$now = time();
	file_put_contents($log_file, $now);

	if($now - $last_call < 3){
		http_response_code(429);
		echo "<h1>Too many requests</h1>";
		echo "<p>Unfortunately, due to a small group of people who are clever enough to write Python code but 
		stupid enough not to realise they are creating a denial of service attack,
		we are having to throttle these kinds of calls at the moment. Take it slow and only ask for the data you really need.
		If you need it ALL ask us for a download it. Please don't scrape our IIIF server!</p>";
		error_log("IIIF delayed too many requests for $ip_address with browser {$agent}");
		exit;
	}

}

define('SOLR_QUERY_URI', "http://webstorage.rbge.org.uk:8983/solr/bgbase/select");

//define('SOLR_QUERY_URI', "https://iiif.rbge.org.uk/solr_proxy.php");

// we are always called with a barcode so let's build a base URI for all the subsequent calls
$base_url = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/';

if(@$_GET['barcode']){
	$file_name = get_image_file_name(@$_GET['barcode']);
	$image_url = "https://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path=" . $file_name  . ";file:";	
}else{
	$image_url = null;
}

function get_image_file_name($barcode, $index = 0){

	// we need to get the file name from SOLR. Nearly always it is the barcode. But not always!

	// the "barcode" may be qualified with _a _b etc after it (it is actually a file name itself!
	if(preg_match('/^E[0-9]{8}$/', $barcode)){
		// we have a pure barcode so get the specimen and return the image at the requested index.
		$solr = new SolrConnection();
		$specimen = $solr->get_specimen($barcode);
		if(!$specimen || !isset($specimen->image_filename_nis) || count($specimen->image_filename_nis) < 1 || $index > count($specimen->image_filename_nis)){
			http_response_code(404);
			echo "<h1>Not Found</h1>";
			echo "<p>Could not find image file name for the $index image of specimen $barcode.";
			exit;
		}
	
		$file_name = $specimen->image_filename_nis[$index];
	}else{
		// we have been given some random string, probably something like E01197039_a
		// in this case it IS the file name sans .zip
		$file_name = $barcode . '.zip';
	}

	return $file_name;
	
}

/**
 * Will return image properties for a barcode
 * @param barcode is the barcode of the specimen
 * @param index which image of the specimen details are wanted for. Defaults to 0 as nearly all specimens will have a single image.
 * 
 */
function get_image_properties($barcode, $index = 0){

	$file_name = get_image_file_name($barcode, $index);
	$uri = "https://data.rbge.org.uk/search/herbarium/scripts/getzoom3.php?path={$file_name};file:/ImageProperties.xml&noCacheSfx=1544716865761";

	// get the image properties

	$handle = curl_init();
	curl_setopt($handle, CURLOPT_URL, $uri);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($handle);
	curl_close($handle);

	$xml= @simplexml_load_string($data);
	if ($xml === false) {
		http_response_code(404);
		echo "<h1>Not Found</h1>";
		echo "<p>Failed to read data for file $file_name for specimen $barcode";
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
