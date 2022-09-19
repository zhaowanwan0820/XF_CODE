<?php
/**
 * 获取项目信息
 * @date 2019/2/25
 * @author: yangshuo5@ucfgroup.com
 */
namespace openapi\controllers\gongdan;

use libs\web\Form;
use libs\utils\Logger;

use openapi\controllers\BaseAction;

class ProjectList extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "startTime" => array("filter" => "string", 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $params = $this->form->data;
        $startTime = isset($params['startTime']) ? $params['startTime'] : '';
        try {
            $result = \SiteApp::init()
                ->dataCache
                ->call($this->rpc, 'local', ['DealProjectService\getProjectInfo', [$startTime]], 15*60, false, true);
        } catch (\Exception $e) {
            Logger::error('getProjectListError:'.$e->getMessage());
            $result = [];
        }
        $this->json_data = $result;
    }
}