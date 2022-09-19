<?php
/**
 * 多投宝已投项目页
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\duotou\DuotouService;
use core\service\duotou\DtEntranceService;
use libs\utils\Page;

class Finplan extends BaseAction {

    public function init() {
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'status' => array('filter' => 'string'),
            'date_start'=>array("filter"=>'reg', "message"=>"起始时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'date_end'=>array("filter"=>'reg', "message"=>"结束时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'p' => array('filter' => 'int'),
            //'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $status = intval($params['status']);
        $date_start = $params['date_start'];
        $date_end = $params['date_end'];
        $page = intval($params['p']);
        $page = $page <= 0 ? 1 : $page;
        $page_size = 10;
        $user_id = intval($GLOBALS['user_info']['id']);

        $totalLoanMoney = 0;//持有资产
        $totalRepayInterest = 0;//已获收益

        $request = array(
            'userId' => $user_id,
        );
        $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\UserStats','getUserDuotouInfo',$request));
        if(!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $totalLoanMoney = $response['data']['remainMoney'];
        $totalRepayInterest = $response['data']['totalInterest'];
        $totalNoRepayInterest = $response['data']['totalNoRepayInterest'];

        $request = array(
            'status' => $status,
            'pageNum' => $page,
            'pageSize' => $page_size,
            'userId' => $user_id,
            'startDate' => $date_start,
            'endDate' => $date_end,
        );
       
        $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoans',$request));
        $count = $response['data']['totalNum'];
        $list = $response['data']['data'];
        $activityInfos = array();
        foreach ($list as &$value) {
            $dtEntranceService = new DtEntranceService();
            if($value['activityId'] > 0) { //参与了活动
                $activityId = $value['activityId'];
                if(isset($activityInfos[$activityId])) {
                    $tempData['activityInfo'] = $activityInfos[$activityId];
                    $value['activityInfo'] = $activityInfos[$activityId];
                } else {
                    $activityInfo = $dtEntranceService->getEntranceInfo($activityId);
                    $value['activityInfo'] = $activityInfos[$activityId] = $activityInfo;
                }
            }
            $value['quitTime'] = empty($value['quitTime']) ? '-' : date('Y-m-d', $value['quitTime']);
        }

        $pages = "";
        if ($count > $page_size) {
            $page_model = new Page($count, $page_size); //初始化分页对象
            $pages = $page_model->show(array("addtourl" => 1, "status", "date_start", "date_end"));
        }
        $this->tpl->assign('pages', $pages);

        $this->tpl->assign("date", date("Y-m-d", time()));
        $this->tpl->assign("date_start", $date_start);
        $this->tpl->assign("date_end", $date_end);
        $this->tpl->assign("status", $status);
        $this->tpl->assign("list", $list);
        $this->tpl->assign("totalLoanMoney", $totalLoanMoney);
        $this->tpl->assign("totalRepayInterest", $totalRepayInterest);
        $this->tpl->assign("totalNoRepayInterest", $totalNoRepayInterest);
        $page_title = '投资的项目';
        if($this->is_firstp2p) {
            $page_title = "出借的项目";
        }
        $this->tpl->assign("page_title", $page_title);
        $this->tpl->assign("inc_file", "web/views/account/finplan.html");
        $this->template = "web/views/account/frame.html";
    }

}
