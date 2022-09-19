<?php
/**
 * 网信出行Service
 * @date 20171017
 */
namespace core\service\life;

use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Life\RequestCommon;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Protos\Life\Enum\ErrorCode;
use NCFGroup\Protos\Life\Enum\CommonEnum;
use NCFGroup\Protos\Life\Enum\TripEnum;
use libs\utils\Logger;
use libs\utils\ABControl;
use core\exception\LifeException;
use core\service\vip\VipService;
use core\service\life\LifeRpcService;
use core\service\life\PaymentUserService;
use core\dao\UserModel;

class UserTripService extends LifeRpcService {
    // 总白名单是否开启
    private static $isTripOpen = null;
    // 出行白名单是否开启
    private static $isTripList = null;
    // 理财师白名单是否开启
    private static $isFmList = null;

    /**
     * 总白名单
     * @param int $userId 用户ID
     * @return boolean
     */
    public static function isTripOpen() {
        if (self::$isTripOpen === null) {
            self::$isTripOpen = false;
            if(self::isTripList()) {
                self::$isTripOpen = true;
            }else if(self::isFmList()) {
                self::$isTripOpen = true;
            }
        }
        return self::$isTripOpen;
    }

    /**
     * 出行白名单
     * @return boolean
     */
    public static function isTripList() {
        if (self::$isTripList === null) {
            self::$isTripList = false;
            if(ABControl::getInstance()->hit(CommonEnum::ABCONTROL_TRIP_LIST)) {
                self::$isTripList = true;
            }
        }
        return self::$isTripList;
    }

    /**
     * 理财师白名单
     * @return boolean
     */
    public static function isFmList() {
        if (self::$isFmList === null) {
            self::$isFmList = false;
            if(ABControl::getInstance()->hit(CommonEnum::ABCONTROL_FM_LIST)) {
                self::$isFmList = true;
            }
        }
        return self::$isFmList;
    }

    /**
     * 根据网信出行订单号，获取用户出行订单
     * @param int $outOrderId 订单号
     * @param string $merchantId 商户编号
     * @return array
     */
    public function getUserTripByOutOrderId($outOrderId, $merchantId) {
        try {
            if (empty($outOrderId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId' => $outOrderId,
                'merchantId' => $merchantId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getUserTripByOutOrderId', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['outOrderId'=>$outOrderId, 'merchantId'=>$merchantId]);
        }
    }

    /**
     * 根据用户ID，获取用户所有的出行订单列表
     * @param int $userId 用户ID
     * @param int $merchantId 商户编号
     * @param int $status 订单状态(1:进行中订单2:尚未支付或支付失败)
     * @param int $page
     * @param int $count
     * @return array
     */
    public function getMyUserTripList($userId, $merchantId, $status = 0, $page = 1, $count = 10) {
        try {
            // 参数检查
            if (empty($userId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
                'merchantId' => addslashes($merchantId),
                'status' => (int)$status,
                'page' => (int)$page,
                'count' => (int)$count,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getOrders', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 根据用户ID，获取用户出行订单详情
     * @param int $userId 用户ID
     * @param int $outOrderId 出行主订单号
     * @param int $merchantId 商户编号
     * @return array
     */
    public function getMyUserTripDetail($userId, $outOrderId, $merchantId) {
        try {
            // 参数检查
            if (empty($userId) || empty($outOrderId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
                'outOrderId' => (int)$outOrderId,
                'merchantId' => addslashes($merchantId),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getOrderShow', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'outOrderId'=>$outOrderId, 'merchantId'=>$merchantId]);
        }
    }

    /**
     * 获取出行价格的折扣系数
     * @param int $userId 用户ID
     * @return array
     */
    public function getDiscountInfo($userId) {
        try {
            if (empty($userId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($userId, 'id,user_name,mobile,group_id', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 获取用户VIP等级
            $vipService = new VipService();
            $vipAccountInfo = $vipService->getVipInfo($userId);

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => $userBaseInfo['id'],
                'groupId' => (int)$userBaseInfo['group_id'],
                'vipAccountInfo' => !empty($vipAccountInfo) ? $vipAccountInfo->getRow() : [],
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getDiscountInfo', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 查询用户的红包详情
     * @param string $awardCode 红包token
     * @return array
     */
    public function getUserBonusDetail($awardCode) {
        try {
            if (empty($awardCode)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'awardCode' => addslashes($awardCode),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getUserBonusDetail', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 获取优惠券列表
     * @param int $userId 用户ID
     * @return array
     */
    public function getCouponList($userId) {
        try {
            if (empty($userId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($userId, 'id,user_name,mobile', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            $res = [];
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 获取网信生活配置信息列表
     * @return array
     */
    public function getLifeConfList() {
        try {
            // 请求RPC服务
            $request = new RequestCommon();
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getLifeConfList', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['name'=>$name, 'convert'=>$convert]);
        }
    }

    /**
     * 获取网信生活配置信息
     * @param string $name 配置键值
     * @param string $convert 是否转换为数组
     * @return array
     * @example
     *  'errorCode' => int 0
     *  'errorMsg' => string 'success' (length=7)
     *  'data' => string '1800' (length=4)
     */
    public function getLifeConf($name, $convert = 0) {
        try {
            if (empty($name)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'name' => addslashes($name),
                'convert' => (int)$convert,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getLifeConf', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['name'=>$name, 'convert'=>$convert]);
        }
    }

    /**
     * 保存网信生活配置信息
     * @param array $vars 参数数组
     * @return array
     */
    public function saveLifeConf($vars) {
        try {
            if (empty($vars['title']) || empty($vars['name']) || strlen($vars['value']) <= 0) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'saveLifeConf', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $vars);
        }
    }

    /**
     * 删除网信生活配置信息
     * @param string $name 配置键值
     * @return array
     */
    public function deleteLifeConf($name) {
        try {
            if (empty($name)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $request->setVars(['name'=>addslashes($name)]);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'deleteLifeConf', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $vars);
        }
    }

    /**
     * 进入出行首页，需要进行风控检查
     * @return array
     */
    public function checkRiskRule($params) {
        try {
            // 参数检查
            if (empty($params['merchantId']) || empty($params['userId'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($params['userId'], 'id,user_name,real_name,mobile,mobile_code,group_id,idcardpassed', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }
            // 实名认证
            if ($userBaseInfo['idcardpassed'] != 1) {
                LifeException::exception(ErrorCode::WXCX_USER_HASNOT_IDPASS);
            }
            // 用户真实姓名不能为空
            if (empty($userBaseInfo['real_name'])) {
                LifeException::exception(ErrorCode::WXCX_USER_HASNOT_IDPASS);
            }
            if (empty($userBaseInfo['mobile'])) {
                LifeException::exception(ErrorCode::WXCX_USER_MOBILE_EMPTY);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId'      => addslashes($params['merchantId']), // 商户编号
                'userId'          => $userBaseInfo['id'], // 用户ID
                'mobileCode'      => $userBaseInfo['mobile_code'], // 国家码
                'mobile'          => $userBaseInfo['mobile'], // 订车人手机号
                'groupId'         => $userBaseInfo['group_id'], // 用户组ID
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'checkRiskRule', $request);

            if (isset($res['data']['errmsg'])) {
                unset($res['data']['errmsg']);
            }
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 创建出行订单
     * @param array $params 参数数组
     * estimateAmount：预估费用,单位分
     * userId：订车人ID
     * userName：订车人姓名
     * mobile：订车人手机号
     * @return array
     */
    public function createOrder($params) {
        try {
            // 参数检查
            if (empty($params['merchantId']) || empty($params['userId'])
                || empty($params['cityCode']) || empty($params['serviceType'])
                || empty($params['carTypeId']) || empty($params['passengerPhone'])
                || !is_numeric($params['passengerPhone']) || empty($params['passengerName'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($params['userId'], 'id,user_name,real_name,mobile,mobile_code,group_id,idcardpassed', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }
            // 实名认证检查
            if ($userBaseInfo['idcardpassed'] != 1) {
                LifeException::exception(ErrorCode::WXCX_USER_HASNOT_IDPASS);
            }
            // 用户真实姓名不能为空
            if (empty($userBaseInfo['real_name'])) {
                LifeException::exception(ErrorCode::WXCX_USER_HASNOT_IDPASS);
            }
            if (empty($userBaseInfo['mobile'])) {
                LifeException::exception(ErrorCode::WXCX_USER_MOBILE_EMPTY);
            }

            // 获取用户VIP等级
            $vipService = new VipService();
            $vipAccountInfo = $vipService->getVipInfo($params['userId']);

            // 乘客手机号
            $passerPhone = isset($params['passengerPhone']) ? addslashes($params['passengerPhone']) : '';
            // 是否本人乘车
            $isSelf = 1; // 本人叫车
            if (strcmp($userBaseInfo['mobile'], $passerPhone) !== 0) {
                $isSelf = 0; // 为他人叫车
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId'         => addslashes($params['merchantId']), // 商户编号
                'userId'             => $userBaseInfo['id'], // 用户ID
                'groupId'            => $userBaseInfo['group_id'], // 用户组ID
                'userName'           => $userBaseInfo['real_name'], // 订车人姓名
                'userPhoneArea'      => $userBaseInfo['mobile_code'],
                'userPhone'          => $userBaseInfo['mobile'], // 订车人手机号
                'vipGrade'           => !empty($vipAccountInfo) ? (int)$vipAccountInfo['service_grade'] : VipEnum::VIP_GRADE_PT,
                'cityCode'           => (int)$params['cityCode'], // 城市编码
                'serviceType'        => (int)$params['serviceType'], // 服务类型
                'carTypeId'          => (int)$params['carTypeId'], // 车辆级别
                'isSelf'             => (int)$isSelf, // 是否本人叫车
                'passengerPhoneArea' => isset($params['passengerPhoneArea']) ? (int)$params['passengerPhoneArea'] : 86, //乘客手机区号
                'passengerPhone'     => $passerPhone, // 乘客手机号
                'passengerName'      => isset($params['passengerName']) ? addslashes($params['passengerName']) : $userBaseInfo['real_name'], // 乘车人姓名
                'estimateAmount'     => isset($params['estimateAmount']) ? (int)$params['estimateAmount'] : 0, // 预估费用,单位分
                'estimateMileage'    => !empty($params['estimateMileage']) ? addslashes($params['estimateMileage']) : '', // 预估里程
                'estimateTime'       => isset($params['estimateTime']) ? (int)$params['estimateTime'] : 0, // 预估行驶时间,单位分钟
                'fromAddress'        => isset($params['fromAddress']) ? $params['fromAddress'] : '', // 出发地地址
                'fromLongitude'      => isset($params['fromLongitude']) ? addslashes($params['fromLongitude']) : '', // 出发地经度坐标
                'fromLatitude'       => isset($params['fromLatitude']) ? addslashes($params['fromLatitude']) : '', // 出发地纬度坐标
                'toAddress'          => isset($params['toAddress']) ? $params['toAddress'] : '', // 目的地地址
                'toLongitude'        => isset($params['toLongitude']) ? addslashes($params['toLongitude']) : '', // 目的地经度坐标
                'toLatitude'         => isset($params['toLatitude']) ? addslashes($params['toLatitude']) : '', // 目的地纬度坐标
                'bookTime'           => isset($params['bookTime']) ? addslashes($params['bookTime']) : date('Y-m-d H:i:s'), // 乘车时间
                'flightNo'           => isset($params['flightNo']) ? addslashes($params['flightNo']) : '', // 航班号
                'msgBoard'           => isset($params['msgBoard']) ? addslashes($params['msgBoard']) : '', // 客户留言
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\TripOrder', 'createOrder', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, (!empty($res['data']) ? $res['data'] : $params));
        }
    }

    /**
     * 创建出行发票订单
     * @param array $params 参数数组
     * estimateAmount：预估费用,单位元
     * userId：订车人ID
     * userName：订车人姓名
     * mobile：订车人手机号
     * @return array
     */
    public function createInvoice($params) {
        try {
            // 参数检查
            if (empty($params['merchantId']) || empty($params['outOrderId'])
                || empty($params['userId']) || empty($params['amount'])
                || empty($params['title']) || empty($params['userName'])
                || empty($params['phone']) || !is_numeric($params['phone'])
                || empty($params['address']) || empty($params['invoiceType'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 发票类型为企业发票时，需要检查纳税人识别号
            if ($params['invoiceType'] == TripEnum::INVOICE_TYPE_ENTERPRISE && empty($params['companyCode'])) {
                LifeException::exception(ErrorCode::WXCX_INVOICE_COMPANYCODE_EMPTY);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId'  => addslashes($params['merchantId']), // 商户编号
                'outOrderId'  => addslashes($params['outOrderId']), // 出行订单号，多个以逗号隔开
                'userId'      => (int)$params['userId'], // 用户ID
                'amount'      => (int)$params['amount'], // 所开发票金额,单位分
                'invoiceType' => (int)$params['invoiceType'], // 发票类型(1:个人2:企业) 
                'typeId'      => !empty($params['typeId']) ? (int)$params['typeId'] : TripEnum::TYPEID_PASSENGER_SERVICE, // 发票内容(1:客运服务费)
                'title'       => addslashes($params['title']), // 发票抬头
                'userName'    => addslashes($params['userName']), // 签收人姓名
                'phone'       => addslashes($params['phone']), // 签收人联系电话
                'province'    => isset($params['province']) ? addslashes($params['province']) : '', // 邮寄所在省
                'city'        => isset($params['city']) ? addslashes($params['city']) : '', // 邮寄所在城市
                'area'        => isset($params['area']) ? addslashes($params['area']) : '', // 邮寄所在区
                'address'     => addslashes($params['address']), // 邮寄地址
                'companyCode' => !empty($params['companyCode']) ? addslashes($params['companyCode']) : '', // 纳税人识别号
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInvoice', 'doCreateInvoice', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 根据用户ID，获取用户发票行程列表
     * @param int $userId 用户ID
     * @param string $merchantId 商户编号
     * @param int $page
     * @param int $count
     * @return array
     */
    public function getInvoiceTripList($userId, $merchantId, $page = 1, $count = 10) {
        try {
            // 参数检查
            if (empty($userId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId'     => (int)$userId,
                'merchantId' => addslashes($merchantId),
                'page'       => (int)$page,
                'count'      => (int)$count,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInvoice', 'getInvoiceListByTrip', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'merchantId'=>$merchantId]);
        }
    }

    /**
     * 根据用户ID，获取用户开发票历史列表
     * @param int $userId 用户ID
     * @param string $merchantId 商户编号
     * @param int $page
     * @param int $count
     * @return array
     */
    public function getInvoiceHistoryTripList($userId, $merchantId, $page = 1, $count = 10) {
        try {
            // 参数检查
            if (empty($userId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId'     => (int)$userId,
                'merchantId' => addslashes($merchantId),
                'page'       => (int)$page,
                'count'      => (int)$count,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInvoice', 'getInvoiceHistoryList', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'merchantId'=>$merchantId]);
        }
    }

    /**
     * 根据用户ID，获取用户发票详情
     * @param int $userId 用户ID
     * @param int $invoiceId 发票自增ID
     * @return array
     */
    public function getInvoiceDetail($userId, $invoiceId) {
        try {
            // 参数检查
            if (empty($userId) || empty($invoiceId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId'     => (int)$userId,
                'invoiceId'  => (int)$invoiceId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInvoice', 'invoiceDetail', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 根据用户ID，获取用户默认的邮寄地址
     * @param int $userId 用户ID
     * @return array
     */
    public function getDefaultAddress($userId) {
        try {
            // 参数检查
            if (empty($userId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户默认的邮寄地址，为空则获取邮寄列表第一条
            $service = new \core\service\AddressService();
            $result = $service->getDefault((int)$userId);
            if (empty($result)) {
                $list = $service->getList((int)$userId);
                if(!empty($list)) {
                    $result = $list[0];
                }
            }
            $data = ['errorCode'=>0, 'errorMsg'=>'', 'data'=>[]];
            if (!empty($result)) {
                $data['data']['id']        = $result['id'];
                $data['data']['isDefault'] = $result['is_default'];
                $data['data']['consignee'] = $result['consignee'];
                $data['data']['mobile']    = $result['mobile'];
                $data['data']['area']      = $result['area'];
                $data['data']['address']   = $result['address'];
            }
            return $this->_handleResponse($data);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 取消出行订单
     * @param array $params 参数数组
     * merchantId：商户编号
     * userId：用户ID
     * outOrderId：出行订单号
     * cancelType：取消类型(1:用户取消 2:系统取消)
     * tryCancel：尝试取消(0:正式取消 1:尝试取消)
     * cancelReason：取消原因(1:行程有变2:距离太远)，非必传
     * cancelDesc：取消原因描述，非必传
     * @return array
     */
    public function cancelOrder($params) {
        try {
            // 参数检查
            if (empty($params['merchantId']) || empty($params['userId'])
                || empty($params['outOrderId']) || !isset($params['tryCancel'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId' => addslashes($params['merchantId']), // 商户编号
                'userId'     => (int)$params['userId'], // 用户ID
                'outOrderId' => (int)$params['outOrderId'], // 出行订单号
                'cancelType' => isset($params['cancelType']) ? (int)$params['cancelType'] : TripEnum::TRIP_CANCEL_TYPE_USER_CANCEL, // 取消类型(1:用户取消 2:系统取消)
                'tryCancel'  => (int)$params['tryCancel'], // 尝试取消(0:正式取消 1:尝试取消)
            );
            isset($params['cancelRule']) && $vars['cancelRule'] = (int)$params['cancelRule']; // 取消规则(0:通用取消规则 1:自有业务取消规则)
            isset($params['cancelReason']) && $vars['cancelReason'] = (int)$params['cancelReason']; // 取消原因
            !empty($params['cancelDesc']) && $vars['cancelDesc'] = addslashes($params['cancelDesc']); // 取消原因描述
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'cancelOrder', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取取消规则
     * @return array
     */
    public function getCancelRule() {
        try {
            // 请求RPC服务
            $request = new RequestCommon();
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'cancelOrderRule', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 出行下单回调逻辑
     * @param array $params 参数数组
     * order_no：第三方平台订单号
     * out_trade_id：网信出行订单号
     * create_status：下单结果(0:失败 1:成功)
     * create_msg：结果描述
     * @return array
     */
    public function orderNotify($params) {
        try {
            // 参数检查
            if (empty($params['order_no']) || empty($params['out_trade_id']) || !isset($params['create_status'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            !isset($params['platform']) && $params['platform'] = CommonEnum::PLATFORM_AAZC;
            $request->setVars($params);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'orderNotify', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 出行发票回调逻辑
     * @param array $params 参数数组
     * invoice_id：第三方平台发票订单号
     * status：发票状态
     * express_company：快递公司
     * express_no：快递单号
     * @return array
     */
    public function invoiceNotify($params) {
        try {
            // 参数检查
            if (empty($params['invoice_id']) || !isset($params['status'])
                || empty($params['express_company']) || empty($params['express_no'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            !isset($params['platform']) && $params['platform'] = CommonEnum::PLATFORM_AAZC;
            $request->setVars($params);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'invoiceNotify', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 出行订单状态回调逻辑
     * @param array $params 参数数组
     * order_no：第三方平台订单号
     * out_trade_id：网信出行订单号
     * order_status：订单状态
     * @return array
     */
    public function statusNotify($params) {
        try {
            // 参数检查
            if (empty($params['order_no']) || empty($params['out_trade_id'])
                || !isset($params['order_status']) || !is_numeric($params['order_status'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            !isset($params['platform']) && $params['platform'] = CommonEnum::PLATFORM_AAZC;
            $request->setVars($params);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'statusNotify', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 创建业务消费子订单，创建网信支付订单-用户主动支付
     * @param array $params 参数数组
     * outOrderId：出行订单号,多个以逗号隔开
     * userId：订车人ID
     * merchantId：管理后台给AA开通的商户编号
     * amount：实付金额，单位：分
     * goodsName：商品名称，非必填
     * returnUrl：同步通知地址，非必填
     * shouldAmount：应付金额，单位：分，非必填
     * discountAmount：优惠金额，单位：分，非必填
     * cardType：银行卡类型(0:不限制1:储蓄卡2:信用卡)，非必填
     * @return array
     */
    public function createBusinessOrder($params) {
        try {
            // 参数检查
            if (empty($params['outOrderId']) || empty($params['userId'])
                || empty($params['merchantId']) || empty($params['amount'])) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($params['userId'], 'id,user_name,real_name', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 支付成功后的同步回调地址
            $returnUrl = !empty($params['returnUrl']) ? addslashes($params['returnUrl']) : '';
            // 优惠金额，单位：分
            $discountAmount = !empty($params['discountAmount']) ? (int)$params['discountAmount'] : 0;
            // 应付金额，单位：分
            $shouldAmount = !empty($params['shouldAmount']) ? (int)$params['shouldAmount'] : (int)$params['amount'];
            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId'     => addslashes($params['outOrderId']), // 消费主订单号，逗号分隔
                'merchantId'     => addslashes($params['merchantId']), // 管理后台给AA开通的商户编号
                'userId'         => $userBaseInfo['id'], // 用户ID
                'goodsName'      => !empty($params['goodsName']) ? addslashes($params['goodsName']) : TripEnum::TRIP_GOODS_NAME, // 商品名称
                'amount'         => (int)$params['amount'],
                'returnUrl'      => $returnUrl, // 同步通知地址
                'goodsDesc'      => !empty($params['goodsDesc']) ? addslashes($params['goodsDesc']) : '', // 商品描述
                'shouldAmount'   => (int)$shouldAmount,
                'discountAmount' => (int)$discountAmount,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'createBusinessOrder', $request);
            if (!isset($res['errorCode']) || $res['errorCode'] != 0) {
                throw new \Exception($res['errorMsg'], $res['errorCode']);
            }

            // 获取用户付款方式的第一条记录
            if (isset($res['errorCode']) && $res['errorCode'] == 0) {
                $paymentObj = new PaymentUserService();
                $payCardList = $paymentObj->getMyPayCardList($params['userId'], $params['merchantId'], 1);
                $res['data']['cardInfo'] = !empty($payCardList['cardList']) ? array_shift($payCardList['cardList']) : [];
                $res['data']['cardTips'] = $payCardList['cardTips'];
            }
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 检测用户授权状态
     * @param int $userId 用户ID
     * @return array
     */
    public function checkUserAuthStatus($userId) {
        try {
            // 参数检查
            if (empty($userId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInfo', 'checkUserAuthStatus', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 检查用户对出行是否授权
     * @param int $userId 用户ID
     * @param string $merchantId 商户编号
     * @return array
     */
    public function queryUserAuth($userId, $merchantId) {
        try {
            // 参数检查
            if (empty($userId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
                'merchantId' => addslashes($merchantId),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInfo', 'checkUserAuthStatus', $request);
            // 获取客服电话
            $csHotlinePhoneInfo = \SiteApp::init()->dataCache->call(new \libs\rpc\Rpc(), 'local', array('ApiConfService\getApiAdvConf', array(CommonEnum::TRIP_APP_SERVICE_TEL_KEY, 1, 2)), 60);
            if (!empty($csHotlinePhoneInfo[0]['value'])) {
                $res['data']['tripInfo']['companyPhone'] = $csHotlinePhoneInfo[0]['value'];
            } else {
                $res['data']['tripInfo']['companyPhone'] = CommonEnum::TRIP_APP_SERVICE_TEL_DEFAULT;
            }
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'merchantId'=>$merchantId]);
        }
    }

    /**
     * 对用户进行授权
     * @param int $userId 用户ID
     * @return array
     */
    public function addUserAuth($userId) {
        try {
            // 参数检查
            if (empty($userId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInfo', 'addUserAuth', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 添加出行订单备注信息
     * @param int $outOrderId 出行主订单号
     * @param string $remark 备注内容
     * @param string $operateName 操作人员姓名
     * @return array
     */
    public function addTripRemarkData($outOrderId, $remark, $operateName) {
        try {
            // 参数检查
            if (empty($outOrderId) || empty($remark) || empty($operateName)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId'  => (int)$outOrderId,
                'remark'      => addslashes($remark),
                'operateName' => addslashes($operateName),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInfo', 'addTripRemarkData', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['outOrderId'=>$outOrderId]);
        }
    }

    /**
     * 获取该订单已经开发票的数量
     * @param int $outOrderId 订单号
     * @return array
     */
    public function getInvoiceCountByOutOrderId($outOrderId) {
        try {
            if (empty($outOrderId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId' => (int)$outOrderId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserInvoice', 'getInvoiceCountByOutOrderId', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['outOrderId'=>$outOrderId]);
        }
    }

    /**
     * 根据消费主订单号，查询是否有成功、处理中的退款订单
     * @param int $outOrderId 订单号
     * @param string $merchantId 商户编号
     * @return array
     */
    public function getTripRefundOrderByOutOrderId($outOrderId, $merchantId) {
        try {
            if (empty($outOrderId) || empty($merchantId)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId' => (int)$outOrderId,
                'merchantId' => addslashes($merchantId),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\RefundOrder', 'getTripRefundOrderByOutOrderId', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__);
        }
    }

    /**
     * 发起出行订单退款请求
     * @param int $outOrderId 出行主订单号
     * @param string $merchantId 商户编号
     * @param int $amount 退款金额，单位分
     * @return array
     */
    public function addTripRefund($outOrderId, $merchantId, $amount) {
        try {
            // 参数检查
            if (empty($outOrderId) || empty($merchantId) || empty($amount)) {
                LifeException::exception(ErrorCode::WXCX_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId'  => (int)$outOrderId,
                'merchantId'  => addslashes($merchantId),
                'amount'      => (int)$amount,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\RefundOrder', 'doTripRefund', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['outOrderId'=>$outOrderId, 'amount'=>$amount]);
        }
    }
}