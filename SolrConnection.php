<?php

require_once("config.php");

/**
* Simple class to abstract calls to Solr index of specimens
*
*/
class SolrConnection
{
	
	function get_specimen($barcode){
		
		$back = $this->query('barcode_s:' . $barcode);		
		if(isset($back->response) && isset($back->response->docs) && count($back->response->docs)){
			return $back->response->docs[0];
		}
		return null;
		
	}

	function query($query){
    
	    $uri = SOLR_QUERY_URI . urlencode($query) . '&rows=1000';
		
	    $ch = curl_init( $uri );
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    
	    // Send request.
	    $result = curl_exec($ch);
	    curl_close($ch);
	    return json_decode($result);
	}
	


}
	
	
?>