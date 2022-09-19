<?php
/**
 * 投资人已投项目
 *
 * @date 2018-08-07
 *
 */

namespace task\apis\account;

use task\lib\ApiAction;
use libs\web\Url;
use libs\utils\Aes;
use libs\utils\Logger;
use core\service\dealload\DealLoadService;
use core\service\contract\ContractInvokerService;
use core\service\deal\DealLoanRepayService;

class Load extends ApiAction
{

    public function invoke()
    {

        $params  = $this->getParam();
        $status = intval($params['status']);
        $date_start = $params['date_start'];
        $date_end = $params['date_end'];
        $page = intval($params['p']);
        $page = $page <= 0 ? 1 : $page;
        $page_size = $params['page_size'];
        $user_id = intval($params['user_id']);
        $page_size_loan = 7;
        $offset = ($page - 1) * $page_size;
        $type = 0;
        $dealloadservice = new DealLoadService();
        $result = $dealloadservice->getUserLoadList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type);
        $count = $result['count'];
        $list = $result['list'];

        //$now = get_gmtime();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                //走从库
                $deal_load = $dealloadservice->getDealLoadDetail($v['id'], true, true);

                $deal = !empty($deal_load['deal']) ? $deal_load['deal']->getRow(): '';
                $deal['deal_name'] = msubstr($deal['old_name'], 0, 24);
                $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);
                $list[$k]['deal'] = $deal;
                $list[$k]['deal_load'] = !empty($deal_load) ? $deal_load->getRow():array();
                $list[$k]['repay_start_time'] = $deal['repay_start_time'] == 0 ? "-" : to_date($deal['repay_start_time'], 'Y-m-d');

                // 合同
                list($list[$k]['is_attachment'], $list[$k]['contracts']) = ContractInvokerService::getContractListByDealLoadId('remoter', $v['id']);

                // 回款计划
                if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                    $DealLoanRepayService = new DealLoanRepayService();
                    $loan_repay_list = $DealLoanRepayService->getLoanRepayListByLoanId($v['id']);

                    foreach ($loan_repay_list as &$item) {
                        $item['real_time'] = $item['real_time'] > 0 ? to_date($item['real_time'], "Y-m-d") : "-";
                        if($deal['deal_type'] ==1){
                            //预计到账日期
                            $list[$k]['deal_compound_repay_time'] = to_date($item['time'],'Y-m-d');
                            if($item['status'] == 1){
                                $list[$k]['deal_compound_real_time'] = to_date($item['real_time'], "Y-m-d");
                            }
                        }
                    }

                    //回款信息分页
                    $c = count($loan_repay_list);
                    $page_loan = ceil($c / $page_size_loan);
                    $repay_list = array();

                    for ($i = 0; $i < $page_loan; $i++) {
                        for ($j = 0; $j < $page_size_loan; $j++) {
                            $repay = array_shift($loan_repay_list);
                            if (!$repay) {
                                break 2;
                            }
                            $repay_list[$i][$j] = !empty($repay) ? $repay->getRow() : array();
                        }
                    }

                    $arr_page_loan = array();
                    for ($i = 1; $i <= $page_loan; $i++) {
                        $arr_page_loan[] = $i;
                    }

                    $list[$k]['loan_repay_list'] = $repay_list;
                    $list[$k]['loan_page'] = $arr_page_loan;
                }
            }
        }
        $ret = array(
            'count' => $count,
            'list' => $list
        );
        $this->json_data = $ret;
    }

}
