<?php
/**
 *
 * 标的相关
 * 2016-5-21  到 2018-5-1 的借款列表
 */

use libs\utils\Aes;
use libs\utils\Logger;
use libs\vfs\Vfs;
use libs\db\Db;
use libs\utils\Finance;
use NCFGroup\Common\Library\Idworker;
use libs\utils\DBDes;

// 加载标的相关函数
FP::import("app.Lib.deal");

class DealHistoryAction extends DealDetailAction{
    protected $template = '../DealDetail/index';
    protected $module_name = 'DealHistory';
    protected $title_name = '借款列表-历史';
    protected $history = 1;
    public function index()
    {
        parent::index();
    }
}
?>
