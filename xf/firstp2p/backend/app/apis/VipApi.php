<?php

namespace NCFGroup\Ptp\Apis;

use \core\service\vip\VipService;

/**
 * 信仔vip信息接口
 */
class VipApi
{
    private $params = [];

    private function init()
    {
        $di = getDI();
        $this->params = $di->get('requestBody');
        if (empty($this->params['userId'])) {
            throw new \Exception("缺少参数 userId");
        }
    }

    public function msg() {
        $result = array('errorCode' => 0, 'errorMsg' => '');
        try{
            $this->init();
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }
        $userId = $this->params['userId'];
        $vipService = new VipService();
        $vipInfo = $vipService->getFormatVipInfo($userId);
        $message = $this->getFormatMsg($vipInfo);
        $result['data'] = [
            'type' => 1,
            'title' => "为您找到的会员信息",
            'content' => [
                'title' => $message,
                'columnNum' => 2,
                'actionList' => [
                    [
                        'type' => 1,
                        'imageUrl' => '',
                        'title' => '会员详情',
                        'uri' => '{"type":27}',
                    ],
                    [
                        'type' => 2,
                        'imageUrl' => '',
                        'title' => '经验值说明',
                        'uri' => app_conf('VIP_POINT_DESC_URL'),
                    ],
                ],
            ],
        ];

        return $result;
    }

    private function getFormatMsg($vipInfo) {
        $msg = '您当前会员等级:'.$vipInfo['gradeName']."\n";
        $msg .= '经验值:'.$vipInfo['point']. ($vipInfo['expirePoint'] ? ',即将过期'.$vipInfo['expirePoint']."\n" : "\n");
        if ($vipInfo['isRelegated']) {
            $msg .= '保级中,'. ceil($vipInfo['remainRelegatedTime']/86400).'天后结束保级并降为'.$vipInfo['actualGradeName']."\n";
            $msg .= '还需'.$vipInfo['remainRelegatedPoint'].'经验值免降级';
        } else if ($vipInfo['nextGradeName']) {
            $msg .= '还需'.$vipInfo['upgradePoint'].'经验值升级为'.$vipInfo['nextGradeName'];
        }
        return $msg;
    }

}
