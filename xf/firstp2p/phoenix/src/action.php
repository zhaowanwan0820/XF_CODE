<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-12 10:31:22
 * @encode UTF-8编码
 */
abstract class P_Action {

    public $check_login = false;
    private $_content = '';
    public $csrf_check = false;
    public $csrf_token = false;
    public $errno = P_Conf_Globalerrno::OK;
    public $error = '';
    public $form_check = false;
    public $json_data = false;
    public $request = array();
    public $template = false;
    public $tpl_data = false;
    public $tpl_engine = P_Conf_Template::ENGINE_APP_TEMPLATE;
    private $_tpl_obj = false;
    public $tpl_handler = false;
    public $user = false;
    public $session_handler = false;
    public $xid_map = false;
    private $_xid_objs = array();

    public function __construct() {
        $this->xid_map = M::C('xid_map');
    }

    private function _after_invoke() {
        if ((false === $this->json_data && false === $this->tpl_data) || (false === $this->template && false !== $this->tpl_data)) {
            $this->errno = P_Exception_Handler::get_last_errno();
            $this->error = P_Exception_Handler::get_last_error();
        }
        $this->_convert_xid();
        $this->_display();
        P_Log_Slogs::write();
        echo $this->_content;
    }

    private function _before_invoke() {
        if ($this->form_check !== false && is_array($this->form_check) && false === $this->_check_form($this->form_check)) {
            return false;
        }
        if ($this->session_handler !== false && is_array($this->session_handler) && isset($this->session_handler[P_Conf_Action::INDEX_CALL_METHOD], $this->session_handler[P_Conf_Action::INDEX_CALL_PARAMS]) && is_callable($this->session_handler[P_Conf_Action::INDEX_CALL_METHOD])) {
            $this->user = call_user_func_array($this->session_handler[P_Conf_Action::INDEX_CALL_METHOD], $this->session_handler[P_Conf_Action::INDEX_CALL_PARAMS]);
            if ($this->check_login && empty($this->user)) {
                new P_Exception_Action("请先登录", P_Conf_Globalerrno::USER_NEED_LOGIN);
                return false;
            }
        }
        $class = implode(P_Conf_Autoload::CLASS_NAME_GLUE, array(P_Conf_Autoload::FRAMEWORK_PREFIX, P_Conf_Template::DEFAULT_INFFIX, ucfirst(strtolower($this->tpl_engine))));
        if (!class_exists($class)) {
            new P_Exception_Action(P_Conf_Globalerrno::$message[P_Conf_Globalerrno::INVALID_TEMPLATE_ENGINE], P_Conf_Globalerrno::INVALID_TEMPLATE_ENGINE);
            return false;
        } else {
            $this->_tpl_obj = new $class();
            if (is_callable($this->tpl_handler)) {
                call_user_func($this->tpl_handler, $this->_tpl_obj);
            }
        }
        $this->_get_template();
        return true;
    }

    private function _check_form($forms) {
        return ($this->request = P_Formcheck_Adapter::valid($forms));
    }

    private function _convert_xid() {
        if (is_array($this->xid_map) && !empty($this->xid_map)) {
            if (false !== $this->tpl_data) {
                $this->tpl_data = $this->_xid_map($this->tpl_data);
            }
            if (false !== $this->json_data) {
                $this->json_data = $this->_xid_map($this->json_data);
            }
        }
    }

    private function _display() {
        if (false !== $this->json_data || $this->errno) {
            $this->_content = P_Http::json_encode(array(
                        'errno' => $this->errno,
                        'error' => $this->error,
                        'data' => is_array($this->json_data) ? $this->json_data : array(),
            ));
            P_Http::header_json();
        } else {
            $this->_tpl_obj->assign($this->tpl_data);
            $this->_content = $this->_tpl_obj->display($this->template);
            P_Http::header_html();
        }
    }

    public function execute() {
        $this->init();
        if ($this->_before_invoke()) {
            $this->invoke();
        } else {
            
        }
        $this->_after_invoke();
    }

    private function _get_template() {
        if (false === $this->template) {
            $template = M::D('APP_ROOT_PATH') . P_Conf_Template::DFEAULT_PATH . substr(str_replace(P_Conf_Autoload::CLASS_NAME_GLUE, P_Conf_Autoload::PATH_GLUE, strtolower(get_class($this))), 1) . P_Conf_Template::DEFAULT_SUFFIX;
            if (!file_exists($template)) {
                new P_Exception_Action("fail to find {$template}", P_Conf_Globalerrno::INVALID_TEMPLATE_FILE);
                return false;
            }
            $this->template = $template;
        }
        return true;
    }

    abstract public function invoke();

    private function _xid_map($data) {
        if (!is_array($data)) {
            return $data;
        }
        $ret = array();
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $ret[$k] = $this->_xid_map($v);
                continue;
            }
            if (isset($this->xid_map[$k])) {
                if (isset($this->_xid_objs[$k])) {
                    $obj = $this->_xid_objs[$k];
                } else {
                    $key = trim(strval($this->xid_map[$k][P_Conf_Formcheck::XID_INDEX_KEY]));
                    $token = isset($this->xid_map[$k][P_Conf_Formcheck::XID_INDEX_TOKEN]) ? intval($this->xid_map[$k][P_Conf_Formcheck::XID_INDEX_TOKEN]) : false;
                    $obj = new P_Crypt_Xid($key, $token);
                    $this->_xid_objs[$k] = $obj;
                }
                $ret[$k] = $obj->encrypt($v);
                continue;
            }
            if (!isset($this->xid_map[$k])) {
                $ret[$k] = $v;
                continue;
            }
        }
        return $ret;
    }

}
