<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\RequestEarningDealsIncome;
use NCFGroup\Protos\Ptp\ResponseEarningDealsIncome;
use core\service\EarningService;

/**
 * PtpEarningService
 */
class PtpEarningService extends ServiceBase
{

    /**
     * 获取收益信息
     */
    public function getDealsIncome(RequestEarningDealsIncome $request)
    {
        $isShowAll = $request->isShowAll;

        $earningService = new EarningService;
        $ret = $earningService->getDealsIncomeView();
        // if ($ret['income_plan_sum'] == '--') { // 缓存未命中情况重新读取
        //     $ret = $earningService->getIncomeViewNew();
        // }

        if (!$isShowAll) {
            $income['income_sum'] = $ret['income_sum'];
            $income['income_plan_sum'] = $ret['income_plan_sum'];
        } else {
            $income = $ret;
        }

        $response = new ResponseEarningDealsIncome;
        $response->income = $income;
        return $response;
    }
}
