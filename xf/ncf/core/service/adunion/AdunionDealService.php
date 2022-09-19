<?php
/**
 * AdunionDealService.php
 * @date 2014-09-15
 * @author wangshijie@ucfgroup.com
 */

namespace core\service\adunion;

use core\dao\adunion\AdunionDealModel;
use core\service\deal\DealService;
use core\service\user\UserService;
use libs\utils\Logger;
use core\service\BaseService;
use NCFGroup\Common\Library\ApiService;

/**
 * Class AdunionDealService
 * @package core\service
 */
class AdunionDealService extends BaseService {

    //供M站重构，api使用
    public $euid = '';

    /**
     * 需要记录的信息
     */
    private $records = array('1' => '注册', '2' => '身份认证', '3' => '绑定银行卡', '4' => '投资', '5' => '登录');

    /**
     * @desc 获取广告订单信息
     *
     * @param $union_id
     * @param $stime
     * @param $etime
     * @param $sn
     *
     * @return array
     */
    public function getAdDealList($cn, $stime, $etime, $sn) {
        $list = array();
        if ($stime) {
            $stime = date('Y-m-d H:i:s', $stime);
        }
        if ($etime) {
            $etime = date('Y-m-d H:i:s', $etime);
        }
        $list = AdunionDealModel::instance()->get_order_by_cn($cn, $stime, $etime, $sn);

        return $list;
    }

    /**
     * @desc 插入或者更新追踪信息
     *
     * @param int $uid
     * @param array $data
     */
    private function updateAdDeal($uid, $data) {
        $res = AdunionDealModel::instance()->findBy("uid=:uid AND order_sn='' limit 1", 'id, euid', array(':uid' => intval($uid)));

        $id = 0;
        if (!empty($res)) {
            $id = $res->id;
            if ($res->euid) {
                $data['euid'] = $res->euid;
            }
        }

        $data['uid'] = $uid;
        $data['updated_at'] = date('Y-m-d H:i:s');
        AdunionDealModel::instance()->update_order($data, intval($id));
    }

    /**
     * @desc 记录广告联盟相关信息
     *
     * @param $uid int
     * @param $type int
     * @param $deal_id int
     * @param $load_id int
     * @param $money deceil
     * @param $order_channel int
     *
     * @return bool
     */
    public function triggerAdRecord($uid, $type, $deal_id = 0, $load_id = 0, $money = 0.00, $order_channel = 0, $coupon = '', $ceuid = '', $ctrack_id=0) {
        Logger::info("广告联盟数据落单, 数据:" . json_encode(func_get_args()));

        $params = array(
            'uid'=>$uid,
            'type'=>$type,
            'deal_id'=>$deal_id,
            'load_id'=>$load_id,
            'money'=>$money,
            'order_channel'=>$order_channel,
            'coupon'=>$coupon,
            'ceuid'=>$ceuid,
            'ctrack_id'=>$ctrack_id
        );

        return ApiService::rpc('ncfwx', 'ncfph/triggerAdRecord', $params);
    }

    public function getRegistEuidByUids($uids) {
        if (empty($uids)) {
            return array();
        }

        $uidsStr = implode(', ', array_keys($uids));
        $res = AdunionDealModel::instance()->findAllViaSlave("uid IN({$uidsStr}) AND is_new_custom = 1", true, 'euid, uid', array());
        if (empty($res)) {
            return array();
        }

        $map = array();
        foreach ($res as $item) {
            $map[$item['uid']] = $item['euid'];
        }
        return $map;
    }

    /**
     * @根据用户Id，邀请码获取广告联盟的用户信息
     * @param int $userId
     * @param string $invtionCode
     * @param int $type
     * @return array
     */
    public function getUserAdUnionInfo($userId, $invtionCode)
    {
        $res = AdunionDealModel::instance()->findBy("uid=:uid And cn = ':cn' limit 1", 'euid', array(':uid' => $userId, ':cn' => $invtionCode));
        return $res;
    }

    //通过uid获取用户的euid,注册或者投资的第一条记录
    public function getEuidByUid($uid) {
        if (empty($uid)) {
            return '';
        }
        $res = AdunionDealModel::instance()->findAllViaSlave("uid = {$uid} AND euid != '' order by id desc limit 1", true, 'euid', array());
        if (empty($res)) {
            return '';
        }
        return $res[0]['euid'];
    }


}
