<?php
/**
 * @file TestArrayUtil.php
 *  
 **/


require_once "../ArrayUtil.php";
class TestArrayUtil extends PHPUnit_Framework_TestCase {
	public function testSortKeyArray()
	{
		$data = array(
			array(
				'a'=>'a',
				'realTime'=>12345,
			),
			array(
				'b'=>'b',
				'realTime'=>123,
			),
			array(
				'c'=>'c',
				'realTime'=>345,
			),
			array(
				'd'=>'d',
				'realTime'=>1234,
			),
		);
		var_dump(ArrayUtil::sortKeyArray($data,'realTime'));	
		var_dump(ArrayUtil::sortKeyArray($data,'realTime',false));	
	}

	public function testUnsetKeyArray()
	{
		$data = array(
			array(
				'a'=>'a',
				'realTime'=>12345,
			),
			array(
				'b'=>'b',
				'realTime'=>123,
			),
			array(
				'c'=>'c',
				'realTime'=>345,
			),
			array(
				'd'=>'d',
				'realTime'=>1234,
			),
		);
		var_dump(ArrayUtil::unsetKeyArray($data,'realTime'));	
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
