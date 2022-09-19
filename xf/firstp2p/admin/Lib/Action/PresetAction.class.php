<?php
/**
 * 预约管理
 * Enter description here ...
 * @author guomumin<aaron8573@gmail.com>
 *
 */

class PresetAction extends CommonAction{
    public function index(){
        $map = array ();
        $_REQUEST ['listRows'] = 20;
        if(intval($_REQUEST['program_id'])>0)
        {
            $map['program_id'] = intval($_REQUEST['program_id']);
        }
        $name = $this->getActionName ();
        $model = D ( $name );
        if (isset ( $_REQUEST ['_order'] )) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = $model->getPk ();
        }
        $sort = 'desc';
        if (isset ( $_REQUEST ['_sort'] )) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        }
        // 取得满足条件的记录数
        $count = $model->where ( $map )->count ( 'id' );
        if ($count > 0) {
            // 创建分页对象
            $listRows = '';
            if (! empty ( $_REQUEST ['listRows'] ))    $listRows = $_REQUEST ['listRows'];
            $p = new Page ( $count, $listRows );
            // 分页查询数据
            $voList = $model->where ( $map )->order ( "`" . $order . "` " . $sort )->limit ( $p->firstRow . ',' . $p->listRows )->findAll ();
            foreach ( $voList as &$val ){
                // 查询用户帐号金额
                $uinfo = M('user')->where("`mobile` = '{$val['mobile']}'")->find();
                $val['user_money'] = $uinfo['money'];
            }
            // 分页跳转的时候保证查询条件
            /*
            foreach ( $map as $key => $val ) {
                if (! is_array ( $val )) $p->parameter .= "$key=" . urlencode ( $val ) . "&";
            }
            */
            // 分页显示
            $page = $p->show ();
            // 列表排序显示
            $sortImg = $sort; // 排序图标
            $sortAlt = $sort == 'desc' ? l ( "ASC_SORT" ) : l ( "DESC_SORT" ); // 排序提示
            $sort = $sort == 'desc' ? 1 : 0; // 排序方式
            // 模板赋值显示
            $this->assign ( 'list', $voList );
            $this->assign ( 'sort', $sort );
            $this->assign ( 'order', $order );
            $this->assign ( 'sortImg', $sortImg );
            $this->assign ( 'sortType', $sortAlt );
            $this->assign ( "page", $page );
            $this->assign ( "nowPage", $p->nowPage );
        }
        $this->display ();
    }
    /**
     * 删除预约信息
     */
    public function delete(){
        $ajax = intval($_REQUEST['ajax']);
        $ids = $_REQUEST ['id'];
        if (isset ( $ids )) {
            if(stripos($ids, ',') !== FALSE){
                $ids = explode(',', $ids);
                $i = 0;
                foreach ($ids as $id){
                    if($i==0){
                        $where = ' id="'.$id.'" ';
                    }else{
                        $where .= ' or id="'.$id.'" ';
                    }
                    $i++;
                }
            }else{
                $where = ' id="'.$ids.'" ';
            }
            if(M("Preset")->where($where)->delete()){
                $this->success (l("DELETE_SUCCESS"),$ajax);
            }else{
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        }else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    /**
     * 导出预约名单
     * @param 页数 $page
     */
    public function export_csv($page = 1){
        set_time_limit(0);
        $limit = (($page - 1)*intval(app_conf("BATCH_PAGE_SIZE"))).",".(intval(app_conf("BATCH_PAGE_SIZE")));
        $ids = $_REQUEST ['id'];
        if(stripos($ids, ',') !== FALSE){
                $ids = explode(',', $ids);
                $i = 0;
                foreach ($ids as $id){
                    if($i==0){
                        $where = ' id="'.$id.'" ';
                    }else{
                        $where .= ' or id="'.$id.'" ';
                    }
                    $i++;
                }
            }else{
                $where = ' 1=1 ';
            }
        $list = M('Preset')->where($where)->limit($limit)->findAll();

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => M("Preset")->getLastSql()
                )
        );




        if($list){
            register_shutdown_function(array(&$this, 'export_csv'), $page+1);
            $user_value = array('id'=>'""','real_name'=>'""','email'=>'""','mobile'=>'""','money'=>'""','user_name'=>'""','create_time'=>'""','is_staff'=>'""');
            if($page == 1)
            $content = iconv("utf-8","gbk","编号,姓名,用户ID,电子邮箱,手机号,金额,用户名,地址,预约日期,是否内部员工,账户余额,预约项目");
            //开始获取扩展字段
            $extend_fields = M("UserField")->order("sort desc")->findAll();
            foreach($extend_fields as $k=>$v)
            {
                $user_value[$v['field_name']] = '""';
                if($page==1)
                $content = $content.",".iconv('utf-8','gbk',$v['field_show_name']);
            }
            if($page==1)
            $content = $content . "\n";
            foreach($list as $k=>$v)
            {
                $user_value = array();
                $user_value['id'] = iconv('utf-8','gbk','"' . $v['id'] . '"');
                $user_value['real_name'] = iconv('utf-8','gbk','"' . $v['real_name'] . '"');
                $user_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where user_name = '".$v['user_name']."'");
                $user_value['user_id'] = iconv('utf-8','gbk','"' . $user_id . '"');
                $user_value['email'] = iconv('utf-8','gbk','"' . $v['email'] . '"');
                $user_value['mobile'] = "\t".iconv('utf-8','gbk','"' . $v['mobile'] . '"');
                $user_value['money'] = "\t".iconv('utf-8','gbk','"' . (string)$v['money'] . '"');
                $user_value['user_name'] = iconv('utf-8','gbk','"' . $v['user_name'] . '"');
                $user_value['user_area'] = iconv('utf-8','gbk','"' . $v['user_area'] . '"');
                $date = to_date($v['create_time']);
                $user_value['create_time'] = "\t".iconv('utf-8','gbk','"' . $date . '"');
                $user_value['is_staff'] = iconv('utf-8','gbk','"' . $v['is_staff'] . '"');
                // 查询用户帐号金额
                $uinfo = M('user')->where("`mobile` = '{$v['mobile']}'")->find();
                $user_value['user_money'] = "\t".$uinfo['money'];
                $program_name = $GLOBALS['db']->getOne("select program_name from ".DB_PREFIX."preset_program where id=".$v['program_id']);
                $user_value['program_name'] = iconv('utf-8','gbk','"' . $program_name . '"');
                $content .= implode(",", $user_value) . "\n";
            }
            header("Content-Disposition: attachment; filename=preset.csv");
            echo $content;
        }else{
            if($page==1)
            $this->error(L("NO_RESULT"));
        }
    }
    /**
     * 预约项目列表
     * Enter description here ...
     */
    public function preset_program(){
        $status = array(
            0 => "未开始",
            1 => "开售",
            2 => "售馨",
            3 => "强行停止"
        );
        $map = array ();
        $_REQUEST ['listRows'] = 20;
        $model = D ( "preset_program" );
        if (isset ( $_REQUEST ['_order'] )) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = $model->getPk ();
        }
        $sort = 'desc';
        if (isset ( $_REQUEST ['_sort'] )) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        }
        // 取得满足条件的记录数
        $count = $model->where ( $map )->count ( 'id' );
        if ($count > 0) {
            // 创建分页对象
            $listRows = '';
            if (! empty ( $_REQUEST ['listRows'] ))    $listRows = $_REQUEST ['listRows'];
            $p = new Page ( $count, $listRows );
            // 分页查询数据
            $voList = $model->where ( $map )->order ( "`" . $order . "` " . $sort )->limit ( $p->firstRow . ',' . $p->listRows )->findAll ();
            // 分页跳转的时候保证查询条件
            foreach ( $map as $key => $val ) {
                if (! is_array ( $val )) $p->parameter .= "$key=" . urlencode ( $val ) . "&";
            }
            // 分页显示
            $page = $p->show ();
            // 列表排序显示
            $sortImg = $sort; // 排序图标
            $sortAlt = $sort == 'desc' ? l ( "ASC_SORT" ) : l ( "DESC_SORT" ); // 排序提示
            $sort = $sort == 'desc' ? 1 : 0; // 排序方式
            // 模板赋值显示
            $this->assign ( 'list', $voList );
            $this->assign ( 'sort', $sort );
            $this->assign ( 'order', $order );
            $this->assign ( 'sortImg', $sortImg );
            $this->assign ( 'sortType', $sortAlt );
            $this->assign ( "page", $page );
            $this->assign ( "nowPage", $p->nowPage );
        }
        $this->assign("status",$status);
        $this->assign("main_title","预约项目");
        $this->display();
    }
    /**
     * 添加预约项目
     */
    public function add(){
        if (isset($_POST['submit'])){
            $data = array();
            $data['program_name'] = trim($_POST['program_name']);
            $data['program_url'] = trim($_POST['program_url']);
            $data['program_html'] = $_POST['program_html'];
            $data['program_content'] = htmlspecialchars(trim($_POST['program_content']));
            $data['program_status'] = $_POST['program_status'];
            $data['program_create_time'] = get_gmtime();
            $data['program_default'] = 0;
            $data['program_desc'] = $_POST['program_desc'];
            $data['program_deals'] = str_replace('，', ',', $_POST['program_deals']);
            $data['program_is_login'] = intval($_POST['program_is_login']);
            $data['program_area'] = implode('||', $_POST['program_area']);
            if ($data['program_name'] == ""){
                $this->error("项目名称填写错误");
            }
            if ($data['program_html'] == ""){
                $this->error("页面内容不能为空");
            }
            if(!preg_match("/^[a-zA-Z0-9]+$/", $data['program_url'])){
                $this->error("项目url只能是英文和字母的混合");
            }
            $is_have_url = M('PresetProgram')->where(array('program_url' => $data['program_url']))->findAll();
            if($is_have_url){
                $this->error("项目url已经存在");
            }
            $inster_id = M("PresetProgram")->add($data);
            if ($inster_id>0)
            {
                $this->success(L("INSERT_SUCCESS"));
            } else {
                $this->error(L("INSERT_FAILED"));
            }
        }else{
            $province = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2");  //省
            $this->assign("province",$province);
            $this->assign('preset_area', dict::get('PRESET_AREA'));
            $this->display();
        }
    }
    /**
     * 编辑项目
     */
    public function edit(){
        $id = intval($_REQUEST['id']);
        if (isset($_POST['submit'])){
            $data = array();
            $data['program_name'] = trim($_POST['program_name']);
            $data['program_url'] = trim($_POST['program_url']);
            $data['program_html'] = $_POST['program_html'];
            $data['program_content'] = htmlspecialchars(trim($_POST['program_content']));
            $data['program_status'] = $_POST['program_status'];
            $data['program_default'] = 0;
            $data['program_desc'] = $_POST['program_desc'];
            $data['program_deals'] = str_replace('，', ',', $_POST['program_deals']);
            $data['program_is_login'] = intval($_POST['program_is_login']);
            $data['program_area'] = implode('||', $_POST['program_area']);
            if ($data['program_name'] == ""){
                $this->error("项目名称填写错误");
            }
            if ($data['program_html'] == ""){
                $this->error("页面内容不能为空");
            }
            if(!preg_match("/^[a-zA-Z0-9]+$/", $data['program_url'])){
                $this->error("项目url只能是英文和字母的混合");
            }
            $is_have_url = M('PresetProgram')->where("id != $id and program_url = '{$data['program_url']}' ")->findAll();
            if($is_have_url){
                $this->error("项目url已经存在");
            }
            $rs = $GLOBALS['db']->autoExecute(DB_PREFIX."preset_program",$data,"UPDATE","id=".$id);
            if ($rs)
            {
                $this->success(L("修改成功"));
            } else {
                $this->error(L("修改失败"));
            }
        }else{
            $program = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."preset_program where id=".$id);
            $program['program_create_time'] = to_date($program['program_create_time']);
            //附件相关
            $attach_list = M('PresetAttachment')->where("program_id = $id")->findAll();
            if($attach_list){
                foreach($attach_list as &$val){
                    $year = to_date($val['create_time'],"Y");
                    $month = to_date($val['create_time'], "m");
                    $day = to_date($val['create_time'], "d");
                    $val['path'] = get_www_url().$GLOBALS['dict']['PRESET_PATH'].$year.'/'.$month.'/'.$day.'/'.$val['filename'];
                }
            }
            //预约地区相关
            $program_area = explode('||', $program['program_area']);
            $preset_area = dict::get('PRESET_AREA');
            $preset_area_format = array();

            if($preset_area){
                foreach($preset_area as $area){
                    $tmp_arr = array('name' => $area, 'check' => 0);
                    if(in_array($area, $program_area)){
                        $tmp_arr['check'] = 1;
                    }
                    $preset_area_format[] = $tmp_arr;
                }
            }
            $this->assign('preset_area', $preset_area_format);
            $this->assign('preview_url', get_www_url());
            $this->assign('attach', $attach_list);
            $this->assign("program", $program);
            $this->display();
        }
    }

    public function deletef(){
        $ajax = intval($_REQUEST['ajax']);
        $ids = $_REQUEST ['id'];
        $program_ids = $ids;
        if (isset ( $ids )) {
            //批量删除项目
            if(stripos($ids, ',') !== FALSE){
                $ids = explode(',', $ids);
                $i = 0;
                foreach ($ids as $id){
                    if($i==0){
                        $where = ' id="'.$id.'" ';
                    }else{
                        $where .= ' or id="'.$id.'" ';
                    }
                    $i++;
                }
            }else{
                $where = ' id="'.$ids.'" ';
            }
            //删除项目对应的附件和文件
            $attach_list = M('PresetAttachment')->where("program_id in ($program_ids)")->findAll();
            if($attach_list){
                foreach($attach_list as &$val){
                    $year = to_date($val['create_time'],"Y");
                    $month = to_date($val['create_time'], "m");
                    $day = to_date($val['create_time'], "d");
                    $file_path = APP_WEBROOT_PATH.$GLOBALS['dict']['PRESET_PATH'].$year.'/'.$month.'/'.$day.'/'.$val['filename'];
                    @unlink($file_path);
                    M("PresetAttachment")->where(array('id' => $val['id']))->delete();
                }
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if(M("PresetProgram")->where($where)->delete()){
                save_log($adm_session['adm_name']."删除".$id."预约项目成功",1);
                $this->success (l("DELETE_SUCCESS"),$ajax);
            }else{
                save_log($adm_session['adm_name']."删除".$id."预约项目失败",0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        }else {
            save_log($adm_session['adm_name']."删除".$id."预约项目失败",0);
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    /**
    * 附件列表
    * @author wenyanlei  2013-10-22
    */
    public function file_list(){
        $id = intval($_REQUEST ['id']);
        if($id <= 0){
            $this->error ('操作失败');
        }
        $voList = D ( "preset_attachment" )->where ( array('program_id' => $id) )->findAll ();
        // require_once APP_ROOT_PATH . '/libs/vfs/Vfs.php';
        if($voList){
            foreach($voList as &$val){
                $year = to_date($val['create_time'],"Y");
                $month = to_date($val['create_time'], "m");
                $day = to_date($val['create_time'], "d");
                $val['path'] = 'http:' .app_conf("STATIC_HOST") . '/' . $val['filename'];
            }
        }
        $program = M('PresetProgram')->where("id = $id")->find();
        // var_dump($program);
        $this->assign('program_id', $id);
        $this->assign ( 'program' , $program);
        $this->assign ( 'list', $voList );
        $this->display();
    }
    /**
     * 添加附件
     * @author wenyanlei  2013-10-23
     */
    public function file_add(){
        $program_id = intval($_REQUEST ['id']);
        if($program_id <= 0){
            $this->error ('操作失败!');
        }
        $create_time = get_gmtime();
        $file = $this->upload_file($_FILES['attach_file'], $create_time);
        if($file === false){
            $this->error ('操作失败');
        }
        else {
            $file['program_id'] = $program_id;
            $file['create_time'] = $create_time;
            $file['description'] = $file['code'];
            $inster_id = M("PresetAttachment")->add($file);
            $this->success('操作成功');
        }
    }
    /**
    * 删除附件
    * @author wenyanlei  2013-10-23
    */
    public function file_del(){
        $attach_ids = $_REQUEST['ids'];
        if (isset ( $attach_ids )) {
            $id_arr = explode(',', $attach_ids);
            if($id_arr){
                foreach($id_arr as $id_one){
                    $attach = M("PresetAttachment")->where(array('id' => $id_one))->find();
                    $year = to_date($attach['create_time'],"Y");
                    $month = to_date($attach['create_time'], "m");
                    $day = to_date($attach['create_time'], "d");
                    // $file_path // = APP_WEBROOT_PATH.$GLOBALS['dict']['PRESET_PATH'].$year.'/'.$month.'/'.$day.'/'.$attach['filename'];
//                     @unlink($file_path);
                    M("PresetAttachment")->where(array('id' => $id_one))->delete();
                }
                $this->success (l("DELETE_SUCCESS"));
            }
        }
        $this->error (l("INVALID_OPERATION"));
    }
    /**
    * ajax获取附件列表
    * @author wenyanlei  2013-10-23
    */
    public function refresh_file(){
        $id = intval($_REQUEST['id']);
        if($id){
            $voList = D ( "preset_attachment" )->where ( array('program_id' => $id) )->findAll ();
            // require_once APP_ROOT_PATH . '/libs/vfs/Vfs.php';
            if($voList){
                $file_str = '';
                foreach($voList as &$val){
                    $year = to_date($val['create_time'],"Y");
                    $month = to_date($val['create_time'], "m");
                    $day = to_date($val['create_time'], "d");
                    $path = 'http:' . app_conf("STATIC_HOST") . '/' . $val['filename'];
                    $file_str .=  "文件 {$val['title']} ：<a target='_blank' href='{$path}'>{$path}</a> <br />";
                }
                echo json_encode($file_str);
            }
        }
    }
    /**
     * 上传图片
     * @param 上传的图片 $file
     * @param 上传时间 $time
     */
    private function upload_file($file, $time){
        if(empty($file) || $file['error'] != 0){
            return false;
        }
        $uploadFileInfo = array(
            'file' => $file,
            'asAttachment' => 1,
            'app' => 'preset',
        );

        $result = uploadFile($uploadFileInfo);
        if(!empty($result['aid'])) {
            $attach = array();
            $attach['title'] = $result['post_data']['name'];
            $attach['filename'] = $result['full_path'];
            $attach['code'] = json_encode(array('code' => 'preset', 'aid' => $result['aid']));
            $attach['type'] = $result['post_data']['extension'];
            return $attach;
        }
        return false;
        exit;
        $year = to_date($time,"Y");
        $month = to_date($time, "m");
        $day = to_date($time, "d");
        $dir = $GLOBALS['dict']['PRESET_PATH'].$year."/".$month."/".$day."/";
        $this->mkdirs($dir); //创建层级目录
        $name = explode('.', $file['name']);
        $name_count = count($name) - 1;
        $type = $name[$name_count];
        unset($name[$name_count]);
        $title = implode('.', $name);
        $filename = md5(time().rand());
        $pic_path = $year."/".$month."/".$day."/".$filename.'.'.$type;
        //TODO vfs requirement check?
        require_once APP_ROOT_PATH.'/libs/vfs/Vfs.php';
        require_once APP_ROOT_PATH.'/libs/vfs/VfsException.php';
        try {
            libs\vfs\Vfs::write(APP_WEBROOT_PATH.$GLOBALS['dict']['PRESET_PATH'].$pic_path, $file['tmp_name']);
        } catch (VfsException $e) {
            // nothing to do reffered the origin code snap
        }
        // move_uploaded_file($file['tmp_name'], APP_WEBROOT_PATH.$GLOBALS['dict']['PRESET_PATH'].$pic_path);
        $attach = array();
        $attach['title'] = $title;
        $attach['filename'] = $filename.'.'.$type;
        $attach['type'] = $type;

        return $attach;
    }
    /**
    * 获取当前后台对应的前台域名
    * @author wenyanlei  2013-12-31
    */
    private function get_host(){
        $site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
        $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN'];
        foreach($site_list as $site){
            if(strpos($_SERVER['HTTP_HOST'], $site) !== false){
                return $site_domain[$site];
            }
        }
        return '';
    }
    /**
     * 创建层级目录
     * @param 目录 $dir
     */
    private function mkdirs($dir)
    {
        $dir_arr = explode("/", $dir);
        $dirs = APP_WEBROOT_PATH;
        $i = 1;
        foreach ($dir_arr as $path){
            if ($i > 1)
            {
                $dirs .= "/".$path;
            }else{
                $dirs .= $path;
            }
            if (!is_dir($dirs))
            {
                mk_dir($dirs, 0777);
            }
            $i++;
        }
        return true;
    }
}
?>
