<?php
include __DIR__ . "/init.php";

use Minime\Annotations\Reader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\ArrayCache;

$dir = APP_MODULE_DIR . "../backend/app/services/";
$nameSpace = 'NCFGroup\Ptp\services';

$serviceFileList = glob($dir . '*Service.php');

$targetService = '';
if (array_key_exists('service', $_GET)) {
    $targetService = $_GET['service'];
}

// 手贱，如果在URL上拼错了Service名称
if (!empty($targetService) && !file_exists($dir.$targetService.".php")) {
    header("location: service.php");
    exit(0);
}

if($targetService) {
    $reflectionClass = new ReflectionClass($nameSpace."\\".$targetService);
    $reader = new Reader(new Parser, new ArrayCache);
    $view->setVar('class', $reflectionClass);
    $view->setVar('reader', $reader);
    $view->setVar("targetService", $targetService);

    $methods = array();
    foreach ($reflectionClass->getMethods() as $k => $method) {
        if(substr($method->name, 0, 2) == '__') {
            continue;
        }

        if(!$method->isPublic()) {
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
                        $tmpStr .= '<a href="spec.php?class=' . str_replace('\\', '_', $paramClass) .'">' . $paramClass . '</a> ';
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
            $methodReturn = '<a href="spec.php?class='.str_replace('\\', '_', $methodReturn).'">'.$methodReturn.'</a>';
        }
        // 加入返回结果
        $methods[$k]['methodReturn'] = $methodReturn;
        $docComment = '   ' . $method->getDocComment();
        // 加入注释
        $methods[$k]['docComment'] = $docComment;
        $methodDeclareClass = $method->getDeclaringClass()->getName();

        $sampleCodes = <<<EOT
<?php
// ... 此处省略若干框架启动代码
// ...
// 实例化Request
\$request = new {$paramClass}();

// 构造合法的Request
\$request
EOT;
        $reflectionParam = new \ReflectionClass($paramClass);
        $sampleSetter = [];
        foreach ($reflectionParam->getProperties() as $prop) {
            $sampleSetter[] = 'set'.ucfirst($prop->name)."(\${$prop->name})";
        }
        $sampleCodes .= '->'.implode("\n->", $sampleSetter) . ";";
        $srvName = substr($targetService, 0 ,-7);
        $sampleCodes .= "

// 发起RPC请求，并获得Response
// \$this->sa 为注入DI的Service Agent
\$response = \$this->sa->callByObject(array(
        'service' => '{$srvName}',
        'method' => '{$method->name}',
        'args' => \$request,
));

// Response可以轻松转成数组
// \$response->toArray()
";
        $methods[$k]['sample'] = $sampleCodes;
    }

    $view->setVar('methods', $methods);
}

// 设置模板变量
$view->setVar("title", "NCFGroup FundRPC ServiceList - Yar Server");
$view->setVar("serviceFileList", $serviceFileList);

$viewPage = pathinfo(__FILE__, PATHINFO_FILENAME);
$view->start()
     ->render("view", $viewPage)
     ->finish();
echo $view->getContent();
