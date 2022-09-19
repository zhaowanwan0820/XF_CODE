<?php
/**
 * 预约标的匹配表
 * @date 2016-12-16
 * @author guofeng3@ucfgroup.com>
 */

namespace core\dao;

class ReservationMatchModel extends BaseModel
{
    /**
     * 预约服务启动类型(1:优先预约2:人工投资+预约)
     * @var int
     */
    const RESERVE_TYPE_DEFAULT_RESERVING = 1;
    const RESERVE_TYPE_DELAY_RESERVE = 2;

    /**
     * 预约服务启动类型配置
     * @var array
     */
    public static $reserveTypeConfig = array(
        self::RESERVE_TYPE_DEFAULT_RESERVING => '优先预约投资',
        self::RESERVE_TYPE_DELAY_RESERVE => '人工直接投资+预约投资',
    );

    /**
     * 是否有效(0:无效1:有效)
     * @var int
     */
    const IS_EFFECT_INVALID = 0;
    const IS_EFFECT_VALID = 1;

    /**
     * TAG名称-预约标匹配-优先预约
     * @var string
     */
    const TAGNAME_RESERVATION_1 = 'RESERVATION_MATCH_1';

    /**
     * TAG名称-预约标匹配-人工投资+预约
     * @var string
     */
    const TAGNAME_RESERVATION_2 = 'RESERVATION_MATCH_2';

    /**
     * TAG名称集合
     */
    public static $tagNameList = array(
        self::TAGNAME_RESERVATION_1,
        self::TAGNAME_RESERVATION_2,
    );

    /**
     * 根据主键ID获取预约匹配记录
     * @param int $id 自增ID
     * @return \libs\db\model
     */
    public function getReserveMatchById($id)
    {
        $data = $this->findBy('`id`=:id', '*', array(':id'=>intval($id)), true);
        if (!empty($data['invest_conf'])) {
           $data['invest_conf'] = array_values(json_decode($data['invest_conf'], true));
        }
        return $data;
    }

    /**
     * 获取有效的预约匹配列表
     * @return \libs\db\model
     */
    public function getReserveMatchList($typeId = 0, $isEffect = -1, $page = 0, $pageSize = 0, $sortBy = '`id` ASC', $entraId = 0)
    {
        $orderBy = !empty($sortBy) ? sprintf(' ORDER BY %s ', $sortBy) : ' ORDER BY `id` ASC ';
        $limit = ($page > 0 && $pageSize > 0) ? sprintf(' LIMIT %d,%d ', (($page - 1) * $pageSize), $pageSize) : '';
        if ($typeId > 0) {
            $whereParams = $isEffect > 0 ? 'type_id=:type_id AND `is_effect`=:is_effect' : 'type_id=:type_id';
            $whereValues = $isEffect > 0 ? array(':type_id'=>$typeId, ':is_effect'=>$isEffect) : array(':type_id'=>$typeId);
        }else{
            $whereParams = $isEffect > 0 ? '`is_effect`=:is_effect' : '1=1';
            $whereValues = $isEffect > 0 ? array(':is_effect'=>$isEffect) : array();
        }
        if ($entraId > 0) {
            $whereParams .= ' AND entra_id = ' . intval($entraId);
        }
        return $this->findAllViaSlave($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

    /**
     * 创建预约匹配记录
     * @param int $reserveType 预约服务启动类型(1:优先预约2:人工投资+预约)
     * @param int $typeId 产品类别
     * @param string $investConf 投资期限配置json
     * @param int $advisoryId 咨询机构ID
     * @param string $projectIds 项目ID串,用逗号隔开
     * @param int $isEffect 是否有效(0:无效1:有效)
     * @param string $remark 备注
     * @param string $tagName TAG串,用逗号隔开
     * @param string $siteName 站点信息
     * @param int $sort 排序字段
     * @return array
     */
    public function createReserveMatch($reserveType, $typeId, $investConf = array(), $advisoryId = 0, $projectIds = array(), $isEffect = 0, $remark = '', $tagName = '', $siteName = '', $sort = 0, $entraId = 0)
    {
        if (empty($entraId)) {
            return array('respCode'=>'01', 'respMsg'=>'缺少入口数据');
        }
        try {
            $this->reserve_type = intval($reserveType); // 预约服务启动类型(1:优先预约2:人工投资+预约)
            $this->type_id = intval($typeId); // 产品类别
            $this->invest_conf = json_encode($investConf); // 投资期限配置json
            $this->advisory_id = intval($advisoryId); // 咨询机构ID
            $this->project_ids = !empty($projectIds) ? join(',', $projectIds) : ''; // 项目ID串,用逗号隔开
            $this->is_effect = intval($isEffect); // 是否有效(0:无效1:有效)
            $this->remark = htmlspecialchars($remark); // 备注
            $this->tag_name = htmlspecialchars($tagName); // TAG串,用逗号隔开
            $this->site_name = htmlspecialchars($siteName); // 站点信息
            $this->sort = intval($sort); // 排序字段
            $this->create_time = time(); // 创建时间
            $this->entra_id = $entraId;
            $result = $this->save();
            if(!$result) {
                throw new \Exception('insert reservation_match failed');
            }
            return array('respCode'=>'00');
        } catch (\Exception $e) {
            return array('respCode'=>'01', 'respMsg'=>$e->getMessage());
        }
    }

    /**
     * 更新预约匹配记录
     * @param int $id 配置ID
     * @param int $reserveType 预约服务启动类型(1:优先预约2:人工投资+预约)
     * @param int $typeId 产品类别
     * @param string $investConf 投资期限配置json
     * @param int $advisoryId 咨询机构ID
     * @param string $projectIds 项目ID串,用逗号隔开
     * @param int $isEffect 是否有效(0:无效1:有效)
     * @param string $remark 备注
     * @param string $tagName TAG串,用逗号隔开
     * @param string $siteName 站点信息
     * @param int $sort 排序字段
     * @return boolean
     */
    public function updateReserveMatchById($id, $reserveType, $typeId, $investConf = array(), $advisoryId = 0, $projectIds = array(), $isEffect = 0, $remark = '', $tagName = '', $siteName = '', $sort = 0, $entraId = 0)
    {
        if (empty($entraId)) {
            return array('respCode'=>'01', 'respMsg'=>'缺少入口数据');
        }
        $data = array(
            'reserve_type' => intval($reserveType),
            'type_id' => intval($typeId),
            'invest_conf' => json_encode($investConf),
            'advisory_id' => intval($advisoryId),
            'project_ids' => !empty($projectIds) ? join(',', $projectIds) : '',
            'is_effect' => intval($isEffect),
            'remark' => !empty($remark) ? htmlspecialchars($remark) : '',
            'tag_name' => !empty($tagName) ? htmlspecialchars($tagName) : '',
            'site_name' => !empty($siteName) ? htmlspecialchars($siteName) : '',
            'sort' => !empty($sort) ? intval($sort) : 0,
            'update_time' => time(),
            'entra_id' => $entraId,
        );
        $this->db->autoExecute($this->tableName(), $data, 'UPDATE', 'id=' . intval($id));
        return $this->db->affected_rows() >= 1 ? array('respCode'=>'00') : array('respCode'=>'01', 'respMsg'=>'update reservation_match failed');
    }

    /**
     * 获取预约匹配数据
     */
    public function getReserveMatch($typeId, $entraId, $isEffect = -1) {
        $whereParams = '`type_id`=:type_id AND `entra_id`=:entra_id';
        $whereValues = [
            ':type_id' => intval($typeId),
            ':entra_id' => intval($entraId),
        ];
        if ($isEffect >= 0) {
            $whereParams .= ' AND `is_effect`=:is_effect';
            $whereValues[':is_effect'] = (int) $isEffect;
        }
        $whereParams .= ' ORDER BY `is_effect` DESC';
        $data = $this->findBy($whereParams, '*', $whereValues, true);
        return $data;
    }

}
