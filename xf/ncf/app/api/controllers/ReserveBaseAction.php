<?php

namespace api\controllers;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\reserve\UserReservationService;

/**
 * ReserveBaseAction
 * 预约标Action
 *
 * @uses BaseAction
 * @package
 * @version $id$
 */
class ReserveBaseAction extends AppBaseAction
{
    protected $needAuth = false;
    protected $useSession = true;
    protected $isReserve = true;

    /**
     * 预约按钮是否置灰
     * @var int
     */
    protected $_isBookingButtonUnused = 0;

    /**
     * 预约标相关配置
     * @var array
     */
    protected $reserveConfig = array(
        'title' => '随心约',
    );

    /**
     * 用户的基本信息
     * @var array
     */
    protected $_userInfo = array();

    /**
     * 用户在redis里的信息
     * @var array
     */
    protected $_userRedisInfo = array();

    /**
     * 用户ID
     * @var int
     */
    protected $_userId = 0;

    /**
     * 预约相关密钥
     * @var string
     */
    protected $reserveSecKey = 'ReDLZAx7ySe8y2NS+B5D5Zw==';

    /**
     * 默认的Form校验规则
     * @var array
     */
    public $sys_param_rules = array(
        'userClientKey' => array('filter' => 'string', 'option' => array('optional' => true)),
    );

    public function _before_invoke() {
        //初始化预约按钮开关
        $siteId = \libs\utils\Site::getId();
        $this->_isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);

        if (!$this->form instanceof Form) {
            $this->form = new Form();
            $this->form->sys_param_rules = $this->sys_param_rules;
        }

        if (!$this->form->validate()) {
            $this->setErr('ERR_SYSTEM', $this->form->getErrorMsg());
        }

        $data = $this->form->data;
        if (!$this->isWapCall()) {
            // 对于app的api的处理
            $this->app_version = $this->getAppVersion();
            // 跳转对应的wap页面
            if (!empty($this->redirectWapUrl)) {
                if (substr($this->redirectWapUrl, 0, 1) == '/') {
                    $url = app_conf('NCFPH_WAP_URL').$this->redirectWapUrl;
                } else {
                    $url = $this->redirectWapUrl;
                }

                if (strpos($url, '?') === false) {
                    $url .= '?'.http_build_query($data, '', '&');
                }

                return app_redirect($url);
            }
        } else {
            $this->app_version = 99999;
        }

        if (!empty($data['token'])) {
            // 检查用户token是否已过期，获取用户信息
            $userInfo = $this->getUserByTokenForH5($data['token']);
            $this->_userId = $userInfo['id'];
            //特殊用户处理
            if (\libs\utils\Block::isSpecialUser($this->_userId)) {
                define('SPECIAL_USER_ACCESS', true);
                if (\libs\utils\Block::checkAccessLimit($this->_userId) === false) {
                    throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
                }
            }

            $this->_userInfo = $userInfo;
            return true;
        }

        // 获取redis中用户的订单信息
        $keyField = '';
        if(isset($data['userClientKey']) && strpos($data['userClientKey'], '_') !== false) {
            list($keyPrifix, $userClientKey) = explode('_', $data['userClientKey']);
            switch ($keyPrifix) {
            case 'YYB': // 预约标
                $keyField = UserReservationService::CACHEKEY_YYB_API;
                break;
            case 'VISITOR': // 访客浏览
                if (!\libs\utils\Signature::verify(array('f'=>$keyPrifix, 'userClientKey'=>$userClientKey), $this->reserveSecKey, 'userClientKey')) {
                    $this->setErr('ERR_SIGNATURE_FAIL'); // 签名证书不正确
                }
                return true;
                break;
            }
        }

        if (empty($keyField)) {
            $this->setErr('ERR_PARAMS_ERROR');
        }

        $cacheKey = sprintf($keyField, $data['userClientKey']);
        $userRedisInfo = \core\service\YeepayPaymentService::getRedisSentinels()->hGetAll($cacheKey);
        if (empty($userRedisInfo) || empty($userRedisInfo['userId']) || empty($userRedisInfo['token'])) {
            $this->setErr('ERR_MANUAL_REASON', '信息已失效，请重新操作');
        }

        $this->_userRedisInfo = $userRedisInfo;
        $this->_userId = isset($userRedisInfo['userId']) ? $userRedisInfo['userId'] : 0;
        //特殊用户处理
        if (\libs\utils\Block::isSpecialUser($this->_userId)) {
            define('SPECIAL_USER_ACCESS', true);
            if (\libs\utils\Block::checkAccessLimit($this->_userId) === false) {
                throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
            }
        }

        // 检查用户token是否已过期，获取用户信息
        $userInfo = $this->getUserByTokenForH5($userRedisInfo['token']);
        $this->_userInfo = $userInfo;
        return true;
    }

    public function _after_invoke() {
        $this->json_data['isBookingButtonUnused'] = $this->_isBookingButtonUnused;
        $this->json_data['title'] = !empty($this->reserveConfig['title']) ? $this->reserveConfig['title'] : '';
        parent::_after_invoke();
    }

    /**
     * 是否开放预约-系统维护的开关
     * @return boolean
     */
    public function isOpenReserve() {
        if((int)app_conf('YYB_SWITCH') === 0) {
            $this->setErr('ERR_SYSTEM', '系统维护中，请稍后再试！');
        }
        return true;
    }

    /**
     * 是否开启存管系统预约(0:关闭1:开启)
     * @return boolean
     */
    public function isOpenSupervisionReserve() {
        if((int)app_conf('SUPERVISION_RESERVE_SWITCH') === 0) {
            return false;
        }
        return true;
    }

    /**
     * 是否关闭预约-仅预约按钮置灰的开关
     * @return boolean
     */
    public function canReserve() {
        $siteId = \libs\utils\Site::getId();
        $isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);
        if($isBookingButtonUnused === 1) {
            $this->setErr('ERR_SYSTEM', '预约服务优化中，请稍后再试！');
        }
        return true;
    }

    /**
     * 设置UserClientKey前缀
     * @param string $token
     * @param string $prefix
     */
    public function setUserClientKeyPrefix($token, $prefix = 'YYB')
    {
        return sprintf('%s_%s', $prefix, md5($token));
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
     * 获取用户缓存中的信息
     * @return array
     */
    public function getUserRedisInfo()
    {
        return $this->_userRedisInfo;
    }

    /**
     * 生成访客签名
     * @return string
     */
    public function generateVisitorSignature($field = 'VISITOR')
    {
        return \libs\utils\Signature::generate(array('f'=>$field), $this->reserveSecKey);
    }

    /**
     * Gererate User Client Key
     */
    public static function genUserClientKey($token, $userId) {
        $redis = \core\service\YeepayPaymentService::getRedisSentinels();
        $userClientKey = sprintf('%s_%s', 'YYB', md5($token));
        $cacheKey = sprintf(UserReservationService::CACHEKEY_YYB_API, $userClientKey);
        $cacheData = array(
            'userId' => $userId,
            'token' => $token,
        );
        $redis->hMset($cacheKey, $cacheData);
        $redis->expire($cacheKey, 86400);
        return $userClientKey;
    }
}
