<?php
/**
 * 直接发送兑换机会.
 *
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 * */
use core\service\O2OService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class O2OSendAction extends CommonAction{

    public function __construct() {
        \libs\utils\PhalconRPCInject::init();
        parent::__construct();
    }

    public function index() {
        $this->display();
    }

    public function doSend() {
        $couponGroupId = addslashes(trim($_POST['couponGroupId']));
        if (!$couponGroupId) {
            $this->error('券组ID不能为空');
        }
        $userIds = $this->doUpload();

        $acquireLogInfo = array(
            'trigger_mode' => CouponGroupEnum::TRIGGER_ADMIN_PUSH,
            'user_id' => '',
            'deal_load_id' => '',
            'create_time' => time(),
            'expire_time' => time() + 10 * 86400,
            'coupon_group_ids' => $couponGroupId,
        );

        $service = new \core\service\O2OService;
        $message = array();
        $success = array();
        try {
            $GLOBALS['db']->startTrans();
            if (is_array($userIds)) {
                $maxId = $service->getMaxGiftSendId();
                foreach ($userIds as $k => $userId) {
                    if (!is_numeric($userId)) {
                        $message[] = ($k+1).'行:'.$userIds[$k]."\r\n";
                        continue;
                    }
                    $userId = intval($userId);
                    $chance = $acquireLogInfo;
                    $chance['user_id'] = $userId;
                    $chance['deal_load_id'] = ++ $maxId;
                    $result = $service->doSendGift($chance);
                    if (!$result) {
                        $message[] = ($k+1).'行:'.$chance['user_id']."\r\n";
                    }
                    $success[] = $chance['user_id']."\r\n";
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $message[] = $e->getMessage();
        }

        $this->assign('success', $success);
        $this->assign('failed', $message);
        $this->display();
    }

    public function doUpload() {
        if ($_FILES['userIds']['error'] == 4) {
            $this->error('请选择文件！');
            return;
        }
        if (end(explode('.', $_FILES['userIds']['name'])) != 'csv') {
            $this->error('请上传csv格式的文件');
            return;
        }

        set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $csv_data = array();
        if (($handle = fopen($_FILES['userIds']['tmp_name'], "r")) !== false) {
            while (($row_data = fgetcsv($handle)) !== false) {
                $csv_data[] = $row_data[0] ? trim($row_data[0]) : '';
            }
            fclose($handle);
            @unlink($_FILES['userIds']['tmp_name']);
        }
        if (empty($csv_data)) {
            $this->error('可处理的数据为空');
            return;
        }
        return $csv_data;
    }

}
?>
