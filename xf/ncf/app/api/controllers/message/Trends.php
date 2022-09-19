<?php
namespace api\controllers\message;

/**
 * 全局动态
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Trends extends AppBaseAction
{
    public function invoke()
    {
        try {
            $result = \SiteApp::init()
                ->dataCache
                ->call($this->rpc, 'local', ['TrendsService\getAllTrends', []], 60, false, true);
        } catch (\Exception $e) {
            Logger::error('TrendsError:'.$e->getMessage());
            $result = [];
        }

        $this->json_data = $result;
    }

}
