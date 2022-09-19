<?php
class AccountLogTypeUtil{    

    /**
    * 新旧流水类型对照
    */
    public static $accountlogtype = array(
        //投标
        "invest_frost"  => array("invest_frost","lease_frost","factoring_frost","art_frost","shengxin_frost"),
        "invest_cancel" => array("invest_cancel","lease_cancle","factoring_cancel","art_cancel","shengxin_cancel"),
        "invest_success" => array("invest","lease","factoring","art","shengxin"),

        "interest_company_ontime" => array("repayment","leasei_repayment","factoringi_repayment","arti_repayment","shengxini_repayment"),
        "interest_company_advance_extra" => array("invest_ai_repayment"),
        "interest_company_advance" => array("invest_ai2_repayment","lease_ai2_repayment", "shengxin_ai2_repayment"),

        "interest_security_ontime" => array("invest_ri_repayment","lease_ri_repayment"),
        "interest_buyback_frost" => array("investi_bb_frost"),
        "interest_buyback_cancel" => array("investi_bb_repayment"),

        "interest_guarantor_overtime_extra" => array("art_ci_over_repay","factoring_ci_over_repay","lease_ci_over_repay","invest_ci_over_repay"),
        "interest_guarantor_overtime" => array("invest_ci_repayment","lease_ci_repayment","factoring_ci_repayment","art_ci_repayment"),
        
        "capital_company_ontime" => array("invest_repayment","lease_repayment","factoring_repayment","art_repayment","shengxin_repayment"),
        "capital_company_advance" => array("invest_ac_repayment", "lease_ac_repayment", "shengxin_ac_repayment"),


        "capital_guarantor_overtime" => array("invest_cc_repayment","lease_cc_repayment"),
        "capital_guarantor_advance" => array("invest_aac_repayment"),
        "capital_security_ontime" => array("invest_rc_repayment", "lease_rc_repayment"),
        "capital_buyback_frost" => array("invest_bb_frost"),
        "capital_buyback_cancel" => array("invest_bb_repayment"),

        "debt_frost" => array("debt","invest_debt","lease_debt","leasedebt", "factoring_debt", "art_debt", "shengxin_debt"),
        "debt_success" => array("debt_success"),
        "debt_cancel" => array("debt_cancel"),
        "debt_finish" => array("lease_debt_finish", "debt_finish","factoring_debt_finish","art_debt_finish","shengxin_debt_finish"),

    );
    

    public static $direction = array(
        //投标
        "invest_frost"  => 0,
        "invest_cancel" => 0,
        "invest_success" => 2,

        "interest_company_ontime" => 1,
        "interest_company_advance_extra" => 1,
        "interest_company_advance" => 1,
        
        "interest_security_ontime" => 1,
        "interest_buyback_frost" => 0,
        "interest_buyback_cancel" => 0,

        "interest_guarantor_overtime_extra" => 1,
        "interest_guarantor_overtime" => 1,
        
        "capital_company_ontime" => 1,
        "capital_company_advance" => 1,

        "capital_guarantor_overtime" => 1,
        "capital_security_ontime" => 1,
        "capital_buyback_frost" => 0,
        "capital_buyback_cancel" => 0,

        "debt_frost" => 0,
        "debt_success" => 2,
        "debt_cancel" => 0,
        "debt_finish" => 1,
    );

    /**
    * 输入的旧的流水类型， 返回新的流水类型
    * -- 即输入type 返回 log_type
    */
    public static function Info($name){

        foreach (self::$accountlogtype as $key => $value) {
            if(in_array($name, $value)){
                $info['log_type_name'] = $key;
                //$info['direction'] = self::$direction[$key]; //暂停此功能
               return $info;
            } 
        }
        return "";
    }

    /**
    * 将以上数组按 'recharge','cash' 拼接
    * -- 即输入log_type ，返回type的拼接字符串
    */
    public static function gluesInfo($key){
        if($key == 'all') {
            $glues = "'";
            foreach (self::$accountlogtype as $key => $value) {
                $glues .=implode("','", $value)."','" ;
            }
            $glues = rtrim($glues, "'");
            $glues = rtrim($glues, ",");
            return $glues;
        } else {
            $glues = "'";
            $glues .= implode("','", self::$accountlogtype[$key])."','";
            $glues = rtrim($glues, "'");
            $glues = rtrim($glues, ",");
            return $glues;
        }
    }

}
?>