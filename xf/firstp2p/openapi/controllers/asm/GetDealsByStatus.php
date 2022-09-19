<?php
/**
 * 信贷系统使用接口
 * 根据状态获取标的的放款审批单号
 * 只获取对公信贷的
 * User: duxuefeng
 * Date: 2018/1/2
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use libs\utils\Block;
use core\dao\DealModel;
use openapi\controllers\BaseAction;
use openapi\conf\adddealconf\common\CommonConf;

class GetDealsByStatus extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "deal_status" => array("filter" => "required", "message" => "deal_status is required"),// 投资状态
            "page_num" => array("filter" => "required", "message" => "page_num is required"),// 分页查询参数的第page_num页。从第1页开始
            "start_time" => array("filter" => "string", "option" => array("optional" => true)),// 起始时间,用于查询已还清状态的标的的条件
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        // 调用频率限制
        $checkCounts = Block::check('CREDIT_GET_DEALS_BY_STATUS_DOWN_MINUTE','credit_get_deals_by_status_down_minute');//一分钟60次
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }

        $params = $this->form->data;
        // 1.deal_status需要是还款中或者已还清
        if (!(in_array($params['deal_status'], array(DealModel::$DEAL_STATUS['repaying'], DealModel::$DEAL_STATUS['repaid'])))){
            $this->setErr("ERR_PARAMS_ERROR", 'deal_status不是出于还款中或者已还清');
            return false;
        }

        // 2.start_time处理 2.1查询还款中的,start_time为0 2.2查询已还清的必须传“start_time”
        $time = to_timespan($params['start_time']);
        if($params['deal_status'] == DealModel::$DEAL_STATUS['repaying'] && !empty($time)){
            $this->setErr("ERR_PARAMS_ERROR", 'deal_status为还款中时,start_time必须为0');
            return false;
        }
        if($params['deal_status'] == DealModel::$DEAL_STATUS['repaid'] && empty($time)){
            $this->setErr("ERR_PARAMS_ERROR", 'deal_status为已还清时，必须传start_time参数或者start_time转换失败');
            return false;
        }

        // 3.查询还款中或者已还清的p2p标的
        $page_size = 1000; //每次查询1000条
        $approve_number = "C%";  //对公信贷推过来标的approve_number是以C开头的
        $result = $this->rpc->local('DealService\getDealListByStatusTypeTime', array(DealModel::DEAL_TYPE_GENERAL, $params['deal_status'], $time, 0, $approve_number, "`approve_number`, `repay_start_time`", $params['page_num'], $page_size));

        if(empty($result)){
            $this->setErr("ERR_SYSTEM");
            return false;
        }

        // 4.判断是否是最后一次
        $json_data = array('is_finish' => 0, 'count' => 0, 'list' => array());
        if($params['page_num'] >= $result['total_page'] || $result['total_size'] == 0){ //访问页数大于总页数，或者没有查询到结果
            $json_data['is_finish'] = 1;
        }
        // 5.deal数据需要转换时区
        foreach($result['res_list'] as $key => $one){
            $result['res_list'][$key]['repay_start_time'] = timestamp_to_conf_zone($one['repay_start_time']);
        }
        $json_data['count'] = count($result['res_list']);
        $json_data['list'] = $result['res_list'];
        $this->json_data = $json_data;
    }
}

