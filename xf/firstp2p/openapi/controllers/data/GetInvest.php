<?php
namespace openapi\controllers\data;

use openapi\controllers\DataBaseAction;
use core\service\DealLoadService;
use libs\web\Form;

/**
 * 已投资列表接口
 * Class GetInvest
 * @package openapi\controllers\data
 */
class GetInvest extends DataBaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            'update_time' => array('filter' => 'int', "option" => array('optional' => true)),
            'sortType' => array('filter' => 'int', "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $offset = empty($params['offset']) ? 0 : intval($params['offset']);
        $count = empty($params['count']) ? 1000 : intval($params['count']);
        $count = ($count > 5000) ? 5000 : $count;

        $updateTime = intval($params['update_time'] - 8 * 3600);
        $updateTime = $updateTime > 0 ? $updateTime : 0;

        $sortType = $params['sortType'];

        try {
            $DealLoadService = new DealLoadService(); 
            $res = $DealLoadService->getLoadBySiteId($this->siteId, $offset, $count, $updateTime, $sortType);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        if ($res) {
            foreach ($res as &$value) {
                $value['create_time'] = $value['create_time'] + 8*3600;
                $value['update_time'] = $value['update_time'] > 0 ? $value['update_time'] + 8*3600 : $value['create_time'];
            }
        }

        $this->json_data = $res;
    }

}
