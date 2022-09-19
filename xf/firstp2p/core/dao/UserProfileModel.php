<?php
/**
 * @author <wangfei5@ucfgroup.com>
 **/

namespace core\dao;

/**
 **/
class UserProfileModel extends BaseModel {


     /**
    * 添加一条用户profile的完整记录
    */
    public function addNewRecord($userId,$datas){
        $this->db = \libs\db\Db::getInstance('profile');
        foreach ($datas as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }
        $this->user_id = $userId;
        $this->create_time = time();
        $ret = $this->insert();
        if($ret){
            return $this->db->insert_id();
        }else{
            return false;
        }

    }

    /**
    * 更新一个用户的profile
    */
    public function updateRecord($userId,$data){
        $this->db = \libs\db\Db::getInstance('profile');
        $tmp = array();
        foreach($data as $k=>$v){
            $tmp[] = "$k=$v";
        }
        $kv = implode(',',$tmp);
        $where = 'user_id='.$this->escape($userId);

       $sql = sprintf("UPDATE firstp2p_user_profile SET %s WHERE %s",$kv,$where);
       return $this->updateRows($sql);
    }
    /**
    * 根据用户ID查询某个用户的profile
    */
    public function getOneByUserId($userId){
        $this->db = \libs\db\Db::getInstance('profile');
        $sql = sprintf("SELECT * FROM firstp2p_user_profile WHERE user_id='%s' LIMIT 1",$this->escape($userId));
        $data = $this->db->getRow($sql);
        if(!empty($data)){
            return $data;
        }else{
            return array();
        }
    }

    public function getUserProfileByUserIdReferUserId($userId,$referUserId){
        $this->db = \libs\db\Db::getInstance('profile');
        $sql = sprintf("SELECT * FROM firstp2p_user_profile WHERE user_id='%s' AND cur_refere_user_id='%s' LIMIT 1",$this->escape($userId),$this->escape($referUserId));
        $data = $this->db->getRow($sql);
        if(!empty($data)){
            return $data;
        }else{
            return array();
        }
    }

    /**
    * 根据用户IDs查询某批用户的profile
    */
    public function getListByUserIds($userIds,$orderBy,$offset=0,$count=10){
        $this->db = \libs\db\Db::getInstance('profile');
        $sql = sprintf("SELECT user_id,%s as show_str FROM firstp2p_user_profile WHERE user_id IN (%s) ORDER BY %s DESC,id DESC LIMIT $offset,$count ",$orderBy,implode(',',$userIds),$orderBy);
        $data = $this->db->getAll($sql);
        if(!empty($data)){
            return $data;
        }else{
            return array();
        }
    }

    /**
    * 根据用户IDs查询某批用户的profile
    */
    public function getListByRefererUserId($referUserId,$orderBy,$offset=0,$count=10){
        $this->db = \libs\db\Db::getInstance('profile');
        $sql = sprintf("SELECT user_id,%s as show_str FROM firstp2p_user_profile WHERE cur_refere_user_id=%s ORDER BY %s DESC,id DESC LIMIT $offset,$count ",$orderBy,$referUserId,$orderBy);
        $data = $this->db->getAll($sql);
        if(!empty($data)){
            return $data;
        }else{
            return array();
        }
    }

    /**
    * 根据用户IDS批量查询用户的基本信息
    */
    public function getBaseInfoByUserIds($userId){
        $sql = sprintf("SELECT id,user_name,mobile,real_name,create_time,money,byear,bmonth,bday FROM firstp2p_user WHERE id = '%s'",$userId);
        $data = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
        if(!empty($data)){
            return $data;
        }else{
            return array();
        }
    }

    /**
    * 默认根据在投情况获取客户列表
    */
    public function getInvestCustomers($referUserId){
        $sql = "SELECT cb.user_id,cb.refer_user_id,ulrs.norepay_principal FROM firstp2p_coupon_bind as cb LEFT JOIN
            firstp2p_user_loan_repay_statistics as ulrs ON  ulrs.user_id = cb.user_id
        WHERE cb.refer_user_id ='$referUserId' ORDER BY ulrs.norepay_principal";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        return $ret;
    }

    /*
    * 未实名客户列表
    */
    public function getUnRealNameCustomers($referUserId,$offset=0,$count=10){
        $rowNames = " cb.user_id, cb.refer_user_id, u.id, u.user_name, u.mobile,u.real_name,u.create_time,u.money,u.byear,u.bmonth,u.bday ";
        $ct = " count(*) as total ";
        $sql = "SELECT %s
                FROM firstp2p_coupon_bind as cb INNER JOIN firstp2p_user as u ON cb.user_id=u.id AND u.idcardpassed !=1
                WHERE cb.refer_user_id='%s' %s";
        $order = ' ORDER BY u.create_time DESC ';
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow(sprintf($sql,$ct,$referUserId,''));
        $total = empty($ret['total'])?0:intval($ret['total']);
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll(sprintf($sql,$rowNames,$referUserId,$order)." LIMIT $offset,$count");
        $data = empty($ret)?array():$ret;
        return array("total"=>$total,"data"=>$data);
    }

    /*
    * 可用余额排序
    */
    public function getUsersByMoney($referUserId,$offset=0,$count=10){
        $rowNames = " cb.user_id, cb.refer_user_id, u.id, u.user_name, u.mobile,u.real_name,u.create_time,u.money as show_str,u.byear,u.bmonth,u.bday ";
        $ct = " count(*) as total ";
        $sql = "SELECT %s
                FROM firstp2p_coupon_bind as cb INNER JOIN firstp2p_user as u ON cb.user_id=u.id
                WHERE cb.refer_user_id='%s' ORDER BY u.money DESC";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow(sprintf($sql,$ct,$referUserId));
        $total = empty($ret['total'])?0:intval($ret['total']);
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll(sprintf($sql,$rowNames,$referUserId)." LIMIT $offset,$count");
        $data = empty($ret)?array():$ret;
        return array("total"=>$total,"data"=>$data);
    }

    /*
    * 可用余额排序by UIDS
    */
    public function getUsersByMoneyByUids($uids,$offset=0,$count=10){
        $rowNames = "u.id, u.user_name, u.mobile,u.real_name,u.create_time,u.money as show_str,u.byear,u.bmonth,u.bday ";
        $sql = "SELECT %s
                FROM firstp2p_user as u WHERE u.id IN (%s) ORDER BY u.money DESC";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll(sprintf($sql,$rowNames,implode(',',$uids))." LIMIT $offset,$count");
        $data = empty($ret)?array():$ret;
        return $data;
    }

    /*
    * 回款时间排序客户列表
    */
    public function getUsersByRepayTime($referUserId,$offset=0,$count=10){
        $year = date("Y");
        $rowNames = " cb.user_id, dlrc.repay_month, dlrc.repay_day";
        $now = date("Y")*10000+date("m")*100 + date("d");
        $year = date("Y");
        $i=0;
        $sqlTmp = array();
        while($i<2){
            $sqlTmp[] = sprintf("SELECT %s as years,%s FROM firstp2p_coupon_bind as cb LEFT JOIN firstp2p_deal_loan_repay_calendar_%s as dlrc
                            ON cb.user_id=dlrc.user_id AND (%s*10000+dlrc.repay_month*100+dlrc.repay_day)>%s WHERE cb.refer_user_id='%s' ",
                            $year+$i,$rowNames,$year+$i,$year+$i,$now,$referUserId);
            $i++;
        }
        $sql = "SELECT *,MIN(years*10000+repay_month*100+repay_day) AS t FROM (".implode(" UNION ALL ",$sqlTmp).") as tmptable GROUP BY user_id ORDER BY t DESC LIMIT $offset,$count";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll(sprintf($sql,$year,$rowNames,$now,$referUserId));
        $data = empty($ret)?array():$ret;
        return $data;

    }

    public function getAllCustomersByReferUserId($referUserId){
        $totalSql = "select count(*) as total from firstp2p_coupon_bind where refer_user_id='%s'";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow(sprintf($totalSql,$referUserId));
        $total = empty($ret['total'])?0:intval($ret['total']);
        return $total;
    }


    /*
    * 回款时间排序客户列表
    */
    public function getUsersByRepayTimeByUids($uids,$offset=0,$count=10){
        $rowNames = " user_id,repay_month, repay_day ,(norepay_principal+norepay_interest) as money";
        $now = date("Y")*10000+date("m")*100 + date("d");
        $year = date("Y");
        $i=0;
        $sqlTmp = array();
        while($i<2){
            $sqlTmp[] = sprintf("SELECT %s as years,%s FROM firstp2p_deal_loan_repay_calendar_%s WHERE user_id IN (%s) AND (%s*10000+repay_month*100+repay_day)>%s",$year+$i,$rowNames,$year+$i,implode(',',$uids),$year+$i,$now);
            $i++;
        }
        $sql = "SELECT *,MIN(years*10000+repay_month*100+repay_day) AS t FROM (".implode(" UNION ALL ",$sqlTmp).") as tmptable GROUP BY user_id ORDER BY t LIMIT $offset,$count";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll($sql);
        $data = empty($ret)?array():$ret;
        return array("data"=>$data);
    }


    public function getCustomersByReferUserId($referUserId){
        $sql = "select user_id from firstp2p_coupon_bind where refer_user_id=%s";
        $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getAll(sprintf($sql,$referUserId));
        $data = empty($ret)?array():$ret;
        return $data;
    }

}
