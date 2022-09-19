<?php

error_reporting(E_ALL);
ini_set('memory_limit', '2048M');

require(dirname(dirname(__DIR__)) . '/app/init.php');

class AddAdunionDeal {

    public function run() {
        $objAdunionDealService = new \core\service\AdunionDealService();
        $strFileName = sprintf('%s/%s', __DIR__, '/json.txt');
        $resHandle = fopen($strFileName, 'r');

        while (!feof($resHandle)) {
            $strLine = trim(fgets($resHandle));
            if (empty($strLine)) {
                continue;
            }

            $arrLine = json_decode($strLine, true);
            if (!empty($arrLine['invite_code']) || !empty($arrLine['euid'])) {
                $objAdunionDealService->addAdRecord($arrLine);
            }
        }

        fclose($resHandle);
    }

}

$objAddAdunionDeal = new AddAdunionDeal();
$objAddAdunionDeal->run();
