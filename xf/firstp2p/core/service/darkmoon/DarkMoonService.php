<?php
/**
 *
 * @author xiaoan
 */

namespace core\service\darkmoon;

use core\service\BaseService;
use libs\mail\Mail;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use libs\utils\Logger;
use core\service\darkmoon\ContractService;
use core\dao\UserModel;
use libs\sms\SmsServer;
use core\service\CouponService;
use core\service\CouponLogService;
use libs\utils\Alarm;
use core\dao\JobsModel;

\FP::import("libs.libs.msgcenter");

class DarkMoonService extends BaseService {

    /**
     * 获取用户是否需要签合同
     * @param $user_id
     * @return boole
     */
    public function isSignUserContract($idno){

        if (!is_numeric($idno)){
            return false;
        }

        $dark_moon_model = new DarkmoonDealLoadModel();

        return $dark_moon_model->isSignUserContract($idno);
    }

    /**
     * 按标的生成邀请返利记录
     */
    public function couponConsumeByDealId($deal_id) {
        $deal_load_model = new DarkmoonDealLoadModel();
        $list = $deal_load_model->getByDealId($deal_id, DarkmoonDealLoadModel::SIGN_ALREADY_STATUS);
        //$list = $deal_load_model->getByDealId($deal_id);//测试用
        $deal = DarkmoonDealModel::instance()->find($deal_id);
        if (empty($deal) || empty($list)) {
            return false;
        }
        $couponService = new CouponService(CouponLogService::MODULE_TYPE_DARKMOON);
        try{
            $GLOBALS['db']->startTrans();
            foreach ($list as $item) {
                $coupon_fields['deal_id'] = $item['deal_id'];
                $coupon_fields['deal_load_id'] = $item['id'];
                $coupon_fields['money'] = $item['money'];
                $coupon_fields['loantype'] = $deal['loantype'];
                $coupon_fields['repay_time'] = $deal['repay_time'];
                $coupon_fields['money'] = $item['money'];
                $result=$couponService->consumeSynchronous($item['id'], '', $item['user_id'], $coupon_fields);
                if(!$result){
                    throw new \Exception("生成邀请返利记录失败,deal_id : ".$deal_id." , deal_load_id : ".$item['id']);
                }
            }
            $GLOBALS['db']->commit();
            return true;
        }catch(\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"exception:" . $e->getMessage())));
            return false;
        }
    }

    /**
     * 根据投标id更新邀请人信息，优先以绑定理财师为准
     */
    public function updateReferUserByDealLoadId($deal_load_id) {
        $log_info = array(__CLASS__, __FUNCTION__, $deal_load_id);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $deal_load = DarkmoonDealLoadModel::instance()->find($deal_load_id);
        if (empty($deal_load)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty deal_load'))));
            return false;
        }
        $coupon = false;
        if (!empty($deal_load['user_id'])) {
            $couponBindService = new \core\service\CouponBindService();
            $coupon = $couponBindService->getByUserId($deal_load['user_id'] );
        }
        if ((empty($coupon) || empty($coupon['short_alias'])) && !empty($deal_load['short_alias_csv'])) {
            $couponService = new CouponService(CouponLogService::MODULE_TYPE_DARKMOON);
            $coupon = $couponService->checkCoupon($deal_load['short_alias_csv']);
        }
        if (empty($coupon)) {
            Logger::info(implode(" | ", array_merge($log_info, array($deal_load['user_id'], $deal_load['short_alias_csv'], 'empty coupon'))));
            return false;
        }

        $deal_load->short_alias = $coupon['short_alias'];
        $deal_load->refer_user_id = $coupon['refer_user_id'];
        $rs = $deal_load->save();
        Logger::info(implode(" | ", array_merge($log_info, array($deal_load['user_id'], $deal_load['short_alias_csv'], json_encode($coupon), ($rs ? 'success' : 'fail')))));
        return $rs;
    }

    /**
     * 批量更新生成投资合同
     */
    public function sendConsumeUserSignContract($user_id,$idNo,$deal_id,$fields='id'){

        if (empty($user_id) || empty($idNo) || empty($deal_id) || empty($fields)){
            return false;
        }

        $deal_load_model = new DarkmoonDealLoadModel();
        $list = $deal_load_model->getAllByIdnoDealId($idNo,$deal_id,'id,status');
        if (empty($list)){
            return false;
        }
        // 发送合同
        $contract_service = new ContractService();
        foreach($list as $key => $v){
            if ($v['status'] != DarkmoonDealLoadModel::SIGN_WAIT_STATUS) continue;
            $ret = $contract_service->sendLoanContract($v['id']);
            if (!empty($ret)){
                $ret = $deal_load_model->updateSignStatus($v['id']);
                if (empty($ret)){
                    Logger::error(__CLASS__.' '.__FUNCTION__.' load id'.$v['id'].' update status 2');
                    Alarm::push('DARKMOON','更新投资人签署状态失败',' loadid '.$v['id'].' user_id '.$user_id);
                }
            }else{
                Alarm::push('DARKMOON','投资人签署合同失败',' loadid '.$v['id'].' user_id '.$user_id);
                return false;
            }
        }
        // 如果投资人全部签署给借款人发短信
        $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
        $count = $darkmoonDealLoadModel->getUnsignCount($deal_id);
        if(intval($count) == 0){
            $this->sendBorrowSms($deal_id);
        }
        return true;
    }

    public function sendBorrowSms($dealId){

        if (empty($dealId)){
            return false;
        }

        $deal_model = new DarkmoonDealModel();
        $dealInfo = $deal_model->getInfoById($dealId,'deal_status,user_id,jys_record_number');
        if (empty($dealInfo['deal_status']) || $dealInfo['deal_status'] != DarkmoonDealModel::DEAL_SIGNING_STATUS){
            return false;
        }

        $userModel = new UserModel();
        $userInfo = $userModel->find($dealInfo['user_id'],'mobile',true);
        if (empty($userInfo['mobile'])){
            return false;
        }
         $darkmoon_url =  app_conf("DARKMOON_HOST");
        $msg = array(
            'jys_number' => $dealInfo['jys_record_number'],
            'var2' => $darkmoon_url.'/exchange?id='.intval($dealId),
        );

        $ret = SmsServer::instance()->send($userInfo['mobile'],'TPL_SMS_BORROW_CTCT_RPT',$msg,null,app_conf('QYGJ_SITE_ID'));

        Logger::info(__CLASS__.' '.__FUNCTION__.$dealId.'sms rs '.json_encode($ret). ' done ');
    }
    /**
     * 批量更新投资用户id
     */
    public function updateBatchUserId($user_id,$idNo,$deal_id){
        if (empty($user_id) || empty($idNo) || empty($deal_id)){
            return false;
        }
        $deal_load_model = new DarkmoonDealLoadModel();
        $list = $deal_load_model->getAllByIdnoDealId($idNo,$deal_id,'id,user_id');
        if (empty($list)){
            return false;
        }
        $ret = true;
        $GLOBALS['db']->startTrans();
        try {
            // 更新userid
            foreach ($list as $key => $v) {
                if (empty($v['user_id'])) {
                    $ret = $deal_load_model->updateLoadUserInfo($v['id'], $user_id);
                    //更新邀请人信息
                    $darkMoonService = new \core\service\darkmoon\DarkMoonService();
                    $darkMoonService->updateReferUserByDealLoadId($v['id']);
                    if (empty($ret)) {
                        throw new \Exception('deal load update user id fail '.$v['id'].' '.$user_id);
                    }
                }
            }

            $GLOBALS['db']->commit();
            return true;
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            Alarm::push('DARKMOON','批量更新user_is',$e->getMessage());
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
            return false;
        }

        return true;
    }

    public function isTimeStampFinishedByDealId($deal_id) {
        $deal = DarkmoonDealModel::instance()->getInfoById($deal_id);
        if(intval($deal['deal_status'])==4){
            return true;
        }
        return false;
    }

    public function finishTimeStamp($deal_id){
        $deal = DarkmoonDealModel::instance()->find($deal_id);
        $deal->deal_status=4;
        return $deal->save();
    }

    public function sendEmailByDealId($deal_id) {
        $deal_load_model = new DarkmoonDealLoadModel();
        $list = $deal_load_model->getByDealId($deal_id, DarkmoonDealLoadModel::SIGN_ALREADY_STATUS);
        $deal = DarkmoonDealModel::instance()->find($deal_id);
        if (empty($deal) || empty($list)) {
            return false;
        }
        $userModel=new \core\dao\UserModel ();
        foreach ($list as $item) {
            $userInfo = $userModel->find($item['user_id']);
            if(empty($userInfo['email_sub'])){
                continue;
            }
            $params['email_sub'] = $userInfo['email_sub'];
            $params['jys_record_number'] = $deal['jys_record_number'];
            $params['user_name'] = $userInfo['real_name'];
            $params['id'] = $item['id'];
            $params['deal_id'] = $deal_id;
            $function = 'core\service\darkmoon\DarkMoonService::jobsSendEmailCallback';
            $param = array('params'=>$params);
            $job_model = new JobsModel();
            $job_model->priority = JobsModel::PRIORITY_DARKMOON_EMAIL_SEND;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                return false;
            }
        }
        return true;
    }

    public function jobsSendEmailCallback($params){
        $contract= new ContractService();
        $mail = new Mail();
        $attachment=array();
        $mail->setFrom('guanjia@ucfgroup.com', '签约管家');
        $pdf=$contract->getContractListByDealLoadId($params['id']);
        foreach($pdf as $k=>$v){
            $path=$contract->createPdfTsa($v['id'],$params['deal_id']);
            if(!$path){
                continue;
            }
            $attachment[$k]['path']=$path;
            $attachment[$k]['name']=$v['title'].".pdf";
        }
        $title="{$params['jys_record_number']}的合同下发";
        $content ="<p>尊敬的{$params['user_name']}女士/先生，&nbsp;</p>\r\n<p>　　&nbsp;{$params['jys_record_number']}的合同已经下发，感谢您使用我们的服务。</p>\r\n<p>　网信众汇</p>\r\n<p>&nbsp;注：此邮件由系统自动发送，请勿回复！</p>\r\n";
        $result = $mail->send($title, $content, $params['email_sub'],$attachment);
        return $result;

    }

    public function sendDealLoadSms($dealId){

        if (empty($dealId)){
            return false;
        }

        $deal_model = new DarkmoonDealModel();
        $dealInfo = $deal_model->getInfoById($dealId,'deal_status,jys_record_number');
        if (empty($dealInfo['deal_status']) || $dealInfo['deal_status'] != DarkmoonDealModel::STATUS_DEAL_COMPLETE){
            return false;
        }

        $deal_load_model = new DarkmoonDealLoadModel();

        $deal_load_list = $deal_load_model->getByDealId($dealId);

        if (empty($deal_load_list)){
            return true;
        }
        foreach($deal_load_list as $v){
            if ($v['status'] == DarkmoonDealLoadModel::SIGN_DISCARD_STATUS) continue;
            if (empty($v['user_id'])) continue;
            $userModel = new UserModel();
            $userInfo = $userModel->find($v['user_id'],'email',true);
            if (empty($userInfo['email'])) continue;

            $msg = array(
                'jys_number' => $dealInfo['jys_record_number'],
                'email' => $userInfo['email'],
            );

           $ret = SmsServer::instance()->send($v['mobile'],'TPL_SMS_CTCT_RPT',$msg,null,app_conf('QYGJ_SITE_ID'));
        }
        return true;
    }

}
