<?php

class ConfAction extends CommonAction {

    protected $is_log_for_fields_detail = true;

    private static $site_list;

    private static $site_id_list = array(0, 1, 2, 3, 4);

    private static $input_type_list = array(
        '0' => '文本输入',
        '1' => '下拉框输入',
        '5' => '日期时间',
//        '2' => '图片上传',
//        '3' => '编辑器',
    );

    public function __construct() {
        self::$site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
        self::$site_list['0'] = '公共配置';
        ksort(self::$site_list);
        parent::__construct();
    }

    /**
     * 设置最后更新时间
     */
    public function setLastUpdateTime()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            $this->error('Redis连接异常');
        }

        $redis->set('conf_last_update_time', time());
        $this->success('更新成功');
    }

    public function index_common() {
        $sql_where = "is_effect = 1 and is_conf = 1 and site_id = 0 and group_id > 0";
        $conf_res = M("Conf")->where($sql_where)->order("group_id asc,sort asc")->findAll();
        $this->_form_conf_list($conf_res);
        foreach ($conf_res as $k => $v) {
            $conf[$v['group_id']][] = $v;
        }
        $this->assign("conf", $conf);
        $this->display();
    }

    public function set_conf() {
        $condition['is_effect'] = 1;
        $condition['is_conf'] = 1;
        $site_id = isset($_REQUEST['site_id']) ? intval($_REQUEST['site_id']) : 0;
        $condition['site_id'] = $site_id;
        $conf_res = M("Conf")->where($condition)->findAll();
        foreach ($conf_res as $k => $v) {
            $input_name = $site_id . "_" . $v['name'];
            conf($v['name'], $_REQUEST[$input_name], $site_id);

            //更新缓存(后续开启)
            if (false) {
                //if ($v['name'] == 'URL_MODEL' && $v['value'] != $_REQUEST[$v['name']]) {
                clear_auto_cache("byouhui_filter_nav_cache");
                clear_auto_cache("cache_shop_acate_tree");
                clear_auto_cache("cache_shop_cate_tree");
                clear_auto_cache("cache_youhui_cate_tree");
                clear_auto_cache("city_list_result");
                clear_auto_cache("fyouhui_filter_nav_cache");
                clear_auto_cache("get_help_cache");
                clear_auto_cache("page_image");
                clear_auto_cache("tuan_filter_nav_cache");
                clear_auto_cache("youhui_page_recommend_youhui_list");
                clear_auto_cache("ytuan_filter_nav_cache");
                clear_auto_cache("store_filter_nav_cache");
                clear_dir_file(APP_RUNTIME_PATH . "app/data_caches/");
                clear_dir_file(APP_RUNTIME_PATH . "app/tpl_caches/");
                clear_dir_file(APP_RUNTIME_PATH . "app/tpl_compiled/");

                clear_dir_file(APP_RUNTIME_PATH . "app/data_caches/");
                clear_dir_file(APP_RUNTIME_PATH . "data/page_static_cache/");
                clear_dir_file(APP_RUNTIME_PATH . "data/dynamic_avatar_cache/");
            }
        }

        $this->_clear_cache($site_id);

        // 写配置文件，弃用
        if (false) {
            //开始写入配置文件
            $sys_configs = M("Conf")->findAll();
            $config_str = "<?php\n";
            $config_str .= "return array(\n";
            foreach ($sys_configs as $k => $v) {
                $config_str .= "'" . $v['name'] . "'=>'" . addslashes($v['value']) . "',\n";
            }
            $config_str .= ");\n ?>";
            $filename = APP_ROOT_PATH . "conf/sys_config.php";

            if (!$handle = fopen($filename, 'w')) {
                $this->error(l("OPEN_FILE_ERROR") . $filename);
            }


            if (TRUE === FALSE) {
                $this->error(l("WRITE_FILE_ERROR") . $filename);
            }

            fclose($handle);
        }

        save_log(l("CONF_UPDATED"), 1);
        //clear_cache();
        //write_timezone();
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function index() {
        $siteId = isset($_REQUEST['site_id']) ? intval($_REQUEST['site_id']) : '0';
        $title = isset($_REQUEST['title']) ? addslashes(trim($_REQUEST['title'])) : '';

        $model = M(MODULE_NAME);
        //$condition = $this->_search();

        $condition = "site_id='{$siteId}'";
        if ($title !== '') {
            $condition .= "AND (name LIKE '%{$title}%' OR title LIKE '%{$title}%')";
        }

        $_REQUEST['_sort'] = 1;
        $_REQUEST['listRows'] = 1000;

        if (!empty ($model)) {
            $this->_list($model, $condition);
        }
        $list = $this->get('list');

        foreach ($list as $k => $item) {
            $list[$k]['site_id'] = self::$site_list[$item['site_id']];
            $list[$k]['input_type'] = self::$input_type_list[$item['input_type']];
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            $lastUpdateTime = $redis->get('conf_last_update_time');
        }

        $this->assign('list', $list);
        $this->assign('lastUpdateTime', $lastUpdateTime);
        $this->assign("main_title", L(MODULE_NAME . "_INDEX"));
        $this->assign('site_list', self::$site_list);
        $this->display();
    }

    /**
     * 分站配置信息列表
     */
    public function index_site() {
        if (app_conf('ENV_FLAG') == 'online') {
            exit('暂时关闭，请使用系统配置单个配置');
        }
        // 参数设置
        $_REQUEST ['_sort'] = 1;
        $_REQUEST ['listRows'] = 10000;
        $condition['is_effect'] = 1;
        $condition['is_conf'] = 1;
        $condition['site_id'] = array('egt', 0);

        $model = M(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model, $condition, 'name');
        }
        $conf_list = $this->get('list');
        //$this->_form_conf_list($conf_list);
        $list = array_fill_keys($GLOBALS['sys_config']['TEMPLATE_LIST'],array());
        $list['0'] = array();
        foreach ($conf_list as $k => $v) {
            if (!empty($v['value_scope'])) {
                $v['value_scope'] = $this->_form_value_scope_array($v['value_scope'], $v['name']);
            }
            $list[$v['site_id']][] = $v;
        }
        ksort($list);
        $tab_list = array();
        foreach (self::$site_list as $k => $item) {
            $tab_list[$k] = self::$site_list[$k];
        }
        $this->assign('tab_list', $tab_list);
        $this->assign('list', $list);
        $this->assign("main_title", L(MODULE_NAME . "_INDEX"));
        $this->display();
    }

    public function reset_site(){
        $site = $_REQUEST['site'];
        if(isset($GLOBALS['sys_config']['TEMPLATE_LIST'][$site])){
            $site_id = intval($GLOBALS['sys_config']['TEMPLATE_LIST'][$site]);
            $model = M(MODULE_NAME);
            //删除当前分站所有配置
            $model->where(array('site_id'=>$site_id))->delete();
            //复制主站配置
            $sql = 'INSERT INTO __TABLE__ (title,name,value,site_id,group_id,input_type,value_scope,is_effect,is_conf,sort,tip) '
                .' SELECT title,name,value,"'.$site_id.'" as site_id,group_id,input_type,value_scope,is_effect,is_conf,sort,tip '
                .'FROM __TABLE__ where site_id=1';
            $ret = $model->execute($sql);
            if($ret !== false){
                save_log('更新分站【'.$site.'】配置 ' . L("INSERT_SUCCESS"), 0);
                $this->success(L("INSERT_SUCCESS")." 更新了{$ret}条数据.");
            }else{
                save_log('更新分站【'.$site.'】配置 ' . L("INSERT_SUCCESS"), 0);
                $this->error(L("INSERT_FAILED"), 0);
            }
        }else{
            die('param error');
        }
    }

    public function add() {
        $site_ids = implode(",", $GLOBALS['sys_config']['TEMPLATE_LIST']);
        $this->assign("site_ids", $site_ids);
        $this->display();
    }

    public function edit() {
        $this->assign('input_type_list', self::$input_type_list);
        //$this->assign('site_list', array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']));
        parent::edit();
    }

    public function insert() {
        $form = D(MODULE_NAME);

        // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        $site_id_list = explode(',', $_REQUEST['site_id_list']);
        foreach ($site_id_list as $site_id) {
            $data['site_id'] = $site_id;
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
                save_log($log_info . L("INSERT_SUCCESS"), 1);
            } else {
                //错误提示
                save_log($log_info . L("INSERT_FAILED"), 0);
                $this->error(L("INSERT_FAILED"), 0);
            }
        }

        $this->_clear_cache();
        $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
        $this->success(L("INSERT_SUCCESS"));

    }

    public function _after_update() {
        $this->_clear_cache();
    }

    private function _clear_cache($site_id) {
        $confCacheKey = $GLOBALS['sys_config']['FIRSTP2P_CONF_DATACACHE'];
        $result = SiteApp::init()->dataCache->getRedisInstance()->del($confCacheKey);
        $site_id_list = empty($site_id) ? self::$site_id_list : array($site_id);
        foreach ($site_id_list as $item) {
            $cache_key = "conf_site_" . $item;
            SiteApp::init()->cache->delete($cache_key);
        }
    }

    /**
     * 处理数据库conf数据，TEMPLATE，SHOP_LANG类型进行数据完善
     *
     * @param $conf_list
     * @return mixed
     */
    private function _form_conf_list(&$conf_list) {
        foreach ($conf_list as $k => $v) {
            $v['value'] = htmlspecialchars($v['value']);
            if (strpos($v['value'], './public/attachment/') !== false) {
                $v['value'] = str_replace('./public/attachment/', './attachment/', $v['value']);
            }
            if ($v['name'] == 'TEMPLATE') {

                //输出现有模板文件夹
                $directory = APP_ROOT_PATH . "app/Tpl/";
                $dir = @opendir($directory);
                $tmpls = array();

                while (false !== ($file = @readdir($dir))) {
                    if ($file != '.' && $file != '..')
                        $tmpls[] = $file;
                }
                @closedir($dir);
                //end

                $v['input_type'] = 1;
                $v['value_scope'] = $tmpls;
            } elseif ($v['name'] == 'SHOP_LANG') {
                //输出现有语言包文件夹
                $directory = APP_ROOT_PATH . "app/Lang/";
                $dir = @opendir($directory);
                $tmpls = array();

                while (false !== ($file = @readdir($dir))) {
                    if ($file != '.' && $file != '..')
                        $tmpls[] = $file;
                }
                @closedir($dir);
                //end

                $v['input_type'] = 1;
                $v['value_scope'] = $tmpls;
            } else {
                $v['value_scope'] = explode(",", $v['value_scope']);
            }
            $conf_list[$k] = $v;
        }
        return $conf_list;
    }


    /**
     * 把value_scope字符串转化成数组
     *
     * @return array|bool
     */
    private function _form_value_scope_array($value_scope, $conf_name = '') {
        if (empty($value_scope)) {
            return false;
        }
        $value_scope = explode(',', $value_scope);
        if (empty($value_scope)) {
            return false;
        }
        $result = array();
        foreach ($value_scope as $item) {
            $kv = explode(':', $item);
            if (is_array($kv) && count($kv) >= 2) {
                $result[$kv[0]] = $kv[1];
            } else {
                if (!empty($conf_name)) {
                    $l_key = "CONF_" . $conf_name . "_" . $item;
                    $item = (L($l_key) == $l_key) ? $item : L($l_key);
                }
                $result[$item] = $item;
            }
        }
        return $result;
    }


    public function mobile() {
        $config = M("MConfig")->findAll();
        $this->assign("config", $config);
        $this->display();
    }

    public function savemobile() {
        foreach ($_POST as $k => $v) {
            M("MConfig")->where("code='" . $k . "'")->setField("val", $v);
        }
        $this->success("保存成功");
    }

    public function insertnews() {
        //B('FilterString');
        $name = "MConfigList";
        $model = D($name);
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
        $data['is_verify'] = 1;
        $data['group'] = 4;
        //保存当前数据对象
        $list = $model->add($data);
        if ($list !== false) { //保存成功
            //$this->saveLog(1,$list);
            $this->success(L('INSERT_SUCCESS'));
        } else {
            //失败提示
            //$this->saveLog(0,$list);
            $this->error(L('INSERT_FAILED'));
        }
    }

    /*    function edit() {
            $name = "MConfigList";
            $model = D($name);

            $id = $_REQUEST [$model->getPk()];
            $vo = $model->getById($id);
            $this->assign('vo', $vo);
            $this->display();
        }*/

    public function news() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search();
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $map['group'] = 4;
        $name = $this->getActionName();
        $model = D("MConfigList");
        if (!empty ($model)) {
            $this->_list($model, $map);
        }
        $this->display();
        return;
    }

    function updatenews() {
        //B('FilterString');
        $name = "MConfigList";
        $model = D($name);
        if (false === $data = $model->create()) {
            $this->error($model->getError());
        }
        // 更新数据
        $list = $model->save($data);
        $id = $data[$model->getPk()];
        if (false !== $list) {
            //成功提示
            //$this->saveLog(1,$id);
            $this->success(L('UPDATE_SUCCESS'));
        } else {
            //错误提示
            //$this->saveLog(0,$id);
            $this->error(L('UPDATE_FAILED'));
        }
    }

    /*    public function foreverdelete() {
            //删除指定记录
            $result = array('isErr' => 0, 'content' => '');
            $id = $_REQUEST['id'];
            if (!empty($id)) {
                $name = "MConfigList";
                $model = D($name);
                $pk = $model->getPk();
                $condition = array($pk => array('in', explode(',', $id)));
                if (false !== $model->where($condition)->delete()) {
                    //$this->saveLog(1,$id);
                } else {
                    //$this->saveLog(0,$id);
                    $result['isErr'] = 1;
                    $result['content'] = L('FOREVER_DELETE_SUCCESS');
                }
            } else {
                $result['isErr'] = 1;
                $result['content'] = L('FOREVER_DELETE_FAILED');
            }

            die(json_encode($result));
        }*/


    public function toogle_status() {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $field = $_REQUEST['field'];
        $info = $id . "_" . $field;
        $c_is_effect = M("MConfigList")->where("id=" . $id)->getField($field); //当前状态

        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M("MConfigList")->where("id=" . $id)->setField($field, $n_is_effect);

        save_log($info . l("SET_EFFECT_" . $n_is_effect), 1);
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), 1);
    }

}

?>
