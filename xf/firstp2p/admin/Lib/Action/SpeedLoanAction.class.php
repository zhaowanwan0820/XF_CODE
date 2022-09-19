<?php
/**
 * 网信速贷
 */

use core\service\UserService;
use core\service\speedLoan\ConfigService;
use core\dao\DealLoanTypeModel;

class SpeedLoanAction extends CommonAction{

    public function index(){

    }

    /*
     * 参数配置
     */
    public function settings() {
        $configService = new ConfigService();
        $data = $configService->getConfigColums('config');
        $data['SPEED_LOAN_DEAL_TYPE_DICTIONARY'] = ConfigService::$speedLoanDealType;
        $dealTypeList = DealLoanTypeModel::instance()->getDealTypes(false);
        foreach ($dealTypeList['data'] as $dealType) {
            if (empty($dealType['id'])) {
                continue;
            }
            $data['SPEED_LOAN_PRODUCT_TYPE_DICTIONARY'][$dealType['id']] = $dealType['name'];
        }

        $data['SPEED_LOAN_DEAL_TYPE_ARRY'] = explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_DEAL_TYPE']);
        $data['SPEED_LOAN_PRODUCT_TYPE_ARRY'] = explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_PRODUCT_TYPE']);
        $data['SPEED_LOAN_DEAL_REPAY_TYPE_ARRY'] = explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_DEAL_REPAY_TYPE']);

        $data['SPEED_LOAN_OTHER_DEAL_TYPE_ARRY'] = $data['SPEED_LOAN_OTHER_DEAL_TYPE'] !== '' ? explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_OTHER_DEAL_TYPE']) : [];
        $data['SPEED_LOAN_OTHER_PRODUCT_TYPE_ARRY'] = $data['SPEED_LOAN_OTHER_PRODUCT_TYPE'] !== '' ? explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_OTHER_PRODUCT_TYPE']) : [];
        $data['SPEED_LOAN_OTHER_DEAL_REPAY_TYPE_ARRY'] = $data['SPEED_LOAN_OTHER_DEAL_REPAY_TYPE'] !== '' ? explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_OTHER_DEAL_REPAY_TYPE']) : [];
        $timeStart = explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_SERVICE_HOUR_START']);
        $timeEnd = explode(ConfigService::$configDelimiter, $data['SPEED_LOAN_SERVICE_HOUR_END']);
        $data['SPEED_LOAN_SERVICE_HOUR_START'] = $timeStart[0];
        $data['SPEED_LOAN_SERVICE_MINUTE_START'] = $timeStart[1];
        $data['SPEED_LOAN_SERVICE_HOUR_END'] = $timeEnd[0];
        $data['SPEED_LOAN_SERVICE_MINUTE_END'] = $timeEnd[1];
        $this->assign('settings', $data);
        $this->display();
    }

    /**
     * 更新配置项
     */
    public function updateSetttings() {
        $toUpdate = $_POST;
        foreach ($toUpdate as $configName => $data) {
            $_type = gettype($data);
            switch ($_type){
                case 'string':
                    if ($configName == 'SPEED_LOAN_SERVICE_HOUR_START') {
                        $toUpdate[$configName] = addslashes(trim($data.'：'.$toUpdate['SPEED_LOAN_SERVICE_MINUTE_START']));
                        unset($toUpdate['SPEED_LOAN_SERVICE_MINUTE_START']);
                    } else if ($configName == 'SPEED_LOAN_SERVICE_HOUR_END') {
                        $toUpdate[$configName] = addslashes(trim($data.'：'.$toUpdate['SPEED_LOAN_SERVICE_MINUTE_END']));
                        unset($toUpdate['SPEED_LOAN_SERVICE_MINUTE_END']);
                    } else {
                        $toUpdate[$configName] = addslashes(trim($data));
                    }
                    break;
                case 'array':
                    foreach ($data as $idx => $value) {
                        $data[$idx] = addslashes(trim($value));
                    }
                    $toUpdate[$configName] = implode(ConfigService::$configDelimiter, $data);
                    break;
            }
        }
        if (isset($_POST['SPEED_LOAN_SERVICE_HOUR_START'])) {
            if (intval($_POST['SPEED_LOAN_SERVICE_HOUR_START']) < 0 || intval($_POST['SPEED_LOAN_SERVICE_HOUR_START']) > 23 || intval($_POST['SPEED_LOAN_SERVICE_MINUTE_START']) < 0 || intval($_POST['SPEED_LOAN_SERVICE_MINUTE_START']) > 59) {
                return $this->error('服务开始时间不合法');
            }
            $toUpdate['SPEED_LOAN_SERVICE_HOUR_START'] = addslashes(trim($_POST['SPEED_LOAN_SERVICE_HOUR_START'].ConfigService::$configDelimiter.$toUpdate['SPEED_LOAN_SERVICE_MINUTE_START']));
            unset($toUpdate['SPEED_LOAN_SERVICE_MINUTE_START']);
        }
        if (isset($_POST['SPEED_LOAN_SERVICE_HOUR_END'])) {
            if (intval($_POST['SPEED_LOAN_SERVICE_HOUR_END']) < 0 || intval($_POST['SPEED_LOAN_SERVICE_HOUR_END']) > 23 || intval($_POST['SPEED_LOAN_SERVICE_MINUTE_START']) < 0 || intval($_POST['SPEED_LOAN_SERVICE_MINUTE_END']) > 59) {
                return $this->error('服务结束时间不合法');
            }
            $toUpdate['SPEED_LOAN_SERVICE_HOUR_END'] = addslashes(trim($_POST['SPEED_LOAN_SERVICE_HOUR_END'].ConfigService::$configDelimiter.$toUpdate['SPEED_LOAN_SERVICE_MINUTE_END']));
            unset($toUpdate['SPEED_LOAN_SERVICE_MINUTE_END']);
        }
        if (bccomp($toUpdate['SPEED_LOAN_SERVICE_RATE'], bcadd($toUpdate['SPEED_LOAN_SERVICE_FEE_STEP_ONE'], $toUpdate['SPEED_LOAN_SERVICE_FEE_STEP_TWO'], 4), 4) != 0) {
            return $this->error('平台服务费率错误，必须要满足: 平台服务费率(总) = 平台服务费率(第一段) + 平台服务费率(第二段)');
        }
        if (empty($toUpdate['SPEED_LOAN_MORTGAGE_RATE']) || bccomp($toUpdate['SPEED_LOAN_MORTGAGE_RATE'], 0.1, 4) === -1) {
            return $this->error('抵押物质押率错误，不能小于0.1%');
        }

        //初始化为空的配置项
        foreach(ConfigService::$canEmptyConfigKeys['config'] as $key) {
            if (!isset($toUpdate[$key])) {
                $toUpdate[$key] = '';
            }
        }

        $speedLoanConfigService = new ConfigService();
        $result = $speedLoanConfigService->updateSetttings($toUpdate);
        if ($result) {
            return $this->success('更新成功！');
        } else {
            return $this->error('更新失败！');
        }
    }
}
