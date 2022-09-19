<?php

/**
 * 暗月-标的Action
 * @author jin
 * @date 2018-5-5 18:00:47
 *
 * Class DealAction
 */

use libs\utils\Logger;
use NCFGroup\Protos\Contract\RequestGetCategorys;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\service\darkmoon\DarkMoonService;
use core\service\darkmoon\ContractService;
use libs\utils\Rpc;

class DarkMoonDealAction extends CommonAction
{
    private $dmDeal;
    private $jysArr = [];
    public function __construct()
    {
        $this->dmDeal = M('DarkmoonDeal');
        parent::__construct();
        // 交易所
        $jysList = M("DealAgency")->where('is_effect = 1 and type=9 ')->order('id ASC')->findAll();
        foreach ($jysList as $jys) {
            $this->jysArr[$jys['id']] = $jys['name'];
        }
    }

    public function index()
    {
        $condition = ['is_effect' => 1];
        $condition = ['deal_status' => ['neq','3']];
        if (!empty($_REQUEST['jys_record_number'])) {
            $condition['jys_record_number'] = trim($_REQUEST['jys_record_number']);
        }
        if (!empty($_REQUEST['jys_id'])) {
            $condition['jys_id'] = intval($_REQUEST['jys_id']);
        }
        if (isset($_REQUEST['deal_status']) && $_REQUEST['deal_status'] !== '') {
            $condition['deal_status'] = intval($_REQUEST['deal_status']);
            if ($condition['deal_status'] == 3) {
                $this->assign("isTrash", 1);
            }
        }
        if (!empty($_REQUEST['user_id'])) {
            $condition['user_id'] = intval($_REQUEST['user_id']);
        }

        if (!empty($this->dmDeal)) {
            $this->_list($this->dmDeal, $condition, 'create_time');
        }

        $this->assign("jys", $this->jysArr);
        $this->assign("dealStatus", DarkmoonDealModel::$dealStatus);
        $this->display();
        return;
    }

    protected function form_index_list(&$voList){
        foreach ($voList as &$v) {
            $v['jysArr'] = $this->jysArr;
            $v['dealStatus'] = DarkmoonDealModel::$dealStatus;
            $userInfo = M('User')->where('id='.intval($v['user_id']))->find();
            $v['userName'] = $userInfo ? $userInfo['real_name'] : '--';
            $v['status'] = $v['deal_status'] == 0;
        }
    }

    public function add(){
        $this->_editData();
        $this->display ();
    }

    public function edit(){

        $this->_editData();
        $id = intval($_GET['id']);
        if ($id) {
            $condition['id'] = $id;
            $dealData = $this->dmDeal->where($condition)->find();
            $this->assign("deal", $dealData);
        }
        $this->display ();
    }

    public function trash()
    {
        $condition['is_effect'] = 0;
        if (!empty($this->dmDeal)) {
            $this->_list($this->dmDeal, $condition, 'create_time');
        }
        $this->assign("isTrash", 1);
        $this->display('index');
        return;
    }

    public function del()
    {
        $dlCondition['deal_id'] = intval($_GET['id']);
        $dlCondition['status'] = 2;
        $signCount = M('DarkmoonDealLoad')->where($dlCondition)->count();
        if ($signCount > 0) {
            $this->error('已有签署客户，标的不可以作废!');
        }

        $condition['id'] = intval($_GET['id']);
        $rs = $this->dmDeal->where($condition)->setField("deal_status", 3);
        if ($rs) {
            $setCondition['deal_id'] = intval($_GET['id']);
            M('DarkmoonDealLoad')->where($setCondition)->setField("status", 3);
            $this->success('作废成功！');
        } else {
            $this->error('作废失败！');
        }
        return;
    }

    public function save(){
        $data = $this->dmDeal->create();
        if (!empty($data['jys_record_number'])) {
            $condition['jys_record_number'] = $data['jys_record_number'];
            $condition['is_effect'] = 1;
            $rs = $this->dmDeal->where($condition)->find();
            if (!empty($rs) && $rs['id'] != $data['id']){
                $this->error('交易所备案编号已经存在！');
            }
        }
        $dmDealModel = new DarkmoonDealModel();
        if ($dmDealModel->saveData($data)) {
            save_log("Add_DarkmoonDeal:".implode('|',$data), 1);
        }
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function updateDealStatus()
    {
        $id = intval($_GET['id']);
        //$status = intval($_GET['status']);
        $status = DarkmoonDealModel::DEAL_SIGNING_STATUS;
        $deal = DarkmoonDealModel::instance()->find($id);
        if($deal['deal_status'] <  $status){
            try{
                $GLOBALS['db']->startTrans();
                $deal['deal_status'] = $status;
                if(!$deal->save()){
                    throw new \Exception('更新标的状态失败');
                }
                //生成不用盖戳的合同记录
                //TODO 如果有多方签署的合同,并且该合同一个标只有一份，则需要修改fullSendContract
                $cs = new \core\service\darkmoon\ContractService();
                $contResult = $cs->fullSendContract($id);
                if(!$contResult){
                    throw new \Exception('生成合同失败');
                }
                $GLOBALS['db']->commit();
                $this->success('更新成功！');
                return true;
            }catch(\Exception $e){
                $GLOBALS['db']->rollback();
                $this->error('更新失败！ 失败原因:' . $e->getMessage());
                return false;
            }
        }else{
            $this->error('合同已经生成');
            return false;
        }
        // TODO 测试用，生成返利数据，后续后移到生成时间戳时
        /*$darkMoonService = new DarkMoonService();
        $rs_coupon = $darkMoonService->couponConsumeByDealId($condition['id']);

        $send_email = $darkMoonService->sendEmailByDealId($condition['id']);
        */
    }

    private function _editData(){
        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
        $this->assign("deal_agency",$deal_agency);

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory", $deal_advisory);

        //还款方式 还款期限
        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE']);
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));
        $this->assign('repay_time_month', get_repay_time_month());

        //合同
        $tplRequest = new RequestGetCategorys();
        $tplRequest->setIsDelete(0);
        $tplRequest->setSourceType(ContractService::SOURCETYPE);
        $rpc = new Rpc('contractRpc');
        $tplResponse = $rpc->go( "\NCFGroup\Contract\Services\Category", "getCategorys",$tplRequest);

        if(!is_array($tplResponse->list)){
            $this->error('获取模板分类失败，请检查合同服务！');
        }

        $this->assign('contract_tpl_type', $tplResponse->list);

        $this->assign("jys", $this->jysArr);
    }

    public function genTimestamp(){
        $id = $_GET['id'];

        $deal = DarkmoonDealModel::instance()->getInfoById($id);

        if($deal['deal_status'] != 2){
            $this->error('只有在已签署状态才能进行打戳');
        }

        try{
            $GLOBALS['db']->startTrans();
            if(!\core\service\ContractInvokerService::signAllContractByServiceId('signer', $id,\NCFGroup\Protos\Contract\Enum\ContractServiceEnum::SERVICE_TYPE_DARK_MOON_DEAL)) {
                throw new \Exception("打戳失败 deal_id".$id);
            }
            $darkMoonService = new DarkMoonService();
            //生成返利数据
            $rs_coupon = $darkMoonService->couponConsumeByDealId($id);
            if(!$rs_coupon){
                throw new \Exception("生成返利记录失败 deal_id".$id);
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            $this->error($ex->getMessage());
        }
        $this->success('更新成功！');
    }
    public function sendEmail(){
        $deal_id = intval($_GET['id']);
        $darkMoonService = new DarkMoonService();
        $res= $darkMoonService->isTimeStampFinishedByDealId($deal_id);
        $error="标的状态不正确";
        if($res){
            $result=$darkMoonService->sendEmailByDealId($deal_id);
            if($result){
                $this->success("发送成功");
            }
            $error="发送失败";
        }
        $this->error($error);
    }

    public function sendSms(){

        $id = intval($_GET['id']);

        $deal = DarkmoonDealModel::instance()->getInfoById($id);
        if (empty($deal)){
            $this->error('标信息不存在');
        }
        if ($deal['deal_status'] != DarkmoonDealModel::STATUS_DEAL_COMPLETE){
            $this->error('借款人必须签署完成，才能给投资人发送短信');
        }
        $darkmoonService = new DarkMoonService();

        $ret = $darkmoonService->sendDealLoadSms($id);
        if ($ret){
            $this->success('发送成功');
        }

        $this->error('发送失败');
    }


}
