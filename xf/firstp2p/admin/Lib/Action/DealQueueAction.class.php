<?php
// +----------------------------------------------------------------------
// | 标的队列管理
// +----------------------------------------------------------------------
// | @author wangyiming@ucfgroup.com
// +----------------------------------------------------------------------

use core\service\DealService;
use core\dao\DealQueueModel;
use core\dao\DealModel;
use core\dao\DealQueueInfoModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealProjectModel;

use core\service\DealLoanTypeService;

class DealQueueAction extends CommonAction {
    /**
     * 补充列表数据
     */
    protected function form_index_list(&$list)
    {
        
        if ($this->is_cn) {
            $dealType = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
            foreach ($dealType as $k=>$v) {
               $dealTypeArr[$v['id']] = $v['name']; 
           }
        }
        foreach ($list as $key => $item) {
            // 获取产品类别名称
            if (!$this->is_cn) {
                $dealTypeObj = DealLoanTypeModel::instance()->find($item['type_id']);
            } else { 
                $dealTypeObj = isset($dealTypeArr[$item['type_id']]) ? $dealTypeArr[$item['type_id']] : '无';         
            }
            
            if (empty($dealTypeObj)) {
                $list[$key]['type_name'] = '无';
            } else {
                if (!$this->is_cn) {
                    $list[$key]['type_name'] = $dealTypeObj->name;
                } else {
                    $list[$key]['type_name'] = $dealTypeObj;
                }
            }
        }
    }

    public function index() {
        $map = array();
        if (trim($_REQUEST['name'])) {    //增加队列名称搜索
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }

        // cn 站只显示指定产品类别的队列
        /*if ($this->is_cn) {
            $allowed_type_tag_str = addslashes(app_conf("CN_DEAL_QUEUE_ALLOWED_TYPE"));
            $allowed_type_tag_arr = explode(',', $allowed_type_tag_str);
            $where_type_tag_arr = array();
            foreach ($allowed_type_tag_arr as $type_tag) {
                $where_type_tag_arr[] = sprintf('"%s"', $type_tag);
            }
            if (!empty($where_type_tag_arr)) {
                $map['type_id'] = array('IN', sprintf('SELECT `id` FROM %s WHERE `type_tag` IN (%s)', DealLoanTypeModel::instance()->tableName(), implode(',', $where_type_tag_arr)));
            }
            $map['is_cn'] = 1;
        } */
        if ($this->is_cn) {
           $map['is_cn'] = 1;
        } 
        $site_id = !empty($_REQUEST['site_id']) ? intval($_REQUEST['site_id']) : $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2p'];
        $map['site_id'] = $site_id;
        $this->assign('site_list', $GLOBALS['sys_config']['TEMPLATE_LIST']);
        $this->assign('site_id', $site_id);

        $model = D(MODULE_NAME);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

    public function add() {
        C('TOKEN_ON',true);

        // 获取产品类别
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();
        $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
        $this->assign("deal_type_tree",$this->is_cn ? $GLOBALS['dict']['DEAL_TYPE_ID_CN'] : $deal_type_tree);

        // get deal_params_conf
        $params_conf_model = M('DealParamsConf');
        $deal_params_conf_list = $params_conf_model->findAll();
        $this->assign("deal_params_conf_list", $deal_params_conf_list);

        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->display($template);
    }

    /**
     * 创建标的队列
     * @actionlock
     */
    public function insert() {
        C('TOKEN_ON',true);
        if(!isset($_SESSION[C('TOKEN_NAME')])) {
            $this->redirect(u(MODULE_NAME."/index"));
        }

        $m = M(MODULE_NAME);

        $name = $_REQUEST['name'];
        $note = $_REQUEST['note'];
        $is_effect = intval($_REQUEST['is_effect']);
        $invest_deadline = intval($_REQUEST['invest_deadline']);
        $invest_deadline_unit = intval($_REQUEST['invest_deadline_unit']);

        if (!$name) {
            $this->error("队列名称不可为空");
        }

        // #JIRA 3404 消费贷自动上标
        $type_id = intval($_REQUEST['type_id']);
        $start_time = ('' == trim($_REQUEST['start_time'])) ? 0 : to_timespan($_REQUEST['start_time']);

        $data = array(
            "name" => $name,
            "note" => $note,
            "is_effect" => $is_effect,
            "create_time" => get_gmtime(),
            "type_id" => $type_id,
            "start_time" => $start_time,
            "deal_params_conf_id" => intval($_REQUEST['deal_params_conf_id']),
            "invest_deadline" => $invest_deadline,
            "invest_deadline_unit" => $invest_deadline_unit,
            "is_cn" => isset($_REQUEST['is_cn']) && !empty($_REQUEST['is_cn']) ? $_REQUEST['is_cn'] : 0,
        );

        $count = $this->getCntByName($name);
        if($count > 0) {
            $this->error ( "队列名称不能重复!");
        }
        $result = $m->add ($data);

        if(!$result){
            $dbErr = M()->getDbError();
            save_log($data['name'].L("INSERT_FAILED").$dbErr,0);
            $this->error(L("INSERT_FAILED").$dbErr);
        }
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );

        // 获取产品类别
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();
        $deal_type_tree = D("DealLoanType")->toFormatTree($deal_type_tree,'name');
        if ($this->is_cn) {
            $deal_type_tree = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
        }
        $this->assign("deal_type_tree",$deal_type_tree);

        // get deal_params_conf
        $params_conf_model = M('DealParamsConf');
        $deal_params_conf_list = $this->is_cn ? $params_conf_model->where("`is_cn` = 1")->findAll(): $params_conf_model->findAll();
        $this->assign("deal_params_conf_list", $deal_params_conf_list);
        $this->assign("is_cn", $this->is_cn);
        $this->display();
    }

    public function getCntByName($name, $id=false) {
        if ($id) {
            $condition = "`name`='{$name}' AND `id`!='{$id}'";
        } else {
            $condition = array("name"=>$name);
        }
        $cnt = M(MODULE_NAME)->where($condition)->count();
        return $cnt;
    }

    /**
     * 修改保存标的队列
     * @actionlock
     */
    public function save() {
        $m = M(MODULE_NAME);
        $data = $m->create ();
        $count = $this->getCntByName($data['name'], $data['id']);
        if($count > 0) {
            $this->error ( "队列名称不能重复!");
        }

        // 时间字符串转化为时间戳
        $data['start_time'] = ('' == trim($_REQUEST['start_time'])) ? 0 : to_timespan($_REQUEST['start_time']);

        //确认修改
        $rs = $m->save($data);
        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 删除标的队列
     * @actionlock
     */
    public function delete() {
        $ajax = intval($_REQUEST['ajax']);
        $queue_id_arr = isset($_REQUEST ['id']) ? explode(',', $_REQUEST ['id']) : array();
        if (false === DealQueueModel::instance()->deleteQueues($queue_id_arr)) {
            save_log(l("DELETE_FAILED"),0);
            $this->error (l("DELETE_FAILED"),$ajax);
        } else {
            save_log(l("DELETE_SUCCESS"),1);
            $this->success (l("DELETE_SUCCESS"),$ajax);
        }
    }

    public function show_detail() {
        $id = intval($_REQUEST['id']);
        if (!$id) {
            $this->error (l("INVALID_OPERATION"));
        }

        $name = trim($_REQUEST['name']);
        if (isset($_REQUEST['deal_status'])) {
            $deal_status = $_REQUEST['deal_status'] == "all" ? false : intval($_REQUEST['deal_status']);
        } else {
            $deal_status = false;
        }

        $real_name = trim($_REQUEST['real_name']);
        if ($real_name) {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%".trim($real_name)."%'";
            $user_ids = $GLOBALS['db']->getOne($sql);
        } else {
            $user_ids = false;
        }

        // 获取项目对应的标的id数组
        $deal_id_arr = array();
        if (!empty($_REQUEST['project_name'])) {
            $deal_id_arr[] = 0;
            $project_name = trim($_REQUEST['project_name']);
            $project_id_arr = \core\dao\DealProjectModel::instance()->getProjectIdsByName($project_name);
            $project_ids_string =implode(',', $project_id_arr);
            $deal_id_arr_tmp = \core\dao\DealModel::instance()->findAllViaSlave(" `project_id` IN ({$project_ids_string})", true, 'id');
            foreach ($deal_id_arr_tmp as $value_id) {
                $deal_id_arr[] = intval($value_id['id']);
            }
        }

        $reportStatus = trim($_REQUEST['report_status']);
        // 添加固定起息日字段
        // 1、将deal数据读出，2、根据deal数据中的project_id来搜索对应的固定起息日
        $deal_list = DealQueueModel::instance()->getDealListByQueueId($id, $name, $user_ids, $deal_status, $deal_id_arr,$reportStatus);
        // 获取固定起息日
        $project_ids = array();
        foreach($deal_list as $key => $value){
            $project_ids[] = $value['project_id'];
        }
        $condition = sprintf("id IN (%s)",implode(",", $project_ids));
        $date_list = M('DealProject')->where($condition)->getField('id,fixed_value_date');
        // 将固定起息日存入列表中
        foreach($deal_list as $key => $value){
            $deal_list[$key]['fixed_value_date']=$date_list[$value['project_id']];
        }
        $deal_queue = DealQueueModel::instance()->findViaSlave($id);
        $this->assign('deal_queue', $deal_queue);
        $this->assign("list", $deal_list);
        $this->assign("is_cn", $this->is_cn);
        $template = $this->is_cn ? 'show_detail_cn' : 'show_detail';
        $this->display($template);
    }

    public function add_deal() {
        $queue_id = intval($_REQUEST['queue_id']);
        $jump_id = intval($_REQUEST['jump_id']);
        $project_name = addslashes(trim($_REQUEST['project_name']));
        $deal_status = (!isset($_REQUEST['deal_status']) || $_REQUEST['deal_status'] == 'all') ? '0,1,6' : intval($_REQUEST['deal_status']);

        if (!$queue_id) {
            $this->error (l("INVALID_OPERATION"));
        }
        $deals = $this->getDealListNoQueue($project_name, $deal_status);
        $this->assign("list", $deals);
        $this->assign("queue_id", $queue_id);
        $this->assign("jump_id", $jump_id);
        $template = $this->is_cn ? 'add_deal_cn' : 'add_deal';
        $this->display($template);
    }


    /**
     * 获取不在队列中的标的列表
     * @return array
     */
    private function getDealListNoQueue($project_name = '', $deal_status = '0,1,6')
    {
        $map['deal_status'] = array('IN', addslashes($deal_status));
        $map['is_delete'] = 0;
        $map['publish_wait'] = 0;
        $map['id'] = array('NOT IN', sprintf('SELECT `deal_id` FROM %s', DealQueueInfoModel::instance()->tableName()));
        $project_name_query = !empty($project_name) ? sprintf(' AND `name` LIKE "%s"', '%' . addslashes($project_name) . '%') : '';
        $map['project_id'] = array('IN', sprintf('SELECT `id` FROM %s WHERE `status` != 1 %s', DealProjectModel::instance()->tableName(), $project_name_query));

        $model = DI('Deal');
        if (!empty($model)) {
             if ($this->is_cn) {
                $map['deal_type'] = 0;
             }
            $list = $this->_list($model, $map, 'id', true);
        }

        return !empty($list) ? $list : array();
    }
    /**
     * 向标的队列插入借款
     * @actionlock
     */
    public function insert_deal() {
        $ajax = intval($_REQUEST['ajax']);
        $queue_id = intval($_REQUEST['queue_id']);
        $deal_id = $_REQUEST['deal_id'];
        $jump_deal_id = intval($_REQUEST['jump_deal_id']);
        if ($queue_id && $deal_id) {
            $result = DealQueueModel::instance()->insertDealQueue($queue_id, $deal_id, $jump_deal_id);
            if ($result!==false) {
                save_log("成功将标的 {$deal_id} 加入到队列 {$queue_id} 中", 1);
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
     * 移动队列中的标的
     * @actionlock
     */
    public function move_deal() {
        $ajax = intval($_REQUEST['ajax']);
        $queue_id = intval($_REQUEST['queue_id']);
        $deal_id = intval($_REQUEST['deal_id']);
        $direction = intval($_REQUEST['direction']);
        if ($queue_id && $deal_id && $direction) {
            $result = DealQueueModel::instance()->moveDealQueue($queue_id, $deal_id, $direction);
            if ($result!==false) {
                save_log("成功将标的 {$deal_id} 自队列 {$queue_id} 中向 {$direction} 移动", 1);
                $this->success ('移动成功',$ajax);
            } else {
                save_log('移动失败',0);
                $this->error ('移动失败',$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 从队列删除标的
     * @actionlock
     */
    public function delete_deal() {
        $ajax = intval($_REQUEST['ajax']);
        $queue_id = intval($_REQUEST['queue_id']);
        $deal_id = $_REQUEST['deal_id'];
        if ($queue_id && $deal_id) {
            $result = DealQueueModel::instance()->deleteDealQueue($queue_id, $deal_id);
            if ($result!==false) {
                save_log("成功将标的 {$deal_id} 从队列 {$queue_id} 中删除", 1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log(l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
}
?>
