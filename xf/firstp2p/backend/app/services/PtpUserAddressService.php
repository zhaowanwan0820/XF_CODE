<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use libs\common\ErrCode;
use core\service\AddressService;

/**
 * 用户邮寄地址service
 * @uses ServiceBase
 * @package default
 */
class PtpUserAddressService extends ServiceBase {
    /**
     * 获取全部地址列表
     */
    public function getList(SimpleRequestBase $request) {
        $response = new ResponseBase();
        $params = $request->getParamArray();
        if (empty($params['userId'])) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = ErrCode::getCode('ERR_PARAM');
            $response->errorMsg = ErrCode::getMsg('ERR_PARAM');
            return $response;
        }

        $service = new AddressService();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->result = $service->getList((int)$params['userId']);
        return $response;
    }

    /**
     * 获取默认地址
     */
    public function getDefault(SimpleRequestBase $request) {
        $response = new ResponseBase();
        $params = $request->getParamArray();
        if (empty($params['userId'])) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = ErrCode::getCode('ERR_PARAM');
            $response->errorMsg = ErrCode::getMsg('ERR_PARAM');
            return $response;
        }

        $service = new AddressService();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->result = $service->getDefault((int)$params['userId']);
        return $response;
    }

    /**
     * 获取单个地址
     */
    public function getOne(SimpleRequestBase $request) {
        $response = new ResponseBase();
        $params = $request->getParamArray();
        if (empty($params['userId']) || empty($params['id'])) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = ErrCode::getCode('ERR_PARAM');
            $response->errorMsg = ErrCode::getMsg('ERR_PARAM');
            return $response;
        }

        $service = new AddressService();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->result = $service->getOne((int)$params['userId'], (int)$params['id']);
        return $response;
    }

    /**
     * 添加收货地址
     */
    public function addAddress(SimpleRequestBase $request) {
        try{
            $response = new ResponseBase();
            $params = $request->getParamArray();
            if (empty($params['userId']) || empty($params['consignee'])
                 || empty($params['mobile']) || empty($params['area'])
                 || empty($params['address'])) {
                throw new \Exception('ERR_PARAM');
            }

            // 整理参数
            $addressData = [
                'consignee' => addslashes($params['consignee']),
                'mobile' => addslashes($params['mobile']),
                'area' => addslashes($params['area']),
                'address' => addslashes($params['address']),
            ];
            isset($params['isDefault']) && $addressData['is_default'] = intval($params['isDefault']);
            $service = new AddressService();
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->ret = $service->add((int)$params['userId'], $addressData);
        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $errorCode = $response->errorCode = ErrCode::getCode($e->getMessage());
            if ($errorCode == ErrCode::getCode('ERR_UNKNOWN')) {
                $response->errorMsg = $e->getMessage();
            }else{
                $response->errorMsg = ErrCode::getMsg($e->getMessage());
            }
        }
        return $response;
    }

    /**
     * 编辑收货地址
     */
    public function editAddress(SimpleRequestBase $request) {
        try{
            $response = new ResponseBase();
            $params = $request->getParamArray();
            if (empty($params['userId']) || empty($params['id'])
                || empty($params['consignee']) || empty($params['mobile'])
                || empty($params['area']) || empty($params['address'])) {
                throw new \Exception('ERR_PARAM');
            }

            // 整理参数
            $addressData = [
                'consignee' => addslashes($params['consignee']),
                'mobile' => addslashes($params['mobile']),
                'area' => addslashes($params['area']),
                'address' => addslashes($params['address']),
            ];
            isset($params['isDefault']) && $addressData['is_default'] = intval($params['isDefault']);
            $service = new AddressService();
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->ret = $service->update((int)$params['userId'], (int)$params['id'], $addressData);
        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $errorCode = $response->errorCode = ErrCode::getCode($e->getMessage());
            if ($errorCode == ErrCode::getCode('ERR_UNKNOWN')) {
                $response->errorMsg = $e->getMessage();
            }else{
                $response->errorMsg = ErrCode::getMsg($e->getMessage());
            }
        }
        return $response;
    }
}