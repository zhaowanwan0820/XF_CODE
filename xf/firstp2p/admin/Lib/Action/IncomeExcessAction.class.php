<?php

use core\service\IncomeExcessService;
use core\service\DealService;
use core\service\InterestExtraService;
use core\dao\InterestExtraModel;
use libs\utils\Logger;

/**
 * 超额收益功能
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-29
 */
class IncomeExcessAction extends CommonAction{

    // 存储标信息
    private static $deal_list = array();

    /**
     * 获取符合超额收益条件的标
     */
    public function getIncomeExcessDealsList()
    {
        $incomeExcessService = new IncomeExcessService();
        $conds = $this->_getConds();//获取查询条件

        $res = $incomeExcessService->getIncomeExcessDealsList($conds);
        $p = new Page ($res['totalNum'], $conds['page_size']);//分页类

        $this->assign('list', $res['list']);

        $this->assign("page", $p->show());
        $this->assign("nowPage", $p->nowPage);
        $this->assign('site_list', array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));//站点列表
        $this->display('deal_list');
    }

    /**
     * 获取待审核标的
     */
    public function getIncomeExcessAuditList()
    {
        $incomeExcessService = new IncomeExcessService();
        $conds = $this->_getConds();//获取查询条件

        $res = $incomeExcessService->getIncomeExcessAuditList($conds);
        $p = new Page ($res['totalNum'], $conds['page_size']);//分页类

        $this->assign('list', $res['list']);
        $this->assign("page", $p->show());
        $this->assign("nowPage", $p->nowPage);
        $this->assign('site_list', array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));//站点列表
        $this->display('audit_list');
    }

    /**
     * 获取要查询的条件
     */
    private function _getConds(){

        $conds = array();
        if(isset($_REQUEST['deal_id']) && !empty($_REQUEST['deal_id']))//标id
        {
            $conds['deal_id'] = intval($_REQUEST['deal_id']);
        }

        if(isset($_REQUEST['start_success_date']) && !empty($_REQUEST['start_success_date']))//满标开始时间
        {
            $conds['start_success_time'] = to_timespan($_REQUEST['start_success_date']);
        }

        if(isset($_REQUEST['end_success_date']) && !empty($_REQUEST['end_success_date']))//满标截止时间
        {
            $conds['end_success_time'] = to_timespan($_REQUEST['end_success_date']);
        }

        if(isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id']))//站点id
        {
            $conds['site_id'] = intval($_REQUEST['site_id']);
        }

        if (isset ($_REQUEST ['_order']) && !empty($_REQUEST['_order'])) {
            $conds['_order'] = addslashes(trim($_REQUEST ['_order']));
        }

        if (isset ($_REQUEST ['_sort'])) {
            $conds['_sort'] = intval($_REQUEST ['_sort']);
        }

        if (isset ($_REQUEST ['p'])) {
            $conds['page_num'] = intval($_REQUEST ['p']);
        } else {
            $conds['page_num'] = 1;
        }

        $conds['page_size'] = 20;//每页条数

        $sortAlt = intval($conds['_sort'])? l("DESC_SORT") : l("ASC_SORT") ;
        $this->assign('sort', intval($conds['_sort'])?0:1);
        $this->assign('order', $_REQUEST ['_order']);
        $this->assign('sortImg', intval($conds['_sort'])? 'desc' : 'asc');
        $this->assign('sortType', $sortAlt);
        $this->assign('interest_type', $conds['interest_type']);

        return $conds;
    }

    public function form_index_list(&$list)
    {
        if(!empty($list))
        {
            $site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);//站点列表
            foreach($list as &$val){
                $val['interest_type'] = IncomeExcessService::$INTEREST_TYPE_MAP[$val['interest_type']];
                $val['site_id'] = $site_list[$val['site_id']];
            }
        }
    }

    /**
     *  获取已结算标的贴息列表
     * @param int $deal_id 标id
     * @param int $batch_number 结算的批次号
     */
    public function getIncomeExcessHistory(){
        $incomeExcessService = new IncomeExcessService();
        $conds = $this->_getConds();//获取查询条件

        $res = $incomeExcessService->getIncomeExcessHistory($conds);
        $p = new Page ($res['totalNum'], $conds['page_size']);//分页类

        $this->assign('list', $res['list']);
        $this->assign("page", $p->show());
        $this->assign("nowPage", $p->nowPage);
        $this->assign('site_list', array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));//站点列表
        $this->display('history_list');
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

        if (empty($_REQUEST['dealId'])){
            $this->error("标的信息不存在");
            return ;
        }
        $dealId = intval(trim($_REQUEST['dealId']));

        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId,true,false);

        $interestExtraModel = new InterestExtraModel();
        $interestExtraInfo = $interestExtraModel->findBy('deal_id='.$dealId,'success_time,repay_start_time,rate',array(), true);

        if(empty($dealInfo) || empty($interestExtraInfo)) {
            $this->error("标的信息不存在");
            return ;
        }

        //限制一个批次最多导出10w的贴息详细记录
        $limit = 100000;
        $page_size = 5000;
        $where = " deal_id = {$dealId} AND income_type = ".InterestExtraService::INCOME_TYPE_EXCESS;
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
        header("Content-Disposition: attachment; filename=ZX{$dealId}.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');

        $title = array('编号','转入账户','转出账户','“超额收益”贴息金额','满标日','放款日','备注');
        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $title);
        $start = 0;

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

                $row[] = $v['deal_id'];
                $row[] = userNameFormat($v['user_name']);
                $row[] = $v['out_user_name'];
                $row[] = $v['interest'];
                $row[] = to_date($interestExtraInfo['success_time'], 'Y-m-d');
                $row[] = to_date($interestExtraInfo['repay_start_time'], 'Y-m-d');
                $row[] = "投资记录ID:{$v['deal_load_id']}，借款标题:{$dealInfo['name']},超额收益利率:{$interestExtraInfo['rate']},超额收益:{$v['interest']}";//投资记录ID +借款标题+超额收益利率+超额收益

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
     * 显示超额收益审核界面
     */
    public function showAudit() {
        $deal_id = intval(trim($_REQUEST['id']));

        $dealService = new DealService();
        $deal = $dealService->getDeal($deal_id,true,false);

        $incomeExcessService = new IncomeExcessService();
        $dealIncomeExcessInfo = $incomeExcessService->getIncomeExcessInfoByDealId($deal_id);
        $deal['excess_rate'] = $dealIncomeExcessInfo['rate'];
        $deal['excess_id'] = $dealIncomeExcessInfo['id'];

        $this->assign("deal", $deal);
        $this->display ('deal_audit');
    }

    /**
     * 显示超额收益配置界面
     */
    public function showConfig() {
        $id = intval(trim($_REQUEST['id']));

        $dealService = new DealService();
        $deal = $dealService->getDeal($id,true,false);

        $this->assign("deal", $deal);
        $this->display ('deal_config');
    }

    /**
     * 设置超额收益
     */
    public function setExcessRate() {
        $dealId = intval(trim($_REQUEST['dealId']));
        $excessRate = floatval(trim($_REQUEST['excessRate']));
        //获取登录人session
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));

        $incomeExcessService = new IncomeExcessService();
        $dealIncomeExcessInfo = $incomeExcessService->getIncomeExcessInfoByDealId($dealId);
        if(empty($dealIncomeExcessInfo)) {
            $res = $incomeExcessService->saveIncomeExcessByDealId($dealId, $excessRate,$adm_session["adm_id"]);
        } else {
            $changeData = array(
                 'status' => InterestExtraService::INTEREST_STATUS_0,
                 'rate' => $excessRate,
            );
            $res = $incomeExcessService->updateIncomeExcessByExcessId($dealIncomeExcessInfo['id'],$changeData) ;
        }

        if($res) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0,'超额收益配置失败',0);
        }
    }

    /**
     * 审核超额收益
     */
    public function auditExcessRate() {
        $excessId = intval(trim($_REQUEST['excessId']));//超额收益信息ID
        $auditStatus = floatval(trim($_REQUEST['auditStatus']));

        $status = InterestExtraService::INTEREST_STATUS_N1;
        if ($auditStatus == 1) { //通过
            $status = InterestExtraService::INTEREST_STATUS_3;
        }

        $changeData = array(
            'status' => $status,
            'audit_time' => time(),
        );

        $incomeExcessService = new IncomeExcessService();
        $res = $incomeExcessService->updateIncomeExcessByExcessId($excessId,$changeData) ;
        if($res) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0,'超额收益审核失败',0);
        }
    }

    /**
     * 删除标信息
     */
    public function delDealIncomeExcessInfo() {
        $excessId = intval(trim($_REQUEST['excessId']));//超额收益信息ID

        $incomeExcessService = new IncomeExcessService();

        $changeData = array(
            'status' => InterestExtraService::INTEREST_STATUS_N2,
        );

        $res = $incomeExcessService->updateIncomeExcessByExcessId($excessId,$changeData) ;
        if($res) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0,'超额收益审核失败',0);
        }
    }

    /**
     * 删除标信息
     */
    public function delDeal() {
        $deal_ids = trim($_REQUEST['deal_ids']);
        $dealIds = explode(",", $deal_ids);

        $incomeExcessService = new IncomeExcessService();
        //审核中的标不允许删除
        if($incomeExcessService->checkIsDealInAudit($dealIds)) {
            $this->ajaxReturn(0,'待审批标的不允许删除！',0);
        }
        //获取登录人session
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));

        $res = $incomeExcessService->delDealByIds($dealIds,$adm_session["adm_id"]) ;

        if($res) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0,'删除标的失败！',0);
        }
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
