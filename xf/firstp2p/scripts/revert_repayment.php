<?php
/**
 * 自动生成对账单程序
 * 每天晚上0点开始执行，生成前一天的对账数据
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

require_once(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__DEBUG', false);

use core\dao\UserModel;
use core\dao\UserCarryModel;
use core\dao\DealLoanRepayModel;

$tofix = array(
12576,
12636,
12640,
12645,
12648,
11731,
11761,
10469,
10473,
12801
);


foreach ($tofix as $deal_id) {
    DealLoanRepayModel::instance()->revert_repayment($deal_id);
}
echo "修复完成";
