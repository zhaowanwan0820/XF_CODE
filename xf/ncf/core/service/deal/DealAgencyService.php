<?php
/**
 * 机构
 **/

namespace core\service\deal;

use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\agency\AgencyImageService;
use core\dao\deal\DealAgencyModel;

use core\enum\DealAgencyEnum;

class DealAgencyService extends BaseService {

    /**
     * 获取担保公司
     *
     * @param $id 担保公司id
     */
    public function getDealAgency($id) {
        $id = intval($id);
        if($id <= 0){
            return false;
        }
        $agency_info = DealAgencyModel::instance()->findViaSlave($id);
        if($agency_info){
            //代理人用户信息
            $agency_user_info = UserService::getUserByCondition("id={$agency_info['agency_user_id']}",'user_name,idno,real_name');
            if($agency_user_info){
                $agency_info['agency_agent_real_name'] = $agency_user_info['real_name'];//担保公司代理人真实姓名
                $agency_info['agency_agent_user_name'] = $agency_user_info['user_name'];//担保公司代理人网信理财网站用户名
                $agency_info['agency_agent_user_idno'] = $agency_user_info['idno'];//担保公司代理人身份证号
            }

            //机构银行卡信息 取 关联用户信息
            $bankcard_info = BankService::getNewCardByUserId($agency_info['user_id']);
            if($bankcard_info){
                $bank_info = BankService::getBankInfoByBankId($bankcard_info['bank_id'], 'name');
                if($bank_info){
                    $agency_info['bankzone'] = $bank_info['name'].$bankcard_info['bankzone'];
                }

                $agency_info['card_name'] = $bankcard_info['card_name'];
                $agency_info['bankcard'] = $bankcard_info['bankcard'];
            }

            // 获取机构相关的图片信息 by fanjingwen@
            $agencyImgSer = new AgencyImageService();
            $imgArr = $agencyImgSer->getAgencyImages($agency_info->id);
            foreach ($imgArr as $key => $value) {
                $agency_info->$key = $value;
            }
        }

        return $agency_info;
    }

    /**
     * 根据产品类型获取借款类型标识
     * @param $type_id int
     * @return string
     */
    public function getDealAgencyById($id) {
        if(empty($id) || !is_numeric($id) || ($id < 0)){
            return false;
        }
        return DealAgencyModel::instance()->getDealAgencyById($id);
    }

    /**
     * 获取默认的支付机构id
     * @return int
     */
    public function getUcfPayAgencyId() {
        return DealAgencyModel::instance()->getUcfPayAgencyId();
    }

    public function getDealAgencyBySiteId($site_id) {
        $site_id = intval($site_id);
        $condition = "site_id = '$site_id' AND is_icp = '1'";
        $result = DealAgencyModel::instance()->findByViaSlave($condition);
        //如果平台方没有独立icp，则返回firstp2p默认平台方信息.
        if($result == null){
            $condition = "site_id = '1' AND is_icp = '1'";
            $result = DealAgencyModel::instance()->findByViaSlave($condition);
        }
        return $result;
    }

    /**
     * 获取某个用户是否是担保公司用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserAgencyInfoNew($user_info){
        //获取汇赢(HY)担保帐号
        \FP::import("libs.common.dict");
        $hydb_arr = \dict::get('HY_DB');

        $agency_info = array();
        if(in_array($user_info['user_name'], $hydb_arr)){
            $agency_info = array(
                'is_hy' => 1,
                'user_id' => $user_info['id'],
                'id' => $GLOBALS['dict']['HY_DBGS'], //agency_id,改成id是为了方便吐出的数据结构一致
                'user_name' => $user_info['user_name'],
            );
        }else{
            //判断用户是否担保公司帐号
            $user_agency = DealAgencyModel::instance()->getAgencyByAgencyUserId($user_info['id'], DealAgencyEnum::TYPE_GUARANTEE);
            if($user_agency){
                $agency_info = $user_agency;
                $agency_info['is_hy'] = 0;
            }
        }
        return array('agency_info' => $agency_info, 'is_agency' => empty($agency_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是资产管理方用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserAdvisoryInfo($user_info){
        $advisory_info = array();
        $user_advisory = DealAgencyModel::instance()->getAgencyByAgencyUserId($user_info['id'],DealAgencyEnum::TYPE_CONSULT);
        if($user_advisory){
            $advisory_info = $user_advisory;
        }
        return array('advisory_info' => $advisory_info, 'is_advisory' => empty($advisory_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是渠道方，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserCanalInfo($user_info){
        $canal_info = array();
        $user_canal = DealAgencyModel::instance()->getAgencyByAgencyUserId($user_info['id'],DealAgencyEnum::TYPE_CANAL);
        if($user_canal){
            $canal_info = $user_canal;
        }
        return array('canal_info' => $canal_info, 'is_canal' => empty($canal_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是委托机构用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserEntrustInfo($user_info){
        $entrust_info = array();
        $user_entrust = DealAgencyModel::instance()->getAgencyByAgencyUserId($user_info['id'],7);
        if($user_entrust){
            $entrust_info = $user_entrust;
        }
        return array('entrust_info' => $entrust_info, 'is_entrust' => empty($entrust_info) ? 0 : 1);
    }


    /**
     * 根据机构类型和名称（非必填，支持模糊查询）
     * @param int $type
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($type, $name, $page_num, $page_size, $is_credit_display)
    {
        $pageSize = empty($page_size) ? 5 : intval($page_size);
        $pageNum = empty($page_num) ? 1 : intval($page_num);
        $agencyList = DealAgencyModel::instance()->getListByTypeName(intval($type), htmlentities($name), $pageNum, $pageSize, intval($is_credit_display));
        return $agencyList;
    }


}
