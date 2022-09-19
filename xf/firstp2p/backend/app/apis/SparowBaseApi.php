<?php

namespace NCFGroup\Ptp\Apis;

use libs\utils\Logger;
use libs\rpc\Rpc;
use core\service\UserService;
use core\service\WeiXinService;
use api\conf\Error;

class SparowBaseApi
{
    const RDS_KEY_REPLAY = 'FIRSTP2P_API_REPLAY_%s_%s';

    const SALT_NCFWX = 'VAjHTxJ6JyjZDF6o';

    protected $saltDict = [
        'ncfwx' => self::SALT_NCFWX,
    ];

    public function __construct()
    {
        $this->request = getDI()->get('request');
        $this->input = file_get_contents('php://input');
        $this->input = json_decode($this->input, true);

        if ($this->request->isPost()) {
            $this->req = $this->request->getPost();
        } else {
            $this->req = $this->request->getQuery();
        }
        unset($this->req['_url']);
        unset($this->req['ctl']);
        unset($this->req['act']);
        unset($this->req['city']);
        Logger::info(SparowBaseApi::logFilter(implode('|', [__METHOD__, json_encode($this->req)])));

        // if (app_conf('ENV_FLAG') == 'dev') return;

        // $from = $this->req['from'] ?: 'ncfwx';
        $salt = $this->saltDict['ncfwx'];
        if (empty($salt)) return $this->echoJson(10001, '非法来源');

        try {
            $this->check($this->req, $salt);
        } catch (\Exception $e) {
            $this->echoJson($e->getCode(), $e->getMessage());
        }
    }

    protected function echoJson($code, $msg = '', $data = '')
    {
        $this->error = $msg;
        $this->errno = $code;
        $this->json_data = $data;
        header('Content-type: application/json;charset=UTF-8');
        $json = array('code' => $code, 'msg' => $msg, 'data' => $data);
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        die;
    }


    public function getTimestamp()
    {
        return intval(microtime(true) * 1000);
    }

    public function sign($params, $salt)
    {
        if (isset($params['sign'])) unset($params['sign']);

        ksort($params);
        $temp = '';
        foreach ($params as $k => $val) {
            $val = urlencode($val);
            $temp .= "&{$k}={$val}";
        }

        $temp = substr($temp, 1);
        $temp .= $salt;
        // var_dump($temp);
        // Logger::info(implode('|', [__METHOD__, $temp]));
        return strtoupper(md5($temp));
    }

    public function check($params, $salt)
    {
        $sign = $params['sign'];
        $timestamp = $params['timestamp'];

        $now = self::getTimestamp();
        if ($now - $timestamp > 60000) {
            throw new \Exception("时间过期失效", 50001);
        }

        if ($sign != self::sign($params, $salt)) {
            throw new \Exception("验签失败", 50003);
        }

        $rds = \SiteApp::init()->dataCache->getRedisInstance();
        $key = sprintf(self::RDS_KEY_REPLAY, $salt, $sign);

        if ($rds->exists($key)) {
            throw new \Exception("链接已失效", 50002);
        }
        $rds->setex($key, 60, 1);
    }


    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @param string $token
     * @return bool|array
     */
    protected function getUserByToken($need_err = true, $token = '')
    {
        $isWeixin = isset($this->req['weixinOpenId']) ? true : false;
        $isMobile = isset($this->req['mobile']) ? true : false;
        $userId = isset($this->req['userId']) ? intval($this->req['userId']) : 0;
        if ($userId > 0) {
            return (new UserService)->getUser($userId);
        }

        if ($isMobile) { // 手机模式优先级高于微信openID

            $mobile = $this->req['mobile'];
            $userInfo = (new UserService)->getUserByMobile($mobile);

            if (empty($userInfo)) {
                return [
                    'mobile' => $mobile,
                ];
            }
            return $userInfo;

        } else if ($isWeixin) { // token为openId
            $openId = $this->req['weixinOpenId'];
            $res = (new \core\service\BonusBindService)->getBindInfoByOpenid($openId);
            if (empty($res)) return false;

            $mobile = $res['mobile'];
            $userInfo = (new UserService)->getUserByMobile($mobile);

            if (empty($userInfo)) {
                return [
                    'mobile' => $mobile,
                ];
            }
            return $userInfo;

        } else {
            $token = isset($this->req['token']) ? $this->req['token'] : (!empty($token) ? $token : '');
            if (empty($token)) {
                return false;
            }
            $token_info = (new UserService)->getUserByCode($token);
            if (!empty($token_info['code'])) {
                return false;
            }
            return $token_info['user'];
        }

    }

    public function setErr($err, $error = "") {
        $arr = Error::get($err);
        $errno = $arr["errno"] ?: -1;
        $error = empty($error) ? $arr["errmsg"] : $error;
        throw new \Exception($error, $errno);
    }

    public static function logFilter($msg)
    {
        $preg = [
            '#"password":"(.*?)"#',
            '#("mobile.*?":"\d{3})(\d+?)(\d{4})"#',
        ];
        $replace = [
            '"password":"***"',
            '${1}****${3}',
        ];
        return preg_replace($preg, $replace, $msg);
    }

}
