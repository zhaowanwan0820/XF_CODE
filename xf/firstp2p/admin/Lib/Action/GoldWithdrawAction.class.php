<?php
/**
 * 黄金变现相关操作.
 */
use NCFGroup\Common\Library\Idworker;
use core\service\GoldService;

class GoldWithdrawAction extends CommonAction
{
    private $service;
    private $goldPrice;

    public function __construct()
    {
        parent::__construct();
        $this->service = new GoldService();
        $res = $this->service->getGoldPrice();
        $this->goldPrice = $res['data']['gold_price'];
    }

    public function withdraw()
    {
        $this->display();
    }

    public function doWithdraw()
    {
        $errorMsg = '';
        $userIds = $this->getUserIds();
        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                $result = $this->withdrawByUserId($userId);
                if (true !== $result) {
                    $errorMsg .= $userId.':'.$result.'</br>';
                }
            }
        }

        if (empty($errorMsg)) {
            echo '处理成功';
        } else {
            echo '处理失败,'.$errorMsg;
        }
    }

    private function withdrawByUserId($userId)
    {
        $ticket = Idworker::instance()->getId();
        $userWithrawInfoRes = $this->service->getUserWithrawInfo($userId);
        $gold = $userWithrawInfoRes['data']['gold'];
        if (bccomp($gold, 0, 3) <= 0) {
            return true;
        }
        $maxGoldCurrent = 1000000000;
        $priceRate = app_conf('GOLD_PRICE_RATE') ? app_conf('GOLD_PRICE_RATE') : 0.5; //浮动利率
        $withdrawMinFee = app_conf('GOLD_WITHDRAW_MIN_FEE') ? 0.01 : $withdrawMinFeeConf; //单笔变现最低手续费
        $result = $this->service->withdrawApply($userId, $ticket, $gold, $maxGoldCurrent, $this->goldPrice, $priceRate, $withdrawMinFee);
        if (empty($result) || 0 != $result['errCode']) {
            return $result['errMsg'];
        }

        return true;
    }

    /**
     *获取Csv数据.
     */
    private function getUserIds()
    {
        if (empty($_FILES['upfile']['name'])) {
            $this->error('上传的文件不能为空');
        }

        if ('csv' != end(explode('.', $_FILES['upfile']['name']))) {
            $this->error('请上传csv格式的文件！');
        }

        $data = array();
        if (false !== ($handle = fopen($_FILES['upfile']['tmp_name'], 'r'))) {
            while (false !== ($row_data = fgetcsv($handle))) {
                $userId = intval($row_data[0]);
                $data[$userId] = $userId;
            }
        }
        return $data;
    }
}
