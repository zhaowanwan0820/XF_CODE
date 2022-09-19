<?php

namespace core\service\oto;

use core\service\oto\O2ORpcService;
use core\service\CouponService;
use core\service\CouponBindService;
use core\exception\O2OException;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\dao\UserModel;
use NCFGroup\Common\Library\ApiService;

/**
 * 礼券相关服务功能
 */
class O2OCouponService extends O2ORpcService {
    /**
     * 获取用户未来将要过期的礼券个数
     * @param $userId int 用户id
     * @param $elapsedTime int 逝去的时间
     * @return int | false
     */
    public function getUserWillExpireCouponCount($userId, $elapsedTime = 86400) {
        // 参数校验
        if (empty($userId) || !is_numeric($userId) || $userId < 0) {
            return 0;
        }

        try {
            $request = new SimpleRequestBase();
            $request->setParamArray(array('ownerUserId'=>$userId, 'timeElapsed'=>$elapsedTime));
            $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getUserWillExpireCouponCount', $request);
            return intval($response['data']);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, func_get_args());
        }
    }

    /**
     * 获取用户未领取的列表
     */
    public function getUnpickList($userId, $page = 1, $pageSize = 10, $expireStatus = OtoAcquireLogModel::UNPICK_ALL) {

    }

    /**
     * 领取AR红包
     * @param int $userId 用户id
     * @param string $trackId 图片id
     * @param string $trackName 图片名称
     * @return mixed
     */
    public function acquireArCoupon($userId, $trackId, $trackName) {
        // 参数校验
        if (empty($userId) || !is_numeric($userId) || $userId < 0) {
            throw new \Exception('用户id不正确');
        }

        $userInfo = UserModel::instance()->find($userId, 'id, create_time, refer_user_id, group_id');
        if (empty($userInfo)) {
            throw new \Exception('用户不存在');
        }

        $referUserCode = '';
        // 勋章等其他类型的奖励，获取邀请人信息
        $coupon_bind_service = new CouponBindService();
        $coupon_bind = $coupon_bind_service->getByUserId($userId);
        if (!empty($coupon_bind)) {
            $referUserCode = $coupon_bind['short_alias'];
        }

        $request = new SimpleRequestBase();
        $params = array();
        $params['userId'] = $userId;
        $params['trackId'] = $trackId;
        $params['trackName'] = $trackName;
        $params['referUserCode'] = $referUserCode;
        $params['userGroupId'] = $userInfo['group_id'];
        $request->setParamArray($params);
        $response = $this->requestO2O('\NCFGroup\O2O\Services\Coupon', 'acquireArCoupon', $request);
        return $response;
    }

    public function getCouponRewardList($userId, $roleType, $pageNo, $pageSize) {
        $result =  ApiService::rpc("o2o", "coupon/getCouponRewardList", ['userId' => $userId, 'roleType' => $roleType, 'pageNo'=> $pageNo, 'pageSize' => $pageSize]);
        if ($result) {
            foreach($result['data'] as $item) {
                $data['type'] = $item['allowanceType'];
                $data['type_name'] = $item['allowanceTypeDesc'];
                $data['create_time'] = date('Y-m-d H:i:s', $item['createTime']);
                $data['remark'] = $item['extra']['productName'] ?: ($item['allowanceMoney'] ?: '');
                $result['list'][] = $data;
            }
            unset($result['data']);
        }
        return $result;
    }
}
