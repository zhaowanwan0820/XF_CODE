<?php

/**
 * AdvData class file
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 */

namespace core\data;

class AdvData extends BaseData {
    private $_arr_adv = array( "key" => "ph_adv_", "time" => 86400);

    public function getAdv($adv_id, $tpl_dir = null) {
        $id = $this->_format_key($adv_id);
        $result = \SiteApp::init()->cache->get($this->_arr_adv["key"] . $id);
        if ($result) {
            return json_decode($result, true);
        } else {
            return false;
        }
    }

    public function setAdv($adv_id, $data, $tpl_dir = null) {
        $id = $this->_format_key($adv_id);
        $str = json_encode($data);
        return \SiteApp::init()->cache->set($this->_arr_adv["key"] . $id, $str, $this->_arr_adv["time"]);
    }

    public function flushAdv($adv_id, $tmpl = null) {
        $id = $this->_format_key($adv_id);
        return \SiteApp::init()->cache->delete($this->_arr_adv["key"] . $id);
    }

    private function _format_key($key) {
        return md5($key);
    }
}
