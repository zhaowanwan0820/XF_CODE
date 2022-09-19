<?php
/**
 * 请求活期项目API
 */
class QueryCurrent extends ItzApi{
    public $logcategory = "query.current";

    public function run($addtime='') {
        Yii::log("QueryCurrent: RequestData: addtime=$addtime;", "info", $this->logcategory);
        if (empty($addtime)) $addtime = time(); 
        $check_time = strtotime(date('Y-m-d H:i:s',$addtime));
        if ($check_time != $addtime) {
            Yii::log("QueryCurrent: RequestData's params is not timestamp,  addtime:".$addtime, "error", $this->logcategory);
            $this->code = 7200;
            return $this;
        }

        $time = time();
        $today_time = strtotime(date('Ymd',$addtime));//当日零点
        $current_time = $today_time + 10*60*60;
        if ($time > $current_time) { //今日10点
            $purchase_time = $current_time;
        } else { //昨日10点
            $purchase_time = $current_time-86400;
        }
        
        try{
            //今日剩余申购额度  发售时间  状态
            $itzCurrentBorrow = ItzCurrentBorrow::model()->findBySql("SELECT purchase_quota,status,purchase_amount FROM itz_current_borrow WHERE pubtime =:pubtime",array(':pubtime'=>$purchase_time));  
            if (empty($itzCurrentBorrow) || FunctionUtil::float_bigger_equal(0, $itzCurrentBorrow->purchase_quota, 3) || !in_array($itzCurrentBorrow->status, array(1,3))) {
                Yii::log("QueryCurrent: ItzCurrentBorrow's info is illegal,  pubtime:".$purchase_time.',status:'.$itzCurrentBorrow->status.',purchase_quota:'.$itzCurrentBorrow->purchase_quota, "error", $this->logcategory);
                $this->code = 7210;
                return $this;
            }

            //年化率
            $itzCurrentConfig = ItzCurrentConfig::model()->findBySql("SELECT apr FROM itz_current_config WHERE effect_time =:effect_time",array(':effect_time'=>$today_time));  
            if (empty($itzCurrentConfig) || FunctionUtil::float_bigger_equal(0, $itzCurrentConfig->apr, 3)) {
                Yii::log("QueryCurrent: itzCurrentConfig's info is illegal,  effect_time:".$effect_time, "error", $this->logcategory);
                $this->code = 7211;
                return $this;
            }
        }catch(Exception $e){
            Yii::log('QueryCurrent:'.print_r($e->getMessage(),true), "error", $this->logcategory);
            $this->code = 7231;
            return $this;
        }
        
        //本期剩余申购额度
        $left_purchase_quota = $itzCurrentBorrow->purchase_quota - $itzCurrentBorrow->purchase_amount;
        if (FunctionUtil::float_bigger_equal(0, $left_purchase_quota, 3)) {
            $left_purchase_quota = 0;
            if ($itzCurrentBorrow->status != 3) {
                $itzCurrentBorrow->status = 2;
            }
        }
         
        //每万元收益
        $linghuo_config = Yii::app()->c->linkconfig['linghuo_config']; 
        $params['account'] = 10000;
        $params['year_apr'] = $itzCurrentConfig->apr;
        $params['borrow_style'] = $linghuo_config['interest_style'];
        $params['repayment_time'] = time()+86401;
        $params['borrow_time'] = time();
        $interest_data = InterestPayUtil::EqualInterest($params);
        //起投金额以及计息方式配置
        $data = array();
        $data['left_purchase_quota'] = $left_purchase_quota;
        $data['apr'] = $itzCurrentConfig->apr;
        $data['per_million_interest'] = round($interest_data[0]['interest'], 2);
        $data['lowest_account'] = (int)$linghuo_config['lowest_account'];
        $data['invest_step'] = (int)$linghuo_config['invest_step'];
        $data['status'] = (int)$itzCurrentBorrow->status;
        $data['style'] = (int)$linghuo_config['interest_style'];
        $data['pubtime'] = $purchase_time;
        $this->code = 0;
        $this->data = $data;
        return $this;
    }
}
