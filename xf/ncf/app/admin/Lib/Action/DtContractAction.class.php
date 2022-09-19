<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/10/26
 * Time: 下午4:42
 */

use core\service\contract\CategoryService;
use core\enum\contract\ContractServiceEnum;
use core\service\contract\TplService;
use core\service\contract\ContractTplIdentifierService;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\contract\ContractPreService;
use core\service\contract\ContractService;
use core\service\contract\ContractInvokerService;
use libs\tcpdf\Mkpdf;

class DtContractAction extends DtCommonAction {

    public static $contractTypeMap = array(
        0 => '顾问协议',
        1 => '借款合同',
        2 => '债权转让协议'
        );

    public static $roleMap = array(
        0 => '出借/受让方',
        1 => '出借方',
        2 => '受让方'
    );


    /**
     * 合同模板分类管理
     */
    public function category() {
        $p = $_REQUEST['p']?intval($_REQUEST['p']):1;

       $response =  CategoryService::getCategoryList(ContractServiceEnum::TYPE_DT,null,$p);
        if($response){
            $list = $response['data'];

            foreach($list as &$v){
                if($v['createTime'] > 0){
                    $v['createTime'] = $v['createTime'] - 28800;
                }
            }

            $totalPage = $response['totalPage'];
            $totalNum = $response['totalNum'];

            $page = new \Page($totalNum, app_conf("PAGE_SIZE"));
            $page_str = $page->show();

            $this->assign('page',$page_str);
            $this->assign('p',$p);
            $this->assign('totalPage',$totalPage);
            $this->assign('totalNum',$totalNum);
            $this->assign('list', $list);
            $this->display ();
        }else{
            $this->error("RPC response is null");
        }
        
    }

    /**
     * 合同分类添加
     */
    public function contTypeAdd(){
        if($_REQUEST['save'] == true){
            $response = CategoryService::addCategory(trim($_REQUEST['typeName']),trim($_REQUEST['typeTag']),intval($_REQUEST['contractType']),0,intval($_REQUEST['useStatus']),1,intval($_REQUEST['type']),0);
            if($response){
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $this->display ();
        }
    }


    /**
     * 合同分类修改
     */
    public function contTypeEdit(){

        if($_REQUEST['update'] == true){

            $response = CategoryService::updateCategoryById(intval($_REQUEST['id']),trim($_REQUEST['typeName']),trim($_REQUEST['typeTag']),intval($_REQUEST['contractType']),number_format($_REQUEST['contractVersion'], 2),intval($_REQUEST['useStatus']));

            if($response->status == true){
                $this->success("修改成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $response = CategoryService::getCategoryById(intval($_REQUEST['id']));
            $this->assign('type_info',$response);
            $this->display();
        }
    }

    public function showTemplates()
    {
        //查询当前选中的模板
        $typeId = intval($_REQUEST['typeId']);
        $contractVersion = $_REQUEST['contractVersion']?$_REQUEST['contractVersion']:number_format(1,2);
        //获取分类下全部模板
        if(intval($typeId)>0){
            $response = TplService::getTplsByCid($typeId,$contractVersion);
            if(count($response)>0){
                $this->assign("tpl_list",$response);
            }
            if(isset($_REQUEST['editId'])&&(intval($_REQUEST['editId'])>0)){
                $response = TplService::getTplById(intval($_REQUEST['editId']));
                $tpl = $response;
            }
        }else{
            $this->error('分类ID不正确');
        }

        $this->assign("tpl",$tpl);
        $this->assign("contract_version",floatval($contractVersion));
        $this->assign("type_id",$typeId);

        $response = ContractTplIdentifierService::getTplIdentifierList();
        $this->assign('tpl_identifier_list', $response);

        $this->display();
    }

    public function add(){
        //查询当前选中的模板
        $typeId = intval($_REQUEST['typeId']);
        $contractVersion = $_REQUEST['contractVersion']?$_REQUEST['contractVersion']:number_format(1,2);
        $this->assign("contractVersion",$contractVersion);
        $this->assign("typeId",$typeId);

        // 获取模板标识列表
        $response = ContractTplIdentifierService::getTplIdentifierList();
        $this->assign('tpl_identifier_list', $response);

        $this->display ();
    }

    public function doAdd(){

        $contractTitle = trim($_REQUEST['contractTitle']);
        $name = trim($_REQUEST['name']);
        $type = intval($_REQUEST['type']);
        $isHtml = intval($_REQUEST['isHtml']);
        $typeId = intval($_REQUEST['typeId']);
        $content = str_replace('./', "", $_REQUEST['content']);
        $version = floatval(number_format($_REQUEST['version'],2));
        $tplIdentifierId = intval($_REQUEST['tplIdentifierId']);

        $this->assign('jumpUrl',"javascript:history.back(-1);");
        if($contractTitle == ''){
            $this->error('模板标题不能为空');
        }

        if($name == ''){
            $this->error('分类名称不能为空');
        }

        // 添加模板
        $response = TplService::addTpl($contractTitle,$name,$typeId,$content,$type,$isHtml,$version,$tplIdentifierId );

        if($response){
            $this->assign('jumpUrl','/m.php?m=DtContract&a=showTemplates&typeId='.$typeId.'&contractVersion='.$version);
            $this->success('添加成功');
        }else{
            //错误提示
            $this->error('添加失败');
        }

    }

    public function updateTpl(){

        $id = intval($_REQUEST['id']);
        $contractTitle = trim($_REQUEST['contractTitle']);
        $name = trim($_REQUEST['name']);
        $type = intval($_REQUEST['type']);
        $isHtml = intval($_REQUEST['isHtml']);
        $typeId = intval($_REQUEST['typeId']);
        $content = str_replace('./', "", $_REQUEST['content']);
        $version = floatval(number_format($_REQUEST['version'],2));
        $tplIdentifierId = intval($_REQUEST['tplIdentifierId']);

        $this->assign('jumpUrl',"javascript:history.back(-1);");
        if($contractTitle == ''){
            $this->error('模板标题不能为空');
        }

        if($name == ''){
            $this->error('分类名称不能为空');
        }

        // 添加模板
        $response = TplService::updateTplById($id,$contractTitle,$name,$typeId,$content,$type,$isHtml,$version,$tplIdentifierId);

        $new_version = $version + 0.01;
        if($response->status == true){
            $this->assign('jumpUrl','/m.php?m=DtContract&a=showTemplates&typeId='.$typeId.'&contractVersion='.$new_version);
            $this->success('更新成功');
        }else{
            //错误提示
            $this->error($response->errorMsg);
        }
    }

    public function dealLoanContract(){
        $loanId = intval($_REQUEST['loanId']);
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');


        $id = trim($_REQUEST['id']);
        $p2pDealId = $_REQUEST['p2p_deal_id'];
        $startDate = $_REQUEST['start_date'];
        $endDate = $_REQUEST['end_date'];

        $request = array(
            "pageNum"=>$pageNum,
            "pageSize"=>$pageSize,
            'loanId'=>$loanId,
            "p2pDealId"=>$p2pDealId,
            "startDate"=>$startDate,
            "endDate"=>$endDate,
        );
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\LoanMappingContract',
            'method' => 'getContractList',
            'args' => $request));
        if (!$response) {
            $this->error("rpc请求失败");
        }
        if ($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $list  = $response['data']['data'];
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $list[$key]['title'] = self::$contractTypeMap[$value['contract_type']];
                $list[$key]['role'] = self::$roleMap[$value['contract_type']];
                if($value['contract_type'] == 1){
                    $dealInfo = DealService::getDealInfo($value['p2p_deal_id']);
                    $userInfo = UserService::getUserById($dealInfo['user_id']);
                }elseif($value['contract_type'] == 2){
                    $userInfo = UserService::getUserById($value['redemption_user_id']);
                }else{
                    $userInfo = array();
                }
                $value['unique_id'] = str_pad($response['data']['tableIndex'],2,0,STR_PAD_LEFT).str_pad($value['cid'],20,0,STR_PAD_LEFT);
                $list[$key]['user_name'] = $userInfo['real_name'] ? $userInfo['real_name'] : '-';
                $list[$key]['create_time_date'] = date('Y-m-d H:i:s',$value['create_time']);
                $list[$key]['p2p_deal_id'] =intval($value['p2p_deal_id']);
                $list[$key]['dt_deal_id'] =intval($value['loan_id']);
                $list[$key]['dt_record_id'] =intval($value['cid']);
                $list[$key]['dt_loan_id'] =intval($value['redemption_loan_id']);
                $contract = $this->getContactByCtype($value['contract_type'],$value);
                $list[$key]['is_tsa'] = $contract['status']==1?1:0;
                $list[$key]['contract_id'] = isset($contract['id'])?$contract['id']:0;
                $list[$key]['number'] = isset($contract['number'])?$contract['number']:$this->getPreContractNumber($value['contract_type'],$value);
                $list[$key]['id'] = $key+$pageSize*($pageNum-1)+1;
                $list[$key]['money'] = bcdiv($value['money'], 100, 2);
                $projectId = $value['project_id'];
            }
        }
        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->assign("loan_id", $loanId);
        $this->assign("project_id", $projectId);
        $this->assign("data", $list);
        $this->display ();
    }

    //合同预览
    public function openContract(){
        $result = $this->getContractContent();
        echo hide_message($result);
    }

    private function getContactByCtype($ctype,$data){
        $dtLoanId = $data['loan_id'];
        $userId =  $data['user_id'];
        $p2pLoadId = $data['p2p_load_id'];
        $p2pDealId = $data['p2p_deal_id'];
        $redemptionLoanId = $data['redemption_loan_id'];
        switch ($ctype) {
            case 0:
                $result = ContractService::getContractByLoadId($dtLoanId,0,0, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT,false);
                break;
            case 1:
                $result =  ContractService::getContractByLoadId($p2pDealId,$p2pLoadId,0,ContractServiceEnum::SOURCE_TYPE_PH,true);
                break;
            case 2:
                $result = $this->getContractByLoadId($data);
                break;
        }
        return  empty($result)? false:$result[0];
    }

    public function getContractByLoadId($data){
        $dtLoanId = $data['loan_id'];
        $userId =  $data['user_id'];
        $p2pLoadId = $data['p2p_load_id'];
        $p2pDealId = $data['p2p_deal_id'];
        $redemptionLoanId = $data['redemption_loan_id'];

        $contractService = new ContractService();
        $number = $contractService->createDtNumber($dtLoanId,$data['unique_id']);
        $contract = ContractService::getContractByNumber($dtLoanId,$number, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
        return $contract;
    }

    private function getPreContractNumber($ctype,$data){
        $userId =  $data['user_id'];
        $p2pLoadId = $data['p2p_load_id'];
        $projectId = $data['project_id'];
        $p2pDealId = $data['p2p_deal_id'];
        $loanId = $data['loan_id'];
        $p2pLoadId = $data['p2p_load_id'];
        $redemptionLoanId = $data['redemption_loan_id'];
        $uniqueId = $data['unique_id'];
        switch ($ctype) {
            case 0:
                $number = $this->createDtDealNumber($projectId,ContractServiceEnum::TYPE_DT,10,$userId,$loanId);
                break;
            case 1:
                $number = ContractService::createDealNumber($p2pDealId,13,$userId,$dealLoadId);
                break;
            case 2:
                $number = ContractService::createDtNumber($loanId, $uniqueId);//合同编号
                break;
        }
        return $number;
    }

    //方法从合同库
    private function createDtDealNumber($dealId,$type,$contractType,$userId,$dealLoadId){
        //标的ID，标的类型（o：p2p,1:duotou）,合同类型，用户ID，机构ID，投资ID
        $number = str_pad(str_pad($dealId,10,"0",0).str_pad($type,2,"0",0).str_pad($contractType,2,"0",0).str_pad($userId,10,"0",0).str_pad($dealLoadId,10,"0",0),34,"0",0);
        $number = str_pad($number,34,0,STR_PAD_LEFT);
        return $number;
    }


    /**
    * 获取合同内容
    */
    private function getContractContent(){
        $ctype = intval($_REQUEST['ctype']);
        $money = $_REQUEST['money'];
        $userId = intval($_REQUEST['user_id']);
        $createTime = intval($_REQUEST['create_time']);
        $p2pDealId = intval($_REQUEST['p2p_deal_id']);
        $dtDealId = intval($_REQUEST['dt_deal_id']);
        $redemptionLoanId = $_REQUEST['redemption_loan_id'];
        $redemptionUserId = intval($_REQUEST['redemption_user_id']);
        $dtRecordId = intval($_REQUEST['dt_record_id']);
        $loanId = intval($_REQUEST['loan_id']);
        $p2pLoadId = intval($_REQUEST['p2p_load_id']);
        $number = $_REQUEST['number'];
        $contractId = intval($_REQUEST['contract_id']);
        $projectId = intval($_REQUEST['project_id']);
        $contractPre = new ContractPreService();

        switch ($ctype) {
            case 0:
                if(!empty($contractId)){
                    $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt',$contractId,$dtDealId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                }else{
                    $result = $contractPre->getDtbContractInvest($projectId,$userId, $money, $number,$createTime);
                }
                break;
            case 1:
                if(empty($contractId)){
                    return '借款合同为空';
                }
                $contractInfo = ContractInvokerService::getOneFetchedContract('viewer', $contractId, $p2pDealId);
                $result = $contractInfo['content'];
                break;
            case 2:
                // contractId为空，则获取落库合同内容
                if(!empty($contractId)){
                    $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt',$contractId,$dtDealId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                }else{
                    $result = $contractPre->getDtbLoanTransfer($projectId,$userId,$redemptionUserId,$p2pDealId,$money,$number,$createTime,$dtRecordId,$dtDealId);
                }
                break;
        }
        return $result;
    }

    //合同下载
    public function download(){
        $result = $this->getContractContent();
        $number = $_REQUEST['number'];
        $file_name = $number.".pdf";
        $file_path = APP_ROOT_PATH.'../runtime/'.$file_name;
        $mkpdf = new Mkpdf ();
        $mkpdf->mk($file_path, $result);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
    }

    //下载打戳合同
    public function downloadTsa(){
        $contractId = $_REQUEST['contract_id'];
        $p2pDealId = $_REQUEST['p2p_deal_id'];
        $loanId = $_REQUEST['loan_id'];
        $ctype = intval($_REQUEST['ctype']);
        switch ($ctype) {
            case 0:
                $dealId = $loanId;
                ContractInvokerService::downloadTsa('dt',$contractId,$dealId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                break;
            case 1:
                $dealId = $p2pDealId;
                ContractInvokerService::downloadTsa('filer',$contractId, $dealId);
                break;
            case 2:
                $dealId = $loanId;
                ContractInvokerService::downloadTsa('dt',$contractId,$dealId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                break;
        }
    }

}
