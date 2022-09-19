<?php

namespace core\dao;

use core\dao\ApiConfModel;
/**
 * 用户自定义快捷入口Model
 */
class UserShortcutsModel extends BaseModel {

    private $_shortcutsConf = array(
        1 => 'home_shortcuts_conf',
        2 => 'individual_shortcuts_conf',
    );

    /**
     * 获取用户个性化快捷入口信息
     * @return array
     */
    public function getUserShortcuts($userId ) {
        $condition = "`user_id`=".intval($userId)." and `conf_type`= 1 ";
        $shortcuts = $this->findBy($condition, '*', null, true);
        if (empty($shortcuts)) {
            return false;
        }
        $confId = $this->getCorrectConfig($shortcuts->shortcuts_id);
        if (!empty($confId)) {
            $sql = sprintf("SELECT * FROM %s WHERE `conf_type`='6' AND `name`='home_shortcuts' AND `is_effect`='1' AND `is_delete`='0' AND `id` in(%s)",ApiConfModel::instance()->tableName(),$confId);
            $conf = array_flip(explode(',',$confId));
            $rslt = $this->findAllBySql($sql,true);
            foreach ($rslt as $k => $v) {
                $conf[$v['id']] = $v;
            }
            foreach ($conf as $k => $v) {
                if (!is_array($v)) {
                    unset($conf[$k]);
                }
            }
            return $conf;
        }
        return false;
    }

    /**
     * 获取首页、个人中心页快捷入口信息
     * @return array
     */
    public function getAllShortcuts($type) {
        if (!array_key_exists($type, $this->_shortcutsConf)) {
            return false;
        }
        $name = $this->_shortcutsConf[$type];
        $sql1 = sprintf("SELECT `value` from %s where   `name` = '%s'",ApiConfModel::instance()->tableName(),$name);
        $row = $this->findBySql($sql1);
        if (!$row) {
            return false;
        }
        $conf = json_decode($row->getRow()['value'],true);
        $tmp = [];
        foreach ($conf as $k => $v) {
            $confId = $this->getCorrectConfig($v);
            $order = array_flip(explode(',', $confId));
            if (!empty($confId) && strcmp($k, 'typeName')) {
                $sql=sprintf("SELECT * FROM %s WHERE `is_effect` = '1' AND `is_delete` = '0' AND `id` in(%s)",ApiConfModel::instance()->tableName(),$confId);
                $rslt = $this->findAllBySql($sql,true);
                foreach ($rslt as $key => $val){
                    if (array_key_exists($val['id'],$order)) {
                       $order[$val['id']] = $val;
                    }
                }
                foreach ($order as $id => $val) {
                    if (!is_array($val)) {
                        unset($order[$id]);
                    }
                }
                $tmp[$k] = $order;
            }
        }
        $tmp['typeName'] = isset($conf['typeName']) ? $conf['typeName'] : '';

        return $tmp;
    }


    /**
     * 修改用户个性化配置
     * @return boolean
     */
    public function modifyShortcuts($userId, $config){
        $condition = "`user_id`=".intval($userId) ;
        $shortcutsConf = $this->findBy($condition, '*', null, true);
        if (!$shortcutsConf) {
            $sql = "INSERT INTO `firstp2p_user_shortcuts`(`user_id`,`shortcuts_id`,`conf_type`) VALUES ('%s', '%s', 1) ";
            $sql = sprintf($sql,$userId, $config);
        }else {
            $sql = "UPDATE `firstp2p_user_shortcuts` SET `shortcuts_id` = '%s' WHERE `conf_type` = 1 AND `user_id` = '%s'  ";
            $sql = sprintf($sql,$config, $userId);
        }
        return $this->execute($sql);
    }

    /**
     * 获取首页置顶快捷入口
     * @return array
     */
    public function getStickShortcuts(){
        $sql=sprintf("SELECT * FROM %s WHERE `name`='home_shortcuts' AND `is_effect` = '1' AND `is_delete` = '0' ",ApiConfModel::instance()->tableName());
        $rslt = $this->findAllBySql($sql,true);
        return $rslt;
    }

    /**
     * 处理配置ID错误
     * @return string
     */
    public function getCorrectConfig($config){
        $arr = explode(',', $config);
        foreach ($arr as $k => $v) {
            if ( 0 == ceil($v)) {//保留数字或数字字符串
                unset($arr[$k]);
            }
        }
        $rslt = implode(',', $arr);
        return $rslt;
    }
}
