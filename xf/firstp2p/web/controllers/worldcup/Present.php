<?php
/**
 * Present.php
 *
 * @date 2014年6月24日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\worldcup;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\WorldCupModel;

class Present extends BaseAction {

    public function init() {
        $this->form = new Form("post");
        $this->form->rules = array(
            "int-ball" => array("filter" => "string", "message" => "球队不能为空"),
            "int-renyuan" => array("filter" => "string", "message" => "球员不能为空"),
            "realName" => array("filter" => "string", "message" => "真实姓名不能为空"),
            "realName" => array("filter" => "length", "message" => "真实姓名应为2-6个汉字", "option" => array("min" => 6, "max" => 18)),
            "int-phone"=> array("filter" => "length", "message"=>"手机号码应为7-11为数字", "option"=>array("min" => 7, "max" => 11)),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), '', 0, 0, "http://".APP_HOST."/worldcup/view");
        }
        if (get_gmtime() >= strtotime("2014-07-13")) {
            return $this->show_error("活动已过期", '', 0, 1);
        }
    }

    public function invoke() {
        $data = array();
        $data['name'] = $this->form->data['realName'];
        $data['mobile'] = $this->form->data['int-phone'];
        $data['team'] = $this->form->data['int-ball'];
        $data['player'] = $this->form->data['int-renyuan'];
        $result = WorldCupModel::instance()->insertData($data);
        if (!$result) {
            // 出错了是否存文件
        }
        //注册红包
        $hongbao_code = trim(app_conf('REG_HONGBAO_CODE'));
        $this->tpl->assign("host", APP_HOST);
        $this->tpl->assign("data", $this->form->data);
        $this->tpl->assign("hongbao_code", $hongbao_code);
        $this->template = "web/views/worldcup/present.html";
    }
}
