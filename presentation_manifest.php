<?php

include_once('config.php');

// creates a iiif manifest for the specimen passed in 
$barcode = $_GET['barcode'];
$props = get_image_properties($barcode);

$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");
$out->id = "$base_url/manifest";
$out->type = "Manifest";

$out->label = create_label("Specimen: $barcode");

// get the DwC from the DB
$response = $mysqli->query("SELECT * FROM darwin_core WHERE CatalogNumber = '$barcode'");
if ($mysqli->error) {
    echo $mysqli->error;
    exit(1);
}
$row = $response->fetch_assoc();
$out->metadata = array();

$out->metadata[] = create_key_value_label('CTAF ID', "<a href=\"" . $row['GloballyUniqueIdentifier'] . "\">" .$row['GloballyUniqueIdentifier']. "</a>" );
$out->metadata[] = create_key_value_label('Catalogue Number', $row['CatalogNumber']);
$out->metadata[] = create_key_value_label('Scientific Name', $row['ScientificName']);
$out->metadata[] = create_key_value_label('Collector', $row['Collector']);
$out->metadata[] = create_key_value_label('Collector Number', $row['CollectorNumber']);
$out->metadata[] = create_key_value_label('Family', $row['Family']);
$out->metadata[] = create_key_value_label('Genus', $row['Genus']);
$out->metadata[] = create_key_value_label('Species', $row['SpecificEpithet']);
$out->metadata[] = create_key_value_label('Higher Geography', $row['HigherGeography']);
$out->metadata[] = create_key_value_label('Field Notes', $row['FieldNotes']);
$out->metadata[] = create_key_value_label('Country', $row['Country']);
$out->metadata[] = create_key_value_label('State/Province', $row['StateProvince']);
$out->metadata[] = create_key_value_label('County', $row['County']);
$out->metadata[] = create_key_value_label('Locality', $row['Locality']);
$out->metadata[] = create_key_value_label('Collected', $row['EarliestDateCollected']);
$out->metadata[] = create_key_value_label('Verbatim Collected', $row['VerbatimCollectingDate']);
$out->metadata[] = create_key_value_label('Verbatim Elevation', $row['VerbatimElevation']);
$out->metadata[] = create_key_value_label('Min Elevation m', $row['MinimumElevationInMeters']);
$out->metadata[] = create_key_value_label('Max Elevation m', $row['MaximumElevationInMeters']);
$out->metadata[] = create_key_value_label('Type Status', $row['TypeStatus']);
$out->metadata[] = create_key_value_label('Geodetic Datum', $row['GeodeticDatum']);
$out->metadata[] = create_key_value_label('Verbatim Longitude', $row['VerbatimLongitude']);
$out->metadata[] = create_key_value_label('Verbatim Latitude', $row['VerbatimLatitude']);
$out->metadata[] = create_key_value_label('Decimal Longitude', $row['DecimalLongitude']);
$out->metadata[] = create_key_value_label('Decimal Latitude', $row['DecimalLatitude']);

$out->summary = new stdClass();
$out->summary = array("Summary of Specimen: $barcode");
$out->viewingDirection = "left-to-right";

$out->thumbnail = array();
$out->thumbnail[] = new stdClass();

// the thumbnail is an actual link to an image of an appropriate size 
// we pick the level 1 of the zoomify tile pyramid and ask for that.
// https://iiif.rbge.org.uk/herb/iiif/E00001237/full/824,1258/0/default.jpg

$out->thumbnail[0]->id = $base_url . '/full/' . $props['zoomify_layers'][1]['width'] . ',' . $props['zoomify_layers'][1]['height'] . '/0/default.jpg';;
$out->thumbnail[0]->type = "Image";
$out->thumbnail[0]->service = array();
$out->thumbnail[0]->service[0] = new stdClass();
$out->thumbnail[0]->service[0]->id = $base_url;
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

$canvas = new stdClass();
$out->items = array($canvas);
$canvas->id = "$base_url/canvas";
$canvas->type = "Canvas";
$canvas->label = create_label("Scan");

$canvas->height = $props['height'];
$canvas->width = $props['width'];

// annotation page
$canvas->items = array();
$image_anno_page = new stdClass();
$canvas->items[] = $image_anno_page;
$image_anno_page->id = "$base_url/annotation_page";
$image_anno_page->type = "AnnotationPage";

// annotation
$image_anno = new stdClass();
$image_anno_page->items = array($image_anno);
$image_anno->id = "$base_url/annotation";
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

$image_anno->target = "$base_url/canvas";


//print_r($out);
$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);
$json = str_replace('"_id":','"@id":', $json);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo $json;

	
?>