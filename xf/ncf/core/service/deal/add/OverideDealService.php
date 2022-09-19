<?php
/**
 * 上标后对标的进行部分重置操作
 *
 * @author  jinhaidong
 * @date 2018-6-19 18:08:29
 */
namespace core\service\deal\add;

use core\dao\deal\DealExtModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\project\DealProjectModel;
use core\service\deal\DealBaseService;
use core\dao\deal\DealModel;
use libs\utils\Logger;
use core\enum\DealLoanTypeEnum;

class OverideDealService extends DealBaseService {

    static $dealData;
    public static function overideDeal($dealData){
        self::$dealData = $dealData;
        self::_overideEffect();
        self::_voerideSiteId();
        return self::$dealData;
    }

    private static function _overideEffect(){

        $invalid = array(DealLoanTypeEnum::TYPE_ZHANGZHONG, DealLoanTypeEnum::TYPE_XFFQ, DealLoanTypeEnum::TYPE_XFD, DealLoanTypeEnum::TYPE_XSJK);
        if(in_array(self::$dealData['type_id'],$invalid)){
            self::$dealData['is_effect'] = 0;
        }
        return self::$dealData;
    }

    private static function _voerideSiteId(){
        $default = $GLOBALS['sys_config']['TEMPLATE_LIST']['普通标(3个月及以上)'];
        $siteMapping = array(

        );

        $dealTypeId = self::$dealData['type_id'];

        $loanTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($dealTypeId);
        self::$dealData['site_id'] = in_array($loanTypeTag,$siteMapping) ? $siteMapping[$loanTypeTag] : $default;
        return self::$dealData;
    }
}