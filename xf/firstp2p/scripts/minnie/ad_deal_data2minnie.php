<?php
/**
 * 将ad_deal表中的数据批量插入到minnie中
 */
require_once dirname(__FILE__).'/../../app/init.php';
use libs\utils\Logger;
use libs\utils\Curl;
use core\dao\AdunionDealModel;
use core\dao\DealModel;
use core\dao\UserModel;

set_time_limit(0);

class D2M {

    private $minnieUrl = null;

    public function run($coupon) {
        $this->minnieUrl = app_conf('MINNIE_ADD_URL');
        $proId = app_conf('MINNIE_PRO_ID');

        if(!empty($coupon)){
            $oADM = new AdunionDealModel();
            $oDM = new DealModel();
            $oUM = new UserModel();
            $iRegCount = 0;
            $iDealCount = 0;
            
            $param = array(':cn' => $coupon);
            
            $userEuid = array();
            $regDone = false;
            $dealDone = false;
            while(true){
                if(!$regDone){
                    $regCondition = 'cn=":cn" AND is_new_custom = 1 ORDER BY created_at ASC LIMIT '.$iRegCount.', 1000;';
                    $result = $oADM->findAllViaSlave($regCondition, true, "*", $param);
                    if(empty($result)){
                        $regDone = true;
                    }
                    $iRegCount += count($result);
                }
                if($regDone && !$dealDone){
                    $dealCondition = 'cn=":cn" AND goods_price > 0 ORDER BY order_time ASC LIMIT '.$iDealCount.', 1000;';
                    $result = $oADM->findAllViaSlave($dealCondition, true, "*", $param);
                    if(empty($result)){
                        $dealDone = true;
                    }
                    $iDealCount += count($result);
                }
                if($result === false){
                    echo "$coupon findall return false\n";
                    break;
                }
                
                if(empty($result)){
                    echo "$coupon run done\n";
                    break ;
                }
                $dealIds = array();
                $uids = array();
                foreach($result as $item){
                    $uid = intval($item['uid']);
                    $ucode = numTo32($uid);
                    if(empty($uid)){
                        continue;
                    }
                    if($item['is_new_custom']){
                        $saveData = array(
                             'pro_id'          => $proId,
                             'action'          => 'REG',
                             'open_id'         => $ucode,
                             'euid'            => $item['cn'].'_'.$this->getEuid($userEuid,$ucode, $item['euid']),
                             'data_unique_key' => $uid,
                             'action_data'     => json_encode(array('coupon' => $item['cn'], 'regist_time' => $item['created_at'])),
                        );
                        $saveRet = $this->addData($saveData);
                        if(!$saveRet){
                            Logger::error("insert error ".json_encode($saveData));
                            break;
                        }
                    }
                    if($item['goods_price']>0){
                        $uids[] = $uid;
                        $dealId = intval($item['mid']);
                        if(!empty($dealId)){
                            $dealIds[] = $dealId;
                        }
                    }
                }
                if(!empty($dealIds)){
                    //获取deal信息
                    $dealIds = array_unique($dealIds);
                    $dealRet = $oDM->getDealInfoByIds($dealIds, "id,min_loan_money,repay_time,loantype,deal_type");
                    $dealInfos = array();
                    foreach($dealRet as $d){
                        $dealInfos[$d['id']] = $d;
                    }
                    unset($dealRet);
                    //获取用户信息
                    $ucondition = " id in (".implode(",", $uids).") ";
                    $uRet = $oUM->findAllViaSlave($ucondition, true, "id, real_name");
                    $userInfos = array();
                    foreach($uRet as $u){
                        $userInfos[$u['id']] = $u;
                    }
                    unset($uRet);
                    foreach($result as $item){
                        $uid = intval($item['uid']);
                        $ucode = numTo32($uid);
                        if(empty($uid)){
                            continue;
                        }
                        if($item['goods_price']>0){
                            
                        }else{
                            continue;
                        }
                        $saveData = array(
                            'pro_id'          => $proId,
                            'action'          => 'DEAL',
                            'open_id'         => $ucode,
                            'euid'            => $item['cn'].'_'.$this->getEuid($userEuid,$ucode, $item['euid']),
                            'data_unique_key' => $item['order_sn'],
                            'action_data'     => json_encode(array(
                                  'uid'              => $uid,
                                  'username'         => $userInfos[$uid]['real_name'],
                                  'order_sn'         => $item['order_sn'],
                                  'order_money'      => $item['total_price'],
                                  'ordertime'        => $item['order_time'],
                                  'dealid'           => $item['mid'],
                                  'dealname'         => $item['goods_name'],
                                  'deal_start_money' => $dealInfos[$item['mid']]['min_loan_money'],
                                  'deal_repay_time'  => $dealInfos[$item['mid']]['repay_time'],
                                  'deal_loantype'    => $dealInfos[$item['mid']]['loantype'],
                                  'deal_type'        => $dealInfos[$item['mid']]['deal_type'],
                                  'deal_anul'        => $this->_dealAnul(array(
                                        'loantype'        => $dealInfos[$item['mid']]['loantype'],
                                        'repay_time'      => $item['days'],
                                        'deal_load_money' => $item['total_price']
                                  )),
                             )),
                        );
                        $saveRet = $this->addData($saveData);
                        if(!$saveRet){
                            Logger::error("insert error ".json_encode($saveData));
                            break;
                        }
                    }
                }
                unset($result);
                echo "deal reg $iRegCount row, order $iDealCount row\n";
            }
            Logger::info("auto log all euid ".json_encode($userEuid));
            unset($userEuid);
        }else{
            $saveData = array(
                 'pro_id'          => $proId,
                 'action'          => 'REG',
                 'open_id'         => "autotest",//numTo32($this->_params['uid']),
                 'euid'            => "test",//$coupon . '_' . $this->_params['euid'],
                 'data_unique_key' => "autotest",//$this->_params['uid'],
                 'action_data'     => json_encode(array('coupon' => "test", 'regist_time' => date("Y-m-d H:i:s"))),
            );
            $ret = $this->addData($saveData);
            if($ret){
                echo "test insert ok\n";
            }else{
                echo "test insert fail\n";
            }
        }
        return ;
    }

    private function _dealAnul($data) {
        $anulRatio = in_array($data['loantype'], array(1, 2, 8)) ? 0.56 : 1;
        return floor($data['deal_load_money'] * $data['repay_time'] / 360 * $anulRatio * 100) / 100;
    }

    private function getEuid(&$userEuid, $ucode, $euid){
        $euid = trim($euid);
        if(isset($userEuid[$ucode])){
            if(!empty($euid) && $userEuid[$ucode] != $euid){
                Logger::info("euid is uneq $ucode {$userEuid[$ucode]} , $euid");
            }
            return $userEuid[$ucode];
        }
        if(empty($euid)){
            $userEuid[$ucode] = $euid;
            return $euid;
        }
        $retEuid = "";
        $euArr = explode("_", $euid);

        for($i=count($euArr);$i>0;$i--){
            $val = $euArr[$i-1];
            if(empty($val)){
                continue;
            }
            if(isset($userEuid[$val])){
                $retEuid = $userEuid[$val]."_".$val;
                break;
            }else{
                break;
            }
        }
        if(empty($retEuid)){
            $retEuid = $euid;
        }
        $userEuid[$ucode] = $retEuid;
        return $retEuid;
    }

    private function addData($saveData){
        $result = json_decode(Curl::post($this->minnieUrl, $saveData), true);
        if (empty($result) || Curl::$httpCode != 200 || $result['errno']) {
            Logger::error(" auto add minnie data 保存数据失败, 数据:" . json_encode($saveData)." 返回结果：".json_encode($result));
            return false;
        }
        return true;
    }
}

$coupon = empty($argv[1]) ? "" : trim($argv[1]);
$obj = new D2M();
$obj->run($coupon);
