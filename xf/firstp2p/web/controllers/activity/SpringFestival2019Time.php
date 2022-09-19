<?php
namespace web\controllers\activity;

use web\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Common\Library\ApiService;

class SpringFestival2019Time extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {

        $data = ApiService::rpc('marketing', 'DealAssistance/timeInfo', []);
        if (ApiService::hasError()) {
            $errorData = ApiService::getErrorData();
            echo json_encode(['code' => $errorData['applicationCode'], 'msg' => $errorData['devMessage']]);
            die;
        }
        echo json_encode(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

}
