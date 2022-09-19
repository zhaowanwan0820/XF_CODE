<?php

namespace NCFGroup\Ptp\services;

use core\service\vip\VipService;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestGetVipUserList;
use NCFGroup\Protos\Ptp\RequestGetVipInfo;
use NCFGroup\Protos\Ptp\ResponseGetVipUserList;
use NCFGroup\Protos\Ptp\ResponseGetVipInfo;


class PtpVipService extends ServiceBase
{
    public function acquireVipPoint(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();

        $userId     = intval($params['userId']);
        $point      = intval($params['point']);
        $token      = $params['token'];
        $sourceType = $params['sourceType'];
        $sourceId   = $params['sourceId'];
        $info       = $params['info'];

        return (new VipService())->updateVipPoint($userId, $point, $sourceType, $token, $info, $sourceId);
    }

    public function getVipInfoByUserId(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        $userId = intval($params['userId']);
        $vipService = new VipService();
        $vipInfo = $vipService->getVipInfoForCC($userId);
        return $vipInfo;
    }

    /**
     * getVipInfo返回格式化的vip信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-08
     * @param RequestGetVipInfo $request
     * @access public
     * @return void
     */
    public function getVipInfo(RequestGetVipInfo $request) {
        $userId = intval($request->getUserId());
        $vipService = new VipService();
        $vipInfo = $vipService->getFormatVipInfo($userId);

        $firstDesc = $secondDesc = '';
        $secondDesc = ($vipInfo['expirePoint'] ? '即将过期'.$vipInfo['expirePoint'] : "");
        if ($vipInfo['isRelegated']) {
            $firstDesc = '保级中,'. ceil($vipInfo['remainRelegatedTime']/86400).'天后结束保级并降为'.$vipInfo['actualGradeName'];
            $secondDesc = '还需'.$vipInfo['remainRelegatedPoint'].'经验值免降级';
        } else if ($vipInfo['nextGradeName']) {
            $firstDesc = '还需'.$vipInfo['upgradePoint'].'经验值升级为'.$vipInfo['nextGradeName'];
        }
        $response = new ResponseGetVipInfo();
        $response->setGradeName($vipInfo['gradeName']);
        $response->setImgUrl($vipInfo['imgUrl']);
        $response->setPoint(intval($vipInfo['point']));
        $response->setFirstDesc($firstDesc);
        $response->setSecondDesc($secondDesc);
        return $response;
    }

    /**
     * getVipUserList返回批量用户vip等级
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-01-08
     * @param RequestGetUserInvestList $request
     * @access public
     * @return void
     */
    public function getVipUserList(RequestGetVipUserList $request) {
        $userIds = $request->getUserIds();
        $userIds = explode(',', $userIds);

        $vipService = new VipService();
        $vipUsers = $vipService->getVipUserList($userIds);
        $response = new ResponseGetVipUserList();
        $response->setList($vipUsers);
        return $response;
    }
}
