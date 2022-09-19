<?php

/**
 * RiskCheckService class file.
 *
 * @author liuzhenpeng@ucfgroup.com
 *
 * */

namespace core\service;

use libs\utils\Logger;

/**
 * RiskCheckService
 *
 * @packaged default
 * */
class RiskCheckService extends BaseService {

    /**
     * 将信息记录到系统日志
     * @param  array $params_list
     */
    public function insertRiskLog($params_list)
    {
        $result = $this->invoke_fraud_api($params_list);

        $log_data['tags'] = 'risk_check';

        if(isset($result[0])){
            $log_data['time'] = date('Y-m-d H:i:s');
            $log_data['msg']  = '接收到返回';
            $log_data['params_item'] = $params_list;
            $log_data['return_item'] = $result[0];
            return logger::info($log_data);
        }

        $log_data['time'] = date('Y-m-d H:i:s');
        $log_data['msg']  = '程序被调用，但没有接收到返回';

        return logger::info($log_data);
    }

    /**
     * @封装接口调用参数
     * @param  array $params
     * @return array
     */
    private function invoke_fraud_api($params = array())
    {
        $data_string = json_encode($params);
        $data_string = '[' . $data_string . ']';

        $url = "http://172.21.30.22:8686/audit";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8','Content-Length: ' . strlen($data_string)));

        $result = curl_exec($ch);
        return json_decode($result, true); 
    }
}

// END class RiskCheckService 
