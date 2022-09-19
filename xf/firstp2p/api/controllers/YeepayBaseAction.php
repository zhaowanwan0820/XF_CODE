<?php
namespace api\controllers;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\YeepayPaymentService;
use core\service\UserReservationService;

/**
 * YeepayBaseAction
 * 易宝Action
 *
 * @uses BaseAction
 * @package
 * @version $id$
 */
class YeepayBaseAction extends AppBaseAction
{
    /**
     * 用户的基本信息
     * @var array
     */
    protected $_userInfo = array();

    /**
     * 用户在redis里的订单信息
     * @var array
     */
    protected $_userRedisOrderInfo = array();

    /**
     * 用户ID
     * @var int
     */
    protected $_userId = 0;

    /**
     * 默认的Form校验规则
     * @var array
     */
    public $sys_param_rules = array(
        'userClientKey' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
    );

    protected $useSession = true;
    public function _before_invoke()
    {
        if (!$this->form instanceof Form)
        {
            $this->form = new Form();
            $this->form->sys_param_rules = $this->sys_param_rules;
        }
        if (!$this->form->validate())
        {
            $this->setErr('ERR_SYSTEM', $this->form->getErrorMsg());
            return false;
        }

        $this->app_version = $this->getAppVersion();
        $this->setAutoViewDir();

        $data = $this->form->data;
        // 获取redis中用户的订单信息
        $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $data['userClientKey']);
        $userRedisOrderInfo = YeepayPaymentService::getRedisSentinels()->hGetAll($cacheKey);
        if (empty($userRedisOrderInfo) || empty($userRedisOrderInfo['userId']) || empty($userRedisOrderInfo['token']))
        {
            $this->setErr('ERR_MANUAL_REASON', '订单信息已失效，请重新下单');
            return false;
        }
        $this->_userRedisOrderInfo = $userRedisOrderInfo;
        $this->_userId = isset($userRedisOrderInfo['userId']) ? $userRedisOrderInfo['userId'] : 0;

        //特殊用户处理
        if (\libs\utils\Block::isSpecialUser($this->_userId))
        {
            define('SPECIAL_USER_ACCESS', true);
            if (\libs\utils\Block::checkAccessLimit($this->_userId) === false)
            {
                throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
            }
        }

        // 检查用户token是否已过期，获取用户信息
        $userInfo = $this->getUserByTokenForH5($userRedisOrderInfo['token']);
        if (empty($userInfo))
        {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $this->_userInfo = $userInfo;
        return true;
    }

    public function _after_invoke()
    {
        $class = get_called_class();
        if ($class::IS_H5 && $this->errno == 0)
        {
            $this->tpl->display($this->template);
        } else {
            $arr_result = array();
            if ($this->errno == 0) {
                $arr_result['errno'] = 0;
                $arr_result['error'] = '';
                $arr_result['data'] = $this->json_data;
            } else {
                $arr_result['errno'] = $this->errno;
                $arr_result['error'] = $this->error;
                $arr_result['data'] = '';
            }
            if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
                var_export($arr_result);
            } else {
                header('Content-type: application/json;charset=UTF-8');
                echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * 获取用户的基本信息
     * @return array
     */
    public function getUserBaseInfo()
    {
        return $this->_userInfo;
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
}
