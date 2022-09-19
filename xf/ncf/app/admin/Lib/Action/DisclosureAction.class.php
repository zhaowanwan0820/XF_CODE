<?php

use libs\utils\Logger;
use core\service\disclosure\DisclosureService;

class DisclosureAction extends CommonAction{

    public function showData(){
        $name=$this->getActionName();
        $model = DI($name);
        if (! empty ( $model )) {
            $voList = $this->_list ( $model, "1=1");
        }
        $this->assign('data', $voList[0]);
        $this->display();
    }

    public function showImage(){
        $name=$this->getActionName();
            $model = DI($name);
            if (! empty ( $model )) {
                $voList = $this->_list ( $model, "1=1");
        }
        $this->assign('data', $voList[0]);
        $this->display();
    }



    public function uploadImage()
    {
        $data = array(
            'overdue_data_list_attachment' =>addslashes($_REQUEST['dataImageUrl']),
            'overdue_chart_attachment' =>addslashes($_REQUEST['chartImageUrl']),
        );
        // 保存
        $service = new DisclosureService();
        $result = $service->saveImage($data);
        //日志信息
        if (false !== $result) {
            //成功提示
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            $this->error(L("UPDATE_FAILED"));
        }
    }

    public function loadFile() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }
        if (!empty($file)) {
            $maxwidth =  $_REQUEST['id'] == 'overdue_data_list' ? 953 : 979;
            $maxheight =  $_REQUEST['id'] == 'overdue_data_list' ? 157 : 387;
            resizeImage($file['tmp_name'], $maxwidth, $maxheight,$file['tmp_name'],'',1);
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(600 / 1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
        }
        if(!empty($result['aid']) && empty($result['errors'])){
            $imgUrl = get_attr($result['aid'],1,false);
            $rel = array("code" => 1,"imgUrl" => $imgUrl);
        }else if(!empty($result['errors'])){
            $rel = array("code" => 0,"message" => end($result['errors']));
        }else{
            $rel = array("code" => 0,"message" => "图片上传失败");
        }
        echo  json_encode($rel);
    }

    public function update(){
        $data = array(
            'borrow_amount' =>floatval($_REQUEST['borrow_amount']), //'累计借贷金额',
            'loaner_number' =>intval($_REQUEST['loaner_number']), //'累计出借人数量',
            'remain_interest' =>floatval($_REQUEST['remain_interest']), //'利息余额',
            'borrow_count' =>intval($_REQUEST['borrow_count']), //'累计借贷笔数',
            'borrower_number' =>intval($_REQUEST['borrower_number']), //'累计借款人数量',
            'remain_money' =>floatval($_REQUEST['remain_money']), //'借贷余额',
            'average_borrow_amount' =>floatval($_REQUEST['average_borrow_amount']), //'人均累计借款金额',
            'average_loan_amount' =>floatval($_REQUEST['average_loan_amount']), //'人均累计出借金额',
            'remain_money_count' =>intval($_REQUEST['remain_money_count']), //'借贷余额笔数',
            'third_guarantee_amount' =>floatval($_REQUEST['third_guarantee_amount']), //'第三方累计代偿金额',
            'third_guarantee_count' =>intval($_REQUEST['third_guarantee_count']), //'第三方累计代偿笔数',
            'relation_borrow_amount' =>floatval($_REQUEST['relation_borrow_amount']), //'关联关系借款金额',
            'relation_borrow_count' =>intval($_REQUEST['relation_borrow_count']), //'关联关系借款笔数',
            'total_borrower_count' =>intval($_REQUEST['total_borrower_count']), //'当前出借人数量',
            'total_loaner_count' =>intval($_REQUEST['total_loaner_count']), //'当前借款人数量',
            'top_ten_borrower_paid' =>floatval($_REQUEST['top_ten_borrower_paid']), //'前十大借款人待还金额占比',
            'largest_borrower_paid_percent' =>floatval($_REQUEST['largest_borrower_paid_percent']), //'最大单借款人待还金额占比',
            'dealline' =>$_REQUEST['dealline'], //'截止日期',
        );
        $service = new DisclosureService();
        $result = $service->saveData($data);
        if($result){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
}
