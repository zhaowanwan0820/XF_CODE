<?php

// +----------------------------------------------------------------------
// | 管理后台-普通用户相关
// +----------------------------------------------------------------------
// | Author: guofeng
// +----------------------------------------------------------------------

use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\UserCarryService;
use core\service\CouponLevelService;
use core\service\CouponService;
use core\service\UserTagService;
use core\service\RemoteTagService;
use core\dao\UserModel;
use core\dao\UserGroupModel;

class UserBase extends CommonAction {
    /**
     * 用户组列表
     */
    protected $groups = array();

    public function __construct() {
        parent::__construct();
    }

    /**
     * 个人会员列表首页
     * @see CommonAction::index()
     */
    public function index($actionName='User') {
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
            $map_enterprise['user_type'] = UserModel::USER_TYPE_ENTERPRISE;
            $userEnterpriseInfo = MI('User')->where($map_enterprise)->find();
            if ($userEnterpriseInfo) {
                return header('Location:?m=Enterprise&a=index&user_id=' . $_GET['user_id'] . '&user_name=' . $_GET['user_name']);
            }
        }

        $this->groups = UserGroupModel::instance()->getGroups();
        $this->assign("group_list", $this->groups);
        //定义条件
        $where = 'is_delete = 0';
        // 用户类型-普通用户
        $where .= ' AND (user_type = ' . UserModel::USER_TYPE_NORMAL . ' OR user_type IS NULL)';

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

        // 存管系统余额
        if (method_exists($this, '_filter')) {
            $this->_filter($where);
        }
        empty($actionName) && $actionName = $this->getActionName();
        $model = DI($actionName);
        if (!empty($model)) {
            $this->_setPageEnable(false);
            $this->_list($model, $where);
        }
        $this->assign('limit_types', UserCarryService::$withdrawLimitTypeCn);
        //设置列表当前页号
        \es_session::set('currentPage', $this->assign('nowPage'));
        $this->display();
    }

    /**
     * 查询相关字段
     */
    protected function form_index_list(&$list) {
        $coupon_level_service = new CouponLevelService();
        $coupon_service = new CouponService();
        foreach ($list as &$item) {
            // 查询优惠券短码
            $user_level = $coupon_level_service->getUserLevel($item['id']);
            $user_coupon = $coupon_service->getUserCoupons($item['id']);
            $user_tag_service = new UserTagService();
            $item['coupon'] = '<a href="m.php?m=User&a=index&invite_code=' . $user_coupon['short_alias'] . '">' . $user_coupon['short_alias'] . '</a>';
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
            $item['level'] = $user_level['level'];
            $invite_uid = 0;
            if ($item['invite_code']) {
                $invite_uid = CouponService::hexToUserId($item['invite_code']);
            }
            $item['invite_code'] = "<a href='m.php?m=User&a=index&user_id={$invite_uid}'>" . $item['invite_code'] . "</a>";
            $item['email'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['email']}</div>";
            $item['login_ip'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['login_ip']}</div>";

            $item['user_tag'] = implode("|", array_map(function($val){return $val['tag_name'];}, $user_tag_service->getTags($item['id'])));
        }
    }

}