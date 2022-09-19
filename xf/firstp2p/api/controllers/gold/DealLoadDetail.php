<?php
/**
 * 标的详情页
 * @author zhaohui zhaohui3@ucfgroup
 * @data 2017.5.22
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class DealLoadDetail extends GoldBaseAction {

    const IS_H5 = true;

    //private $_forbid_deal_status;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'dealLoadId' => array('filter' => 'required', 'message' => 'dealLoadId is required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }


        //获取投资详情
        $dealLoadDetail = $this->rpc->local('GoldService\getDealLoadDetail', array(intval($data['dealLoadId']),$user['id']));
        if ($dealLoadDetail['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$dealLoadDetail['errMsg']);
            return false;
        }
        //获取标的信息
        $dealId = intval($data['dealId']);
        $res = $this->rpc->local('GoldService\getDealById', array($dealLoadDetail['data']['deal_id']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }
        $deal = array();
        $deal = $this->handleDeal($res);
        //合同信息
        $contract_info = $this->rpc->local('ContractNewService\getGoldContractIdByDealLoad', array($dealLoadDetail['data']['deal_id'],intval($data['dealLoadId']),$user['id']));
        $contract_list  = empty($contract_info) ? array() : array($contract_info);
        $this->tpl->assign("contract_list", $contract_list);
        //获取投资记录
        $result = $this->rpc->local('GoldService\getDealLog', array($dealLoadDetail['data']['deal_id'],$isFull=true));
        //收益计划
        $repayInfo = $this->rpc->local('GoldService\getMoneyRepayByDealIdAndUserId', array(intval($data['dealLoadId']),$user['id']));
        $interestBlackListUserIds=$this->rpc->local('GoldService\getSpecialDealBlackList',array());
        $repayInfoList = array();
        if (!empty($repayInfo)) {
            $repayInfoList = $repayInfo;
            foreach ( $repayInfo as $key => $value) {
                $repayInfoList[$key]['money'] = $value['money'].'克';
                if($res['data']['repayTime']==170&&$res['data']['startTime']<=1504799400&&in_array($user['id'],$interestBlackListUserIds)&&$value['type'] == 2){
                    unset($repayInfoList[$key]);
                } elseif ($value['type'] == 1 && $value['status'] == 1) {
                    $repayInfoList[$key]['info'] ='已转入优金宝';
                    $repayInfoList[$key]['info_res'] ='已购黄金';
                    $repayInfoList[$key]['time']=date('Y-m-d',$repayInfoList[$key]['time']);
                } elseif ($value['type'] == 2 && $value['status'] == 1) {
                    $repayInfoList[$key]['info'] ='已转入优金宝';
                    $repayInfoList[$key]['info_res'] ='收益克重';
                    $repayInfoList[$key]['time']=date('Y-m-d',$repayInfoList[$key]['time']);
                } elseif ($value['type'] == 2 && $value['status'] == 0) {
                    $repayInfoList[$key]['info'] ='未到账';
                    $repayInfoList[$key]['info_res'] ='收益克重';
                    $repayInfoList[$key]['time']=($res['data']['repayTime']==170&&$res['data']['startTime']<=1504799400)?($value['time']-6912000):$value['time'];
                    $repayInfoList[$key]['time']=date('Y-m-d',$repayInfoList[$key]['time']);
                } elseif ($value['type'] == 1 && $value['status'] == 0) {
                    $repayInfoList[$key]['info'] ='未到账';
                    $repayInfoList[$key]['info_res'] ='已购黄金';
                    $repayInfoList[$key]['time']=date('Y-m-d',$repayInfoList[$key]['time']);
                }
            }
        }
        $repayInfoList=array_values($repayInfoList);
        //每人每日最大变现黄金克重
        $maxGoldCurrentConf = app_conf('GOLD_MAX_WITHDRAW_PER_DAY');
        $maxGoldCurrent = $maxGoldCurrentConf === '' ? 1000 : $maxGoldCurrentConf;
        $this->tpl->assign('withdrawLimit',floorfix($maxGoldCurrent,3));
        $this->tpl->assign('deal_load_detail', $dealLoadDetail['data']);
        $this->tpl->assign('usertoken', $data['token']);
        $this->tpl->assign('data', $data);
        $this->tpl->assign('load_list',$result['data']);
        $this->tpl->assign('deal', $deal);
        $this->tpl->assign('repayInfoList', $repayInfoList);
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

    public function handleDeal($dealInfo) {
        $res = array();
        $res['annual_compen_amount'] = number_format(floorfix($dealInfo['data']['rate'],3,6),3);//年化补偿克重(利率*100)
        $res['period'] = $dealInfo['data']['repayTime'];//期限
        $res['loan_type'] = $dealInfo['data']['loantype'];//期限单位如果是5则为月 其他是天
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
        $res['detailHtml'] = $dealInfo['data']['intro'];
        //获取优金宝详情(为了获取活期的变现手续费和提金金价)
        $goldCurrentDetail = $this->rpc->local('GoldService\getInfo', array());
        $res['current_fee'] = floorfix($goldCurrentDetail['withdrawFee'],2);//变现手续费
        $res['take_fee'] = floorfix($goldCurrentDetail['receiveFee'],2);//提金手续费
        return $res;
    }
}
