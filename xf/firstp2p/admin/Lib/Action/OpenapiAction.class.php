<?php

/**
 * @abstract openapi 线上访问接口
 * @author yutao@ucfgroup.com
 * @date 2015-07-24
 */
class OpenapiAction extends CommonAction {

    private static $_openapiActionList = array('user/info', 'user/GetUserInfoByMobile', 'deals', 'deals/Detail');

    public function index() {
        $this->display();
    }

    /**
     * 生成openapi 公钥和私钥
     */
    public function genClient() {
        $clientId = $this->generateKey(true);
        $clientSecret = $this->generateKey();
        $this->assign('clientId', $clientId);
        $this->assign('clientSecret', $clientSecret);
        $this->display();
    }

    /**
     * openapi 生成client
     * @param type $unique
     * @return type
     */
    private function generateKey($unique = false) {
        $key = md5(base64_encode(uniqid(rand(), true)));
        if ($unique) {
            list($usec, $sec) = explode(' ', microtime());
            $key .= dechex($usec) . dechex($sec);
            $key = substr($key, 3, 24);
        }
        return $key;
    }

    /**
     * 实时请求openapi结果
     */
    public function access() {
        $openAction = $_GET['openAction'];
        $userId = $_GET['userId'];
        $params = $_GET['params'];
        if (!in_array($openAction, self::$_openapiActionList)) {
            echo '此action不允许模拟线上访问';
            return;
        }

        $paramArray = array();
        if (!empty($params)) {
            $paramArrayTmp = explode(',', $params . ',');
            if (count($paramArrayTmp) > 0) {
                foreach ($paramArrayTmp as $key => $value) {
                    $tmp = explode(':', $value);
                    if (isset($tmp[0]) && isset($tmp[1])) {
                        $paramArray[$tmp[0]] = $tmp[1];
                    }
                }
            }
        }
        $requestParam = array();
        //查询用户token
        if (!empty($userId)) {
            $sql = "SELECT access_token,client_id FROM oauth_token WHERE 1=1 ";
            $sql .= " AND user_id = '{$userId}' ";
            if (!empty($paramArray['client_id'])) {
                $sql .= " AND client_id = '" . $paramArray['client_id'] . "'";
            }
            $sql .= " ORDER BY id DESC";
            $ret = $GLOBALS['db']->get_slave()->getRow($sql);
            if (empty($ret)) {
                echo "此用户ID没有对应的token";
                return;
            }
            $requestParam['oauth_token'] = $ret['access_token'];
            $requestParam['client_id'] = $ret['client_id'];
        } else {
            $requestParam['client_id'] = '7b9bd46617b3f47950687351';
        }
        $requestParam['timestamp'] = date('Y-m-d H:i:s', time());
        $requestParam = array_merge($requestParam, $paramArray);
        $requestParam['sign'] = $this->auth($requestParam);
        $ret = \libs\utils\Curl::post('http://openapi.firstp2p.com/' . $openAction, $requestParam);
        if (!empty($ret)) {
            echo $ret;
        } else {
            echo '调用openapi错误，请手动调用';
        }
        return;
    }

    private function auth($param) {
        $client_id = $param['client_id'];
        $app_secret = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$client_id]['client_secret'];
        $sortedReq = $app_secret;
        ksort($param);
        reset($param);
        while (list ($key, $val) = each($param)) {
            if (!is_null($val)) {
                $sortedReq .= $key . $val;
            }
        }
        $sortedReq .= $app_secret;
        $sign_md5 = strtoupper(md5($sortedReq));
        return $sign_md5;
    }

}
