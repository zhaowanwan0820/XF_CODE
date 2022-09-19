<?php
/**
 * Contractpre controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-08-01
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\contract\ContractInvokerService;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;

/**
 * 合同协议
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class Contractpre extends DuotouBaseAction
{

    public function init()
    {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            'project_id' => array(
                    'filter' => 'int',
                    'message' => 'project_id is required',
            ),
            'money' => array(
                    'filter' => 'float',
                    'message' => 'money is required',
            ),
            'tpl_identifier_id' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ), // 1:顾问协议 2:债权转让协议
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke()
    {
        $userInfo = $this->user;
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
        $data = $this->form->data;
        $projectId = $data['project_id'];
        $money = $data['money'];
        // 智多新合同才需要$tpl_identifier_id这个参数
        $tpl_identifier_id = isset($this->form->data['tpl_identifier_id']) ? $this->form->data['tpl_identifier_id'] : 1;

        if (!$userId || $projectId <= 0) {
            return $this->assignError('ERR_PARAMS_ERROR');
        }
        $ContractData = '';
        //TODO $tpl_identifier_id == 2这种判断为临时措施，需要紧急上线。下次加借款合同时，使用标准的合同列表方式获取。
        if($tpl_identifier_id == 2){
            $ContractData = ContractInvokerService::getFetchedContract('pre',array(), $projectId, ContractServiceEnum::TYPE_DT, ContractTplIdentifierEnum::DTB_TRANSFER);
        }else{
            $ContractData = ContractInvokerService::getDtbContractPre('pre',$projectId, $userId, $money);
        }
        $this->json_data = array("content"=>$ContractData);
    }

}
