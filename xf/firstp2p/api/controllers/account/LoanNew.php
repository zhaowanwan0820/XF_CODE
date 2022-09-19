<?php
/**
 * 回款计划
 * @author pengchanglu@ucfgroup.com
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoanNew extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            "token" => array("filter"=>"required"),
            'type'=>array("filter"=>'int', 'option' => array('optional' => true)),//1 未还  2已还
            'count'=>array("filter"=>'int', 'option' => array('optional' => true)),
            'offset'=>array("filter"=>'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $info = $this->rpc->local('UserService\getUserByCode', array($data['token']));
        $type = intval($data['type']);//1 未还  2已还
        $count = intval($data['count'])?intval($data['count']):10;//获取条数
        $offset = intval($data['offset']);
        if ($info['code'] || !$info['user']['id']) {
            $this->setErr('ERR_GET_USER_FAIL');// 获取oauth用户信息失败
        }else{
            $uid = $info['user']['id'];
            if($type == 1){//未还
                $result = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,0,0,array($offset,$count),'newapi',NULL,0));
                $data = $this->data_format($result['list'],$type);
            }else{// 已还
                $result = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,0,0,array($offset,$count),'newapi',NULL,1));
                $data = $this->data_format($result['list'],$type);
            }
            $this->json_data = $data;
        }
    }

    /**
     * 格式化数据
     * @param unknown $data
     * @param unknown $type
     * @return NULL|Ambigous <multitype:, unknown>
     */
    protected function data_format($data,$type){
        if(!$data){return Null;}
            //print_r($data);
            $arr = array();
        foreach($data as $k=>$v){
            $arr[$k]['productID'] = $v['deal_id'];
            $arr[$k]['name'] = $v['deal_name'];
            $arr[$k]['time'] = $v['time'];
            $arr[$k]['real_time'] = $v['real_time'];
            $arr[$k]['type'] = $v['money_type'];
            $arr[$k]['status'] = $v['repay_status'];
            $arr[$k]['money'] = $v['money'];
            $arr[$k]['position'] = $type;
        }
        //        if($type == 1){
        ////             rsort($arr);
        //            $arr = $this->data_sort($arr);
        //        }
        return $arr;
    }
    /**
     * data 排序
     * @param unknown $data
     * @return multitype:unknown
     */
    protected function data_sort($data){
        $c = count($data);
        $arr = array();
        foreach($data as $k=>$v){
            $arr[] = $data[$c-$k-1];
        }
        return $arr;
    }
}
