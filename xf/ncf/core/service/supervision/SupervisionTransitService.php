<?php
namespace core\service\supervision;

use core\enum\DealEnum;
use libs\db\Db;
use libs\common\ErrCode;
use libs\utils\Logger;
use libs\utils\ABControl;
use core\service\supervision\SupervisionBaseService;
use core\service\supervision\SupervisionService;
use core\enum\SupervisionEnum;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Risk;

/**
 * 存管中转站服务
 */
class SupervisionTransitService extends SupervisionBaseService
{
    public static $superSrvs = ['superInfo', 'bindcard', 'cardValidate', 'h5authCard', 'offlineCharge']; //超级账户Srv

    static $formServer = [
        'register' => [ // 个人/港澳台/企业用户开户
            'class' => 'core\service\supervision\SupervisionAccountService',
            'fun' => 'memberRegisterPage'
        ],
        'registerStandard' => [
            'class' => 'core\service\supervision\SupervisionAccountService',
            'fun' => 'memberStandardRegisterPage',
        ],
        'charge' => [ // 充值
            'class' => 'core\service\supervision\SupervisionFinanceService',
            'fun' => 'charge'
        ],
        'withdraw' => [ // web/h5验密提现至银行卡
            'class' => 'core\service\supervision\SupervisionFinanceService',
            'fun' => 'secretWithdraw'
        ],
        'bid' => [ // 银行验密投资前的调用生成表单并保存订单信息
            'class' => 'core\service\deal\P2pDealBidService',
            'fun' => 'dealBidSecretRequest'
        ],
        'dtbid' => [ // 智多鑫银行验密投资前的调用生成表单并保存订单信息
            'class' => 'core\service\duotou\DtP2pDealBidService',
            'fun' => 'dealBidSecretRequest'
        ],
        'info' => [ // 存管银行个人页面
            'class' => 'core\service\supervision\SupervisionAccountService',
            'fun' => 'memberInfo'
        ],
        'authCreate' => [ // 开通授权
            'class' => 'core\service\supervision\SupervisionAccountService',
            'fun' => 'memberAuthorizationCreate'
        ],
        'p2pOfflineCharge' => [ // 网贷大额充值
            'class' => 'core\service\supervision\SupervisionFinanceService',
            'fun' => 'offlineCharge'
        ],
        'enterpriseCharge' => [ // 企业用户充值
            'class' => 'core\service\supervision\SupervisionFinanceService',
            'fun' => 'enterpriseCharge'
        ],
    ];

    private $serverClass;
    private $fun;
    private $params = [];
    private $formParams = [];
    private $accountId; //账户Id
    private $from = 'pc';

    public function changeSrv($params, $accountId)
    {
        //划转:未激活用户去开户激活
        if (in_array($params['srv'], ['transfer', 'transferWx'])) {
            $supervisionService = new SupervisionService();
            $svInfo = $supervisionService->svInfo($accountId);
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

    /**
     * 获取表单
     */
    public function formFactory($server, $accountId, $p = [], $from = 'h5')
    {
        //存管服务降级 排除理财账户srv
        if (!in_array($server, self::$superSrvs) && SupervisionService::isServiceDown()) {
            return ['status' => 0, 'msg' => SupervisionService::maintainMessage()];
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
        if (isset($this->params['notice_url'])) {
            $this->formParams['noticeUrl'] = $this->params['notice_url'];
        }
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
        if ($res['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
            if ($res['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/registerNotify';
        }
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
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/registerStandardNotify';
        }
        if (!empty($this->params['bankCardNo'])) {
            $this->formParams['bankCardNo'] = $this->params['bankCardNo'];
        }
        if (!empty($this->params['bankCardPhone'])) {
            $this->formParams['bankCardPhone'] = $this->params['bankCardPhone'];
        }
        return [$this->accountId, $this->from, $this->formParams];
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
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/chargeNotify';
        }
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
        $params['returnUrl'] = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/withdrawNotify';
        }
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
            'canUseBonus' => isset($this->params['canUseBonus']) ? $this->params['canUseBonus'] : DealEnum::CAN_USE_BONUS,
            'ip' => get_real_ip(), // 异步回调会存成支付机器ip，故在验密表单获取
            'fingerprint' => isset($this->params['fingerprint']) ? $this->params['fingerprint'] : Risk::getFinger(),
            ];

        $dealId = $this->params['dealId'];
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : '';
        $noticeUrl = isset($this->params['notice_url']) ? $this->params['notice_url'] : app_conf('NOTIFY_DOMAIN') . '/supervision/investCreateNotify';

        if ($returnUrl && in_array($this->params['mobileType'], ['21', '22'])) {
            $appendOrderId = parse_url($returnUrl, PHP_URL_QUERY) ? '&orderId=' . $orderId : '?orderId=' . $orderId;
            $returnUrl .= $appendOrderId;
        }

        $optionParams = ['returnUrl' => $returnUrl,'mobileType' => $this->params['mobileType'],'platform' => $this->from,'noticeUrl' => $noticeUrl];

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
        $noticeUrl = !empty($this->params['notice_Url']) ? $this->params['notice_Url'] :app_conf('NOTIFY_DOMAIN') . '/supervision/bookfreezeCreateNotify';

        if(strtolower($this->from) == 'pc'){
            $returnUrl = '';
        }

        $dealId = $this->params['dealId'];
        $optionParams = ['returnUrl' => $returnUrl,'mobileType' => $this->formParams['mobileType'],'platform' => $this->from,'noticeUrl' =>$noticeUrl];
        return [$orderId,$dealId, $this->accountId, $this->params['money'], $bidParams,$optionParams];
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

    /**
     * 账户明细表单
     */
    private function infoForm()
    {
        return [$this->accountId, $this->from, $this->formParams];
    }

    /**
     * 添加用户授权
     */
    private function authCreateForm()
    {
        $returnUrl = isset($this->params['return_url']) ? $this->params['return_url'] : ''; //回调地址
        $grantList = isset($this->params['grant_list']) ? $this->params['grant_list'] : ''; //授权名称
        $noticeUrl = !empty($this->params['notice_url']) ? $this->params['notice_url'] : app_conf('NOTIFY_DOMAIN') . '/supervision/memberAuthorizationCreateNotify';
        $params = [
            'userId' => $this->accountId,
            'grantList' => $grantList,
            'returnUrl' => $returnUrl,
            'noticeUrl' => $noticeUrl,
            'mobileType' => $this->params['mobileType'],
        ];
        return [$params, $this->from];
    }

    /**
     * 网贷大额充值表单
     */
    private function p2pOfflineChargeForm()
    {
        $params = [];
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        $params['userId'] = $this->accountId;
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/offlineChargeNotify';
        }
        return [array_merge($this->formParams, $params), $this->from];
    }

    /**
     * 企业用户充值表单
     */
    private function enterpriseChargeForm()
    {
        $params = [];
        $amount = isset($this->params['amount']) ? intval(bcmul($this->params['amount'], 100, 0)) : 0; //元转为分
        $orderId = !empty($this->params['orderId']) ? $this->params['orderId'] : Idworker::instance()->getId();
        $params['orderId'] = $orderId;
        $params['amount'] = $amount;
        $params['userId'] = $this->accountId;
        if (empty($this->formParams['noticeUrl'])) {
            $this->formParams['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/enterpriseChargeNotify';
        }
        return [array_merge($this->formParams, $params), $this->from];
    }
}
