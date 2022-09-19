<?php

// +----------------------------------------------------------------------
// | 管理后台-企业用户注册相关
// +----------------------------------------------------------------------
// | Author: wangqunqiang<wangqunqiang@ucfgroup.com>
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");
FP::import("libs.libs.user");

use core\service\user\BOBase;
use core\service\CouponService;
use core\service\UserTagService;
use core\service\BanklistService;
use core\service\UserBankcardService;
use core\service\PaymentService;
use core\service\DeliveryRegionService;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\EnterpriseRegisterModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;
use libs\utils\Logger;

require_once __DIR__.'/EnterpriseBase.class.php';

class EnterpriseRegisterAction extends EnterpriseBase {
    /**
     * 用户组列表
     */
    protected $groups = array();

    public function __construct() {
        parent::__construct();
    }

    /**
     * 用户管理-企业会员列表-编辑页面
     * @see CommonAction::edit()
     */
    public function edit() {
        parent::edit();
    }

    /**
     * 用户管理-企业会员列表-查看页面
     * @see CommonAction::info()
     */
    public function info() {
        parent::edit();
    }

    /**
     * 用户管理-企业会员列表-更新逻辑
     * @see CommonAction::update()
     */
    public function update() {
        parent::update(false);
    }

    /**
     * 创建/编辑银行开户信息
     */
    public function editBankAccount() {
        parent::editBankAccount(false);
    }

    /**
     * 用户管理-企业会员列表
     * @see CommonAction::index()
     */
    public function index() {
        $this->groups = \core\dao\UserGroupModel::instance()->getGroups();
        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        // 获取会员列表
        $name = $this->getActionName();
        $list = $this->_list(DI($name), $map);
        $verifyStatus = (new EnterpriseRegisterModel())->getVerfiyStatus();
        foreach($list as $k => $v) {
            $list[$k]['verify_status'] = $verifyStatus[$v['verify_status']];
            $list[$k]['verify_stat'] = $v['verify_status'];
        }

        $this->assign("group_list", $this->groups);

        $this->assign('main_title','企业会员注册列表');
        //审核状态
        $this->assign('verify_status', $verifyStatus);
        //审核状态\做判断是否显示用
        $this->assign('pass', EnterpriseRegisterModel::VERIFY_STATUS_PASS);
        $this->assign('has_info', EnterpriseRegisterModel::VERIFY_STATUS_HAS_INFO);
        $this->assign('list', $list);
        //企业用途表
        $this->assign('company_purpose_map', EnterpriseModel::getCompanyPurposeMap());
        //设置列表当前页号
        \es_session::set('enterpriseListCurrentPage', isset($_GET['p']) ? (int)$_GET['p'] : 1);
        $this->display();
    }


    /**
     * 显示操作界面
     */
    public function showOperate() {
        $this->assign('verify_status', (new EnterpriseRegisterModel())->getFirstPassStatus());
        $this->display ('operate');
    }


    /**
     * 初审通过操作
     */
    public function operation(){
        if (!is_numeric($_REQUEST['userId']) || $_REQUEST['userId'] <= 0) {
            self::jsonOutput(-1, '企业会员ID无效');
        }
        if (!is_numeric($_REQUEST['status']) || $_REQUEST['status'] <= 0) {
            self::jsonOutput(-1, '审核状态无效');
        }
        isset($_REQUEST['reason']) && $_REQUEST['reason'] = self::stripString($_REQUEST['reason']);
        $params = ['verify_status' => EnterpriseRegisterModel::VERIFY_STATUS_FIRST_PASS, 'verify_remark' => $_REQUEST['reason']];
        $update = (new EnterpriseRegisterModel())->updateVerifyStatus($_REQUEST['userId'], $params);
        if($update)
        {
            return self::jsonOtput(1, "初审通过");
        }
        return self::jsonOutput(-1, "操作错误");
    }


    public function export_csv($page = 1) {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        // 获取会员列表
        $name = $this->getActionName();
        $list = $this->_list(DI($name), $map);
        if ($list) {
            if ($page == 1) {
                $content = iconv("utf-8", "gbk", "用户ID,企业会员编号,企业会员名称,企业全称,支付账户状态,银行账户,用户类型,企业联络手机号,注册时间,推荐人姓名,推荐人电话,邀请人所属网站");
                $content = $content . "\n";
            }

            $couponService = new CouponService();
            foreach ($list as $k => $v) {
                // 企业联络人手机号
                $consignee_phone = !empty($v['consignee_phone']) ? $v['consignee_country_code'].'-'.$v['consignee_phone'] : '';
                // 推荐人手机号
                $inviter_phone = !empty($v['inviter_phone']) ? $v['inviter_country_code'].'-'.$v['inviter_phone'] : '';
                $userInfo = MI('User')->where(array('id'=>$v['user_id']))->find();
                $refer_user_id = $userInfo['refer_user_id'];
                if (empty($refer_user_id)) {
                    $coupon = $couponService->checkCoupon($userInfo['invite_code']);
                    if ($coupon !== FALSE && $coupon['refer_user_id']) {
                        $refer_user_id = $coupon['refer_user_id'];
                    }
                }

                $inviter_group = '';
                if ($refer_user_id > 0) {
                    $inviterInfo = MI('User')->where(array('id'=>$refer_user_id))->find();
                    if (!empty($inviterInfo)) {
                        $inviter_group = $inviterInfo['group_id'] > 0 ? $this->groups[$inviterInfo['group_id']]['name'] : '';
                    }
                }

                $user_value = [];
                $user_value['user_id'] = iconv('utf-8', 'gbk', '"' . $v['user_id'] . '"');
                $user_value['member_sn'] = iconv('utf-8', 'gbk', '"' . numTo32Enterprise($v['user_id']) . '"');
                $user_value['user_name'] = iconv('utf-8', 'gbk', '"' . $v['user_name'] . '"');
                $user_value['name'] = iconv('utf-8', 'gbk', '"' . $v['name'] . '"');
                $user_value['isbind_bankcard'] = iconv('utf-8', 'gbk', '"' . $v['isbind_bankcard'] . '"');
                $user_value['user_bankcard'] = iconv('utf-8', 'gbk', '"' . $v['user_bankcard'] . '"');
                $user_value['user_type'] = iconv('utf-8', 'gbk', '"' . '企业用户' . '"');
                $user_value['consignee_phone'] = iconv('utf-8', 'gbk', '"' . $consignee_phone . '"');
                $user_value['create_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['create_time']) . '"');
                $user_value['inviter_name'] = iconv('utf-8', 'gbk', '"' . $v['inviter_name'] . '"');
                $user_value['inviter_phone'] = iconv('utf-8', 'gbk', '"' . $inviter_phone . '"');
                $user_value['inviter_group'] = iconv('utf-8', 'gbk', '"' . $inviter_group . '"');
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
                    'sensitive' => 'exportenterpriseregister',
                    'analyze' => $map
                )
            );

            header("Content-Disposition: attachment; filename=" . $filename . ".csv");
            echo $content;
        } else {
            if ($page == 1) {
                $this->error(L("NO_RESULT"));
            }
        }
    }

    /**
     * 组织查询条件
     * @param array $request
     */
    private function _getSqlMap(&$request) {
        $map = $map_user = $map_enterprise = array();
        // 未绑卡的用户
        $map_user['payment_user_id'] = 0;
        // 未删除的帐户
        $map_user['is_delete'] = 0;

        if (trim($request['user_id']) != '') {
            $map_user['id'] = intval($request['user_id']);
        }

        // 企业会员编号
        $member_sn = addslashes(trim($request['member_sn']));
        if(!empty($member_sn)) {
            $map_user['id'] = array('eq', de32Tonum($member_sn));
        }

        // 企业会员标识
        $identifier = isset($_REQUEST['identifier']) ? trim($_REQUEST['identifier']) : '';
        if (!empty($identifier)) {
            $map_enterprise['identifier'] = array('eq', addslashes($identifier));
        }

        // 会员名称
        if(trim($request['user_name']) !='') {
            $name = addslashes(trim($request['user_name']));
            $map_user['user_name'] = array('like', '%' . $name . '%');
        }

        // 会有所属网站
        if (trim($request['inviter_group_id']) != '') {
            $map_user['group_id'] = intval($request['inviter_group_id']);
        }
        // 企业全称
        if (trim($request['name']) != '') {
            $map['name'] = addslashes(trim($request['name']));
        }

        // 推荐人姓名
        if(trim($request['inviter_name']) !='') {
            $map['inviter_name'] = addslashes(trim($request['inviter_name']));
        }

        // 推荐人电话
        if(trim($request['inviter_phone']) !='') {
            $map['inviter_phone'] = addslashes(trim($request['inviter_phone']));
        }

        // 企业联络手机号码
        if(trim($request['consignee_phone']) !='') {
            $map['consignee_phone'] = addslashes(trim($request['consignee_phone']));
        }

        // 注册时间
        if(trim($request['create_time']) !='') {
            $map['create_time'] = array(array('egt', strtotime($request['create_time'] . ' 00:00:00')), array('elt', strtotime($request['create_time'] . ' 23:59:59')));
        }

        // 审核状态
        if(trim($request['verify_status']) !='' && $request['verify_status'] > 0) {
            $map['verify_status'] = intval($request['verify_status']);
        }

        // 获取符合上述条件的用户数据
        $userids = $enterpriseIds = [];
        if ($map_user) {
            // 用户类型-企业用户
            $map_user['user_type'] = UserModel::USER_TYPE_ENTERPRISE;
            $userList = MI('User')->where($map_user)->findAll();
            if ($userList) {
                $ids = array();
                foreach ($userList as $value) {
                    $ids[] = $value['id'];
                }
                $userIds = $ids;
            }else{
                $userIds = array(0);
            }
        }

        if ($map_enterprise) {
            $userList = MI('Enterprise')->where($map_enterprise)->findAll();
            if ($userList) {
                $ids = array();
                foreach ($userList as $value) {
                    $ids[] = $value['user_id'];
                }
                $enterpriseIds = $ids;
            }else{
                $enterpriseIds = array(0);
            }
        }

        if (!empty($userIds) && !empty($enterpriseIds)) {
            $ids = array_intersect($enterpriseIds, $userIds);
            $ids && $map['user_id'] = array('in', join(',', $ids));
        } else if (empty($userIds) && !empty($enterpriseIds)) {
            $ids = $enterpriseIds;
            $ids && $map['user_id'] = array('in', join(',', $ids));
        } else if (!empty($userIds) && empty($enterpriseIds)) {
            $ids = $userIds;
            $ids && $map['user_id'] = array('in', join(',', $ids));
        }
        else {
            $map['user_id'] = 0;
        }

        return $map;
    }

    /**
     * 列表数据的后续处理
     * @see CommonAction::form_index_list()
     */
    protected function form_index_list(&$list) {
        if ($list) {
            $couponLevelService = new \core\service\CouponLevelService();
            $couponService = new CouponService();
            $userTagService = new UserTagService();
            foreach ($list as &$item) {
                $userInfo = MI('User')->where(array('id' => $item['user_id']))->find();
                if (empty($userInfo)) continue;
                $item['id'] = isset($userInfo['id']) ? $userInfo['id'] : 0;
                $item['user_name'] = isset($userInfo['user_name']) ? userNameFormat($userInfo['user_name']) : '';
                $item['real_name'] = isset($userInfo['real_name']) ? $userInfo['real_name'] : '';
                $item['money'] = isset($userInfo['money']) ? $userInfo['money'] : '';
                $item['lock_money'] = isset($userInfo['lock_money']) ? $userInfo['lock_money'] : '';
                $item['idno'] = isset($userInfo['idno']) ? idnoFormat($userInfo['idno']) : '';
                $item['email'] = isset($userInfo['email']) ? adminEmailFormat($userInfo['email']) : '';
                $item['is_effect'] = isset($userInfo['is_effect']) ? $userInfo['is_effect'] : 0;
                $item['invite_code'] = isset($userInfo['invite_code']) ? $userInfo['invite_code'] : '';
                $refer_user_id = empty($userInfo['invite_code']) ? 0 : CouponService::hexToUserId($userInfo['invite_code']);
                if ($refer_user_id > 0) {
                    $inviterInfo = MI('User')->where(array('id'=>$refer_user_id))->find();
                    if (empty($inviterInfo)) {
                        $item['inviter_group_id'] = 0;
                        $item['inviter_group'] = '';
                    } else {
                        $item['inviter_group_id'] = $inviterInfo['group_id'];
                        $item['inviter_group']= $item['inviter_group_id'] > 0 ? $this->groups[$item['inviter_group_id']]['name'] : '';
                    }
                } else {
                    $item['inviter_group_id'] = 0;
                    $item['inviter_group'] = '';
                }

                // 获取用户绑定的银行卡信息
                $bankcard = MI('UserBankcard')->where('user_id=' . $item['user_id'] . ' ORDER BY id DESC')->find();
                $item['user_bankcard'] = '未验证';
                $item['isbind_bankcard'] = '未开通';
                if ($bankcard && $bankcard['status'] == 1) {
                    $item['user_bankcard'] = formatBankcard($bankcard['bankcard']);
                    $item['isbind_bankcard'] = '已开通';
                }

                // 用户手机
                if(!empty($userInfo['mobile'])){
                    $item['mobile'] = adminMobileFormat($userInfo['mobile']);
                    if (!empty($userInfo['mobile_code']) && $userInfo['mobile_code'] != '86') {
                        $item['mobile'] = $userInfo['mobile_code'] . '-' . $userInfo['mobile'];
                    }
                }

                // 查询优惠码
                $userCoupon = $couponService->getUserCoupons($userInfo['id']);
                $item['coupon'] = '<a href="m.php?m=Enterprise&a=index&invite_code=' . $userCoupon['short_alias']. '">' . $user_coupon['short_alias'] . '</a>';

                // 根据会员ID获取会员等级
                $userLevel = $couponLevelService->getUserLevel($userInfo['id']);
                $item['level'] = isset($userLevel['level']) ? $userLevel['level'] : 0;
                $invite_uid = $userInfo['invite_code'] ? CouponService::hexToUserId($userInfo['invite_code']) : 0;
                $item['invite_code'] = "<a href='m.php?m=User&a=index&user_id={$invite_uid}'>" . $userInfo['invite_code'] . "</a>";
                $item['email'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['email']}</div>";
                $item['login_ip'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$userInfo['login_ip']}</div>";
                $item['user_tag'] = implode('|', array_map(function($val){return $val['tag_name'];}, $userTagService->getTags($userInfo['id'])));
            }
        }
    }
}
