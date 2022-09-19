<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use libs\idno\CommonIdnoVerify;
use libs\utils\Logger;
use core\service\face\OcrService;

class PtpIdnoService extends ServiceBase {

    public function checkIdno($request) {
        $params = $request->getParamArray();
        if (empty($params) || !isset($params['name']) || !isset($params['idno'])) {
            Logger::info(__CLASS__ . "checkIdno params error");
            return false;
        }
        $name = $params['name'];
        $idno = $params['idno'];

        Logger::info(__CLASS__ . ",name:{$name},idno:" . idnoNewFormat($idno));

        $obj = new CommonIdnoVerify();

        return $obj->checkIdno($name, $idno);
    }

    /**
     * 自动识别身份证信息
     * @param $request
     * @return array
     */
    public function idCardOcr($request) {
        $params = $request->getParamArray();
        if (empty($params) || !isset($params['image'])) {
            Logger::info(__CLASS__ . "idCardOcr params error");
            return false;
        }
        $image = $params['image'];
        $mode = $params['mode'];
        if(empty($mode)) {
            $mode = 3; //auto 自动区分身份证正面背面双面
        }

        $service = new OcrService();

        return $service->idCardOcr($image, $mode);
    }
}
