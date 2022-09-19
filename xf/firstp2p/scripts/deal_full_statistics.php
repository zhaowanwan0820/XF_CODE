<?php
/**
 * 每日统计当日满标的数量及每标投资人数
 */
require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use core\service\DealService;
use core\dao\DealLoadModel;
use core\dao\DealModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');

class DealFullStatistics{
    public function run() {
        $day_s = date('Y-m-d 00:00:00',strtotime("-1 day"));
        $day_e = date('Y-m-d 23:59:59',strtotime("-1 day"));
        $yes_start = to_timespan($day_s);
        $yes_end = to_timespan($day_e);

        $deal_service = new DealService();
        $rs = $deal_service->getFullStatistics($yes_start, $yes_end);

        $cnt = count($rs);
        $str = "从{$day_s}到{$day_e} 共有{$cnt}个满标 <br>";

        if ($cnt > 0) {
            $str .= "<table><tr><th>标id</th><th>标名称</th><th>投资人数</th></tr>";
            foreach($rs as $row) {
                $str .= "<tr><th>{$row['deal_id']}</th><th>{$row['name']}</th><th>{$row['cnt']}</th>";
            }
            $str .= "</table>";
        }

        FP::import("libs.common.dict");
        //$user = array("zhanglei5@ucfgroup.com");
        $user = dict::get("FULL_NOTICE_USER");

        $msgcenter = new msgcenter();
        $title = "从{$day_s}到{$day_e} 满标概况";

        foreach($user as $email) {
            $data['email'] = $email;
            $msgcenter->setMsg($email, 0, $str, false, $title);
        }

        $msgcenter->save();
        unset($msgcenter);
    }

}

$obj = new DealFullStatistics();
$obj->run();
