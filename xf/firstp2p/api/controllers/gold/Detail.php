<?php
/**
 * 标的详情页
 * @author zhaohui zhaohui3@ucfgroup
 * @data 2017.5.22
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class Detail extends GoldBaseAction {

    const IS_H5 = true;

    //private $_forbid_deal_status;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'option' => array('optional' => true)),
            'dealId' => array('filter' => 'required', 'message' => 'dealId is required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        if(!empty($data['token'])) {
            $user = $this->getUserByToken();
            if (empty($user)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            $isUserDealLoad = $this->rpc->local('GoldService\isUserDealLoad', array($user['id'],intval($data['dealId'])));
        }

        //获取标的信息
        $dealId = intval($data['dealId']);
        $res = $this->rpc->local('GoldService\getDealById', array($dealId));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }


        //判断是否满标,如果是投资用户满标也可以看标的详情
        $flag = $res['data']['dealStatus'] == 2 || $res['data']['dealStatus'] == 4;
        $flag1 = $flag && empty($data['token']);//满标并且未登录
        $flag2 = $flag && !empty($data['token']) && empty($isUserDealLoad);//满标并且登录并且非投资用户
        if ($flag1 || $flag2) {
            $this->template = 'api/views/_v46/gold/full.html';
            return false;
        }
        $deal = array();
        $deal = $this->handleDeal($res);
        //获取实时金价
        $ret = $this->rpc->local('GoldService\getGoldPrice', array());
        //获取投资记录
        $result = $this->rpc->local('GoldService\getDealLog', array($dealId,$isFull=true));
        //每人每日最大变现黄金克重
        $maxGoldCurrentConf = app_conf('GOLD_MAX_WITHDRAW_PER_DAY');
        $maxGoldCurrent = $maxGoldCurrentConf === '' ? 1000 : $maxGoldCurrentConf;

        //增加满额赠金字段描述
        if ($res['data']['loantype'] == 5) {
            $dealBidDays = $res['data']['repayTime'];
        } else {
            $dealBidDays = $res['data']['repayTime'] * 30;
        }
        $deal['rebateGold'] = '';
        $rebateGold = $this->rpc->local('O2OService\getRebateGoldRule', array($dealBidDays));
        if ($rebateGold && $rebateGold['rebate']) {
            $deal['rebateGold'] .= '单笔每满'.$rebateGold['min']. '克可获赠'.$rebateGold['rebate'].'克优金宝';
        }
        $this->tpl->assign('withdrawLimit',floorfix($maxGoldCurrent,3));
        $this->tpl->assign('deal', $deal);
        $this->tpl->assign('gold_price', floorfix($ret['data']['gold_price'],2) == 0 ? '- -' : floorfix($ret['data']['gold_price'],2));
        $this->tpl->assign('load_list',$result['data']);

    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    public function handleDeal($dealInfo) {
        $res = array();
        $res['annual_compen_amount'] = number_format(floorfix($dealInfo['data']['rate'],3,6),3);//年化补偿克重(利率*100)
        $res['available'] = number_format(floorfix($dealInfo['data']['borrowAmount']-$dealInfo['data']['loadMoney'],3,6),3);//可购克重
        $res['period'] = $dealInfo['data']['repayTime'];//期限
        $res['loantype'] = $dealInfo['data']['loantype'];//期限单位如果是5则为月 其他是天
        $res['min_loan_money'] = number_format($dealInfo['data']['minLoanMoney'],3);//起购克数
        $res['gold_type'] = '纯度为99.99%的现货黄金';//黄金品种（暂时没有）
        $res['delay_pick_up_way'] = number_format(floorfix($dealInfo['data']['rate'],3,6),3);//延期提货补偿计算方式（暂时没有）
        //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5106?filter=-1,jira在此
        //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5169?filter=-1
        //2017年9月7日23:55转换为时间戳
        $startTime = mktime(23,55,0,9,7,2017);
        if ($dealInfo['data']['startTime'] < $startTime && $dealInfo['data']['repayTime'] > 100){
            $res['delivery_method'] = self::$loantype_info[6];
        } else {
            if ($dealInfo['data']['loantype'] == 5) {
                $res['delivery_method'] = self::$loantype_info[5];
            } elseif ($dealInfo['data']['loantype'] == 6) {
                $res['delivery_method'] = self::$loantype_info[6];
            }
        }
        $res['buyer_fee'] = number_format(floorfix($dealInfo['data']['buyerFee'],2),2);//买入手续费
        $res['tag'] = $dealInfo['data']['dealTagName'];//tag
        $res['detailHtml'] = $dealInfo['data']['intro'];
        //获取优金宝详情(为了获取活期的变现手续费和提金金价)
        $goldCurrentDetail = $this->rpc->local('GoldService\getInfo', array());
        $res['current_fee'] = floorfix($goldCurrentDetail['withdrawFee'],2);//变现手续费
        $res['take_fee'] = floorfix($goldCurrentDetail['receiveFee'],2);//提金手续费
        return $res;
    }
 
}
