<?php

/**
 * 接口监控系统
 * @author zhangyao1<zhangyao1@ucfgroup.com>
 */

namespace web\controllers\open;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Logger;

class ApiMonitor extends BaseAction
{
    const IS_H5 = false;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'uri' => array('filter' => 'required', 'message' => 'uri不能为空'),
                'result_code' => array('filter' => 'required', 'message' => 'result_code不能为空'),
                'response_time' => array('filter' => 'required', 'message' => 'response_time不能为空'),
                'module' => array('filter' => 'required', 'message' => 'module不能为空'),
                );

        if (!$this->form->validate()) {
            $this->setErr(20001, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;

        $params['result_code'] = intval($params['result_code']);
        $params['response_time'] = intval($params['response_time']);

        $userAgent = $this->rpc->local("ApiMonitorService\getClientTerminal", array());
        $params['ua'] = $userAgent['from'];
        $params['os'] = $userAgent['os'];

        $ip = $this->rpc->local("ApiMonitorService\getip", array());
        $params['ip'] = $ip;

        $res = $this->rpc->local("ApiMonitorService\addApiLogByUri", array($params));
        if(!$res){
            Logger::error("添加接口监控记录失败" . json_encode($params));
            $this->setErr('ERR_PARAMS_ERROR', "添加接口监控记录失败");
        }
        $this->json_data = [];
    }
}
