<?php

class HandleChanel
{
    protected $channel;

    /**
     * 初始化项目渠道.
     *
     * @param $product_type
     *
     * @throws Exception
     */
    public function initHandel($product_type)
    {
        $config = [];
        switch ($product_type) {
            case 1://尊享
                $this->channel = new HandleZXData($config);
                break;
            case 2://普惠
                $this->channel = new HandlePHData($config);
                break;
            case 3://金融工厂
                $this->channel = new HandleJRGCData($config);
                break;
            case 4://智多新
                $this->channel = new HandleZDXData($config);
                break;
            case 5://交易所
                $this->channel = new HandleJYSData($config);
                break;
//            case 6://东方红
//                $this->channel = new HandleDFHData($config);
//                break;
//            case 7://中国龙
//                $this->channel = new HandleZGLData($config);
                break;
            default:
                throw new Exception('当前仅支持：金融工厂 智多新 交易所');
        }
    }

    /**
     * 根据导入文件，处理生成相关数据.
     *
     * @param $import
     *
     * @return BaseHandleOfflineData
     *
     * @throws Exception
     */
    public function handleImportData($import)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }

        try {
            return $this->channel->handle($import);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handleImportFileData($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->getImportFileList($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handleUploadRepayFileData($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->getUploadRepayFileList($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handelRepayData($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->handelCollectionInfo($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handleUserAccountData($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->handleUserAccount($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handelImportFileAuth($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            $this->channel->handelImportFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function handelUploadRepayFileAuth($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            $this->channel->handelUploadRepayFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function handelUploadUserAccountFileAuth($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            $this->channel->handelUploadUserAccountFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getRepayLogList($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->getRepayLogList($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getUserAccountFileList($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->getUserAccountFileList($params);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 用户侧获取合同列表.
     *
     * @param $params
     *
     * @return array
     *
     * @throws Exception
     */
    public function getContractList($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->checkContractNeedParams($params)->getContractList();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 用户侧获取合同详情.
     *
     * @param $params
     *
     * @return array
     *
     * @throws Exception
     */
    public function getContractInfo($params)
    {
        if (!$this->channel instanceof BaseHandleOfflineData) {
            throw new Exception('初始化渠道类型失败 '.__LINE__);
        }
        try {
            return $this->channel->checkContractNeedParams($params)->getContractInfo();
        } catch (Exception $e) {
            throw $e;
        }
    }
}

/**
 * 导入数据 对外调用类
 * Class HandelOfflineDataService.
 */
class HandleOfflineDataService
{
    protected static $instance;

    protected static function getInstance($product_type)
    {
        if (is_null(self::$instance)) {
            static::$instance = new HandleChanel();
        }
    
        try {
            static::$instance->initHandel($product_type);
        } catch (Exception $e) {
            throw $e;
        }

        return static::$instance;
    }

    /**
     * 脚本处理出借记录.
     *
     * @param $product_type
     * @param $import
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function tenderRun($product_type, $import)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handleImportData($import);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 脚本处理还款计划.
     *
     * @param $product_type
     * @param $import
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function repayRun($product_type, $import)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handelRepayData($import);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 处理用户账户数据导入.
     *
     * @param $product_type
     * @param $import
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function accountRun($product_type, $import)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handleUserAccountData($import);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 获取出借记录导入文件列表.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function importFileList($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handleImportFileData($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 获取出借记录导入文件列表.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function uploadUserAccountFileList($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->getUserAccountFileList($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 获取用户账户导入文件列表.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function uploadRepayFileList($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handleUploadRepayFileData($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 审核出借记录导入文件.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function authImportFile($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handelImportFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 审核出还款计划导入文件.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function authUploadRepayFile($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handelUploadRepayFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 审核用户账户导入文件.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function authUserAccountFile($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->handelUploadUserAccountFileAuth($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 还款计划导入明细列表.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function getRepayLogList($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->getRepayLogList($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }




    /************用户侧调用接口*******************/

    /**
     * 合同列表.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function getContractList($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->getContractList($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * 合同详情.
     *
     * @param $product_type
     * @param $params
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function getContractInfo($product_type, $params)
    {
        try {
            $instance = self::getInstance($product_type);
            $ret = $instance->getContractInfo($params);
        } catch (Exception $e) {
            throw $e;
        }

        return $ret;
    }
}
