<?php

/**
 * 智投数据分析，每日执行一次
 * /apps/product/php/bin/php intelligent_investment_analysis.php
 * @author wangchuanlu
 * @date 2018-03-20
*/

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use core\dao\IntelligentInvestmentModel;

set_time_limit(0);
ini_set('memory_limit', '4096M');

class IntelligentInvestmentAnalysis {

    public function run() {
        $intelligentInvestmentModel = new IntelligentInvestmentModel();
        $res = $intelligentInvestmentModel->updateSpecialAverage();
        if($res) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "智投数据分析成功！")));
        }else {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, " 智投数据分析失败！")));
        }
        return true;
    }
}

$intelligentInvestmentAnalysis = new IntelligentInvestmentAnalysis();
$intelligentInvestmentAnalysis->run();
