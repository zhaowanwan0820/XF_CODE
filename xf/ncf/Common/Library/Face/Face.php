<?php

namespace NCFGroup\Common\Library\Face;

use NCFGroup\Common\Library\CommonLogger;
use NCFGroup\Common\Library\Idno\Idno;
use NCFGroup\Common\Library\Face\Providers\Yitu as FaceProvider;

/**
 * 活体检测
 */
class Face
{

    /**
     * 活体检测
     */
    public static function livenessDetect($packageData)
    {
        return FaceProvider::livenessDetect($packageData);
    }

    /**
     * 人脸比对 (包括活体检测)
     */
    public static function faceImageVerify($name, $idno, $packageData)
    {
        $result = self::livenessDetect($packageData);
        return Idno::verifyPhoto($name, $idno, $result['query_image_package_result']['query_image_contents'][0]);
    }

}
