<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class StockCodeType extends AbstractEnum
{
    const STOCK = 0;// A股票
    const FUTURES = 1; //期货
    const OPTION = 2;//期权
    const FOREIGN_EXCHANGE = 3;//外汇
    const INDEX = 4; //指数
    const FUND = 5;//基金   //场内基金
    const BOND = 6;//债券
    const WARRANT = 7;//认购权证
    const PUT_WARRANT = 8;//认沽权证
    const CATTLE_PERMIT = 9;//牛证
    const BEAR_PERMIT = 10;//熊证
    const OTHERS = 11;//其他
    const STOCK_PLATE = 12;//板块

    protected static $details = array(
            StockCodeType::STOCK => '股票',
            StockCodeType::FUTURES => '期货',
            StockCodeType::OPTION => '期权',
            StockCodeType::FOREIGN_EXCHANGE => '外汇',
            StockCodeType::INDEX => '指数',
            StockCodeType::FUND => '基金',
            StockCodeType::BOND => '债券',
            StockCodeType::WARRANT => '认购权证',
            StockCodeType::PUT_WARRANT => '认沽权证',
            StockCodeType::CATTLE_PERMIT => '牛证',
            StockCodeType::BEAR_PERMIT => '熊证',
            StockCodeType::OTHERS => '其他',
            StockCodeType::STOCK_PLATE => '板块',
        );
    public static function getTypeMap()
    {
       return self::$details;
    }
   /*
    * 通过股票代码获取股票类型
    * @param string $code 股票代码
    * @param string $exchange 交易所 ('SZ'表示深交所,'SH'表示上交所，不区分大小写)
    */
    public static function getStockTypeByCode($code,$exchange)
    {
       $ex = strtoupper($exchange);
       $codeType = StockCodeType::OTHERS;
       if($ex == 'SZ') {
           $codeType = self::getSzStockType($code);
       } else if($ex == 'SH'){
           $codeType = self::getShStockType($code);
       }
       return $codeType;
    }


   /**
    * 区分深圳交易所的股票类型
    */
    protected static function getSzStockType($code)
    {
        $scdm = '';
        $baseType = '';

        $firstOne = substr($code,0,1);
        $firstTwo = substr($code,0,2);
        $firstThree = substr($code,0,3);
        if(strcmp($firstTwo,'39') == 0) {
            $scdm = 'zs';
            $baseType = 'zs';
        } else if(strcmp($firstThree,'000') == 0 || strcmp($firstThree,'001') == 0) {
            $scdm = 'sza';
            $baseType = 'a';
        } else if(strcmp($firstThree,'002') >= 0 && strcmp($firstThree,'004') <= 0) {
            $scdm = 'zxqy';
            $baseType = 'a';
        } else if(strcmp($firstOne,'2') == 0) {
            $scdm = 'szb';
            $baseType = 'b';
        } else if(strcmp($firstTwo,'03') == 0 || strcmp($firstTwo,'08') == 0) {
            $scdm = 'qz';
            $baseType = 'qz';
        } else if(strcmp($firstTwo,'10') >= 0 && strcmp($firstTwo,'13')<= 0) {
            $scdm = 'zq';
            if(strcmp($firstTwo,'11') == 0 || strcmp($firstTwo,'12') == 0) {
                $baseType = 'zq';
            } else {
                $baseType = 'gz';
            }
        } else if(strcmp($firstTwo,'15') == 0 || strcmp($firstTwo,'16') == 0) {
            $scdm = 'kfsjj';
            $baseType ='jj';
        } else if(strcmp($firstTwo,'18') == 0) {
            $scdm = 'fbsjj';
            $baseType = 'jj';
        } else if(strcmp($firstTwo,'40') == 0) {
            $scdm = 'sanban';
            $baseType = 'sanban';
        } else if(strcmp($firstTwo,'42') == 0) {
            $scdm = 'sanbanusd';
            $baseType = 'sanbanusd';
        } else if(strcmp($firstTwo,'43') == 0 || strcmp($firstTwo,'83') == 0) {
            $scdm = 'gpgs';
            $baseType = 'gpgs';
        } else if(strcmp($firstTwo,'85') == 0) {
            $scdm = 'gqjlqq';
            $baseType = 'gqjlqq';
        } else if(strcmp($firstTwo,'36') == 0) {
            $scdm = 'other';
            $baseType = 'gndm';
        } else if(strcmp($firstTwo,'30') == 0) {
            $scdm = 'cyb';
            $baseType = 'a';
        } else {
            $scdm = 'other';
            $baseType = 'other';
        }
        return self::getStockType($scdm,$baseType);
    }
    /**
     *区分上海交易所的股票类型
     */
    protected static function getShStockType($code)
    {
        $scdm = '';
        $baseType = '';

        $firstOne = substr($code,0,1);
        $firstTwo = substr($code,0,2);
        $firstThree = substr($code,0,3);
        if(strcmp($firstThree,'000') == 0 || strcmp($firstThree,'999') == 0) {
            $scdm = 'zs';
            $baseType = 'zs';
        } else if(strcmp($firstThree,'600') >= 0 && strcmp($firstThree,'609') <= 0 ) {
            $scdm = 'sha';
            $baseType = 'a';
        } else if(strcmp($firstThree,'900') == 0) {
            $scdm = 'shb';
            $baseType = 'b';
        } else if(strcmp($firstTwo,'58') == 0) {
            $scdm = 'qz';
            $baseType = 'qz';
        } else if(strcmp($firstThree,'009') == 0 || strcmp($firstTwo,'01') == 0 || strcmp($firstOne,'2') == 0) {
            $scdm = 'zq';
            $baseType = 'gz';
        } else if(strcmp($firstTwo,'10') == 0 || strcmp($firstTwo,'11') == 0) {
            $scdm = 'zq';
            $baseType = 'kzz';
        } else if(strcmp($firstThree,'12') == 0) {
            $scdm = 'zq';
            $baseType = 'zq';
        } else if(strcmp($firstTwo,'51') == 0 || strcmp($firstTwo,'52') == 0) {
            $scdm = 'kfsjj';
            $baseType = 'jj';
        } else if(strcmp($firstThree,'500') == 0) {
            $scdm = 'fbsjj';
            $baseType = 'jj';
        } else if(strcmp($firstTwo,'83') == 0 || strcmp($firstTwo,'88') == 0) {
            $scdm = 'dsfzs';
            $baseType = 'dsfzs';
        } else if(strcmp($firstTwo,'09') == 0 || strcmp($firstThree,'181') == 0 || strcmp($firstThree,'190') == 0 || $code == '939988') {
            $scdm = 'other';
            $baseType = 'gndm';
        } else if(strcmp($firstThree,'609') == 0) {
            $scdm = 'other';
            $baseType = 'tbdm';
        } else if(strcmp(substr($code,0,4),'7518') == 0) {
            $scdm = 'gzyfx';
            $baseType = 'gzyfx';
        } else {
            $scdm = 'other';
            $baseType = 'other';
        }
        return self::getStockType($scdm,$baseType);
    }
    /*
     * 获取股票类型
     */
    protected static function getStockType($scdm,$baseType)
    {
        $codeType = StockCodeType::OTHERS;
        if($scdm == 'zs') {
            $codeType = StockCodeType::INDEX;
        } else if($scdm == 'zq') {
            $codeType = StockCodeType::BOND;
        } else if($scdm == 'kfsjj' || $scdm == 'fbsjj') {
            $codeType = StockCodeType::FUND;
        } else if($baseType == 'a') {
            $codeType = StockCodeType::STOCK;
        } else if($baseType == 'b') {
            $codeType = StockCodeType::B_STOCK;
        }
        return $codeType;
    }

}
