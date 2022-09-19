<?php
/**
 * DealLoadAction.class.php
 *
 * @date 2013-12-11 11:32
 * @author liangqiang@ucfgroup.com
 */

use app\models\service\Finance;
use core\service\UserGroupService;
use core\dao\UserGroupModel;

class DealLoadAction extends DealChannelLogAction {

    public function __construct() {
        parent::__construct();
        $this->model = M('DealChannelLog');
        $this->pk_name = $this->model->getPk();
    }

    public function index() {
        // 查询总数
        $this->assign('user_group_list', UserGroupModel::instance()->findAll());

        $sql_count = "SELECT COUNT(*)" . $this->_gen_query_sql(true);
        $count = $GLOBALS['db']->getOne($sql_count);
        if (empty($count)) {
            $this->display();
            return;
        }

        // 分页
        $sql_limit = '';
        if (!empty ($_REQUEST ['listRows'])) {
            $listRows = $_REQUEST ['listRows'];
        } else {
            $listRows = '';
        }
        $p = new Page ($count, $listRows);
        $sql_limit = " LIMIT " . $p->firstRow . ", " . $p->listRows;

        // 查询结果列表
        $sql = $this->_gen_query_sql() . $sql_limit;
        $list = $GLOBALS['db']->getAll($sql);

        if (empty($list)) {
            $this->display();
            return;
        }
        $list = \libs\utils\DBDes::encryptOrDecryptBySpecifiedFields(array('du_mobile','u_mobile'),$list,false);
        $this->_form_list_data($list);

        // 输出显示
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->display();

        return;
    }

    public function delete() {
        parent::delete();
    }

    /**
     * 导出excel
     */
    public function export_csv() {
        $sql = $this->_gen_query_sql();
        $list = $GLOBALS['db']->getAll($sql);
        if (empty($list)) {
            $this->error(L("NO_RESULT"));
        }

        $this->_form_list_data($list);

        $title = "投资记录ID,成交时间,投资人ID,投资人会员名称,投资人姓名,投资人手机号,投资金额,渠道来源,借款编号,借款标题,借款人会员名称,借款人姓名,借款人手机号,借款期限,推广人会员名称,推广人姓名,推广记录类型,订单状态,结算状态,返利金额";
        $content = iconv('utf-8', 'gbk', $title) . "\n";
        foreach ($list as $k => $v) { // 慢慢挑
            $row = '';
            $row .= $v['l_id'];
            $row .= "," . to_date($v['l_create_time']);
            $row .= "," . $v['u_id'];
            $row .= "," . $v['u_user_name'];
            $row .= "," . $v['u_real_name'];
            $row .= "," . $v['u_mobile'];
            $row .= ",\"" . $v['l_money'] . "\"";
            $row .= "," . $v['user_group_name'];
            $row .= "," . $v['d_id'];
            $row .= "," . $v['d_name'];
            $row .= "," . $v['du_user_name'];
            $row .= "," . $v['du_real_name'];
            $row .= "," . $v['du_mobile'];
            $row .= "," . $v['d_repay_time'];
            $row .= "," . $v['channel_name'];
            $row .= "," . $v['cu_real_name'];
            $row .= "," . $v['add_type'];
            $row .= "," . $v['cl_deal_status'];
            $row .= "," . $v['fee_status'];
            $row .= ",\"" . $v['pay_fee'] . "\"";
            $row = strip_tags($row);
            $content .= iconv('utf-8', 'gbk', $row) . "\n";
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportDealLoad',
                'analyze' => $sql
                )
        );


        $datatime = date("YmdHis", get_gmtime());
        header("Content-Disposition: attachment; filename=deal_load_list_{$datatime}.csv");
        echo $content;
    }

    /**
     * 拼装sql
     *
     * @param bool $is_sql_for_count 为count仅返回where子句
     * @return string
     */
    private function _gen_query_sql($is_sql_for_count = false) {
        // 这个拼装感觉不适的话，就改php分步实现吧(支持排序较难)....这个sql越来越令人崩溃了
        $sql = "SELECT u.id AS u_id, u.user_name AS u_user_name, u.real_name AS u_real_name, u.mobile AS u_mobile,u.group_id,";
        $sql .= " du.id AS du_id, du.user_name AS du_user_name, du.real_name AS du_real_name, du.mobile AS du_mobile,";
        $sql .= " d.id AS d_id, d.name AS d_name, d.repay_time as d_repay_time, d.loantype AS d_loantype, d.deal_status AS d_deal_status,";
        $sql .= " l.id AS l_id, l.deal_id, l.user_id AS l_user_id, l.money AS l_money, l.create_time AS l_create_time,l.site_id,";
        $sql .= " cl.id AS cl_id, cl.add_type, cl.deal_status AS cl_deal_status, cl.fee_status, cl.channel_id, cl.deal_load_money AS cl_deal_load_money,";
        $sql .= " cl.advisor_fee_rate AS cl_advisor_fee_rate, cl.pay_factor AS cl_pay_factor, cl.pay_fee AS cl_pay_fee, cl.create_time AS cl_create_time ,";
        $sql .= " c.name AS channel_name, c.channel_value AS channel_value, c.channel_type,";
        $sql .= " (cl.deal_load_money*cl.advisor_fee_rate*cl.pay_factor*0.01) AS channel_fee";
        $sql_where = " FROM " . DB_PREFIX . "user u, " . DB_PREFIX . "user du, " . DB_PREFIX . "deal d, " . DB_PREFIX . "deal_load l";
        $sql_where .= " LEFT JOIN " . DB_PREFIX . "deal_channel_log cl ON l.id=cl.deal_load_id AND cl.is_delete=0 LEFT JOIN " . DB_PREFIX . "deal_channel c ON cl.channel_id=c.id";
        $sql_where .= " WHERE l.deal_id=d.id AND l.user_id=u.id AND d.user_id=du.id AND d.is_delete=0 AND deal_parent_id<>0";

        //机构管理后台
        $sql_where .= $this->orgCondition(false, 'd');

        if (!empty($_REQUEST['id'])) {
            $sql_where .= " AND l.id = '" . trim($_REQUEST['id']) . "'";
        }
        // 手机号精确匹配，其它模糊匹配
        if (!empty($_REQUEST['user_name'])) {
            $sql_where .= " AND u.user_name LIKE '" . trim($_REQUEST['user_name']) . "%'";
        }
        if (!empty($_REQUEST['real_name'])) {
            $sql_where .= " AND u.real_name LIKE '" . trim($_REQUEST['real_name']) . "%'";
        }
        if (!empty($_REQUEST['mobile'])) {
            $sql_where .= " AND u.mobile = '" . trim($_REQUEST['mobile']) . "'";
        }
        if (!empty($_REQUEST['deal_name'])) {
            $sql_where .= " AND d.name LIKE '" . trim($_REQUEST['deal_name']) . "%'";
        }
        if (!empty($_REQUEST['group_id'])) {
            $sql_where .= " AND u.group_id = " . intval($_REQUEST['group_id']);
        }

        if ($is_sql_for_count) {
            return $sql_where;
        }

        // 排序字段，排序方式默认按照倒序排列 接受_sort参数 0 表示倒序 非0都 表示正序，默认为创建时间倒序
        $sql_order = '';
        if (isset ($_REQUEST ['_order'])) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = !empty ($sortBy) ? $sortBy : 'l_id';
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

        $sql = $sql . $sql_where . $sql_order;
        return $sql;
    }

    /**
     * 各种处理，看代码理解吧。。。
     *
     * 结清：顾问类型，订单状态是还款中
     * 编辑：未结清
     * 删除：未结清
     *
     * @param $list
     */
    private function _form_list_data(&$list) {
        $user_group_service = new UserGroupService();
        foreach ($list as &$item) {
            $item['u_user_name'] = $this->_get_user_link($item['u_id'], $item['u_user_name']);
            $item['u_real_name'] = $this->_get_user_link($item['u_id'], $item['u_real_name']);
            $item['du_user_name'] = $this->_get_user_link($item['du_id'], $item['du_user_name']);
            $item['du_real_name'] = $this->_get_user_link($item['du_id'], $item['du_real_name']);
            $item['l_money'] = format_price($item['l_money']);
            $item['d_name'] = "<a href='" . u("Deal/index", array('id' => $item['d_id'])) . "' target='_blank'>" . $item['d_name'] . "</a>";
            $item['d_repay_time'] = $item['d_repay_time'] . ($item['d_loantype'] == 5 ? '天' : '个月');
            $item['d_name'] = "<a href='" . u("Deal/index", array('id' => $item['d_id'])) . "' target='_blank'>" . $item['d_name'] . "</a>";
            $item['cu_real_name'] = '-';
            $item['opt_add'] = '';
            $item['opt_edit'] = '';
            $item['opt_del'] = '';
            $item['opt_pay'] = '';

            $user_group = $user_group_service->getGroupInfo($item['group_id']);
            $item['user_group_name'] = $user_group['name'];
            if (!empty($item['channel_id'])) {

                //if ($item['add_type'] == 2 && $item['fee_status'] == 0) {
                if ($item['fee_status'] == 0) { // 推广链接也能编辑 --20131227
                    $item['opt_edit'] = "<span id='channel_edit_" . $item['cl_id'] . "'><a href='javascript:weebox_edit(" . $item['cl_id'] . ",this);'>编辑</a></span>";;
                    $item['opt_del'] = '<span id="channel_del_' . $item['cl_id'] . '"><a href="javascript:del(\'' . $item['cl_id'] . '\');">删除</a></span>';
                }
                if ($item['cl_deal_status'] == 1 && $item['fee_status'] == 0 && $item['channel_type'] == 0) {
                    $item['opt_pay'] = "<span id='pay_channel_fee_" . $item['cl_id'] . "'><a href='#' onclick='pay_channel_fee(" . $item['cl_id'] . ",this);'>结算</a></span>";
                }

                $item['channel_name'] = "<a href='" . u("DealChannel/index", array('channel_value' => $item['channel_value'])) . "' target='_blank'>" . $item['channel_name'] . "</a>";
                // 注意，有些值已经替换为文本
                $item['add_type'] = $item['add_type'] == 1 ? '推广链接' : '手工添加';
                if ($item['cl_deal_status'] == 1 && $item['channel_type'] == 0) {
                    $item['fee_status'] = $item['fee_status'] == 1 ? '已结清' : '未结清';
                    $item['fee_status'] = "<span id='fee_status_" . $item['cl_id'] . "'>" . $item['fee_status'] . "</span>";
                } else {
                    $item['fee_status'] = '';
                }
                $item['cl_deal_status'] = self::$deal_status_list[$item['cl_deal_status']];
                $advisor_fee_rate = Finance::convertToPeriodRate($item['d_loantype'], $item['cl_advisor_fee_rate'], $item['d_repay_time']);
                //$item['channel_fee'] = $advisor_fee_rate * $item['cl_deal_load_money'] * $item['cl_pay_factor'] * 0.01;
                //$item['channel_fee'] = "<a href='" . u("DealChannelLog/index", array('id' => $item['cl_id'])) . "' target='_blank'>" . format_price($item['channel_fee']) . "</a>";
                $item['pay_fee'] = $item['cl_pay_fee'];
                $item['pay_fee'] = "<a href='" . u("DealChannelLog/index", array('id' => $item['cl_id'])) . "' target='_blank'>" . format_price($item['pay_fee']) . "</a>";
                $item['advisor_fee_rate'] = $item['cl_advisor_fee_rate'];
                $item['pay_factor'] = $item['cl_pay_factor'];
                if ($item['channel_type'] == 0) {
                    $item['cu_real_name'] = $GLOBALS['db']->getOne("SELECT real_name FROM " . DB_PREFIX . "user WHERE id = " . $item['channel_value']);
                    $item['cu_real_name'] = $this->_get_user_link($item['channel_value'], $item['cu_real_name']);
                }

            } else {
                $item['channel_name'] = '-';
                $item['add_type'] = '-';
                $item['cl_deal_status'] = '-';
                $item['fee_status'] = '-';
                $item['channel_fee'] = '-';
                $item['pay_fee'] = '-';
                $item['advisor_fee_rate'] = '-';
                $item['pay_factor'] = '-';
                $item['opt_add'] = '-';
                // 已经使用优惠券，则不能使用邀请码
                $coupon_log_dao = new core\dao\CouponLogModel();
                $coupons = $coupon_log_dao->findByDealLoadId($item['l_id']);
                if (empty($coupons)) {
                    $item['opt_add'] = '<a href="javascript:weebox_add_channel(' . $item['l_id'] . ')">' . '添加推广记录</a>';
                }
            }
        }
    }

    /**
     * user_id转用户名称并加链接
     *
     * @param $user_id
     * @param $text
     * @return string
     */
    private function _get_user_link($user_id, $text) {
        return "<a href='" . u("User/index", array('user_id' => $user_id)) . "' target='_blank'>" . $text . "</a>";
    }




}
