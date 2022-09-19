<?php
/**
 * 标的队列
 */


use core\dao\dealqueue\DealQueueModel;
use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\project\DealProjectModel;

use core\service\DealLoanTypeService;
use core\service\user\UserService;

class DealQueueAction extends CommonAction {

    /**
     * 补充列表数据(CommonAction的_list方法会自动调用form_index_list方法)
     */
    protected function form_index_list(&$list){
        foreach ($list as $key => $item) {
            // 获取产品类别名称
            $dealTypeObj = DealLoanTypeModel::instance()->find($item['type_id'], 'name', true);
            if (empty($dealTypeObj)) {
                $list[$key]['type_name'] = '无';
            } else {
                $list[$key]['type_name'] = $dealTypeObj->name;
            }
        }
    }

    /**
     * 上标队列列表
     */
    public function index() {
        $map = array();
        //增加队列名称搜索
        if(trim($_REQUEST['name'])) {
            $map['name'] = array('like','%'.trim($_REQUEST['name']).'%');
        }
        $model = D(MODULE_NAME);
        $voList = array();
        if(!empty($model)){
            $voList = $this->_list ($model, $map);
        }
        // #WXPH-177 增加显示队列数量
        if(!empty($voList)){
            // 获取队列id数组
            $dealQueueIdArray = array();
            foreach($voList as $dealQueue){
                if(!empty($dealQueue['id'])){
                    $dealQueueIdArray[] = $dealQueue['id'];
                }
            }
            if(!empty($dealQueueIdArray)){
                // 获取队列中的数量
                $sql = "SELECT `queue_id`, count(*) AS `num` FROM " . DealQueueInfoModel::instance()->tableName() . " WHERE `queue_id` IN (" . implode(',',$dealQueueIdArray) . ") GROUP BY `queue_id`;";
                $result = DealQueueInfoModel::instance()->findAllBySqlViaSlave($sql,true);
                $nums = array();
                foreach($result as $one){
                    $nums[$one['queue_id']] = $one['num'];
                }
                // 将数量放入到队列中
                foreach($voList as $k => $dealQueue){
                    $voList[$k]['num'] = isset($nums[$dealQueue['id']]) ? intval($nums[$dealQueue['id']]) : 0;
                    $voList[$k]['alarm'] = 0;
                    if(($dealQueue['is_effect'] == 1) && ($dealQueue['alarm_value'] > 0) && $voList[$k]['num'] <= $dealQueue['alarm_value']){
                        $voList[$k]['alarm'] = 1;
                    }
                }
                $this->assign('list', $voList);
            }

        }


        $this->display();
    }

    /**
     * 显示 新增队列页面
     */
    public function add() {
        C('TOKEN_ON',true);

        // 获取产品类别
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();
        // 获取标的队列参数配置(普惠)
        $deal_params_conf_list = M('DealParamsConf')->where("`is_cn` = 1")->findAll();

        $this->assign("deal_type_tree", $deal_type_tree);
        $this->assign("deal_params_conf_list", $deal_params_conf_list);
        $this->display();
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
        $alarm_value = intval($_REQUEST['alarm_value']);

        if (!$name) {
            $this->error("队列名称不可为空");
        }
        if(!is_int($alarm_value) &&  ($alarm_value < 0)){
            $this->error("预警值不能小于0");
        }

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
            "alarm_value" => $alarm_value,
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

    /**
     * 查看队列信息
     */
    public function edit() {
        C('TOKEN_ON',true);
        // 获取上标队列列表
        $condition['id'] = intval($_REQUEST ['id']);
        $vo = M(MODULE_NAME)->where($condition)->find();

        // 获取产品类别
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0' AND `type_tag` != 'LGL'")->order('sort desc')->findAll();

        // 获取标的队列参数配置(普惠)
        $deal_params_conf_list = M('DealParamsConf')->where("`is_cn` = 1")->findAll();

        $this->assign('vo', $vo);
        $this->assign("deal_type_tree",$deal_type_tree);
        $this->assign("deal_params_conf_list", $deal_params_conf_list);
        $this->display();
    }


    /**
     * 获取指定名称的队列
     */
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
        C('TOKEN_ON',true);
        $m = M(MODULE_NAME);
        $data = $m->create ();
        $count = $this->getCntByName($data['name'], $data['id']);
        if($count > 0) {
            $this->error ( "队列名称不能重复!");
        }
        $alarm_value = intval($data['alarm_value']);
        if(!is_int($alarm_value) &&  ($alarm_value < 0)){
            $this->error("预警值不能小于0");
        }

        // 时间字符串转化为时间戳
        $data['start_time'] = ('' == trim($_REQUEST['start_time'])) ? 0 : to_timespan($_REQUEST['start_time']);

        //确认修改
        $rs = $m->save($data);
        $this->redirect(u(MODULE_NAME."/index"));
    }

    /**
     * 删除标的队列
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

    /**
     * 显示队列中标的
     */
    public function show_detail() {
        $id = intval($_REQUEST['id']);
        if (!$id) {
            $this->error (l("INVALID_OPERATION"));
        }

        // 获取队列
        $deal_queue = DealQueueModel::instance()->findViaSlave($id);

        // 借款标题
        $name = trim($_REQUEST['name']);
        if (isset($_REQUEST['deal_status'])) {
            $deal_status = $_REQUEST['deal_status'] == "all" ? false : intval($_REQUEST['deal_status']);
        } else {
            $deal_status = false;
        }
        // 借款人姓名
        if(trim($_REQUEST['real_name'])!=''){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);
            if(empty($ids)){
                $this->assign('deal_queue', $deal_queue);
                $this->assign("list", array());
                $this->display();
                return true;
            }
            $user_ids = !empty($ids) ? implode(',',$ids) : false;
        }else{
            $user_ids = false;
        }

        // 获取项目对应的标的id数组
        $deal_id_arr = array();
        if (!empty($_REQUEST['project_name'])) {
            $deal_id_arr[] = 0;
            $project_name = trim($_REQUEST['project_name']);
            $project_id_arr = DealProjectModel::instance()->getProjectIdsByName($project_name);
            $project_ids_string = !empty($project_id_arr) ? implode(',', $project_id_arr) : 0;
            $deal_id_arr_tmp = DealModel::instance()->findAllViaSlave(" `project_id` IN ({$project_ids_string})", true, 'id');
            foreach ($deal_id_arr_tmp as $value_id) {
                $deal_id_arr[] = intval($value_id['id']);
            }
        }

        $reportStatus = trim($_REQUEST['report_status']);
        // 获取标的
        $deal_list = DealQueueModel::instance()->getDealListByQueueId($id, $name, $user_ids, $deal_status, $deal_id_arr,$reportStatus);
       $this->assign('deal_queue', $deal_queue);
        $this->assign("list", $deal_list);
        $this->display();
    }


    /**
     * 获取不在队列中的标的列表
     */
    public function add_deal() {
        $queue_id = intval($_REQUEST['queue_id']);
        $jump_id = intval($_REQUEST['jump_id']);
        $project_name = addslashes(trim($_REQUEST['project_name']));
        $deal_status = (!isset($_REQUEST['deal_status']) || $_REQUEST['deal_status'] == 'all') ? '0,1,6' : intval($_REQUEST['deal_status']);

        if (!$queue_id) {
            $this->error (l("INVALID_OPERATION"));
        }

        $approve_number = '';
        // 放款审批单编号
        if (!empty($_REQUEST['approve_number'])) {
            $approve_number = addslashes(trim($_REQUEST['approve_number']));
        }

        $deals = $this->getDealListNoQueue($project_name, $deal_status,$approve_number);
        $this->assign("list", $deals);
        $this->assign("queue_id", $queue_id);
        $this->assign("jump_id", $jump_id);
        $this->display();
    }


    /**
     * 获取不在队列中的标的列表
     * @return array
     */
    private function getDealListNoQueue($project_name = '', $deal_status = '0,1,6',$approve_number='')
    {
        $map['deal_status'] = array('IN', addslashes($deal_status));
        if(!empty($approve_number)) {
            $map['approve_number'] = array('eq', $approve_number);
        }
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
