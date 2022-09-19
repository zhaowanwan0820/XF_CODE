<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\vip\VipService;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\dao\DealLoadModel;

/**
 * VIP信息接口
 */
class VipuserApi extends ApiBackend {
    /**
     * 获取vip等级列表
     * @return array
     */
    public function getVipGradeList() {
        $vipService = new VipService();
        $res = $vipService->getVipGradeList();
        return $this->formatResult($res);
    }

    /**
     * getVipInfo 获取vip信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function getVipInfo() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }
        $vipService = new VipService();
        $res = $vipService->getVipInfo($userId);
        $res = ($res) ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * updateVipPointCallback 更新经验值回调
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function updateVipPointCallback() {
        $param = $this->getParam('param');
        $vipService = new VipService();
        $res = $vipService->updateVipPoint($param['userId'], $param['sourceAmount'], $param['sourceType'], $param['token'], $param['info'], $param['sourceId'], 0, 0, $param['bidAmount']);
        $p2pUserInvest = DealLoadModel::instance()->countByUserId($param['userId']);
        if (isset($param['isFirstInvest']) && $param['isFirstInvest'] && !$p2pUserInvest) {
            //如果p2p是首投，增加首投邀请人经验值&信力
            $sourceAmount = 1;
            $referUserId = $vipService->getReferUserId($param['userId']);
            $sourceType = VipEnum::VIP_SOURCE_INVITE;
            if ($referUserId) {
                $token = $sourceType.'_'.$param['userId'];
                $info = '邀请'.$param['userId'].'首投奖励';
                PaymentApi::log("FirstDeal add vip point|userId|" . $param['userId']."|referUserId|".$referUserId."|token|".$token);
                $vipService->updateVipPoint($referUserId, $sourceAmount, $sourceType, $token, $info, $param['sourceId'], 0, $param['sourceAmount'], $param['bidAmount']);
            }
        }
        return $this->formatResult($res);
    }

    /**
     * getVipInfoAndBidErrMsg 投标时获取vip信息和不符合vip的投标文案
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function getVipInfoAndBidErrMsg() {
        $userId = $this->getParam('userId');
        $bidServiceGrade = $this->getParam('bidServiceGrade');
        $vipService = new VipService();
        $vipInfo = $vipService->getVipInfo($userId);
        $res['vipInfo'] = ($vipInfo) ? $vipInfo->getRow() : array();
        if ($bidServiceGrade) {
            $res['vipBidMsg'] = $vipService->getVipBidErrMsg($bidServiceGrade);
        }
        return $this->formatResult($res);
    }

    /**
     * getVipGrade 获取vip等级详情
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function getVipGrade() {
        $userId = $this->getParam('userId');
        $vipService = new VipService();
        $res = $vipService->getVipGrade($userId);
        $res = !empty($res) ? $res : array();
        return $this->formatResult($res);
    }

    /**
     * isShowVip 是否显示vip信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-08
     * @access public
     * @return void
     */
    public function isShowVip() {
        $userId = $this->getParam('userId');
        $vipService = new VipService();
        $res = $vipService->isShowVip($userId);
        return $this->formatResult($res);
    }

    /**
     * 获取某个vip等级的加息利率
     * @param $vipLevel int 用户的vip等级
     * @return float|bool
     */
    public function getVipInterest() {
        $vipLevel = $this->getParam('vipLevel');
        $vipService = new VipService();
        $res = $vipService->getVipInterest($vipLevel);
        return $this->formatResult($res);
    }

    /**
     * getExpectRebateAndPoint 投资完成后显示预期加息和vip经验值
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-26
     * @access public
     * @return void
     */
    public function getExpectRebateAndPoint() {
        $userId = $this->getParam('userId');
        $annualizedAmount = $this->getParam('annualizedAmount');
        $sourceType = $this->getParam('sourceType');
        $vipService = new VipService();
        $res = $vipService->getExpectRebateAndPoint($userId, $annualizedAmount, $sourceType);
        return $this->formatResult($res);
    }

    /**
     * vipRaiseRate vip加息接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-06
     * @access public
     * @return void
     */
    public function vipRaiseRate() {
        $userId = $this->getParam('userId');
        $bidTime = $this->getParam('bidTime');
        $bidAmount = $this->getParam('bidAmount');
        $annualizedAmount = $this->getParam('annualizedAmount');
        $dealLoadId = $this->getParam('dealLoadId');
        $token = $this->getParam('token');
        $sourceType = $this->getParam('sourceType');
        $vipService = new VipService();
        $res = $vipService->vipRaiseRate($userId, $bidTime, $bidAmount, $annualizedAmount, $dealLoadId, $token, $sourceType);
        return $this->formatResult($res);
    }
}
