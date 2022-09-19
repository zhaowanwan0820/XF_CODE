<?php

/**
  * 收益显示（包括交易总额、投资人总数等）
  */

namespace api\controllers\deal;

use api\controllers\AppBaseAction;
use core\service\disclosure\DisclosureService;

class DealsIncome extends AppBaseAction {

    protected $needAuth = false;

    public function invoke() {
        $service = new DisclosureService();
        $result = $service->getShowData();
        $dealsIncomeView['borrow_amount_total'] = number_format($result['borrow_amount'],2) ;//'累计借款金额',
        $dealsIncomeView['buy_count_total'] = number_format($result['borrow_count']) ; //'累计借款笔数',
        $dealsIncomeView['distinct_user_total'] = number_format($result['loaner_number']) ; //'累计出借人数量',
        if($this->isWapCall()){
            $pchost = app_conf("FIRSTP2P_CN_DOMAIN");
            $dealsIncomeView['consumer_url'] = "https://".$pchost."/feedback/feedback";
            if(!empty($GLOBALS['user_info']['uid'])){//登录情况下写redis
                $rk = uniqid();
                $dealsIncomeView['consumer_url'] .= "?rktoken=".$rk;
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                $redis->setex($rk, 3600, $GLOBALS['user_info']['uid']);
            }
        }
        $this->json_data = $dealsIncomeView;
    }
}
