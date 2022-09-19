<?php
/**
 * 获取银行网点
 * @author <pengchanglu@ucfgroup.com>
 **/

namespace web\controllers\api;

use libs\web\Form;
use web\controllers\BaseAction;

class Banklist extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'c'=>array("filter"=>'string'),
            'b'=>array("filter"=>'string'),//bank
            'p'=>array("filter"=>'string'),//省份
            'n'=>array("filter"=>'string'),//网点名字
            'jsonpCallback'=>array("filter"=>'string'),//网点名字
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $result = $this->rpc->local('BanklistService\getBanklist',array($data['c'],$data['p'],$data['b']));
        $name = trim($data['n']);
        $str = $this->to_html($result,$name,$data['jsonpCallback']);
        if($data['jsonpCallback']){
            echo $data['jsonpCallback'].'("'.$str.'")';
        }else{
            echo $str;
        }
    }

    /**
     * 生成对应的 html 格式文件
     * @param $list
     * @param $name
     */
    protected function to_html($list,$name,$is_json=false) {
        if(!$list){
            if($is_json){
                return "<input name='bank_bankzone' value='".$name."' />";
            }
            return "<input class='idbox w315' name='bankzone' value='".$name."' />";
        }
        if($is_json){
            $str = "<select id='_js_bankone' class='' name='bank_bankzone' >";
        }else{
            $str = "<select id='_js_bankone' class='select_box w323' name='bankzone' >";
        }
        foreach($list as $k => $v) {
            $selected = !empty($name) && $name == $v['name'] ? 'selected' : '';
            $str .= "<option value='{$v['name']}' {$selected}>{$v['name']}</option>";
        }
        return $str.'</select>';
    }
}
