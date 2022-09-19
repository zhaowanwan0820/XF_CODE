<?php
/**
 *------------------------------------------------------------------
 * 新用户双返红包http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-1966
 *------------------------------------------------------------------
 * @auther wangshijie<wangshijie@ucfgroup.com>
 *------------------------------------------------------------------
 */
use core\service\BonusService;
class BonusNewUserRebateAction extends CommonAction {

    const CONST_NAME_SUFFIX = '_INVITER';
    private $consume_types = array('1' => '仅限投资', '2' => '可提现');

    public function __construct() {
        parent::__construct();
        $this->model = M('BonusDispatchConfig');
    }

    /**
     * 新手双返红包列表页面
     */
    public function index() {
        if (!empty ($this->model)) {
            $this->_list($this->model);
        }
        $result = $this->get('list');
        $data = $inviter = array();
        foreach ($result as $item) {
            if (substr($item['const_name'], -8) == self::CONST_NAME_SUFFIX) {
                $inviter["{$item['const_name']}"] = $item;
                continue;
            }
            $data["{$item['const_name']}"] = $item;
        }

        //增加分页信息
        $count = count($data);
        if ($count > 0) {
            $p = new Page ($count, '');
            $this->assign("page", $p->show());
            $this->assign("nowPage", $p->nowPage);
        }

        foreach ($data as $key => &$item) {
            $key = $key . self::CONST_NAME_SUFFIX;
            $item['inviter_is_group']       = $inviter[$key]['is_group'];
            $item['inviter_consume_type']   = $inviter[$key]['consume_type'];
            $item['inviter_count']          = $inviter[$key]['count'];
            $item['inviter_money']          = $inviter[$key]['money'];
            $item['inviter_send_limit_day'] = $inviter[$key]['send_limit_day'];
            $item['inviter_use_limit_day']  = $inviter[$key]['use_limit_day'];
            $item['consume_type'] = $this->consume_types[$item['consume_type']];
            $item['is_group'] = $item['is_group'] == 0 ? '投资红包' : '红包组';
            $item['inviter_is_group'] = $item['inviter_is_group'] == 0 ? '投资红包' : '红包组';
            $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
            $item['end_time'] = date('Y-m-d H:i:s', $item['end_time']);
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        $this->assign('list', $data);
        $this->display();
    }

    /**
     * 添加返红包节点
     */
    public function add() {
        if ($_POST) {
            try {
                $this->model->startTrans();
                $_POST['start_time'] = strtotime($_POST['start_time']);
                $_POST['end_time'] = strtotime($_POST['end_time']);
                $_POST['update_time'] = $_POST['create_time'] = get_gmtime();
                $inviter = array('is_group' => $_POST['inviter_is_group'], 'consume_type' => $_POST['inviter_consume_type'],
                         'count' => $_POST['inviter_count'], 'money' => $_POST['inviter_money'], 'send_limit_day' => $_POST['inviter_send_limit_day'],
                         'use_limit_day' => $_POST['inviter_use_limit_day'], 'const_name' => $_POST['const_name'] . '_INVITER');
                unset($_POST['inviter_is_group'], $_POST['inviter_consume_type'], $_POST['inviter_count']);
                unset($_POST['inviter_money'], $_POST['inviter_send_limit_day'], $_POST['inviter_use_limit_day']);
                $result = $this->model->add($_POST);
                if ($result) {
                    $result = $this->model->add(array_merge($_POST, $inviter));
                }
                $this->model->commit();
            } catch(Exception $e) {
                $this->model->rollback();
                exit;
            }
            if ($result) { //成功提示
                $this->success(L("INSERT_SUCCESS"));
            } else { //错误提示
                save_log($log_info . L("INSERT_FAILED"), array_merge($_POST, array('inviter' => $inviter)));
                $this->error(L("INSERT_FAILED"), 0);
            }

            $this->redirect(u(MODULE_NAME."/index"));
        } else {
            $this->assign("consume_types", $this->consume_types);
            $this->display('add');
        }
    }

    /**
     * 更新返红包节点
     */
    public function update() {
        \SiteApp::init()->cache->delete('BONUS_XQL_CONFIG_LIST');
        try {
            $id = $_POST['id'];
            $inviter_id = $_POST['inviter_id'];
            $this->model->startTrans();
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['update_time'] = get_gmtime();
            $inviter = array('is_group' => $_POST['inviter_is_group'], 'consume_type' => $_POST['inviter_consume_type'],
                     'count' => $_POST['inviter_count'], 'money' => $_POST['inviter_money'], 'send_limit_day' => $_POST['inviter_send_limit_day'],
                     'use_limit_day' => $_POST['inviter_use_limit_day'], 'const_name' => $_POST['const_name'] . '_INVITER');
            unset($_POST['inviter_is_group'], $_POST['inviter_consume_type'], $_POST['inviter_count'], $_POST['inviter_id'], $_POST['id']);
            unset($_POST['inviter_money'], $_POST['inviter_send_limit_day'], $_POST['inviter_use_limit_day']);
            $result = $this->model->where("id='$id'")->save($_POST);
            if ($result) {
                $result = $this->model->where("id='$inviter_id'")->save(array_merge($_POST, $inviter));
            }
            $this->model->commit();
            \SiteApp::init()->cache->delete(BonusService::CACHE_PREFIX_BONUS_NEW_USER_REBATE.$_POST['const_name']);
            \SiteApp::init()->cache->delete(BonusService::CACHE_PREFIX_BONUS_NEW_USER_REBATE.$_POST['const_name'].self::CONST_NAME_SUFFIX);
        } catch(Exception $e) {
            $this->model->rollback();
        }
        if ($result) { //成功提示
            $this->success(L("UPDATE_SUCCESS"));
            $this->redirect(u(MODULE_NAME."/index"));
        } else { //错误提示
            save_log($log_info . L("INSERT_FAILED"), array_merge($_POST, array('inviter' => $inviter)));
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }

    /**
     * 超级红包编辑页面
     */
    public function edit() {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        $inviter_const_name = $vo['const_name'].self::CONST_NAME_SUFFIX;
        $inviter = $this->model->where("const_name='$inviter_const_name'")->find();
        $vo['start_time'] = date('Y-m-d H:i:s',$vo['start_time']);
        $vo['end_time'] = date('Y-m-d H:i:s',$vo['end_time']);
        $vo['inviter_is_group']       = $inviter['is_group'];
        $vo['inviter_consume_type']   = $inviter['consume_type'];
        $vo['inviter_count']          = $inviter['count'];
        $vo['inviter_money']          = $inviter['money'];
        $vo['inviter_send_limit_day'] = $inviter['send_limit_day'];
        $vo['inviter_use_limit_day']  = $inviter['use_limit_day'];
        $vo['inviter_id']  = $inviter['id'];

        $this->assign('vo', $vo);
        $this->assign("consume_types", $this->consume_types);
        $this->display();
    }

    /**
     * 红包配置输出列表页面数据处理
     */
    protected function form_index_list(&$list) {
        $item['consume_type'] = $this->consume_types[$item['consume_type']];
        $item['is_group'] = $item['is_group'] == 0 ? '投资红包' : '红包组';
        $item['inviter_is_group'] = $item['inviter_is_group'] == 0 ? '投资红包' : '红包组';
    }

    public function get_id_list() {

        $ajax = intval($_REQUEST['ajax']);// 检查参数
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
        $ids = explode(',', $id);
        foreach ($ids as $item) {
            $result = $this->model->where("id='$item'")->find();
            if (!empty($result)) {
                $const_name = $result['const_name'].self::CONST_NAME_SUFFIX;
                $result = $this->model->where("const_name='$const_name'")->find();
                $result['id'] = intval($result['id']);
                if (intval($result['id']) > 0) {
                    $id .= ",{$result['id']}";
                }
            }
        }
        return explode(',', $id);
    }
}
