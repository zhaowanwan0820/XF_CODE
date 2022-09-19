<?php
/**
 * 接口配置
 * @date 2018-11-30
 * @author zhanyao <zhangyao1@ucfgroup.com>
 */

namespace core\dao;

class ApiConfigModel extends BaseModel
{
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('itil');
        parent::__construct();
    }

    public function tableName()
    {
        return 'api_config';
    }

    public function insertApiData($data)
    {
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

    public function getApiIdByUM($uri, $module)
    {
        if(empty($uri) || empty($module)){
            return false;
        }

        $condition = sprintf(" uri = '%s' AND module = '%s'", $uri, $module);
        $res = $this->findBy($condition, 'id');
        if($res){
            return $res->id;
        }
        return false;
    }

}
