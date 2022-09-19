<?php
/**
 * 网信生活相关-后台管理
 */
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Protos\Life\RequestCommon;
use NCFGroup\Protos\Life\Enum\CommonEnum;
use NCFGroup\Protos\Life\Enum\PaymentEnum;
use NCFGroup\Protos\Life\Enum\TripEnum;
use NCFGroup\Protos\Life\Enum\JobsEnum;
use core\service\life\UserTripService;
use core\service\life\PaymentUserService;
use core\dao\UserModel;
use libs\db\MysqlDb;

class LifeAction extends CommonAction {
    private $db;
    private static $input_type_list = array(
        '0' => '文本输入',
        '1' => '下拉框输入',
        '5' => '日期时间',
        //'2' => '图片上传',
        //'3' => '编辑器',
    );

    /**
     * 异步任务管理-等待执行
     */
    public function wait() {
        $_GET['status'] = JobsEnum::JOBS_STATUS_WAITING;
        $this->jobIndex();
    }

    /**
     * 异步任务管理-执行中
     */
    public function process() {
        $_GET['status'] = JobsEnum::JOBS_STATUS_PROCESS;
        $this->jobIndex();
    }

    /**
     * 异步任务管理-执行成功
     */
    public function succ() {
        $_GET['status'] = JobsEnum::JOBS_STATUS_SUCCESS;
        $this->jobIndex();
    }

    /**
     * 异步任务管理-执行失败
     */
    public function fail() {
        $_GET['status'] = JobsEnum::JOBS_STATUS_FAILED . ',' . JobsEnum::JOBS_STATUS_TERMINATE;
        $this->jobIndex();
    }

    /**
     * 异步任务管理-首页
     */
    public function jobIndex() {
        $map = array();
        $status = isset($_GET['status']) ? addslashes($_GET['status']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        // Jobs优先级
        $priority = -1;
        if (isset($_REQUEST['priority']) && $_REQUEST['priority'] != '') {
            $priority = intval($_REQUEST['priority']);
        }
        // JobsID
        $jobsId = 0;
        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '') {
            $jobsId = intval($_REQUEST['id']);
        }
        $pageSize = C('PAGE_LISTROWS');
        $vars = array(
            'status'   => $status,
            'pageNum'  => $p,
            'pageSize' => $pageSize,
            'priority' => $priority,
            'jobsId'   => $jobsId,
        );
        $request = new RequestCommon();
        $request->setVars($vars);

        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\Jobs',
            'method' =>'listJobs',
            'args' => $request,
        ));

        $page = new Page($response['totalNum'], $pageSize);
        $this->assign('page', $page->show());
        $this->assign('nowPage', $p);
        $this->assign('data', $response['data']);
        $this->assign('status', $status);
        $this->display('jobs_index');
    }

    /**
     * 异步任务管理-今日执行情况
     */
    public function today() {
        $time = strtotime(date('Y-m-d', time()));
        // 获取当天列表
        $request = new RequestCommon();
        $request->setVars(['startTime'=>$time]);
        $list = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\Jobs',
            'method' =>'getJobsAdminList',
            'args' => $request,
        ));

        $func = $_REQUEST['func'];
        if ($func) {
            $start_time = $time - 86400*7;
            // 获取7天前的列表
            $request = new RequestCommon();
            $request->setVars(['func'=>$func, 'startTime'=>$start_time, 'endTime'=>time()]);
            $list_week = $this->getRpc('lifeRpc')->callByObject(array(
                'service' => 'NCFGroup\Life\Services\Jobs',
                'method' =>'getJobsAdminListByFunc',
                'args' => $request,
            ));

            $count = $list_week['count'];
            $cost = $list_week['cost'];

            $arr_date = array();
            for ($i=0; $i<=7; $i++) {
                $d = date('Y-m-d', $start_time + 86400*$i);
                $arr_date[$i] = $d;
                $arr_count[$i] = $count[$d] ? $count[$d] : '0';
                $arr_cost[$i] = $cost[$d] ? $cost[$d] : '0';
            }
            $this->assign('arr_date', $arr_date);
            $this->assign('count', $arr_count);
            $this->assign('cost', $arr_cost);
        }
        $this->assign('list', $list);
        $this->display('today');
    }

    /**
     * 异步任务管理-查看某个任务详情
     */
    public function view() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }

        $vars = array(
            'id' => $id,
        );
        $request = new RequestCommon();
        $request->setVars($vars);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\Jobs',
            'method' => 'getJobsById',
            'args' => $request,
        ));

        $job = $response;
        if (empty($job)) {
            $this->error('记录不存在');
        }

        $this->assign('job', $job);
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->display();
    }

    /**
     * 异步任务管理-重新执行某个任务
     */
    public function redo() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }

        $vars = array(
            'id' => $id,
        );
        $request = new RequestCommon();
        $request->setVars($vars);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\Jobs',
            'method' => 'redoJobs',
            'args' => $request,
        ));

        if (!$response['ret']) {
            $this->error((!empty($response['errorMsg']) ? $response['errorMsg'] : '操作失败'));
        } else {
            $this->success('加入队列成功');
        }
    }

    /**
     * 异步任务管理-重新批量执行某个任务
     */
    public function multi_redo() {
        $ids = isset($_GET['id']) ? $_GET['id'] : 0;
        if (empty($ids)) {
            $this->error('无效的参数');
        }

        $id_arr = explode(",", $ids);
        foreach ($id_arr as $k => $v) {
            try {
                $vars = array(
                    'id' => (int)$v,
                );
                $request = new RequestCommon();
                $request->setVars($vars);
                $response = $this->getRpc('lifeRpc')->callByObject(array(
                    'service' => 'NCFGroup\Life\Services\Jobs',
                    'method' => 'redoJobs',
                    'args' => $request,
                ));
                if (!$response['ret']) {
                    $this->error((!empty($response['errorMsg']) ? $response['errorMsg'] : '操作失败'));
                }
            }
            catch(\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->success('加入队列成功');
    }

    /**
     * 异步任务管理-终止某个任务
     */
    public function terminate() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }

        $vars = array(
            'id' => $id,
        );
        $request = new RequestCommon();
        $request->setVars($vars);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\Jobs',
            'method' => 'terminateJobs',
            'args' => $request,
        ));

        if (!$response['ret']) {
            $this->error((!empty($response['errorMsg']) ? $response['errorMsg'] : '操作失败'));
        } else {
            $this->success('任务终止成功');
        }
    }

    /**
     * 配置管理-配置列表
     */
    public function index() {
        $name = isset($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
        $obj = new UserTripService();
        $list = $lifeList = [];
        if (empty($name)) {
            $result = $obj->getLifeConfList();
            if (!empty($result['data'])) {
                foreach ($result['data'] as $key => $item) {
                    if (in_array($item['name'], CommonEnum::$specialConfKeyList)) {
                        $result['data'][$key]['value'] = str_replace('\"', '"', stripslashes($item['value']));
                    }
                }
            }
            $list = $lifeList = $result['data'];
        }else{
            $result = $obj->getLifeConf($name);
            if (!isset($result['data'][1])) {
                if (in_array($item['name'], CommonEnum::$specialConfKeyList)) {
                    $result['data']['value'] = str_replace('\"', '"', stripslashes($result['data']['value']));
                }
                $list[] = $lifeList[] = $result['data'];
            }
        }
        $tmpList = $this->_getTmpList();
        if (!empty($tmpList) && empty($name)) {
            foreach ($tmpList as $tmpKey => $tmpItem) {
                $isExist = false;
                foreach ($lifeList as $key => $item) {
                    if (strcmp($item['name'], $tmpItem['name']) == 0) {
                        if (in_array($item['name'], CommonEnum::$specialConfKeyList)) {
                            $tmpItem['value'] = str_replace('\"', '"', stripslashes($tmpItem['value']));
                        }
                        $list[$key] = $tmpItem;
                        $isExist = true;
                        break;
                    }
                }
                $isExist == false && $list[$tmpKey] = $tmpItem;
            }
        }

        // 获取最近更新时间
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            $lastUpdateTime = $redis->get(CommonEnum::CACHEKEY_CONF_LASTTIME);
            !empty($lastUpdateTime) && $lastUpdateTime = unserialize($lastUpdateTime);
        }

        $this->assign('lastUpdateTime', $lastUpdateTime);
        $this->assign('main_title', '网信生活配置');
        $this->assign('input_type_list', self::$input_type_list);
        $this->assign('list', $list);
        $this->display('index');
    }

    /**
     * 配置管理-新增配置页面
     */
    public function add() {
        $this->display('add');
    }

    /**
     * 配置管理-编辑配置页面
     */
    public function edit() {
        $id = addslashes($_REQUEST['id']);
        $obj = new UserTripService();
        $result = $obj->getLifeConf($id);
        if (!empty($result['data']) && in_array($result['data']['name'], CommonEnum::$specialConfKeyList)) {
            $result['data']['value'] = str_replace('\"', '"', stripslashes($result['data']['value']));
        }
        $this->assign('data', $result['data']);
        $this->display('edit');
    }

    /**
     * 配置管理-保存配置页面
     */
    public function save() {
        if (empty($_POST['title']) || empty($_POST['name']) || strlen($_POST['value']) <= 0) {
            $this->error('参数错误或不合法');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $params = [
            'id'    => $id,
            'title' => addslashes($_POST['title']),
            'name'  => addslashes($_POST['name']),
            'value' => addslashes($_POST['value']),
            'sort'  => isset($_POST['sort']) ? (int)$_POST['sort'] : 0,
            'tip'   => addslashes($_POST['tip']),
        ];
        $msgErr = $id <= 0 ? L('INSERT_FAILED') : L('UPDATE_FAILED');
        $msg = $id <= 0 ? L('INSERT_SUCCESS') : L('UPDATE_SUCCESS');
        // 保存
        $obj = new UserTripService();
        $response = $obj->saveLifeConf($params);
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($msgErr);
        }
        // 设置临时列表
        $this->_setTmpList($params);
        $this->assign('jumpUrl', u(MODULE_NAME . '/index'));
        $this->success($msg);
    }

    /**
     * 配置管理-立即发布按钮，设置最后更新时间
     */
    public function setLastUpdateTime($isTips = true) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            $this->error('Redis连接异常');
        }

        $redis->set(CommonEnum::CACHEKEY_CONF_LASTTIME, serialize(time()));
        // 清空session
        \es_session::delete('tmpLifeList');
        $isTips && $this->success('更新成功');
    }

    /**
     * 配置管理-删除配置
     */
    public function foreverdelete() {
        $name = addslashes($_REQUEST['name']);
        $obj = new UserTripService();
        $response = $obj->deleteLifeConf($name);
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error(sprintf('删除失败：%s(%s)', $response['errorMsg'], $response['errorCode']));
        }
        // 立即发布
        $this->setLastUpdateTime(false);
        $this->success('删除成功');
    }

    /**
     * 订单管理-消费订单列表
     */
    public function consumeList() {
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        if (!isset($_REQUEST['count'])) {
            $_REQUEST['count'] = 20;
        }
        if (!isset($_REQUEST['orderStatus'])) {
            $_REQUEST['orderStatus'] = -1;
        }
        if (!isset($_REQUEST['payStatus'])) {
            $_REQUEST['payStatus'] = -1;
        }
        if (empty($_REQUEST['startTime'])) {
            $_REQUEST['startTime'] = date('Y-m-d 00:00:00');
        }
        if (empty($_REQUEST['endTime'])) {
            $_REQUEST['endTime'] = date('Y-m-d 23:59:59');
        }
        if (empty($_REQUEST['userId']) && !empty($_REQUEST['realName'])) {
            $userIdList = UserModel::instance()->getUserByRealName(trim($_REQUEST['realName']), 'id');
            $_REQUEST['userId'] = -1;
            if (!empty($userIdList)) {
                $_tmp = [];
                foreach ($userIdList as $item) {
                    $_tmp[] = $item['id'];
                }
                $_REQUEST['userId'] = $_tmp;
            }
        }else if (empty($_REQUEST['userId']) && !empty($_REQUEST['mobile'])) {
            $userInfo = UserModel::instance()->getUserinfoByUsername(trim($_REQUEST['mobile']));
            $_REQUEST['userId'] = !empty($userInfo['id']) ? $userInfo['id'] : -1;
        }

        // 消费订单列表的状态配置
        $consumeStatusConfig = self::_getConsumeStatusList();
        if (!empty($consumeStatusConfig[$_REQUEST['orderStatus']])) {
            $_REQUEST['status'] = $consumeStatusConfig[$_REQUEST['orderStatus']]['statusList'];
        }
        // 获取出行订单列表
        $obj = new PaymentUserService();
        $list = $obj->getConsumeList($_REQUEST);
        if ($list['errorCode'] != 0) {
            $this->error($list['errorMsg']);
        }

        // 获取支付状态列表
        $payStatusList = [];
        foreach (TripEnum::$allPayStatusList as $payId => $payName) {
            $payStatusList[] = ['id'=>$payId, 'name'=>$payName];
        }

        // 获取商户列表
        $merchantList = [];
        $merchantListPage = $this->getMerchantList();
        if (!empty($merchantListPage['list'])) {
            foreach ($merchantListPage['list'] as $item) {
                $merchantList[] = ['id'=>$item['merchant_id'], 'name'=>$item['merchant_name']];
            }
        }

        $_REQUEST['userId'] <= 0 && $_REQUEST['userId'] = '';
        //设置列表当前页号
        \es_session::set('wxcxConListCurrParam', http_build_query($_REQUEST));
        $page = new Page($list['data']['totalNum'], $_REQUEST['count'], http_build_query($_REQUEST));
        $this->assign('page', $page->show(true, count($list['data']['list'])));
        $this->assign('nowPage', $p);
        $this->assign('main_title', '网信生活出行订单列表');
        $this->assign('order_list', array_values($consumeStatusConfig));
        $this->assign('pay_status_list', $payStatusList);
        $this->assign('merchant_list', $merchantList);
        $this->assign('list', $list['data']['list']);
        $this->display('consume_list');
    }

    /**
     * 订单管理-消费订单详情
     */
    public function consumeDetail() {
        if (empty($_REQUEST['id'])) {
            $this->error('参数不能为空');
        }

        $detailParams = explode('|', $_REQUEST['id'], 3);
        if (count($detailParams) != 3) {
            $this->error('参数不合法');
        }

        // 获取出行订单详情
        $obj = new PaymentUserService();
        $info = $obj->getConsumeDetail($detailParams[0], $detailParams[1], $detailParams[2]);
        if ($info['errorCode'] != 0) {
            $this->error($info['errorMsg']);
        }

        // 检查出行订单是否已退款
        $isRefund = 0;
        $obj = new UserTripService();
        $refundData = $obj->getTripRefundOrderByOutOrderId($detailParams[0], $detailParams[1]);
        if (!empty($refundData['data'])) {
            $isRefund = 1;
        }

        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxConListCurrParam'));
        $this->assign('main_title', '网信生活出行订单详情页面');
        $this->assign('data', $info['data']);
        $this->assign('isRefund', $isRefund);
        $this->display('consume_detail');
    }

    /**
     * 查询用户的红包详情
     * @throws Exception
     * @throws \Exception
     */
    public function getUserBonusData() {
        $result = array('status' => -1, 'msg' => '查询失败', 'data'=>[]);
        try {
            if (empty($_REQUEST['awardCode'])) {
                throw new \Exception('参数错误');
            }

            $obj = new UserTripService();
            $ret = $obj->getUserBonusDetail($_REQUEST['awardCode']);
            if ($ret['errorCode'] != 0) {
                throw new \Exception($ret['errorMsg']);
            }

            $result['status'] = 0;
            $result['msg']    = '查询成功';
            $result['data']   = $ret['data'];
        } catch(\Exception $e) {
            $result['status'] = -1;
            $result['msg'] = $e->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 订单管理-退款订单列表
     */
    public function refundList() {
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        if (!isset($_REQUEST['count'])) {
            $_REQUEST['count'] = 20;
        }
        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
        }
        if (empty($_REQUEST['startCreateTime'])) {
            $_REQUEST['startCreateTime'] = date('Y-m-d 00:00:00');
        }
        if (empty($_REQUEST['endCreateTime'])) {
            $_REQUEST['endCreateTime'] = date('Y-m-d 23:59:59');
        }
        if (empty($_REQUEST['userId']) && !empty($_REQUEST['realName'])) {
            $userIdList = UserModel::instance()->getUserByRealName(trim($_REQUEST['realName']), 'id');
            $_REQUEST['userId'] = -1;
            if (!empty($userIdList)) {
                $_tmp = [];
                foreach ($userIdList as $item) {
                    $_tmp[] = $item['id'];
                }
                $_REQUEST['userId'] = $_tmp;
            }
        }else if (empty($_REQUEST['userId']) && !empty($_REQUEST['mobile'])) {
            $userInfo = UserModel::instance()->getUserinfoByUsername($_REQUEST['mobile']);
            $_REQUEST['userId'] = !empty($userInfo['id']) ? $userInfo['id'] : 0;
        }

        // 获取退款列表
        $obj = new PaymentUserService();
        $list = $obj->getRefundList($_REQUEST);
        if ($list['errorCode'] != 0) {
            $this->error($list['errorMsg']);
        }

        // 获取退款状态列表
        $refundStatusList = [];
        foreach (PaymentEnum::$refundStatusMap as $refundId => $refundName) {
            $refundStatusList[] = ['id'=>$refundId, 'name'=>$refundName];
        }

        // 获取商户列表
        $merchantList = [];
        $merchantListPage = $this->getMerchantList();
        if (!empty($merchantListPage['list'])) {
            foreach ($merchantListPage['list'] as $item) {
                $merchantList[] = ['id'=>$item['merchant_id'], 'name'=>$item['merchant_name']];
            }
        }

        $page = new Page($list['data']['totalNum'], $_REQUEST['count'], http_build_query($_REQUEST));
        $this->assign('page', $page->show(true, count($list['data']['list'])));
        $this->assign('nowPage', $p);
        $this->assign('main_title', '网信生活退款订单列表');
        $this->assign('refund_status_list', $refundStatusList);
        $this->assign('merchant_list', $merchantList);
        $this->assign('list', $list['data']['list']);
        $this->display('refund_list');
    }

    /**
     * 订单管理-出行订单备注页面
     */
    public function tripRemark() {
        if (empty($_REQUEST['outOrderId']) || empty($_REQUEST['merchantId'])) {
            return false;
        }

        // 获取出行订单信息
        $outOrderId = intval($_REQUEST['outOrderId']);
        $merchantId = addslashes($_REQUEST['merchantId']);
        $obj = new UserTripService();
        $tripOrderInfo = $obj->getUserTripByOutOrderId($outOrderId, $merchantId);
        if (empty($tripOrderInfo['data'])) {
            echo "<script>alert('该出行订单不存在');$.weeboxs.close();</script>";
            exit;
        }

        $this->assign('outOrderId', $outOrderId);
        $this->assign('merchantId', $merchantId);
        $this->assign('tripOrderInfo', $tripOrderInfo['data']);
        $this->display();
    }

    /**
     * 订单管理-提交出行备注
     * @throws Exception
     * @throws \Exception
     */
    public function doTripRemark() {
        $result = array('status' => -1, 'msg' => '操作失败');
        try {
            if (empty($_REQUEST['remark']) || empty($_REQUEST['outOrderId']) || empty($_REQUEST['merchantId'])) {
                throw new \Exception('参数错误');
            }

            $remark = addslashes(trim($_REQUEST['remark']));
            $outOrderId = intval($_REQUEST['outOrderId']);
            $merchantId = addslashes(trim($_REQUEST['merchantId']));
            $obj = new UserTripService();
            $tripOrderInfo = $obj->getUserTripByOutOrderId($outOrderId, $merchantId);
            if (empty($tripOrderInfo['data'])) {
                throw new \Exception('该出行订单不存在');
            }

            $admSession = es_session::get(md5(conf("AUTH_KEY")));
            $remarkRet = $obj->addTripRemarkData($outOrderId, $remark, $admSession['adm_name']);
            if ($remarkRet['errorCode'] != 0 || $remarkRet['data']['status'] == 0) {
                throw new \Exception('备注添加失败');
            }

            $result['status'] = 0;
            $result['msg']    = '备注添加成功';
        } catch(\Exception $e) {
            $result['status'] = -1;
            $result['msg'] = $e->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 订单管理-出行订单退款页面
     * @return boolean
     */
    public function tripRefund() {
        if (empty($_REQUEST['outOrderId']) || empty($_REQUEST['merchantId'])) {
            return false;
        }

        // 获取出行订单信息
        $outOrderId = intval($_REQUEST['outOrderId']);
        $merchantId = addslashes($_REQUEST['merchantId']);
        $obj = new UserTripService();
        $tripOrderInfo = $obj->getUserTripByOutOrderId($outOrderId, $merchantId);
        if (empty($tripOrderInfo['data'])) {
            echo "<script>alert('该出行订单不存在');$.weeboxs.close();</script>";
            exit;
        }
        if ($tripOrderInfo['data']['pay_status'] != PaymentEnum::ORDER_STATUS_SUCCESS) {
            echo "<script>alert('该出行订单尚未支付成功，不能退款');$.weeboxs.close();</script>";
            exit;
        }

        // 该订单是否已开发票
        $invoiceCntInfo = $obj->getInvoiceCountByOutOrderId($outOrderId);
        $this->assign('outOrderId', $outOrderId);
        $this->assign('merchantId', $merchantId);
        $this->assign('isInvoice', !empty($invoiceCntInfo['data']['count']) ? 1 : 0);
        $this->assign('actualAmount', (int)$tripOrderInfo['data']['actual_amount']);
        $this->assign('actualAmountYuan', bcdiv($tripOrderInfo['data']['actual_amount'], 100, 2));
        $this->display();
    }

    /**
     * 订单管理-发起出行退款
     * @throws Exception
     * @throws \Exception
     */
    public function doTripRefund() {
        $result = array('status' => -1, 'msg' => '操作失败');
        try {
            if (empty($_REQUEST['outOrderId']) || empty($_REQUEST['merchantId']) || empty($_REQUEST['amount'])) {
                throw new \Exception('参数错误');
            }

            $amountYuan = addslashes(trim($_REQUEST['amount']));
            $amount = bcmul($amountYuan, 100, 2);
            $outOrderId = intval($_REQUEST['outOrderId']);
            $merchantId = addslashes(trim($_REQUEST['merchantId']));
            $obj = new UserTripService();
            $tripOrderInfo = $obj->getUserTripByOutOrderId($outOrderId, $merchantId);
            if (empty($tripOrderInfo['data'])) {
                throw new \Exception('该出行订单不存在');
            }

            // 检查该出行订单是否已退款
            $refundData = $obj->getTripRefundOrderByOutOrderId($outOrderId, $merchantId);
            if (!empty($refundData['data'])) {
                throw new \Exception('该出行订单已经退过款了，不能重复退款');
            }

            // 发起退款请求
            $refundRet = $obj->addTripRefund($outOrderId, $merchantId, $amount);
            if ($refundRet['errorCode'] != 0) {
                throw new \Exception($refundRet['errorMsg']);
            }

            // 记录退款备注
            $admSession = es_session::get(md5(conf("AUTH_KEY")));
            $remark = sprintf('%s操作退款%s元', $admSession['adm_name'], $amountYuan);
            $remarkRet = $obj->addTripRemarkData($outOrderId, $remark, $admSession['adm_name']);
            if ($remarkRet['errorCode'] != 0 || $remarkRet['data']['status'] == 0) {
                throw new \Exception('退款备注记录写入失败');
            }

            $result['status'] = 0;
            $result['msg']    = '退款已受理';
        } catch(\Exception $e) {
            $result['status'] = -1;
            $result['msg'] = $e->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 版块商户-商户列表
     */
    public function merchantList() {
        $params['page'] = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $params['count'] = $_REQUEST['count'] = isset($_REQUEST['count']) ? (int)$_REQUEST['count'] : 10;
        // 商户编号
        if (!empty($_REQUEST['merchantId'])) {
            $params['merchantId'] = addslashes(trim($_REQUEST['merchantId']));
        }
        // 商户名称
        if (!empty($_REQUEST['merchantName'])) {
            $params['merchantName'] = addslashes(trim($_REQUEST['merchantName']));
        }
        // 商户简称
        if (!empty($_REQUEST['shortName'])) {
            $params['shortName'] = addslashes(trim($_REQUEST['shortName']));
        }
        // 获取所有商户列表
        $allMerList = $this->getMerchantList(['page'=>1, 'count'=>100]);
        // 商户列表
        $list = $this->getMerchantList($params);

        //设置列表当前页号
        \es_session::set('wxcxMerListCurrParam', http_build_query($_REQUEST));
        $page = new Page($list['totalNum'], $_REQUEST['count'], http_build_query($_REQUEST));
        $this->assign('page', $page->show(true, count($list['list'])));
        $this->assign('nowPage', $params['page']);
        $this->assign('main_title', '网信生活商户列表');
        $this->assign('list', $list['list']);
        $this->assign('allMerList', $allMerList['list']);
        $this->display('merchant_list');
    }

    /**
     * 版块商户-新增商户页面
     */
    public function merchantAdd() {
        $merchantId = sprintf('%s_%s%s', 'MEC', date('ymdHis'), \core\service\YeepayPaymentService::random(10, 1));
        // 获取版块列表
        $secAllList = $this->getSectionList(['page'=>1, 'count'=>100]);
        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxMerListCurrParam'));
        $this->assign('main_title', '网信生活商户创建页面');
        $this->assign('merchantId', $merchantId);
        $this->assign('secAllList', $secAllList['list']);
        $this->assign('data', []);
        $this->display('merchant_detail');
    }

    /**
     * 版块商户-商户详情页面
     */
    public function merchantDetail() {
        if (empty($_REQUEST['id'])) {
            $this->error('参数不能为空');
        }
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $response = $this->getMerchantInfo($_REQUEST['id']);
        // 获取版块列表
        $secAllList = $this->getSectionList(['page'=>1, 'count'=>100]);

        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxMerListCurrParam'));
        $this->assign('main_title', '网信生活商户详情页面');
        $this->assign('data', !empty($response['data']) ? $response['data'] : []);
        $this->assign('secAllList', $secAllList['list']);
        $this->display('merchant_detail');
    }

    /**
     * 版块商户-保存商户基本信息
     */
    public function saveMerchant() {
        if (empty($_POST['merchantName']) || empty($_POST['secId'])) {
            $this->error('参数错误或不合法');
        }

        $merchantId = isset($_POST['merchantId']) ? addslashes($_POST['merchantId']) : '';
        $msgErr = empty($merchantId) ? L('INSERT_FAILED') : L('UPDATE_FAILED');
        $msg = empty($merchantId) ? L('INSERT_SUCCESS') : L('UPDATE_SUCCESS');

        //后台用户信息
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $_POST['adminId']   = intval($adm_session['adm_id']);
        $_POST['adminName'] = addslashes($adm_session['adm_name']);

        // 获取该商户编号的信息
        $oldMerchantData = [];
        if (!empty($merchantId)) {
            $oldMerchantDataResponse = $this->getMerchantInfo($merchantId);
            $oldMerchantData = !empty($oldMerchantDataResponse['data']) ? $oldMerchantDataResponse['data'] : [];
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' =>'saveMerchant',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($msgErr);
        }
        save_log('网信生活-新增或更新商户信息，商户编号['.$merchantId.']操作成功', 1, $oldMerchantData, $_POST);
        // 当前参数
        $nowParam = \es_session::get('wxcxMerListCurrParam');
        $this->assign('jumpUrl', u(MODULE_NAME . '/merchantList?' . $nowParam));
        $this->success($msg);
    }

    /**
     * 版块商户-获取所有的商户列表
     * @param array $params 参数列表
     */
    public function getMerchantList($params = []) {
        try {
            $request = new RequestCommon();
            $request->setVars($params);
            $response = $this->getRpc('lifeRpc')->callByObject(array(
                'service' => 'NCFGroup\Life\Services\SectionMerchant',
                'method' => 'getMerchantListByPage',
                'args' => $request,
            ));
            return ($response['errorCode'] == 0) ? $response['data'] : [];
        } catch(\Exception $e) {
            return [];
        }
    }

    /**
     * 版块商户-获取指定商户的信息
     * @param string $merchantId
     */
    public function getMerchantInfo($merchantId, $status = -1) {
        $request = new RequestCommon();
        $request->setVars(['merchantId'=>addslashes($merchantId), 'status'=>(int)$status]);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' => 'getMerchantInfoById',
            'args' => $request,
        ));
        return $response;
    }

    /**
     * 版块商户-版块列表
     */
    public function sectionList() {
        $params['page'] = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $params['count'] = $_REQUEST['count'] = isset($_REQUEST['count']) ? (int)$_REQUEST['count'] : 10;
        // 版块编号
        if (!empty($_REQUEST['secId'])) {
            $params['secId'] = addslashes(trim($_REQUEST['secId']));
        }
        // 版块名称
        if (!empty($_REQUEST['secName'])) {
            $params['secName'] = addslashes(trim($_REQUEST['secName']));
        }
        // 版块简称
        if (!empty($_REQUEST['shortName'])) {
            $params['shortName'] = addslashes(trim($_REQUEST['shortName']));
        }
        // 获取所有版块列表
        $allSecList = $this->getSectionList(['page'=>1, 'count'=>100]);
        // 版块列表
        $list = $this->getSectionList($params);

        //设置列表当前页号
        \es_session::set('wxcxSecListCurrParam', http_build_query($_REQUEST));
        $page = new Page($list['totalNum'], $_REQUEST['count'], http_build_query($_REQUEST));
        $this->assign('page', $page->show(true, count($list['list'])));
        $this->assign('nowPage', $params['page']);
        $this->assign('main_title', '网信生活版块列表');
        $this->assign('list', $list['list']);
        $this->assign('allSecList', $allSecList['list']);
        $this->display('sec_list');
    }

    /**
     * 版块商户-版块详情页面
     */
    public function sectionDetail() {
        if (empty($_REQUEST['id'])) {
            $this->error('参数不能为空');
        }
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $response = $this->getSectionInfo($_REQUEST['id']);
        // 版块logoID
        if(!empty($response['data']['sec_logo'])) {
            $response['data']['secLogoName'] = get_attr($response['data']['sec_logo'], 1, false);
        }

        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxSecListCurrParam'));
        $this->assign('main_title', '网信生活商户详情页面');
        $this->assign('data', !empty($response['data']) ? $response['data'] : []);
        $this->display('section_detail');
    }

    /**
     * 版块商户-新增版块页面
     */
    public function sectionAdd() {
        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxSecListCurrParam'));
        $this->assign('main_title', '网信生活版块创建页面');
        $this->assign('data', []);
        $this->display('section_detail');
    }

    /**
     * 版块商户-保存商户基本信息
     */
    public function saveSection() {
        if (empty($_POST['secName'])) {
            $this->error('参数错误或不合法');
        }

        $secId = isset($_POST['secId']) ? addslashes($_POST['secId']) : '';
        $msgErr = empty($secId) ? L('INSERT_FAILED') : L('UPDATE_FAILED');
        $msg = empty($secId) ? L('INSERT_SUCCESS') : L('UPDATE_SUCCESS');

        //后台用户信息
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $_POST['adminId']   = intval($adm_session['adm_id']);
        $_POST['adminName'] = addslashes($adm_session['adm_name']);

        // 获取该版块编号的信息
        $oldSecData = [];
        if (!empty($secId)) {
            $oldSecDataResponse = $this->getSectionInfo($secId);
            $oldSecData = !empty($oldSecDataResponse['data']) ? $oldSecDataResponse['data'] : [];
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' =>'saveSection',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($msgErr);
        }
        save_log('网信生活-新增或更新版块信息，版块编号['.$secId.']操作成功', 1, $oldSecData, $_POST);
        // 当前参数
        $nowParam = \es_session::get('wxcxSecListCurrParam');
        $this->assign('jumpUrl', u(MODULE_NAME . '/sectionList?' . $nowParam));
        $this->success($msg);
    }

    /**
     * 版块商户-获取所有的版块列表
     * @param array $params 参数列表
     */
    public function getSectionList($params = []) {
        try {
            $request = new RequestCommon();
            $request->setVars($params);
            $response = $this->getRpc('lifeRpc')->callByObject(array(
                'service' => 'NCFGroup\Life\Services\SectionMerchant',
                'method' => 'getSectionListByPage',
                'args' => $request,
            ));
            return ($response['errorCode'] == 0) ? $response['data'] : [];
        } catch(\Exception $e) {
            return [];
        }
    }

    /**
     * 版块商户-获取指定版块的信息
     * @param string $secId
     */
    public function getSectionInfo($secId, $status = -1) {
        $request = new RequestCommon();
        $request->setVars(['secId'=>addslashes($secId), 'status'=>(int)$status]);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' => 'getSecInfoById',
            'args' => $request,
        ));
        return $response;
    }

    /**
     * 版块支付方式-支付方式列表
     */
    public function sectionPaymentList() {
        $params['page'] = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $params['count'] = $_REQUEST['count'] = isset($_REQUEST['count']) ? (int)$_REQUEST['count'] : 10;
        // 版块列表
        $secMap = [];
        $secAllList = $this->getSectionList(['page'=>1, 'count'=>100]);
        if (!empty($secAllList['list'])) {
            foreach ($secAllList['list'] as $item) {
                $secMap[$item['sec_id']] = $item['sec_name'];
            }
        }

        // 版块编号
        if (!empty($_REQUEST['secId'])) {
            $params['secId'] = addslashes(trim($_REQUEST['secId']));
        }
        // 版块支付方式列表
        $paymentList = $this->getSectionPaymentList($params, $secMap);

        //设置列表当前页号
        \es_session::set('wxcxSecPayListCurrParam', http_build_query($_REQUEST));
        $page = new Page($paymentList['totalNum'], $_REQUEST['count'], http_build_query($_REQUEST));
        $this->assign('page', $page->show(true, count($paymentList['list'])));
        $this->assign('nowPage', $params['page']);
        $this->assign('main_title', '版块支付方式列表');
        $this->assign('secList', $secAllList['list']);
        $this->assign('secMap', $secMap);
        $this->assign('list', $paymentList['list']);
        $this->display('sec_payment_list');
    }

    /**
     * 版块支付方式-新增支付方式页面
     */
    public function sectionPaymentAdd() {
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        // 版块列表
        $secMap = [];
        $secAllList = $this->getSectionList(['page'=>1, 'count'=>100]);
        if (!empty($secAllList['list'])) {
            foreach ($secAllList['list'] as $item) {
                $secMap[$item['sec_id']] = $item['sec_name'];
            }
        }

        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxSecPayListCurrParam'));
        $this->assign('main_title', '版块支付方式新增页面');
        $this->assign('data', []);
        $this->assign('secMap', $secMap);
        $this->assign('payFlagList', PaymentEnum::$payFlagConfig);
        $this->assign('cardTypeList', PaymentEnum::$cardTypeConfig);
        $this->display('sec_payment_detail');
    }

    /**
     * 版块支付方式-支付方式详情页面
     */
    public function sectionPaymentDetail() {
        if (empty($_REQUEST['id']) || empty($_REQUEST['secId'])) {
            $this->error('参数不能为空');
        }

        // 获取版块支付方式详情
        $p = $_REQUEST['page'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $response = $this->getSecPaymentInfoById($_REQUEST['id'], $_REQUEST['secId']);
        // 版块列表
        $secMap = [];
        $secAllList = $this->getSectionList(['page'=>1, 'count'=>100]);
        if (!empty($secAllList['list'])) {
            foreach ($secAllList['list'] as $item) {
                $secMap[$item['sec_id']] = $item['sec_name'];
            }
        }

        // 当前参数
        $this->assign('nowParam', \es_session::get('wxcxSecPayListCurrParam'));
        $this->assign('main_title', '版块支付方式详情页面');
        $this->assign('data', !empty($response['data']) ? $response['data'] : []);
        $this->assign('secMap', $secMap);
        $this->assign('payFlagList', PaymentEnum::$payFlagConfig);
        $this->assign('cardTypeList', PaymentEnum::$cardTypeConfig);
        $this->display('sec_payment_detail');
    }

    /**
     * 版块支付方式-保存支付方式基本信息
     */
    public function saveSectionPayment() {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $secId = isset($_POST['secId']) ? addslashes($_POST['secId']) : '';
        $msgErr = empty($id) ? L('INSERT_FAILED') : L('UPDATE_FAILED');
        $msg = empty($id) ? L('INSERT_SUCCESS') : L('UPDATE_SUCCESS');

        // 后台用户信息
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $_POST['adminId']   = intval($adm_session['adm_id']);
        $_POST['adminName'] = addslashes($adm_session['adm_name']);

        // 获取该商户编号的信息
        $oldSecData = [];
        if (!empty($secId)) {
            $oldSecDataResponse = $this->getSecPaymentInfoById($id, $secId);
            $oldSecData = !empty($oldSecDataResponse['data']) ? $oldSecDataResponse['data'] : [];
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' =>'saveSectionPayment',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($msgErr);
        }
        save_log('网信生活-新增或更新版块支付方式信息，版块编号['.$secId.']操作成功', 1, $oldSecData, $_POST);
        // 当前参数
        $nowParam = \es_session::get('wxcxSecPayListCurrParam');
        $this->assign('jumpUrl', u(MODULE_NAME . '/sectionPaymentList?' . $nowParam));
        $this->success($msg);
    }

    /**
     * 版块支付方式-获取所有的版块支付方式列表
     * @param array $params 参数列表
     */
    public function getSectionPaymentList($params = [], $secMap = []) {
        try {
            $request = new RequestCommon();
            $request->setVars($params);
            $response = $this->getRpc('lifeRpc')->callByObject(array(
                'service' => 'NCFGroup\Life\Services\SectionMerchant',
                'method' => 'getSectionPaymentListByPage',
                'args' => $request,
            ));
            $paymentList = ['list'=>[], 'totalNum'=>0];
            if ($response['errorCode'] == 0) {
                if (empty($response['data']['list'])) {
                    return $paymentList;
                }
                $paymentList['totalNum'] = $response['data']['totalNum'];
                foreach ($response['data']['list'] as $item) {
                    $paymentList['list'][] = [
                        'id'             => $item['id'],
                        'sec_id'         => $item['sec_id'],
                        'sec_name'       => !empty($secMap[$item['sec_id']]) ? $secMap[$item['sec_id']] : '',
                        'status'         => $item['status'],
                        'pay_flag'       => $item['pay_flag'],
                        'pay_flag_name'  => !empty(PaymentEnum::$payFlagConfig[$item['pay_flag']]) ? PaymentEnum::$payFlagConfig[$item['pay_flag']] : '',
                        'card_type'      => $item['card_type'],
                        'card_type_name' => !empty(PaymentEnum::$cardTypeConfig[$item['card_type']]) ? PaymentEnum::$cardTypeConfig[$item['card_type']] : '',
                        'admin_name'     => $item['admin_name'],
                        'update_time'    => !empty($item['update_time']) ? date('Y-m-d H:i:s', $item['update_time']) : date('Y-m-d H:i:s', $item['create_time']),
                    ];
                }
            }
            return $paymentList;
        } catch(\Exception $e) {
            return [];
        }
    }

    /**
     * 版块支付方式-获取指定版块支付方式的信息
     * @param string $secId
     */
    public function getSecPaymentInfoById($id, $secId, $status = -1) {
        $request = new RequestCommon();
        $request->setVars(['id'=>(int)$id, 'secId'=>addslashes($secId), 'status'=>(int)$status]);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\SectionMerchant',
            'method' => 'getSecPaymentInfoById',
            'args' => $request,
        ));
        return $response;
    }

    /**
     * 配置管理-获取临时列表
     * @param array $data
     */
    private function _getTmpList() {
        $tmpListSession = \es_session::get('tmpLifeList');
        $tmpList = [];
        if (!empty($tmpListSession)) {
            $tmpList = json_decode($tmpListSession, true);
        }
        return $tmpList;
    }

    /**
     * 配置管理-设置临时列表
     * @param array $data
     */
    private function _setTmpList($data) {
        $tmpList = $this->_getTmpList();
        if (!empty($data)) {
            $tmpList[$data['name']] = $data;
        }
        return \es_session::set('tmpLifeList', json_encode($tmpList));
    }

    /**
     * 订单管理-消费订单列表的状态配置
     */
    private static function _getConsumeStatusList() {
        return array(
            1 => array('id'=>1, 'name'=>'已下单', 'statusList'=>TripEnum::$tripStatusCreate),
            2 => array('id'=>2, 'name'=>'进行中', 'statusList'=>TripEnum::$tripStatusIng),
            3 => array('id'=>3, 'name'=>'已完成', 'statusList'=>TripEnum::$tripStatusDone),
            4 => array('id'=>4, 'name'=>'已取消', 'statusList'=>TripEnum::$tripStatusCancel),
        );
    }

    public function query() {
        // 获取数据库列表
        $this->assign('dbs', CommonEnum::$dbListConfig);
        // 获取当前数据库
        $this->assign('useDb', CommonEnum::$dbListConfig[0]);
        $this->assign('tables', $this->_getTables($this->get('useDb')));
        $this->assign('query', $_REQUEST['q']);
        $this->display();
    }

    public function execute() {
        $dbName = addslashes($_REQUEST['db']);
        $paramQuery = trim($_REQUEST['query']);
        if (MAGIC_QUOTES_GPC) {
            $paramQuery = stripslashes($paramQuery);
        }
        if (empty($paramQuery) || stripos($paramQuery, 'select') === false) {
            $this->error('SQL不能为空或不合法！');
        }

        $this->assign('query', $paramQuery);
        $paramQueryNew = str_replace('|', ' ', trim($paramQuery));
        if (!self::checkParamsInject($paramQueryNew)) {
            $this->error('只能执行查询SQL！');
        }

        // 查询数据
        $startTime = microtime(TRUE);
        if ($dbName == CommonEnum::$dbListConfig[0]) {
            $vars = ['queryEncode'=>\libs\utils\Aes::encode($paramQueryNew, $GLOBALS['sys_config']['XFZF_SEC_KEY'])];
            $response = $this->_queryLife($vars);
            if ($response['errorCode'] != 0) {
                $array = [];
                $array[] =  number_format((microtime(TRUE) - $startTime), 6) . 's';
                $errorTips = sprintf('%s（%s）', $response['errorMsg'], $response['errorCode']);
                $this->ajaxReturn($array, $errorTips, 1);
            }
            $query = $response['data']['query'];
            $result = $response['data']['result'];
        } else {
            $query = $paramQueryNew;
            $this->db = MysqlDb::getInstance($dbName);
            $result = $this->db->getAll($query);
        }

        $runtime = number_format((microtime(TRUE) - $startTime), 6);
        if (!empty($_POST['record'])) {
            // 记录执行SQL语句
            Log::write('RunTime:'.$runtime.'s SQL = '.$query, Log::SQL);
        }
        if (false !== $result) {
            $array[] =  $runtime.'s';
            $array[]  = '';
            //记录执行的sql到后台日志表
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('adminId：%d，adminName：%s，执行了SQL：%s', $adm_session['adm_id'], $adm_session['adm_name'], addslashes($query)))));
            if(empty($result)) {
                $this->ajaxReturn($array, 'SQL执行成功！', 1);
            }
            $fields = array_keys($result[0]);
            $array[] = $fields;
            foreach($result as $key=>$val) {
                $val  = array_values($val);
                $array[] = $val;
            }
            $this->ajaxReturn($array, 'SQL执行成功！', 1);
        } else {
            $this->error('SQL错误！');
        }
    }

    public function getTables() {
        $dbName = addslashes($_REQUEST['db']);
        // 获取数据库的表列表
        $tables = $this->_getTables($dbName);
        $this->ajaxReturn($tables, '数据表获取完成', 1);
    }

    private function _getTables($dbName) {
        $query = 'SHOW TABLES FROM ' . self::_getDbName($dbName);
        $cacheKey = sprintf('life_admin_%s', md5($query));
        $listCache = self::getLifeQueryCache($cacheKey);
        if (!empty($listCache)) {
            return $listCache;
        }
    
        if ($dbName == CommonEnum::$dbListConfig[0]) {
            // 查询数据
            $vars = ['queryEncode'=>\libs\utils\Aes::encode($query, $GLOBALS['sys_config']['XFZF_SEC_KEY'])];
            $response = $this->_queryLife($vars);
            if ($response['errorCode'] != 0) {
                return [];
            }

            $result = $response['data']['result'];
        } else {
            $this->db = MysqlDb::getInstance($dbName);
            $result = $this->db->getAll($query);
        }

        $list = [];
        foreach ($result as $key => $val) {
            $list[$key] = current($val);
        }
        self::setLifeQueryCache($cacheKey, $list);
        return $list;
    }

    public function getFields() {
        $dbName = addslashes($_REQUEST['db']);
        $tbName = addslashes($_REQUEST['tb']);
        $query = 'DESC '.$tbName;
        $cacheKey = sprintf('life_admin_%s_%s', $dbName, md5($query));
        $listCache = self::getLifeQueryCache($cacheKey);
        if (!empty($listCache)) {
            $this->ajaxReturn($listCache, '数据表字段获取完成', 1);
        }

        if ($dbName == CommonEnum::$dbListConfig[0]) {
            // 获取数据表的字段列表
            $vars = ['queryEncode'=>\libs\utils\Aes::encode($query, $GLOBALS['sys_config']['XFZF_SEC_KEY'])];
            $response = $this->_queryLife($vars);
            if ($response['errorCode'] != 0) {
                return [];
            }

            $result = $response['data']['result'];
        } else {
            $this->db = MysqlDb::getInstance($dbName);
            $result = $this->db->getAll($query);
        }

        $list = [];
        foreach ($result as $key => $val) {
            $list[$key] = current($val);
        }
        self::setLifeQueryCache($cacheKey, $list);
        $this->ajaxReturn($list, '数据表字段获取完成', 1);
    }

    private static function _getDbName($dbName) {
        if ($dbName == 'firstp2p' && in_array(app_conf('ENV_FLAG'), ['dev', 'test'])) {
            return sprintf('%s_test', $dbName);
        }
        return $dbName;
    }

    private function _queryLife($vars) {
        try{
            $request = new RequestCommon();
            $request->setVars($vars);
            $response = $this->getRpc('lifeRpc')->callByObject(array(
                'service' => 'NCFGroup\Life\Services\TripOrder',
                'method' => 'queryList',
                'args' => $request,
            ));
            if ($response['errorCode'] != 0) {
                throw new \Exception($response['errorMsg']);
            }
            return $response;
        } catch(\Exception $e) {
            return ['errorCode'=>$e->getCode(), 'errorMsg'=>$e->getMessage()];
        }
    }

    public static function checkParamsInject($str) {
        foreach(CommonEnum::$queryInjectPatterns as $pattern) {
            if(preg_match($pattern, $str)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取生活查询的缓存
     * @param string $cacheKey
     */
    public static function getLifeQueryCache($cacheKey) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!is_null($redis)) {
            $listJson = $redis->get($cacheKey);
            return json_decode($listJson, true);
        }
        return [];
    }

    /**
     * 设置生活查询的缓存
     * @param string $cacheKey
     */
    public static function setLifeQueryCache($cacheKey, $list, $expireTime = 3600) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        return is_null($redis) ? false : $redis->setex($cacheKey, $expireTime, json_encode($list));
    }

    /**
     * 网信生活-工具页
     */
    public function tools() {
        // 获取所有商户列表
        $allMerList = $this->getMerchantList(['page'=>1, 'count'=>100]);
        $this->assign('allMerList', $allMerList['list']);
        $this->display();
    }

    /**
     * 收银台-收银台主订单超时关闭
     */
    public function paymentColse() {
        if (empty($_POST['payOrderId'])) {
            $this->error('参数错误或不合法');
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars(['payOrderId'=>(int)$_POST['payOrderId']]);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\PaymentUser',
            'method' =>'setMainPaymentFailed',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($response['errorMsg']);
        }

        if (!isset($response['data']['ret']) || $response['data']['ret'] === false) {
            return $this->error('受理失败，请重试');
        } else {
            save_log('网信生活-收银台主订单超时关闭，支付主订单号['.$_POST['payOrderId'].']操作成功', 1, [], $_POST);
            return $this->success('收银台主订单超时关闭请求已受理，JobsId：' . $response['data']['jobsId']);
        }
    }

    /**
     * 网信出行-出行主订单设置是否有效
     */
    public function tripEffect() {
        if (empty($_POST['outOrderId']) || empty($_POST['merchantId'])) {
            $this->error('参数错误或不合法');
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\TripOrder',
            'method' =>'setTripEffect',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($response['errorMsg']);
        }

        save_log('网信生活-出行主订单置为无效操作成功，出行主订单号['.$_POST['outOrderId'].']操作成功', 1, [], $_POST);
        return $this->success('设置成功，当前状态为：' . $response['data']['effect_name']);
    }

    /**
     * 网信出行-清理用户银行卡列表缓存
     */
    public function clearUserCardList() {
        if (empty($_POST['userId'])) {
            $this->error('参数错误或不合法');
        }

        // 清理
        $obj = new PaymentUserService();
        $ret = $obj->clearUserCardListCache($_POST['userId']);

        save_log('网信生活-清理用户银行卡列表缓存操作成功，用户ID['.$_POST['userId'].']操作成功', 1, [], $_POST);
        return $this->success('清理成功，状态：' . (int)$ret);
    }

    /**
     * 网信出行-进行中的出行发票状态置为失败
     */
    public function invoiceFailed() {
        if (empty($_POST['invoiceOrderId']) || empty($_POST['merchantId'])) {
            $this->error('参数错误或不合法');
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\UserInvoice',
            'method' =>'setInvoiceStatusFailed',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($response['errorMsg']);
        }

        save_log('网信生活-进行中的出行发票状态置为失败操作成功，出行发票订单号['.$_POST['invoiceOrderId'].']操作成功', 1, [], $_POST);
        return $this->success('操作成功');
    }

    /**
     * 收银台-把银行卡置为无效
     */
    public function paymentCardStatus() {
        if (empty($_POST['userId']) || empty($_POST['bankSign'])) {
            $this->error('参数错误或不合法');
        }

        // 保存
        $request = new RequestCommon();
        $request->setVars($_POST);
        $response = $this->getRpc('lifeRpc')->callByObject(array(
            'service' => 'NCFGroup\Life\Services\UserCard',
            'method' =>'setCardEffect',
            'args' => $request,
        ));
        if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
            $this->error($response['errorMsg']);
        }

        save_log('收银台-把银行卡置为无效操作成功，用户ID['.$_POST['userId'].']，银行卡签名['.$_POST['bankSign'].']操作成功', 1, [], $_POST);
        return $this->success('操作成功');
    }

    // 配置管理-配置列表
    public function index2() {
        $this->index();
    }
    // 任务管理-等待执行
    public function wait2() {
        $this->wait();
    }
    // 任务管理-执行中
    public function process2() {
        $this->process();
    }
    // 任务管理-执行成功
    public function succ2() {
        $this->succ();
    }
    // 任务管理-执行失败
    public function fail2() {
        $this->fail();
    }
    // 订单管理-订单列表
    public function consumeList2() {
        $this->consumeList();
    }
    // 订单管理-退款列表
    public function refundList2() {
        $this->refundList();
    }
    // 版块商户-版块列表
    public function sectionList2() {
        $this->sectionList();
    }
    // 版块商户-商户列表
    public function merchantList2() {
        $this->merchantList();
    }
    // 版块商户-版块支付列表
    public function sectionPaymentList2() {
        $this->sectionPaymentList();
    }
}