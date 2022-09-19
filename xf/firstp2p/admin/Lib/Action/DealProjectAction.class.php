<?php
// +----------------------------------------------------------------------
// | 项目管理
// +----------------------------------------------------------------------
// | @author zhanglei5@ucfgroup.com
// +----------------------------------------------------------------------

use core\service\UserBankcardService;
use core\service\UserService;
use core\service\DealService;
use core\service\DealProjectService;
use core\service\ncfph\AccountService;
use core\service\DealProjectRiskAssessmentService;
use core\service\DealCompoundService;
use core\service\DealProjectCompoundService;
use core\service\BankService;
use core\service\ContractService;
use core\service\DealContractService;
use core\service\SendContractService;

use core\dao\DealLoanRepayModel;
use core\dao\JobsModel;
use core\dao\DealModel;
use core\dao\DealQueueInfoModel;
use core\dao\DealLoadModel;
use core\dao\DealProjectModel;
use core\dao\UserModel;
use core\dao\DealExtModel;
use core\dao\EnterpriseModel;

use libs\utils\Logger;
use libs\utils\Finance;

use NCFGroup\Protos\Contract\RequestSetProjectDescription;
use NCFGroup\Protos\Contract\RequestGetProjectDescription;
use NCFGroup\Protos\Contract\ResponseSetProjectDescription;
use NCFGroup\Protos\Contract\ResponseGetProjectDescription;


class DealProjectAction extends CommonAction{
    //  private $deal_type_list = array('0' => '普通标', '1' => '利滚利');
    const DEAL_TYPE_LGL = 1;
    // export csv max num
    const EXPORT_CSV_MAX_COUNT = 10000;

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    public function index($deal_type = 0)
    {
        //jira:4308 贷款类型默认为专享
        $_REQUEST['deal_type'] = isset($_REQUEST['deal_type']) ? intval($_REQUEST['deal_type']):2;

        $uids = array();
        $sortBy = '';
        if (intval($_REQUEST['pro_id'])) {  // 项目编号搜索
            $where_array[] = sprintf(' `id` = %d ', $_REQUEST['pro_id']);
        }

        if (trim($_REQUEST['pro_name'])) {    //增加项目名称搜索
            $where_array[] = "name like '%".trim($_REQUEST['pro_name'])."%'";
        }

        if (trim($_REQUEST['user_name'])) {    //增加项目名称搜索
            $condition = " `user_name` = ':user_name'";
            $user = new core\dao\UserModel();
            $rs = $user->findAllViaSlave($condition, true, 'id', array(":user_name" => $_REQUEST['user_name']));
            foreach($rs as $row) {
                $uids[] = $row['id'];
            }
        }

        if (trim($_REQUEST['real_name'])) {    //增加项目名称搜索
            $condition = " `real_name` = ':real_name'";
            $user = new core\dao\UserModel();
            $rs = $user->findAllViaSlave($condition, true, 'id', array(":real_name" => $_REQUEST['real_name']));
            foreach($rs as $row) {
                $ruids[] = $row['id'];
            }
            if (count($uids) > 0) {
                $uids = array_merge($uids,$ruids);
            } else {
                $uids = $ruids;
            }
        }

        if (trim($_REQUEST['real_name']) || trim($_REQUEST['user_name'])) {
            $where_array[] = "user_id in (".implode(',', $uids).") ";
        }

        if (trim($_REQUEST['user_id'])) {    //增加用户名id
            $where_array[] = "user_id = ".trim($_REQUEST['user_id']) ;
        }

        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $where_array[] = sprintf("approve_number = '%s'", addslashes(trim($_REQUEST['approve_number'])));
        }

        $deal_type = $this->getDealType($deal_type);

        if ($deal_type == self::DEAL_TYPE_LGL) {   // 利滚利项目
            $template = 'lgl_index';
        } else {
            if ($this->is_cn) {
                $template = 'index_cn';
            } else {
            	$template = 'index';
            	$this->assign('deal_type', $deal_type);
            }
        }

        $where_array[] = "deal_type= ".trim($deal_type) ;

        // 固定起息日
        if (!empty($_REQUEST['fixed_value_date_start']) && !empty($_REQUEST['fixed_value_date_end'])) {
            $where_array[] = sprintf(' fixed_value_date BETWEEN %d AND %d ', to_timespan($_REQUEST['fixed_value_date_start']), to_timespan($_REQUEST['fixed_value_date_end']));
        } else if (!empty($_REQUEST['fixed_value_date_start'])) {
            $where_array[] = sprintf(' fixed_value_date >= %d ', to_timespan($_REQUEST['fixed_value_date_start']));
        } else if (!empty($_REQUEST['fixed_value_date_end'])) {
            $where_array[] = sprintf(' fixed_value_date <= %d ', to_timespan($_REQUEST['fixed_value_date_end']));
        }

        // 业务状态
        if (isset($_REQUEST['business_status']) && 999 != $_REQUEST['business_status']) {
            $where_array[] = sprintf(' business_status = %d AND fixed_value_date > 0', intval($_REQUEST['business_status']));
        } else {
            $_REQUEST['business_status'] = 999;
        }

        $name = $this->getActionName ();
        $model = DI ( $name );

        $where = $where_array ? implode(' and ', $where_array) : '';
        if (isset ( $_REQUEST ['_order'] )) {
            $order = $_REQUEST ['_order'];
            if ($_REQUEST['_order'] == 'diff') {    // 项目里的 差额
                unset($_REQUEST['_order']);
                $order = "borrow_amount` - `money_loaned";
            }
        } else {
            $order = $model->getPk ();
        }
        $sort = 'desc';
        if (isset ( $_REQUEST ['_sort'] )) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        }
        // 取得满足条件的记录数
        $count = $model->where ( $where )->count ();
        if ($count > 0) {
            // 创建分页对象
            $listRows = '';
            if (! empty ( $_REQUEST ['listRows'] )){
                $listRows = $_REQUEST ['listRows'];
            }
            $p = new Page ( $count, $listRows );

            // 分页查询数据
            $voList = $model->where ( $where )->order ( "`" . $order . "` " . $sort )->limit ( $p->firstRow . ',' . $p->listRows )->findAll ();
            $format_list = $this->formatList($voList, $deal_type);
            $deal_pro_service = new DealProjectService();
            foreach($format_list as $k => $v){
                $format_list[$k]['status'] = ($v['status'] == 0) ? '正常' : '作废';
                $format_list[$k]['entrust_sign'] = ($v['entrust_sign'] == 0) ? '未委托' : '已委托';
                $format_list[$k]['entrust_agency_sign'] = ($v['entrust_agency_sign'] == 0) ? '未委托' : '已委托';
                $format_list[$k]['entrust_advisory_sign'] = ($v['entrust_advisory_sign'] == 0) ? '未委托' : '已委托';
                $format_list[$k]['is_entrust_zx'] = $deal_pro_service->isProjectEntrustZX($v['id']);
            }

            // 分页显示
            $page = $p->show ();

            // 列表排序显示
            $sortImg = $sort; // 排序图标
            $sortAlt = $sort == 'desc' ? l ( "ASC_SORT" ) : l ( "DESC_SORT" ); // 排序提示
            $sort = $sort == 'desc' ? 1 : 0; // 排序方式

            // 模板赋值显示
            $this->assign ( 'list', $format_list);
            $this->assign ( 'sort', $sort );
            $this->assign ( 'order', $order );
            $this->assign ( 'sortImg', $sortImg );
            $this->assign ( 'sortType', $sortAlt );
            $this->assign ( "page", $page );
            $this->assign ( "nowPage", $p->nowPage );
        }
        $this->assign('project_business_status', DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态
        $this->assign('isSvDown', \libs\payment\supervision\Supervision::isServiceDown()); // 存管是否降级

        $this->display ($template);
    }

    /**
     * 通知贷项目列表
     */
    public function compound_project(){
        $this->index(self::DEAL_TYPE_LGL);
    }

    /**
     * 受托支付银行信息修改
     */
    public function editBankInfo()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $project = M(MODULE_NAME)->where(array('id' => $id))->find();
        if (empty($project)) {
            $this->error('项目不存在');
        }

        if ($project['loan_money_type'] != 3) {
            $this->error('项目放款类型不是受托支付');
        }
        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData();

        $this->assign('vo', $project);
        $this->display();
    }

    /**
     * 开户行选择器模板数据
     */
    private function _assignBankzoneRegionData()
    {
        //二级地址
        $region_lv2 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where region_level = 2");
        foreach ($region_lv2 as $k => $v) {
            if ($v['id'] == intval($vo['province_id'])) {
                $region_lv2[$k]['selected'] = 1;
                break;
            }
        }
        $this->assign("region_lv2", $region_lv2);

        //三级地址
        $region_lv3 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where pid = " . intval($vo['province_id']));
        foreach ($region_lv3 as $k => $v) {
            if ($v['id'] == intval($vo['city_id'])) {
                $region_lv3[$k]['selected'] = 1;
                break;
            }
        }
        $this->assign("region_lv3", $region_lv3);

        //三级地址
        $n_region_lv3 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "region_conf where pid = " . intval($vo['n_province_id']));
        foreach ($n_region_lv3 as $k => $v) {
            if ($v['id'] == intval($vo['n_city_id'])) {
                $n_region_lv3[$k]['selected'] = 1;
                break;
            }
        }
        $this->assign("n_region_lv3", $n_region_lv3);

        //一级地区
        $n_region_lv1 = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "delivery_region where region_level = 1");
        $this->assign("n_region_lv1", $n_region_lv1);

        //银行列表
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign('bank_list', $bank_list);
    }

    /**
     * 受托支付银行信息保存
     */
    public function saveBankInfo()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $bankzone = isset($_REQUEST['bank_bankzone']) ? trim($_REQUEST['bank_bankzone']) : '';
        if ($bankzone === '') {
            $this->error('开户网点选择错误');
        }

        $cardName = isset($_REQUEST['card_name']) ? trim($_REQUEST['card_name']) : '';
        if ($cardName === '') {
            $this->error('账户名必须填写');
        }

        $bankId = isset($_REQUEST['bank_id']) ? intval($_REQUEST['bank_id']) : '';
        if ($bankId === 0) {
            $this->error('银行选择错误');
        }

        $bankcard = isset($_REQUEST['bankcard']) ? trim($_REQUEST['bankcard']) : '';
        if ($bankcard === '') {
            $this->error('卡号必须填写');
        }

        $data = array(
            'card_name' => $cardName,
            'bank_id' => $bankId,
            'bankzone' => $bankzone,
            'bankcard' => $bankcard,
        );

        $result = $GLOBALS['db']->update('firstp2p_deal_project', $data, "id={$id} and loan_money_type=3");
        if (!$result) {
            $this->error('修改失败');
        }

        $this->success('修改成功', 0, '?m=UserCarry&a=dealloanList');
    }

    /**
     * add  添加项目页
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @access public
     * @return void
     */
    public function add()
    {
        C('TOKEN_ON',true);
        $deal_type = 0;
        if (isset($_REQUEST['deal_type'])) {
            $deal_type = intval($_REQUEST['deal_type']);
            $template = 'lgl_add';
        } else {
            $template =  $this->is_cn ? 'add_cn':'add';
        }

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData();

        //从配置文件取公用信息
        $this->assign('loan_type', $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'] : $GLOBALS['dict']['LOAN_TYPE']);        //还款方式
        $this->assign('project_business_status', DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('deal_type', $deal_type);

        $this->assign("borrow_fee_type", $this->is_cn ? $GLOBALS['dict']['BORROW_FEE_TYPE_CN'] :$GLOBALS['dict']['BORROW_FEE_TYPE']); //费用收取方式
        $this->assign('loan_money_type', $this->is_cn ? $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'] : $GLOBALS['dict']['LOAN_MONEY_TYPE']); //放款方式
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);

        $this->display($template);
    }

    /**
     * _checkCompound
     * 检查通知贷项目相关参数
     * @access private
     * @return void
     */
    private function _checkCompound()
    {
        if (intval(trim($_REQUEST['redemption_period'])) <= 0) {
            $this->error("赎回期必须大于0");
        }
        if (!is_numeric($_REQUEST['redemption_period'])) {
            $this->error ( "赎回期必须为数字" );
        }

        if (!is_numeric($_REQUEST['lock_period'])) {
            $this->error ( "锁定期必须为数字" );
        }
    }
    /**
     * 创建标的
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function insert()
    {
        $_POST['bankzone'] = $_POST['bank_bankzone'];

        $deal_type = isset($_REQUEST['deal_type']) ? $_REQUEST['deal_type'] : 0;
        if ($deal_type == 1){
            $this->_checkCompound();
        }

        if(!empty($_REQUEST['contractDescription'])){
            $contractDescription = $_REQUEST['contractDescription'];
        }

        $userId = intval($_REQUEST['user_id']);
        $user = UserModel::instance()->findViaSlave($userId);
        if(empty($user)) {
            $this->error ( "获取用户信息失败!");
        }
        //是否是关联方
        if (UserModel::USER_TYPE_NORMAL == $user['user_type']) {
            $idno = $user['idno'];
            $userType = 1;
        } elseif (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
            $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userId);
            $idno = $enterpriseInfo['credentials_no'];
            $userType = 0;
        }
        $ncfphAccountService = new AccountService();
        $isRelated = $ncfphAccountService->checkRelatedUser($idno,$userType);
        if($isRelated == 1 ) {
            $this->error ( "该借款人为关联方，不能借款!");
        } else if($isRelated == -1 )  {
            $this->error ( "查询关联方信息超时，请稍后再试!");
        }

        $m = M(MODULE_NAME);
        //开始验证有效性
        $data = $m->create();
        $data['fixed_value_date'] = empty($data['fixed_value_date']) ? 0 : to_timespan($data['fixed_value_date']);
        if ($data['fixed_value_date'] && to_date($data['fixed_value_date'], 'Ymd') < to_date(get_gmtime(), 'Ymd')) {
            $this->error('“起息日”应当大于当前时间');
        }

        $return = $this->getCntByName($data['name']);
        if($return['cnt'] > 0) {
            $this->error ( "项目名称不能重复!");
        }else{
            $projectId= $this->_insertCompound($data);
            if(!empty($contractDescription)){
                $request = new RequestSetProjectDescription();
                $request->setProjectId($projectId);
                $request->setType(1);
                $request->setSourceType(0);
                $request->setContent($contractDescription);

                $response = $this->getRpc('contractRpc')->callByObject(array(
                    'service' => "\NCFGroup\Contract\Services\Category",
                    'method' => "setProjectDescription",
                    'args' => $request,
                ));
            }
        }

        save_log('新增项目'.$data['name'].L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));

        $this->redirect(u(MODULE_NAME."/".($deal_type == 0 ? 'index' : 'compound_project')));
    }

    /**
     * 编辑项目
     */
    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = DealProjectModel::instance()->find($id);
        if ($vo['deal_type'] == self::DEAL_TYPE_LGL) {
            $compound = M('DealProjectCompound')->where(array('project_id'=>$vo['id']))->find();
            $vo['day_rate'] = DealCompoundService::convertRateYearToDay($vo['rate'], $compound['redemption_period']);
            $vo['lock_period'] = $compound['lock_period'];
            $vo['redemption_period'] = $compound['redemption_period'];
            $template = 'lgl_edit';
        } else {
            $template =  $this->is_cn ? 'edit_cn':'edit';
        }

        $vo['fixed_value_date'] = $vo['fixed_value_date'] == 0 ? '' : to_date($vo['fixed_value_date'], 'Y-m-d');
        $vo['is_online'] = ($vo['business_status'] >= DealProjectModel::$PROJECT_BUSINESS_STATUS['process']); // 标识项目是否有标的上线过
        $vo['can_edit'] = ($vo['business_status'] < DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying']); // 还款中之前的状态，可以编辑项目信息

        //获取委托投资说明
        $contract = '';
        $request = new RequestGetProjectDescription();
        $request->setProjectId(intval($id));
        $request->setType(1);
        $request->setSourceType(0);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getProjectDescription",
            'args' => $request,
        ));

        if(count($response->data)> 0){
            $contract = $response->data['content'];
        }

        // 银行卡类型
        $cardTypes = array(
                array('id' => \core\dao\UserBankcardModel::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
                array('id' => \core\dao\UserBankcardModel::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign('card_types',$cardTypes);
        if (!empty($vo['risk_bearing'])){
            $risk_service = new DealProjectRiskAssessmentService();
            $risk_info = $risk_service->getAssesmentNameById($vo['risk_bearing'], 1);
            $vo['risk_name'] = empty($risk_info['name']) ? '' : $risk_info['name'];
            $vo['risk_describe'] = empty($risk_info['describe']) ? '' : $risk_info['describe'];
            $vo['business_status'] = intval($vo['business_status']);
        }

        $loan_money_type = $this->is_cn ? $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'] : $GLOBALS['dict']['LOAN_MONEY_TYPE'];
        $loan_type  = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'] : $GLOBALS['dict']['LOAN_TYPE'];
        $this->assign('contract',$contract);

        $this->assign ( 'vo', $vo );
        //从配置文件取公用信息
        $this->assign('loan_type', $loan_type);        //还款方式
        $this->assign("borrow_fee_type", $this->is_cn ? $GLOBALS['dict']['BORROW_FEE_TYPE_CN'] : $GLOBALS['dict']['BORROW_FEE_TYPE']); //费用收取方式
        $this->assign('loan_money_type', $loan_money_type); //放款方式
        $this->assign('project_business_status', DealProjectModel::$PROJECT_BUSINESS_STATUS); // 项目的业务状态
        $this->assign('project_business_status_map', DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态 名字映射

        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData();

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->display($template);
    }

    public function show() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        if ($vo['deal_type'] == self::DEAL_TYPE_LGL) {
            $compound = M('DealProjectCompound')->where(array('project_id'=>$vo['id']))->find();
            $vo['day_rate'] = DealCompoundService::convertRateYearToDay($vo['rate'], $compound['redemption_period']);
            $vo['lock_period'] = $compound['lock_period'];
            $vo['redemption_period'] = $compound['redemption_period'];
            $template = 'lgl_edit';
        } else {
            $template = 'edit';
        }
        echo $vo['intro'];
    }

    /**
     * 修改保存项目
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function save()
    {
        $_POST['bankzone'] = $_POST['bank_bankzone'];

        if(!empty($_REQUEST['contractDescription'])){
            $contractDescription = $_REQUEST['contractDescription'];
        }
        unset($_REQUEST['contractDescription']);
        // 产品结构、风险说明、承受能力不做更新
        unset($_POST['product_mix_1'],$_POST['product_mix_2'],$_POST['product_mix_3'],$_POST['none_name'],$_POST['none_name2']);
        $m = M(MODULE_NAME);
        $data = $m->create ();
        $vo = $m->where(array('id' => $data['id']))->find();

        if($vo['status'] == 0 && $data['status'] == 1){
           $this->check_project($data['id']);
        }

        if($vo['status'] == 1 && $data['status'] == 0){
            $data['status'] = 1;
        }
        // 转换时间变量
        $data['fixed_value_date'] = trim($data['fixed_value_date']) == '' ? 0 : to_timespan($data['fixed_value_date']);
        if ($data['fixed_value_date'] && to_date($data['fixed_value_date'], 'Ymd') < to_date(get_gmtime(), 'Ymd')) {
            $this->error('“起息日”应当大于当前时间');
        }

        $m->startTrans();
        if ($vo['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {    // 利滚利项目
            $this->_checkCompound();
            try {
                $compound_data = $this->_getCompound($vo['id']);
                M('DealProjectCompound')->where("project_id={$vo['id']}")->save($compound_data);
                //确认修改
                $rs = $m->save($data);

                if(!empty($contractDescription)){
                    $request = new RequestSetProjectDescription();
                    $request->setProjectId($vo['id']);
                    $request->setType(1);
                    $request->setSourceType(0);
                    $request->setContent($contractDescription);

                    $response = $this->getRpc('contractRpc')->callByObject(array(
                        'service' => "\NCFGroup\Contract\Services\Category",
                        'method' => "setProjectDescription",
                        'args' => $request,
                    ));
                }

                $m->commit();
            } catch (Exception $e) {
                $m->rollback();
                $dbErr = M()->getDbError();
                save_log('编辑利滚利项目'.$data['name'].L("INSERT_FAILED").$dbErr, C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
                $this->error(L("INSERT_FAILED").$dbErr);
            }

        } else {
            //普通项目修改
            try {
                // 专享项目满标，业务状态更新
                $pro_service = new DealProjectService();
                $full_money = DealModel::instance()->getFullDealsMoneySumByProjectId($vo['id']);
                $is_entrust_zx = $pro_service->isProjectEntrustZX($vo['id']);

                //判断是否为调整项目总金额触发满标待审核到募集中的状态变更
                if($is_entrust_zx && ($vo['business_status'] == DealProjectModel::$PROJECT_BUSINESS_STATUS['full_audit']) && ($data['borrow_amount'] > $full_money)){
                    $data['business_status'] = DealProjectModel::$PROJECT_BUSINESS_STATUS['process'];
                }

                if ($is_entrust_zx && DealProjectModel::$PROJECT_BUSINESS_STATUS['process'] == $vo['business_status'] && 0 == bccomp($data['borrow_amount'], $full_money, 2)) {
                    $data['business_status'] = DealProjectModel::$PROJECT_BUSINESS_STATUS['full_audit'];
                }

                $rs = $m->save($data);
                if(!empty($contractDescription)){
                    $request = new RequestSetProjectDescription();
                    $request->setProjectId(intval($vo['id']));
                    $request->setType(1);
                    $request->setSourceType(0);
                    $request->setContent($contractDescription);

                    $response = $this->getRpc('contractRpc')->callByObject(array(
                        'service' => "\NCFGroup\Contract\Services\Category",
                        'method' => "setProjectDescription",
                        'args' => $request,
                    ));
                }
                $m->commit();
                Logger::info(sprintf('project save success,new-data：%s,business_status：%d,full_deal_money：%s,is_entrust_zx:%d,file：%s, line:%s', json_encode($data), $vo['business_status'], $full_money, $is_entrust_zx, __FILE__, __LINE__));
            } catch (Exception $e) {
                echo $e->getMessage();
                $m->rollback();

                $dbErr = M()->getDbError();
                save_log('编辑普通项目'.$data['name'].L("INSERT_FAILED").$dbErr, C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
                $this->error(L("INSERT_FAILED").$dbErr);
            }
        }

        $this->redirect(u(MODULE_NAME."/".($vo['deal_type'] == 1 ? 'compound_project' : 'index')));
    }

    /**
     * 删除标的
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

            $list = M(MODULE_NAME)->where ( $condition )->delete();
            if ($list!==false) {
                save_log(l("DELETE_SUCCESS"),1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log(l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 根据项目名查找 是否有重复的  cnt > 0 则代表有重复,如果有id 则代表是在编辑页面查询
     * @author zhanglei5@ucfgroup.com
     */
    function getCntByName() {
        $return= array("status"=>0,"message"=>"",'cnt'=>0);
        $m = M(MODULE_NAME);
        $ajax = intval($_REQUEST['ajax']);
        if($ajax) {
            $name = $_REQUEST ['name'];
            $id = $_REQUEST ['id'];
        }else {
            $args = func_get_args();
            $name = $args[0];
            $id = isset($args[1]) ? $args[1] : 0;
        }
        if($name==''){
            return ajax_return($return);
        }
        $condition = array('name'=>$name);
        if(isset($id) && $id > 0 ) {
            $condition['id'] = array('NEQ',$id);
        }

        $cnt = $m->where($condition)->count();
        if($cnt > 0) {
            $return['status'] = 1;
            $return['cnt'] = $cnt;
        }
        if($ajax) {
            return ajax_return($return);
        }else {
            return $return;
        }

    }

    public function checkSubmit() {
        $data['bank_auth'] = 0;
        $data['user_auth'] = 0;
        $data['amount_auth'] = 0;
        $return= array("status"=>0,"message"=>"","data"=>$data);
        $user_id = $_REQUEST ['user_id'];
        $borrow_amount = $_REQUEST ['borrow_amount'];  //借款金额
        $service = new core\service\UserBankcardService();
        $bank = $service->getBankcard($user_id);
        if(intval($bank['status']) == 1) {
            $data['bank_auth'] = 1;
        }

        $service = new core\service\UserService();
        $user = $service->getUserViaSlave($user_id);
        $data['user_auth'] = $user['idcardpassed'] == 1 ? 1 : 0;

        if($borrow_amount < app_conf ( 'MIN_BORROW_QUOTA' ) || $borrow_amount > app_conf ( 'MAX_BORROW_QUOTA' )) {
            $return['message'] = ( "‘借款金额’应为" . app_conf ( 'MIN_BORROW_QUOTA' ) . "至" . app_conf ( 'MAX_BORROW_QUOTA' ) . "的整数！" );
        }else {
            $data['amount_auth'] = 1;
        }

        if($data['bank_auth'] == 1 && $data['user_auth'] == 1 && $data['amount_auth'] == 1) {
            $return['status'] = 1;
        }
        $return['data'] = $data;
        return ajax_return($return);
    }

    public function checkSave() {
        $return= array("status"=>0,"message"=>"");
        $pro_id = $_REQUEST['id'];
        $borrow_amount = $_REQUEST ['borrow_amount'];  //借款金额
        $ds = new core\service\DealService();
        $deal_status = array(0,1,2,4,5);    // 检查是否线上开始募集子标 （进行中、满标、还款中）
        $list = $ds->getDealByProId($pro_id,$deal_status);
        $data['edit_user'] = 0;
        if(count($list) == 0) {
            $data['edit_user'] = 1;
        }

        $list = $ds->getDealByProId($pro_id,$deal_status);
        $sum = 0; $dids = array();
        foreach($list as $k => $v) {
            $sum += $v['borrow_amount'];
            $dids[] = $v['id'];
        }

        $data['amount_auth'] = 0;
        if($borrow_amount < app_conf ( 'MIN_BORROW_QUOTA' ) || $borrow_amount > app_conf ( 'MAX_BORROW_QUOTA' )) {
            $return['message'] = ( "‘借款金额’应为" . app_conf ( 'MIN_BORROW_QUOTA' ) . "至" . app_conf ( 'MAX_BORROW_QUOTA' ) . "的整数！" );
        }else {
            $data['amount_auth'] = 1;
        }

        $data['sum'] = $sum;
        $data['dids'] = $dids;
        $return['status'] = 1;
        $return['data'] = $data;
        return ajax_return($return);
    }

    public function formatList($list, $deal_type) {
        $compound_service = new DealCompoundService();

        foreach ($list as &$row ) {
            $row['diff'] = $row['borrow_amount'] - $row['money_loaned'];
            if ($deal_type == 1) {  //  利滚利
                $row['list_name'] = $row['user_id']." <br/> ".get_user_name($row['user_id'])." <br/> ".get_user_name($row['user_id'], 'real_name');
                $row['rate_day'] = $compound_service->getDayRateByDealId($row['id']);
            }

            $new_list[$row['id']] = $row;
            $ids[] = $row['id'];
        }
        // 根据id 到compoun表找到利滚利项目关联信息
        if ($deal_type == 1) {
            $map['project_id'] = array('IN',implode(',' , $ids));
            $lgl_list = M('DealProjectCompound')->where($map)->select();
            $project_compound_service = new DealProjectCompoundService();
            foreach($lgl_list as $row) {
                $project_id = $row['project_id'];
                /*
                //已赎回本金 这儿可能会查询比较多
                $new_list[$row['project_id']]['redeemed_principal'] = $project_compound_service->getPayedProjectCompoundPrincipal($project_id);
                // 赎回中本金
                $new_list[$row['project_id']]['redeeming_principal'] = $project_compound_service->getUnpayedCompoundPrincipal($project_id);
                // 已赎回利息
                $new_list[$row['project_id']]['redeemed_interest'] = $project_compound_service->getPayedCompoundInterest($project_id);
                // 赎回中利息
                $new_list[$row['project_id']]['redeeming_interest'] = $project_compound_service->getUnpayedCompoundInterest($project_id);
                */

                $new_list[$row['project_id']]['lock_period'] = $row['lock_period'];
                $new_list[$row['project_id']]['redemption_period'] = $row['redemption_period'];
            }
        }

        return $new_list;
    }

    private function _insertCompound($pdata) {
        $m = M('DealProjectCompound');
        $m->startTrans();
        try {
            $project_id = M('DealProject')->add ($pdata);

            if ($pdata['deal_type'] == self::DEAL_TYPE_LGL) {
                $compound_data = $this->_getCompound($project_id);
                $rs = $m->add($compound_data);
            }
            $m->commit();
        } catch (Exception $e) {
            $m->rollback();
            $dbErr = M()->getDbError();
            save_log('新增利滚利项目'.$data['name'].L("INSERT_FAILED").$dbErr, C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
            $this->error(L("INSERT_FAILED").$dbErr);
        }

        return $project_id;
    }

    private function _getCompound($project_id) {
        $data['project_id'] = $project_id;
        $data['lock_period'] = $_POST['lock_period'];
        $data['redemption_period'] = $_POST['redemption_period'];
        return $data;
    }


    /**
     * convertRateYearToDay
     * 年化转日化 利率
     *
     * @access public
     * @return float
     */
    public function convertRateYearToDay() {
        $rate = $_REQUEST['rate'];
        $redemption_period = $_REQUEST['redemption_period'];
        $data['day_rate'] = DealCompoundService::getDayRateByYearRate($rate, $redemption_period);
        return ajax_return($data);
    }


    /**
     * yuqi
     * 通知贷逾期的标的列表
     *
     * @access public
     * @return void
     */
    public function yuqi() {
        $deal_compound_service = new DealCompoundService();
        // 取得总的记录数
//        $count = $deal_compound_service->getDelayCount();
//        if ($count > 0) {
            // 创建分页对象
//            $listRows = '';
//            if (! empty ( $_REQUEST ['listRows'] )){
//                $listRows = $_REQUEST ['listRows'];
//            }
//            $p = new Page ( $count, $listRows );
        // 分页查询数据
        $rs = $deal_compound_service->getDelayList();
        foreach ($rs as $row) {
            $row['list_name'] = "{$row['borrow_user_id']}/".getListUser($row['borrow_user_id'],$row['user_name'])."<br>".getListUser($row['borrow_user_id'],$row['real_name'])." <br>". getListUser($row['borrow_user_id'],$row['mobile']);
            $row['repay_time'] = getRepayTime($row['repay_time'], $row['loantype']);
            $row['loan_type']= get_loantype($row['loantype']);
            $row['borrow_amount']= format_price($row['borrow_amount'], false);
            $row['repay_date'] = date('Y-m-d',get_gmtime());
            $deal_compound = $deal_compound_service->getDealCompound($row['deal_id']);
            $row['redemption_period'] = $deal_compound['redemption_period'];
            $list[] = $row;
        }
        // 分页显示
//            $page = $p->show ();


        $this->assign ('list', $list);
//        $this->assign ( "page", $page );
//        $this->assign ( "nowPage", $p->nowPage );

        $this->display('lgl_yuqi');

    }

    public function repayCompound() {
        $deal_id = intval($_REQUEST['deal_id']);

        $data = array('rs' => 0 ,'msg' => '强制还款失败');
        if ($deal_id > 0) {
            $GLOBALS['db']->startTrans();
            $job = new JobsModel();
            $param = array('deal_id'=>$deal_id);
            $deal = new DealModel();

            try{
                $job->priority = 80;
                $rs = $job->addJob('\core\service\DealCompoundService::repayCompound', $param);
                $row = $deal->find($deal_id);
                $row['is_during_repay'] = 1;
                $row->save();
                $GLOBALS['db']->commit();
                $arr_log = array(__CLASS__, __FUNCTION__, $deal_id, "succ");
                Logger::info(implode(" | ", $arr_log));
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $arr_log = array(__CLASS__, __FUNCTION__, $deal_id, "fail", $e->getMessage());
                Logger::info(implode(" | ", $arr_log));
                $this->error('强制还款失败！');
                return false;
            }
        } else {
            $this->error('强制还款失败！');
        }
        $this->success('强制还款成功！');
    }

    /**
     * 利滚利项目还款计划
     */
    public function compound_repay_schedule() {
        $project_id = intval($_REQUEST['id']);
        if (empty($project_id)) {
            $this->error('参数错误！');
        }
        $deal_project_compound_service = new DealProjectCompoundService();
        $result = $deal_project_compound_service->getRepaySchedule($project_id);
        $this->assign("list", $result);
        $this->display();
    }

    //近日还款不足项目
    public function compound_repay_less(){
        //搜索条件
        $map['deal_type'] = 1;

        if(trim($_REQUEST['name']) != ''){
            $map['name'] = array('like','%'.addslashes(trim($_REQUEST['name'])).'%');
        }

        if(intval($_REQUEST['user_id']) >= 0){
            $map['user_id'] = intval($_REQUEST['user_id']);
        }

        $deal_compound = new DealCompoundService();
        $next_repay_time = $deal_compound->getNextRepayDate();

        $user_list = array();
        if($next_repay_time){
            $user_list = $deal_compound->getMoneyLessBorrower($next_repay_time);
        }

        if(empty($user_list)){
            $this->assign('list', array());
        }else{
            $map['user_id'] = array('in', implode(',', array_keys($user_list)));
            if (method_exists ( $this, '_filter' )) {
                $this->_filter ( $map );
            }
            $model = D ("DealProject");
            $this->_list ( $model, $map );
            $list = $this->get('list');

            foreach($list as &$row){
                $row = $row + $user_list[$row['user_id']];
                $row['repay_money_all'] = DealLoanRepayModel::instance()->getLglTotalMoneyByProjectId($row['id'], $next_repay_time);
                $deal_compound = M('DealProjectCompound')->where(array('project_id' => $row['id']))->find();
                $row['redemption_period'] = $deal_compound['redemption_period'];
            }
            $this->assign('next_repay_date', to_date($next_repay_time, 'Y-m-d'));
            $this->assign('list', $list);
        }
        $this->display();
    }

    /**
     * 导出csv
     */
    function export_csv(){
       self::log(array(__FUNCTION__, __LINE__, json_encode($_REQUEST)));
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $memory_start = memory_get_usage();
        $uids = array();
        $sortBy = '';
        if (trim($_REQUEST['pro_name'])) {    //增加项目名称搜索
            $where_array[] = "name like '%".trim($_REQUEST['pro_name'])."%'";
        }

        if (trim($_REQUEST['user_name'])) {    //增加项目名称搜索
            $condition = " `user_name` = ':user_name'";
            $user = new core\dao\UserModel();
            $rs = $user->findAllViaSlave($condition, true, 'id', array(":user_name" => $_REQUEST['user_name']));
            foreach($rs as $row) {
                $uids[] = $row['id'];
            }
        }

        if (trim($_REQUEST['real_name'])) {    //增加项目名称搜索
            $condition = " `real_name` = ':real_name'";
            $user = new core\dao\UserModel();
            $rs = $user->findAllViaSlave($condition, true, 'id', array(":real_name" => $_REQUEST['real_name']));
            foreach($rs as $row) {
                $ruids[] = $row['id'];
            }
            if (count($uids) > 0) {
                $uids = array_merge($uids,$ruids);
            } else {
                $uids = $ruids;
            }
        }

        if (trim($_REQUEST['real_name']) || trim($_REQUEST['user_name'])) {
            $where_array[] = "user_id in (".implode(',', $uids).") ";
        }

        if (trim($_REQUEST['user_id'])) {    //增加用户名id
            $where_array[] = "user_id = ".trim($_REQUEST['user_id']) ;
        }
        // 标类型
        $deal_type = !empty($_REQUEST['deal_type']) ? intval($_REQUEST['deal_type']) : 0;
        $where_array[] = "deal_type=" . $deal_type;

        // 固定起息日
        if (!empty($_REQUEST['fixed_value_date_start']) && !empty($_REQUEST['fixed_value_date_end'])) {
            $where_array[] = sprintf(' fixed_value_date BETWEEN %d AND %d ', to_timespan($_REQUEST['fixed_value_date_start']), to_timespan($_REQUEST['fixed_value_date_end']));
        } else if (!empty($_REQUEST['fixed_value_date_start'])) {
            $where_array[] = sprintf(' fixed_value_date >= %d ', to_timespan($_REQUEST['fixed_value_date_start']));
        } else if (!empty($_REQUEST['fixed_value_date_end'])) {
            $where_array[] = sprintf(' fixed_value_date <= %d ', to_timespan($_REQUEST['fixed_value_date_end']));
        }

        // 业务状态
        if (isset($_REQUEST['business_status']) && 999 != $_REQUEST['business_status']) {
            $where_array[] = sprintf(' business_status = %d AND fixed_value_date > 0', intval($_REQUEST['business_status']));
        } else {
            $_REQUEST['business_status'] = 999;
        }

        $name = $this->getActionName ();
        $model = DI ( $name );

        $where = $where_array ? implode(' and ', $where_array) : '';

        $order = $model->getPk ();
        $sort = 'desc';

        // 取得满足条件的记录数
        $count = $model->where ( $where )->count ();

        if ($count > self::EXPORT_CSV_MAX_COUNT){
            $this->error("每次导出条数不能超过".self::EXPORT_CSV_MAX_COUNT."条，目前为{$count}条，请增加筛选条件缩小范围");
        }
        $page_size = 5000;
        $start = 0;
        $datatime = date("YmdHis", get_gmtime());
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=deal_project_{$datatime}.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        if (!$this->is_cn){
            $title = array('项目编号', '项目名称', '期限', '还款方式',
            '借款综合成本(年化)', '借款人会员id', '借款人会员名称', '借款人会员姓名', '借款总额', '已上标金额',
            '待上标金额', '已投资金额', '差额', '放款审批单编号', '项目授信额度','用户类型','固定起息日','业务状态'
            );
        }else{
            $title = array('项目编号', '项目名称', '期限', '还款方式', '借款人会员id', '借款人会员名称', '借款人会员姓名', '借款总额', '已上标金额','待上标金额', '已出借金额', '差额', '放款审批单编号', '项目授信额度','用户类型');
        }
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $title);
        $deal_pro_service = new DealProjectService();
        while (true) {
            $voList = $model->where($where)->order("`" . $order . "` " . $sort)->limit($start . ',' . $page_size)->findAll();
            if (empty($voList)){
                break;
            }
            $format_list = $this->formatList($voList, 0);
            $start += $page_size;
            foreach($format_list as $v){
                $row = array();
                $row[] = $v['id'];
                $row[] = $v['name'];
                $row[] = ($v['loantype'] == 5) ? $v['repay_time'].'天' :$v['repay_time']. '个月';
                $row[] =  $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loantype']]: $GLOBALS['dict']['LOAN_TYPE'][$v['loantype']];
                if (!$this->is_cn) {
                    $row[] = $v['rate'].'%';
                }
                $row[] = $v['user_id'];
                $row[] = get_user_name($v['user_id']);
                $row[] = get_user_name($v['user_id'],'real_name');

                $row[] = format_price($v['borrow_amount'],false);
                $row[] = format_price($v['money_borrowed'],false);
                $row[] = format_price($v['borrow_amount']-$v['money_borrowed'],false);
                $row[] = format_price($v['money_loaned'],false);
                $row[] = format_price($v['diff'],false);
                $row[] = $v['approve_number'];
                $row[] = $v['credit'];

                // JIRA#FIRSTPTOP-3260 企业账户二期功能 fanjingwen@
                $row[] = getUserTypeName($v['user_id']);
                /*$row[] = $deal_pro_service->isProjectEntrustZX($v['id']) ? to_date($v['fixed_value_date'], 'Y-m-d') : '--'; // 固定起息日
                $row[] = $deal_pro_service->isProjectEntrustZX($v['id']) ? getProjectBusinessStatusNameByValue($v['business_status']) : '--'; // 业务状态*/

                foreach ($row as &$item) {
                    $item = iconv("utf-8", "gbk//IGNORE", strip_tags($item));
                }
                fputcsv($fp, $row);
                unset($row);
            }
            $list_count = count($format_list);
            unset($format_list);
            $memory_end = memory_get_usage();
            $memory_use = intval(($memory_end - $memory_start) / (1024 * 1024)) . "mb";
            self::log(array(__FUNCTION__, __LINE__, $list_count, $memory_use));
        }

        exit;

    }

    public function copy() {
        $id = intval($_GET['id']);
        $ajax = intval($_GET['ajax']);

        if ($id) {
            $dp_service = new DealProjectService();
            $rs = $dp_service->copyDealProject($id);
            if ($rs) {
                save_log('复制项目 id:'.$id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
                $this->success('已成功复制',$ajax);
                exit;
            }
        }

        save_log('复制项目 id:'.$id,C('FAILED'), '', '', C('SAVE_LOG_FILE'));
        $this->error('操作失败',$ajax);
    }

    /**
     * 记录日志
     */
    protected static function log($log) {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $admin_name = $adm_session['adm_name'];
        $log = array_merge(array(__CLASS__, APP, $admin_name), $log);
        logger::info(implode(" | ", $log));
    }

    /**
     * 操作批量变更页面
     */
    public function batch_update()
    {
        $this->assign('main_title', '批量更新项目信息');
        $template = $this->is_cn ? 'batch_update_cn' : 'batch_update';
        $this->display($template);
    }

    /**
     * 导出批量修改 csv 模板
     */
    function get_batch_update_csv_tpl()
    {
        header('Content-Type: text/csv;charset=utf8');
        header("Content-Disposition: attachment; filename=batch_update_project_template.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        if ($this->is_cn) {
            $title = array('项目id', '借款总额', '项目授信额度', '状态');
        } else {
            $title = array('项目id', '借款总额', '项目授信额度', '借款综合成本', '状态');
        }
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }

        fputcsv($fp, $title);
        exit;
    }

    /**
     * 执行批量变更
     */
    public function do_batch_update()
    {
        try {
            // 判断是否为 csv
            $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
            if(!in_array($_FILES['batch_update_file']['type'], $mimes)) {
                throw new \Exception('上传文件需为 csv 格式！');
            }

            if (false === ($handle = fopen($_FILES['batch_update_file']['tmp_name'], 'r'))) {
                throw new \Exception('csv 文件打开失败！');
            }

            $success_collection = array();
            $fail_collection = array();
            $is_header = true;
            while(false !== ($data = fgetcsv($handle))) {
                if (!$this->is_cn) {
                    // 固定项目修改信息只能是5列
                    if (5 != count($data)) {
                        throw new \Exception('数据格式错误：此 csv 只能是5列！');
                    }
                }

                // 约定第一行为表头，跳过
                if ($is_header) {
                    $is_header = false;
                    continue;
                }

                if ($this->is_cn) {
                    list($new_data['id'],
                        $new_data['borrow_amount'],
                        $new_data['credit'], // 授信额度
                        $new_data['status'],
                    ) = $data; // 赋值给对应项
                } else {
                    list($new_data['id'],
                        $new_data['borrow_amount'],
                        $new_data['credit'], // 授信额度
                        $new_data['rate'],
                        $new_data['status'],
                    ) = $data; // 赋值给对应项

               }
                if (empty($new_data['id'])) {
                    Logger::error(sprintf('项目id 为空，，参数：%s，file：%s, line:%s', json_encode($new_data), __FILE__, __LINE__));
                    continue;
                }

                $project_obj = DealProjectModel::instance()->findViaSlave($new_data['id']);
                if (empty($project_obj)) {
                    $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'project_id'=>$new_data['id'],
                            'fail_msg'=>'项目不存在',
                        );
                    Logger::error(sprintf('项目信息更新失败，项目id：%d，失败原因：没有此项目，file：%s, line:%s', $new_data['id'], __FILE__, __LINE__));
                    continue;
                }

                //获取项目下的所有标的
                $deal_list = DealModel::instance()->getDealByProId($new_data['id']);
                $deal_invalid = false;//项目下标的状态不满足变更条件
                $total_borrow_amount = 0; //项目下标的总借款金额
                foreach ($deal_list as $deal) {
                    //判断项目下标的“投资状态”是否是“等待确认”
                    if($deal['deal_status'] <> 0) {
                        $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'project_id'=>$new_data['id'],
                            'fail_msg'=>'投资状态不是等待确认',
                        );
                        $deal_invalid = true;
                        break;
                    }

                    //判断项目下标的“投资列表” 是否为“空”
                    $totalBid = DealLoadModel::instance()->getLoadCount($deal['id']);
                    if($totalBid['buy_count'] > 0) {
                        $fail_collection[] = array(
                            'id'=>count($fail_collection) + 1,
                            'project_id'=>$new_data['id'],
                            'fail_msg'=>'投资列表不为空',
                        );
                        $deal_invalid = true;
                        break;
                    }
                    $total_borrow_amount = bcadd($total_borrow_amount,$deal['borrow_amount'],2);
                }

                //当需要变更“借款总额”时，需进行以下判断
                if(!empty($new_data['borrow_amount']) && (bccomp($total_borrow_amount , $new_data['borrow_amount'],2) == 1)) {
                    //判断“借款总额”是否大于等于“借款金额”
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'project_id'=>$new_data['id'],
                        'fail_msg'=>'项目借款总额<项目借款金额',
                    );
                    $deal_invalid = true;
                }

                if($deal_invalid) {
                    continue;
                }

                // 将非 '' 值的项 赋值给项目信息
                foreach ($new_data as $key => $value) {
                    if ('' === $value || is_null($value) || 'id' == $key) {
                        continue;
                    } elseif ($project_obj->offsetExists($key)) {
                        $project_obj->$key = addslashes($value);
                    }
                }

                if (false === $project_obj->save()) {
                    $fail_collection[] = array(
                        'id'=>count($fail_collection) + 1,
                        'project_id'=>$new_data['id'],
                        'fail_msg'=>'项目信息更新失败',
                    );
                    Logger::error(sprintf('项目信息更新失败，项目id：%d，失败原因：save 保存出错，file：%s, line:%s', $new_data['id'], __FILE__, __LINE__));
                    continue;
                } else {
                    $success_collection[] = $new_data['id'];
                    Logger::info(sprintf('项目信息更新成功，更新内容：%s，file：%s, line:%s', json_encode($new_data), __FILE__, __LINE__));
                }
            }
        } catch (\Exception $e) {
            Logger::error(sprintf('%s，上传文件名：%s，file：%s, line:%s', $e->getMessage(), $_FILES['batch_update_file']['name'], __FILE__, __LINE__));
            $this->error($e->getMessage());
            return;
        }

        $this->assign('main_title', '项目信息更新结果');
        $this->assign('file_name', $_FILES['batch_update_file']['name']);
        $this->assign('success_count', count($success_collection));
        $this->assign('fail_count', count($fail_collection));
        $this->assign('fail_collection', $fail_collection);
        $this->display();
    }

    /**
     * 操作项目放款表单
     */
    public function lent()
    {
        $this->assign('role', $this->getRole());
        $this->assign('return_type_list', self::$returnTypes);
        $this->assign('readonly', $_REQUEST['readonly']);// 审核中或者审核通过的不可以被编辑

        $project_id = intval($_REQUEST['id']);
        $project_info = DealProjectModel::instance()->findViaSlave($project_id);
        $deal_info = DealProjectModel::instance()->getFirstDealByProjectId($project_id);
        $user_info = UserModel::instance()->findViaSlave($project_info['user_id']);
        $this->assign('project', $project_info);
        $this->assign('can_lent', to_date($project_info['fixed_value_date'], 'Ymd') <= to_date(get_gmtime(), 'Ymd'));
        $this->assign('deal', $deal_info);
        $this->assign('user_info',$user_info);

        $this->assign('loan_money_type', $GLOBALS['dict']['LOAN_MONEY_TYPE']); //放款方式
        $bank_serv = new BankService();
        $bank_list = $bank_serv->getAllByStatusOrderByRecSortId();
        $this->assign("bank_list", $bank_list);

        // 计算还款期数
        $repay_times = $deal_info->getRepayTimes();
        $this->assign('repay_times', $repay_times);

        // 借款期限
        if($deal_info['loantype'] == 5) {
            $repay_time =   $deal_info['repay_time'] . "天";
        } else {
            $repay_time = $deal_info['repay_time'] . "月";
        }
        $this->assign('repay_time', $repay_time);

        // 计算服务费
        $loan_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['loan_fee_rate'], $deal_info['repay_time'], false);
        $consult_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['consult_fee_rate'], $deal_info['repay_time'], false);
        $guarantee_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['guarantee_fee_rate'], $deal_info['repay_time'], false);
        $pay_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['pay_fee_rate'], $deal_info['repay_time'], false);
        $canal_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['canal_fee_rate'], $deal_info['repay_time'], false);

        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal_info['id']);
        $fee_info = $this->getFeeInfo($project_id);
        $this->assign('deal_ext', $deal_ext);
        $this->assign('fee_info', $fee_info);
        $this->assign('repay_start_time', empty($deal_info['repay_start_time']) ? '' :to_date($deal_info['repay_start_time'], 'Y-m-d'));
        $this->assign('today', date('Y-m-d'));
        $this->assign('redirectUrl', empty($_SESSION['lastDealProjectLoanUrl']) ? '?m=DealProjectLoan' : $_SESSION['lastDealProjectLoanUrl']);
        $this->display();
    }

    /**
     * 汇总项目下标的提前还款信息
     */
    private function getFeeInfo($project_id, $deal_ext)
    {
        $deal_ext_info = array(
            'loan_fee_ext' => array(),
            'consult_fee_ext' => array(),
            'guarantee_fee_ext' => array(),
            'pay_fee_ext' => array(),
            'canal_fee_ext' => array(),
        );
        $deal_list = DealModel::instance()->getDealByProId($project_id, array(DealModel::$DEAL_STATUS['full']));

        // 求和
        $func_same_key_sum = function($fee_info, $deal_ext_fee) {
            foreach($fee_info as $period => $fee) {
                $deal_ext_fee[$period] += $fee;
            }
            return $deal_ext_fee;
        };
        foreach ($deal_list as $deal) {
            $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

            // 平台手续费
            $loan_fee_ext = json_decode($deal_ext['loan_fee_ext'], true);
            $deal_ext_info['loan_fee_ext'] = call_user_func($func_same_key_sum, $loan_fee_ext, $deal_ext_info['loan_fee_ext']);

            // 咨询费
            $consult_fee_ext = json_decode($deal_ext['consult_fee_ext'], true);
            $deal_ext_info['consult_fee_ext'] = call_user_func($func_same_key_sum, $consult_fee_ext, $deal_ext_info['consult_fee_ext']);

            // 担保费
            $guarantee_fee_ext = json_decode($deal_ext['guarantee_fee_ext'], true);
            $deal_ext_info['guarantee_fee_ext'] = call_user_func($func_same_key_sum, $consult_fee_ext, $deal_ext_info['guarantee_fee_ext']);

            // 支付费
            $pay_fee_ext = json_decode($deal_ext['pay_fee_ext'], true);
            $deal_ext_info['pay_fee_ext'] = call_user_func($func_same_key_sum, $pay_fee_ext, $deal_ext_info['pay_fee_ext']);

            // 渠道费
            $canal_fee_ext = json_decode($deal_ext['canal_fee_ext'], true);
            $deal_ext_info['canal_fee_ext'] = call_user_func($func_same_key_sum, $canal_fee_ext, $deal_ext_info['canal_fee_ext']);
        }

        $deal_ext_info['loan_fee_ext_sum'] = array_sum($deal_ext_info['loan_fee_ext']);
        $deal_ext_info['consult_fee_ext_sum'] = array_sum($deal_ext_info['consult_fee_ext']);
        $deal_ext_info['guarantee_fee_ext_sum'] = array_sum($deal_ext_info['guarantee_fee_ext']);
        $deal_ext_info['pay_fee_ext_sum'] = array_sum($deal_ext_info['pay_fee_ext']);
        $deal_ext_info['canal_fee_ext_sum'] = array_sum($deal_ext_info['canal_fee_ext']);

        return $deal_ext_info;

    }

    /**
     * 更新项目放款表单
     */
    public function update_lent()
    {
        $project_id = intval($_REQUEST['project_id']);
        if (empty($project_id)) {
            $this->error('参数错误！');
            return;
        }

        if (1 == $_REQUEST['loan_type']) {
            $loan_type = 1; //先计息后放款
        } else {
            $loan_type = 0; //直接放款
        }

        if (false === DealExtModel::instance()->updateAllLoanTypeByProjectId($project_id, $loan_type)) {
            save_log(sprintf('放款类型更新失败，项目id：%d，新的放款类型：%d', $project_id, $loan_type), C('FAILED'), '', '', C('SAVE_LOG_FILE'));
            $this->error(L("UPDATE_FAILED").$dbErr,0);
        } else {
            save_log(sprintf('放款类型更新成功，项目id：%d，新的放款类型：%d', $project_id, $loan_type), C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    /**
     * 审核项目满标
     */
    public function audit_full()
    {
        $map['business_status'] = DealProjectModel::$PROJECT_BUSINESS_STATUS['full_audit'];
        $project_list = $this->_list(DI(MODULE_NAME), $map);
        $project_list = $this->handleFullProjectInfo($project_list);

        $this->assign('list', $project_list);
        $this->display();
    }

    /**
     * 处理满标的项目信息
     * @param array $project_list 从项目表中取出的项目信息
     * @return array $new_project_list
     */
    private function handleFullProjectInfo($project_list)
    {
        foreach ($project_list as $key => $project) {
            $deal = DealProjectModel::getFirstDealByProjectId($project['id']);
            $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

            // 项目信息
            $project['loan_money_type_name'] = $GLOBALS['dict']['LOAN_MONEY_TYPE'][$project['loan_money_type']];
            $full_money = DealModel::instance()->getFullDealsMoneySumByProjectId($project['id']);
            $project['full_money'] = $full_money;
            $project['remaining_money'] = bcsub($project['borrow_amount'], $full_money, 2);
            $project['user_info'] = UserModel::instance()->findViaSlave($project['user_id']);
            $project['user_info']['user_name_url'] = (1 == $project['user_info']['user_type']) ? getUserFieldUrl($project['user_info'], 'company_name') : getUserFieldUrl($project['user_info'], 'real_name');
            $project['user_info']['user_mobile_url'] = getUserFieldUrl($project['user_info'], 'mobile');
            $project['formated_fixed_value_date'] = to_date($project['fixed_value_date'], 'Y-m-d');

            // 标的信息
            $project['deal'] = $deal;
            $project['deal']['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
            $project['deal']['repay_period'] = $deal['repay_time'] . ($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY'] == $deal['loantype'] ? '天' : '月');

            $project_list[$key] = $project;
        }

        return $project_list;
    }

    /**
     * 项目满标确认
     */
    public function confirm_full()
    {
        $res = array('code' => 0, 'msg' => '成功');
        $project_id = intval($_REQUEST['project_id']);

        try {
            $GLOBALS['db']->startTrans();

            // 验证
            // 金额
            $pro_service = new DealProjectService();
            if (!$pro_service->isProjectFull($project_id)) {
                throw new \Exception('满标金额与项目借款金额不匹配', 1);
            }

            // 更新项目业务状态 - 转让签署中
            if (!DealProjectModel::instance()->changeProjectStatus($project_id, DealProjectModel::$PROJECT_BUSINESS_STATUS['transfer_sign'])) {
                throw new \Exception('项目业务状态变更失败', 3);
            }

            // 合同 - 生成项目的权益转让协议
            $send_contract_service = new SendContractService();
            if (!$send_contract_service->sendProjectContract($project_id)) {
                throw new \Exception('项目合同生成失败', 4);
            }

            $GLOBALS['db']->commit();
            Logger::info(sprintf('满标确认成功。项目 id ：%d', $project_id));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $res = array('code' => $e->getCode(), 'msg' => $e->getMessage());
            Logger::info(sprintf('满标确认失败：%s；项目 id：%d', $e->getMessage(), $project_id));
        }

        echo json_encode($res);
    }

    //将掌众标的置为无效
    public function setZhangzhongInvalid() {
        if (!\libs\payment\supervision\Supervision::isServiceDown()) {
            $this->error('非法操作，请先降级');
        }
        Logger::info('setZhangzhongInvalid. start');
        $zzjrTypeId = \core\dao\DealLoanTypeModel::instance()->getIdByTag(\core\dao\DealLoanTypeModel::TYPE_ZHANGZHONG);
        $sql = sprintf('select id, project_id from firstp2p_deal where deal_status in (0,1,6) and type_id = %d and is_effect = 1 and load_money = 0 and report_status = 1 and is_delete = 0', $zzjrTypeId);
        $db = \libs\db\Db::getInstance('firstp2p');
        $result = $db->getAll($sql);
        foreach ($result as $val) {
            try {
                $db->startTrans();

                //标的更新无效，逻辑删除，流标
                $updateSql = sprintf('update firstp2p_deal set is_delete = 1, is_effect = 0, deal_status = 3, update_time = %d where id = %d and type_id = %d and deal_status in (0,1,6) and is_effect = 1 and load_money = 0 and report_status = 1 and is_delete = 0', time() - 28800, $val['id'], $zzjrTypeId);
                $db->query($updateSql);
                if ($db->affected_rows() > 0) {
                    //标的项目废弃
                    $updateProjectSql = sprintf('update firstp2p_deal_project set status = 1, update_time = %d where id = %d and status = 0', time() - 28800, $val['project_id']);
                    $db->query($updateProjectSql);
                    Logger::info('setZhangzhongInvalid. dealId: ' . $val['id']);
                }

                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                $this->error('处理失败');
            }
        }
        Logger::info('setZhangzhongInvalid. end');
        $this->success('处理成功');
    }


    /**
     * 编辑项目
     */
    public function view()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = DealProjectModel::instance()->find($id);
        $vo['fixed_value_date'] = $vo['fixed_value_date'] == 0 ? '' : to_date($vo['fixed_value_date'], 'Y-m-d');
        $vo['is_online'] = ($vo['business_status'] >= DealProjectModel::$PROJECT_BUSINESS_STATUS['process']); // 标识项目是否有标的上线过
        $vo['can_edit'] = ($vo['business_status'] < DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying']); // 还款中之前的状态，可以编辑项目信息

        //获取委托投资说明
        $contract = '';
        $request = new RequestGetProjectDescription();
        $request->setProjectId(intval($id));
        $request->setType(1);
        $request->setSourceType(0);
        $response = $this->getRpc('contractRpc')->callByObject(array(
            'service' => "\NCFGroup\Contract\Services\Category",
            'method' => "getProjectDescription",
            'args' => $request,
        ));

        if(count($response->data)> 0){
            $contract = $response->data['content'];
        }

        // 银行卡类型
        $cardTypes = array(
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
            array('id' => \core\dao\UserBankcardModel::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign('card_types',$cardTypes);
        if (!empty($vo['risk_bearing'])){
            $risk_service = new DealProjectRiskAssessmentService();
            $risk_info = $risk_service->getAssesmentNameById($vo['risk_bearing'], 1);
            $vo['risk_name'] = empty($risk_info['name']) ? '' : $risk_info['name'];
            $vo['risk_describe'] = empty($risk_info['describe']) ? '' : $risk_info['describe'];
            $vo['business_status'] = intval($vo['business_status']);
        }

        $loan_money_type = $this->is_cn ? $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'] : $GLOBALS['dict']['LOAN_MONEY_TYPE'];
        $loan_type  = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'] : $GLOBALS['dict']['LOAN_TYPE'];
        $this->assign('contract',$contract);

        $this->assign ( 'vo', $vo );
        //从配置文件取公用信息
        $this->assign('loan_type', $loan_type);        //还款方式
        $this->assign("borrow_fee_type", $this->is_cn ? $GLOBALS['dict']['BORROW_FEE_TYPE_CN'] : $GLOBALS['dict']['BORROW_FEE_TYPE']); //费用收取方式
        $this->assign('loan_money_type', $loan_money_type); //放款方式
        $this->assign('project_business_status', DealProjectModel::$PROJECT_BUSINESS_STATUS); // 项目的业务状态
        $this->assign('project_business_status_map', DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态 名字映射

        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
        $this->assign("bank_list", $bank_list);

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData();

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->display();
    }

    /**
     * 废弃项目时校验项目信息
     * @param array $project_list 从项目表中取出的项目信息
     */
    private function check_project($project_id){
        $deal = new DealModel();
        $sql  ="select id,deal_status from ".DB_PREFIX."deal where project_id =".intval($project_id);
        $res = $GLOBALS['db']->getAll($sql);
        $deal_queue_info  = new DealQueueInfoModel();
        $deal_load = new DealLoadModel();
        if(!empty($res)){
            foreach($res as $v){
                if(!empty($v) && $v["deal_status"] !=0 ){
                    switch ($v['deal_status']){
                        case 0:
                            $deal_status = '等待确认';
                            break;
                        case 1:
                            $deal_status = '进行中';
                            break;
                        case 2:
                            $deal_status = '满标';
                            break;
                        case 3:
                            $deal_status = '流标';
                            break;
                        case 4:
                            $deal_status = '还款中';
                            break;
                        case 5:
                            $deal_status = '已还清';
                            break;
                        default:
                            $deal_status = '等待确认';
                    }
                    $this->error('投资状态为'.$deal_status.'，作废失败');exit;
                }
                $deal_info = $deal_queue_info ->getDealQueueByDealId($v['id']);
                if(!empty($deal_info) ){
                    $this->error('项目包含标的所属队列不为空，项目作废失败');exit;
                }
                $ct= $deal_load ->getCountByDealIds(array(($v['id'])));
                if( !empty($ct) && $ct[0]["cnt"] >0  ){
                    $this->error('投资列表非空，作废失败');exit;
                }
            }
        }
    }

}
