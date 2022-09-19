<?php

use libs\utils\Page;
use core\enum\PaymentEnum;
use core\enum\SupervisionEnum;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionFinanceService;
use core\dao\supervision\SupervisionChargeModel;

ini_set('memory_limit', '2048M');

class SupervisionChargeAction extends CommonAction
{
    // 用户ID列表
    private $userIds = [];

    public function index()
    {
        $condition = array();
        $status = isset($_GET['pay_status']) ? intval($_GET['pay_status']) : -1;
        if(isset($_GET['pay_status']) && $_GET['pay_status'] != -1) {
            $condition['pay_status'] =  intval($_GET['pay_status']);
        }
        $_REQUEST['pay_status'] = $status;
        if(trim($_REQUEST['out_order_id'])!=''){
            $condition['out_order_id'] = $_REQUEST['out_order_id'];
        }

        // 会员名称，精确匹配，避免慢查询
        if(trim($_REQUEST['user_name']) != '')
        {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition['user_id'] = array('in', $accountIds);
            $this->assign('user_name', $_REQUEST['user_name'] );
            unset($_REQUEST['user_name']);
        }

        $user_num = trim($_REQUEST['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition['user_id'] = array('in', $accountIds);
        }

        // 付款时间
        $pay_start = $pay_end = 0;
        if (!empty($_REQUEST['pay_time_start'])) {
            $pay_start = strtotime($_REQUEST['pay_time_start']);
            $condition['update_time'] = array('egt', $pay_start);
        }

        if (!empty($_REQUEST['pay_time_end'])) {
            $pay_end = strtotime($_REQUEST['pay_time_end']);
            $condition['update_time'] = array('between', sprintf('%s,%s', $pay_start, $pay_end));
        }

        $this->assign('main_title', '充值订单列表');
        $this->assign("default_map",$condition);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        // 不再计算列表页总条数
        $this->_setPageEnable(false);
        $this->_list(MI('SupervisionCharge'), $condition);

        // 根据用户ID列表获取用户昵称、真实姓名
        $userNameList = [];
        if (!empty($this->userIds)) {
            // 批量获取用户信息
            $userNameList = UserService::getUserInfoByIds($this->userIds, false);
        }
        $this->assign('userNameList', $userNameList);

        $this->assign('pay_status', $status);
        $statusList = [
            ['value' => -1, 'name' => '全部状态'],
            ['value' => 0, 'name' => '处理中'],
            ['value' => 1, 'name' => '成功'],
            ['value' => 2, 'name' => '失败'],
        ];
        $this->assign('payStatusList', $statusList);
        $this->assign('p', $p);
        $this->assign('charge_map', self::getChargeMap());
        $this->display('index');
    }

    protected function form_index_list(&$list)
    {
        $existUserIds = [];
        foreach ($list as $key => $item) {
            // 账户ID需要转出用户ID
            $accountId = $item['user_id'];
            if (!isset($existUserIds[$accountId])) {
                $existUserIds[$accountId] = 1;
                // 把账户ID转换为用户ID
                $userId = AccountService::getUserId($accountId);
                if (!empty($userId)) {
                    $this->userIds[] = $userId;
                }
            }
        }
    }

    public function export_payment() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $where_arr = array();

//         if (trim($_REQUEST['order_sn']) != '') {
//             $order_id = MI("DealOrder")->where("order_sn='" . trim($_REQUEST['order_sn']) . "'")->getField("id");
//             $where_arr[] = "pn.order_id = '".$order_id."'";
//         }

        if (trim($_REQUEST['out_order_id']) != '') {
            $where_arr[] = "sc.out_order_id = '".trim($_REQUEST['out_order_id'])."'";
        }

        $pay_time_start = strtotime($_REQUEST['pay_time_start']);
        $pay_time_end = strtotime($_REQUEST['pay_time_end']);
        if($pay_time_start){
            $where_arr[] = "sc.update_time >= ".$pay_time_start;
        }
        if($pay_time_end){
            $where_arr[] = "sc.update_time <= ".$pay_time_end;
        }
        // 状态
        if (intval($_REQUEST['pay_status']) != -1) {
            $where_arr[] = "sc.pay_status = ".intval($_REQUEST['pay_status']);
        }

        // 会员名称，精确匹配，避免慢查询
        if(trim($_REQUEST['user_name']) != '')
        {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $where_arr[] = " sc.user_id IN  (".implode(',', $accountIds).')';
        }

        $user_num = trim($_REQUEST['user_num']);
        if($user_num){
            $uid = de32Tonum($user_num);
            $where_arr[] = " sc.user_id = '{$uid}' ";
        }

        //$where_str = $where_arr ? ' AND '.implode(' AND ', $where_arr) : '';

        //$sql = 'SELECT pn.*,o.order_sn AS order_sn, u.user_name, p.name, bc.name AS bankname FROM firstp2p_payment_notice pn, firstp2p_user u, firstp2p_payment p, firstp2p_deal_order o, firstp2p_bank_charge bc WHERE pn.user_id=u.id AND pn.payment_id=p.id AND pn.order_id=o.id AND bc.short_name=o.bank_id '.$where_str.' ORDER BY pn.id DESC';

        $where_str = $where_arr ? ' where '.implode(' AND ', $where_arr) : '';
        //$sql = 'SELECT pn.*,o.order_sn AS order_sn,o.payment_id,o.bank_id,u.user_name, p.name FROM firstp2p_payment_notice pn left join firstp2p_user u on pn.user_id=u.id left join firstp2p_payment p on pn.payment_id=p.id left join  firstp2p_deal_order o on pn.order_id=o.id '.$where_str.' ORDER BY pn.id DESC';
        $tableName = SupervisionChargeModel::instance()->tableName();
        $sql = 'SELECT sc.* FROM `' . $tableName . '` sc '.$where_str.' ORDER BY sc.id DESC';

        $res = $GLOBALS['db']->get_slave()->query($sql);
        if ($res === false) {
            $this->error('收款单列表为空');
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportPaymentNotice',
                'analyze' => $sql
            )
        );

        $datatime = date("YmdHis", time());
        $file_name = 'payment_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        //$content = iconv("utf-8", "gbk//IGNORE", "编号,付款单号,创建时间,支付时间,是否已支付,订单号,会员名称,收款方式,银行卡,付款单金额,支付平台交易号,付款单备注") . "\n";
        $head = array("编号", "付款单号", "创建时间", "支付时间", "支付状态", "会员名称", "付款单金额");
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);

        while($val = $GLOBALS['db']->fetchRow($res)) {
            $passportId = AccountService::getUserId($val['user_id']);
            $userInfo = UserService::getUserById($passportId);
            $create_time = date('Y-m-d H:i:s', $val['create_time']);

            $is_paid = '';
            $pay_time = '-';
            if($val['pay_status'] == SupervisionEnum::PAY_STATUS_NORMAL){
                $is_paid = '未支付';
            }else if ($val['pay_status'] == SupervisionEnum::PAY_STATUS_SUCCESS){
                $pay_time = date('Y-m-d H:i:s',$val['update_time']);
                $is_paid =  '支付成功';
            } else if ($val['pay_status'] == SupervisionEnum::PAY_STATUS_FAILURE) {
                $is_paid = '支付失败';
                $pay_time = date('Y-m-d H:i:s',$val['update_time']);
            }
            $money = sprintf("%.2f",bcdiv($val['amount'], 100, 2));

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $arr = array(
                    $val['id'],
                    "" . $val['out_order_id'] . "\t",
                    $create_time,
                    $pay_time,
                    $is_paid,
                    $userInfo['user_name'],
                    $money,
            );
            foreach ($arr as &$item) {
                $item = iconv("utf-8", "gbk//IGNORE", $item);
            }
            fputcsv($fp, $arr);
        }
        EXIT;
    }

    /**
     * 代扣充值列表
     */
    public function autorecharge_index()
    {
        $condition = array('platform'=>PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE);
        // 充值订单号
        if (trim($_REQUEST['out_order_id']) != '') {
            $condition['out_order_id'] = addslashes($_REQUEST['out_order_id']);
        }
        // 会员ID
        if (!empty($_REQUEST['user_id'])) {
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId((int)$_REQUEST['user_id']);
            empty($accountIds) && $accountIds = [0];
            $condition['user_id'] = array('in', $accountIds);
            $this->assign('user_id', (int)$_REQUEST['user_id'] );
        }
        // 会员名称，精确匹配，避免慢查询
        if (trim($_REQUEST['user_name']) != '') {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition['user_id'] = array('in', $accountIds);
            $this->assign('user_name', $_REQUEST['user_name'] );
            unset($_REQUEST['user_name']);
        }
        // 会员编号
        $user_num = trim($_REQUEST['user_num']);
        if ($user_num) {
            $userId = de32Tonum($user_num);
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition['user_id'] = array('in', $accountIds);
        }
        // 付款时间
        $pay_start = $pay_end = 0;
        if (!empty($_REQUEST['pay_time_start'])) {
            $pay_start = strtotime($_REQUEST['pay_time_start']);
            $condition['update_time'] = array('egt', $pay_start);
        }
        if (!empty($_REQUEST['pay_time_end'])) {
            $pay_end = strtotime($_REQUEST['pay_time_end']);
            $condition['update_time'] = array('between', sprintf('%s,%s', $pay_start, $pay_end));
        }

        $this->assign('main_title', '代扣充值列表');
        $this->assign('default_map', $condition);
        // 不再计算列表页总条数
        $this->_setPageEnable(false);
        $this->_list(MI('SupervisionCharge'), $condition);

        // 根据用户ID列表获取用户昵称、真实姓名
        $userNameList = [];
        if (!empty($this->userIds)) {
            // 批量获取用户信息
            $userNameList = UserService::getUserInfoByIds($this->userIds, false);
        }
        $this->assign('userNameList', $userNameList);

        $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $this->assign('p', $p);
        $this->display('autorecharge_index');
    }

    /**
     * 大额充值-查询订单列表
     */
    public function offline_query_orders()
    {
        $params = $list = [];
        // 页码
        $params['page'] = $_REQUEST['page'] = isset($_REQUEST['page']) ? max((int)$_REQUEST['page'], 1) : 1;

        if (!empty($_REQUEST['userId'])) {
            // 校验日期范围
            if (strtotime($_REQUEST['startDate']) > strtotime($_REQUEST['endDate'])) {
                $this->error('日期范围不合法');
            }

            // 当前日期
            $maxDateStamp = strtotime(date('Y-m-d'));
            // 校验起始日期
            if (strtotime($_REQUEST['startDate']) > $maxDateStamp) {
                $this->error('起始日期不能超过当前日期');
            }

            // 校验终止日期
            if (strtotime($_REQUEST['endDate']) > $maxDateStamp) {
                $this->error('终止日期不能超过当前日期');
            }

            // 30天前的日期
            $minDateStamp = strtotime(date('Y-m-d', strtotime('-30 days')));
            if (strtotime($_REQUEST['startDate']) < $minDateStamp || strtotime($_REQUEST['endDate']) > $maxDateStamp) {
                $this->error('只能查询近一个月的数据');
            }

            // 用户ID
            $params['userId'] = (int)$_REQUEST['userId'];

            // 起始日期
            if(trim($_REQUEST['startDate']) != '') {
                $params['startDate'] = trim($_REQUEST['startDate']);
            }

            // 终止日期
            if(trim($_REQUEST['endDate']) != '') {
                $params['endDate'] = trim($_REQUEST['endDate']);
            }

            // 订单状态(全部:空|处理中:I|成功:S|失败（订单关闭）:F)
            if(trim($_REQUEST['status']) != '') {
                $params['status'] = trim($_REQUEST['status']);
            }

            // 银行卡号
            if(trim($_REQUEST['bankCardNo']) != '') {
                $params['bankCardNo'] = trim($_REQUEST['bankCardNo']);
            }

            // 大额充值-转账充值订单信息查询接口
            $obj = new SupervisionFinanceService();
            $result = $obj->offlineRechargeSearch($params);
            if($result['status'] == SupervisionEnum::RESPONSE_SUCCESS && !empty($result['data']['rechargeInfoList'])) {
                $list = $result['data']['rechargeInfoList'];
                foreach ($list as $key => $item) {
                    $list[$key]['userId'] = $result['data']['userId'];
                    $list[$key]['userUrl'] = "<a href='".u("User/index", array('id'=>$list[$key]['userId']))."' target='_blank'>".$list[$key]['userId']."</a>";
                    $list[$key]['statusName'] = SupervisionEnum::$offlineOrderStatusMap[$item['status']];
                    $list[$key]['amountYuan'] = bcdiv($item['amount'], 100, 2) . '元';
                    $list[$key]['tradeTypeName'] = $item['tradeType'] === 'I' ? '入金' : '出金';
                }
            }
            $this->getPage($_REQUEST, $list);
        }else{
            $_REQUEST['startDate'] = date('Y-m-d');
            $_REQUEST['endDate'] = date('Y-m-d');
        }

        $offlineOrderStatusList = [];
        foreach (SupervisionEnum::$offlineOrderStatusMap as $key => $name) {
            $offlineOrderStatusList[] = ['key'=>$key, 'name'=>$name];
        }
        $this->assign('main_title', '订单信息查询');
        $this->assign('default_map', $params);
        $this->assign('offlineOrderStatusList', $offlineOrderStatusList);
        $this->assign('p', $params['page']);
        $this->assign('np', $params['page']+1);
        $this->assign('list', $list);
        $this->display('offline_query_orders');
    }

    /**
     * 大额充值-资金流水查询列表
     */
    public function offline_query_accountrecords()
    {
        $params = $list = [];
        // 页码
        $params['page'] = $_REQUEST['page'] = isset($_REQUEST['page']) ? max((int)$_REQUEST['page'], 1) : 1;

        if (!empty($_REQUEST['bankCardNo'])) {
            // 校验日期范围
            if (strtotime($_REQUEST['startDate']) > strtotime($_REQUEST['endDate'])) {
                $this->error('日期范围不合法');
            }

            // 当前日期
            $maxDateStamp = strtotime(date('Y-m-d'));
            // 校验起始日期
            if (strtotime($_REQUEST['startDate']) > $maxDateStamp) {
                $this->error('起始日期不能超过当前日期');
            }

            // 校验终止日期
            if (strtotime($_REQUEST['endDate']) > $maxDateStamp) {
                $this->error('终止日期不能超过当前日期');
            }

            // 30天前的日期
            $minDateStamp = strtotime(date('Y-m-d', strtotime('-30 days')));
            if (strtotime($_REQUEST['startDate']) < $minDateStamp || strtotime($_REQUEST['endDate']) > $maxDateStamp) {
                $this->error('只能查询近一个月的数据');
            }

            // 对手方账户
            $params['bankCardNo'] = trim($_REQUEST['bankCardNo']);

            // 起始日期
            if(trim($_REQUEST['startDate']) != '') {
                $params['startDate'] = trim($_REQUEST['startDate']);
            }

            // 终止日期
            if(trim($_REQUEST['endDate']) != '') {
                $params['endDate'] = trim($_REQUEST['endDate']);
            }

            // 订单状态(全部:空|I-未匹配|S-匹配成功|RS-退款）:F)
            if(trim($_REQUEST['status']) != '') {
                $params['status'] = trim($_REQUEST['status']);
            }

            // 大额充值-转账充值订单信息查询接口
            $obj = new SupervisionFinanceService();
            $result = $obj->coreAccountLogSearch($params);
            if($result['status'] == SupervisionEnum::RESPONSE_SUCCESS && !empty($result['data']['result'])) {
                $list = $result['data']['result'];
                foreach ($list as $key => $item) {
                    $list[$key]['amountYuan'] = bcdiv($item['amount'], 100, 2) . '元';
                    $list[$key]['balanceYuan'] = bcdiv($item['balance'], 100, 2) . '元';
                    $list[$key]['statusName'] = SupervisionEnum::$offlineRecordsStatusMap[$item['status']];
                    $list[$key]['tradeTypeName'] = $item['tradeType'] === 'I' ? '入金' : '出金';
                }
            }
            $this->getPage($_REQUEST, $list);
        }else{
            $_REQUEST['startDate'] = date('Y-m-d');
            $_REQUEST['endDate'] = date('Y-m-d');
        }

        $offlineOrderStatusList = [];
        foreach (SupervisionEnum::$offlineRecordsStatusMap as $key => $name) {
            $offlineOrderStatusList[] = ['key'=>$key, 'name'=>$name];
        }
        $this->assign('main_title', '资金流水查询');
        $this->assign('default_map', $params);
        $this->assign('offlineOrderStatusList', $offlineOrderStatusList);
        $this->assign('p', $params['page']);
        $this->assign('np', $params['page']+1);
        $this->assign('list', $list);
        $this->display('offline_query_accountrecords');
    }

    /**
     * 获取充值来源映射配置
     * @return array
     */
    public static function getChargeMap() {
        return [
            PaymentEnum::PLATFORM_WEB => 'WEB',
            PaymentEnum::PLATFORM_ANDROID => 'ANDROID',
            PaymentEnum::PLATFORM_IOS => 'IOS',
            PaymentEnum::PLATFORM_MOBILEWEB => 'WAP',
            PaymentEnum::PLATFORM_OFFLINE => '线下充值',
            PaymentEnum::PLATFORM_MOBILEWEB => 'WAP',
            PaymentEnum::PLATFORM_H5 => 'H5',
            PaymentEnum::PLATFORM_AUTHCARD => '绑卡认证',
            PaymentEnum::PLATFORM_FUND_REDEEM => '基金赎回',
            PaymentEnum::PLATFORM_LCS => '理财师客户端',
            PaymentEnum::PLATFORM_SUPERVISION => '存管',
            PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE => '存管代扣充值',
            PaymentEnum::PLATFORM_OFFLINE_V2 => '大额充值',
            PaymentEnum::PLATFORM_H5_NEW_CHARGE => '大额充值',
            PaymentEnum::PLATFORM_ENTERPRISE_H5CHARGE => '存管代扣充值',
        ];
    }

    public function getPage($params, $list) {
        $url = $_SERVER['REQUEST_URI'];
        $page = (int)$params['page'];
        $pageNum = count($list);
        $pageUrl = sprintf('当前显示  %d 条记录 (当前第%d页) ', $pageNum, $page);
        if (empty($list) && $page <= 1) {
            $this->assign('page', $pageUrl);
            return;
        }

        $firstUrl = $parse['path'];
        $query = [];
        $parse = parse_url($url);
        if(!empty($params)) {
            $query = array_merge($params, $query);
            unset($params['page']);
            $firstUrl = $parse['path'] . '?' . http_build_query($params) . '&page=1';
            $preUrl = $parse['path'] . '?' . http_build_query($params) . '&page=' . ($page-1);
            $nextUrl = $parse['path'] . '?' . http_build_query($params) . '&page=' . ($page+1);
        }
        // 过滤url中的XSS注入字符，单引号双引号
        $nextUrl  = str_replace(array('\'', '"'), array('', ''), $nextUrl);
        if ($page <= 1) {
            $pageUrl .= $pageNum >= 30 ? sprintf("<a href='%s'>下一页</a>", $nextUrl) : '';
        }else{
            if (empty($list)) {
                $pageUrl .= sprintf("&nbsp;<a href='%s'>第一页</a>", $firstUrl);
            }else{
                $pageUrl .= sprintf("&nbsp;<a href='%s'>上一页</a>", $preUrl);
                $pageUrl .= $pageNum >= 30 ? sprintf("&nbsp;<a href='%s'>下一页</a>", $nextUrl) : '';
            }
        }
        $this->assign('page', $pageUrl);
    }
}
