<?php

namespace NCFGroup\Ptp\Apis;

use core\service\candy\CandyUtilService;
use core\service\candy\CandyPayService;
use core\service\DealLoadService;
use core\service\DealCustomUserService;
use core\service\UserService;
use core\service\vip\VipService;
use core\service\BwlistService;
use core\service\AgreementService;
use libs\utils\Logger;

class CandyUtilApi
{
    const CANDY_CHECK_IN = 'checkIn';
    const CANDY_LOTTERY = 'lottery';
    const FILE_SIZE_LIMIT = 2097152;

    /**
     * 上传图片
     */
    public function upload()
    {
        $file = $_FILES['file'];

        if(empty($file) || $file['error'] != 0) {
            return $this->formatResult(null, -1, "read file error");
        }

        // 检查文件类型
        // if (!in_array($file['type'], ["image/png", "image/jpg", "image/gif", "image/jpeg"])) {
        //     return $this->formatResult(null, -1, "file must be picture");
        // }

        // 检查文件大小
        if ($file['size'] > self::FILE_SIZE_LIMIT) {
            return $this->formatResult(null, -1, "large file");
        }

        $uploadFileInfo = array(
            'file' => $file,
            'asAttachment' => 1,
            'other' => 'apk'
        );
        $result = uploadFile($uploadFileInfo);
        if (!empty($result['aid'])) {
            Logger::info("CandyUtilApi update file success. result:{$result['full_path']}");
            return $this->formatResult($result["full_path"]);
        }
        return $this->formatResult(null, -1, "upload file error");
    }

    /**
     * 信宝限时购资格验证
     */
    public function shopQualifyCheck()
    {
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        $qualifyId = isset($_GET['qualifyId']) ? intval($_GET['qualifyId']) : 0;

        if ($userId == 0 || $qualifyId == 0) {
            return $this->formatResult(null, -1, "参数错误");
        }

        $candyPayService = new CandyPayService();
        $config = $candyPayService->getShopQualifyConfig();
        Logger::info("CandyUtilApi shopQualifyCheck. user_id: {$userId}, qualify_id: {$qualifyId}");

        try {
            $pass = $candyPayService->shopQualifyCheck($userId, $qualifyId);
            $data['pass'] = $pass;
            return $this->formatResult($data);
        } catch (\Exception $e) {
            Logger::error('CandyUtilApi shopQualifyCheck:' . $e->getMessage());
            $data['pass'] = false;
            $data['tips'] = $e->getMessage();
            return $this->formatResult($data);
        }
    }

    /**
     * 信宝签到赚信宝资格验证，用户是否投资过
     */
    public function hasInvest()
    {
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        $time = isset($_GET['time']) ? intval($_GET['time']) : 0;

        if ($userId == 0) {
            Logger::info("CandyUtilApi hasInvest. userId: {$userId}, time: {$time}");
            return $this->formatResult(null, -1, "参数错误");
        }

        $dealLoadService = new DealLoadService();
        $result = $dealLoadService->hasLoanByTime($userId, $time);
        $data['pass'] = $result;
        Logger::info("CandyUtilApi hasInvest. user_id: {$userId}, pass:" . $data['pass']);
        return $this->formatResult($data);
    }

    /**
     * 信宝任务是否展示尊享和任务分享
     */
    public function getSwitches()
    {
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

        if ($userId == 0) {
            Logger::info("CandyUtilApi getSwitches. userId: {$userId}");
            return $this->formatResult(null, -1, "参数错误");
        }

        $data['zunxiang'] = $this->hasZunxiang($userId);
        $data['bonus'] = $this->hasBonus($userId);
        Logger::info("CandyUtilApi getSwithes. userId:{$userId}, zunxiang:" . $data['zunxiang'] . " ,bonus:" . $data['bonus']);
        return $this->formatResult($data);
    }

    /**
     * 用户是否有资格投资尊享
     */
    private function hasZunxiang($userId)
    {
        $dealCustomService = new DealCustomUserService();
        // 黑名单或者用户不可投专享
        if ($dealCustomService->checkBlackList($userId) == true || !$dealCustomService->canLoanZx($userId)) {
            return false;
         }
        return true;
    }

    /**
     * 是否有资格分享任务
     */
    private function hasBonus($userId)
    {
        $bonusOn = app_conf('CANDY_BONUS_ON');
        // WHITE:仅白名单可见 ALL:所有用户可见
        if (empty($bonusOn)) {
            return false;
        }
        // 全量打开
        if ($bonusOn == 'ALL') {
            return true;
        }

        // 通用黑白名单
        $bwlistService = new BwlistService();
        if ($bwlistService->inList('zhuli2019', $userId)) {
            return true;
        }
        return false;
    }

    /**
     * 邀请好友是否在黑名单
     */
    public function inInviteBlack()
    {
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        if ($userId == 0) {
            Logger::info("CandyUtilApi inInviteBlack. userId: {$userId}");
            return $this->formatResult(null, -1, "参数错误");
        }
        $blackGroups = app_conf('CANDY_INVITE_BLACK_GROUP');
        if (!empty($blackGroups)) {
            $blackGroups = explode(',', $blackGroups);
            $groupid = (new UserService())->getUser($userId, false, false, true)['group_id'];
            if (in_array($groupid, $blackGroups)) {
                Logger::info("CandyUtilApi inInviteBlack. userid:{$userId}, groupid:{$groupid}");
                return $this->formatResult(array("pass" => false));
            }
        }
        return $this->formatResult(array("pass" => true));
    }

    /**
     * 格式化输出
     */
    private function formatResult($data , $code = 0, $msg = 'success')
    {
        return array(
            'data'=>$data,
            'errorCode'=>$code,
            'errorMsg'=>$msg
        );
    }
}
