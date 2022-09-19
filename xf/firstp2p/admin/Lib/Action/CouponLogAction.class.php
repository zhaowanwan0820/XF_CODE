<?php
/**
 * 优惠券处理
 * @date 2014-02-25 18:35
 * @author liangqiang@ucfgroup.com
 * @modify 2014-3-31 caolong
 */

use core\service\BonusService;
use core\service\CouponService;
use core\service\CouponLevelService;
use core\service\UserCouponLevelService;
use core\service\CouponLogService;
use core\dao\CouponDealModel;
use core\dao\CouponLogModel;
use core\dao\CouponLogBakModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\service\DealService;
use core\service\UserService;
use core\service\third\PlatformService;
use libs\utils\Logger;
use libs\utils\Rpc;
use core\dao\DealLoadModel;
use core\service\third\ThirdDealService;


class CouponLogAction extends CommonAction
{

    /**
     * 订单状态
     */
    public static $deal_status_list = array('0' => '待等材料', '1' => '进行中', '2' => '满标', '3' => '流标', '4' => '还款中',
        '5' => '已还清');

    /**
     * 表类型
     */
    public static $deal_type_list = array('0' => '普通标', '1' => '通知贷', '2' => '交易所', '3' => '专享', '5' => '小贷');

    /**
     * 结算时机
     */
    public static $pay_type_list = array('0' => '放款时', '1' => '还清时');

    /**
     * 结算方式
     */
    public static $pay_auto_list = array('1' => '自动结', '2' => '手工结');

    /**
     * 投资来源
     */
    public static $deal_load_source_type = array('0' => 'web', '1' => '后台预约', '3' => 'ios', '4' => '安卓', '5' => '前台预约', '6' => 'openAPI', '8' => 'wap');

    /**
     * 结算状态
     */
    public static $pay_status_list = array('0' => '运营待审核', '1' => '自动结算', '2' => '已结算', 3 => '财务待审核', 4 => '运营待审核(财务拒绝)', 5 => '结算中', 6 => '线下结算');

    protected static $coupon_type = array('1' => '注册', '2' => '投资', '3' => '特斯拉');

    protected $is_log_for_fields_detail = true;

    protected $is_use_slave = true;

    public function __construct(){
        parent::__construct();
        $this->pageEnable = isset($_REQUEST['_page']) && $_REQUEST['_page'] == 1 ? true : false;
    }

    public function index()
    {
        //检查参数
        if (!empty($_REQUEST['consume_user_signup_date_begin']) && !empty($_REQUEST['consume_user_signup_date_end'])
            && to_timespan($_REQUEST['consume_user_signup_date_begin']) > to_timespan($_REQUEST['consume_user_signup_date_end'])
        ) {
            $this->error("投资人注册时间的开始时间不能晚于结束时间");
        }

        $groups = UserGroupModel::instance()->getGroups();
        $this->assign("group_list", $groups);

        $getSql = $this->_get_query_sql();
        $sql_conut = $getSql['count_sql'];
        $memory_start = memory_get_usage();
        if($this->pageEnable === false){
            $count = self::LIST_WITHOUT_PAGE_MAX;
        }else{
            $count = $this->global_db->getOne($sql_conut);
        }

        $sql = $getSql['sql'];
        if ($count > 0) {
            $p = new Page ($count, 30);
            //分页查询数据
            $sql .= ' limit ' . $p->firstRow . ',' . $p->listRows;
            $result = $this->global_db->getAll($sql);
            $result = \libs\utils\DBDes::encryptOrDecryptBySpecifiedFields('lu_mobile', $result, false);
            $memory_end = memory_get_usage();
            $memory_use = intval(($memory_end - $memory_start) / (1024 * 1024)) . "mb";
            self::log(array(__FUNCTION__, __LINE__, 'sqlquery:' . $memory_use));

            $page = $p->show($this->pageEnable,count($result));
            $result = $this->form_index_list($result);
            //模板赋值显示
            $this->assign('list', $result);
            $this->assign("page", $page);
            $this->assign("nowPage", $p->nowPage);
            $this->assign("totalPages", $p->totalPages);
            $this->assign("totalRows", $p->totalRows);
        }
        $this->display();
    }

    private function _get_query_sql()
    {
        $post = $_REQUEST;
        foreach ($post as $k => $v) {
            $post[$k] = trim($v);
        }
        $user_num = trim($_REQUEST['user_num']);
        if ($user_num) {
            $post['user_id'] = de32Tonum($user_num);
        }

        $refer_user_num = trim($_REQUEST['refer_user_num']);
        if ($refer_user_num) {
            $post['refer_user_id'] = de32Tonum($refer_user_num);
        }

        $join_tables = array();

        $sql_str = ' 1 ';
        $user_model = new UserModel();

        //投资人ID
        if ($post['user_id']) {
            $join_tables[0] = 'firstp2p_deal_load';
            $sql_str .= " AND l.user_id = '{$post['user_id']}'";
        }

        $sql_consume_user = "1";
        //投资人手机号
        if ($post['mobile']) {
            $sql_consume_user .= " AND mobile = '{$post['mobile']}'";
        } elseif ($post['user_name']) {
            $sql_consume_user .= " AND user_name = '{$post['user_name']}'";
        }
        if ($sql_consume_user != "1" && empty($post['user_id'])) {
            $join_tables[0] = 'firstp2p_deal_load';
            $consume_user_list = $user_model->findAllViaSlave($sql_consume_user, true, 'id');
            $sql_str .= " AND l.user_id in " . self::list2IdStrForSql($consume_user_list);
        }

        //投标ID
        if ($post['deal_load_id']) {
            $join_tables[0] = 'firstp2p_deal_load';
            $sql_str .= " AND l.id = '{$post['deal_load_id']}'";
        }
        //借款编号
        if ($post['deal_id']) {
            $join_tables[0] = 'firstp2p_deal_load';
            $sql_str .= " AND l.deal_id = '{$post['deal_id']}'";
        }
        //借款标题
        if ($post['deal_name'] && empty($post['deal_id'])) {
            $deal_model = new DealModel();
            $deal_list = $deal_model->findAllViaSlave("name LIKE '%{$post['deal_name']}%'", true, "id");
            if (count($deal_list) > 100) {
                // 禁止显示搜索结果
                $this->error("输入的借款标题过于模糊，请增加关键词");
            }
            $sql_str .= " AND l.deal_id in " . self::list2IdStrForSql($deal_list);
        }
        // 项目名称
        if (!empty($post['project_name'])) {
            $sql_str .= ' AND l.`deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($post['project_name']) . '\')) ';
        }
        //标的类型
        if (isset(self::$deal_type_list[$post['deal_type']])) {
            $join_tables[0] = 'firstp2p_deal_load';
            $sql_str .= " AND l.deal_type = '{$post['deal_type']}'";
        }
        //投资时间
        if ($post['deal_load_date_begin']) {
            $join_tables[0] = 'firstp2p_deal_load';
            $deal_load_date_begin = to_timespan($post['deal_load_date_begin']);
            $sql_str .= " AND l.create_time >= {$deal_load_date_begin}";
        }
        if ($post['deal_load_date_end']) {
            $join_tables[0] = 'firstp2p_deal_load';
            $deal_load_date_end = to_timespan($post['deal_load_date_end']);
            $sql_str .= " AND l.create_time <= {$deal_load_date_end}";
        }

        //推荐人会员名称
        if ($post['refer_user_id']) {
            $join_tables[1] = 'firstp2p_coupon_log';
            $sql_str .= " AND c.refer_user_id = '{$post['refer_user_id']}'";
        }
        //推荐人会员名称
        if ($post['refer_user_name'] && empty($post['refer_user_id'])) {
            $join_tables[1] = 'firstp2p_coupon_log';
            $refer_user_list = $user_model->findAllViaSlave("user_name='" . $post['refer_user_name'] . "'", true, 'id');
            $sql_str .= " AND c.refer_user_id in " . self::list2IdStrForSql($refer_user_list);
        }
        // 推荐人机构会员名称
        if ($post['agency_user_name']) {
            $join_tables[1] = 'firstp2p_coupon_log';
            $agency_user_list = $user_model->findAllViaSlave("user_name = '{$post['agency_user_name']}'", true, 'id');
            $sql_str .= " AND c.agency_user_id in " . self::list2IdStrForSql($agency_user_list);

        }
        //优惠码
        if ($post['short_alias']) {
            $join_tables[1] = 'firstp2p_coupon_log';
            $post['short_alias'] = strtoupper($post['short_alias']);
            $couponService = new CouponService();
            $refer_user_id = $couponService->shortAliasToReferUserId($post['short_alias']);
            if ($refer_user_id) {
                $sql_str .= " AND c.refer_user_id = '{$refer_user_id}'";
            } else {
                $sql_str .= " AND c.refer_user_id = '-1'";
            }
        }

        $default_sortBy = 'l_id';
        $alias = "l.id l_id, l.deal_id l_deal_id, l.user_id l_user_id, l.user_name l_user_name, l.money l_money,
            l.create_time l_create_time,l.source_type l_source_type, l.deal_type l_deal_type, l.site_id l_site_id ";
        $tables = 'firstp2p_deal_load l';

        if (current($join_tables) == 'firstp2p_coupon_log') {
            $default_sortBy = 'c.id';
            $alias = "c.*";
            $tables = 'firstp2p_coupon_log c';
        } elseif (count($join_tables) > 1) {
            //拼接 联查  投标记录表 、优惠券表
            $alias = "l.id l_id, l.deal_id l_deal_id, l.user_id l_user_id, l.user_name l_user_name, l.money l_money,
            l.create_time l_create_time,l.source_type l_source_type, l.deal_type l_deal_type, l.site_id l_site_id , c.* ";
            $tables = "{$join_tables[0]} l LEFT JOIN {$join_tables[1]} c ON l.id=c.deal_load_id";
        }

        $sql_where = " FROM {$tables} WHERE {$sql_str} ";
        $sql = "SELECT {$alias} {$sql_where}";

        //没有输入查询条件取max id 为count统计数量，优化查询速度，避免全表扫描
        if (trim($sql_str) != '1') {
            $sql_count = "SELECT count(*) {$sql_where}";
        } else {
            $sql_count = "SELECT max(id) FROM {$tables}";
        }
        // 排序字段，排序方式默认按照倒序排列 接受_sort参数 0 表示倒序 非0都 表示正序，默认为创建时间倒序
        $sql_order = '';
        if (isset ($_REQUEST ['_order'])) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = !empty ($sortBy) ? $sortBy : $default_sortBy;
        }
        $asc = false;
        if (isset ($_REQUEST ['_sort'])) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        } else {
            $sort = $asc ? 'asc' : 'desc';
        }
        if (!empty($order)) {
            $sql_order = " ORDER BY " . $order . " " . $sort;
        }
        $sortImg = $sort; //排序图标
        $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
        $sort = ($sort == 'desc') ? 1 : 0; //排序方式
        $this->assign('sort', $sort);
        $this->assign('order', $order);
        $this->assign('sortImg', $sortImg);
        $this->assign('sortType', $sortAlt);
        $sql .= $sql_order;
        $rs = array('sql' => $sql, 'count_sql' => $sql_count);
        self::log(array(__FUNCTION__, __LINE__, json_encode($rs)));
        return $rs;
    }

    /**
     * 把连表模糊查询的条件转成id字符串，进行分步查询
     *
     * @param $list
     * @return string
     */
    private static function list2IdStrForSql($list)
    {
        $id_array = array();
        if (!empty($list)) {
            foreach ($list as $item) {
                $id_array[] = $item['id'];
            }
            return "(" . implode(',', $id_array) . ")";
        } else {
            return "(-1)";
        }
    }

    /**
     * 处理列表数据显示
     */
    protected function form_index_list(&$list, $csv_data_type = false)
    {
        $memory_start = memory_get_usage();
        $deal_model = new DealModel();
        $coupon_log_model = new CouponLogModel();
        $bonus_service = new BonusService();
        $userCouponLevelService = new UserCouponLevelService();

        foreach ($list as &$item) {
            //投资信息
            if (empty($item['l_id'])) {
                $deal_load = $this->get_deal_load_data($item['deal_load_id']);
                $item['l_id'] = $deal_load['l_id'];
                $item['l_deal_id'] = $deal_load['l_deal_id'];
                $item['l_user_id'] = $deal_load['l_user_id'];
                $item['l_user_name'] = $deal_load['l_user_name'];
                $item['l_money'] = $deal_load['l_money'];
                $item['l_create_time'] = $deal_load['l_create_time'];
                $item['l_source_type'] = $deal_load['l_source_type'];
                $item['l_deal_type'] = $deal_load['l_deal_type'];
                $item['l_site_id'] = $deal_load['l_site_id'];
            }

            //订单信息
            $deal = $this->get_deal_data($item['l_deal_id']);
            $item['d_name'] = $deal['name'];
            $item['d_loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]; // 还款方式
            $item['d_repay_time'] = $deal['repay_time'] . ($deal['loantype'] == 5 ? '天' : '个月');
            $item['d_deal_status'] = $deal['deal_status'];
            $item['d_user_id'] = $deal['user_id'];
            $item['d_repay_start_time'] = $deal['repay_start_time'];
            $item['l_source_type'] = isset(self::$deal_load_source_type[$item['l_source_type']]) ? self::$deal_load_source_type[$item['l_source_type']] : '未知';
            $item['l_deal_type_text'] = self::$deal_type_list[$item['l_deal_type']];
            if (!isset($item['cd_pay_auto'])) {
                $coupon_deal = $this->get_coupon_deal_data($item['l_deal_id']);
                $item['cd_pay_type'] = $coupon_deal['pay_type'];
                $item['cd_pay_auto'] = $coupon_deal['pay_auto'];
            }
            $item['cd_pay_type'] = empty($item['cd_pay_type']) ? CouponDealModel::PAY_TYPE_FANGKUAN : $item['cd_pay_type']; //结算时机
            $item['cd_pay_auto'] = empty($item['cd_pay_auto']) ? CouponDealModel::PAY_AUTO_NO : $item['cd_pay_auto']; //结算方式
            $item['cd_pay_type_text'] = self::$pay_type_list[$item['cd_pay_type']]; //结算时机
            $item['cd_pay_auto_text'] = self::$pay_auto_list[$item['cd_pay_auto']]; //结算方式

            $item['l_money_yearly'] = round($item['l_money'] * $deal['repay_time'] / ($deal['loantype'] == 5 ? CouponLogService::DAYS_OF_YEAR : 12), 2); // 年化投资额
            //$bonus_used = $bonus_service->get_used($item['l_id']);
            //$item['l_bonus_used'] = empty($bonus_used) ? 0 : $bonus_used['money']; // 使用红包金额

            if (!isset($item['short_alias'])) {
                $coupon_log = $coupon_log_model->findByViaSlave("deal_load_id=':deal_load_id'", '*', array(':deal_load_id' => $item['l_id']));
                $coupon_log = empty($coupon_log) ? array() : $coupon_log->getRow();
                $item = array_merge($item, $coupon_log);
            }

            //投资人信息
            if (empty($item['lu_mobile'])) {
                $deal_load_user = $this->get_user_data($item['l_user_id']);
                $item['lu_real_name'] = $deal_load_user['real_name'];
                $item['lu_mobile'] = $deal_load_user['mobile'];
                $item['lu_create_time'] = $deal_load_user['create_time'];
            }

            if ($csv_data_type === false) {
                //投资人F码信息
                $load_user_coupon = CouponService::userIdToHex($item['l_user_id']);
                $item['l_user_name'] = $this->_get_user_link($item['l_user_id'], $item['l_user_name']);
                $item['l_user_num'] = $this->_get_user_link($item['l_user_id'], numTo32($item['l_user_id']));
                if (!empty($load_user_coupon)) {
                    $item['l_user_id'] .= "<br/>({$load_user_coupon})";
                }
                $item['lu_mobile'] = "<div style='width:30px;white-space:normal;word-wrap:break-word;'>" . adminMobileFormat($item['lu_mobile']) . "</div>";
                //订单信息
                $item['d_name'] = "<div style='width:30px;white-space:normal;word-wrap:break-word;'>{$item['d_name']}</div>";
                $deal_index_action = $item['l_deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND ? 'compound' : 'index';
                $item['d_name'] = "<a href='" . u("Deal/{$deal_index_action}", array('id' => $item['l_deal_id'])) . "' target='_blank'>" . $item['d_name'] . "</a>";
                $item['l_deal_id'] = $item['l_deal_id'] . "<br />" . $item['cd_pay_type_text'] . "-" . $item['cd_pay_auto_text'];;
                //借款人信息
                $deal_user = $this->get_user_data($item['d_user_id']);
                $item['du_user_name'] = $this->_get_user_link($item['d_user_id'], $deal_user['user_name']);
                $item['du_real_name'] = $this->_get_user_link($item['d_user_id'], $deal_user['real_name']);
            }

            //优惠券信息
            if (!empty($item['short_alias'])) {
                if (!empty($item['refer_user_id'])) {
                    if (empty($item['refer_real_name'])) {
                        $refer_user = $this->get_user_data($item['refer_user_id']);
                        $item['refer_real_name'] = $refer_user['real_name'];
                        $item['coupon_level_id'] = $refer_user['coupon_level_id'];
                    }
                    $item['refer_user_level'] = $userCouponLevelService->getGroupAndLevelByUserId($item['refer_user_id']);
                    if ($csv_data_type === false) {
                        $item['refer_user_name'] = $this->_get_user_link($item['refer_user_id'], $item['refer_user_name']);
                        $item['refer_user_num'] = $this->_get_user_link($item['refer_user_id'], numTo32($item['refer_user_id']));
                        $item['refer_real_name'] = $this->_get_user_link($item['refer_user_id'], $item['refer_real_name']);
                        $item['short_alias'] = $item['short_alias'] . "<br/><br/>" . $item['refer_user_level'];
                    }
                }
                $agency_user = $this->get_user_data($item['agency_user_id']);
                $item['agency_user_name'] = empty($agency_user) ? '' : $agency_user['user_name'];
                $item['agency_real_name'] = empty($agency_user) ? '' : $agency_user['real_name'];
                if ($csv_data_type === false) {
                    $item['agency_user_name'] = $this->_get_user_link($item['agency_user_id'], $agency_user['user_name']);
                    $item['agency_real_name'] = $this->_get_user_link($item['agency_user_id'], $agency_user['real_name']);
                    $item['rebate_ratio_amount'] = "({$item['rebate_ratio']})<br/>{$item['rebate_ratio_amount']}";
                    $item['referer_rebate_ratio_amount'] = "({$item['referer_rebate_ratio']})<br/>{$item['referer_rebate_ratio_amount']}";
                    $item['agency_rebate_ratio_amount'] = "({$item['agency_rebate_ratio']})<br/>{$item['agency_rebate_ratio_amount']}";
                }
            }

            $item['pay_status_text'] = self::$pay_status_list[$item['pay_status']];

            if ($csv_data_type !== false) {
                continue;
            }

            $item['opt_edit'] = '';
            $item['opt_del'] = '';
            $item['opt_operation'] = ''; // 运营操作
            $item['opt_finance'] = ''; // 财务操作
            $item['opt_pay_list'] = ''; // 返利明细

            if (empty($item['id']) && $item['d_deal_status'] != 3) {
                //$item['opt_edit'] = "<span id='channel_edit_" . $item['id'] . "'><a href='javascript:javascript:weeboxs_add(" . $item['l_id'] . ");'>添加优惠券</a></span>";
            }

            if (!empty($item['id'])) {
                // 默认状态或者财务拒绝或者财务待审核
                if ($item['deal_status'] != 2 && in_array($item['pay_status'], array(0, 4, 5))) {
                    $item['opt_edit'] = "<span id='channel_edit_" . $item['id'] . "'><a href='javascript:javascript:weeboxs_edit(" . $item['id'] . ");'>编辑</a></span>";
                }

                if (in_array($item['l_deal_type'], CouponLogService::$deal_type_group1) && in_array($item['pay_status'], array(0, 4))) {
                    $item['opt_del'] = '<a href="javascript:coupon_log_del(\'' . $item['id'] . '\');">删除</a>';
                }

                if (in_array($item['l_deal_type'], CouponLogService::$deal_type_group1)) {
                    if ($item['deal_status'] == 1 && in_array($item['pay_status'], array(0, 4))) {
                        if ($item['cd_pay_type'] == 0 || ($item['cd_pay_type'] == 1 && $deal['deal_status'] == 5)) {
                            $item['opt_operation'] = "<a href='#' onclick='operation_passed(" . $item['id'] . ");'>运营通过</a>";
                        }
                    }
                    if ($item['deal_status'] == 1 && in_array($item['pay_status'], array(0, 3, 4))) {
                        if ($item['cd_pay_type'] == 0 || ($item['cd_pay_type'] == 1 && $deal['deal_status'] == 5)) {
                            $item['opt_finance'] = "<a href='javascript:finance_audit(" . $item['id'] . ",1)' >财务通过</a>";
                        }

                    }
                }

                if ($item['l_deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND && in_array($item['pay_status'], array(1, 2, 5))) {
                    $item['opt_pay_list'] = '<a href="javascript:pay_list(\'' . $item['deal_load_id'] . '\');">查看明细</a>';
                }
            }

            $item['pay_status'] = self::$pay_status_list[$item['pay_status']];
            $item['d_deal_status'] = $deal_model->getDealStatusText($deal); //订单状态

            // 获取投资的站点
            $item['l_site_name'] = \libs\utils\Site::getTitleById($item['l_site_id']);
        }
        $list_count = count($list);
        $memory_end = memory_get_usage();
        $memory_use = intval(($memory_end - $memory_start) / (1024 * 1024)) . "mb";
        self::log(array(__FUNCTION__, __LINE__, $list_count, 'form_index_list:' . $memory_use));
        return $list;
    }

    /**
     * 导出数据  使用翻页
     * 精简版数据的加载效率高，能导出全量数据。详细版的导出记录数有限，时间漫长。
     */
    public function export_csv()
    {
        self::log(array(__FUNCTION__, __LINE__, json_encode($_REQUEST)));
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $memory_start = memory_get_usage();
        $sql = $this->_get_query_sql();
        $count = $this->global_db->getOne($sql['count_sql']);
        $count_max = app_conf('COUPON_LOG_EXPORT_SIZE_MAX');
        $count_max = empty($count_max) ? 10000 : $count_max; // 默认 1万条
        if ($count > $count_max) {
            $this->error("每次导出条数不能超过{$count_max}条，目前为{$count}条，请增加筛选条件缩小范围");
        }

        $page_size = app_conf('COUPON_LOG_EXPORT_PAGE_SIZE');
        $page_size = empty($page_size) ? 5000 : $page_size;
        $start = 0;

        $datatime = date("YmdHis", get_gmtime());
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=conpon_log_{$datatime}.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        $fp = fopen('php://output', 'w+');
        $title = array('投资记录ID', '成交时间', '投资人ID', '投资人会员名称',
            '投资人姓名', '投资人手机号', '投资金额', '年化投资额', '类型', '投资来源',
            '借款编号', '借款标题', '上标平台', '借款期限', '投资人注册时间', '计息时间', '推荐人会员ID',
            '推荐人会员名称', '推荐人姓名', '推荐人群组',
            '机构会员名ID', '机构姓名', '优惠券短码', '投资人返点金额', '投资人返点比例',
            '投资人返点比例金额', '推荐人返点金额', '推荐人返点比例', '推荐人返点比例金额',
            '机构返点金额', '机构返点比例', '机构返点比例金额', '结算状态', '使用时间', '结算时间', '结算时机', '结算方式');

        foreach ($title as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $title);

        while (true) {
            $sql_limit = $sql['sql'] . "  limit {$start},{$page_size} ";
            $list = $this->global_db->getAll($sql_limit);
            self::log(array(__FUNCTION__, __LINE__, 'export_csv query', $start));
            if (empty($list)) {
                break;
            }
            $this->form_index_list($list, true);
            $start += $page_size;

            foreach ($list as $v) {
                $row = array();
                $row[] = $v['l_id'];
                $row[] = to_date($v['l_create_time']);
                $row[] = $v['l_user_id'];
                $row[] = $v['l_user_name'];
                $row[] = $v['lu_real_name'];
                $row[] = adminMobileFormat($v['lu_mobile']);
                $row[] = $v['l_money'];
                $row[] = $v['l_money_yearly'];
                //$row[] = $v['l_bonus_used'];
                $row[] = $v['l_deal_type_text'];
                $row[] = $v['l_source_type'];
                $row[] = $v['l_deal_id'];
                $row[] = $v['d_name'];
                $row[] = $this->get_deal_site_data($v['l_deal_id']); // 领导需求暂时添加，过几天删掉，影响性能
                $row[] = $v['d_repay_time'];
                $row[] = to_date($v['lu_create_time']);
                $row[] = to_date($v['d_repay_start_time']);
                $row[] = $v['refer_user_id'];
                $row[] = $v['refer_user_name'];
                $row[] = $v['refer_real_name'];
                $row[] = $v['refer_user_level'];
                $row[] = $v['agency_user_id'];
                $row[] = $v['agency_user_name'];
                $row[] = $v['short_alias'];
                $row[] = $v['rebate_amount'];
                $row[] = $v['rebate_ratio'];
                $row[] = $v['rebate_ratio_amount'];
                $row[] = $v['referer_rebate_amount'];
                $row[] = $v['referer_rebate_ratio'];
                $row[] = $v['referer_rebate_ratio_amount'];
                $row[] = $v['agency_rebate_amount'];
                $row[] = $v['agency_rebate_ratio'];
                $row[] = $v['agency_rebate_ratio_amount'];
                $row[] = $v['pay_status_text'];
                $row[] = to_date($v['create_time']);
                $row[] = to_date($v['pay_time']);
                $row[] = $v['cd_pay_type_text'];
                $row[] = $v['cd_pay_auto_text'];

                foreach ($row as &$item) {
                    $item = iconv("utf-8", "gbk//IGNORE", strip_tags($item));
                }
                fputcsv($fp, $row);
                unset($row);
            }
            //for
            unset($list);
        }
        //while

        $memory_end = memory_get_usage();
        $memory_use = intval(($memory_end - $memory_start) / (1024 * 1024)) . "mb";
        self::log(array(__FUNCTION__, __LINE__, $memory_use));


        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportCoupouLog',
                'analyze' => $sql
            )
        );

        exit;
    }

    /**
     * 导出excel
     */
    public function get_export_csv() {

        $title = "投资记录ID, 创建时间, 投资人ID, 投资人会员名称, 投资人姓名,标的ID, 标的名称, 标的期限, 标的状态, 投资金额,推荐人姓名, 机构会员名称, 优惠券短码, 结算比例系数, 客户系数,投资人返点比例金额, 推荐人返点比例, 推荐人返点比例金额, 机构返点比例, 机构返点比例金额,结算状态, 结算时间";
        $content = iconv('utf-8', 'gbk', $title) . "\n";

        $module = $_REQUEST['module_name'];
        $param = $this->_getParam();//获取查询条件

        if (!empty($param)) {
            $couponLogService = new CouponLogService($module);
            $list = $couponLogService->getListByParams($param);
            $this->form_index_list_common($list, $module);
            foreach ($list as $k => $v) {
                $row = '';
                $row .= $v['deal_load_id'];
                $row .= "," . to_date($v['create_time']);
                $row .= "," . $v['consume_user_id'];
                $row .= "," . $v['consume_user_name'];
                $row .= "," . $v['consume_real_name'];
                $row .= "," . $v['deal_id'];
                $row .= "," . $v['deal_name'];
                $row .= "," . $v['repay_time'];
                $row .= "," . $v['deal_status'];
                $row .= "," . $v['deal_load_money'];
                $row .= "," . $v['refer_real_name'];
                $row .= "," . $v['agency_user_name'];
                $row .= "," . $v['short_alias'];
                $row .= "," . $v['referer_rebate_ratio_factor'];
                $row .= "," . $v['discount_ratio'];
                $row .= "," . $v['rebate_ratio_amount'];
                $row .= "," . $v['referer_rebate_ratio'];
                $row .= "," . $v['referer_rebate_ratio_amount'];
                $row .= "," . $v['agency_rebate_ratio'];
                $row .= "," . $v['agency_rebate_ratio_amount'];
                $row .= "," . $v['pay_status'];
                $row .= "," . to_date($v['pay_time']);

                $row = strip_tags($row);
                $content .= iconv('utf-8', 'gbk', $row) . "\n";
            }
        }

        $datatime = date("YmdHis", get_gmtime());
        header("Content-Disposition: attachment; filename=deal_load_list_{$datatime}.csv");
        echo $content;
    }


    /**
     * 获取用户信息
     */
    protected function get_user_data($user_id)
    {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return array();
        }
        static $user_info = array();
        if (!isset($user_info[$user_id])) {
            $user_data = UserModel::instance()->find($user_id, 'user_name,real_name,mobile,group_id,new_coupon_level_id coupon_level_id,create_time');
            if ($user_data) {
                $user_info[$user_id] = $user_data->getRow();
            } else {
                return array();
            }
        }
        return $user_info[$user_id];
    }

    /**
     * 获取标的信息
     */
    protected function get_deal_data($deal_id, $module = 'p2p')
    {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $deal_info = array();
        $couponService = new CouponService($module);
        $deal_data = $couponService->getDealInfoByDealId($deal_id);
        $deal_info[$deal_id] = $deal_data;
        return $deal_info[$deal_id];
    }

    /**
     * 获取标的信息
     */
    protected function get_coupon_deal_data($deal_id)
    {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $coupon_deal_info = array();
        if (!isset($coupon_deal_info[$deal_id])) {
            $coupon_deal = CouponDealModel::instance()->findByViaSlave('deal_id=:deal_id', 'pay_type,pay_auto', array(':deal_id' => $deal_id));
            if ($coupon_deal) {
                $coupon_deal_info[$deal_id]['pay_type'] = empty($coupon_deal) ? CouponDealModel::PAY_TYPE_FANGKUAN : $coupon_deal['pay_type'];
                $coupon_deal_info[$deal_id]['pay_auto'] = empty($coupon_deal) ? CouponDealModel::PAY_AUTO_NO : $coupon_deal['pay_auto'];
            } else {
                return array();
            }
        }
        return $coupon_deal_info[$deal_id];
    }

    /**
     * 获取标的分站信息
     */
    protected function get_deal_site_data($deal_id)
    {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $deal_info = array();
        if (!isset($deal_info[$deal_id])) {
            $deal_data = get_deal_domain($deal_id, true);
            if ($deal_data) {
                $deal_info[$deal_id] = $deal_data;
            } else {
                return array();
            }
        }
        return $deal_info[$deal_id];
    }

    /**
     * 获取投资记录信息
     */
    protected function get_deal_load_data($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return array();
        }
        static $deal_load_info = array();
        if (!isset($deal_load_info[$id])) {
            $deal_load_data = DealLoadModel::instance()->find($id, 'id l_id, deal_id l_deal_id, user_id l_user_id, user_name l_user_name, money l_money,
            create_time l_create_time,source_type l_source_type, deal_type l_deal_type, site_id l_site_id');
            if ($deal_load_data) {
                $deal_load_info[$id] = $deal_load_data->getRow();
            } else {
                return array();
            }
        }
        return $deal_load_info[$id];
    }

    protected function get_platform_data($client_id){
        $platformInfo = PlatformService::getPlatformInfo();
        return isset($platformInfo[$client_id])?$platformInfo[$client_id]:'';
    }

    /**
     * 添加优惠券
     */
    public function add()
    {
        $id = intval($_REQUEST['id']);
        $dealLoad = M('DealLoad')->where('id=' . $id)->find();
        $userInfo = M('User')->where('id=' . intval($dealLoad['user_id']))->find();
        $this->assign('user', $userInfo);
        $this->assign('deal_load', $dealLoad);
        $this->display();
    }

    /**
     * 添加优惠券入库
     */
    public function addCouponLog()
    {
        filter_request($_POST);
        self::log(array(__FUNCTION__, __LINE__, json_encode($_POST)));
        $form = D(MODULE_NAME);
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        $dealLoad = M('DealLoad')->where('id=' . intval($data['deal_load_id']))->find();
        $couponService = new CouponService();
        $coupon = $couponService->checkCoupon($data['short_alias']);
        if ((empty($coupon) && $data['short_alias'] != CouponService::SHORT_ALIAS_DEFAULT) || $coupon['coupon_disable']) {
            $this->error($GLOBALS['lang']['COUPON_DISABLE']);
        }

        $deal_status = M('Deal')->where('id=' . intval($dealLoad['deal_id']))->getField('deal_status');
        if (intval($deal_status) === 3) { //流标 不操作
            $this->error('已经流标不能添加优惠券');
        }

        $coupon_log = new CouponLogModel();
        $existDealLoad = $coupon_log->findByDealLoadId($dealLoad['id']);
        if (!empty($existDealLoad)) {
            $this->error('该投标已经存在优惠券返利记录');
        }

        $dealChannelLog = M('DealChannelLog')->where('is_delete=0 AND deal_load_id=' . intval($dealLoad['id']))->find();
        if (!empty($dealChannelLog)) {
            $this->error('该投标已经存在邀请返利记录');
        }

        if (!empty($data['rebate_amount']) && $data['rebate_amount'] > 0) {
            $existOneConsumeOneRebate = $coupon_log->isExistOneConsumeOneRebate($dealLoad['user_id'], $dealLoad['deal_id']);
            if (!empty($existOneConsumeOneRebate)) {
                $this->error('投标用户不能重复获得该订单的返利金额，返利金额字段请设置为0');
            }
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $extions = array('admin_id' => intval($adm_session['adm_id']),
            'deal_status' => ($deal_status == 5 || $deal_status == 4) ? 1 : 0,
            'add_type' => 2
        );
        //费率 处理
        //if (isset($data['referer_rebate_ratio_factor']) && $data['referer_rebate_ratio_factor'] != '') {
        //    $extions['referer_rebate_ratio_factor'] = $data['referer_rebate_ratio_factor'];
        //}
        //返点金额
        if (isset($data['rebate_amount']) && $data['rebate_amount'] != '') {
            $extions['rebate_amount'] = $data['rebate_amount'];
        }
        //返点比例
        if (isset($data['rebate_ratio']) && $data['rebate_ratio'] != '') {
            $extions['rebate_ratio'] = $data['rebate_ratio'];
        }
        //推荐人返点金额
        if (isset($data['referer_rebate_amount']) && $data['referer_rebate_amount'] != '') {
            $extions['referer_rebate_amount'] = $data['referer_rebate_amount'];
        }
        //推荐人返点比例
        if (isset($data['referer_rebate_ratio']) && $data['referer_rebate_ratio'] != '') {
            $extions['referer_rebate_ratio'] = $data['referer_rebate_ratio'];
        }
        //机构返点金额
        if (isset($data['agency_rebate_amount']) && $data['agency_rebate_amount'] != '') {
            $extions['agency_rebate_amount'] = $data['agency_rebate_amount'];
        }
        //机构返点比例
        if (isset($data['agency_rebate_ratio']) && $data['agency_rebate_ratio'] != '') {
            $extions['agency_rebate_ratio'] = $data['agency_rebate_ratio'];
        }

        $referUserId = $coupon['refer_user_id'];
        if (empty($data['refer_user_id']) && !empty($referUserId)) {
            $data['refer_user_id'] = $referUserId;
        } else { //效验用户 id  和短码绑定是否一致
            if ($referUserId != $data['refer_user_id']) {
                $this->error('推荐会员ID与优惠券短码不一致');
            }
        }

        $agencyUserId = $coupon['agency_user_id'];
        if (empty($data['agency_user_id']) && !empty($agencyUserId)) {
            $data['agency_user_id'] = $agencyUserId;
        } else { //效验用户 id  和短码绑定是否一致
            if ($agencyUserId != $data['agency_user_id']) {
                $this->error('机构会员ID与优惠券短码不一致');
            }
        }

        if ($data['short_alias'] == CouponService::SHORT_ALIAS_DEFAULT) {
            $coupon['short_alias'] = CouponService::SHORT_ALIAS_DEFAULT;
        }

        $coupon_log_service = new CouponLogService();
        $return = $coupon_log_service->addLog($coupon, $dealLoad['user_id'], $dealLoad['id'], $extions);
        self::log(array(__FUNCTION__, __LINE__, $return, json_encode($data), json_encode($coupon), json_encode($return->getRow())));
        if ($return) {
            save_log("[{$return['id']}][{$dealLoad['id']}]" . L("LOG_STATUS_1"), 1, '', array($data, $return->getRow()));
            $this->success('添加优惠券成功');
        } else {
            $this->error('添加优惠券失败');
        }

    }

    /**
     * 多投保和p2p 显示编辑页面
     */
    public function edit()
    {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        // model 类型
        $type = addslashes($_GET['type']);
        if (empty($id) && empty($type)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $mModel = 'CouponLog' . ucfirst($type);
        $vo = M($mModel)->where($condition)->find();
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 更新优惠券操作
     * @see CommonAction::update()
     */
    public function update()
    {
        self::log(array(__FUNCTION__, __LINE__, json_encode($_POST)));
        $id = intval($_POST['id']);
        $type = addslashes($_POST['type']);
        if (empty($id) && empty($type)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $mModel = 'CouponLog' . ucfirst($type);
        $form = D($mModel);
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }
        unset($data['type']);
        $short_alias = $data['short_alias'];
        $couponInfo = M($mModel)->where('id=' . $id)->find();
        self::log(array(__FUNCTION__, __LINE__, json_encode($couponInfo)));
        if (in_array($couponInfo['deal_type'], CouponLogService::$deal_type_group1) && ($data['rebate_days'] < 0 || !is_numeric($data['rebate_days']))) {
            $this->error('返利天数字段不能小于零');
        }
        if (!empty($data['rebate_amount']) && $data['rebate_amount'] > 0) {
            $coupon_log = CouponLogModel::getInstance($type);
            $existOneConsumeOneRebate = $coupon_log->isExistOneConsumeOneRebate($couponInfo['consume_user_id'], $couponInfo['deal_id'], $couponInfo['id']);
            if (!empty($existOneConsumeOneRebate)) {
                $this->error('投标用户不能重复获得该订单的返利金额，返利金额字段请设置为0');
            }
        }

        $data['discount_ratio'] = bcadd($data['discount_ratio'], 0,2);
        if (bccomp($data['discount_ratio'], 0,2) < 1  || bccomp($data['discount_ratio'], 1,2) > 0 ) {
            $this->error('数据格式错误');
        }

        $couponService = new CouponService();
        if ($short_alias != $couponInfo['short_alias']) { //短码有变化  优惠券互相切换
            $coupon = $couponService->checkCoupon($short_alias);
            if ($coupon !== false && !$coupon['coupon_disable']) {
                //更新优惠券信息
                $data['short_alias'] = $coupon['short_alias'];
            }
        }
        if ($short_alias == CouponService::SHORT_ALIAS_DEFAULT) {
            $data['short_alias'] = CouponService::SHORT_ALIAS_DEFAULT;
        }

        //推荐人有变化，更新推荐人用户名
        if ($data['refer_user_id'] != $couponInfo['refer_user_id']) {
            $user_model = new UserModel();
            $refer_user_info = $user_model->find($data['refer_user_id'], 'user_name');
            if (!empty($refer_user_info)) {
                $data['refer_user_name'] = $refer_user_info['user_name'];
            }
        }

        $data['update_time'] = get_gmtime();
        $r = M($mModel)->where('id=' . $id)->save($data);
        self::log(array(__FUNCTION__, __LINE__, $r, json_encode($data)));
        if (!$r) {
            $this->error('优惠券编辑失败');
        }

        // 更新金额计算 ,如果标是普通标类型，则更新返点比例金额，通知贷不更新，因为通知到返点比例金额要做累加，修改了优惠码会把已经反的比例金额按照新的邀请码计算
        $type = empty($type) ? CouponLogService::MODULE_TYPE_P2P : $type;
        if (in_array($couponInfo['deal_type'], CouponLogService::$deal_type_group1)) {
            $coupon_log_service = new CouponLogService($type);
            $coupon_log_service->updateAmount($id);
        }
        save_log("[{$id}][{$couponInfo['deal_load_id']}]" . L("LOG_STATUS_1"), 1, $couponInfo, $data);
        $this->success('优惠券修改成功');
    }

    public function fixShortAlias()
    {

        self::log(array(__FUNCTION__, __LINE__, json_encode($_POST)));
        $id = intval($_GET['id']);
        if (empty($id)) {
            $this->error('优惠券数据id为空');
        }
        $couponInfo = M('CouponLog')->where('id=' . $id)->find();
        if (empty($couponInfo)) {
            $this->error('邀请码记录不能为空');
        }
        if ($couponInfo['short_alias'] != 'F00000') {
            $this->error('邀请码必须为空码');
        }
        //检查，只有未结算的普通标可以改
        if ($couponInfo['pay_status'] == 1 || $couponInfo['pay_status'] == 2) {
            $this->error('已经结算');
        }
        $deal_load = M('DealLoad')->where('id=' . $couponInfo['deal_load_id'])->find();
        if (empty($deal_load['short_alias'])) {
            $this->error('投资记录邀请码为空');
        }
        if ($deal_load['short_alias'] == $couponInfo['short_alias']) {
            $this->error('邀请码一致');
        }

        $couponService = new CouponService();
        $coupon = $couponService->checkCoupon($deal_load['short_alias']);
        if (empty($coupon)) {
            $this->error('邀请码错误');
        }
        //$data['id'] = $couponInfo['id'];
        $data['short_alias'] = $coupon['short_alias'];
        $data['refer_user_id'] = $coupon['refer_user_id'];
        $data['agency_user_id'] = $coupon['agency_user_id'];
        $data['rebate_amount'] = $coupon['rebate_amount'];
        $data['rebate_ratio'] = $coupon['rebate_ratio'];
        $data['referer_rebate_ratio'] = $coupon['referer_rebate_ratio'];
        $data['agency_rebate_ratio'] = $coupon['agency_rebate_ratio'];
        $data['referer_rebate_amount'] = $coupon['referer_rebate_amount'];
        $data['agency_rebate_amount'] = $coupon['agency_rebate_amount'];


        //推荐人有变化，更新推荐人用户名
        if ($data['refer_user_id'] != $couponInfo['refer_user_id']) {
            $user_model = new UserModel();
            $refer_user_info = $user_model->find($data['refer_user_id'], 'user_name');
            if (!empty($refer_user_info)) {
                $data['refer_user_name'] = $refer_user_info['user_name'];
            }
        }

        $data['update_time'] = get_gmtime();
        $r = M('CouponLog')->where('id=' . $id)->save($data);
        self::log(array(__FUNCTION__, __LINE__, $r, json_encode($data)));
        if (!$r) {
            $this->error('优惠券编辑失败');
        }
        $coupon_log_service = new CouponLogService();
        // 更新金额计算
        $coupon_log_service->updateAmount($id);
        save_log("[{$id}][{$couponInfo['deal_load_id']}]" . L("LOG_STATUS_1"), 1, $couponInfo, $data);
        $this->success('优惠券修改成功');

    }

    /**
     * 修复注册未进coupon_log
     * @param $userid
     */
    public function regCoupon()
    {
        $user_id = intval($_REQUEST['user_id']);
        if (empty($user_id)) {
            $this->error('参数错误');
        }

        $user_model = new UserModel();
        $user_info = $user_model->find($user_id, 'invite_code');
        if (empty($user_info['invite_code'])) {
            $this->error('邀请码为空');
        }
        $coupon_service = new CouponService();
        $ret = $coupon_service->regCoupon($user_id, $user_info['invite_code'], CouponLogService::ADD_TYPE_ADMIN);
        if ($ret) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 运营通过
     */
    public function operation_passed()
    {
        $id_list = $this->get_id_list();
        self::log(array(__FUNCTION__, __LINE__, json_encode($id_list), 'start'));
        if (empty($id_list)) {
            $this->error('参数错误');
        }
        $coupon_service = new CouponService();
        $coupon_log_model = new CouponLogModel();
        $deal_model = new DealModel();
        $log_info = "结算运营通过:" . implode(',', $id_list);
        foreach ($id_list as $id) {
            $coupon_log = $coupon_log_model->find($id, 'deal_type,deal_id');
            // 不处理通知贷
            if ($coupon_log['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                continue;
            }
            $coupon_deal_info = M("CouponDeal")->where("deal_id=" . $coupon_log['deal_id'])->getField("pay_type");
            $deal_info = $deal_model->find($coupon_log['deal_id'], 'deal_status');
            // 优惠码结算时间为还清时
            if (($coupon_deal_info['pay_type'] == 1 && $deal_info['deal_status'] != 5)) {
                continue;
            }
            $result = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_FINANCE_AUDIT);
            if (!empty($result['message']) || $result['code'] != 0) {
                self::log(array(__FUNCTION__, __LINE__, json_encode($id_list), $id, 'fail'));
                save_log($log_info . L("LOG_STATUS_0"), 0);
                $this->error('操作失败');
                exit;
            }
        }
        self::log(array(__FUNCTION__, __LINE__, json_encode($id_list), 'success'));
        save_log($log_info . L("LOG_STATUS_1"), 1);
        $this->display_success('操作成功');
    }

    /**
     * 财务审核 (拒绝,通过)
     */
    public function finance_audit()
    {
        // 增加开关控制
        $turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
        if ($turn_on_coupon_pay == 1) {
            $this->error('系统禁止结算，请与管理员联系');
            exit;
        }
        $is_passed = trim($_GET['is_passed']);
        $id_list = $this->get_id_list();
        self::log(array(__FUNCTION__, __LINE__, $is_passed, json_encode($id_list), 'start'));
        if (empty($id_list) || !in_array($is_passed, array(1, 2))) {
            $this->error('参数错误');
            exit;
        }

        $coupon_service = new CouponService();
        $coupon_log_service = new CouponLogService();
        $coupon_log_model = new CouponLogModel();
        $deal_model = new DealModel();
        $log_info = "结算财务" . ($is_passed == 1 ? '通过' : '拒绝') . ":" . implode(',', $id_list);
        $is_success = true;
        foreach ($id_list as $id) {
            $coupon_log = $coupon_log_model->find($id, 'deal_type,deal_id');
            // 不处理通知贷
            if ($coupon_log['deal_type'] == CouponLogService::DEAL_TYPE_COMPOUND) {
                continue;
            }

            if ($is_passed == 1) { // 财务通过
                try {
                    $coupon_deal_info = M("CouponDeal")->where("deal_id=" . $coupon_log['deal_id'])->getField("pay_type");
                    $deal_info = $deal_model->find($coupon_log['deal_id'], 'deal_status');
                    // 优惠码结算时间为还清时
                    if (($coupon_deal_info['pay_type'] == 1 && $deal_info['deal_status'] != 5)) {
                        continue;
                    }
                    $is_success = $coupon_log_service->pay($id); //返利支出
                } catch (Exception $e) {
                    self::log(array(__FUNCTION__, __LINE__, $is_passed, json_encode($id_list), $id, 'fail' . $e->getMessage()));
                    save_log($log_info . L("LOG_STATUS_0"), 0);
                    $this->error('系统繁忙，请稍后重试');
                    exit;
                }
            } else if ($is_passed == 2) { // 财务拒绝
                $result = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_FINANCE_REJECTED);
                if (!empty($result['message']) || $result['code'] != 0) {
                    $is_success = false;
                }
            }
            if ($is_success === false) { //有失败即停止操作
                self::log(array(__FUNCTION__, __LINE__, $is_passed, json_encode($id_list), $id, 'fail'));
                save_log($log_info . L("LOG_STATUS_0"), 0);
                $this->error('操作失败');
                exit;
            }
        }

        self::log(array(__FUNCTION__, __LINE__, $is_passed, json_encode($id_list), 'success'));
        save_log($log_info . L("LOG_STATUS_1"), 1);
        $this->display_success('操作成功');
    }

    /**
     * ajax校验优惠码
     */
    public function check_short_alias()
    {
        $return = array("status" => 0, "message" => "");
        $short_alias = $_REQUEST['short_alias'];
        $deal_id = $_REQUEST['deal_id'];
        $type = addslashes($_REQUEST['type']);
        if (empty($short_alias)) {
            return ajax_return($return);
        }
        if ($short_alias == CouponService::SHORT_ALIAS_DEFAULT) {
            $return = array("status" => 1, "message" => "");
            return ajax_return($return);
        }
        $coupon_service = new CouponService();
        $coupon = $coupon_service->queryCoupon($short_alias, true);
        if (empty($coupon)) {
            return ajax_return(array("status" => 0, "message" => "不正确"));
        } else if (!$coupon['is_effect']) {
            return ajax_return(array("status" => 0, "message" => "不适应此项目"));
        } else if ($coupon['coupon_disable']) {
            return ajax_return(array("status" => 0, "message" => $GLOBALS['lang']['COUPON_DISABLE']));
        }
        $return = array("status" => 1, "message" => "", "data" => $coupon);
        return ajax_return($return);
    }

    /**
     * 记录日志
     */
    protected static function log($log)
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $admin_name = $adm_session['adm_name'];
        $log = array_merge(array(__CLASS__, APP, $admin_name), $log);
        logger::info(implode(" | ", $log));
    }

    /**
     * user_id转用户名称并加链接
     *
     * @param $user_id
     * @param $text
     * @return string
     */
    private function _get_user_link($user_id, $text)
    {
        return "<a href='" . u("User/index", array('user_id' => $user_id)) . "' target='_blank'>" . $text . "</a>";
    }

    /**
     * 删除数据
     */
    function delete()
    {

        return true; //暂时去掉 删除功能

        $id = $_GET['id'];
        if (!is_numeric($id)) {
            $this->error('参数错误', 1);
        }
        // 记录开始操作日志
        self::log(array(__FUNCTION__, __LINE__, 'delete', $id, 'start'));
        $coupon_log_obj = new CouponLogModel();
        $get_coupon_log_info = $coupon_log_obj->find($id);
        if (empty($get_coupon_log_info)) {
            $this->error('删除的信息不存在', 1);
        }
        $coupon_log_bak_obj = new CouponLogBakModel();
        // 开始事务
        $GLOBALS['db']->startTrans();

        try {
            $coupon_log_bak_result = $coupon_log_bak_obj->backupForDetele($id);
            if ($coupon_log_bak_result === false) {
                throw new Exception('插入备份表失败');
            }
            $coupon_log_del = $get_coupon_log_info->remove();
            if ($coupon_log_del === false) {
                throw new Exception('删除couponlog失败');
            }
            $GLOBALS['db']->commit();
            self::log(array(__FUNCTION__, __LINE__, 'cp coupon_log to bak', $id, 'success', json_encode($get_coupon_log_info->getRow())));
            save_log("[{$id}][{$get_coupon_log_info['deal_load_id']}]删除成功", 1, '', $get_coupon_log_info->getRow());
            $this->success('删除成功', 1, '?m=CouponLog&a=index');
        } catch (Exception $e) {
            $GLOBALS['db']->rollback();
            self::log(array(__FUNCTION__, __LINE__, 'cp coupon_log to bak', $id, 'fail', json_encode($get_coupon_log_info->getRow()), $e->getMessage()));
            $this->ajaxReturn('删除失败', '', 1);
        }

        $this->ajaxReturn('删除失败', '', 1);
    }

    public function duotoulist()
    {
        $this->commonlist(CouponLogService::MODULE_TYPE_DUOTOU);
    }

    public function goldlist()
    {
        $this->commonlist(CouponLogService::MODULE_TYPE_GOLD);
    }

    public function goldclist()
    {
        $this->commonlist(CouponLogService::MODULE_TYPE_GOLDC);
    }

    public function darkmoonlist()
    {
        $this->commonlist(CouponLogService::MODULE_TYPE_DARKMOON);
    }

    public function ncfphlist()
    {
        $this->commonlist(CouponLogService::MODULE_TYPE_NCFPH);
    }

    public function thirdlist(){
        $_REQUEST['deal_type'] =0;
        $this->commonlist(CouponLogService::MODULE_TYPE_THIRD);
    }

    public function thirdplist(){
        $_REQUEST['deal_type'] =2;
        $this->commonlist(CouponLogService::MODULE_TYPE_THIRD,'thirdplist');
    }

    /**
     * 通用列表
     */
    private function commonlist($module,$tpl='')
    {
        $param = $this->_getParam($module);//获取查询条件
        $couponLogService = new CouponLogService($module);
        if($this->pageEnable === false){
            $count = self::LIST_WITHOUT_PAGE_MAX;
        }else{
            $count = $couponLogService->getCountByParams($param);
        }

        $p = new Page ($count, 20);//分页类

        $list = array();
        if ($count > 0) {

            $param['firstRow'] = $p->firstRow;
            $param['listRows'] = $p->listRows;

            $list = $couponLogService->getListByParams($param);
            $this->form_index_list_common($list, $module);

        }

        $this->assign('module_name', $module);
        $this->assign('module_text', CouponLogService::$module_name_map[$module]);
        $this->assign('platform', PlatformService::getPlatformInfo());
        $this->assign('list', $list);
        $this->assign("page", $p->show($this->pageEnable,count($list)));
        $this->assign('_sort', ($_REQUEST['_sort'] == 'desc') ? 'asc' : 'desc');
        $this->assign("nowPage", $p->nowPage);
        $this->assign ( "totalPages", $p->totalPages);
        $this->assign ( "totalRows", $p->totalRows);
        $this->display($tpl?$tpl:$module . 'list');

    }

    private function _getParam($module = 'p2p')
    {
        $param = array();
        $userModel = new UserModel();
        if (!empty($_REQUEST['consume_user_id'])) {
            $param['consume_user_id'] = intval($_REQUEST['consume_user_id']);
        } elseif (!empty($_REQUEST['consume_real_name'])) {
            $userModel = new UserModel();
            $userIds = $userModel->getUserIdsByRealName(addslashes(trim($_REQUEST['consume_real_name'])));
            if (!empty($userIds)) {
                $param['consume_user_id'] = $userIds;
            } else {
                $param['consume_user_id'] = 0;
            }
        } elseif (!empty($_REQUEST['mobile'])) {
            $param['consume_user_id'] = $userModel->getUserIdByMobile(intval($_REQUEST['mobile']));
        } elseif (!empty($_REQUEST['consume_user_name'])) {
            $userInfo = $userModel->getUserinfoByUsername(trim($_REQUEST['consume_user_name']));
            $param['consume_user_id'] = empty($userInfo['id']) ? '' : $userInfo['id'];
        }

        if (!empty($_REQUEST['refer_user_id'])) {
            $param['refer_user_id'] = intval($_REQUEST['refer_user_id']);
        }elseif (!empty($_REQUEST['refer_user_name'])) {
            $userInfo= $userModel->getUserinfoByUsername(trim($_REQUEST['refer_user_name']));
            $param['refer_user_id'] = empty($userInfo['id']) ? '' : $userInfo['id'];
        }

        if (!empty($_REQUEST['refer_user_name'])) {
            $param['refer_user_name'] = addslashes(trim($_REQUEST['refer_user_name']));
        }

        if (!empty($_REQUEST['agency_user_name'])) {
            $userModel = new UserModel();
            $userId = $userModel->findByViaSlave("user_name='" . trim($_REQUEST['agency_user_name']) . "'", 'id');
            if (!empty($userId)) {
                $param['agency_user_id'] = $userId['id'];
            } else {
                $param['agency_user_id'] = 0;
            }
        }

        if (!empty($_REQUEST['deal_load_id'])) {
            $param['deal_load_id'] = intval($_REQUEST['deal_load_id']);
        }

        if (!empty($_REQUEST['short_alias'])) {
            $param['short_alias'] = addslashes(trim($_REQUEST['short_alias']));
        }

        if (isset($_REQUEST['pay_status']) && $_REQUEST['pay_status'] !== '') {
            $param['pay_status'] = intval($_REQUEST['pay_status']);
        }

        if (!empty($_REQUEST['deal_id'])) {
            if($module == CouponLogService::MODULE_TYPE_THIRD){
                //第三方标id转换
                $thirdDealService = new ThirdDealService();
                $param['deal_id'] = $thirdDealService->getIdByDealId(trim($_REQUEST['deal_id']));
            }else{
                $param['deal_id'] = intval($_REQUEST['deal_id']);
            }
        } elseif (!empty($_REQUEST['deal_name'])) {
            $couponService = new CouponService(CouponLogService::MODULE_TYPE_DUOTOU);
            $dealInfo = $couponService->getDealInfoByName(addslashes(trim($_REQUEST['deal_name'])));

            if (!empty($dealInfo)) {
                $param['deal_id'] = array_keys($dealInfo);

            } else {
                $param['deal_id'] = 0;
            }
        }

        if (!empty($_REQUEST['create_time_begin'])) {
            $param['create_time_begin'] = to_timespan($_REQUEST['create_time_begin']);
        }

        if (!empty($_REQUEST['create_time_end'])) {
            $param['create_time_end'] = to_timespan($_REQUEST['create_time_end']);
        }

        if (!empty($_REQUEST['pay_time_begin'])) {
            $param['pay_time_begin'] = to_timespan($_REQUEST['pay_time_begin']);
        }

        if (!empty($_REQUEST['pay_time_end'])) {
            $param['pay_time_end'] = to_timespan($_REQUEST['pay_time_end']);
        }

        if(!empty($_REQUEST['client_id'])){
            $param['client_id'] = addslashes(trim($_REQUEST['client_id']));
        }

        if(isset($_REQUEST['deal_type'])){
            $param['deal_type'] = intval($_REQUEST['deal_type']);
        }

        if (isset ($_REQUEST ['_order']) && !empty($_REQUEST['_order'])) {
            $param['_order'] = addslashes(trim($_REQUEST ['_order']));
        }
        if (isset ($_REQUEST ['_sort'])) {
            $param['_sort'] = intval($_REQUEST ['_sort']);
        }
        $sortAlt = intval($param['_sort']) ? l("DESC_SORT") : l("ASC_SORT");
        $this->assign('sort', intval($param['_sort']) ? 0 : 1);
        $this->assign('order', $_REQUEST ['_order']);
        $this->assign('sortImg', intval($param['_sort']) ? 'desc' : 'asc');
        $this->assign('sortType', $sortAlt);

        return $param;
    }

    protected function form_index_list_common(&$list, $module = 'p2p')
    {
        if (empty($list)) {
            return false;
        }
        $userModel = new UserModel();
        foreach ($list as $key => &$item) {

            $realName = $userModel->find($item['consume_user_id'], 'real_name', true);
            $list[$key]['consume_real_name'] = isset($realName['real_name']) ? $realName['real_name'] : '--';
            $realName = $userModel->find($item['refer_user_id'], 'real_name', true);
            $list[$key]['refer_real_name'] = isset($realName['real_name']) ? $realName['real_name'] : '--';
            $userName = $userModel->find($item['agency_user_id'], 'user_name', true);
            $list[$key]['agency_user_name'] = isset($userName['user_name']) ? $userName['user_name'] : '--';
            $item['opt_edit'] = ''; //编辑
            $item['opt_pay_list'] = ''; // 返利明细

            $data = $this->get_deal_data($item['deal_id'], $module);

            $item['deal_name'] = $data['name'];

            $item['repay_time'] = $data['repay_time'] . ($data['loantype'] == 5 ? '天' : '个月');

            $status = array('待等材料', '进行中', '满标', '流标', '还款中', '已还清');
            $item['deal_status'] = $status[$data['deal_status']];

            $item['consume_user_name'] = $this->_get_user_link($item['consume_user_id'], $item['consume_user_name']);
            $item['consume_real_name'] = $this->_get_user_link($item['consume_user_id'], $item['consume_real_name']);
            if (!empty($item['refer_real_name'])) {
                $item['refer_real_name'] = $this->_get_user_link($item['refer_user_id'], $item['refer_real_name']);
            }
            if (!empty($item['agency_user_name'])) {
                $item['agency_user_name'] = $this->_get_user_link($item['agency_user_id'], $item['agency_user_name']);
            }
            $item["referer_rebate_ratio"] = round($item['referer_rebate_ratio'], 2);
            $item["agency_rebate_ratio"] = round($item['agency_rebate_ratio'], 2);
            if (in_array($item['deal_type'], CouponLogService::$deal_type_group1)) { //非通知贷类型，一次性结算
                // 默认状态或者财务拒绝或者财务待审核
                if (in_array($item['pay_status'], array(0, 4, 5))) {
                    $item['opt_edit'] = "<span id='channel_edit_" . $item['id'] . "'><a href='javascript:javascript:weeboxs_edit(" . $item['id'] . ");'>编辑</a></span>";
                }
            } else { //通知贷类型，有周结算明细
                if (in_array($item['pay_status'], array(1, 2, 5)) && !in_array($item['deal_type'], CouponLogService::$deal_type_group1)) {
                    $item['opt_pay_list'] = '<a href="javascript:pay_list(\'' . $item['deal_load_id'] . '\');">查看明细</a>';
                }
            }

            if($module == CouponLogService::MODULE_TYPE_THIRD){
                $list[$key]['platform'] = $this->get_platform_data($item['client_id']);
                $list[$key]['deal_id'] = $data['third_deal_Id'];
            }

            $list[$key]['pay_status'] = self::$pay_status_list[$item['pay_status']];

        }
        return $list;
    }

//http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-4062
    /*1.投资人投资返利（投资人返点比例）调整至0
    2.队列待上标的投资人投资返利调整至0
    */
    public function updateCouponLevelRebate()
    {//更新 firstp2p_coupon_level_rebate  by gengkuan  2016-10-11
        try {
            // 记录开始操作日志
            save_log("firstp2p_coupon_level_rebate start", 1);
            self::log(array('firstp2p_coupon_level_rebate,start'));
            $this->global_db = $GLOBALS['db'];
            $sql = 'update firstp2p_coupon_level_rebate set rebate_amount = 0,rebate_ratio =0 where deal_id = 0';
            $this->global_db->query($sql);
            // 记录结束日志

        } catch (Exception $e) {
            save_log("firstp2p_coupon_level_rebate fail", 0, $sql);
            echo $e->getMessage();
            exit;
        }
        save_log("firstp2p_coupon_level_rebate success", 1, $sql);
        echo "更新成功";
    }

    public function updateCouponSpecial()
    {//更新 firstp2p_coupon_special by gengkuan  2016-10-11
        try {
            // 记录开始操作日志
            save_log("firstp2p_coupon_special start", 1);
            $this->global_db = $GLOBALS['db'];
            $sql = 'update firstp2p_coupon_special set rebate_amount = 0,rebate_ratio =0 where deal_id = 0';
            $this->global_db->query($sql);
        } catch (Exception $e) {
            save_log("firstp2p_coupon_special fail", 0, $sql);
            echo $e->getMessage();
            exit;
        }
        save_log("firstp2p_coupon_special succes", 1, $sql);
        echo "更新成功";
    }

}
