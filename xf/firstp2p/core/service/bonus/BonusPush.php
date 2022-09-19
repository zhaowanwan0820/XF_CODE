<?php

/**
 * 红包账户
 */
namespace core\service\bonus;

use core\dao\BonusConfModel;

/**
 * 红包账户
 */
class BonusPush {

    const GET_GROUP = 'PUSH_GET_GROUP_MSG';
    const GET_BONUS = 'PUSH_GET_BONUS_MSG';
    const WILL_EXPIRE_GROUP = 'PUSH_WILL_EXPIRE_GROUP_MSG';
    const WILL_EXPIRE_BONUS = 'PUSH_WILL_EXPIRE_BONUS_MSG';

    public static function getConfig($type = self::WILL_EXPIRE_BONUS) {

        $config = array(
            'switch' => BonusConfModel::get('PUSH_SWITCH'),
            'title' => BonusConfModel::get('PUSH_TITLE'),
            'pre_min' => BonusConfModel::get('PUSH_PRE_TIME'),
            'min_money' => BonusConfModel::get('PUSH_MIN_MONEY'),
            'content'  => BonusConfModel::get($type)
        );
        if ($type == self::GET_GROUP || $type == self::GET_BONUS) {
            $config['title'] = BonusConfModel::get('PUSH_TITLE');
        } else {
            $config['title'] = BonusConfModel::get('PUSH_WILL_EXPIRE_TITLE');
        }
        if (empty($config['content'])) {
            return array();
        }

        return $config;
    }

}
