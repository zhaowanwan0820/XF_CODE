<?php
/**
 * 掌众信息披露列表后台
 * @author zhaohui <zhaohui3@ucfgroup.com>
 *
 */

class WeshareInfoDisclosureAction extends CommonAction {
    /**
     * 信息披露列表首页
     *
     */
    public function index(){
        $form = D(MODULE_NAME);
        if (!empty($form)) {
            $this->_list($form);
        }
        $list = $this->get('list');
        $this->assign('main_title', '信息披露列表');
        //$this->assign('list', $list);
        $this->display();
    }
    /**
     * 新增信息披露编辑
     *
     */
    public function add(){
        $this->assign('main_title', '新增信息披露');
        $this->display();
    }
    /**
     * 新增信息披露
     *
     */
    public function insert(){
        //校验传入参数
        $form = D(MODULE_NAME);
        if (!empty($form)) {
            $this->_list($form);
        }
         $list = $this->get('list');
         // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }
        adddeepslashes($data);//过滤参数
        //检查唯一性（产品类型和投资期限）
        $check_data = array();
        $check_data['ptype'] = $data['product_type'];
        $check_data['term'] = $data['invest_term'];
        $check_data['unit'] = $data['invest_unit'];
        if ($this->checkUniqe($check_data,$ajax=0)) {
            $this->error("添加失败，项目信息已存在", 0);
        }
        //设置create_time
        $data['create_time'] = time();
        // 保存
        $result = $form->add($data);

        //日志信息
        $log_info = "[" . $form->getLastInsID() . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";

        if ($result) {
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1,'',$data);
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0);
        }
        $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
        $this->success(L("INSERT_SUCCESS"));
    }
    /**
     * 编辑更新
     *
     */
    public function update()
    {
        B('FilterString');
        $form = D(MODULE_NAME);
        // 字段校验
        $data = $form->create();
        $fields = $form->getDbFields();
        //设置update_time
        if(in_array('update_time',$fields)){
            $form->__set('update_time',time());
        }
        if (!$data) {
            $this->error($form->getError());
        }
        adddeepslashes($data);//过滤参数
        //检查唯一性（产品类型和投资期限）
        $check_data = array();
        $check_data['ptype'] = $data['product_type'];
        $check_data['term'] = $data['invest_term'];
        $check_data['unit'] = $data['invest_unit'];
        $check_res = $this->checkUniqe($check_data,$ajax=0);
        if ($check_res && $check_res != $data['id']) {
            $this->error("添加失败，项目信息已存在", 0);
        }
        $this->is_log_for_fields_detail = true;
        //日志信息
        $log_info = "[" . $data[$this->pk_name] . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";
        $this->pk_value = $data[$this->pk_name];
        $old_data = '';
        $new_data = '';
        if ($this->is_log_for_fields_detail) { // 记录字段更新详细日志
            $condition[$this->pk_name] = $this->pk_value;
            $old_data = M(MODULE_NAME)->where($condition)->find();
            $new_data = $data;
        }
        // 保存
        $result = $form->save();
        if ($result !== false) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1, $old_data, $new_data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            //$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }
    //检查产品类型和投资期限的唯一性
    public function checkUniqe($data,$ajax = 1) {
        if ($ajax == 1) {
            addslashes($_REQUEST);
        } else {
            addslashes($data);
        }
        $condition['product_type'] = $ajax==1 ? $_REQUEST['ptype'] : $data['ptype'];
        $condition['invest_term'] = $ajax==1 ? $_REQUEST['term'] : $data['term'];
        $condition['invest_unit'] = $ajax==1 ? $_REQUEST['unit'] : $data['unit'];
        $ret = M(MODULE_NAME)->where($condition)->find();
        if ($ret['id']) {
            if ($ajax == 1)
                echo json_encode(array('errno'=>0,'id'=>$ret['id']));
            else
                return $ret['id'];
        } else {
            if ($ajax == 1)
                echo json_encode(array('errno'=>1));
            else
                return false;
        }
    }
}
