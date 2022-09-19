<?php
/**
 *
 * 标的相关
 * 2016-5-21之前的借款列表
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

class DealMovedAction extends DealDetailAction{
    protected $module_name = 'DealMoved';
    protected $title_name = '借款列表-离线';
    protected $template = '../DealDetail/index';
    protected $history = 2;
    /**
     * 列表
     */
    public function __construct() {
        parent::__construct();
    }
    public function index()
    {
        parent::index();

    }
}
?>
