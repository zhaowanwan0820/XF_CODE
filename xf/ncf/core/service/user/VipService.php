<?php

namespace core\service\user;

use core\service\BaseService;

class VipService extends BaseService {
    const VIP_SOURCE_P2P = 'p2p';              //p2p
    private static $funcMap = array(
        'getVipGradeList' => array(),                   // 获取vip等级列表
        'getVipInfo' => array('userId'),                // 通过userId获取vip信息
        'updateVipPointCallback' => array('param'),     //更新vip积分回调
        'getVipInfoAndBidErrMsg' => array('userId', 'bidServiceGrade'),    // 投标时获取vip信息和不符合vip等级的错误信息
        'getVipGrade' => array('userId'),               // 通过userId获取vip信息[含展示的拓展信息]
        'isShowVip' => array('userId'),                 // 是否显示vip信息
        'getVipInterest' => array('vipLevel'),          //获取某个vip等级的加息利率
        'getExpectRebateAndPoint' => array('userId', 'annualizedAmount', 'sourceType'),          //获取投资完成后的预期加息和经验值
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError($name.' method not exist', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        // 用户中心的api接口
        $userCenterApiArr = array('getVipGrade');

        if (in_array($name, $userCenterApiArr)) {
            return self::rpc('user', 'vipuser/'.$name, $args);
        } else {
            return self::rpc('ncfwx', 'vipuser/'.$name, $args);
        }

    }

    /**
     * 更新vip经验值
     * @param $param
     * <code>
     * 'userId' => ,
     * 'sourceAmount' =>,
     *'sourceType' => $sourceType,
     *'token' => $sourceType.'_'.$this->user['id'].'_'.$this->loadId,
     *'info' => $this->deal['name'].",{$this->money}元",
     *'sourceId' => $this->loadId,
     * </code>
     */
    public function jobsUpdateVipPointCallback($param){
        return self::updateVipPointCallback($param);
    }

    /**
     * vipRaiseRate p2p加息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-06
     * @param mixed $userId
     * @param mixed $bidTime
     * @param mixed $bidAmount
     * @param mixed $annualizedAmount
     * @param mixed $dealLoadId
     * @param mixed $token
     * @param mixed $sourceType
     * @access public
     * @return void
     */
    public function vipRaiseRate($userId, $bidTime, $bidAmount, $annualizedAmount, $dealLoadId, $token, $sourceType = self::VIP_SOURCE_P2P) {
        $params = compact('userId', 'bidTime', 'bidAmount', 'annualizedAmount', 'dealLoadId', 'token', 'sourceType');
        return self::rpc('ncfwx', 'vipuser/vipRaiseRate', $params);
    }
}
