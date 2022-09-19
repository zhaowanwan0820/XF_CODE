<?php
/**
 * 回款计划
 * @author pengchanglu@ucfgroup.com
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Loan extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
                "token" => array("filter"=>"required"),
                'type'=>array("filter"=>'int', 'option' => array('optional' => true)),
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
     $type = intval($data['type']);//1 未来  2过去
     $count = intval($data['count'])?intval($data['count']):10;//获取条数
     $offset = intval($data['offset']);//时间锚点
     $time = get_gmtime();
     if ($info['code'] || !$info['user']['id']) {
      $this->setErr('ERR_GET_USER_FAIL');// 获取oauth用户信息失败
     }else{
      $uid = $info['user']['id'];
      if($type == 1){//未来数据
       //$where .= " AND time > {$time} ORDER BY time ASC ";
       $result = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,$time,0,array($offset,$count),'api'));
       $data = $this->data_format($result['list'],$type);
      }elseif($type == 2){//过去数据
       //$where .= " AND time <= {$time} ORDER BY time DESC";
       $result = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,0,$time,array($offset,$count),'api'));;
       $data = $this->data_format($result['list'],$type);
      }else{//默认数据  各取十条来出来
       //$where_1 = $where." AND time > {$time} ORDER BY time ASC";
       $result_1 = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,$time,0,array(0,$count),'api'));
                //$where_2 = $where." AND time <= {$time} ORDER BY time DESC ";
                $result_2 = $this->rpc->local('DealLoanRepayService\getRepayList',array($uid,0,$time,array(0,$count),'api'));;

                $count_1 = count($result_1['list']);
                 $count_2 = count($result_2['list']);

                if($count_1<($count/2)){
                    $data_1 = $this->data_format($result_1['list'],1);
                    $data_2 = $this->data_format(array_slice($result_2['list'], 0,$count-$count_1),2);
                }else{
                    if($count_2<($count/2)){
                        $data_1 = $this->data_format(array_slice($result_1['list'], 0,($count-$count_2)),1);
                        $data_2 = $this->data_format($result_2['list'],2);
                    }else{
                        $data_1 = $this->data_format(array_slice($result_1['list'], 0,$count/2),1);
                        $data_2 = $this->data_format(array_slice($result_2['list'], 0,$count/2),2);
                    }
                }
                $data_1 = $data_1==null ?array():$data_1;
                $data_2 = $data_2==null ?array():$data_2;
                $data = array_merge($data_1,$data_2);
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
        if($type == 1){
//             rsort($arr);
            $arr = $this->data_sort($arr);
        }
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
