<?php

include_once('config.php');

// creates a iiif manifest for the specimen passed in 
$barcode = $_GET['barcode'];

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

$canvas = new stdClass();
$out->items = array($canvas);
$canvas->id = "$base_url/canvas";
$canvas->type = "Canvas";
$canvas->label = create_label("Scan");

$canvas->thumbnail = array();
$canvas->thumbnail[] = new stdClass();
$canvas->thumbnail[0]->id = $base_url;
$canvas->thumbnail[0]->type = "Image";
$canvas->thumbnail[0]->service = array();
$canvas->thumbnail[0]->service[0] = new stdClass();
$canvas->thumbnail[0]->service[0]->id = $base_url;
$canvas->thumbnail[0]->service[0]->type = "ImageService3";
$canvas->thumbnail[0]->service[0]->profile = "level0";


$props = get_image_properties($barcode);
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

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo $json;

	
?>