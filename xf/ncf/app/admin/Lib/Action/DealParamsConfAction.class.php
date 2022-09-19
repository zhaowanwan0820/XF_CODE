<?php
/**
 * 标的参数配置方案
 */

use core\dao\dealqueue\DealQueueModel;
use core\dao\deal\DealModel;
use core\enum\DealEnum;
use core\service\user\VipService;
use core\service\user\UserService;

class DealParamsConfAction extends CommonAction
{
    private function _checkDealParamsConf($data)
    {
        // 开始验证数据有效性
        if (empty($data['name'])) {
            $this->error('参数配置方案名称不能为空');
        }

        if (empty($data['description'])) {
            $this->error('参数配置方案描述不能为空');
        }
    }

    private function _filterDealParamsConf($data)
    {
        // 如果不是投资限定用户 或者vip用户，uid 要为0
        if ((DealEnum::DEAL_CROWD_SPECIFY_USER != $data['deal_crowd']) && (DealEnum::DEAL_CROWD_VIP != $data['deal_crowd'])) {
            $data['specify_uid'] = 0;
        }

        if (DealEnum::DEAL_CROWD_VIP == $data['deal_crowd']) {
            $data['specify_uid'] = $_REQUEST['specify_vip'] ? intval($_REQUEST['specify_vip']) : 0;
        }

        // xss
        if (!empty($data['activity_introduction'])) {
            $data['activity_introduction'] = preg_replace('/<script|<iframe/i', ' ', $data['activity_introduction']);
        }

        return $data;
    }

    public function index()
    {
        $map = array();
        if ($conf_name = trim($_REQUEST['name'])) {
            $map['name'] = array('like', '%' . $conf_name . '%');
            $this->assign('params_conf_name', $conf_name);
        }
        $params_conf_model = M('DealParamsConf');
        if (!empty($params_conf_model)) {
            $this->_list($params_conf_model, $map);
        }
        $this->display();
    }

    public function add()
    {
        //限制vip等级
        $vipGrades = VipService::getVipGradeList();
        if (empty($vipGrades)){
            $this->error('获取vip等级列表失败');
        }
        // 标和用户组对应关系
        $group = M("DealGroup")->where(array('deal_id'=>$id))->select();

        $this->assign('vipGrades', $vipGrades);
        $this->_assignBidLimitCondition();
        $this->display();
    }

    private function _assignBidLimitCondition($params_conf_info = array())
    {
        // 指定用户投资
        if (!empty($params_conf_info) && DealEnum::DEAL_CROWD_SPECIFY_USER == $params_conf_info['deal_crowd']) {
            $specify_uid_info = \core\service\user\UserService::getUserById(intval($params_conf_info['specify_uid']),'id, real_name, mobile');
            $this->assign('specify_uid_info',$specify_uid_info);
        }
        // 投资限定条件1-投资人群 不包含专享
        $deal_crowd = $GLOBALS['dict']['DEAL_CROWD'];
      
        // $key = array_search( '专享标', $deal_crowd);
        // if (false !== $key) {
        //     unset($deal_crowd[$key]);
        // }
        $usergroupList = UserService::getUserGroupList();
        if (empty($usergroupList)){
            $this->error('获取用户组列表失败');
        }
        $this->assign('usergroupList', $usergroupList);
        $this->assign('deal_crowd', $deal_crowd);

        // 投资限定条件2-个人用户、企业用户
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);
    }

    public function insert()
    {
        $params_conf_model = M('DealParamsConf');
        $params_conf_data = $params_conf_model->create(); 
        $params_conf_data['create_time'] = time();
        $params_conf_data['is_cn'] = 1;//默认普惠
        $this->_checkDealParamsConf($params_conf_data);
        $params_conf_data = $this->_filterDealParamsConf($params_conf_data);
        if ($params_conf_data['deal_crowd'] == '2') {
            $relation = 0;
            foreach ($_POST['relation'] as $v) {
                $relation = $relation | $v;
            }
            $relation = base_convert($relation, 2, 10);
            $params_conf_data['condition_params'] = json_encode(['group_ids' => $_POST['user_group'], 'relation' => $relation]);
            unset($_POST['group_ids']);
        }
        $inserted_id = $params_conf_model->add($params_conf_data); // 返回插入的id
        if (empty($inserted_id)) {
            $db_err = $params_conf_model->getDbError();
            save_log('参数配置方案：' . $params_conf_data['name'] . L("INSERT_FAILED") . $db_err, 0);
            $this->error(L("INSERT_FAILED") . $db_err);
        } else {
            save_log('参数配置方案：' . $params_conf_data['name'] . L("INSERT_SUCCESS"), C('SUCCESS'), '', $params_conf_data, C('SAVE_LOG_FILE'));
            $this->success(L("INSERT_SUCCESS"), 0);
        }
    }

    public function edit()
    {
        $params_conf_model = M('DealParamsConf');
        $condition['id'] = intval($_REQUEST['id']);
        $conf_info = $params_conf_model->where($condition)->find();
        if (empty($conf_info)) {
            $this->error('获取配置方案信息失败');
        }
        //限制vip等级
        $vipGrades = VipService::getVipGradeList();
        if (empty($vipGrades)){
            $this->error('获取vip等级列表失败');
        }
        $this->assign('vipGrades', $vipGrades);

        $this->assign ('conf_info', $conf_info);
        $condition_params = json_decode($conf_info['condition_params'], true);
        $this->assign('relation', $condition_params['relation']);
        $this->assign('user_group', $condition_params['group_ids']);

        // 增加只查看权限 access_permission为1，代表只读
        // 看是否有关联队列
        $band_info = DealQueueModel::instance()->getDealQueueByParamsConfId($conf_info['id'], 'id');
        $access_permission = empty($band_info) ? intval($_REQUEST['access_permission']) : 1;
        if (1 == $access_permission) {
            $title = '查看';
        } else {
            $title = '编辑';
        }
        $this->assign('access_permission', $access_permission);
        $this->assign('title', $title);
        $this->_assignBidLimitCondition($conf_info);
        $this->display();
    }
    public function update()
    {
        $params_conf_model = M('DealParamsConf');
        $params_conf_data = $params_conf_model->create();
        $this->_checkDealParamsConf($params_conf_data);
        $params_conf_data = $this->_filterDealParamsConf($params_conf_data);
        if ($params_conf_data['deal_crowd'] == '2') {
            $relation = 0;
            foreach ($_POST['relation'] as $v) {
                $relation = $relation | $v;
            }
            $relation = base_convert($relation, 2, 10);
            $params_conf_data['condition_params'] = json_encode(['group_ids' => $_POST['user_group'], 'relation' => $relation]);
            unset($_POST['group_ids']);
        }
        $affect_rows_num = $params_conf_model->save($params_conf_data); // 返回影响的行数
        if (false === $affect_rows_num) {
            $db_err = $params_conf_model->getDbError();
            save_log('参数配置方案：' . $params_conf_data['name'] . '更新失败' . $db_err, 0);
            $this->error('更新配置方案失败');
        } else {
            save_log('参数配置方案：' . $params_conf_data['name'] . '更新成功', C('SUCCESS'), '', $params_conf_data, C('SAVE_LOG_FILE'));
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    public function delete()
    {
        $params_conf_model = M('DealParamsConf');
        $condition['id'] = intval($_REQUEST['id']);
        // is applying in deal_queue
        $deal_queue_model = M('DealQueue');
        $cond_queue['deal_params_conf_id'] = $condition['id'];
        $deal_queue_res = $deal_queue_model->where($cond_queue)->findAll();
        if (!empty($deal_queue_res)) {
            $this->error(L('DELETE_FAILED') . ' 请先解除此方案与自动队列的关系！');
        }
        $affect_rows_num = $params_conf_model->where($condition)->delete();
        if (0 == $affect_rows_num) {
            $db_err = $params_conf_model->getDbError();
            save_log('参数配置方案id：' . $condition['id'] . L('DELETE_FAILED') . $db_err, 0);
            $this->error(L('DELETE_FAILED'));
        } else {
            save_log('参数配置方案id：' . $condition['id'] . L("DELETE_SUCCESS"), C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));
            $this->success(L("DELETE_SUCCESS"));
        }
    }

}
