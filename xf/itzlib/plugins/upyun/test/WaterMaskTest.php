<?php
error_reporting(E_ALL);

require_once('../WaterMask.php');

$obj = new WaterMask(dirname(__FILE__)."/src.jpg");        
//类型：0为文字水印、1为图片水印
$obj->waterType = 1;     
               
//水印透明度，值 越小透明度越高
$obj->transparent = 100;                   
//水印图片        
$obj->waterImg = dirname(__FILE__)."/water.gif";//水印图片
//输出水印图片文件覆盖到输入的图片文件 
$obj->output();