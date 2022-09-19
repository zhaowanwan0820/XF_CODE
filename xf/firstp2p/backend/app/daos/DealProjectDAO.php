<?php

namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Ptp\models\Firstp2pDealProject;
use NCFGroup\Ptp\models\Firstp2pDealProjectCompound;
use libs\utils\Logger;

/**
 * DealProjectDAO
 * @package default
 */
class DealProjectDAO {

    public static function addProjectInfo($name, $userId, $approveNumber, $borrowAmount, $credit, $loanType, $rate, $repayReroid, $projectInfoUrl, $projectExtrainfoUrl) {
        $obj = new Firstp2pDealProject();
//        $obj->initialize();
        $obj->name = $name;
        $obj->userId = $userId;
        $obj->approveNumber = $approveNumber;
        $obj->borrowAmount = $borrowAmount;
        $obj->credit = $credit;
        $obj->loantype = $loanType;
        $obj->rate = $rate;
        $obj->repayTime = $repayReroid;
        $obj->projectInfoUrl = $projectInfoUrl;
        $obj->projectExtrainfoUrl = empty($projectExtrainfoUrl) ? "" : $projectExtrainfoUrl;
        $obj->intro = '';
        $obj->createTime = time();
        $obj->updateTime = time();
        $obj->cardName = '';
        $obj->bankcard = '';
        $obj->bankzone = '';
        $obj->bankId = 0;

        try {
            $obj->save();
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi addProjectInfo" . "|" . $ex->getMessage());
            $obj = null;
        }
        if ($obj && $obj->id > 0) {
            return $obj;
        }
        return false;
    }

    /**
     * 根据approve_number获得dealProject信息
     *
     * @param mixed $approve_number
     * @static
     * @access public
     * @return void
     */
    public static function getProject($approve_number) {
        try {
            $projectObj = Firstp2pDealProject::findFirst("approveNumber='{$approve_number}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProject" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }

    /**
     * 根据projectId获得dealProjectCompound信息
     *
     * @param mixed $projectId
     * @static
     * @access public
     * @return void
     */
    public static function getProjectCompound($projectId) {
        try {
            $projectCompoundObj = Firstp2pDealProjectCompound::findFirst("projectId='{$projectId}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProjectCompound" . "|" . $ex->getMessage());
            $projectCompoundObj = null;
        }
        return $projectCompoundObj;
    }

    /**
     * 更新dealProject信息
     * @param type $dealProjectObj
     * @return boolean|null
     */
    public static function updateProjectInfo($dealProjectObj) {
        try {
            $dealProjectObj->save();
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi updateProjectInfo" . "|" . $ex->getMessage());
            $dealProjectObj = null;
        }
        if ($dealProjectObj && $dealProjectObj->id > 0) {
            return $dealProjectObj;
        }
        return false;
    }

    /**
     * 更新dealProject&&Compound(可能)信息
     * @param type $dealProjectObj
     * @return boolean|null
     */
    public static function updateProjectInfoCompound($dealProjectObj, $dealProjectCompoundObj) {
        $db = $dealProjectObj->getDI()->get('firstp2p');
        try {
            $db->begin();
            $dealProjectCompoundObj->projectId = $dealProjectObj->id;
            $dealProjectObj->save();
            $dealProjectCompoundObj->save();
            $db->commit();
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi updateProjectInfoCompound" . "|" . $ex->getMessage());
            $dealProjectObj = null;
            $dealProjectCompoundObj = null;
            $db->rollback();
        }
        if ($dealProjectObj->id > 0 && $dealProjectCompoundObj->id > 0) {
            return $dealProjectObj;
        }
        return false;
    }

    public static function addProjectInfoCompound($name, $userId, $approveNumber, $borrowAmount, $credit, $loanType, $rate, $repayReroid, $projectInfoUrl, $projectExtrainfoUrl, $dealType, $lockPeriod, $redemptionPeriod) {
        $obj = new Firstp2pDealProject();
        $db = $obj->getDI()->get('firstp2p');
        $obj->name = $name;
        $obj->userId = $userId;
        $obj->approveNumber = $approveNumber;
        $obj->borrowAmount = $borrowAmount;
        $obj->credit = $credit;
        $obj->loantype = $loanType;
        $obj->rate = $rate;
        $obj->repayTime = $repayReroid;
        $obj->projectInfoUrl = $projectInfoUrl;
        $obj->projectExtrainfoUrl = empty($projectExtrainfoUrl) ? "" : $projectExtrainfoUrl;
        $obj->intro = '';
        $obj->createTime = time();
        $obj->updateTime = time();
        $obj->dealType = $dealType;
        $obj->cardName = '';
        $obj->bankcard = '';
        $obj->bankzone = '';
        $obj->bankId = 0;

        try {
            $db->begin();
            $obj->save();
            $compoundObj = new \NCFGroup\Ptp\models\Firstp2pDealProjectCompound();
            $compoundObj->lockPeriod = $lockPeriod;
            $compoundObj->redemptionPeriod = $redemptionPeriod;
            $compoundObj->projectId = $obj->id;

            $compoundObj->save();
            $db->commit();
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi addProjectInfoCompound" . "|" . $ex->getMessage());
            $obj = null;
            $compoundObj = null;
            $db->rollback();
        }

        if ($compoundObj->id > 0 && $obj->id > 0) {
            return $obj;
        }
        return false;
    }

    /**
     * 根据id获得dealProject信息
     *
     * @param int $projectId
     * @static
     * @access public
     * @return void
     */
    public static function getProjectById($projectId) {
        try {
            $projectObj = Firstp2pDealProject::findFirst("id='{$projectId}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProject BY_ID" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }

    /**
     * 根据name获得dealProject信息
     * @param string $name
     * @static
     * @access public
     * @return void
     */
    public static function getProjectByName($name) {
        try {
            $projectObj = Firstp2pDealProject::findFirst("name='{$name}'");
        } catch (\Exception $ex) {
            \libs\utils\Logger::debug("openapi getProject BY_NAME" . "|" . $ex->getMessage());
            $projectObj = null;
        }
        return $projectObj;
    }

    public static function getProjectByIds($ids, $columns = '') {
        if (empty($ids)) {
            return array();
        }

        if (empty($columns)) {
            $columns = 'id, name, loantype, repayTime, intro';
        }

        $ids = implode(', ', array_map('intval', $ids));
        return Firstp2pDealProject::find(array(
            'columns'  => $columns,
            'conditions' => "id IN ({$ids})",
        ));
    }

    /**
     * 修改项目的银行卡账号
     * @param int $project_id
     * @param string $bankcard 新的银行卡号
     * @param int $bank_id
     * @param string $bankzone 开户网点
     * @param string $card_name 开户人姓名
     * @param int $card_type 对公对私标识，对私0   对公1
     * @return boolean
     */
    public static function updateBankInfoById($project_id, $bankcard, $bank_id, $bankzone, $card_name ,$card_type = 0,$clearing_type=0)
    {
        try {
            $pro_obj = self::getProjectById($project_id);
            $log_params_info = sprintf('project_id: %d | old_bankcard: %s | new_bankcard: %s', $project_id, $pro_obj->bankcard, $bankcard);
            $pro_obj->bankcard = addslashes($bankcard);
            $pro_obj->bankId = intval($bank_id);
            $pro_obj->bankzone = addslashes($bankzone);
            $pro_obj->cardName = addslashes($card_name);
            $pro_obj->cardType = intval($card_type);

            if($clearing_type > 0){
                $pro_obj->clearingType = $clearing_type;
            }
            if (false === $pro_obj->save()) {
                throw new \Exception('update project-bankcard failed');
            }
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'success', $log_params_info, 'line:' . __LINE__)));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'fail', $log_params_info, $e->getMessage(), 'line:' . __LINE__)));
            return false;
        }
    }
}
