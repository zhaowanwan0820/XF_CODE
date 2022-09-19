<?php

namespace core\dao\disclosure;

use core\dao\BaseModel;

class DisclosureModel extends BaseModel {

    public function getShowData(){
        $condition = " 1=1 ";
        return $this->findByViaSlave($condition);
    }

    public function saveData($data){
        $dataOnline = $this->getShowData();
        if(empty($dataOnline)){
            $this->borrow_amount = $data['borrow_amount']; //'累计借贷金额',
            $this->loaner_number = $data['loaner_number']; //'累计出借人数量',
            $this->remain_interest = $data['remain_interest']; //'利息余额',
            $this->borrow_count = $data['borrow_count']; //'累计借贷笔数',
            $this->borrower_number = $data['borrower_number']; //'累计借款人数量',
            $this->remain_money = $data['remain_money']; //'借贷余额',
            $this->average_borrow_amount = $data['average_borrow_amount']; //'人均累计借款金额',
            $this->average_loan_amount = $data['average_loan_amount']; //'人均累计出借金额',
            $this->remain_money_count = $data['remain_money_count']; //'借贷余额笔数',
            $this->third_guarantee_amount = $data['third_guarantee_amount']; //'第三方累计代偿金额',
            $this->third_guarantee_count = $data['third_guarantee_count']; //'第三方累计代偿笔数',
            $this->relation_borrow_amount = $data['relation_borrow_amount']; //'关联关系借款金额',
            $this->relation_borrow_count = $data['relation_borrow_count']; //'关联关系借款笔数',
            $this->total_borrower_count = $data['total_borrower_count']; //'当前出借人数量',
            $this->total_loaner_count = $data['total_loaner_count']; //'当前借款人数量',
            $this->top_ten_borrower_paid = $data['top_ten_borrower_paid']; //'前十大借款人待还金额',
            $this->largest_borrower_paid_percent = $data['largest_borrower_paid_percent']; //'最大单借款人待还金额占比',
            $this->dealline = $data['dealline']; //'截止日期',
            return $this->insert();
        }else{
            $this->id  = $dataOnline['id'];
            return $this->update($data);
        }
    }

    public function saveImage($data){
        $dataOnline = $this->getShowData();
        if(empty($dataOnline)){
            $this->overdue_data_list_attachment = $data['overdue_data_list_attachment'];
            $this->overdue_chart_attachment = $data['overdue_chart_attachment'];
            return $this->insert();
        }else{
            $this->id = $dataOnline['id'];
            return $this->update($data);
        }
    }

}
