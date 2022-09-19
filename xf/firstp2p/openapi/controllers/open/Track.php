<?php
/**
 * @abstract openapi  设置cookie，供wap使用
 * @date 2017年 1月 12日 星期二 11:47:18 CST
 *
 */

namespace openapi\controllers\open;

use libs\web\Url;
use libs\web\Open;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class Track extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        if(empty($_COOKIE['returnBtn'])){
            $rootDomain = '.'.implode('.', array_slice((explode('.', $_SERVER['HTTP_HOST'])), -2));
            header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
            setcookie("returnBtn", 1, 0, '/', $rootDomain, false, true);
        }
        $this->json_data = 1;
        return true;
    }

    public function _after_invoke() {
    }

    public function authCheck() {
        return true;
    }
}
