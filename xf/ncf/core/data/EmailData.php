<?php

/**
 * Email 相关redis 操作
 * @author zhanglei5@ucfgroup.com
 */

namespace core\data;

class EmailData extends BaseData {
    private $_arr_email = array("key"=>"email_");
    /**
     * 获得打开对账单邮件的总数
     * @param string $key
     * @return mixed|boolean
     */
    public function getOpenBillCnt($key) {
        $real_key = $this->_arr_email["key"] . $key;
        $cache = \SiteApp::init()->cache;
       // $cache->hashKey = false;
//        $cache->serializer = true;

        $result = $cache->get($real_key);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function incrOpenBillCnt($key) {
        $real_key = $this->_arr_email["key"] . $key;
        $cache = \SiteApp::init()->cache;
        return $cache->incr(md5($cache->keyPrefix.$real_key));
    }

    public function setOpenBillCnt($key,$value) {
        $real_key = $this->_arr_email["key"] . $key;
        $cache = \SiteApp::init()->cache;
        $result = $cache->set($real_key,$value);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }


}