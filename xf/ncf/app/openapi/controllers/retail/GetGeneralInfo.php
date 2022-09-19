<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/14
 * Time: 18:15
 */

namespace openapi\controllers\retail;

use libs\web\Form;
use core\service\deal\DealLoanTypeService;
use core\service\contract\ContractNewService;
use openapi\controllers\BaseAction;

/**
 * 获取合同或产品相关信息
 *
 * Class GetAgencyInfo
 * @package openapi\controllers\retail
 */
class GetGeneralInfo extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "pageNum" => array("filter" => "int"),
            "pageSize" => array("filter" => "int"),
            "name" => array("filter" => "string"),
            "type" => array("filter" => "int"), //type = 1;获取产品相关信息  type=2:获取合同相关信息
            "dealType" => array("filter" => "int"), //deal_type,合同分类类型 0-网贷，1-通知贷，2-交易所, 3-专享，5-小贷
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
        if (empty($params['type'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'type参数有误');
            return false;
        }
        if ($params['type'] == 1) {
            $dealLoanTypeService = new DealLoanTypeService();
            $ret = $dealLoanTypeService->getListByTypeName(htmlspecialchars($params['name']), $params['pageNum'], $params['pageSize']);
        } elseif ($params['type'] == 2) {
            //使用合同服务接口(以前是查firstp2p库中的firstp2p_contract_category_tmp)
            $contractNewService = new ContractNewService();
            $result = $contractNewService->getListByTypeName(htmlspecialchars($params['name']), $params['pageNum'], $params['pageSize'],$params['dealType']);
            $ret = array('total_page'=>$result['totalPage'], 'total_size'=> $result['totalNum'], 'res_list' =>array());
            if(empty($result['data'])){
                $this->json_data =  $ret;
                return false;
            }
            foreach($result['data'] as $category ){
               $ret['res_list'][] = array('id' => $category['id'], 'type_name'=> $category['typeName']);
            }
        }

        $this->json_data = $ret;
    }

}
