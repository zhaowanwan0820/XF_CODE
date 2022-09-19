<?php
/**
 * JobsAction class file.
 *
 * @author wangqunqiang@ucfgroup.com
 * */
use core\dao\OtoConfigModel;



class OtoConfigAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }


    public function index() {
        $map = array();
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->_list(D('OtoConfig'), $map);
        $this->assign('p', $p);
        $this->display('index');
    }

    public function createConfig(){
         $item = array (
            'groupId' => '',
            'couponCode' => '',
            'eventName' => '',
            'tagConstant' => '',
            'toGroupId' => '',
            'toCouponLevelId' => '',
            'isEffective' => '',
            'id' => '',
          );
        $groups = \core\dao\UserGroupModel::instance()->findAll();
        $this->assign('events', OtoConfigModel::$events);
        $this->assign('groups', $groups);
        $this->assign('item', $item);
        $this->display('view');
    }

    public function getCouponLevel() {
        $groupId = intval($_GET['groupId']);
        $result = array();
        $result['status'] = 1;
        if (empty($groupId)) {
            $result['options'] = '<option value="0">不选择</option>';
            echo json_encode($result);
            return;
        }
        $sql = 'SELECT id,level FROM firstp2p_coupon_level WHERE is_effect = 1 AND group_id = '.$groupId;
        $data = $GLOBALS['db']->getAll($sql);
        $result['options'] = '';
        if (is_array($data)) {
            foreach ($data as $item) {
                $result['options'] .= "<option value='{$item['id']}'>{$item['level']}</option> \n";
            }
        }
        echo json_encode($result);
        return ;
    }
    public function updateConfig() {
       $item = $_POST['item'];
       $insertMode = 'INSERT';
       if (intval($item['id']) > 0) {
            $insertMode = 'UPDATE';
       }
       else {
            // 参数校验
            $item['addTime'] = time();
            if ((empty($item['couponCode']) && empty($item['groupId'])) || (!empty($item['couponCode']) && !empty($item['groupId']))) {
                $this->error('推荐人会组ID或者优惠码只能填写一项');
            }
            if (!empty($item['toGroupId']) && empty($item['toCouponLevelId'])) {
                $this->error('请选择优惠码等级');
            }
            if (!empty($item['couponCode']))
            {
                $sql = "SELECT count(*) as tp FROM firstp2p_oto_config WHERE eventName = '{$item['eventName']}' AND couponCode = '{$item['couponCode']}'";
                $count = $GLOBALS['db']->getOne($sql);
                if ($count >= 1) {
                    $this->error('相同的优惠码和事件已经存在，请勿重复添加');
                }
            }
            if (!empty($item['groupId']))
            {
                $sql = "SELECT count(*) as tp FROM firstp2p_oto_config WHERE eventName = '{$item['eventName']}' AND groupId = '{$item['groupId']}'";
                $count = $GLOBALS['db']->getOne($sql);
                if ($count >= 1) {
                    $this->error('相同的邀请人会员组和事件已经存在，请勿重复添加');
                }
            }
       }

       $item['groupName'] = $GLOBALS['db']->getOne("SELECT name FROM firstp2p_user_group WHERE id = '{$item['groupId']}'");
       $item['toGroupName'] = $GLOBALS['db']->getOne("SELECT name FROM firstp2p_user_group WHERE id = '{$item['toGroupId']}'");
       $item['toCouponLevelName'] = $GLOBALS['db']->getOne("SELECT level FROM firstp2p_coupon_level WHERE id = '{$item['toCouponLevelId']}'");
       $adm_session = es_session::get(md5(conf("AUTH_KEY")));
       $adm_name = $adm_session['adm_name'];
       $item['updateTime'] = time();
       $item['admName'] = $adm_name;
       if ($insertMode == 'UPDATE') {
           $GLOBALS['db']->autoExecute('firstp2p_oto_config', $item, $insertMode , " id = '{$item['id']}'");
       }
       else {
           $GLOBALS['db']->autoExecute('firstp2p_oto_config', $item, $insertMode);
       }
       $affectRow = $GLOBALS['db']->affected_rows();
       if ($affectRow >= 1) {
            $this->success('保存成功');
       }
       else {
            $this->error('保存失败');
       }
    }

    public function viewConfig() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }
        $item = OtoConfigModel::instance()->find($id);
        if (empty($item)) {
            $this->error('记录不存在');
        }
        $groups = \core\dao\UserGroupModel::instance()->findAll();
        $couponLevels = $GLOBALS['db']->get_slave()->getAll("SELECT id,level FROM firstp2p_coupon_level WHERE is_effect = 1 AND group_id = {$item['toGroupId']}");
        $this->assign('item', $item);
        $this->assign('couponLevels', $couponLevels);
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->assign('events', OtoConfigModel::$events);
        $this->assign('groups', $groups);
        $this->display();
    }
}
