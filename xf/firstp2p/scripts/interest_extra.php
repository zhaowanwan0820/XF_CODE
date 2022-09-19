<?php
/**
 * interest_extra.php
 *  贴息定时任务脚本
 * @date 2015-11-4
 * @author <wangzhen3@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use core\service\InterestExtraService;
set_time_limit(0);

class interest_extra{
    public function run(){
        $interestExtraService = new InterestExtraService();
        $interestExtraService->interestExtraprocess();
    }
}
$interest_extra = new interest_extra();
$interest_extra->run();
exit;