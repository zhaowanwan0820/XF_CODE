<?php
/**
 * Autoload class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 * @package libs\init
 **/

/**
 * autoload class 管理类加载的类
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Autoloader {
    /**
     * 注册autoload方法
     **/
    public function __construct() {
        spl_autoload_register(array($this, 'load'));       //注册标准类加载方法
    }

    /**
     * 依据PSR-0标准分析类文件的相对路径
     * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md PSR-0
     * @param string $className 类名
     * @return string 类文件路径
     **/
    private function relativePath($className) {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        // 暂不需要支持apiVersion
        // $prefix = 'api/controllers/';
        // $apiVersion = isset($_REQUEST['apiVersion']) ? trim($_REQUEST['apiVersion']) : '';
        // if (preg_match("~^{$prefix}~i", $fileName) && preg_match('~^v\d+$~i', $apiVersion)) {
        //     $vfileName = $prefix . strtolower($apiVersion) . '/' . preg_replace("~^{$prefix}~i", '', $fileName) . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        //     if (file_exists(APP_ROOT_PATH . $vfileName)) {
        //         return $vfileName;
        //     }
        // }


        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        return $fileName;
    }

    /**
     * 标准类库加载
     *
     * @param string $className 自动传入的类名, 包含完整的命名空间路径
     * @return void
     **/
    private function load($className) {
        $basePath = ROOT_PATH;
        $names = explode('\\', $className);
        if (empty($names[0])) {
            return false;
        }

        // 后期需要根据实践的目录调整完善代码
        $module = $names[0];
        if ($module == 'core') {
            // 对于core的处理
            $basePath = ROOT_PATH;
        } else if (in_array($module, array('openapi', 'api', 'web', 'task'))) {
            // 对于openapi，api，web和task的处理
            $basePath = ROOT_PATH.'app/';
        } else if (in_array($module, array('tests'))) {
            // 增加单元测试namespace的支持
            $basePath = ROOT_PATH;
        }
        else {
            $basePath = ROOT_PATH.'core/';
        }

        $path = $basePath.$this->relativePath($className);
        return $this->import($path);
    }

    /**
     * 检查文件并执行require
     *
     * @param string $path 类文件绝对路径
     *
     * @return void
     **/
    private function import($path) {
        if(file_exists($path)){
            require $path;
            return true;
        } else {
            return false;
        }
    }
}// END class Autoloader

$loader = new  Autoloader();
