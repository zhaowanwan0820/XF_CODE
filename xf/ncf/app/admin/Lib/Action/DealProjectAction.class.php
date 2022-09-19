<?php
// +----------------------------------------------------------------------
// | 项目管理
// +----------------------------------------------------------------------
// | @author zhanglei5@ucfgroup.com
// +----------------------------------------------------------------------

use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\user\BankService;
use core\service\project\ProjectService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\supervision\SupervisionService;

use core\enum\DealProjectEnum;
use core\enum\UserEnum;
use core\enum\RelatedEnum;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\dao\project\DealProjectModel;
use core\dao\related\RelatedCompanyModel;
use core\dao\related\RelatedUserModel;
use libs\utils\DBDes;
use libs\utils\Logger;

class DealProjectAction extends CommonAction{

    const EXPORT_CSV_MAX_COUNT = 10000; // 导出csv最大条数

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    protected $pageEnable = false;

    public function index($deal_type = 0)
    {
        //jira:4308 贷款类型默认为网贷
        $_REQUEST['deal_type'] = 0;

        $uids = array();
        $sortBy = '';
        if (intval($_REQUEST['pro_id'])) {  // 项目编号搜索
            $where_array[] = sprintf(' `id` = %d ', $_REQUEST['pro_id']);
        }

        if (trim($_REQUEST['pro_name'])) {    //增加项目名称搜索
            if($_REQUEST['pro_name_use_like']) { //是否使用模糊查询
                $where_array[] = "name like '".trim($_REQUEST['pro_name'])."%'";
            } else {
                $where_array[] = "name = '".trim($_REQUEST['pro_name'])."'";
            }
        }

        if (trim($_REQUEST['user_name'])) {    //增加项目名称搜索
            $rs = UserService::getUserByName($_REQUEST['user_name'],'id');
            $uids[] = $rs['id'];
        }

        if (trim($_REQUEST['real_name'])) {    //增加项目名称搜索
            $rs = UserService::getUserIdByRealName($_REQUEST['real_name']);
            foreach($rs as $ruid) {
                $ruids[] = $ruid;
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

        $this->assign('deal_type', $deal_type);

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
            $deal_pro_service = new ProjectService();
            foreach($format_list as $k => $v){
                $format_list[$k]['status'] = ($v['status'] == 0) ? '正常' : '作废';
                $format_list[$k]['entrust_sign'] = ($v['entrust_sign'] == 0) ? '未委托' : '已委托';
                $format_list[$k]['entrust_agency_sign'] = ($v['entrust_agency_sign'] == 0) ? '未委托' : '已委托';
                $format_list[$k]['entrust_advisory_sign'] = ($v['entrust_advisory_sign'] == 0) ? '未委托' : '已委托';
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
        $this->assign('project_business_status', DealProjectEnum::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态
        $this->assign('isSvDown', SupervisionService::isServiceDown()); // 存管是否降级

        $this->display ();
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

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData();

        //从配置文件取公用信息
        $this->assign('loan_type',  $GLOBALS['dict']['LOAN_TYPE_CN']); //还款方式
        $this->assign('project_business_status', DealProjectEnum::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('deal_type', $deal_type);

        $this->assign("borrow_fee_type", $GLOBALS['dict']['BORROW_FEE_TYPE_CN']); //费用收取方式
        $this->assign('loan_money_type', $GLOBALS['dict']['LOAN_MONEY_TYPE_CN']); //放款方式
        $bank_list = BankService::getAllByStatusOrderByRecSortId(0);

        $this->assign("bank_list", $bank_list);

        $this->display();
    }

    /**
     * 创建标的
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function insert()
    {
        $_POST['bankzone'] = $_POST['bank_bankzone'];

        $m = M(MODULE_NAME);
        //开始验证有效性
        $data = $m->create();

        $userId = intval($_REQUEST['user_id']);
        $user = UserService::getUserById($userId);
        if(empty($user)) {
            $this->error ( "获取用户信息失败!");
        }
        //是否是关联方
        $isRelated = false;
        if (UserEnum::USER_TYPE_NORMAL == $user['user_type']) {
            $idno = $user['idno'];
            $relatedUserModel = new RelatedUserModel();
            $isRelated = $relatedUserModel->isRelatedUser($idno,RelatedEnum::CHANNEL_NCFPH);
        } elseif (UserEnum::USER_TYPE_ENTERPRISE == $user['user_type']) {
            $enterpriseInfo = UserService::getEnterpriseInfo($userId);
            if(empty($enterpriseInfo)) {
                $this->error ( "获取企业用户信息失败!");
            }
            $idno = $enterpriseInfo['credentials_no'];
            $relatedCompanyModel = new RelatedCompanyModel();
            $isRelated = $relatedCompanyModel->isRelatedCompany($idno,RelatedEnum::CHANNEL_NCFPH);
        }
        if($isRelated) {
            $this->error ( "该借款人为关联方，不能借款!");
        }

        $data['bankcard'] = DBDes::encryptOneValue($data['bankcard']);

        $return = $this->getCntByName($data['name']);
        if($return['cnt'] > 0) {
            $this->error ( "项目名称不能重复!");
        }else{
            $m = M('DealProject');
            try {
                $projectId = $m->add ($data);
            } catch (\Exception $e) {
                $dbErr = M()->getDbError();
                save_log('新增项目'.$data['name'].L("INSERT_FAILED").$dbErr, C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
                $this->error(L("INSERT_FAILED").$dbErr);
            }
        }
        save_log('新增项目'.$data['name'].L("INSERT_SUCCESS"), C('SUCCESS'), '', $data, C('SAVE_LOG_FILE'));
        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 编辑项目
     */
    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = DealProjectModel::instance()->find($id);

        $vo['is_online'] = ($vo['business_status'] >= DealProjectEnum::$PROJECT_BUSINESS_STATUS['process']); // 标识项目是否有标的上线过
        $vo['can_edit'] = ($vo['business_status'] < DealProjectEnum::$PROJECT_BUSINESS_STATUS['repaying']); // 还款中之前的状态，可以编辑项目信息
        // 银行卡类型
        $cardTypes = array(
                array('id' => \core\enum\UserBankCardEnum::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
                array('id' => \core\enum\UserBankCardEnum::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign('card_types',$cardTypes);
        if (!empty($vo['risk_bearing'])){
            $risk_service = new DealProjectRiskAssessmentService();
            $risk_info = $risk_service->getAssesmentNameById($vo['risk_bearing'], 1);
            $vo['risk_name'] = empty($risk_info['name']) ? '' : $risk_info['name'];
            $vo['risk_describe'] = empty($risk_info['describe']) ? '' : $risk_info['describe'];
            $vo['business_status'] = intval($vo['business_status']);
        }

        $loan_money_type = $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'];
        $loan_type  = $GLOBALS['dict']['LOAN_TYPE_CN'];
        if (!empty($vo['bankcard']) && !is_numeric($vo['bankcard']) ){
            $vo['bankcard'] = DBDes::decryptOneValue($vo['bankcard']);
        }

        $this->assign ( 'vo', $vo );
        //从配置文件取公用信息
        $this->assign('loan_type', $loan_type);        //还款方式
        $this->assign("borrow_fee_type", $GLOBALS['dict']['BORROW_FEE_TYPE_CN']); //费用收取方式
        $this->assign('loan_money_type', $loan_money_type); //放款方式
        $this->assign('project_business_status', DealProjectEnum::$PROJECT_BUSINESS_STATUS); // 项目的业务状态
        $this->assign('project_business_status_map', DealProjectEnum::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态 名字映射

        $bank_list = BankService::getAllByStatusOrderByRecSortId(0);
        $this->assign("bank_list", $bank_list);

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData($vo);

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->display();
    }

    public function show() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $template = 'edit';
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

        unset($_REQUEST['contractDescription']);
        // 产品结构、风险说明、承受能力不做更新
        unset($_POST['product_mix_1'],$_POST['product_mix_2'],$_POST['product_mix_3'],$_POST['none_name'],$_POST['none_name2']);
        $m = M(MODULE_NAME);
        $data = $m->create ();
        $vo = $m->where(array('id' => $data['id']))->find();
        $sql  ="select deal_status from ".DB_PREFIX."deal where project_id =".intval($data['id']);
        $res = $GLOBALS['db']->getOne($sql);
        if($vo['status'] == 0 && $data['status'] == 1){
            if(!empty($res) && $res['deal_status'] == 1 || $res['deal_status'] == 4){
                $this->error('已上标或还款中，不能作废');exit;
            }
        }

        if($vo['status'] == 1 && $data['status'] == 0){
            $data['status'] = 1;
        }
        $data['bankcard'] = DBDes::encryptOneValue($data['bankcard']);

        $m->startTrans();
        //普通项目修改
        try {
            $full_money = DealModel::instance()->getFullDealsMoneySumByProjectId($vo['id']);
            $rs = $m->save($data);
            if(!$rs) {
                throw new \Exception('保存项目信息失败！');
            }
            $m->commit();
            Logger::info(sprintf('project save success,new-data：%s,business_status：%d,full_deal_money：%s,is_entrust_zx:%d,file：%s, line:%s', json_encode($data), $vo['business_status'], $full_money, $is_entrust_zx, __FILE__, __LINE__));
        } catch (\Exception $e) {
            echo $e->getMessage();
            $m->rollback();

            $dbErr = M()->getDbError();
            save_log('编辑普通项目'.$data['name'].L("INSERT_FAILED").$dbErr, C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
            $this->error(L("INSERT_FAILED").$dbErr);
        }

        $this->redirect(u(MODULE_NAME."/index"));
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

        $bank = BankService::getNewCardByUserId($user_id);
        if(intval($bank['status']) == 1) {
            $data['bank_auth'] = 1;
        }

        $user = UserService::getUserByCondition("id={$user_id}");
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
        $ds = new core\service\deal\DealService();
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
        $userIds = array();
        foreach ($list as &$row ) {
            $userIds[] = $row['user_id'];
        }
        $userInfos = UserService::getUserInfoByIds(array_unique($userIds),true);
        $userFormats = array();
        foreach ($userInfos as $userInfo) {
            $userFormats[$userInfo['id']] = $userInfo;
        }

        foreach ($list as &$row ) {
            $row['diff'] = $row['borrow_amount'] - $row['money_loaned'];
            $row['showUserName'] = get_user_url($userFormats[$row['user_id']]);
            $row['showRealUserName'] = get_user_url($userFormats[$row['user_id']],'real_name');
            $row['showUserTypeName'] = $userFormats[$row['user_id']]['user_type_name'];
            $new_list[$row['id']] = $row;
            $ids[] = $row['id'];
        }
        return $new_list;
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

        if (intval($_REQUEST['pro_id'])) {  // 项目编号搜索
            $where_array[] = sprintf(' `id` = %d ', $_REQUEST['pro_id']);
        }

        if (trim($_REQUEST['pro_name'])) {    //增加项目名称搜索
            $where_array[] = "name like '%".trim($_REQUEST['pro_name'])."%'";
        }

        if (trim($_REQUEST['user_name'])) {    //增加项目名称搜索
            $rs = UserService::getUserByName($_REQUEST['user_name'],'id');
            $uids[] = $rs['id'];
        }

        if (trim($_REQUEST['real_name'])) {    //增加项目名称搜索
            $rs = UserService::getUserIdByRealName($_REQUEST['real_name']);
            foreach($rs as $ruid) {
                $ruids[] = $ruid;
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
        // 只取网贷
        $where_array[] = "deal_type=0";

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
        $title = array('项目编号', '项目名称', '期限', '还款方式', '借款人会员id', '借款人会员名称', '借款人会员姓名', '借款总额', '已上标金额','待上标金额', '已出借金额', '差额', '放款审批单编号', '项目授信额度','用户类型');
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $title);
        $deal_pro_service = new ProjectService();
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
                $row[] =  $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loantype']];
                $row[] = $v['user_id'];
                $row[] = $v['showUserName'];
                $row[] = $v['showRealUserName'];
                $row[] = format_price($v['borrow_amount'],false);
                $row[] = format_price($v['money_borrowed'],false);
                $row[] = format_price($v['borrow_amount']-$v['money_borrowed'],false);
                $row[] = format_price($v['money_loaned'],false);
                $row[] = format_price($v['diff'],false);
                $row[] = $v['approve_number'];
                $row[] = $v['credit'];

                $row[] =$v['showUserTypeName'];

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
            $dp_service = new ProjectService();
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
     * 开户行选择器模板数据
     */
    private function _assignBankzoneRegionData($vo)
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
        $bank_list = BankService::getAllByStatusOrderByRecSortId(0);
        $this->assign('bank_list', $bank_list);
    }
    /**
     * 操作批量变更页面
     */
    public function batch_update()
    {
        $this->assign('main_title', '批量更新项目信息');
        $this->display('batch_update_cn');
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
        $title = array('项目id', '借款总额', '项目授信额度', '状态');
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

                    // 固定项目修改信息只能是5列
                    if (4 != count($data)) {
                        throw new \Exception('数据格式错误：此 csv 只能是4列！');
                    }


                // 约定第一行为表头，跳过
                if ($is_header) {
                    $is_header = false;
                    continue;
                }

                list($new_data['id'],
                    $new_data['borrow_amount'],
                    $new_data['credit'], // 授信额度
                    $new_data['status'],
                    ) = $data; // 赋值给对应项

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
                            'fail_msg'=>'出借状态不是等待确认',
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
                            'fail_msg'=>'出借列表不为空',
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
     * 编辑项目
     */
    public function view()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = DealProjectModel::instance()->find($id);

        $vo['is_online'] = ($vo['business_status'] >= DealProjectEnum::$PROJECT_BUSINESS_STATUS['process']); // 标识项目是否有标的上线过
        $vo['can_edit'] = ($vo['business_status'] < DealProjectEnum::$PROJECT_BUSINESS_STATUS['repaying']); // 还款中之前的状态，可以编辑项目信息
        // 银行卡类型
        $cardTypes = array(
            array('id' => \core\enum\UserBankCardEnum::CARD_TYPE_PERSONAL, 'card_type_name' => '个人账户'),
            array('id' => \core\enum\UserBankCardEnum::CARD_TYPE_BUSINESS, 'card_type_name' => '公司账户'),
        );
        $this->assign('card_types',$cardTypes);
        if (!empty($vo['risk_bearing'])){
            $risk_service = new DealProjectRiskAssessmentService();
            $risk_info = $risk_service->getAssesmentNameById($vo['risk_bearing'], 1);
            $vo['risk_name'] = empty($risk_info['name']) ? '' : $risk_info['name'];
            $vo['risk_describe'] = empty($risk_info['describe']) ? '' : $risk_info['describe'];
            $vo['business_status'] = intval($vo['business_status']);
        }

        $loan_money_type = $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'];
        $loan_type  = $GLOBALS['dict']['LOAN_TYPE_CN'];
        if (!empty($vo['bankcard']) && !is_numeric($vo['bankcard']) ){
            $vo['bankcard'] = DBDes::decryptOneValue($vo['bankcard']);
        }

        $this->assign ( 'vo', $vo );
        //从配置文件取公用信息
        $this->assign('loan_type', $loan_type);        //还款方式
        $this->assign("borrow_fee_type", $GLOBALS['dict']['BORROW_FEE_TYPE_CN']); //费用收取方式
        $this->assign('loan_money_type', $loan_money_type); //放款方式
        $this->assign('project_business_status', DealProjectEnum::$PROJECT_BUSINESS_STATUS); // 项目的业务状态
        $this->assign('project_business_status_map', DealProjectEnum::$PROJECT_BUSINESS_STATUS_MAP); // 项目的业务状态 名字映射

        $bank_list = BankService::getAllByStatusOrderByRecSortId(0);
        $this->assign("bank_list", $bank_list);

        //开户行选择器需要的数据
        $this->_assignBankzoneRegionData($vo);

        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->display();
    }
}
