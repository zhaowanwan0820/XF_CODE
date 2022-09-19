<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\ResponseUserCoupon;
use NCFGroup\Protos\Ptp\ResponseUserCouponInfo;
use core\service\UserTagService;
use core\service\CouponLogService;
use core\service\CouponService;
use NCFGroup\Protos\Ptp\RequestCoupon;
use core\service\UserService;
use core\service\BonusService;
use NCFGroup\Protos\Ptp\RequestRebate;
use openapi\lib\Tools;
use NCFGroup\Ptp\daos\AdunionDealDAO;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use core\service\CouponBindService;

/**
 * PtpCouponService
 * coupon相关service
 * @uses ServiceBase
 * @package default
 */
class PtpCouponService extends ServiceBase {

    public function consume(RequestCoupon $request){

        $response = array();
        $response['resCode'] = RPCErrorCode::SUCCESS;
        $response['resMsg'] = '操作成功';

        $coupon = $request->getCoupon();

        $type = $request->getType();
        if(!in_array($type,array(CouponLogService::MODULE_TYPE_DUOTOU,CouponLogService::MODULE_TYPE_P2P,CouponLogService::MODULE_TYPE_JIJIN,CouponLogService::MODULE_TYPE_GOLD,CouponLogService::MODULE_TYPE_GOLDC))){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '项目名称不正确';
            return $response;
        }

        $money = $request->getMoney();
        if($money <= 0.0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '投资金额不能小于等于0';
            return $response;
        }

        $userId = $request->getUserId();
        if($userId <= 0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '投资人id错误';
            return $response;
        }

        $dealId = $request->getDealid();
        if($dealId <= 0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '标ID不能为空';
            return $response;
        }

        $dealLoadId = $request->getDealLoadId();
        if($dealLoadId <= 0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '投资ID不能为空';
            return $response;
        }

        $repayStartTime = $request->getRepayStartTime();
        if($repayStartTime <= 0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '起息日不能为空';
            return $response;
        }
        $siteId = $request->getSiteId();
        if($siteId <= 0){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = 'siteId不能为空';
            return $response;
        }
        $coupon_fields = array();
        $coupon_fields['deal_id'] = $dealId;
        $coupon_fields['repay_start_time'] = $repayStartTime;
        $coupon_fields['money'] = $money;
        $coupon_fields['site_id'] = $siteId;

        //活期黄金专用
        $amount = $request->getAmount();
        if (!empty($amount)) {
            $coupon_fields['amount'] = $amount;
        }
        $price = $request->getPrice();
        if (!empty($price)) {
            $coupon_fields['price'] = $price;
        }

        $couponService = new CouponService($type);
        $ret = $couponService->consume($dealLoadId, $coupon, $userId, $coupon_fields, CouponService::COUPON_SYNCHRONOUS);
        if(empty($ret)){
            $response['resCode'] = RPCErrorCode::FAILD;
            $response['resMsg'] = '操作失败';
            return $response;
        }else{
            $response['coupon_log'] = $ret === true? array():$ret->_row;
        }

        return $response;
    }
    /**
     * 根据userId获取用户邀请码
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserCoupon(ProtoUser $request) {
        $userId = $request->getUserId();
        $idcardPassed = $request->getIdcardPassed();
        $isUsedCode = (new CouponService())->isCouponUsed($userId);
        //没有通过身份认证并且没有使用过
        if (($idcardPassed != 1) && !$isUsedCode) {
            $isNotCode = 1;
        }

        $coupons = (new CouponService())->getUserCoupons($userId);

        $response = new ResponseUserCoupon();
        if (empty($coupons) || count($coupons) < 1) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        $firstCoupon = array_slice($coupons, 0, 1);
        $shareContent = app_conf('COUPON_WEB_ACCOUNT_COUPON_PAGE_SHAREMSG');

        foreach ($firstCoupon as $k => $v) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setCoupon($k);
            $shareContent = empty($shareContent) ? '' : str_replace('{$COUPON}',$k ,$shareContent);
            $response->setIsNotCode(intval($isNotCode));
            $response->setRebateRatio(sprintf("%.2f", $v['rebate_ratio']));
            $response->setRefererRebateRatio(sprintf("%.2f", $v['referer_rebate_ratio']));
            $response->setShareContent((string) $shareContent);
        }
        return $response;
    }

    /**
     * 验证邀请码有效性
     * @param RequestCoupon $request
     */
    public function checkCoupon(RequestCoupon $request){
        $response = array();
        $response['resCode'] =  RPCErrorCode::SUCCESS;
        $response['resMsg'] = '查询成功';
        $coupon = $request->getCoupon();
        if (empty($coupon)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '邀请码不能为空';
            return $response;
        }
        $result = (new CouponService())->checkCoupon($coupon);
        if(empty($result)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '查询失败';
            return $response;
        }
        $response['data'] = $result;

        return $response;
    }

    /**
     * 通过邀请码获取邀请码信息
     * @param RequestCoupon $request
     */
    public function queryCoupon(RequestCoupon $request){
        $response = array();
        $response['resCode'] =  RPCErrorCode::SUCCESS;
        $response['resMsg'] = '查询成功';
        $coupon = $request->getCoupon();
        if (empty($coupon)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '邀请码不能为空';
            return $response;
        }

        $result = (new CouponService())->queryCoupon($coupon,true);
        if(empty($result)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '查询失败';
            return $response;
        }
        $response['data'] = $result;

        return $response;
    }

    /**
    * 获取用户邀请码以及该邀请码下面的所有信息
    */
    public function getUserCouponV2(ProtoUser $request){
        $userId = $request->getUserId();
        $type = $request->getType();
        $inviteeId = $request->getInviteeId();   // 被邀请人ID
        $idcardPassed = $request->getIdcardPassed();
        $isUsedCode = (new CouponService())->isCouponUsed($userId);
        //初始化返回proto
        $response = new ResponseUserCouponInfo();

        //没有通过身份认证并且没有使用过
        if (($idcardPassed != 1) && !$isUsedCode) {
            //如果没有通过显示，输入默认文案
            $newRegisterInviteRebateDefault = app_conf('NEW_REGISTER_INVITEE_REBATE_DEFAULT');
            $newRegisterRebateDefault = app_conf('NEW_REGISTER_REBATE_DEFAULT');
            $rebateProfit = number_format(10000 * $newRegisterRebateDefault, 0, '', '');
            $isNotCode = 1;
            $response->setIsNotCode($isNotCode);
        }

        // 判断是否为O2O的
        $isO2O = 0;
        $userTagService = new UserTagService();
        $isO2O_User = $userTagService->getTagByConstNameUserId('O2O_HY_USER',$userId);
        $isO2O_Seller = $userTagService->getTagByConstNameUserId('O2O_HY_USER',$userId);
        if($isO2O_User || $isO2O_Seller){
            $isO2O = 1;
        }
        $response->setIsO2O(intval($isO2O));
        //多个码子处理
        $couponService = new CouponService();
        $coupons = $couponService->getUserCoupons($userId);
        $coupon = array_shift($coupons);
        $shareMsg = app_conf('COUPON_WEB_ACCOUNT_COUPON_PAGE_SHAREMSG');
        $rebateInfo = $couponService->getRebateInfo($userId);
        if($rebateInfo["rebate_effect_days"] || !$rebateInfo["basic_group_id"])  {
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG",1);
        }else{
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG_NO_LIMIT",1);
        }
        $bonusService = new BonusService();
        $canShare = 0;
        if ($bonusService->isCashBonusSender($userId,1)) {//现金红包分享
            $canShare = 1;
            $shareMsg = app_conf("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS");
            //活动
            $bonusTitle = app_conf('CASH_BONUS_SHARE_TITLE');
            $bonusImg = app_conf('CASH_BONUS_SHARE_FACE');
        } else {
            $shareMsg = app_conf("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG");
            $bonusImg = "";
            $bonusTitle = "";
        }


        $response->setBonusTitle(urlencode($bonusTitle));
        $response->setBonusImg($bonusImg);
        $response->setCanShare(intval($canShare));
        $referer_rebate_ratio = sprintf("%.2f", $coupon['referer_rebate_ratio']);
        $response->setCoupon((string)$coupon['short_alias']);
        $response->setRebateRatio(sprintf("%.2f", $coupon['rebate_ratio']));
        $response->setRefererRebateRatio($referer_rebate_ratio);
        $response->setShareContent(urlencode(str_replace('{$COUPON}', $coupon['short_alias'], $shareMsg)));
          //优惠吗使用详情等信息
        $couponUsedList = $this->getCouponUsedList($userId,$type, $request->getPageNum(), $request->getPageSize(),$inviteeId);
        $response->setConsumeUserCount(intval($couponUsedList['data']['consume_user_count']));
        $response->setRefererRebateAmount(floatval($couponUsedList['data']['referer_rebate_amount']));
        $response->setRefererRebateAmountNo(floatval($couponUsedList['data']['referer_rebate_amount_no']));
        $response->setCouponModelTypes($couponUsedList['coupon_model_types']);
        //增加被邀请人的累计返利、待返返利、是否绑卡、是否投资
        if(!empty($inviteeId)){
            $response->setInviteeIsBank(intval($couponUsedList['isBindBank']));
            $response->setInviteeIsInvest(intval($couponUsedList['isInvest']));
            $response->setInviteeRebateAmount(floatval($couponUsedList['consume_rebate_amount']));
            $response->setInviteeRebateAmountNo(floatval($couponUsedList['consume_rebate_amount_no']));
        }

        if(!empty($referer_rebate_msg)) {
            $referer_rebate_msg = str_replace('{$referer_rebate_ratio}', "<span class='color-red2'>{$referer_rebate_ratio}</span>", $referer_rebate_msg);
        }
        $response->setRefererRebateMsg($referer_rebate_msg);
        if(empty($couponUsedList['list'])){
            $response->setList(array());
        }else{
            $response->setList($couponUsedList['list']);
        }

        //判断用户是否投资两次以上
        $isBidMore = (new UserTagService())->getTagByConstNameUserId('BID_MORE', $userId);
        $response->setBidMore(intval($isBidMore));
        return $response;
    }

    /**
     * 开放平台根据siteId、结算时间、结算状态调取邀请记录
     */
    public function getCouponListByOpen(RequestRebate $rebate){
        $couponLogService = new couponLogService();
        $dbStart = ($rebate->pageNum - 1) * $rebate->pageSize;
        $payStatus = $rebate->payStatus == 'all' ? false : intval($rebate->payStatus);
        $result = $couponLogService->getLogPaid ($rebate->type, $rebate->userId, $dbStart, $rebate->pageSize,'', '', '',  $rebate->siteId, '', $payStatus, $rebate->payTimeStart, $rebate->payTimeEnd);
        if(empty($result['data']) || empty($result['data']['list'])){
            return array();
        }
        return $result;
    }

    public function getCouponListByOpenNew(RequestRebate $rebate){
        $dbStart = ($rebate->pageNum - 1) * $rebate->pageSize;
        $payStatus = $rebate->payStatus == 'all' ? false : intval($rebate->payStatus);

        $couponLogService = new couponLogService();
        $result = $couponLogService->getP2PLogPaid($rebate->userId, array(
             'getResType'      => $rebate->getResType,
             'firstRow'        => $dbStart,
             'pageSize'        => $rebate->pageSize,
             'payStatus'       => $payStatus,
             'payTimeStart'    => $rebate->payTimeStart,
             'payTimeEnd'      => $rebate->payTimeEnd,
             'mobile'          => $rebate->mobile,
             'consumeUserId'   => $rebate->consumeUserId,
             'createTimeStart' => $rebate->createTimeStart,
             'createTimeEnd'   => $rebate->createTimeEnd,
         ));

         //根据deal_load_id 查询出euid
         //从firstp2p_adunion_deal表中，拉取euid信息
         if(!empty($result['data']['list'])){
             $consumeUserIds = array();
             foreach($result['data']['list'] as &$item){
                 $consumeUserIds[] = $item['consume_user_id'];
             }

             //进行查询
             $orders = AdunionDealDAO::getOrderInfoByUids($consumeUserIds);
             if(!empty($orders)){
                foreach($orders as $val){
                    $euids[$val['uid']] = $val['euid'];
                }
                foreach($result['data']['list'] as &$item){
                    $item['euid'] = empty($euids[$item['consume_user_id']]) ? '' : $euids[$item['consume_user_id']];
                }
             }

             $userService = new UserService();
             $userInfo = $userService->getUserInfoListByID($consumeUserIds);
             if (!empty($userInfo)) {
                 foreach($result['data']['list'] as &$item){
                     $item['realName'] = $userInfo[$item['consume_user_id']]['real_name'];
                 }
             }
          }

          return $result;
    }

    private function getCouponUsedList($userId,$type,$pageNum,$pageSize,$inviteeId = NULL){
        $couponLogService = new couponLogService();
        $dbStart = ($pageNum-1)*$pageSize;
        $result = $couponLogService->getLogPaid($type,$userId,$dbStart,$pageSize,NULL,NULL,NULL,NULL,$inviteeId);
        $list = $result['data']['list'];
        if(!empty($inviteeId) && $type === 'p2p'){//根据被 邀请人ID筛选返利记录及投资状态
            $ret['consume_rebate_amount'] = $result['data']['invest_data']['consume_rebate_result_amount'];
            $ret['consume_rebate_amount_no'] = $result['data']['invest_data']['consume_rebate_result_amount_no'];
            $inviteeStatus = $result['data']['invest_data']['invitee_status']['pay_status_no'];
            $ret['isBindBank'] = $inviteeStatus != CouponLogService::STATUS_BIND_BANK_NO ? 1 : 0;
            $ret['isInvest'] = $inviteeStatus != CouponLogService::STATUS_INVEST ? 0 : 1;
        }
        if($type){
            foreach ($list as $key => $item) {
                $list[$key]['sum_pay_refer_amount'] = $item['sum_pay_refer_amount'] ?: 0;

                if($type === 'reg'){
                    //验证被邀请人是否绑定银行卡及是否投资
                    $list[$key]['isBindBank'] = $item['pay_status_no'] != CouponLogService::STATUS_BIND_BANK_NO ? 1 : 0;
                    $list[$key]['isInvest'] = $item['pay_status_no'] != CouponLogService::STATUS_INVEST ? 0 : 1;
                    //被邀请人ID加密
                    $list[$key]['encode_consume_user_id'] = Tools::encryptID($item['consume_user_id']);
                }
            }
            $ret['list'] = $list;

            $totalRefererRebateAmount=$couponLogService->getTotalRefererRebateAmount($userId);
            $ret['data']['referer_rebate_amount'] = $totalRefererRebateAmount['referer_rebate_amount'];
            $ret['data']['referer_rebate_amount_no'] = $totalRefererRebateAmount['referer_rebate_amount_no'];

            $inviteNumber = $couponLogService->getTotalInviteNumber($userId);
            $ret['data']['consume_user_count'] =$inviteNumber;

        }else{//老接口 有文案问题的话叫用户更新新版本
            foreach($list as $item){
                $pay_status_text = '--';
                $note = '其他状态';
                $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
                if ($item['pay_status'] == CouponService::PAY_STATUS_NO_IDPASSED) {
                    $pay_status_text = '--';
                    $note = "被邀请人尚未实名认证及绑定银行卡";
                    $item['consume_user_name'] = moblieFormat($item['mobile']);//未绑卡的用户前端展示脱敏的手机号
                } else if ($item['pay_status'] == CouponService::PAY_STATUS_IDPASSED) {
                    $pay_status_text = '--';
                    $note = "被邀请人尚未绑定银行卡";
                } else if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID))) {
                    if ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                        $pay_status_text = '已返 <em>' . $item['count_pay'] . '</em> 次';
                        $pay_status_text .= ' 共计 <em>' . $item['sum_pay_refer_amount'] . '</em> 元';
                        $note = '邀请人已赎回 返利完成';
                    } else {
                        $pay_status_text = '已返 <em>' . $pay_money . '元</em>';
                        $note = '返利完成';
                    }
                } else if ($item['pay_status'] == CouponService::PAY_STATUS_PAYING) {
                    $pay_status_text = '已返 <em>' . $item['count_pay'] . '</em> 次';
                    $pay_status_text .= ' 共计 <em>' . $item['sum_pay_refer_amount'] . '</em> 元';
                    $note = '投资放款后每7天返利一次，直至赎回。';
                } else {
                    $pay_status_text = $item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND ? '待返 -- ' : '待返 ' . $pay_money .'元';
                    $note = $item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND ? '投资放款后每7天返利一次，直至赎回。' : "投资完成，预计15个工作日后获得返利";
                }

                if ($item['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                    $log_info = "{$item['consume_real_name']}受您邀请，投资通知贷项目，{$item['deal_name']}项目。投资：{$item['deal_load_money']}";
                } else {
                    $log_info = "{$item['consume_real_name']}受您邀请，投资{$item['deal_name']}项目。投资：{$item['deal_load_money']}，还款方式：{$item['repay_time']}{$item['loantype_time']}，{$item['loantype']}";
                }
                if ($item['repay_start_time']) {
                    $log_info .= "，起息日：" . $item['repay_start_time'];
                }
                $tmp = array();
                $tmp['type'] = $item['type'];
                $tmp['deal_type'] = $item['deal_type'];
                $tmp['pay_status'] = $item['pay_status'];
                $tmp['pay_time'] = $item['pay_time'];
                $tmp['pay_status_text'] = $pay_status_text;
                $tmp['note'] = $note;
                $tmp['log_info'] = $log_info;
                $tmp['consume_user_name'] = $item['consume_user_name'];
                $tmp['consume_real_name'] = $item['consume_real_name'];
                $tmp['create_time'] = $item['create_time'];
                $tmp['create_time_str'] = date('Y-m-d H:i:s',$item['create_time']);
                $ret['list'][] = $tmp;
            }
            $ret['data']['consume_user_count'] = $result['data']['consume_user_count'];
            $ret['data']['referer_rebate_amount'] = $result['data']['referer_rebate_amount'];
            $ret['data']['referer_rebate_amount_no'] = $result['data']['referer_rebate_amount_no'];
        }

        $ret['coupon_model_types'] = couponLogService::getModelTypes();
        return $ret;
    }

    public function getInviteCode(ProtoUser $request) {
        $userId = $request->getUserId();
        $couponService = new CouponService();
        $queryRes = $couponService->getOneUserCoupon($userId);

        $response = new ResponseUserCouponInfo();
        if (empty($queryRes)) {
           $response->resCode = false;
           $response->resMsg  = "找不到邀请码信息";
        } else {
            $response->resCode = true;
            $response->setRebateRatio(sprintf("%.2f", $queryRes['rebate_ratio']));
            $response->setCoupon($queryRes['short_alias']);
        }

        return $response;
    }

    /**
     *  获取最近使用邀请码
     * @param RequestCoupon $request
     */
    public function getCouponLatest(RequestCoupon $request){
        $response = array();
        $userId = $request->getUserId();
        if (empty($userId)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = 'userid 错误';
            return $response;
        }
        $couponLatest = (new CouponService())->getCouponLatest($userId);
        $response['short_alias'] = $couponLatest['short_alias'];
        $response['coupon'] = $couponLatest['coupon'];
        $response['is_fixed'] = $couponLatest['is_fixed'];

        return $response;
    }

/**
 * 通知贷赎回接口
 * $deal_load_id 投资id
 * $repay_days 返利天数
 */
    public function redeem(RequestCoupon $request){
        $response = array();
        $type = $request->getType();
        if(!in_array($type , array(CouponLogService::MODULE_TYPE_P2P, CouponLogService::MODULE_TYPE_DUOTOU))){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '项目名称 错误';
            return $response;
        }

        $deal_load_id = $request->getDealLoadId();
        if (empty($deal_load_id)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '投资id错误';
            return $response;
        }

        $deal_repay_time = $request->getDealRepayTime();
        if (empty($deal_repay_time)){
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '还款时间 不能为空';
            return $response;
        }

        $couponLogService = new CouponLogService($type);
        $result =$couponLogService->redeem($deal_load_id,$deal_repay_time);
        if($result)
        {
            $response['resCode'] =  RPCErrorCode::SUCCESS;
            $response['resMsg'] = '操作成功';
        }else{
            $response['resCode'] =  RPCErrorCode::FAILD;
            $response['resMsg'] = '操作失败';
        }

        return $response;
    }

    /**
     * 根据userId获取用户绑定的邀请码
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase; $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase
     */
    public function getBindCouponByUserid(SimpleRequestBase $request){
        $rObj = new ResponseBase();
        $rObj->bindCoupon = "";

        $par = $request->getParamArray();
        $userId = intval($par['user_id']);

        if(!empty($userId)){
            $oCBS = new CouponBindService();
            $aBindInfo = $oCBS->getByUserId($userId);
            if(!empty($aBindInfo['short_alias'])){
                $rObj->bindCoupon = $aBindInfo['short_alias'];
            }
        }

        return $rObj;
    }
}
