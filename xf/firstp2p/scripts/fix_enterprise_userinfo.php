<?php

require_once dirname(__FILE__).'/../app/init.php';

use core\service\EnterpriseService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use libs\utils\Logger;

set_time_limit(0);

$startStr = 'ENTERPRISE INFO: '.date('Y-m-d H:i:s').'修复企业用户信息脚本开始执行：';
echo $startStr, PHP_EOL;
Logger::remote($startStr, Logger::INFO);

$list = require __DIR__.'/fix_enterprise_userinfo_data.php';

// 更新企业用户信息：企业证件号码，开户许可证核准号, 行业类型
$enterpriseService = new EnterpriseService();
$num_success = 0;

/**
 * list 各个参数含义
 * user_id             用户id
 * reg_amt             注册资金
 * app_no              开户许可证核准号
 * indu_cate           行业类型
 */
foreach ($list as $key => $item) {
    try {
        $userId = $item[0];
        // 数据库注册资金单位为元
        if (!empty($item[1])) {
            $data['reg_amt'] = bcmul($item[1], 10000, 2);
        }
        if (!empty($item[2])) {
            $data['app_no'] = $item[2];
        }
        // 根据行业类型查找对应的key
        if (!empty($item[3])) {
            $induCate = array_search($item[3], UserAccountEnum::$inducateTypes);
            if ($induCate === false) {
                throw new \Exception('induCate is illegal, data: '.json_encode($item));
            }
            $data['indu_cate'] = $induCate;
        }
        $data['update_time'] = time();
        $message = 'ENTERPIRSE INFO'.json_encode($data);

        $result = $enterpriseService->updateByUid($userId, $data);
        if ($result != false) {
            $logLevel = Logger::INFO;
            $message .= ', success,';
            $num_success++;
        } else {
            $logLevel = Logger::ERR;
            $message .= ', failed';
        }
        echo $message, PHP_EOL;
        Logger::remote($message, $logLevel);
    } catch (\Exception $e) {
        $errorMsg = 'ENTERPRISE ERROR: failed, msg: '.$e->getMessage();
        echo $errorMsg, PHP_EOL;
        Logger::remote($errorMsg, Logger::ERR);
    }
}

$endStr = 'ENTERPRISE INFO: 共'.count($list).'个用户，数据修复成功'.$num_success."个";
echo $endStr.PHP_EOL;
Logger::remote($endStr, Logger::INFO);
