<?php
/**
 *
 * 借款类型
 */


use core\dao\deal\DealModel;

class DealLoanTypeAction extends CommonAction{
    public function index()
    {
        $condition['is_delete'] = 0;
        $condition['pid'] = 0;
        $this->assign("default_map",$condition);

        //列表过滤器，生成查询Map对象
        $map = $this->_search ();
        //追加默认参数
        if($this->get("default_map"))
        $map = array_merge($map,$this->get("default_map"));

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get("list");

        $result = array();
        $row = 0;
        foreach($list as $k=>$v)
        {
            $v['name'] = $v['name'];
            $result[$row] = $v;
            $row++;

        }

        $this->assign("list",$result);

        $this->display ();
        return;
    }


    public function trash()
    {
        $condition['is_delete'] = 1;
        $this->assign("default_map",$condition);
        parent::index();
    }
    public function add()
    {
        $this->assign("newsort",M(MODULE_NAME)->where("is_delete=0")->max("sort")+1);
        $this->display();
    }

    public function insert() {


        $data = M(MODULE_NAME)->create ();

        if(isset($data['auto_loan'])){
            $data['auto_loan'] = 1;
        }

        if(isset($data['auto_start'])){
            $data['auto_start'] = 1;
        }

        if(isset($data['after_fee'])){
            $data['after_fee'] = 1;
        }

        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if(empty($data['name']) || trim($data['name']) == '')
        {
            $this->error(L("DEALCATE_NAME_EMPTY_TIP"));
        }

        if(!preg_match("/^[A-Za-z0-9_]{2,6}$/", $data['type_tag'])){
            $this->error('请输入长度在2-6之间的标识');
        }

        $is_have_tag = M(MODULE_NAME)->where("type_tag = '".$data['type_tag']."'")->find();

        if($is_have_tag){
            $this->error('唯一标识已经存在');
        }

        // 更新数据
        $log_info = $data['name'];
        $list=M(MODULE_NAME)->add($data);
        if (false !== $list) {

            //成功提示
            save_log($log_info.L("INSERT_SUCCESS"),1);

            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    /**
     * 异步检查标识
     */
    public function check_mark_name(){

        $mark = urldecode($_REQUEST['mark']);
        $tag = addslashes($_REQUEST['tag']);
        $id = intval($_REQUEST['id']);

        if(empty($mark) || empty($tag)){
            $this->ajaxReturn('非法操作','',0);
        }

        $where = "type_tag='$mark'";
        if($tag == 'edit'){
            if($id <= 0){
                $this->ajaxReturn('非法操作','',0);
            }
            $where .= ' and id !='.$id;
        }
        $is_have_name = M(MODULE_NAME)->where($where)->find();

        if($is_have_name){
            $this->ajaxReturn('标识已经存在,请重新输入','',0);
        }else{
            $this->ajaxReturn('标识不存在','',1);
        }
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['is_delete'] = 0;
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $conf_value = json_decode($vo['conf_value'],true);
        //借款平台手续费;
        $vo['loan_fee_rate'] = $conf_value['loan_fee_rate'];
        //年化收益基本利率
        $vo['income_base_rate'] = $conf_value['income_base_rate'];
        $this->assign ( 'vo', $vo );

        $this->display ();
    }


    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);

        $info = M(MODULE_NAME)->where("id=".$id)->getField("name");
        if (empty($info)){
            save_log('info not exist'.l("SET_EFFECT_0"),0);
            $this->ajaxReturn(0,l("SET_EFFECT_0"),0);
        }
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态

        $ret = M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        if ($ret === false){
            save_log('info not exist',0);
            $this->ajaxReturn(0,l("SET_EFFECT_0"),0);
        }
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);

        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1);
    }

    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $log_info = M(MODULE_NAME)->where("id=".$id)->getField("name");
        if(!check_sort($sort))
        {
            save_log(l("SORT_FAILED"),0);
            $this->error(l("SORT_FAILED"),1);
        }
        $ret = M(MODULE_NAME)->where("id=".$id)->setField("sort",$sort);
        if ($ret === false){
            save_log(l("SORT_FAILED"),0);
            $this->error(l("SORT_FAILED"),1);
        }
        save_log($log_info.l("SORT_SUCCESS"),1);

        $this->success(l("SORT_SUCCESS"),1);
    }

    public function update() {

        $data = M(MODULE_NAME)->create ();

        if(isset($data['auto_loan'])){
            $data['auto_loan'] = 1;
        }else{
            $data['auto_loan'] = 0;
        }

        if(isset($data['auto_start'])){
            $data['auto_start'] = 1;
        }else{
            $data['auto_start'] = 0;
        }

        if(isset($data['after_fee'])){
            $data['after_fee'] = 1;
        }else{
            $data['after_fee'] = 0;
        }

        if(!preg_match("/^[A-Za-z0-9_]{2,6}$/", $data['type_tag'])){
            $this->error('请输入长度在2-6之间的标识');
        }

        $is_have_tag = M(MODULE_NAME)->where("type_tag = '".$data['type_tag']."' and id != ".$data['id'])->find();

        if($is_have_tag){
            $this->error('唯一标识已经存在');
        }
        //借款平台手续费
        $loan_fee_rate = floatval($_REQUEST['loan_fee_rate']);
        //年化收益基本利率
        $income_base_rate = floatval($_REQUEST['income_base_rate']);
        $data['conf_value'] = json_encode(array('loan_fee_rate' => $loan_fee_rate,'income_base_rate' => $income_base_rate));
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        // 更新数据
        $list = M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1,'',$data['conf_value']);

            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }

    /**
     * 删除记录
     */

    public function delete() {


        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);

        if (isset ( $id )) {
                $condition = array ('id' => array ('in', $id ) );
                $dealModel = new DealModel();
                $ret = $dealModel->checkLoanTypeExist($id);

                if ($ret)
                {
                    $this->error ("标中存在借款类型",$ajax);
                }

                $rel_data = M(MODULE_NAME)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];    
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 1 );
                if ($list!==false) {
                    save_log($info.l("DELETE_SUCCESS"),1);
                    $this->success (l("DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("DELETE_FAILED"),0);
                    $this->error (l("DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 恢复记录
     */
    public function restore() {

        $ajax = intval($_REQUEST['ajax']);
        $id = addslashes($_REQUEST ['id']);
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $rel_data = M(MODULE_NAME)->where($condition)->findAll();                
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];    
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 0 );
                if ($list!==false) {
                    save_log($info.l("RESTORE_SUCCESS"),1);
                    $this->success (l("RESTORE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("RESTORE_FAILED"),0);
                    $this->error (l("RESTORE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    /**
     * 彻底删除
     */
    public function foreverdelete() {

        $ajax = intval($_REQUEST['ajax']);
        $id = addslashes($_REQUEST ['id']);
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

                $rel_data = M(MODULE_NAME)->where($condition)->findAll();                
                foreach($rel_data as $data)
                {
                    $info[] = $data['name'];    
                }
                if($info) $info = implode(",",$info);
                $list = M(MODULE_NAME)->where ( $condition )->delete();

                if ($list!==false) {
                    save_log($info.l("FOREVER_DELETE_SUCCESS"),1);

                    $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }


}
?>