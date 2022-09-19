<?php

/**
 * AdvData class file
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 */

namespace core\data;

class AdvData extends BaseData {
    private $_arr_adv = array("key"=>"adv_", "time"=>86400);

    public function getAdv($adv_id, $tpl_dir = null) {
        $tpl_site_dir = !empty($tpl_dir) ? $tpl_dir : app_conf("TPL_SITE_DIR");
        $id = $this->_format_key($tpl_site_dir . $adv_id);
        $result = \SiteApp::init()->cache->get($this->_arr_adv["key"] . $id);
        if ($result) {
            return json_decode($result, true);
        } else {
            return false;
        }
    }

    public function setAdv($adv_id, $data, $tpl_dir = null) {
        $tpl_site_dir = !empty($tpl_dir) ? $tpl_dir : app_conf("TPL_SITE_DIR");
        $id = $this->_format_key($tpl_site_dir . $adv_id);
        $str = json_encode($data);
        return \SiteApp::init()->cache->set($this->_arr_adv["key"] . $id, $str, $this->_arr_adv["time"]);
    }

    public function flushAdv($adv_id, $tmpl) {
        $tmpl = $tmpl ? $tmpl : "default";
        $id = $this->_format_key($tmpl . $adv_id);
        return \SiteApp::init()->cache->delete($this->_arr_adv["key"] . $id);
    }

    private function _format_key($key) {
        return md5($key);
    }
}