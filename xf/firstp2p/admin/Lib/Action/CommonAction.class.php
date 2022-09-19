<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use libs\utils\Logger;

class CommonAction extends AuthAction
{

    /**
     * 当前action的M实例
     */
    protected $model;

    /**
     * 当前数据库表的主键字段名
     */
    protected $pk_name;

    /**
     * 当前新增编辑的主键值
     */
    protected $pk_value;

    /**
     * 系统日志显示的字段名
     *
     * 默认取‘name’字段，若没有，只显示id
     */
    protected $log_info_field = 'name';

    /**
     * 是否记录字段更新详细日志
     */
    protected $is_log_for_fields_detail = false;

    /**
     * 是否走从库，true为主库
     */
    protected $is_use_slave = false;

    /**
     * GLOBAL db属性
     */
    protected $global_db = null;

    public function __construct()
    {
        parent::__construct();
        if (defined('MODULE_NAME')) {
            if(!in_array(MODULE_NAME, array("SmsTask", "SmsTaskUser"))){
                $this->model = M(MODULE_NAME);
            }else{
                $this->model = M(MODULE_NAME, 'Model', false, "msg_box", "master");
            }
            $this->pk_name = $this->model->getPk();
        }
        if ($this->is_use_slave){
            $this->global_db = $GLOBALS['db']->get_slave();
        }else{
            $this->global_db = $GLOBALS['db'];
        }
    }

    /**
     * 列表
     *
     * 单表的列表查询，支持表字段值的检索和排序，支持翻页
     */
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search();
        //追加默认参数
        if ($this->get("default_map")) {
            //$map = array_merge($map, $this->get("default_map"));
            $map = array_merge($this->get("default_map"), $map); // 搜索框的值覆盖默认值
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        //$name = $this->getActionName();
        //$model = D($name);
        if (!empty ($this->model)) {
            $this->_list($this->model, $map);
        }
        $this->display();
        return;
    }

    /**
     * 编辑显示
     *
     * 用于单表的编辑页面的显示赋值，请求参数为：主键名=主键值
     */
    public function edit()
    {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 编辑更新
     *
     * 单表的表单提交更新操作。字段的校验和自动赋值操作，可通过对应Model类的自动校验和自动完成实现
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
            $form->__set('update_time',get_gmtime());
        }

        if (!$data) {
            $this->error($form->getError());
        }

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
            //$this->assign("jumpUrl", u(MODULE_NAME . "/index"));
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1, $old_data, $new_data);
            $this->display_success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            //$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }

    /**
     * 新增
     *
     * 单表的表单提交新增操作。字段的校验和自动赋值操作，可通过对应Model类的自动校验和自动完成实现
     */
    public function insert()
    {
        B('FilterString');
        $form = D(MODULE_NAME);

        // 字段校验
        $data = $form->create();
        $fields = $form->getDbFields();
        //设置create_time
        if(in_array('create_time',$fields)){
            $form->__set('create_time',get_gmtime());
        }
        if (!$data) {
            $this->error($form->getError());
        }

        //$this->assign("jumpUrl", u(MODULE_NAME . "/index"));
        $result = $form->add(); // 保存
        $this->pk_value = $form->getLastInsID();

        //日志信息
        $log_info = "[" . $form->getLastInsID() . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";
        $new_data = '';
        if ($this->is_log_for_fields_detail) { // 记录字段更新详细日志
            $new_data = $data;
        }

        if ($result !== false) {
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1, '', $new_data);
            $this->display_success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0);
        }
    }

    /**
     * 改变有效字段is_effect
     *
     * 暂时只开通单个id的设置，只需传主键即可，自动把is_effect进行0/1反值设置
     */
    public function set_effect()
    {
        $ajax = $_REQUEST['ajax'];
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
        $condition = array($this->pk_name => $id);

        $old_is_effect = $this->model->where($condition)->getField("is_effect"); //当前状态
        $new_is_effect = $old_is_effect == 0 ? 1 : 0; //需设置的状态

        $msg['success'] = l("SET_EFFECT_" . $new_is_effect);
        $msg['fail'] = l("LOG_STATUS_0");

        $log_info = $this->get_log_info($condition);

        $rs = $this->model->where($condition)->setField("is_effect", $new_is_effect);
        if ($rs !== false) {
            save_log($log_info . $msg['success'], 1);
            $this->ajaxReturn($new_is_effect, $msg['success'], 1); //得ajaxReturn，不能调set_field_by_id
        } else {
            save_log($log_info . $msg['fail'], 0);
            $this->error($msg['fail'], $ajax);
        }
    }

    /**
     * 批量改变有效字段is_effect
     */
    public function set_effect_all($condition, $is_effect) {
        $ajax = $_REQUEST['ajax'];
        if (empty($condition) || !in_array($is_effect, array('0', '1'))) {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }

        $msg['success'] = l("SET_EFFECT_" . $is_effect);
        $msg['fail'] = l("LOG_STATUS_0");
        $log_info = $this->get_log_info($condition);

        $rs = $this->model->where($condition)->setField("is_effect", $is_effect);
        if ($rs !== false) {
            save_log($log_info . $msg['success'], 1);
            $this->display_success($msg['success']);
        } else {
            save_log($log_info . $msg['fail'], 0);
            $this->error($msg['fail'], $ajax);
        }
    }

    /**
     * 假删除，设置删除字段is_delete=1
     *
     * request参数的字段名为数据库主键名，参数值可以为主键字符串，以,隔开
     */
    public function delete()
    {
        $field_list['is_delete'] = 1;
        $msg['success'] = l("DELETE_SUCCESS");
        $msg['fail'] = l("DELETE_FAILED");
        $this->set_field_by_id($field_list, $msg);
    }

    /**
     * 恢复假删除，设置删除字段is_delete=0
     *
     * request参数的字段名为数据库主键名，参数值可以为主键字符串，以,隔开
     */
    public function restore()
    {
        $field_list['is_delete'] = 0;
        $msg['success'] = l("RESTORE_SUCCESS");
        $msg['fail'] = l("RESTORE_FAILED");
        $this->set_field_by_id($field_list, $msg);
    }

    /**
     * 彻底删除指定记录
     *
     * 子类调用前根据情况检查依赖关系，确认是否能删除
     * request参数的字段名为数据库主键名，参数值可以为主键字符串，以,隔开
     */
    public function foreverdelete()
    {
        $ajax = intval($_REQUEST['ajax']);
        $condition = array($this->pk_name => array('in', $this->get_id_list()));
        $log_info = $this->get_log_info($condition);

        $rs = $this->model->where($condition)->delete();
        if ($rs !== false) {
            save_log($log_info . l("FOREVER_DELETE_SUCCESS"), 1);
            $this->display_success(l("FOREVER_DELETE_SUCCESS"), $ajax);
        } else {
            save_log($log_info . l("FOREVER_DELETE_FAILED"), 0);
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }
    }

    /**
     * 根据主键更新记录的字段值
     *
     * 提供给action方法使用，可更新is_effect, is_delete，status等常用字段
     * request参数的字段名为数据库主键名，参数值可以为主键字符串，以,隔开
     *
     * @param $field_list 字段数据，key为字段名，value为字段新值，可多个字段
     * @param $msg 消息列表，'success'=>成功消息, 'fail'=>失败消息
     */
    protected function set_field_by_id($field_list, $msg = array())
    {
        // 检查参数
        $ajax = intval($_REQUEST['ajax']);
        if (empty($field_list)) {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
        if (empty($msg)) {
            $msg['success'] = l("LOG_STATUS_1");
            $msg['fail'] = l("LOG_STATUS_0");
        }

        $condition = array($this->pk_name => array('in', $this->get_id_list()));

        //日志信息
        $log_info = $this->get_log_info($condition);
        $new_data = '';
        if ($this->is_log_for_fields_detail) { // 记录字段更新详细日志
            $new_data = $field_list;
        }

        // 更新操作
        foreach ($field_list as $k => $v) { // M->setField的变态调用方式。。。
            $fields[] = $k;
            $values[] = $v;
        }
        $rs = $this->model->where($condition)->setField($fields, $values);
        if ($rs !== false) {
            save_log($log_info . $msg['success'], 1, '', $new_data);
            $this->display_success($msg['success'], $ajax);
        } else {
            save_log($log_info . $msg['fail'], 0);
            $this->error($msg['fail'], $ajax);
        }
    }

    /**
     * 获取多选id列表的where过滤条件
     *
     * request参数的字段名为数据库主键名，参数值可以为主键字符串，以,隔开
     *
     * @return string id数组
     */
    protected function get_id_list()
    {
        // 检查参数
        $ajax = intval($_REQUEST['ajax']);

        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
        return explode(',', $id);
    }

    /**
     * 生成log日志记录名称信息
     *
     * 默认取name字段，如果没有则取主键值。子类可以根据实际情况覆盖，添加特有日志信息
     *
     * @param array $condition where条件id数组
     *
     * @return bool|string 记录名称信息
     */
    protected function get_log_info($condition)
    {
        $data_list = $this->model->where($condition)->findAll();
        if (empty($data_list)) {
            return false;
        }

        $log_info = array();
        foreach ($data_list as $data) {
            $log_msg = "[" . $data[$this->pk_name] . "]";
            if (isset($data[$this->log_info_field])) {
                $log_msg .= $data[$this->log_info_field];
            }
            $log_info[] = $log_msg;
        }
        return implode(",", $log_info) . "|";
    }

    /**
    +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
    +----------------------------------------------------------
     * @access protected
    +----------------------------------------------------------
     * @param string $name 数据对象名称
    +----------------------------------------------------------
     * @return HashMap
    +----------------------------------------------------------
     * @throws ThinkExecption
    +----------------------------------------------------------
     */
    protected function _search($name = '')
    {
        //生成查询条件
//        if (empty ($name)) {
//            $name = $this->getActionName();
//        }
//        $name = $this->getActionName();
//        $model = D($name);
        $map = array();
        foreach ($this->model->getDbFields() as $key => $val) {
            if (isset ($_REQUEST [$val]) && $_REQUEST [$val] != '') {
                $map [$val] = $_REQUEST [$val];
            }
        }
        return $map;

    }

    const LIST_WITHOUT_PAGE_MAX = 1000000;

    protected $pageEnable = true;

    protected function _setPageEnable($pageEnable)
    {
        $this->pageEnable = $pageEnable;
    }

    /**
    +----------------------------------------------------------
     * 根据表单生成查询条件
     * 进行列表过滤
    +----------------------------------------------------------
     * @access protected
    +----------------------------------------------------------
     * @param Model $model 数据对象
     * @param HashMap $map 过滤条件
     * @param string $sortBy 排序
     * @param boolean $asc 是否正序
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     * @throws ThinkExecption
    +----------------------------------------------------------
     */
    protected function _list($model, $map, $sortBy = '', $asc = false, $formIndexList = true, $total = false)
    {
        if (isset($_REQUEST['_page'])) {
            $this->pageEnable = $_REQUEST['_page'] ? true : false;
        }

        //$_connectionType = $model->isSlave ? 'slave' : 'master';
        //排序字段 默认为主键名
        if (isset ($_REQUEST ['_order'])) {
            $order = $_REQUEST ['_order'];
        } else {
            $order = !empty ($sortBy) ? $sortBy : $this->pk_name;
        }
        //排序方式默认按照倒序排列
        //接受 sost参数 0 表示倒序 非0都 表示正序
        if (isset ($_REQUEST ['_sort'])) {
            $sort = $_REQUEST ['_sort'] ? 'asc' : 'desc';
        } else {
            $sort = $asc ? 'asc' : 'desc';
        }

        if ($total === false) {
            //取得满足条件的记录数
            if($model instanceof \core\dao\BaseNoSQLModel){
                $time_cost_1 = microtime(true);
                $count = empty($map) ? 0 : $model->count($map);
                $time_cost_2 = microtime(true);
                $time_cost = number_format($time_cost_2 - $time_cost_1, 2);
                save_log('time_cost count:' . $time_cost, 1, false, false, false);
            } elseif ($this->pageEnable === false) {
                $count = self::LIST_WITHOUT_PAGE_MAX;
            } else {
                $count = $model->where($map)->count('id');
            }
        } else {
            $count = $total;
        }

        if ($count > 0) {
            //创建分页对象
            if (!empty ($_REQUEST ['listRows'])) {
                $listRows = $_REQUEST ['listRows'];
            } else {
                if ($_REQUEST['a'] == 'export_csv') {
                    $listRows = $count;
                } else {
                    $listRows = '';
                }
            }
            $p = new Page ($count, $listRows);
            //分页查询数据
            if ($model instanceof \core\dao\BaseNoSQLModel) {
                if (empty($map)) {
                    $voList = array();
                } else {
                    $mongoOrder = array('asc' => 1,'desc' => -1);
                    $order_param = isset ($_REQUEST ['_order']) ? array($order => $mongoOrder[$sort]) : array();
                    $voList = $model->find($map, $order_param, array(), $p->listRows, $p->firstRow);
                    $time_cost_3 = microtime(true);
                    $time_cost = number_format($time_cost_3 - $time_cost_2, 2);
                    save_log('time_cost find:' . $time_cost, 1, false, false, false);
                    $voList = $voList->toArray();
                }
            } else {
                $voList = $model->where($map)->order("`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->findAll();
            }

            if ($formIndexList) {
                $this->form_index_list($voList);
            }

            //分页跳转的时候保证查询条件
            foreach ($map as $key => $val) {
                if (!is_array($val)) {
                    if($key<>'_string'){
                        $p->parameter .= "$key=" . urlencode($val) . "&";
                    }
                }
            }

            //分页显示
            $page = $p->show($this->pageEnable, count($voList));
            //列表排序显示
            $sortImg = $sort; //排序图标
            $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
            $sort = $sort == 'desc' ? 1 : 0; //排序方式
            //模板赋值显示
            $this->assign('list', $voList);
            $this->assign('sort', $sort);
            $this->assign('order', $order);
            $this->assign('sortImg', $sortImg);
            $this->assign('sortType', $sortAlt);
            $this->assign("page", $page);
            $this->assign("firstRow", $p->firstRow);
            $this->assign("nowPage", $p->nowPage);
            $this->assign("totalPages", $p->totalPages);
            $this->assign("totalRows", $p->totalRows);
            $this->assign("pageSize", $p->listRows);
        }
        return $voList;
    }

    /**
     * 列表数据的后续处理
     */
    protected function form_index_list(&$list){

    }

    protected function display_success($message, $ajax = 0) {
            $default_jump = !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : u("Index/main");
            $this->assign("jumpUrl", $default_jump);

        $this->_dispatch_jump($message, 1, $ajax);
    }

    /**
     * 上传图片的通公基础方法
     *
     * @return array
     */
    protected function uploadImage()
    {
        if (conf("WATER_MARK") != "")
            $water_mark = get_real_path() . conf("WATER_MARK"); //水印
        else
            $water_mark = "";
        $alpha = conf("WATER_ALPHA"); //水印透明
        $place = conf("WATER_POSITION"); //水印位置

        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = conf('MAX_IMAGE_SIZE'); /* 配置于config */
        //设置上传文件类型

        $upload->allowExts = explode(',', conf('ALLOW_IMAGE_EXT')); /* 配置于config */

        $dir_name = to_date(get_gmtime(), "Ym");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "d");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "H");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }


        $save_rec_Path = "/public/attachment/" . $dir_name . "/origin/"; //上传时先存放原图
        $savePath = APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/"; //绝对路径
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/")) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/");
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name . "/origin/", 0777);
        }
        $domain_path = get_domain() . APP_ROOT . $save_rec_Path;

        $upload->saveRule = "uniqid"; //唯一
        $upload->savePath = $savePath;
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            foreach ($uploadList as $k => $fileItem) {
                $file_name = $fileItem['savepath'] . $fileItem['savename']; //上图原图的地址
                //水印图
                $big_save_path = str_replace("origin/", "", $savePath); //大图存放图径
                $big_file_name = str_replace("origin/", "", $file_name);

//                    Image::thumb($file_name,$big_file_name,'',$big_width,$big_height);
                @file_put_contents($big_file_name, @file_get_contents($file_name));
                if (file_exists($water_mark)) {
                    Image::water($big_file_name, $water_mark, $big_file_name, $alpha, $place);
                }
                $big_save_rec_Path = str_replace("origin/", "", $save_rec_Path); //上传的图存放的相对路径
                $uploadList[$k]['recpath'] = $save_rec_Path;
                $uploadList[$k]['bigrecpath'] = $big_save_rec_Path;
                if (app_conf("PUBLIC_DOMAIN_ROOT") != '') {
                    $origin_syn_url = app_conf("PUBLIC_DOMAIN_ROOT") . "/es_file.php?username=" . app_conf("IMAGE_USERNAME") . "&password=" . app_conf("IMAGE_PASSWORD") . "&file=" . get_domain() . APP_ROOT . "/attachment/" . $dir_name . "/origin/" . $fileItem['savename'] . "&path=attachment/" . $dir_name . "/origin/&name=" . $fileItem['savename'] . "&act=0";
                    $big_syn_url = app_conf("PUBLIC_DOMAIN_ROOT") . "/es_file.php?username=" . app_conf("IMAGE_USERNAME") . "&password=" . app_conf("IMAGE_PASSWORD") . "&file=" . get_domain() . APP_ROOT . "/attachment/" . $dir_name . "/" . $fileItem['savename'] . "&path=attachment/" . $dir_name . "/&name=" . $fileItem['savename'] . "&act=0";
                    @file_get_contents($origin_syn_url);
                    @file_get_contents($big_syn_url);
                }
            }
            return array("status" => 1, 'data' => $uploadList, 'info' => L("UPLOAD_SUCCESS"));
        } else {
            return array("status" => 0, 'data' => null, 'info' => $upload->getErrorMsg());
        }
    }


    /**
     * 上传文件公共基础方法
     *
     * @return array
     */
    protected function uploadFile()
    {
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = conf('MAX_IMAGE_SIZE'); /* 配置于config */
        //设置上传文件类型

        $upload->allowExts = explode(',', conf('ALLOW_IMAGE_EXT')); /* 配置于config */

        $dir_name = to_date(get_gmtime(), "Ym");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "d");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }

        $dir_name = $dir_name . "/" . to_date(get_gmtime(), "H");
        if (!is_dir(APP_ROOT_PATH . "public/attachment/" . $dir_name)) {
            @mkdir(APP_ROOT_PATH . "public/attachment/" . $dir_name);
            @chmod(APP_ROOT_PATH . "public/attachment/" . $dir_name, 0777);
        }


        $save_rec_Path = "/attachment/" . $dir_name . "/"; //上传时先存放原图
        $savePath = APP_ROOT_PATH . "public/attachment/" . $dir_name . "/"; //绝对路径
        $domain_path = get_domain() . APP_ROOT . $save_rec_Path;


        $upload->saveRule = "uniqid"; //唯一
        $upload->savePath = $savePath;
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            foreach ($uploadList as $k => $fileItem) {
                $uploadList[$k]['recpath'] = $save_rec_Path;
                if (app_conf("PUBLIC_DOMAIN_ROOT") != '') {
                    $syn_url = app_conf("PUBLIC_DOMAIN_ROOT") . "/es_file.php?username=" . app_conf("IMAGE_USERNAME") . "&password=" . app_conf("IMAGE_PASSWORD") . "&file=" . $domain_path . $fileItem['savename'] . "&path=attachment/" . $dir_name . "/&name=" . $fileItem['savename'] . "&act=0";
                    @file_get_contents($syn_url);
                }
            }
            return array("status" => 1, 'data' => $uploadList, 'info' => L("UPLOAD_SUCCESS"));
        } else {
            return array("status" => 0, 'data' => null, 'info' => $upload->getErrorMsg());
        }
    }

    /*
     * 改方法加到父类不合适 by liangqiang 20140114
     *
    public function _before_update()
    {
        $uname = $_REQUEST['uname'];
        if ($uname && trim($uname) != '') {
            $rs = M(MODULE_NAME)->where("uname='" . $uname . "' and id <> " . intval($_REQUEST['id']))->count();
            if ($rs > 0) {
                $this->error(l("UNAME_EXISTS"));
            }
        }
    }

    public function _before_insert()
    {
        $uname = $_REQUEST['uname'];
        if ($uname && trim($uname) != '') {
            $rs = M(MODULE_NAME)->where("uname='" . $uname . "' and id <> " . intval($_REQUEST['id']))->count();
            if ($rs > 0) {
                $this->error(l("UNAME_EXISTS"));
            }
        }
    }*/


    public function toogle_status()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $field = $_REQUEST['field'];
        $info = $id . "_" . $field;
        $c_is_effect = M(MODULE_NAME)->where("id=" . $id)->getField($field); //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=" . $id)->setField($field, $n_is_effect);
        save_log($info . l("SET_EFFECT_" . $n_is_effect), 1);
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), 1);
    }

    /**
     * 获取当前是ab角色中谁在访问,默认是a角色
     */
    public function getRole() {
        return isset($_GET['role']) && trim($_GET['role']) == 'b' ? 'b' : 'a';
    }

    /**
     * 在ab角色中，删除业务数据和任务数据
     * 所带参数 t 为业务类型ID，例如 SERVICE_TYPE_BONUS
     */
    public function physicsDelete() {
        $ajax = intval($_REQUEST['ajax']);
        $condition = array($this->pk_name => array('in', $this->get_id_list()));
        $log_info = $this->get_log_info($condition);

        $this->model->startTrans();
        $rs = $this->model->where($condition)->delete();
        if ($rs === false) {
            save_log($log_info . l("FOREVER_DELETE_FAILED"), 0);
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }

        $conds = array(
            'service_type' => $this->getServiceType(),
            'service_id'   => array('in', $this->get_id_list()),
         );

        $rs = M('ServiceAudit')->where($conds)->delete();
        if ($rs === false) {
            $this->model->rollback();
            save_log($log_info . l("FOREVER_DELETE_FAILED"), 0);
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }

        $this->model->commit();
        save_log($log_info . l("FOREVER_DELETE_SUCCESS"), 1);
        $this->display_success(l("FOREVER_DELETE_SUCCESS"), $ajax);
    }

    /**
     * ab角色中，分页查询任务数据, 并展示业务数据列表
     *
     * @return array 返回查询任务数据的结果
     */
    public function queryServiceAudit() {
        $serviceAuditModel = D('ServiceAudit');
        $this->_list($serviceAuditModel, $this->getWhereStmt());

        $taskList = array();
        foreach ($this->get('list') as $auditTask) {
            $taskList[$auditTask['service_id']] = $auditTask;
        }

        $dataList = $this->queryServiceData($taskList);

        $this->assign('all_audit_status', ServiceAuditModel::$auditStatus);
        $this->assign('task_list', $taskList);
        $this->assign('data_list', $dataList);

        return array('task_list' => $taskList, 'data_list' => $dataList);
    }

    /**
     * ab角中，查询具体的业务数据
     *
     * @return array 返回查询业务数据的结果
     */
    public function queryServiceData($data) {
        $dataList = array();

        $serviceIds = array_keys($data);
        if (!empty($serviceIds)) {
            $list = M(MODULE_NAME)->where(array('id' => array('IN', $serviceIds)))->order('id DESC')->findAll();
            foreach ($list as $item) {
                $dataList[$item['id']] = $item;
            }
        }

        return $dataList;
    }

    /**
     * 完成AB审核的审核动作,并执行用户回调
     */
    public function doServiceAudit() {
        $param = $this->getAuditParam();

        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => $this->getServiceType(),'service_id' => $param['service_id']);
        $task  = $serviceAuditModel->queryTaskByServiceId($conds);
        if ($task['status'] != ServiceAuditModel::NOT_AUDIT) {
            $this->error("数据非待审状态!");
        }

        if ($param['is_pass']) {
            $task['status'] = ServiceAuditModel::AUDIT_SUCC;
        } else {
            $task['status'] = ServiceAuditModel::AUDIT_FAIL;
            if (empty($param['reason'])) {
                $this->error("请填写退回原因!");
            }
            $task = $serviceAuditModel->addLastAuditError($task, $param['reason']);
        }

        $serviceAuditModel->startTrans();
        if (!$serviceAuditModel->save($task)) {
            $this->error(L("UPDATE_FAILED"));
        }

        if (is_callable($param['callback']) && false === call_user_func_array($param['callback'], array($task))) {
            $serviceAuditModel->rollback();
            $this->error(L("UPDATE_FAILED"));
        }

        $serviceAuditModel->commit();
        save_log("AB角审核业务", $param['is_pass'], '', $task);

        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => 'b')));
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function getRpc($rpcName) {

        if(!isset($GLOBALS[$rpcName])) {
            \libs\utils\PhalconRPCInject::init();
        }
        return $GLOBALS[$rpcName];
    }

}

