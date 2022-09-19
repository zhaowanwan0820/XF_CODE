<?php
/**
 * 资产花园
 * 平台相关
 */
class AgPlatformService extends ItzInstanceService
{

    public function getPlatFormList($data){
        $where = ' WHERE 1 ';
        if(isset($data['platform_name']) && !empty($data['platform_name'])){
            $where .= " AND name LIKE '%{$data['platform_name']}%'  ";
        }
        if(empty($data['type'])){
            $where .=" AND type = 1 ";
        }
        $sql = "SELECT id,`name`,company_name FROM ag_platform ".$where;
        return  Yii::app()->agdb->createCommand($sql)->queryAll()?:[];
    }


}