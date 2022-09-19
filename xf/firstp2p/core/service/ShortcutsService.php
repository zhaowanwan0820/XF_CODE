<?php

namespace core\service;

use core\dao\UserShortcutsModel;
use core\dao\ApiConfModel;

/**
 * Class ShortcutsService
 *
 * @package core\service
 */
class ShortcutsService extends BaseService {
    private $_typeName = array(
        'userService' => '用户服务',
        'helpService' => '帮助服务',
    );

    public function getUserShortcuts($userId) {
        // 获取用户个性化快捷入口
        $shortcuts = UserShortcutsModel::instance()->getUserShortcuts($userId);
        if (empty($shortcuts)) {
            return false;
        }
        return $this->formatResult($shortcuts);
    }

    public function getAllShortcuts($type,$version = false) {
        //获取全部快捷入口
        $allShortcuts = UserShortcutsModel::instance()->getAllShortcuts($type);
        if (!$allShortcuts) {
            return false;
        }
        return $this->formatResultForAll($allShortcuts,$type,$version);
    }

    public function modifyUserShortcuts($userId, $config){
        $result = UserShortcutsModel::instance()->modifyShortcuts($userId, $config);
        return $result;
    }

    public function getMineShortcuts(){
        $allShortcuts = UserShortcutsModel::instance()->getAllShortcuts(1);
        $mineShortcuts = [];
        if ($allShortcuts) {
            foreach ($allShortcuts as $type => $conf) {
                if (!strcmp($type, 'mine')) {
                    foreach ($conf as $k => $v) {
                        $confValue = json_decode($v['value'],true);
                        if ((empty($confValue['startTime']) || strtotime($confValue['startTime']) < time()) && (empty($confValue['endTime']) || strtotime($confValue['endTime']) > time())){
                            $tmp = [];
                            $tmp['id'] = $v['id'];
                            $tmp['title'] = $v['title'];
                            $tmp['subTitle'] = $confValue['subTitle'];
                            $tmp['type'] = $confValue['type'];
                            $tmp['isStick'] = $confValue['isStick'];
                            $tmp['imageUrl'] = $confValue['imageUrl'];
                            if ( isset($confValue['url'])) {
                                $tmp['url'] = $confValue['url'];
                            }
                            $mineShortcuts[] = $tmp;
                        }
                    }
                }
            }
        }
        return $mineShortcuts;
    }

    public function getStickShortcuts(){
        $allShortcuts = UserShortcutsModel::instance()->getStickShortcuts();
        foreach ($allShortcuts as $k => $v) {
            if (json_decode($v['value'],true)['isStick'] == '0') {
                unset($allShortcuts[$k]);
            }
        }
        return $this->formatResult($allShortcuts);
    }

    public function formatResult($shortcuts){
        $result = [];
        foreach ($shortcuts as $k => $v) {
            $confValue = json_decode($v['value'],true);
            if ((empty($confValue['startTime']) || strtotime($confValue['startTime']) < time()) && (empty($confValue['endTime']) || strtotime($confValue['endTime']) > time())){
                $tmp = json_decode($v['value'], true);
                $tmp['title'] = $v['title'];
                $tmp['id'] = $v['id'];
                $result[] = $tmp;
            }
        }
        return $result;
    }

    public function formatResultForAll($shortcuts,$type,$version=false){
        $result = [];
        if ($type == 1) {
            $conf = explode(',', $shortcuts['typeName']);
            $typeName = array(
                'welfare' => $conf[0],
                'assets' => $conf[1],
                'service' => $conf[2]
            );
            $this->_typeName = array_merge($this->_typeName, $typeName);
        }
        foreach ($shortcuts as $type => $conf) {
            if (strcmp($type, 'typeName') && strcmp($type, 'mine')){
                foreach ($conf as $k => $v) {
                    $confValue = json_decode($v['value'],true);
                    if ((empty($confValue['startTime']) || strtotime($confValue['startTime']) < time()) && (empty($confValue['endTime']) || strtotime($confValue['endTime']) > time())){
                        $tmp = [];
                        $tmp['id'] = $v['id'];
                        $tmp['title'] = $v['title'];
                        $tmp['subTitle'] = $confValue['subTitle'];
                        $tmp['type'] = $confValue['type'];
                        $tmp['isStick'] = $confValue['isStick'];
                        $tmp['imageUrl'] = $confValue['imageUrl'];
                        $tmp['confType'] = $this->_typeName[$type];
                        $tmp['sTime'] = $confValue['startTime'];
                        $tmp['eTime'] = $confValue['endTime'];
                        if ( isset($confValue['url'])) {
                            $tmp['url'] = $confValue['url'];
                        }
                        //增加版本控制，如果配置版本大于当前app版本，则不显示
                        if(isset($confValue['version']) && !empty($version) && $version < $confValue['version']){
                            continue;
                        }
                        $confArr[$this->_typeName[$type]][] = $tmp;
                    }
                }
            }
        }
        foreach ($confArr as $type => $arr){
            foreach ($arr as $k => $v){
                $result[] = $v;
            }
        }
        return $result;
    }

    public function sortByStick(&$arr){
        $isStick = array_column($arr,'isStick');
        array_multisort($isStick,SORT_DESC,$arr);
    }

    public function dupRemove(&$userShortcuts, $stickShortcuts){
        $id = [];
        foreach ($stickShortcuts as $k => $v) {
            $id[] = $v['id'];
        }
        foreach ($userShortcuts as $k => $v) {
            if (in_array($v['id'], $id)) {
                unset($userShortcuts[$k]);
            }
        }
    }

}
