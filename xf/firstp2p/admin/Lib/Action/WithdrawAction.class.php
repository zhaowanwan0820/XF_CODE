<?php
class WithdrawAction extends CommonAction{
    public function index()
    {
        $this->display();
    }

    public function process() {
        $ids = preg_split('/\s+/si', trim($_POST['ids']));
        $act = trim($_POST['op']);
        if (empty($act)) {
            $this->error('请选择操作类型');
        }
        $log = array();
        $log['cnt'] = 0;
        $log['succ'] = 0;
        $log['type'] = __CLASS__.__FUNCTION__;
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $log['op_user_id'] = intval($adm_session['adm_id']);
        ini_set('memory_limit', '512M');
        set_time_limit(0);
        foreach ($ids as $recid) {
            $recid = intval(trim($recid));
            $userCarryData = \core\dao\UserCarryModel::instance()->find($recid);
            if (empty($userCarryData)) {
                $log['fail'][] = '提现记录['.$recid.']不存在';
                continue;
            }
            $log['cnt'] ++;

            $userCarryService = new \core\service\UserCarryService();
            try {
            if ($act === 'refuse') {
                $res = $userCarryService->doRefuse($recid);
                if($res) {
                    $log['succ'] ++;
                }
            }
            else if($act === 'pass'){
                $GLOBALS['db']->startTrans();
                try {
                    $_toUpdate['status'] = 3;
                    $_toUpdate['update_time'] = $_toUpdate['update_time_step1'] = $_toUpdate['update_time_step2'] = get_gmtime();
                    $se = \es_session::get(md5(conf("AUTH_KEY")));
                    $data['desc'] = '<p>风控：' . $se['adm_name'] . '通过</p>';
                    $GLOBALS['db']->autoExecute('firstp2p_user_carry', $_toUpdate, 'UPDATE', "id = '{$recid}' AND status IN (0,1)");
                    $affect = $GLOBALS['db']->affected_rows();
                    if ($affect <  1) {
                        throw new \Exception('提现记录'.$recid.'已被处理');
                    }
                    $res = $userCarryService->doPass(null, $recid);
                    if ($res) {
                        $log['succ'] ++;
                    }
                    $GLOBALS['db']->commit();
                }
                catch (\Exception $e) {
                    $GLOBALS['db']->rollback();
                    throw $e;
                }
            }
            }
            catch(\Exception $e) {
                $log['fail'][] = $recid.'处理失败:'.$e->getMessage();
            }
        }
        if (empty($log['fail'])) {
            $this->success('处理成功');
        }
        else {
            $msg = implode("<br/>", $log['fail']);
            $this->assign('waitSecond',"30");
            $this->error($msg);
        }
    }
}
?>
