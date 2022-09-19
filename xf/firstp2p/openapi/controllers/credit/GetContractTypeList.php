<?php
/**
 * Created by PhpStorm.
 * User: gengkuan
 * Date: 2018/11/2
 * Time: 10:30
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 合同类型列表
 *
 * Class GetContractTypeList
 * @package openapi\controllers\credit
 */
class GetContractTypeList extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "pageNum" => array("filter" => "int"),
            "pageSize" => array("filter" => "int"),
            "name" => array("filter" => "string"),
            "dealType" => array("filter" => "required", "message" => "dealType is required"),//deal_type,合同分类类型 0-网贷，1-通知贷，2-交易所, 3-专享，5-小贷
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        if (empty($params['dealType'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'type参数有误');
            return false;
        }
        //使用合同服务接口(以前是查firstp2p库中的firstp2p_contract_category_tmp)
        $result = $this->rpc->local('ContractNewService\getListByTypeName',array(htmlspecialchars($params['name']), $params['pageNum'], $params['pageSize'],$params['dealType']));
        $ret = array('total_page'=>$result['totalPage'], 'total_size'=> $result['totalNum'], 'res_list' =>array());
        if(empty($result['data'])){
            $this->json_data =  $ret;
            return false;
        }
        foreach($result['data'] as $category ){
            $ret['res_list'][] = array('id' => $category['id'], 'type_name'=> $category['typeName']);
        }
        $this->json_data = $ret;
    }

}
