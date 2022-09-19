<?php
use libs\utils\PaymentApi;
use libs\common\WXException;
use core\service\CouponLevelService;
use core\service\CouponService;
use core\service\UserTagService;
use core\dao\UserModel;
use core\service\GoldService;
use core\service\GoldChargeService;

class GoldChargeAction extends CommonAction
{
    const TYPE_MONEY = 0;   //增加余额
    const TYPE_BUY_LOCK_GOLD = 1; // 购买活期 需要减余额并冻结

    // 审核状态
    const AUDIT_STATUS_PROCESS = 0; // 处理中
    const AUDIT_STATUS_SUCCESS = 1; // 成功
    const AUDIT_STATUS_FAILED = 2; // 失败

    // 审核描述
    private static $auditMsg = array(
        self::AUDIT_STATUS_PROCESS => '审核中',
        self::AUDIT_STATUS_SUCCESS => '审核通过',
        self::AUDIT_STATUS_FAILED => '审核未通过',
    );

    // 黄金充值/冻结的资金记录类型
    private static $chargeMoneyType = array(
        'charge' => ['message'=>'管理员编辑账户', 'note'=>'充值单号：%d'],
        'lock' => ['message'=>'管理员编辑账户', 'note'=>'冻结克重：%sg'],
    );

    public function index()
    {
        // 参数列表
        $params = [];
        $params['startTime'] = !empty($_REQUEST['start_time']) ? strtotime($_REQUEST['start_time']) : 0;
        $params['endTime'] = !empty($_REQUEST['end_time']) ? strtotime($_REQUEST['end_time']) : 0;
        $params['auditStatus'] = isset($_REQUEST['audit_status']) ? (int)$_REQUEST['audit_status'] : -1;
        $params['pageNum'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $params['pageSize'] = isset($_REQUEST['listRows']) ? (int)$_REQUEST['listRows'] : 20;
        $_REQUEST['listRows'] = isset($_REQUEST['listRows']) ? intval($_REQUEST['listRows']) : $params['pageSize'];
        $_REQUEST['audit_status'] = $params['auditStatus'];

        // 获取黄金-申请充值列表
        $goldChargeService = new GoldChargeService();
        $list = $goldChargeService->getChargeList($params);

        // 根据列表，处理分页等
        $this->_fetchList($list);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->assign('p', $p);
        // 审批状态
        $auditMap = [];
        foreach (self::$auditMsg as $key => $value) {
            $auditMap[] = ['key'=>$key, 'value'=>$value];
        }
        $this->assign('auditMap', $auditMap);
        //设置列表当前页号
        \es_session::set('goldChargeListCurrentPage', $p);
        $this->display('index');
    }

    /**
     * 用户管理-个人会员列表首页
     * @see CommonAction::index()
     */
    public function apply_list($actionName = 'User') {
        $user_num = trim($_GET['user_num']);
        if($user_num){
            $_REQUEST['user_id'] = de32Tonum($user_num);
        }

        //定义条件
        $where = 'is_delete = 0';
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
            $where .= " and real_name = '".trim($_REQUEST['real_name'])."'";
        }
        if(trim($_REQUEST['email'])!='')
        {
            $where .= " and email = '".trim($_REQUEST['email'])."'";
        }
        if(trim($_REQUEST['mobile'])!='')
        {
            $where .= " and mobile = '".trim($_REQUEST['mobile'])."'";
        }
        if(trim($_REQUEST['idno'])!='')
        {
            // 身份证号采用加密存储，统一使用大写的X后缀
            $idno = strtoupper(addslashes(trim($_REQUEST['idno'])));
            $where .= " and idno = '".$idno."'";
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
        //设置列表当前页号
        \es_session::set('currentPage', $this->assign('nowPage'));
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->assign('p', $p);
        //设置列表当前页号
        \es_session::set('goldChargeListCurrentPage', $p);
        $this->display();
    }

    /**
     * 黄金-申请充值列表-新增页面
     */
    public function add() {
        $userId = !empty($_GET['uid']) ? (int)$_GET['uid'] : 0;
        if (empty($userId)) {
            self::jsAlert('参数错误或不合法', '', 'history.go(-1);');
        }

        // 获取用户信息
        $userService = new \core\service\UserService();
        $userInfo = $userService->getUserByUserId($userId);
        $this->assign('uid', $userId);
        $this->assign('orderId', $orderId);
        $this->assign('userName', (!empty($userInfo['user_name']) ? $userInfo['user_name'] : '暂无'));

        // 获取该用户的黄金克重
        $goldService = new GoldService();
        $goldInfo = $goldService->getGoldInfoByUserId($userId);
        $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
        $gold = $this->_goldOutput($gold);
        $this->assign('gold', $gold);
        // 当前页号
        $currentPage = max(1, (int)\es_session::get('goldChargeListCurrentPage'));
        $this->assign('currentPage', $currentPage);
        $this->assign('jumpUrl', u(MODULE_NAME . '/apply_list?p='.$currentPage));
        $this->display();
    }

    /**
     * 黄金-申请充值列表-编辑页面
     */
    public function edit() {
        $userId = !empty($_GET['uid']) ? (int)$_GET['uid'] : 0;
        $orderId = !empty($_GET['orderId']) ? (int)$_GET['orderId'] : 0;
        if (empty($userId) || empty($orderId)) {
            self::jsAlert('参数错误或不合法', '', 'history.go(-1);');
        }

        // 获取用户信息
        $userService = new \core\service\UserService();
        $userInfo = $userService->getUserByUserId($userId);
        if (empty($userInfo)) {
            self::jsAlert('用户信息不存在', '', 'history.go(-1);');
        }
        $this->assign('uid', $userId);
        $this->assign('orderId', $orderId);
        $this->assign('userName', (!empty($userInfo['user_name']) ? $userInfo['user_name'] : '暂无'));

        $goldChargeService = new GoldChargeService();
        // 获取用户的充值申请记录
        $goldCharge = $goldChargeService->getGoldChargeByOrderId($orderId, $userId);
        $this->assign('goldCharge', $goldCharge);

        // 获取该用户的黄金克重
        $goldService = new GoldService();
        $goldInfo = $goldService->getGoldInfoByUserId($userId);
        $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
        $gold = $this->_goldOutput($gold);
        $this->assign('gold', $gold);
        // 当前页号
        $currentPage = max(1, (int)\es_session::get('goldChargeListCurrentPage'));
        $this->assign('currentPage', $currentPage);
        $this->assign('jumpUrl', u(MODULE_NAME . '/index?p='.$currentPage));
        $this->display();
    }

    /**
     * 更新黄金申请记录
     * @see CommonAction::update()
     */
    public function update() {
        $params = [];
        if (!isset($_POST['isNew']) || empty($_POST['isNew'])) {
            $params['orderId'] = !empty($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
            if (empty($params['orderId'])) {
                self::jsonOutput(-1, '参数错误或不合法');
            }
        }

        $params['userId'] = !empty($_POST['uid']) ? (int)$_POST['uid'] : 0;
        $params['gold'] = !empty($_POST['gold']) && is_numeric($_POST['gold']) ? addslashes($_POST['gold']) : 0;
        if (empty($params['userId']) || empty($params['gold']) || !is_numeric($params['gold'])) {
            self::jsonOutput(-1, '参数错误或不合法');
        }
        // 格式化黄金字段
        $params['gold'] = $this->_goldFormat($params['gold']);

        // 获取该用户的黄金克重
        $goldService = new GoldService();
        $goldInfo = $goldService->getGoldInfoByUserId($params['userId']);
        $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
        $gold = $this->_goldFormat($gold);
        if (bccomp($params['gold'], '0.000', 3) < 0 && bccomp(bcadd($params['gold'], $gold, 3), '0.000', 3) < 0) {
            self::jsonOutput(-1, '用户黄金账户可用余额小于扣款克重');
        }

        // 操作人姓名
        $adminInfo = self::getAdminInfo();
        $params['operateName'] = $adminInfo['adminName'];
        // 流水单号
        $params['waterLine'] = !empty($_POST['waterLine']) ? addslashes($_POST['waterLine']) : '';
        // 备注
        $params['remark'] = !empty($_POST['remark']) ? addslashes($_POST['remark']) : '';

        // 编辑申请充值列表
        $goldChargeService = new GoldChargeService();
        $goldRet = $goldChargeService->updateGoldChargeByOrderId($params);
        $msg = $goldRet ? L('UPDATE_SUCCESS') : L('UPDATE_FAILED');
        self::jsonOutput(1, $msg);
    }

    /**
     * 查询黄金账户余额
     */
    public function balance() {
        $userId = !empty($_GET['uid']) ? (int)$_GET['uid'] : 0;
        if (empty($userId)) {
            echo "<script>alert('参数错误或不合法'); $.weeboxs.close(); </script>";
            exit;
        }
        // 获取用户信息
        $userService = new \core\service\UserService();
        $userInfo = $userService->getUserByUserId($userId);
        if (empty($userInfo)) {
            echo "<script>alert('用户信息不存在'); $.weeboxs.close(); </script>";
            exit;
        }
        $this->assign('userInfo', $userInfo);

        // 获取该用户的黄金克重
        $goldService = new GoldService();
        $goldInfo = $goldService->getGoldInfoByUserId($userId);
        $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
        $gold = $this->_goldOutput($gold);
        $this->assign('gold', $gold);
        $this->display();
    }

    /**
     * 黄金-充值申请页-冻结/解冻页面
     */
    public function lock_gold() {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $params = [];
            $params['userId'] = !empty($_POST['uid']) ? (int)$_POST['uid'] : 0;
            if (empty($params['userId'])) {
                self::jsonOutput(-1, '参数错误或不合法');
            }
            $params['gold'] = !empty($_POST['gold']) && is_numeric($_POST['gold']) ? addslashes($_POST['gold']) : 0;
            if (empty($params['userId']) || empty($params['gold']) || !is_numeric($params['gold'])) {
                self::jsonOutput(-1, '参数错误或不合法');
            }
            // 获取用户信息
            $userService = new \core\service\UserService();
            $userInfo = $userService->getUserByUserId($params['userId']);
            if (empty($userInfo)) {
                self::jsonOutput(-1, '用户信息不存在');
            }

            // 格式化黄金字段
            $params['gold'] = $this->_goldFormat($params['gold']);
            // 获取该用户的黄金克重
            $goldService = new GoldService();
            $goldInfo = $goldService->getGoldInfoByUserId($params['userId']);
            // 黄金可用余额
            $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
            $gold = $this->_goldFormat($gold);
            // 黄金冻结金额
            $lockGold = is_numeric($goldInfo['lockGold']) && !empty($goldInfo['lockGold']) ? $goldInfo['lockGold'] : 0;
            $lockGold = $this->_goldFormat($lockGold);
            if (bccomp($params['gold'], '0.000', 3) > 0 && bccomp($params['gold'], $gold, 3) > 0) {
                $error = '用户黄金账户可用余额小于冻结金额';
                self::jsonOutput(-1, $error);
            }
            if (bccomp($params['gold'], '0.000', 3) < 0 && bccomp(bcadd($params['gold'], $lockGold, 3), '0.000', 3) < 0) {
                $error = '用户黄金账户冻结金额小于解冻金额';
                self::jsonOutput(-1, $error);
            }

            // 资金记录类型
            $params['message'] = self::$chargeMoneyType['lock']['message'];
            // 资金记录描述
            $params['note'] = sprintf(self::$chargeMoneyType['lock']['note'], $params['gold']);
            // 资金类型
            $params['moneyType'] = self::TYPE_BUY_LOCK_GOLD;
            // 管理员用户ID
            $adminInfo = self::getAdminInfo();
            $params['adminId'] = $adminInfo['adminId'];

            // 编辑申请充值列表
            $goldChargeService = new GoldChargeService();
            $goldRet = $goldChargeService->checkUserAndChangeMoney($params);
            if ($goldRet) {
                $msg = L('UPDATE_SUCCESS');
                save_log('黄金账户充值-冻结解冻黄金，会员id['.$params['userId'].']操作成功', 1, array('gold'=>$gold, 'lock_gold'=>$lockGold), $params);
            }else{
                $msg = L('UPDATE_FAILED');
            }
            self::jsonOutput(1, $msg);
        }

        $userId = !empty($_GET['uid']) ? (int)$_GET['uid'] : 0;
        if (empty($userId)) {
            self::jsAlert('参数错误或不合法', '', 'history.go(-1);');
        }

        // 获取用户信息
        $userService = new \core\service\UserService();
        $userInfo = $userService->getUserByUserId($userId);
        if (empty($userInfo)) {
            self::jsAlert('用户信息不存在', '', 'history.go(-1);');
        }
        $this->assign('uid', $userId);
        $this->assign('userName', (!empty($userInfo['user_name']) ? $userInfo['user_name'] : '暂无'));

        // 获取该用户的黄金克重
        $goldService = new GoldService();
        $goldInfo = $goldService->getGoldInfoByUserId($userId);
        $gold = is_numeric($goldInfo['gold']) && !empty($goldInfo['gold']) ? $goldInfo['gold'] : 0;
        $gold = $this->_goldOutput($gold);
        $this->assign('gold', $gold);
        // 当前页号
        $currentPage = max(1, (int)\es_session::get('goldChargeListCurrentPage'));
        $this->assign('currentPage', $currentPage);
        $this->assign('jumpUrl', u(MODULE_NAME . '/apply_list?p='.$currentPage));
        $this->display();
    }

    /**
     * 申请充值批准/拒绝-批量
     * @throws WXException
     */
    public function doAudit() {
        // 获取ID数组
        $ids = $this->get_id_list();
        $params = [];
        $params['ids'] = addslashes($_POST['id']);
        $params['auditStatus'] = intval($_POST['audit_status']);
        $isBatch = intval($_POST['is_batch']);
        // 审核人姓名
        $adminInfo = self::getAdminInfo();
        $params['adminId'] = $adminInfo['adminId'];
        $params['auditName'] = $adminInfo['adminName'];

        // 批量审批申请充值记录
        $goldChargeService = new GoldChargeService();
        $return = $goldChargeService->chargeAudit($params);
        save_log('黄金账户充值后台审核-批准/拒绝', 1, [], ['params'=>$params, 'result'=>$return]);

        if (false === $return['ret']) {
            $auditMsg = $return['errMsg'];
        }else{
            $auditSuccessCnt = count($return['data']['success']);
            $auditFailCnt = count($return['data']['failMsg']);
            if ($isBatch == 1) {
                $auditMsg = sprintf('一共执行%d笔，%d笔成功，%d笔失败或已审核。', count($ids), $auditSuccessCnt, $auditFailCnt);
            }else{
                $auditMsg = $auditSuccessCnt > 0 ? $return['data']['success'][0] : $return['data']['failMsg'][0];
            }
        }
        ajax_return(['status'=>1, 'info'=>$auditMsg, 'data'=>join(',', $return['data']['failMsg'])]);
    }

    /**
     * 获取登录用户信息
     */
    private static function getAdminInfo() {
        $adminSession = es_session::get(md5(conf('AUTH_KEY')));
        $adminInfo = array();
        $adminInfo['adminName'] = $adminSession['adm_name'];
        $adminInfo['adminId'] = intval($adminSession['adm_id']);
        return $adminInfo;
    }

    /**
     * 根据列表，处理分页等
     * @param array $list
     */
    protected function _fetchList($list) {
        if (isset($_REQUEST['_page'])) {
            $this->pageEnable = $_REQUEST['_page'] ? true : false;
        }

        //取得满足条件的记录数
        $count = !empty($list['totalNum']) ? $list['totalNum'] : 0;
        if ($count > 0) {
            //创建分页对象
            if (!empty ($_REQUEST['listRows'])) {
                $listRows = $_REQUEST['listRows'];
            } else {
                if ($_REQUEST['a'] == 'export_csv') {
                    $listRows = $count;
                } else {
                    $listRows = '';
                }
            }

            //接受 sost参数 0 表示倒序 非0都 表示正序
            if (isset ($_REQUEST ['_sort'])) {
                $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
            } else {
                $sort = $asc ? 'asc' : 'desc';
            }

            $p = new Page ($count, $listRows);
            $voList = $list['data'];
            //分页跳转的时候保证查询条件
            foreach ($_REQUEST as $key => $val) {
                if (!is_array($val)) {
                    if($key<>'_string'){
                        $p->parameter .= "$key=" . urlencode($val) . "&";
                    }
                }
            }

            //分页显示
            $page = $p->show($this->pageEnable, count($voList));
            //列表排序显示
            $sortImg = $sort; //排序图标
            $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
            $sort = $sort == 'desc' ? 1 : 0; //排序方式
            //模板赋值显示
            $this->assign('list', $voList);
            $this->assign('sort', $sort);
            $this->assign('order', $order);
            $this->assign('sortImg', $sortImg);
            $this->assign('sortType', $sortAlt);
            $this->assign("page", $page);
            $this->assign("nowPage", $p->nowPage);
        }
    }

    /**
     * 格式化输出
     * @param float $gold
     */
    protected function _goldOutput($gold) {
        return sprintf('%sg', number_format($this->_goldFormat($gold), 3));
    }

    /**
     * 格式化黄金字段
     * @param float $gold
     */
    protected function _goldFormat($gold) {
        return floorfix($gold, 3, 6);
    }

    /**
     * 弹出提示框
     * @param int $message 消息内容
     * @param string $url 要重定向的 url
     */
    protected static function jsAlert($message, $url = '', $initHtml = '') {
        $out = '<script language="JavaScript" type="text/javascript">';
        $out .= "alert('{$message}');";
        $url && $out .= "document.location='{$url}';";
        $initHtml && $out .= "{$initHtml}";
        $out .= '</script>';
        echo $out;
        exit;
    }

    /**
     * Json输出
     * @param int $code
     * @param string $msg
     */
    public static function jsonOutput($code, $msg = '', $data = array()) {
        echo (is_array($code) && !empty($code)) ? json_encode($code) : json_encode(self::_genErrorMsg($code, $msg, $data));
        exit;
    }

    /**
     * 组建错误消息
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected static function _genErrorMsg($code, $msg, $data = array()) {
        return array(
            'request' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REDIRECT_URL'] :
            (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
            'code' => $code,
            'msg' => $msg
        ) + (is_array($data) && !empty($data) ? $data : array());
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
            $user_coupon = $coupon_service->getUserCoupon($item['id']);
            $user_tag_service = new UserTagService();
            $item['coupon'] = "<br/>" . '<a href="m.php?m=User&a=index&invite_code=' . $user_coupon['short_alias'] . '">' . $user_coupon['short_alias'] . '</a>';
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

            // 获取企业用户数据
            if ((int)$item['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                // 会员编号
                $item['user_num'] = numTo32Enterprise($item['id']);
                // 企业用户-联系人信息
                $enterpriseContactInfo = MI('EnterpriseContact')->where(array('user_id'=>$item['id']))->find();
                $item['mobile'] = !empty($enterpriseContactInfo['major_mobile']) ? adminMobileFormat($enterpriseContactInfo['major_mobile']) : '';
            }else{
                // 会员编号
                $item['user_num'] = numTo32($item['id']);
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
    /**
     * 导出 index csv
     */
    public function export_csv()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $memory_start = memory_get_usage();
        // 参数列表
        $i = 0;
        $total = 0;
        $pageSize = 1000;
        $hasTotalCount = 1;
        $pageNo = 1;
        $params = [];
        $params['startTime'] = !empty($_REQUEST['start_time']) ? strtotime($_REQUEST['start_time']) : 0;
        $params['endTime'] = !empty($_REQUEST['end_time']) ? strtotime($_REQUEST['end_time']) : 0;
        $params['auditStatus'] = isset($_REQUEST['audit_status']) ? (int)$_REQUEST['audit_status'] : -1;
        $params['startTime'] = !empty($_REQUEST['start_time']) ? strtotime($_REQUEST['start_time']) : 0;
        $goldChargeService = new GoldChargeService();
        $content = iconv("utf-8","gbk","编号,操作人,克重,会员名称,姓名,手机号,状态,审核记录,申请时间,流水单,备注");
        $content = $content . "\n";
        do {
            try {
                $params['pageNum'] = $pageNo;
                $params['pageSize'] = $pageSize;
                $list = $goldChargeService->getChargeList($params);
                if ($total == 0) {
                    // 获取总的数据条数
                    $total = $list['totalSize'];
                    $hasTotalCount = 0;
                }
                $deal_list = $list['data'];
                $order_value = array(
                    'orderId'=>'""',
                    'operateName'=>'""',
                    'gold'=>'""',
                    'userName'=>'""',
                    'realName'=>'""',
                    'mobile'=>'""',
                    'auditMsg' => '""',
                    'auditRecord' => '""',
                    'createTime'=>'""',
                    'waterLine' => '""',
                    'remark' => '""',
                );
                foreach($deal_list as $k=>$v)
                {
                    $order_value['orderId'] = '"' . iconv('utf-8','gbk',"'".$v['orderId']) . '"';
                    $order_value['operateName'] = '"' . iconv('utf-8','gbk',$v['operateName']) . '"';
                    $order_value['gold'] = '"' . iconv('utf-8','gbk',$v['gold']) . '"';
                    $order_value['userName'] = '"' . iconv('utf-8','gbk',$v['userName']). '"';
                    $order_value['realName'] = '"' . iconv('utf-8','gbk',$v['realName']) . '"';
                    $order_value['mobile'] = '"' . iconv('utf-8','gbk',$v['mobile']) . '"';
                    $order_value['auditMsg'] = '"' . iconv('utf-8','gbk',$v['auditMsg']) . '"';
                    $order_value['auditRecord'] = '"' . iconv('utf-8','gbk',$v['auditRecord']) . '"';
                    $order_value['createTime'] = '"' . iconv('utf-8','gbk',$v['createTime']) . '"';
                    $order_value['waterLine'] = '"' . iconv('utf-8','gbk',$v['waterLine']) . '"';
                    $order_value['remark'] = '"' . iconv('utf-8','gbk',$v['remark']) . '"';
                    if(is_array($ids) && count($ids) > 0){
                        if(array_search($v['id'],$ids) !== false){
                            $content .= implode(",", $order_value) . "\n";
                        }
                    }else{
                        $content .= implode(",", $order_value) . "\n";
                    }
                }
            } catch (\Exception $ex) {
                Logger::error('exportList: '.$ex->getMessage());
            }
            // 处理下一页数据
            $pageNo++;
            $i += $pageSize;
        } while ($i <= $total);
        $datatime = date("YmdHis",get_gmtime());
        header("Content-Disposition: attachment; filename={$datatime}_gold_charge_list.csv");
        echo $content;
        return;

    }
}