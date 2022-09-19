<?php
/**
 * 产品用款--咨询机构限额
 */
namespace core\service\deal;

use core\service\BaseService;
use core\dao\deal\ProductManagementModel;

class ProductManagementService extends BaseService{

    private static $result = array(
        'errno' => 0,
        'errmsg' => '',
        'level' => 0,
        'use_money' => 0,
    );

    /**
     * 后台交易平台用款预警使用
     * 更新项目对应产品名称下的后台记录中use_money,is_wanning,并返回更新后的is_warning状态
     * errno=1:可以上标
     * errno=1:上标金额超出了所限金额
     * @param  int $product_name,$money
     * @return int $is_wanning
     */
    public function getProductManagement($product_name='',$money=0)
    {
        //获取后台配置的产品名称及用款限额
        if (empty($product_name) || empty($money)) {
            return false;
        }
        $model = ProductManagementModel::instance();
        $product_res = $model->getProductInfoByProductName($product_name,true,"product_id,product_name,money_limit,use_money,use_money,money_effect_term_start,money_effect_term_end");
        if (!empty($product_res)) {
            $nowTime = time();
            if ($nowTime > $product_res['0']['money_effect_term_end'] || $nowTime < $product_res['0']['money_effect_term_start']) {
                self::$result['errno'] = 2;
                self::$result['errmsg'] = '上标时间不在相应产品限额有效时间内'.'now:'.$nowTime.'end:'.$product_res['0']['money_effect_term_end'].','.$product_res['0']['money_effect_term_start'];
                return self::$result;
            }
            $use_money = $product_res['0']['use_money'] +$money;
            if ($product_res['0']['money_limit'] <= $use_money) {
                self::$result['errno'] = 1;
                self::$result['errmsg'] = '上标金额超出了所限金额'.'usemoney:'.$product_res['0']['use_money'].'money:'.$money.'limitmoney:'.$product_res['0']['money_limit'];
                return self::$result;
            }
            //获取预警提示级别0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
            $level = getWarningLevelByMoney($product_res['0']['money_limit'], $use_money);
            self::$result['errmsg'] = '可以上标';
            self::$result['level'] = $level;
            self::$result['use_money'] = $use_money;
            self::$result['product_id'] = isset($product_res['0']['product_id']) ? $product_res['0']['product_id'] : '';
            return self::$result;
        } else {
            return false;
        }
    }

}
