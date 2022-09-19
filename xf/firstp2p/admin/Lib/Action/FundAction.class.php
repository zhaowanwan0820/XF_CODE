<?php

/**
 * 基金项目管理 
 * @author 杨庆<yangqing@ucfgroup.com>
 */
class FundAction extends CommonAction{
    public function index()
    {
        parent::index();
    }
    public function trash()
    {
        $condition['is_delete'] = 1;
        $this->assign("default_map",$condition);
        parent::index();
    }
    public function add()
    {
        $this->display();
    }
    public function edit() {        
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;     
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );
        $this->display ();
    }

    public function member(){
        $id = intval($_REQUEST['id']);
        $fund = M('Fund')->where(array('id'=>$id))->field('id,name,status')->find();
        $condition['fund_id'] = $id;    
        $list = M('FundSubscribe')->where($condition)->select();
        $this->assign('list', $list);
        $this->assign('fund', $fund);
        $this->display();
    }

    public function delete() {
        die('尚未实现');
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $fund = M('Fund')->where($condition)->find();
            if($fund){
                $ret = M(MODULE_NAME)->where($condition)->delete();             
                if ($ret!==false) {
                    save_log($fund['name'].l("DELETE_SUCCESS"),1);
                    $this->success (l("DELETE_SUCCESS"),$ajax);
                } else {
                    save_log($fund['name'].l("DELETE_FAILED"),0);
                    $this->error (l("DELETE_FAILED"),$ajax);
                }
            }else{
                $this->error ('参数错误',$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }       
    }
    
    public function insert() {
        $data = M(MODULE_NAME)->create ();

        // 更新数据
        $log_info = $data['title'];
        $data['create_time'] = get_gmtime();
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
    
    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }
}
?>
