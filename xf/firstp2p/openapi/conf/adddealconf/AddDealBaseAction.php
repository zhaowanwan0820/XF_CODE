<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/16
 * Time: 10:47
 */
namespace openapi\conf\adddealconf;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\utils\Alarm;
class AddDealBaseAction {

    /**
     * 处理金额小数点
     * @param $money
     * @return string
     */
    function getMoney($money) {
        return bcadd($money, 0, 2);
    }

    /**
     * 获取递增的项目名称
     * @param $key
     * @return mixed
     */
    function getProjectIncrNo($key) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        return $redis->incr($key);
    }
    /**
     * @param $productType 产品类型如信石借款，功夫贷等1-闪电借款 2-信石借款 3-功夫贷
     * @param $investTerm 产品借款时间
     * @param int $investUnit 产品借款时间单位，信石借款默认为1（单位为天） 2-月
     * *@param $file 对应的模板
     * @return bool|mixed
     */
    function getDisclosureInfo ($productType,$investTerm,$investUnit =1,$params,$file){
        $data = array(
            'errorCode' =>  0,
            'errorMsg' => '',
            'data' => '',
        );
        //获取后天配置的信息披露数据 start
        $request = new SimpleRequestBase();
        $paramsArray = array(
            'productType' => $productType, //产品类型
            'investTerm' => $investTerm, //产品借款期限
            'investUnit' => $investUnit, //现在只支持天
        );
        $request->setParamArray($paramsArray);
        $result = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpWeshare',
            'method' => 'getWeshareInfoDisclosureInfo',
            'args' => $request
        ));
        if (empty($result) || $result->resCode != 0) {
            $data['errorCode'] = 1;
            $data['errorMsg'] = '后台没有相匹配的信息披露';
            return $data;
        }
        if ($result->isEffect != 1) {
            $data['errorCode'] = 1;
            $data['errorMsg'] = '后台没有对应有效的信息披露';
            return $data;
        }
        $resInfo = $result->toArray();
        unset($resInfo['resCode']);
        if (in_array('', $resInfo)) {
            $data['errorCode'] = 1;
            $data['errorMsg'] = '对应的后台信息披露信息有空值';
            return $data;
        }
        if (empty($params['financAdd'])) {
            $data['errorCode'] = 1;
            $data['errorMsg'] = 'financAdd不能为空';
            return $data;
        }
        //项目简介拼接
        if ($resInfo['loanUsage'] == '<p>日常消费（融资方保证按照借款用途使用资金）</p>') {
            $loanUsageRep = '日常消费'; //跟产品确认，如果是默认值，则取日常消费，其他不变
        } else {
            $loanUsageRep = str_replace('<p>', '', $resInfo['loanUsage']);
        }
        $productDescInfo = '融资方于' . date("Y") . '年' . date("m") . '月' . date("d") . '日' . '在' . $params['financAdd'] . '申请融资用于' . $loanUsageRep;
        //读取模板内容
        if (empty($file)) {
            Alarm::push('ANGLI', '模板问题导致上标失败', '模板不能为空');
            $data['errorCode'] = 1;
            $data['errorMsg'] = '模板不能为空';
            return $data;
        }
        $idnoFormat = substr($params['idno'], 0, 3) . '******' . substr($params['idno'], -3); //与产品确认规则去前三后三中间6个星号
        $file = str_replace("\n", "", $file);
        $file = str_replace('${productDesc}', $productDescInfo, $file);
        $file = str_replace('${repayGuaranteeMeasur}', $resInfo['repayGuaranteeMeasur'], $file);
        $file = str_replace('${loanUsage}', $resInfo['loanUsage'], $file);
        $file = str_replace('${expectIntrerstDate}', $resInfo['expectIntrerstDate'], $file);
        $file = str_replace('${limitManage}', $resInfo['limitManage'], $file);
        $file = str_replace('${name}', nameFormat($params['realName']), $file);
        $file = str_replace('${idno}', $idnoFormat, $file);
        $file = str_replace('${overCount}', '0次', $file);
        $file = str_replace('${overMoney}', '0元', $file);
        $file = str_replace('${projectRiskTip}', $resInfo['projectRiskTip'], $file);

        $projectExtrainfoUrl = '';
        $data['data'] = $file;
        //获取后天配置的信息披露数据 end
        return $data;
    }
}