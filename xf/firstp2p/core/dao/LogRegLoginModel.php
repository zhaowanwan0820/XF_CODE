<?php

/**
 * LogRegLoginModel class file.
 *
 * @author yutao@ucfgroup.com
 *
 * */

namespace core\dao;

class LogRegLoginModel extends BaseModel {

    const TOUCH_TRY_TIMES = 3;

    /**
     * 插入登录注册日志
     *
     * @param $logInfo
     */
    public function insert($logInfo = array()) {

        \FP::import("libs.utils.logger");
        $destination = APP_ROOT_PATH."log/logger/reg_login.log";

        if(!is_file($destination)) {
            try {
                $i = 0;
                do {
                    ++$i;

                    if(touch($destination)) {
                        break;
                    }

                    if($i >= self::TOUCH_TRY_TIMES) {
                        throw new \Exception("can not touch regular files, the disk is full or broken");
                    }

                } while (true);
            } catch (\Exception $e) {
                \logger::error($e->getMessage());
                return true;
            }
        }

        $logKeys = implode(", ", array_keys($logInfo));
        \logger::wLog($logKeys . " | " .join(" | ", $logInfo), \logger::INFO, \logger::FILE, $destination);

        return true;
    }

}

// END class LogRegLoginModel extends BaseModel
