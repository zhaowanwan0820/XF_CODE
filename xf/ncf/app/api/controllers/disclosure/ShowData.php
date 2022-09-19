<?php

namespace api\controllers\disclosure;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\disclosure\DisclosureService;

class ShowData extends AppBaseAction
{
    protected $needAuth = false;

    public function invoke()
    {
        $service = new DisclosureService();
        $result = $service->getShowData();
        $data['borrow_amount']=number_format($result['borrow_amount']) ; //'累计借贷金额',
        $data['loaner_number']=number_format($result['loaner_number']) ; //'累计出借人数量',
        $data['remain_interest']=number_format($result['remain_interest'],2) ; //'利息余额',
        $data['borrow_count']=number_format($result['borrow_count']) ; //'累计借贷笔数',
        $data['borrower_number']=number_format($result['borrower_number']) ; //'累计借款人数量',
        $data['remain_money']=number_format($result['remain_money'],2) ; //'借贷余额',
        $data['average_borrow_amount']=number_format($result['average_borrow_amount'],2) ; //'人均累计借款金额',
        $data['average_loan_amount']=number_format($result['average_loan_amount'],2) ; //'人均累计出借金额',
        $data['remain_money_count']=number_format($result['remain_money_count']) ; //'借贷余额笔数',
        $data['third_guarantee_amount']=number_format($result['third_guarantee_amount'],2) ; //'第三方累计代偿金额',
        $data['third_guarantee_count']=number_format($result['third_guarantee_count']) ; //'第三方累计代偿笔数',
        $data['relation_borrow_amount']=number_format($result['relation_borrow_amount'],2) ; //'关联关系借款金额',
        $data['relation_borrow_count']=number_format($result['relation_borrow_count']) ; //'关联关系借款笔数',
        $data['total_borrower_count']=number_format($result['total_borrower_count']) ; //'当前出借人数量',
        $data['total_loaner_count']=number_format($result['total_loaner_count']) ; //'当前借款人数量',
        $data['top_ten_borrower_paid']=number_format($result['top_ten_borrower_paid'],2) ; //'前十大借款人待还金额',
        $data['largest_borrower_paid_percent']=number_format($result['largest_borrower_paid_percent'],2) ; //'最大单借款人待还金额占比',
        $data['dealline']=date("Y年m月d日",strtotime($result['dealline'])) ; //'截止日期',
        $data['overdue_data_list_attachment']=$result['overdue_data_list_attachment'] ;
        $data['overdue_chart_attachment']=$result['overdue_chart_attachment'] ;

        $this->json_data = $data;
    }
}
