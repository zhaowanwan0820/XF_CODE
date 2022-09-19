<?php
namespace core\dao\project;

use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\enum\DealEnum;
use libs\db\Db;
use libs\utils\DBDes;
use libs\utils\Logger;

class DealProjectModel extends BaseModel {

    /**
     * 获取项目的已上标金额
     * @param $id
     * @return float
     */
    public function getProBorrowed($id){
        $sql = "SELECT SUM(`borrow_amount`) AS sum_money FROM %s WHERE project_id = ':project_id'
                AND is_delete = 0 AND deal_status != 3 AND publish_wait = 0 AND `parent_id` != 0";
        $sql = sprintf($sql, DealModel::instance()->tableName());

        $param = array(':project_id' => $id);
        $result = $this->findBySql($sql,$param);
        return $result['sum_money'];
    }

    /**
     * 获取项目的已投资金额
     * @param $id
     * @return float
     */
    public function getProLoaned($id){

        $sql = "SELECT sum(dl.money) AS sum_money FROM %s d left join %s dl on d.id = dl.deal_id
                where d.project_id = :project_id and d.is_delete = 0 and d.deal_status != 3 and d.parent_id != 0";
        $sql = sprintf($sql, DealModel::instance()->tableName(), \core\dao\deal\DealLoadModel::instance()->tableName());

        $param = array(':project_id' => $id);
        $result = $this->findBySql($sql,$param);
        // 暂时去掉已还清不迁移
        //$sum_money = $this->getMovedProLoaned($id);
        return $result['sum_money'];
    }

    /**
     * 获取已还清的项目金额
     * @param $id
     */
    public function getMovedProLoaned($id){

        $vardb = Db::getInstance(DealEnum::DEAL_MOVED_DB_NAME, 'slave');
        $move_sql = "SELECT id FROM %s where project_id ='%d' and is_delete = 0 and deal_status = 5 and parent_id != 0";
        $move_sql = sprintf($move_sql, DealModel::instance()->tableName(),intval($id));
        $deal_id = array();
        $result = $vardb->getAll($move_sql);

        if (empty($result)){
            return 0;
        }
        foreach($result as $v){
            $deal_id[$v['id']] = $v['id'];
        }
        $str_deal_id = implode(',',$deal_id);
        $sql = "SELECT sum(money) AS sum_money FROM %s
                where  deal_id in ($str_deal_id)";
        $sql = sprintf($sql, \core\dao\deal\DealLoadModel::instance()->tableName());
        $result = $this->findBySql($sql);

        return $result['sum_money'];
    }

    public function addProject($data){
        if(isset($data['bankcard']) && !empty($data['bankcard'])){
            $data['bankcard'] =  DBDes::encryptOneValue($data['bankcard']);
        }
        $this->saveProject($data);
        return $this->id;
    }

    public function saveProject($data){
        foreach($data as $k=>$v){
            $this->{$k} = $v;
        }
        $this->update_time = get_gmtime();
        $this->save();
    }

    /**
     * @param $deal_project_id
     * @return array
     */
    public function getFirstDealByProjectId($deal_project_id,$deal_status = null) {
        $cond = "`project_id`='".intval($deal_project_id)."' AND `is_delete`='0'";
        if($deal_status != null){
            $cond .= " AND `deal_status`='".intval($deal_status)."' ";
        }
        $row = DealModel::instance()->findBy($cond." ORDER BY `id` LIMIT 1", "*", array(), true);
        return $row;
    }

    /**
     * 根据项目名称模糊获取项目id
     * @param  string  $name
     * @return array
     */
    public function getProjectIdsByName($name){
        $param = array(':name' => $name);
        $res = $this->findAllViaSlave("`name` like '%:name%'", true, 'id', $param);
        $id_arr_tmp = empty($res) ? array() : $res;
        $id_arr = array();
        foreach ($id_arr_tmp as $id_tmp) {
            $id_arr[] = intval($id_tmp['id']);
        }
        return $id_arr;
    }

    /**
     * 根据项目名称获取项目id
     * @param $name
     * @return array
     */
    public function getProjectIdByName($name) {
        $condition = sprintf("`name` = '%s'", $name);
        $res = $this->findByViaSlave($condition, 'id');
        return !empty($res['id']) ? $res['id'] : 0;
    }

    /**
     * 根据approve_number获得dealProject信息
     * @param $name
     * @return array
     */
    public function getProjectIdByApproveNumber($approve_number) {
        $condition = sprintf("`approve_number` = '%s'", $approve_number);
        $res = $this->findBy($condition, 'id');
        return $res['id'];
    }

    /**
     * 根据approve_number获得dealProject信息
     * @param $name
     * @return array
     */
    public function getProjectInfoByApproveNumber($approve_number) {
        $condition = sprintf("`approve_number` = '%s'", $approve_number);
        $res = $this->findBy($condition);
        return $res;
    }

    /**
     * 修改项目的银行卡账号
     * @param int $project_id
     * @param string $bankcard 新的银行卡号
     * @param int $bank_id
     * @param string $bankzone 开户网点
     * @param string $card_name 开户人姓名
     * @param int $card_type 对公对私标识，对私0   对公1
     * @return boolean
     */
    public function updateBankInfoById($project_id, $bankcard, $bank_id, $bankzone, $card_name ,$card_type = 0)
    {
        try {
            $pro_obj = DealProjectModel::instance()->find($project_id);
            $log_params_info = sprintf('project_id: %d | old_bankcard: %s | new_bankcard: %s', $project_id, $pro_obj['bankcard'], $bankcard);
            $pro_obj->bankcard = addslashes($bankcard);
            $pro_obj->bank_id = intval($bank_id);
            $pro_obj->bankzone = addslashes($bankzone);
            $pro_obj->card_name = addslashes($card_name);
            $pro_obj->card_type = intval($card_type);
            if (false === $pro_obj->save()) {
                throw new \Exception('update project-bankcard failed');
            }
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'success', $log_params_info, 'line:' . __LINE__)));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'fail', $log_params_info, $e->getMessage(), 'line:' . __LINE__)));
            return false;
        }
    }
}
