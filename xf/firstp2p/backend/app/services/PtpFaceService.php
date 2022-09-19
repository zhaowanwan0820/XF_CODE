<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use core\service\face\FaceService;
use NCFGroup\Protos\Ptp\RPCErrorCode;

class PtpFaceService extends ServiceBase {

    public function faceRecognize($request) {
        $params = $request->getParamArray();
        if (empty($params)) {
            return false;
        }
        $package = $params['package'];
        $idno = $params['idno'];
        $name = $params['name'];

        $service = new FaceService();

        return $service->faceRecognize($package, $idno, $name);
    }
}
