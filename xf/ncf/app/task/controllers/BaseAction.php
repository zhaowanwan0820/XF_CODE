<?php

namespace task\controllers;

use libs\web\Action;

class BaseAction extends Action {
    protected $json_data_err = false;

    /**
     * 继承父类 _before_invoke
     */
    public function _before_invoke() {
        //增加监控点，类似WEB_CONTROLLERS_USER_LOGIN
        \libs\utils\Monitor::add(strtoupper(str_replace('\\', '_', get_called_class())));
        return true;
    }

    /**
     * 继承父类_after_invoke，实现数据展示
     */
    public function _after_invoke() {
        $arr_result = array();
        if ($this->errorCode == 0) {
            $arr_result["code"] = 0;
            $arr_result["msg"] = "success";
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["code"] = $this->code;
            $arr_result["msg"] = $this->msg;
            $arr_result["data"] = $this->json_data_err;
        }

        echo json_encode($arr_result);
    }

    public function getParams() {
        $params =  json_decode(file_get_contents('php://input'),true);
        return isset($params['Message']) ? $params['Message'] : '';
    }

    public function getTopic(){
        $params =  json_decode(file_get_contents('php://input'),true);
        return isset($params['Topic']) ? $params['Topic'] : '';
    }
}