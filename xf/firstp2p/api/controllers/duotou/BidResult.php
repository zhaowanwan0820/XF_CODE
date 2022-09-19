<?php
namespace api\controllers\duotou;

/**
 * wap多投投资结果页面
 **/
class BidResult extends DuotouBidReturn
{

    const IS_H5 = true;

    public function invoke()
    {
        parent::invoke();
        $this->tpl->assign('data', $this->json_data);
    }


}
