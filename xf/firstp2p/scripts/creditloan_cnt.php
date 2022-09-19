<?php

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';

ini_set('memory_limit', '1024M');
set_time_limit(0);

$ulrs = new \core\dao\UserLoanRepayStatisticsModel();

// 取用用户持有资产大于7500的用户
$list = $ulrs->findAllViaSlave("`norepay_principal` >= 7500", true, 'user_id');

$cls = new \core\service\CreditLoanService();
$ubps = new \core\service\UniteBankPaymentService();
$counter = 0;
$counterChecked = 0;

foreach ($list as $row) {
    $user_id = $row['user_id'];
    // 读取用户是否有符合银信通投资要求的标的
    $deal_list = $cls->getCreditDealsByUserId($user_id);
    // 用户有符合要求投资记录
    if (!empty($deal_list))
    {
        $counterChecked ++;
        // 判断用户是否是通过四要素
        //$result = \libs\db\Db::getInstance('firstp2p','slave')->getOne("SELECT COUNT(*) FROM firstp2p_user_bankcard WHERE user_id = '{$user_id}'");
        $result = $ubps->isFastPayVerify($user_id);
        if ($result == true)
        {
            $counter ++;
            echo $user_id.PHP_EOL;
            \libs\utils\PaymentApi::log('CreditLoanCount::UserValidated '.$user_id);
        }
        else
        {
            \libs\utils\PaymentApi::log('CreditLoanCount::UserUnValidated '.$user_id);
        }
    }
}

echo 'total checked: '. $counterChecked.PHP_EOL;
echo 'total passed: '.$counter.PHP_EOL;
echo 'need to validate: '.($counterChecked - $counter).PHP_EOL;
