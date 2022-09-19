<?php
namespace api\controllers\payment;

/**
 * 跳转到银行页面
 * @author longbo
 */
use core\enum\DealEnum;
use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\supervision\SupervisionTransitService;
use core\service\account\AccountService;
use libs\utils\Logger;
use libs\utils\Risk;
use core\service\bonus\BonusService;

class Transit extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required'),
            'site_id' => array('filter' => 'int'),
            'params' => array('filter' => 'string'),
            'accountType' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            if (!$this->isWapCall()) {
                $result = array('error' => '参数错误');
                $this->display($result);
                return false;
            }

            $this->setErr('ERR_PARAMS_VERIFY_FAIL');
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->user;
        $site_id = $data['site_id'];
        $params = isset($data['params']) ? json_decode(stripslashes($data['params']), true) : [];
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $user['id'], $user['user_purpose'], 'data:' . var_export($data, true), 'Params:' . var_export($params, true))));
        if (empty($params['srv'])) {
            if (!$this->isWapCall()) {
                $result = array('error' => '缺少srv服务');
                $this->display($result);
                return false;
            }

            $this->setErr(-1, '缺少srv服务');
        }

        try {
            $transitService = new SupervisionTransitService();
            $accountType = isset($params['accountType']) ? (int) $params['accountType'] : $user['user_purpose'];
            $accountId = AccountService::initAccount($user['id'], $accountType);
            $params = $transitService->changeSrv($params, $accountId);
            $params['canUseBonus'] = isset($user['canUseBonus']) ? $user['canUseBonus'] : DealEnum::CAN_USE_BONUS ;
            $params['fingerprint'] = Risk::getFinger();
            // 红包使用总开关
            $isBonusEnable = BonusService::isBonusEnable();
            if (empty($isBonusEnable)){
                Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.__LINE__.' canUseBonus '.$isBonusEnable. ' ' .$user['canUseBonus']);
                $params['canUseBonus'] = false;
            }
            $srv = trim($params['srv']);
            unset($params['srv']);
            if(empty($params['mobileType'])) {
                $params['mobileType'] = '1' . $this->getOs();
            }
            $supervisionRes = $transitService->formFactory($srv, $accountId, $params, 'h5');
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $accountId, 'Error:'.$e->getMessage())));
            $supervisionRes = array();
        }

        if (empty($supervisionRes['status'])) {
            $supervisionRes['status'] = 0;
        }
        $result['status'] = $supervisionRes['status'];
        if ($supervisionRes['status']) {
            $result['form'] = $supervisionRes['form'];
            $result['formId'] = $supervisionRes['formId'];
        } else {
            $msg = '网络错误，请重试';
            $result['msg'] = $msg;
        }
        if (!$this->isWapCall()) {
            $this->display($result);
        }
        $this->json_data = $result;
    }

    private function display(array $data)
    {
        $status = isset($data['status']) ? $data['status'] : 0;
        $form = isset($data['form']) ? $data['form'] : 0;
        $formId = isset($data['formId']) ? $data['formId'] : 0;
        $msg = isset($data['msg']) ? $data['msg'] : '';
        $error = isset($data['error']) ? $data['error'] : '';

        $content = <<<content1
{$form}
<script>document.getElementById("{$formId}").submit();</script>";
content1;

        if ($status != 1) {
            $content = <<<content2
<div class="mod-warp">
    <div class="error-pic">
        <img src="data:image/png;base64,/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAA8AAD/4QN8aHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjMtYzAxMSA2Ni4xNDU2NjEsIDIwMTIvMDIvMDYtMTQ6NTY6MjcgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ9InhtcC5kaWQ6ZmEwOTFlOWUtNjUyMi00NmU0LWE0ODEtNzBmNjBjZmVkY2NmIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjU5OUI1QzIzOEQyMzExRTZBM0I5QzIxRjlCNzNCRDM3IiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjU5OUI1QzIyOEQyMzExRTZBM0I5QzIxRjlCNzNCRDM3IiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAoTWFjaW50b3NoKSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjM0Nzg2MWYxLWI1ODAtNDg1NS1iODE1LTI2NDViNTkxYTU5ZSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpmYTA5MWU5ZS02NTIyLTQ2ZTQtYTQ4MS03MGY2MGNmZWRjY2YiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwv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" alt="错误" width="207" height="230" />
    </div>
    <p class="error-word">{$msg}</p>
    <div class="right-away">
        <a href="storemanager://api?type=closecgpages" id="right-away-btn">返　回</a>
    </div>
</div>
content2;
        }

        $html = <<<payment
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />
    <meta name="format-detection" content="telephone=no">
    <style type="text/css">
    *{padding: 0px; margin:0px;}
    .error-pic {width: 100%;padding-top: 30px;}
    .error-pic img {display: block;margin: 0 auto;}
    .error-word {font-size: 0.8rem;line-height: 1.5rem;padding: 20px;text-align: center;}
    .right-away {width: 17.15rem;margin: 0 auto;}
    .right-away a {display: block;background: #fc8c01;line-height: 2.45rem;color: #FFF;text-align: center;border-radius: 0.2rem; outline: none; text-decoration: none;}
    </style>
    </head>
    <body>
        { $content }
    </body>
</html>
payment;

        echo $html;
        exit;
    }
}

