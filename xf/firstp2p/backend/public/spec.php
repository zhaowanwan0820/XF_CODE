<?php
// initialize
include __DIR__ ."/init.php";

use Minime\Annotations\Reader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\ArrayCache;

$class = isset($_GET['class'])?$_GET['class']:"";
if(empty($class)) {
    echo "Nothing found here!";
    exit(1);
}

$reader = new Reader(new Parser, new ArrayCache);
$class = str_replace("_", "\\", $class);
if(!class_exists($class)) {
    echo "Whoops, Class {$class} not exists!";
    exit(1);
}
$reflectionClass = new ReflectionClass($class);
if($reflectionClass->isInternal()) {
    echo "Internal classes Cannot be readed.";
    exit(1);
}
$constReflection = new \NCFGroup\Common\Library\ConstDoc($reflectionClass);

// 设置模板变量
$view->setVar('reflectionClass', $reflectionClass);
$view->setVar('constReflection', $constReflection);
$view->setVar('reader', $reader);
$view->setVar("title", "NCFGroup FundRPC ProtoBuffer Specification");
$view->setVar('class', $class);

$methods = array();
foreach ($reflectionClass->getMethods() as $k => $method) {
    if(substr($method->name, 0, 2) == '__') {
        continue;
    }
    // 加入方法名
    $methods[$k]['name'] = $method->name;
    $modifier = implode(' ', \Reflection::getModifierNames($method->getModifiers()));
    // 加入修饰符
    $methods[$k]['modifier'] = $modifier;
    try {
        $annotations = $reader->getMethodAnnotations($method->class, $method->name);
    }catch(\Exception $e) {
        // nothing to do
    }
    $paramList = $method->getParameters();
    $params = array();
    if(!empty($paramList)) {
        foreach($paramList as $param) {
            $tmpStr = '';
            if($param->isArray()) {
                $tmpStr .= 'array ';
            } else {
                if($param->getClass()) {
                    $paramClass = $param->getClass()->getName();
                    $tmpStr .= '<a href="?class=' . str_replace('\\', '_', $paramClass) .'">' . $paramClass . '</a> ';
                }
            }
            if($param->isPassedByReference()) {
                $tmpStr .= '&';
            }
            $tmpStr .= '$' . $param->getName();
            if(!$method->isInternal() && $param->isOptional()) {
                $tmpStr .= ' = ' . var_export($param->getDefaultValue(), true);
            }
            $params[] = $tmpStr;
        }
        $paramStr = implode(", ", $params);
    } else {
        $paramStr = 'void';
    }
    // 加入参数列表
    $methods[$k]['paramStr'] = $paramStr;
    $methodReturn = $annotations->get('return');
    if(class_exists($methodReturn)) {
        $methodReturn = '<a href="?class='.str_replace('\\', '_', $methodReturn).'">'.$methodReturn.'</a>';
    }
    // 加入返回结果
    $methods[$k]['methodReturn'] = $methodReturn;
    $docComment = '   ' . $method->getDocComment();
    // 加入注释
    $methods[$k]['docComment'] = $docComment;
    $methodDeclareClass = $method->getDeclaringClass()->getName();
    $inherit = '';
    if($methodDeclareClass != ltrim($class, "\\")) {
        $inherit = '<span class="type">inherit</span> <a href="spec.php?class=' . str_replace('\\', '_', $methodDeclareClass) . '">'.$methodDeclareClass . '</a>';
    }
    //加入继承关系
    $methods[$k]['inherit'] = $inherit;
}
$view->setVar('methods', $methods);

$viewPage = pathinfo(__FILE__, PATHINFO_FILENAME);
$view->start()
     ->render("view", $viewPage)
     ->finish();
echo $view->getContent();

?>
