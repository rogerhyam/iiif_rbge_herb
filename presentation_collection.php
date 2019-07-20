<?php
	
// allows family/genus/species browsing of specimens.
// little viewer support for this but may be useful exploratory resource

require_once('config.php');
require_once('SolrConnection.php');

// load up the queries

$family = $_GET['family'];
if($family == '-') $family = false;

$genus = $_GET['genus'];
if($genus == '-') $genus = false;

$species = $_GET['species'];
if($species == '-') $species = false;

// this is the query we are building
$q = (object)array(
		"query" => '*:*',
		"filter" => array('has_images_i:1'),
		"limit" => 0,
		"facet" => array()
);

if(!$family){
	
	// no family so give them a choice of families	
	$q->facet["families"] = array(
		"terms" => array(
			"field" => "family_ni",
			"limit" => -1,
			"missing" => true,
			"sort" => "index"
		)
	);
	
}else{

	// they have a family - do they have a genus
	// add family to the filter
	$q->filter[] = 'family_ni:' . strtoupper($family);
	
	if(!$genus){
		// no genus so give them a choice
		$q->facet["genera"] = array(
			"terms" => array(
				"field" => "genus_ni",
				"limit" => -1,
				"sort" => "index",
				"missing" => true
			)
		);
		
	}else{
		// they have a genus so give them a choice of species
		
		// add genus to the filter
		$q->filter[] = 'genus_ni:' . strtoupper($genus);
		
		if(!$species){
			$q->facet["species"] = array(
				"terms" => array(
					"field" => "species_ni",
					"limit" => -1,
					"sort" => "index",
					"missing" => true
				)
			);
		}else{
			// add species to filter
			$q->filter[] = 'species_ni:' . strtolower($species);
			
			// when we get to genus level we can also display specimens
			$q->limit = 10000;
		}
		
		
	}

}

// solr doesn't like and empty facet list
if(!$q->facet) unset($q->facet);


$solr = new SolrConnection();
$result = $solr->query_object($q);

//echo "<pre>";
//	print_r($result);

// echo "<pre>";
// print_r(json_decode($result, JSON_PRETTY_PRINT));


$out = new stdClass();
$out->context = array("http://www.w3.org/ns/anno.jsonld","http://iiif.io/api/presentation/3/context.json");

$out->id = 'http://'. $_SERVER['HTTP_HOST'] . "/herb/iiif/collection/".$_GET['family']."/".$_GET['genus']."/" .$_GET['species'] ;

$out->type = "Collection";
$out->label = create_label(get_collection_name($family, $genus, $species));
$out->summary = create_label( "Specimens in " . get_collection_name($family, $genus, $species));
$out->items = array();

//print_r($result->facets->genera->buckets);

// add the families if we have them
if(isset($result->facets->families)){
	foreach($result->facets->families->buckets as $f){
		$man = new stdClass();
		$man->id = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/collection/' . $f->val . "/" . $_GET['genus'] . "/" .$_GET['species'];
		$man->type = "Collection";
		$man->label = create_label( get_collection_name($f->val, $genus, $species, $f->count) );
		$out->items[] = $man;
		
	}
}

// add the genera if we have them
if(isset($result->facets->genera)){
	foreach($result->facets->genera->buckets as $g){
		$man = new stdClass();
		$man->id = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/collection/' .$_GET['family']. "/" . $g->val . "/" .$_GET['species'];
		$man->type = "Collection";
		$man->label = create_label( get_collection_name($family, $g->val, $species, $g->count) );
		$out->items[] = $man;
		
	}
}

// add the species if we have them
if(isset($result->facets->species)){
	foreach($result->facets->species->buckets as $s){
		$man = new stdClass();
		$man->id = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/collection/' .$_GET['family']. "/" . $_GET['genus'] . "/" . $s->val;
		$man->type = "Collection";
		$man->label = create_label( get_collection_name($family, $genus, $s->val, $s->count) );
		$out->items[] = $man;
		
	}
}

if(isset($result->response->docs)){
	foreach($result->response->docs as $doc){
		$man = new stdClass();
		$man->id = 'https://'. $_SERVER['HTTP_HOST'] . '/herb/iiif/'. $doc->barcode_s .'/manifest';
		$man->type = "Manifest";
		$man->label = create_label( $doc->barcode_s );
		$out->items[] = $man;
	}
}

$json = json_encode( $out, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES );

// total hack to add the @ to the context attribute (not acceptable in php)
$json = str_replace('"context":','"@context":', $json);

header('Content-Type: application/json');

//echo "<pre>";
echo $json;
//echo "</pre>";

/* ----------------------------- */

function get_collection_name($family, $genus, $species, $count = false){
	
	$label = "";
	
	if($family) $label .= ucwords(strtolower($family));
	if($genus) $label .= ', ' . ucwords(strtolower($genus));
	if($species) $label .= ' ' . strtolower($species);
	if($count !== false) $label .= ' (' . $count . ' specimens)';
	
	if(strlen($label) < 1) $label = "Herbarium E";
	
	return $label;
	
}

	
?>