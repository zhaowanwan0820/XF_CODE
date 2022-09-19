<?php
/**
 * AdunionDealService.php
 * @date 2014-09-15
 * @author wangshijie@ucfgroup.com
 */

namespace core\service;
use core\dao\AdunionDealModel;
use core\service\DealService;
use core\service\UserTagService;
use libs\utils\Logger;

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
        return true; // 下面的已经废弃

        //邀请码和渠道信息
        $cn = empty($coupon) ? \es_cookie::get('link_coupon') : $coupon;
        $euid = empty($ceuid) ? $this->euid : $ceuid;
        $euid = empty($euid)  ? \es_cookie::get('euid') : $euid;

        //判断是否应该入库 (无渠道时，邀请码必须在列表中)
        if(empty($euid) && (empty($cn) || !in_array(strtoupper($cn), get_adunion_order_coupon()))) {
            return false;
        }

        //入库数据
        $cn = strtoupper($cn);
        $coupon = strtoupper($coupon);
        $data = array(
            'cn'         => $cn,
            'euid'       => AdunionDealModel::escape_string($euid),
            'goods_cn'   => $coupon,
            'goods_type' => $cn == $coupon,
            'track_id'   => 0,
        );

        switch($type) {
            case 1:
                $data['is_new_custom'] = 1;
                $reg_invite_code = \es_session::get('ad_invite_code');
                if($reg_invite_code) {
                    $data['cn'] = $reg_invite_code;
                }
                break;
            case 2:
            case 3:
            case 5://登录
                break;
            case 4:
                $deal_id = intval($deal_id);
                if (!$deal_id) {
                    return false;
                }
                $deal_service = new DealService();
                $deal_info = $deal_service->getDeal($deal_id);
                if ($deal_info['deal_type'] == 1) { //去掉通知贷
                    return false;
                }
                $tag_service = new UserTagService();
                $bidMore = $tag_service->getTagByConstNameUserId('BID_MORE', $uid);
                $data['mid'] = $deal_id;
                $data['order_sn'] = $load_id;
                $data['goods_id'] = $load_id;
                $data['order_channel'] = $order_channel;
                $data['goods_name'] = $deal_info['name'];
                $data['order_time'] = date("Y-m-d H:i:s");
                $data['total_price'] = $data['goods_price'] = $money;
                $data['goods_ta'] = empty($bidMore) ? 2 : 3 ; //1 为历史数据了不修复， 2为首投 3 为复投
                $data['goods_cate'] = $deal_info['type_id'];
                $data['days'] = intval($deal_info['repay_time']);
                if ($deal_info['loantype'] != 5) {
                    $data['days'] = $data['days'] * 30;
                }
                break;
            default:
                return false;
        }

        $data['status'] = $this->records[$type];
        $this->updateAdDeal($uid, $data);

        //营销分级开关
        if (!app_conf('MINNIE_SWITCH')) {
            return true;
        }

        //营销分级邀请码
        $minnieCoupon = array_map('strtoupper', array_map('trim', explode(',', app_conf('MINNIE_COUPON'))));
        if (in_array($cn, $minnieCoupon)) { //营销分级
            $data['uid'] = $uid;
            if ($data['total_price'] > 0) { //投资时候捞取注册euid
                $uidEuidMap = $this->getRegistEuidByUids([$uid => 1]);
                if (!empty($uidEuidMap[$uid])) {
                    $data['euid'] = $uidEuidMap[$uid];
                }
            }

            $data['deal_info'] = empty($deal_info) ? array() : $deal_info->getRow();
            $service = new \core\service\OpenService();
            $service->doBackGroundTask(\core\event\OpenEvent::ADUNION_DEAL, $data);
        }
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

    public function addAdRecord($params) {
        Logger::info("获取到注册消息, 数据:" . json_encode($params));

        $coupon = strtoupper(trim(@$params['invite_code']));
        $ctime  = sprintf("%s", date('Y-m-d H:i:s', $params['create_time'] + 8 * 60 * 60));
        $euid   = trim(@$params['euid']);
        $userId = intval($params['user_id']);

        $data = [
            'cn' => $coupon,
            'euid' => $euid,
            'goods_cn' => $coupon,
            'goods_type' => 1,
            'track_id' => 0,
            'is_new_custom' => 1,
            'status' => '注册',
            'uid' => $userId,
            'updated_at' => $ctime,
            'order_time' => $ctime,
            'created_at' => $ctime,
        ];

        return AdunionDealModel::instance()->update_order($data);
    }

}
