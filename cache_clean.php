<?php
	
// called by cron to keep the cache from getting too big.

error_reporting(E_ALL);
ini_set('display_errors', 1);

$cache = 'cache';
$max_size_mb = 10000; // megabytes - ten gig now

$max_size = $max_size_mb * 1048576;
$size = get_directory_size($cache);

if($size > $max_size){
	
	$file_list = glob($cache . '/*.jpg');
	
	// build a list of all the files with their access time and size
	$files_by_access = array();
	foreach($file_list as $f){
		
		$file_info = stat($f);
		
		$files_by_access[] = array(
			"path" => $f,
			"atime" => $file_info['atime'],
			"bytes" => $file_info['size']
		);
		
	}
	
	// sort it by access time descending (most recent first)
	usort($files_by_access, "sort_function");
	
	// work through and delete the ones after we have reached the max size
	$running_size = 0; // bytes
	$deleted_size = 0;
	foreach($files_by_access as $f){
		$running_size =  $running_size + $f['bytes'];
		if($running_size < $max_size){
			continue;
		}else{
			$deleted_size = $deleted_size + $f['bytes'];
			unlink($f['path']);
		}	
	}
	
	echo "Deleted ". number_format($deleted_size/1048576). "MB.";
}else{
	echo "Cache size ". number_format($size/1048576) ."MB. Max sizes is ". number_format($max_size/1048576) ."MB. Nothing to delete";
}

function sort_function($a, $b){
	return $a['atime'] > $b['atime']? 0 : 1;
}

function get_directory_size($path){
    $bytestotal = 0;
    $path = realpath($path);
    if($path!==false && $path!='' && file_exists($path)){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
            $bytestotal += $object->getSize();
        }
    }
	
    return $bytestotal;
}
	
	
	
?>