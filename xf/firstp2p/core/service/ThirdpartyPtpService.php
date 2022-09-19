<?php
namespace core\service;

/**
 * 第三方网贷平台Service
 * @author longbo
 */
use core\dao\AuthUserModel;
use core\dao\ThirdConfigModel;
use libs\db\MysqlDb;
use libs\utils\Logger;
use libs\utils\Alarm;
use core\service\partner\RequestService;
use core\service\UserService;

class ThirdpartyPtpService extends BaseService
{

    const UNAUTH = 0; //未授权
    const ISAUTH = 1; //已授权
    const HASACCOUNT = 2; //已开户
    const HASASSETS = 3; //有资产

    const DEALS_KEY = 'third_deal_list_'; //标的缓存key前

    //获取标的
    public function dealList($platform)
    {
        $key = self::DEALS_KEY.$platform;
        try {
            if ($redis = \SiteApp::init()->dataCache->getRedisInstance()) {
                $cache = $redis->get($key);
                $result = json_decode($cache, true);
                if (is_array($result)) {
                    return $result;
                }
            }
        } catch (\Exception $e){
            Logger::error("redis cache read error.".$e->getMessage());
        }

        $config = $this->getConfig($platform);

        if (empty($config) || empty($config['is_deals'])) {
            return [];
        }

        $count = $this->getExt($config, 'ProjectNum', 3);
        $result = [];
        try {
            $result = RequestService::init($platform)
                ->setApi('deals.list')
                ->setPost(['count' => $count])
                ->request();
        } catch (\Exception $e) {
            Alarm::push('third_deallist', "第三方标的获取失败", "{$platform}列表:".$e->getMessage());
        }

        if ($result) {
            array_walk($result, function (&$value) {
                $value['repayment'] = isset($GLOBALS['dict']['LOAN_TYPE_CN'][$value['repayment']]) ?
                    $GLOBALS['dict']['LOAN_TYPE_CN'][$value['repayment']] : '';
            });
        }

        if (is_object($redis)) {
            try {
                $time = intval($this->getExt($config, 'ProjectCache', 60));
                $redis->setEx($key, $time, json_encode($result));
            } catch (\Exception $e) {
                Logger::error("redis cache write error.".$e->getMessage());
            }
        }

        return $result;
    }


    //是否已授权
    public function isOauthUser($platform = '', $userId = '')
    {
        $config = $this->getConfig($platform);
        if (!empty($config['client_id']) && $userId) {
            return AuthUserModel::instance()->isExist(trim($config['client_id']), $userId);
        }
        return false;
    }

    //用户状态
    public function userStatus($platform = '', $userId = '')
    {
        $isOauth = $this->isOauthUser($platform, $userId);
        if (!$isOauth) {
            return self::UNAUTH;
        }
        $status = self::ISAUTH;
        try {
            $assets = RequestService::init(trim($platform))
                ->setApi('user.asset')
                ->setPost(['open_id' => $userId])
                ->request();
            if (!empty($assets)) {
                $status = self::HASACCOUNT;
                if (isset($assets['total']) && $assets['total'] > 0) {
                    $status = self::HASASSETS;
                }
            }
        } catch (\Exception $e) {
            Logger::info($platform.' get user assets failed:'.$e->getMessage());
        }

        return $status;
    }

    //获取第三方平台配置信息
    public function getPlatform($userId)
    {
        $configs = $this->handleConfigs($userId);
        if (empty($configs)) {
            return [];
        }
        $isInvested = (new UserService())->hasLoan($userId);
        if (!$isInvested) {
            return [];
        }
        return $this->_configFmt($configs);
    }

    //获取第三方平台URL信息
    public function getUrls($userId)
    {
        $configs = $this->handleConfigs($userId);
        $resUrls = [];
        foreach($configs as $config) {
            if ($config['user_status'] == self::HASASSETS
                || ($config['is_show'] == 1 && $config['user_status'] >= self::HASACCOUNT)
            ) {
                $urls = [];
                $urls['platform'] = trim($config['key']);
                $urls['withdrawUrl'] = trim($config['withdraw_url']);
                $urls['rechargeUrl'] = trim($config['recharge_url']);
                $urls['myloanUrl'] = trim($config['myloan_url']);
                $resUrls[] = $urls;
            }
        }
        return $resUrls;
    }

    //处理配置
    public function handleConfigs($userId = '')
    {
        $configs = ThirdConfigModel::instance()->getAll();
        $resConfigs = [];
        foreach ($configs as $config) {
            if (empty($config['key']) || empty($config['name']) || empty($config['icon'])) {
                continue;
            }
            $config['is_show'] = 1;
            $config['user_status'] = $this->userStatus($config['key'], $userId);
            if ($config['user_status'] == self::UNAUTH) {
                $config['is_assets'] = 0;
            }

            $isShow = true;
            if (empty($config['enable'])) {
                $isShow = false;
            } elseif (!empty($config['whitelist'])) {
                $bwlistService = new BwlistService();
                $isInList = $bwlistService->inList($config['whitelist'], $userId);
                if (!$isInList) {
                    $isShow = false;
                }
            }

            if (!$isShow) {
                $config['is_show'] = 0;
                $config['is_deals'] = 0;
                if ($config['user_status'] < self::HASASSETS) {
                    $config['is_assets'] = 0;
                }
            }

            $config['more_url'] = '';
            if (!empty($config['is_deals'])) {
                $config['more_url'] = $this->getExt($config, 'DealMoreUrl', '');
            }

            $resConfigs[] = $config;
        }

        return $resConfigs;
    }

    private function _configFmt($configs)
    {
        return array_map(function ($config) {
            $newConfig = [];
            $newConfig['platform'] = trim($config['key']);
            $newConfig['needAssets'] = intval($config['is_assets']);
            $newConfig['needDeal'] = intval($config['is_deals']);
            $newConfig['name'] = trim($config['name']);
            $newConfig['icon'] = 'https:'.trim($config['icon']);
            $newConfig['marketUrl'] = trim($config['index_url']);
            $newConfig['isShow'] = intval($config['is_show']);
            $newConfig['userStatus'] = $config['user_status'];
            $newConfig['dealMoreUrl'] = trim($config['more_url']);
            return $newConfig;
        }, $configs);
    }

    //获取扩展配置key的值,获取不到取default
    public function getExt($config, $key = '', $default = '')
    {
        if (!empty($config['extra'])) {
            $extra = json_decode($config['extra'], true);
            if (isset($extra[$key])) {
                return $extra[$key];
            }
        }
        return $default;
    }

    //获取单个配置
    public function getConfig($key = '')
    {
        return ThirdConfigModel::instance()->getOne($key);
    }


}


