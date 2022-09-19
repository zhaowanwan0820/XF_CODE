<?php
/**
 * OtoAcquireLogModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\oto\O2OUtils;

/**
 * 用户gift关系表
 *
 * @author luzhengshuai@ucfgroup.com
 */
class OtoAcquireLogModel extends BaseModel {
    // 领券
    const STATUS_ACQUIRE = 0;

    // 领券并返利成功
    const STATUS_REBATE_COMPLETE = 1;

    // 请求O2O状态,未请求O2O
    const REQUEST_STATUS_INIT = 0;
    // 请求O2O状态,成功,返回结果
    const REQUEST_STATUS_SUC = 1;
    // 请求O2O状态,等待
    const REQUEST_STATUS_WAIT = 2;
    // 请求O2O状态, 成功，结果为空
    const REQUEST_STATUS_EMPTY = 3;
    // 请求O2O状态, 失败
    const REQUEST_STATUS_FAIL = 4;
    // 未领取未过期
    const UNPICK_UNEXPIRED = 0;
    // 未领取已过期
    const UNPICK_EXPIRED = 1;
    // 未领取不筛选是否过期
    const UNPICK_ALL = -1;

    private $firstBidAction = array(
        CouponGroupEnum::TRIGGER_FIRST_DOBID => CouponGroupEnum::TRIGGER_REPEAT_DOBID,
        CouponGroupEnum::TRIGGER_SECOND_DOBID => CouponGroupEnum::TRIGGER_REPEAT_DOBID,
        CouponGroupEnum::TRIGGER_GOLD_FIRST_DOBID => CouponGroupEnum::TRIGGER_GOLD_REPEAT_DOBID,
        CouponGroupEnum::TRIGGER_SUDAI_FIRST_DOBID => CouponGroupEnum::TRIGGER_SUDAI_REPEAT_DOBID
    );

    /**
     * @param int $userId 用户id
     * @param int $action 触发动作
     * @return int 修正后的触发动作
     */
    public function fixOtoAcquireLogAction($userId, $action) {
        // 首投全局唯一，userId+action查询有记录直接返回
        if (array_key_exists($action, $this->firstBidAction) && $this->hasFirstBid($userId, $action)) {
            // 修正action，保证正常落单
            if ($action == CouponGroupEnum::TRIGGER_FIRST_DOBID
                && !$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID)) {
                $action = CouponGroupEnum::TRIGGER_SECOND_DOBID;
            } else {
                $action = $this->firstBidAction[$action];
            }
        } else {
            // 对于复投的修复
            if ($action == CouponGroupEnum::TRIGGER_REPEAT_DOBID) {
                if (!$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_FIRST_DOBID) && (time()> strtotime('20180307'))) {
                    //20180307是新仓库创建时间,历史数据不再补首投记录
                    $action = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                } else if (!$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID)) {
                    $action = CouponGroupEnum::TRIGGER_SECOND_DOBID;
                }
            } else if ($action == CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID || $action == CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID) {
                // 对于智多鑫的修正
                if (!$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_FIRST_DOBID)) {
                    $action = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                } else if (!$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID)) {
                    $action = CouponGroupEnum::TRIGGER_SECOND_DOBID;
                }
            }
        }

        return $action;
    }

    public function getGiftInfoByUniqKey($userId, $triggerMode, $dealLoadId) {
        // HOTFIX sql注入
        $userId = intval($userId);
        $triggerMode = intval($triggerMode);
        $dealLoadId = intval($dealLoadId);
        $condition = "user_id = '$userId' AND trigger_mode = $triggerMode AND deal_load_id = $dealLoadId";
        $giftInfo = $this->findBy($condition);
        if ($giftInfo) {
            $giftInfo = $giftInfo->getRow();
            if (!empty($giftInfo['extra_info'])) {
                $giftInfo['extra_info'] = json_decode($giftInfo['extra_info'], true);
            }
        }

        return $giftInfo;
    }

    public function getGiftInfo($userId, $triggerMode, $dealLoadId = 0, $dealType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        $giftInfo = $this->getGiftInfoByUniqKey($userId, $triggerMode, $dealLoadId);
        if (empty($giftInfo)) {
            // 尝试修正action的值
            // 首投全局唯一，userId+action查询有记录直接返回
            if (array_key_exists($triggerMode, $this->firstBidAction) && $this->hasFirstBid($userId, $triggerMode)) {
                // 修正action，保证正常落单
                if ($triggerMode == CouponGroupEnum::TRIGGER_FIRST_DOBID
                    && !$this->hasFirstBid($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID)) {
                    $triggerMode = CouponGroupEnum::TRIGGER_SECOND_DOBID;
                } else {
                    $triggerMode = $this->firstBidAction[$triggerMode];
                }
                $giftInfo = $this->getGiftInfoByUniqKey($userId, $triggerMode, $dealLoadId);
            } else {
                // 对于复投的修复
                if ($triggerMode == CouponGroupEnum::TRIGGER_REPEAT_DOBID) {
                    $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_FIRST_DOBID, $dealLoadId);
                    if ($giftInfo) {
                        // 首次投资
                        $triggerMode = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                    } else {
                        // 第二次及以上
                        $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID, $dealLoadId);
                        $triggerMode = CouponGroupEnum::TRIGGER_SECOND_DOBID;
                    }
                } else if ($triggerMode == CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID || $triggerMode == CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID) {
                    // 全局首投
                    $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_FIRST_DOBID, $dealLoadId);
                    if ($giftInfo) {
                        $triggerMode = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                    } else {
                        $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_SECOND_DOBID, $dealLoadId);
                        $triggerMode = CouponGroupEnum::TRIGGER_SECOND_DOBID;
                    }
                } else if ($triggerMode == CouponGroupEnum::TRIGGER_GOLD_REPEAT_DOBID) {
                    // 首次购金
                    $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_GOLD_FIRST_DOBID, $dealLoadId);
                    $triggerMode = CouponGroupEnum::TRIGGER_GOLD_FIRST_DOBID;
                } else if ($triggerMode == CouponGroupEnum::TRIGGER_SUDAI_REPEAT_DOBID) {
                    // 首次用速贷业务
                    $giftInfo = $this->getGiftInfoByUniqKey($userId, CouponGroupEnum::TRIGGER_SUDAI_FIRST_DOBID, $dealLoadId);
                    $triggerMode = CouponGroupEnum::TRIGGER_SUDAI_FIRST_DOBID;
                }
            }
        }

        if ($giftInfo) {
            $o2oTriggerMode = $triggerMode;
            // 对于duotou的相关action，现在对于o2o系统都是复投
            if ($triggerMode == CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID
                || $triggerMode == CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID) {
                $o2oTriggerMode = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            }

            $giftInfo['trigger_mode'] = $triggerMode;
            $giftInfo['o2o_trigger_mode'] = $o2oTriggerMode;
            $giftInfo['deal_type'] = $dealType;
        }

        return $giftInfo;
    }

    /**
     * 对于全局action的判断
     */
    public function hasFirstBid($userId, $action) {
        $condition = "user_id = '$userId' AND trigger_mode = ". intval($action);
        $giftInfo = $this->findBy($condition);
        return $giftInfo ? true : false;
    }

    /**
     * getFirstTwoDealActionByUserId获取用户首投和复投的acquireLog
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-11-13
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getFirstTwoDealActionByUserId($userId) {
        $result = array();
        $condition = "user_id = '$userId' AND trigger_mode in ( ". CouponGroupEnum::TRIGGER_FIRST_DOBID. ",". CouponGroupEnum::TRIGGER_SECOND_DOBID .")";
        $logs =  $this->findAll($condition);
        if (!empty($logs)) {
            foreach($logs as $k => $item) {
                $item = $item->getRow();
                $result[$item['trigger_mode']] = $item;
            }
        }
        return $result;
    }

    public function getUnpickList($userId, $page, $pageSize, $status = self::UNPICK_ALL) {
        $nowTime = time();
        $start = ($page-1) * $pageSize;
        //$condition = "user_id = $userId AND gift_id = 0 AND request_status = " .self::REQUEST_STATUS_SUC. " ORDER BY id DESC LIMIT $start, $pageSize";
        // 这块这么写是为了优化ORDER BY慢的问题
        if($status == self::UNPICK_UNEXPIRED) {
            $tmpSql = "SELECT * FROM firstp2p_oto_acquire_log WHERE user_id = $userId AND gift_id = 0 AND request_status = " .self::REQUEST_STATUS_SUC. " AND expire_time > $nowTime";
        } elseif($status == self::UNPICK_EXPIRED) {
            $tmpSql = "SELECT * FROM firstp2p_oto_acquire_log WHERE user_id = $userId AND gift_id = 0 AND request_status = " .self::REQUEST_STATUS_SUC. " AND expire_time <= $nowTime";
        } else{
            $tmpSql = "SELECT * FROM firstp2p_oto_acquire_log WHERE user_id = $userId AND gift_id = 0 AND request_status = " .self::REQUEST_STATUS_SUC;
        }
        $sql = "SELECT * FROM ($tmpSql) AS temp ORDER BY id DESC LIMIT $start, $pageSize";
        $items = $this->findAllBySqlViaSlave($sql);

        if ($items) {
            foreach ($items as $key=>$item) {
                if (empty($item['extra_info'])) {
                    continue;
                }

                $extra = json_decode($item['extra_info'], true);
                $item['deal_type'] = isset($extra['consume_type']) ? $extra['consume_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;
                $items[$key] = $item;
            }
        }

        return $items;
    }

    public function checkUser($userId) {
        $condition = "user_id = $userId AND request_status = " .self::REQUEST_STATUS_SUC. " LIMIT 1";
        return $this->findAllViaSlave($condition);
    }

    public function updateById($data, $id) {
        $data['update_time'] = time();
        $condition = "id = $id";

        //TODO 临时fix，解决程序并发下券更新错误的问题, 后续根源解决
        if (isset($data['request_status'])) {
            if ($data['request_status'] == self::REQUEST_STATUS_EMPTY) {
                $condition .= " AND request_status IN(".self::REQUEST_STATUS_INIT.",".self::REQUEST_STATUS_WAIT.")";
            } else if ($data['request_status'] == self::REQUEST_STATUS_SUC) {
                // 如果是成功状态，则必须为空值才能更新
                $condition .= " AND coupon_group_ids='' AND request_status!=".self::REQUEST_STATUS_EMPTY;
            }
        }
        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }

    /**
     * 获取记录的年化交易额信息
     */
    public function getAnnuAmountById($id, $isMaster = false) {
        $id = intval($id);
        if ($id <= 0) {
            return 0;
        }

        $annualizedAmount = 0;
        $condition = "id = ".$id;
        if ($isMaster) {
            $giftInfo = $this->findBy($condition);
        } else {
            $giftInfo = $this->findByViaSlave($condition);
        }

        if (!empty($giftInfo)) {
            $extraInfo = json_decode($giftInfo['extra_info'], true);
            if (isset($extraInfo['deal_annual_amount'])) {
                $annualizedAmount = $extraInfo['deal_annual_amount'];
            }
        }

        return $annualizedAmount;
    }

    public function getByGiftId($giftId, $isMaster = false) {
        $condition = "gift_id = '$giftId'";
        if ($isMaster) {
            $giftInfo = $this->findBy($condition);
        } else {
            $giftInfo = $this->findByViaSlave($condition);
        }

        if (!empty($giftInfo)) {
            $giftInfo['extra_info'] = json_decode($giftInfo['extra_info'], true);
        }

        return $giftInfo;
    }

    public function addLog($data, $requestStatus = self::REQUEST_STATUS_INIT) {
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $this->request_status = $requestStatus;
        $this->create_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }
}
