<?php
/*
 * 封装一部分合同管理的方法
 * @author wangyiming@ucfgroup.com
 */
class ContractModel extends CommonModel {
    const HY_TYPE = "HY";

    /**
     * 根据合同id获取单个合同
     * @param $id int 合同ID
     * @return array 合同信息
     */
    public function get_contract_by_id ($id) {
        return M('Contract')->where("id={$id}")->find();
    }

    /*
     * 代签贷款人合同
     * @param $id int 合同id
     * @return boolean
     */
    public function contract_agree ($id) {
        $info = $this->get_contract_by_id($id);
        if (!$info) {
            return false;
        }

        $time = time();
        /* if ($info['type'] == 1) {
            $contracts = M("Contract")->where("type=1 AND deal_id='{$info['deal_id']}' AND number='{$info['number']}'")->findAll();
            if ($contracts) {
                foreach ($contracts as $val) {
                    $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/", date("Y年m月d日", $time), $val['content']);
                    $data    = array(
                        "content" => $content,
                    );
                    $option  = array(
                        "where" => "id='{$val['id']}'",
                    );
                    M("Contract")->save($data, $option);

                    $this->del_contract_file($val);
                }
            }
        }
        
        if($info['type'] == 5){
            $replace = "<span id='borrow_sign_time'>".date("Y年m月d日",$time)."</span>";
            $content = preg_replace("/\<span[\s]*id\=\'borrow_sign_time\'\>.*?\<\/span\>/",$replace,$info['content']);
            
            $data    = array(
                "content" => $content,
            );
            $option  = array(
                "where" => "id='$id'",
            );
            M("Contract")->save($data, $option);

            $this->del_contract_file($info);
        } */

        $user_name =  M("User")->where("id=".$info['user_id']." and is_delete = 0")->getField("user_name");
        $data = $where = array(
            "user_id"     => $info['user_id'],
            "user_name"   => $user_name,
            "agency_id"   => 0,
            "contract_id" => $id,
            "deal_id"     => $info['deal_id'],
        );
        
        $data['pass'] = 1;
        $data['create_time'] = $time;
        
        $is_have_sign = M("AgencyContract")->where($where)->find();
        
        if($is_have_sign){
        	$sign_res = M("AgencyContract")->where($where)->save($data); 
        }else{
        	$sign_res = M("AgencyContract")->add($data);
        }
        
        if ($sign_res === false) {
            $this->log($info['deal_id'], $id, 1, false);
            return false;
        } else {
            $this->log($info['deal_id'], $id, 1);
            return true;
        }
    }

    /*
     * 代签担保公司合同
     * @param $id int 合同id
     * @param $agency_uid int 担保人user_id
     * @return boolean
     */
    public function contract_agree_agency ($id, $agency_uid) {
        $info      = $this->get_contract_by_id($id);
        $agency_id = $info['agency_id'];
        $deal_id   = $info['deal_id'];
		$time = time();
        $deal_info = M("Deal")->where("id='{$deal_id}'")->find();
        if ($deal_info['contract_tpl_type'] == self::HY_TYPE && $agency_id == $GLOBALS['dict']['HY_DBGS']) {
            $agency_user_info = array(
                "agency_id" => $GLOBALS['dict']['HY_DBGS'],
                "is_hy"     => 1,
            );

            FP::import("libs.common.dict");
            foreach (dict::get('HY_DB') as $user_name) {
                //先查询是否汇赢担保公司代理人已经签过，如签过则跳过
                $con = M("AgencyContract")->where("user_name='{$user_name}' AND agency_id='{$agency_user_info['agency_id']}' AND contract_id='{$id}' AND deal_id='{$deal_id}' AND pass = 1")->find();
                if ($con) {
                    continue;
                }
                $data = $where = array(
                    "user_id"     => $agency_uid,
                    "user_name"   => $user_name,
                    "agency_id"   => $agency_user_info['agency_id'],
                    "contract_id" => $id,
                    "deal_id"     => $deal_id,
                );
                $data['pass'] = 1;
                $data['create_time'] = $time;
                
                $is_have_sign = M("AgencyContract")->where($where)->find();
                
                if($is_have_sign){
                	$sign_res = M("AgencyContract")->where($where)->save($data);
                }else{
                	$sign_res = M("AgencyContract")->add($data, array(), true);
                }
                
                if ($sign_res === false) {
                    $this->log($deal_id, $id, 2, false);
                    return false;
                }
            }
            $this->log($deal_id, $id, 2);
            return true;
        } else {
            $agency_user_info = M("AgencyUser")->where("agency_id='{$agency_id}' AND user_id = {$agency_uid}")->find();
            if (!$agency_user_info) {
                return false;
            }

            $data = $where = array(
                "user_id"     => $agency_uid,
                "user_name"   => $agency_user_info['user_name'],
                "agency_id"   => $agency_user_info['agency_id'],
                "contract_id" => $id,
                "deal_id"     => $deal_id,
            );
            
            $data['pass'] = 1;
            $data['create_time'] = $time;
            
            $is_have_sign = M("AgencyContract")->where($where)->find();
            
            if($is_have_sign){
            	$sign_res = M("AgencyContract")->where($where)->save($data);
            }else{
            	$sign_res = M("AgencyContract")->add($data);
            }
            
            if ($sign_res === false) {
                $this->log($deal_id, $id, 2, false);
                return false;
            } else {
                $this->log($deal_id, $id, 2);
                return true;
            }
        }
    }

    /*
     * 根据合同信息删除合同文件
     * @param $contract_info array 合同信息
     */
    private function del_contract_file ($contract_info) {
        $id   = ceil($contract_info['id'] / 1000);
        $file = $GLOBALS['dict']['CONTRACT_PDF_PATH']."{$id}/" . md5($contract_info['number']) . ".pdf";
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /*
     * 根据合同id检查订单的全部合同状态
     * @param $deal_id int 订单id
     * @return boolean
     */
    public function check_contract_deal ($deal_id) {
        $deal_info = M("Deal")->where("id={$deal_id}")->find();
        if (!$deal_info) {
            return false;
        }
        return $this->check_contract_user($deal_info) && $this->check_contract_agency($deal_info);
    }

    /*
     * 根据订单id做出如下操作：1.将全部合同状态更新为已通过；2.更新见证人证明书；3.发送邮件
     * @param $deal_id int 订单id
     */
    public function access_contract ($deal_id) {

        FP::import("app.deal");
        FP::import("libs.common.app");

        //更新合同状态
        $this->pass_contract($deal_id);
        //更新见证人证明书
        $this->update_prove($deal_id);
        //发送邮件
        send_contract_sign_email($deal_id);
    }

    /*
     * 根据订单id将订单全部合同状态更新为通过
     * @param $deal_id int 订单id
     * @return boolean
     */
    private function pass_contract ($deal_id) {
        $data   = array(
            "status" => 1,
        );
        $option = array(
            "where" => "deal_id='{$deal_id}'"
        );

        return M("Contract")->save($data, $option);
    }

    /*
     * 更新见证人证明书
     * @param $deal_id int 订单id
     * @return boolean
     */
    private function update_prove ($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }
        $data   = array(
            "effect_time" => time(),
        );
        $option = array(
            "where" => "deal_id='{$deal_id}'",
        );

        return M("DealLoadProve")->save($data, $option);
    }

    /*
     * 根据订单信息判断用户是否通过全部合同
     * @param $deal_info array 订单信息
     * @return boolean
     */
    private function check_contract_user ($deal_info) {
        //如果有母单，则获取母单所有的子单
        $arr_deal_ids = array();
        $arr_deal_ids[] = $deal_info['id'];

        foreach ($arr_deal_ids as $deal_id) {
            $contract = M("Contract")->where("deal_id={$deal_id} AND (type=1 OR type=2 OR type=6)")->findAll();
            foreach ($contract as $val) {
                if ($val['user_id']) {
                    $is_loaner = M("DealLoad")->where("user_id={$val['user_id']} AND deal_id={$deal_id}")->find();
                    if (!empty($is_loaner)) {
                        continue;
                    }
                }
                $is_pass = M("AgencyContract")->where("contract_id={$val['id']} AND pass=1")->find();
                if (!$is_pass) {
                    return false;
                }
            }
        }
        return true;
    }

    /*
     * 根据订单信息判断担保公司是否通过全部合同
     * @param $deal_info array 订单信息
     * @return boolean
     */
    private function check_contract_agency ($deal_info) {
        $arr_deals[] = $deal_info;

        //todo 需要测试这里的逻辑是否正确
        foreach ($arr_deals as $deal) {
            $info = M("Contract")->where("deal_id={$deal['id']} AND agency_id>0")->find();
            if ($info) {
                if ($deal['contract_tpl_type'] == self::HY_TYPE) {
                    FP::import("libs.common.dict");
                    $ag_count = count(dict::get('HY_DB'));
                } else {
                    $ag_count = M("AgencyUser")->where("agency_id={$info['agency_id']}")->count();
                }

                $contracts = M("Contract")->where("deal_id='{$info['deal_id']}' AND agency_id='{$info['agency_id']}' AND (type=2 OR type=3 OR type=4)")->findAll();
                foreach ($contracts as $val) {
                    $pass_num = M("AgencyContract")->where("contract_id='{$val['id']}' AND pass=1")->count();
                    if ($pass_num < $ag_count) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }
    
    /*
     * 检查一条合同是否需要签署
     * @param $cont_id int 合同id
     * @return boolean
     */
    public function sign_one_contract($cont_id){
        $cont_id = intval($cont_id);
        $return = false;
        if($cont_id <= 0){
            return $return;
        }
        
        $cont_info = $this->get_contract_by_id($cont_id);
        $deal_info = M("Deal")->where("id='{$cont_info['deal_id']}'")->find();
        
        //借款人
        if($cont_info['user_id'] > 0 && $cont_info['agency_id'] == 0 && $cont_info['user_id'] == $deal_info['user_id']){
            
            //检查这个合同是否已经签署，防止签署重复
            $is_sign = $this->check_cont_issign($cont_info['user_id'], 0, $cont_id, $cont_info['deal_id']);
            if(!$is_sign){
                return $this->contract_agree($cont_id);
            }
            
        //担保公司
        }elseif($cont_info['user_id'] == 0 && $cont_info['agency_id'] > 0){
            //获取担保公司用户数组
            if($deal_info['contract_tpl_type'] == 'HY' && $cont_info['agency_id'] == $GLOBALS['dict']['HY_DBGS']){
                FP::import("libs.common.dict");
                foreach(dict::get('HY_DB') as $agency_user_hy){
                    $user_id =  M("User")->where("user_name = '".$agency_user_hy."'")->getField("id");
                    if($user_id){
                        //检查这个用户是否已经签署，防止签署重复
                        $is_sign = $this->check_cont_issign($user_id, $cont_info['agency_id'], $cont_id, $cont_info['deal_id']);
                        if(!$is_sign){
                            $this->contract_agree_agency($cont_info['id'], $user_id);
                            $return = true;
                        }
                    }
                }
            }else{
                $agency_user = M("AgencyUser")->where('agency_id = '.$cont_info['agency_id'])->findAll();
                if($agency_user){
                    foreach($agency_user as $agency_one){
                        //检查这个用户是否已经签署，防止签署重复
                        $is_sign = $this->check_cont_issign($agency_one['user_id'], $cont_info['agency_id'], $cont_id, $cont_info['deal_id']);
                        if(!$is_sign){
                            $this->contract_agree_agency($cont_info['id'], $agency_one['user_id']);
                            $return = true;
                        }
                    }
                }
            }
        }
        
        return $return;
    }
    
    /*
     * 检查一条合同是否已经签署
     * @param $user_id int 用户id
     * @param $agency_id int 担保公司id
     * @param $cont_id int 合同id
     * @param $deal_id int 借款id
     * @return boolean
     */
    public function check_cont_issign($user_id, $agency_id, $cont_id, $deal_id){
        if($user_id){
            $user_name =  M("User")->where("id=".$user_id." and is_delete = 0")->getField("user_name");
        }
        $where = array(
                "user_id"     => $user_id,
                "user_name"   => $user_name,
                "agency_id"   => $agency_id,
                "contract_id" => $cont_id,
                "deal_id"     => $deal_id,
                "pass"        => 1,
        );
        
        $is_have_sign = M("AgencyContract")->where($where)->find();
        return $is_have_sign ? true : false;
    }

    /*
     * 私有记录日志方法
     * @param $deal_id int 订单id
     * @param $contract_id int 合同id
     * @param $type int 1-贷款人合同 2-担保公司合同
     * @param $is_succ boolean 默认true-操作成功 false-操作失败
     */
    private function log ($deal_id, $contract_id, $type, $is_succ = true) {
        $act = $type == 1 ? "代签贷款人合同" : "代签担保公司合同";
        $act .= $is_succ ? "成功" : "失败";
        $data = array(
            "act"         => $act,
            "deal_id"     => $deal_id,
            "contract_id" => $contract_id,
        );
        $msg  = implode(" | ", $data);
        save_log($msg, $is_succ ? 1 : 0);
    }

}

?>
