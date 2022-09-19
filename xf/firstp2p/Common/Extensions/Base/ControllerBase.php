<?php
namespace NCFGroup\Common\Extensions\Base;
use Phalcon\Mvc\Controller;
use NCFGroup\Common\Library\HttpLib;

class ControllerBase extends Controller
{
    public function initialize()
    {
        $this->varz->increaseVarz("total_requests", 1);
        if ($this->request->isPost()) {
            $this->varz->increaseVarz('post_requests', 1);
        }
        if (HttpLib::isMobileAccess()) {
            $this->varz->increaseVarz('mobile_requests', 1);
        }
    }
}

?>
