<?php
/**
 * PlatformManagementService.php
 * @author wenyanlei@ucfgroup.com
 */
namespace core\service;
use core\dao\PlatformManagementModel;

class PlatformManagementService extends BaseService{

    private static $result = array(
                'errno' => 0,
                'errmsg' => '',
                'level' => 0,
                'use_money' => 0,
                'advisory_name' => ''
        );
    /**
     * 后台交易平台用款预警使用
     * 根据机构id和所提项目的钱数判断是否满足后台配置的限额要求
     * errno=0:可以上标
     * errno=1:上标金额超出了所限金额
     * @param  int $advisory_id，$money所提项目钱数
     * @return array
     */
    public function getPlatManagement($advisory_id,$money=0)
    {
        if (empty($advisory_id) || empty($money)) {
            return false;
        }
        //获取后台配置的平台咨询机构名称及用款限额
        $model = PlatformManagementModel::instance();
        $advisory_res = $model->getPlatformInfoByAdvisoryId($advisory_id,true,"advisory_name,advisory_id,money_limit,use_money,money_effect_term_start,money_effect_term_end");

        if (!empty($advisory_res)) {
            $nowTime = time();
            if ($nowTime > $advisory_res['0']['money_effect_term_end'] || $nowTime < $advisory_res['0']['money_effect_term_start']) {
                self::$result['errno'] = 2;
                self::$result['errmsg'] = '上标时间不在机构有效时间内'.','.$nowTime.','.$advisory_res['0']['money_effect_term_end'].','.$advisory_res['0']['money_effect_term_start'];
                return self::$result;
            }
            $use_money = $advisory_res['0']['use_money'] +$money;
            if ($advisory_res['0']['money_limit'] <= $use_money) {
                self::$result['errno'] = 1;
                self::$result['errmsg'] = '上标金额超出了所限金额use_money:'.$advisory_res['0']['use_money'].'money:'.$money.'money_limit:'.$advisory_res['0']['money_limit'];
                return self::$result;
            }
            //获取预警提示级别0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
            $level = getWarningLevelByMoney($advisory_res['0']['money_limit'], $use_money);
            self::$result['errmsg'] = '可以上标';
            self::$result['level'] = $level;
            self::$result['use_money'] = $use_money;
            self::$result['advisory_name'] = isset($advisory_res['0']['advisory_name']) ? $advisory_res['0']['advisory_name'] : '';
            return self::$result;
        } else {
            return false;
        }
    }
    /**
     * 更新咨询机构对应的use_money,is_wanning
     * errno=2:上标后台更新失败，请重试
     * @param  float $use_money,int $advisory_id,$level
     * @return array
     */
    public function updatePlatformInfo($use_money,$advisory_id,$level,$exc = false) {
        $model = PlatformManagementModel::instance();
        //更新后台数据
        $update_res = $model->updatePlatformInfoByCondition($use_money,$advisory_id,$level);
        if (!$update_res) {
            if ($exc) {
                throw new \Exception("update warning advisory platformInfo fail");
            }
            self::$result['errno'] = 2;
            self::$result['errmsg'] = '上标后台更新失败，请重试';
            return self::$result;
        }
        self::$result['errmsg'] = '更新成功';
        self::$result['level'] = $level;
        return self::$result;
    }
}
