<?php
	
// render a collection based on the family or genus of the specimen

require_once('config.php');

$genus = $_GET['genus'];
$species = $_GET['species'];

// fixme - catch injection

$sql = "SELECT 
    s.COLL_NAME,
    s.COLL_NUM,
	n.genus,
	n.species,
	s.barcode
 FROM image_archive.derived_images as i
 join specimens as s on i.barcode = s.BARCODE
 join current_names as n on n.specimen_num = s.SPECIMEN_NUM
 where i.image_type = 'ZOOMIFY'
 and n.genus = '$genus'
 and n.species = '$species'
 order by i.barcode";

$response = $mysqli->query($sql);
if ($mysqli->error) {
    echo $mysqli->error;
    exit(1);
}

$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");
$out->id = 'http://'. $_SERVER['HTTP_HOST'] . '/iiif/collection/' . $genus;
$out->type = "Collection";
$out->label = create_label("<i>$genus $species</i>");
$out->summary = create_label("Specimens with images from <i>$genus $species</i>.");
$out->items = array();

while($row = $response->fetch_assoc()){
	$man = new stdClass();
	$man->id = 'http://'. $_SERVER['HTTP_HOST'] . '/iiif/' . $row['barcode'];
	$man->type = "Manifest";
	$man->label = create_label( $row['barcode'] . ' - ' . $row['COLL_NAME'] . ' ' . $row['COLL_NUM']  );
	
	$thumbnail = new stdClass();
	$thumbnail->id = 'http://192.168.7.71/iiif/E00590785/4096,0,1758,2048/220,/0/default.jpg';
	$thumbnail->type = "Image";
	
	$service = new stdClass();
	$service->id = 'http://'. $_SERVER['HTTP_HOST'] . '/iiif/' . $row['barcode'];
	$service->type = "ImageService3";
	$service->profile = "level0";
	$thumbnail->service = array($service);
	
	$out->thumbnail = array($thumbnail);
	
	$out->items[] = $man;
}


$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo $json;

	
?>