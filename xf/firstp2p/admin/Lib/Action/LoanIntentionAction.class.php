<?php

/**
 * 借款意向相关
 *
 * @author wangge@ucfgroup.com
 */
class LoanIntentionAction extends CommonAction {

    /**
     * 所有状态
     */
    public static $status = array(
        1 => '待审核',
        2 => '已通过',
        3 => '未通过',
    );

    /**
     * 未审核
     */
    const NOT_AUDIT  = 1;

    /**
     * 审核通过
     */
    const AUDIT_SUCC = 2;

    /**
     * 审核不通过
     */
    const AUDIT_FAIL = 3;

    /**
     * 获取查询条件
     */
    private function getWhereStmt() {
        $conds = array();

        $userId = intval($_GET['user_id']);
        if ($userId > 0) {
            $conds['user_id'] = $userId;
        }

        $auditStatus = intval($_GET['audit_status']);
        if ($auditStatus > 0) {
            $conds['status'] = $auditStatus;
        }

        $timeStart = trim($_GET['time_start']);
        if (!empty($timeStart)) {
            $conds['apply_time'][] = array('egt', to_timespan($timeStart));
        }

        $timeEnd = trim($_GET['time_end']);
        if (!empty($timeEnd)) {
            $conds['apply_time'][] = array('elt', to_timespan($timeEnd));
        }

        return $conds;
    }

    /**
     * 首页
     */
    public function index() {
        $model = M(MODULE_NAME);
        $this->_list($model, $this->getWhereStmt());

        $list = $this->get('list');
        $this->assign('list', $list);
        $this->assign('status', self::$status);

        $this->display();
    }

    /**
     * 审核
     */
    public function audit() {
        $id = intval($_GET['id']);
        $oldData = M(MODULE_NAME)->find($id);
        if (empty($oldData)) {
            $this->ajaxReturn(null, L('DATA_NOT_FOUND'), false);
        }

        $status = intval($_GET['status']);
        $newData = array_merge($oldData, array('status' => $status, 'update_time' => get_gmtime()));
        $res = M(MODULE_NAME)->save($newData);
        if (!$res) {
            save_log(L('FUNC_LIQUIDITY'), 1, $oldData, $newData);
            $this->ajaxReturn(null, L('UPDATE_FAILED'), false);
        }

        save_log(L('FUNC_LIQUIDITY'), 1, $oldData, $newData);
        $this->ajaxReturn($newData, null, true);
    }

    /**
     * 展示
     */
    public function show() {
        $id = intval($_GET['id']);
        $data = M(MODULE_NAME)->find($id);
        if (empty($data)) {
            $this->error(L('DATA_NOT_FOUND'));
        }

        $this->assign('status', self::$status);
        $this->assign("data", $data);
        $this->display();
    }

    public function export_csv(){
        $model = M(MODULE_NAME);
        $this->_list($model, $this->getWhereStmt());
        $list = $this->get('list');
        $content = iconv("utf-8", "gbk", "用户ID,申请金额,申请借款周期,申请人电话,申请人地址,申请人公司,申请人职级,申请状态,申请类型,申请时间,操作时间")."\n";
        foreach($list as $one){
            $one['company'] = iconv("utf-8", "gbk",$one['company']);
            $one['work_level'] = iconv("utf-8", "gbk",$one['work_level']);
            $one['address'] = iconv("utf-8", "gbk",$one['address']);
            $one['status'] = iconv("utf-8", "gbk",$one['status']==1?'待审核':($one['status']==2?'通过':'拒绝'));
            $one['apply_time'] = date("Y-m-d H:i:s",$one['apply_time']);
            $one['update_time'] = iconv("utf-8", "gbk",empty($one['update_time'])?'未审核':date("Y-m-d H:i:s",$one['update_time']));
            $one['loan_time'] = iconv("utf-8", "gbk",$one['loan_time']."个月");
            $one['type'] = iconv("utf-8", "gbk",$one['type']==2?'职易贷':'变现通');
            $content .= sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                    $one['user_id'],$one['loan_money'],$one['loan_time'],$one['phone'],
                    $one['address'],$one['company'],$one['work_level'],
                    $one['status'],$one['type'],$one['apply_time'],$one['update_time']
                    )."\n";
        }
        $filename = '变现通'.date('Y-m-d H:i:s',time());
        header("Content-Disposition: attachment; filename=" . $filename . ".csv");
        echo $content;
    }
}
