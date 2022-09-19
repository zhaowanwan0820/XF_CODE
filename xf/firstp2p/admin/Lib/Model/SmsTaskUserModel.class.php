<?php

class SmsTaskUserModel extends CommonModel {

    public function __construct() {
        $this->table_prefix = 'firstp2p_';
        parent::__construct('SmsTaskUser', false, 'msg_box', 'master');
    }

}
