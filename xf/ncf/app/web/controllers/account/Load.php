<?php
/**
 * Load.php
 * 只有投资人的合同。
 * @date 2014-04-08
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use libs\utils\Page;
use web\controllers\BaseAction;
use libs\web\Url;
use libs\utils\Aes;
use core\service\dealload\DealLoadService;
use core\service\deal\DealLoanRepayService;
use core\service\contract\ContractInvokerService;

/**
 * 个人中心-投资的项目
 * Class Load
 * @package web\controllers\account
 */
class Load extends BaseAction
{
    public function init()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'status' => array('filter' => 'string'),
            'date_start'=>array("filter"=>'reg', "message"=>"起始时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'date_end'=>array("filter"=>'reg', "message"=>"结束时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'p' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        $status = intval($params['status']);
        $date_start = isset($params['date_start'])?$params['date_start']:false;
        $date_end = isset($params['date_end'])?$params['date_end']:false;
        $page = intval($params['p']);
        $page = $page <= 0 ? 1 : $page;
        $page_size = app_conf("PAGE_SIZE");
        $user_id = intval($GLOBALS['user_info']['id']);
        $page_size_loan = 7;
        $offset = ($page - 1) * $page_size;

        $type = 0;
        $dealLoadService = new DealLoadService();
        $result = $dealLoadService->getUserLoadList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type);
        $count = $result['count'];
        $list = $result['list'];

        $dealLoanRepayService = new DealLoanRepayService();
        $contractInvokerService = new ContractInvokerService();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                //走从库
                $deal_load = $dealLoadService->getDealLoadDetail($v['id'], true, true);
                $deal = $deal_load['deal'];
                $deal['deal_name'] = msubstr($deal['old_name'], 0, 24);
                $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);
                $list[$k]['deal'] = $deal;
                $list[$k]['deal_load'] = $deal_load;
                $list[$k]['repay_start_time'] = $deal['repay_start_time'] == 0 ? "-" : to_date($deal['repay_start_time'], 'Y-m-d');

                // 合同
                $contracList = $contractInvokerService->getContractListByDealLoadId('remoter', $v['id']);
                if($contracList) {
                    list($list[$k]['is_attachment'], $list[$k]['contracts']) = $contracList;
                }
                // 回款计划
                if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
                    $loan_repay_list = $dealLoanRepayService->getLoanRepayListByLoanId($v['id']);
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
                            $repay_list[$i][$j] = $repay;
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

        if ($count > $page_size) {
            $page_model = new Page($count, $page_size); //初始化分页对象
            $pages = $page_model->show(array("addtourl" => 1, "status", "date_start", "date_end"));
            $this->tpl->assign('pages', $pages);
        }
        $this->tpl->assign("type", intval($params['type']));
        $this->tpl->assign("date_start", $date_start);
        $this->tpl->assign("date_end", $date_end);
        $this->tpl->assign("status", $status);
        $this->tpl->assign("list", $list);
        $this->tpl->assign("page_title", "出借的项目");
        $this->tpl->assign("inc_file", "web/views/account/load.html");
        $this->tpl->assign("is_duotou_inner_user", !is_qiye_site() && is_duotou_inner_user() ? 1 : 0);
        $this->template = "web/views/account/frame.html";
    }
}
