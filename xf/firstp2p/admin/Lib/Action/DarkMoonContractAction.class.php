<?php


use core\service\darkmoon\ContractService;
use core\service\UserService;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\dao\darkmoon\DarkmoonDealModel;


class DarkMoonContractAction extends CommonAction{

    public function __construct() {
        parent::__construct();
    }

  public function index() {
      $pageNum = intval($_REQUEST['p']);
      $pageNum = $pageNum > 0 ? $pageNum : 1;
      $pageSize = C('PAGE_LISTROWS');
      $limit = (($pageNum-1)*$pageSize).",".$pageSize;
      $map = array();
      $deal_id = intval($_REQUEST['dealid']);
      if($deal_id){
          $map['deal_id'] = $deal_id;
      }

      $list = M("DarkmoonDealLoad")->where($map)->order('id desc')->limit($limit)->findAll();
      $count = M("DarkmoonDealLoad")->where($map)->count();

      $this->form_index_list($list);
      $this->assign('list', $list);
      $this->assign('deal', $deal);
      $this->assign("dealStatus", DarkmoonDealModel::$dealStatus);
      $p = new Page ($count, $pageSize);
      $page = $p->show ();
      $this->assign ( "page", $page );
      $this->assign ( "nowPage", $p->nowPage );
      $this->display();
    }

    protected function form_index_list(&$list){
        if(!empty($list)){
            foreach ($list as &$val){
                $dealinfo = $this->get_deal_data($val['deal_id']);
                $val['dealName'] = $dealinfo["jys_record_number"];
                $userModel = new \core\dao\UserModel ();
                $userInfo=$userModel->find($val['user_id']);
                $val['userMobile'] = $userInfo['mobile'];
                $borrowUserInfo=$userModel->find($dealinfo['user_id']);
                $val['borrowMobile'] = $borrowUserInfo['mobile'];
                $val['borrowUserName'] = $borrowUserInfo['real_name'];
                $val['borrowUserSignTime'] = $dealinfo['sign_time']?date("Y-m-d H:i:s",$dealinfo['sign_time']):'-';
                $val['borrowUserSignTime'] = $val['status']==DarkmoonDealLoadModel::SIGN_DISCARD_STATUS ?'-':$val['borrowUserSignTime'];
                $val['userSignTime'] = $val['sign_time']?date("Y-m-d H:i:s",$val['sign_time']):'-';
                $val['createTime'] = $val['create_time']?date("Y-m-d H:i:s",$val['create_time']):'-';
                $val['borrowUserSignStatus'] = $val['status']==DarkmoonDealLoadModel::SIGN_DISCARD_STATUS ? DarkmoonDealLoadModel::$signstatus[$val['status']]:DarkmoonDealModel::$dealStatus[$dealinfo['deal_status']];
                $val['userSignStatus'] = DarkmoonDealLoadModel::$signstatus[$val['status']];
                $val['dealStatus'] = $dealinfo['deal_status'];
                if($val['status'] == 2 ){
                    $val['tpls'] = (new ContractService())->getContractListByDealLoadId($val['id']);
                    if($dealinfo['deal_status'] != 4){// getContractListByDealLoadId 能获取到已经打戳合同，deal_status = 4 表示已经打完戳了
                        $val['tpls'] = array_merge($this->getSystemContactList($val['deal_id']),$val['tpls']);
                    }
                    //$val['tpls'][] = $this->getTplByPrefix($val['deal_id']);
                }else{
                    $val['tpls'] = $this->getTplsByDealId($val['deal_id']);
                }
            }
        }
    }

    /**
     * 获取标的信息
     */
    protected function get_deal_data($deal_id) {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $deal_info = array();
        if (!isset($deal_info[$deal_id])) {
              $deal_info[$deal_id] = M("DarkmoonDeal")->where(array("id"=>$deal_id))->find();
        }
        return $deal_info[$deal_id];
    }

    /**
     * 获取模板的信息
     */
    protected function getTplByPrefix($deal_id) {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $tpl = array();
        if (!isset($tpl[$deal_id])) {
            $tpl[$deal_id] = (new ContractService())->getTplByPrefix($deal_id);
        }
        return $tpl[$deal_id];
    }

    /**
     * 获取不需要签署的合同
     */
    protected function getSystemContactList($deal_id) {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $systemContracts = array();
        if (!isset($systemContracts[$deal_id])) {
            $systemContracts[$deal_id] = (new ContractService())->getSystemContactList($deal_id);
        }
        return $systemContracts[$deal_id];
    }

    /**
     * 获取模板的信息
     */
    protected function getTplsByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $tpls = array();
        if (!isset($tpl[$deal_id])) {
            $tpls[$deal_id] = (new ContractService())->getContractList($deal_id);
        }
        return $tpls[$deal_id];
    }


    //预览
    public function opencontract(){
        $tplId = intval($_REQUEST['tplId']);
        $cId = intval($_REQUEST['cId']);
        $dealLoadId = intval($_REQUEST['id']);
        $dealLoadInfo = DarkmoonDealLoadModel::instance()->find($dealLoadId);
        if($cId){
            //合同已经成
            $contract = (new ContractService())->getContract($cId, $dealLoadInfo['deal_id'],$dealLoadInfo['user_id']);
        }else{
            //合同未生产下载预览合同
            $contract = (new ContractService())->viewContract($dealLoadInfo['deal_id'], $tplId, $dealLoadId);
        }

        echo $contract['content'];
    }

    //下载
    public function download(){
        $tplId = intval($_REQUEST['tplId']);
        $dealLoadId = intval($_REQUEST['id']);
        $cId = intval($_REQUEST['cId']);
        $dealLoadInfo = DarkmoonDealLoadModel::instance()->find($dealLoadId);
        if($cId){
            //合同已经成
            (new ContractService())->download($cId ,$dealLoadInfo['deal_id'],$dealLoadInfo['user_id']);
        }else{
            //合同未生产下载预览合同
            (new ContractService())->viewDownload($dealLoadInfo['deal_id'], $tplId, $dealLoadId);
        }
    }


    //下载打戳pdf
    public function downloadTsa(){
        $cId = intval($_REQUEST['cId']);
        $dealLoadId = intval($_REQUEST['id']);
        $dealLoadInfo = DarkmoonDealLoadModel::instance()->find($dealLoadId);
        (new ContractService())->downloadTsa($cId ,$dealLoadInfo['deal_id']);
    }

}