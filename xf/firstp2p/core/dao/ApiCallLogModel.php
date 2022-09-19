<?php
/**
 * 请求接口记录
 * @date 2018-11-30
 * @author zhanyao <zhangyao1@ucfgroup.com>
 */

namespace core\dao;

class ApiCallLogModel extends BaseModel
{
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('itil');
        parent::__construct();
    }

    public function tableName()
    {
        return 'api_call_log';
    }

    public function insertApiLog($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();
        $this->setRow($data);
        if ($this->insert()) {
            return $this->db->insert_id();
        }else{
            throw new \Exception('添加失败');
            return false;
        }
    }

}
