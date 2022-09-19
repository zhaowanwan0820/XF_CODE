<?php
/**
 * GetJobsWorkerStateAction.class.php
 * 环境db及JobsWorker情况
 * @date 20166-01-30
 * @author 樊靖雯 <fanjignwen@ucfgroup.com>
 */

use core\service\GetJobsWorkerStateService;
use libs\utils\Curl;

class GetJobsWorkerStateAction extends CommonAction {

    public function index()
    {
        // filter
        $str = addslashes($_GET['seq']);

        // curl
        $str = Curl::get("http://test" . $str . ".firstp2plocal.com/getjobsworkerstate.php");
        echo $str;
    }

}

?>
