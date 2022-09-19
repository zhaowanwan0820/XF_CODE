<?php

/**
 * 信息披露-数据展示
 */
namespace web\controllers\disclosure;

use web\controllers\BaseAction;
use core\service\disclosure\DisclosureService;
use libs\web\Form;

class ShowData extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'borrow_amount' => array('filter' => 'string'),
            'loaner_number' => array('filter' => 'string'),
            'remain_interest' => array('filter' => 'string'),
            'borrow_count' => array('filter' => 'string'),
            'borrower_number' => array('filter' => 'string'),
            'remain_money' => array('filter' => 'string'),
            'average_borrow_amount' => array('filter' => 'string'),
            'average_loan_amount' => array('filter' => 'string'),
            'remain_money_count' => array('filter' => 'string'),
            'third_guarantee_amount' => array('filter' => 'string'),
            'third_guarantee_count' => array('filter' => 'string'),
            'relation_borrow_amount' => array('filter' => 'string'),
            'relation_borrow_count' => array('filter' => 'string'),
            'total_loaner_count' => array('filter' => 'string'),
            'total_borrower_count' => array('filter' => 'string'),
            'top_ten_borrower_paid' => array('filter' => 'string'),
            'top_ten_borrower_paid' => array('filter' => 'string'),
            'dealline' => array('filter' => 'string'),
            'overdue_data_list_attachment' => array('filter' => 'string'),
            'overdue_chart_attachment' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $service = new DisclosureService();
        $result = $service->getShowData();
        $list['borrow_amount']=htmlspecialchars(number_format(empty($data['borrow_amount']) ? $result['borrow_amount'] :$data['borrow_amount'],2)); //'累计借贷金额',
        $list['loaner_number']=htmlspecialchars(number_format(empty($data['loaner_number']) ? $result['loaner_number'] :$data['loaner_number'])) ; //'累计出借人数量',
        $list['remain_interest']=htmlspecialchars(number_format(empty($data['remain_interest']) ? $result['remain_interest'] :$data['remain_interest'],2)) ; //'利息余额',
        $list['borrow_count']=htmlspecialchars(number_format(empty($data['borrow_count']) ? $result['borrow_count'] :$data['borrow_count'])) ; //'累计借贷笔数',
        $list['borrower_number']=htmlspecialchars(number_format(empty($data['borrower_number']) ? $result['borrower_number'] :$data['borrower_number'])) ; //'累计借款人数量',
        $list['remain_money']=htmlspecialchars(number_format(empty($data['remain_money']) ? $result['remain_money'] :$data['remain_money'],2)) ; //'借贷余额',
        $list['average_borrow_amount']=htmlspecialchars(number_format(empty($data['average_borrow_amount']) ? $result['average_borrow_amount'] :$data['average_borrow_amount'],2)); //'人均累计借款金额',
        $list['average_loan_amount']=htmlspecialchars(number_format(empty($data['average_loan_amount']) ? $result['average_loan_amount'] :$data['average_loan_amount'],2)) ; //'人均累计出借金额',
        $list['remain_money_count']=htmlspecialchars(number_format(empty($data['remain_money_count']) ? $result['remain_money_count'] :$data['remain_money_count'])) ; //'借贷余额笔数',
        $list['third_guarantee_amount']=htmlspecialchars(number_format(empty($data['third_guarantee_amount']) ? $result['third_guarantee_amount'] :$data['third_guarantee_amount'],2)) ; //'第三方累计代偿金额',
        $list['third_guarantee_count']=htmlspecialchars(number_format(empty($data['third_guarantee_count']) ? $result['third_guarantee_count'] :$data['third_guarantee_count'])) ; //'第三方累计代偿笔数',
        $list['relation_borrow_amount']=htmlspecialchars(number_format(empty($data['relation_borrow_amount']) ? $result['relation_borrow_amount'] :$data['relation_borrow_amount'],2)) ; //'关联关系借款金额',
        $list['relation_borrow_count']=htmlspecialchars(number_format(empty($data['relation_borrow_count']) ? $result['relation_borrow_count'] :$data['relation_borrow_count'])) ; //'关联关系借款笔数',
        $list['total_borrower_count']=htmlspecialchars(number_format(empty($data['total_borrower_count']) ? $result['total_borrower_count'] :$data['total_borrower_count'])) ; //'当前出借人数量',
        $list['total_loaner_count']=htmlspecialchars(number_format(empty($data['total_loaner_count']) ? $result['total_loaner_count'] :$data['total_loaner_count'])) ; //'当前借款人数量',
        $list['top_ten_borrower_paid']=htmlspecialchars(number_format(empty($data['top_ten_borrower_paid']) ? $result['top_ten_borrower_paid'] :$data['top_ten_borrower_paid'],2)) ; //'前十大借款人待还金额',
        $list['largest_borrower_paid_percent']=htmlspecialchars(number_format(empty($data['largest_borrower_paid_percent']) ? $result['largest_borrower_paid_percent'] :$data['largest_borrower_paid_percent'],2)) ; //'最大单借款人待还金额占比',
        $list['dealline']=htmlspecialchars(empty($data['dealline']) ? $result['dealline'] :$data['dealline']) ; //'截止日期',
        $list['dealline']=date("Y年m月d日",strtotime($list['dealline']));//'截止日期',
        $list['overdue_data_list_attachment']=htmlspecialchars(empty($data['overdue_data_list_attachment']) ? $result['overdue_data_list_attachment'] :$data['overdue_data_list_attachment']) ;
        $list['overdue_chart_attachment']=htmlspecialchars(empty($data['overdue_chart_attachment']) ? $result['overdue_chart_attachment'] :$data['overdue_chart_attachment']) ;

        $this->tpl->assign("data", $list);
        $this->tpl->display("web/views/disclosure/show_data.html");
    }
}
