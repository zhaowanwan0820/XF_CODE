<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Ptp\models\Firstp2pAdunionDeal;


class AdunionDealDAO
{
    /**
     * 根据dealLoadId,获取euid等信息,单个拉取
     *
     * @param mixed $leveId
     * @static
     * @access public
     * @return void
     */
    public static function getOrderInfoByDealLoadId($dealLoadId)
    {
        if(empty($dealLoadId)){
            return array();
        }
        $res = Firstp2pAdunionDeal::findFirst("goodsId={$dealLoadId}");
        if($res == false){
            return array();
        }
        return $res->toArray();
    }
    /**
     * 根据dealLoadId数组,获取euid等信息，批量拉取
     *
     * @param mixed $leveId
     * @static
     * @access public
     * @return void
     */
    public static function getOrderInfoByDealLoadIds($dealLoadIds)
    {
        if(empty($dealLoadIds)){
            return array();
        }
        if(!is_array($dealLoadIds)){
            $dealLoadIds = array($dealLoadIds);
        }
        $res = Firstp2pAdunionDeal::find(array(
                    "columns" => array('goodsId', 'euid'),
                    "conditions" => "goodsId in( " . implode(', ',$dealLoadIds) . ")",
                    ));

        if($res == false){
            return array();
        }
        return $res->toArray();
    }

    public static function getOrderInfoByUids($uids)
    {
        if(empty($uids)){
            return array();
        }

        if(!is_array($uids)){
            $uids = array($uids);
        }

        $res = Firstp2pAdunionDeal::find(array(
              "columns" => array('uid', 'euid'),
              "conditions" => "isNewCustom = 1 AND uid in( " . implode(', ',$uids) . ")",
        ));

        if($res == false){
            return array();
        }

        return $res->toArray();
    }

    public static function getOrderInfoByEuids($euids)
    {
        if(empty($euids)){
            return array();
        }

        if(!is_array($euids)){
            $euids = array($euids);
        }

        foreach($euids as $euid){
            $euidStr .= "'$euid',";
        }
        $euidStr = substr($euidStr, 0, -1);
        $res = Firstp2pAdunionDeal::find(array(
                    "columns" => array('uid', 'euid', 'createdAt'),
                    "conditions" => "isNewCustom = 1 AND euid in(" . $euidStr. ")",
                    "order" => "id desc"
                    ));

        if($res == false){
            return array();
        }

        return $res->toArray();
    }

    public static function getEuidInfoByIds($uids, $cn) {
        $query = "SELECT * FROM (SELECT uid, euid FROM firstp2p_adunion_deal WHERE uid IN (%s) AND cn = '%s' AND euid != '' ORDER BY id) tmp GROUP BY uid";
        $query = sprintf($query, implode(', ', $uids), $cn);
        return getDI()->get('firstp2p')->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);
    }

    public static function getRegistList($params) {
        $sql = "SELECT %s FROM firstp2p_adunion_deal WHERE %s";
        $where = sprintf("is_new_custom = 1 AND cn = '%s'", $params['cn']);

        if (!empty($params['startTime'])) {
            $where .= sprintf(" AND created_at >= '%s'", $params['startTime']);
        }

        if (!empty($params['endTime'])) {
            $where .= sprintf(" AND created_at <= '%s'", $params['endTime']);
        }

        $db = getDI()->get('firstp2p_r');
        $pageSize = ($params['pageSize'] <= 0 || $params['pageSize'] > 300) ? 100 : intval($params['pageSize']);

        $countSql = sprintf($sql, 'COUNT(*) AS totalSize', $where);
        $countRes = $db->fetchOne($countSql, \Phalcon\Db::FETCH_ASSOC);
        if ($countRes['totalSize'] <= 0) {
            return [
                'totalSize'   => 0,
                'currentPage' => 1,
                'pageSize'    => $pageSize,
                'userList'    => [],
            ];
        }

        $currentPage = $params['currentPage'] < 1 ? 1 : intval($params['currentPage']);
        $where .= sprintf(' LIMIT %s, %s', ($currentPage - 1) * $pageSize, $pageSize);
        $return = [
            'totalSize'   => $countRes['totalSize'],
            'currentPage' => $currentPage,
            'pageSize'    => $pageSize,
            'userList'    => [],
        ];

        $querySql = sprintf($sql, 'uid, euid', $where);
        $queryRes = $db->fetchAll($querySql, \Phalcon\Db::FETCH_ASSOC);
        foreach ($queryRes as $item) {
            $return['userList'][$item['uid']] = $item;
        }

        return $return;
    }

}
