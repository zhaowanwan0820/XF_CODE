<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use core\service\face\FaceService;
use core\service\face\cmd\AbstractCmd;
use libs\utils\Logger;

class LivingCmd extends AbstractCmd {
    // 人脸识别
    protected $type = FaceService::TYPE_LIVING;

    /**
     * 检查check
     */
    public function check() {
        // 开关关闭，直接返回不用人脸
        if (!FaceService::isFaceSwitchOn($this->type)) {
            return $this->buildCheckResult(0);
        }

        return $this->buildCheckResult(0);
    }

    /**
     * 比较
     */
    public function compare() {

    }
}