<?php

include_once('config.php');
include_once('SolrConnection.php');


// creates a iiif manifest for the specimen passed in 
$barcode = @$_GET['barcode'];

$solr = new SolrConnection();
$specimen = $solr->get_specimen($barcode);

if(!$specimen){
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Not Found</h1>";
	echo "Can't load that specimen.\n";
	exit;
}

$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");
$out->id = $base_url . $barcode . "/manifest";
$out->type = "Manifest";
$label = strip_tags($specimen->current_name_ni) . " ($barcode)";
$out->label = create_label($label);

$out->metadata = array();

$guid = "http://data.rbge.org.uk/herb/" . $barcode;
$out->metadata[] = create_key_value_label('CETAF ID', "<a href=\"$guid\">$guid</a>" );
$out->metadata[] = create_key_value_label('Catalogue Number', $barcode);
if(isset($specimen->current_name_ni)) $out->metadata[] = create_key_value_label('Scientific Name', $specimen->current_name_ni);
if(isset($specimen->collector_s)) $out->metadata[] = create_key_value_label('Collector', $specimen->collector_s);
if(isset($specimen->collector_num_s)) $out->metadata[] = create_key_value_label('Collector Number', $specimen->collector_num_s);
if(isset($specimen->family_ni)) $out->metadata[] = create_key_value_label('Family', ucfirst(strtolower($specimen->family_ni)));
if(isset($specimen->genus_ni)) $out->metadata[] = create_key_value_label('Genus', ucfirst(strtolower($specimen->genus_ni)));
if(isset($specimen->species_ni)) $out->metadata[] = create_key_value_label('Species', strtolower($specimen->species_ni));
if(isset($specimen->description_ni)) $out->metadata[] = create_key_value_label('Field Notes', $specimen->description_ni);
if(isset($specimen->country_s)) $out->metadata[] = create_key_value_label('Country', $specimen->country_s);
if(isset($specimen->sub_country1_ni)) $out->metadata[] = create_key_value_label('State/Province', $specimen->sub_country1_ni);
if(isset($specimen->locality_ni)) $out->metadata[] = create_key_value_label('Locality', $specimen->locality_ni);	

$out->summary = new stdClass();
$out->summary = array("Summary of Specimen: $barcode");
$out->viewingDirection = "left-to-right";

$out->thumbnail = array();
$out->thumbnail[] = new stdClass();

// the thumbnail is an actual link to an image of an appropriate size 
// we pick the level 1 of the zoomify tile pyramid and ask for that.
// https://iiif.rbge.org.uk/herb/iiif/E00001237/full/824,1258/0/default.jpg

// there is always and image with the filename of the barcode, there may be others as well.
// this can be used for the thumbnail
$props = get_image_properties($barcode);
$out->thumbnail[0]->id = $base_url . $barcode . '/full/' . $props['zoomify_layers'][1]['width'] . ',/0/default.jpg';
$out->thumbnail[0]->type = "Image";
$out->thumbnail[0]->service = array();
$out->thumbnail[0]->service[0] = new stdClass();
$out->thumbnail[0]->service[0]->id = $base_url . $barcode;
$out->thumbnail[0]->service[0]->type = "ImageService3";
$out->thumbnail[0]->service[0]->profile = "level0";

$out->rights = "https://creativecommons.org/licenses/by-sa/2.5/";
$out->requiredStatement = create_key_value_label("Attribution", "Royal Botanic Garden Edinburgh");


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

// see also links to other places
/*
	"seeAlso": [
        {
          "id": "https://data.rbge.org.uk/herb/E00421509",
          "type": "Text",
          "label": { "en": [ "Catalogue page for this specimen." ] },
          "format": "text/html"
        },
       {
          "id": "https://data.rbge.org.uk/herb/E00421509",
          "type": "Dataset",
          "label": { "en": [ "Specimen Description in RDF XML." ] },
          "format": "application/rdf+xml"
        }
]
*/
$out->seeAlso = array();

// link to web page for specimen
$out->seeAlso[] = (object)array(
	'id' => "https://data.rbge.org.uk/herb/" . $barcode,
	'type' => "Text",
	'label' => create_label("Catalogue page for this specimen."),
	'format' => "text/html",
);

// link to RDF data for specimen
$out->seeAlso[] = (object)array(
	'id' => "https://data.rbge.org.uk/herb/" . $barcode,
	'type' => "Dataset",
	'label' => create_label("Specimen description in RDF XML."),
	'format' => "application/rdf+xml",
);


// There may be multiple images so create a canvas
$out->items = array();

foreach($specimen->image_filename_nis as $file){

	$image = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);

	// get the props for the image
	if($image != $barcode){
		$props = get_image_properties($image);
	}
	
	$canvas = new stdClass();
	$out->items[] = $canvas;
	$canvas->id = $base_url . $image . "#canvas";
	$canvas->type = "Canvas";
	$canvas->label = create_label($image);

	$canvas->height = $props['height'];
	$canvas->width = $props['width'];

	// annotation page
	$canvas->items = array();
	$image_anno_page = new stdClass();
	$canvas->items[] = $image_anno_page;
	$image_anno_page->id = $base_url . $image . "#annotation_page";
	$image_anno_page->type = "AnnotationPage";

	// thumbnail for the canvas
	$thumbnail = new stdClass();
	$thumbnail->id = $base_url . $image . '/full/' . $props['zoomify_layers'][1]['width'] . ',/0/default.jpg';
	$thumbnail->type = "Image";
	$thumbnail->service = array();
	$thumbnail->service[0] = new stdClass();
	$thumbnail->service[0]->id = $base_url . $barcode;
	$thumbnail->service[0]->type = "ImageService3";
	$thumbnail->service[0]->profile = "level0";
	$canvas->thumbnail[] = $thumbnail;

	// annotation
	$image_anno = new stdClass();
	$image_anno_page->items = array($image_anno);
	$image_anno->id = $base_url . $image ."#annotation";
	$image_anno->type = "Annotation";
	$image_anno->motivation = "Painting";
	$image_anno->body = new stdClass();
	$image_anno->body->id = $base_url . $image . "/info.json";
	$image_anno->body->type = "Image";
	$image_anno->body->format = "image/jpeg";

	// service
	$service = new stdClass();
	$service->id = $base_url . $image;
	$service->type = "ImageService3";
	$service->profile = "level0";
	$image_anno->body->service = array($service);

	$image_anno->body->height = $props['height'];
	$image_anno->body->width = $props['width'];

	$image_anno->target = $base_url . $image . "#canvas";

}

//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);
$json = str_replace('"_id":','"@id":', $json);

header('Content-Type: application/json');
echo $json;

	
?>
