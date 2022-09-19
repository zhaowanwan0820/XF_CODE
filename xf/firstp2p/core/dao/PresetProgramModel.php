<?php
/**
 * PresetProgram class file.
 *
 * @author wangyiming<wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * 用户信息
 *
 * @author wangyimign<wangyiming@ucfgroup.com>
 **/
class PresetProgramModel extends BaseModel {
    /**
     * 获取活动信息
     * @param string $act
     * @return obj
     */
    public function getPresetProgram($act) {
        $condition = "`program_url`=':act'";
        return $this->findBy($condition, "*", array(':act'=>$act));
    }
  
} // END class PresetProgramModel extends BaseModel
