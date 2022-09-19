<?php
/**
 * RiskAssessmentOperLogModel class file.
 *
 * @author weiwei12@ucfgroup.com
 */

namespace core\dao\risk;

use core\dao\BaseModel;

/**
 * 风险评估操作日志
 *
 * @author weiwei12@ucfgroup.com
 */
class RiskAssessmentOperLogModel extends BaseModel
{
    /**
     * 连firstp2p_payment库
     * RiskAssessmentQuestionsModel constructor.
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }


    /**
     * 添加日志
     * @param $oper_user_id
     * @param $oper_type
     * @param $object_type
     * @param $object_id
     * @return bool
     */
    public function addLog($oper_user_id, $oper_type, $object_type, $object_id)
    {
        $data = array(
            'oper_user_id'  => $oper_user_id,
            'oper_type'     => $oper_type,
            'object_type'   => $object_type,
            'object_id'     => $object_id,
            'oper_time'     => time(),
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }




}
