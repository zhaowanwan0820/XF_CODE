<?php
/**
 * LandingIncome  PC落地页收益显示
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/

namespace web\controllers\user;
use web\controllers\BaseAction;

class LandingIncome extends BaseAction {
    private $_income_site = array();
    public function invoke() {
        if(array_search(app_conf('TEMPLATE_ID'),$this->_income_site) !== false){
            $deals_income_view = $this->rpc->local("EarningService\getDealsIncomeView",array(false));
        }else{
            $deals_income_view = $this->rpc->local("EarningService\getDealsIncomeView",array());
        }
        $ret['errorCode'] = 0;
        $ret['errorMsg'] = '';
        $ret['data']['income_sum'] = $deals_income_view['income_sum'];
        $res = json_encode($ret);
        if ($_GET['callback']) {
            echo htmlspecialchars($_GET['callback']).'('.$res.')';
        }
        return;
    }

}
