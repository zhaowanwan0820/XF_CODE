<?php
// +----------------------------------------------------------------------
// | 投标贴息系统
// +----------------------------------------------------------------------
// | Author: wanzhen,zhaoxiaoan
// +----------------------------------------------------------------------

use core\service\InterestExtraService;
use libs\utils\Logger;
use core\dao\InterestExtraModel;

class InterestExtraAction extends CommonAction{

    // 默认走从库
    protected $is_use_slave = true;

    // 存储标信息
    private static $deal_list = array();

    /**
     * 获取符合贴息条件的标
     */
    public function interestExtraDeals()
    {
        $interestExtraService = new InterestExtraService();
        $param = $this->_getParam();//获取查询条件
        $count = $interestExtraService->getInterestExtraDealsCount($param);
        $p = new Page ($count, 100);//分页类

        $list = array();
        if($count > 0)
        {
            $param['firstRow'] = $p->firstRow;
            $param['listRows'] = $p->listRows;
            $list = $interestExtraService->getInterestExtraDealsList($param);
            $this->form_index_list($list,$param['interest_type']);
        }

        $this->assign('list', $list);
        $this->assign("page", $p->show());
        $this->assign("nowPage", $p->nowPage);
        $this->assign('interest_types', InterestExtraService::$INTEREST_TYPE_MAP);//贴息类型
        $this->assign('site_list', array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));//站点列表
        $this->display();
    }

    public function doInterestExtra()
    {
        $ajax = intval($_REQUEST['ajax']);
        $deal_ids = trim($_REQUEST['deal_ids']);
        $deal_ids = array_map('intval',explode(',', $deal_ids));//过滤deal_ids

        $status = intval($_REQUEST['status']);//标的贴息状态
        if(!in_array($status, array(InterestExtraService::INTEREST_STATUS_N2,InterestExtraService::INTEREST_STATUS_1)))//前端提交过来的状态必须为删除或者提交状态
        {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "status 状态不正确", "deal_ids ".json_encode($deal_ids) , $status)));
            $this->error("参数错误", $ajax);
            return ;
        }

        $interest_type  = intval($_REQUEST['interest_type']);//贴息类型
        if(!isset(InterestExtraService::$INTEREST_TYPE_MAP[$interest_type]))//前端提交过来的贴息类型必须符合条件
        {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "interest_type 贴息类型不正确", "deal_ids ".json_encode($deal_ids) , $interest_type)));
            $this->error("参数错误", $ajax);
            return ;
        }

        if(empty($deal_ids))
        {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "标id不能为空", "deal_ids ".json_encode($deal_ids) , $status?"提交":"删除")));
            $this->error("请选择标", $ajax);
            return ;
        }

        //获取登录人session
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $interestExtraService = new InterestExtraService();
        $result = $interestExtraService->saveInterestExtraByDealIds($deal_ids, $status, $interest_type ,$adm_session["adm_id"]);
        if($result)
        {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "deal_ids ".json_encode($deal_ids) , $status?"提交成功":"删除成功")));
            save_log("贴息管理" ,1 ,'',json_encode($deal_ids));
            $this->display_success("操作成功", $ajax);
        }
        else
        {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "deal_ids ".json_encode($deal_ids) , $status?"提交失败":"删除失败")));
            $this->error("操作失败", $ajax);
        }
    }

    /**
     * 获取要查询的条件
     */
    private function _getParam(){

        $param = array();
        if(isset($_REQUEST['deal_id']) && !empty($_REQUEST['deal_id']))//标id
        {
            $param['deal_id'] = intval($_REQUEST['deal_id']);
        }
        if(isset($_REQUEST['start_success_date']) && !empty($_REQUEST['start_success_date']))//满标开始时间
        {
            $param['start_success_time'] = to_timespan($_REQUEST['start_success_date']);
        }
        if(isset($_REQUEST['end_success_date']) && !empty($_REQUEST['end_success_date']))//满标截止时间
        {
            $param['end_success_time'] = to_timespan($_REQUEST['end_success_date']);
        }
        if(isset($_REQUEST['interest_type']))//返类型
        {
            $param['interest_type'] = intval($_REQUEST['interest_type']);
        }
        else //默认选中T1
        {
            $param['interest_type'] = InterestExtraService::INTEREST_TYPE_1;
        }

        if(isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id']))//站点id
        {
            $param['site_id'] = intval($_REQUEST['site_id']);
        }
        if (isset ($_REQUEST ['_order']) && !empty($_REQUEST['_order'])) {
            $param['_order'] = addslashes(trim($_REQUEST ['_order']));
        }
        if (isset ($_REQUEST ['_sort'])) {
            $param['_sort'] = intval($_REQUEST ['_sort']);
        }
        $sortAlt = intval($param['_sort'])? l("DESC_SORT") : l("ASC_SORT") ;
        $this->assign('sort', intval($param['_sort'])?0:1);
        $this->assign('order', $_REQUEST ['_order']);
        $this->assign('sortImg', intval($param['_sort'])? 'desc' : 'asc');
        $this->assign('sortType', $sortAlt);
        $this->assign('interest_type', $param['interest_type']);

        return  $param;
    }

    public function form_index_list(&$list)
    {
        if(!empty($list))
        {
            $site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);//站点列表
            foreach($list as &$val){
                $val['interest_type'] = InterestExtraService::$INTEREST_TYPE_MAP[$val['interest_type']];
                $val['site_id'] = $site_list[$val['site_id']];
            }
        }
    }

    /**
     *  获取已结算标的贴息列表
     * @param int $deal_id 标id
     * @param int $batch_number 结算的批次号
     */
    public function getPayedInterestList(){

        $where = ' status='.InterestExtraService::INTEREST_STATUS_2.' AND income_type = '.InterestExtraService::INCOME_TYPE_INTEREST;;
        if (!empty($_REQUEST['deal_id']) && is_numeric($_REQUEST['deal_id'])){
            $where  .= " AND deal_id='".intval($_REQUEST['deal_id'])."'";
        }
        if (!empty($_REQUEST['batch_number'])){
            list($y,$m,$d) = explode('-',$_REQUEST['batch_number']);

            if (!is_numeric($y) || !is_numeric($m) || !is_numeric($d) || checkdate($m, $d, $y) === false){
                $this->error("批次编号错误");
                return ;
            }
            $where .= " AND pay_date='{$_REQUEST['batch_number']}'";
        }
        if (empty($_REQUEST['_order'])){
            $order_by = ' ORDER BY pay_date DESC';
        }else{
            $order_by = ' ORDER BY ' . $_REQUEST['_order'] . ' ' . ($_REQUEST['_sort']=='desc' ? 'desc' : 'asc');
        }
        $sql_count = 'SELECT COUNT( DISTINCT pay_date) FROM '.DB_PREFIX.'interest_extra WHERE '.$where;
        $count = $this->global_db->getOne($sql_count);
        if ($count > 0){
            $sql = 'SELECT pay_date,deal_id,SUM(`interest_amount`) AS total_amount FROM '.DB_PREFIX.'interest_extra WHERE '.$where. ' GROUP BY pay_date '.$order_by;
            $p = new Page ($count, 30);
            //分页查询数据
            $sql .= ' limit ' . $p->firstRow . ',' . $p->listRows;
            $result = $this->global_db->getAll($sql);
            $page = $p->show();
            //模板赋值显示
            $this->assign('list', $result);
            $this->assign("page", $page);
            $this->assign('_sort',($_REQUEST['_sort']=='desc') ? 'asc': 'desc');
            $this->assign("nowPage", $p->nowPage);
        }
        $this->display('payed_interest_list');
        return;
    }

    /**
     * 导出csv
     *
     */
    public function export_csv()
    {
        self::log(array(__FUNCTION__, __LINE__, json_encode($_REQUEST)));
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        if (empty($_REQUEST['batch_number'])){
            $this->error("必须选择批次编号");
            return ;
        }
        list($y,$m,$d) = explode('-',$_REQUEST['batch_number']);
        if (!is_numeric($y) || !is_numeric($m) || !is_numeric($d) || checkdate($m, $d, $y) === false){
            $this->error("批次编号错误");
            return ;
        }



        // 限制一个批次最多导出10w的贴息详细记录
        $limit = 100000;
        $page_size = 5000;
        $where = " pay_date='{$_REQUEST['batch_number']}' AND income_type = ".InterestExtraService::INCOME_TYPE_INTEREST;
        $sql_max_count = "SELECT COUNT(*) max_limit FROM ".DB_PREFIX.'interest_extra_log WHERE '.$where;
        $is_max_count = $this->global_db->getRow($sql_max_count);
        $limit_page_size = app_conf('INTEREST_EXTRA_LOG_EXPROT_MAX_NUM');
        if (!empty($limit_page_size)){
            list($limit,$page_size) = explode(',', $limit_page_size);
            $limit = intval($limit);
            $page_size = intval($page_size);
            if (empty($limit) || empty($page_size)){
                $this->error("配置错误");
                return;
            }
        }
        // 超过最大禁止导出
        if ($is_max_count['max_limit'] > $limit){
            $this->error("超过最大导出数，最大导出为{$limit}");
            return;
        }

        $sql = 'SELECT id,deal_id,interest,user_name,deal_load_id,out_user_name FROM '.DB_PREFIX.'interest_extra_log WHERE'.$where.' LIMIT ';

        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=interest_list_{$_REQUEST['batch_number']}.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');

        $title = array('编号','投资记录id','转入账号','转出账号','贴息金额','满标日','放款日');
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $title);
        $start = 0;

        $interestExtraModel = new InterestExtraModel();
        self::log(array(__FUNCTION__, __LINE__, 'export_csv start'));
        while (true) {
            // 超过最大限制条数
            if ($start >= $limit){
                unset(self::$deal_list);
                break;
            }
            $sql_limit = $sql . " {$start},{$page_size} ";
            $interest_deal_list = $this->global_db->getAll($sql_limit);

            if (empty($interest_deal_list)) {
                break;
            }
            $start += $page_size;

            foreach ($interest_deal_list as $v) {
                $row = array();
                if (!isset(self::$deal_list[$v['deal_id']])){
                    $get_one_deal = $interestExtraModel->findBy('deal_id='.intval($v['deal_id']),'success_time,repay_start_time',array(), true);
                    self::$deal_list[$v['deal_id']] = array(
                        'success_time' => $get_one_deal['success_time'],
                        'repay_start_time' => $get_one_deal['repay_start_time'],
                    );
                }
                $row[] = $v['deal_id'];
                $row[] = $v['deal_load_id'];
                $row[] = userNameFormat($v['user_name']);
                $row[] = $v['out_user_name'];
                $row[] = $v['interest'];
                $row[] = to_date(self::$deal_list[$v['deal_id']]['success_time'], 'Y-m-d');
                $row[] = to_date(self::$deal_list[$v['deal_id']]['repay_start_time'], 'Y-m-d');

                foreach ($row as &$item) {
                    $item = iconv("utf-8", "gbk//IGNORE", strip_tags($item));
                }
                fputcsv($fp, $row);
                unset($row);
            }

        }

        self::log(array(__FUNCTION__, __LINE__, 'export_csv end '.$start));
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
}
?>
