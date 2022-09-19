<?php

use libs\utils\Logger;

class OexchangeBatchAction extends CommonAction{

    public function index(){
        $iProId = intval($_GET['pro_id']);
        if(empty($iProId)){
            $this->error ( "参数错误");
        }
        $oModelP = D('OffexchangeProject');
        $aPro = $oModelP->getById($iProId);
        if(empty($aPro)){
            $this->error ( "项目信息不存在");
        }
        $oModelB = D('OffexchangeBatch');
        $where = " `pro_id` = ".$aPro['id'];
        $count = $oModelB->getOexchangeBatchCount($where);
        $p = new Page ( $count, 10);
        $page = $p->show ();
        $this->assign ( "page", $page );
        if($count > 0){
            $list = $oModelB->getOexchangeBatchList($where, $p->nowPage);
            $fxuser = M("user")->field("`id`, `real_name`")->where(" id = {$aPro['fx_uid']} ")->find();
            $this->assign('fxuser', $fxuser);
        }else{
            $list = array();
        }

        $this->assign('project', $aPro);
        $this->assign('branch_business_status', array(1=>"进行中", 2=>"还款中", 3=>"已还清"));
        $this->assign('list', $list);
        $this->display ();
    }

    public function add(){
        $iProId = intval($_REQUEST['pro_id']);
        if(empty($iProId)){
            $this->error ( "参数错误");
        }
        $oModel = D('OffexchangeProject');
        $aPro = $oModel->getById($iProId);
        if(empty($aPro) || $aPro['deal_status'] != 2){
            $this->error ( "项目信息不存在或不可添加批次");
        }
        $aBatch = array(
            'pro_id' => $aPro['id'],
            'consult_rate' => intval($aPro['consult_rate'] * OffexchangeBatchModel::RATENUM),
            'guarantee_rate' => intval($aPro['guarantee_rate'] * OffexchangeBatchModel::RATENUM),
            'invest_adviser_rate' => intval($aPro['invest_adviser_rate'] * OffexchangeBatchModel::RATENUM),
            'publish_server_rate' => intval($aPro['publish_server_rate'] * OffexchangeBatchModel::RATENUM),
            'hang_server_rate' => intval($aPro['hang_server_rate'] * OffexchangeBatchModel::RATENUM),
        );
        $bRet = D('OffexchangeBatch')->saveOexchangeBatch($aBatch);
        if(is_string($bRet)){
            $this->error ($bRet);
        }
        if($bRet){
            $this->redirect(u(MODULE_NAME."/index?pro_id=".$aPro['id']));
        }else{
            $this->error ( "批次添加失败");
        }
    }

    public function edit(){
        $isSave = !empty($_POST['id']);
        $id = $isSave ? intval($_POST['id']) : intval($_GET['id']);
        if(empty($id)){
            $this->error ( "参数错误");
        }
        $oModelB = D('OffexchangeBatch');
        $aBatch = $oModelB->getById($id);
        if(empty($aBatch)){
            $this->error ( "编辑信息不存在");
        }
        if($aBatch['deal_status'] != 1){
            $this->error ( "项目当前状态不可编辑");
        }
        if($isSave){//保存
            $bRet = $oModelB->saveOexchangeBatch($_POST);
            if(is_string($bRet)){
                $this->error ($bRet);
            }
            if($bRet){
                $this->redirect(u(MODULE_NAME."/index?pro_id=".$aBatch['pro_id']));
            }else{
                $this->error ( "批次编辑失败");
            }
        }else{
            $oModelP = D('OffexchangeProject');
            $aPro = $oModelP->getById($aBatch['pro_id']);
            $iBatchAmount = M('exchange_load')->where(" `batch_id` = ".$aBatch['id']." and `project_id` = ".$aBatch['pro_id']." and `status` = 1 ")->sum('pay_money');

            $this->assign('iBatchAmount', $iBatchAmount);
            $this->assign('project', $aPro);
            $this->assign('batch', $aBatch);
            $this->display ();
        }
    }

    public function fee(){
        $iBid = intval($_GET['id']);
        if(empty($iBid)){
            $this->error ( "参数错误");
        }
        $oModelB = D('OffexchangeBatch');
        $aBatch = $oModelB->getById($iBid);
        if(empty($aBatch)){
            $this->error ( "批次信息不存在");
        }
        $oModel = D('OffexchangeProject');
        $project = $oModel->getById($aBatch['pro_id']);
        if(empty($project)){
            $this->error ( "项目信息不存在");
        }
        if(isset($_GET['export'])){//导出
            $agencyAll = M('deal_agency')->where(" `type` in (1,2,9,11,12)")->findAll();
            $agency = array();
            foreach ($agencyAll as $item) {
                $agency[$item['type']][$item['id']] = $item;
            }
            $content = iconv("utf-8","gbk","备案编号,期数,生成费用日期,募集金额,借款期限,业务管理方,发行服务费率,发行服务费,投资顾问方,投资顾问费率,投资顾问费,咨询机构,咨询费率,咨询费,担保机构,担保费率,担保费,交易所,挂牌费率,挂牌服务费");
            $content .= "\n";
            $content .= iconv("utf-8","gbk","{$project['jys_number']},{$aBatch['batch_number']} 期,".substr($aBatch['fee_time'],0,10).",\t{$aBatch['amount']} 元,{$project['repay_time']}".(1 == $project['repay_type'] ? "天" : "月").",{$agency[12][$project['business_manage_id']]['name']},{$aBatch['publish_server_rate']} %,\t{$aBatch['publish_server_fee']} 元, {$agency[11][$project['invest_adviser_id']]['name']}, {$aBatch['invest_adviser_rate']} %,\t{$aBatch['invest_adviser_fee']} 元, {$agency[2][$project['consult_id']]['name']}, {$aBatch['consult_rate']} %,\t{$aBatch['consult_fee']} 元, {$agency[1][$project['guarantee_id']]['name']}, {$aBatch['guarantee_rate']} %,\t{$aBatch['guarantee_fee']} 元, {$agency[9][$project['jys_id']]['name']}, {$aBatch['hang_server_rate']} %,\t{$aBatch['hang_server_fee']} 元");
            $content .= "\n";

            $datatime = date("YmdHis",get_gmtime());
            header("Content-Disposition: attachment; filename=fee_{$datatime}.csv");
            echo $content;
            return;
        }else{
            $this->assign('project', $project);
            $this->assign('batch', $aBatch);
            $this->display ();
        }
    }
}
