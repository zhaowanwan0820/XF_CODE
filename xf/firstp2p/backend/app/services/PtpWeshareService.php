<?php
namespace NCFGroup\Ptp\services;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Ptp\daos\WeshareDao;
use \NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;

class PtpWeshareService extends ServiceBase {
    /**
     * 获取掌众信息披露信息
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function getWeshareInfoDisclosureInfo($request) {
        $params = $request->getParamArray();
        if (empty($params)) {
            return false;
        }
        $productType = intval($params['productType']);
        $investTerm = intval($params['investTerm']);
        $investUnit = intval($params['investUnit']);
        $resObj = WeshareDao::getWeshareInfoDisclosureInfo($productType,$investTerm,$investUnit);
        if (empty($resObj)) {
            return false;
        }
        $res = $resObj->toArray();
        $response = new ResponseBase();
        $response->id = $res['id'];
        $response->projectType = $res['projectType'];
        $response->productType = $res['productType'];
        $response->investTerm = $res['investTerm'];
        $response->investUnit = $res['investUnit'];
        $response->repayGuaranteeMeasur = $res['repayGuaranteeMeasur'];
        $response->loanUsage = $res['loanUsage'];
        $response->expectIntrerstDate = $res['expectIntrerstDate'];
        $response->limitManage = $res['limitManage'];
        $response->projectRiskTip = $res['projectRiskTip'];
        $response->isEffect = $res['isEffect'];
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }
}