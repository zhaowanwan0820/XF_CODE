<?php
include_once 'Config.php';
	/**
	 *用户自定义实现对数据库的操作,获取订单的信息
	 **/
class Dto{
	/**
	 * 根据活动id和下单时间查询订单信息 
	 * @param 活动id $campaignId
	 * @param 下单时间 $date
	 * @throws Exception
	 */
	public function getOrderByOrderTime($campaignId,$orderStartTime,$orderEndTime){
		if (empty($campaignId) || empty($orderStartTime)||empty($orderEndTime)){
	 		throw new Exception("campaignId ,orderStatTime or orderEndTime is null", 613, "");
	 	}
//		$date = date('Y-m-d H',$date);//转化成时间,到数据库的查询
		$orderlist = array();
		$label = 'yimacps';
        $channel_info = ChannelService::getInstance()->getChannelInfoByLabel($label);
        $all_logs = ChannelService::getInstance()->getChannelLogs($channel_info['id'],2,'ALL',$orderStartTime,$orderEndTime);    //type 2 获取投资的列表 
		foreach($all_logs as $channel_log){
		    $channel_log_id = $channel_log['id'];
            $cps = json_decode($channel_log['ext'],true);
            if(empty($cps['orderNo']) || $cps['campaignId']!=$campaignId) {
                continue ;
            }
			$order = new Order();
			$order->setOrderNo($cps['orderNo']);
			$orderTime = date('Y-m-d H:i:s', $cps['orderTime']);
			$order->setOrderTime($orderTime);
			$updateTime = date('Y-m-d H:i:s', $cps['updateTime']);
			$order->setUpdateTime($updateTime);
			$order->setCampaignId($cps['campaignId']);
			$order->setFeedback($cps['feedback']);
			$order->setFare($cps['fare']);
			$order->setFavorable($cps['favorable']);
			$order->setFavorableCode($cps['favorableCode']);
			$order->setOrderStatus($cps['orderStatus']);
			$order->setPaymentStatus($cps['paymentStatus']);
			$order->setPaymentType($cps['paymentType']);
		
            $id_array = UrlUtil::_url2key($cps['productNo']);
            $borrow_id = $id_array[0];
            $borrow = BorrowService::getInstance()->getBorrowFromCache($borrow_id);
//            $days = round(($borrow['borrow']['repayment_time'] - $borrow['borrow']['formal_time']) / 86400,0);
            $days = round(($borrow['borrow']['repayment_time'] - $channel_log['addtime']) / 86400,0);
            $CommissionType = ''; 
            if($cps['price'] < 100000) {
                if($days < 120) {
                    $CommissionType = 'A';
                } elseif($days >= 120 && $days <= 360) {
                    $CommissionType = 'D';
                } else {
                    $CommissionType = 'G';
                }
            } elseif($cps['price'] >= 100000 && $cps['price'] <= 500000) {
                if($days < 120) {
                    $CommissionType = 'B';
                } elseif($days >= 120 && $days <= 360) {
                    $CommissionType = 'E';
                } else {
                    $CommissionType = 'H';
                }
            } else {
                if($days < 120) {
                    $CommissionType = 'C';
                } elseif($days >= 120 && $days <= 360) {
                    $CommissionType = 'F';
                } else {
                    $CommissionType = 'I';
                }
            }
            
			$pro = new Product();
			$pro->setProductNo($cps['productNo']);
			$pro->setName($cps['name']);
			$pro->setCategory($cps['category']);
			$pro->setCommissionType($CommissionType);
			$pro->setAmount($cps['amount']);
			$pro->setPrice($cps['price']);
			
			$products = array($pro);
			$order -> setProducts($products);
			
			$orderlist[] = $order;	
		}
	 	return $orderlist;
	}
	
	/**
	 * 根据活动id和订单更新时间查询订单信息
	 * @param 活动id $campaignId
	 * @param 订单更新时间 $date
	 */
    public function getOrderByUpdateTime($campaignId,$updateStartTime,$updateEndTime){
		if (empty($campaignId) || empty($updateStartTime)||empty($updateEndTime)){
	 		throw new Exception("CampaignId or date is null!", 648, "");
	 	}
		$orderStatusList = array();
		$label = 'yimacps';
        $channel_info = ChannelService::getInstance()->getChannelInfoByLabel($label);
        $all_logs = ChannelService::getInstance()->getChannelLogs($channel_info['id'],2,'ALL',$updateStartTime,$updateEndTime);
		
		foreach($all_logs as $channel_log){
		    $channel_log_id = $channel_log['id'];
            $cps = json_decode($channel_log['ext'],true);
		    if(empty($cps['orderNo']) || $cps['campaignId']!=$campaignId) {
                continue ;
            }
			$orderStatus = new OrderStatus();
			$orderStatus -> setOrderNo($cps['orderNo']);
			$updateTime = date('Y-m-d H:i:s', $cps['updateTime']);
			$orderStatus -> setUpdateTime($updateTime); //设置订单更新时间，如果没有下单时间，要提前对接人提前说明
			$orderStatus -> setFeedback($cps['feedback']);
			$orderStatus -> setOrderStatus($cps['orderStatus']);//设置订单状态
			$orderStatus -> setPaymentStatus($cps['paymentStatus']);//设置支付状态
			$orderStatus -> setPaymentType($cps['paymentType']);// 支付方式
			$orderStatusList[]=$orderStatus;
		}
	 	return $orderStatusList;
	}
}
?>
