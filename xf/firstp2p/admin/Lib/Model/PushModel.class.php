<?php

class PushModel extends CommonModel {
    function getList() {
        $d = D('Deal')->getTableName();
        $p = $this->getTableName();
        $rs = $this->query("select $p.*,$d.deal_status from $p join $d where $p.deal_id=$d.id and $p.is_delete = 0");
        return $rs;
    }

}
?>
