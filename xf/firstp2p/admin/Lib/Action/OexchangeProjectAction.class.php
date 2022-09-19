<?php

use libs\utils\Logger;

class OexchangeProjectAction extends CommonAction{

    public function index(){
        $where = "1 = 1";
        $oModel = D('OffexchangeProject');
        if(is_numeric($_REQUEST['is_ok']) && in_array($_REQUEST['is_ok'], [0,1])){
            $where .= " and `is_ok` = {$_REQUEST['is_ok']} ";
        }
        if(!empty($_REQUEST['id'])){
            $where .= " and `id` = ".intval($_REQUEST['id']) . " ";
        }
        if(!empty($_REQUEST['name'])){
            $name = trim($_REQUEST['name']);
            $name = urldecode($name);
            $where .= " and `name` like '%{$name}%' ";
        }
        if(!empty($_REQUEST['jys_number'])){
            $jys_number = trim($_REQUEST['jys_number']);
            $jys_number = urldecode($jys_number);
            $where .= " and `jys_number` like '%{$jys_number}%' ";
        }
        if(!empty($_REQUEST['jys_id'])){
            $where .= " and `jys_id` = ".intval($_REQUEST['jys_id']) . " ";
        }
        if(!empty($_REQUEST['fx_uid'])){
            $where .= " and `fx_uid` = ".intval($_REQUEST['fx_uid']) . " ";
        }
        if(!empty($_REQUEST['fx_name'])){
            $fxName = trim($_REQUEST['fx_name']);
            $uid = M("user")->field(" GROUP_CONCAT(id) as uids ")->where(" real_name LIKE '%" . addslashes($fxName) . "%' ")->find();
            if(empty($uid['uids'])){
                $where .= " and 1 < 0 ";
            }else{
                $where .= " and `fx_uid` in ({$uid['uids']}) ";
            }
        }
        if(!empty($_REQUEST['deal_status'])){
            $where .= " and `deal_status` = ".intval($_REQUEST['deal_status']) . " ";
        }

        $count = $oModel->getOexchangeProjectCount($where);
        $iPageSize = 30;
        $p = new Page ( $count, $iPageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        if($count > 0){
            $list = $oModel->getOexchangeProjectList($where, $p->nowPage, $iPageSize);
            $aFxuid = array();
            foreach ($list as $item) {
                $aFxuid[] = intval($item['fx_uid']);
            }
            $user_list = M("user")->field("`id`, `real_name`")->where(" id IN (".implode(",", $aFxuid).") ")->select();
            $vuser_list = array();
            foreach ($user_list as $item) {
                $vuser_list[$item['id']] = $item;
            }
            $this->assign('user_list', $vuser_list);
        }else{
            $list = array();
        }
        $agencyAll = M('deal_agency')->where(" `type` = 9 ")->findAll();
        $jys = array();
        foreach ($agencyAll as $item) {
            $jys[$item['id']] = $item;
        }

        $this->assign('jys', $jys);

        $this->assign('project_business_status', array(1=>"等待确认", 2=>"进行中", 3=>"还款中", 4=>"已还清"));
        $this->assign('list', $list);
        $this->assign('_REQUEST', $_REQUEST);
        $this->display ();
    }

    public function add(){
        $isSave = !empty($_POST['is_save']);
        if($isSave){
            $this->savePro($_POST);
        }else{
            $this->assign('view_status', 1);
            $this->addview();
        }
    }

    public function edit(){
        $id = intval($_GET['id']);
        if(empty($id)){
            $this->error ( "参数错误");
        }
        $oModel = D('OffexchangeProject');
        $aPro = $oModel->getById($id);
        if(empty($aPro)){
            $this->error ( "编辑信息不存在");
        }
        if($aPro['deal_status'] > 2){
            $this->error ( "项目当前状态不可编辑");
        }
        $Batchcount = D("OffexchangeBatch")->getOexchangeBatchCount(" pro_id = $id and is_ok = 1 ");
        $bCanQR = false;
        if(empty($Batchcount)){
            $bCanQR = true;
        }

        $this->assign('view_status', $aPro['deal_status'] + 1);
        $this->assign('project', $aPro);
        $this->assign('bCanQR', $bCanQR);
        $this->addview();
    }

    public function view(){
        $id = intval($_GET['id']);
        if(empty($id)){
            $this->error ( "参数错误");
        }
        $oModel = D('OffexchangeProject');
        $aPro = $oModel->getById($id);
        if(empty($aPro)){
            $this->error ( "编辑信息不存在");
        }
        $this->assign('view_status', 99);
        $this->assign('project', $aPro);
        $this->addview();
    }

    public function copy (){
        $id = intval($_GET['id']);
        if(empty($id)){
            $this->error ( "参数错误");
        }
        $oModel = D('OffexchangeProject');
        $aPro = $oModel->getById($id);
        if(empty($aPro)){
            $this->error ( "编辑信息不存在");
        }
        $unsetFiled = array("id", "real_amount", "deal_status", "cuid", "approve_number");
        foreach ($unsetFiled as $f) {
            unset($aPro[$f]);
        }
        if($aPro['repay_type'] == 1){
            $aPro['repay_time_day'] = $aPro['repay_time'];
        }

        $aPro['name'] .= "-复制";
        $aPro['jys_number'] .= "-复制";
        $aPro['is_ok'] = 1;
        $this->savePro($aPro);
    }

    private function addview(){
        $agencyAll = M('deal_agency')->where(" `type` in (1,2,9,11,12)")->findAll();
        $agency = array();
        foreach ($agencyAll as $item) {
            $agency[$item['type']][] = $item;
        }
        $repay_time = get_repay_time_month(2, 36, 1);
        $repay_time_ji = get_repay_time_month(3, 36, 3);

        $this->assign('agency', $agency);
        $this->assign('repay_time', $repay_time);
        $this->assign('repay_time_ji', $repay_time_ji);
        $this->display ('add');
    }

    private function savePro($data){
        $oModel = D('OffexchangeProject');
        $aNotEmpty = array();
        $aNotNum = array('name', "jys_number", "money_todo");

        $aNotEmpty['name'] = trim($data['name']);
        //判断项目名称是否唯一
        $id = empty($data['id']) ? 0 : intval($data['id']);
        $isUniqueName = $oModel->checkNameIsUnique($id, $aNotEmpty['name']);
        if(!$isUniqueName){
            $this->error ( "该项目名称已存在");
        }

        $aNotEmpty['jys_number'] = trim($data['jys_number']);
        $iaNotEmpty['fx_uid'] = intval($data['fx_uid']);
        $aNotEmpty['amount'] = floatval($data['amount']);
        $aNotEmpty['expect_year_rate'] = floatval($data['expect_year_rate']);
        $aNotEmpty['money_todo'] = trim($data['money_todo']);

        foreach ($aNotEmpty as $key => $value) {
            if(empty($value)){
                $this->error ( "参数{$key}错误");
            }
            if(!in_array($key, $aNotNum) && !is_numeric($value)){
                $this->error ( "参数{$key}值错误");
            }
        }

        $repay_type = intval($data['repay_type']);
        if($repay_type == 1){
            $repay_time = intval($data['repay_time_day']);
            if(empty($repay_time)){
                $this->error ( "参数错误");
            }
        }
        $bRet = $oModel->saveOexchangeProject($data);
        if($bRet){
            $this->redirect(u(MODULE_NAME."/index"));
        }else{
            $this->error ( "保存失败");
        }
    }
}
