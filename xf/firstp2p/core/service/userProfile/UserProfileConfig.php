<?php
// 全量刷新数据指标配置
// name  => class
namespace core\service\userProfile;

class UserProfileConfig{

    public static function getFlushDataConf($key){

        $conf = array(
            'all'=>array('InvestIndex','CommissionIndex'),
            'invest'=>array('InvestIndex'),
            'commission'=>array('CommissionIndex'),
        );
        $arrayKeys = array_keys($conf);
        if(!in_array($key,$arrayKeys)) return array();
        return $conf[$key];
    }

    public static function getBlackList(){
        return [4159];
    }

}
?>
