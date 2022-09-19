<?php

namespace core\service\oto;

use core\service\oto\O2ORpcService;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class O2OStoreRelationService extends O2ORpcService {
    /**
     * 获取零售店人员信息
     */
    public function getStoreRelation($idno, $userType, $isActive = 1) {
        try {
            if (empty($idno)) {
                throw new \Exception('身份证号不能为空');
            }

            $paramArray = array();
            $paramArray['idno'] = $idno;
            $paramArray['userType'] = $userType;
            $paramArray['isActive'] = $isActive;

            $request = new SimpleRequestBase();
            $request->setParamArray($paramArray);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\SupplierStoreRelation', 'getStoreRelation', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__);
        }

        return $response['data'];
    }

    /**
     * 同步零售店管理人员信息
     */
    public function syncStoreRelation($data) {
        try {
            if (empty($data)) {
                throw new \Exception('零售店人员信息不能为空');
            }

            $request = new SimpleRequestBase();
            $request->setParamArray($data);
            $response = $this->requestO2O('\NCFGroup\O2O\Services\SupplierStoreRelation', 'addRelation', $request);
        } catch (\Exception $e) {
            return $this->_handleException($e, __FUNCTION__);
        }

        return $response['ret'];
    }
}