<?php
/**
 * 向指定天数 需要还款的借款人 发送还款提醒
 * 01 12 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php payment_request.php
 * @author wenyanlei 20140319
 */

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/utils/logger.php';

use app\models\dao\User;
use app\models\dao\Deal;
use app\models\dao\DealRepay;
use core\service\DealService;
use core\dao\DealLoanTypeModel;
use libs\utils\Aes;
use libs\sms\SmsServer;

set_time_limit(0);

$ds = new DealService();
$deal_all = $ds->getNoticeRepay();

$num = 0;
$error_msg = '';

if($deal_all){

    $Msgcenter = new Msgcenter();

    foreach($deal_all as $deal_one){

        $next_repay_info = DealRepay::instance()->findBy("deal_id = ".$deal_one->id." AND repay_time ='".$deal_one->next_repay_time."' AND true_repay_time = 0 AND status = 0 ORDER BY repay_time ASC LIMIT 1");

        if($next_repay_info){

            $user_info = User::instance()->findBy("id = ".$deal_one->user_id);

            //发送短信和邮件
            if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
            {
                $_mobile = 'enterprise';
                $userName = get_company_shortname($user_info['id']); // by fanjingwen
            } else {
                $_mobile = $user_info['mobile'];
                $userName = $user_info['user_name'];
            }
            $notice = array();
            $notice['user_name'] = $userName;
            $notice['deal_name'] = $deal_one->name;
            $notice['repay_time_y'] = to_date($deal_one->next_repay_time,"Y");
            $notice['repay_time_m'] = to_date($deal_one->next_repay_time,"m");
            $notice['repay_time_d'] = to_date($deal_one->next_repay_time,"d");
            $notice['repay_money'] = round($next_repay_info->repay_money,2);

            //消费贷,掌众-闪电消费,现金贷功夫贷,现金贷闪信贷,现金贷车贷通,现金贷优易借,掌众50天-闪电消费(线上),东风贷,汇达贷,产融贷,经易贷,个人租房分期 不发三日内还款通知短信
            //农担支农贷有另外的脚本发催款短信，在此屏蔽短信
            if(!$ds->isDealOfDealTypeList($deal_one->id, [DealLoanTypeModel::TYPE_XFD, DealLoanTypeModel::TYPE_ZHANGZHONG, DealLoanTypeModel::TYPE_CR,
                DealLoanTypeModel::TYPE_XJDGFD, DealLoanTypeModel::TYPE_XSJK, DealLoanTypeModel::TYPE_XJDCDT, DealLoanTypeModel::TYPE_NDD,
                DealLoanTypeModel::TYPE_XJDYYJ, DealLoanTypeModel::TYPE_ZZJRXS, DealLoanTypeModel::TYPE_DFD, DealLoanTypeModel::TYPE_HDD,
                DealLoanTypeModel::TYPE_CRDJYD, DealLoanTypeModel::TYPE_GRZFFQ])) {
                SmsServer::instance()->send($_mobile, 'TPL_DEAL_THREE_SMS', $notice, $deal_one->user_id, get_deal_siteid($deal_one->id));
            }

            //产融贷、消费贷、车贷车贷通、功夫贷 只有这些产品类别发送邮件
            if(!$ds->isDealOfDealTypeList($deal_one->id, [DealLoanTypeModel::TYPE_CR, DealLoanTypeModel::TYPE_XFD, DealLoanTypeModel::TYPE_XJDGFD, DealLoanTypeModel::TYPE_XJDCDT])) {
                continue;
            }

            $notice['deal_url'] = get_deal_domain($deal_one->id).'/d/'.Aes::encryptForDeal($deal_one->id);
            $notice['repay_url'] = get_deal_domain($deal_one->id).'/uc_deal-refund';
            $notice['help_url'] = get_deal_domain($deal_one->id).'/helpcenter';
            $notice['site_name'] = app_conf("SHOP_TITLE");

            $notice_title = "还款通知";
            $num = $Msgcenter->setMsg($user_info->email, $deal_one->user_id, $notice, 'TPL_DEAL_THREE_EMAIL',$notice_title,'',get_deal_domain_title($deal_one->id));
            //记录发送日志
            $log = array(
                '标题' => $notice_title.'已发送 -- 短信通知',
                '时间' => date('Y-m-d H:i:s',time()),
                '借款标题' => $deal_one->name.' -- id:'.$deal_one->id,
                '接收用户' => $notice['user_name'],
                '接收地址' => '手机号：'.$user_info['mobile']
            );
            logger::wLog($log);//记录短信

            $log['标题'] = $notice_title.'已发送 -- 邮件通知';
            $log['接收地址'] = '邮箱：'.$user_info['email'];
            logger::wLog($log);//记录邮件
        }else{
            $error_msg .= '借款id:'.$deal_one->id." 无对应的还款记录 \n";
        }
    }

    $Msgcenter->save();
}
echo 'END..',count($deal_all),' deals, ',$num," messages \n",$error_msg;
?>
