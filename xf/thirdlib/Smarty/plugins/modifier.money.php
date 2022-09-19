<?php
/**
 * money
 * 
 * Type:     modifier<br>
 * Name:     money<br>
 */
function smarty_modifier_money($string){
	$output = sprintf('%.2f', $string);
	if ($output != '0.00' && ceil($output) % 10000 == 0) {
		$output = intval($output/10000).'万';
	}
	return $output;
}

?>
