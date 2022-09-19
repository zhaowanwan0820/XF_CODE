<?php
/**
 * 回款计划日历 年月列表
 * @author jinhaidong@ucfgroup.com
 * @date 2016-3-29 16:07:52
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoanCalendarList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
                "token" => array("filter"=>"required"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $uid = $user['id'];
        $result = $this->rpc->local('DealLoanRepayCalendarService\getDealLoanRepayCalendarList', array($uid,'api'));

        $ncfphData = (new \core\service\ncfph\AccountService())->getLoanCalendarList($uid, 'api');
        if (!empty($ncfphData['list'])) {
            $result = $this->mergeP2PData($result, $ncfphData);
        }

        $result = $this->filterZeroData($uid,$result);

        // 线上有用户有特定用户出现错误的个数数据，在加了一层循环，把数据转换正确
        // {"errno":0,"error":"","data":{"list":[{"year":2017,"month":{"0":"2","1":"3","2":"4","3":"5","4":"6","5":"7","6":"8","7":"9","8":"10","10":"12"}},{"year":2018,"month":{"0":1,"1":2,"2":3,"3":4,"4":5,"5":6,"6":7,"7":8,"8":9,"9":10,"11":12}}],"default_year":"2018","default_month":"10"}}
        foreach($result['list'] as $k=>$v){
            if(!isset($v['month'])) continue;
            $result['list'][$k]['month'] =  array_values($v['month']);
        }
        $this->json_data = $result;
    }


    private function filterZeroData($uid,$result){
        if(!isset($result['list'])) return $result;
        $needFileds = ['norepay_interest', 'repay_interest', 'norepay_principal', 'repay_principal', 'prepay_principal', 'prepay_interest'];
        $c = count($needFileds);
        $defaultYm = array();


        foreach($result['list'] as $kkk=> $item){
            $year = $item['year'];
            $defaultYm[$year] = $item['month'];

            $wxdata = $this->rpc->local('DealLoanRepayCalendarService\getSumByYearMonth', array($uid,$year,'api'));
            $phdata = (new \core\service\ncfph\AccountService())->getSumByYearMonth($uid, $year,'api');
            $wxkeys = array_keys($wxdata);
            $phkeys = array_keys($phdata);
            $keys = array_unique(array_merge($wxkeys,$phkeys));

            // 合并数据 如果所有值为0unset此月份
            foreach($keys as $key){
                $zeroNum = 0;

                foreach ($needFileds as $val){
                    if(!isset($wxdata[$key][$val])){
                        $wxdata[$key][$val] = 0;
                    }
                    if(!isset($phdata[$key][$val])){
                        $phdata[$key][$val] = 0;
                    }
                    $data[$key][$val] = bcadd($wxdata[$key][$val],$phdata[$key][$val],2);
                    if(bccomp($data[$key][$val],0,2) == 0){
                        $zeroNum++;
                    }
                }
                if($zeroNum == $c){
                    $tmpkey = array_flip($result['list'][$kkk]['month']);
                    unset($result['list'][$kkk]['month'][$tmpkey[$key]]);
                    unset($defaultYm[$year][$tmpkey[$key]]);
                }
                if(empty($result['list'][$kkk]['month'])){
                    unset($result['list'][$kkk]);
                    unset($defaultYm[$year]);
                }
            }
        }
        $defaultYM = \core\service\DealLoanRepayCalendarService::getDefaultYearAndMonth($defaultYm);
        $result['default_year'] = $defaultYM['defaultYear'];
        $result['default_month'] = $defaultYM['defaultMonth'];
        $result['list'] = array_values($result['list']);
        return $result;
    }

    private function mergeP2PData($wxData, $p2pData)
    {
        if (empty($wxData['list'])) {
            return $p2pData;
        }

        $tmpWxList = $tmpP2pList = [];
        if (isset($wxData['list']) && !empty($wxData['list'])) {
            foreach ($wxData['list'] as $list) {
                $tmpWxList[$list['year']] = $list;
            }
            $wxData['list'] = $tmpWxList;
        }

        if (isset($p2pData['list']) && !empty($p2pData['list'])) {
            foreach ($p2pData['list'] as $list) {
                $tmpP2pList[$list['year']] = $list;
            }
            $p2pData['list'] = $tmpP2pList;
        }

        $beginYear = \core\service\DealLoanRepayCalendarService::BEGIN_YEAR;
        $endYear = date('Y') + 3;// TODO 改为自动监测

        $mergeData = $tmpData = [];
        for($year = $beginYear; $year <= $endYear; $year++) {
            if (isset($wxData['list'][$year]) && isset($p2pData['list'][$year]) && $wxData['list'][$year]['year'] == $year && $p2pData['list'][$year]['year'] == $year) {
                $months = array_flip((array)$wxData['list'][$year]['month']) + array_flip((array)$p2pData['list'][$year]['month']);
                ksort($months);
                $months = array_keys($months);
                $mergeData['list'][] = [
                    'year' => $year,
                    'month' => $months
                ];
                $tmpData[$year] = $months;
            } elseif (isset($wxData['list'][$year]) && $wxData['list'][$year]['year'] == $year) {
                $mergeData['list'][] = [
                    'year' => $year,
                    'month' => $wxData['list'][$year]['month']
                ];
                $tmpData[$year] = $wxData['list'][$year]['month'];
            }  elseif (isset($p2pData['list'][$year]) && $p2pData['list'][$year]['year'] == $year) {
                $mergeData['list'][] = [
                    'year' => $year,
                    'month' => $p2pData['list'][$year]['month']
                ];
                $tmpData[$year] = $p2pData['list'][$year]['month'];
            }
        }

        $defaultYM = \core\service\DealLoanRepayCalendarService::getDefaultYearAndMonth($tmpData);
        $mergeData['default_year'] = $defaultYM['defaultYear'];
        $mergeData['default_month'] = $defaultYM['defaultMonth'];

        return $mergeData;
    }
}
