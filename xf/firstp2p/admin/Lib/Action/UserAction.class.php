<?php

// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");

use core\service\AccountService;
use core\service\user\BOBase;
use core\service\UserService;
use core\service\UserBankcardService;
use core\service\PaymentService;
use core\service\CouponService;
use core\service\CouponLogService;
use core\service\AccountAuthorizationService;
use core\service\PaymentUserAccountService;
use core\dao\FinanceAuditModel;
use core\dao\UserLogModel;
use core\dao\UserIdentityModifyLogModel;
use core\dao\UserPwdResetAuditModel;
use libs\utils\PaymentApi;
use core\service\UserTagService;
use core\dao\DealCompoundModel;
use core\dao\AccountAuthorizationModel;
use core\service\MsgBoxService;
use core\service\CouponBindService;
use core\service\curlHook\ThirdPartyHookService;
use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use \core\dao\UserBankcardModel;
use libs\db\Db;
use core\service\RemoteTagService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\StandardApi;
use core\service\SupervisionBaseService;
use core\service\SupervisionAccountService;
use core\tmevent\supervision\SupervisionUpdateUserMobileEvent;
use core\tmevent\supervision\SupervisionUpdateUserBankCardEvent;
use core\tmevent\supervision\UcfpayUpdateUserBankCardEvent;
use core\tmevent\supervision\WxUpdateUserBankCardEvent;
use core\tmevent\supervision\WxUpdateUserInfoEvent;
use core\tmevent\passport\UpdateIdentityEvent;
use core\tmevent\passport\UpdateCertEvent;
use core\service\PassportService;
use libs\utils\ABControl;
use core\tmevent\supervision\SupervisionMemberInfoModifyEvent;
use core\tmevent\supervision\WxAddUserIdentityModifyLogEvent;
use core\tmevent\supervision\WxUpdateUserIdentityInfoEvent;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Life\RequestCommon as LifeRequestCommon;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Common\Library\Idworker;
use libs\sms\SmsServer;
// 个人机构用户修改代理人手机号和法人信息
use core\tmevent\supervision\SupervisionEnterpriseUpdateEvent as SupervisionUpdateEvent;
use core\tmevent\ucfpay\UcfpayEnterpriseUpdateEvent as UcfpayUpdateEvent;
use core\service\UserCouponLevelService;
use core\service\ncfph\AccountService as PhAccountService;
use libs\vfs\VfsHelper;
use core\dao\AttachmentModel;
use core\service\UserGroupService;
use NCFGroup\Common\Library\ApiService;
use core\service\booking\BookService;

//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

class UserAction extends CommonAction {

    public static $msg = array('code' => '0000', 'msg' => '');

    /**
     * 用户组列表
     */
    protected $groups = array();
    protected $failMsg = '';

    /**
     * 更换银行卡的申请列表-审核失败原因配置
     * @var array
     */
    protected static $auditBankInfoFailType = array(
        1 => array(
            'reasonId' => 1,
            'reason' => '证件信息模糊',
            'reasonDesc' => '证件信息难以辨认，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        2 => array(
            'reasonId' => 2,
            'reason' => '银行卡信息模糊',
            'reasonDesc' => '银行卡信息难以辨认，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        3 => array(
            'reasonId' => 3,
            'reason' => '证件有遮挡',
            'reasonDesc' => '证件信息有遮挡，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        4 => array(
            'reasonId' => 4,
            'reason' => '银行卡有遮挡',
            'reasonDesc' => '银行卡信息有遮挡，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        5 => array(
            'reasonId' => 5,
            'reason' => '上传照片失败',
            'reasonDesc' => '照片上传失败，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        6 => array(
            'reasonId' => 6,
            'reason' => '上传照片有误',
            'reasonDesc' => '请上传本人左手持银行卡，右手持身份证，面部清楚，证件号清晰的照片，以便确认您的本人身份。',
        ),
        7 => array(
            'reasonId' => 7,
            'reason' => '证件号码有误',
            'reasonDesc' => '填写的证件号码与照片上的号码不符，请核实后再次申请。',
        ),
        8 => array(
            'reasonId' => 8,
            'reason' => '银行卡号有误',
            'reasonDesc' => '填写的银行卡号与照片上的卡号不符，请您核实后再次申请。',
        ),
        9 => array(
            'reasonId' => 9,
            'reason' => '银行名称有误',
            'reasonDesc' => '填写的银行与照片上的银行不符，请您核实后再次申请。',
        ),
        10 => array(
            'reasonId' => 10,
            'reason' => '银行卡无法认证',
            'reasonDesc' => '银行卡无法认证，请更换以下十八家银行任意一家银行的银行卡进行认证。中国工商银行，中国建设银行，中国农业银行，中国银行，中国邮政储蓄银行，交通银行，招商银行，中国光大银行，中信银行，浦发银行，中国民生银行，平安银行，兴业银行，华夏银行，广发银行，北京银行，上海银行，海口联合农商银行，请重新尝试后再次申请。',
        ),
        11 => array(
            'reasonId' => 11,
            'reason' => '护照号码不符',
            'reasonDesc' => '因与后台实名认证护照号码不符，请联系客服更改护照号码。',
        ),
        12 => array(
            'reasonId' => 12,
            'reason' => '证件信息和银行卡信息模糊',
            'reasonDesc' => '证件信息和银行卡信息难以辨认，请上传本人左手持银行卡，右手持身份证，面部清楚，证件号及银行卡号清晰的照片，以便确认您的本人身份。',
        ),
        13 => array(
            'reasonId' => 13,
            'reason' => '其他',
            'reasonDesc' => '其他',
        ),
    );

    /**
     * 海外用户身份认证审核--失败列表
     * @var array
     */
    protected static $identityFailType = array(
        1 => array(
            'reasonId' => 1,
            'reason' => '证件信息模糊',
            'reasonDesc' => '证件信息难以辨认，请上传本人清晰的证件照片，以便确认您的身份',
        ),
        2 => array(
            'reasonId' => 2,
            'reason' => '上传照片失败',
            'reasonDesc' => '照片上传失败，请上传本人清晰的证件照片，以便确认您的身份',
        ),
        3 => array(
            'reasonId' => 3,
            'reason' => '上传照片有误',
            'reasonDesc' => '请上传本人证件号清晰的照片，以便确认您的身份',
        ),
        4 => array(
            'reasonId' => 4,
            'reason' => '证件号码有误',
            'reasonDesc' => '填写的证件号码与证件照片上的号码不符，请核实后再次申请',
        ),
        5 => array(
            'reasonId' => 5,
            'reason' => '证件类型有误',
            'reasonDesc' => '证件上传有误，请上传本人清晰的证件照片，以便确认您的身份',
        ),
        6 => array(
            'reasonId' => 6,
            'reason' => '有效期填写错误',
            'reasonDesc' => '填写的有效期与证件照片上有效期不符，请核实后再次申请',
        ),
        7 => array(
            'reasonId' => 7,
            'reason' => '其他',
            'reasonDesc' => '其他',
        ),
    );
    public function __construct() {
        parent::__construct();
        require_once APP_ROOT_PATH . "/system/libs/user.php";
    }

    public function index() {
        $user_num = trim($_GET['user_num']);
        if($user_num){
           $_REQUEST['user_id'] = de32Tonum($user_num);
        }

        // 兼容管理后台普通会员与企业会员的处理
        if ((isset($_GET['user_id']) && $_GET['user_id'] > 0) OR (isset($_GET['user_name']) && !empty($_GET['user_name']))) {
            $map_enterprise = array();
            if (trim($_GET['user_id']) != '') $map_enterprise['id'] = intval($_GET['user_id']);
            if (trim($_GET['user_name']) != '') $map_enterprise['user_name'] = addslashes(trim($_GET['user_name']));
            // 未删除的帐户
            $map_enterprise['is_delete'] = 0;
            // 用户类型-企业用户
            $map_enterprise['user_type'] = \core\dao\UserModel::USER_TYPE_ENTERPRISE;
            $userEnterpriseInfo = MI('User')->where($map_enterprise)->find();
            if ($userEnterpriseInfo) {
                return header('Location:?m=Enterprise&a=index&user_id=' . $_GET['user_id'] . '&user_name=' . $_GET['user_name']);
            }
        }

        $this->groups = \core\dao\UserGroupModel::instance()->getGroups();
        $this->assign("group_list", $this->groups);
        //定义条件
        $where = 'is_delete = 0';
        // 用户类型-普通用户
        $where .= ' AND (user_type = ' . \core\dao\UserModel::USER_TYPE_NORMAL . ' OR user_type IS NULL)';

        if (intval($_REQUEST['group_id']) > 0) {
            $where .= ' and group_id = ' . intval($_REQUEST['group_id']);
        }

        if (intval($_REQUEST['coupon_level_id']) > 0) {
            $where .= ' and coupon_level_id = ' . intval($_REQUEST['coupon_level_id']);
        }
        if (trim($_REQUEST['user_id']) != '') {
            $where .= ' and id = ' . intval($_REQUEST['user_id']);
        }
        if (trim($_REQUEST['invite_code'])) {
            $where .= " and invite_code = '" . trim($_REQUEST['invite_code']) . "'";
        }
        if(trim($_REQUEST['user_name'])!='')
        {
            $where .= " and user_name like '".trim($_REQUEST['user_name'])."%'";
        }
        if(trim($_REQUEST['real_name'])!='')
        {
            //$where .= " and real_name like '".trim($_REQUEST['real_name'])."%'"; // 引发慢查询，没必要模糊匹配的都关闭
            $where .= " and real_name = '".trim($_REQUEST['real_name'])."'";
        }
        if(trim($_REQUEST['email'])!='')
        {
            //$where .= " and email like '".trim($_REQUEST['email'])."%'";
            $where .= " and email = '".trim($_REQUEST['email'])."'";
        }
        if(trim($_REQUEST['mobile'])!='')
        {
            //$where .= " and mobile like '".trim($_REQUEST['mobile'])."%'";
            $where .= " and mobile = '".trim($_REQUEST['mobile'])."'";
        }

        if(trim($_REQUEST['idno'])!='')
        {
            // 身份证号采用加密存储，统一使用大写的X后缀
            $idno = strtoupper(addslashes(trim($_REQUEST['idno'])));
            $where .= " and idno = '".$idno."'";
        }

        if (intval($_REQUEST['new_coupon_level_id']) > 0) {
            $where .= ' and new_coupon_level_id = ' . intval($_REQUEST['new_coupon_level_id']);
        }

        // 是否开通存管户
        $this->assign('supervision_account_list', [
                ['id' => 1, 'name' => '已开通'],
                ['id' => 2, 'name' => '未开通'],
        ]);
        if(trim($_REQUEST['supervision_account'])!='')
        {
            $account_type = intval($_REQUEST['supervision_account']);
            if ($account_type == 1) {
                $where .= " AND supervision_user_id != 0 ";
            } else if ($account_type == 2) {
                $where .= " AND supervision_user_id = 0 ";
            }
        }

        if (trim($_REQUEST['pid_name']) != '') {
            $pid = MI("User")->where("user_name='" . trim($_REQUEST['pid_name']) . "'")->getField("id");
            if ($pid) {
                $where .= ' and pid = ' . intval($pid);
            } else {
                $where .= ' and pid < 0';
            }
        }

        if (trim($_REQUEST['bankcard']) != '') {
            $sql = "select group_concat(user_id) from " . DB_PREFIX . "user_bankcard where bankcard = '" . trim($_REQUEST['bankcard']) . "'";
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if ($ids) {
                $where .= " and id in (" . $ids . ")";
            } else {
                $where .= " and id in ('')";
            }
        }
        $user_tag_service = new UserTagService();
        $this->assign('user_tags', $user_tag_service->lists());
        if(trim($_REQUEST['tag_id']) != '') { //根据标签筛选用户
            $user_ids = $user_tag_service->getUidsByTagId(intval($_REQUEST['tag_id']));
            $where .= " AND id in (" . implode(',', $user_ids) . ")";
        }

        $remoteTagService = new RemoteTagService();
        $this->assign('user_remote_tags', $remoteTagService->getTagAttrs());
        $tagKey = trim($_REQUEST['remote_tag_key']);

        if (!empty($tagValue) && empty($tagKey)) {
            $this->error("请选择用户键名");
        }

        $tagValue = trim($_REQUEST['remote_tag_value']);
        if ($tagKey != '') {
            $userIds = $remoteTagService->getUserByTag($tagKey, $tagValue);
            $where = ' id IN ("'.implode('","', $userIds).'")';
        }

        if(trim($_REQUEST['coupon']) != ''){
            $couponService = new CouponService ();
            $refer_user_id = $couponService->shortAliasToReferUserId (trim($_REQUEST['coupon']));
            if ($refer_user_id) {
                $where = " id = '{$refer_user_id}' ";
            }
        }

        // 你看不见我,
        if ($this->is_cn) {
            $where .= ' AND id NOT IN (8118934,7963653) AND supervision_user_id>0';
        }

        // 存管系统余额
        if (method_exists($this, '_filter')) {
            $this->_filter($where);
        }
        $name = $this->getActionName();
        $model = DI($name);
        if (!empty($model)) {
            $this->_setPageEnable(false);
            $list = $this->_list($model, $where);
            $list = $this->appendAccountInfo($list);
            $this->assign('list', $list);
        }
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $new_coupon_level =  M("UserCouponLevel")->findAll();
        $this->assign('new_coupon_level',$new_coupon_level);
        $this->display($template);
    }

    private function appendAccountInfo($list) {
        //收集id
        $userIds = $typeList = [];
        foreach ($list as $key => $item) {
            $userIds[] = $item['id'];
            $typeList[] = $item['user_purpose'];
        }

        //获取账户信息
        if (!empty($userIds)) {
            $phAccountService = new PhAccountService();
            $phResult = $phAccountService->getInfoByUserIdsAndTypeList($userIds, $typeList, false); //这里不同步状态，减少存管请求次数
        }

        foreach ($list as $key => $item) {
            $index = $item['id'] . '_' . $item['user_purpose'];
            $list[$key]['sv_money'] = isset($phResult[$index]) ? number_format($phResult[$index]['money'], 2) : 0;
            $list[$key]['sv_lock_money'] = isset($phResult[$index]) ? number_format($phResult[$index]['lockMoney'], 2) : 0;
            $list[$key]['sv_status_desc'] = isset($phResult[$index]) ? $phResult[$index]['statusDesc'] : '未开通';
            $list[$key]['sv_account_desc'] = isset($phResult[$index]) && $phResult[$index]['status'] != 0 ? $phResult[$index]['accountTypeDesc'] : '';
        }

        return $list;
    }

    public function withdrawAmount()
    {
        $userId = intval($_REQUEST['id']);
        $accounts = (new AccountService())->getAccountList($userId);
        $options = [];
        foreach ($accounts as $platform => $subaccounts) {
            foreach ($subaccounts as $account) {
                // 可提现额度用户不参与设置
                if ($account['accountType'] == UserAccountEnum::ACCOUNT_FINANCE && $platform == UserAccountEnum::PLATFORM_SUPERVISION) {
                    $options[] = "<option value='{$platform}_{$account['accountType']}'>{$account['accountTypeDesc']}</option>";
                }
            }
        }
        if (empty($options)) {
            $this->assign('errorMsg', ' 用户暂无可用的账户');
        }
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);
        $this->assign('userId', $userId);
        $this->assign('optionHtml', implode($options,"\n"));
        $this->display('limitpage');

    }

    public function limitPage()
    {
        $userId = intval($_REQUEST['id']);

        $accounts = (new AccountService())->getAccountList($userId);
        $options = [];
        foreach ($accounts as $platform => $subaccounts) {
            foreach ($subaccounts as $account) {
                // 可提现额度用户不参与设置
                if ($account['accountType'] == UserAccountEnum::ACCOUNT_FINANCE && $platform == UserAccountEnum::PLATFORM_SUPERVISION) {
                    continue;
                }
               $options[] = "<option value='{$platform}_{$account['accountType']}'>{$account['accountTypeDesc']}</option>";
            }
        }
        if (empty($options)) {
            $this->assign('errorMsg', ' 用户暂无可用的账户');
        }
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);
        $this->assign('userId', $userId);
        $this->assign('optionHtml', implode($options,"\n"));
        $this->display();
    }

    /**
     * 查看用户在先锋支付的余额
     */
    public function balance() {
        $userId = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
        $uInfo = M("User")->getById($userId);
        $params = array(
            'source' => 1,
            'userId' => $userId,
        );

        $result = array();
        $userInfo = array();
        $bankInfo = array();
        $supervisionUserInfo = [];

        //如果开启对接先锋支付启用验证
        if (app_conf('PAYMENT_ENABLE')) {
            $result = \libs\utils\PaymentApi::instance()->request('searchuserbalance', $params);
            $userInfo = \libs\utils\PaymentApi::instance()->request('searchuserinfo', $params);
            // 获取支付系统所有银行卡列表-安全卡数据
            $obj = new UserBankcardService();
            $bankInfo = $obj->queryBankCardsList($userId);
        }

        if ($uInfo['supervision_user_id'] > 0) {
            $supervisionResult = (new SupervisionAccountService)->memberSearch($userId);
            if ($supervisionResult['respCode'] == '00') {
                $supervisionUserInfo = $supervisionResult['data'];
            }
        }
        $showCreateAccount = $uInfo['group_id'] == '3' && $uInfo['payment_user_id'] == 0 ? true : false;
        $showCreateAccount = $showCreateAccount ? '<a class="button" id="button" href="/m.php?m=User&a=entreg&uid=' . $userId . '" >企业用户开户</a>' : '';
        $this->assign('result', $result);
        $this->assign('userInfo', $userInfo);
        if ($this->is_cn && !empty($bankInfo['list'])) {
            foreach($bankInfo['list'] as &$item) {
                $item['cardNo'] = formatBankcard($item['cardNo']);
            }
        }
        $this->assign('bankInfo', $bankInfo);
        $this->assign('supervisionUserInfo', $supervisionUserInfo);
        $this->assign('uid', $userId);
        $this->assign('p2pUserInfo', $uInfo);
        $this->assign('isEnterprise', $uInfo['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE);
        $this->assign('showCreateAccount', $showCreateAccount);
        $this->display();
    }

    /**
     * 同步认证类型
     */
    public function syncCertStatus()
    {
        try {
            $userId = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
            if (empty($userId)) {
                throw new \Exception('参数错误');
            }
            // 获取支付系统所有银行卡列表-安全卡数据
            $obj = new UserBankcardService();
            $bankInfo = $obj->queryBankCardsList($userId, true);
            if ($bankInfo['respCode'] != '00') {
                throw new \Exception($bankInfo['respMsg']);
            }
            if (empty($bankInfo['list'])) {
                throw new \Exception('银行卡查询接口返回绑卡信息为空');
            }
            $cert = $bankInfo['list']['certStatus'];
            $cardNo = $bankInfo['list']['cardNo'];

            $UserBankcardModel = new UserBankcardModel();
            $result = $UserBankcardModel->updateCertStatusByUserIdAndCardNo($userId, $cardNo, $cert);
            if (!$result) {
                throw new \Exception('同步认证类型失败');
            }
            self::$msg = ['code'=> 0, 'msg' => '同步认证类型成功'];
            // 记录操作日志
            // 获取用户绑卡数据
            $userBankCardInfo = $UserBankcardModel->getCardByUser($userId);
            $certStatusOld = isset($userBankCardInfo['cert_status']) ? $userBankCardInfo['cert_status'] : 0;
            $certStatusNew = isset(UserBankcardModel::$cert_status_map[$cert]) ? UserBankcardModel::$cert_status_map[$cert] : 0;
            if ($certStatusOld != $certStatusNew) {
                save_log('个人会员查看余额-同步认证类型，会员id['.$userId.']操作成功', 1, array('cert_status'=>$certStatusOld), array('cert_status'=>$certStatusNew));
            }
        }
        catch (\Exception $e)
        {
            self::$msg = ['code' => 4000, 'msg' => $e->getMessage()];
        }
        echo json_encode(self::$msg);
        exit;
    }

    /**
     * 更新payment_user_id
     */
    public function updatePaymentUserId()
    {
        $userId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $userInfo = \libs\utils\PaymentApi::instance()->request('searchuserinfo', array('source' => 1, 'userId' => $userId));

        if (empty($userInfo['userId'])) {
            $this->error('用户未在支付开户');
        }
        // 获取用户在理财端的用户信息
        $userBaseInfo = M('User')->getById($userId);

        $GLOBALS['db']->update('firstp2p_user', array('payment_user_id' => $userInfo['userId']), "id={$userId}");
        // 记录操作日志
        save_log('个人会员查看余额-更新payment_user_id，会员id['.$userId.']操作成功', 1, array('payment_user_id'=>$userBaseInfo['payment_user_id']), array('payment_user_id'=>$userInfo['userId']));
        $this->success('更新成功');
    }

    /**
     * 更新用户在支付的手机号
     */
    public function updatePaymentPhone()
    {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->error(\libs\utils\PaymentApi::maintainMessage());
        }

        $userId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $userInfo = M('User')->getById($userId);
        $response = PaymentApi::instance()->request('phoneupdate', array(
            'userId' => $userId,
            'newPhone' => $userInfo['mobile'],
        ));
        // 记录操作日志
        save_log('个人会员查看余额-更新支付端的手机号，会员id['.$userId.']操作成功', 1, array(), array('mobile'=>$userInfo['mobile']));
        $this->success($response['respMsg']);
    }

    public function entreg()
    {
        $userId = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
        $uInfo = M("User")->getById($userId);
        $this->assign('uid', $uInfo['id']);
        $this->assign('mobile', $uInfo['mobile']);
        $this->display();
    }

    public function doentreg() {
        $params = array(
            'userId' => intval($_POST['userid']),
            'enterpriseFullName' => trim($_POST['fullname']),
            'enterpriseShortName' => trim($_POST['shortname']),
            'businessLicense' => trim($_POST['executeno']),
            'agentPersonName' => trim($_POST['contract']),
            'agentPersonPhone' => trim($_POST['mobile']),
        );
        $msgCodes = array(
            '02' => '参数错误',
            '31' => '用户已经存在',
            '32' => '手机号已被占用',
        );
        $resp = PaymentApi::instance()->request('compregister', $params);
        if (empty($resp) || $resp['respCode'] == '01') {
            echo '<script>alert("接口请求超时,请重新注册"); window.history.go(-1);</script>';
            exit;
        }
        if ($resp['status'] == '00') {
            $GLOBALS['db']->query('UPDATE firstp2p_user SET payment_user_id = ' . $params['userId'] . ' WHERE  id = ' . $params['userId']);
            echo '<script>alert("注册成功"); window.history.go(-1);</script>';
        }
        else {
            echo '<script>alert("企业开户失败,' . $msgCodes[$resp['status']] . '"); window.history.go(-1);</script>';
        }
    }

    /**
     * 查询相关字段
     */
    protected function form_index_list(&$list) {
        $coupon_level_service = new \core\service\CouponLevelService();
        $coupon_service = new \core\service\CouponService();
        $couponBindService = new \core\service\CouponBindService();
        $userCouponLevelService = new UserCouponLevelService();
        foreach ($list as &$item) {
            // 查询优惠券短码
            $user_level = $coupon_level_service->getUserLevel($item['id']);
            $user_tag_service = new \core\service\UserTagService();
            $bankcard = M("UserBankcard")->where("user_id=" . $item['id'] . " order by id desc")->find();
            if ($bankcard) {
                if ($bankcard['status'] == 1) {
                    $item['user_bankcard'] = formatBankcard($bankcard['bankcard']);
                } else {
                    $item['user_bankcard'] = '未验证';
                }
            } else {
                $item['user_bankcard'] = '未绑定';
            }
            if(!empty($item['idno'])){
                $item['idno'] = idnoFormat($item['idno']);
            }
            if(!empty($item['user_name'])){
                $user_id=MI('User')->where("user_name='".$item['user_name']."'")->field('id')->find();


                $item['user_num'] =numTo32($user_id['id']);
                $item['user_name'] = userNameFormat($item['user_name']);
            }
            if(!empty($item['mobile'])){
                $item['mobile'] = adminMobileFormat($item['mobile']);
                if (!empty($item['mobile_code']) && $item['mobile_code'] != '86') {
                    $item['mobile'] = $item['mobile_code'] . '-' . $item['mobile'];
                }
            }
            if(!empty($item['email'])){
                $item['email'] = adminEmailFormat($item['email']);
            }
            $item['group'] = $this->groups[$item['group_id']]['name'];
            $item['service_status'] = $this->groups[$item['group_id']]['service_status']==1?'有效':'无效';
            $item['level'] = $user_level['level'];

            $item['coupon'] = CouponService::userIdToHex($item['id'],$this->groups[$item['group_id']]['prefix']);
            if($item['coupon']){
                $item['coupon'] = "<a href='m.php?m=User&a=index&invite_code={$item['coupon']}'>" . $item['coupon'] . "</a>";
            }
            $invite_uid = 0;
            if ($item['invite_code']) {
                $invite_uid = CouponService::hexToUserId($item['invite_code']);
                $item['invite_code'] = "<a href='m.php?m=User&a=index&user_id={$invite_uid}'>" . $item['invite_code'] . "</a>";
            }

            $item['email'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['email']}</div>";
            $item['login_ip'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['login_ip']}</div>";

            $item['user_tag'] = implode("|", array_map(function($val){return $val['tag_name'];}, $user_tag_service->getTags($item['id'])));

            $couponBind = $couponBindService->getByUserId($item['id']);
            if(!empty($couponBind)){
                $item['refer_user_id'] = $couponBind['refer_user_id']==0?'':$couponBind['refer_user_id'];
                $item['refer_user_code'] =  $couponBind['short_alias'];
                if($item['refer_user_id']){
                    $refer_user_group_id = MI('User')->where("id='".$item['refer_user_id']."'")->field('group_id')->find();
                    if(!empty($refer_user_group_id) && isset($refer_user_group_id['group_id'])){
                        $item['refer_user_group_name'] = $this->groups[$refer_user_group_id['group_id']]['name'];
                    }
                    $item['refer_user_code'] = "<a href='m.php?m=User&a=index&user_id={$item['refer_user_id']}'>" . $item['refer_user_code'] . "</a>";
                    $item['refer_user_id'] = "<a href='m.php?m=User&a=index&user_id={$item['refer_user_id']}'>" . $item['refer_user_id'] . "</a>";
                }
                $item['invite_user_id'] = $couponBind['invite_user_id']==0?'':$couponBind['invite_user_id'];
                $item['invite_user_code'] =  $couponBind['invite_code'];
                if($item['invite_user_id']){
                    $item['invite_user_code'] = "<a href='m.php?m=User&a=index&user_id={$item['invite_user_id']}'>" . $item['invite_user_code'] . "</a>";
                    $item['invite_user_id'] = "<a href='m.php?m=User&a=index&user_id={$item['invite_user_id']}' >" . $item['invite_user_id'] . "</a>";
                }
            }
            $data= array();
            if($item['new_coupon_level_id'] != 0){
                $data=$userCouponLevelService->getLevelById($item['new_coupon_level_id']);
            }
            $item['new_coupon_level_name'] = empty($data) ? '无' : $data['name'];

        }
    }

    /**
     * 待审核用户
     */
    public function wait() {
        $where_wait_user = 'id IN(SELECT uid FROM firstp2p_user_passport WHERE status=0) AND idcardpassed=3 AND is_delete=0 AND is_effect=1';
        $name = $this->getActionName();
        $model = DI($name);
        if (!empty($model)) {
            $this->_list($model, $where_wait_user);
        }
        $this->display();
    }

    public function trash() {
        $condition['is_delete'] = 1;
        $this->assign("default_map", $condition);
        parent::index();
    }

    public function add() {
        $group_list = M("UserGroup")->findAll();
        $this->assign("group_list", $group_list);
        $cate_list = M("TopicTagCate")->findAll();
        $this->assign("cate_list", $cate_list);

        $field_list = M("UserField")->order("sort desc")->findAll();
        foreach ($field_list as $k => $v) {
            $field_list[$k]['value_scope'] = preg_split("/[ ,]/i", $v['value_scope']);
        }

        // 身份证件类型
        $this->assign("idTypes", $GLOBALS['dict']['ID_TYPE']);
        // 用户账户类型
        $this->assign('user_purpose_list', $this->is_cn ? $GLOBALS['dict']['ENTERPRISE_PURPOSE_CN'] : $GLOBALS['dict']['ENTERPRISE_PURPOSE']);
        //一级地区
        $n_region_lv1 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where region_level = 1");
        $this->assign("n_region_lv1", $n_region_lv1);
        //用户银行卡信息
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);

        $this->assign("field_list", $field_list);
        $new_coupon_level =  M("UserCouponLevel")->findAll();
        $this->assign('new_coupon_level',$new_coupon_level);
        $this->display();
    }

    public function insert() {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();

        // 是否需要拷贝用户数据
        $isCopyUser = !empty($_REQUEST['isCopyUser']) && $_REQUEST['isCopyUser'] == 1;
        //开始验证有效性
        $this->assign("jumpUrl", $isCopyUser ? $_SERVER['HTTP_REFERER'] : u(MODULE_NAME . "/add"));
        if (!check_empty($data['user_pwd'])) {
            $this->error(L("USER_PWD_EMPTY_TIP"));
        }

        if ($data['user_pwd'] != $_REQUEST['user_confirm_pwd']) {
            $this->error(L("USER_PWD_CONFIRM_ERROR"));
        }

        if(!(new UserCouponLevelService())->checkLevelMatchGroupById(intval($_REQUEST['group_id']),intval($_REQUEST['new_coupon_level_id']))){
            $this->error('会员组和服务等级不匹配');
        }

        // 根据用户手机号，检查账户类型
        //if (!empty($_REQUEST['mobile']) && substr($_REQUEST['mobile'], 0, 1) !== '6' && $_REQUEST['user_purpose'] != EnterpriseModel::COMPANY_PURPOSE_MIX) {
            //$this->error('普通用户的账户类型只能选择[借贷混合用户]');
        //}

        // 后台添加默认手机号已认证
        $_REQUEST['mobilepassed'] = true;
        $_REQUEST['country_code'] = 'cn';
        // 指定为普通用户
        $_REQUEST['user_type'] = UserModel::USER_TYPE_NORMAL;
        // 国家区号
        $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
        $_REQUEST['mobile_code'] = $mobile_code_list[$_REQUEST['country_code']]['code'];

        // 过滤用户真实姓名中的空格
        if (isset($_REQUEST['real_name'])) {
            $_REQUEST['real_name'] = trim($_REQUEST['real_name']);
        }
        // 用户的账户类型
        $_REQUEST['user_purpose'] = (int)$_REQUEST['user_purpose'];

        $GLOBALS['db']->startTrans();
        try {
            $res = save_user($_REQUEST, 'INSERT', 0, true);
            if ($res['status'] == 0) {
                $errorMsg = '创建用户基本信息失败！';
                $error_field = $res['data'];
                if ($error_field['error'] == EMPTY_ERROR) {
                    if ($error_field['field_name'] == 'user_name') {
                        $errorMsg = L("USER_NAME_EMPTY_TIP");
                    //去掉邮箱校验，JIRA#FIRSTPTOP-4269
                    //} elseif ($error_field['field_name'] == 'email') {
                    //   $this->error(L("USER_EMAIL_EMPTY_TIP"));
                    } else {
                        $errorMsg = sprintf(L("USER_EMPTY_ERROR"), $error_field['field_show_name']);
                    }
                }
                if ($error_field['error'] == FORMAT_ERROR) {
                    if ($error_field['field_name'] == 'email') {
                        $errorMsg = L("USER_EMAIL_FORMAT_TIP");
                    }
                    if ($error_field['field_name'] == 'mobile') {
                        $errorMsg = L("USER_MOBILE_FORMAT_TIP");
                    }
                }
                if ($error_field['error'] == EXIST_ERROR) {
                    if ($error_field['field_name'] == 'user_name') {
                        $errorMsg = L("USER_NAME_EXIST_TIP");
                    } elseif ($error_field['field_name'] == 'email') {
                        $errorMsg = L("USER_EMAIL_EXIST_TIP");
                    } elseif ($error_field['field_name'] == 'mobile') {
                        $errorMsg = '手机号已经存在！';
                    }
                }
                throw new \Exception($errorMsg);
            }

            $user_id = intval($res['data']);
            // 初始化第三方账户余额
            //\core\dao\UserThirdBalanceModel::instance()->initBalance($user_id);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage());
        }

        foreach ($_REQUEST['auth'] as $k => $v) {
            foreach ($v as $item) {
                $auth_data = array();
                $auth_data['m_name'] = $k;
                $auth_data['a_name'] = $item;
                $auth_data['user_id'] = $user_id;
                M("UserAuth")->add($auth_data);
            }
        }

        foreach ($_REQUEST['cate_id'] as $cate_id) {
            $link_data = array();
            $link_data['user_id'] = $user_id;
            $link_data['cate_id'] = $cate_id;
            M("UserCateLink")->add($link_data);
        }
        $user_tag_service = new \core\service\UserTagService();
        $user_tag = array('REG_Y_'.date('Y'),'REG_M_'.date('m'));
        $user_tag_service->addUserTagsByConstName($res['data'],$user_tag);
        // 更新数据
        $log_info = $data['user_name'];
        save_log('个人会员信息添加，会员id['.$user_id.']，会员名称[' . $log_info . ']' . L("INSERT_SUCCESS"), 1, array(), $_REQUEST);
        $isCopyUser && $this->assign("jumpUrl", u(MODULE_NAME . '/index'));
        $this->success(L("INSERT_SUCCESS"));
    }

    /**
     * 拷贝用户信息到新建用户页面
     */
    public function copy_user() {
        $this->assign('isCopyUser', 1);
        $this->edit();
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $con['user_id']=$id;
        $vo1=M('delivery')->where($con)->find();

        $user_comments_service = new \core\service\UserService();
        $comments = $user_comments_service -> UserLockComments($id);
        $this -> assign('comments', $comments);

        /*@author:liuzhenpeng, modify:股票开户用户禁止修改身份证号, time:2015-12-04*/
        $user_tag_service = new \core\service\UserTagService();
        $user_tag_result  = $user_tag_service->getTagByConstNameUserId('STOCK_USER_REG', $id);
        if($user_tag_result !== false){
            $this->assign('stock', 1);
        }

        //返利系数
        //$vo['channel_pay_factor'] = ($vo['channel_pay_factor'] > 0.0000) ? $vo['channel_pay_factor'] : '';
        $vo['group_factor'] = M("UserGroup")->where("id=" . $vo['group_id'])->getField("channel_pay_factor");

        $this->assign('vo', $vo);
        $this->assign('vo1', $vo1);
        $group_list = M("UserGroup")->findAll();
        $this->assign("group_list", $group_list);

        //国家区号
        $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
        $this->assign("mobile_code_list", $mobile_code_list);
        // 用户账户类型
        $this->assign('user_purpose_list', $this->is_cn ? $GLOBALS['dict']['ENTERPRISE_PURPOSE_CN']: $GLOBALS['dict']['ENTERPRISE_PURPOSE']);

        $cate_list = M("TopicTagCate")->findAll();
        foreach ($cate_list as $k => $v) {
            $cate_list[$k]['checked'] = M("UserCateLink")->where("user_id=" . $vo['id'] . " and cate_id = " . $v['id'])->count();
        }
        $this->assign("cate_list", $cate_list);
        $field_list = M("UserField")->order("sort desc")->findAll();
        foreach ($field_list as $k => $v) {
            $field_list[$k]['value_scope'] = preg_split("/[ ,]/i", $v['value_scope']);
            $field_list[$k]['value'] = M("UserExtend")->where("user_id=" . $id . " and field_id=" . $v['id'])->getField("value");
        }
        $this->assign("field_list", $field_list);
        $rs = M("UserAuth")->where("user_id=" . $id . " and rel_id = 0")->findAll();
        foreach ($rs as $row) {
            $auth_list[$row['m_name']][$row['a_name']] = 1;
        }

        //一级地区
        $n_region_lv1 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where region_level = 1");
        $this->assign("n_region_lv1", $n_region_lv1);
        //地区列表
        $region_lv2 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where region_level = 2");  //二级地址
        $this->assign("region_lv2", $region_lv2);
        //城市列表-籍贯
        if (!empty($vo['n_province_id'])) {
            $n_region_lv3 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where pid = " . intval($vo['n_province_id']));
            $this->assign('n_region_lv3', $n_region_lv3);
        }
        //城市列表-户口所在地
        if (!empty($vo['province_id'])) {
            $region_lv3 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where pid = " . intval($vo['province_id']));
            $this->assign('region_lv3', $region_lv3);
        }

        //用户银行卡信息
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);
        $bankcard_info = UserBankcardModel::instance()->getNewCardByUserId($id);
        if ($bankcard_info) {
            foreach ($bank_list as $k => $v) {
                if ($v['id'] == $bankcard_info['bank_id']) {
                    $bankcard_info['is_rec'] = $v['is_rec'];
                    $bankcard_info['bank_name'] = $v['name'];
                    break;
                }
            }
            if ($this->is_cn) {
                $bankcard_info['bankcard'] = formatBankcard($bankcard_info['bankcard']);
            }
            $this->assign("bankcard_info", $bankcard_info);
        }
        $this->assign("auth_list", $auth_list);
        $this->assign("idTypes", $GLOBALS['dict']['ID_TYPE']);
        $fullRegion = Db::getInstance('firstp2p','adminslave')->getRow("SELECT province,city FROM firstp2p_banklist WHERE bank_id = '{$bankcard_info['branch_no']}'");
        $fullRegion = $fullRegion['province'].' '.$fullRegion['city'];
        $new_coupon_level =  M("UserCouponLevel")->findAll();
        $this->assign('new_coupon_level',$new_coupon_level);
        $this->assign('fullregion', $fullRegion);
        // 主站域名
        $this->assign('wxlc_domain', get_http() . app_conf('WXLC_DOMAIN'));
        $this->display();

    }

    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset($id)) {
            //删除验证
            if (M("DealOrder")->where(array('user_id' => array('in', explode(',', $id))))->count() > 0) {
                $this->error(l("ORDER_EXIST_DELETE_FAILED"), $ajax);
            }
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['user_name'];
            }
            if ($info)
                $info = implode(",", $info);
            $list = M(MODULE_NAME)->where($condition)->setField('is_delete', 1);
            if ($list !== false) {
                //把信息屏蔽
                M("Topic")->where("user_id in (" . $id . ")")->setField("is_effect", 0);
                M("TopicReply")->where("user_id in (" . $id . ")")->setField("is_effect", 0);
                M("Message")->where("user_id in (" . $id . ")")->setField("is_effect", 0);
                save_log($info . l("DELETE_SUCCESS"), 1);
                $this->success(l("DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("DELETE_FAILED"), 0);
                $this->error(l("DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function restore() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['user_name'];
            }
            if ($info)
                $info = implode(",", $info);
            $list = M(MODULE_NAME)->where($condition)->setField('is_delete', 0);
            if ($list !== false) {
                //把信息屏蔽
                M("Topic")->where("user_id in (" . $id . ")")->setField("is_effect", 1);
                M("TopicReply")->where("user_id in (" . $id . ")")->setField("is_effect", 1);
                M("Message")->where("user_id in (" . $id . ")")->setField("is_effect", 1);
                save_log($info . l("RESTORE_SUCCESS"), 1);
                $this->success(l("RESTORE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("RESTORE_FAILED"), 0);
                $this->error(l("RESTORE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function foreverdelete() {
        // 禁止物理删除用户记录
        return false;
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['user_name'];
            }
            if ($info)
                $info = implode(",", $info);
            $ids = explode(',', $id);
            foreach ($ids as $uid) {
                delete_user($uid);
            }
            save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
            clear_auto_cache("consignee_info");
            $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function view_bank() {
        $userbankInfo = array();
        $uid = intval($_REQUEST['uid']);
        if ($uid) {
            $userbankInfo = UserBankcardModel::instance()->getNewCardByUserId($uid);
        } else if (trim($_REQUEST['bankcard']) != '') {
            $condition = "bankcard = '". trim($_REQUEST['bankcard'])."'";
            $userbankInfo = UserBankcardModel::instance()->getUserBankCardRow($condition);
        }
        if (empty($userbankInfo)) {
            $userbankInfo = array (
                'id' => '',
                'bank_id' => '',
                'bankcard' => '',
                'bankzone' => '',
                'user_id' => '0',
                'status' => '0',
                'card_name' => '',
                'card_type' => '',
                'region_lv1' => '0',
                'region_lv2' => '0',
                'region_lv3' => '0',
                'region_lv4' => '0',
                'image_id' => '0',
                'create_time' => '',
                'update_time' => '',
                'verify_status' => '0',
                'branch_no' => '',
                'e_account' => '',
                'p_account' => '',
                'unitebank_state' => 0,
            );
        }
        if ($userbankInfo['region_lv1'] == '0') {
            $userbankInfo['region_lv1'] = '1';
        }
        if ($userbankInfo['user_id'] == '0') {
            $userbankInfo['user_id'] = $uid;
        }
        //用户银行卡信息
        $bankList = Db::getInstance('firstp2p','adminslave')->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bankList);
        //用户信息查询
        $userInfo = Db::getInstance('firstp2p', 'adminslave')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$uid}'");
        $this->assign('userInfo', $userInfo);
        //一级地区
        $n_region_lv1 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where region_level = 1");
        // 银行卡类型
        $cardTypes = array(
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign("n_region_lv1", $n_region_lv1);
        $this->assign('bankcard_info', $userbankInfo);
        $this->assign('cardTypes', $cardTypes);
        $this->display();
    }

    public function edit_bank()
    {
        error_reporting(E_ERROR);
        ini_set('display_errors', 1);
        $uid = intval($_REQUEST['uid']);
        $userbankInfo = UserBankcardModel::instance()->getNewCardByUserId($uid);
        if (empty($userbankInfo))
        {
            $userbankInfo = array (
                'id' => '',
                'bank_id' => '',
                'bankcard' => '',
                'bankzone' => '',
                'user_id' => '0',
                'status' => '0',
                'card_name' => '',
                'card_type' => '',
                'region_lv1' => '0',
                'region_lv2' => '0',
                'region_lv3' => '0',
                'region_lv4' => '0',
                'image_id' => '0',
                'create_time' => '',
                'update_time' => '',
                'verify_status' => '0',
                'branch_no' => '',
                'e_account' => '',
                'p_account' => '',
                'unitebank_state' => 0,
            );
        }
        if ($userbankInfo['region_lv1'] == '0')
        {
            $userbankInfo['region_lv1'] = '1';
        }
        if ($userbankInfo['user_id'] == '0')
        {
            $userbankInfo['user_id'] = $uid;
        }
        if ($this->is_cn) {
            $userbankInfo['bankcard'] = '';
        }

        //用户银行卡信息
        $bankList = Db::getInstance('firstp2p','adminslave')->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bankList);
        //用户信息查询
        $userInfo = Db::getInstance('firstp2p', 'adminslave')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$uid}'");
        $this->assign('userInfo', $userInfo);
        //一级地区
        $n_region_lv1 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where region_level = 1");
        // 银行卡类型
        $cardTypes = array(
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign("n_region_lv1", $n_region_lv1);
        $this->assign('bankcard_info', $userbankInfo);
        $this->assign('cardTypes', $cardTypes);
        // 主站域名
        $this->assign('wxlc_domain', get_http() . app_conf('WXLC_DOMAIN'));
        $this->display();
    }

    /**
     * 修改银行卡-旧
     */
    public function do_edit_bank_bak()
    {
        $userId = intval($_REQUEST['id']);

        try
        {
            Db::getInstance('firstp2p')->startTrans();
            $bankcard = $this->filterJs(trim($_REQUEST['bank_bankcard']));
            if (empty($bankcard)) {
                throw new \Exception('银行卡不能为空');
            }
            $userBankcardInfo = array(
                'card_name' => htmlspecialchars($_REQUEST['bank_card_name']), //开户姓名
                'region_lv1' => intval($_REQUEST['c_region_lv1']),
                'region_lv2' => intval($_REQUEST['c_region_lv2']),
                'region_lv3' => intval($_REQUEST['c_region_lv3']),
                'region_lv4' => intval($_REQUEST['c_region_lv4']),
                'bankzone' => htmlspecialchars($_REQUEST['bankzone_1']) ? htmlspecialchars($_REQUEST['bankzone_1']) : htmlspecialchars($_REQUEST['bank_bankzone']),
                'bankcard' => $bankcard, //处理卡号 只能是数字
                'bank_id' => intval($_REQUEST['bank_id']),
                'user_id' => intval($_REQUEST['id']),
            );
            if (isset($_REQUEST['card_type'])) {
                $userBankcardInfo['card_type'] = intval($_REQUEST['card_type']);
            }
            $userBankcardService = new UserBankcardService();
            $userbankInfo = $userBankcardService->getBankcard($userBankcardInfo['user_id']);
            try {
                // 支付降级
                if (\libs\utils\PaymentApi::isServiceDown())
                {
                    throw new \Exception(\libs\utils\PaymentApi::maintainMessage());
                }
                if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                    // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                    $isNew = $_REQUEST['bankcard_id'] > 0 ? false : true;
                    $paymentService = new PaymentService();
                    $group_id = isset($_REQUEST['group_id']) ? intval($_REQUEST['group_id']) : 0;
                    $bankcardInfo = $paymentService->getBankcardInfo($userBankcardInfo, $isNew, $group_id, $userBankcardInfo['user_id']);
                    // 发送请求
                    $paymentService->bankcardSync($userBankcardInfo['user_id'], $bankcardInfo);
                }
            } catch (\Exception $e) {
                throw $e;
            }
            // 构造提交数据
            $mode = 'INSERT';
            $condition = '';
            if ($_REQUEST['bankcard_id'] > 0) {
                $bankcardId = intval($_REQUEST['bankcard_id']);
                $userBankcardInfo['update_time'] = get_gmtime();
                $mode = 'UPDATE';
                $condition = " user_id = '{$userId}' AND id = '{$bankcardId}' ";
            } else {
                // 后台新增加修改银行卡置为已绑卡已验卡
                $userBankcardInfo['status'] = 1;
                $userBankcardInfo['verify_status'] = 1;
                $userBankcardInfo['create_time'] = get_gmtime();
            }

            // 编辑用户绑卡敏感信息时，记录管理员操作记录
            $operateLog = $this->_recordUserCardOperateLog($userBankcardInfo, $userbankInfo);
            // 更新用户绑卡信息
            Db::getInstance('firstp2p')->autoExecute('firstp2p_user_bankcard', $userBankcardInfo, $mode, $condition);
            $rs = Db::getInstance('firstp2p')->affected_rows();
            if ($rs && $mode === 'INSERT') {
                // 新增银行卡，支出返利
                $coupon_service = new CouponService();
                $coupon_service->regRebatePay(intval($_REQUEST['id']));
            }
            save_log('个人会员绑卡信息修改，会员id['.$userId.']更新成功', 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
            Db::getInstance('firstp2p')->commit();
        }
        catch (\Exception $e)
        {
            Db::getInstance('firstp2p')->rollback();
            $this->error($e->getMessage());
        }
        $this->success('编辑银行卡成功');
    }

    /**
     * 修改银行卡
     */
    public function do_edit_bank()
    {
        try{
            // 编辑银行卡的按钮标识
            $editType = isset($_REQUEST['edit_type']) ? (int)$_REQUEST['edit_type'] : 1;
            $userId = intval($_REQUEST['id']);
            $_REQUEST['bank_bankcard'] = $this->filterJs(trim($_REQUEST['bank_bankcard']));

            // 兼容普惠合规要求 不填银行卡号则取库中数据    @sunxuefeng
            if (empty($_REQUEST['bank_bankcard'])) {
                // 默认从库读
                $userBankcardInfo = (new UserBankcardService())->getBankcard($userId);
                $_REQUEST['bank_bankcard'] = !empty($userBankcardInfo) ? $userBankcardInfo->bankcard : '';
            }
            // END

            if (empty($_REQUEST['bank_bankcard'])) {
                throw new \Exception('银行卡号不能为空');
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName('adminDoEditBank');
            $operateLog = ['oldUserCardInfo'=>[], 'newUserCardInfo'=>[]];
            $supervisionAccountObj = new SupervisionAccountService();

            // 用户已在网信账户开户
            if ($editType === 1) {
                $isUcfpayUser = $supervisionAccountObj->isUcfpayUser($userId);
                if ($isUcfpayUser) {
                    // 拼接绑卡信息
                    $userBankcardInfo = array(
                        'card_name' => addslashes($_REQUEST['bank_card_name']), //开户姓名
                        'region_lv1' => intval($_REQUEST['c_region_lv1']),
                        'region_lv2' => intval($_REQUEST['c_region_lv2']),
                        'region_lv3' => intval($_REQUEST['c_region_lv3']),
                        'region_lv4' => intval($_REQUEST['c_region_lv4']),
                        'bankzone' => !empty($_REQUEST['bankzone_1']) ? addslashes($_REQUEST['bankzone_1']) : addslashes($_REQUEST['bank_bankzone']),
                        'bankcard' => addslashes($_REQUEST['bank_bankcard']), //处理卡号 只能是数字
                        'bank_id' => intval($_REQUEST['bank_id']),
                        'user_id' => $userId,
                        'card_type' => (int)$_REQUEST['card_type'] == 0 ? UserBankcardModel::CARD_TYPE_PERSONAL : UserBankcardModel::CARD_TYPE_BUSINESS, // 银行卡类型
                    );
                    isset($_REQUEST['branch_no']) && $userBankcardInfo['branch_no'] = addslashes($_REQUEST['branch_no']);
                    // 查询用户的绑卡记录
                    $userBankcardService = new UserBankcardService();
                    $userbankInfo = [];
                    if (isset($_REQUEST['bankcard_id']) && (int)$_REQUEST['bankcard_id'] > 0) {
                        $userbankInfo = $userBankcardService->getBankcard($userId);
                    }
                    // 编辑用户绑卡敏感信息时，记录管理员操作记录
                    $operateLog = $this->_recordUserCardOperateLog($userBankcardInfo, $userbankInfo);
    
                    // 支付降级
                    if (\libs\utils\PaymentApi::isServiceDown())
                    {
                        throw new \Exception(\libs\utils\PaymentApi::maintainMessage());
                    }
                    if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                        $isNew = isset($_REQUEST['bankcard_id']) && $_REQUEST['bankcard_id'] > 0 ? false : true;
                        $groupId = isset($_REQUEST['group_id']) ? intval($_REQUEST['group_id']) : 0;
    
                        // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                        $paymentService = new PaymentService();
                        $bankcardInfo = $paymentService->getBankcardInfo($userBankcardInfo, $isNew, $groupId, $userBankcardInfo['user_id']);
                        $gtm->addEvent(new \core\tmevent\supervision\UcfpayUpdateUserBankCardEvent($userBankcardInfo, $bankcardInfo));
                    }
                }
            }
            $gtm->addEvent(new \core\tmevent\supervision\WxUpdateUserBankCardEvent($_REQUEST));
            // 用户已在存管账户开户或者是存管预开户用户
            $svService = new \core\service\SupervisionService();
            if ($supervisionAccountObj->isSupervisionUser($userId) || $svService->isUpgradeAccount($userId)) {
                $gtm->addEvent(new \core\tmevent\supervision\SupervisionUpdateUserBankCardEvent($userId));
            }
            $result = $gtm->execute();
            if (!$result) {
                throw new \Exception($gtm->getError());
            }
            try {
                \core\service\partner\PartnerService::modifyCardNotify($userId);
            } catch (\Exception $e) {
                PaymentApi::log("AdminModifyCardNotify failed. Err:".$e->getMessage(), Logger::ERR);
            }
            $userService = new UserService($userId);
            if ($userService->isEnterpriseUser()) {
                //个人型企业用户自动增加存管白名单标签
                $userTagService = new UserTagService();
                $userTagService->addSupervisionStaticWhitelistTag($userId);
            }
            save_log('个人会员绑卡信息修改，会员id['.$userId.']更新成功，编辑按钮类型['.$editType.']', 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
            $this->success('编辑银行卡成功');
        } catch (\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, sprintf('adminDoEditBank,ExceptionCode:%s,ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            $this->error($e->getMessage());
        }
    }

    public function update() {
        B('FilterString');
        if (PaymentApi::isServiceDown())
        {
            $this->error(PaymentApi::maintainMessage());
        }

        // 支付用户信息修改同步
        try
        {
            // 判断用户是否已经开户
            $userId = intval($_POST['id']);
            $mobile = trim($_POST['mobile']);
            if (empty($userId)) {
                throw new \Exception('操作失败');
            }
            $form = D(MODULE_NAME);
            $data = $form->create();
            if (empty($data)) {
                throw new \Exception($form->getError());
            }

            if (!check_real_name($_POST['real_name'])) {
                throw new \Exception('真实姓名不能有特殊字符');
            }

            // 根据用户手机号，检查账户类型
            //if (!empty($data['mobile']) && substr($data['mobile'], 0, 1) !== '6' && $_POST['user_purpose'] != EnterpriseModel::COMPANY_PURPOSE_MIX) {
            //    throw new \Exception('普通用户的账户类型只能选择[借贷混合用户]');
            //}

            //开始验证有效性
            $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
            if (!check_empty($data['user_pwd']) && $data['user_pwd'] != $_REQUEST['user_confirm_pwd']) {
                throw new \Exception(L("USER_PWD_CONFIRM_ERROR"));
            }

            if(empty($data['group_id'])){
                throw new \Exception("会员组不能为空");
            }
            if(empty($data['new_coupon_level_id'])){
                throw new \Exception("服务等级不能为空");
            }
            if(!(new UserCouponLevelService())->checkLevelMatchGroupById($data['group_id'],$data['new_coupon_level_id'])){
                throw new \Exception("会员组和服务等级不匹配");
            }

            // 启动GTM管理器
            $userInformation = Db::getInstance('firstp2p', 'adminslave')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$userId}'");
            $gtm = new GlobalTransactionManager();
            $gtm->setName('adminUpdatePhone');

            // 可以编辑邮箱和手机号
            //unset($_REQUEST['email']);
            //unset($_REQUEST['mobile']);
            $_REQUEST['mobilepassed'] = 'true';

            // 国家区号
            $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
            $_REQUEST['mobile_code'] = $mobile_code_list[$_REQUEST['country_code']]['code'];
            // 用户的账户类型
            $_REQUEST['user_purpose'] = (int)$_REQUEST['user_purpose'];

            // 用户会员名称
            $log_info = M(MODULE_NAME)->where('id=' . intval($data['id']))->getField('user_name');

            // 网信理财-用户信息修改Event
            $gtm->addEvent(new WxUpdateUserInfoEvent($_REQUEST, $userInformation));

            // 已经开户的用户才跟先锋支付同步资料
            if (!empty($userInformation['payment_user_id']))
            {
                $params = [];
                $params['id'] = $userId;
                $params['newData'] = $_POST;
                $gtm->addEvent(new EventMaker([
                    'commit' => [(new \core\service\PaymentUserAccountService), 'modifyUserInfo', $params],
                ]));
            }
            // 如果开通存管户，并且手机号与原手机号不一致或者是存管预开户用户
            $svService = new \core\service\SupervisionService();
            if ((!empty($userInformation['supervision_user_id']) && $userInformation['mobile'] != $mobile) || $svService->isUpgradeAccount($userId))
            {
                $gtm->addEvent(new SupervisionUpdateUserMobileEvent($userId, $mobile));
            }
            // 如果通行证可用，同步通行证相关
            $passportService = new PassportService();
            if ($userInformation['mobile'] != $mobile && $passportInfo = $passportService->isLocalPassport($userId)) {
                $gtm->addEvent(new UpdateIdentityEvent($passportInfo['ppid'], $userInformation['mobile'], $mobile));
            }
            $gtmRet = $gtm->execute();
            if (!$gtmRet) {
                throw new \Exception($gtm->getError());
            }

            $deli['user_id']=htmlspecialchars($_POST['id']);
            $deli['address']=htmlspecialchars($_POST['deli_address']);
            $deli['name']=htmlspecialchars($_POST['deli_name']);
            $deli['mobile']=htmlspecialchars($_POST['deli_mobile']);
            $deli['postalcode']=htmlspecialchars($_POST['deli_postalcode']);
            $deli['deli_province']=htmlspecialchars($_POST['deli_province']);
            $deli['deli_city']=htmlspecialchars($_POST['deli_city']);
            $deli['deli_areaA']=htmlspecialchars($_POST['deli_areaA']);
            $deli['area']=$deli['deli_province'].':'.$deli['deli_city'];
            if ($deli['deli_city'] && $deli['area']!='0:0') {
                $deli['area'].=':'.$deli['deli_areaA'];
            } else {
                $deli['area']='';
            }
            $con['user_id']=$deli['user_id'];
            $vo1=M('delivery')->where($con)->find();
            if ($vo1) {
                M("delivery")->where('user_id='.$deli['user_id'])->save($deli);
            } elseif (!$vo1) {
                M("delivery")->add($deli);
            }

            // 编辑敏感信息时，记录管理员操作记录
            $operateLog = $this->_recordUserOperateLog($data);

            // 哈哈农庄专用,一会上一会不上。代码都写了八遍了。
            $hookParams = array(
                'id' => $data['id'],
                'oldMobile' => $userInformation['mobile'],
                'newMobile' => $data['mobile'],
                'real_name' => $data['real_name'],
                'group_id' => $data['group_id'],
            );
            // 哈哈农庄通知请求参数构造完毕

            //更新权限
            M("UserAuth")->where("user_id=" . $data['id'] . " and rel_id = 0")->delete();
            foreach ($_REQUEST['auth'] as $k => $v) {
                foreach ($v as $item) {
                    $auth_data = array();
                    $auth_data['m_name'] = $k;
                    $auth_data['a_name'] = $item;
                    $auth_data['user_id'] = $data['id'];
                    M("UserAuth")->add($auth_data);
                }
            }
            //开始更新is_effect状态
            M("User")->where("id=" . intval($_REQUEST['id']))->setField("is_effect", intval($_REQUEST['is_effect']));
            $user_id = intval($_REQUEST['id']);
            M("UserCateLink")->where("user_id=" . $user_id)->delete();
            foreach ($_REQUEST['cate_id'] as $cate_id) {
                $link_data = array();
                $link_data['user_id'] = $user_id;
                $link_data['cate_id'] = $cate_id;
                M("UserCateLink")->add($link_data);
            }

            //账户被禁用的情况下不更新优惠码绑定关系
            $is_effect = intval($_POST['is_effect']);
            if ($is_effect == 1) {
                //获取登录人session
                $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                //理财师更换组，修改邀请码绑定表里面的邀请码
                $couponBindService = new CouponBindService();
                $ret = $couponBindService->refreshByReferUserId(intval($_POST['id']),intval($adm_session["adm_id"]));
                if (!$ret) {
                    $this->error('投资人绑定邀请码更新失败！');
                }
            }

            // 回调钩子,哈哈农庄专用,一会上一会不上。代码都写了八遍了。
            if($hookParams['oldMobile'] != $hookParams['newMobile']){
                $tphs = new ThirdPartyHookService();
                $channel = 'HaHa';
                $url = $GLOBALS['sys_config']['CURL_HOOK_CONF'][$channel];
                $tphs->asyncCall($url, $hookParams, $channel);
            }

            save_log('个人会员信息修改，会员id['.$userId.']，会员名称[' . $log_info . ']' . L("UPDATE_SUCCESS"), 1, $operateLog['oldUserInfo'], $operateLog['newUserInfo']);
            $this->success(L("UPDATE_SUCCESS"));
        }
        catch (\Exception $e)
        {
            $this->error($e->getMessage());
        }
    }

    /**
     * 编辑实名信息页面
     */
    public function edit_identity() {
        $uid = isset($_REQUEST ['uid']) ? intval($_REQUEST ['uid']) : 0;
        $condition['is_delete'] = 0;
        $condition['id'] = $uid;
        $vo = M(MODULE_NAME)->where($condition)->find();

        $this->assign("idTypes", $GLOBALS['dict']['ID_TYPE']);
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 编辑实名信息
     */
    public function do_edit_identity() {
        $userId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $realName = isset($_POST['real_name']) ? addslashes(filter_zero_width_char($_POST['real_name'])) : '';
        $idType = isset($_POST['id_type']) ? intval($_POST['id_type']) : 0;
        $idno = isset($_POST['idno']) ? addslashes($_POST['idno']) : '';
        if (empty($userId) || empty($realName) || empty($idType) || empty($idno)) {
            $this->error('缺少参数');
        }
        if (!check_real_name($realName)) {
            $this->error('真实姓名不能有特殊字符');
        }
        $svService = new \core\service\SupervisionService();
        $svAccountService = new \core\service\SupervisionAccountService();
        $userInformation = Db::getInstance('firstp2p', 'adminslave')->getRow("SELECT * FROM firstp2p_user WHERE id = '{$userId}'");
        //写系统日志用
        $oldLog = ['user_id' => $userInformation['id'], 'real_name' => nameFormat($userInformation['real_name']), 'id_type' => $userInformation['id_type'], 'idno' => idnoFormat($userInformation['idno'])];
        $newLog = ['user_id' => $userInformation['id'], 'real_name' => nameFormat($realName), 'id_type' => $idType, 'idno' => idnoFormat($idno)];

        //是否存管用户
        if ($svService->isUpgradeAccount($userId) || $svAccountService->isSupervisionUser($userId)) {

            //检查是否正在处理
            if (UserIdentityModifyLogModel::instance()->isPending($userId)) {
                $this->error('该用户正在同步实名信息，请误重复操作');
            }

            //存管用户异步更新
            $gtm = new GlobalTransactionManager();
            $gtm->setName('adminUpdateUserIdentityApply');

            $orderId = Idworker::instance()->getId();
            //请求存管更新实名信息
            $updateParams = [
                'userId' => $userId,
                'orderId' => $orderId,
                'realName' => $realName,
                'certType' => isset(UserModel::$idCardType[$idType]) ? UserModel::$idCardType[$idType] : UserModel::$idCardType['default'],
                'certNo' => $idno,
            ];
            $gtm->addEvent(new SupervisionMemberInfoModifyEvent($updateParams));

            //添加实名变更日志
            $logParams = [
                'user_id' => $userId,
                'order_id' => $orderId,
                'real_name' => $realName,
                'id_type' => $idType,
                'idno' => $idno,
            ];
            $gtm->addEvent(new WxAddUserIdentityModifyLogEvent($logParams));

            $gtmRet = $gtm->execute();
            if (!$gtmRet) {
                $this->error('同步存管实名信息失败');
            }
            save_log('个人会员-修改实名，会员id['.$userId.']同步存管实名信息申请成功', 1, $oldLog, $newLog);
            $this->success('正在同步实名信息，请稍后查看审核结果');

        } else {

            //非存管用户同步更新
            $gtm = new GlobalTransactionManager();
            $gtm->setName('adminUpdateUserIdentity');

            //更新网信实名信息和绑卡开户名
            $updateParams = [
                'user_id' => $userId,
                'real_name' => $realName,
                'id_type' => $idType,
                'idno' => $idno,
            ];
            $gtm->addEvent(new WxUpdateUserIdentityInfoEvent($updateParams));

            // 已经开户的用户才跟先锋支付同步资料
            if (!empty($userInformation['payment_user_id']))
            {
                $params = [];
                $params['id'] = $userId;
                $params['newData'] = [
                    'real_name' => $realName,
                    'id_type' => $idType,
                    'idno' => $idno,
                    'mobile' => $userInformation['mobile'],
                    'mobile_code' => $userInformation['mobile_code'],
                ];

                $gtm->addEvent(new EventMaker([
                    'commit' => [(new \core\service\PaymentUserAccountService), 'modifyUserInfo', $params],
                ]));
            }
            // 通行证相关
            $passportService = new PassportService();
            if ($passportInfo = $passportService->isLocalPassport($userId)) {
                $oldCertInfo = [
                    'certType' => $passportService->idTypeMap[$userInformation['id_type']],
                    'certNo' => $userInformation['idno'],
                    'realname' => $userInformation['real_name']
                ];
                $newCertInfo = [
                    'certType' => $passportService->idTypeMap[$idType],
                    'certNo' => $idno,
                    'realname' => $realName
                ];
                $gtm->addEvent(new UpdateCertEvent($passportInfo['ppid'], $oldCertInfo, $newCertInfo));
            }

            $gtmRet = $gtm->execute();
            if (!$gtmRet) {
                $this->error('更新用户实名信息失败');
            }
            save_log('个人会员-修改实名，会员id['.$userId.']更新用户实名信息成功', 1, $oldLog, $newLog);

            $this->success(L("UPDATE_SUCCESS"));
        }

    }

    public function set_effect() {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        // feature/4937 风控修改用户状态为无效时，根据用户tag拒绝用户未处理的提现记录
        $userService = new UserService();
        $result = $userService->setUserEffect($id);
        if ($result['status'] != true) {
            return $this->ajaxReturn($result['setState'], '修改用户状态失败', 1);
        }
        save_log($result['username']. l("SET_EFFECT_" . $result['setState']), 1);
        return $this->ajaxReturn($result['setState'], '修改用户状态成功', 0);
    }

    public function set_coupon_disable() {
        $id = intval($_REQUEST['id']);
        $userService = new UserService();
        $result = $userService->setCouponDisable($id);
        if ($result['status'] != true) {
            return $this->ajaxReturn($result['coupon_disable'], '修改用户优惠码状态失败', 0);
        }
        save_log($id. l("SET_COUPON_DISABLE_" . $result['coupon_disable']), 1);
        return $this->ajaxReturn($result['coupon_disable'], '修改用户优惠码状态成功', 1);
    }

    public function account() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $this->display();
    }

    /**
     * 修改用户资金
     * @actionLock
     */
    public function modify_account() {
        $user_id = intval($_REQUEST['id']);
        $money = floatval($_REQUEST['money']);
        $score = intval($_REQUEST['score']);
        $point = intval($_REQUEST['point']);
        $quota = floatval($_REQUEST['quota']);
        $lock_money = floatval($_REQUEST['lock_money']);

        if ($lock_money != 0) {
            if (empty($_COOKIE['canLockNegative'])) {
                if ($lock_money > 0 && $lock_money > D("User")->where('id=' . $user_id)->getField("money")) {
                    $this->error("输入的冻结资金不得超过账户总余额");
                }

                if ($lock_money < 0 && abs($lock_money) > D("User")->where('id=' . $user_id)->getField("lock_money")) {
                    $this->error("输入的冻结资金不得大于已冻结的资金");
                }
            }
        }

        $msg = trim($_REQUEST['msg']) == '' ? l("ADMIN_MODIFY_ACCOUNT") : trim($_REQUEST['msg']);
        // TODO finance? 后台 会员与留言 修改账户金额信息 | 不同步
        modify_account(array('score' => $score, 'point' => $point, 'quota' => $quota, 'lock_money' => $lock_money), $user_id, l("ADMIN_MODIFY_ACCOUNT"), 1, $msg);
        if (floatval($_REQUEST['quota']) != 0) {
            $content = "您好，" . app_conf("SHOP_TITLE") . "审核部门经过综合评估您的信用资料及网站还款记录，将您的信用额度调整为：" . D("User")->where("id=" . $user_id)->getField('quota') . "元";

            $group_arr = array(0, $user_id);
            sort($group_arr);
            $group_arr[] = 4;
            $msg_data['content'] = $content;
            $msg_data['to_user_id'] = $user_id;
            $msg_data['create_time'] = get_gmtime();
            $msg_data['type'] = 0;
            $msg_data['group_key'] = implode("_", $group_arr);
            $msg_data['is_notice'] = 4;

            /*
            $GLOBALS['db']->autoExecute(DB_PREFIX . "msg_box", $msg_data);
            $id = $GLOBALS['db']->insert_id();
            $GLOBALS['db']->query("update " . DB_PREFIX . "msg_box set group_key = '" . $msg_data['group_key'] . "_" . $id . "' where id = " . $id);
            */
            $msgBoxService = new MsgBoxService();
            $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'],"", $msg_data['content']);
        }
        save_log(l("ADMIN_MODIFY_ACCOUNT"), 1);
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function work() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $work_info = M("UserWork")->where("user_id=" . $user_id)->find();
        $this->assign("work_info", $work_info);
        //地区列表
        $region_lv2 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where region_level = 2");  //二级地址
        if ($work_info) {
            foreach ($region_lv2 as $k => $v) {
                if ($v['id'] == intval($work_info['province_id'])) {
                    $region_lv2[$k]['selected'] = 1;
                    break;
                }
            }
        }
        $this->assign("region_lv2", $region_lv2);
        if ($work_info) {
            $region_lv3 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where pid = " . intval($work_info['province_id']));  //三级地址

            foreach ($region_lv3 as $k => $v) {
                if ($v['id'] == intval($work_info['city_id'])) {
                    $region_lv3[$k]['selected'] = 1;
                    break;
                }
            }
            $this->assign("region_lv3", $region_lv3);
        }
        $this->display();
    }

    //ajax方法获取用户的联系人 add by wenyanlei 20130627
    public function contact() {
        if (!isset($_REQUEST['id'])) {
            $this->error(L("IS_EFFECT_0"));
        }
        $relation_list = $GLOBALS['dict']['DICT_RELATIONSHIPS'];
        $contact = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "user_contact where is_delete = 0 and user_id = " . intval($_REQUEST['id']));
        $this->assign('user_id', intval($_REQUEST['id']));
        $this->assign("relation", $relation_list);
        $this->assign("contact_info", $contact);
        $this->display();
    }

    public function money_transfer() {
        if (!isset($_REQUEST['id'])) {
            $this->error(L("IS_EFFECT_0"));
        }
        $user_info = $GLOBALS['db']->getRow('select id,user_name,real_name,money from firstp2p_user where id =' . intval($_REQUEST['id']));
        $this->assign("user_info", $user_info);
        $this->display();
    }

    /**
     * 转账详情弹窗
     */
    public function money_transfer_detail() {
        $id = intval($_REQUEST['id']);
        $money = floatval($_REQUEST['money']);
        $info = getRequestString('info');
        $user_id = intval($_REQUEST['user_id']);
        if ($money <= 0) {
            $this->ajaxReturn('金额应大于0', '', 0, 'EVAL');
        }

        if (empty($user_id)) {
            $this->ajaxReturn('请输入转入用户ID', '', 0, 'EVAL');
        }
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $out_user = M("User")->where("id = $id")->find();

        $send_user = M("User")->where("id = '$user_id'")->find();
        if (!$send_user) {
            $this->ajaxReturn('转入用户不存在！', '', 0, 'EVAL');
        }
        if ($send_user['id'] == $id) {
            $this->ajaxReturn('不能转给自己', '', 0, 'EVAL');
        }
        $remain_money = bcsub($out_user['money'], $money, 2);
        if (bccomp($remain_money, 0, 2) === -1) {
            $this->ajaxReturn('转出账户余额不足', '', 0, 'EVAL');
        }
        $this->assign("out_user", $out_user);
        $this->assign("send_user", $send_user);
        $this->assign("money", $money);
        $this->assign("remain_money", $remain_money);
        $this->assign("info", $info);
        $this->display();

    }

    /**
     * 转账处理
     * @actionLock
     */
    public function money_transfer_do() {
        $id = intval($_REQUEST['id']);
        $money = floatval($_REQUEST['money']);
        $info = getRequestString('info');
        $user_id = stripslashes($_REQUEST['user_id']);
        if ($money <= 0) {
            $this->ajaxReturn('金额应大于0', '', 0);
        }

        if (empty($user_id)) {
            $this->ajaxReturn('请输入转入用户ID', '', 0);
        }
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $out_user = M("User")->where("id = $id")->find();

        $send_user = M("User")->where("id = '$user_id'")->find();
        if (!$send_user) {
            $this->ajaxReturn('转入用户不存在！', '', 0);
        }
        if ($send_user['id'] == $id) {
            $this->ajaxReturn('不能转给自己', '', 0);
        }
        // 如果开启对接先锋支付启用验证
        if (app_conf('PAYMENT_ENABLE')) {
            if (empty($send_user['payment_user_id'])) {
                $this->ajaxReturn('转入用户未在先锋支付开户！', '', 0);
            }
            if (empty($out_user['payment_user_id'])) {
                $this->ajaxReturn('转出用户未在先锋支付开户！', '', 0);
            }
        }
        $finance = new FinanceAuditModel();
        //转账进入审核列表里面
        $data = array();
        $data['into_name'] = $send_user['user_name'];
        $data['out_name'] = $out_user['user_name'];
        $data['money'] = $money;
        $data['create_time'] = get_gmtime();
        $data['apply_user'] = $adm_name;
        $data['info'] = $info;
        $finance->setRow($data);
        //开始事务
        $GLOBALS['db']->startTrans();
        try {
            $rs = $finance->save();
            if ($rs == false) {
                throw new Exception('事务数据更新错误！');
            }
            // TODO finance? 后台 会员列表 转账申请 | 不同步
            //modify_account(array('lock_money' => $money), $id, "转账申请", true, '您的账户向会员' . $user_name . '的账户转入金额' . $money . '元' . ' ' . $info);
            save_log('申请' . $out_user['user_name'] . '的账户向会员' . $send_user['user_name'] . '的账户转入金额' . $money . '元', 1);
            $GLOBALS['db']->commit();
        } catch (Exception $e) {
            $GLOBALS['db']->rollback();
            $this->ajaxReturn('参数错误！事务操作', '', 1);
        }
        $this->ajaxReturn('申请成功，等待财务通过！', '', 1);
        exit;
    }

    //修改联系人列表
    public function modify_contact() {
        $user_id = intval($_POST['user_id']);
        if (!$user_id) {
            $this->error(L("UPDATE_FAILED"));
        }
        $id = isset($_POST['id']) ? $_POST['id'] : array();
        $name = isset($_POST['name']) ? $_POST['name'] : array();
        $relation = isset($_POST['relation']) ? $_POST['relation'] : array();
        $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : array();
        //查询原有联系人
        $id_arr = $contact_arr = array();
        $contact_arr = $GLOBALS['db']->getAll("select id from " . DB_PREFIX . "user_contact where is_delete = 0 and user_id = " . $user_id);
        if (count($contact_arr) > 0) {
            foreach ($contact_arr as $key => $val) {
                $id_arr[$val['id']] = $val['id'];
            }
        }
        if (count($name) > 0) {
            foreach ($name as $key => $val) {
                $contact_arr = array(
                    'name' => isset($val) ? htmlspecialchars($val) : '',
                    'relation' => isset($relation[$key]) ? htmlspecialchars($relation[$key]) : '',
                    'mobile' => isset($mobile[$key]) ? htmlspecialchars($mobile[$key]) : ''
                );
                if (isset($id[$key]) && in_array($id[$key], $id_arr)) {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "user_contact", $contact_arr, "UPDATE", "id=" . intval($id[$key]));
                    unset($id_arr[$id[$key]]);
                } elseif ($contact_arr['name']) {
                    $contact_arr['user_id'] = $user_id;
                    $contact_arr['create_time'] = time();
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "user_contact", $contact_arr, "INSERT");
                }
            }
        }
        if (count($id_arr) > 0) {
            $ids = implode(',', $id_arr);
            $GLOBALS['db']->autoExecute(DB_PREFIX . "user_contact", array('is_delete' => '1'), "UPDATE", "id in (" . $ids . ")");
        }
        //save_log(l("ADMIN_MODIFY_ACCOUNT_contact"),1);
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function modify_work() {
        $data['user_id'] = intval($_REQUEST['id']);
        $data['office'] = trim($_REQUEST['office']);
        $data['jobtype'] = trim($_REQUEST['jobtype']);
        $data['province_id'] = intval($_REQUEST['province_id']);
        $data['city_id'] = intval($_REQUEST['city_id']);
        $data['officetype'] = trim($_REQUEST['officetype']);
        $data['officedomain'] = trim($_REQUEST['officedomain']);
        $data['officecale'] = trim($_REQUEST['officecale']);
        $data['position'] = trim($_REQUEST['position']);
        $data['salary'] = trim($_REQUEST['salary']);
        $data['workyears'] = trim($_REQUEST['workyears']);
        $data['workphone'] = trim($_REQUEST['workphone']);
        $data['workemail'] = trim($_REQUEST['workemail']);
        $data['officeaddress'] = trim($_REQUEST['officeaddress']);
        /* if(isset($_REQUEST['urgentcontact']))
          $data['urgentcontact'] = trim($_REQUEST['urgentcontact']);
          if(isset($_REQUEST['urgentrelation']))
          $data['urgentrelation'] = trim($_REQUEST['urgentrelation']);
          if(isset($_REQUEST['urgentmobile']))
          $data['urgentmobile'] = trim($_REQUEST['urgentmobile']);
          if(isset($_REQUEST['urgentcontact2']))
          $data['urgentcontact2'] = trim($_REQUEST['urgentcontact2']);
          if(isset($_REQUEST['urgentrelation2']))
          $data['urgentrelation2'] = trim($_REQUEST['urgentrelation2']);
          if(isset($_REQUEST['urgentmobile2']))
          $data['urgentmobile2'] = trim($_REQUEST['urgentmobile2']); */
        if ($GLOBALS['db']->getOne("SELECT count(*) FROM " . DB_PREFIX . "user_work WHERE user_id=" . $data['user_id']) == 0) {
            //添加
            $GLOBALS['db']->autoExecute(DB_PREFIX . "user_work", $data, "INSERT");
        } else {
            //编辑
            $GLOBALS['db']->autoExecute(DB_PREFIX . "user_work", $data, "UPDATE", "user_id=" . $data['user_id']);
        }
        $msg = trim($_REQUEST['msg']) == '' ? l("ADMIN_MODIFY_ACCOUNT_WORK") : trim($_REQUEST['msg']);
        save_log(l("ADMIN_MODIFY_ACCOUNT_WORK"), 1);
        $this->success(L("UPDATE_SUCCESS"));
    }

    /**
     * 资金记录交易类型
     */
    private $dealTypeMap = array(
        '0' => '',
        '1' => '通知贷',
        '2' => '交易所',
        '3' => '专享',
        '4' => '网贷',
        '5' => '小贷',
        '100' => '黄金'
    );


    /**
     * 读取用户ncfph存管资金明细
     */
    public function account_detail_supervision($accountDetailName = '网贷P2P账户明细') {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $this->assign('accountDetailName', $accountDetailName);
        $this->assign("log_info_type", UserLogModel::getLogInfoTypList());
        $from_backup = intval($_REQUEST['backup']);
        $page = $_REQUEST['page']? intval($_REQUEST['page']) - 1 : 0;
        $page_row = 20;
        $isNeedTotal = isset($_REQUEST['isNeedTotal']) ? 1 : 0;
        $this->assign("page", $page + 1);

        $param = array(
            'user_id' => $user_id,
            'account_type' => $user_info['user_purpose'],
            'log_time_start' => to_timespan($_REQUEST['log_time_start']),
            'log_time_end' => to_timespan($_REQUEST['log_time_end']),
            'from_backup' => $from_backup,
            'log_info' => $_REQUEST['log_info']? trim($_REQUEST['log_info']): '',
            'limit' => [$page * $page_row, $page_row],
            'isNeedTotal' => $isNeedTotal,
        );
        $list = ApiService::rpc("ncfph", "account/AccountLog", $param, false, 15);

        if($isNeedTotal == 1){
            $this->assign('totalRows', $list['total']);
            $this->assign('pageRow', $page_row);
            $list = empty($list) ? [] : $list['list'];

        }

        foreach ($list as $key => $item) {
                $list[$key]['deal_type'] = $this->dealTypeMap[$item['deal_type']];
                $list[$key]['log_info'] = str_replace("智多鑫","智多新",$list[$key]['log_info']);
                $list[$key]['note'] = str_replace("智多鑫","智多新",$list[$key]['note']);
                $list[$key]['user_name'] = $user_info['user_name'];
                $list[$key]['mobile'] = $user_info['mobile'];
            }
        $this->assign('list', $list);
        $this->display();
        return;
    }

    /**
     * 获取网信生活交易账户记录
     */
    public function account_detail_life($accountDetailName = '网信生活账户明细') {
        $userId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $userInfo = M("User")->getById($userId);
        $this->assign("user_info", $userInfo);
        $this->assign('accountDetailName', $accountDetailName);
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $param = array(
            'userId' => $userId,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        );
        $log_info = empty($_REQUEST['log_info']) ? '': trim($_REQUEST['log_info']);
        if (!empty($log_info)){
            $param['logInfo'] = $log_info;
        }
        if (!empty($_REQUEST['log_time_start'])){
            $param['startTime'] = strtotime($_REQUEST['log_time_start']);
        }

        if (!empty($_REQUEST['log_time_end'])){
            $param['endTime'] = strtotime($_REQUEST['log_time_end']);
        }

        // 指定管理后台调用
        $param['isAdm'] = 1;
        $request = new LifeRequestCommon();
        $request->setVars($param);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\User',
            'method' => 'getLifeUserLogList',
            'args' => $request,
        ));
        if(empty($response)) {
            $this->error("LifeRpc请求失败");
        }
        if($response['errorCode'] != 0) {
            $this->error("LifeRpc错误，errorCode：" . $response['errorCode'] . ' errorMsg：' . $response['errorMsg']);
        }
        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->assign('list', $response['data']['list']);
        $this->assign("log_info_type", $response['data']['logType']);
        $this->display();
        return;
    }

    /**
     * 获取黄金交易账户记录
     */
    public function account_detail_gold(){
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $request = new RequestCommon();
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $param = array(
            'userId' => $user_id,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        );
        $log_info = empty($_REQUEST['log_info']) ? '': trim($_REQUEST['log_info']);
        if (!empty($log_info)){
            $param['logInfo'] = $log_info;
        }
        if (!empty($_REQUEST['log_time_start'])){
            $param['startTime'] = strtotime($_REQUEST['log_time_start']);
        }

        if (!empty($_REQUEST['log_time_end'])){
            $param['endTime'] = strtotime($_REQUEST['log_time_end']);
        }


        $request->setVars($param);
        $response = $this->getRpc('goldRpc')->callByObject(array(
            'service' => 'NCFGroup\Gold\Services\User',
            'method' => 'getGoldUserLogListForAdmin',
            'args' => $request,
        ));
        if(empty($response)) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }
        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $this->assign ( "totalPages", $p->totalPages);
        $this->assign ( "totalRows", $p->totalRows);

        $this->assign('list',$response['data']['data']);
        $this->assign("log_info_type",$response['data']['logType']);
        $this->display();
        return;

    }
    /**
     * 用户资金明细列表
     */
    public function account_detail($userLogTypes = [0,1,2,3,5,100], $accountDetailName = '非网贷账户明细')
    {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $this->assign('accountDetailName', $accountDetailName);

        $this->assign("log_info_type", UserLogModel::getLogInfoTypList());

        $condition['user_id'] = $user_id;

        $log_time_start = to_timespan($_REQUEST['log_time_start']);
        $log_time_end = to_timespan($_REQUEST['log_time_end']);
        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        $this->assign("from_backup", $from_backup);

        if ($log_time_start > 0) {
            $condition['log_time'] = array('egt', $log_time_start);
        }

        if ($log_time_end > 0) {
            $condition['log_time'] = array('elt', $log_time_end);
        }

        if ($log_time_start > 0 && $log_time_end > 0) {
            $condition['log_time'] = array('between', array($log_time_start, $log_time_end));
        }

        if (trim($_REQUEST['log_info'])) {
            $condition['log_info'] = $_REQUEST['log_info'];
        }

        $condition['deal_type'] = array('in', $userLogTypes);

        $ul_model = new UserLogModel();
        if ($ul_model->isSplit == 2) {
            $i = $ul_model->getDescriptor($user_id);
            if ($from_backup)
                $model = MI("UserLog_" . $i, 'firstp2p_moved', 'slave');
            else
                $model = MI("UserLog_" . $i);
        } else {
            if ($from_backup)
                $model = MI("UserLog",  'firstp2p_moved', 'slave');
            else
                $model = MI("UserLog");
        }

        if (!empty($model)) {
            $list = $this->_list($model, $condition, 'log_time', false, false);

            foreach ($list as $key => $item) {
                $list[$key]['deal_type'] = $this->dealTypeMap[$item['deal_type']];
                $list[$key]['log_info'] = str_replace("智多鑫","智多新",$list[$key]['log_info']);
                $list[$key]['note'] = str_replace("智多鑫","智多新",$list[$key]['note']);
                $list[$key]['user_name'] = $user_info['user_name'];
                $list[$key]['mobile'] = $user_info['mobile'];
            }
            $this->assign('list', $list);
        }
        $this->display();
        return;
    }

    // 用户资产总额
    public function user_summary() {
        $user_id = intval($_REQUEST['uid']);
        $summary = user_statics($user_id);
        $user = \core\dao\UserModel::instance()->find($user_id);
        //$money_all = \libs\utils\Finance::addition(array($summary['stay'], $user['money'], $user['lock_money']), 2);

        // 普惠可用余额和冻结金额
        $accountInfo = PhAccountService::getInfoByUserIdAndType($user_id, $user['user_purpose']);
        $user['money'] = bcadd($user['money'], $accountInfo['money'], 2);
        $user['lock_money'] = bcadd($user['lock_money'], $accountInfo['lockMoney'], 2);

        $account_service = new \core\service\AccountService();
        $data = $account_service->getUserSummaryNew($user_id, true);
        $principal = $data['corpus'];

        //$principal_compound = DealLoanRepayModel::instance()->getSumByUserId($user_id, DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL, DealLoanRepayModel::STATUS_NOTPAYED);
        //$interest_compound = DealLoanRepayModel::instance()->getSumByUserId($user_id, DealLoanRepayModel::MONEY_COMPOUND_INTEREST, DealLoanRepayModel::STATUS_NOTPAYED);

        $dc_model = new DealCompoundModel();
        $compound_processing = $dc_model->getLoadMoneyNotRedeem($user_id);
        $total = \libs\utils\Finance::addition(array($principal, $user['money'], $user['lock_money'], $data['dt_load_money']), 2);


        $this->assign('user', $user);
        $this->assign('summary', $summary);
        $this->assign('money_all', $total);
        $this->assign('principal', $data['corpus']);
        //$this->assign('principal_compound', $principal_compound);
        $this->assign('interest', $data['income'] - $data['compound']['interest']);
        //$this->assign('interest_compound', $interest_compound);
        $this->assign('compound_processing', $compound_processing);
        $this->assign('total', $total);
        $this->display();
    }

    //用户明细导出
    public function account_export() {

        set_time_limit(0);
        @ini_set('memory_limit', '2048M');

        $user_id = intval($_REQUEST['id']);
        if ($user_id <= 0) {
            $this->error('导出失败');
        }

        $this->assign("log_info_type", core\dao\UserLogModel::getLogInfoTypList());

        $user_info = M("User")->getById($user_id);

        if (empty($user_info)) {
            $this->error('导出失败');
        }

        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);

        $condition = "user_id = $user_id AND is_delete = 0";

        $log_time_start = to_timespan($_REQUEST['log_time_start']);
        $log_time_end = to_timespan($_REQUEST['log_time_end']);

        if ($log_time_start > 0) {
            $condition .= " AND log_time >= '{$log_time_start}'";
        }

        if ($log_time_end > 0) {
            $condition .= " AND log_time <= '{$log_time_end}'";
        }

        if (trim($_REQUEST['log_info'])) {
            $condition .= " AND log_info = '" . trim($_REQUEST['log_info']) . "'";
        }

        if (isset($_REQUEST['deal_type']) && trim($_REQUEST['deal_type'])) {
            $condition .= ' AND deal_type IN ('.addslashes(trim($_REQUEST['deal_type'])).')';
        }

        $ul_model = new UserLogModel();
        $i = $ul_model->getDescriptor($user_id);
        if ($from_backup)
            $res = $GLOBALS['db']::getInstance('firstp2p_moved', 'slave')->query("SELECT * FROM firstp2p_user_log_{$i} WHERE $condition ORDER BY log_time DESC, id DESC");
        else
            $res = $GLOBALS['db']->get_slave()->query("SELECT * FROM firstp2p_user_log_{$i} WHERE $condition ORDER BY log_time DESC, id DESC");
        if ($res === false) {
            $this->error('账户明细为空');
        }
            //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportUserDetail',
                'analyze' => $condition
                )
        );

        $file_name = $user_info['user_name'] . ' 帐户明细';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        $title = array(
            "编号", "类型", "操作时间", "资金变动", "备注",
            "冻结(+)/解冻(-)", "可用余额", "账户资金总额", "冻结金额总额"
        );

        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));

        while ($log = $GLOBALS['db']->fetchRow($res)) {

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            if ($log['log_info'] == '提现失败' && $log['note'] == '' && $log['log_admin_id'] > 0)
            {
                $log['log_info'] = '提现还款';
            }

            $row = sprintf("%s||%s||\t%s||%s||%s||%s||%s||%s||%s", $log['id'], $log['log_info'], to_date($log['log_time']), format_price($log['money']), htmlspecialchars($log['note']), format_price($log['lock_money']), format_price($log['remaining_money']), format_price($log['remaining_total_money']), format_price($log['remaining_total_money'] - $log['remaining_money'])
            );
            fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
        }
        exit;
    }

    //网贷用户明细导出
    public function account_export_supervision() {
        set_time_limit(0);
        @ini_set('memory_limit', '2048M');

        $user_id = intval($_REQUEST['id']);
        if ($user_id <= 0) {
            $this->error('导出失败');
        }

        $this->assign("log_info_type", core\dao\UserLogModel::getLogInfoTypList());

        $user_info = M("User")->getById($user_id);
        if (empty($user_info)) {
            $this->error('导出失败');
        }

        $from_backup = intval($_REQUEST['backup']);
        $p = 0;
        $offset = 1;

        $param = array(
            'user_id' => $user_id,
            'account_type' => $user_info['user_purpose'],
            'log_time_start' => to_timespan($_REQUEST['log_time_start']),
            'log_time_end' => to_timespan($_REQUEST['log_time_end']),
            'from_backup' => $from_backup,
            'log_info' => $_REQUEST['log_info']? trim($_REQUEST['log_info']): '',
            'limit' => [$p, $offset],
        );

        $file_name = $user_info['user_name'] . ' 网贷帐户明细';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');
        $title = array(
            "编号", "类型", "操作时间", "资金变动", "备注",
            "冻结(+)/解冻(-)", "网贷P2P账户可用余额", "网贷P2P账户资金总额", "网贷P2P账户冻结总额"
        );
        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));

        $count = 0;// 计数器
        $max = 10000;// 每隔$max行，刷新一下输出buffer，不要太大，也不要太小
        $offset = 1000;
        $param['limit'] = [$p, $offset];
        while ($list = ApiService::rpc("ncfph", "account/AccountLog", $param, false, 15)) {

            $count += 1000;
            if ($count % $max == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }

            foreach ($list as $k => $v) {
                if ($v['log_info'] == '提现失败' && $v['note'] == '' && $v['log_admin_id'] > 0) {
                    $v = '提现还款';
                }

                    $row = sprintf("%s||%s||\t%s||%s||%s||%s||%s||%s||%s", $v['id'], $v['log_info'], to_date($v['log_time']), format_price($v['money']), htmlspecialchars($v['note']), format_price($v['lock_money']), format_price($v['remaining_money']), format_price($v['remaining_total_money']), format_price($v['remaining_total_money'] - $v['remaining_money'])
                );
                fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
            }

            $param['limit'] = [$p += $offset, $offset];
        }
        return;
    }

    /**
     * 读取护照通行证信息
     */
    public function passport_info() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);

        //籍贯
        $user_info['n_province'] = M("RegionConf")->where("id=" . $user_info['n_province_id'])->getField("name");
        $user_info['n_city'] = M("RegionConf")->where("id=" . $user_info['n_city_id'])->getField("name");

        //户口所在地
        $user_info['province'] = M("RegionConf")->where("id=" . $user_info['province_id'])->getField("name");
        $user_info['city'] = M("RegionConf")->where("id=" . $user_info['city_id'])->getField("name");

        $work_info = M("UserWork")->where("user_id=" . $user_id)->find();

        //护照通行证
        $passport = M("UserPassport")->where("uid=" . $user_id)->order('ctime desc')->find();
        if ($passport['type'] == 2) {
            $passport['typedesc'] = '护照';
        }else if ($passport['type'] == 3) {
            $passport['typedesc'] = '军官证';
        } else {
            $passport['typedesc'] = '';
        }
        if ($passport && $passport['file']) {
            $file_list = unserialize($passport['file']);
            foreach ($file_list as $key=>$file) {
                $replace = $this->is_cn ? 'www.firstp2p.cn' : 'www.ncfwx.com';
                $file_list[$key] = str_replace("um.firstp2p.com", $replace, $file);
            }

            $passport['file'] = $file_list;
        }

        $t_credit_file = M("UserCreditFile")->where("user_id=" . $user_id)->findAll();
        foreach ($t_credit_file as $k => $v) {
            $file_list = array();
            if ($v['file'])
                $file_list = unserialize($v['file']);
            if (is_array($file_list)) {
                $v['file_list'] = $file_list;
            }
            $credit_file[$v['type']] = $v;
        }

        $data = array(
            'user_info' => $user_info,
            'work_info' =>  $work_info,
            'passport' => $passport,
            'credit_file' => $credit_file
        );
        echo json_encode($data);
        exit;
    }

    public function passport_verify_log() {
        $map = [];
        if ($_REQUEST['user_name']) {
            $map['user_name'] =  array('eq',$_REQUEST['user_name']);
        }
        if ($_REQUEST['real_name']) {
            $map['real_name'] =  array('eq',$_REQUEST['real_name']);
        }
        if ($_REQUEST['mobile']) {
            $map['mobile'] =  array('eq',$_REQUEST['mobile']);
        }
        if ($_REQUEST['verify_status']) {
            $map['verify_status'] =  array('eq',$_REQUEST['verify_status']);
        }
        $apply_time_start = $apply_time_end = 0;
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = to_timespan($_REQUEST['apply_time_start']);
            $map['apply_time'] = array('egt', $apply_time_start);
        }
        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = to_timespan($_REQUEST['apply_time_end']);
            $map['apply_time'] = array('between', sprintf('%s,%s', $apply_time_start, $apply_time_end));
        }
        $verify_time_start = $verify_time_end = 0;
        if (!empty($_REQUEST['verify_time_start'])) {
            $verify_time_start = to_timespan($_REQUEST['verify_time_start']);
            $map['verify_time'] = array('egt', $verify_time_start);
        }
        if (!empty($_REQUEST['verify_time_end'])) {
            $verify_time_end = to_timespan($_REQUEST['verify_time_end']);
            $map['verify_time'] = array('between', sprintf('%s,%s', $verify_time_start, $verify_time_end));
        }
        if (!empty($_REQUEST['verify_admin'])) {
            $map['verify_admin'] =  array('eq',$_REQUEST['verify_admin']);
        }
        if (empty($_REQUEST ['_order'])) {
            $_REQUEST ['_order'] = "apply_time";
        }

        $this->_list(M("UserPassportVerifyLog"), $map);
        $this->assign("default_map", $map);
        $this->display();
    }

    public function passport_verify_export() {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');

        $datatime = date("YmdHis", time());
        $file_name = 'passport_verify_log_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $status = intval($_REQUEST['verify_status']);
        $user_name = $_REQUEST['user_name'];
        $real_name = $_REQUEST['real_name'];
        $mobile = $_REQUEST['mobile'];
        $admin = $_REQUEST['verify_admin'];

        $db = $GLOBALS['db'];
        $head = array('编号', '会员名称', '姓名', '手机号', '注册时间', '状态', '申请时间', '处理时间', '处理人', '审核状态','审核失败原因');
        $sql = 'SELECT user_id,user_name,real_name,mobile,create_time,is_effect,apply_time,verify_time,verify_admin,verify_status,failed_reason FROM `firstp2p_user_passport_verify_log` WHERE 1=1 ';
        if ($status) {
            $sql .= " AND verify_status = '{$status}' ";
        }
        if ($user_name) {
            $sql .= " AND user_name = '{$user_name}' ";
        }
        if ($real_name) {
            $sql .= " AND real_name = '{$real_name}' ";
        }
        if ($mobile) {
            $sql .= " AND mobile = '{$mobile}' ";
        }
        if ($admin) {
            $sql .= " AND verify_admin = '{$admin}' ";
        }

        $apply_sql = '';
        $apply_time_start = $apply_time_end = 0;
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = to_timespan($_REQUEST['apply_time_start']);
            $apply_sql = " AND creat_time >= '{$apply_time_start}' ";
        }

        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = to_timespan($_REQUEST['apply_time_end']);
            $apply_sql = " AND create_time BETWEEN '{$apply_time_start}' AND '{$apply_time_end}'";
        }
        if (!empty($apply_sql)) {
            $sql .= $apply_sql;
        }

        $verify_sql = '';
        $verify_time_start = $verify_time_end = 0;
        if (!empty($_REQUEST['verify_time_start'])) {
            $verify_time_start = to_timespan($_REQUEST['verify_time_start']);
            $verify_sql = " AND `verify_time`  >= '{$verify_time_start}' ";
        }

        if (!empty($_REQUEST['apply_time_end'])) {
            $verify_time_end = to_timespan($_REQUEST['apply_time_end']);
            $verify_sql = " AND `verify_time`  BETWEEN '{$verify_time_start}' AND '{$verify_time_end}'";
        }
        if (!empty($apply_sql)) {
            $sql .= $verify_sql;
        }

        $list = $db->getAll($sql);
        $status_arr = array( '1' => '审核通过', '2' => '审核失败');
        $effect_arr = array( '1' => '有效', '0' => '无效');
        foreach ($list as $k => $v) {
            $v['verify_status'] = $status_arr[$v['verify_status']];
            $v['is_effect'] = $effect_arr[$v['is_effect']];
            $v['create_time'] = to_date($v['create_time']);
            $v['apply_time'] = to_date($v['apply_time']);
            $v['verify_time'] = to_date($v['verify_time']);
            $v['failed_reason'] = isset(self::$identityFailType[intval($v['failed_reason'])]) ? self::$identityFailType[intval($v['failed_reason'])]['reason'] : $v['failed_reason'];
            $list[$k] = $v;
        }

        $fp = fopen('php://output', 'a');
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);



        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportFinanceAudit',
                'analyze' => $sql
            )
        );

        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        foreach ($list as $arr) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            foreach ($arr as $k => $v) {
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", $v);
            }
            fputcsv($fp, $arr);
        }
        exit;
    }

    public function passed() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);

        //籍贯
        $user_info['n_province'] = M("RegionConf")->where("id=" . $user_info['n_province_id'])->getField("name");
        $user_info['n_city'] = M("RegionConf")->where("id=" . $user_info['n_city_id'])->getField("name");

        //户口所在地
        $user_info['province'] = M("RegionConf")->where("id=" . $user_info['province_id'])->getField("name");
        $user_info['city'] = M("RegionConf")->where("id=" . $user_info['city_id'])->getField("name");

        $this->assign("user_info", $user_info);

        $work_info = M("UserWork")->where("user_id=" . $user_id)->find();
        $this->assign("work_info", $work_info);

        //护照通行证
        $passport = M("UserPassport")->where("uid=" . $user_id)->order('ctime desc')->find();

        if ($passport && $passport['file']) {
            $file_list = unserialize($passport['file']);
            $passport['file'] = $file_list;
        }
        $this->assign("passport", $passport);
        $t_credit_file = M("UserCreditFile")->where("user_id=" . $user_id)->findAll();
        foreach ($t_credit_file as $k => $v) {
            $file_list = array();
            if ($v['file'])
                $file_list = unserialize($v['file']);
            if (is_array($file_list)) {
                $v['file_list'] = $file_list;
            }
            $credit_file[$v['type']] = $v;
        }

        $this->assign("credit_file", $credit_file);
        $this->display();
        return;
    }

    /**
     * 用户审核
     */
    public function op_passed() {
        $user_id = intval($_REQUEST['user_id']);
        $field = $_REQUEST['field'];
        $field_array = array(
            "idcardpassed" => "身份认证",
            "workpassed" => "工作认证",
            "creditpassed" => "信用报告",
            "incomepassed" => "收入认证",
            "housepassed" => "房产认证",
            "carpassed" => "购车认证",
            "marrypassed" => "结婚认证",
            "edupassed" => "学历认证",
            "skillpassed" => "技术职称认证",
            "videopassed" => "视频认证",
            "mobiletruepassed" => "手机实名认证",
            "residencepassed" => "居住地证明",
        );
        if ($field_array[$field] == "") {
            exit();
        }

        $user_info = M("User")->getById($user_id);
        $this->assign("user_info", $user_info);
        $this->assign("field", $field);
        $this->assign("field_array", $field_array);
        $this->assign("failReasonTypeList",self::$identityFailType);
        $this->display();
        return;
    }

    /**
     * 提交用户审核结果
     * 审核状态 0 未审核 1通过 2没有通过 3提交资料
     */
    public function modify_passed() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $field = $_REQUEST['field'];
        $pass_info = array();
        if ($field == 'idcardpassed') {
            $pass_info = M("UserPassport")->where("uid=" . $user_id)->order('ctime desc')->find();
        }

        $ispassed = intval($_REQUEST[$field]);
        $field_array = array(
            "idcardpassed" => array('name' => "身份认证", "type" => "credit_identificationscanning"),
            "workpassed" => array('name' => "工作认证", "type" => "credit_contact"),
            "creditpassed" => array('name' => "信用报告", "type" => "credit_credit"),
            "incomepassed" => array('name' => "收入认证", "type" => "credit_incomeduty"),
            "housepassed" => array('name' => "房产认证", "type" => "credit_house"),
            "carpassed" => array('name' => "购车认证", "type" => "credit_car"),
            "marrypassed" => array('name' => "结婚认证", "type" => "credit_marriage"),
            "edupassed" => array('name' => "学历认证"),
            "skillpassed" => array('name' => "技术职称认证", "type" => "credit_titles"),
            "videopassed" => array('name' => "视频认证"),
            "mobiletruepassed" => array('name' => "手机认证", "type" => "credit_mobilereceipt"),
            "residencepassed" => array('name' => "居住地证明", "type" => "credit_residence"),
        );
        if ($field_array[$field] == "") {
            exit();
        }

        $success = true;
        $err = '';

        try {
            $data[$field] = $ispassed;
            if ($ispassed == 1) {
                $data[$field . '_time'] = get_gmtime();
            } else {
                $data[$field . '_time'] = 0;
            }

            $result_update = M('User')->where('id=' . $user_id)->data($data)->save();

            if ($ispassed > 0) {
                if ($ispassed == 1) {
                    // 如果开启对接先锋支付启用验证
                    //$content .="在".app_conf('SHOP_TITLE')."提交的".$field_array[$field]['name']."信息已经成功通过审核。";
                    $u_info = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "user WHERE id=" . $user_id);
                    $user_current_level = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "user_level where id = " . intval($u_info['level_id']));
                    $user_level = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "user_level where point <=" . intval($u_info['point']) . " order by point desc");
                    if ($user_current_level['point'] < $user_level['point']) {
                        $u_info['level_id'] = intval($user_level['id']);
                        $GLOBALS['db']->query("update " . DB_PREFIX . "user set level_id = " . $u_info['level_id'] . " where id = " . $u_info['id']);
                    }
                    if ($field == 'idcardpassed') {//用户身份认证信息审核
                        // TIPS：这块先硬编码吧，更改港澳台身份证信息的存储字段，需后期根据需求优化下
                        $idTypeMap = array("护照" => '2', "军官证" => '3', '香港' => '4', '澳門' => '4', "臺灣" => '6');
                        //获取用户通行证数据
                        if ($pass_info) {
                            $pass = array();
                            $pass['utime'] = get_gmtime();
                            $pass['status'] = $ispassed;
                            M("UserPassport")->where("uid=" . $user_id)->save($pass);
                            $data = array();
                            $data['real_name'] = $pass_info['name'];
                            $data['id_type'] = $idTypeMap[$pass_info['region']];
                            $data['idno'] = str_replace(' ', '', $pass_info['passportid']);
                            if ($data['id_type'] == '3') {
                                $data['idno'] = str_replace(' ', '', $pass_info['idno']);
                            }
                            $data['sex'] = $pass_info['sex'];
                            $birth = explode("-", $pass_info['birthday']);
                            $data['byear'] = $birth[0];
                            $data['bmonth'] = $birth[1];
                            $data['bday'] = $birth[2];
                            M('User')->where('id=' . $user_id)->data($data)->save();
                        }
                        if (app_conf('PAYMENT_ENABLE')) {
                            try {
                                $service = new PaymentService;
                                $rs = $service->register($user_id);
                                if ($rs === PaymentService::REGISTER_FAILURE) {
                                    throw new \Exception('开户检测没有通过，开户失败' . var_export($rs, true));
                                }
                            } catch (Exception $e) {
                                throw new \Exception('开户检测没有通过，开户失败' . var_export($rs, true));
                            }
                        }
                        $card_name = !empty($info) ? $info['name'] : $user_info['real_name'];
                        $bank = M('UserBankcard')->where('user_id=' . $user_id)->find();
                        //更新用户 关联银行信息
                        if ($bank) {
                            $bank['card_name'] = $card_name;
                            $bank['update_time'] = get_gmtime();
                            M('UserBankcard')->where('user_id=' . $user_id)->data($bank)->save();
                        } else {
                            $bank['card_name'] = $card_name;
                            $bank['create_time'] = get_gmtime();
                            $bank['user_id'] = $user_info['id'];
                            M('UserBankcard')->add($bank);
                        }
                    }
                } else {
                    if ($field == 'idcardpassed') {//更新通行证信息
                        $pass = array();
                        $pass['utime'] = get_gmtime();
                        $pass['status'] = $ispassed;
                        M("UserPassport")->where("uid=" . $user_id)->save($pass);
                    }
                }
            }
            if ($field == 'idcardpassed' && $ispassed == 0) {//更新通行证信息
                $pass = array();
                $pass['utime'] = get_gmtime();
                $pass['status'] = 0;
                M("UserPassport")->where("uid=" . $user_id)->save($pass);
            }
        } catch (\Exception $e) {
            $success = false;
            $err = $e->getMessage();
            \libs\utils\PaymentApi::log('用户审核失败' . $err);
        }
        save_log(l("ADMIN_MODIFY_CREDIT") . ":" . $user_info['user_name'] . " " . $field_array[$field]['name'], 1);

        //通行证审核记录
        if ($field == 'idcardpassed' && !empty($pass_info)) {
            $log_info = M("UserPassportVerifyLog")->where("user_id=" . $user_id)->find();
            \libs\utils\Logger::info(json_encode($log_info));
            if (empty($log_info)) {
                $log = array();
                $log['user_id'] = $user_id;
                $log['user_name'] = $user_info['user_name'];
                $log['real_name'] = ($success === true && $ispassed == 1) ? $pass_info['name'] : '';
                $log['mobile'] = $user_info['mobile'];
                $log['create_time'] = $user_info['create_time'];
                $log['apply_time'] = $pass_info['ctime'];
                $log['verify_time'] = get_gmtime();
                $log['is_effect'] = $user_info['is_effect'];
                $log['verify_status'] = ($success === true && $ispassed == 1) ? 1 : 2;
                $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                $log['verify_admin'] = $adm_session['adm_name'];
                $log['failed_reason'] = intval($_REQUEST['reason']) == 7 ? $_REQUEST['msg'] : $_REQUEST['reason'];
                $GLOBALS['db']->autoExecute('firstp2p_user_passport_verify_log', $log, 'INSERT');
            } else {
                $log = array();
                $log['real_name'] = ($success === true && $ispassed == 1) ? $pass_info['name'] : '';
                $log['verify_time'] = get_gmtime();
                $log['apply_time'] = $pass_info['utime'];
                $log['verify_time'] = get_gmtime();
                $log['verify_status'] = ($success === true && $ispassed == 1) ? 1 : 2;
                $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                $log['verify_admin'] = $adm_session['adm_name'];
                $log['failed_reason'] = intval($_REQUEST['reason']) == 7 ? $_REQUEST['msg'] : $_REQUEST['reason'];
                M("UserPassportVerifyLog")->where("user_id=" . $user_id)->save($log);
            }
        }

        if (!$success) {
            $this->error('审核失败:' . $err);
        }
        if ($field == 'idcardpassed') {
            if ($ispassed == 1) {
                $msg_content = array(
                    'ctime' => !empty($pass_info) ? date("Y-m-d",$pass_info['ctime']) : date('Y-m-d'),
                );
                // SMSSend 后台审核身份证通过
                SmsServer::instance()->send($user_info['mobile'], 'TPL_SMS_IDCARDPASSED', $msg_content, $user_id);
            }elseif ($ispassed == 2) {
                $log = M("UserPassportVerifyLog")->where("user_id=" . $user_id)->find();
                $reasonId = intval($log['failed_reason']) == 0 ? $log['failed_reason'] : intval($log['failed_reason']);
                $failed_reason = is_numeric($reasonId) ? self::$identityFailType[intval($log['failed_reason'])]['reasonDesc'] : $reasonId;
                $msg_content = array(
                    'ctime' => !empty($pass_info) ? date("Y-m-d",$pass_info['ctime']) : date('Y-m-d'),
                    'failed_reason' => $failed_reason,
                );
                // SMSSend 后台审核身份证通过
                SmsServer::instance()->send($user_info['mobile'], 'TPL_SMS_UNIDCARDPASSED', $msg_content, $user_id);
            }
        }
        $this->success(L("UPDATE_SUCCESS"));
    }
    /* 添加发送消息 */

    function addSendMsg($info) {
        $content = array();
        $GLOBALS['tmpl']->assign("info", $info);
        $content['username'] = $info['user_name'];
        $content['content'] = $info['content'];

        \libs\sms\SmsServer::instance()->send($info['dest'], $info['tpl'], $content, $info['user_id']);
    }

    public function foreverdelete_account_detail() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $islot = $_REQUEST ['islot'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M("UserLog")->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['id'];
            }
            if ($info)
                $info = implode(",", $info);

            if ($islot == 1) {
                $list = M("UserLog")->where($condition)->delete();
            } else {
                $list = M("UserLog")->where($condition)->save(array('is_delete' => 1));
            }
            if ($list !== false) {
                save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
                $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("FOREVER_DELETE_FAILED"), 0);
                $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function export_csv($page = 1) {
        // 禁用导出功能#FIRSTPTOP-4818
        $this->error('该功能已被禁用');

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $limit = (($page - 1) * intval(app_conf("BATCH_PAGE_SIZE"))) . "," . (intval(app_conf("BATCH_PAGE_SIZE")));
        //定义条件
        $map[DB_PREFIX . 'user.is_delete'] = 0;

        if (intval($_REQUEST['group_id']) > 0) {
            $map[DB_PREFIX . 'user.group_id'] = intval($_REQUEST['group_id']);
        }

        if (trim($_REQUEST['user_name']) != '') {
            $map[DB_PREFIX . 'user.user_name'] = array('like', '%' . trim($_REQUEST['user_name']) . '%');
        }
        if (trim($_REQUEST['email']) != '') {
            $map[DB_PREFIX . 'user.email'] = array('like', '%' . trim($_REQUEST['email']) . '%');
        }
        if (trim($_REQUEST['mobile']) != '') {
            $map[DB_PREFIX . 'user.mobile'] = trim($_REQUEST['mobile']);
        }
        if (trim($_REQUEST['pid_name']) != '') {
            $pid = M("User")->where("user_name='" . trim($_REQUEST['pid_name']) . "'")->getField("id");
            $map[DB_PREFIX . 'user.pid'] = $pid;
        }
        if (trim($_REQUEST['invite_code']) != '') {
            $map[DB_PREFIX . 'user.invite_code'] = trim($_REQUEST['invite_code']);
        }
        /*
          $list = M(MODULE_NAME)
          ->where($map)
          ->join(DB_PREFIX.'user_group ON '.DB_PREFIX.'user.group_id = '.DB_PREFIX.'user_group.id')
          ->field(DB_PREFIX.'user.*,'.DB_PREFIX.'user_group.name')
          ->order('id')
          ->limit($limit)->findAll();
         */
        $list = MI(MODULE_NAME)
                ->where($map)
                ->join(DB_PREFIX . 'user_group ON ' . DB_PREFIX . 'user.group_id = ' . DB_PREFIX . 'user_group.id')
                ->field(DB_PREFIX . 'user.*,' . DB_PREFIX . 'user_group.name')
                ->order('id')
                ->findAll();
        if ($list) {

            //register_shutdown_function(array(&$this, 'export_csv'), $page+1);

            $user_value = array('id' => '""', 'user_name' => '""', 'email' => '""', 'mobile' => '""', 'group_id' => '""');
            if ($page == 1)
                $content = iconv("utf-8", "gbk", "编号,用户名,电子邮箱,手机号,会员组,会员余额,冻结资金,最后登录IP,注册时间,最后登录时间,姓名,身份证号码,开户名,银行卡号,银行,开户国家,开户省,开户市,开户区,开户网点");
            //开始获取扩展字段
            $extend_fields = M("UserField")->order("sort desc")->findAll();
            foreach ($extend_fields as $k => $v) {
                $user_value[$v['field_name']] = '""';
                if ($page == 1)
                    $content = $content . "," . iconv('utf-8', 'gbk', $v['field_show_name']);
            }
            if ($page == 1)
                $content = $content . "\n";

            foreach ($list as $k => $v) {
                // 获取银行卡信息
                $bankInfo = M("UserBankcard")->where("user_id=" . $v['id'])->find();
                $bankInfo['region_lv1_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv1'])->getField("name");
                $bankInfo['region_lv2_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv2'])->getField("name");
                $bankInfo['region_lv3_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv3'])->getField("name");
                $bankInfo['region_lv4_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv4'])->getField("name");
                $bankInfo['bank_name'] = M("bank")->where("id=" . $bankInfo['bank_id'])->getField("name");
                $user_value = array();
                $user_value['id'] = iconv('utf-8', 'gbk', '"' . $v['id'] . '"');
                //$user_value['user_name'] = iconv('utf-8','gbk','"' . $v['user_name'] . '"');
                //为兼容线上用户id为253的用户名（包含类似空格的特殊字符）
                $user_value['user_name'] = iconv('utf-8', 'gbk', '"' . str_replace(' ', ' ', $v['user_name']) . '"');
                $user_value['email'] = iconv('utf-8', 'gbk', '"' . $v['email'] . '"');
                $user_value['mobile'] = "\"\t" . $v['mobile'] . "\"";
                $user_value['group_id'] = iconv('utf-8', 'gbk', '"' . $v['name'] . '"');
                $user_value['money'] = iconv('utf-8', 'gbk', '"' . $v['money'] . '"');
                $user_value['lock_money'] = iconv('utf-8', 'gbk', '"' . $v['lock_money'] . '"');
                $user_value['login_ip'] = iconv('utf-8', 'gbk', '"' . $v['login_ip'] . '"');
                $user_value['create_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['create_time']) . '"');
                $user_value['login_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['login_time']) . '"');
                $user_value['real_name'] = iconv('utf-8', 'gbk', '"' . $v['real_name'] . '"');
                $user_value['idno'] = "\"\t" . $v['idno'] . "\"";
                $user_value['card_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['card_name'] . '"');
                $user_value['bankcard'] = "\"\t" . $bankInfo['bankcard'] . "\"";
                $user_value['bank_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['bank_name'] . '"');
                $user_value['region_lv1_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv1_name'] . '"');
                $user_value['region_lv2_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv2_name'] . '"');
                $user_value['region_lv3_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv3_name'] . '"');
                $user_value['region_lv4_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv4_name'] . '"');
                $user_value['bankzone'] = iconv('utf-8', 'gbk', '"' . $bankInfo['bankzone'] . '"');

                //过滤敏感信息
                if(!empty($user_value['idno'])){
                    $user_value['idno'] = idnoFormat($user_value['idno']);
                }
                if(!empty($item['user_name'])){
                    $item['user_name'] = adminMobileFormat($item['user_name']);
                }
                if(!empty($user_value['mobile'])){
                    $user_value['mobile'] = adminMobileFormat($user_value['mobile']);
                }
                if(!empty($user_value['email'])){
                    $user_value['email'] = adminEmailFormat($user_value['email']);
                }
                if(!empty($user_value['bankcard'])){
                    $user_value['bankcard'] = formatBankcard($user_value['bankcard']);
                }
                //取出扩展字段的值
                $extend_fieldsval = M("UserExtend")->where("user_id=" . $v['id'])->findAll();
                foreach ($extend_fields as $kk => $vv) {
                    foreach ($extend_fieldsval as $kkk => $vvv) {
                        if ($vv['id'] == $vvv['field_id']) {
                            $user_value[$vv['field_name']] = iconv('utf-8', 'gbk', '"' . $vvv['value'] . '"');
                            break;
                        }
                    }
                }
                $content .= implode(",", $user_value) . "\n";
            }
            // 获取最后一个用户id号
            $n = count($list) - 1;
            $uid = $list[$n]['id'];
            $uid = str_pad($uid, 6, "0", STR_PAD_LEFT);
            $filename = 'user-' . $uid . '_' . to_date(get_gmtime(), 'Y-m-d_H-i-s');
            //记录导出日志
            setLog(
                array(
                    'sensitive' => 'exportuser',
                    'analyze' => $map
                    )
            );

            header("Content-Disposition: attachment; filename=" . $filename . ".csv");
            echo $content;
        } else {
            if ($page == 1)
                $this->error(L("NO_RESULT"));
        }
    }

    function lock_money() {
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);

        $this->assign("user_info", $user_info);
        $this->display();
    }

    /**
     * 重置银行卡信息
     */
    function resetbank() {
        try
        {
            $id = intval($_POST['id']);
            $uid = intval($_POST['uid']);
            $bankcard = $_POST['bankcard'];
            if (empty($id) || empty($uid) || empty($bankcard))
            {
                throw new \Exception('参数错误');
            }

            $accountService = new \core\service\SupervisionAccountService();
            $result = $accountService->memberCardUnbind($uid, $bankcard, true);

            // 更新本地数据
            $data = [];
            $data['bank_id'] = '';
            $data['bankcard'] = '';
            $data['bankzone'] = '';
            $data['status'] = 0;
            $data['card_name'] = '';
            $data['region_lv1'] = '';
            $data['region_lv2'] = '';
            $data['region_lv3'] = '';
            $data['region_lv4'] = '';
            $data['image_id'] = '';
            $data['update_time'] = get_gmtime();
            $data['verify_status'] = 0;

            // 编辑用户绑卡敏感信息时，记录管理员操作记录
            $operateLog = $this->_recordUserCardOperateLog($data, array(), $uid);

            if (true == $result) {
                self::$msg = ['code'=> '0000', 'msg' => '解除绑卡成功'];
            } else {
                self::$msg = ['code'=> '4000', 'msg' => '解除绑卡失败'];
            }
            // 记录操作日志
            save_log('个人会员绑卡信息-重置银行卡，会员id['.$uid.']解除绑卡成功', 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
            PaymentApi::log('Admin Resetbankcard success, userId'.$uid);
        }
        catch (\Exception $e)
        {
            self::$msg = ['code' => '4000', 'msg' => $e->getMessage()];
            PaymentApi::log('Admin Resetbankcard fail, userId'.$uid.', msg:'.$e->getMessage());
        }
        echo json_encode(self::$msg);
        exit;
    }

    function check_merchant_name() {
        $merchant_name = addslashes(trim($_REQUEST['merchant_name']));
        $ajax = intval($_REQUEST['ajax']);
        $result = $GLOBALS['db']->getOne("select count(*) from " . DB_PREFIX . "supplier_account where account_name = '" . $merchant_name . "'");
        if (intval($result) == 0)
            $this->error(l("MERCHANT_NAME_NOT_EXIST"), $ajax);
        else
            $this->success("", $ajax);
    }


    /**
     * 重置四要素验证状态
     */
    public function resetVerifyStatus()
    {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            echo json_encode(['code' => '4000', 'msg' => PaymentApi::maintainMessage()]);
            exit;
        }
        $id = intval($_POST['id']);
        $verifyStatus = intval($_POST['verify_status']);
        $uid = intval($_POST['uid']);
        do {
            if (!empty($id) && isset($verifyStatus)) {
                $value = $verifyStatus == 0 ? 1 : 0;
                $updateTime = get_gmtime();

                // 编辑用户绑卡敏感信息时，记录管理员操作记录
                $operateLog = $this->_recordUserCardOperateLog(array('verify_status'=>$value), array(), $uid);

                // 用户银行卡审核状态已经通过的时候
                $sql = "UPDATE `firstp2p_user_bankcard` SET verify_status = {$value}, update_time = {$updateTime}  WHERE id = {$id}";
                $GLOBALS['db']->query($sql);
                self::$msg['msg'] = $value;
                // 记录操作日志
                save_log('个人会员绑卡信息-重置四要素验卡状态，会员id['.$uid.']操作成功', 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
            } else {
                self::$msg = array('code' => 4000, 'msg' => '参数错误');
            }
        } while (0);
        echo json_encode(self::$msg);

    }

    //重置用户管理银行 状态 add caolong-2013-1-23
    public function resetStatus() {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            echo json_encode(['code' => '4000', 'msg' => PaymentApi::maintainMessage()]);
            exit;
        }
        $id = intval($_POST['id']);
        $status = intval($_POST['status']);
        $uid = intval($_POST['uid']);
        do {
            if (!empty($id) && isset($status)) {
                $value = $status == 0 ? 1 : 0;
                $updateTime = get_gmtime();

                // 编辑用户绑卡敏感信息时，记录管理员操作记录
                $operateLog = $this->_recordUserCardOperateLog(array('status'=>$value), array(), $uid);

                // 用户银行卡审核状态已经通过的时候，进行同步
                $sql = "UPDATE `firstp2p_user_bankcard` SET status = {$value}, update_time = {$updateTime}  WHERE id = {$id}";
                $GLOBALS['db']->query($sql);
                self::$msg['msg'] = $value;
                // 记录操作日志
                save_log('个人会员绑卡信息-重置绑卡状态，会员id['.$uid.']操作成功', 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
            } else {
                self::$msg = array('code' => 4000, 'msg' => '参数错误');
            }
        } while (0);
        echo json_encode(self::$msg);
    }

    /**
     * 自动换卡列表
     */
    public function autoAuditBankInfo()
    {
        $condition = array('auto_audit' => 1);//自动换卡

        // 会员名称
        $user_name = getRequestString('user_name');
        if ($user_name) {
            $userInfo = M('User')->where(array('user_name' => $user_name))->find();
            $condition['user_id'] = $userInfo['id'];
        }
        // 手机号
        $mobile = getRequestString('mobile');
        if ($mobile) {
            $userInfo = M('User')->where(array('mobile' => $mobile))->find();
            $condition['user_id'] = $userInfo['id'];
        }
        // 姓名(会员名称/手机号，没有查到用户时再查)
        $real_name = getRequestString('real_name');
        if (empty($condition['user_id']) && !empty($real_name)) {
            $sql = sprintf('SELECT group_concat(id) FROM `%s` WHERE real_name=\'%s\'', DB_PREFIX . 'user', trim($real_name));
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $condition['user_id'] = !empty($ids) ? array('in', $ids) : 0;
        }

        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $condition['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $condition['create_time'] = array('elt', $apply_end);
        }

        if (!empty($_REQUEST['apply_start']) && !empty($_REQUEST['apply_end'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $condition['create_time'] = array('BETWEEN', array($apply_start, $apply_end));
        }

        if (!empty($_REQUEST['face_verified'])) {
            if ($_REQUEST['face_verified'] == 1) {
                $condition['total_assets'] = array('gt', 0);
            } else if ($_REQUEST['face_verified'] == 2) {
                $condition['total_assets'] = 0;
            }
        }

        //每页显示条数
        $_REQUEST['listRows'] = isset($_REQUEST['export']) ? 100000 : 100;

        $list = $this->_list(D('UserBankcardAudit'), $condition, 'id', false, false);

        // 拼接User表信息
        foreach ($list as $key => $item) {
            $userInfo = M('User')->where(array('id' => $item['user_id']))->find();
            $list[$key]['user_name'] = $userInfo['user_name'];
            $list[$key]['real_name'] = $userInfo['real_name'];
            $list[$key]['mobile'] = $userInfo['mobile'];
            $list[$key]['face_verified'] = $item['status']==3&&$item['total_assets']>0 ? '通过' : '--';

            // 拼接申请次数
            $count = M('UserBankcardAudit')->where(array('user_id' => $item['user_id'], 'auto_audit' => 1))->count();
            $list[$key]['count'] = $count;
        }

        if (isset($_REQUEST['export'])) {
            //记录导出日志
            setLog(
                array(
                    'sensitive' => 'exportuser',
                    'analyze' => implode('|||', $GLOBALS['db']->queryLog),
                    )
            );
            $title = '编号,用户名,姓名,手机号,申请时间';
            $content = iconv('utf-8', 'gbk', $title) . "\n";
            foreach ($list as $k => $v) {
                $row = '';
                $row .= $v['id'];
                $row .= ','.$v['user_name'];
                $row .= ','.$v['real_name'];
                $row .= ','.$v['mobile'];
                $row .= ",\"" . to_date($v['create_time']) . "\"";
                $row = strip_tags($row);
                $content .= iconv('utf-8', 'gbk', $row) . "\n";
            }
            $datatime = date("YmdHis", get_gmtime());
            header("Content-Disposition: attachment; filename=auto_bank_audit_{$datatime}.csv");
            echo $content;

            exit;
        }

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 银行卡修改申请列表
     * 人工换卡
     */
    public function AuditBankInfo()
    {
        $condition = array('auto_audit' => 0);//人工换卡
        $status = getRequestInt("status");

        if ($status) {//status != 0
            $condition['status'] = $status;
        }
        // 会员名称
        $user_name = getRequestString('user_name');
        if ($user_name) {
            $userInfo = M('User')->where(array('user_name' => $user_name))->find();
            $condition['user_id'] = $userInfo['id'];
        }
        // 手机号
        $mobile = getRequestString('mobile');
        if ($mobile) {
            $userInfo = M('User')->where(array('mobile' => $mobile))->find();
            $condition['user_id'] = $userInfo['id'];
        }
        // 姓名(会员名称/手机号，没有查到用户时再查)
        $real_name = getRequestString('real_name');
        if (empty($condition['user_id']) && !empty($real_name)) {
            $sql = sprintf('SELECT group_concat(id) FROM `%s` WHERE real_name=\'%s\'', DB_PREFIX . 'user', trim($real_name));
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            $condition['user_id'] = !empty($ids) ? array('in', $ids) : 0;
        }

        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $condition['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $condition['create_time'] = array('elt', $apply_end);
        }

        if (!empty($_REQUEST['apply_start']) && !empty($_REQUEST['apply_end'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $condition['create_time'] = array('BETWEEN', array($apply_start, $apply_end));
        }

        if (!empty($_REQUEST['deal_start'])) {
            $deal_start = to_timespan($_REQUEST['deal_start']);
            $condition['audit_time'] = array('egt', $deal_start);
        }

        if (!empty($_REQUEST['deal_end'])) {
            $deal_end = to_timespan($_REQUEST['deal_end']);
            $condition['audit_time'] = array('elt', $deal_end);
        }

        if (!empty($_REQUEST['deal_start']) && !empty($_REQUEST['deal_end'])) {
            $deal_start = to_timespan($_REQUEST['deal_start']);
            $deal_end = to_timespan($_REQUEST['deal_end']);
            $condition['audit_time'] = array('BETWEEN', array($deal_start, $deal_end));
        }

        if (!empty($_REQUEST['admin_name'])) {
            $condition['admin'] = addslashes(trim($_REQUEST['admin_name']));
        }

        //每页显示条数
        $_REQUEST['listRows'] = isset($_REQUEST['export']) ? 100000 : 100;

        $list = $this->_list(D('UserBankcardAudit'), $condition, 'id', false, false);

        //拼接User表信息
        foreach ($list as $key => $item) {
            $userInfo = M('User')->where(array('id' => $item['user_id']))->find();
            $list[$key]['user_name'] = $userInfo['user_name'];
            $list[$key]['real_name'] = $userInfo['real_name'];
            $list[$key]['mobile'] = $userInfo['mobile'];
            // $list[$key]['like_ratio'] = floatval($item['like_ratio'] * 100) . '%';

            //拼接申请次数
            $count = M('UserBankcardAudit')->where(array('user_id' => $item['user_id'], 'auto_audit' => 0))->count();
            $list[$key]['count'] = $count;
        }

        if (isset($_REQUEST['export'])) {
            //记录导出日志
            setLog(
                array(
                    'sensitive' => 'exportuser',
                    'analyze' => implode('|||', $GLOBALS['db']->queryLog),
                    )
            );
            $title = '编号,用户名,姓名,手机号,申请时间,处理时间,处理人,状态';
            $content = iconv('utf-8', 'gbk', $title) . "\n";
            $status = array(
                0 => '未审核',
                1 => '审核中',
                2 => '审核失败',
                3 => '审核通过',
            );
            foreach ($list as $k => $v) {
                $row = '';
                $row .= $v['id'];
                $row .= ','.$v['user_name'];
                $row .= ','.$v['real_name'];
                $row .= ','.$v['mobile'];
                $row .= ",\"" . to_date($v['create_time']) . "\"";
                $row .= ",\"" . to_date($v['audit_time']) . "\"";
                $row .= ','.$v['admin'];
                $row .= ','.$status[$v['status']];
                $row = strip_tags($row);
                $content .= iconv('utf-8', 'gbk', $row) . "\n";
            }
            $datatime = date("YmdHis", get_gmtime());
            header("Content-Disposition: attachment; filename=bank_audit_{$datatime}.csv");
            echo $content;

            exit;
        }

        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->assign('failReasonTypeList', self::$auditBankInfoFailType);
        $this->display();
    }

    /**
     * 银行卡信息审核
     */
    public function BankAuditing() {
        $id = intval($_POST['aid']);
        $status = intval($_POST['status']);
        if (empty($id)) {
            $this->error('参数错误');
        }
        // 未选择审核状态或审核状态参数不对
        if (!isset($status) || !in_array($status, array(2,3))) {
            $this->error('请先选择审核操作项');
        }

        // 审核的详细描述
        $description = $this->filterJs($_POST['description']);
        $adm_info = es_session::get(md5(conf("AUTH_KEY")));
        // 审核通过
        if ($status === 3) {
            $sql_str = ",description ='{$description}',audit_time = " . get_gmtime() . ",admin = '{$adm_info['adm_name']}'";
        }else{
            // 审核失败原因类型
            $failReasonType = intval($_POST['failReasonType']);
            // 审核失败时，使用系统设定的描述
            if (empty(self::$auditBankInfoFailType[$failReasonType])) {
                $this->error('审核状态参数错误');
            }
            if (!empty(self::$auditBankInfoFailType[$failReasonType]['reasonDesc'])) {
                $description = self::$auditBankInfoFailType[$failReasonType]['reasonDesc'];
            }
            $failReason = !empty(self::$auditBankInfoFailType[$failReasonType]['reason']) ? self::$auditBankInfoFailType[$failReasonType]['reason'] : '';
            $sql_str = ",fail_reason='{$failReason}',description ='{$description}',audit_time = " . get_gmtime() . ",admin = '{$adm_info['adm_name']}'";
        }

        // 审核更新俩表加事务
        $GLOBALS['db']->startTrans();
        try {
            $sql = "UPDATE `firstp2p_user_bankcard_audit` SET STATUS =" . $status . " " . $sql_str . " WHERE id =" . $id;
            $GLOBALS['db']->query($sql);
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows == 0) {
                throw new Exception("审核状态更新失败");
            }

            // 审核通过
            if ($status === 3) {  //更新数据
                $result = $this->updateUserBankCard($id);
                if (!$result) {
                    $msg = '更新银行卡信息失败'.$this->failMsg;
                    throw new Exception($msg);
                }
            }

            $GLOBALS['db']->commit();
        } catch (Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error('系统错误,' . $e->getMessage());
        }

        // 之前发送短信方式不再使用
        $this->sendMessageFromUser($id, $status);
        $this->success('操作成功');
    }

    /**
     * 根据审核表id更新数据到 用户银行信息表
     * @param string $id
     * @date   2014-2-7
     */
    private function updateUserBankCard($id = '') {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->failMsg = \libs\utils\PaymentApi::maintainMessage();
            return false;
        }
        $data = array();
        if (!empty($id)) {
            $result = M('UserBankcardAudit')->where('id=' . intval($id))->find();
            if (!empty($result) && !empty($result['user_bank_id'])) {
                $data['bank_id'] = $result['bank_id'];
                $data['bankcard'] = $result['bankcard'];
                //$data['user_id']     = $result['user_id'];
                $data['status'] = 1;
                // 用户银行卡后台审核之后，置为已验证状态
                $data['verify_status'] = 1;
                $data['card_name'] = $result['card_name'];
                $bankService = new \core\service\BankService();
                $_hideExtra = $bankService->isHideExtraBank($result['bank_id']);
                if ($_hideExtra) {
                    $data['bankzone'] = '';
                    $data['region_lv1'] = 0;
                    $data['region_lv2'] = 0;
                    $data['region_lv3'] = 0;
                    $data['region_lv4'] = 0;
                }
                else {
                    $data['bankzone'] = $result['bankzone'];
                    if (!empty($result['bankzone_1'])) {
                        $data['bankzone'] = $result['bankzone_1'];
                    }
                    $data['region_lv1'] = $result['region_lv1'];
                    $data['region_lv2'] = $result['region_lv2'];
                    $data['region_lv3'] = $result['region_lv3'];
                    $data['region_lv4'] = $result['region_lv4'];
                }
                $data['image_id'] = $result['image_id'];
                $data['update_time'] = get_gmtime();

                $gtm = new GlobalTransactionManager();
                $gtm->setName('AuditBankInfo');

                if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {

                    // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                    $paymentService = new PaymentService();
                    $bankcardInfo = $paymentService->getBankcardInfo($data);
                    $gtm->addEvent(new UcfpayUpdateUserBankCardEvent($result, $bankcardInfo));
                }

                $userbankcardId = \libs\db\Db::getInstance('firstp2p')->getOne("SELECT id FROM firstp2p_user_bankcard WHERE user_id = '{$result['user_id']}'");
                $userBankcardInfo = array(
                    'bank_card_name' => addslashes($data['card_name']), //开户姓名
                    'c_region_lv1' => intval($data['region_lv1']),
                    'c_region_lv2' => intval($data['region_lv2']),
                    'c_region_lv3' => intval($data['region_lv3']),
                    'c_region_lv4' => intval($data['region_lv4']),
                    'bank_bankzone' => addslashes($data['bank_bankzone']),
                    'bank_bankcard' => addslashes($data['bankcard']), //处理卡号 只能是数字
                    'bank_id' => intval($data['bank_id']),
                    'short_name' => $bankcardInfo['bankCode'],
                    'bank_name' => $bankcardInfo['bankName'],
                    'id' => $result['user_id'],
                    'bankcard_id' => $userbankcardId,
                );
                $gtm->addEvent(new WxUpdateUserBankCardEvent($userBankcardInfo));
                // 用户已在存管账户开户或者是存管预开户用户
                $supervisionAccountObj = new SupervisionAccountService();
                $svService = new \core\service\SupervisionService();
                if ($supervisionAccountObj->isSupervisionUser($result['user_id']) || $svService->isUpgradeAccount($result['user_id'])) {
                    $gtm->addEvent(new SupervisionUpdateUserBankCardEvent($result['user_id'], $userBankcardInfo));
                }

                // 审核通过后，要把该用户所有的银行卡都更新，已确认 Update At 20160715 12:00
                $gtmRet = $gtm->execute();
                if (!$gtmRet) {
                    $this->failMsg = '更新绑卡信息失败';
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    //发消息给用户
    private function sendMessageFromUser($id, $status = false) {
        if (!empty($id)) {
            //给用户发送消息
            $result = M('UserBankcardAudit')->where('id=' . intval($id))->find();
            $sql = 'select id,mobile,email,sex,real_name,user_name from ' . DB_PREFIX . 'user where id =' . intval($result['user_id']);
            $userInfo = $GLOBALS['db']->getRow($sql);
            if (!empty($result) && !empty($userInfo)) {
                if ($status != 3) {
                    $des = '审核未通过，原因：' . $result['description'];
                    $tmp_email = 'TPL_EMAIL_AUTH_FAILED';
                    $tmp_sms = 'TPL_SMS_AUTH_FAILED';
                    $tpl = 'TPL_SMS_USER_BIND_BANK_FAIL';
                    $params = array(
                        'time' => date('m-d H:i'),
                        'reason' => !empty($result['fail_reason']) ? $result['fail_reason'] : self::$auditBankInfoFailType[1]['reason'],
                    );
                    $title = '修改银行卡失败';
                } else {
                    $tpl = 'TPL_SMS_USER_BIND_BANK_SUCC';
                    $params = array(
                        'time' => date('m-d H:i'),
                    );
                    $title = '修改银行卡成功';
                    $des = '审核已通过';
                    $tmp_email = 'TPL_EMAIL_AUTH_OK';
                    $tmp_sms = 'TPL_SMS_AUTH_OK';
                }
                $content = '您的银行卡修改申请' . $des;
                $group_arr = array(0, $result['user_id']);
                sort($group_arr);
                $group_arr[] = 1;
                //站内消息
                $msg_data = array();
                $msg_data['title'] = '银行卡修改提示';
                $msg_data['content'] = $content;
                $msg_data['to_user_id'] = $result['user_id'];
                $msg_data['create_time'] = get_gmtime();
                $msg_data['type'] = 0;
                $msg_data['group_key'] = implode("_", $group_arr);
                $msg_data['is_notice'] = $status != 3 ? 3 : 1; //已经通过的 属于系统 消息

                $msgBoxService = new MsgBoxService();
                $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], $msg_data['title'], $msg_data['content']);
                /*
                $GLOBALS['db']->autoExecute(DB_PREFIX . "msg_box", $msg_data);
                $id = $GLOBALS['db']->insert_id();
                $GLOBALS['db']->query("update " . DB_PREFIX . "msg_box set group_key = '" . $msg_data['group_key'] . "_" . $id . "' where id = " . $id);
                */
                // 发送短信这个去除
                try {
                    $Msgcenter = new Msgcenter();
                    //发邮件
                    $Msgcenter->setMsg($userInfo['email'], $result['user_id'], array('user_name' => $userInfo['user_name'], 'auth' => '银行卡更换申请'), $tmp_email, '银行卡更换申请信息通知');
                    ////发短信
                    //$Msgcenter->setMsg($userInfo['mobile'], $result['user_id'], array('user_name'=>$userInfo['user_name'],'auth'=>'银行卡更换申请'),$tmp_sms);
                    //$Msgcenter->save();
                    //短信通知
                    if (app_conf("SMS_ON") == 1) {
                        // SMSSend 银行卡审核结果短信
                        SmsServer::instance()->send($userInfo['mobile'], $tpl, $params, $userInfo['id']);
                    }
                } catch (Exception $e) {
                    print_r($e->getMessage());
                }
            }
        }
    }

    /**
     * 获取指定用户银行信息
     */
    public function getBankInfo() {
        $id = intval($_REQUEST['id']);
        $data = array();
        if (empty($id)) {
            $this->error("参数错误");
        }
        $model = MI('UserBankcardAudit');
        $result = $model->find($id);
        if (empty($result)) {
            $this->error('数据不存在');
        }
        $data['data'] = $result;
        $data['user'] = MI('User')->find($result['user_id']);
        $data['name'] = !empty($data['user']['real_name']) ? $data['user']['real_name'] : $data['user']['user_name'];
        $data['bank'] = MI('Bank')->find($result['bank_id']);
        $data['city'] = $this->combinationCity(array($result['region_lv1'], $result['region_lv2'], $result['region_lv3'], $result['region_lv4']));
        $imageFile = (new AttachmentModel)->getAttachmentById($result['image_id']);
        $file = $imageFile['attachment'];
        $streamContent = VfsHelper::image($file, true);
        $data['stream'] = 'data:image/jpeg;base64,'.base64_encode($streamContent);

        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 获取指定用户银行信息
     */
    public function getAutoAuditBankInfo() {
        $id = intval($_POST['id']);
        if (!empty($id)) {
            $model = MI('UserBankcardAudit');
            $result = $model->find($id);
            if (!empty($result)) {
                $user = MI('User')->find($result['user_id']);
                $name = !empty($user['real_name']) ? $user['real_name'] : $user['user_name'];
                $bank = MI('Bank')->find($result['bank_id']);
                $city = $this->combinationCity(array($result['region_lv1'], $result['region_lv2'], $result['region_lv3'], $result['region_lv4']));
                $str = "<div class='info'>
                            <span class='span_block'>姓名：" . $name . "</span>
                            <span class='span_block'>身份证号：" . $user['idno'] . "</span>
                            <span class='span_block'>选择银行：" . $bank['name'] . "</span>
                            <span class='span_block'>开户行所在地：" . $city . "</span>
                            <span class='span_block'>开户行网点：" . $result['bankzone'] . "</span>
                            <span class='span_block'>银行卡号：" . $result['bankcard'] . "</span>
                       </div>";

                self::$msg['msg'] = $str;
            } else
                self::$msg = array('code' => 4001, 'msg' => '数据不存在');
        } else
            self::$msg = array('code' => 4000, 'msg' => '参数错误');
        echo json_encode(self::$msg);
    }


    //组合城市数据
    private function combinationCity($arr = array()) {
        $str = '';
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                $model = M('DeliveryRegion');
                if (!empty($val)) {
                    $r = $model->find($val);
                    if (!empty($r))
                        $str .= "  " . $r['name'];
                }
            }
        }
        return ltrim($str, "  ");
    }

    //替换 js style 内容
    private function filterJs($str = '') {
        if (!empty($str)) {
            $pregfind = array("/<script.*>.*<\/script>/siU", "/<style.*>.*<\/style>/siU",);
            $pregreplace = array('', '',);
            $str = preg_replace($pregfind, $pregreplace, $str);
        }
        return $str;
    }

    // ajax重置密码
    public function edit_password() {
        if (!is_numeric($_REQUEST['id'])) {
            $this->error('参数错误');
        }
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        if (empty($user_info)) {
            $this->error('用户信息不存在');
        }
        if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
            $userService = new \core\service\UserService($user_id);
            $recipients = $userService->getEnterpriseInfo(true);
            //默认显示第一个短信接收人
            $receiveMsgMobile = isset($recipients['contact']['receive_msg_mobile']) ? $recipients['contact']['receive_msg_mobile'] : '86';
            $receiveMsgMobileFirst = explode('-', explode(',', $receiveMsgMobile)[0]);
            $mobile_code = isset($receiveMsgMobileFirst[0]) ? $receiveMsgMobileFirst[0] : '86';
            $mobile = isset($receiveMsgMobileFirst[1]) ? $receiveMsgMobileFirst[1] : '';
            $readonly = true; //不可编辑手机号，只读
        } else {
            $mobile = $user_info['mobile'];
            $mobile_code = $user_info['mobile_code'];
            $readonly = false;
        }
        $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
        $this->assign('id', $user_info['id']);
        $this->assign('user_name', $user_info['user_name']);
        $this->assign('mobile', $mobile);
        $this->assign('mobile_code', $mobile_code);
        $this->assign('mobile_code_list', $mobile_code_list);
        $this->assign('readonly', $readonly);
        $this->display();
    }

    // 重置密码
    // 提交申请
    public function do_edit_password() {
        if (!is_numeric($_POST['id'])) {
            $this->error("参数错误");
        }
        $user_id = intval($_POST['id']);
        $user_info = M("User")->getById($user_id);
        if (empty($user_info)) {
            $this->error('用户信息不存在');
        }

        //手机号
        $mobile = $_POST['mobile'];
        $country_code = $_POST['country_code'];
        if (!check_mobile($mobile) || empty($country_code)) {
            $this->error("接收手机号格式错误");
        }

        //国家区号
        $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
        $mobile_code = $mobile_code_list[$_REQUEST['country_code']]['code'];

        //添加审核
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $audit_data = [
            'user_id' => $user_id,
            'mobile' => $mobile,
            'country_code' => $country_code,
            'mobile_code' => $mobile_code,
            'apply_uid' => $adm_session['adm_id'],
            'apply_uname' => $adm_session['adm_name'],
        ];
        $result = UserPwdResetAuditModel::instance()->addAudit($audit_data);
        if (!$result) {
            $this->error('提交申请失败');
        }
        $this->success('提交申请成功');
    }

    /**
     * 确认审核
     */
    public function confirm_edit_password() {
        if (!is_numeric($_REQUEST['id'])) {
            $this->error('参数错误');
        }
        $id = intval($_REQUEST['id']);
        $userPwdResetAuditModel = UserPwdResetAuditModel::instance();
        $audit = $userPwdResetAuditModel->find($id);
        if (empty($audit)) {
            $this->error('审核记录不存在');
        }
        $userId = $audit['user_id'];
        $status = intval($_REQUEST['status']);
        $remark = isset($_REQUEST['remark']) ? addslashes($_REQUEST['remark']) : '';

        try {
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();
            $admSession = es_session::get(md5(conf("AUTH_KEY")));
            $updateData = [
                'status' => $status,
                'remark' => $remark,
                'audit_uid' => $admSession['adm_id'],
                'audit_uname' => $admSession['adm_name'],
            ];
            $auditRet = $userPwdResetAuditModel->confirmAudit($id, $updateData);
            if (!$auditRet) {
                throw new \Exception('确认审核失败');
            }
            if ($status == UserPwdResetAuditModel::STATUS_SUCCESS) {
                $userInfo = M("User")->getById($userId);
                //生成随机密码
                $randString = String::rand_string(10, 7);
                //密码加密
                $boBase = new BOBase();
                $userPwd = $boBase->compilePassword($randString);

                //重置用户密码
                $info = array(
                    'user_pwd' => $userPwd,
                    'force_new_passwd' => 1,
                );
                $ret = $db->autoExecute('firstp2p_user', $info, 'UPDATE', " id = '{$userId}'");
                if (!$ret) {
                    throw new \Exception('重置用户密码失败');
                }
                // 增加短信提示
                if (app_conf("SMS_ON") == 1) {
                    if ($userInfo['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $accountTitle = get_company_shortname($userInfo['id']); // by fanjingwen
                        $mobile = 'enterprise';
                    } else {
                        $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                        $mobile = $audit['mobile'];
                    }
                    $vars = [$accountTitle, $randString];
                    //使用接收人手机号
                    $result = SmsServer::instance()->send($mobile, 'TPL_SMS_RESET_PASSWORD', $vars,$userInfo['id']);
                    if (empty($result) || $result['code'] != 0) {
                        throw new \Exception('下发短信失败 ' . $result['message']);
                    }
                }
                $passportService = new PassportService();
                $passportService->sessionDestroyByUserId($userInfo['id']);
                save_log('重置会员id：' . $userId . '密码成功', 1);
            }
            $db->commit();
            $this->success('操作成功');
        } catch(\Exception $e) {
            $db->rollback();
            save_log('重置会员id：' . $userId . '密码失败', 0);
            $this->error('操作失败，' . $e->getMessage());
        }
    }

    /**
     * 用户密码重置审核列表
     */
    public function userPwdResetAudit() {
        $condition = array();

        // 会员id
        if (!empty($_REQUEST['user_id'])) {
            $condition['user_id'] = (int) $_REQUEST['user_id'];
        }

        // 手机号
        if (!empty($_REQUEST['mobile'])) {
            $condition['mobile'] = addslashes($_REQUEST['mobile']);
        }

        // 操作人
        if (!empty($_REQUEST['apply_uname'])) {
            $condition['apply_uname'] = array('LIKE', '%' . trim($_REQUEST['apply_uname'] . '%'));
        }

        $list = $this->_list(D('UserPwdResetAudit'), $condition, 'id', false, false);
        foreach ($list as $key => $val) {
            $user = M(MODULE_NAME)->where(array('id' => $val['user_id']))->find();
            $list[$key]['is_same_mobile'] = true;
            if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
                $userService = new \core\service\UserService($val['user_id']);
                $recipients = $userService->getEnterpriseInfo(true);
                //替换成接收人手机号
                $mobileCode = '86';
                $mobile = isset($recipients['contact']['receive_msg_mobile']) ? $recipients['contact']['receive_msg_mobile'] : '';
                $list[$key]['mobile'] = $mobile;
            } else {
                $mobile = $user['mobile'];
                $mobileCode = $user['mobile_code'];
                if ($val['mobile'] != $mobile || $val['mobile_code'] != $mobileCode) {
                    $list[$key]['is_same_mobile'] = false;
                }
                $list[$key]['mobile'] = $val['mobile'];
            }
            $list[$key]['user_name'] = $user['user_name'];
            $list[$key]['mobileFormat'] = moblieFormat($val['mobile']);
            $list[$key]['status_text'] = UserPwdResetAuditModel::$statusMap[$val['status']];
        }

        //导出
        if (isset($_REQUEST['export'])) {
            $title = '编号,用户id,用户名称,接收手机号,操作人,操作时间,审核人,审核时间,审核状态,审核备注';
            $content = iconv('utf-8', 'gbk', $title) . "\n";
            foreach ($list as $k => $v) {
                $row = '';
                $row .= $v['id'];
                $row .= ','.$v['user_id'];
                $row .= ','.$v['user_name'];
                $row .= ','.$v['mobile'];
                $row .= ','.$v['apply_uname'];
                $row .= ",\"" . format_date($v['apply_time']) . "\"";
                $row .= ','.$v['audit_uname'];
                $row .= ",\"" . format_date($v['audit_time']) . "\"";
                $row .= ','.$v['status_text'];
                $row .= ','.$v['remark'];
                $row = strip_tags($row);
                $content .= iconv('utf-8', 'gbk', $row) . "\n";
            }
            $datatime = date("YmdHis", get_gmtime());
            header("Content-Disposition: attachment; filename=user_pwd_reset_audit_{$datatime}.csv");
            echo $content;

            exit;
        }



        $this->assign('list', $list);
        $this->assign('main_title', '重置密码审核和记录');
        $this->display();

    }

    /**
     * ajax根据用户id获取用户信息
     * @author zhanglei5@ucfgroup.com
     */
    public function getAjaxUser() {
        $return = array("status" => 0, "message" => "");
        $id = intval($_REQUEST['id']);
        if ($id == 0) {
            return ajax_return($return);
        }
        $m_user = M(MODULE_NAME);
        $user = $m_user->where(array('id' => $id))->find();
        if (!$user) {
            return ajax_return($return);
        }
        $return['status'] = 1;
        $return['user'] = $user;

        // JIRA#3260 企业账户二期 - 用户类型
        if (UserModel::USER_TYPE_NORMAL == $return['user']['user_type']) {
            $return['user']['user_type_name'] = UserModel::USER_TYPE_NORMAL_NAME;
            // 获取带有url超链的姓名string
            $return['user']['name'] = getUserFieldUrl($return['user'], UserModel::TABLE_FIELD_REAL_NAME);
        } elseif (UserModel::USER_TYPE_ENTERPRISE == $return['user']['user_type']) {
            $return['user']['user_type_name'] = UserModel::USER_TYPE_ENTERPRISE_NAME;
            // 获取企业名称
            $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($return['user']['id']);
            // 获取带有url超链的姓名string
            $return['user']['company_name'] = $enterpriseInfo['company_name'];
            $return['user']['name'] = getUserFieldUrl($return['user'], EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
        }

        // -------------- over --------------

        return ajax_return($return);
    }

    public function usersToMove() {
        $this->display();
    }

    public function uploadUserIds() {
        $this->display();
    }

    /**
     * 上传用户身份证号信息
     */
    public function doUploadUserIds() {
        if ($_FILES['upfile']['error'] == 4) {
            $this->error('请选择文件！');
            return;
        }

        if (end(explode('.', $_FILES['upfile']['name'])) != 'csv') {
            $this->error('请上传csv格式的文件');
            return;
        }

        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $csv_data = $this->_make_csv_data_group();
        if (empty($csv_data)) {
            $this->error('可处理的数据为空');
            return;
        }
        if (is_array($csv_data)) {
            $userService = new \core\service\UserService();

            foreach ($csv_data as $k => $rowdata) {
                // 检查用户身份证号是否已经存在用户
                $userService->addUserRegisterInfo($rowdata);
            }
        }
        $this->success('处理成功');
    }

    /**
     * 执行用户移动组操作
     */
    public function doUsersMove() {
        if ($_FILES['upfile']['error'] == 4) {
            $this->error('请选择文件！');
            return;
        }

        if (end(explode('.', $_FILES['upfile']['name'])) != 'csv') {
            $this->error('请上传csv格式的文件');
            return;
        }

        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $csv_data = $this->_make_csv_data_group();
        if (empty($csv_data)) {
            $this->error('可处理的数据为空');
            return;
        }
        if (is_array($csv_data)) {
            $batchid = time();
            $cnt = 0;
            $ttl = count($csv_data);
            foreach ($csv_data as $cur => $rowdata) {
                // 身份证号采用加密存储，统一使用大写的X后缀
                $idno = strtoupper(addslashes(trim($rowdata['idno'])));
                // 检查用户身份证号是否已经存在用户
                $sql = "SELECT id,group_id,coupon_level_id FROM firstp2p_user WHERE idno = '{$idno}'";
                $user = $GLOBALS['db']->get_slave()->getRow($sql);
                if (!empty($user)) {
                    $GLOBALS['db']->startTrans();
                    try {
                        $dataToUpdate = array(
                            'group_id' => $rowdata['group_id'],
                            'coupon_level_id' => $rowdata['level_id']
                        );
                        // 移动会员组
                        $GLOBALS['db']->autoExecute('firstp2p_user', $dataToUpdate, 'UPDATE', " id = '{$user['id']}'");
                        $affectRows = $GLOBALS['db']->affected_rows();
                        if ($affectRows < 1) {
                            throw new \Exception(' 0 rows affected');
                        }
                        // 打tags
                        $tags = explode('|', $rowdata['tags']);
                        if (!empty($tags)) {
                            $tagService = new \core\service\UserTagService();
                            $tagService->addUserTagsByConstName($user['id'], $tags);
                        }
                        // 去tags
                        $removeTags = explode('|', $rowdata['removeTags']);
                        if (!empty($removeTags)) {
                            $tagService = new \core\service\UserTagService();
                            $tagService->delUserTagsByConstName($user['id'], $removeTags);
                        }
                        $GLOBALS['db']->commit();
                        $cnt ++;
                        PaymentApi::log('grouptrans bid:'.$batchid.'('.($cur+1).'/'.$ttl.'):move user_id'.$user['id'].' group from '.$user['group_id'].' to '.$rowdata['group_id'].', level from '.intval($user['level_id']).' to '.$rowdata['level_id'].' succeed');
                    }
                    catch(\Exception $e) {
                        $GLOBALS['db']->rollback();
                        PaymentApi::log('grouptrans bid:'.$batchid.'('.($cur+1).'/'.$ttl.'):move user_id'.$user['id'].' group from '.$user['group_id'].' to '.$rowdata['group_id'].', level from '.intval($user['level_id']).' to '.$rowdata['level_id'].' failed '.$e->getMessage());
                    }
                }
            }
        }
        $this->success('更新成功');
    }
    private function _make_csv_data_group() {
        $csv_data = array();
        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== false) {
            while (($row_data = fgetcsv($handle)) !== false) {
                $csv_data[] = array(
                    'idno' => $row_data[0] ? trim($row_data[0]) : '',
                    'group_id' => $row_data[1] ? trim($row_data[1]) : '',
                    'level_id' => $row_data[2] ? trim($row_data[2]) : '',
                    'tags' => $row_data[3] ? trim($row_data[3]) : '',
                    'invite_code' => $row_data[4] ? trim($row_data[4]) : '',
                    'partner' => $row_data[5] ? trim($row_data[5]) : '',
                    'removeTags' => $row_data[6] ? trim($row_data[6]) : '',
                );
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        return $csv_data;
    }

    // 一房财富 独立经纪人列表
    public function yifang() {
        ini_set('memory_limit', '512M');
        $map = array();

        if (trim($_REQUEST['user_name'])) {
            $map['user_name'] = trim($_REQUEST['user_name']);
        }

        if (trim($_REQUEST['adm_name'])) {
            $map['adm_name'] = array('LIKE', '%' . trim($_REQUEST['adm_name'] . '%'));
        }

/*        if (trim($_REQUEST['mobile'])) {
            $map['mobile'] = trim($_REQUEST['mobile']);
        }
*/
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $model = M("UserYifang");

        if (!empty($model)) {
            $this->_list($model, $map);
        }

        $this->display();
    }

    //修改用组和等级日志列表
    public function changeGroupLevelLog() {
        ini_set('memory_limit', '512M');
        $map = array();

        if (trim($_REQUEST['user_name'])) {
            $map['user_name'] = trim($_REQUEST['user_name']);
        }

        if (!empty($_REQUEST['user_num'])) {
            $user_id = de32Tonum($_REQUEST['user_num']);
            $userInfo = UserModel::instance()->find($user_id,'user_name');
            $map['user_name'] = $userInfo['user_name'];
        }

        if (trim($_REQUEST['adm_name'])) {
            $map['adm_name'] = array('LIKE', '%' . trim($_REQUEST['adm_name'] . '%'));
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $model = M("ChangeGroupLevelLog");

        if (!empty($model)) {
            $this->_list($model, $map);
        }

        $this->display();
    }

    private static function _iframe_check($msg) {
        printf('<script>window.parent.yifang_check("%s")</script>', $msg);
        exit;
    }

   private static function _iframe_alert($msg, $is_reload = 0) {
        printf('<script>window.parent.yifang_alert("%s","%s")</script>', $msg, $is_reload);
        exit;
    }

    private function _make_csv_data() {
        $csv_data = array();

        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== false) {
            if(fgetcsv($handle) !== false){ //第一行是标题不放到数据列表里
                while (($row_data = fgetcsv($handle)) !== false) {

                    if (count($row_data) != 6) {
                        $error_info=sprintf("序号%d，数据应该是6列，该行是 %s列！", $row_data[0], count($row_data));
                        $this->error($error_info);
                    }

                    //todo 这块代码而且上线需要重写
                    $user_num = "";
                    if(!empty($row_data[3])){
                        $user_num=$row_data[3];
                        $mobileResult=MI('User')->where("id=" . de32Tonum($row_data[3]))->field('mobile')->find();
                        $row_data[3]=$mobileResult['mobile']?$mobileResult['mobile']:'00000000000';
                    }

                    $csv_data[$row_data[0]] = array(
                        'csv_key' => $row_data[0],
                        'user_name' => $row_data[1] ? trim($row_data[1]) : '',
                        'real_name' => $row_data[2] ? iconv('GBK', 'UTF-8', trim($row_data[2])) : '',
                        'user_num' => $user_num ? trim($user_num) : '',
                        'mobile' => $row_data[3] ? trim($row_data[3]) : '',
                        'group_id' => $row_data[4] ? trim($row_data[4]) : '',
                        'level_id' => $row_data[5] ? trim($row_data[5]) : '',
                    );
                }
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        return $csv_data;
    }

    private function _make_csv_data_change_level_group() {
        $csv_data = array();

        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== false) {
            if(fgetcsv($handle) !== false){ //第一行是标题不放到数据列表里
                while (($row_data = fgetcsv($handle)) !== false) {

                    if (count($row_data) != 5) {
                        $error_info=sprintf("序号%d，数据应该是5列，该行是 %s列！", $row_data[0], count($row_data));
                        $this->error($error_info);
                    }

                    $csv_data[$row_data[0]] = array(
                        'csv_key' => trim($row_data[0]),
                        'real_name' => $row_data[1] ? iconv('GBK', 'UTF-8', trim($row_data[1])) : '',
                        'user_num' => $row_data[2] ? trim($row_data[2]) : '',
                        'group_id' => $row_data[3] ? trim($row_data[3]) : '',
                        'level_id' => $row_data[4] ? trim($row_data[4]) : '',
                    );
                }
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        return $csv_data;
    }

    public function yifangcsv() {
        if ($_REQUEST['show_error'] == 1) {
            ini_set('display_error', 1);
            error_reporting(E_ALL ^ E_NOTICE);
        }

        if ($_FILES['upfile']['error'] == 4) {
            //self::_iframe_alert("请选择文件！");
            //exit();
            $this->error ( "上传的文件不能为空" );
        }

        if (end(explode('.', $_FILES['upfile']['name'])) != 'csv') {
            //self::_iframe_alert("请上传csv格式的文件！");
            //exit();
            $this->error ( "请上传csv格式的文件！" );
        }

        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $csv_data = $this->_make_csv_data();
        if (empty($csv_data)) {
            //self::_iframe_alert('可处理的数据为空');
            $this->error ('可处理的数据为空');
        }
        $username = $mobile_list = array();
        foreach ($csv_data as $csv_row) {
            if (!empty($csv_row['mobile'])) {
                $mobile_list[] = strval($csv_row['mobile']);
                continue;
            }
            $username[] = $csv_row['user_name'];
        }
        $where = array('user_name' => array('in', $username));
        $user_data = $user_list = $user_list_1 = $user_data_mobile = array();

        if (!empty($username)) {
            $user_list = MI('User')->where($where)->field('id,user_name,mobile,real_name,group_id,coupon_level_id,is_effect,is_delete')->select();
        }
        if (!empty($mobile_list)) {
            foreach($mobile_list as $key=>$value){
                $mobile_list[$key] = libs\utils\DBDes::encryptOneValue($value);
            }
            $user_list_1 = MI('User')->where(array('mobile' => array('in', $mobile_list)))->field('id,user_name,mobile,real_name,group_id,coupon_level_id,is_effect,is_delete')->select();
        }

        $user_list = array_merge($user_list, $user_list_1);
        if ($user_list) {
            foreach ($user_list as $user_row) {
                $user_data[$user_row['user_name']] = $user_row;
                $user_data_mobile[$user_row['mobile']] = $user_row;
            }
            unset($user_list, $user_list_1);
        }
        $error = array();
        $correct = array();
        $error_list=array();
        foreach ($csv_data as $csv_row) {
            $check_key = 'user_name';
            if (!empty($csv_row['mobile'])) {
                $check_key = 'mobile';
                $user_online = $user_data_mobile[$csv_row['mobile']];
            } else {
                $user_online = $user_data[$csv_row['user_name']];
            }
            if (empty($user_online)) {
                $error[] = sprintf("序号:%s，用户名：%s，在用户列表中不存在", $csv_row['csv_key'], $csv_row['user_name']);
                unset($csv_row['mobile']);
                $error_list[] = $csv_row;
                continue;
            }

            $userGroupService = new \core\service\UserGroupService();
            if (!empty($csv_row['user_name']) && $csv_row['user_name'] != $user_online['user_name']) { //检查用户名是否正确
                $error[] = sprintf("序号:%s，用户名：%s，根据手机号获取的用户名：%s", $csv_row['csv_key'], $csv_row['user_name'], $user_online['user_name']);
                unset($csv_row['mobile']);
                $error_list[] = $csv_row;
                continue;
            } elseif ($csv_row[$check_key] && (strtolower($csv_row[$check_key]) != strtolower($user_online[$check_key]) || ($csv_row['real_name'] != '' && $csv_row['real_name'] != $user_online['real_name']))) {
                $error[] = sprintf("序号:%s，会员名称:%s,会员编号:%s, 在用户表中用户名为:%s, 名称:%s,用户表中名称:%s", $csv_row['csv_key'],  $csv_row['user_name'],$csv_row['user_num'], $user_online['user_name'],$csv_row['real_name'], $user_online['real_name']);
                unset($csv_row['mobile']);
                $error_list[] = $csv_row;
                continue;
            } elseif ($_REQUEST['check_group_id'] && !$userGroupService->agencyUsersIsSameByIds($user_online['group_id'],$csv_row['group_id'])){
                $error[] = sprintf("序号:%s，会员名称:%s, 机构不匹配", $csv_row['csv_key'], $csv_row['user_name']);
                unset($csv_row['mobile']);
                $error_list[] = $csv_row;
                continue;
            }elseif( $user_online['is_delete'] || empty($user_online['is_effect']) ){
                $error[] = sprintf("序号:%s，会员名称:%s,会员状态无效或已删除", $csv_row['csv_key'], $csv_row['user_name']);
                unset($csv_row['mobile']);
                $error_list[] = $csv_row;
                continue;
            }
           /* else {
                $csv_row['user_id'] = $user_online['id'];
                $csv_row['old_groupid'] = $user_online['group_id'];
                $csv_row['old_levelid'] = $user_online['coupon_level_id'];
                $csv_row['mobile'] = $csv_row['mobile'] ? $csv_row['mobile'] : $user_online['mobile'];
                $csv_row['user_name'] = $csv_row['user_name'] ? $csv_row['user_name'] : $user_online['user_name'];
                $csv_row['real_name'] = $csv_row['real_name'] ? $csv_row['real_name'] : $user_online['real_name'];
                $correct[$csv_key] = $csv_row;
            }*/

            // 正确的数据
            $csv_row ['user_id'] = $user_online ['id'];
            $csv_row ['old_groupid'] = $user_online ['group_id'];
            $csv_row ['old_levelid'] = $user_online ['coupon_level_id'];
            $csv_row ['mobile'] = $csv_row ['mobile'] ? $csv_row ['mobile'] : $user_online ['mobile'];
            $csv_row ['user_name'] = $csv_row ['user_name'] ? $csv_row ['user_name'] : $user_online ['user_name'];
            $csv_row ['real_name'] = $csv_row ['real_name'] ? $csv_row ['real_name'] : $user_online ['real_name'];
            $correct [] = $csv_row;
        }
        unset($user_data, $csv_data);

        $is_check = intval($_REQUEST['is_check']);

        if ($is_check==0) { // 0:检查并导入
            //$model = M("UserYifang");
            $couponBindService  = new CouponBindService();

            //$yifang_data = $model->where($where)->findAll();
/*
            $yifang_mobile = array();
            foreach ($yifang_data as $yifangrow) {
                $yifang_mobile[$yifangrow['mobile']] = $yifangrow;
            }
 */
            //$model->startTrans();// 避免大事务
            try {
                //$update_list = array();
                if(!empty($correct)){
                    $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                    $result = $couponBindService -> changeGroupAndLevel($correct,$adm_session);
                    if(!$result){
                        throw new Exception("导入失败");
                    }
                    /*foreach ($correct as $correct_key => $correct_row) {
                        // if (!isset($yifang_mobile[$correct_row['mobile']])) {
                        try {
                            $model->startTrans();
                            $userid = $correct_row['user_id'];
                            $res = M('user')->where(sprintf("id ='%d'", $userid))
                                ->save(array('group_id' => $correct_row['group_id'], 'coupon_level_id' => $correct_row['level_id']));
                            if ($res === false) {
                                throw new Exception(sprintf("序号:%s，用户名：%s，更新分组处理失败", $correct_key, $correct_row['user_name']));
                            }else{
                                $correct_row['new_groupid'] = $correct_row['group_id'];
                                $correct_row['new_levelid'] = $correct_row['level_id'];
                                $correct_row['adm_id'] = $adm_session['adm_id'];
                                $correct_row['adm_name'] = $adm_session['adm_name'];
                                unset($correct_row['user_id']);
                                $add_res = $model->add($correct_row);
                                if (!$add_res) {
                                    throw new Exception(sprintf("序号:%s，用户名：%s，处理失败", $correct_key, $correct_row['user_name']));
                                }
                            }
                            $model->commit();
                        } catch (Exception $e) {
                            $model->rollback();
                            throw new Exception($e->getMessage());
                        }
                        $ret = $couponBindService->refreshByReferUserId($userid,$adm_session["adm_id"]);
                        if(!$ret){
                            throw new Exception(sprintf("序号:%s，用户名：%s，更新投资用户绑定邀请码失败", $correct_key, $correct_row['user_name']));
                        }
                        //}
                    }*/
                }
                //$model->commit();
                //self::_iframe_alert('导入成功！', 1);
                if(empty($error)){
                    $this->success("导入成功,没有错误数据");
                }else{
                    $err_count_total_line = count ( $error );
                    $header = array('序号','用户名','真实姓名','会员编号','分组ID','优惠码等级ID');
                    $error_json = implode(",",$header)."\n";
                    foreach($error_list as $k=>$v){
                        $error_json .=implode(",",$v)."\n";
                    }
                    if(empty($correct)){
                        $alert_msg=sprintf("文件中没有正确数据，共有%s条错误数据",$err_count_total_line);
                    }else{
                        $alert_msg=sprintf("正确数据已导入成功，共有%s条错误数据",$err_count_total_line);
                    }
                    echo <<<EOT
                        <div class="main">
                        <div class="main_title">$alert_msg </div>
                        <form action="" method="post">
                        <div class="button_row">
                        <input type="hidden" name="a" value="download_csv_datas">
                        <input type="hidden" name="m" value="User">
                        <input type="hidden" name="error_data" value="$error_json">
                        <input type="submit" class="button" value="下载错误数据"/>
                        </div>
                        </form>
                    </div>
EOT;
                }
            } catch (Exception $e) {
                    //$model->rollback();
                    //self::_iframe_alert($e->getMessage(), 1);
                    $this->error ($e->getMessage());
            }
        }
        /*elseif ($is_check==2 && !empty($error)) { // 2: 只下载错误数据，不导入
            $content = implode(',', array('序号','用户名','真实姓名','会员编号','分组ID','优惠码等级ID'))."\n";
            foreach($error_list as $k){
                 logger::info('error_list:'.json_encode($error_list));
                 $content .= implode(",",$k)."\n";
            }
            $datatime = date("YmdHis", get_gmtime());
            header("Content-Disposition: attachment; filename=csv_error_data_{$datatime}.csv");
            echo iconv('utf-8', 'gbk//ignore', $content);
       } else { // 1:检查数据
            self::_iframe_check(implode("\\n", $error));
       }*/
    }


    public function changeGroupLevelCSV() {
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        if ($_FILES['upfile']['error'] == 4) {
            $this->error ( "上传的文件不能为空" );
        }

        if (end(explode('.', $_FILES['upfile']['name'])) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
        }

        $csv_data = $this->_make_csv_data_change_level_group();
        if (empty($csv_data)) {
            $this->error ('可处理的数据为空');
        }

        $data = $this->checkGroupLevelData($csv_data);
        $correctList = $data['correctList'];
        $errorList = $data['errorList'];
        $userService  = new UserService();
        try {
            if(!empty($correctList)){
                $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                $result = $userService -> changeGroupAndLevel($correctList,$adm_session);
                if(!$result){
                    throw new Exception("导入失败");
                }
            }

            if(empty($errorList)){
                $this->success("导入成功,没有错误数据");
            }else{
                $err_count_total_line = count ( $errorList );
                $header = array('序号','姓名','会员编号','分组ID','优惠码等级ID','错误原因');
                $error_json = implode(",",$header)."\n";
                foreach($errorList as $k=>$v){
                    $error_json .=implode(",",$v)."\n";
                }
                if(empty($correct)){
                    $alert_msg=sprintf("文件中没有正确数据，共有%s条错误数据",$err_count_total_line);
                }else{
                    $alert_msg=sprintf("正确数据已导入成功，共有%s条错误数据",$err_count_total_line);
                }
                echo <<<EOT
                    <div class="main">
                    <div class="main_title">$alert_msg </div>
                    <form action="" method="post">
                    <div class="button_row">
                    <input type="hidden" name="a" value="download_csv_datas">
                    <input type="hidden" name="m" value="User">
                    <input type="hidden" name="error_data" value="$error_json">
                    <input type="submit" class="button" value="下载错误数据"/>
                    </div>
                    </form>
                </div>
EOT;
            }
        } catch (Exception $e) {
                $this->error ($e->getMessage());
        }
    }

      //数据校验
       public function checkGroupLevelCSV() {
        set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        if ($_FILES['upfile']['error'] == 4) {
            $this->error ( "上传的文件不能为空" );
        }

        if (end(explode('.', $_FILES['upfile']['name'])) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
        }

        $csv_data = $this->_make_csv_data_change_level_group();
        if (empty($csv_data)) {
            $this->error ('可处理的数据为空');
        }

        $data = $this->checkGroupLevelData($csv_data,true);
        $correctList = $data['correctList'];
        $errorList = $data['errorList'];
        $userService  = new UserService();

        if(empty($errorList)){
            $this->success("文件中没有错误数据");
        }else{
            $err_count_total_line = count ( $errorList );
            $header = array('序号','姓名','会员编号','分组ID','优惠码等级ID','错误原因');
            $error_json = implode(",",$header)."\n";
            foreach($errorList as $k=>$v){
                $error_json .=implode(",",$v)."\n";
            }

            if(empty($correct)){
                $alert_msg=sprintf("文件中没有正确数据，共有%s条错误数据",$err_count_total_line);
            }else{
                $alert_msg=sprintf("共有%条正确数据，共有%s条错误数据",count($correct),$err_count_total_line);
            }
            echo <<<EOT
                <div class="main">
                <div class="main_title">$alert_msg </div>
                <form action="" method="post">
                <div class="button_row">
                <input type="hidden" name="a" value="download_csv_datas">
                <input type="hidden" name="m" value="User">
                <input type="hidden" name="error_data" value="$error_json">
                <input type="submit" class="button" value="下载错误数据"/>
                </div>
                </form>
            </div>
EOT;
        }
    }

    //批量整理用户组上传数据校验
    private function checkGroupLevelData($csv_data,$riskCheck = false){
        $data = array(
            'correctList' => array(),
            'errorList' => array()
            );

        foreach ($csv_data as $key => $csv_row) {
            $userInfo = '';

            if (empty($csv_row['csv_key'])) {
                $csv_row[] = "序号不能为空";
                $data['errorList'][$key] = $csv_row;
                continue;
            }

            if (empty($csv_row['user_num'])) {
                $csv_row[] = "会员编号不能为空";
                $data['errorList'][$key]= $csv_row;
                continue;
            }

            $userId = de32Tonum($csv_row['user_num']);
            $userInfo=MI('User')->where("id=" . $userId )->field('id,user_name,is_effect,is_delete,real_name,mobile,group_id,new_coupon_level_id')->find();
            if (empty($userInfo)) {
                $csv_row[] = "会员编码有误";
                $data['errorList'][$key] = $csv_row;
                continue;
            }

            if($userInfo['real_name'] != $csv_row['real_name']){
                $csv_row[] = "姓名与会员编号不匹配";
                $data['errorList'][$key]= $csv_row;
                continue;
            }

            $userGroupService = new \core\service\UserGroupService();
            if ($_REQUEST['check_group_id'] && !$userGroupService->agencyUsersIsSameByIds($userInfo['group_id'],$csv_row['group_id'])){
                $csv_row[] = "机构不匹配";
                $data['errorList'][$key] = $csv_row;
                continue;
            }
            if( $userInfo['is_delete'] || empty($userInfo['is_effect']) ){
                $csv_row[] = "会员状态无效或已删除";
                $data['errorList'][$key] = $csv_row;
                continue;
            }
            $groupInfo = $userGroupService ->getGroupInfo($csv_row['group_id']);
            if(empty($groupInfo) || $groupInfo['is_effect'] == 0){
                $csv_row[] = "会员组不存在或者会员组无效";
                $data['errorList'][$key] = $csv_row;
                continue;
            }

            //服务等级在用户服务组没有服务标识的时候可以不填写
            if(empty($csv_row['level_id']) && $groupInfo['service_status'] == 1){
                $csv_row[] = "服务等级不能为空";
                $data['errorList'][$key] = $csv_row;
                continue;
            }

            if(!empty($csv_row['level_id'])){
                $userCouponLevelService = new \core\service\UserCouponLevelService();
                $userCouponLevel = $userCouponLevelService ->getLevelById($csv_row['level_id']);
                if(empty($userCouponLevel) || $userCouponLevel['is_effect'] == 0){
                    $csv_row[] = "服务等级不存在或者服务等级无效";
                    $data['errorList'][$key] = $csv_row;
                    continue;
                }
            }

            //验证邀请码打包规则
            $csv_row ['level_id'] = empty($csv_row ['level_id'])?$userInfo ['new_coupon_level_id']:$csv_row ['level_id'];
            if(!(new UserCouponLevelService())->checkLevelMatchGroupById($csv_row['group_id'],$csv_row ['level_id'])){
                $csv_row[] = "会员组和服务等级不匹配";
                $data['errorList'][$key] = $csv_row;
                continue;
            }

            //风险数据校验
            if($riskCheck){
                $userGroupService = new UserGroupService();
                $result = $userGroupService->checkServiceStatusIsSame($userInfo['group_id'],$csv_row['group_id']);
                if(!$result){
                    $csv_row[] = "该用户在服务组和非服务组之间跳转";
                    $data['errorList'][$key] = $csv_row;
                    continue;
                }
            }

            // 正确的数据
            $csv_row ['user_id'] = $userInfo ['id'];
            $csv_row ['old_groupid'] = $userInfo ['group_id'];
            $csv_row ['old_levelid'] = $userInfo ['new_coupon_level_id'];
            $csv_row ['mobile'] = $userInfo ['mobile'];
            $csv_row ['user_name'] =  $userInfo ['user_name'];
            $csv_row ['real_name'] = $userInfo ['real_name'];
            $data['correctList'][$key] = $csv_row;
    }

    return $data;
}

    public function download_csv_datas(){
        $content=$_REQUEST['error_data'];
        $datatime = date("YmdHis", get_gmtime());
        header("Content-Disposition: attachment; filename=csv_error_data_{$datatime}.csv");
        echo iconv('utf-8', 'gbk//ignore', $content);
    }

    public function downLoad_csv_templete(){
        $header = array('序号','姓名','会员编号','分组ID','优惠码等级ID');
        $content = implode(",",$header)."\n";
        $datatime = date("YmdHis", get_gmtime());
        header('Content-Type: text/csv;charset=utf8');
        header("Content-Disposition: attachment; filename=csv_import_change_group_level.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        echo iconv('utf-8', 'gbk//ignore', $content);
    }


    /**
     * 添加字段更新数据库数据
     * changlu
     * refer_user_id 字段
     */
    public function repair_referUser() {

        $slq_1 = "UPDATE `firstp2p_user` AS u , `firstp2p_coupon_special` AS s SET u.refer_user_id = s.refer_user_id WHERE u.refer_user_id = 0 AND u.invite_code = s.short_alias and s.deal_id = 0";
        $rs1 = $GLOBALS['db']->query($slq_1);

        $sql = "UPDATE `firstp2p_user` SET refer_user_id = CONV(RIGHT(invite_code,5),16,10) WHERE invite_code != '' AND refer_user_id = 0 ";
        $rs = $GLOBALS['db']->query($sql);
        echo intval($rs1), '-----', intval($rs), "操作成功！";
        exit;
    }

    /*
     * 沉睡用户列表
     *
     * add by yutao
     */

   public function sleep() {
        $this->groups = \core\dao\UserGroupModel::instance()->getGroups();
        $this->assign("group_list", $this->groups);
        //定义条件
        $where = 'is_delete = 0';

        if (method_exists($this, '_filter')) {
            $this->_filter($where);
        }
        $name = $this->getActionName();
        $model = DI($name);
        if (!empty($model)) {
            $this->sleepList($model, $where);
        }
        $this->display();
    }

    public function sleepList($model, $map) {
        $redisKey = 'FIRSTP2P_SLEEP_USER_LOGIN';
        if ($_GET['_order'] == "create_time") {
            $redisKey = 'FIRSTP2P_SLEEP_USER_CREATE';
        }
        if ($_GET['isInSG'] == 1) {
            $redisKey .= "_GROUP";
        }
        /*
         * 对于有搜索条件的特殊处理
         */
        $searchFlag = FALSE;
        if (trim($_GET['user_name']) != '') {
            $searchFlag = TRUE;
            //$where .= " and user_name like '%".trim($_GET['user_name'])."%'";
        }

        if ($searchFlag) {
            $map .= " and user_name like '" . trim($_GET['user_name']) . "%'";
            $searchUser = $model->where($map)->field('id,user_name,real_name,mobile,create_time,login_time,invite_code,group_id')->findAll();
            $redisList = $this->getAllSleep($redisKey);
            foreach ($searchUser as $key => $value) {
                $isMember = FALSE;
                $sid = $value['id'];
                foreach ($redisList as $value) {
                    if ($sid == $value) {
                        $isMember = TRUE;
                        break;
                    }
                }
                if (!$isMember) {
                    unset($searchUser[$key]);
                }
            }
            $count = count($searchUser);
            if ($count > 0) {
                $p = new Page($count, $count);
                if (!empty($_GET['_order'])) {
                    if ($_GET['_sort'] == 1) {
                        foreach ($searchUser as $user) {
                            $sort[] = $user[$_GET['_order']];
                        }
                        array_multisort($sort, SORT_ASC, $searchUser);
                        $searchUser = array_reverse($searchUser);
                    } else if ($_GET['_sort'] == 0) {
                        foreach ($searchUser as $user) {
                            $sort[] = $user[$_GET['_order']];
                        }
                        array_multisort($sort, SORT_ASC, $searchUser);
                    }
                }
                $voList = $searchUser;
                //分页显示
                $page = $p->show();
                //模板赋值显示
                $this->assign('list', $voList);
                $this->assign("page", $page);
                $this->assign("nowPage", $p->nowPage);
            }
        } else {
            $count = $this->getSleepCount($redisKey);
            if ($count > 0) {
                //创建分页对象
                if (!empty($_REQUEST ['listRows'])) {
                    $listRows = $_REQUEST ['listRows'];
                } else {
                    $listRows = '';
                    //$listRows = 2;
                }
                $p = new Page($count, $listRows);
                //分页查询数据
                if (!empty($_GET['p'])) {
                    $sleepUserIds = $this->getSleepUserIds($redisKey, $count, $_GET['_order'], $_GET['_sort'], $p->firstRow, ($p->firstRow + $p->listRows - 1));
                } else {
                    $sleepUserIds = $this->getSleepUserIds($redisKey, $count, $_GET['_order'], $_GET['_sort'], $p->firstRow, ($p->listRows - 1));
                }
                $sleepUserIds = implode(',', $sleepUserIds);
                $map .= " and id IN ({$sleepUserIds}) ";
                if (!empty($_GET['_order'])) {
                    if ($_GET['_sort'] == 1) {
                        $map .= "ORDER BY {$_GET['_order']} DESC";
                    } else {
                        $map .= "ORDER BY {$_GET['_order']} ASC";
                    }
                }
                $voList = $model->where($map)->findAll();
                //$this->form_index_list($voList);
                //分页跳转的时候保证查询条件
                foreach ($map as $key => $val) {
                    if (!is_array($val)) {
                        $p->parameter .= "$key=" . urlencode($val) . "&";
                    }
                }
                //分页显示
                $page = $p->show();
                //模板赋值显示
                $this->assign('list', $voList);
                $this->assign("page", $page);
                $this->assign("nowPage", $p->nowPage);
            }
        }

        return;
    }

    /*
     * 获得全部结果集
     * return array
     */

    public function getAllSleep($redisKey) {

        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            return NULL;
        }

        return $redis->lrange($redisKey, 0, -1);
    }

    /*
     * 获得沉睡用户数量
     * return int or NULL
     */

    public function getSleepCount($redisKey) {

        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            return NULL;
        }

        return $redis->llen($redisKey);
    }

    /**
     * 汇总用户的资金记录
     */
    public function user_log_summary() {
        $user_id = intval($_REQUEST['uid']);
        $time = $_REQUEST['date'] ? to_timespan($_REQUEST['date']) : false;

        if ($user_id) {
            $user_service = new \core\service\UserLogService();
            $result = $user_service->getSummary($user_id, $time);
            $total = array_sum($result);

            $this->assign('summary', $result);
            $this->assign('total', $total);
        }

        $this->assign('user_id', $user_id);
        $this->assign('date', $date);
        $this->display();
    }


    /*
     * 得到redis中sleepUser的ID
     * @param $redisKey    redis中的key
     * @param $from        起始位置
     * @param $to          终止位置
     * return array or NULL
     */

    public function getSleepUserIds($redisKey, $count, $order, $sort = 0, $from, $to) {
        $redis = SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            return NULL;
        }
        if ($sort == 1) {
            $from_new = $count - $to - 1;
            $to_new = $count - $from - 1;

            $sleepUserIds = $redis->lrange($redisKey, $from_new, $to_new);
            $sleepUserIds = array_reverse($sleepUserIds);
        } else {
            $sleepUserIds = $redis->lrange($redisKey, $from, $to);
        }
        return $sleepUserIds;
    }

    public function saveUserInviteCode() {

        $userId = $_GET['id'];
        $inviteCode = $_GET['inviteCode'];
        $couponLogService = new CouponLogService();
        $ret = $couponLogService->changeRegShortAlias($userId, $inviteCode);
        echo json_encode(array('ret' => $ret));

        //return $ret;
    }

    public function insertCarnivalUsers() {
        $user_id = $_REQUEST['user_id'];
        $user_name = $_REQUEST['user_name'];
        $gift_practical = $_REQUEST['gift_practical'];
        $gift_virtual = $_REQUEST['gift_virtual'];
        $activityCaSer = new \core\service\ActivityCarnivalService();
        return $activityCaSer->insertCarnivalUser($user_id, $user_name, $gift_practical, $gift_virtual);
    }

    public function export_sleep_csv() {
        set_time_limit(0);

        $userModel = new Model();
        $sql = "SELECT id,user_name,real_name,mobile,create_time,login_time,invite_code,group_id from " . DB_PREFIX . "user where real_name != '' and
            login_time != 0 and id not in (select distinct user_id from " . DB_PREFIX . "deal_load)";
        if ($_GET['isInSG'] != 1) {
            $sql .= " and group_id not in ({$GLOBALS['sys_config']['SPECIAL_COUPON_USER_GROUP']})";
        }
        $list = $userModel->query($sql);


        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => $sql
                )
        );




        if (is_array($list) && count($list) > 0) {
            $content = iconv("utf-8", "gbk", "用户ID,用户名,姓名,手机号,注册时间,最后登录时间,邀请码,分组ID");
            $content .= "\n";
            foreach ($list as $k => $v) {
                $user_value['id'] = iconv('utf-8', 'gbk', '"' . $v['id'] . '"');
                $user_value['user_name'] = iconv('utf-8', 'gbk', '"' . str_replace(' ', ' ', $v['user_name']) . '"');
                $user_value['real_name'] = iconv('utf-8', 'gbk', '"' . str_replace(' ', ' ', $v['real_name']) . '"');
                $user_value['mobile'] = "\"\t" . $v['mobile'] . "\"";
                $user_value['create_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['create_time']) . '"');
                $user_value['login_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['login_time']) . '"');
                $user_value['invite_code'] = iconv('utf-8', 'gbk', '"' . str_replace(' ', ' ', $v['invite_code']) . '"');
                $user_value['group_id'] = iconv('utf-8', 'gbk', '"' . $v['group_id'] . '"');
                $content .= implode(",", $user_value) . "\n";
            }
            $filename = 'sleepUser_' . to_date(get_gmtime(), 'Y-m-d_H-i-s');
            header("Content-Disposition: attachment; filename=" . $filename . ".csv");
            echo $content;
        } else {
            $this->error(L("NO_RESULT"));
        }
    }

    public function doWithdrawLimitApply() {
        try {
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $permissions = explode(',', app_conf('WITHDRAW_LIMIT_APPLY'));
            if (!in_array($adm_session['adm_role_id'], $permissions)) {
                throw new \Exception('您无权发起限制提现申请。');
            }
            $platform_account_type = isset($_POST['platform_account_type']) && strpos($_POST['platform_account_type'], '_') ? explode('_', $_POST['platform_account_type']) : [];
            if (empty($platform_account_type)) {
                throw new \Exception('限制提现用户类型不正确');
            }
            $uid = isset($_POST['userId']) ? intval($_POST['userId']) : null;
            if (empty($uid)) {
                throw new \Exception('用户ID不能为空！');
            }
            $type = isset($_POST['withdraw_limit_type']) ? intval($_POST['withdraw_limit_type']) : null;
            if (!in_array($type, array_keys(\core\service\UserCarryService::$withdrawLimitTypeCn))) {
                throw new \Exception('请选择申请类型！');
            }
            $uname = $GLOBALS['db']->get_slave()->getOne("SELECT user_name FROM firstp2p_user WHERE id = '{$uid}'");
            if (empty($uname)) {
                throw new \Exception('用户名称不能为空！');
            }
            $amount = isset($_POST['limit_amount']) ? floatval($_POST['limit_amount']) : null;
            if (empty($amount)) {
                throw new \Exception('限制金额不能为空！');
            }
            $memo = isset($_POST['memo']) ? addslashes($_POST['memo'])  : '';
            $usercarryService = new \core\service\UserCarryService();
            $record = [];
            $record['userId'] = $uid;
            $record['username'] = $uname;
            $record['amount'] = !empty($_POST['isWhiteList']) ? 0 : bcsub($amount, 0, 2);
            $record['remain_money'] = !empty($_POST['isWhiteList']) ? bcmul(bcsub($amount, 0, 2), 100): 0;
            $record['limit_type'] = $type;
            $record['memo'] = $memo;
            $record['platform'] = $platform_account_type[0];
            $record['account_type'] = $platform_account_type[1];
            if (false === $usercarryService->addWithdrawLimitRecord($record)) {
                throw new \Exception('提交限制提现申请处理失败！');
            }
            return $this->success();
        }
        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


    // 客服查询
    public function custServInquir() {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $log['function'] = __CLASS__.' | '.__FUNCTION__;
        $log['visit_name'] = 'visit_name='.$adm_session['adm_name'].'('.$adm_session['adm_role'].')';
        $log['visit_time'] = 'visit_time='.date('Y年m月d日 H:i:s', time());

        //定义条件
        $where = 'is_delete = 0';

        $mobile = (int) $_GET['mobile'];
        !empty($mobile) && $where .= " and mobile = '".$mobile."'";

        $id = (int) $_GET['id'];
        !empty($id) && $where .= " and id = ".$id."";

        $user_id = (string) $_GET['user_id'];
        !empty($user_id) && $where .= ' and id = ' . intval(de32Tonum($user_id));

        $idno = (string) $_GET['idno'];
        !empty($idno) && $where .= " and idno = '".strtoupper(addslashes($idno))."'";

        $bankcard = (string) $_GET['bankcard'];
        if(!empty($bankcard)){
            $card_user_info = (new UserBankcardModel())->getRowByCardNum($bankcard);
            !empty($card_user_info['user_id']) && $where .= ' and id = ' . $card_user_info['user_id'];
        }

        $log['where'] = $where;

        $name = $this->getActionName();
        if ($where != 'is_delete = 0') {
            $page_flag['flag'] = 1;
            $user_info = ($_GET['bankcard'] != '' && empty($card_user_info['user_id'])) ? $user_info = array() : DI($name)->where($where)->field('id,user_name,real_name,mobile,idno,create_time,money,lock_money,is_effect,user_purpose')->find();
            $log[table_name] = 'table_name='.$name;
            $log[user_info] = 'user_info='.$user_info['id'];
            if ($user_info) {
                unset($page_flag);

                //用户统计
                $user_statics = user_statics($user_info['id']);
                //---合并普惠数据
                $user_statics_ncfph = (new \core\service\ncfph\AccountService())->getUserStat($user_info['id']);
                $user_statics_wx = $user_statics;
                $user_statics = \core\service\ncfph\AccountService::mergeP2P($user_statics, $user_statics_ncfph);

                $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
                $account_data = (new \core\service\AccountService())->getUserSummary($user_info['id'], true);
                $p2p_user_statics = (new \core\service\ncfph\AccountService())->getSummary($user_info['id']);
                $account_data = \core\service\ncfph\AccountService::mergeP2P($account_data, $p2p_user_statics);

                $total = \libs\utils\Finance::addition(array($user_statics['new_stay'], $user_info['money'], $user_info['lock_money'], $accountInfo['totalMoney']), 2);

                //脱敏相关信息
                $info[0]['mobile']    = adminMobileFormat($user_info['mobile']);
                $info[0]['user_name'] = userNameFormat($user_info['user_name']);
                $info[0]['real_name'] = $user_info['real_name'];
                $info[0]['idno']      = idnoFormat($user_info['idno']);
                if (trim($_GET['bankcard']) != '') {
                    $info[0]['bankcard'] = formatBankcard(trim($_REQUEST['bankcard']));
                } else {
                    $sql = "select bankcard from " . DB_PREFIX . "user_bankcard where user_id = '" . $user_info['id'] . "'";
                    $info[0]['bankcard'] = formatBankcard($GLOBALS['db']->get_slave()->getOne($sql));
                }

                $info[0]['visit_time']  = date('Y年m月d日 H:i:s', time());
                $info[0]['create_time'] = to_date($user_info['create_time']);
                $info[0]['is_effect'] =  $user_info['is_effect']  ? '有效' : '无效';
                $info[0]['money']       = $total;
                $info[0]['principal']   = \libs\utils\Finance::addition(array($account_data['corpus'], $account_data['dt_norepay_principal']));
                $info[0]['duotou_norepay_principal']   =  $account_data['dt_norepay_principal'];
                $info[0]['id']          = $user_info['id'];

                $info[0]['user'] = $adm_session['adm_name'].'('.$adm_session['adm_role'].')';
                $info[0]['idnum'] = 1;

                $info[0]['ph_norepay_principal'] = $user_statics_ncfph['norepay_principal'];
                $info[0]['wx_norepay_principal'] = $user_statics_wx['u_stay']['principal'];
            }
        }

        logger::info(implode(" | ", $log));
        $this->assign("user_info",$info);
        $this->assign("page_flag",$page_flag);
        $this->display();
    }
    public function custServInquir_detail() {
        $id = intval($_REQUEST ['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $field = 'id,user_name,email,mobile,group_id,real_name,idno,id_type,byear,bmonth,bday,sex,level_id';
        $vo = M(MODULE_NAME)->where($condition)->field($field)->find();

        //获取用户的地址
        if (!intval($id)) return false;
        $address['user_id'] = $id;
        $info_user = M('user_address')->where($address)->field('user_id,consignee,area,address,mobile,postcode')->findAll();
        if (!empty($info_user)) {
            foreach ($info_user as &$val){
                if ($val['area'] && strpos($val['area'],':') !==0) {
                    $val['area'] = explode(":",$val['area']);
                }
            }
            unset($val);
        }
        //身份证件类型
        $ID_TYPE = $GLOBALS['dict']['ID_TYPE'];
        if (array_key_exists($vo['id_type'],$ID_TYPE)) {
            $vo['idno_type'] = $ID_TYPE[$vo['id_type']];
        }

        //性别
        if ($vo['sex'] == 0) {
            $vo['sex'] = '女';
        } else {
            $vo['sex'] = '男';
        }
        $vo['birthday'] = $vo['byear'].'年'.$vo['bmonth'].'月'.$vo['bday'].'日';
        //银行信息
        $user_bankc_info = M("UserBankcard")->where("user_id=" . $vo['id'])->field("bankcard,card_name,bankzone,bank_id")->find();
        $bank_list = $GLOBALS['db']->getAll("SELECT id,name from " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        if ($user_bankc_info) {
            foreach ($bank_list as $v) {
                if ($v['id'] == $user_bankc_info['bank_id']) {
                    $user_bankc_info['name'] = $v['name'];
                    break;
                }
            }
        }
        // 会员所属网站
        $level_info = M("UserGroup")->where("id=" . $vo['group_id'])->find();
        $vo['user_net'] = $level_info['name'];
        //返利系数
        $rpc = new \libs\rpc\Rpc();
        $res = $rpc->local('CouponService\getOneUserCoupon', array($id));
        $vo['user_code'] = $res['short_alias'];
        $vo['user_ratio'] = $res['rebate_ratio'];
        $reco_user = $rpc->local('CouponBindService\getByUserId', array($id));
        $vo['reco_user_code'] = $reco_user['short_alias'];
        $vo['reco_user_name'] = M("User")->where("id=" . $reco_user['refer_user_id'])->getField("real_name");

        //资金记录
        $point_level = M("UserLevel")->where("id=" . $vo['level_id'])->find();
        $user_info['user_level'] = $level_info['name'];
        $user_info['point_level']= $point_level['name'];
        $user_info['discount']   = $level_info['discount']*10;
        $result = $rpc->local('UserLogService\get_user_log',array(array(0,20),$vo['id'],'money'));
        $money_log = $result['list'];
        foreach ($money_log as $key=>$value) {
            $money_log[$key]['log_time'] = to_date($value['log_time'],"m-d H:i");
        }

        $money_log_pre = array_slice($money_log, 0 ,5);
        $more_log = array_slice($money_log, 5);

        // 提现失败记录
        $withdrawFailed = array();
        $withdrawMore = array();
        $withdrawFailed = \libs\db\Db::getInstance('firstp2p', 'adminslave')->getAll("SELECT money,id,create_time,withdraw_msg FROM firstp2p_user_carry WHERE withdraw_status = 2 AND user_id = '{$id}' AND create_time >= '".strtotime('-7000 days')."'");
        foreach ($withdrawFailed as $idx => $withdraw)
        {
            $withdraw['create_time'] = to_date($withdraw['create_time']);
            if($idx <= 4)
            {
                $withdrawTips[] = $withdraw;
            }
            else
            {
                $withdrawMore[] = $withdraw;
            }
        }
        $this->assign('moreWithdraw', $withdrawMore);
        $this->assign('withdrawFailed', $withdrawTips);
        $this->assign("more_log",$more_log);
        $this->assign("money_log_pre",$money_log_pre);
        $this->assign("info_user",$info_user);
        $this->assign("vo",$vo);
        $this->assign("user_bankc_info",$user_bankc_info);
        $this->display();
    }

    /**
     * 编辑个人会员敏感信息时，记录管理员操作记录
     * @param int $userId
     * @param array $request
     */
    private function _recordUserOperateLog($request) {
        $oldUserInfo = $newUserInfo = array();
        // 获取个人会员信息
        $userBaseInfo = M('User')->where(array('id'=>intval($request['id'])))->find();
        if (empty($userBaseInfo)) {
            return array('oldUserInfo'=>$oldUserInfo, 'newUserInfo'=>$newUserInfo);
        }
        // 记录更新前后，用户的信息变化
        // 邮箱
        if (isset($request['email']) && strcmp($request['email'], $userBaseInfo['email']) !== 0) {
            $oldUserInfo['email'] = $userBaseInfo['email'];
            $newUserInfo['email'] = $request['email'];
        }
        // 手机号
        if (isset($request['mobile']) && strcmp($request['mobile'], $userBaseInfo['mobile']) !== 0) {
            $oldUserInfo['mobile'] = $userBaseInfo['mobile'];
            $newUserInfo['mobile'] = $request['mobile'];
        }
        // 真实姓名
        if (isset($request['real_name']) && strcmp($request['real_name'], $userBaseInfo['real_name']) !== 0) {
            $oldUserInfo['real_name'] = $userBaseInfo['real_name'];
            $newUserInfo['real_name'] = $request['real_name'];
        }
        // 身份证号
        if (isset($request['idno']) && strcmp($request['idno'], $userBaseInfo['idno']) !== 0) {
            $oldUserInfo['idno'] = $userBaseInfo['idno'];
            $newUserInfo['idno'] = $request['idno'];
        }
        // 证件类型
        if (isset($request['id_type']) && strcmp($request['id_type'], $userBaseInfo['id_type']) !== 0) {
            $oldUserInfo['id_type'] = $userBaseInfo['id_type'];
            $newUserInfo['id_type'] = $request['id_type'];
        }
        // 用户状态
        if (isset($request['is_effect']) && strcmp($request['is_effect'], $userBaseInfo['is_effect']) !== 0) {
            $oldUserInfo['is_effect'] = $userBaseInfo['is_effect'];
            $newUserInfo['is_effect'] = $request['is_effect'];
        }
        return array('oldUserInfo'=>$oldUserInfo, 'newUserInfo'=>$newUserInfo);
    }

    /**
     * 编辑个人会员绑卡敏感信息时，记录管理员操作记录
     * @param int $userId
     * @param array $request
     */
    private function _recordUserCardOperateLog($request, $userBankCardInfo = array(), $userId = 0) {
        $oldUserCardInfo = $newUserCardInfo = array();
        // 获取个人会员绑卡信息
        if (empty($userBankCardInfo)) {
            $userBankcardService = new UserBankcardService();
            $bankCardUserId = isset($request['user_id']) ? $request['user_id'] : $userId;
            $userBankCardInfo = $userBankcardService->getBankcard($bankCardUserId);
        }
        if (empty($userBankCardInfo)) {
            return array('oldUserCardInfo'=>$oldUserCardInfo, 'newUserCardInfo'=>$newUserCardInfo);
        }
        // 记录更新前后，用户绑卡信息的变化
        // 银行编号
        if (isset($request['bank_id']) && strcmp($request['bank_id'], $userBankCardInfo['bank_id']) !== 0) {
            $oldUserCardInfo['bank_id'] = $userBankCardInfo['bank_id'];
            $newUserCardInfo['bank_id'] = $request['bank_id'];
        }
        // 银行卡号
        if (isset($request['bankcard']) && strcmp($request['bankcard'], $userBankCardInfo['bankcard']) !== 0) {
            $oldUserCardInfo['bankcard'] = $userBankCardInfo['bankcard'];
            $newUserCardInfo['bankcard'] = $request['bankcard'];
        }
        // 开户网点
        if (isset($request['bankzone']) && strcmp($request['bankzone'], $userBankCardInfo['bankzone']) !== 0) {
            $oldUserCardInfo['bankzone'] = $userBankCardInfo['bankzone'];
            $newUserCardInfo['bankzone'] = $request['bankzone'];
        }
        // 开户名
        if (isset($request['card_name']) && strcmp($request['card_name'], $userBankCardInfo['card_name']) !== 0) {
            $oldUserCardInfo['card_name'] = $userBankCardInfo['card_name'];
            $newUserCardInfo['card_name'] = $request['card_name'];
        }
        // 开户行所在地-国家
        if (isset($request['region_lv1']) && strcmp($request['region_lv1'], $userBankCardInfo['region_lv1']) !== 0) {
            $oldUserCardInfo['region_lv1'] = $userBankCardInfo['region_lv1'];
            $newUserCardInfo['region_lv1'] = $request['region_lv1'];
        }
        // 开户行所在地-省
        if (isset($request['region_lv2']) && strcmp($request['region_lv2'], $userBankCardInfo['region_lv2']) !== 0) {
            $oldUserCardInfo['region_lv2'] = $userBankCardInfo['region_lv2'];
            $newUserCardInfo['region_lv2'] = $request['region_lv2'];
        }
        // 开户行所在地-市
        if (isset($request['region_lv3']) && strcmp($request['region_lv3'], $userBankCardInfo['region_lv3']) !== 0) {
            $oldUserCardInfo['region_lv3'] = $userBankCardInfo['region_lv3'];
            $newUserCardInfo['region_lv3'] = $request['region_lv3'];
        }
        // 开户行所在地-区县
        if (isset($request['region_lv4']) && strcmp($request['region_lv4'], $userBankCardInfo['region_lv4']) !== 0) {
            $oldUserCardInfo['region_lv4'] = $userBankCardInfo['region_lv4'];
            $newUserCardInfo['region_lv4'] = $request['region_lv4'];
        }
        // 绑卡状态
        if (isset($request['status']) && strcmp($request['status'], $userBankCardInfo['status']) !== 0) {
            $oldUserCardInfo['status'] = $userBankCardInfo['status'];
            $newUserCardInfo['status'] = $request['status'];
        }
        // 验卡状态
        if (isset($request['verify_status']) && strcmp($request['verify_status'], $userBankCardInfo['verify_status']) !== 0) {
            $oldUserCardInfo['verify_status'] = $userBankCardInfo['verify_status'];
            $newUserCardInfo['verify_status'] = $request['verify_status'];
        }
        // 联行号码
        if (isset($request['branch_no']) && strcmp($request['branch_no'], $userBankCardInfo['branch_no']) !== 0) {
            $oldUserCardInfo['branch_no'] = $userBankCardInfo['branch_no'];
            $newUserCardInfo['branch_no'] = $request['branch_no'];
        }
        // 对公对私标示
        if (isset($request['card_type']) && strcmp($request['card_type'], $userBankCardInfo['card_type']) !== 0) {
            $oldUserCardInfo['card_type'] = $userBankCardInfo['card_type'];
            $newUserCardInfo['card_type'] = $request['card_type'];
        }
        return array('oldUserCardInfo'=>$oldUserCardInfo, 'newUserCardInfo'=>$newUserCardInfo);
    }

    public function resetPaymentUserId() {
        $userId = intval($_GET['id']);
        if ($userId === 0) {
            $this->error('用户Id不能为空');
        }
        save_log('个人会员查看余额-更新payment_user_id，会员id['.$userId.']操作成功', 1, array('payment_user_id'=>$userBaseInfo['payment_user_id']), array('payment_user_id'=>0));
        $sql = " UPDATE firstp2p_user SET payment_user_id = '0' WHERE id = '{$userId}'";
        \libs\db\Db::getInstance('firstp2p', 'master')->query($sql);
        $this->success('更新成功');
    }


   public function syncUserBalance() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $userId = intval($_GET['id']);
        if ($userId === 0) {
            $this->error('用户Id不能为空');
        }
        if ($userId != app_conf('SUPERVISION_ADVANCE_ACCOUNT')) {
            $this->error('该用户信息错误');
        }
        $svService = new SupervisionAccountService();
        if (!$svService->isSupervisionUser($userId)) {
            $this->error('用户尚未开通存管账户');
        }

        $svUserBalance = $svService->balanceSearch($userId);
        $userActuralMoney = $userActuralFreezeMoney = 0.00;
        if ($svUserBalance['status'] == SupervisionBaseService::RESPONSE_SUCCESS) {
            $userActuralMoney = bcdiv($svUserBalance['data']['availableBalance'], 100, 2);
            $userActuralFreezeMoney = bcdiv($svUserBalance['data']['freezeBalance'], 100, 2);
        }
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        try {
            $db->startTrans();
            $sql = " UPDATE firstp2p_user SET money = '{$userActuralMoney}',lock_money = '{$userActuralFreezeMoney}' WHERE id = '{$userId}'";
            $db->query($sql);
            if ($db->affected_rows() < 1) {
                throw new \Exception('执行失败');
            }

            $sql = " UPDATE firstp2p_user_third_balance SET supervision_balance = '{$userActuralMoney}', supervision_lock_money = '{$userActuralFreezeMoney}' WHERE user_id = '{$userId}'";
            $db->query($sql);
            if ($db->affected_rows() < 1) {
                throw new \Exception('执行失败');
            }
            $db->commit();
            $this->success('更新成功');
       } catch(\Exception $e) {
            $db->rollback();
            $this->error('更新失败');
       }
    }


    /**
     * 存管系统-账户注销
     */
    public function cancelUserAccount() {
        $userId = intval($_GET['userId']);
        if ($userId === 0 || !is_numeric($userId)) {
            $this->error('用户Id不能为空');
        }
        $this->getRpc('fundRpc');
        $supervisionAccountObj = new SupervisionAccountService();
        $result = $supervisionAccountObj->memberCancel($userId);
        if (empty($result) || $result['status'] != SupervisionAccountService::RESPONSE_SUCCESS || $result['respCode'] != SupervisionAccountService::RESPONSE_CODE_SUCCESS) {
            save_log('个人会员列表-注销用户，会员id['.$userId.']操作失败-errMsg:' . json_encode($result), 0);
            $this->error($result['respMsg']);
        }
        save_log('个人会员列表-注销用户，会员id['.$userId.']操作成功', 1);
        $this->success('账户注销成功');
    }

    /**
     * 清理用户存管缓存
     */
    public function clearUserSupervisionCache() {
        $userId = intval($_GET['id']);
        if ($userId === 0 || !is_numeric($userId)) {
            $this->error('用户Id不能为空');
        }
        $supervisionAccountObj = new SupervisionAccountService();
        $supervisionAccountObj->clearUserAllSupervisionCache($userId);
        $this->success('清理用户存管缓存成功');
    }

    /**
     * 存管升级协议
     */
    public function wxFreepayment() {
        //更新签署状态
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $userId = !empty($_POST['userId']) ? intval($_POST['userId']) : 0;
            $status = !empty($_POST['status']) ? intval($_POST['status']) : 0;
            if ($userId) {
                // 获取用户在理财端的用户信息
                $userInfo = M('User')->getById($userId);
                if (empty($userInfo)) {
                    $this->error("用户不存在");
                }
                $GLOBALS['db']->update('firstp2p_user', array('wx_freepayment' => $status), "id={$userId}");
                save_log('更新wx_freepayment，会员id['.$userId.']操作成功', 1, array('wx_freepayment'=>$userInfo['wx_freepayment']), array('wx_freepayment'=>$status));
                $this->success('更新成功');
            }
            $this->error("参数错误");
        }

        $userId = !empty($_GET['userId']) ? intval($_GET['userId']) : 0;
        $userInfo = [];
        if ($userId) {
            // 获取用户在理财端的用户信息
            $userInfo = M('User')->getById($userId);
        }
        $this->assign("userId", $userId);
        $this->assign("userInfo", $userInfo);
        $this->display();
    }

    /**
     * 用户实名更改日志列表
     */
    public function userIdentityModifyLog() {
        $condition = array();

        // 会员id
        if (!empty($_REQUEST['user_id'])) {
            $condition['user_id'] = (int) $_REQUEST['user_id'];
        }

        // 姓名
        if (!empty($_REQUEST['real_name'])) {
            $condition['real_name'] = addslashes($_REQUEST['real_name']);
        }

        // 证件类型
        if (isset($_REQUEST['id_type']) && $_REQUEST['id_type'] != -1) {
            $condition['id_type'] = (int) $_REQUEST['id_type'];
        }

        // 证件编号
        if (!empty($_REQUEST['idno'])) {
            $condition['idno'] = addslashes($_REQUEST['idno']);
        }

        // 证件类型
        if (!empty($_REQUEST['order_id'])) {
            $condition['order_id'] = (int) $_REQUEST['order_id'];
        }

        // 状态
        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;//默认全部
        }
        if (isset($_REQUEST['status']) && $_REQUEST['status'] != -1) {
            $condition['status'] = (int) $_REQUEST['status'];
        }

        //创建日期
        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = strtotime($_REQUEST['apply_start']);
            $condition['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = strtotime($_REQUEST['apply_end']);
            $condition['create_time'] = array('elt', $apply_end);
        }

        //完成日期
        if (!empty($_REQUEST['finish_start'])) {
            $finish_start = strtotime($_REQUEST['finish_start']);
            $condition['update_time'] = array('egt', $finish_start);
        }

        if (!empty($_REQUEST['finish_end'])) {
            $finish_end = strtotime($_REQUEST['finish_end']);
            $condition['update_time'] = array('elt', $finish_end);
        }

        $list = $this->_list(D('UserIdentityModifyLog'), $condition, 'id', false, false);
        foreach ($list as $key => $val) {
            $list[$key]['idno'] = idnoFormat($val['idno']);
        }


        $this->assign("idTypes", $GLOBALS['dict']['ID_TYPE']);
        $this->assign('list', $list);
        $this->display();
    }

    public function getUserIdentityModifyInfo() {
        if (empty($_REQUEST['id'])) {
            echo json_encode(['code' => 4000, 'msg' => '参数错误']);
            exit;
        }

        $id = (int) $_REQUEST['id'];
        $log = MI('UserIdentityModifyLog')->find($id);
        if (empty($log)) {
            echo json_encode(['code' => 4001, 'msg' => '数据不存在']);
            exit;
        }
        $str = "<div class='info'>
                <span class='span_block'>编号：" . $log['user_id'] . "</span>
                <span class='span_block'>姓名：" . $log['real_name'] . "</span>
                <span class='span_block'>证件类型：" . $GLOBALS['dict']['ID_TYPE'][$log['id_type']] . "</span>
                <span class='span_block'>证件号：" . $log['idno'] . "</span>
                <span class='span_block'>状态：" . UserIdentityModifyLogModel::$statusMap[$log['status']] . "</span>
                <span class='span_block'>创建时间：" . format_date($log['create_time']) . "</span>
                <span class='span_block'>完成时间：" . format_date($log['update_time']) . "</span>
                </div>";
        echo json_encode(['code' => 0000, 'msg' => $str]);

    }
    //用户黄金明细导出
    public function account_export_gold() {

        set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $user_id = intval($_REQUEST['id']);
        if ($user_id <= 0) {
            $this->error('导出失败');
        }
        $user_info = M("User")->getById($user_id);
        if (empty($user_info)) {
            $this->error('导出失败');
        }
        $request = new RequestCommon();
        $i = 0;
        $total = 0;
        $pageSize = 1000;
        $hasTotalCount = 1;
        $pageNo = 1;
        $log_info = empty($_REQUEST['log_info']) ? '': trim($_REQUEST['log_info']);
        if (!empty($log_info)){
            $param['logInfo'] = $log_info;
        }
        if (!empty($_REQUEST['log_time_start'])){
            $param['startTime'] = strtotime($_REQUEST['log_time_start']);
        }

        if (!empty($_REQUEST['log_time_end'])){
            $param['endTime'] = strtotime($_REQUEST['log_time_end']);
        }
        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportUserDetail',
                'analyze' => $condition
            )
        );
        $file_name = $user_info['user_name'] . ' 黄金帐户明细';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($file_name) . '.csv"');
        header('Cache-Control: max-age=0');
        $title = array(
            "编号", "投资记录ID", "交易类型", "操作时间", "黄金变动","备注",
            "冻结(+)/解冻(-)", "黄金账户资金总额", "黄金账户可用余额", "黄金账户冻结总额"
        );
        $fp = fopen('php://output', 'a');
        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));
        do {
            try {
                $param = array(
                    'userId' => $user_id,
                    'pageNum' => $pageNo,
                    'pageSize' => $pageSize
                );
                $request->setVars($param);
                $response = $this->getRpc('goldRpc')->callByObject(array(
                    'service' => 'NCFGroup\Gold\Services\User',
                    'method' => 'getGoldUserLogListForAdmin',
                    'args' => $request,
                ));
                if(empty($response)) {
                    $this->error("rpc请求失败");
                }
                if($response['errCode'] != 0) {
                    $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
                }
                if ($total == 0) {
                    // 获取总的数据条数
                    $total = $response['data']['totalSize'];
                    $hasTotalCount = 0;
                }
                $items = $response['data']['data'];
                foreach ( $items as $k =>$log) {

                    if ($log['logInfo'] == '提现失败' && $log['note'] == '' && $log['logAdminId'] > 0)
                    {
                        $log['logInfo'] = '提现还款';
                    }
                    $row = sprintf("%s||%s||\t%s||%s||%s||%s||%s||%s||%s", $log['id'], $log['dealLoadId'],$log['logInfo'], to_date($log['logTime']), format_price($log['gold'],false), htmlspecialchars($log['note']), format_price($log['lockMoney'],false), format_price($log['remainingMoney'],false), format_price($log['remainingTotalMoney'],false), format_price($log['remainingLockMoney'],false)
                    );
                    fputcsv($fp, explode('||', iconv("utf-8", "gbk", $row)));
                }
            } catch (\Exception $ex) {
                Logger::error('exportList: '.$ex->getMessage());
            }
            // 处理下一页数据
            $pageNo++;
            $i += $pageSize;
        } while ($i <= $total);
        exit;

    }

    // 授权展示
    public function account_auth() {
        if (!is_numeric($_REQUEST['id'])) {
            $this->error('参数错误');
        }
        $userId = intval($_REQUEST['id']);
        $accountAuthorizationService = new AccountAuthorizationService();
        $authList = $accountAuthorizationService->getAuthList($userId);
        $this->assign('authList', $authList);
        $this->display();
    }

    public function changeUserPurpose() {
        $this->error('该功能已下架');

        $this->assign('grantList', AccountAuthorizationModel::$grantTypeName);
        $this->assign('types', EnterpriseModel::instance()->getCompanyPurposeMap());
        $this->display();
    }

    public function doChangeUserPurpose() {
        $this->error('该功能已下架');

        if (!isset($_REQUEST['purpose']) || intval($_REQUEST['purpose']) == -1) {
            $this->error('用户类型不正确');
        }
        $purpose = intval($_REQUEST['purpose']);
        if (!isset($_REQUEST['userIds']) || empty($_REQUEST['userIds'])) {
            $this->error('待刷新用户列表不能为空');
        }
        $grantTypeList = !empty($_REQUEST['grant']) ? $_REQUEST['grant'] : []; //授权
        $logError = [];
        $accountService = new SupervisionAccountService();
        $accountAuthService = new AccountAuthorizationService();
        // 企业用户列表里面的企业用户
        $userIds = explode("\n", $_REQUEST['userIds']);
        foreach ($userIds as $userId) {
            $userId = trim($userId);
            if (empty($userId)) {
                continue;
            }
            $userObj = new UserService($userId);
            $sql = "SELECT id,user_purpose FROM firstp2p_user WHERE id = ".$userId;
            $userBaseData = \libs\db\Db::getInstance('firstp2p', 'master')->getRow($sql);
            if (empty($userBaseData)) {
                $logError[] = $userId.' 用户不存在';
                continue;
            }
            $userPurposeInfo = $userObj->getUserPurposeInfo($purpose);
            if (empty($userPurposeInfo) || empty($userPurposeInfo['supervisionBizType'])) {
                $logError[] = $userId.'不支持的账户类型';
                continue;
            }
            $grantList = $accountAuthService->convertToGrant($grantTypeList);
            $response = $accountService->updateUserPurpose($userId, $purpose, $userPurposeInfo['supervisionBizType'], $grantList);
            if ($response !== true) {
                $logError[] = $userId.$response;
                continue;
            }
        }

        $mesasge = '操作成功';
        if (!empty($logError)) {
            $message = implode("<br/>", $logError);
            return $this->error($message);
        }
        return $this->success($message, 0, "?m=User&a=changeUserPurpose", null , 10);
    }


    /**
     * 编企个人列表中的企业用户手机号级法人信息功能
     */
    public function editAgencyUserInfo()
    {
        try {
            $userId = intval($_GET['id']) ?  intval($_GET['id']) : 0;
            if (empty($userId))
            {
                throw new \Exception('用户id不能为空');
            }
            $userSrv = new UserService();
            $userInfo = $userSrv->getUser($userId);
            $pattern = $userInfo['mobile_code'].$userInfo['mobile']{0};
            if ($pattern != '866')
            {
                throw new \Exception('该用户非个人会员列表中的企业用户');
            }
            $userInfo = array(
                'userId' => $userId,
                'username' => $userInfo['user_name'],
                'realname' => $userInfo['real_name'],
            );
            $this->assign('userInfo', $userInfo);
        } catch (\Exception $e) {
            $this->assign('errorMsg', $e->getMessage());
        }
        $this->display('edit_agency_user_info');
    }

    /**
     * 提交代理人手机号或者企业法人信息修改
     */
    public function doEditAgencyUserInfo()
    {
        $resp= [
            'errCode' => 0,
            'errMsg' => '',
        ];
        try {
            $userId = intval($_POST['userId']) ? intval($_POST['userId']) : 0;
            if (empty($userId)) {
                throw new \Exception('用户 id 不能为空');
            }
            $userSrv = new UserService();
            $userInfo = $userSrv->getUser($userId);

            // 请求数据
            $requestUcfpay = $requestSupervision = [];

            // 代理人手机号
            $agencyMobile= isset($_POST['agencyMobile']) ? trim($_POST['agencyMobile'])  : 0;
            if (!empty($agencyMobile)) {
                $requestUcfpay['agentPersonPhone'] = $requestSupervision['agentPersonPhone'] = $agencyMobile;
            }

            // 必填参数
            $requestUcfpay['userId'] = $requestSupervision['userId'] = $userId;

            // 法人姓名
            $corporation = isset($_POST['corporation']) ? trim($_POST['corporation']) : '';
            if (!empty($corporation)) {
                $requestUcfpay['coperation'] = $requestSupervision['corporationName'] = $corporation;
            }

            // 法人证件号
            $corporationCard = isset($_POST['corporationCard']) ? trim($_POST['corporationCard']) : '';
            if (!empty($corporationCard)) {
                $requestUcfpay['coperationCard'] = $requestSupervision['corporationCertNo'] = $corporationCard;
            }

            // 开通超级账户的用户，同步超级账户数据
            if ($userInfo['payment_user_id'] == $userId) {
                $result = PaymentApi::instance()->request('newcompupdate', $requestUcfpay);
                if (!isset($result['respCode']) || $result['status'] != SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
                    throw new \Exception('同步超级账户失败,请重新提交');
                }
            } else {
                throw new \Exception('用户尚未开通超级账户');
            }

            // 开通存管， 则同步存管数据
            if ($userInfo['supervision_user_id'] == $userId) {
                $requestSupervision['noticeUrl'] = app_conf('NOTIFY_DOMAIN') .'/supervision/enterpriseUpdateNotify';
                $result = StandardApi::instance('supervision')->request('enterpriseUpdateApi', $requestSupervision);
                if (!isset($result['respCode'])) {
                    throw new \Exception('同步存管账户超时，请联系支付审核确认后再次提交');
                }
                if ($result['respCode'] != SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
                    throw new \Exception($result['respMsg']);
                }
            }
        } catch (\Exception $e) {
            $resp['errCode'] = 1;
            $resp['errMsg'] = $e->getMessage();
        }
        echo json_encode($resp);
    }

    /**
     *风险校验
     */
    public function ajaxCheckRiskInfo(){
        $msg="";
        $userId = $_REQUEST['id'];
        $groupId = $_REQUEST['group_id'];
        $couponBindService= new CouponBindService();
        $count = $couponBindService->getCountByReferUserId($userId);
        if($count){
            $msg .= "该用户名下有客户\r\n";
        }

        $condition['is_delete'] = 0;
        $condition['id'] = $userId;
        $userInfo = M(MODULE_NAME)->where($condition)->find();
        $userGroupService = new UserGroupService();
        $result = $userGroupService->checkServiceStatusIsSame($userInfo['group_id'],$groupId);
        if(!$result){
            $msg .= "该用户在服务组和非服务组之间跳转\r\n";
        }
        if($msg){
            $this->error($msg,'1');
        }else{
            $this->success($msg,'1');
        }
    }

    /**
     * 用户预约登记列表
     */
    public function booking_index() {
        $map = [];
        // 用户ID
        if (trim($_REQUEST['user_id']) != '') {
            $map['user_id'] = intval($_REQUEST['user_id']);
        }
        // 真实姓名
        if(trim($_REQUEST['real_name']) != '') {
            $map['user_id'] = DI("User")->where("real_name='".trim($_REQUEST['real_name'])."'")->getField('id');
        }
        // 手机号
        if(trim($_REQUEST['mobile']) != '') {
            $map['user_id'] = DI("User")->where("mobile='".trim($_REQUEST['mobile'])."'")->getField('id');
        }
        // 预约场次
        if (trim($_REQUEST['reserved_session']) != '') {
            $map['reserved_session'] = (int)$_REQUEST['reserved_session'];
        }
        // 预约状态
        if (trim($_REQUEST['status']) != '') {
            $map['status'] = (int)$_REQUEST['status'];
        }
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $model = DI('Booking');
        if (!empty($model) && isset($_REQUEST['user_id'])) {
            $list = $this->_list($model, $map);
            $list = $this->appendBookingInfo($list);
            $this->assign('list', $list);
        }

        $this->display();
    }

    /**
     * 整理用户预约数据
     * @param array $list
     * @return array
     */
    private function appendBookingInfo($list) {
        if (empty($list)) {
            return $list;
        }

        $accountObj = new AccountService();
        $sessions = array();
        foreach ($list as $key => $item) {
            // 预约时间段
            try {
                $screen = $item['reserved_session'];
                if (!isset($sessions[$screen])) {
                    $sessionInfo = BookService::getSession($screen);
                    $sessions[$screen] = $sessionInfo;
                } else {
                    $sessionInfo = $sessions[$screen];
                }
            } catch (\Exception $ex) {
                $sessionInfo = array();
                $sessions[$screen] = $sessionInfo;
            }

            $list[$key]['time_range'] = isset($sessionInfo['sessionDesc']) ? $sessionInfo['sessionDesc'] : '暂无';
            $list[$key]['city_name'] = isset($sessionInfo['cityName']) ? $sessionInfo['cityName'] : '暂无';

            // 获取用户基本信息
            $userInfo = MI('User')->where("id='" . trim($item['user_id']) . "' AND is_delete=0")->find();
            if (empty($userInfo)) {
                continue;
            }

            $list[$key]['user_name'] = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
            $list[$key]['real_name'] = isset($userInfo['real_name']) ? $userInfo['real_name'] : '';
            $list[$key]['idno'] = isset($userInfo['idno']) ? $userInfo['idno'] : '';
            $list[$key]['mobile'] = isset($userInfo['mobile']) ? $userInfo['mobile'] : '';
            $list[$key]['sex'] = isset($userInfo['sex']) ? $userInfo['sex'] : '';
            $list[$key]['user_purpose'] = isset($userInfo['user_purpose']) ? $userInfo['user_purpose'] : '';

            // 获取用户资金信息
            $userMoneyInfo = $accountObj->getUserSummaryNew($item['user_id']);
            if (empty($userMoneyInfo)) {
                continue;
            }
            $list[$key]['wx_cash'] = isset($userMoneyInfo['wx_cash']) ? format_price($userMoneyInfo['wx_cash']) : '暂无';
            $list[$key]['wx_cash_init'] = isset($userMoneyInfo['wx_cash']) ? $userMoneyInfo['wx_cash'] : '';
            $list[$key]['wx_freeze'] = isset($userMoneyInfo['wx_freeze']) ? format_price($userMoneyInfo['wx_freeze']) : '暂无';
            $list[$key]['wx_freeze_init'] = isset($userMoneyInfo['wx_freeze']) ? $userMoneyInfo['wx_freeze'] : '';
            $list[$key]['ph_cash'] = isset($userMoneyInfo['ph_cash']) ? format_price(bcdiv($userMoneyInfo['ph_cash'], 100, 2)) : '暂无';
            $list[$key]['ph_cash_init'] = isset($userMoneyInfo['ph_cash']) ? $userMoneyInfo['ph_cash'] : '';
            $list[$key]['ph_freeze'] = isset($userMoneyInfo['ph_freeze']) ? format_price(bcdiv($userMoneyInfo['ph_freeze'], 100, 2)) : '暂无';
            $list[$key]['ph_freeze_init'] = isset($userMoneyInfo['ph_freeze']) ? $userMoneyInfo['ph_freeze'] : '';
            $list[$key]['corpus'] = isset($userMoneyInfo['corpus']) ? format_price($userMoneyInfo['corpus']) : ''; // 待收本金
            $list[$key]['corpus_init'] = isset($userMoneyInfo['corpus']) ? $userMoneyInfo['corpus'] : '';
            $list[$key]['income'] = isset($userMoneyInfo['income']) ? format_price($userMoneyInfo['income']) : ''; // 待收利息
            $list[$key]['income_init'] = isset($userMoneyInfo['income']) ? $userMoneyInfo['income'] : '';
        }
        return $list;
    }

    /**
     * 导出用户预约列表
     */
    public function get_booking_csv()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');

        $map = [];
        // 用户ID
        if (trim($_REQUEST['user_id']) != '') {
            $map['user_id'] = intval($_REQUEST['user_id']);
        }
        // 真实姓名
        if(trim($_REQUEST['real_name']) != '') {
            $map['user_id'] = DI("User")->where("real_name='".trim($_REQUEST['real_name'])."'")->getField('id');
        }
        // 手机号
        if(trim($_REQUEST['mobile']) != '') {
            $map['user_id'] = DI("User")->where("mobile='".trim($_REQUEST['mobile'])."'")->getField('id');
        }
        // 预约场次
        if (trim($_REQUEST['reserved_session']) != '') {
            $map['reserved_session'] = (int)$_REQUEST['reserved_session'];
        }
        // 预约状态
        if (trim($_REQUEST['status']) != '') {
            $map['status'] = (int)$_REQUEST['status'];
        }
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $list = [];
        $model = DI('Booking');
        if (!empty($model) && isset($_REQUEST['user_id'])) {
            $list = $this->_list($model, $map);
            $list = $this->appendBookingInfo($list);
        }
        if (empty($list)) {
            $this->error('暂无符合的数据，无法导出');
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => $map
            )
        );
        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=Booking_{$datatime}.csv");

        $title = array(
            '编号','用户ID', '用户姓名', '手机号', '身份证号',
            '网信余额', '网信冻结', '普惠余额', '普惠冻结',
            '待收本金', '预约场次', '预约时间段', '预约提交时间',
            '预约状态',
        );
        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }
        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);

        foreach ($list as $v) {
            // 网信余额显示
            $wxCashString = $wxFreezeString = $phCashString = $phFreezeString = $corpusString = '小于1万';
            if (!empty($v['wx_cash_init']) && bccomp($v['wx_cash_init'], 10000, 2) >= 0) {
                $wxCashString = number_format(floatval(bcdiv($v['wx_cash_init'], 10000, 2))) . '万';
            }
            if (!empty($v['wx_freeze_init']) && bccomp($v['wx_freeze_init'], 10000, 2) >= 0) {
                $wxFreezeString = number_format(floatval(bcdiv($v['wx_freeze_init'], 10000, 2))) . '万';
            }
            if (!empty($v['ph_cash_init']) && (int)$v['ph_cash_init'] >= 1000000) {
                $phCashInitYuan = bcdiv($v['ph_cash_init'], 100, 2);
                $phCashString = number_format(floatval(bcdiv($phCashInitYuan, 10000, 2))) . '万';
            }
            if (!empty($v['ph_freeze_init']) && (int)$v['ph_freeze_init'] >= 1000000) {
                $phFreezeInitYuan = bcdiv($v['ph_freeze_init'], 100, 2);
                $phFreezeString = number_format(floatval(bcdiv($phFreezeInitYuan, 10000, 2))) . '万';
            }
            if (!empty($v['corpus']) && bccomp($v['corpus'], 10000, 2) >= 0) {
                $corpusString = number_format(floatval(bcdiv($v['corpus'], 10000, 2))) . '万';
            }
            $arr = array();
            $arr[] = $v['id'];
            $arr[] = $v['user_id'];
            $arr[] = $v['real_name'];
            $arr[] = $v['mobile'];
            $arr[] = "'".$v['idno'];
            $arr[] = $wxCashString;
            $arr[] = $wxFreezeString;
            $arr[] = $phCashString;
            $arr[] = $phFreezeString;
            $arr[] = $corpusString;
            $arr[] = $v['reserved_session'];
            $arr[] = $v['time_range'];
            $arr[] = date('Y-m-d H:i:s', $v['reserved_at']);
            if ($v['status'] == 1) {
                $arr[] = '有效';
            } else {
                $arr[] = '取消';
            }
            $arr[] = "\t";

            foreach ($arr as $k => $v){
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", strip_tags($v));
            }

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, $arr);
        }
        exit;
    }
}
