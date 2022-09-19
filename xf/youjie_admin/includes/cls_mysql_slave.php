<?php

require(ROOT_PATH . 'includes/cls_mysql.php');
class cls_mysql_slave extends cls_mysql
{
    var $slaveid = null;

    function set_config($config) {
        if(!empty($this->config['slave'])) {
            $this->slaveid = array_rand($this->config['slave']);
        }
        parent::set_config($config);
    }

    /* 随机分配从库连接 */
    function set_slave_config() {
        $this->settings = $this->config['slave'][$this->slaveid];
        $this->settings['charset'] = $this->config['charset'];
        $this->settings['pconnect'] = $this->config['pconnect'];
    }

    function slave_connect() {
        $this->set_slave_config();
        $dbhost = $this->settings['dbhost'];
        $dbuser = $this->settings['dbuser'];
        $dbpw = $this->settings['dbpw'];
        $dbname = $this->settings['dbname'];
        $this->connect($dbhost, $dbuser, $dbpw, $dbname);
    }

    function query($sql, $type = '', $onlyread = false) {
        // 如果执行查询操作，则执行从库连接
        if($this->slaveid && strtoupper(substr($sql, 0 , 6)) == 'SELECT') {
            $this->slave_connect();
        } elseif($onlyread) {
          $this->ErrorMsg("此功能已设置只读查询!");
          return FALSE;
        } else {
            parent::set_config($this->config);
            $dbhost = $this->settings['dbhost'];
            $dbuser = $this->settings['dbuser'];
            $dbpw = $this->settings['dbpw'];
            $dbname = $this->settings['dbname'];
            $this->connect($dbhost, $dbuser, $dbpw, $dbname);
        }
        return parent::query($sql, $type);
    }

    /* 删除失败连接*/
    function del_error_link() {
        unset($this->config['slave'][$this->slaveid]);
        $this->set_config($this->config);
        $this->set_slave_config();
        $dbhost = $this->settings['dbhost'];
        $dbuser = $this->settings['dbuser'];
        $dbpw = $this->settings['dbpw'];
        $dbname = $this->settings['dbname'];
        $this->connect($dbhost, $dbuser, $dbpw, $dbname);
    }

}
