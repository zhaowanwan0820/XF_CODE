<?php

class DealModel extends CommonModel {
    public function getDealsInfoByIds($dealIds) {
        $user = D('User')->getTableName();
        $pm = $this->getTableName();
        $sql = "select u.user_name,u.real_name,u.mobile,d.id,d.name,d.borrow_amount,d.rate,d.repay_time,d.user_id  from ".$pm;
        $sql.= " as d join $user as u on d.user_id = u.id where d.id in(".implode(",",$dealIds).")";
        $rs = $this->query($sql);
        return $rs;
    }
}
?>
