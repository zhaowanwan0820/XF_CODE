<?php
/**
 * 短期标预约配置表
 * @date 2016-11-15
 * @author guofeng3@ucfgroup.com>
 */

namespace core\dao;

use core\dao\UserReservationModel;

class ReservationConfModel extends BaseModel
{
    /**
     * 更新时的参数列表
     * @var array
     */
    private $updateParams = array();

    /**
     * 投资期限配置json字段
     * @var array
     */
    public static $investFieldConfig = array(
        'deadline',
        'deadline_unit',
        'rate',
    );

    /**
    * 预约期限配置json字段
    * @var array
    */
    public static $expireFieldConfig = array(
        'expire',
        'expire_unit',
    );

    /**
     * 预约类型-1:公告
     * @var int
     */
    const TYPE_NOTICE = 1;

    /**
     * 预约类型-2:配置
     * @var int
     */
    const TYPE_CONF = 2; //废弃

    /**
     * 预约类型-3:期限
     * @var int
     */
    const TYPE_DEADLINE = 3; //废弃

    /**
     * 预约类型-4:网贷公告
     * @var int
     */
    const TYPE_NOTICE_P2P = 4;

    /**
     * 预约配置
     * @var array
     */
    private static $reserveConf;

    /**
     * 预约类型数组(1:公告2:配置)
     * @var array
     */
    public static $typeConfConfig = array(
        self::TYPE_NOTICE,
        self::TYPE_CONF,
        self::TYPE_DEADLINE,
        self::TYPE_NOTICE_P2P,
    );

    /**
     * 默认预约最低金额 100元
     */
    const RESERVE_MIN_AMOUNT_DEFAULT = 100;

    /**
     * 默认预约最大值 0没有限制
     */
    const RESERVE_MAX_AMOUNT_DEFAULT = 0;

    //最小投资额
    const RESERVE_P2P_MIN_LOAN_MONEY = 100;
    const RESERVE_EXCLUSIVE_MIN_LOAN_MONEY = 1000;

    /**
     * 根据预约类型，获取预约公告或配置信息
     * @param int $type 预约类型
     * @return \libs\db\model
     */
    public function getReserveInfoByType($type, $dealTypeList = [])
    {
        $key = $type . '_' . implode('_', $dealTypeList);
        if (isset(self::$reserveConf[$key])) {
            return self::$reserveConf[$key];
        }
        intval($type) <= 0 && $type = self::TYPE_NOTICE;
        $data = $this->findBy('`type`=:type', '*', array(':type'=>intval($type)), true);
        if (self::TYPE_CONF == $type && !empty($data))
        {
            // 投资期限配置json
            $data['invest_conf'] = array_values(json_decode($data['invest_conf'], true));
            $data['invest_conf'] = $this->filterConf($data['invest_conf'], $dealTypeList);
            // 预约期限配置json
            $data['reserve_conf'] = json_decode($data['reserve_conf'], true);
            // 预约金额配置
            $data['amount_conf'] = !empty($data['amount_conf']) ? json_decode($data['amount_conf'], true) : [];
            $data['amount_conf'] = $this->filterConf($data['amount_conf'], $dealTypeList);

        }

        //投资期限独立配置
        if (self::TYPE_DEADLINE == $type && !empty($data)) {
            $data['invest_conf'] = array_values(json_decode($data['invest_conf'], true));
        }

        //公共配置含有预约期限 (期限)
        if (self::TYPE_NOTICE == $type) {
            // 预约期限配置json
            $data['reserve_conf'] = json_decode($data['reserve_conf'], true);
        }
        self::$reserveConf[$key] = $data;
        return $data;
    }

    /**
     * 过滤配置
     */
    private function filterConf($reserveConf, $dealTypeList) {
        if (empty($dealTypeList)) {
            return $reserveConf;
        }
        $result = [];
        foreach ($reserveConf as $config) {
            //不显示网贷
            if (isset($config['deal_type']) && !in_array($config['deal_type'], $dealTypeList)) {
                continue;
            }
            $result[] = $config;
        }
        return $result;
    }

    /**
     * 创建预约配置信息
     * @param int $type 预约类型
     * @param string $description 委托合同和协议或预告描述
     * @param string $bannerUri 短期标预告图片链接
     * @param int $minAmountCent 最低预约金额，单位分
     * @param int $maxAmountCent 最高预约金额，单位分
     * @param array $investConf 投资期限配置
     * @param array $reserveConf 预约期限配置
     * @param string $reserveRule 预约规则
     * @param string $amountConf 预约金额配置
     * @return boolean
     */
    public function createReserveInfo($type, $description, $bannerUri = '', $minAmountCent = 0, $maxAmountCent = 0, $investConf = array(), $reserveConf = array(), $reserveRule = '', $amountConf = [])
    {
        $check = $this->_checkParams($type, $description, $bannerUri, $minAmountCent, $maxAmountCent, $investConf, $reserveConf, $reserveRule, $amountConf);
        if (!$check) {
            return false;
        }
        $this->description = htmlspecialchars($description); // 委托合同和协议或预告描述
        $this->create_time = time(); // 创建时间
        try {
            $result = $this->save();
            if(!$result) {
                throw new \Exception("create reservation_conf failed");
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 更新预约配置信息
     * @param int $type 预约类型
     * @param string $description 委托合同和协议或预告描述
     * @param string $bannerUri 短期标预告图片链接
     * @param int $minAmountCent 最低预约金额，单位分
     * @param int $maxAmountCent 最高预约金额，单位分
     * @param array $investConf 投资期限配置
     * @param array $reserveConf 预约期限配置
     * @param string $reserveRule 预约规则
     * @param string $amountConf 预约金额配置
     * @return boolean
     */
    public function updateReserveInfo($type, $description, $bannerUri = '', $minAmountCent = 0, $maxAmountCent = 0, $investConf = array(), $reserveConf = array(), $reserveRule = '', $amountConf = [])
    {
        $check = $this->_checkParams($type, $description, $bannerUri, $minAmountCent, $maxAmountCent, $investConf, $reserveConf, $reserveRule, $amountConf);
        if (!$check) {
            return false;
        }
        $this->updateParams['description'] = htmlspecialchars($description); // 委托合同和协议或预告描述
        $this->updateParams['update_time'] = time(); // 更新时间
        return $this->updateBy(
            $this->updateParams,
            sprintf('`type`=%d', intval($type))
        );
    }

    /**
     * 操作数据前的参数校验
     * @param int $type
     * @param string $description
     * @param string $bannerUri
     * @param int $minAmountCent
     * @param int $maxAmountCent
     * @param array $investConf
     * @param array $reserveConf
     * @param string $reserveRule 预约规则
     * @param string $amountConf 预约金额配置
     */
    private function _checkParams($type, $description, $bannerUri = '', $minAmountCent = 0, $maxAmountCent = 0, $investConf = array(), $reserveConf = array(), $reserveRule = '', $amountConf = [])
    {
        if (!in_array($type, self::$typeConfConfig))
        {
            return false;
        }
        $this->type = $type;
        // 预约类型(1:公告2:配置)
        switch ($type)
        {
            case self::TYPE_NOTICE:
            case self::TYPE_NOTICE_P2P:
                if (empty($bannerUri) || empty($reserveRule))
                {
                    return false;
                }
                $this->banner_uri = $this->updateParams['banner_uri'] = htmlspecialchars($bannerUri); // 短期标预告图片链接
                $this->reserve_rule = $this->updateParams['reserve_rule'] = htmlspecialchars($reserveRule); // 预约规则
                $this->reserve_conf = $this->updateParams['reserve_conf'] = json_encode($reserveConf); // 预约期限配置json
                break;
            case self::TYPE_CONF:
                if (intval($minAmountCent) <= 0 || empty($investConf) || empty($reserveConf))
                {
                    return false;
                }
                $this->min_amount = $this->updateParams['min_amount'] = intval($minAmountCent); // 最低预约金额，单位分
                $this->max_amount = $this->updateParams['max_amount'] = intval($maxAmountCent); // 最高预约金额，单位分
                $this->invest_conf = $this->updateParams['invest_conf'] = json_encode($investConf); // 投资期限配置json
                $this->reserve_conf = $this->updateParams['reserve_conf'] = json_encode($reserveConf); // 预约期限配置json
                $this->amount_conf = $this->updateParams['amount_conf'] = json_encode($amountConf); // 预约金额配置json
                break;
            case self::TYPE_DEADLINE:
                if (empty($investConf))
                {
                    return false;
                }
                $this->invest_conf = $this->updateParams['invest_conf'] = json_encode($investConf); // 投资期限配置json
                break;
            default:
                return false;
                break;
        }
        return true;
    }
}
