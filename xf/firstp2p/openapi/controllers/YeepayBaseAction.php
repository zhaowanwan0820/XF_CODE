<?php
namespace openapi\controllers;

use openapi\controllers\BaseAction;
use libs\web\Form;
use core\service\YeepayPaymentService;

/**
 * YeepayBaseAction 
 * 易宝支付api，安全验证部分
 * 
 * @uses BaseAction
 * @package 
 * @version $id$
 * @author 郭峰<guofeng3@ucfgroup.com>
 */
class YeepayBaseAction extends BaseAction
{
    /**
     * 用户在redis里的订单信息
     * @var array
     */
    protected $_userRedisOrderInfo = array();

    /**
     * 默认的Form校验规则
     * @var array
     */
    public $sys_param_rules = array(
        'userClientKey' => array('filter' => 'required', 'message' => 'userClientKey is required')
    );

    public function _before_invoke() {
        return true;
    }

    /**
     * 鉴权认证
     */
    public function authCheck()
    {
        if (!$this->form instanceof Form) {
            $this->form = new Form();
            $this->form->sys_param_rules = $this->sys_param_rules;
        }
        if (!$this->form->validate()) {
            throw new \Exception('ERR_SYSTEM');
        }

        $redis = YeepayPaymentService::getRedisSentinels();
        if (!$redis)
        {
            throw new \Exception('暂无可用的存储服务，请稍后再试');
        }
        $data = $this->form->data;
        if (!isset($data['userClientKey']) || empty($data['userClientKey']))
        {
            throw new \Exception('ERR_SIGNATURE_NULL');
        }

        // 获取redis中用户的订单信息
        $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_ORDER_API, $data['userClientKey']);
        $userRedisOrderInfo = $redis->hGetAll($cacheKey);
        if (empty($userRedisOrderInfo) || !isset($userRedisOrderInfo['orderId']) || empty($userRedisOrderInfo['orderId']))
        {
            throw new \Exception('ERR_SIGNATURE_FAIL');
        }

        $this->_userRedisOrderInfo = $userRedisOrderInfo;
        $this->_user_id = isset($userRedisOrderInfo['userId']) ? $userRedisOrderInfo['userId'] : 0;
        return true;
    }

    /**
     * 获取用户缓存中的订单信息
     * @return array
     */
    public function getUserRedisOrderInfo()
    {
        return $this->_userRedisOrderInfo;
    }

    /**
     * 获取临时Token
     * @return string
     */
    public function getAsgnToken($sessionId = 'openapi_cr_asgn')
    {
        return \es_session::get($sessionId);
    }

    /**
     * 设置临时Token
     * @return string
     */
    public function setAsgnToken($sessionId = 'openapi_cr_asgn')
    {
        $asgn = md5(uniqid());
        \es_session::set($sessionId, $asgn);
        return $asgn;
    }

    /**
     * 计算Signature
     * @param array $params
     * @return string
     */
    public function getSignature($params, $appSecret)
    {
        if (isset($params['sign']))
        {
            unset($params['sign']);
        }
        ksort($params);
        reset($params);
        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            if (!is_null($value))
            {
                $paramsJoin[] = $key . $value;
            }
        }
        $paramsString = $appSecret . implode('', $paramsJoin) . $appSecret;
        return strtoupper(md5($paramsString));
    }

    /**
     * 校验签名是否正确
     * @param array $params
     * @param string $signature
     * @param string $appSecret
     * @return boolean
     */
    public function verifySignature($params, $signature, $appSecret)
    {
        $signatureLocal = $this->getSignature($params, $appSecret);
        return $signatureLocal === $signature;
    }

    /**
     * 校验client_id以及sign的方法
     * @throws \Exception
     */
    public function verifyClientSignature()
    {
        if (!$this->clientConf)
        {
            $this->_getClientConf();
        }
        if (empty($this->_client_id) || (!$this->clientConf))
        {
            throw new \Exception('ERR_SYSTEM_CLIENTID');
        }

        $req = $this->form->data;
        if (empty($req['sign'])) {
            throw new \Exception('ERR_SYSTEM_SIGN_NULL');
        }
        if (is_object($req)) {
            $req = (array) $req;
        }

        // 校验签名
        if (!$this->verifySignature($req, $req['sign'], $this->clientConf['client_secret']))
        {
            throw new \Exception('ERR_SYSTEM_SIGN');
        }
        return true;
    }
}
