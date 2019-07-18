<?php
	// given a barcode and zoom level will fetch all tiles
	
	require_once('config.php');
	
	$level = @$_GET['level'];
	$barcode = $_GET['barcode'];
	$props = get_image_properties($barcode);
	
	$layers = $props['zoomify_layers']
	
	
	
	// print out a summary of the layers
?>
<table>
	<tr>
		<th>Level</th>
		<th>Width</th>
		<th>Height</th>
		<th>Cols</th>
		<th>Rows</th>
		<th>Tiles in Layer</th>
	</tr>
<?php
foreach($layers as $n => $layer){
	echo "<tr>";
	echo "<td><a href=\"image_debug.php?barcode=$barcode&level=$n\">Level: ". $n ."</a></td>";
	echo "<td>". round($layer['width']) ."</td>";
	echo "<td>". round($layer['height']) . "</td>";
	echo "<td>". $layer['cols'] ."</td>";
	echo "<td>". $layer['rows'] ."</td>";
	echo "<td>". $layer['tiles_in_layer'] ."</td>";
	echo "</tr>";
}			
?>
</table>

<hr/>

<style>
	.image-grid td{
		width: 80px;
		height: 80px;
		position:relative;
	}
	.image-grid img{
		max-width: 80px;
		max-height: 80px;
		position: absolute;
		  top: 0px;
		  left: 0px;
	}
</style>
<?php	
	if(!isset($level)) exit;
	
	$layer = $layers[$level];
	
	// get to here we are rendering one of the levels
	echo "<table class=\"image-grid\">";
	
	for ($i=0; $i < $layer['rows']; $i++) {
		echo "<tr>";
		for ($j=0; $j < $layer['cols']; $j++) {
			
			$tile_group = get_tile_group($layers, $level, $j, $i);
			$uri = "$image_url/TileGroup$tile_group/$level-$j-$i.jpg";
			echo "<td><a href=\"$uri\"><img src=\"$uri\" title=\"$level-$i-$j\"/></a></td>";
		}
		echo "</tr>";
	}
	
	echo "</table>";
	
	
?>