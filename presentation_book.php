<?php
	
// This is experimental code.
// if passed a genus and species it will create a "book" of specimens that can be paged through
// we need metadata on the individual canvases though
// also would it be of any use to anyone?

require_once('config.php');
require_once('SolrConnection.php');

$genus = $_GET['genus'];
$species = $_GET['species'];

// this is the query we are building
$q = (object)array(
		"query" => '*:*',
		"filter" => array(
			'has_images_i:1',
			'genus_ni:' . strtoupper($genus),
			'species_ni:' . strtolower($species),
			'record_type_s:specimen'
		),
		"limit" => 10000
);

$solr = new SolrConnection();
$result = $solr->query_object($q);

$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");
$out->id = "$base_url". "book/$genus/$species";
$out->type = "Manifest";
$out->label = create_label( "Book of " . ucwords(strtolower($genus)) . ' ' . strtolower($species) . " specimens" );

$rbge = new stdClass();
$rbge->id = "http://www.rbge.org.uk";
$rbge->type = "Agent";
$rbge->label = create_label("Royal Botanic Garden Edinburgh");
$rbge->homepage = array(
	(object)array(
		"id" => "http://www.rbge.org.uk",
		"type" => "Text",
		"label" => create_label("Royal Botanic Garden Edinburgh"),
		"format" => "text/html" 
	)
);
$rbge->logo = (object)array(
	"id" => 'https://'. $_SERVER['HTTP_HOST'] . '/herb/rbge_logo.png',	
	"type" => "Image",
	"format" => "image/png"
);

// v2 support logo - could be removed when Mirador supports v3.0 properly
$out->logo = (object)array(
	"_id" => 'https://'. $_SERVER['HTTP_HOST'] . '/herb/rbge_logo.png'
);

$out->provider = array();
$out->provider[] = $rbge;

$out->summary = new stdClass();
$out->summary = array("Summary of $genus");
$out->viewingDirection = "left-to-right";

// add the items
$out->items = array();

foreach($result->response->docs as $specimen){
	
	$barcode = $specimen->barcode_s;

	$base_url = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/' . $barcode;
	$props = get_image_properties($barcode);

	$canvas = new stdClass();
	$out->items[] = $canvas;
	$canvas->id = "$base_url#canvas";
	$canvas->type = "Canvas";
	$canvas->label = create_label($barcode);
	$canvas->height = $props['height'];
	$canvas->width = $props['width'];
	// annotation page
	$canvas->items = array();
	$image_anno_page = new stdClass();
	$canvas->items[] = $image_anno_page;
	$image_anno_page->id = "$base_url#annotation_page";
	$image_anno_page->type = "AnnotationPage";
	// annotation
	$image_anno = new stdClass();
	$image_anno_page->items = array($image_anno);
	$image_anno->id = "$base_url#annotation";
	$image_anno->type = "Annotation";
	$image_anno->motivation = "Painting";
	$image_anno->body = new stdClass();
	$image_anno->body->id = "$base_url/info.json";
	$image_anno->body->type = "Image";
	$image_anno->body->format = "image/jpeg";
	$service = new stdClass();
	$service->id = $base_url;
	$service->type = "ImageService3";
	$service->profile = "level0";
	$image_anno->body->service = array($service);
	$image_anno->body->height = $props['height'];
	$image_anno->body->width = $props['width'];
	$image_anno->target = "$base_url#canvas";
		
}


//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);
$json = str_replace('"_id":','"@id":', $json);

//echo '<pre>';
header('Content-Type: application/json');
echo $json;
//print_r($result->response);
	
	
?>