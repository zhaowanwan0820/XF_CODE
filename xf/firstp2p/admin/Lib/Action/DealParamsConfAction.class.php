<?php
/**
 * 标的参数配置方案
 */

use core\dao\DealQueueModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\service\vip\VipService;
use core\service\DealCustomUserService;

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
        if ((DealModel::DEAL_CROWD_SPECIFY_USER != $data['deal_crowd']) && (DealModel::DEAL_CROWD_VIP != $data['deal_crowd'])) {
            $data['specify_uid'] = 0;
        }

        if (DealModel::DEAL_CROWD_VIP == $data['deal_crowd']) {
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
        if ($this->is_cn) {
            $map['is_cn'] = 1;
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
        $vipService = new VipService();
        $vipGrades = $vipService->getVipGradeList();
        $this->assign('vipGrades', $vipGrades);
        $this->_assignBidLimitCondition();
        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->display($template);
    }

    private function _assignBidLimitCondition($params_conf_info = array())
    {
        // 指定用户投资
        if (!empty($params_conf_info) && DealModel::DEAL_CROWD_SPECIFY_USER == $params_conf_info['deal_crowd']) {
            $specify_uid_info = UserModel::instance()->findViaSlave(intval($params_conf_info['specify_uid']), 'id, real_name, mobile');
            $this->assign('specify_uid_info',$specify_uid_info);
        }

        // 投资限定条件1-投资人群 不包含专享
        $deal_crowd = $GLOBALS['dict']['DEAL_CROWD'];
      
        $key = array_search( '专享标', $deal_crowd);
        if (false !== $key) {
            unset($deal_crowd[$key]);
        }
        $this->assign('deal_crowd', $deal_crowd);

        // 投资限定条件2-个人用户、企业用户
        $this->assign('bid_restrict', $GLOBALS['dict']['BID_RESTRICT']);
    }

    public function insert()
    {
        $params_conf_model = M('DealParamsConf');
        $params_conf_data = $params_conf_model->create();
        $params_conf_data['create_time'] = time();
        $this->_checkDealParamsConf($params_conf_data);
        $params_conf_data = $this->_filterDealParamsConf($params_conf_data);
        if ($params_conf_data['deal_crowd'] == '35') {
            $params_conf_data['condition_params'] = json_encode(['group_ids' => $_POST['group_ids']]);
            unset($_POST['group_ids']);
        }
        $inserted_id = $params_conf_model->add($params_conf_data); // 返回插入的id
        /*if($params_conf_data['deal_crowd']==34){
            $result=$this->importCsvUserIds($inserted_id);
            $dealCustomUserService = new \core\service\DealCustomUserService ();
            $updateCache=$dealCustomUserService->getCacheDealUserIds(1);
        }*/

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
        $vipService = new VipService();
        $vipGrades = $vipService->getVipGradeList();
        $this->assign('vipGrades', $vipGrades);

        $this->assign ('conf_info', $conf_info);
        if ($conf_info['deal_crowd'] == '35') {
            $groupIds = json_decode($conf_info['condition_params'], true);
            $groupIds = $groupIds['group_id'];
            $groupInfos = [];
            foreach ($groupIds as $groupId) {
                if ($groupId > 0) {
                    $groupInfos[] = [
                        'group_id' => $groupId,
                        'group_name' => M("UserGroup")->where(array('id' => $groupId))->getField('name')
                    ];
                }
            }
            $this->assign('groupInfos', $groupInfos);
        }

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
        if ($this->is_cn){
            $this->display('edit_cn');
        } else {
            $this->display();
        }
    }

    public function view(){
        $dealId = intval($_REQUEST['id']);
        $dealCustomUserService = new \core\service\DealCustomUserService ();
        $result=$dealCustomUserService->getDealUserList($dealId);
        $userModel = new \core\dao\UserModel ();
        foreach($result as $key=>$value){
            $userInfo=$userModel->find($value['user_id']);
            $result[$key]['mobile']=$userInfo['mobile'];
        }
        $this->assign('user_info', $result);
        $this->display();
    }

    public function update()
    {
        $params_conf_model = M('DealParamsConf');
        $params_conf_data = $params_conf_model->create();
        $this->_checkDealParamsConf($params_conf_data);
        $params_conf_data = $this->_filterDealParamsConf($params_conf_data);
        if ($params_conf_data['deal_crowd'] == '35') {
            $params_conf_data['condition_params'] = json_encode(['group_id' => $_POST['group_ids']]);
            unset($_POST['group_ids']);
        }
        $affect_rows_num = $params_conf_model->save($params_conf_data); // 返回影响的行数
        /*if($params_conf_data['deal_crowd']==34){
            //更新之前删除数据
            $dealCustomUserService = new \core\service\DealCustomUserService ();
            $deleteResult=$dealCustomUserService->deleteInfo($params_conf_data['id']);
            if(!$deleteResult){
                $this->error ( "删除csv数据失败" );
            }
            $result=$this->importCsvUserIds($params_conf_data['id']);
            $updateCache=$dealCustomUserService->getCacheDealUserIds(1);
        }*/
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

    /**
     * 批量修改定制用户
     */
    public function importCsvUserIds($deal_id) {
        if (empty ( $_FILES ['upfile'] ['name'] )) {
            $this->error ( "上传的文件不能为空" );
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
        }
        $max_error_num = 30;
        $error_total_num = 0;
        $max_import_line = 1000;
        $i = 1;
        $error_str = '';
        $new_data = array ();
        $error_data = array ();
        // 获取登录人session
        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
        $csv_content = file_get_contents ( $_FILES ['upfile'] ['tmp_name'] );
        if (empty ( $csv_content )) {
            $this->error ( '文件内容不能为空' );
        }
        $total_line = explode ( "\n", iconv ( 'GBK', 'UTF-8', $csv_content ) );
        // 统计去掉第一个行title
        $count_total_line = count ( $total_line ) - 1;
        // 最后一行如果空行，不做计数
        if (empty ( $total_line [$count_total_line] )) {
            $count_total_line -= 1;
        }
        if ($count_total_line > $max_import_line) {
            $this->error ( '最大导入' . $max_import_line . '条数据' );
        }
        $correct = array ();
        $csv_row=array();
        $csv_data=array();
        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" )) !== false) {
            if (fgetcsv ( $handle ) !== false) { // 第一行是标题不放到数据列表里
                while ( ($row_data = fgetcsv ( $handle )) !== false ) {

                    $error_msg = $this->check_csv_datas ( $row_data, $i );
                    if (! empty ( $error_msg ['error_msg'] )) {
                        $error_total_num ++;
                        $error_str .= $error_msg ['error_msg'];
                        unset ( $error_msg );
                        $i ++;
                        continue;
                    }
                    if (! empty ( $error_str )) {
                        $error_data = explode ( ',', $error_str );
                    }else{
                        $csv_row[$i]=$row_data[0];
                    }
                    $i ++;
                }
            }
            fclose ( $handle );
            @unlink ( $_FILES ['upfile'] ['tmp_name'] );
            if (!empty ( $error_str )) {
                $this->error($error_str);
            }
            // 更新数据
            else {
                $correct_user_id=array_unique($csv_row);
                foreach($correct_user_id as $key =>$value){
                    $csv_data['user_id']=$value;
                    $userModel = new \core\dao\UserModel ();
                    $userInfo=$userModel->find( intval($value));
                    $csv_data['user_name']=$userInfo['user_name'];
                    $csv_data['admin_id']=$adm_session ["adm_id"];
                    $csv_data['deal_id']=$deal_id;
                    $correct[$key]=$csv_data;
                }
                $dealCustomUserService = new \core\service\DealCustomUserService ();
                $result = $dealCustomUserService->insertInfo ( $correct );
                if (! $result) {
                    $this->error ( "更新csv失败" );
                }
            }
        } else {
            $this->error ( "上传的文件不可读" );
        }
    }
    /**
     * 检查csv 数据
     * @param
     *
     */
    private function check_csv_datas($data, $line) {
        $ret = array (
                'user_id' => 0,
                'error_msg' => ''
                );
        $error_str='';
        $error_list = '';
        $error_array= array();
        // 判断会员ID是否为空
        if (empty ( $data [0] )) {
            $error_str .= ' 会员ID不能为空';
            foreach ( $data as $k => $v ) {
                $v = iconv ( 'gbk', 'utf-8', $v );
                $error_list .= $v . ',';
            }
        } else {
            $userModel = new \core\dao\UserModel ();
            $userInfo=$userModel->find( intval($data [0]));
            // 判断用户是否存在
            if (empty ( $userInfo )) {
                $error_str="用户不存在";
                foreach ( $data as $k => $v ) {
                    $v = iconv ( 'gbk', 'utf-8', $v );
                    $error_list .= $v . ',';
                }
            } 
        }
        if (! empty ( $error_list )) {
            $error_list = $error_list .$error_str."\n";
        }
        $ret ['user_id'] = empty ( $userInfo ['id'] ) ? 0 : $userInfo ['id'];
        $ret ['error_msg'] = $error_list;
        return $ret;
    }
}
