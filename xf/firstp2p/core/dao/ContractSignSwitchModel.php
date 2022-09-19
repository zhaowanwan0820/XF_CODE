<?php
/**
 * ContractSignSwitchModel class file.
 *
 * @author wangzhen3@ucfgroup.com
 **/

namespace core\dao;


/**
 * 合同实时代签开关类
 *
 * @author wangzhen3@ucfgroup.com
 **/
class ContractSignSwitchModel extends BaseModel {

    /**
     * 开关状态
     */
    const StatusClosed = 0; //关闭
    const StatusOpended = 1;//打开

    const TypeBorrow = 1; //借款人
    const TypeAgency = 2;//担保方
    const TypeAdvisory = 3;//资产管理方

    /**
     * 获取打开的开关
     * @return array
     */
    public function getOpenedSwitches(){
      return $this->findAllViaSlave('status = ' .self::StatusOpended . ' and adm_id != 0 ', true);
    }

} // END class ContractSignSwitchModel extends BaseModel
