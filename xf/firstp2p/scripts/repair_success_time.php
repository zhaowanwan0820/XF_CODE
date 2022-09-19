<?php

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';

set_time_limit(0);

class RepairSuccessTime {

    public function run() {
        $deal_model = new \core\dao\DealModel();
        $deal_load_model = new \core\dao\DealLoadModel();
        $deal_list = $deal_model->findAll("`success_time`='0' AND `deal_status` IN (4,5)");

        foreach ($deal_list as $deal) {
            $deal_load = $deal_load_model->findBy("`deal_id`='{$deal->id}' ORDER BY `id` DESC LIMIT 1");
            $success_time = $deal_load->create_time;
            $deal->success_time = $success_time;
            $deal->save();

            $this->_log($deal->id, $success_time);
        }

        echo "=========success=========\n";
    }

    private function _log($deal_id, $success_time) {
        file_put_contents("/tmp/repair.log", $deal_id . "\t" . $success_time . "\n", FILE_APPEND);
    }
}

$obj = new RepairSuccessTime();
$obj->run();
