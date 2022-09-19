<?php
/**
 * 通用白名单分类
 *
 * @date 2018-5-28
 */
use core\dao\BwlistTypeModel;
use libs\utils\Logger;

class BwTypeAction extends CommonAction {


   public static $validate = array(
        array('name','require','分类名称不能为空！',1),
        array('type_key','require','分类标识不能为空',1),
    );

    public function __construct(){

        parent::__construct();
    }

    /**
     * 分类操作界面
     */
    public function index(){

        $_REQUEST['name']       = stripslashes($_REQUEST['name']);
        $_REQUEST['type_key']     = stripslashes($_REQUEST['type_key']);
        $_REQUEST['is_effect']     = isset($_REQUEST['is_effect']) ? intval($_REQUEST['is_effect']) : -1;
        $name = stripslashes($_REQUEST['name']);
        $is_effect = stripslashes($_REQUEST['is_effect']);

        $map                = $this->_search();
        $model              = M('BwlistType');
        //追加默认参数
        if ($this->get("default_map"))
            $map = array_merge($this->get("default_map"), $map); // 搜索框的值覆盖默认值
        if (method_exists($this, '_filter'))  $this->_filter($map);

        if(!empty($name)) $map['name'] = array('like','%'.trim($name).'%');
        if( $is_effect !=-1)  $map['is_effect'] = array('eq',$is_effect);

        if (!empty ($model))
            $this->_list($model, $map);


        $this->assign('p', (isset($_GET['p']) ? (int)$_GET['p'] : 1));
        $this->display();
    }

    protected function form_index_list(&$list){

        foreach($list as $key => $v){
            $list[$key]['name'] = $v['name'];
            $list[$key]['id'] = $v['id'];
            $list[$key]['key'] = $v['type_key'];
            $list[$key]['is_effect'] = empty($v['is_effect']) ? '无效':'有效';
            $list[$key]['createTime'] = date("Y-m-d H:i:s",$v['create_time']);
        }

    }

    /**
     * 保存
     */
    public function save(){
        if (empty($_POST)){
            $this->error("参数错误",1);
        }
        // 更新
        if (isset($_POST['id'])){
            $ret = $this->update();
        }else{
            // 添加
            $ret = $this->insert();
        }
        $msg = empty($ret) ? $this->error("操作失败",1):$this->success("操作成功",1);
    }

    public function update(){
        $id = intval($_POST['id']);
        $log_info = __CLASS__.' '. __FUNCTION__;

        B('FilterString');
        $model = M("BwlistType");
        $condition = "id='{$id}'";
        $vo = $model->where($condition)->find();
        if (empty($vo)){
            $this->error("信息不存在");
        }
        $model->setProperty("_validate",self::$validate);
        $data = $model->create ();
        if (empty($data)){
            $this->error($model->getError(),1);
        }

        if ($vo['type_key'] != $data['type_key']){
            $type_key = addslashes($data['type_key']);
            $condition = "type_key='{$type_key}'";
            $vo = $model->where($condition)->find();
            if (!empty($vo)){
                $this->error("分类标识不能重复",1);
            }
        }
        $data['update_time'] = time();

        $ret = $model->save($data);
        if (empty($ret)){
            Logger::error($log_info.' data '.json_encode($data).' false');
            return false;
        }

        return true;

    }
    public function insert(){

        $log_info = __CLASS__.' '. __FUNCTION__;

        B('FilterString');
        $model = M("BwlistType");
        $model->setProperty("_validate",self::$validate);
        $data = $model->create ();
        if (empty($data)){
            $this->error($model->getError(),1);
        }

        $type_key = addslashes($data['type_key']);
        $condition = "type_key='{$type_key}'";
        $vo = $model->where($condition)->find();
        if (!empty($vo)){
            $this->error("分类标识不能重复",1);
        }
        $data['create_time'] = time();
        $data['update_time'] = time();

        $ret = $model->add($data);
        if (empty($ret)){
            Logger::error($log_info.' data '.json_encode($data).' false');
            return false;
        }

        return true;

    }
    public function add(){

        $this->display();
    }

    /**
     * 编辑分类
     */
    public function mod()
    {
        $id = intval($_GET['id']);

        $bwTypeModel = new BwlistTypeModel();
        $info = $bwTypeModel->getOne($id);
        if (empty($info)) {
            $this->error('信息不存在');
        }
        $this->assign('info', $info);
        $this->display();
    }

}
