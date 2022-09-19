<?php
/**
 * 回款计划
 */

namespace task\apis\account;

use task\lib\ApiAction;
use core\service\dealload\DealLoadService;
use core\service\deal\DealService;
use libs\utils\Finance;
use core\service\deal\DealLoanRepayService;

class LoanNew extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $offset = $param['offset'] ?: 0;
        $count  = $param['count'] ?: 10;
        $type   = $param['type'] == 1 ? 0 : 1;
        $dealLoanRepayService = new DealLoanRepayService();
        if (!empty($param['history']) && $param['history'] == 1){
            $dealLoanRepayService->is_history_db = true;
        }
        $result = $dealLoanRepayService->getRepayList($userId, 0, 0, [$offset,$count], 'newapi', NULL, $type);
        // 防止后续有用原库的
        $dealLoanRepayService->is_history_db = false;
        $data = $this->data_format($result['list'],$type);
        $this->json_data = $data;

    }

    protected function data_format($data, $type){
        if(!$data){
            return Null;
        }

        $arr = [];
        foreach($data as $k=>$v) {
            $arr[$k]['productID'] = $v['deal_id'];
            $arr[$k]['name']      = $v['deal_name'];
            $arr[$k]['time']      = $v['time'];
            $arr[$k]['real_time'] = $v['real_time'];
            $arr[$k]['type']      = $v['money_type'];
            $arr[$k]['status']    = $v['repay_status'];
            $arr[$k]['money']     = $v['money'];
            $arr[$k]['position']  = $type;
        }
        return $arr;
    }

}
