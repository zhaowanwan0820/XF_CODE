<?php
/**
 * 通用白列表
 *
 * @date 2018-5-29
 */
use core\dao\BwlistTypeModel;
use core\dao\BwlistModel;
use libs\utils\Logger;

class BwListAction extends CommonAction {

    // 新增
    const STATUS_ADD = 1;
    // 移除
    const STATUS_DEL = 0;

    public static  $one_data = array();


    public function __construct(){

        parent::__construct();
    }

    /**
     * 列表
     */
    public function index(){

        $_REQUEST['vlaue1']       = stripslashes($_REQUEST['value1']);
        $_REQUEST['value2']  = stripslashes($_REQUEST['value2']);
        $_REQUEST['value3'] =  stripslashes($_REQUEST['value3']);
        $value = stripslashes($_REQUEST['value1']);
        $value2 = stripslashes($_REQUEST['value2']);
        $value3 = stripslashes($_REQUEST['value3']);
        $type_id = intval($_REQUEST['tid']);
        if (empty($type_id)){
            $this->error('参数错误');
        }
        $type_info = M('BwlistType')->where('id='.$type_id)->find();
        if (empty($type_info)){
            $this->error('分类信息不存在');
        }

        $map                = $this->_search();
        $model              = M('Bwlist');
        //追加默认参数
        if ($this->get("default_map"))
            $map = array_merge($this->get("default_map"), $map); // 搜索框的值覆盖默认值
        if (method_exists($this, '_filter'))  $this->_filter($map);
        if (!empty($value)) $map['value'] = array('like','%'.trim($value).'%');
        if (!empty($value2)) $map['value2'] = array('like','%'.trim($value2).'%');
        if (!empty($value3)) $map['value3'] = array('like','%'.trim($value3).'%');
        $map['type_id'] = array('eq',$type_id);
        if (!empty ($model))
            $this->_list($model, $map);

        $this->assign('typeName',$type_info['name']);
        $this->assign('typeId',$type_id);
        $this->assign('note',$type_info['note']);
        $this->assign('p', (isset($_GET['p']) ? (int)$_GET['p'] : 1));
        $this->display();
    }

    protected function form_index_list(&$list){

        foreach($list as $key => $v){
            $list[$key]['value1'] = $v['value'];
            $list[$key]['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
            $opt_name = M("Admin")->where("id=".$v['admin_id'])->getField("adm_name");
            $list[$key]['opt_name'] = empty($opt_name) ? '--' : $opt_name;
        }

    }

    public function add(){
        $type_id = intval($_GET['typeId']);
        $type_info = M('BwlistType')->where('id='.$type_id)->find();
        if (empty($type_info)){
            $this->error('分类信息不存在',1);
        }
        $this->assign('');
        $this->assign('typeId',$type_id);
        $this->display();
    }

    /**
     * 保存
     */
    public function save(){
        if (empty($_POST)){
            $this->error("参数错误",1);
        }
        // 添加
        $ret = $this->insert();
        $msg = empty($ret) ? $this->error("操作失败",1):$this->success("操作成功",1);
    }

    public function insert(){

        $log_info = __CLASS__.' '. __FUNCTION__;

        B('FilterString');
        $model = M("Bwlist");
        $data = $model->create ();
        if (empty($data)){
            $this->error($model->getError(),1);
        }

        if (empty($data['value']) && empty($data['value2']) && empty($data['value2'])){
            $this->error('三个值不能都为空',1);
        }
        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['type_id'] = intval($_POST['typeId']);
        $data['admin_id'] = $adm_session['adm_id'];

        $ret = $model->add($data);
        if (empty($ret)){
            Logger::error($log_info.' data '.json_encode($data).' false');
            return false;
        }
        Logger::info($log_info.' '.$data['admin_id']);
        return true;

    }
    public function import(){

        $tid = intval($_REQUEST['tid']);
        if (empty($tid)){
            $this->error('参数错误');
        }
        if (empty ( $_FILES ['upfile'] ['name'] )) {
            $this->error('上传文件不能为空');
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $this->error ( "请上传csv格式的文件！" );
        }

        $max_import_line = 3000;

        $csv_content = file_get_contents ( $_FILES ['upfile'] ['tmp_name'] );
        $csv_content = trim ($csv_content);
        if (empty ( $csv_content )) {
            $this->error ( '文件内容不能为空' );
        }
        $total_line = explode ( "\n", iconv ( 'GBK', 'UTF-8', $csv_content ) );
        // 统计去掉第一个行Title
        $count_total_line = count ( $total_line ) - 1;
        // 最后一行如果空行，不做计数
        if (empty ( $total_line [$count_total_line] )) {
            $count_total_line -= 1;
        }
        if ($count_total_line > $max_import_line) {
            $this->error ( '最大导入' . $max_import_line . '条数据' );
        }

        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" )) === false) {
            $this->error('文件不可读');
        }
        // 第一行是标题不放到数据列表里
        if (fgetcsv ( $handle ) === false) {
            $this->error('数据读取错误');
        }
        $filename = basename($_FILES ['upfile'] ['name']);
        $i = 0;
        $j = 0;
        $err_msg = '';
        $error_total_num = 0;

        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );

        while ( ($row_data = fgetcsv ( $handle )) !== false ) {

            $ret = $this->checkCsv($row_data, $tid);
            if ($ret == false){
                $error_total_num++;
                $j++;
                $err_msg .= '第 '.$j.' 行 '.implode(',',$row_data).' <br />';
                continue;
            }
            $csv_row[$i] = array(
                'value' => addslashes(trim($row_data[0])),
                'value2' => addslashes(trim($row_data[1])),
                'value3' => addslashes(trim($row_data[2])),
                'type_id' => $tid,
                'create_time' => time(),
                'update_time' => time(),
                'admin_id' => $adm_session['adm_id'],
                'status' => $row_data[3],
            );
            $i++;
            $j++;
        }
        fclose ( $handle );
        @unlink ( $_FILES ['upfile'] ['tmp_name'] );

        if (empty($csv_row)){
            $err_msg = "文件名：{$filename}<br />
                    成功：0条<br />
                    失败：{$error_total_num}条<br />
                    失败明细：{$err_msg}
                ";
            $this->error($err_msg);
        }


        $log_info = __CLASS__.' '.__FUNCTION__.' '.$adm_session['adm_id'];
        $bwlistModel = M('Bwlist');
        try {
            $bwlistModel->startTrans();
            $i = 0;
            foreach ($csv_row as $key => $v) {
                switch($v['status']){
                    case self::STATUS_ADD:
                        unset($v['status']);
                        $ret = $bwlistModel->add($v);
                        break;
                    case self::STATUS_DEL:
                        $where = $this->getDelWhere($v);
                        if (empty($where)) {
                            $ret = false;
                            break;
                        }

                        $ret = $bwlistModel->where($where)->delete();
                        Logger::info($log_info.' delete '.$where.' '.$ret);
                        break;
                    default:
                        Logger::error(__CLASS__.' '.__FUNCTION__.' status error '.json_encode($v));
                        continue;
                        break;
                }

                if (empty($ret)){
                    throw new \Exception('写入失败 '.json_encode($v));
                }
                $i++;
            }

            $bwlistModel->commit();
        }catch (\Exception $e){
            $bwlistModel->rollback();
            Logger::error($log_info.' '.' fail '.$e->getMessage());
            $this->error('导入失败 第 '.($i+1).'行错误 ');
        }
        save_log($log_info.L("INSERT_SUCCESS"),1,'',json_encode($csv_row),2);
        $msg = "文件名：{$filename}
        <br />成功：{$i}条<br />
        失败：{$error_total_num}条<br />
        失败明细：{$err_msg}
                ";
        $this->success($msg);
    }

    private function getDelWhere($data){

        if (empty($data['type_id'])){
            return false;
        }

        $where = '';
        if (!empty($data['value'])){
            $where .= "value='{$data['value']}'";
        }
        if (!empty($data['value2'])){
            $where = empty($where) ?    "value2='{$data['value2']}'" : $where." and value2='{$data['value2']}' ";
        }
        if (!empty($data['value3'])){
            $where = empty($where) ? "value3='{$data['value3']}'" : $where." and value3='{$data['value3']}' ";
        }

        if (!empty($where)){
            $where .= " AND type_id='{$data['type_id']}'";
        }

        return $where;
    }

    private function checkCsv($data, $tid = 0){


        if (count($data) !=4) return false;
        if (empty($data[0]) && empty($data[1]) && empty($data[2])) return false;
        if (!in_array($data[3],array(1,0))) return false;
        if (empty(self::$one_data)){
            self::$one_data = array(
                'value' => $data[0],
                'value2' => $data[1],
                'value3' => $data[2],
            );
        }else{
            if (!empty(self::$one_data['value']) && empty($data[0])){
                return false;
            }
            if (!empty(self::$one_data['value2']) && empty($data[1])){
                return false;
            }
            if (!empty(self::$one_data['value3']) && empty($data[2])){
                return false;
            }
        }
        if ($data[3] == self::STATUS_ADD){
            return true;
        }
        $bwlistModel = M('Bwlist');
        $del_data = array(
            'value' => $data[0],
            'value2' => $data[1],
            'value3' => $data[2],
            'type_id' => $tid
        );
        $where = $this->getDelWhere($del_data);
        if (!empty($where)) {
            $info = $bwlistModel->where($where)->find();
            if (empty($info)){
                return false;
            }
        }
        return true;
    }

    public function del(){

        $id = intval($_REQUEST['id']);
        if (empty($id)){
            $this->error('参数错误');
        }
        $log_info = $id;
        $ret = M('Bwlist')->where("id=".$id)->delete();
        if ($ret){
            save_log($log_info.L("DELETE_SUCCESS"),1,$id);
            $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
            Logger::info($log_info.' '.__CLASS__.' '.__LINE__.' '.$adm_session['adm_id'].' succ');
            $this->success("操作成功");
        }else{
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.' del fail '.$id);
            $this->error("操作失败");
        }
    }

    public function typeDel(){
        $id = intval($_REQUEST['tid']);
        if (empty($id)){
            $this->error('参数错误');
        }
        $log_info = $id;
        $ret = M('Bwlist')->where("type_id='{$id}'")->delete();
        if ($ret){
            save_log($log_info.L("DELETE_SUCCESS"),1,$id);
            $this->success("操作成功");
        }else{
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.' del fail '.$id);
            $this->error("操作失败");
        }
    }

}
