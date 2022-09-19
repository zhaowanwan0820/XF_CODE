<?php
include __DIR__."/ClassGenerator.php";

$jsonPath = $argv[1];
$classPath = $argv[2];

foreach(glob($jsonPath.'/*.json') as $jsonFile)
{
    $classGenerator = NCFGroup\Common\Library\CodeGenerator\ClassGenerator::parseByJson(file_get_contents($jsonFile), pathinfo($jsonFile)['filename']);
    $classGenerator->generate($classPath);
}
