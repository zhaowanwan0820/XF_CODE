<?php
return array(
    'select_account_for_update'=>'select * from dw_account where user_id=:user_id for update',
    'select_recharge_for_update'=>'select * from dw_account_recharge where trade_no=:trade_no for update',
);
?>
