<?php

require_once "../StringUtil.php";
/**
 * test case.
 */
class TestStringUtil extends PHPUnit_Framework_TestCase { 

	public function testTruncate() {
	    $input = "<em>qq</em>.com/huaxia《<em>QQ</em>华夏》-《华夏地理》杂志独家推荐网游关于<em>腾讯</em>游戏<em>腾讯</em>游戏，全球领先的游戏开发和运营机构，国内最大的网络游戏社区。以“用心创造快乐”的理念，<em>腾讯</em>游戏通过在多个产品细分领域的耕耘， ...";
		//$str = StringUtil::truncate($input, 100);
		$input = "范冰冰一袭复古蓝衫，短发红唇惊艳全场，连昔日的好莱坞影后格温妮丝";
		$str = StringUtil::truncate($input, 10);
		$input = "足坛扫黑案足坛扫黑案 足坛扫黑案足坛扫黑案";
		$str = StringUtil::truncate($input, 20);
		$input = "09：16";
		$str = StringUtil::truncate($input, 3);
		var_dump($input,$str);
		$input = "一二三四五六七八九十";
		$str = StringUtil::truncate($input, 9);
		var_dump($input,$str);
		$input = "一二三四五六七八九十a";
		$str = StringUtil::truncate($input, 9);
		var_dump($input,$str);
		$input = "美联储上缴769亿美元 货币政策是否地方似懂非懂";
		$str = StringUtil::truncate($input, 18,"...");
		var_dump($input,$str);
		$input = "美&联&nbsp;";
		$str = StringUtil::truncate($input, 3,"...");
		var_dump($input,$str);
	}

}
