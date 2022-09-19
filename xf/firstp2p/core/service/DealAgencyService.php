<?php
/**
 * DealAgencyService class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\service;

use core\dao\BankModel;
use core\dao\UserModel;
use core\dao\DealAgencyModel;
use core\dao\UserBankcardModel;
use core\dao\AgencyImageModel;
use core\service\AgencyImageService;

class DealAgencyService extends BaseService {

    // mark
    const MARK_XIJINJIAO = 'XIJINJIAO';

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
            $agency_user_info = UserModel::instance()->findViaSlave($agency_info['agency_user_id'],'user_name,idno,real_name');
            if($agency_user_info){
                $agency_info['agency_agent_real_name'] = $agency_user_info['real_name'];//担保公司代理人真实姓名
                $agency_info['agency_agent_user_name'] = $agency_user_info['user_name'];//担保公司代理人网信理财网站用户名
                $agency_info['agency_agent_user_idno'] = $agency_user_info['idno'];//担保公司代理人身份证号
            }

            //机构银行卡信息 取 关联用户信息
            //$bankcard_info = UserBankcardModel::instance()->findBy('user_id ='.$agency_info['user_id']);
            $bankcard_info = UserBankcardModel::instance()->getOneCardByUser($agency_info['user_id']);
            if($bankcard_info){
                $bank_info = BankModel::instance()->findViaSlave($bankcard_info['bank_id'], 'name');
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
     * [根据机构类型获取机构相关信息(包括图片)]
     * @param int [agency-type]
     * @return array [[机构信息列表]]
     */
    public function getDealAgencyListByType($agencyType)
    {
        $agencyList = DealAgencyModel::instance()->getDealAgencyList($agencyType);

        // 机构的其他信息
        $agencyImgSer = new AgencyImageService();
        $agencyListNew = array();
        foreach ($agencyList as $agencyKey => $agency) {
            $imgArr = $agencyImgSer->getAgencyImages($agency->id);
            foreach ($imgArr as $imgKey => $value) {
                $agencyList[$agencyKey]->$imgKey = $value;
            }
        }

        return $agencyList;
    }

    /**
     * 判断机构是否为西金交
     * @param int $agency_id
     * @return boolean
     */
    static public function isXiJinJiaoAgency($agency_id)
    {
        if ($agency_id <= 0) {
            return false;
        }

        $agency_info = DealAgencyModel::instance()->getDealAgencyById($agency_id);

        // mark 是否为 西金交 的标识
        return empty($agency_info) ? false : (self::MARK_XIJINJIAO == $agency_info['mark']);
    }

    /**
     * 根据机构类型和名称（非必填，支持模糊查询）
     * @param int $type
     * @param $name
     * @param $page_num
     * @param $page_size
     * @return mixed
     */
    public function getListByTypeName($type, $name, $page_num, $page_size, $is_credit_display = 0)
    {
        $pageSize = empty($page_size) ? 5 : intval($page_size);
        $pageNum = empty($page_num) ? 1 : intval($page_num);
        $agencyList = DealAgencyModel::instance()->getListByTypeName(intval($type), htmlentities($name), $pageNum, $pageSize, intval($is_credit_display));
        return $agencyList;
    }

}
