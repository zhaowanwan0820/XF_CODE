<?php

namespace web\controllers\adunion;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\BdActivityServive;

class BdActOrders extends BaseAction {

    public function init() {
        $this->form = new Form("get");

        $this->form->rules = array(
                'coupon' => array('filter' => 'string'),
                'startDate' => array('filter' => 'string'),
                'endDate' => array('filter' => 'string'),
                'prizeId' => array('filter' => 'int'),
                'format' => array('filter' => 'string'),
                );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $coupon = htmlspecialchars($data['coupon']);
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $prizeId = intval($data['prizeId']);
        $format = $data['format'];


        $orderList = $this->rpc->local("BdActivityService\getOrders", array($coupon, $prizeId, $startDate, $endDate));

        $userIds = array();
        foreach ($orderList as $item) {
            $userIds[] = $item['user_id'];
        }
        $cashpresentList = $this->rpc->local('CashpresentService\getPresentListByUserIds', array($userIds));

        $orders = array();
        foreach($orderList as $order) {
            
            $status = '未知';
            if( $order['status'] == 0){
                $status = '未返回';
            }elseif ($order['status'] == 1){
                $status = '成功';
            }elseif ($order['status'] == -1){
                $status = '失败';
            }
            $orders[] = array(
                    'user_id' => $order['user_id'],
                    'mobile' => $order['mobile'],
                    'order_sn' => $order['order_sn'],
                    'count' => $order['count'],
                    'status' => $status,
                    'prize_id' => $order['prize_id'],
                    'coupon' => $order['coupon'],
                    'code' => $order['code'],
                    'cashpresent' => empty($cashpresentList[$order['user_id']]['status']) ? '失败' : '成功',
                    'create_time' => date("Y-m-d H:i:s",$order['create_time']),
                    );
        }
        
        if($this->auth401())
            $this->output($orders, $format);
    }

    private function output($data, $format) {
        switch($format){

            case 'table':
                $this->outputTable($data);
                break;
            case 'csv':
                $this->outputCsv($data);
                break;
            default:
                $this->outputJson($data);
                break;

        }

    }

    private function auth401(){
        do{
            if(!isset($_SERVER['PHP_AUTH_USER'])) break;
            if(!isset($_SERVER['PHP_AUTH_PW'])) break;
            if($_SERVER['PHP_AUTH_USER']==='reader' AND $_SERVER['PHP_AUTH_PW'] === substr(md5("wangxinglicai_".date("Y-m-d")),0,6))
                return true;
        }while(false);

        header('WWW-Authenticate: Basic realm="BD REPORT"');
        header('HTTP/1.0 401 Unauthorized');
        echo '未通过HTTP认证.';
        return false;
    

    }

    private function outputTable($data){
        echo "<!DOCTYPE html><html lang='zh-cn'><head><meta charset='utf-8'><title>数据</title></head><body>";
        echo "<table border=1 width='97%' cellspacing=0><tr><th>user_id</th><th>手机号</th><th>订单编号</th><th>数量</th><th>状态</th><th>奖品id</th><th>渠道号</th><th>串码</th><th>打款状态</th><th>创建时间</th></tr>";
        foreach($data as $item){
            echo "<tr>";
            echo "<td>{$item['user_id']}</td><td>{$item['mobile']}</td><td>{$item['order_sn']}</td>
                <td>{$item['count']}</td><td>{$item['status']}</td><td>{$item['prize_id']}</td>
                <td>{$item['coupon']}</td><td>{$item['code']}</td><td>{$item['cashpresent']}</td><td>{$item['create_time']}</td>";
            echo "</tr>";
        }
        echo "</table></body></html>";
    }

    private function outputJson($data){
        header("Content-Type: application/json");
        header("Cache-Control: no-store");
        echo json_encode($data);
        return false;
    }

}
