<?php

namespace NCFGroup\Common\Library\Idno;

use NCFGroup\Common\Library\CommonLogger;
use NCFGroup\Common\Library\Idno\Providers\Rongshu;
//use NCFGroup\Common\Library\Face\Providers\Yuanjin;

/**
 * 实名认证
 */
class Idno
{

    /**
     * 验证姓名与身份证号
     */
    public static function verifyName($name, $idno)
    {
        return Rongshu::verifyName($name, $idno);
    }

    /**
     * 验证照片与身份证号
     */
    public static function verifyPhoto($name, $idno, $base64ImageString)
    {
        return Rongshu::verifyPhoto($name, $idno, $base64ImageString);
    }

}
