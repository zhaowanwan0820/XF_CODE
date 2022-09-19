<?php

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\darkmoon\DarkmoonDealModel;
use NCFGroup\Protos\Contract\RequestCategoryList;
use NCFGroup\Protos\Contract\RequestAddCategory;
use NCFGroup\Protos\Contract\RequestUpdateCategoryById;
use NCFGroup\Protos\Contract\RequestGetCategoryById;
use NCFGroup\Protos\Contract\RequestGetTplByCid;
use NCFGroup\Protos\Contract\RequestGetTplByName;
use NCFGroup\Protos\Contract\RequestGetTplById;
use NCFGroup\Protos\Contract\RequestAddTpl;
use NCFGroup\Protos\Contract\RequestUpdateTplById;
use NCFGroup\Protos\Contract\RequestDelCategoryByIds;
use NCFGroup\Protos\Contract\RequestGetContractTplIdentifierList;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use core\service\ContractInvokerService;

class ContractServiceAction extends CommonAction {

    private $_tip_variable = '
        {$notice.borrow_real_name}    借款人真实姓名<br>
        {$notice.borrow_user_name}    借款人用户名<br>
        {$notice.borrow_user_idno}    借款人身份证<br>
        {$notice.borrow_address}    借款人住址<br>
        {$notice.borrow_mobile}        借款人手机号<br>
        {$notice.borrow_postcode}    借款人邮箱（历史错误）<br>
        {$notice.borrow_email}        借款人邮箱<br><br>

        {$notice.company_name}        借款公司名称<br>
        {$notice.company_address}    公司地址<br>
        {$notice.company_legal_person}    公司法定代表人<br>
        {$notice.company_tel}        公司联系电话<br>
        {$notice.company_license}    公司营业执照号<br>
        {$notice.company_description}    公司简介';

    private $_dealType = array(
        array('id'=>DealModel::DEAL_TYPE_GENERAL,'name' => '网贷'),
        array('id'=>DealModel::DEAL_TYPE_EXCHANGE,'name' => '交易所'),
        array('id'=>DealModel::DEAL_TYPE_EXCLUSIVE,'name' => '专享'),
        array('id'=>DealModel::DEAL_TYPE_PETTYLOAN,'name' => '小贷'),
        array('id'=>DarkmoonDealModel::DEAL_TYPE_OFFLINE_EXCHANGE,'name' => '线下交易所'),
        array('id'=>ContractServiceEnum::SOURCE_TYPE_RESERVATION,'name' => '随心约普惠'),
        array('id'=>ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER,'name' => '随心约尊享'),
    );


    /**
     * 合同模板分类管理
     */
    public function getCategory() {

        $request = new RequestCategoryList();
        $p = $_REQUEST['p']?intval($_REQUEST['p']):1;
        if($p < 1){
            $p = $_GET['p'] = 1;
        }

        if(isset($_REQUEST['contract_type']) && ($_REQUEST['contract_type'] <> '')){
            $request->setContractType(intval($_REQUEST['contract_type']));
        }

        if(isset($_REQUEST['use_status']) && ($_REQUEST['use_status']<>'')){
            $request->setUseStatus(intval($_REQUEST['use_status']));
        }

        if(isset($_REQUEST['type_name']) && ($_REQUEST['type_name'] <> '')){
            $request->setTypeName(trim($_REQUEST['type_name']));
        }
        if(isset($_REQUEST['source_type']) && ($_REQUEST['source_type'] <> '')){
            $request->setSourceType(trim($_REQUEST['source_type']));
        }


        if($this->is_cn){
            $request->setSourceType(0);
        }

        $request->setPageNum($p);
        $request->setIsDelete(0);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategoryList",
            'args' => $request,
        ));

        if($response->list){
            $list = $response->list['data'];
            $totalPage = $response->list['totalPage'];
            $totalNum = $response->list['totalNum'];
            foreach($list as &$v){
                $v['createTime'] = intval($v['createTime']) - 8*3600;
            }

            $page = new \Page($totalNum, 30);
            $page_str = $page->show();

            $this->assign('isCn',$this->is_cn);
            $this->assign('dealType',$this->_dealType);
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

            $request = new RequestAddCategory();
            $request->setTypeName(trim($_REQUEST['typeName']));
            $request->setTypeTag(trim($_REQUEST['typeTag']));
            $request->setContractType(intval($_REQUEST['contractType']));
            $request->setIsDelete(0);
            $request->setUseStatus(intval($_REQUEST['useStatus']));
            $request->setType(intval($_REQUEST['type']));
            if($this->is_cn){
                $request->setSourceType(0);
            }else{
                $request->setSourceType(intval($_REQUEST['dealType']));
            }

            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "addCategory",
                'args' => $request,
            ));

            if($response->status == 1){
                $this->success("添加成功");
            }else if($response->status == 2){
                $this->error("添加失败,分类标识重复!");
            }else{
                $this->error("添加失败");
            }
        }else{
            $dealType = array();
            $dealType[] = array('id'=>DealModel::DEAL_TYPE_GENERAL,'name' => '网贷');
            $dealType[] = array('id'=>DealModel::DEAL_TYPE_EXCHANGE,'name' => '交易所');
            $dealType[] = array('id'=>DealModel::DEAL_TYPE_EXCLUSIVE,'name' => '专享');
            $dealType[] = array('id'=>DealModel::DEAL_TYPE_PETTYLOAN,'name' => '小贷');
            $dealType[] = array('id'=>DealModel::DEAL_TYPE_GOLD,'name' => '黄金');

            $this->assign('isCn',$this->is_cn);
            $this->assign('dealType',$this->_dealType);
            $this->display ();
        }
    }


    /**
     * 合同分类修改
     */
    public function contTypeEdit(){

        if($_REQUEST['update'] == true){
            $request = new RequestUpdateCategoryById();
            $request->setCategoryId(intval($_REQUEST['id']));
            $request->setTypeName(trim($_REQUEST['typeName']));
            $request->setTypeTag(trim($_REQUEST['typeTag']));
            $request->setContractType(intval($_REQUEST['contractType']));
            $request->setContractVersion(number_format($_REQUEST['contractVersion'], 2));
            if($this->is_cn){
                $request->setSourceType(0);
            }else{
                $request->setSourceType(intval($_REQUEST['dealType']));
            }
            $request->setUseStatus(intval($_REQUEST['useStatus']));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "updateCategoryById",
                'args' => $request,
            ));
            if($response->status == 1){
                $this->success("修改成功");
            }elseif($response->status == 2){
                $this->error("分类重复");
            }else{
                $this->error("修改失败");
            }
        }else{
            $request = new RequestGetCategoryById();
            $request->setCategoryId(intval($_REQUEST['id']));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "getCategoryById",
                'args' => $request,
            ));

            $this->assign('isCn',$this->is_cn);
            $this->assign('dealType',$this->_dealType);
            $this->assign('type_info',$response->list);
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
            $requestCategory = new RequestGetCategoryById();
            $requestCategory->setCategoryId($typeId);
            $responseCategory = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "getCategoryById",
                'args' => $requestCategory,
            ));
            if(!empty($responseCategory->list['typeName'])){
                $this->assign("typeName",$responseCategory->list['typeName']);
            }

            $request = new RequestGetTplByCid();
            $request->setCategoryId($typeId);
            $request->setContractVersion(floatval(number_format($contractVersion,2)));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByCid",
                'args' => $request,
            ));
            if(count($response->list['data'])>0){
                $this->assign("tpl_list",$response->list['data']);
            }
            if(isset($_REQUEST['editId'])&&(intval($_REQUEST['editId'])>0)){
                $request = new RequestGetTplById();
                $request->setId(intval($_REQUEST['editId']));
                $response = $this->getRpc('contractRpc')->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplById",
                    'args' => $request,
                ));
                $tpl = $response->list['data'];
            }
        }else{
            $this->error('分类ID不正确');
        }
        $param = $this->get_param_lang($tpl['name']);

        $this->assign("tpl",$tpl);
        $this->assign("param",$param);
        $this->assign("contract_version",floatval($contractVersion));
        $this->assign("type_id",$typeId);

        // 获取模板标识列表
        $request = new RequestGetContractTplIdentifierList();
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
            'method' => "getTplIdentifierList",
            'args' => $request,
        ));
        $this->assign('tpl_identifier_list', $response->getList());

        $this->display();
    }

    public function add(){
        //查询当前选中的模板
        $typeId = intval($_REQUEST['typeId']);
        $contractVersion = $_REQUEST['contractVersion']?$_REQUEST['contractVersion']:number_format(1,2);
        $this->assign("contractVersion",$contractVersion);
        $this->assign('tip_variable', $this->_tip_variable);
        $this->assign("typeId",$typeId);

        // 获取模板标识列表
        $request = new RequestGetContractTplIdentifierList();
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\ContractTplIdentifier",
            'method' => "getTplIdentifierList",
            'args' => $request,
        ));
        $this->assign('tpl_identifier_list', $response->getList());

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

        $request = new RequestAddTpl();

        $request->setContractTitle($contractTitle);
        $request->setName($name);
        $request->setType($type);
        $request->setContractCid($typeId);
        $request->setIsHtml($isHtml);
        $request->setContent($content);
        $request->setVersion($version);
        $request->setTplIdentifierId($tplIdentifierId);

        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "addTpl",
            'args' => $request,
        ));

        if($response->status == true){
            $this->assign('jumpUrl','/m.php?m=ContractService&a=showTemplates&typeId='.$typeId.'&contractVersion='.$version);
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

        $request = new RequestUpdateTplById();

        $request->setId($id);
        $request->setContractTitle($contractTitle);
        $request->setName($name);
        $request->setType($type);
        $request->setContractCid($typeId);
        $request->setIsHtml($isHtml);
        $request->setContent($content);
        $request->setVersion($version);
        $request->setTplIdentifierId($tplIdentifierId);

        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "updateTplById",
            'args' => $request,
        ));

        $newVersion = $version+0.01;

        if($response->status == true){
            $contRequest = new RequestGetTplByCid();
            $contRequest->setCategoryId($typeId);
            $contRequest->setContractVersion(floatval(number_format($newVersion,2)));
            $contResponse = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByCid",
                'args' => $contRequest,
            ));

            if(isset($contResponse->list['data'])){
                $tpls = $contResponse->list['data'];
                foreach($tpls as $tpl){
                    if($tpl['contractTitle'] === $contractTitle){
                        $contId = $tpl['id'];
                    }
                }
                $this->assign('jumpUrl','/m.php?m=ContractService&a=showTemplates&typeId='.$typeId.'&contractVersion='.$newVersion.'&editId='.$contId);
                $this->success('更新成功');
            }else{
                $this->assign('jumpUrl','/m.php?m=ContractService&a=showTemplates&typeId='.$typeId.'&contractVersion='.$newVersion);
                $this->success('更新成功');
            }

        }else{
            //错误提示
            $this->error($response->errorMsg);
        }
    }

    /**
     * 输出模板中的变量
     */
    private function get_param_lang($tpl_name){
        $contract_param_lang = $GLOBALS['contract'];
        $param_lang = array();

        if($contract_param_lang){

            foreach($contract_param_lang as $tpl => $param){
                if(strpos($tpl_name, $tpl) !== false){
                    $param_lang = $param;
                    break;
                }
            }
        }

        return $param_lang;
    }


    /**
     * 导出合同模板
     */
    public function export()
    {
        $id = intval($_REQUEST['id']);
        $version = $_REQUEST['version'];

        //取出模板列表和分类信息

        $request = new RequestGetTplByCid();
        $request->setCategoryId($id);
        $request->setContractVersion(floatval(number_format($version,2)));
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Tpl",
            'method' => "getTplsByCid",
            'args' => $request,
        ));

        $templateInfo = $response->list['data'];

        $zipFileName = tempnam('', 'zip_');
        $downloadFileName = iconv('utf-8', 'gbk', $id).date('.Ymd').'.zip';

        //打包成zip文件
        $zip = new ZipArchive();
        $zip->open($zipFileName, ZipArchive::OVERWRITE);

        foreach ($templateInfo as $item)
        {
            $filename = iconv('utf-8', 'gbk', $item['contractTitle']).'.doc';

            $content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
            $content .= $item['content'];
            $content .= '</html>';

            $zip->addFromString($filename, $content);
        }
        $zip->close();

        //下载
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=\"$downloadFileName\"");

        echo file_get_contents($zipFileName);

        unlink($zipFileName);
    }

    public function contTypeCopy()
    {
        $type_id = intval($_REQUEST['id']);
        if ($type_id <= 0) {
            $this->error('非法操作');
        }

        $request = new RequestGetCategoryById();
        $request->setCategoryId(intval($type_id));
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategoryById",
            'args' => $request,
        ));

        $typeInfo = $response->list;
        if ($_POST) {
            $request = new RequestAddCategory();
            $request->setTypeName(htmlspecialchars(trim($_POST['type_name'])));
            $request->setTypeTag(htmlspecialchars(trim($_POST['type_tag'])));
            $request->setContractType(intval($typeInfo['contractType']));
            $request->setIsDelete(0);
            $request->setUseStatus($typeInfo['useStatus']);
            $request->setType(0);
            $responseCategory = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "addCategory",
                'args' => $request,
            ));

            if($responseCategory->status == 1){
                $request = new RequestGetTplByCid();
                $request->setCategoryId($type_id);
                $request->setContractVersion(floatval(number_format($typeInfo['contractVersion'],2)));
                $responseTpl = $this->getRpc('contractRpc')->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Tpl",
                    'method' => "getTplsByCid",
                    'args' => $request,
                ));

                if(count($responseTpl->list['data'])>0){
                    $tpls = $responseTpl->list['data'];
                    $request = new RequestAddTpl();
                    foreach($tpls as $tpl){
                        $request->setContractTitle($tpl['contractTitle']);
                        $request->setName(str_replace($typeInfo['typeTag'],trim($_POST['type_tag']),$tpl['name']));
                        $request->setType($tpl['type']);
                        $request->setContractCid(intval($responseCategory->id));
                        $request->setIsHtml($tpl['isHtml']);
                        $request->setContent($tpl['content']);
                        $request->setVersion(floatval(number_format(number_format(1.00,2))));
                        $request->setTplIdentifierId(intval($tpl['tplIdentifierId']));
                        $responseAdd = $this->getRpc('contractRpc')->callByObject(array(
                            'service' => "\NCFGroup\Contract\Services\Tpl",
                            'method' => "addTpl",
                            'args' => $request,
                        ));
                        if(!$responseAdd->status){
                            //错误提示
                            $this->error('添加失败');
                        }
                    }
                }

                $this->success("复制成功");
            }else{
                $this->error($responseCategory->errorMsg);
            }
        } else {
            $this->assign('type_info', $typeInfo);
            $this->display();
        }
    }

    public function delCategory(){
        $type_id = intval($_REQUEST['id']);
        if ($type_id <= 0) {
            $this->error('非法操作');
        }

        $request = new RequestDelCategoryByIds();
        $request->setIds(array(intval($type_id)));
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "delCategoryByIds",
            'args' => $request,
        ));

        if(!$response->status){
            $this->error('删除失败!');
        }else{
            $this->success("删除成功");
        }
    }

    public function preview(){
        $typeId = intval ( $_REQUEST['typeId']);
        $contractVersion = $_REQUEST['contractVersion']?$_REQUEST['contractVersion']:number_format(1,2);
        $dealId = intval ( $_REQUEST ['deal_id'] );
        $userId = intval ( $_REQUEST ['user_id'] );
        $money = $_REQUEST ['money'];
        //获取分类下全部模板
        if(intval($typeId)>0){
            $requestCategory = new RequestGetCategoryById();
            $requestCategory->setCategoryId($typeId);
            $responseCategory = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "getCategoryById",
                'args' => $requestCategory,
            ));
            if (!empty($responseCategory->list['typeName'])) {
                $this->assign("typeName", $responseCategory->list['typeName']);
            }
            $request = new RequestGetTplByCid();
            $request->setCategoryId($typeId);
            $request->setContractVersion(floatval(number_format($contractVersion, 2)));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Tpl",
                'method' => "getTplsByCid",//通过id 获取下面的合同模板和合同模板标示
                'args' => $request,
            ));
            if(count($response->list['data'])>0 && !empty($dealId) &&!empty($userId) && !empty($money)  ){
                $this->assign("tpl_list",$response->list['data']);
            }
        }
        $this->display();
    }

    /**
     * 查看虚拟合同内容
     * @return string
     */
    public function opencontract() {
        $id = intval ( $_REQUEST ['id'] );
        if(!$id){
            exit ();
        }
        $dealId = intval ( $_REQUEST ['deal_id'] );
        $deal_model = new DealModel();
        $deal = $deal_model->findViaSlave($dealId);
        if(empty($deal)){
            echo hide_message('标的信息不存在');
            exit ();
        }
        $userId = intval ( $_REQUEST ['user_id'] );
        $user_model = new UserModel();
        $user = $user_model->find($userId,' * ',true);
        if(empty($user)){
            echo hide_message('虚拟用户不存在');
            exit ();
        }
        $money = $_REQUEST ['money'];
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->getOneFetchedContractByTplId('viewer', $dealId,$id,$userId,$money);
        echo hide_message($contract['content']);
    }

    /**
     * 下载pdf文件
     */
    public function download(){
        $id = intval ( $_REQUEST ['id'] );
        if(!$id){
            exit ();
        }
        $dealId = intval ( $_REQUEST ['deal_id'] );
        $deal_model = new DealModel();
        $deal = $deal_model->findViaSlave($dealId);
        if(empty($deal)){
            exit ();
        }
        $userId = intval ( $_REQUEST ['user_id'] );
        $user_model = new UserModel();
        $user = $user_model->find($userId,' * ',true);
        if(empty($user)){
            exit ();
        }
        $money = $_REQUEST ['money'];
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->getOneFetchedContractByTplId('viewer', $dealId,$id,$userId,$money);
        $file_name = "合同预览".$dealId.'_'.$userId.".pdf";
        $file_path = APP_ROOT_PATH.'runtime/'.$file_name;
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $mkpdf = new \Mkpdf ();
        $mkpdf->mk($file_path, $contract['content']);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
            exit;


    }
}
?>
