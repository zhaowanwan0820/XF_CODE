<?php
/**
 * 网信收银台Service
 * @date 20171130
 */
namespace core\service\life;

use NCFGroup\Protos\Life\RequestCommon;
use NCFGroup\Protos\Life\Enum\ErrorCode;
use NCFGroup\Protos\Life\Enum\PaymentEnum;
use NCFGroup\Protos\Life\Enum\CommonEnum;
use libs\utils\Logger;
use core\exception\LifeException;
use core\service\life\LifeRpcService;
use core\dao\UserModel;
use core\dao\UserBankcardModel;
use core\dao\BankModel;
use core\service\BankService;

class PaymentUserService extends LifeRpcService {
    // 版块支持的银行卡类型列表
    private static $secCardTypeList = [];

    /**
     * 获取用户绑卡页面
     * @param int $userId 用户ID
     * @param string $returnUrl 同步回调地址
     * @param int $isP2pBind 是否是绑定理财卡为消费卡(0:不是1:是)
     * @return array
     */
    public function getUserBindCardPage($userId, $returnUrl = '', $isP2pBind = 1) {
        try {
            if (empty($userId)) {
                LifeException::exception(ErrorCode::MISS_PARAMETERS, 'userId');
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($userId, 'id,real_name,mobile,idno', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars['userId'] = (int)$userId;
            if (empty($returnUrl)) {
                $vars['returnUrl'] = get_domain() . '/life/cardCenterBindReturn';
            }
            // 是否是绑定理财卡为消费卡
            if ($isP2pBind == 1) {
                if (empty($userBaseInfo['real_name']) || empty($userBaseInfo['idno'])) {
                    LifeException::exception(ErrorCode::WXCX_USER_HASNOT_IDPASS);
                }
                // 获取用户理财卡
                $p2pCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId, 'bank_id,bankcard');
                if (empty($p2pCardInfo['bankcard'])) {
                    LifeException::exception(ErrorCode::P2P_USER_BINDCARD_FAILED);
                }
                $vars['bankCardNo'] = $p2pCardInfo['bankcard'];
                $vars['realName'] = $userBaseInfo['real_name'];
                $vars['certNo'] = $userBaseInfo['idno'];
            }
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserCard', 'getUserBindCardPage', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 绑卡同步回调逻辑
     * @param array $data 支付系统返回的加密数据
     * @return array
     */
    public function bindCardReturn($data) {
        try {
            // 解密数据
            $payData = $this->parsePayData($data);
            if (!isset($payData['errorCode']) || $payData['errorCode'] != 0 || $payData['data']['ret'] !== true) {
                LifeException::exception(ErrorCode::WXCX_USER_BINDCARD_FAILED);
            }

            $payResult = $payData['data']['result'];
            $userId = (int)$payResult['userId'];
            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($userId, 'id,user_name,real_name', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }
            // 绑卡人的真实姓名
            $payResult['userName'] = $userBaseInfo['real_name'];

            $res = [];$bindMsg = '';
            if ($payResult['resCode'] === ErrorCode::RESPONSE_CODE) {
                $bindMsg = '绑卡成功';
                $res['status'] = PaymentEnum::CARD_BIND_SUCCESS;
                // 已经脱敏的银行卡号，卡号后4位
                $cardLastNo = substr($payResult['bankCardNo'], -4);
                $res['content'] = sprintf(PaymentEnum::BINDCARD_SUCCESS_TIPS, $cardLastNo, PaymentEnum::$cardTypeConfig[$payResult['bankCardType']]);

                // 获取银行卡ID、银行卡名称等信息
                if (!empty($payResult['bankCode'])) {
                    $bankInfo = BankModel::instance()->getBankByCode($payResult['bankCode']);
                    if (!empty($bankInfo)) {
                        $payResult['bankId'] = (int)$bankInfo['id'];
                        $payResult['bankName'] = $bankInfo['name'];
                        $payResult['shortName'] = $bankInfo['short_name'];
                        $payResult['bankShortname'] = !empty($bankInfo['abbreviate_name']) ? $bankInfo['abbreviate_name'] : $bankInfo['name'];
                    }
                }
                // 主动发起绑卡异步回调通知
                $payResult['source'] = 'LIFE_PAYMENT';
                $bindNotifyRet = $this->bindCardNotifyForP2p($payResult);
            } else {
                $bindMsg = '绑卡失败';
                $res['status'] = PaymentEnum::CARD_BIND_FAILED;
                $res['content'] = $payResult['resMessage'];
                $bindNotifyRet = ['errorCode'=>ErrorCode::WXCX_USER_BINDCARD_FAILED, 'errorMsg'=>ErrorCode::$errMsg[ErrorCode::WXCX_USER_BINDCARD_FAILED]];
            }

            Logger::info(implode(' | ', array(__CLASS__, 'bindCardReturn', APP, sprintf('同步绑卡通知成功-%s|userId：%d，payData：%s，res：%s，bindNotifyRet：%s', $bindMsg, $userId, json_encode($payData), json_encode($res), json_encode($bindNotifyRet)))));
            return $res;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, 'bindCardReturn', APP, sprintf('同步绑卡通知成功-绑卡异常|data：%s，payData：%s', $data, json_encode($payData)))));
            return ['status'=>PaymentEnum::CARD_BIND_PROCESS, 'content'=>'', 'errcode'=>$e->getCode(), 'errmsg'=>$e->getMessage()];
        }
    }

    /**
     * 接收支付的绑卡异步回调通知-For理财调用
     * @return array
     */
    public function bindCardNotifyForP2p($params) {
        try {
            if (empty($params)) {
                LifeException::exception(ErrorCode::MISS_PARAMETERS, 'params');
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $request->setVars($params);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserCard', 'bindCardNotifyForP2p', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['params'=>$params]);
        }
    }

    /**
     * 用户解绑消费卡
     * @return array
     */
    public function unBindUserConsumeCard($userId, $cardId) {
        try {
            if (empty($userId) || empty($cardId)) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($userId, 'id,user_name,real_name', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $request->setVars(['userId'=>(int)$userId, 'cardId'=>(int)$cardId]);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserCard', 'unBindUserConsumeCard', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'cardId'=>$cardId]);
        }
    }

    /**
     * 解密支付系统同步返回的数据
     * @param array $data 支付系统返回的加密数据
     * @return array
     */
    public function parsePayData($data) {
        try {
            if (empty($data)) {
                LifeException::exception(ErrorCode::MISS_PARAMETERS, 'data');
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'data' => addslashes($data),
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\PaymentUser', 'parsePayData', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['data'=>$data]);
        }
    }

    /**
     * 获取用户消费卡数量
     * @return array
     */
    public function getMyConsumeCardCount($userId) {
        try {
            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserCard', 'getCardNumber', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 获取用户消费用途的绑卡列表,按绑卡时间倒序
     * @return array
     */
    public function getMyConsumeCardList($userId) {
        return $this->_handleResponse(['errorCode'=>0, 'data'=>[]]);
        try {
            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserCard', 'getCardList', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId]);
        }
    }

    /**
     * 获取用户理财卡+消费卡银行卡总数量
     * @param int $userId 用户ID
     * @return string
     */
    public function getMyCardNumber($userId) {
        // 获取用户理财卡
        $p2pCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        return !empty($p2pCardInfo) ? '1张' : '';
        $myCardList = $this->getMyCardList($userId);
        return !empty($myCardList['cardList']) ? sprintf('%d张', count($myCardList['cardList'])) : '';
    }

    /**
     * 获取理财端设置的银行卡列表缓存
     * @param int $userId
     */
    public static function getUserCardListCache($userId) {
        return [];
        $cacheKey = sprintf(CommonEnum::CACHEKEY_PAYMENT_USERCARD_LIST, $userId);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!is_null($redis)) {
            $listJson = $redis->get($cacheKey);
            return json_decode($listJson, true);
        }
        return [];
    }

    /**
     * 设置理财端银行卡列表的缓存
     * @param int $userId
     */
    public static function setUserCardListCache($userId, $list, $expireTime = 60) {
        $cacheKey = sprintf(CommonEnum::CACHEKEY_PAYMENT_USERCARD_LIST, $userId);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        return is_null($redis) ? false : $redis->setex($cacheKey, $expireTime, json_encode($list));
    }

    /**
     * 清理理财端设置的银行卡列表的缓存
     * @param int $userId
     */
    public static function clearUserCardListCache($userId) {
        $cacheKey = sprintf(CommonEnum::CACHEKEY_PAYMENT_USERCARD_LIST, $userId);
        $redis = \SiteApp::init()->dataCache;
        return is_null($redis) ? false : $redis->remove($cacheKey);
    }

    /**
     * 获取用户理财卡+消费卡列表
     * @param int $userId 用户ID
     * @return array
     */
    public function getMyCardList($userId) {
        $list = ['cardList'=>[], 'cardTips'=>'绑定新卡支付'];
        $bankObj = new BankService();
        // 获取用户理财卡
        $p2pCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        if (!empty($p2pCardInfo) && $p2pCardInfo['status'] == UserBankcardModel::STATUS_BINDED) {
            // 通过银行ID获取银行基本信息
            $bankBaseInfo = $bankObj->getBank($p2pCardInfo['bank_id']);
            // 银行名称
            $bankName = !empty($bankBaseInfo['name']) ? $bankBaseInfo['name'] : '';
            // 银行名称简称
            $abbreviateName = !empty($bankBaseInfo['abbreviate_name']) ? $bankBaseInfo['abbreviate_name'] : $bankName;
            // 银行简码
            $bankShortName = !empty($bankBaseInfo['short_name']) ? $bankBaseInfo['short_name'] : '';
            // 理财卡-卡号前6位
            $cardFirstno = substr($p2pCardInfo['bankcard'], 0, 6);
            // 理财卡-卡号后4位
            $cardLastno = substr($p2pCardInfo['bankcard'], -4);
            $cardUnique = md5($p2pCardInfo['bankcard']);
            $cardTypeName = PaymentEnum::$cardTypeConfig[PaymentEnum::CARD_TYPE_DEPOSIT];
            $cardTag = sprintf('%s%s', $abbreviateName, $cardLastno);
            $cardTagDesc = sprintf('%s%s(%s)', $abbreviateName, $cardTypeName, $cardLastno);
            // 获取银行logo、背景图
            $bankImgInfo = $bankObj->getBankImgUrl($p2pCardInfo['bank_id'], 'logo_id,bg_id');

            $cardType = PaymentEnum::CARD_TYPE_DEPOSIT;

            $list['cardList'][] = [
                'card_flag'    => sprintf('%d_%d', PaymentEnum::CARD_FLAG_P2P, $p2pCardInfo['id']), // 理财卡
                'id'           => $p2pCardInfo['id'],
                'user_id'      => $p2pCardInfo['user_id'],
                'bank_id'      => $p2pCardInfo['bank_id'],
                'bank_name'    => $bankName,
                'bank_shortname' => $abbreviateName,
                'bank_code'    => $bankShortName,
                'status'       => (int)$p2pCardInfo['status'],
                'bankcard'     => bankNoFormat($p2pCardInfo['bankcard'], 6, 4),
                'card_firstno' => $cardFirstno,
                'card_lastno'  => $cardLastno,
                'card_type'    => $cardType, // 银行卡类型(1:储蓄卡2:信用卡)
                'cardtype_name'=> $cardTypeName,
                'card_purpose' => PaymentEnum::CARD_STATUS_FINANCE, // 银行卡用途(1:仅消费2:仅理财充值提现3:消费跟充值提现)
                'card_purpose_name' => PaymentEnum::$cardPurposeConfig[PaymentEnum::CARD_STATUS_FINANCE],
                'bind_token'   => '', // 支付绑卡token
                'card_unique'  => $cardUnique,
                'card_tag'     => $cardTag,
                'card_tag_desc'=> $cardTagDesc,
                'is_valid'     => !empty($p2pCardInfo['verify_status']) ? true : false, // 用户银行卡是否已验证
                'bank_logo'    => !empty($bankImgInfo['logo_id']) ? $bankImgInfo['logo_id'] : '',
                'bank_bgpic'   => !empty($bankImgInfo['bg_id']) ? $bankImgInfo['bg_id'] : '',
            ];
            //$list['cardTips'] = sprintf(PaymentEnum::PAYMENT_CARDLIST_P2P_TIPS, $bankName, PaymentEnum::$cardTypeConfig[PaymentEnum::CARD_TYPE_DEPOSIT], $cardLastno);
        }

        // 读取列表缓存
        $listCache = self::getUserCardListCache($userId);
        if (!empty($listCache)) {
            // 合并列表
            $list['cardList'] = array_merge($list['cardList'], $listCache);
        } else {
            // 获取用户消费卡列表
            $consumeList = $this->getMyConsumeCardList($userId);
            if ($consumeList['errorCode'] === 0 && !empty($consumeList['data'])) {
                // 合并列表
                $list['cardList'] = array_merge($list['cardList'], $consumeList['data']);
                // 设置列表缓存
                self::setUserCardListCache($userId, $consumeList['data']);
            }
        }

        // 整理数据
        if (!empty($list['cardList'])) {
            $tmpBankImgList = $existCardUnique = [];
            foreach ($list['cardList'] as $consumeKey => &$consumeItem) {
                // 银行卡唯一标识
                if ($consumeItem['card_purpose'] == PaymentEnum::CARD_STATUS_FINANCE) {
                    $cardUnique = $consumeItem['card_unique'];
                    unset($consumeItem['card_unique']);
                }else{
                    $cardUnique = strtolower($consumeItem['bank_sign']);
                }

                // 银行名称简称
                empty($consumeItem['bank_shortname']) && $consumeItem['bank_shortname'] = $consumeItem['bank_name'];
                $consumeItem['bank_shortname'] = msubstr($consumeItem['bank_shortname'], 0, 6);
                // 绑卡Token
                !empty($consumeItem['bind_token']) && $consumeItem['bind_token'] = bankNoFormat($consumeItem['bind_token'], 4, 4);
                // 脱敏手机号
                !empty($consumeItem['mobile']) && $consumeItem['mobile'] = moblieFormat($consumeItem['mobile']);
                // 消费卡/理财卡的区分字段
                $consumeItem['card_flag'] = sprintf('%d_%d', $consumeItem['card_purpose'], $consumeItem['id']);
                // 消费卡号脱敏
                $consumeItem['bankcard'] = bankNoFormat($consumeItem['bankcard'], 6, 4);
                $consumeItem['cardtype_name'] = PaymentEnum::$cardTypeConfig[$consumeItem['card_type']];
                $consumeItem['card_purpose_name'] = PaymentEnum::$cardPurposeConfig[$consumeItem['card_purpose']];
                empty($consumeItem['card_tag']) && $consumeItem['card_tag'] = sprintf('%s%s(%s)', $consumeItem['bank_shortname'], PaymentEnum::$cardTypeConfig[$consumeItem['card_type']], $consumeItem['card_lastno']);

                // 银行卡状态
                $consumeItem['status'] = (int)$consumeItem['status'];
                // 用户银行卡是否已验证
                !isset($consumeItem['is_valid']) && $consumeItem['is_valid'] = !empty($consumeItem['verify_status']) ? true : false;
                // 获取银行logo、背景图
                empty($tmpBankImgList[$consumeItem['bank_id']]) && $tmpBankImgList[$consumeItem['bank_id']] = $bankObj->getBankImgUrl($consumeItem['bank_id'], 'logo_id,bg_id');
                $bankImgInfo = $tmpBankImgList[$consumeItem['bank_id']];
                $consumeItem['bank_logo'] = !empty($bankImgInfo['logo_id']) ? $bankImgInfo['logo_id'] : '';
                $consumeItem['bank_bgpic'] = !empty($bankImgInfo['bg_id']) ? $bankImgInfo['bg_id'] : '';

                if (isset($existCardUnique[$cardUnique])) {
                    if ($list['cardList'][$existCardUnique[$cardUnique]]['card_purpose'] == PaymentEnum::CARD_STATUS_FINANCE) {
                        $list['cardList'][$existCardUnique[$cardUnique]]['id'] = $consumeItem['id'];
                        $list['cardList'][$existCardUnique[$cardUnique]]['card_purpose'] = PaymentEnum::CARD_STATUS_ALL;
                        $list['cardList'][$existCardUnique[$cardUnique]]['card_flag'] = sprintf('%d_%d', PaymentEnum::CARD_STATUS_ALL, $consumeItem['id']);
                        $list['cardList'][$existCardUnique[$cardUnique]]['card_tag'] = $list['cardList'][$existCardUnique[$cardUnique]]['card_tag_desc'];
                    }
                    unset($list['cardList'][$consumeKey]);
                }
                $existCardUnique[$cardUnique] = $consumeKey;
            }
            unset($tmpBankImgList);
        }

        if (!empty($list['cardList'])) {
            $list['cardList'] = array_values($list['cardList']);
        }
        $list['cardCount'] = count($list['cardList']);
        return $list;
    }

    /**
     * 获取用户付款方式列表
     * @param int $userId 用户ID
     * @param string $merchantId 商户编号
     * @param int $isConsume 是否只获取消费卡列表
     * @return array
     */
    public function getMyPayCardList($userId, $merchantId, $isConsume = 0) {
        $list = ['cardList'=>[]];
        $myCardList = $this->getMyCardList($userId);
        $list['cardTips'] = $myCardList['cardTips'];
        if (!empty($myCardList['cardList'])) {
            foreach ($myCardList['cardList'] as $key => $consumeItem) {
                // 是否支持该类型的银行卡
                $isSupport = (int)$this->inSectionCardTypeList($merchantId, $consumeItem['card_type']);

                // 只获取消费卡（不支持的银行卡或者未升级的理财卡跳过）
                if ($isConsume == 1 && ($isSupport == 0 || $consumeItem['card_purpose'] == PaymentEnum::CARD_STATUS_FINANCE)) {
                    continue;
                }

                $list['cardList'][] = [
                    'id'            => $consumeItem['id'],
                    'card_purpose'  => $consumeItem['card_purpose'], // 用途(1:仅消费2:仅理财3:理财+消费)
                    'card_purpose_name' => $consumeItem['card_purpose_name'],
                    'card_type'     => $consumeItem['card_type'],
                    'cardtype_name' => $consumeItem['cardtype_name'],
                    'card_flag'     => $consumeItem['card_flag'],
                    'is_support'    => $isSupport,
                    'card_tag'      => $consumeItem['card_tag'],
                    'bank_logo'     => $consumeItem['bank_logo'],
                ];
            }
        }
        return $list;
    }

    /**
     * 创建网信支付订单
     * @param array $params 参数数组
     * outOrderId：消费订单号
     * userId：订车人ID
     * merchantId：管理后台给AA开通的商户编号
     * goodsName：商品名称
     * amount：实付金额，单位：分
     * notifyUrl：异步通知地址
     * returnUrl：同步通知地址，非必填
     * shouldAmount：应付金额，单位：分，非必填
     * discountAmount：优惠金额，单位：分，非必填
     * @return array
     */
    public function createOrder($params) {
        try {
            // 参数检查
            if (empty($params['outOrderId']) || empty($params['userId'])
                || empty($params['merchantId']) || empty($params['goodsName'])
                || empty($params['amount']) || empty($params['notifyUrl'])) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($params['userId'], 'id,user_name,real_name', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 优惠金额，单位：分
            $discountAmount = !empty($params['discountAmount']) ? (int)$params['discountAmount'] : 0;
            // 应付金额，单位：分
            $shouldAmount = !empty($params['shouldAmount']) ? (int)$params['shouldAmount'] : 0;
            // 实付金额，单位：分
            $amount = (int)$params['amount'];
            // 同步回调地址
            $returnUrl = !empty($params['returnUrl']) ? addslashes($params['returnUrl']) : '';
            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId' => addslashes($params['outOrderId']), // 出行订单号
                'userId' => $userBaseInfo['id'], // 用户ID
                'merchantId' => addslashes($params['merchantId']), // 管理后台给AA开通的商户编号
                'goodsName' => addslashes($params['goodsName']), // 商品名称
                'goodsDesc' => !empty($params['goodsDesc']) ? addslashes($params['goodsDesc']) : '', // 商品描述
                'amount' => $amount,
                'returnUrl' => $returnUrl, // 同步通知地址
                'notifyUrl' => addslashes($params['notifyUrl']), // 异步通知地址
                'shouldAmount' => (int)$shouldAmount,
                'discountAmount' => (int)$discountAmount,
            );
            !empty($params['cardId']) && $vars['cardId'] = (int)$params['cardId'];
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\PaymentUser', 'createPaymentOrderForUser', $request);

            // 获取用户付款方式的第一条记录
            if (isset($res['errorCode']) && $res['errorCode'] == 0) {
                $payCardList = $this->getMyPayCardList($params['userId'], $params['merchantId'], 1);
                $res['data']['cardInfo'] = !empty($payCardList['cardList']) ? array_shift($payCardList['cardList']) : [];
                $res['data']['cardTips'] = $payCardList['cardTips'];
            }
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 支付网信支付订单
     * @param array $params 参数数组
     * payOrderId：支付主订单号
     * outOrderId：出行子订单号
     * userId：订车人ID
     * merchantId：管理后台给AA开通的商户编号
     * cardId：银行卡ID
     * @return array
     */
    public function paymentOrder($params) {
        try {
            // 参数检查
            if (empty($params['payOrderId']) || empty($params['outOrderId'])
                || empty($params['userId']) || empty($params['merchantId'])
                || empty($params['cardId'])) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 获取用户基本信息
            $userBaseInfo = UserModel::instance()->find($params['userId'], 'id,user_name,real_name', true);
            if (empty($userBaseInfo)) {
                LifeException::exception(ErrorCode::WXCX_USER_NOTEXIST);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'payOrderId' => addslashes($params['payOrderId']), // 支付主订单号
                'outOrderId' => addslashes($params['outOrderId']), // 消费子订单号
                'merchantId' => addslashes($params['merchantId']), // 管理后台给AA开通的商户编号
                'userId'     => $userBaseInfo['id'], // 用户ID
                'cardId'     => (int)$params['cardId'],
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\PaymentUser', 'paymentOrder', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 解密支付系统同步返回的数据
     * @param int $userId 用户ID
     * @param int $subPayId 支付子订单号
     * @return array
     */
    public function queryPaymentInfo($userId, $subPayId) {
        try {
            if (empty($userId) || empty($subPayId)) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId'     => (int)$userId,
                'subPayId'   => (int)$subPayId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\PaymentUser', 'getUserOrderBySubPayId', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'subPayId'=>$subPayId]);
        }
    }

    /**
     * 获取出行订单列表
     * @return array
     */
    public function getConsumeList($params) {
        try {
            if (strlen($params['payStatus']) <= 0 || empty($params['startTime'])) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }
            if ((!empty($params['orderAmountMin']) && empty($params['orderAmountMax']))
                || (empty($params['orderAmountMin']) && !empty($params['orderAmountMax']))) {
                LifeException::exception(ErrorCode::ERR_MONEY_RANGE);
            }
            // 金额不在规定范围内
            if (!empty($params['orderAmountMin']) && !empty($params['orderAmountMax']) && bccomp($params['orderAmountMin'], $params['orderAmountMax'], 2) > 0) {
                LifeException::exception(ErrorCode::ERR_MONEY_RANGE);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId'      => !empty($params['merchantId']) ? addslashes($params['merchantId']) : '',
                'outOrderId'      => !empty($params['outOrderId']) ? (int)$params['outOrderId'] : 0,
                'userId'          => !empty($params['userId']) ? $params['userId'] : [],
                'status'          => !empty($params['status']) ? $params['status'] : 0,
                'payStatus'       => isset($params['payStatus']) ? (int)$params['payStatus'] : 0,
                'startTime'       => !empty($params['startTime']) ? addslashes($params['startTime']) : date('Y-m-d 00:00:00'),
                'endTime'         => !empty($params['endTime']) ? addslashes($params['endTime']) : date('Y-m-d 23:59:59'),
                'orderAmountMin'  => !empty($params['orderAmountMin']) ? bcmul($params['orderAmountMin'], 100, 2) : 0, // 订单最小金额，单位分
                'orderAmountMax'  => !empty($params['orderAmountMax']) ? bcmul($params['orderAmountMax'], 100, 2) : 0, // 订单最大金额，单位分
                'thirdOrderId'    => !empty($params['thirdOrderId']) ? (int)$params['thirdOrderId'] : 0, // 第三方出行订单号
                'page'            => !empty($params['page']) ? (int)$params['page'] : 1,
                'count'           => !empty($params['count']) ? (int)$params['count'] : 10,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getAdminUserTripList', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['data'=>$data]);
        }
    }

    /**
     * 获取出行订单详情
     * @return array
     */
    public function getConsumeDetail($outOrderId, $merchantId = '', $userId = '') {
        try {
            if (empty($outOrderId)) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'outOrderId' => (int)$outOrderId,
                'merchantId' => !empty($merchantId) ? addslashes($merchantId) : '',
                'userId'     => !empty($userId) ? (int)$userId : 0,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\UserTrip', 'getAdminUserTripInfo', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['data'=>$data]);
        }
    }

    /**
     * 获取退款订单列表
     * @return array
     */
    public function getRefundList($params) {
        try {
            if (empty($params['startCreateTime'])) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            // 退款截止时间
            empty($params['endCreateTime']) && $params['endCreateTime'] = date('Y-m-d H:i:s');
            // 退款时间校验
            if (!empty($params['startCreateTime']) && !empty($params['endCreateTime'])
                && $params['startCreateTime'] >= $params['endCreateTime']) {
                LifeException::exception(ErrorCode::ERR_DATETIME_RANGE);
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId'      => !empty($params['merchantId']) ? addslashes($params['merchantId']) : '',
                'startCreateTime' => !empty($params['startCreateTime']) ? addslashes($params['startCreateTime']) : 0,
                'endCreateTime'   => !empty($params['endCreateTime']) ? addslashes($params['endCreateTime']) : 0,
                'outOrderId'      => !empty($params['outOrderId']) ? (int)$params['outOrderId'] : 0,
                'userId'          => !empty($params['userId']) ? $params['userId'] : [],
                'status'          => !empty($params['status']) ? (int)$params['status'] : PaymentEnum::REFUND_STATUS_SUCCESS,
                'page'            => isset($params['page']) ? (int)$params['page'] : 1,
                'count'           => isset($params['count']) ? (int)$params['count'] : 10,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\RefundOrder', 'getRefundList', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['data'=>$data]);
        }
    }

    /**
     * 获取在该商户、该版块支持的银行卡类型
     * @param string $merchantId 商户编号
     */
    public function getSectionCardTypeList($merchantId) {
        try {
            if (empty($merchantId)) {
                LifeException::exception(ErrorCode::ERR_PARAMS_ERROR);
            }

            if (!empty(self::$secCardTypeList[$merchantId])) {
                return self::$secCardTypeList[$merchantId];
            }

            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'merchantId' => $merchantId,
                'payFlag'    => PaymentEnum::PAY_FLAG_BINDCARD, // 绑卡支付
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\SectionMerchant', 'getPaymentListByMerchantId', $request);
            if ($res['errorCode'] == 0 && !empty($res['data'])) {
                foreach ($res['data'] as $item) {
                    self::$secCardTypeList[$merchantId][$item['card_type']] = $item['status'];
                }
                return self::$secCardTypeList[$merchantId];
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 检查cardType是否在该商户、该版块支持的银行卡类型里面
     * @param string $merchantId 商户编号
     * @param int $cardType 银行卡类型(0:不限制1:储蓄卡2:信用卡)
     */
    public function inSectionCardTypeList($merchantId, $cardType = 0) {
        if (empty($merchantId) || $cardType <= 0) {
            return true;
        }

        $list = $this->getSectionCardTypeList($merchantId);
        if (!empty($list)) {
            return !empty($list[$cardType]) ? true : false;
        }
        return true;
    }

    /**
     * 生成支付验证交易密码的参数列表
     * @param int $userId 用户ID
     * @param int $payOrderId 支付主订单号
     * @return array
     */
    public function getVerifyPayPasswdParams($userId, $payOrderId) {
        try {
            // 请求RPC服务
            $request = new RequestCommon();
            $vars = array(
                'userId' => (int)$userId,
                'payOrderId' => (int)$payOrderId,
            );
            $request->setVars($vars);
            $res = $this->requestRpc('NCFGroup\Life\Services\PaymentUser', 'getVerifyPayPasswdParams', $request);
            return $this->_handleResponse($res);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__, ['userId'=>$userId, 'payOrderId'=>$payOrderId]);
        }
    }

}