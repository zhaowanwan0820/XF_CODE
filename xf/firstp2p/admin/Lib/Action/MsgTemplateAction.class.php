<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use core\data\DealData;

class MsgTemplateAction extends CommonAction{

    private $_tip_variable = '
        {$notice.borrow_real_name}    借款人真实姓名<br>
        {$notice.borrow_user_name}    借款人用户名<br>
        {$notice.borrow_user_idno}    借款人身份证<br>
        {$notice.borrow_address}    借款人住址<br>
        {$notice.borrow_mobile}        借款人手机号<br>
        {$notice.borrow_postcode}    借款人邮箱（历史错误）<br>
        {$notice.borrow_email}        借款人邮箱<br><br>

        {$notice.company_name}        借款公司名称<br>
        {$notice.company_address}    公司地址<br>
        {$notice.company_legal_person}    公司法定代表人<br>
        {$notice.company_tel}        公司联系电话<br>
        {$notice.company_license}    公司营业执照号<br>
        {$notice.company_description}    公司简介';

    public function index()
    {
        $type_id = intval($_REQUEST['id']);
        if($type_id > 0){
            $where = array('msg_typeid' => $type_id);
        }
        $tpl_list = M("MsgTemplate")->where($where)->findAll();

        if($tpl_list){
            foreach($tpl_list as &$tpl_val){
                $tpl_val['msg_title'] = empty($tpl_val['msg_title']) ? l("LANG_".$tpl_val['name']) : $tpl_val['msg_title'];
            }
        }

        $type_name = '';
        if($type_id){
            $this->assign("from",$_REQUEST['from']);
            $type_name = M ( "MsgCategory" )->where ( array ('id' => $type_id) )->getField('type_name');
        }
        $this->assign("type_name",$type_name);
        $this->assign('msg_type_list', $this->makeListTree(true));
        $this->assign("tpl_list",$tpl_list);
        $this->assign("type_id",$type_id > 0 ? $type_id : 0);
        $this->display();
    }

    /**
     * 导出合同模板
     */
    public function export()
    {
        $id = intval($_REQUEST['id']);

        //取出模板列表和分类信息
        $templateInfo = M('MsgTemplate')->where(array('msg_typeid' => $id))->findAll();
        $categoryInfo = M('MsgCategory')->where(array('id' => $id))->find();

        $zipFileName = tempnam('', 'zip_');
        $downloadFileName = iconv('utf-8', 'gbk', $categoryInfo['type_name']).date('.Ymd').'.zip';

        //打包成zip文件
        $zip = new ZipArchive();
        $zip->open($zipFileName, ZipArchive::OVERWRITE);

        foreach ($templateInfo as $item)
        {
            $filename = iconv('utf-8', 'gbk', $item['msg_title']).'.doc';

            $content = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
            $content .= $item['content'];
            $content .= '</html>';

            $zip->addFromString($filename, $content);
        }
        $zip->close();

        //下载
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename=\"$downloadFileName\"");

        echo file_get_contents($zipFileName);

        unlink($zipFileName);
    }

    /**
     * 模板添加
     * @author wenyanlei  2013-10-30
     */
    public function add(){

        //复制
        $is_copy = 0;
        $tpl_info = array();
        if($_POST['tag'] && $_POST['tag'] == 'copy'){

            $is_copy = 1;
            $tpl_id = intval($_POST['copy_id']);

            $tpl_info = M ( "MsgTemplate" )->where ( "id=$tpl_id" )->find ();
            $tpl_info['msg_title'] = empty($tpl_info['msg_title']) ? l("LANG_".$tpl_info['name']) : $tpl_info['msg_title'];
            $tpl_info['msg_type_id'] = intval($_POST['parent_id']);

            if($_POST['select_type'] == 1){
                $insert_data['type_name'] = trim($_POST['type_name']);
                $insert_data['parent_id'] = intval($_POST['parent_id']);
                $insert_data['is_contract'] = intval($_POST['is_contract']);
                $insert_data['contract_type'] = intval($_POST['contract_type']);
                $insert_data['type_tag'] = ($insert_data['is_contract'] == 0 || $insert_data['parent_id'] == 0) ? '' : trim($_POST['type_tag']);
                $insert_data['create_time'] = get_gmtime();

                if($insert_data['type_name'] == ''){
                    $this->error('分类名称不能为空');
                }

                if($insert_data['parent_id'] != 0 && $insert_data['is_contract'] == 1 && $insert_data['type_tag'] == ''){
                    $this->error('合同分类标识不能为空');
                }

                if(!empty($insert_data['type_tag'])){
                    $is_have_tag = M('MsgCategory')->where(array('type_tag' => $insert_data['type_tag']))->findAll();
                    if($is_have_tag){
                        $this->error("合同分类标记已经存在");
                    }
                }

                $GLOBALS['db']->autoExecute(DB_PREFIX."msg_category",$insert_data,"INSERT");
                $type_id = $GLOBALS['db']->insert_id();
                if(!$type_id){
                    $this->error('添加分类失败');
                }
                $tpl_info['msg_type_id'] = $type_id;
            }
        }



        $this->assign('is_copy', $is_copy);
        $this->assign('tpl_info', $tpl_info);
        $this->assign('tip_variable', $this->_tip_variable);

        //分类列表
        $this->assign ( 'list', $this->makeListTree(true) );

        $this->display ();
    }

    public function do_add(){
        $insert_data['msg_title'] = trim($_POST['msg_title']);
        $insert_data['contract_title'] = trim($_POST['contract_title']);
        $insert_data['name'] = trim($_POST['tmpl_name']);
        $insert_data['type'] = intval($_POST['type']);
        $insert_data['is_html'] = intval($_POST['is_html']);
        $insert_data['msg_typeid'] = intval($_POST['parent_id']);
        $insert_data['content'] = $insert_data['is_html'] == 1 ? str_replace('./', "", $_POST['content1']) : $_POST['content0'];

        $this->assign('jumpUrl',"javascript:history.back(-1);");
        if($insert_data['msg_title'] == ''){
            $this->error('分类名称不能为空');
        }

        if($insert_data['name'] == ''){
            $this->error('分类名称不能为空');
        }

        $is_have_name = M('MsgTemplate')->where(array('name' => $insert_data['name']))->findAll();
        if($is_have_name){
            $this->error("模板标识已经存在");
        }

        // 更新数据
        $list=M('MsgTemplate')->add ($insert_data);

        if (false !== $list) {
            //更新redis模版文件
            $tpl_list = M("MsgTemplate")->findAll();
            $this->updateMsgTemplate($tpl_list);

            //成功提示
            $this->assign('jumpUrl','/m.php?m=MsgTemplate&a=index');
            $this->success('添加成功');
        } else {
            //错误提示
            $this->error('添加失败');
        }
    }

    public function copy_tpl(){
        $this->assign ( 'list', $this->makeListTree(true));
        $this->assign ( 'copy_id', intval($_REQUEST['id']) );
        $this->display ();
    }

    public function check_tpl_name(){
        $name = urldecode($_REQUEST['tpl_name']);
        $tag = $_REQUEST['tag'];
        $id = intval($_REQUEST['id']);

        if(empty($name) || empty($tag)){
            $this->ajaxReturn('非法操作','',0);
        }

        $where = "name='$name'";
        if($tag == 'edit'){
            if($id <= 0){
                $this->ajaxReturn('非法操作','',0);
            }
            $where .= ' and id !='.$id;
        }
        $is_have_name = M('MsgTemplate')->where($where)->findAll();
        //echo M('MsgTemplate')->getLastSql();

        if($is_have_name){
            $this->ajaxReturn('标识已经存在','',0);
        }else{
            $this->ajaxReturn('标识不存在','',1);
        }
    }

    //2013/08/09 因添加富文本编辑器额外的load方法  By Liwei
    public function load_template()
    {
        //查询当前选中的模板
        $type_id = intval($_REQUEST['type_id']);
        $edit_id = intval($_REQUEST['edit_id']);

        //要修改的模板
        $tpl = $tpl_where = array();

        //获取分类下全部模板列表
        if($type_id){
            $tpl_where = array('msg_typeid' => $type_id);
        }

        $tpl_list = M("MsgTemplate")->where($tpl_where)->findAll();
        if($tpl_list){
            foreach($tpl_list as &$tpl_val){
                $tpl_val['msg_title'] = empty($tpl_val['msg_title']) ? l("LANG_".$tpl_val['name']) : $tpl_val['msg_title'];
                if($tpl_val['id'] == $edit_id){
                    $tpl = $tpl_val;
                }
            }
        }

        $type_name = '';
        if($type_id){
            $type_name = M ( "MsgCategory" )->where ( array ('id' => $type_id) )->getField('type_name');
        }

        $param = $this->get_param_lang($tpl['name']);

        $this->assign("type_name",$type_name);
        $this->assign("type_id",$type_id);
        $this->assign("tpl",$tpl);
        $this->assign("param",$param);
        $this->assign("tpl_list",$tpl_list);
        $this->assign('tip_variable', $this->_tip_variable);
        $this->assign('msg_type_list', $this->makeListTree(true));
        $this->display("index");
    }


    public function load_tpl()
    {
        $name = trim($_REQUEST['name']);
        $tpl = M("MsgTemplate")->where("name='".$name."'")->find();
        if($tpl)
        {
            $tpl['tip'] = l("MSG_TIP_".strtoupper($name));
            $this->ajaxReturn($tpl,'',1);
        }
        else
        {
            $this->ajaxReturn('','',0);
        }
    }

    public function update()
    {
        $data = M(MODULE_NAME)->create ();
        if($data['name']==''||$data['id']==0)
        {
            $this->error(l("SELECT_MSG_TPL"));
        }
        $log_info = $data['name'];

        // 强制删除编辑器添加的 ./
        $data['content'] = str_replace('./', "", $data['content']);

        //$this->assign("jumpUrl",u(MODULE_NAME."/index"));
        $is_have_name = M('MsgTemplate')->where("name = '{$data['name']}' and id != {$data['id']}")->findAll();
        if($is_have_name){
            $this->error("模板标识已经存在");
        }

        // 更新数据
        //$list=M(MODULE_NAME)->save ($data);
        $update = $GLOBALS['db']->autoExecute(DB_PREFIX."msg_template",$data,"UPDATE",'id = '.$data['id']);

        if (false !== $update) {
            //更新redis模版文件
            $tpl_list = M("MsgTemplate")->findAll();
            $this->updateMsgTemplate($tpl_list);

            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }

    /*
     * 更新模板缓存
     */
    public function updateCache()
    {
        $tpl_id = intval($_REQUEST['id']);
        $tpl = MI("MsgTemplate")->where("id='".$tpl_id."'")->find();
        $deal_data = new DealData();
        $result = $deal_data->setMsgTemplatesByName($tpl['name'],$tpl);
        if($result){
            $this->success(L("UPDATE_SUCCESS"));
        }else{
            save_log('update cache'.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,'update cache '.L("UPDATE_FAILED"));
        }
    }

    /**
    * 输出模板中的变量
    */
    private function get_param_lang($tpl_name){

        $contract_param_lang = $GLOBALS['contract'];

        $param_lang = array();

        if($contract_param_lang){

            foreach($contract_param_lang as $tpl => $param){
                if(strpos($tpl_name, $tpl) !== false){
                    $param_lang = $param;
                    break;
                }
            }
        }
        return $param_lang;
    }

    /**
     * 合同模板分类管理
     */
    public function cont_type() {
        //跳转到合同服务地址
        $this->redirect("ContractService/getCategory",array());
        $map['is_delete'] = 0;
        $map['is_contract'] = 1;
        $map['type_tag'] = array('neq', '');

        if(trim($_REQUEST['type_name'])){
            $map['type_name'] = array('like','%'.trim($_REQUEST['type_name']).'%');
        }

        if(is_numeric($_REQUEST['contract_type'])){
            $map['contract_type'] = intval($_REQUEST['contract_type']);
        }

        if(is_numeric($_REQUEST['use_status'])){
            $map['use_status'] = intval($_REQUEST['use_status']);
        }

        $this->_list ( M('MsgCategory'), $map );
        $this->display ();
    }

    /**
     * 合同分类添加
     */
    public function cont_type_add(){

        $data = M('MsgCategory')->create ();

        if($data){
            $data['parent_id'] = 1;
            $data['is_contract'] = 1;
            $data['create_time'] = get_gmtime();

            $is_have_tag = M('MsgCategory')->where(array('type_tag' => $data['type_tag'], 'is_delete' => 0))->find();
            if($is_have_tag){
                $this->error("合同分类标记已经存在");
            }
            if(!M('MsgCategory')->add($data)){
                $this->error('添加失败');
            }
            $this->success('添加成功');
        }else{
            $this->display ();
        }
    }

    /**
     * 合同分类修改
     */
    public function cont_type_edit(){

        $data = M('MsgCategory')->create ();

        if($data){
            $id = $data['id'];
            $is_have_tag = M('MsgCategory')->where("type_tag = '{$data['type_tag']}' and id != {$id} and is_delete = 0")->find();
            if($is_have_tag){
                $this->error("合同分类标记已经存在");
            }

            if(M('MsgCategory')->save($data) === false){
                $this->error('修改失败');
            }
            $this->success('修改成功');
        }else{
            $type_id = intval($_REQUEST['id']);
            $type_info = M ( "MsgCategory" )->where ( array ('id' => $type_id) )->find();
            if(empty($type_info)){
                $this->error('非法操作');
            }
            $this->assign ( 'type_info', $type_info );
            $this->display ();
        }
    }

    /**
     * 短信邮件模板分类管理
     */
    public function msg_type() {
        $this->assign ( 'list', $this->makeListTree() );
        $this->display ();
    }



    /**
    * 短信邮件模板分类添加
    */
    public function msg_type_add(){

        $data = M('MsgCategory')->create ();

        if($data){
            $data['is_contract'] = 0;
            $data['create_time'] = get_gmtime();

            if(!M('MsgCategory')->add($data)){
                $this->error('添加失败');
            }
            $this->success('添加成功');
        }else{
            $this->assign ( 'list', $this->makeListTree() );
            $this->display ();
        }
    }

    /**
     * 短信邮件模板分类修改
     */
    public function msg_type_edit(){

        $data = M('MsgCategory')->create ();

        if($data){
            if(M('MsgCategory')->save($data) === false){
                $this->error('修改失败');
            }
            $this->success('修改成功');
        }else{

            $type_id = intval($_REQUEST['id']);
            $type_info = M ( "MsgCategory" )->where ( array ('id' => $type_id) )->find();
            if(empty($type_info)){
                $this->error('非法操作');
            }
            $this->assign ( 'list', $this->makeListTree() );
            $this->assign ( 'type_info', $type_info );

            $this->display ();
        }
    }

    public function cont_type_copy(){

        $type_id = intval($_REQUEST['id']);
        if($type_id <= 0){
            $this->error('非法操作');
        }
        $type_info = M ( "MsgCategory" )->where ( array ('is_delete' => 0, 'id' => $type_id) )->find ();
        if(empty($type_info) || !$type_info['is_contract'] || empty($type_info['type_tag'])){
            $this->error('非法操作');
        }

        if($_POST){
            $data['type_name'] = htmlspecialchars(trim($_POST['type_name']));
            $data['type_tag'] = htmlspecialchars(trim($_POST['type_tag']));
            $data['parent_id'] = $type_info['parent_id'];
            $data['use_status'] = $type_info['use_status'];
            $data['is_contract'] = $type_info['is_contract'];
            $data['contract_type'] = $type_info['contract_type'];
            $data['create_time'] = get_gmtime();

            if(empty($data['type_name']) || empty($data['type_tag'])){
                $this->error('分类名称和标识不能为空');
            }
            if(M('MsgCategory')->where(array('type_tag' => $data['type_tag'], 'is_delete' => 0))->find()){
                $this->error("分类标识已经存在");
            }

            $tag_conf = array(
                    'TPL_LOAN_CONTRACT_ADV',//借款合同预签
                    'TPL_LOAN_CONTRACT',//借款合同||资产收益权转让协议
                    'TPL_ENTRUST_WARRANT_CONTRACT_ADV',//委托担保合同预签
                    'TPL_ENTRUST_WARRANT_CONTRACT',//委托担保合同
                    'TPL_WARRANDICE_CONTRACT_ADV',//保证反担保合同预签
                    'TPL_WARRANDICE_CONTRACT',//保证反担保合同
                    'TPL_WARRANT_CONTRACT_ADV',//保证合同预签
                    'TPL_WARRANT_CONTRACT',//保证合同
                    'TPL_BORROWER_PROTOCAL',//借款人平台服务协议||资产转让方咨询服务协议
                    'TPL_LENDER_PROTOCAL',//出借人平台服务协议||资产受让方咨询服务协议
                    'TPL_BUYBACK_NOTIFICATION',//资产收益权回购通知
                    'TPL_DEAL_PAYMENT_ORDER',//汇赢的《付款委托书》
                    'TPL_DEAL_LOAN_PROVE',//见证人证明书(借款合同)
                    'TPL_DEAL_WARRANT_PROVE',//见证人证明书(保证合同)
            );

            $GLOBALS['db']->startTrans ();
            try {
                $GLOBALS['db']->autoExecute(DB_PREFIX."msg_category",$data,"INSERT");
                $new_type_id = $GLOBALS['db']->insert_id();

                if($new_type_id){
                    $msg_template = M('MsgTemplate')->where(array('msg_typeid' => $type_id))->findAll();
                    foreach($msg_template as $temp){
                        $temp_data['name'] = '';
                        foreach($tag_conf as $tag){
                            if(strpos($temp['name'], $tag) !== false){
                                $temp_data['name'] = $tag.'_'.$data['type_tag'];
                                break;
                            }
                        }
                        if(empty($temp_data['name'])){
                            $temp_data['name'] = str_replace($type_info['type_tag'], $data['type_tag'], $temp['name']);
                        }

                        $sub_start = strpos(str_replace('（', '(', $temp['msg_title']),'(');
                        $base_title = $sub_start ? substr($temp['msg_title'],0,$sub_start) : $temp['msg_title'];
                        $temp_data['contract_title'] = $temp['contract_title'];
                        $temp_data['msg_title'] = sprintf("%s(%s)", $base_title, $data['type_name']);
                        $temp_data['content'] = $temp['content'];
                        $temp_data['is_html'] = $temp['is_html'];
                        $temp_data['msg_typeid'] = $new_type_id;
                        $temp_data['type'] = $temp['type'];

                        if($GLOBALS['db']->autoExecute(DB_PREFIX."msg_template",$temp_data,"INSERT") == false){
                            throw new Exception(sprintf("复制模板：%s 至 %s 失败！", $temp['msg_title'], $temp_data['msg_title']));
                            exit;
                        }
                    }
                    $GLOBALS['db']->commit();
                    $this->success('复制成功');
                }else{
                    throw new Exception("复制分类失败");
                }
            } catch (Exception $e) {
                $GLOBALS['db']->rollback();
                $this->error($e->getMessage());
            }
            exit;
        }else{
            $this->assign ( 'type_info', $type_info );
            $this->display ();
        }
    }

    /**
    * 模板分类删除
    * @author wenyanlei  2013-10-30
    */
    public function msg_type_del(){
        $id = $_REQUEST ['id'];
        if (!isset ( $id )) {
            $this->error('非法操作');
        }

        $id_arr = explode ( ',', $id );

        $condition = array (
                'parent_id' => array ('in', $id_arr ),
                'is_delete' => 0
        );

        $msg_category_model = M ( "MsgCategory" );

        $son_list = $msg_category_model->where($condition)->findAll();

        if($son_list){
            $this->error('不能直接删除父类');
        }

        try {
            $msg_category_model->startTrans();

            $del_category = $msg_category_model->where ( array ('id' => array ('in', $id_arr ) ) )->setField ( 'is_delete', 1 );

            if($del_category === false){
                throw new Exception("删除分类失败");
            }

            $del_template = M ( "MsgTemplate" )->where ( array ('msg_typeid' => array ('in', $id_arr ) ) )->delete();

            if($del_template === false){
                throw new Exception("删除分类下的模板失败");
            }

            $msg_category_model->commit();
            $this->success (l("DELETE_SUCCESS"));

        } catch (Exception $e) {
            $msg_category_model->rollback();
            $this->error($e->getMessage());
        }
    }


    private function makeListTree($have_cont = false){
        $map['is_delete'] = 0;
        if(!$have_cont){
            $map['is_contract'] = 0;
        }

        $type_list = M ( "MsgCategory" )->where ( $map )->findAll ();

        return $this->makeDataTrees($type_list);
    }

    /**
     * 新的分类树的处理
     * @param unknown $type_list
     * @param number $k
     * @param number $fid
     * @param unknown $return
     * @return array
     */
    private function makeDataTrees($type_list, $k = 0, $fid = 0, &$return = array())
    {
        //循环一级分类，处理每个一级分类下的子分类层级数据，有几个一级分类就循环几次。
        foreach ( $type_list as &$type ) {
            if ($type ['parent_id'] == $fid) {
                $k ++;
                $str = "";
                for($i = 0; $i < $k - 1; $i ++) {
                    $str .= "&nbsp;&nbsp;";
                }
                $str = ($i == 0) ? $str : $str . "|--";
                $type['type_name'] = $str . $type['type_name'];
                $return[] = $type;
                $this->makeDataTrees ( $type_list, $k, $type ['id'], $return);
                $k --;
            }
        }

        return $return;
    }

    private function updateMsgTemplate($tpl_list){
        $redis = \SiteApp::init()->dataCache->getRedisInstance(); //将所有合同模版放在redis中存储
        $redis->set('contract_templates',serialize($tpl_list),'ex',(strtotime(date("Y-m-d",strtotime("+1 day")))+10800)-time());
    }

    /**
    * 处理数据方便分类树展示
    * @author wenyanlei  2013-10-29
    */
    /* private function get_type_by_parentid($type_list, &$new_type_list, $id_arr = array(0), $level = 0){

        if(is_array($type_list) && is_array($id_arr)){

            $new_ids = array();
            foreach($type_list as $type_key => $type_val){

                if(in_array($type_val['parent_id'], $id_arr)){

                    $type_val['level'] = $level;
                    $type_val['create_time'] = to_date($type_val['create_time']);
                    $type_val['type_old_name'] = $type_val['type_name'];
                    $type_val['contract_tp'] = '';
                    $type_val['is_cont'] = '';
                    $type_val['copy_button'] = '0';

                    if($type_val['is_contract'] == 1){
                        $type_val['is_cont'] = '是';

                        if(!empty($type_val['type_tag'])){
                            $type_val['copy_button'] = '1';
                            if($type_val['contract_type'] == 1){
                                $type_val['contract_tp'] = '公司借款';
                            }else{
                                $type_val['contract_tp'] = '个人借款';
                            }
                        }
                    }

                    if($level > 0){
                        $type_val['type_name'] = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level-1).'|--'.$type_val['type_name'];
                        //$type_val['type_name'] = '|'.str_repeat(htmlentities('-- '), $level).$type_val['type_name'];
                    }

                    $new_type_list[] = $type_val;
                    $new_ids[] = $type_val['id'];
                    unset($type_list[$type_key]);
                }
            }

            if($type_list){
                $this->get_type_by_parentid($type_list, $new_type_list, $new_ids, ++$level);
            }
        }
        return true;
    } */
}
?>
