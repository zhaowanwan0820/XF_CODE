<?php
use core\service\contract\CategoryService;
use core\service\contract\TplService;
use core\service\user\UserService;
use core\service\contract\ContractTplIdentifierService;
use core\service\contract\ContractInvokerService;
use core\enum\DealEnum;
use core\enum\contract\ContractServiceEnum;
use core\dao\deal\DealModel;
use libs\tcpdf\Mkpdf;

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
        array('id'=>DealEnum::DEAL_TYPE_GENERAL,'name' => '网贷'),
        array('id'=>ContractServiceEnum::SOURCE_TYPE_RESERVATION,'name' => '随心约普惠'),
    );

    /**
     * 合同模板分类管理
     */
    public function getCategory() {
        $p = $_REQUEST['p']?intval($_REQUEST['p']):1;
        if($p < 1){
            $p = $_GET['p'] = 1;
        }
        if(isset($_REQUEST['contract_type']) && ($_REQUEST['contract_type'] <> '')){
            $contract_type=intval($_REQUEST['contract_type']);
        }
        if(isset($_REQUEST['use_status']) && ($_REQUEST['use_status']<>'')){
            $use_status = intval($_REQUEST['use_status']);
        }
        if(isset($_REQUEST['type_name']) && ($_REQUEST['type_name'] <> '')){
            $type_name = trim($_REQUEST['type_name']);
        }
        $sourceType = ContractServiceEnum::SOURCE_TYPE_PH;
        if(isset($_REQUEST['source_type']) && ($_REQUEST['source_type'] <> '')){
            $sourceType = intval($_REQUEST['source_type']);
        }
        $response = CategoryService::getCategoryList(ContractServiceEnum::TYPE_P2P,$sourceType,$p,$type_name,$use_status,$contract_type,0,30);

        $list = $response['data'];
        $totalPage = $response['totalPage'];
        $totalNum = $response['totalNum'];
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

    }
    /**
     * 合同分类添加
     */
    public function contTypeAdd(){
        if($_REQUEST['save'] == true){
            $response =  CategoryService :: addCategory(trim($_REQUEST['typeName']),trim($_REQUEST['typeTag']),intval($_REQUEST['contractType']),
                 0,intval($_REQUEST['useStatus']),1,ContractServiceEnum::TYPE_P2P,intval($_REQUEST['dealType']));
            if($response['status'] == 1){
                $this->success("添加成功");
            }else if($response['status'] == 2){
                $this->error("添加失败,分类标识重复!");
            }else{
                $this->error("添加失败");
            }
        }else{
            $this->assign('dealType',$this->_dealType);
            $this->display ();
        }
    }
    /**
     * 合同分类修改
     */
    public function contTypeEdit(){
        if($_REQUEST['update'] == true){
            $response =  CategoryService :: updateCategoryById(intval($_REQUEST['id']),trim($_REQUEST['typeName']),
                trim($_REQUEST['typeTag']), intval($_REQUEST['contractType']), 0, intval($_REQUEST['useStatus']),
                number_format($_REQUEST['contractVersion'], 2),ContractServiceEnum::SOURCE_TYPE_PH);
            if($response) {
                $this->success("编辑成功");
            }else{
                $this->error("修改失败");
            }
        }else{
            $response= CategoryService ::getCategoryById(intval($_REQUEST['id']));
            $this->assign('type_info',$response);
            $this->assign('dealType',$this->_dealType);
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
            $responseCategory= CategoryService ::getCategoryById($typeId);
            if(!empty($responseCategory['typeName'])){
                $this->assign("typeName",$responseCategory['typeName']);
            }
            $response= TplService ::getTplsByCid($typeId,floatval(number_format($contractVersion,2)));
            if(count($response)>0){
                $this->assign("tpl_list",$response);
            }
            if(isset($_REQUEST['editId'])&&(intval($_REQUEST['editId'])>0)){
                $response= TplService ::getTplById(intval($_REQUEST['editId']));
                $tpl = $response;
            }
        }else{
            $this->error('分类ID不正确');
        }
        $param = $this->get_param_lang($tpl['name']);
        $this->assign("tpl",$tpl);
        $this->assign("param",$param);
        $this->assign("contract_version",floatval($contractVersion));
        $this->assign("type_id",$typeId);
        $response=ContractTplIdentifierService::getTplIdentifierList();
        // 获取模板标识列表
        $this->assign('tpl_identifier_list', $response);

        $this->display();
    }
    public function add(){
        //查询当前选中的模板
        $typeId = intval($_REQUEST['typeId']);
        $contractVersion = $_REQUEST['contractVersion']?$_REQUEST['contractVersion']:number_format(1,2);
        $this->assign("contractVersion",$contractVersion);
        $this->assign('tip_variable', $this->_tip_variable);
        $this->assign("typeId",$typeId);
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
        $response = TplService::addTpl($contractTitle,$name,$typeId,$content,$type,$isHtml,$version,$tplIdentifierId);
        if($response == true){
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
        $response = TplService :: updateTplById($id,$contractTitle,$name,$typeId,$content,$type,$isHtml,$version,$tplIdentifierId);
        $newVersion = $version+0.01;
        if($response == true){
            $contResponse = TplService::getTplsByCid($typeId);
            if(!empty($contResponse)){
                $tpls = $contResponse;
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
        $templateInfo= TplService ::getTplsByCid($id,floatval(number_format($version,2)));
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
    public function contTypeCopy(){
        $type_id = intval($_REQUEST['id']);
        if ($type_id <= 0) {
            $this->error('非法操作');
        }
        $typeInfo= CategoryService ::getCategoryById($type_id);;
        if ($_POST) {
            $responseCategory =  CategoryService :: addCategory(trim($_REQUEST['type_name']),trim($_REQUEST['type_tag']), intval($_REQUEST['contractType']),
                0,intval($_REQUEST['useStatus']),1,ContractServiceEnum::TYPE_P2P,ContractServiceEnum::SOURCE_TYPE_PH);
           $responseCategory['status']=1;
            if($responseCategory['status'] == 1){
                $tpls= TplService ::getTplsByCid($type_id,floatval(number_format($typeInfo['contractVersion'],2)));
                if(count($tpls)){
                    foreach($tpls as $tpl){
                        $responseAdd = TplService::addTpl($tpl['contractTitle'],str_replace($typeInfo['typeTag'],trim($_POST['type_tag']),$tpl['name']),
                        $responseCategory['id'],$tpl['content'],$tpl['type'],$tpl['content'],floatval(number_format(number_format(1.00,2))),intval($tpl['tplIdentifierId']));
                        if(!$responseAdd){
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
        $response = CategoryService :: delCategoryByIds($type_id);
        if(!$response){
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
            $responseCategory= CategoryService ::getCategoryById($typeId);
            if(!empty($responseCategory['typeName'])){
                $this->assign("typeName",$responseCategory['typeName']);
            }
            $response= TplService ::getTplsByCid($typeId,floatval(number_format($contractVersion,2)));
            if(count($response)>0 && !empty($dealId) &&!empty($userId) && !empty($money)  ){
                $this->assign("tpl_list",$response);
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
        $deal = $deal_model->getDealInfoViaSlave($dealId);
        if(empty($deal)){
            echo hide_message('标的信息不存在');
            exit ();
        }
        $userId = intval ( $_REQUEST ['user_id'] );
        $user = UserService::getUserById($userId,'id',true);
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
        $deal = $deal_model->getDealInfoViaSlave($dealId);
        if(empty($deal)){
            exit ();
        }
        $userId = intval ( $_REQUEST ['user_id'] );
        $user = UserService::getUserById($userId,' * ',true);
        if(empty($user)){
            exit ();
        }
        $money = $_REQUEST ['money'];
        $contract_invoker = new ContractInvokerService();
        $contract = $contract_invoker->getOneFetchedContractByTplId('viewer', $dealId,$id,$userId,$money);
        $file_name = "合同预览".$dealId.'_'.$userId.".pdf";
        $file_path = ROOT_PATH.'runtime/'.$file_name;
        if(!file_exists($file_path)){
            set_time_limit(300);
            $mkpdf = new Mkpdf ();
            $mkpdf->mk($file_path, $contract['content']);
        }
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        echo readfile($file_path);
        @unlink($file_path);
        exit;


    }
}
?>
