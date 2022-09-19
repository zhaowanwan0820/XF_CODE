<?php
/**
 * 向指定天数 需要还款的借款人 发送还款提醒
 * 01 12 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php payment_request.php
 * @author wenyanlei 20140319
 */

require_once dirname(__FILE__).'/../app/init.php';

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealAgencyModel;
use core\dao\DealRepayModel;
use core\dao\DealExtModel;
use core\dao\DealLoanTypeModel;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\sms\SmsServer;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class DealRepayDunMessage{


    const NOTICE_ROLE_USER           = 0b00000001; // 投资人
    const NOTICE_ROLE_BORROWER       = 0b00000010; // 借款人
    const NOTICE_ROLE_AGENCY         = 0b00000100; // 担保方
    const NOTICE_ROLE_ADVISORY       = 0b00001000; // 咨询方
    const NOTICE_ROLE_ENTRUST_AGENCY = 0b00010000; // 委托方
    const NOTICE_ROLE_CANAL          = 0b00100000; // 资金渠道方


    // 还款方式对应的提醒次数和提前多少天进行提醒
    public static $nddLoanTypes = array(
        '3' => array(30,15,7,1), //到期支付本金收益 单位：月
        '5' => array(30,15,7,1), //到期支付本金收益 单位：天
        '2' => array(7,1), //按月等额本息还款
        '4' => array(7,1), //按月支付收益到期还本
    );

    // 借款方和担保方对应的短信模板
    public static $noticeRoleTpls = array(
        self::NOTICE_ROLE_BORROWER => "TPL_DEAL_DUN_BORROWER",
        self::NOTICE_ROLE_AGENCY => "TPL_DEAL_DUN_AGENCY",
    );

    // 短信签名为网信还是网信普惠
    public static $smsSigns = array(
        1,   // 签名为网信
        100, // 签名为网信普惠
    );

    // 配置某个产品类别的提醒次数，提醒天数，哪几方进行催款，短信签名
    // 如果没有配置短信签名，则获取标的对应上标站点，然后使用其签名
    //   如果是1，则发送短信是【网信】****短信内容*****。
    //   如果是100，则发送短信是【网信普惠】****短信内容*****。
    //   其余上标站点，比如上标站点是5,对应的站点名称是木兰贷,就会发送短信【网信】[木兰贷]****短信内容*****。
    public static $noticeRules = array();

    public function __construct(){
        // 农担贷的配置，以后有了其他产品类别的再进行配置
        $nddRole = self::NOTICE_ROLE_BORROWER + self::NOTICE_ROLE_AGENCY;
        self::$noticeRules[DealLoanTypeModel::TYPE_NDD]=array(
            'noticeRole'=>$nddRole,
            'loanType'=>self::$nddLoanTypes,
            'smsSign'=> 100,
        );
    }

    /**
     * 该标的是否需要发送催款短信
     * @param string $typeTag 产品类别的typeTag
     * @param int $loantype deal表的loantype
     * @param int $noticeDate 提前多少天提醒
     * @return boolean true|false
     */
    public function isSend($typeTag,$loantype,$noticeDate){
       if(empty($typeTag)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"typeTag为空 ")));
            return false;
        }
        if(empty($loantype)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"loantype为空 ")));
            return false;
        }
        if(empty($noticeDate)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"noticeDate为空 ")));
            return false;
        }

        $dunDate = self::$noticeRules[$typeTag]['loanType'][$loantype];
        if(empty($dunDate)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"没有找到指定配置 | typeTag:{$typeTag}  loantype:{$loantype}")));
            return false;
        }
        return in_array($noticeDate,$dunDate);
    }

    /**
     * 根据typeTag进行催款
     * @param string $typeTag 产品类别的typeTag
     * @return boolean true|false
     */
    public function noticeRepay($typeTag){
        if(empty(self::$noticeRules[$typeTag])){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"没有对该产品进行配置 typeTag:".$typeTag)));
            return false;
        }
        $typeId = DealLoanTypeModel::instance()->getIdByTag($typeTag);
        if(empty($typeId)){
            Logger::error(implode(" | ", array(__FILE__,__FUNCTION__,__LINE__,"没有这种产品类别 typeTag:".$typeTag)));
            return false;
        }
        $sql = "SELECT
                    d.`id`, d.`name`, d.`type_id`, d.`loantype`, d.`user_id`, d.`agency_id`,
                    d.`advisory_id`, d.`entrust_agency_id`, d.`canal_agency_id`, dr.`repay_time`,
                    dr.`repay_money`, dr.`principal`, dr.`interest`
                FROM
                    `firstp2p_deal` AS d LEFT JOIN `firstp2p_deal_repay` AS dr
                ON
                    d.id = dr.deal_id
                WHERE
                    d.`type_id` = %d  AND d.`deal_status` = 4 AND d.`parent_id` != 0
                    AND dr.`status` = 0 AND dr.`repay_time` BETWEEN %d AND %d ;" ;
        // 1 先按照30,15,7,1来查所有该中产品类别的还款中的标的
        $allNoticeDates = array(30,15,7,1);
        foreach($allNoticeDates as $v){
            //天数转化为time,来进行查询
            $selectSql = sprintf($sql, $typeId, (get_gmtime()+($v-1)*24*3600), (get_gmtime()+$v*24*3600));
            $deals = DealModel::instance()->findAllBySqlViaSlave($selectSql);
            foreach($deals as $deal){
                // 2 该标的是否符合发送催款短信的规则
                // 2.1 loantype和提前天数
                $isSend = $this->isSend($typeTag, $deal['loantype'], $v);
                if(!$isSend){
                    Logger::error(implode(" | ", array(__FILE__,__LINE__," isSend-fail type_id:{$deal['type_id']}".
                        "loantype: {$deal['loantype']}  noticeDate:{$v} deal_id:{$deal['id']}")));
                    continue;
                }
                Logger::info(implode(" | ", array(__FILE__,__LINE__,"isSend-success type_id:{$deal['type_id']}".
                    " loantype: {$deal['loantype']}  noticeDate:{$v} deal_id:{$deal['id']}")));

                // 2.2 deal_ext表里的need_repay_notice是否为1
                $needRepayNotice = DealExtModel::instance()->findBy("`deal_id` = " . $deal['id'], "need_repay_notice");
                if($needRepayNotice['need_repay_notice'] != 1){
                     Logger::error(implode(" | ", array(__FILE__,__LINE__," isSend-fail  need_repay_notice为0  type_id:{$deal['type_id']}".
                        "loantype: {$deal['loantype']}  noticeDate:{$v} deal_id:{$deal['id']}")));
                    continue;
                }

                // 3 根据配置，获取该种产品类别需要催款的各方,然后发送对应模板的短信
                $noticeRole = self::$noticeRules[$typeTag]['noticeRole'];

                // 3.1  获取发送模板中的变量：
                // 借款方
                if($noticeRole & self::NOTICE_ROLE_BORROWER){
                    // 3.2  获取发送模板
                    $tpl = self::$noticeRoleTpls[self::NOTICE_ROLE_BORROWER];
                    // 3.3  获取发送模板中的变量：
                    // notice:1-user_name,2-deal_name,3-repay_time_y,4-repay_time_m,5-repay_time_d,6-repay_money,7-principal,8-interest
                    $userInfo = UserModel::instance()->findBy("id = ".$deal['user_id']);
                    $userName = ($userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) ? get_company_name($userInfo['id']) : $userInfo['real_name'];
                    $notice = array(
                        $userName,
                        $deal['name'],
                        to_date($deal['repay_time'],"Y"),
                        to_date($deal['repay_time'],"m"),
                        to_date($deal['repay_time'],"d"),
                        round($deal['repay_money'],2),
                        round($deal['principal'],2),
                        round($deal['interest'],2),
                    );
                    // 3.4 获取站点
                    $siteId = self::$noticeRules[$typeTag]['smsSign'];
                    // 3.5 发送短信
                    if(empty($siteId)){
                        //如果没有配置签名，则获取标的对应上标站点，然后使用其签名
                        //如果是1，则发送短信是【网信】****短信内容*****。
                        //如果是100，则发送短信是【网信普惠】****短信内容*****。
                        //其余上标站点，比如上标站点是5,对应的站点名称是木兰贷,就会发送短信【网信】[木兰贷]****短信内容*****。
                        SmsServer::instance()->send($userInfo['mobile'], $tpl, $notice, $deal['user_id']);
                        Logger::info(implode(" | ", array(__FILE__,__LINE__,"send_sms_success_borrower | deal_id:{$deal['id']} user_id:{$deal['user_id']} ")));
                    }else{
                        SmsServer::instance()->send($userInfo['mobile'], $tpl, $notice, 0, $siteId);
                        Logger::info(implode(" | ", array(__FILE__,__LINE__,"send_sms_success_borrower | deal_id:{$deal['id']} user_id:{$deal['user_id']} ")));
                    }
                }
                //担保方
                if($noticeRole & self::NOTICE_ROLE_AGENCY ){
                    $tpl = self::$noticeRoleTpls[self::NOTICE_ROLE_AGENCY];
                    // notice:1-user_name,2-deal_name,3-borrower_user_name,4-repay_time_y,5-repay_time_m,6-repay_time_d,7-repay_money,8-principal,9-interest
                    $dealAgencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']);
                    $agencyUserInfo = UserModel::instance()->findViaSlave($dealAgencyInfo->agency_user_id, 'id,mobile,real_name');
                    $userInfo = UserModel::instance()->findBy("id = ".$deal['user_id']);
                    $userName = ($userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) ? get_company_name($userInfo['id']) : $userInfo['real_name'];
                    $notice = array(
                        $agencyUserInfo['real_name'],
                        $deal['name'],
                        $userName,
                        to_date($deal['repay_time'],"Y"),
                        to_date($deal['repay_time'],"m"),
                        to_date($deal['repay_time'],"d"),
                        round($deal['repay_money'],2),
                        round($deal['principal'],2),
                        round($deal['interest'],2),
                    );
                    $siteId = self::$noticeRules[$typeTag]['smsSign'];
                    if(empty($siteId)){
                        SmsServer::instance()->send($agencyUserInfo['mobile'], $tpl, $notice, $agencyUserInfo['id']);
                        Logger::info(implode(" | ", array(__FILE__,__LINE__,"send_sms_success_agency | deal_id:{$deal['id']} user_id:{$agencyUserInfo['id']} ")));
                    }else{
                        SmsServer::instance()->send($agencyUserInfo['mobile'], $tpl, $notice, 0, $siteId);
                        Logger::info(implode(" | ", array(__FILE__,__LINE__,"send_sms_success_agency | deal_id:{$deal['id']} user_id:{$agencyUserInfo['id']} ")));
                    }

                }
                //TODO 如果有其他机构需要发送端，配置一下 noticeRoleTpls，并且'noticeRole'增加对应机构方。
            }
        }
    }

}

if (!isset($argv[1])) {
    exit("请指定typeTag参数\n");
}

$typeTag = $argv[1];

$dunMessage = new DealRepayDunMessage();
$dunMessage->noticeRepay($typeTag);


?>
