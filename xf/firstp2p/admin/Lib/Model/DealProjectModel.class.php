<?php

class DealProjectModel extends CommonModel {

    function getList() {
        $user = D('User')->getTableName();
        $pm = $this->getTableName();
        $rs = $this->query("select u.user_name,u.real_name,p.*  from $pm as p join $user as u on p.user_id = u.id");
        return $rs;
    }

}
?>
