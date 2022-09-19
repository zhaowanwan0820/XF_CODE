<?php

/**
 * ApiConfAction.class.php
 *
 * Filename: ApiConfAction.class.php
 * Descrition: api配置
 * Author: yutao@ucfgroup.com
 * Date: 15-10-28 下午3:32
 */
class ApiConfAction extends CommonAction {

    private static $site_list;
    public function __construct() {
        self::$site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
        parent::__construct();
    }
    public function index() {
        //获取当前所属的站点的conf_type和site_id
        if (isset($_REQUEST['conf_type']) && $_REQUEST['site_id']) {
            $site_id_key = intval($_REQUEST['site_id']);
            $conf_type_key = $_REQUEST['conf_type'];
        } else {
            $site_id_key = '0';
            $conf_type_key = '1';
        }
        $model = M(MODULE_NAME);
        $_REQUEST['site_id'] = $site_id_key;
        $_REQUEST['conf_type'] = $conf_type_key;
        $condition = $this->_search();
        $condition['is_delete'] = 0;
        if (!empty($model)) {
            $this->_list($model, $condition);
        }
        $list = $this->get('list');
        $tab_list['0'] = array('name'=>'公共配置','site_id'=>'0','conf_type'=>'1');//$tab_list['1'] = array('name'=>'特殊','site_id'=>'0','conf_type'=>'2');
        $offset = 0;//为了其他的非分站配置扩展使用
        //通过site_id和conf_type作为唯一标识
        foreach (self::$site_list as $key=>$value) {
            $key_value = $key + $offset;
            $tab_list[$key_value] =array('name'=>$value,'site_id'=>$key,'conf_type'=>'0');
        };
        ksort($tab_list);
        //查找当前所属站点的名称
        foreach ($tab_list as $key=>$value) {
            if ($value['site_id'] == $site_id_key && $value['conf_type'] == $conf_type_key)
                 $now_site = $value['name'];
        }
        $site_conf_keys = $site_id_key.','.$conf_type_key;
        $this->assign('conf_type',$conf_type_key);
        $this->assign('site_id',$site_id_key);
        $this->assign('now_site',$now_site);
        $this->assign('tab_list',$tab_list);
        $this->assign('list', $list);
        $this->assign('main_title', 'API配置');
        $this->display();
    }

    public function add() {
        $this->assign('conf_type',$_REQUEST['conf_type']);
        $this->assign('site_id',intval($_REQUEST['site_id']));
        $this->display();
    }

    public function setLastModifyTime() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            $this->error('Redis连接异常');
        }
        $redis->set('api_conf_last_modify_time', time());
        $this->success('发布成功');
    }
    public function insert() {
        $form = D(MODULE_NAME);
        $condition['conf_type'] = $_REQUEST['conf_type'];
        $condition['site_id'] = intval($_REQUEST['site_id']);
        if (!empty($form)) {
            $this->_list($form, $condition);
        }
        $list = $this->get('list');
        // 字段校验
        $data = $form->create();
        //$data = array_map("html",$data);
        foreach ($list as $value) {
            if ($value['name'] == $data['name']) {
               $this->error(L("键值名不能重复！"),0);
            }
        }
        $data['create_time'] = time();
        if (!$data) {
            $this->error($form->getError());
        }
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
        $this->assign("jumpUrl", u(MODULE_NAME . "/index",array('conf_type'=>$_REQUEST['conf_type'],'site_id'=>intval($_REQUEST['site_id']))));
        $this->success(L("INSERT_SUCCESS"));
    }

    /**
     * 公告管理
     * 配置 param_notice
     */
    public function notice() {
        $name = 'param_notice';
        $title = '公告栏';
        $confType = 0;
        $siteId = \libs\utils\Site::getId();
        $condition = [
            'conf_type' => $confType,
            'name'      => $name,
            'site_id'   => $siteId,
        ];
        $apiConf = M('ApiConf')->where($condition)->find();
        $noticeConf = !empty($apiConf['value']) ? json_decode($apiConf['value'], true) : [];
        $noticeStatus = isset($apiConf['is_effect']) ? (int) $apiConf['is_effect'] : 0;
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $updateStatus = isset($_POST['status']) ? (int) $_POST['status'] : 0;
            $updateValue = isset($_POST['value']) ? $_POST['value'] : '[]';
            if (!empty($apiConf)) {
                $data = [
                    'title'         => $title,
                    'is_effect'     => $updateStatus,
                    'value'         => $updateValue,
                    'update_time'   => get_gmtime(),
                ];
                $result = M('ApiConf')->where($condition)->save($data);
            } else {
                $data = [
                    'conf_type'     => $confType,
                    'name'          => $name,
                    'site_id'       => $siteId,
                    'title'         => $title,
                    'is_effect'     => $updateStatus,
                    'value'         => $updateValue,
                    'create_time'   => get_gmtime(),
                    'update_time'   => get_gmtime(),
                ];
                $result = M('ApiConf')->add($data);
            }
            if (false !== $result) {
                $this->success(L("UPDATE_SUCCESS"));
            } else {
                $this->error(L("UPDATE_FAILED"));
            }
        }
        $advH5Url = app_conf('NCFPH_WAP_URL') . '/common_adv';
        $this->assign('page_list', \core\dao\conf\ApiConfModel::$noticePageList);
        $this->assign('notice_conf', $noticeConf);
        $this->assign('notice_status', $noticeStatus);
        $this->assign('adv_h5_url', $advH5Url);
        $this->assign("jumpUrl",u("ApiConf/notice") . '&site_id=' . $siteId);
        $this->display();
    }

}
