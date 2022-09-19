<?php
/**
 * api广告接口
 * 长路
 */
namespace api\controllers\common;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\AdvService;

class Adv extends AppBaseAction {
    protected $needAuth = false;

    public function invoke() {
        $this->form = new Form();
        $this->form->rules = array(
            "advid" => array("filter"=>"required", "message" => '广告位id不能为空'),
            'type' => array('filter'=>'int', 'message' => 'type格式错误'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_ADVID_EMPTY', $this->form->getErrorMsg());
            return false;
        }

        $data = $this->form->data;
        $advId = $data['advid'];

        $advService = new AdvService();
        $rs = $advService->getAdv($advId);

        if($rs && trim($advId) == 'index_bottom_manage_info' && !$this->isWapCall()){
            $rs = $advService->handleRegexData($rs);
        }

        // 新增type字段区分普通广告位、特殊广告位
        if (isset($data['type']) && $data['type'] == 1) {
            $this->json_data = empty($rs) ? null : $rs;
            return true;
        }

        $rs = trim($rs,"| ");
        if(!$rs){
            $this->json_data = null;
            return false;
        }

        $rs = explode("|",$rs);
        if(!is_array($rs)){
            $this->setErr('ERR_ADVID_EMPTY', $this->form->getErrorMsg());
            return false;
        }

        foreach($rs as $k=>$v){
            $arr = array();
            $v = explode(",",$v);
            if(isset($v[0])){
                // service已经去掉协议头
                if (stripos($v[0],'http:') === false) {
                    $arr['imageUrl'] = 'http:'.trim($v[0]);
                }else{
                    $arr['imageUrl'] = trim($v[0]);
                }
            }else{
                $arr['imageUrl'] = null;
            }
            if(isset($v[1])){
                $arr['adUrl'] = trim($v[1]);
            }else{
                $arr['adUrl'] = null;
            }
            if(isset($v[2]) && strtolower(trim($v[2])) == 'needlogin'){
                $arr['needLogin'] = 1;
            } else {
                $arr['needLogin'] = 0;
            }
            if(isset($v[3])){
                $arr['title'] = trim($v[3]);
            } else {
                $arr['title'] = null;
            }
            if(isset($v[4]) && trim($v[4]) != $_SERVER['HTTP_OS']){
                continue;
            }
            $list[] = $arr;
        }

        $this->json_data = $list;
    }
}
