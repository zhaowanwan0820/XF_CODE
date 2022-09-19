<?php
/**
 * Class RedisCacheAction
 * 后台redis缓存操作
 */
FP::import("libs.common.site");
class RedisCacheAction extends CommonAction {
    private $_q = "libs\\queue\\";
    private $_conf = array();

    public function __construct() {
        parent::__construct();
        $this->_conf = $GLOBALS['components_config']['components'];
    }

    public function index() {
        $this->display();
    }

    // redis队列
    public function queue() {
        $arr_queue = array();
        foreach ($this->_conf as $k => $v) {
            $class = $v['class'];
            if (strpos($class, $this->_q)===0) {
                if (empty( $v['channel'])) continue;
                $channel =  $v['channel'];
                $length = SiteApp::init()->$k->len();
                $note = $length ? "队列有积压" : "队列运行良好" ;
                $arr_queue[$k] = array(
                    "id" => $k,
                    "channel" => $channel,
                    "length" => $length,
                    "note" => $note,
                );
            }
        }

        $this->assign("qlist", $arr_queue);
        $this->display();
    }

    // 清空redis队列
    public function flush() {
        $id = addslashes($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $flag = false;
        foreach ($this->_conf as $k => $v) {
            if ($k == $id && strpos($v['class'], $this->_q)===0 ) {
                $flag = true;
            }
        }
        if (!$flag) {
            $this->error(L("INVALID_OPERATION"), $ajax);
        }
        if (SiteApp::init()->$id->flush() === false) {
            $this->error("操作失败", $ajax);
        } else {
            $this->success("操作成功", $ajax);
        }
    }

    // 获取redis值
    public function execute() {
        $key = addslashes(trim($_REQUEST['key']));
        if (!$key) {
            $this->error("key不可为空");
        }
        $value = SiteApp::init()->cache->get($key);
        if (!$value) {
            $this->error("缓存值不存在");
        } else {
            $this->ajaxReturn($value, "获取成功", 1);
        }
    }

    // 设置redis值
    public function set() {
        $key    = addslashes(trim($_REQUEST['key']));
        $value  = addslashes(trim($_REQUEST['value']));
        $expire = intval($_REQUEST['expire']);
        if (!$key) {
            $this->error("key不可为空");
        }
        if (SiteApp::init()->cache->set($key, $value, $expire > 0 ? $expire : 86400) === false) {
            $this->error("操作失败");
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }

    // 删除redis值
    public function del() {
        $key = addslashes(trim($_REQUEST['key']));
        if (!$key) {
            $this->error("key不可为空");
        }
        if (SiteApp::init()->cache->delete($key) === false) {
            $this->error("操作失败");
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }

    //set高可用redis
    public function ha_set() {
        $key    = addslashes(trim($_REQUEST['key']));
        $value  = addslashes(trim($_REQUEST['value']));
        $expire = intval($_REQUEST['expire']);
        if (!$key) {
            $this->error("key不可为空");
        }
        if (SiteApp::init()->dataCache->getRedisInstance()->setEx($key, $expire > 0 ? $expire : 86400, $value) === false) {
            $this->error("操作失败");
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }

    public function ha_get() {
        $key = addslashes(trim($_REQUEST['key']));
        if (!$key) {
            $this->error("key不可为空");
        }
        $value = SiteApp::init()->dataCache->getRedisInstance()->get($key);
        if (!$value) {
            $this->error("缓存值不存在");
        } else {
            $this->ajaxReturn($value, "获取成功", 1);
        }
    }

    public function ha_del() {
        $key = addslashes(trim($_REQUEST['key']));
        if (!$key) {
            $this->error("key不可为空");
        }
        if (SiteApp::init()->dataCache->getRedisInstance()->del($key) === false) {
            $this->error("操作失败");
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }
}

?>