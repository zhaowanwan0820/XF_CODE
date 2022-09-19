<?php
namespace core\service;

use libs\utils\Logger;
use core\dao\UserModel;
use core\service\P2pDealBidService;
use core\service\SupervisionDealService AS SDS;
use core\service\SupervisionAccountService AS SAS;
use core\service\SupervisionFinanceService AS SFS;
use core\service\SupervisionBaseService;
use core\service\UserThirdBalanceService;
use core\service\UserTagService;
use core\service\AccountService;
use core\service\ncfph\AccountService as PhAccountService;
use NCFGroup\Common\Library\Idworker;
use core\dao\SupervisionTransferModel;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;
use core\dao\AccountAuthorizationModel as AuthModel;
use core\service\AccountAuthorizationService as AuthSrv;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use NCFGroup\Protos\Ptp\Enum\PaymentConfigEnum;
/**
 * P2P存管
 *
 */
class SupervisionService extends SupervisionBaseService
{
    const GRANT_TYPE_SXY = 1; //随心约授权
    const GRANT_TYPE_ZDX = 2; //智多新授权
    const GRANT_TYPE_BORROW = 3; //借款授权

    /*存管升级用户TAG*/
    const SV_UPGRADE_USER = 'SV_UPGRADE_USER';

    /**
     * svInfo 接口中需要强制免密的业务类型
     */
    static $freePaymentBusiness = [
        'gold',
    ];
    //忽略请求异常，默认不忽略
    public $ignoreReqExc = false;

    static $formServer = [
        'register' => [ // 个人/港澳台/企业用户开户
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberRegisterPage'
        ],
        'registerStandard' => [
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberStandardRegisterPage',
        ],
        'freePaymentQuickBid' => [ // 用户授权-快捷投资服务
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberAuthorizationCreate'
        ],
        'freePaymentYxt' => [ // 用户授权-银信通
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberAuthorizationCreate'
        ],
        'transfer' => [ // 提现至超级账户
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'superWithdrawSecret'
        ],
        'transferWx' => [
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'superRechargeSecret',
        ],
        'bid' => [ // 银行验密投资前的调用生成表单并保存订单信息
            'class' => 'core\service\P2pDealBidService',
            'fun' => 'dealBidSecretRequest'
        ],
        'dtbid' => [ // 智多新银行验密投资前的调用生成表单并保存订单信息
            'class' => 'core\service\duotou\DtP2pDealBidService',
            'fun' => 'dealBidSecretRequest'
        ],
        'charge' => [ // 充值
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'charge'
        ],
        'withdraw' => [ // web/h5验密提现至银行卡
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'secretWithdraw'
        ],
        'info' => [ // 存管银行个人页面
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberInfo'
        ],
        'superInfo' => [ // 超级账户个人页面
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'superMemberInfo'
        ],
        'bindcard' => [ // 超级账户绑卡
            'class' => 'core\service\PaymentUserAccountService',
            'fun' => 'h5AuthBindCardForm'
        ],
        'cardValidate' => [ // 超级账户四要素验卡
            'class' => 'core\service\PaymentService',
            'fun' => 'getH5BindCardForm'
        ],
        'h5authCard' => [ // 超级账户四要素验卡
            'class' => 'core\service\PaymentService',
            'fun' => 'getAuthCardForm'
        ],
        'authCreate' => [ // 开通授权
            'class' => 'core\service\SupervisionAccountService',
            'fun' => 'memberAuthorizationCreate'
        ],
        'offlineCharge' => [ // 线下充值
            'class' => 'core\service\PaymentService',
            'fun' => 'getOfflineChargeForm',
        ],
        'offlineChargeV3' => [ // 网信大额充值-下单模式
            'class' => 'core\service\PaymentService',
            'fun' => 'getOfflineChargeV3Form',
        ],
        'p2pOfflineCharge' => [ // 网贷线下充值
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'offlineCharge',
        ],
        'enterpriseCharge' => [ // 企业用户充值
            'class' => 'core\service\SupervisionFinanceService',
            'fun' => 'enterpriseCharge'
        ],
        'offlineChargeOrderPage' => [ // 大额充值历史记录查询页面
            'class' => 'core\service\PaymentService',
            'fun' => 'offlineChargeOrderPage',
        ],
    ];

    public static $superSrvs = ['superInfo', 'bindcard', 'cardValidate', 'h5authCard', 'offlineCharge']; //超级账户Srv

    private $serverClass;
    private $fun;
    private $params = [];
    private $formParams = [];
    private $userId;
    private $from = 'pc';

    /**
     * 存管相关数据
     */
    public function svInfo($userId = 0, $needUserPurpose = 0, $bizType = 'p2p')
    {
        $data = [];
        $svStatus = (int) parent::isSupervisionOpen();
        $data['status'] = $svStatus;
        if ($svStatus && $userId) {
            $userObject = UserModel::instance()->find($userId);

            //查询普惠接口
            $phAccountService = new PhAccountService();
            $accountInfo = $phAccountService->getInfoByUserIdAndType($userId, $userObject['user_purpose']);

            $data['isSvUser'] = !empty($accountInfo['isSupervisionUser']) ? true : false;
            $data['userPurpose'] = $userObject['user_purpose'];
            // 网信普惠用户投资一律验证交易密码
            $data['isFreePayment'] = 0;
            if ($data['isSvUser']) {
                $data['svBalance'] = $accountInfo['money'];
                $data['svFreeze'] = $accountInfo['lockMoney'];
                $data['svMoney'] = $accountInfo['totalMoney'];
                if (in_array($bizType, self::$freePaymentBusiness)) {
                    $data['isFreePayment'] = 1;
                }
                //判断用户是否是存量未激活用户
                $data['isActivated'] = empty($accountInfo['isUnactivated']) ? 1 : 0;
            }
        }
        Logger::info('svInfo:'.json_encode($data));
        return $data;
    }

    /**
     * 包免密协议查询
     * 网信普惠标的投资必须要校验交易密码
     */
    public function isFreePayment($userId, $grantType = 0)
    {
        // 存管上线之后该方法默认返回需要验密
        return false;
    }

    /**
     * 检查授权
     */
    public function checkAuth($userId, $grantType = self::GRANT_TYPE_SXY)
    {
        $grantList = [];
        if ($grantType == self::GRANT_TYPE_SXY) {
            $grantList[AuthModel::GRANT_TYPE_INVEST] = 0;
        }
        if ($grantType == self::GRANT_TYPE_ZDX) {
            $grantList[AuthModel::GRANT_TYPE_INVEST] = 0;
            $grantList[AuthModel::GRANT_TYPE_PAYMENT] = 0;
        }
        if ($grantType == self::GRANT_TYPE_BORROW) {
            $grantList[AuthModel::GRANT_TYPE_REPAY] = 0;
            $grantList[AuthModel::GRANT_TYPE_PAYMENT] = 0;
        }
        $needGrant = $needGrantArr = $granted = $grantMsg = [];
        try {
            $authSrv = new AuthSrv();
            $authInfo = $authSrv->checkAuth($userId, $grantList);
            if (isset($authInfo['code']) && $authInfo['code'] == 1) {
                foreach ($authInfo['data'] as $v) {
                    if ($v['code'] == 1) {
                        $needGrantStr[] = $v['grant'];
                        $needGrantArr[] = $v['grantType'];
                        $grantMsg[] = $v['grantName'].$v['msg'];
                    } else {
                        $granted[] = $v['grantType'];
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error('checkAuthErr:'.$userId.','.$e->getMessage());
        }
        $return = [];
        if ($needGrantArr) {
            $return = [
                'needGrantStr' => join(',', $needGrantStr),
                'needGrantArr' => $needGrantArr,
                'grantMsg' => join(',', $grantMsg),
                'grantedArr' => $granted,
                ];
        }
        Logger::info('checkUserAuth:'.$userId.','.json_encode($return));
        return $return;
    }

    /**
     * 获取表单
     */
    public function formFactory($server, $accountId, $p = [], $from = 'h5')
    {
        //存管服务降级 排除理财账户srv
        if (!in_array($server, self::$superSrvs) && Supervision::isServiceDown()) {
            return ['status' => 0, 'msg' => Supervision::maintainMessage()];
        }
        $server = trim($server);
        if (!in_array($server, array_keys(self::$formServer))) {
            return ['status' => 0, 'msg' => '不存在的srv调用'];
        }

        $this->serverClass = new self::$formServer[$server]['class']();
        $this->fun = self::$formServer[$server]['fun'];
        if (!method_exists($this->serverClass, $this->fun)) {
            return ['status' => 0, 'msg' => '表单服务不存在'];
        }
        $this->params = $p;
        $this->formParams = [];
        if (isset($this->params['return_url'])) {
            $this->formParams['returnUrl'] = $this->params['return_url'];
        }
        if (!isset($this->params['mobileType'])) {
            $this->params['mobileType'] = 11;
        }
        $this->formParams['mobileType'] = $this->params['mobileType'];
        $this->accountId = $accountId;
        $this->from = $from;

        $getFormParams = $server . 'Form';
        $callParams = [];
        if (method_exists($this, $getFormParams)) {
            $callParams = $this->$getFormParams();
        } else {
            return ['status' => 0, 'msg' => '表单服务不存在'];
        }
        try {
            $res = call_user_func_array([$this->serverClass, $this->fun], $callParams);
        } catch (\Exception $e) {
            Logger::error('BuildFormError:'.$e->getMessage());
            return ['status' => 0, 'msg' => '表单服务不存在'];
        }
        if ($res['status'] == parent::RESPONSE_SUCCESS) {
            if ($res['respCode'] == parent::RESPONSE_CODE_SUCCESS) {
                $data = $res['data'];
                $data['status'] = 1;
                return $data;
            }
        }
        return ['status' => 0, 'msg' => $res['respMsg']];
    }

    /**
     * 个人港澳台企业用户开户表单
     */
    private function registerForm()
    {
        if (empty($this->params['isOnekeyRegister'])) {
            return [$this->accountId, $this->from, $this->formParams];
        } else {
            $this->formParams['mobileType'] = 21;
            return [$this->accountId, $this->from, $this->formParams, true, true];
        }
    }

    /**
     * 个人标准开户表单(普惠)
     */
    private function registerStandardForm()
    {
        return [$this->accountId, $this->from, $this->formParams];
    }

    /**
     * 快捷投资免密授权
     */
    private function freePaymentQuickBidForm()
    {
        $this->formParams['grantList'] = join(',', [
            parent::GRANT_INVEST,
            parent::GRANT_WITHDRAW_TO_SUPER,
            ]);
        $this->formParams['userId'] = $this->accountId;

        return [$this->formParams, $this->from];
    }

    /**
     * 银信通免密授权
     */
    private function freePaymentYxtForm()
    {
        $this->formParams['grantList'] = parent::GRANT_WITHDRAW_TO_YXT;
        $this->formParams['userId'] = $this->accountId;
        return [$this->formParams, $this->from];
    }

    /**
     * 验密从存管账户划转到超级账户
     */
    private function transferForm()
    {
        $params = [];
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = bcmul($this->params['amount'], 100);
        $params['superUserId'] = $this->accountId;
        $params['userId'] = $this->accountId;
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 验密从超级账户划转到存管账户
     */
    private function transferWxForm() {
        $params = [];
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = bcmul($this->params['amount'], 100);
        $params['superUserId'] = $this->accountId;
        $params['userId'] = $this->accountId;
        return [array_merge($this->formParams, $params), $this->from];

    }

    /**
     * 充值表单
     */
    private function chargeForm()
    {
        $params = [];
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        $params['userId'] = $this->accountId;
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 提现表单
     */
    private function withdrawForm()
    {
        $params = [];
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        $params['bizType'] = '01';
        $params['efficType'] = 'T1';
        $params['userId'] = $this->accountId;
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 投资表单
     */
    private function bidForm()
    {
        $bidParams = [
            'couponId'     => isset($this->params['couponId']) ? $this->params['couponId'] : '',
            'sourceType'   => isset($this->params['sourceType']) ? $this->params['sourceType'] : 0,
            'siteId'       => isset($this->params['siteId']) ? $this->params['siteId'] : 1,
            'jforderId'    => isset($this->params['jforderId']) ? $this->params['jforderId'] : '',
            'discountId'   => isset($this->params['discountId']) ? $this->params['discountId'] : '',
            'discountType' => isset($this->params['discountType']) ? $this->params['discountType'] : '',
            'discountGoodsPrice' => isset($this->params['discountGoodsPrice']) ? $this->params['discountGoodsPrice'] : '',
            'discountGoodsType' => isset($this->params['discountGoodsType']) ? $this->params['discountGoodsType'] : '',
            ];

        $dealId = $this->params['dealId'];
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';

        if ($returnUrl && in_array($this->params['mobileType'], ['21', '22'])) {
            $appendOrderId = parse_url($returnUrl, PHP_URL_QUERY) ? '&orderId=' . $orderId : '?orderId=' . $orderId;
            $returnUrl .= $appendOrderId;
        }

        $optionParams = ['returnUrl' => $returnUrl,'mobileType' => $this->params['mobileType'],'platform' => $this->from];

        return [$orderId, $dealId, $this->accountId, $this->params['money'], $bidParams,$optionParams];
    }


    private function dtbidForm(){
        $bidParams = [
            'activityId' => isset($this->params['activityId']) ? $this->params['activityId'] : '',
            'couponId'   => isset($this->params['couponId']) ? $this->params['couponId'] : 0,
            'discount_id' => isset($this->params['discount_id']) ? $this->params['discount_id']:0,
            'discount_type' => isset($this->params['discount_type']) ? $this->params['discount_type']:'',
        ];

        $orderId   = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $returnUrl = $this->appendOrderId($returnUrl, $orderId);

        if(strtolower($this->from) == 'pc'){
            $returnUrl = '';
        }

        $dealId = $this->params['dealId'];
        $optionParams = ['returnUrl' => $returnUrl,'mobileType' => $this->formParams['mobileType'],'platform' => $this->from];
        return [$orderId,$dealId, $this->accountId, $this->params['money'], $bidParams,$optionParams];
    }

    /**
     * 账户明细表单
     */
    private function infoForm()
    {
        return [$this->accountId, $this->from, $this->formParams];
    }

    /**
     * 账户明细表单
     */
    private function superInfoForm()
    {
        return [$this->accountId, $this->from, $this->formParams];
    }

    /**
     * h5绑卡卡
     */
    private function bindcardForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $failUrl = isset($this->params['fail_url']) ? $this->params['fail_url'] : $returnUrl;
        $reqSource = empty($this->params['reqSource']) ? (in_array($this->params['mobileType'], [11, 12]) ? 1 : 2) : $this->params['reqSource'];
        $params = [
            'userId' => $this->accountId,
            'reqSource' => $reqSource,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            ];
        if (isset($this->params['isNeedTransfer'])) {
            $params['isNeedTransfer'] = $this->params['isNeedTransfer'];
        }
        if (isset($this->params['isShowProtocol'])) {
            $params['isShowProtocol'] = $this->params['isShowProtocol'];
        }
        if (isset($this->params['bankCardNo'])) {
            $params['bankCardNo'] = $this->params['bankCardNo'];
        }

        return [$params];
    }

    /**
     * h5四要素验卡卡
     */
    private function cardValidateForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $params = [
            'userId' => $this->accountId,
            'returnUrl' => $returnUrl,
            'mobileType' => $this->formParams['mobileType'],
            ];
        return [$params];
    }

    /**
     * 同步划转第二步请求存管行完成划转并处理结果
     */
    public function requestSupervisionInterface($direction, $params) {
        $financeSrv = new SupervisionFinanceService();
        try {
            $transferResult = [];
            if ($direction == SupervisionTransferModel::DIRECTION_TO_WX) {
                $transferResult = $financeSrv->accountSuperWithdraw($params);
            } else if ($direction == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                unset($params['superUserId']);
                $transferResult = $financeSrv->superRecharge($params);
            } else {
                PaymentApi::log('supervision transfer fail, unsupported direction '.$direction);
            }

            if (isset($transferResult['respCode']) && $transferResult['respCode'] !== SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($transferResult['respMsg']);
            }
            return true;
       } catch (\Exception $e) {
            PaymentApi::log('余额划转审批失败,'.$e->getMessage());
            return false;
       }
    }

    /*是否存管升级用户*/
    public function isUpgradeAccount($userId)
    {
        if ($userId) {
            $userTagService = new UserTagService();
            $result = (bool)$userTagService->getTagByConstNameUserId(self::SV_UPGRADE_USER, $userId);
            return $result;
        }
        return false;
    }

    public function updateRedoWithdraw($params) {
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $db->autoExecute('firstp2p_supervision_withdraw', $params, 'UPDATE', " out_order_id = '{$params['out_order_id']}'");
        return true;
    }

    /**
     * app 四要素验证
     */
    private function h5authCardForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $hasTitle = isset($this->params['hasTitle']) ? $this->params['hasTitle'] : 0;
        $params = [
            'userId' => $this->accountId,
            'returnUrl' => $returnUrl,
            'orderId' => md5(microtime(true)),
            'hasTitle' => $hasTitle,
            'transitToken' => $this->params['token'],
            'mobileType' => $this->formParams['mobileType'],
        ];
        return [$params];
    }

    /**
     * 添加用户授权
     */
    private function authCreateForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : ''; //回调地址
        $grantList = isset($this->params['grant_list']) ? $this->params['grant_list'] : ''; //授权名称
        $params = [
            'userId' => $this->accountId,
            'grantList' => $grantList,
            'returnUrl' => $returnUrl,
            'mobileType' => $this->params['mobileType'],
        ];
        return [$params, $this->from];
    }

    private function appendOrderId($returnUrl, $orderId)
    {
        if (strpos($returnUrl, 'http') === 0) {
            $appendOrderId = parse_url($returnUrl, PHP_URL_QUERY) ? '&orderId=' . $orderId : '?orderId=' . $orderId;
            $returnUrl .= $appendOrderId;
        } else  {
            $urlInfo = parse_url($returnUrl);
            parse_str($urlInfo['query']);
            if (!empty($url)) {
                $origUrl = $url;
                $appendOrderId = parse_url($url, PHP_URL_QUERY) ? '&orderId=' . $orderId : '?orderId=' . $orderId;
                $url .= $appendOrderId;
                $returnUrl = str_replace(urlencode($origUrl), urlencode($url), $returnUrl);
            } else {
                $returnUrl .= '&orderId=' . $orderId;
            }
        }
        return $returnUrl;
    }

    public function changeSrv($params, $userId)
    {
        //划转:未激活用户去开户激活
        if (in_array($params['srv'], ['transfer', 'transferWx'])) {
            $svInfo = $this->svInfo($userId);
            if (!empty($svInfo['isSvUser']) && $svInfo['isActivated'] == 0 ) {
                $params = [
                    'srv' => 'register',
                    'return_url' => $params['return_url'],
                ];
            }
        }
        Logger::info('changedParams:' . var_export($params, true));
        return $params;
    }

    // 线下充值
    public function offlineChargeForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $hasTitle = isset($this->params['hasTitle']) ? $this->params['hasTitle'] : 'Y';
        $reqSource = empty($this->params['reqSource']) ? (in_array($this->params['mobileType'], [11, 12]) ? 1 : 2) : $this->params['reqSource'];
        $params = [
            'userId' => $this->accountId,
            'reqSource' => $reqSource,
            'returnUrl' => $returnUrl,
            'orderId' => Idworker::instance()->getId(),
            'hasTitle' => $hasTitle,
            'transitToken' => $this->params['token'],
            'mobileType' => $this->formParams['mobileType'],
        ];
        return [$params];
    }

    // 网信大额充值-下单模式
    public function offlineChargeV3Form()
    {
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? intval($this->params['orderId']) : Idworker::instance()->getId();
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $hasTitle = isset($this->params['hasTitle']) ? $this->params['hasTitle'] : 'Y';
        $reqSource = empty($this->params['reqSource']) ? (in_array($this->params['mobileType'], [11, 12, 21]) ? 'APP' : 'PC') : $this->params['reqSource'];
        $wxVersion = !empty($this->params['wxVersion']) ? intval($this->params['wxVersion']) : 0;
        $bankCardId = !empty($this->params['bankCardId']) ? trim($this->params['bankCardId']) : ''; // 银行卡唯一标识
        $params = [
            'outOrderId' => $orderId,
            'userId' => $this->accountId,
            'wxVersion' => $wxVersion,
            'amount'  => $amount,
            'reqSource' => $reqSource,
            'returnUrl' => $returnUrl,
            'hasTitle' => $hasTitle,
            'bankCardId' => $bankCardId,
        ];
        if ($this->from != 'pc' && !empty($this->formParams['mobileType'])) {
            $params['mobileType'] = $this->formParams['mobileType'];
        }
        return [$params];
    }

    /**
     * 网贷大额充值表单
     */
    private function p2pOfflineChargeForm()
    {
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? intval($this->params['orderId']) : Idworker::instance()->getId();
        $wxVersion = !empty($this->params['wxVersion']) ? intval($this->params['wxVersion']) : 0;
        $params = [];
        $params['orderId'] = $orderId;
        $params['amount']  = $amount;
        $params['userId']  = $this->accountId;
        $params['wxVersion']  = $wxVersion;
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 企业用户充值表单
     */
    private function enterpriseChargeForm()
    {
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params = [];
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        $params['userId'] = $this->accountId;
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 大额转账记录查询界面
     */
    private function offlineChargeOrderPageForm()
    {
        $wxVersion = !empty($this->params['wxVersion']) ? intval($this->params['wxVersion']) : 0;
        $params = [];
        $params['userId'] = $this->accountId;
        $params['wxVersion'] = $wxVersion;
        return [array_merge($this->formParams, $params), $this->from];
    }
}
