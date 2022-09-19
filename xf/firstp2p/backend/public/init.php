<?php
// 初始化系统
require(dirname(dirname(__DIR__)).'/app/init.php');

$GLOBALS['phalcon_bootstrap']->dependModule('backend');
$di = $GLOBALS['phalcon_bootstrap']->getDI();

// 初始化模板
$view = new \Phalcon\Mvc\View();
$view->setDI($di);
$view->setViewsDir(__DIR__."/assets/");
$view->registerEngines(array(
    ".volt" => function() use ($view, $di) {
        $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
        $volt->setOptions(array(
            "compiledPath"      => __DIR__."/assets/compiled/",
            "compiledExtension" => ".compiled",
            "compiledAlways"    => false
        ));
        $compiler = $volt->getCompiler();
        $compiler->addExtension(new \Phalcon\Volt\Extension\PhpFunction());
        return $volt;
    }
));
