<?php
/**
 * ContractSignSwitchModel class file.
 *
 * @author wangzhen3@ucfgroup.com
 **/

namespace core\dao\contract;

use core\enum\contract\ContractSignSwitchEnum;
use core\dao\BaseModel;
/**
 * 合同实时代签开关类
 *
 * @author wangzhen3@ucfgroup.com
 **/
class ContractSignSwitchModel extends BaseModel {

    /**
     * 获取打开的开关
     * @return array
     */
    public function getOpenedSwitches(){
      return $this->findAllViaSlave('status = ' .ContractSignSwitchEnum::STATUS_OPENDED . ' and adm_id != 0 ', true);
    }

} // END class ContractSignSwitchModel extends BaseModel
