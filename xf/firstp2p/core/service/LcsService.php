<?php

namespace core\service;
use libs\utils\Logger;
use core\dao\LcsModel;
use core\service\BonusService;
use core\dao\UserProfileModel;

class LcsService extends BaseService {

    public function getCustomersRepayInfoByYMD($referUserId,$year,$month,$day,$offset,$count){
        $ret = array();
        $lm = new LcsModel();
        $list = $lm->getCustomersRepaysDetailYMD($referUserId,$year,$month,$day,$offset,$count);
        if(empty($list)){
            return array('list'=>[],'allMoney'=>0);
        }
        $upm = new UserProfileModel();
        $ups = new UserProfileService();
        foreach($list as &$one){
            if(floatval($one['repay_principal'])==0  && floatval($one['repay_interest'])==0){
                $one['repayType'] = 'toRepay';
                $one['principalRepayMoney'] = $one['no_repay_principal'];
                $one['profitRepayMoney'] = $one['no_repay_interest'];
                $one['totalRepayMoney'] = $one['principalRepayMoney'] + $one['profitRepayMoney'];
            }else{
                $one['repayType'] = 'alRepay';
                $one['principalRepayMoney'] = $one['repay_principal'];
                $one['profitRepayMoney'] = $one['repay_interest'];
                $one['totalRepayMoney'] = $one['principalRepayMoney'] + $one['profitRepayMoney'];
            }
            $userBaseInfo = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBaseInfo, $one);
        }
        $ret['list'] = $ups->flushCfpData($list);
        $statisticsData = $lm->getCustomersMapRepaysStatistics($referUserId,$year,$month,$day);
        $ret['allMoney'] = $statisticsData[0]['repay'] + $statisticsData[0]['no_repay'];
        $ret['total'] = $statisticsData[0]['total'];
        return $ret;
    }

    public function getCustomersRepayMapDate($referUserId,$year,$month,$offset,$count,$repayType){
        $ret = array();
        $lm = new LcsModel();
        // 日历
        $maps = $lm->getCustomersRepaysMap($referUserId,$year,$month,$offset,$count);
        if(empty($maps)){
            return array('list'=>[],'repayMoneyMonth'=>"0",'alRepayMoneyMonth'=>"0");
        }

        $ret['map'] = $maps;
        $ret['total'] = $lm->getCustomersMapRepaysListCount($referUserId,$year,$month,$repayType);
        $ret['year'] = $year;

        // 详情列表
        $list = $lm->getCustomersMapRepaysList($referUserId,$year,$month,$offset,$count,$repayType);
        // 总已还未还
        $statisticsData = $lm->getCustomersMapRepaysStatistics($referUserId,$year,$month);

        $ret['repayMoneyMonth'] = strval($statisticsData[0]['repay'] + $statisticsData[0]['no_repay']);
        $ret['alRepayMoneyMonth'] = strval($statisticsData[0]['repay']);

        $upm = new UserProfileModel();
        $ups = new UserProfileService();
        foreach($list as &$one){
            $one['totalRepayMoney']= strval($one['principalRepayMoney'] + $one['profitRepayMoney']);
            $userBaseInfo = $upm->getBaseInfoByUserIds($one['user_id']);
            $one = array_merge($userBaseInfo, $one);
        }
        $ret['list'] = $ups->flushCfpData($list);
        return $ret;
    }

    public function getCustomersRepayMonthDate($referUserId){
        $lm = new LcsModel();
        $ret = $lm->getCustomersRepayMonthDate($referUserId);
        return $ret;
    }
}
