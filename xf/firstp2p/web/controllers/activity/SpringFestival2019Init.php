<?php
namespace web\controllers\activity;

use web\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Common\Library\ApiService;
use libs\weixin\Weixin;
use core\dao\BonusConfModel;

class SpringFestival2019Init extends BaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = [
            'c' => ['filter' => 'string', 'option' => ['optional' => true]],
            'token' => ['filter' => 'string', 'option' => ['optional' => true]],
        ];
        if (!$this->form->validate()) {
            echo json_encode(['code' => 10000, 'message' => $this->form->getErrorMsg()]);
            return false;
        }
    }


    public function invoke()
    {
        $params = $this->form->data;

        $code = $params['c'] ?: '';

        $token = $params['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', [$token]);
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $currentUid = $GLOBALS['user_info']['id'] ?: 0;
        $data = ApiService::rpc('marketing', 'DealAssistance/info', [
            'code' => $code,
            'currentUid' => $currentUid,
        ]);

        if (ApiService::hasError()) {
            $errorData = ApiService::getErrorData();
            echo json_encode(['code' => $errorData['applicationCode'], 'message' => $errorData['devMessage']]);
            return false;
        }

        // 获取中奖码
        $awardCode = app_conf('CHUNJIE_2019_AWARD_CODE');
        if ($awardCode) $data['awardCode'] = $awardCode;

        // 微信分享数据
        // $appid = app_conf('WEIXIN_APPID');
        // $secret = app_conf('WEIXIN_SECRET');
        $appid = BonusConfModel::get('XINLI_WEIXIN_APPID');
        $secret = BonusConfModel::get('XINLI_WEIXIN_APPSECRET');

        $code = $code ?: $data['code'];
        $shareLink = get_domain() . "/activity/SpringFestival2019Assistance?c={$code}";
        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $nonceStr = md5(time());
        $timeStamp = time();
        $referLink = $_SERVER['HTTP_REFERER'];
        $signature = $weObj->getJsSign($referLink, $timeStamp, $nonceStr, $appid);

        $data['share']['appId'] = $appid;
        $data['share']['timeStamp'] = $timeStamp;
        $data['share']['nonceStr'] = $nonceStr;
        $data['share']['signature'] = $signature;
        $data['share']['link'] = $shareLink;

        $data['isLogin'] = false;
        if ($currentUid > 0) $data['isLogin'] = true;

        if ($data['userId']) {
            $userInfo = $this->rpc->local('UserService\getUser', [$data['userId']]);
            $data['firstName'] = mb_substr($userInfo['real_name'], 0, 1);
        }
        unset($data['userId']);

        echo json_encode(['code' => 0, 'message' => 'OK', 'data' => $data]);

    }

}
