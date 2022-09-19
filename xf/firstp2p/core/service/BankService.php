<?php
/**
 * BankService class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace core\service;

use core\dao\BankChargeAuxiliaryModel;
use core\dao\BankChargeModel;
use core\dao\BankModel;
use core\dao\UserModel;
use core\dao\DealOrderModel;
use core\dao\PaymentModel;
use core\dao\PaymentNoticeModel;
use core\dao\DeliveryRegionModel;
use core\dao\UserBankcardAuditModel;
use core\dao\UserBankcardModel;
use core\dao\AttachmentModel;
use core\service\UserService;
use core\service\O2OService;
use core\service\AccountService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\sms\SmsServer;

/**
 * BankService
 */
class BankService extends BaseService {

    /**
     * 获取银行信息
     *
     * @return BankModel
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function getBank($bank_id, $fields = '*') {
        return BankModel::instance()->find($bank_id, $fields, true);
    }

    /**
     * 获取所有的绑卡银行列表
     * @return array
     * @author 文岭<liwenling@ucfgroup.com>
     */
    public function bankList(){
        return BankModel::instance()->getAllByStatusOrderByRecSortId(0,true);
    }

    /**
     * 获取银行列表  充值银行卡
     *
     * @return array
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function getBankList() {
        require_once(APP_ROOT_PATH . "system/payment/Yeepay_payment.php");
        $payment = new \Yeepay_payment();
        return $payment->get_bank_list();
    }

    /**
     *  获取银行列表  用户银行卡
     * @param int $status
     */
    public function getBankUserByStatus($status){
        return BankModel::instance()->getAllByStatusOrderByRecSortId($status);
    }
    public function getBankUserByPaymentMethod() {
        return BankModel::instance()->getBankList(' is_rec = 1 AND status = 0 ORDER BY sort ASC ');
    }
    /**
     * 获取用户的充值记录
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getUserOrder($user_id, $page_size, $page=1) {
        $result = DealOrderModel::instance()->getDealOrderByUserId($user_id, $page_size, $page);
        foreach ($result['list'] as $k => $v) {
            $result['list'][$k]['payment_notice'] = PaymentNoticeModel::instance()->getListByOrder($v['id']);
            $result['list'][$k]['payment'] = PaymentModel::instance()->find($v['payment_id']);
        }
        return $result;
    }

    /**
     * 获取配置附属额度信息
     * @param int $charge_id
     * @return array
     */
    public function getAuxiliary($charge_id) {
        if(!empty($charge_id)) {
            $charge = BankChargeModel::instance()->find($charge_id);
            $list['name'] = $charge['name'];
            $result = BankChargeAuxiliaryModel::instance()->getListByCharge($charge_id);
            $list['total'] = count($result);
            $list['list'] = $result;
        } else {
            $list = array(
                'name' => '',
                'total' => 0,
                'list' => array(),
            );
        }
        return $list;
    }

    /**
     * 获取银行对于的 地区
     * @param number $level
     */
    public function getRegion($level = 1){
        //获取用户银行卡信息
        return DeliveryRegionModel::instance()->getRegionsByLevel($level);
    }
    /**
     * 获取用户 银行卡信息
     * @param unknown $uid
     */
    public function userBank($uid,$is_new=false){

        if(!$uid){
            return null;
        }
        $bankcard_info = UserBankcardModel::instance()->getOneCardByUser($uid);
        $bankcard_info['user_bank_id'] = $bankcard_info['id'];
        if($is_new){
                if(empty($bankcard_info['card_name'])){
                $bankcard_info['card_name'] = $GLOBALS['user_info']['real_name'];//get_user("real_name", $uid);
            }
            $bankcard_info['bankzone'] = str_replace(array('<','>'),array('&lt;','&gt;'),$bankcard_info['bankzone']);
            return $bankcard_info;
        }
        //获取用户银行卡信息
        $bankcard = array();
        $r = UserBankcardAuditModel::instance()->getlatestCardAuditByUser($uid);
        $r['user_bank_id'] = $bankcard_info['id'];
        $bankcard = $r;
        $bankcard['card_name'] = $GLOBALS['user_info']['real_name'];//get_user("real_name", $uid);
        if(!$r) {
            $bankcard_info  = $bankcard_info;
        }
        if(!$bankcard['card_name']){
            $bankcard['card_name'] = $GLOBALS['user_info']['real_name'];//get_user("real_name", $uid);
        }
        $bankcard['bankzone'] = str_replace(array('<','>'),array('&lt;','&gt;'),$bankcard['bankzone']);
        return $bankcard;
    }


    /**
     * 换卡接口，直接绑定为可用银行卡，不进审核
     * @param array $data 待绑定银行卡数据
     * @param integer $bankcard_id 用户银行卡ID , 0 新增1修改
     * @return boolean
     */
    public function modifyBankcard($data, $bankcard_id) {
        $data['status'] = 1;  // 状态设置为1后前台不允许再修改

        //已有银行卡，完善信息
        if($bankcard_id){
            $data['update_time'] = get_gmtime();
            $flag = UserBankcardModel::instance()->updateCard($bankcard_id, $data);
            return $flag;
        //添加银行卡
        } else {
            $data['create_time'] = get_gmtime();
            return $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard",$data,"INSERT");
        }
    }

    /**
     * 存储 用户审核银行卡
     * @param unknown $data
     * @param unknown $isnew
     * @return boolean
     */
    public function saveBank($data,$isnew = false){
        if(!$data){
            return false;
        }

        $uid = $data['user_id'];
        if(!$uid){
            return false;
        }

        unset($data['id']);
        // 获取该用户绑定的银行卡信息(必须走主库)
        $bankcard_info = UserBankcardModel::instance()->getOneCardByUser($uid, false);
        if($isnew){
            $data['status'] = 1;  // 状态设置为1后前台不允许再修改

            //已有银行卡，完善信息
            if($bankcard_info){
                $data['update_time'] = get_gmtime();
                $flag = UserBankcardModel::instance()->updateCard($bankcard_info['id'], $data);
                return $flag;
            //添加银行卡
            } else {
                $GLOBALS['db']->startTrans();
                try {
                    $data['create_time'] = get_gmtime();
                    $res = $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard",$data,"INSERT");
                    if (!$res) {
                        throw new \Exception('绑卡失败');
                    }

                    O2OService::triggerO2OOrder($data['user_id'], CouponGroupEnum::TRIGGER_FIRST_BINDCARD);
                    $GLOBALS['db']->commit();
                } catch (\Exception $e) {
                    \logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $data['user_id'], "fail ".$e->getMessage())));
                    $GLOBALS['db']->rollback();
                    return false;
                }
            }
            return true;
        }else{
            // 防止之前传过来的 user_bank_id 被篡改 使用 系统查询的结果
            $data['card_name'] = $bankcard_info['card_name'];
            $data['user_bank_id'] = $bankcard_info['id'];
            $data['create_time'] = get_gmtime();
            //申请时总资产
            $accountService = new AccountService();
            $totalAssets = $accountService->getUserTotalAssets($uid);
            $data['total_assets'] = $totalAssets;
            unset($data['id']);
            //短信通知
            if(app_conf("SMS_ON") == 1){
                $tpl = 'TPL_SMS_USER_BIND_BANK_APPLY';
                $params = array(
                            'time' => to_date($data['create_time'], 'm-d H:i'),
                        );
                // SMSSend 修改银行卡 企业用户不可以在前台修改银行卡
                SmsServer::instance()->send($GLOBALS['user_info']['mobile'], $tpl, $params, $GLOBALS['user_info']['id']);
            }
            return $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard_audit",$data,"INSERT");
        }
    }

    /**
     * 用户银行卡修改 只能修改 开户行和地区 不能修改卡号
     * @author changlu
     * @param $uid
     * @param $data
     * @return mixed
     */
    public function eBank($uid,$data){
        $bankcard_info = UserBankcardModel::instance()->getOneCardByUser($uid);
        //不允许通过这种方式修改银行卡号 和开户人姓名
        unset($data['bankcard']);
        unset($data['card_name']);
        if($bankcard_info){
            $data['update_time'] = get_gmtime();
            return UserBankcardModel::instance()->updateCard($bankcard_info['id'], $data);
        } else{
            $data['user_id'] = $uid;
            $data['create_time'] = get_gmtime();
            return $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard",$data,"INSERT");
        }
    }

    /**
     * 根据bank_id获取充值信息
     * @param int $bank_id
     * @return array
     */
    public function getBankCharge($bank_id) {
        $result = BankChargeModel::instance()->getChargeByValue($bank_id);
        return $result;

    }

    /**
     * getBankChargeByName
     * 根据银行名字返回支持体现银行卡信息，里面有银行icon
     *
     * @param mixed $name
     * @access public
     * @return void
     */
    public function getBankChargeByName($name) {
        $result = BankChargeModel::instance()->getChargeByName($name);
        return $result;
    }

    /**
     * getAllByStatusOrderByRecSortId
     * 获取银行卡列表
     *
     * @param int $status
     * @access public
     * @return void
     */
    public function getAllByStatusOrderByRecSortId($status=0){
        return BankModel::instance()->getAllByStatusOrderByRecSortId('0');
    }

    /**
     * cardCanBind
     * 判断指定银行卡可否被该uid绑定，是否没被其他用户占用
     *
     * @param mixed $bankcard
     * @param mixed $uid
     * @access public
     * @return void
     */
    public function canBankcardBind($bankcard, $uid) {
        $userModel = new UserModel();
        $deleteUids = $userModel->getDeleteUids();
        $uids = array_merge($deleteUids, array($uid));
        $bankcardModel = new UserBankcardModel();
        $usedTtl = $bankcardModel->usedByOthers($bankcard, $uids);
        if ($usedTtl > 0) {
            return false;
        }
        $auditModel = new UserBankcardAuditModel();
        $auditTtl = $auditModel->usedByOthers($bankcard, $uids);
        if ($auditTtl > 0) {
            return false;
        }
        return true;
    }

    /**
     * 用户提交或修改银行卡时 验证是否可以提交（是否已被其他用户绑定）
     *
     * @param $cardnum 银行卡号
     * @param $user_id 提交银行卡用户id
     * @param $is_new 1:添加银行卡  0：修改银行卡
     * @return array
     */
    public function checkBankCardCanBind($cardnum, $user_id){

        $del_users = UserModel::instance()->findAll("is_delete = 1", true, 'id');

        $userid_arr = $del_users ? array_map('array_shift', $del_users) : array();
        $userid_arr[] = $user_id;

        $userids = implode(',', array_unique($userid_arr));

        $card_count = UserBankcardModel::instance()->getCountByCardNum($cardnum, $userids);
        if($card_count > 0){
            return false;
        }
        $audit_count = UserBankcardAuditModel::instance()->getCountByCardNum($cardnum, $userids);
        if($audit_count > 0){
            return false;
        }
        return true;
    }

    /**
     * 银行卡相关字段的检测和过滤
     * @param array $data 银行卡信息
     * @return array
     */
    public function bankInfoXssFilter($data) {
        if (isset($data['bankzone'])) {
            $data['bankzone'] = htmlspecialchars($data['bankzone']);
        }
        if (isset($data['bankzone_1'])) {
            $data['bankzone_1'] = htmlspecialchars($data['bankzone_1']);
        }
        if (isset($data['card_name'])) {
            $data['card_name'] = htmlspecialchars($data['card_name']);
        }
        if (isset($data['bankcard'])) {
            $data['bankcard'] = htmlspecialchars($data['bankcard']);
        }
        if (isset($data['reBankcard'])) {
            $data['reBankcard'] = htmlspecialchars($data['reBankcard']);
        }


        return $data;
    }

    // 可以不填写支行的银行id
    public function hideExtraBanks() {
        $bank_list = $this->getBankUserByPaymentMethod();
        $hideExtra = true;
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                $hideExtraBanks[] = $bank['id'];
            }
        }
        $hideExtraBanks['banklist'] = $bank_list;
        return $hideExtraBanks;
    }

    // 是否需要显示额外的银行信息
    public function isHideExtraBank($id) {
        $banklist = $this->hideExtraBanks();
        unset($banklist['banklist']);
        if (in_array($id, $banklist)) {
            return true;
        }
        return false;
    }
    /**
     * 获取快捷银行卡列表
     */
    public function getFastPayBanks() {
        $ret = array();
        try {
            $result = \libs\utils\PaymentGatewayApi::instance()->request('banklist');
            if ($result['respCode'] != '00') {
                throw new \Exception($result['respMsg']);
            }
            foreach ($result['bankLimit'] as $k => $bank) {
                $bank_id = $GLOBALS['db']->getOne("SELECT id FROM firstp2p_bank WHERE short_name = '{$bank['bankCode']}'");
                if (empty($bank_id)) { unset($result['bankLimit'][$k]); continue; }
                $result['bankLimit'][$k]['bank_id'] = $bank_id;
            }
            $ret = array_values($result['bankLimit']);
        }
        catch (\Exception $e) {
            \libs\utils\PaymentApi::log('GetFastPayBankList failed:'.$e->getMessage());
            return array('status' => -1, 'msg' => $e->getMessage());
        }
        return array('status' => 0, 'msg' => '', 'data' => $ret);
    }

    public function getBankLogo($bankId) {
        $bankInfo = BankModel::instance()->find($bankId);
        if (empty($bankInfo['img'])) {
            return null;
        }
        $imgId = $bankInfo['img'];
        $attachInfo = AttachmentModel::instance()->find($imgId);
        if (empty($attachInfo['attachment'])) {
            return null;
        }
        return app_conf('STATIC_HOST').'/'.$attachInfo['attachment'];
    }

    /**
     * 通过银行编码获取银行信息
     **/
    public function getBankByCode($shortName) {
        return BankModel::instance()->getBankByCode($shortName);
    }

    /**
     * 获取该银行的新logo、背景图等信息
     * @param int $bankId
     * @param string $field
     * @return NULL|string
     */
    public function getBankImgUrl($bankId, $field = 'logo_id') {
        $list = [];
        $bankInfo = BankModel::instance()->find($bankId);
        if (empty($bankInfo)) {
            return $list;
        }

        $fieldList = explode(',', $field);
        foreach ($fieldList as $item) {
            if (empty($bankInfo[$item])) {
                continue;
            }

            $attachId = $bankInfo[$item];
            $attachInfo = AttachmentModel::instance()->find($attachId);
            if (empty($attachInfo['attachment'])) {
                $list[$item] = '';
            } else {
                $list[$item] = get_attr($attachId, 1, false);
            }
        }
        return $list;
    }

    /**
     * 获取银行信息，包含logo等
     */
    public function getBankInfo($bankId) {
        $bankInfo = BankModel::instance()->find($bankId);
        $bankInfo['logo'] = null;
        if (!empty($bankInfo['img'])) {
            $imgId = $bankInfo['img'];
            $attachInfo = AttachmentModel::instance()->find($imgId);
            if (!empty($attachInfo['attachment'])) {
                $bankInfo['logo'] = app_conf('STATIC_HOST').'/'.$attachInfo['attachment'];
            }
        }
        return $bankInfo;
    }

    /**
     * 获取该银行新版的Icon
     * @param string $bankCode
     * @param string $field
     * @return NULL|string
     */
    public function getBankNewIconUrl($bankCode, $field = 'icon_id') {
        $list = [];
        // 通过银行编码获取银行信息
        $bankInfo = $this->getBankByCode($bankCode);
        if (empty($bankInfo)) {
            return $list;
        }

        $fieldList = explode(',', $field);
        foreach ($fieldList as $item) {
            if (empty($bankInfo[$item])) {
                continue;
            }

            $attachId = $bankInfo[$item];
            $attachUrl = get_attr($attachId, 1, false);
            $list[$item] = !empty($attachUrl) ? $attachUrl : '';
        }
        return $list;
    }
}