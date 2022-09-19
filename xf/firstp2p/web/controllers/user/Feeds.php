<?php
/**
 * 获取用户的反馈,call center使用
 * @author 刘振鹏<liuzhenpeng@ucfgroup.com>
 * 
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use core\dao\UserFeedbackModel;
use libs\web\Form;

class Feeds extends BaseAction {
    
    public function init() {
        \FP::import("libs.utils.logger");
        $current_ip = $this->getip();
        $current_ip['call_center'] = 'ip';
        \logger::info($current_ip);

        $ip_total   = count($current_ip);
        $white_list = array('10.12.160.97', '10.12.160.102', '10.12.160.103', '223.203.210.18', '218.240.22.29', '218.240.22.30', '218.240.22.31', '123.58.252.125', '123.58.252.126', '123.58.252.127', '10.100.80.15', '10.100.80.16', '10.100.80.17', '10.100.80.18', '10.100.80.19', '10.100.80.20');
        if(count($white_list)){
            $i = 0;
            foreach($current_ip as $vals){
                if(!in_array(trim($vals), $white_list)){
                    $i++;
                }
            }
            if($i>=$ip_total){
                $json_data['errorCode'] = 1;
                $json_data['errorMsg']  = '没有授权访问';
                $json_data['data']      = array();
                
                echo json_encode($json_data);

                return false;
            }
        }
	}
    
	public function invoke(){
        $form = new Form("post");
        $form->rules = array(
            'offset_id' => array('filter' => 'int','message' => '参数错误'),
            'limit' => array('filter' => 'int','message' => '参数错误'),
        );
        $form->validate();
        if(empty($form->data['offset_id']) || empty($form->data['limit'])){

            $json_data['errorCode'] = 2;
            $json_data['errorMsg']  = '参数错误';
            $json_data['data']      = array();

            echo json_encode($json_data);

            return false;
        }

        $offset_id = ((int) $form->data['offset_id']<0) ? 1 : (int) $form->data['offset_id'];
        $limit     = ((int) $form->data['limit']<1) ? 50 : (int) $form->data['limit'];

        $feedsObj = new UserFeedbackModel();
        $feedsRes = $feedsObj->getOffsetFeedsData($offset_id, $limit);

        if(!count($feedsRes)){
            $json_data['errorCode'] = 3;
            $json_data['errorMsg']  = '没有数据';
            $json_data['data']      = array();

            echo json_encode($json_data);

            return false;
        }

        foreach($feedsRes as $key => $item){
            $feed_list[$key]['id']          = $item['id'];
            $feed_list[$key]['content']     = $item['content'];
            $feed_list[$key]['mobile']      = $item['mobile'];
            $feed_list[$key]['create_time'] = to_date($item['create_time']);
        }

        $json_data['errorCode'] = 0;
        $json_data['errorMsg']  = 'Success';
        $json_data['data']      = $feed_list;
    
        echo json_encode($json_data);
    }

	function getip(){
        $ip_list = array();
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		} else {
			$ip_list[] = isset($_SERVER["HTTP_CLIENT_IP"]) ? ($_SERVER["HTTP_CLIENT_IP"]) : (isset($_SERVER["REMOTE_ADDR"]) ? ($_SERVER["REMOTE_ADDR"]) : "127.0.0.1");
		}
        return $ip_list;
    }
	
}
