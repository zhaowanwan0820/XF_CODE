<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\Page;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\daos\RegionDAO;
use NCFGroup\Protos\Ptp\RequestRegionList;
use NCFGroup\Protos\Ptp\RequestRegionBankList;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\BankService;
use core\service\BanklistService;
use openapi\lib\PinYin;

/**
 * PtpBankService
 *
 * @uses ServiceBase
 * @package default
 */
class PtpBankService extends ServiceBase {

    /**
     * 获取地区列表
     * @param \NCFGroup\Protos\Ptp\RequestRegionList $request
     * @return boolean
     */
    public function getRegionList(RequestRegionList $request) {
        $ret = RegionDAO::getRegionList($request->getRegionLevel());
        if (!is_array($ret) || count($ret) < 1) {
            return false;
        }
        $py = new PinYin();
        foreach ($ret as $key => $value) {
            $ret[$key]['pinyin'] = $py->getAllPY($value['name']);
        }
        return $ret;
    }

    /**
     * 获取银行列表
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return boolean
     */
    public function getBankList(SimpleRequestBase $request) {
        $bank_list = (new BankService())->getBankUserByStatus(array('0'));

        if (!is_array($bank_list) && count($bank_list) < 1) {
            return false;
        }
        $ret = array();
        foreach ($bank_list as $key => $value) {
            $ret[$key]['id'] = $value->id;
            $ret[$key]['name'] = $value->name;
            $ret[$key]['short_name'] = $value->short_name;
        }
        return $ret;
    }

    /**
     * 获取地区银行列表
     * @param \NCFGroup\Protos\Ptp\RequestRegionBankList $request
     * @return boolean
     */
    public function getRegionBankList(RequestRegionBankList $request) {
        $regionBanklist = (new BanklistService())->getBanklist($request->getCity(), $request->getProvince(), $request->getBank());
        if (!is_array($regionBanklist) && count($regionBanklist) < 1) {
            return false;
        }
        return $regionBanklist;
    }

    /**网信支持的银行列表
     * 获取用户信息
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function getBankListFund() {
        $banks_ret = (new BankService())->getBankUserByPaymentMethod();
        $banks = array();
        if (!$banks_ret) {
            throw new \Exception('获取失败！');
        }
        foreach ($banks_ret as $ret) {
            $banks[] = array(
                'id' => $ret->id,
                'name' => $ret->name,
                'deposit' => $ret->deposit,
                'short_name'=> $ret->short_name,
                'img' => $ret->img,
                'sort' => $ret->sort,
                'adminid' => $ret->adminid,
            );
        }
        $response = new ResponseBase();
        $response->banklist = $banks;
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }

    /**
     * 通过银行编码获取银行信息
     * @param SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase
     */
    public function getBankByCode(SimpleRequestBase $request) {
        $response = new ResponseBase();
        $params = $request->getParamArray();
        if (empty($params['shortName'])) {
            $response->msg = '参数错误或不合法';
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        $obj = new BankService();
        $bankInfo = $obj->getBankByCode($params['shortName']);
        if (empty($bankInfo)) {
            $response->msg = '获取银行信息失败';
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }
        $response->bankInfo = $bankInfo->getRow();
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }
}