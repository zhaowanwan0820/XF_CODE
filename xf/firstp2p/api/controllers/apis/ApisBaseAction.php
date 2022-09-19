<?php

namespace api\controllers\apis;

use api\controllers\BaseAction;
use libs\web\Action;
use libs\utils\Logger;
use libs\rpc\Rpc;

class ApisBaseAction extends Action
{
    const RDS_KEY_REPLAY = 'FIRSTP2P_API_REPLAY_%s_%s';

    const SALT_MALL = 'bjwx';
    const SALT_NCFWX = 'VAjHTxJ6JyjZDF6o';
    const SALT_DIANXIAO = 'f4c13c4f4248';

    protected $generalFormRule = [
        'timestamp' => ['filter' => 'required', 'message' => '参数错误'],
        'sign' => ['filter' => 'required', 'message' => '参数错误'],
        'from' => ['filter' => 'string', 'message' => '参数错误', 'option' => ['optional' => true]],
    ];

    protected $fromSalt = [
        'ncfmall' => self::SALT_MALL, // 游戏平台生活
        'ncfwx' => self::SALT_NCFWX, // 游戏平台网信
        'dx' => self::SALT_DIANXIAO, // 电销中心
    ];

    public function _before_invoke()
    {
        $this->rpc = new Rpc();
        $keys = array_flip(array_keys($this->form->rules));

        $params = array_intersect_key($_REQUEST, $keys);

        try {

            $from = $params['from'] ?: 'ncfmall';
            $salt = $this->fromSalt[$from];
            $this->check($params, $salt);
        } catch (\Exception $e) {
            return $this->echoJson($e->getCode(), $e->getMessage());
        }
        return true;
    }

    protected function echoJson($code, $msg = '', $data = '')
    {
        $this->error = $msg;
        $this->errno = $code;
        $this->json_data = $data;
        header('Content-type: application/json;charset=UTF-8');
        $json = array('code' => $code, 'msg' => $msg, 'data' => $data);
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        return false;
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
        Logger::info(implode('|', [__METHOD__, $temp]));
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

    public function show_exception(\Exception $e)
    {
        return $this->echoJson('11001', $e->getMessage());
    }

    protected function logInit() {
        $post = $_POST;
        $post = cleanSensitiveField($post);

        $this->log = array(
            'level' => Logger::STATS,
            'platform' => 'api-apis',
            'errno' => '',
            'errmsg' => '',
            'ip' => get_client_ip(),
            'host' => $_SERVER['HTTP_HOST'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(1),
            'postParams' => $post,
            'getParams' => $_GET,
            'output' => '',
        );
    }

    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @param string $token
     * @return bool|array
     */
    protected function getUserByToken($need_err = true, $token = '') {
        $token = isset($this->form->data['token']) ? $this->form->data['token'] : (!empty($token) ? $token : '');
        if (empty($token)) {
            return false;
        }
        $token_info = $this->rpc->local('UserService\getUserByCode', array($token));
        if (!empty($token_info['code'])) {
            return false;
        }

        return $token_info['user'];
    }

    protected function log() {
        $this->log['errno'] = $this->errno;
        $this->log['errmsg'] = $this->error;
        $tmJsonData = cleanSensitiveField($this->json_data);
        $this->log['output'] = substr(json_encode($tmJsonData), 0, 900);
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process']) * 1000);
        $level = $this->log['level'];
        unset($this->log['level']);
        $_log = getLog();
        if (is_array($_log)) {
            $this->log = array_merge($this->log, $_log);
        }
        $jsonLog = json_encode($this->log, JSON_UNESCAPED_UNICODE);
        $jsonLog = str_replace('\/', '/', $jsonLog);
        call_user_func('\libs\utils\Logger::' . "STATS", $jsonLog);
    }
}
