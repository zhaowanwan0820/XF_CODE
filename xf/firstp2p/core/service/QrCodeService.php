<?php
/**
 * 二维码登录服务
 * @date 2018-12-28
 */
namespace core\service;

use NCFGroup\Common\Library\Idworker;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\utils\Monitor;
use core\service\user\BOFactory;
use NCFGroup\Common\Library\Status\Status;

/**
 * Class QrCodeService
 * @package core\service
 */
class QrCodeService extends BaseService {
    /**
     * 二维码登录的缓存键值
     * @var string
     */
    const QRCODE_LOGIN_KEY = 'QRCODE_LOGIN_KEY_%s';

    /**
     * 二维码扫码入口的缓存键值
     * @var string
     */
    const QRCODE_REFERER_KEY = 'QRCODE_REFERER_KEY_%d';

    /**
     * 二维码登录的缓存过期时间，60秒
     * @var integer
     */
    const QRCODE_LOGIN_KEY_EXPIRE_TIME = 60;

    /**
     * PC端的cookie键值
     * @var string
     */
    const QRTOKEN_COOKIE_KEY = 'qr_tk';

    /**
     * PC端cookie的缓存过期时间，60秒
     * @var integer
     */
    const QRTOKEN_COOKIE_EXPIRE_TIME = 60;

    /**
     * 二维码未认证
     * @var integer
     */
    const QRCODE_STATUS_VALID = 0;

    /**
     * 二维码认证中
     * @var integer
     */
    const QRCODE_STATUS_ING = 1;

    /**
     * 二维码认证成功/登录成功
     * @var integer
     */
    const QRCODE_STATUS_SUCCESS = 2;

    /**
     * 二维码已失效
     * @var integer
     */
    const QRCODE_STATUS_INVALID = 3;

    /**
     * 请求长连接服务重试次数
     */
    const RETRY_TIMES = 3;

    /**
     * 二维码扫码状态映射
     * @var array
     */
    public static $qrCodeStatusMap = [
        self::QRCODE_STATUS_VALID => '二维码未认证',
        self::QRCODE_STATUS_ING => '二维码认证中',
        self::QRCODE_STATUS_SUCCESS => '二维码认证成功',
        self::QRCODE_STATUS_INVALID => '二维码已失效',
    ];

    // 扫码入口-APP快捷支付
    const QRREF_QUICK = 'quick';

    // 扫码入口-APP大额充值
    const QRREF_OFFLINE = 'offline';

    // 扫码入口-APP个人中心
    const QRREF_CENTER = 'center';

    /**
     * 二维码扫码入口映射
     * @var array
     */
    public static $qrRefStatusMap = [
        self::QRREF_QUICK => 'APP快捷支付入口',
        self::QRREF_OFFLINE => 'APP大额充值入口',
        self::QRREF_CENTER => 'APP个人中心入口',
    ];

    // 调用长连接的前缀-扫一扫接口
    const LONGCONNECTION_SCAN = 'SCAN';

    // 调用长连接的前缀-确认登录接口
    const LONGCONNECTION_LOGIN = 'LOGIN';

    public static function getRedis() {
        return \SiteApp::init()->dataCache->getRedisInstance();
    }

    /**
     * 生成二维码唯一加密串
     */
    public static function getQrToken() {
        $qrToken = Idworker::instance()->getId();
        return md5('QRTOKEN_' . $qrToken);
    }

    /**
     * 生成二维码唯一加密串
     */
    public static function getQrTokenByCookie() {
        return \es_cookie::get(self::QRTOKEN_COOKIE_KEY);
    }

    /**
     * 初始化二维码唯一加密串
     */
    public static function initQrToken() {
        // 生成二维码唯一加密串
        $qrToken = self::getQrToken();

        // 设置二维码cookie
        self::setQrCookie($qrToken);

        // 获取redis对象
        $redis = self::getRedis();

        // 生成sessionId
        $sessionId = \es_session::id();
        PaymentApi::log(sprintf('%s, qrToken：%s, sessionId：%s，获取二维码成功', __METHOD__, $qrToken, $sessionId));

        // 初始化二维码登录信息
        $qrRet = self::setQrInfo($qrToken, ['sessionId'=>$sessionId, 'status'=>self::QRCODE_STATUS_VALID], self::QRCODE_LOGIN_KEY_EXPIRE_TIME);
        return $qrRet ? $qrToken : false;
    }

    /**
     * 设置二维码cookie
     * @param string $qrToken
     */
    public static function setQrCookie($qrToken) {
        // 记录cookie
        return \es_cookie::set(self::QRTOKEN_COOKIE_KEY, md5('WXLC_QrCode_' . $qrToken), date('Z') + self::QRTOKEN_COOKIE_EXPIRE_TIME, '', '', false);
    }

    /**
     * 获取二维码缓存信息
     * @param string $qrToken 唯一加密串
     */
    public static function getQrInfo($qrToken) {
        if (empty($qrToken)) {
            return false;
        }

        // 获取redis对象
        $redis = self::getRedis();

        // 获取二维码缓存信息
        $key = sprintf(self::QRCODE_LOGIN_KEY, $qrToken);
        $qrJson = $redis->get($key);
        if (empty($qrJson)) {
            return [];
        }
        return json_decode($qrJson, true);
    }

    /**
     *
     * 设置二维码缓存信息
     * @param string $qrToken 唯一加密串
     * @param array $qrInfo 二维码登录信息
     * @param int $expireTime 二维码超时时间
     */
    public static function setQrInfo($qrToken, array $qrInfo, $expireTime = 0) {
        // 获取redis对象
        $redis = self::getRedis();
        $key = sprintf(self::QRCODE_LOGIN_KEY, $qrToken);

        // 设置二维码cookie
        self::setQrCookie($qrToken);

        // 记录日志
        PaymentApi::log(sprintf('%s, qrToken：%s, qrInfo：%s，设置二维码扫码信息', __METHOD__, $qrToken, json_encode($qrInfo)));
        if ($expireTime) {
            return $redis->setex($key, $expireTime, json_encode($qrInfo));
        }
        return $redis->set($key, json_encode($qrInfo));
    }

    /**
     * 销毁二维码登录信息
     * @param string $qrToken 唯一加密串
     */
    public static function clearQrInfo($qrToken) {
        // 记录cookie
        if (\es_cookie::is_set(self::QRTOKEN_COOKIE_KEY)) {
            \es_cookie::delete(self::QRTOKEN_COOKIE_KEY);
        }

        // 获取redis对象
        $redis = self::getRedis();
        $key = sprintf(self::QRCODE_LOGIN_KEY, $qrToken);
        return $redis->del($key);
    }

    /**
     * 二维码认证成功之后自动登录
     * @param string $qrToken
     * @return array
     */
    public static function qrTokenAutoLogin($qrToken, $userName, $userPwd, $qrInfo = []) {
        // 获取二维码缓存信息
        if (empty($qrInfo)) {
            $qrInfo = self::getQrInfo($qrToken);
        }
        if (empty($qrInfo) || empty($qrInfo['userId']) || empty($qrInfo['sessionId'])) {
            throw new \Exception('二维码已过期，请重新获取');
        }

        // 检查用户是否已经确认登录
        if ($qrInfo['status'] != self::QRCODE_STATUS_ING) {
            throw new \Exception('登录失败，请重试');
        }
        if (empty($userName) || empty($userPwd)) {
            throw new \Exception('用户未登录');
        }

        $userInfo = array(
            'id' => intval($qrInfo['userId']),
            'auto_login' => 1,  // 设置自动登录标识
            'user_name' => $userName,
            'password' => $userPwd,
        );

        $oldSessionId = self::switchSession($qrInfo['sessionId']);

        $bo = BOFactory::instance('web');
        $ret = $bo->doLogin($userInfo);
        if ($ret['code'] != 0) {
            $msg = isset($ret['msg']) ? $ret['msg'] : '登录失败';
            throw new \Exception($msg);
        }

        $longConnectionLoginKey = sprintf('%s_%s', self::LONGCONNECTION_LOGIN, $qrToken);
        if (!self::remoteStatusUpdate($longConnectionLoginKey, self::QRCODE_STATUS_SUCCESS)) {
            throw new \Exception('扫码失败，请重试');
        }
        self::switchSession($oldSessionId, $qrInfo['sessionId']);

        // 销毁二维码登录信息
        self::clearQrInfo($qrToken);

        return true;
    }

    /**
     * 向长连接服务同步状态
     *
     * @param string $qrToken
     * @param int $status
     */
    public static function remoteStatusUpdate($qrToken, $status) {
        // 请求长连接服务修改状态 重试3次 抵御网络抖动
        $result = array();
        for ($i = 0; $i < self::RETRY_TIMES; $i++) {
            $result = Status::set($qrToken, $status);
            if ($result) {
                break;
            }
        }

        PaymentApi::log(sprintf('%s, qrtoken:%s, status:%s, result:%s', __METHOD__, $qrToken, $status, json_encode($result, JSON_UNESCAPED_UNICODE)));
        if (!isset($result['code'])) {
            // 记录告警
            Alarm::push('QrCodeServer', __METHOD__, sprintf('调用二维码长连接服务异常|qrtoken:%s，status:%s，result:%s', $qrToken, $status, json_encode($result)));
            // 添加监控
            Monitor::add('QRSERVER_REQUEST_LONGCONNECTION_ERR');
            PaymentApi::log(sprintf('%s, qrtoken:%s, status:%s, 长连接服务返回错误格式错误', __METHOD__, $qrToken, $status), Logger::ERR);
            return false;
        }

        if ($result['code'] == 0) {
            return true;
        } else {
            $msg = isset($result['message']) ? $result['message'] : '长连接服务异常';
            PaymentApi::log(sprintf('%s, qrtoken:%s, status:%s, StatusMessage:%s', __METHOD__, $qrToken, $status, $msg));
            return false;
        }
    }

    /**
     * 切换到指定id的session
     *
     * @param string $newSessionId
     * @param string $oldSessionId
     */
    public static function switchSession($newSessionId, $oldSessionId = '') {
        empty($oldSessionId) && $oldSessionId = session_id();
        if ($oldSessionId != '') {
            session_write_close();
        }
        session_id($newSessionId);
        session_start();
        return $oldSessionId;
    }

    /**
     * 获取扫码来源标识缓存信息
     * @param int $userId 用户ID
     */
    public static function getQrRefInfo($userId) {
        if (empty($userId)) {
            return false;
        }

        // 获取redis对象
        $redis = self::getRedis();

        // 获取缓存信息
        $key = sprintf(self::QRCODE_REFERER_KEY, $userId);
        $qrJson = $redis->get($key);
        if (empty($qrJson)) {
            return [];
        }
        return json_decode($qrJson, true);
    }

    /**
     *
     * 设置扫码来源标识缓存信息
     * @param int $userId 用户ID
     * @param array $qrRefInfo 二维码扫码入口信息
     * @param int $expireTime 二维码超时时间
     */
    public static function setQrRefInfo($userId, $qrRefInfo, $expireTime = 180) {
        // 获取redis对象
        $redis = self::getRedis();
        $key = sprintf(self::QRCODE_REFERER_KEY, $userId);

        PaymentApi::log(sprintf('%s, userId：%d, qrRefInfo：%s，设置扫码来源标识', __METHOD__, $userId, json_encode($qrRefInfo)));
        if ($expireTime) {
            return $redis->setex($key, $expireTime, json_encode($qrRefInfo));
        }
        return $redis->set($key, json_encode($qrRefInfo));
    }

    /**
     * 销毁扫码来源标识信息
     * @param int $userId 用户ID
     */
    public static function clearQrRefInfo($userId) {
        // 获取redis对象
        $redis = self::getRedis();
        $key = sprintf(self::QRCODE_REFERER_KEY, $userId);
        return $redis->del($key);
    }
}