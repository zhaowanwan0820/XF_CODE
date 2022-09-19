<?php
/**
 * Form class file
 *
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace libs\web;

/**
 * 参数检查类
 * 例：
 * $form = new Form();
 * $form->rules = array(
 *      "url"=>array("filter"=>"url", "message"=>"url", "option"=>array("optional"=>true)), // 可选参数,设置为true则允许为空，不设置为参数则不允许为空
 *      "name"=>array("filter"=>"string", "message"=>"请正确填写姓名"), // 使用预定义方法验证
 *      "mobile"=>array("filter"=>"length", "message"=>"手机号码应为7-11为数字", "option"=>array("min"=>7, "max"=>11)), // 使用Form类的定制方法验证
 *      "birthday"=>array("filter"=>array($this, "valid_birth"), "message"=>"生日格式错误"), // 使用指定对象的方法验证
 *      "xxx"=>array("filter"=>"reg", "message"=>"xxx不符合规则", "option"=>array("regexp"=>"/^[\w]+$/")), // 正则验证
 *
 * );
 * if (!$form->validate()) {
 *      $err = $form->getError();
 *      或
 *      $errmsg = $form->getErrorMsg();
 * }
 *
 * $data = $form->data;
 **/
class Form implements \ArrayAccess
{
    public $rules = array(); // 参数规则数组
    public $data  = array(); // 经过检查处理后的数组

    private $_method = "request"; // 默认接受$_REQUEST数据
    private $_form_name;
    private $_error = array(); // 错误信息

    private $_flags = array(
        "string" => FILTER_SANITIZE_STRING,
        "float"  => FILTER_VALIDATE_FLOAT,
        "bool"   => FILTER_VALIDATE_BOOLEAN,
        "ip"     => FILTER_VALIDATE_IP,
        "url"    => FILTER_VALIDATE_URL,
        "email"  => FILTER_VALIDATE_EMAIL,
        "reg"    => FILTER_VALIDATE_REGEXP,
    );

    public function __construct($method="", $form_name="") {
        $method = strtolower($method);
        if (in_array($method, array("get", "post"))) {
            $this->_method = $method;
        }
        $this->_form_name = $form_name;
    }

    /**
     * 检查参数必须存在
     * @param $value string
     * @return bool
     */
    private function required($value) {
        if ($value !== null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证两次输入的密码是否相同
     * @param $value string
     * @param $option array("name"=>"password2")
     * @return bool
     */
    private function password($value, $option) {
        $password2 = $this->_getValue($option['name']);
        if (!$password2 || $value != $password2) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 正则验证数字
     * @param $value string
     * @return bool
     */
    private function int($value) {
        // 兼容以前的不传值情况
        if ($value == '' || $value == null) {
            return true;
        }
        if ($value !== '0' && $value !== 0 && !preg_match("/^-?[1-9]\d*$/", $value)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 验证参数长度
     * @param $value string
     * @param $option array("min"=>$min, "max"=>$max, "charset"=>$charset)
     * @return bool
     */
    private function length($value, $option) {
        if (isset($option['charset'])) {
            $len = mb_strlen($value, $option['charset']);
        } else {
            $len = strlen($value);
        }
        if (isset($option['min']) && $len<$option['min'] || isset($option['max']) && $len>$option['max']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 将指定参数按照定义规则进行检查
     * 如果出现错误则将错误信息赋予私有变量并返回false
     * 如果验证成功则将request的数据赋予$this->data中
     * @return bool
     */
    public function validate() {
        if (!$this->rules) { // 如果无定义参数，返回true
            return true;
        }
        foreach ($this->rules as $name => $rule) {
            $res = false;
            $filter = $rule['filter'];

            if (isset($rule['option']['optional']) && $rule['option']['optional'] === true && ($this->_getValue($name)=="" || $this->_getValue($name) === null)) {
                continue;
            } 

            if (is_array($filter)) { // 如果filter定义为一个数组，则使用指定的类和方法进行验证
                $obj = $filter[0];
                $func = $filter[1];
                if (!is_object($obj) || !method_exists($obj, $func)) { // 如果第一个参数不是类或指定方法不存在，返回false
                    $this->_setErr($name, $rule['message']);
                    return false;
                }
                if (isset($rule['option'])) { // 如果指定option
                    $res = $obj->$func($this->_getValue($name), $rule['option']);
                } else {
                    $res = $obj->$func($this->_getValue($name));
                }
            } elseif (is_string($filter)) { // 如果filter定义为一个字符串，则使用Form类的方法进行验证
                if (isset($this->_flags[$filter])) {
                    // 如果filter为预定义的flag，则使用filter_var进行验证
                    if (isset($rule['option'])) {
                        $res = filter_var($this->_getValue($name), $this->_flags[$filter], array("options" => $rule['option']));
                    } else {
                        $res = filter_var($this->_getValue($name), $this->_flags[$filter]);
                    }
                } else {
                    // 如果filter不是预定义的flag，则使用Form类的方法进行验证，如果方法不存在，则返回false
                    if (!method_exists($this, $filter)) {
                        $this->_setErr($name, $rule['message']);
                        return false;
                    }

                    if (isset($rule['option'])) { // 如果指定option
                        $res = $this->$filter($this->_getValue($name), $rule['option']);
                    } else {
                        $res = $this->$filter($this->_getValue($name));
                    }
                }
            }

            //if ($res === false || $res === '') { // 如果验证失败，则设置失败信息
            if ($res === false) { // 如果验证失败，则设置失败信息
                $this->_setErr($name, $rule['message']);
                return false;
            } else { // 如果验证成功，将request数据存入共有的$this->data
                $this->data[$name] = $this->_getValue($name);
            }
        }
        return true;
    }

    /**
     * 获取请求数据
     * @param $name string
     * @return mixed
     */
    private function _getValue($name) {
        // 根据method判断返回哪部分数据
        switch ($this->_method) {
            case "get" : $data = $_GET; break;
            case "post" : $data = $_POST; break;
            default : $data = $_REQUEST;
        }
        // 如果有指定form_name，则返回相应form_name的数据
        if ($this->_form_name) {
            return $this->_filter_common($data[$this->_form_name][$name]);
        } else {
            if (isset($data[$name])) {
                return $this->_filter_common($data[$name]);
            } else {
                return null;
            }
        }
    }

    /**
     * 统一的过滤方法
     * @param string $value
     * @param string
     */
    private function _filter_common($value) {
        if (null === $value) {
            return null;
        }
        if(!get_magic_quotes_gpc()){
            $value = addslashes($value);
        }
        return $value;
    }

    /**
     * 设置错误信息
     * @param $name string
     * @param $message string
     */
    private function _setErr($name, $message) {
        $this->_error = array(
            "name" => $name,
            "msg" => $message,
        );
    }

    /**
     * 以数组形式返回错误信息
     * @return array('name'=>'message')
     */
    public function getError() {
        return array($this->_error['name'] => $this->_error['msg']);
    }

    /**
     * 获取错误文案
     * @return string
     */
    public function getErrorMsg() {
        return $this->_error['msg'];
    }

    /**
     * 数组访问接口，设置键值
     *
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * 数组访问操作，检查key是否存在
     *
     * @return boolean
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * 数组访问操作，unset
     *
     * @return void
     **/
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    /**
     * 数组访问操作，取值
     *
     * @return mixed
     **/
    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
