<?php
/**
 * User: duxuefeng
 * Date: 2018/5/31
 * Time: 18:15
 */

namespace openapi\controllers\contract;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Contract\RequestGetTplByContractId;
/**
 * 获取前置合同列表
 *
 */
class GetTmpContracts extends BaseAction
{

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "contractId" => array("filter" => "required", "message" => "contractId is required"),
            "callBackUrl" => array("filter" => "required", "message" => "callBackUrl is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        $this->timeout = 30*60; //30分钟 该链接超时时间30分钟
    }

    public function invoke()
    {
        $params = $this->form->data;
        // 1 contractId 是否存在
        if(!is_numeric($params['contractId']) || ($params['contractId'] <= 0)){
            $this->setErr("ERR_PARAMS_ERROR", "contractId参数错误");
            return false;
        }
        // 2 获取合同模板
        $rpc = new \libs\utils\Rpc('contractRpc');
        if(!$rpc){
            return $this->setErr('ERR_SYSTEM');
        }
        $request = new RequestGetTplByContractId();
        $request->setContractId(intval($params['contractId']));
        $response = $rpc->go('NCFGroup\Contract\Services\ContractBeforeBorrow','getTplByContractId',$request);
        if($response['errCode'] != 0){
            $this->setErr("ERR_CONTRACT_EMPTY", $response['errMsg']);
            return false;
        }
        // 3 生成链接
        $urlList = array('contracts'=>array(),'signUrl'=>'');
        // 3.1 生成合同预览链接
        foreach($response['data']['tpls']['data'] as $key => $tpl){
            $urlList['contracts'][$key]['title'] = $tpl['contractTitle'];
            $req = array('contractId'=>$params['contractId'], 'tplId'=> $tpl['id']);
            $contractPreParams = $this->getOpenapiUrl($req);
            $urlList['contracts'][$key]['url']  = $this->getHost() . "/contract/TmpContractPre?" .  $contractPreParams;
        }
        // 3.2 生成签署链接
        $req = array('contractId'=>$params['contractId']);
        $urlList['signUrl'] = $this->getHost() . "/contract/TmpContractSign?" .  $this->getOpenapiUrl($req);
        $isSign = 0;
        if($reponse['data']['contract']['borrowerSignTime'] > 0){
            $isSign = 1;
        }

        $this->tpl->assign('list',$urlList);
        // 为了防止资产端传来的url有特殊字符，所以将callBackUrl encode，前端会将其decode
        $this->tpl->assign('is_sign',$isSign);
        $this->tpl->assign('call_back_url',urlencode($params['callBackUrl']));

        $this->template = "openapi/views/contract/get_tmp_contracts.html";
        return true;
    }

}
