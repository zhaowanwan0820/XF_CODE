<?php

/**
 * @author YiXiao，<yixiao@ucfgroup.com>
 * @date  2014-2-25 18:03:53
 * @encode UTF-8编码
 */
class P_Session_Oauth {

    private $_client_id;
    private $_curl_opts = array(
        CURLOPT_HEADER => 0,
        CURLOPT_HTTPHEADER => array('Content-Type:application/xml;charset=utf-8'),
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => array(),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => '',
    );
    private $_errno = '';
    private $_error = '';
    private static $_instance = null;

    private function __construct($client_id) {
        $this->_client_id = $client_id;
    }

    private function _build_data($data) {
        if (is_string($data)) {
            return sprintf(P_Conf_Oauth::OAUTH_XML, $data);
        } else {
            return $data;
        }
    }

    private function _build_params($params) {
        foreach ($params as $k => $v) {
            $params[$k] = htmlspecialchars($v);
        }
        return $params;
    }

    private function _build_url($method, $params = array()) {
        switch ($method) {
            case P_Conf_Oauth::OAUTH_FUNC_GET_CODE:
                if (!isset($params[0])) {
                    return false;
                }
                if (preg_match(P_Conf_Oauth::PREG_MOBILE, $params[0])) {
                    return P_Conf_Oauth::OAUTH_URL_PREFIX . P_Conf_Oauth::$url_infix[$method][0] . P_Conf_Oauth::OAUTH_URL_SUFFIX;
                } else if (preg_match(P_Conf_Oauth::PREG_EMAIL, $params[0])) {
                    return P_Conf_Oauth::OAUTH_URL_PREFIX . P_Conf_Oauth::$url_infix[$method][1] . P_Conf_Oauth::OAUTH_URL_SUFFIX;
                } else {
                    return false;
                }
                break;
            default:
                return P_Conf_Oauth::OAUTH_URL_PREFIX . P_Conf_Oauth::$url_infix[$method] . P_Conf_Oauth::OAUTH_URL_SUFFIX;
                break;
        }
    }

    public function execute($request) {
        $ret = false;
        do {
            if (M::D('DEBUG')) {
                P_Log_Slogs::at(var_export($request, true));
            }
            if (!isset($request['method']) || !isset(P_Conf_Oauth::$url_infix[$request['method']])) {
                new P_Exception_Logic("未定义方法", P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
                break;
            }
            if (!isset($request['params']) || !is_array($request['params'])) {
                new P_Exception_Logic("无效的参数", P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
                break;
            }
            if (false === ($url = $this->_build_url($request['method'], $request['params']))) {
                new P_Exception_Logic("无效的方法或者参数", P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
                break;
            }
            if (false === ($data = @call_user_func_array(array($this, P_Conf_Oauth::OAUTH_METHOD_PREFIX . $request['method']), $this->_build_params($request['params'])))) {
                new P_Exception_Logic("无效的参数", P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
                break;
            }
            $ret = $this->_get_response($url, $this->_build_data($data));
            if (M::D('DEBUG')) {
                P_Log_Slogs::at(var_export($ret, true));
            }
            if (false === $ret) {
                break;
            }
            $ret = json_decode($ret, true);
            if (isset($ret['code']) && isset($ret['reason'])) {
                $this->_errno = strval($ret['code']);
                $this->_error = strval($ret['reason']);
                new P_Exception_Logic($this->_error, P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
                break;
            }
            if (isset($ret['result'])) {
                $ret = (boolean) $ret['result'];
            }
        } while (false);
        return $ret;
    }

    public function get_errno() {
        return $this->_errno;
    }

    public function get_error() {
        return $this->_error;
    }

    private function _get_response($url, $data) {
        $ret = false;
        do {
            if (false === ($ch = @curl_init())) {
                $this->_error = "初始化连接服务失败";
                break;
            }
            $this->_curl_opts[CURLOPT_POSTFIELDS] = $data;
            $this->_curl_opts[CURLOPT_URL] = $url;
            if (false === @curl_setopt_array($ch, $this->_curl_opts)) {
                $this->_error = "连接服务参数设置失败";
                break;
            }
            if (M::D('DEBUG')) {
                P_Log_Slogs::at(var_export($this->_curl_opts, true));
            }
            if (false === ($ret = @curl_exec($ch))) {
                $this->_error = "连接服务失败";
                break;
            }
            @curl_close($ch);
        } while (false);
        if ($this->_errno = @curl_errno($ch)) {
            $this->_error .= "，" . @curl_error($ch);
        }
        if (!empty($this->_errno)) {
            new P_Exception_Logic($this->_error, P_Conf_Globalerrno::INTERNAL_LOGIC_ERROR);
        }
        return $ret;
    }

    public static function init($client_id) {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($client_id);
        }
        return self::$_instance;
    }

    private function _oauth_check_user_info($user_name, $telephone, $email) {
        $user_name = !empty($user_name) ? "<username>{$user_name}</username>" : "";
        $email = !empty($email) ? "<email>{$email}</email>" : "";
        $telephone = !empty($telephone) ? "<telephone>{$telephone}</telephone>" : "";
        return "<UserInfo><client_id>{$this->_client_id}</client_id>{$user_name}{$email}{$telephone}</UserInfo>";
    }

    private function _oauth_forget_password($contact, $code, $password) {
        $data = "<pwd>{$password}</pwd><code>{$code}</code>";
        if (preg_match(P_Conf_Oauth::PREG_MOBILE, $contact)) {
            return "<PwdUser><tele>{$contact}</tele>{$data}<findpwdType>3</findpwdType></PwdUser>";
        } else if (preg_match(P_Conf_Oauth::PREG_EMAIL, $contact)) {
            return "<PwdUser><email>{$contact}</email>{$data}<findpwdType>1</findpwdType></PwdUser>";
        } else {
            return false;
        }
    }

    private function _oauth_get_code($contact, $behavior, $source = "众筹网") {
        if (preg_match(P_Conf_Oauth::PREG_MOBILE, $contact)) {
            return "<VerifyCodeParam><telephone>{$contact}</telephone><behavior>{$behavior}</behavior><source>{$source}</source></VerifyCodeParam>";
        } else if (preg_match(P_Conf_Oauth::PREG_EMAIL, $contact)) {
            return "<VerifyCodeEamilParam><email>{$contact}</email><behavior>{$behavior}</behavior><source>{$source}</source></VerifyCodeEamilParam>";
        } else {
            return false;
        }
    }

    private function _oauth_get_user_info($session_id) {
        return "<AuthorizeParam><client_id>{$this->_client_id}</client_id><code>{$session_id}</code><grant_type>authorization_code</grant_type></AuthorizeParam>";
    }

    private function _oauth_login($identity, $password) {
        return "<LoginParam><username>{$identity}</username><password>{$password}</password><response_type>code</response_type><client_id>{$this->_client_id}</client_id></LoginParam>";
    }

    private function _oauth_logout($session_id) {
        return "<LogoutParam><code>{$session_id}</code><client_id>{$this->_client_id}</client_id></LogoutParam>";
    }

    private function _oauth_modify_password($old_password, $new_password, $session_id) {
        return "<PwdUser><oldpwd>{$old_password}</oldpwd><pwd>{$new_password}</pwd><sessionid>{$session_id}</sessionid></PwdUser>";
    }

    private function _oauth_modify_user_name($new_user_name, $session_id) {
        return "<UpdateUserNameParam><client_id>{$this->_client_id}</client_id><username>{$new_user_name}</username><sessionid>{$session_id}</sessionid></UpdateUserNameParam>";
    }

    private function _oauth_register($contact, $code, $password, $username) {
        $data = "<code>{$code}</code><pwd>{$password}</pwd><username>{$username}</username><client_id>{$this->_client_id}</client_id>";
        if (preg_match(P_Conf_Oauth::PREG_MOBILE, $contact)) {
            return "<SysUser><telephone>{$contact}</telephone>{$data}</SysUser>";
        } else if (preg_match(P_Conf_Oauth::PREG_EMAIL, $contact)) {
            return "<SysUser><email>{$contact}</email>{$data}</SysUser>";
        } else {
            return false;
        }
    }

    private function _oauth_third_login($passport_id, $third_id, $third_token, $third_type) {
        return "<ThirdPartParam><passportid>{$passport_id}</passportid><uid>{$third_id}</uid><token>{$third_token}</token><source>{$third_type}</source><client_id>{$this->_client_id}</client_id></ThirdPartParam>";
    }

}
