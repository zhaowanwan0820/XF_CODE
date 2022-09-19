<?php

/**
  * 收益显示（包括交易总额、投资人总数等）
  */

namespace api\controllers\deal;

use api\controllers\AppBaseAction;

class DealsIncome extends AppBaseAction {

    public function invoke() {
        $statisticsArr = explode(',', app_conf('CN_PLATFORM_DEAL_STATISTICS'));
        $dealsIncomeView['borrow_amount_total'] = number_format(array_shift($statisticsArr), 2);
        $dealsIncomeView['buy_count_total'] = number_format(array_shift($statisticsArr));
        $dealsIncomeView['distinct_user_total'] = number_format(array_shift($statisticsArr));
        $dealsIncomeView['income_expected_sum'] = number_format(array_shift($statisticsArr), 2);
        $this->json_data = $dealsIncomeView;
    }
}
