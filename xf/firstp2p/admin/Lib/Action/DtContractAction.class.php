<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 15/10/26
 * Time: 下午4:42
 */
require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

use \NCFGroup\Protos\Contract\RequestCategoryList;
use \NCFGroup\Protos\Contract\RequestAddCategory;
use \NCFGroup\Protos\Contract\RequestUpdateCategoryById;
use \NCFGroup\Protos\Contract\RequestGetCategoryById;
use \NCFGroup\Protos\Contract\RequestGetTplByCid;
use \NCFGroup\Protos\Contract\RequestGetTplById;
use \NCFGroup\Protos\Contract\RequestAddTpl;
use \NCFGroup\Protos\Contract\RequestUpdateTplById;
use NCFGroup\Protos\Contract\RequestGetContractTplIdentifierList;

class DtContractAction extends CommonAction {

    /**
     * 合同模板分类管理
     */
    public function category() {
        $request = new RequestCategoryList();
        $p = $_REQUEST['p']?intval($_REQUEST['p']):1;
        $request->setType(1);
        $request->setPageNum($p);
        $request->setIsDelete(0);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getCategoryList",
            'args' => $request,
        ));

        if($response->list){
            $list = $response->list['data'];

            foreach($list as &$v){
                if($v['createTime'] > 0){
                    $v['createTime'] = $v['createTime'] - 28800;
                }
            }

            $totalPage = $response->list['totalPage'];
            $totalNum = $response->list['totalNum'];

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
            $request = new RequestAddCategory();
            $request->setTypeName(trim($_REQUEST['typeName']));
            $request->setTypeTag(trim($_REQUEST['typeTag']));
            $request->setContractType(intval($_REQUEST['contractType']));
            $request->setIsDelete(0);
            $request->setUseStatus(intval($_REQUEST['useStatus']));
            $request->setType(intval($_REQUEST['type']));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "addCategory",
                'args' => $request,
            ));

            if($response->status == 1){
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
            $request = new RequestUpdateCategoryById();
            $request->setCategoryId(intval($_REQUEST['id']));
            $request->setTypeName(trim($_REQUEST['typeName']));
            $request->setTypeTag(trim($_REQUEST['typeTag']));
            $request->setContractType(intval($_REQUEST['contractType']));
            $request->setContractVersion(number_format($_REQUEST['contractVersion'], 2));
            $request->setUseStatus(intval($_REQUEST['useStatus']));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "updateCategoryById",
                'args' => $request,
            ));
            if($response->status == true){
                $this->success("修改成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            $request = new RequestGetCategoryById();
            $request->setCategoryId(intval($_REQUEST['id']));
            $response = $this->getRpc('contractRpc')->callByObject(array(
                'service' => "\NCFGroup\Contract\Services\Category",
                'method' => "getCategoryById",
                'args' => $request,
            ));
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

        $this->assign("tpl",$tpl);
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

        // 添加模板

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

        $new_version = $version + 0.01;
        if($response->status == true){
            $this->assign('jumpUrl','/m.php?m=DtContract&a=showTemplates&typeId='.$typeId.'&contractVersion='.$new_version);
            $this->success('更新成功');
        }else{
            //错误提示
            $this->error($response->errorMsg);
        }

    }
}
