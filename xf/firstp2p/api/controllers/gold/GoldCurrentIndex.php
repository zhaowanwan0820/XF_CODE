<?php

/**
 * 优金宝首页列表
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class GoldCurrentIndex extends GoldBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules=array(
            'token'=>array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'token传输错误'
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $res = $this->rpc->local('GoldService\getDealCurrent', array());
        $result = array();
        //活期黄金开关，开关关闭不显示优金宝，0为开关开启
        if((int)app_conf('GOLD_CURRENT_SWITCH')===1){
            $result = $this->handle_res($res);
        }
        $this->json_data = $result;
    }
    public function handle_res($res) {
        if (empty($res)) {
            $result = array();
            return $result;
        }
        $result['minBuyAmonut'] = floorfix($res['minBuyAmount'],3,6).'克起';//起购克重

        $result['rate'] = floorfix($res['rate'],2);//年化收益克重

        //$result['loadAmonut'] = number_format($res['loadAmount'],3);//已售克重
        $result['loadAmonut'] = "--";//已售克重

        $result['tagName'] = $res['tagName'];//tag以,分割
        $result['tagNames'] = empty($res['tagName'])? array() : explode(',', $res['tagName']);
        $result['incomeMode'] = '按日计算收益';//收益方式
        $result['gold_unit'] = '%';//年化收益克重基数

        //判断是否交易日
        $isTradDay = check_trading_day(time());
        $result['isFull'] = true;//是否售罄，true为售罄
        $userId=$res['userId'];
        $isSell=$this->rpc->local('GoldService\isSell',array($userId));
        $minSize=$this->rpc->local('GoldService\getMinSize',array());
        $result['min_size']=number_format($minSize);
        $result['middleTag'] = $result['min_size']."克起提金";
        $result['firstTag'] = "灵活买卖";

        if($isSell['errCode']!=0){
            $this->setErr('ERR_MANUAL_REASON',$isSell['errMsg']);
            return false;
        }
        if($isSell['data']==true){
            $result['isFull']=false;
        }
        if (!$isTradDay || !$this->check_trade_time()) {
            $result['status'] = 2;//非交易时段
        } elseif($result['isFull']) {
            $result['status'] = 1;//售罄
        } else {
            $result['status'] = 0;//购买
            $switch=app_conf('GOLD_SALE_CURRENT_USERID');
            if(!empty($switch)){
                $result['status'] = 1;//售罄
                $data = $this->form->data;
                if(!empty($data['token'])){
                    $user = $this->getUserByToken();
                    if($user){
                        $isWhiteList=$this->rpc->local('GoldService\isSellByUserId',array($user['id']));
                        $result['status'] =  $isWhiteList? 0 : 1;//0是购买，1是售罄
                    }
                }
            }
        }
        return $result;
    }

}
