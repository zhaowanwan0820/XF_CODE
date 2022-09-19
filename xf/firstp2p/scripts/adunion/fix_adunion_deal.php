<?php

require(dirname(dirname(__DIR__)) . '/app/init.php');

use libs\utils\Logger;
use core\dao\AdunionDealModel;

error_reporting(E_ALL);
ini_set('memory_limit', '2048M');

class FixAdunionDeal {

    protected $_shortAlias = '';
    protected $_inputFile  = '';

    protected $_inputData = [];
    protected $_adunionInfo = [];
    protected $_userInfo  = [];

    // 下面的数据修复时候需要修改
    public function __construct() {
        $this->_shortAlias = 'FNYJ7P'; // 网贷之家数据修复
        $this->_inputFile  = sprintf('%s/%s', __DIR__, '/data.txt');
        Logger::info(sprintf("开始修复邀请码%s的广告联盟数据", $this->_shortAlias));
    }

    private function _parseInputFile() {
        if (!file_exists($this->_inputFile)) {
            throw new \Exception(sprintf("找不到要处理的文件: %s", $this->_inputFile));
        }

        $handle = fopen($this->_inputFile, 'r');
        while (!feof($handle)) {
            $line = trim(fgets($handle));
            if (!empty($line)) {
                $row = array_map('trim', explode(' ', $line));
                $uid = de32Tonum($row[0]);
                if ($uid <= 0) {
                    echo sprintf("%s 转换成用户id错误%s", $row[0], PHP_EOL);
                    continue;
                }
                if (empty($row[1])) {
                    echo sprintf("%s 输入的euid为空%s", $row[0], PHP_EOL);
                    continue;
                }
                $this->_inputData[$uid] = $row;
            }
        }
        fclose($handle);

        if (empty($this->_inputData)) {
            throw new \Exception(sprintf("文件%s中没有要处理的数据", $this->_inputFile));
        }
    }

    private function _getAdunionInfo() {
        $query = sprintf("SELECT id, uid, euid FROM firstp2p_adunion_deal WHERE is_new_custom = 1 AND uid IN(%s)", implode(',', array_keys($this->_inputData)));
        $result = $GLOBALS['db']->getAll($query);
        foreach ($result as $item) {
            $this->_adunionInfo[$item['uid']] = $item;
        }
    }

    private function _getUserInfo() {
        $query = sprintf("SELECT id, create_time FROM firstp2p_user WHERE id IN(%s)", implode(',', array_keys($this->_inputData)));
        $result = $GLOBALS['db']->getAll($query);
        foreach ($result as $item) {
            $this->_userInfo[$item['id']] = $item;
        }
    }

    private function _fixAduinonData() {
        $data = [
            'cn' => $this->_shortAlias,
            'goods_cn' => $this->_shortAlias,
            'goods_type' => 1,
            'track_id' => 0,
            'is_new_custom' => 1,
            'status' => '注册',
        ];

        foreach ($this->_inputData as $userId => $item) {
            $data['euid'] = $item[1];
            $data['uid'] = $userId;
            $data['updated_at'] = $data['order_time'] = date("Y-m-d H:i:s", $this->_userInfo[$userId]['create_time']);

            if (isset($this->_adunionInfo[$userId])) {
                $result = AdunionDealModel::instance()->update_order($data, $this->_adunionInfo[$userId]['id']);
            } else {
                $result = AdunionDealModel::instance()->update_order($data);
            }

            if (empty($result)) {
                echo sprintf("%s 修复数据失败%s", $item[0], PHP_EOL);
            }
        }
    }

    public function run() {
        $this->_parseInputFile();
        $this->_getAdunionInfo();
        $this->_getUserInfo();
        $this->_fixAduinonData();
    }

}

$fixAdunionDeal = new FixAdunionDeal();
$fixAdunionDeal->run();
