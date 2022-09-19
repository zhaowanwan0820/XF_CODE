<?php
/**
 * email 月对账单   被打开的邮件统计次数
 * @date 2014年6月12日
 * @author zhanglei5@ucfgroup.com
 */

namespace web\controllers\email;

use web\controllers\BaseAction;
use core\data\EmailData;
use libs\utils\Logger;
class Show extends BaseAction {

    public function init() {
    }

    public function invoke() {
        $email_data = new EmailData();
        $rs = $email_data->incrOpenBillCnt('open_bill_cnt');//    var_dump($rs);

        if($rs === false) { // 错误处理
            $log['email_open_bill_time'] = date('Y-m-d H:i:s',time());
            Logger::wLog($log); // 如果出错记录下时间
        }

/*
        $rs = $email_data->getOpenBillCnt('open_bill_cnt');var_dump($rs);
        $rs = $email_data->setOpenBillCnt('bill_cnt','5'); var_dump($rs);
        $rs = $email_data->getOpenBillCnt('bill_cnt'); var_dump($rs); */

    }
}
