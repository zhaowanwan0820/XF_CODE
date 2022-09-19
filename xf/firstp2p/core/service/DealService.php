<?php
/**
 * Deal class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace core\service;

use app\models\dao\DealSite;
use core\dao\ContractModel;
use core\dao\DealContractModel;
use core\dao\DealLoanPartRepayModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealLoadModel;
use core\dao\DealAgencyModel;
use core\dao\DealRepayModel;
use core\dao\AgencyUserModel;
use core\dao\DealTagModel;
use core\dao\MsgCategoryModel;
use core\dao\OpLogModel;
use core\dao\OpStatusModel;
use core\dao\TagModel;
use core\dao\ThirdpartyOrderModel;
use core\dao\UserCompanyModel;
use core\dao\DealExtModel;
use core\dao\DealSiteModel;
use core\dao\UserModel;
use core\dao\UserCarryModel;
use core\dao\DealChannelLogModel;
use core\dao\DealQueueModel;
use core\dao\DealQueueInfoModel;
use core\dao\JobsModel;
use core\data\DealData;
use core\dao\UserBankcardModel;
use core\service\EarningService;
use core\service\DealAgencyService;
//use core\dao\DealGroupModel;
use core\service\DealGroupService;
use core\service\DealProjectService;
use core\service\TaskService;
use core\service\DealTagService;
use libs\event\SubsidyEvent;
use core\service\AdunionDealService;
use core\service\CouponLogService;
use core\service\UserService;
use core\service\XHService;
use core\service\ContractNewService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\SendContractEvent;
use core\event\DealFullCheckerEvent;
use core\event\DealLoansEvent;
use core\dao\DealCompoundModel;
use core\service\DealCompoundService;
use core\dao\CouponDealModel;
use core\service\CouponDealService;
use libs\lock\LockFactory;
use core\service\UserTagService;
use core\service\DealLoadService;
use core\service\ContractService;
use core\service\DtDealService;
use core\service\UserReservationService;
use core\dao\FinanceQueueModel;
use core\dao\LoanOplogModel;
use libs\utils\Finance;
use libs\utils\Aes;
use libs\web\Url;
use libs\utils\Logger;
use libs\sms\SmsServer;
use libs\utils\Rpc;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use core\dao\ReservationConfModel;
use core\service\ReservationConfService;

/**
 * Deal service
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class DealService extends BaseService {

    public static $FIELD_HASH = array(
        '0'=>'id',
        '1'=>'income_total_rate',
        '2'=>'repay_time',
        '3'=>'point_percent',
        '4'=>'deal_status',
    );

    public static $TYPE_HASH = array(
        '0'=>'asc',
        '1'=>'desc',
    );

    const DEAL_NAME_PREFIX = '100起投，'; // 默认标的名前缀

    /**
     * 获取订单详情
     */
    public function getDeal($id, $read_only=false, $hand_deal=true) {
        $bxtTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $ndTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_NDD);
        $deal_model = new DealModel();
        if ($read_only === true) {
            $deal = $deal_model->find($id, "*", true);
        } else {
            $deal = $deal_model->find($id);
        }
        if (empty($deal)) {
            return false;
        }
        //获取产品类别的标识
        $deal['type_tag'] = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);

        if ($hand_deal){
            if ($read_only === false) {
                $deal_model->is_slave = 0;
            }
            $deal = $deal_model->handleDeal($deal, 0, false);
        }
        $deal['isBxt'] = 0;
        if($deal['type_id'] == $bxtTypeId){
            $deal['isBxt'] = 1;
        }
        $deal['isDtb'] = 0;
        if($this->isDealDT($id)){
            $deal['isDtb'] = 1;
        }
        $deal['isNd'] = 0;
        if ($deal['type_id'] == $ndTypeId) {
            $deal['isNd'] = 1;
        }
        if(!empty($deal['deal_tag_name'])){
            $deal_tag_name= explode(',',$deal['deal_tag_name']);
        }
        $i_tag =0;
        foreach($deal_tag_name as $tag){
            if($i_tag){
                $deal['deal_tag_name'.$i_tag] = $tag;
            }else{
                $deal['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }
        return $deal;
    }

    /**
     * 处理流标逻辑
     * @param int $deal_id
     */
    public function failDeal($deal_id) {
        $deal_model = new DealModel();
        $deal = $deal_model->find($deal_id);
        $deal = $deal->getRow();
        //$state = new \core\service\deal\FailState();
        $state_manager = new \core\service\deal\StateManager();
        $state_manager->setDeal($deal);

        $rs = $state_manager->work();

        /*

        $result = $deal_dao->failDeal($deal);
        if ($result === false) {
            // 如果处理过程失败，则发报警邮件
            $msgcenter = new \msgcenter();
            \FP::import("libs.common.dict");
            $email_arr = \dict::get("MSG_WARN_EMAIL");
            if($email_arr){
                $content = "流标处理失败，请检查投资人账户信息。借款id：{$deal['id']}，借款标题：{$deal['name']}。时间：" . date("Y-m-d H:i:s", get_gmtime());
                foreach ($email_arr as $email) {
                    $msgcenter->setMsg($email, 0, $content, false, "流标处理失败");
                }
                $msgcenter->save();
            }

            return false;
        }
         */

        return $rs;
    }

    /**
     * 获取某个标的邀请返利总额
     */
    public function getDealChannelLogMoney($deal_id){
        $deal_id = intval($deal_id);
        $money = 0;

        if($deal_id > 0){
            $deal_channel_log_model = new DealChannelLogModel();
            $money = $deal_channel_log_model->getSumMoneyByDealId($deal_id);
        }
        return $money;
    }

    /**
     * 获取系统内借款总数
     *
     * @return integer 借款总数
     **/
    public function total()
    {
        $deal = new DealModel();
        return $deal->count();
    }

    //P2P标首页显示数量
    const P2P_INDEX_COUNT = 2;

    //专享标首页显示数量
    const ZX_INDEX_COUNT = 7;

    //小贷标首页显示数量
    const PETTY_LOAN_INDEX_COUNT = 7;

    //专享标首页最多显示数量
    const ZX_INDEX_COUNT_MAX = 10;

    /**
     * 获取首页展示的投资列表
     * edit by wangyiming 20160217 pm:heping 首页仅显示全部的标的，并去掉count数字
     * 20160906 增加专享标列表 by quanhengzhuang
     */
    public function getIndexList($isShowReportDeal=false)
    {
        //普通标
        $p2pCount = app_conf('WEB_INDEX_P2P_COUNT');
        if ($p2pCount <= 0) {
            $p2pCount = self::P2P_INDEX_COUNT;
        }
        $option = array();
        $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P;

        $option['isHitSupervision'] = $isShowReportDeal;
        //$deals = DealModel::instance()->getListV2(null, false, 1, $p2pCount, FALSE, TRUE, 0, $option, false, false, false, false);
        $result['list'] = \core\service\ncfph\DealService::getIndexList(1,$p2pCount);

        //专享标
        $zxCount = app_conf('WEB_INDEX_ZX_COUNT');
        if ($zxCount <= 0) {
            $zxCount = self::ZX_INDEX_COUNT;
        }

        $option['deal_type'] = DealModel::DEAL_TYPE_EXCLUSIVE . "," . DealModel::DEAL_TYPE_EXCHANGE;
        $deals = DealModel::instance()->getListV2(null, false, 1, self::ZX_INDEX_COUNT_MAX, FALSE, TRUE, 0, $option, false, false, false, false);

        //超过配置数量的，只显示进行中的标的
        foreach ($deals['list'] as $key => $item) {
            if ($key >= $zxCount && $item['deal_status'] != 1) {
                unset($deals['list'][$key]);
            }
        }

        $result['zx_list'] = $this->handleDealForList($deals['list']);

        return $result;
    }

    /**
     * 获取农担首页展示的投资列表
     *
     */
    public function getIndexListNdd()
    {
        //普通标
        $p2pCount = app_conf('WEB_INDEX_P2P_COUNT');
        if ($p2pCount <= 0) {
            $p2pCount = self::P2P_INDEX_COUNT;
        }
        $option = array();
//        $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P;
//
//        $option['isHitSupervision'] = $isShowReportDeal;
//        $deals = DealModel::instance()->getList(null, false, 1, $p2pCount, FALSE, TRUE, 0, $option, false, false, false, false);
//        $result['list'] = $this->handleDealForList($deals['list']);

        $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
        $ncfphS = new \core\service\ncfph\DealService();
        $result = $ncfphS->getNongdanList(1,$p2pCount,$site_id);
        return $result;
    }

    public function getNdList($type_id, $page, $page_size=0, $is_all_site=false, $site_id=0,$option=array()){
        $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
        $ncfphS = new \core\service\ncfph\DealService();
        $pagesize = app_conf("DEAL_PAGE_SIZE");
        $result = $ncfphS->getNdList($page,$pagesize,$site_id);
        return $result;
    }


    /**
     * 获取标的分类信息
     * @param int $userId 用户id
     * @return mixed
     */
    public function getDealCategoryInfo($userId = 0)
    {
        $dealCategoryInfos = array();
        $dealCategoryConf = app_conf('DEAL_CATEGORY_CONF');
        if(empty($dealCategoryConf)) {
            return $dealCategoryInfos;
        }

        $infoNames=array('type','name','rate','desc','isIndexShow');
//DT,智多鑫,预期收益5%~10%,随存随取灵活投资,1|ZX,专享,预期收益5%~10%,随存随取灵活投资,1|
//P2P,P2P,预期收益5%~10%,新手短期标,1|JYS,交易所,预期收益5%~10%,国资背景安全可靠,1
        $dealCategoryConfs = explode('|',$dealCategoryConf);
        $dealModel = DealModel::instance();

        $isLogin = 0;
        if (!empty($userId)){
            $isLogin = 1;
        }
        // 登录状态统计专项的
        $dealNums = \SiteApp::init()->dataCache->call($dealModel, 'getDealCategoryNum', array($isLogin), 60, false, true);

        foreach ($dealCategoryConfs as $conf) {
            $confs = explode(',',$conf) ;
            $info = array_combine($infoNames,$confs);
            $type = $info['type'];
            switch ($type) {
                case 'DT' :
                    if($userId != 0) {
                        if(is_duotou_inner_user()) {

                            $dtDealService = new DtDealService();
                            $response_dt = $dtDealService->getIndexDeal();
                            if ($response_dt && !empty($response_dt['data'])) {
                                $info['num'] = 1;
                            } else {
                                $info['num'] = 0;
                            }
                            $dealCategoryInfos[$type] = $info;
                        }
                    }
                    break;
                case 'YUE' :
                    $userReservationService = new UserReservationService();
                    $dealTypeList = $userReservationService->getDealTypeListByProduct(0, $userId);

                    //拆分成网贷和专享类型列表
                    $p2pDealTypeList = array_intersect([DealModel::DEAL_TYPE_GENERAL], $dealTypeList);
                    $exclusiveDealTypeList = array_diff($dealTypeList, [DealModel::DEAL_TYPE_GENERAL]);

                    $p2pCards = $exclusiveCards = [];
                    //请求网贷接口
                    if ($p2pDealTypeList) {
                        $result = ( new \core\service\ncfph\ReserveEntraService() )->getReserveEntraList(0, 0, $userId);
                        $p2pCards = !empty($result['list']) ? $result['list'] : [];

                    }
                    if ($exclusiveDealTypeList) {
                        $result = ( new \core\service\ReservationEntraService() )->getReserveEntraDetailList(true, 0, 0);
                        $exclusiveCards = !empty($result['list']) ? $result['list'] : [];
                    }
                    //聚合结果
                    $cards = array_merge($p2pCards, $exclusiveCards);

                    $info['num'] = count($cards);
                    $dealCategoryInfos[$type] = $info;
                    break;
                case 'ZX' :
                case 'P2P' :
                    $deal_ncfph = new \core\service\ncfph\DealService() ;
                    $info['num']= $deal_ncfph-> GetDealNum();
                    $dealCategoryInfos[$type] = $info;
                    break ;
                case 'JYS' :
                    $info['num'] = $dealNums[$type];
                    $dealCategoryInfos[$type] = $info;
                    break;
                case 'CUZX':
                    $info['num'] = $dealNums[$type];
                    $dealCategoryInfos[$type] = $info;
                    break;
            }
        }
        return $dealCategoryInfos;
    }

    /**
     * 对标的列表进行批量handleDeal
     */
    public function handleDealForList($list)
    {
        $deal_list = array();

        if ($list) {
            foreach ($list as $key => $deal) {
                $list[$key] = DealModel::instance()->handleDealNew($deal, 1);
            }
            $deal_list['list'] = $list;
        } else {
            $deal_list['list'] = array();
        }

        return $deal_list;
    }

    public function getBXTList($pageNo=1, $count=3,$need_count = false){
        $dealTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);
        $dealsInfo = DealModel::instance()->getList($dealTypeId, false, $pageNo, $count, false, true, 0, array(), false, false, true,$need_count);
        //$type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array(),$is_real_site=false, $count_only=false
        $list = $dealsInfo['list'];
        if (!empty($list)) {
            foreach ($list as $key => $deal) {
                $dealsInfo['list'][$key] = DealModel::instance()->handleDealNew($deal, 1);
            }
        }else{
            return array();
        }
        $dealsInfo['page_size'] = $count;
        return $dealsInfo;
    }

    /**
     * 根据条件搜索标
     * @param array $cates 搜索分类
     * @param int $repay_time 投资期限
     * @param float $income_rate 年化收益率
     * @param int $page
     * @param int $page_size
     * @param bool $is_all_site
     * @param int $site_id
     * @return array
     */
    public function searchDeals($cates, $repay_time, $income_rate, $page, $page_size, $is_all_site=false, $site_id=0, $show_crowd_specific = true) {
        // 处理搜索分类
        $deal_tyeps_data = DealLoanTypeModel::instance()->getDealTypes();
        $deal_type = $deal_tyeps_data['data'];
        if ($cates) {
            $type_ids = array();
            foreach ($cates as $cate) {
                $ids = $deal_type[$cate]['id'];
                $type_ids = array_merge($type_ids, explode(",", $ids));
            }
        }
        $type = implode(",", $type_ids);

        // 处理搜索分页
        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;

        // 处理搜索选项
        $option = array();
        if ($repay_time) {
            $option['repay_time'] = $repay_time;
        }
        if ($income_rate) {
            $option['income_rate'] = $income_rate;
        }

        $option['show_crowd_specific'] = $show_crowd_specific ? 1 : 0;

        $deals = DealModel::instance()->getList($type, array(), $page, $page_size, $is_all_site, true, $site_id, $option);
        $result = array();
        foreach ($deals['list'] as $k => $v) {
            $result[$k] = DealModel::instance()->handleDeal($v);
        }
        return $result;
    }

    /**
     * searchDealsBySections
     * 根据区间段获取贷款列表
     *
     * @param mixed $cates
     * @param mixed $total 总额区间
     * @param mixed $rate 收益率区间
     * @param mixed $timelimit 时间区间
     * @param mixed $region 地区ID
     * @param mixed $page
     * @param mixed $page_size
     * @param mixed $is_all_site
     * @param int $site_id
     * @param mixed $show_crowd_specific
     * @access public
     * @return void
     */
    public function searchDealsBySections($cates, $total, $rate, $timelimit, $region, $page, $page_size, $is_all_site=false, $site_id=0, $show_crowd_specific = true) {
        // 处理搜索分类
        $deal_tyeps_data = DealLoanTypeModel::instance()->getDealTypes();
        $deal_type = $deal_tyeps_data['data'];
        if ($cates) {
            $type_ids = array();
            foreach ($cates as $cate) {
                $ids = $deal_type[$cate]['id'];
                $type_ids = array_merge($type_ids, explode(",", $ids));
            }
        }
        $type = implode(",", $type_ids);

        // 处理搜索分页
        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;

        // 处理搜索选项
        $option = array();

        if (!empty($region)) { // 支持同时传入多个地区ID
            $regions = explode(',', $region);
            $users = UserBankcardModel::instance()->getUsersByRegions($regions);
            if (null !== $users) {
                foreach ($users as $item) {
                    $option['uids'][] = $item['user_id'];
                }
            }
        }

        $option['repay_time'] = $timelimit;
        $option['income_rate'] = $rate;
        $option['total'] = $total;
        $option['region'] = $region;
        $option['show_crowd_specific'] = $show_crowd_specific ? 1 : 0;

        $deals = DealModel::instance()->getListNew($type, array(), $page, $page_size, $is_all_site, true, $site_id, $option);
        $result = array();
        foreach ($deals['list'] as $k => $v) {
            $result[$k] = DealModel::instance()->handleDeal($v);
        }
        return $result;
    }

    /**
     * 获取标的列表，供列表页使用，功能是一样的，但是由于getList已经有别处使用，所以新加一个方法
     * @param int $page
     * @param int $page_size
     * @param bool $is_all_site
     * @param int $site_id
     */
    public function getDealsList($type_id, $page, $page_size=0, $is_all_site=false, $site_id=0,$option=array()) {
        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;

        if($option['deal_type'] ==  DealModel::DEAL_TYPE_ALL_P2P || $option['deal_type'] == DealModel::DEAL_TYPE_GENERAL){
            $result =  \core\service\ncfph\DealService::getDealsList($type_id, $page, $page_size=0, $is_all_site=false, $site_id=0,$option=array());
            return $result;
        }

        // 存管abtest逻辑
        $option['isHitSupervision'] = (isset($option['isHitSupervision']) && $option['isHitSupervision'] === true) ? true : false;
        $deals = DealModel::instance()->getList($type_id, null, $page, $page_size, $is_all_site, true, $site_id,$option, false, false, false, false);

        $list = $deals['list'];
        foreach ($list as $key => $deal) {
            $list[$key] = DealModel::instance()->handleDealNew($deal);
        }

        $data['list'] = $list;
        $result['list'] = $data;

        $result['page_size'] = $page_size;
        $result['count'] = $deals['count'];
        return $result;
    }

    /**
     * 获取投资列表
     *
     * @param $have_crowd_specific 是否显示‘特定用户组’的标
     * @param $needCount 是否需要统计count信息 默认统计
     */
    public function getList($cate, $type, $field, $page, $page_size=0, $is_all_site=false, $site_id=0, $show_crowd_specific = true, $dealTypes = '', $dealTagName = '',$needCount=true,$isShowP2p=false,$option=array()) {
        $deal_types_data = DealLoanTypeModel::instance()->getDealTypes();
        $arr_types = $deal_types_data['others'];
        if(!in_array($cate, $arr_types)){
            $cate = 0;
        }

        $type = isset($type) ? $type : null;
        $field = isset($field) ? $field : null;

        $sort['field'] = isset(self::$FIELD_HASH[$field]) ? self::$FIELD_HASH[$field] : null;
        $sort['type'] = isset(self::$TYPE_HASH[$type]) ? self::$TYPE_HASH[$type] : null;

        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;

        $deal_type = $deal_types_data['data'];

        $option['show_crowd_specific'] = $show_crowd_specific ? 1 : 0;

        $option['deal_type'] = $dealTypes;

        $option['isHitSupervision'] = $isShowP2p;

        if(!empty($dealTagName)){
            $option['deal_tag_name'] = explode(',',$dealTagName);
        }

        foreach ($deal_type as $k => $v) {
            $type_id = isset($v['id']) ? $v['id'] : false;
            if ($cate == $k) {
                $deals = DealModel::instance()->getList($type_id, $sort, $page, $page_size, $is_all_site, true, $site_id, $option,false,false,false,$needCount);
                $list = $deals['list'];
                foreach ($list as $key => $deal) {
                    $list[$key] = DealModel::instance()->handleDealNew($deal, 1);
                }
                $result['page_size'] = $page_size;
                if($needCount) {
                    $result['count'] = $deals['count'];
                }
            }
        }

        $data['list'] = $list;
        $result['list'] = $data;
        $result['sort'] = $sort;
        $result['deal_type'] = $deal_type;
        $result['cate'] = $cate;
        return $result;
    }

    /**
     * 修改首页 列表页面 缓存引起标的状态有问题的情况
     * @param $list
     * @return mixed
     */
    public function UserDealStatusSwitch($list){
        foreach($list as $k=>$v){
            $list[$k] = DealModel::instance()->UserDealStatusSwitch($v);
        }
        return $list;
    }

    /**
     *对dealId进行加密
     * @param $list
     * @return mixed
     */
    public function EncryptDealIds($list){
        if (is_array($list)) {
            foreach($list as $k=>$v){
                $list[$k]['url'] = Url::gene("d", "", Aes::encryptForDeal($v['id']), true);
                $list[$k]['ecid'] = Aes::encryptForDeal($v['id']);
            }
        }
        return $list;
    }

    /**
     * 获取投资类型
     */
    public function getDealTypes() {
        return DealLoanTypeModel::instance()->getDealTypes();
    }

    /**
     * 获取订单的用户企业信息
     *
     * @param $deal 订单信息
     * @return array
     */
    public function getDealUserCompanyInfo($deal) {
        $data = array();
        $data['user_id'] = $deal['user_id'];
        $data['is_company'] = 0;

        //个人借款
        $user_service = new UserService();
        $user_info = $user_service->getUserViaSlave($deal['user_id']);
        if ($user_info) {
            $data['show_name'] = $user_info['user_name'];
            //合同变量
            $data['borrow_real_name'] = $user_info['real_name']; //真实姓名
            $data['borrow_user_name'] = $user_info['user_name']; //用户名
            $data['borrow_user_idno'] = $user_info['idno']; //身份证
            $data['borrow_address'] = $user_info['address']; //地址
            $data['borrow_mobile'] = $user_info['mobile']; //手机
            $data['borrow_postcode'] = $user_info['email']; //邮箱 （历史错误）
            $data['borrow_email'] = $user_info['email']; //邮箱
            $data['real_name'] = $user_info['real_name']; //邮
        }

        /*
         *公司借款 合同服务相关
         */
        if(is_numeric($deal['contract_tpl_type'])){
            $contractNewService = new ContractNewService();
            $tpl_info = $contractNewService->getCategoryByCid($deal['contract_tpl_type']);
            $tpl_info['contract_type'] = $tpl_info['contractType'];
        }else{
            $tpl_info = $deal['contract_tpl_type'] ? MsgCategoryModel::instance()->findByTypeTag($deal['contract_tpl_type']) : array();

        }
        $data['company_description_html'] = '';
        if (!empty($tpl_info) && $tpl_info['contract_type'] == \app\models\service\ContractType::TYPE_COMPANY) {
            $company_info = UserCompanyModel::instance()->findByUserId($deal['user_id']);
            $data['is_company'] = 1;
            $data['show_name'] = isset($company_info['name']) ? $company_info['name'] : '';
            $data['company_name'] = isset($company_info['name']) ? $company_info['name'] : ''; //名称
            $data['company_address'] = isset($company_info['address']) ? $company_info['address'] : ''; //注册地址
            $data['company_legal_person'] = isset($company_info['legal_person']) ? $company_info['legal_person'] : ''; //法定代表人
            $data['company_tel'] = isset($company_info['tel']) ? $company_info['tel'] : ''; //联系电话
            $data['company_license'] = isset($company_info['license']) ? $company_info['license'] : ''; //营业执照号
            $data['company_description'] = isset($company_info['description']) ? $company_info['description'] : ''; //简介
            $data['company_address_current'] = isset($company_info['domicile']) ? $company_info['domicile'] : ''; //借款公司住所地

            $tempDes = $data['company_description'];
            if(intval($company_info['is_html']) === 0) { //数据处理
               $tempDes = str_replace("\n", "<br/>", $data['company_description']);
            }
            $data['company_description_html'] = $tempDes;
        }
        return $data;
    }

    /**
     * 复制借款
     *
     * @param $deal_id 借款id
     * @param $deal_project_id 项目id
     * @return bool
     */
    public function copyDeal($deal_id, $deal_project_id = 0){

        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_id, $deal_project_id);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $GLOBALS['db']->startTrans();
        try {
            $deal_id = intval($deal_id);
            if($deal_id <= 0){
                throw new \Exception("被复制标ID不能小于0");
            }
            $old_deal = DealModel::instance()->find($deal_id);
            if(empty($old_deal)){
                throw new \Exception("被复制的标信息不存在");
            }

            $deal_model = new DealModel();
            if ($deal_project_id) {
                $old_deal['project_id'] = $deal_project_id;
            }

            $old_deal['deal_crowd']=($old_deal['deal_crowd']==34 || $old_deal['deal_crowd']==35)?0:$old_deal['deal_crowd'];
            $deal_insert_id = $deal_model->insertDealData($old_deal);
            if(empty($deal_insert_id)){
                throw new \Exception("复制标失败");
            }

            // JIRA#3844 更新标的名 by fanjingwen
            $dealName = $this->updateDealName($deal_insert_id, $old_deal->project_id);

            //处理扩展信息表
            $old_deal_ext = DealExtModel::instance()->findBy('deal_id ='.$deal_id);

            if ($old_deal['deal_type'] == DealProjectService::DEAL_TYPE_LGL) {
                $old_deal_compound = DealCompoundModel::instance()->findBy('deal_id ='.$deal_id);

                if($old_deal_compound){
                    $deal_compound = new DealCompoundModel();
                    $old_deal_compound['deal_id'] = $deal_insert_id;
                    $new_compound = $old_deal_compound->getRow();
                    $deal_compound->setRow($new_compound);
                    $rs = $deal_compound->insert();
                    if(empty($rs)){
                        throw new \Exception("复制通知贷标信息失败");
                    }
                }
            }

            //处理扩展信息表
            $old_deal_ext = DealExtModel::instance()->findBy('deal_id ='.$deal_id);
            if($old_deal_ext){
                $deal_ext_model = new DealExtModel();
                $old_deal_ext['deal_id'] = $deal_insert_id;
                $ext_insert = $deal_ext_model->insertDealExt($old_deal_ext);
                if(empty($ext_insert)){
                    throw new \Exception("复制标扩展信息失败");
                }
            }

            $model_coupon_deal = new CouponDealModel();
            // 复制标优惠码设置信息
            $old_coupon_deal = $model_coupon_deal->findBy('deal_id='.$deal_id);
            if (!$old_coupon_deal){
                $coupon_deal_service = new CouponDealService();
                $coupon_deal_insert_deal = array(
                        'deal_id' => $deal_insert_id,
                        'deal_type' => $old_deal['deal_type'],
                        'loantype' => $old_deal['loantype'],
                        'repay_time' => $old_deal['repay_time'],
                        'pay_type' => $old_deal_ext['coupon_pay_type'],
                );
                $res = $coupon_deal_service->add($coupon_deal_insert_deal);
            }else {
                $new_model_coupon_deal = new CouponDealModel();
                $old_coupon_deal['deal_id'] = $deal_insert_id;
                $new_coupon_deal_data = $old_coupon_deal->getRow();
                $new_coupon_deal_data['create_time'] = get_gmtime();
                $new_coupon_deal_data['update_time'] = get_gmtime();
                // 复制新标识未结清
                $new_coupon_deal_data['is_paid'] = 0;
                $new_model_coupon_deal->setRow($new_coupon_deal_data);
                $res = $new_coupon_deal_res = $new_model_coupon_deal->insert();
            }
            if(empty($res)){
                throw new \Exception("复制标优惠码失败");
            }

            //处理所属站点信息
            $site_list = DealSiteModel::instance()->findAll('deal_id = '.$deal_id);
            if($site_list){
                $deal_site_model = new DealSiteModel();
                foreach($site_list as $site_row){
                    $site_insert = $deal_site_model->insertDealSite($deal_insert_id, $site_row->site_id);
                    if(empty($site_insert)){
                        throw new \Exception("复制所属展示信息失败");
                    }
                }
            }

            //复制标的自定义tag
            $tag_service = new DealTagService();
            $tag_arr = $tag_service->getTagByDealId($deal_id);
            if (count($tag_arr) > 0 && is_array($tag_arr)) {
                $tag_service->insert($deal_insert_id, implode(',', $tag_arr));
                if(empty($tag_service)){
                    throw new \Exception("复制自定义tag失败");
                }
            }

            $rs = $GLOBALS['db']->commit();
            if(empty($rs)){
                throw new \Exception("复制标事务提交失败");
            }
            Logger::info(implode(" | ", array_merge($log_info, array('复制标成功'))));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(" | ", array_merge($log_info, array('复制标失败:'.$e->getMessage()))));
            return false;
        }

    }

    /**
     * 获取某个借款的担保公司 的所有关联账户
     *
     * @param $deal_id 借款id
     * @return bool
     */
    public function getDealAgencyUser($deal_id){

        $deal_agency_user = array();
        $deal_info = DealModel::instance()->find($deal_id, 'agency_id,contract_tpl_type');
        if(empty($deal_info)){
            return $deal_agency_user;
        }

        if($deal_info['contract_tpl_type'] == 'HY'){

            \FP::import("libs.common.dict");
            $hydb_user = \dict::get('HY_DB');

            if($hydb_user){
                foreach ($hydb_user as $hydb){
                    $hyinfo = UserModel::instance()->findBy("user_name = '".$hydb."'", 'id,user_name');
                    $deal_agency_user[] = array(
                            'user_id' => $hyinfo['id'],
                            'user_name' => $hyinfo['user_name'],
                            'agency_id' => $GLOBALS['dict']['HY_DBGS'],
                            'is_hy' => 1
                    );
                }
            }
        }else{
            $deal_agency_user = AgencyUserModel::instance()->findAll("agency_id = ".$deal_info['agency_id'], true);
        }

        return $deal_agency_user;
    }


    /**
     * 进行投资
     *
     * @return array
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function bid($user_id, $deal_id, $money, $coupon_id, $source_type = 0, $site_id = 1, $order_id = false, $discount_id = 0, $discount_type = 1) {
        // 统一走新投资逻辑
        $deal = $this->getDeal($deal_id, true);
        $dl_service = new DealLoadService();
        $dl_service->track_id = $this->track_id;
        $dl_service->euid = $this->euid;
        return $dl_service->bid($user_id, $deal, $money, $coupon_id, $source_type, $site_id, $order_id, $discount_id, $discount_type);
    }

    /**
     * [makeDealLoansPackage 对外提供的放款接口]
     * @author <fanjingwen@ucfgroup.com>
     * @param  int      $deal_id    [description]
     * @param  array    $admin      [操作人员[adm_id, adm_name]]
     * @param  int      $repay_start_time [开始还款时间[北京时间对应的时间戳]]
     * @return bool                 [description]
     */
    public function makeDealLoansPackage($deal_id, $admin = array(), $repay_start_time = 0)
    {
        $deal_obj = DealModel::instance()->find($deal_id);
        $GLOBALS['db']->startTrans();
        try {
            // is ok for making loan
            $this->isOKForMakingLoans($deal_obj->getRow());

            // update project
            $deal_pro_service = new DealProjectService();
            $deal_pro_service->updateProBorrowed($deal_obj->project_id);
            $deal_pro_service->updateProLoaned($deal_obj->project_id);

            // update deals
            $deal_obj->deal_status = DealModel::$DEAL_STATUS['repaying'];
            $deal_obj->repay_start_time = empty($repay_start_time) ? to_timespan(date("Y-m-d")) : to_timespan(format_date($repay_start_time, 'Y-m-d'));

            if (empty($deal_obj->next_repay_time)) {
                // according day
                $delta_month_time = DealModel::instance()->get_delta_month_time($deal_obj->loantype, $deal_obj->repay_time);
                $deal_obj->next_repay_time = (5 == $deal_obj->loantype) ? next_replay_day_with_delta($deal_obj->repay_start_time, $delta_month_time) : next_replay_month_with_delta($deal_obj->repay_start_time, $delta_month_time);
            }
            if (!$deal_obj->save()) {
                 throw new \Exception("修改标的状态或者放款时间错误");
            }

            // add jobs
            $function = '\core\service\DealService::makeDealLoansJob';
            $param = array('deal_id' => $deal_obj->id, 'admin' => $admin);
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = 99;
            // delay 10s
            if (!$job_model->addJob($function, $param, get_gmtime() + 180)) {
                throw new \Exception("放款任务添加失败");
            }

            // update `is_has_loans`
            if (!DealModel::instance()->changeLoansStatus($deal_obj->id, 2)) {
                throw new \Exception("更新标放款状态 is_has_loans 失败");
            }

            $GLOBALS['db']->commit();
            Logger::info('function:makeDealLoansAPI status:success deal_id:' . $deal_obj->id);
            $is_success = true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::warn($e->getMessage());
            $is_success = false;
        }

        return $is_success;
    }

    /**
     * 放款jobs
     * @param $deal_id
     * @return bool
     */
    public function makeDealLoansJob($deal_id, $admin = array(), $submit_uid = 0){
        $deal = DealModel::instance()->find($deal_id);
//        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $zhangzhongTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        $deal_data = $deal->getRow();
        $deal_data['isDtb'] = 0;
        if($this->isDealDT($deal_id)){
            $deal_data['isDtb'] = 1;
        }
        $deal_ext = DealExtModel::instance()->getInfoByDeal($deal_id);
        $agency_model = new DealAgencyModel();
        $agency_fee_user = $agency_model->find($deal_data['agency_id']);
        $advisory_fee_user = $agency_model->find($deal_data['advisory_id']);
        $canal_fee_user_id = $agency_model->find($deal_data['canal_agency_id']);
        $loan_fee_user_id = $agency_model->getLoanAgencyUserId($deal_id);
        if (!$deal_data['pay_agency_id']) {
            $deal_data['pay_agency_id'] = $agency_model->getUcfPayAgencyId();
        }
        $pay_agency_user = $agency_model->find($deal_data['pay_agency_id']);

        $management_user_id = 0;
        if($deal_data['isDtb'] == 1) {
            $management_agency_user = $agency_model->find($deal_data['management_agency_id']);
            $management_user_id = $management_agency_user['user_id'];
        }
        $result = $this->makeDealLoans($deal_data, $advisory_fee_user['user_id'], $agency_fee_user['user_id'], $loan_fee_user_id, $pay_agency_user['user_id'], $management_user_id, $canal_fee_user_id['user_id'], $admin);
        if (!$this->isP2pPath($deal) && $deal_ext['is_auto_withdrawal'] == 1 && $deal_data['type_id'] != $zhangzhongTypeId) {
             $grantService = new \core\service\P2pDealGrantService();
             $grantService->sendWithdrawMsg($deal,$result['money'],$result['services_fee']);
        }

        $projectInfo = DealProjectModel::instance()->find($deal_data['project_id']);

        if($result != false){
            $loan_oplog_model = new LoanOplogModel();
            if (0 == $admin['adm_id']) {
                $loan_oplog_model->op_type = LoanOplogModel::OP_TYPE_AUTO_MAKE_LOAN;
            } else {
                $loan_oplog_model->op_type = LoanOplogModel::OP_TYPE_MAKE_LOAN;
            }
            $loan_oplog_model->loan_batch_no = '';
            $loan_oplog_model->deal_id = $deal_data['id'];
            $loan_oplog_model->deal_name = $deal_data['name'];
            $loan_oplog_model->borrow_amount = $deal_data['borrow_amount'];
            $loan_oplog_model->repay_time = $deal_data['repay_time'];
            $loan_oplog_model->loan_type = $deal_data['loantype'];
            $loan_oplog_model->borrow_user_id = $deal_data['user_id'];
            $loan_oplog_model->op_user_id = $admin['adm_id'];
            $loan_oplog_model->loan_money_type = $projectInfo['loan_money_type'];
            $loan_oplog_model->op_time = get_gmtime();
            $loan_oplog_model->submit_uid = intval($submit_uid);
            $loan_oplog_model->loan_money = $deal_data['borrow_amount'] - $result['services_fee'];
            if(!$loan_oplog_model->save()){
                throw new \Exception("保存放款操作记录失败");
            };
        }

        return ($result === false) ? false : true;
    }

    /**
     * 封装放款方法
     */
    public function makeDealLoans($deal_data, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id, $canal_fee_user_id, $admin = array()) {

        $deal_ext = DealExtModel::instance()->getInfoByDeal($deal_data['id'], false);
        $project = DealProjectModel::instance()->find($deal_data['project_id']);

        // 悲观锁，以group_id为锁的键名，防止重复生成
        $lockKey = "DealService-makeDealLoansJob".$deal_data['id'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 900)) {
            return false;
        }

        try {
            $GLOBALS['db']->startTrans();
            $deal_dao = new DealModel();
            $result_make = $deal_dao->makeDealLoans($deal_data, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id, $canal_fee_user_id, $admin);
            if($result_make === false){
                throw new \Exception("放款逻辑处理失败,返回对象：".json_encode($result_make));
            }

            $services_fee = 0;
            //自动提现
            if($deal_ext['is_auto_withdrawal'] == 1 && $project->loan_money_type !=2){

                //平台费+咨询费+担保费+支付服务费
                $services_fee = round($result_make['data']['services_fee'], 2);

                $dealModel = DealModel::instance()->find($deal_data['id']);
                if($this->isP2pPath($dealModel)){
                    $function = '\core\service\P2pDealGrantService::afterGrantWithdraw';
                    $grantMoney = bcsub($deal_data['borrow_amount'],$services_fee,2);
                    $orderId = Idworker::instance()->getId();
                    $param = array($orderId,$deal_data['id'],$grantMoney);
                    $job_model = new JobsModel();
                    $job_model->priority = 50;
                    if (!$job_model->addJob($function, $param, false, 99)) {
                        throw new \Exception('存管标的放款提现jobs添加失败');
                    }
                    $result = array('result'=>0,'money'=>$grantMoney,'services_fee'=>$services_fee);
                }else{
                    $result = $this->withdrawal($deal_data,$services_fee, $admin);
                }
            } else {
                $result = true;
            }

            //更新消费分期返利天数
            $deal_loan_type_dao = new DealLoanTypeModel();
            $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal_data['type_id']);
            if (($type_tag == DealLoanTypeModel::TYPE_XFFQ) && ($deal_data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'])) {
                $repay_times = intval($deal_data['repay_time']) - 1;
                $rebate_days = floor($repay_times*30 + ($deal_ext['first_repay_interest_day'] - $deal_data['repay_start_time'])/86400); //  返利天数=第一期还款日期-起息日期+（期数-1）*30
                if($rebate_days < 0) {
                    throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebate_days);
                }
                // 更新优惠码返利天数
                $coupon_deal_service = new CouponDealService();
                $coupon_res = $coupon_deal_service->updateRebateDaysByDealId(intval($deal_data['id']), $rebate_days);;
                if(!$coupon_res){
                    throw new \Exception("更新标优惠码返利天数失败");
                }
            }

            $event = new DealLoansEvent($deal_data['id']);
            $event->execute();

            $function = '\core\service\DealLoanRepayService::finishDealLoans';
            $param = array($deal_data['id']);
            $job_model = new JobsModel();
            $job_model->priority = 50;
            if (!$job_model->addJob($function, $param, false, 99)) {
                throw new \Exception('回款计划收尾任务添加失败');
            }

            $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey);//解锁
        } catch (\Exception $e) {
            $log = array(
                'type' => 'makeDealLoans',
                'user_name' => $admin['adm_name'],
                'money' => bcsub($deal_data['borrow_amount'],$services_fee,2),
                'deal_id' => $deal_data['id'],
                'path' =>  __FILE__,
                'time' => time(),
            );
            $log['desc'] = '提现申请失败，借款编号：'.$deal_data['id'].' 错误消息：'.$e->getMessage();
            self::log($log);
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey);//解锁
            return false;
        }

        return $result;
    }

     /**
     * 日志记录
     */
    private static function log($body, $level = \libs\utils\Logger::INFO)
    {
        $destination = APP_ROOT_PATH.'log/logger/DealService.'.date('y_m').'.log';
        \libs\utils\Logger::wLog($body, $level, \libs\utils\Logger::FILE, $destination);
    }

    /**
     * 提现申请
     */
    public function withdrawal($deal_data,$services_fee, $admin = array()){
        // 获取标的项目信息
        $dealProjectObj = DealProjectModel::instance()->findViaSlave($deal_data['project_id']);
        $userCarry = new UserCarryModel();
        $deal_loan_type_dao = new DealLoanTypeModel();
        $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal_data['type_id']);
        if ((in_array($type_tag, array(DealLoanTypeModel::TYPE_ZHANGZHONG, DealLoanTypeModel::TYPE_XSJK))) || (($type_tag == DealLoanTypeModel::TYPE_XFFQ)  && ($deal_data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']))) {
            $userCarry->bank_id      = 0;
            $userCarry->real_name    = "";
            $userCarry->region_lv1   = 0;
            $userCarry->region_lv2   = 0;
            $userCarry->region_lv3   = 0;
            $userCarry->region_lv4   = 0;
            $userCarry->bankcard     = "";
            $userCarry->bankzone     = "";
        } else {
            //如果放款方式是受托支付
            if ($dealProjectObj->loan_money_type == DealProjectModel::LOAN_MONEY_TYPE_ENTRUST) {
                $userCarry->bank_id      = (int)$dealProjectObj->bank_id;
                $userCarry->real_name    = $dealProjectObj->card_name;
                $userCarry->region_lv1   = 0;
                $userCarry->region_lv2   = 0;
                $userCarry->region_lv3   = 0;
                $userCarry->region_lv4   = 0;
                $userCarry->bankcard     = $dealProjectObj->bankcard;
                $userCarry->bankzone     = $dealProjectObj->bankzone;
            }else{
                //获取用户银行卡信息
                $bankcard_info = UserBankcardModel::instance()->getNewCardByUserId($deal_data['user_id']);
                // 如果用户没有银行卡信息或者信息没有确认保存过
                if(empty($bankcard_info) || $bankcard_info['status'] != 1) {
                    throw new \Exception(sprintf('用户未绑卡，提现申请失败，借款编号：%d，用户ID：%d', $deal_data['id'], $deal_data['user_id']));
                }
                $userCarry->bank_id      = $bankcard_info['bank_id'];
                $userCarry->real_name    = $bankcard_info['card_name'];
                $userCarry->region_lv1   = $bankcard_info['region_lv1'];
                $userCarry->region_lv2   = $bankcard_info['region_lv2'];
                $userCarry->region_lv3   = $bankcard_info['region_lv3'];
                $userCarry->region_lv4   = $bankcard_info['region_lv4'];
                $userCarry->bankcard     = $bankcard_info['bankcard'];
                $userCarry->bankzone     = $bankcard_info['bankzone'];
            }
        }

        // 银行信息
        $userCarry->user_id      = $deal_data['user_id'];
        $userCarry->money        = $deal_data['borrow_amount'] - $services_fee;
        $userCarry->fee          = 0; // 目前没有提现手续费
        $userCarry->create_time  = get_gmtime();

        //add caolong 2013-12-27
        $userCarry->deal_id      = $deal_data['id'];
        $userCarry->type         = 1;
        //end

        // JIRA#FIRSTPTOP-3303 fanjingwen@ucfgroup.com 2016-03-22
        // 2、获取放款类型
        $extObj = DealExtModel::instance()->getDealExtByDealId($deal_data['id']);
        // 如果放款方式为实际放款，放款类型为直接放款或先收费后放款，则财务默认通过
        if ((1 == $dealProjectObj->loan_money_type) && (0 == $extObj->loan_type || 2 == $extObj->loan_type)) {
            $userCarry->status = 3; // 代表会计通过
            $userCarry->update_time = $userCarry->update_time_step1 = $userCarry->update_time_step2 = get_gmtime();
        } else {
            $userCarry->status       = 1; // 默认审批通过
            $userCarry->update_time  = get_gmtime();
        }
        // -------------- over ------------------------

        $deal_id = $userCarry->deal_id;
        $userCarry->{"`desc`"} = $userCarry->escape(
                htmlspecialchars('<p>操作：' . $admin['adm_name'] . '</p><p>用户提现</p>')
                );
        $redb = $userCarry->save();
        if($redb){
            $user = UserModel::instance()->find($deal_data['user_id']);
            $user->changeMoneyAsyn = true;
            $user->changeMoneyDealType = $deal_data['deal_type'];
            if(empty($user)){
                throw new \Exception("查询不到当前用户".$deal_data['user_id']);
            }
            $result = $user->changeMoney($userCarry->money,"提现申请",'系统发起提现 借款编号：'.$deal_id,
                    $admin['adm_id'],0,UserModel::TYPE_LOCK_MONEY);
            if($result){
                return array('result'=>0,'money'=>$userCarry->money,'services_fee'=>$services_fee);
            }else{
                throw new \Exception("冻结资金失败，借款编号：".$deal_id);
            }
        }else{
            throw new \Exception("提现申请失败，借款编号：".$deal_id);
        }
    }

    /**
     * 满标发送合同清单
     *
     * @param  $deal_id 借款id
     * @return int 写入合同的数量
     */
    public function sendDealContract($deal_id){

        \FP::import("libs.common.app");
        $deal = $this->getDeal($deal_id);

        if(empty($deal) || !in_array($deal['deal_status'], array(2,4,5))){
            return false;
        }

        //没有合同模板则不往下执行 公益标也没有合同模板
        if(empty($deal['contract_tpl_type'])){
            return true;
        }

        require_once(APP_ROOT_PATH."system/libs/send_contract.php");
        $contractModule = new \sendContract();  //引入合同操作类

        $notice_contrace = array();
        $borrow_user_info = $this->getDealUserCompanyInfo($deal); //借款人 或公司信息

        $dealagency_service = new DealAgencyService();
        $agency_info = $dealagency_service->getDealAgency($deal['agency_id']);//担保公司信息
        $advisory_info = $dealagency_service->getDealAgency($deal['advisory_id']);//咨询公司信息

        $loan_user_list = $GLOBALS['db']->getAll("SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.deal_id = ".$deal['id']." AND d.user_id = u.id");
        $guarantor_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']); //获取保证人列表
        //扩展字段
        $earning = new EarningService();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $borrow_user_info['repay_money'] = $all_repay_money;
        $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
        $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
        $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
        $borrow_user_info['leasing_money'] = $deal['leasing_money'];
        $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
        $borrow_user_info['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $borrow_user_info['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $borrow_user_info['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");

        ################   借款合同 （出借人、借款人）         ################
        //出借人
        $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, NULL);
        //出借人平台服务协议
        $contractModule->push_lender_protocal($deal, $loan_user_list, $borrow_user_info);

        //借款人
        $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, $deal['user_id']);
        //借款人平台服务协议
        $contractModule->push_borrower_protocal($deal, $borrow_user_info);

        ################   委托担保合同 （借款人、担保公司）         ################
        //借款人
        $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $deal['user_id']);
        //担保公司
        $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
        ################   保证人反担保（保证人、担保公司）         ################
        //保证人
        $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "guarantor");
        //担保公司
        $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "agency");
        ################   担保合同（担保公司、出借人）         ################
        //担保公司
        $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
        //出借人
        $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, NULL);

        ################   付款委托书（借款人）         ################
        if($deal['contract_tpl_type'] == 'HY'){
            $contractModule->push_payment_order($deal, $loan_user_list, $borrow_user_info);
        }

        ################   资产收益权回购通知（借款人、出借人）         ################
        $contractPreService = new ContractPreService();
        if($contractPreService->getAssetsContTpl($deal_id)){
            $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, NULL);
            $contractModule->push_buyback_notification($deal, $loan_user_list, $borrow_user_info, 1);
        }
        /*
         * 新的合同生成逻辑开始
         */
        //借款合同-（出借人,借款人,保证方,资产管理方）
        $contractModule->push_loan_contract_v2($deal, $loan_user_list, $borrow_user_info, $agency_info, $advisory_info);
        $contractModule->push_borrower_protocal_v2($deal, $borrow_user_info, $advisory_info);
        //新的合同生成逻辑结束
        #########################   END  #################################
        //入库
        $res = $contractModule->save();
        return $res;
    }

    /**
     * 根据标的type_id判断一个标是不是属于融租
     * @param $type_id
     * @return bool
     */
    public function isDealLeaseByType($type_id) {
        $deal_loan_type_dao = new DealLoanTypeModel();
        $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($type_id);
        if ($type_tag == DealLoanTypeModel::TYPE_ZCZR || $type_tag == DealLoanTypeModel::TYPE_YSD) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否是盈嘉1.75标的
     * @param DealModel $deal
     * @return bool
     */
    public function isDealYJ175($dealId){
        $deal = DealModel::instance()->find($dealId);
        $projectId = $deal['project_id'];
        $ps = new DealProjectService();
        return $ps->isProjectYJ175($projectId);
    }

    /**
     * @param $uid
     * @param $status
     * @param $limit array(0,5);
     * @param int | string $deal_type
     * @param boolen $has_entrust_zx 标识是否包含受托专享项目的标的
     * @return array
     */
    public function getListByUid($uid,$status,$limits, $deal_type = false, $has_entrust_zx = true){
        $rs = DealModel::instance()->getListByUid($uid,$status,$limits, $deal_type, $has_entrust_zx);
        return $rs;
    }

    /**
     * 正常还款程序
     * @param int $deal_id
     * @param arr $arr_repay_id
     * @return array
     */
    public function repayDeal($deal_id, $arr_repay_id) {
        $res_arr = array(
            "res" => false,
            "msg" => "操作失败!",
        );

        if (!$deal_id) {
            return $res_arr;
        }
        $deal = DealModel::instance()->find($deal_id);
        if (!$deal || $deal['user_id'] != $GLOBALS['user_info']['id'] || $deal['deal_status'] != DealModel::$DEAL_STATUS['repaying']) {
            return $res_arr;
        }

        foreach ($arr_repay_id as $repay_id) {
            $repay_id = intval($repay_id);
            if (!$repay_id) {
                return $res_arr;
            }
            $deal_repay = DealRepayModel::instance()->find($repay_id);
            if ($deal_repay) {
                $user = UserModel::instance()->find($deal['user_id']);
                if (bccomp($user['money'], $deal_repay['repay_money'], 2) != -1) {
                    if ($deal_repay->repay() === false) {
                        $res_arr['msg'] = "操作失败";
                        return $res_arr;
                    }
                } else {
                    $res_arr['msg'] = "对不起，您的余额不足";
                    return $res_arr;
                }
            }
        }

        $res_arr = array(
            "res" => true,
            "msg" => "操作成功",
        );
        return $res_arr;
    }

    /**
     * 根据项目id 及子标状态 查找子标集合
     * @param int $pro_id
     * @param array|int $deal_status
     * @return array
     * @author zhanglei5@ucfgroup.com
     */
    public function getDealByProId($pro_id,$deal_status=array()) {
        $result = DealModel::instance()->getDealByProId($pro_id,$deal_status);
        return $result;
    }

    /**
     * 根据状态类型、时间区间和和审批单号获得标的列表
     * 走从库
     * @param int $type deal_type
     * @param int $status deal_status
     * @param int $start_time
     * @param int $end_time
     * @param string $approve_number
     * @access public
     * @return array()
     */
    public function getDealListByStatusTypeTime($type, $status, $start_time, $end_time, $approve_number, $fields = "*", $page_num = 1, $page_size = 100) {
        $result = DealModel::instance()->getDealListByStatusTypeTime($type, $status, $start_time, $end_time, $approve_number, $fields, $page_num, $page_size);
        return $result;
    }


    /**
     * 根据source type和deal crowd判定是否能够投标
     * @param int $source_type 来源类型 0前台正常投标 1后台预约投标 3 ios 4 Android
     * @param int $deal_crowd  投资人群 0全部用户 1 新手专享 2 专享 4 手机专享 8 手机新手专享
     * @param array $user    当前投资用户实体
     */
    public function allowedBidBySourceType($source_type, $deal_crowd, $user)
    {
        // default allowed
        $res_arr = array(
            'error' => false,
            'msg'   => '',
            'is_app_first_loan'   => false,
            'is_first_loan'   => false,
        );
        $user_id = $user['id'];
        $source_type = intval($source_type);
        $deal_crowd  = intval($deal_crowd);

        //如果限制app端标,来源是pc
        if( in_array($deal_crowd, array(DealModel::DEAL_CROWD_MOBILE, DealModel::DEAL_CROWD_MOBILE_NEW))  && $source_type == DealLoadModel::$SOURCE_TYPE['general']) {
            $res_arr['error'] = true;
            $res_arr['msg']   = '请打开手机客户端进行投资。';
        } elseif($deal_crowd == DealModel::DEAL_CROWD_MOBILE_NEW) {  //如果手机新手标
            $dl = new \core\dao\DealLoadModel();
            $app_load_cnt = $dl->getCountByUserIdInSuccess($user_id, array(DealLoadModel::$SOURCE_TYPE['ios'], DealLoadModel::$SOURCE_TYPE['android']));
            if($app_load_cnt > 0) {
                $res_arr['error'] = true;
                $res_arr['msg'] = $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '该项目为新手专享项目，只有初次出借的新用户可以出借' : '该项目为新手专享项目，只有初次投资的新用户可以投资';
            }
        }

        return $res_arr;
    }

    /**
     * 检查是否满足18岁以上可投的条件
     *
     * @param int $year
     * @return boolean 是否可投
     */
    public function allowedBidByCheckAge($user){

        $res_err = array(
                'error' => false,
                'msg'   => '',
        );

        $year = empty($user['byear']) ? 0 : $user['byear'];
        $month = empty($user['bmonth']) ? 0 : $user['bmonth'];
        $day = empty($user['bday']) ? 0 : $user['bday'];

        //按年判断
        /* $now_year = \libs\utils\XDateTime::now()->getYear();
        $age = $now_year - $year;

        if($year > 0 && $age < DealLoadModel::BID_AGE_MIN){
            $res_err = array(
                        'error' => true,
                        'msg'   => sprintf("本项目仅限%d岁及以上用户投资", DealLoadModel::BID_AGE_MIN),
            );
        } */

        //精确到天判断
        if($year > 0){
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $day = str_pad($day, 2, '0', STR_PAD_LEFT);

            $age_min = DealLoadModel::BID_AGE_MIN;
            $now_ymd = \libs\utils\XDateTime::now('Ymd')->getDate();
            if(intval(($year+$age_min).$month.$day) > $now_ymd){
                $res_err = array(
                        'error' => true,
                        'msg'   => sprintf("本项目仅限%d岁及以上用户投资", $age_min),
                );
            }
        }
        return $res_err;
    }

    /**
     * getNoticeRepay
     * 查询出所有的指定天数之内需要还款的标
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-14
     * @access public
     * @return array
     */
    public function getNoticeRepay(){
        $warn_day_end = intval(app_conf('REPAY_WARN_DAY'));
        $warn_day_end = $warn_day_end >= 0 ? $warn_day_end : 10;
        $warn_day_start = $warn_day_end - 1;

        $result = DealModel::instance()->getNoticeRepay($warn_day_start,$warn_day_end);
        return $result;
    }


    /**
     * compareDeleteByIds
     * 比较要删除的 deal_ids
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-17
     * @param array/int $deal_ids
     * @access public
     * @return array
     */
    public function compareDeleteByIds($deal_ids) {
        $deny_ids = $allow_ids = array();
        $allow_ids = dealmodel::instance()->getCanDeleteByIds($deal_ids);
        if (is_array($deal_ids)) {
            if (empty($allow_ids)) {
                $allow_ids = array();
            }
            $deny_ids = array_diff($deal_ids, $allow_ids);
        } else {
            if (count($allow_ids) == 0 ) {
                $deny_ids = $deal_ids;
            }
        }

        return array('allow'=>$allow_ids,'deny'=>$deny_ids);

    }

    /**
     * searchDealById
     * 根据标ID获取标详情
     *
     * @param mixed $dealId
     * @access public
     * @return void
     */
    public function searchDealById($dealId)
    {
        $deals = DealModel::instance()->searchDealById($dealId);
        $result = array();
        foreach ($deals['list'] as $k => $v) {
            $result[$k] = DealModel::instance()->handleDeal($v);
        }
        return $result;
    }

    /**
     * 投资生成红包
     * @param unknown $deal_id
     * @param unknown $loan_id
     * @param unknown $user_id
     * @param unknown $loan_money
     * @return Ambigous <boolean, string, mixed>|boolean
     */
    public function makeBonus($deal_id, $loan_id, $user_id, $loan_money, $site_id = 1){

        $isBlackSite = (new BonusService())->isRepeatDealBlackSite($site_id);
        if ($isBlackSite) {
            return false;
        }

        $userService = new \core\service\UserService($user_id);
        if ($userService->isEnterprise()) { //企业账户不生成红包
            return false;
        }

        // 汇源首投demo
        $loadService = new DealLoadService();
        if ($loan_money >= 100 && $loadService->countByUserId($user_id, false) <= 1) {
            $userTagService = new UserTagService();
            if ($userTagService->getTagByConstNameUserId('LWHYJK_MEMBER', $user_id)) {
                $bonusService = new \core\service\BonusService();
                $couponService = new \core\service\CouponService();
                $user = \core\dao\UserModel::instance()->find($user_id);
                $inviteUser = \core\dao\UserModel::instance()->find($user->refer_user_id);
                if (!empty($inviteUser['group_id']) && $inviteUser['group_id'] == 205) {
                    $bonusService->generateConsumeBonus($inviteUser['id'], 5, 3);
                }
                $rs = $userTagService->addUserTagsByConstName($user_id, 'LWHYJK_PRIZE_2_0');
                return $rs;
            }
        }

        //兼容红包测试
        $uid_test = app_conf('BONUS_TEST_USERID');
        if($uid_test && in_array($user_id, explode(',', $uid_test))){
            $loan_money *= 1000;
        }else{
            $bonus_strategy = $this->getStrategy($loan_money, $deal_id);
            $bonus_strategy->deal_id = $deal_id;
            $bonus_strategy->loan_id = $loan_id;
            $bonus_strategy->user_id = $user_id;
            $bonus_strategy->loan_money = $loan_money;
            $rs = $bonus_strategy->makeBonus();
            return $rs;
        }

        return false;
    }

    public function sendDealMessage($deal_id, $type) {

        // 加入判断 防止异步粗暴的执行
        $deal = DealModel::instance()->find($deal_id);

        if(empty($deal) || !in_array($deal['deal_status'], array(2,4,5))){
            return false;
        }

        // 如果是交易所的标的， 并且是收费后放款，就给借款人发短息
        $deal_ext_info = DealExtModel::instance()->getDealExtByDealId($deal_id);
        if (($type == 'full') && ($deal->deal_type == DealModel::DEAL_TYPE_EXCHANGE) && ($deal_ext_info->loan_type == UserCarryModel::LOAN_AFTER_CHARGE)) {
            $project_info = DealProjectModel::instance()->findViaSlave($deal['project_id']);
            $deal_advisory_info = DealAgencyModel::instance()->getDealAgencyById($deal->advisory_id);
            $user_info = UserModel::instance()->findViaSlave($deal_advisory_info->agency_user_id, 'id,mobile');
            $fee = DealModel::instance()->getAllFee($deal_id);

            $borrowUserService = new UserService($deal['user_id']);
            if($borrowUserService->isEnterprise()){
                $enterprise_info = $borrowUserService->getEnterpriseInfo(true);
                $borrowName = $enterprise_info['company_name'];
            }else{
                $company_info = UserCompanyModel::instance()->findByUserId($deal['user_id']);
                $borrowName = $company_info['name'];
            }

            // 满标发送短信通知标的咨询机构的代理人
            $params = array($borrowName,$project_info['name'],format_price($deal['borrow_amount']),format_price(array_sum($fee)));
            //签名 网信 发行人：{$var1}，{$var2}已满标，募集金额{$var3}元，应收各项费用合计{$var4}元。
            $res = SmsServer::instance()->send($user_info->mobile, 'TPL_SMS_DEAL_FULL_JYS_FEE', $params, $user_info->id,1);
            if($res) {
                Logger::info(sprintf('send fee-msg-event success,deal_id:%d, params:%s [%s:%s]',$deal_id, json_encode($params), __FILE__, __LINE__));
            } else {
                Logger::error(sprintf('send fee-msg-event fail,deal_id:%d, params:%s [%s:%s]', $deal_id, json_encode($params), __FILE__, __LINE__));
                return false;
            }
        }

        return send_full_failed_deal_message($deal, $type);
    }

    /**
     * getStrategy
     * 获得具体的红包策略类
     *
     * @param mixed $loan_money 投资金额
     * @author zhanglei5
     * @access private
     * @return BonusStrategy
     */
    private function getStrategy($loan_money, $deal_id = 0) {
        $bonus_service = new \core\service\BonusService();
        $bonus_service->loan_money = $loan_money;
        //$is_xql = $bonus_service->checkXql($loan_money);
        $xql_conf = $bonus_service->getSuperConf($loan_money, $deal_id);
        if ($xql_conf && is_array($xql_conf)) {
            $strategy = new \core\service\bonus\XqlBonusStrategy();
            list($strategy->xql_id, $strategy->money, $strategy->group_count, $strategy->bonus_count, $strategy->send_limit_day, $strategy->use_limit_day) = $xql_conf;
        } else {
            $strategy = new \core\service\bonus\NormalBonusStrategy();
        }
        $strategy->bonus_service = $bonus_service;
        return $strategy;

    }

    /**
     * getManualColumnsVal
     * 获取选定列信息
     *
     * @param mixed $dealId
     * @param mixed $columnsStr
     * @access public
     * @return void
     */
    public function getManualColumnsVal($dealId, $columnsStr)
    {
        $model = new DealModel();
        $dealInfo = $model->find($dealId, $columnsStr, true);
        return $dealInfo;
    }

    /**
     * getExtManualColumnsVal
     * 获取选定列信息
     *
     * @param mixed $dealId
     * @param mixed $columnsStr
     * @access public
     * @return void
     */
    public function getExtManualColumnsVal($dealId, $columnsStr)
    {
        $model = new DealExtModel();
        $extInfo = $model->findByViaSlave('deal_id='.$dealId, $columnsStr);
        return $extInfo;
    }

    public function getFullStatistics($start, $end)
    {
        $status = 2;
        $m_deal = new DealModel();
        $m_deal_load = new DealLoadModel();
        $full_list = $m_deal->getByStatusTime($status, $start, $end);
        if (count($full_list) > 0) {
            $ids = $arr = array();
            foreach($full_list as $row) {
                $ids[] = $row['id'];
                $arr[$row['id']] = $row;
            }

            $load_list = $m_deal_load->getCountByDealIds($ids);

            foreach ($load_list as &$row) {
                $deal_id = $row['deal_id'];
                $row['name'] = $arr[$deal_id]['name'];
            }
            return $load_list;
        }

    //    var_dump($load_list);
    }

    /**
     * 满标变更合同状态的任务，由gearman迁移出来
     */
    public function DealFullChecker($param) {
        $op_status_id = $param['op_status_id'];
        $checker = new DealFullCheckerEvent($op_status_id);
        $r = $checker->execute();

        if ($r === false) {
            throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        }

        return true;
    }

    /**
     * 满标后为防止gearman生成合同异常或延迟，检测状态并通过deal_load补发未生成合同
     */

    public function DealCheckContract($params){

        $deal_id = $params['deal_id'];
        $deal_load = new DealLoadService();
        $contract_model = new ContractModel();
        $op_log_model = new OpLogModel();
        $op_status_model = new OpStatusModel();
        $deal_loads = $deal_load->getDealLoanListByDealId($deal_id);
        $op_log_count = $op_log_model->countViaSlave("op_name = 'DEAL_SEND_CONTRACT_".$deal_id."'");

        //处理send contract遗漏或生成失败的合同
        foreach($deal_loads as $loads){
            if(is_object($loads)){
                $loads = $loads->getRow();
                $contract = $contract_model->findAllViaSlave("deal_load_id = '".$loads['id']."'",true,'id');
                if(empty($contract)){
                    $op_log = $op_log_model->findByViaSlave("op_content = '".$loads['id']."'","id,update_time,op_status");
                    //如果没有生成 op_log 记录，先补充op_log记录
                    if(!isset($op_log['id'])){
                        $op_log_id = $op_log_model->insert_deal_contract($deal_id,$loads['id']);
                        if($op_log_id){
                            $op_log = $op_log_model->find($op_log_id);
                            $op_log_count += 1;
                        }
                    }
                    if(($op_log['id'] > 0) && ($op_log['op_status'] <= 0)){
                        $op_log_id = $op_log['id'] > 0 ? $op_log['id']:false;
                        $event = new SendContractEvent($deal_id,$loads['id'], false, $op_log_id, $op_log['update_time']);
                        $send_contract_event = $event->execute();
                        if(!$send_contract_event){
                            throw new \Exception('发送合同失败 DealCheckContract one - '.$deal_id);
                        }

                    }
                }
            }
        }
        //处理满标遗漏或生成失败的合同
        $contract = $contract_model->findAll("deal_id = '".$deal_id."' AND deal_load_id = 0",true,"id");
        if(empty($contract)){
            $op_log = $op_log_model->findBy("op_content = '0' AND op_name = 'DEAL_SEND_CONTRACT_".$deal_id."'","id,update_time,op_status");
            //由于满标的op_log记录在投资事物中，所以不需要检测是否存在
            if(($op_log['id'] > 0) && ($op_log['op_status'] == 0)){
                $op_log_id = $op_log['id'] > 0 ? $op_log['id']:false;
                $event = new SendContractEvent($deal_id,$loads['id'], true, $op_log_id, $op_log['update_time']);
                $send_contract_event = $event->execute();
                if(!$send_contract_event){
                    throw new \Exception('发送满标合同失败 DealCheckContract Full - '.$deal_id);
                }
            }

        }

        //确保op_status状态更新为成功
        if(count($deal_loads) == ($op_log_count-1)){
            $op_status = $op_status_model->findByViaSlave("op_name = 'DEAL_SEND_CONTRACT_".$deal_id."' AND trans_status = 0","id,trans_status,update_time");
            if($op_status['trans_status'] == 0){
                $deal = DealModel::instance()->find($deal_id);
                if(((substr($deal['contract_tpl_type'],0,5)) === 'NGRZR') OR ((substr($deal['contract_tpl_type'],0,5)) === 'NQYZR')){
                    $deal['contract_version'] = 2;
                }
                $deal_contract_model = new DealContractModel();
                $deal_contract_model->create($deal);
                $op_status_model->update_status($op_status['id'],1);
            }
        }else{
            throw new \Exception('发送合同失败op log记录数存在差异 DealCheckContract Full - '.$deal_id);
        }

        return true;
    }


    /**
     * 即付类型标的
     * 两种判断模式，直接传入site_id判断，或者根据deal_id从数据库判断
     * @param int $site_id
     * @param int $deal_id
     * @return bool
     */
    public function isDealJF($site_id, $deal_id=false) {
        $site_jf = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];
        if (!empty($site_id)) {
            return $site_id == $site_jf;
        } else {
            $ds_model = new DealSiteModel();
            return $ds_model->isDealSiteExists($deal_id, $site_jf);
        }
    }

    /**
     * 判断是否是多投宝标的
     * @param int
     */
    public function isDealDT($deal_id) {
        $tag_service = new DealTagService();
        $tags = $tag_service->getTagListByDealId($deal_id);
        return in_array(DtDealService::TAG_DT, $tags);
    }

    /**
     * 判断是否是农旦标
     */
    public function isDealND($deal_id){
        $deal= DealModel::instance()->find(intval($deal_id), 'type_id', true);
        $ndTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_NDD);
        return ($ndTypeId == $deal->type_id) ? true : false;
    }

    /**
     * 是否是电商互联标的
     * @param $deal
     * @return bool
     */
    public function isDealDSD($deal){
        $DSDTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DSD);
        return ($DSDTypeId == $deal->type_id) ? true : false;
    }

    /**
     * 判断是否是智多鑫三期清盘的标的
     * @param int
     */
    public function isDealDTV3($deal_id) {
        $tag_service = new DealTagService();
        $tags = $tag_service->getTagListByDealId($deal_id);
        return in_array(DtDealService::TAG_DT_V3, $tags);
    }

    /**
     * 判断是否为哈哈农庄化肥定制标
     * @param int $deal_id
     * @return bool
     */
    public function isDealHF($deal_id) {
        $dealTagService = new DealTagService();
        $dealTag = $dealTagService->getTagByDealId($deal_id);
        if(in_array('HHNZ_TRANSCAPITAL',$dealTag)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 判断标的是否走p2p存管流程
     * @param $mixed deal ID 或者DealModel对象
     */
    public function isP2pPath($mixed) {

        if(is_array($mixed) && isset($mixed['report_status'])){
            $reportStatus = $mixed['report_status'];
        }elseif($mixed instanceof DealModel){
            $reportStatus = $mixed['report_status'];
        }elseif(is_integer($mixed)){
            // 从主库获取标的信息
            $deal = $this->getDeal($mixed, false, false);
            $reportStatus = $deal['report_status'];
        }else{
            throw new \Exception("Params error");
        }
        return $reportStatus == 1 ? true :false;
    }

    /**
     * 是否需要报备到存管行
     * @param  $dealId
     * @param $dealTag 逗号分隔
     */
    public function isNeedReportToBank($dealId,$dealTag=''){

        $deal = $this->getDeal($dealId, true, true);

        // 1、如果手动设置为需要报备则一定报备
        if($deal['report_type'] == DealModel::DEAL_REPORT_TYPE_YES){
            return true;
        }

        if((int)app_conf('SUPERVISION_SWITCH') !== 1){
            return false;
        }

        //哈哈农庄化肥标不允许报备
        if($this->isDealHF($dealId)) {
            return false;
        }

        return $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? true :false;
    }

    /**
     * 获取标的deal_type
     * @param $mixed
     * @return int
     */
    public function getDealType($mixed){
        return $this->isP2pPath($mixed) ? DealModel::DEAL_TYPE_SUPERVISION : $mixed['deal_type'];
    }
    /**
     * 获取用户所投资的哈哈农庄化肥标总金额
     * @param int $userId 用户id
     * @return bool
     */
    public function getUserHFLoanAmount($userId) {
        $laonAmount = 0;
        //查询tagid
        $tagModel = new TagModel();
        $tagInfos =  $tagModel->getTags(array('HHNZ_TRANSCAPITAL'));
        if(empty($tagInfos) || count($tagInfos) < 1) {
            return $laonAmount;
        }
        //获取所有tag为当前tag的dealid
        $dealTagModel = new DealTagModel();
        $dealTags =  $dealTagModel->findAll("tag_id = '" . $tagInfos[0]['id'] . "'",true, 'deal_id');
        if(empty($dealTags)) {
            return $laonAmount;
        }
        $dealIds = array();
        foreach ($dealTags as $dealTag) {
            $dealIds[] = $dealTag['deal_id'];
        }
        if(empty($dealIds)) {
            return $laonAmount;
        }
        //筛选还款中的所有dealid
        $dealStr = implode(",", $dealIds);
        $condition = sprintf("`deal_status`='4' AND `deal_type` = 0 AND `is_delete`='0' AND `id` IN (%s)", $dealStr);
        $deals = DealModel::instance()->findAll($condition,true, 'id');
        if(empty($deals)) {
            return $laonAmount;
        }
        $dealIds = array();
        foreach ($deals as $deal) {
            $dealIds[] = $deal['id'];
        }
        //计算dealload总投资金额
        $dealStr = implode(",", $dealIds);
        $dealLoadModel = new DealLoadModel();
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE user_id = %s AND deal_id IN (%s)";
        $sql = sprintf($sql, $dealLoadModel->tableName(), $userId ,$dealStr);
        $result = $dealLoadModel->findBySql($sql,array(), true);
        $laonAmount = DealModel::instance()->floorfix($result['sum']);
        return $laonAmount;
    }

    /**
     * 完成充值时即付转账
     * @param object $user
     * @param float $money
     * @param int $deal_id
     * @param string $order_id
     * @return bool
     */
    public function transferBidJF($user, $money, $deal_id, $order_id) {
        $user->changeMoney($money, '充值', "投资人向即付宝发出付款指令，由其将款项划转至投资人支付账户，标的:{$deal_id}，订单号:{$order_id}");

        $user_jf = UserModel::instance()->find(app_conf('AGENCY_ID_JF'));
        $user_jf->changeMoney(-$money, '转账', "用户充值投资，标的:{$deal_id}，订单号:{$order_id}", 0, 0, 0, 1); // 此账户允许扣负

        $syncRemoteData[] = array(
            'outOrderId' => 'TRANSFER_JF|' . $order_id,
            'payerId' => $user_jf['id'],
            'receiverId' => $user['id'],
            'repaymentAmount' => bcmul($money, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 6,
        );
        $financeIdArr =  FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
        return $financeIdArr[0];
    }

    /**
     * 完成回款时即付转账并自动提现
     * @return bool
     */
    public function transferRepayJF($user, $money, $deal_id, $order_id) {
        $user->changeMoney(-$money, '转账', "支付公司将款项划转至投资人支付账户，投资人委托即付宝将该笔款项转入即付宝账户，标的:{$deal_id}，订单号:{$order_id}");

        $user_jf = UserModel::instance()->find(app_conf('AGENCY_ID_JF_REPAY'));
        $user_jf->changeMoney($money, '转账', "用户回款，标的:{$deal_id}，订单号:{$order_id}");

        $syncRemoteData[] = array(
            'outOrderId' => 'TRANSFER_JF|' . $order_id,
            'payerId' => $user['id'],
            'receiverId' => $user_jf['id'],
            'repaymentAmount' => bcmul($money, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 7,
        );
        $financeIdArr = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
        return $financeIdArr[0] && ThirdpartyOrderModel::instance()->updateRepayTransferId($order_id,$financeIdArr[0]);
    }

    /**
     * 新的提现方法解除业务耦合
     * @param $uid 提现人
     * @param $money 提现金额
     * @param $deal_id 提现对应的标ID
     * @return array
     * @throws \Exception
     */
    public function jfWithdrawal($uid,$money,$deal_id){
        //获取用户银行卡信息
        $bankcard_info = UserBankcardModel::instance()->getUserBankCardRow('user_id='.$uid);
        // 如果用户没有银行卡信息或者信息没有确认保存过
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            throw new \Exception("未找到用户开卡信息user_id:".$uid);
        }
        if(!$deal_id) {
            throw new \Exception("标ID不能为空");
        }
        if(!$money) {
            throw new \Exception("提现金额需大于0 money:".$money);
        }

        $user = UserModel::instance()->find($uid);
        if(empty($user)){
            throw new \Exception("查询不到当前用户".$uid);
        }

        $userCarry = new UserCarryModel();
        // 银行信息
        $userCarry->bank_id      = $bankcard_info['bank_id'];
        $userCarry->real_name    = $bankcard_info['card_name'];
        $userCarry->region_lv1   = $bankcard_info['region_lv1'];
        $userCarry->region_lv2   = $bankcard_info['region_lv2'];
        $userCarry->region_lv3   = $bankcard_info['region_lv3'];
        $userCarry->region_lv4   = $bankcard_info['region_lv4'];
        $userCarry->bankcard     = $bankcard_info['bankcard'];
        $userCarry->bankzone     = $bankcard_info['bankzone'];
        $userCarry->user_id      = $uid;
        $userCarry->money        = $money;
        $userCarry->fee          = 0; // 目前没有提现手续费
        $userCarry->create_time  = get_gmtime();
        $userCarry->update_time  = get_gmtime();
        $userCarry->status       = 3; // 默认审批通过
        //$userCarry->deal_id      = $deal_id;
        $userCarry->type         = 1;

        $redb = $userCarry->save();
        if($redb){
            $user->changeMoneyAsyn = true;
            $result = $user->changeMoney($userCarry->money,"提现申请",'系统发起提现 借款编号：'.$deal_id,
                0,0,UserModel::TYPE_LOCK_MONEY);
            if($result){
                return true;
            }else{
                throw new \Exception("冻结资金失败，借款编号：".$deal_id);
            }
        }else{
            throw new \Exception("提现申请失败，借款编号：".$deal_id);
        }
        return true;
    }

    public function dealEvent($userId, $money, $couponId, $dealLoadId, $isRedeem = false, $siteId = 1) {
        $tagService = new UserTagService();
        $dealLoadService = new DealLoadService();

        try {
            $firstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
            if ($dealLoadId > 0 && $firstDeal['id'] == $dealLoadId) {
                // 首投回调
                $digObject = new \core\service\DigService('firstLoan', array(
                    'id' => $userId,
                    'cn' => $couponId,
                    'loadid' => $dealLoadId,
                    'isRedeem' => $isRedeem,
                    'money' => $money,
                    'siteId' => $siteId,
                ));
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * 获取分站投资列表
     * author  ：耿宽
     *
     * @param $have_crowd_specific 是否显示‘特定用户组’的标
     * @param $needCount 是否需要统计count信息 默认统计
     */
    public function getListBySiteId($page, $page_size=0,$site_id,$map =array()) {

        $type = isset($type) ? $type : null;
        $field = isset($field) ? $field : null;
        $sort['field'] = isset(self::$FIELD_HASH[$field]) ? self::$FIELD_HASH[$field] : null;
        $sort['type'] = isset(self::$TYPE_HASH[$type]) ? self::$TYPE_HASH[$type] : null;
        $page = $page<=0 ? 1 : $page ;
        $page_size = $page_size<=0 ? app_conf("DEAL_PAGE_SIZE") : $page_size ;
        $deals = DealModel::instance()->getListBySiteId($sort, $page, $page_size,$site_id,$map);

        return $deals;
    }

    public function isExistSiteById($id){
        $deals = DealSiteModel::instance()->getAllSitesByDeal($id);
        return $deals;
    }

    /**
     * 通过队列id获取获取标列表
     * @param int $queueId
     * @param int $siteId
     * @return array
     */
    public function getDealListByQueueId($queueId,$siteId){
        $dealList = array();
        $queueId = intval($queueId);
        if(!empty($queueId)){
            $deal_ids = DealQueueInfoModel::instance()->getDealIdsByQueueId($queueId);
            if(!empty($siteId) && !empty($deal_ids)){
                $dealSiteModel = new DealSiteModel();
                $deal_ids = $dealSiteModel->filterDealIdsBySiteIdNotInDealIds($deal_ids, $siteId);
            }
            if(!empty($deal_ids)){
                $dealModel = new DealModel();
                $dealList = $dealModel->findAllViaSlave('id in('.implode(',', $deal_ids).') AND deal_type != 1 AND is_delete = 0 ',true);
            }
        }
        return $dealList;
    }

    /**
     * 获取上标的列表
     * @param intval $siteId
     * @return array
     */
    public function getAddQueueDealList($siteId){
        $dealQueueInfoModel = new DealQueueInfoModel();
        $notIndealIds = $dealQueueInfoModel ->getDealIdsByQueueId(0);
        $dealSiteModel = new DealSiteModel();
        $deal_ids = $dealSiteModel->filterDealIdsBySiteIdNotInDealIds(array(), $siteId ,$notIndealIds);
        $dealModel = new DealModel();
        $dealList = array();
        if(!empty($deal_ids)){
            $dealList = $dealModel->findAllViaSlave('id in('.implode(',', $deal_ids).') AND deal_type != 1 AND `deal_status` IN (0,1) AND `is_delete`=0 AND `publish_wait`=0 ',true);
        }
        return $dealList;
    }
    /**
     * 通过id获取队列信息
     * @param intval $siteId
     * @return array
     */
    public function getQueueById($id) {
        $id = intval($id);
        $dealQueueModel = new DealQueueModel();
        $deal_queue = $dealQueueModel->findAllViaSlave(" id = {$id} ",true);
        return $deal_queue[0];
    }

    /**
     * 给队列插入标
     * @param intval $siteId
     * @return array
     */
    public function inserDealToQueue($dealId,$queueId) {

        $dealQueueModel = new DealQueueModel();
        $result = $dealQueueModel->insertDealQueue($queueId,$dealId);
        return $result;
    }
/**
* 给队列插入标
* @param intval $siteId
* @return array
*/
    public function deleteDealToQueue($dealId,$queueId) {
        $dealQueueModel = new DealQueueModel();
        $result = $dealQueueModel->deleteDealQueue($queueId,$dealId);
        return $result;
    }

    /**
     *删除队列中的标
     * @param intval $siteId
     * @return array
     */
    public function deleteDealQueue($dealId,$queueId) {
        $dealQueueModel = new DealQueueModel();
        $result = $dealQueueModel->deleteDealQueue($queueId,$dealId);
        return $result;
    }

    /**
     * 初始化标的信息
     * @param int $deal_id
     * @param int $is_credit 是否是信贷传过来的标，1：信贷
     * @return bool
     * @throw \Exception
     */
    public function initDeal($deal_id, $is_credit = 1) {
        try {
            if (empty($deal_id)) {
                throw new \Exception('invalid deal_id');
            }

            $deal = DealModel::instance()->find(intval($deal_id));
            if (empty($deal)) {
                throw new \Exception('empty deal info');
            }

            // FIRSTPTOP-3960 大金所只有信贷更新标名
            if (1 == $is_credit) {
                // JIRA#3271 平台产品名称定义 - 更新标的名
                if (!$this->updateDealNameAndPrefix($deal['id'], $deal['project_id'], self::DEAL_NAME_PREFIX)) {
                    throw new \Exception('update deal name fail');
                }
            }

            $deal['deal_status'] = 0;
            $state_manager = new \core\service\deal\StateManager();
            $state_manager->setDeal($deal);
            $state_manager->work();
            Logger::info(sprintf('success:init deal,deal_id:%d,file:%s,line:%s', $deal_id, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('fail:init deal,deal_id:%d,error-msg:%s,file:%s,line:%s', $deal_id, $e->getMessage(), __FILE__, __LINE__));
            throw $e;
        }
    }

    /**
     * @author : fanjingwen@ucfgroup.com
     * @function :  根据标id、标名前缀，更新标名
     * @param : $dealID
     * @param : $projectID
     * @return : string 插入的标名
     */
    public function updateDealName($dealID, $projectID)
    {
        if (empty($dealID) || empty($projectID)) {
            return '';
        }

        // 生成标的名称
        $dealName = $this->createDealName($dealID, $projectID);

        $date = ['name' => $dealName];
        $cond = "`id` = '{$dealID}'";
        $rs = DealModel::instance()->updateBy($date, $cond);
        if (empty($rs)) {
            throw new \Exception("标的名称更新失败" . $dealID);
        }

        return $dealName;
    }

    /**
     * @author : fanjingwen@ucfgroup.com
     * @function : 根据标id、标名前缀，生成标名
     * @param : $dealID
     * @param : $projectID
     * @return : string 标名 (prefix + projectName + 'A' . postfix)
     */
    public function createDealName($dealID, $projectID)
    {
        $projectObj = DealProjectModel::instance()->find($projectID);
        $dealObj = DealModel::instance()->find($dealID, 'id,type_id,agency_id');

        // JIRA#3844 产品名称整体更新
        $product_class = empty($projectObj) ? '' : $projectObj->product_class;

        // deal id补全9位
        $idStr = str_pad(strval($dealID), 9, strval(0), STR_PAD_LEFT);
        $nddTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_NDD);

        // 农担支农贷，则使用定制的命名方式  name = 国担支农贷 + 空格 + 担保机构简写 + 空格 + 编号
        if($nddTypeId == $dealObj['type_id']){
            $agencyObj = DealAgencyModel::instance()->getDealAgencyById($dealObj['agency_id']);
            // 担保机构名称前面加个空格
            $agencyName = empty($agencyObj['short_name']) ? '' :  ' '.$agencyObj['short_name'];
            $dealName = '国担支农贷' . $agencyName . ' A' . $idStr;
            return $dealName;
        }

        $dealName = $product_class . 'A' . $idStr;

        return $dealName;
    }

    /**
     * @author : fanjingwen@ucfgroup.com
     * @function : 根据项目di 获取 projectName
     * @param : $projectID
     * @return : string
     */
    public function getProjectName($projectID)
    {
        // 获取项目名
        $projectObj = DealProjectModel::instance()->find($projectID);
        $projectName = empty($projectObj) ? '' : $projectObj->name;

        return $projectName;
    }

    /*
     * @author : fanjingwen@ucfgroup.com
     * @function :  根据标id、标名前缀，更新标名 及标的名的前缀字段
     * @param : $dealID
     * @param : $projectID
     * @param : $prefixTitle 标名前缀
     * @return boolean
     */
    public function updateDealNameAndPrefix($dealID, $projectID, $prefixTitle)
    {
        if (empty($dealID) || empty($projectID)) {
            return false;
        }

        // 生成标的名称
        $dealName = $this->createDealName($dealID, $projectID);

        $date = ['name' => $dealName];
        $cond = "`id` = '{$dealID}'";

        // 同步更新标名，及其前缀字段
        $GLOBALS['db']->startTrans();
        try
        {
            // 更新标名
            $rsDeal = DealModel::instance()->updateBy($date, $cond);
            if (empty($rsDeal)) {
                throw new \Exception("标的名称更新失败" . $dealID);
            }

            // 更新前缀
            DealExtModel::instance()->updateDealNamePrefix($dealID, $prefixTitle);

            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:update_deal_name,id:%d,new_name:%s,file:%s,line:%s', $dealID, $dealName, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail:update_deal_name,id:%d,new_name:%s,error-msg:%s,file:%s,line:%s', $dealID, $dealName, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * [判断标的是否满足放款需求]
     * @author <fanjingwen@ucfgroup.com>
     * @param array $dealInfo [对应deal表中的key-value]
     * @return boolen [false:throw]
     */
    public function isOKForMakingLoans($dealInfo)
    {
        if(empty($dealInfo)) {
            throw new \Exception("无法找到id为{$dealInfo['id']}的标");
        }

        // 标没有满标
        if(!in_array($dealInfo['deal_status'], array(2,4))) {
            throw new \Exception("标还未满标");
        }

        //除公益标，附件合同除外，验证合同状态
        $contract_new_service = new ContractNewService();
        $contract_category_info = $contract_new_service->getCategoryByCid($dealInfo['contract_tpl_type']);
        if(false === $contract_category_info) {
            throw new \Exception("合同服务异常");
        }
        if(!empty($contract_category_info) && !in_array($contract_category_info['typeTag'], ContractModel::$tpl_type_tag_attachment)){
            //验证合同是否已生成,添加合同服务化标的逻辑
            if(!is_numeric($dealInfo['contract_tpl_type'])){
                $contract_service = new ContractService();
                $contract_count = $contract_service->getCountByDealid($dealInfo['id']);
                if($contract_count == 0) {
                    throw new \Exception("合同尚未生成");
                }
                //验证合同是否已经签署
                $notsign_count = $contract_service->getNotSginCountByDealid($dealInfo['id']);
                if($notsign_count) {
                    throw new \Exception("借款人或担保公司的合同未通过");
                }
            }else{
                $dealContractModel = new DealContractModel();
                if(!$dealContractModel->getDealContractUnSignInfo($dealInfo['id'])){
                    //如果不是公益标,则合同不通过
                    if(!$this->isDealCrowdfunding($dealInfo['id'])){
                        throw new \Exception("借款人或担保,咨询公司,委托人的合同未通过");
                    }
                }
            }
        }

        // 检查机构账户
        $dealAgcServs = new DealAgencyService();

        if( $dealInfo['isDtb'] == 1) {//多投宝验证管理机构
            $management_agency_info = $dealAgcServs->getDealAgency($dealInfo['management_agency_id']); // 管理机构
            if(empty($management_agency_info) || empty($management_agency_info['user_id'])) {
                throw new \Exception('管理机构信息有误');
            }
        }

        //已经放过款
        if($dealInfo['is_has_loans'] != 0) {
            throw new \Exception("已经放过款");
        }

        return true;
    }

    /**
     * 根据标的ID,获取还款人id
     * @author <wangjiantong@ucfgroup.com>
     * @param int $dealId 0:借款人,1:代垫机构
     * @param int $type 0:借款人,1:代垫机构 默认代垫
     * @return int accountUserId; 还款用户id
     */

    public function getRepayUserAccount($dealId,$type=1){

        $deal = DealModel::instance()->find(intval($dealId));
        if($type == 0){
            return $deal['user_id'];
        }else if($type == 1){//代垫-代垫机构
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['advance_agency_id']));
            if($dealAgency){
                return $dealAgency['user_id'];
            }
        }else if($type == 2){//代偿 担保机构代偿
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['agency_id']));
            if($dealAgency){
                return $dealAgency['user_id'];
            }
        }else if($type == 3){//代充值
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['generation_recharge_id']));
            if($dealAgency){
                return $dealAgency['user_id'];
            }
        }else if($type == 4){//代扣
            return $deal['user_id'];
        }else if($type == 5){ //间接代偿 担保机构代偿
            $dealAgency = DealAgencyModel::instance()->find(intval($deal['agency_id']));
             if($dealAgency){
                 return $dealAgency['user_id'];
             }
        }
        return false;
    }

    /**
     * 获取还款人账户
     * @param $dealId
     * @param bool|false $accountType
     */
    public function getRepayAccountTypeByDeal(DealModel $deal){

        // 专享、交易所、小贷走超级账户还款
        if(in_array($deal['deal_type'],array(
            DealModel::DEAL_TYPE_EXCHANGE,
            DealModel::DEAL_TYPE_EXCLUSIVE,
            DealModel::DEAL_TYPE_PETTYLOAN,
        ))){
            return DealRepayModel::DEAL_REPAY_TYPE_SELF;
        }

        $dealTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);
        if(!$dealTypeTag){
            return false;
        }

        // 网贷已报备标的 产融贷走借款人存管账户还款 其它走代充值
        if($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && $deal['report_status'] == DealModel::DEAL_REPORT_STATUS_YES) {

            // 电商贷默认走代垫还款
            if($dealTypeTag == DealLoanTypeModel::TYPE_DSD){
                return DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN;
            }
            if($dealTypeTag != DealLoanTypeModel::TYPE_CR) {
                return DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI;
           }
        }

        // 网贷未报备标的 产融贷、房贷、应收贷、融艺贷、个人消费走超级账户还款 其它走待垫账户还款
        if($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && $deal['report_status'] == DealModel::DEAL_REPORT_STATUS_NO) {
            if(!in_array($dealTypeTag,array(DealLoanTypeModel::TYPE_CR, DealLoanTypeModel::TYPE_FD, DealLoanTypeModel::TYPE_YSD, DealLoanTypeModel::TYPE_ARTD, DealLoanTypeModel::TYPE_GRXF))){
                return DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN;
            }
        }
        return DealRepayModel::DEAL_REPAY_TYPE_SELF;
    }

    /**
     * [根据机构id，获取其下对应的标的列表]
     * @param int [$agencyID]
     * @param int [$agencyType: 对应deal_agency表type]
     * @param $page int
     * @param $page_size int
     * @param $is_all_site bool
     * @param int [$site_id]
     * @param bool $needCount 默认为true 为false的话不需要进行count统计
     * @return array
     */
    public function getListByAgency($agencyID, $agencyType, $page, $page_size=0, $is_all_site = false, $site_id = 0, $needCount = true)
    {
        // 如果是平台机构-网站 - 因为相关联字段不在deal表中
        if (DealAgencyModel::TYPE_PLATFORM == $agencyType) {
            $option = array();
            // 获取机构的site_id
            $agencyObj = DealAgencyModel::instance()->getDealAgencyById($agencyID);
            $site_id = $agencyObj->site_id;
        } else {
            $agencyKey = $this->getAgencyKeyInDealTable($agencyType);
            $option[$agencyKey] = $agencyID;
        }

        $deals = DealModel::instance()->getList(0 , array(), $page, $page_size, $is_all_site, true, $site_id, $option, false, false, false, $needCount);
        $list = $deals['list'];
        foreach ($list as $key => $deal) {
            $list[$key] = DealModel::instance()->handleDealNew($deal);
        }
        if($needCount) {
            $result['count'] = $deals['count'];
        }

        $data['list'] = $list;
        $result['list'] = $data;
        $result['page_size'] = $page_size;

        return $result;
    }

    /**
     * [根据机构类型获取其在deal表中不同的字段名]
     * @param int [$agencyType: 对应deal_agency表type]
     * @return string [对应deal表中的机构相关字段名]
     */
    public function getAgencyKeyInDealTable($agencyType)
    {
        return DealModel::$agencyKey[$agencyType];
    }

    /**
     * [判断是否是公益标]
     * <fanjingwen@ucf>
     * @param int [$deal_id]
     * @return boolen
     */
    public function isDealCrowdfunding($deal_id) {
        $deal = $this->getDeal($deal_id, true, false);
        if ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
            return true;
        }
        return false;
    }

    /**
     * @根据信贷审批单号查询标id
     * @param  string $approve_number
     * @return bool
     */
    public function findApproveNumberDealId($approve_number)
    {
        $dealModel = new DealModel();
        return $dealModel->findBy("approve_number = '" . $dealModel->escape($approve_number) . "'", 'id', array(), true);
    }

    /**
     * @根据信贷审批单号查询标
     * @param  string $approveNumber
     * @return bool
     */
    public function getDealByApproveNumber($approveNumber, $fields = "*")
    {
        $dealModel = new DealModel();
        return $dealModel->findBy("approve_number = '" . $dealModel->escape($approveNumber) . "'", $fields, array(), true);
    }

    /**
     * 通过标的ID 获取标的咨询机构的关联用户ID
     * @param $dealId
     */
    public function getDealConsultRelateUserId($dealId) {
        $deal_model = new DealModel();
        $deal = $deal_model->find($dealId);
        if(!$deal) {
            return false;
        }
        $agenceInfo = DealAgencyModel::instance()->findViaSlave($deal->advisory_id);
        return $agenceInfo ? $agenceInfo->user_id : false;
    }

    /**
     * 截标
     * @param int $deal_id
     * @return bool
     */
    public function cutDeal($dealId, $returnInfo = false) {
        $GLOBALS['db']->startTrans();
        try {
            $dealData = new \core\data\DealData();
            if ($dealData->lockDealBid($dealId) === false) {
                throw new \Exception("标的正处于投资状态");
            }
            $dealInfo = DealModel::instance()->findViaSlave($dealId)->getRow();

            if ($dealInfo['deal_status'] != 1) {
                throw new \Exception('只有状态为“进行中”的标才能修改为满标');
            }

            if ($dealInfo['load_money'] <= 0) {
                throw new \Exception('投资额为0的标禁止修改为满标');
            }

            $before_amount = $dealInfo['borrow_amount'];

            $r = DealModel::instance()->updateMoney($dealId);
            if ($r === false) {
                throw new \Exception('更新标的金额失败');
            }

            //更新项目信息
            $deal_pro_service = new DealProjectService();
            $r = $deal_pro_service->updateProBorrowed($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目金额失败');
            }

            $r = $deal_pro_service->updateProLoaned($dealInfo['project_id']);
            if ($r === false) {
                throw new \Exception('更新项目已投金额失败');
            }

            $deal_info = DealModel::instance()->find($dealInfo['id']);
            $dinfo = $deal_info->getRow();

            $dinfo['borrow_sum'] = $dinfo['borrow_amount'];
            //发送消息,此处只会给单一标发送
            $function  = '\send_full_failed_deal_message';
            $params = array('deal' => $dinfo, 'type' => 'full');
            $r = \core\dao\JobsModel::instance()->addJob($function, $params);
            if ($r === false) {
                throw new \Exception('添加满标任务失败');
            }
            // 改为队列发送合同
            $jobsModel = new JobsModel();

            $contract_function = '\core\service\DealLoadService::sendContract';
            $contract_param = array(
                'deal_id' => $dealId,
                'load_id' => 0,
                'is_full' => true,
                'create_time' => time(),
            );

            $jobsModel->priority = 121;
            $contract_ret = $jobsModel->addJob($contract_function, array('param' => $contract_param)); //不重试
            if ($contract_ret === false) {
                throw new \Exception('满标合同任务插入注册失败');
            }

            $full_ckeck_function = '\core\service\DealLoadService::fullCheck';
            $full_ckeck_param = array(
                'deal_id' => $dealId,
            );
            $jobsModel->priority = 122;
            $full_check_ret = $jobsModel->addJob($full_ckeck_function, array('param' => $full_ckeck_param), get_gmtime() + 1800); //不重试
            if ($full_check_ret === false) {
                throw new \Exception('检测标的合同任务注册失败');
            }

            //满标触发首尾标附加返利
            $coupon_log_service = new CouponLogService();
            $r = $coupon_log_service->handleCouponExtraForDeal($dealId);
            if ($r === false) {
                throw new \Exception('邀请码附加返利消费记录添加失败');
            }

            DealModel::instance()->put_p2p_data($dealId);

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $log_info = array(__CLASS__, __FUNCTION__, $dealId, $e->getMessage());
            Logger::error(implode(" | ", $log_info));
            return $returnInfo ? array('suc' => 0, 'msg' => $e->getMessage()) : false;
        }

        $log_info = array(__CLASS__, __FUNCTION__, $dealId, $before_amount, $dealInfo['load_money']);
        Logger::info(implode(" | ", $log_info));
        return $returnInfo ? array('suc' => 1, 'data' => $dealInfo) : true;
    }

    /*
     * 取得用户投资公益标金额
     * @param $uid
     * @return mixed
     */
    public function getUserCrowdfundingMoney($uid) {
        $sql_gy = "SELECT SUM(d_l.money) AS principal FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status = 4 AND d.is_delete =0 AND d.parent_id!=0 AND d.is_has_loans = 1 AND d.loantype = 7 AND d_l.user_id = {$uid}";
        $principal_gy = DealModel::instance()->floorfix($GLOBALS['db']->get_slave()->getOne($sql_gy));
        return $principal_gy;
    }

    /**
     * 取得某时间段内的用户公益标金额
     * @param $uid
     * @param $startTime
     * @param $endTime
     * @return mixed
     */
    public function getUserCrowdfundingMoneyByTime($uid,$startTime,$endTime) {
        $sql_gy = "SELECT SUM(d_l.money) AS principal FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status = 4 AND d.is_delete =0 AND d.parent_id!=0 AND d.is_has_loans = 1 AND d.loantype = 7 AND d_l.user_id = {$uid}";
        $sql_gy.=" AND  d_l.create_time BETWEEN $startTime AND $endTime";
        $principal_gy = DealModel::instance()->floorfix($GLOBALS['db']->get_slave()->getOne($sql_gy));
        return $principal_gy;
    }

    /**
     * 生成旧版的借款标题，带有前缀 [前缀+项目名称+‘A’+9位编号]
     * @param int $deal_id
     * @param int $project_id
     * @return string $old_deal_name
     */
    public function getOldDealNameWithPrefix($deal_id, $project_id)
    {
        // 获取前缀名
        $deal_ext_obj = DealExtModel::instance()->getDealExtByDealId($deal_id);

        // 获取项目名
        $project_obj = DealProjectModel::instance()->find($project_id);

        // 拼接成旧版借款标题
        $id_str = str_pad(strval($deal_id), 9, strval(0), STR_PAD_LEFT);
        $deal_name = $deal_ext_obj->deal_name_prefix . $project_obj->name . 'A' . $id_str;

        return $deal_name;
    }

    public function saveServiceFeeExt($dealInfo) {
        $deal = $this->getDeal($dealInfo['id']);
        $deal_ext_data = DealExtModel::instance()->getInfoByDeal($deal['id'], false);
        $repay_times = $deal->getRepayTimes();

        //手续费
        if($deal_ext_data['loan_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['loan_fee_rate_type'] = 1;
            $deal_ext_data['loan_fee_ext'] = "";
        } else if ($deal_ext_data['loan_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['loan_fee_rate_type'] = 2;
            for($i = 0; $i < $repay_times; $i++) {
                $loan_fee_arr[] = 0.00;
            }

            $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            $loan_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
            $deal_ext_data['loan_fee_ext'] = json_encode($loan_fee_arr);
        }

        //咨询费
        if($deal_ext_data['consult_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['consult_fee_rate_type'] = 1;
            $deal_ext_data['consult_fee_ext'] = "";
        } else if ($deal_ext_data['consult_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['consult_fee_rate_type'] = 2;
            for($i = 0; $i < $repay_times; $i++) {
                $consult_fee_arr[] = 0.00;
            }

            $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $consult_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);
            $deal_ext_data['consult_fee_ext'] = json_encode($consult_fee_arr);
        }

        //担保费
        if($deal_ext_data['guarantee_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['guarantee_fee_rate_type'] = 1;
            $deal_ext_data['guarantee_fee_ext'] = "";
        } else if ($deal_ext_data['guarantee_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['guarantee_fee_rate_type'] = 2;
            for($i = 0; $i < $repay_times; $i++) {
                $guarantee_fee_arr[] = 0.00;
            }

            $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $guarantee_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);
            $deal_ext_data['guarantee_fee_ext'] = json_encode($guarantee_fee_arr);
        }

        //支付费
        if($deal_ext_data['pay_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['pay_fee_rate_type'] = 1;
            $deal_ext_data['pay_fee_ext'] = "";
        } else if ($deal_ext_data['pay_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['pay_fee_rate_type'] = 2;
            for($i = 0; $i < $repay_times; $i++) {
                $pay_fee_arr[] = 0.00;
            }

            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);
            $deal_ext_data['pay_fee_ext'] = json_encode($pay_fee_arr);
        }

        //渠道费
        if($deal_ext_data['canal_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['canal_fee_rate_type'] = 1;
            $deal_ext_data['canal_fee_ext'] = "";
        } else if ($deal_ext_data['canal_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['canal_fee_rate_type'] = 2;
            for($i = 0; $i < $repay_times; $i++) {
                $canal_fee_arr[] = 0.00;
            }

            $canal_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $canal_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $canal_fee_rate / 100.0);
            $deal_ext_data['canal_fee_ext'] = json_encode($canal_fee_arr);
        }

        if($deal['isDtb'] == 1) {//多投宝收取管理服务费
            //管理服务费
            if($deal_ext_data['management_fee_rate_type'] == 1) {//前期收
                $deal_ext_data['management_fee_rate_type'] = 1;
                $deal_ext_data['management_fee_ext'] = "";
            } else if ($deal_ext_data['management_fee_rate_type'] == 2) {//后期收
                $deal_ext_data['management_fee_rate_type'] = 2;
                for($i = 0; $i < $repay_times; $i++) {
                    $management_fee_arr[] = 0.00;
                }

                $management_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['management_fee_rate'], $deal['repay_time'], false);
                $management_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $management_fee_rate / 100.0);
                $deal_ext_data['management_fee_ext'] = json_encode($management_fee_arr);
            }
        }

        return $deal_ext_data->saveDealExtServicefee($deal['id'], $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr,$pay_fee_arr,$canal_fee_arr, $management_fee_arr);
    }

    /**
     * 根据ids获取标的信息
     * @param int $deal_ids
     * @param mixed $columns
     */
    public function getDealInfoByIds($deal_ids, $columns = '*') {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }

        if(empty($deal_ids) || !is_array($deal_ids)){
            return false;
        }
        $deal_ids = array_map('intval', $deal_ids);

        return DealModel::instance()->getDealInfoByIds($deal_ids, $columns);
    }

    /**
     * 获取deal_type的类别分组-[p2p系统逻辑拆分]
     * @return string 可查询的deal_type
     */
     public function getDealTypeGroup() {
        return is_wxlc() ? '0, 1, 3' : '0, 1';
     }
    /**
     *  根据deal_type判断标的是否为专享
     */
    public function isDealEx($deal_type)
    {
        return (DealModel::DEAL_TYPE_EXCLUSIVE == $deal_type) ? true : false;
    }

    /**
     *  根据deal_type判断标的是否为交易所
     */
    public function isDealExchange($deal_type)
    {
        return (DealModel::DEAL_TYPE_EXCHANGE == $deal_type) ? true : false;
    }

    /**
     *  根据deal_type判断标的是否为通知贷
     */
    public function isDealComp($deal_type)
    {
        return (DealModel::DEAL_TYPE_COMPOUND == $deal_type) ? true : false;
    }

    /**
     * 是否掌众、信石、功夫贷标的
     * @param intval $deal_id
     * @return boolean
     */
    public function isZhangzhongDeal($deal_id){
        $deal_id = intval($deal_id);
        $zzjrTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        // 这里不应该这么写，但是要的太急，我觉得这么写也没什么不妥，如果标的属于信石，则在催收短信、优惠码规则、放款短信等逻辑上保持一致。于是，这个方法虽然判断是否为章众标的，但其实是代表标的是否等同于章众的各逻辑
        $alTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XSJK);

        // 功夫贷
        $kfdTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDGFD);

        $dealTypeId = DealModel::instance()->find($deal_id,'type_id',true);
        return in_array($dealTypeId['type_id'], array($zzjrTypeId, $alTypeId, $kfdTypeId));
    }

    /**
     * 是否掌众标的
     * @param int $dealId
     */
    public function isDealOfZhangzhong($dealId, $dealInfo = []) {
        if (empty($dealInfo)) {
            $dealInfo = DealModel::instance()->find($dealId, 'type_id', true);
        }
        $zzjrTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        return $zzjrTypeId == $dealInfo['type_id'];
    }

    /**
     * 是否放心花标的(现金贷优易借)
     * @param int $dealId
     */
    public function isDealOfYouyijie($dealId, $dealInfo = []) {
        if (empty($dealInfo)) {
            $dealInfo = DealModel::instance()->find($dealId, 'type_id', true);
        }
        $yyjTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XJDYYJ);
        return $yyjTypeId == $dealInfo['type_id'];
    }

    /**
     * 判断标的是否在指定的资产类型里面
     * @param int $dealId 标的ID
     * @param array $dealLoanTypeList tagType数组
     */
    public function isDealOfDealTypeList($dealId, $dealLoanTypeList, $dealInfo = []) {
        if (empty($dealInfo)) {
            $dealInfo = DealModel::instance()->find($dealId, 'type_id', true);
        }
        if (empty($dealInfo) || (int)$dealInfo['type_id'] <= 0 || empty($dealLoanTypeList)) {
            return false;
        }
        // 通过tag数组，批量获取typeId
        $dealTypeIds = DealLoanTypeModel::instance()->getIdListByTag($dealLoanTypeList);
        return !empty($dealTypeIds) ? in_array((int)$dealInfo['type_id'], $dealTypeIds) : false;
    }

    /**
     * 是否云图生活-享花的标的
     * @param int $dealId
     * @return boolean
     */
    public function isDealYtsh($dealId) {
        $tag_service = new DealTagService();
        $tags = $tag_service->getTagListByDealId($dealId);
        return in_array(XHService::TAG_XH, $tags);
    }

    /**
     * 专享按照项目放款
     * @param $projectId 项目id
     * @return boolean 是否成功
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function makeProjectLoansJob($projectId,$admin = array(),$submitUid=0)
    {
        $deals = $this->getDealByProId($projectId,array(2));

        try {
            $dealModel = new DealModel();
            $function = '\core\service\DealService::makeProjectDealLoansJob';
            $param = array('project_id' => $projectId,'admin'=>$admin,'submit_uid'=>$submitUid);
            $GLOBALS['db']->startTrans();
            $jobModel = new \core\dao\JobsModel();

            $dealProService = new DealProjectService();

            //更新各标的状态及还款时间
            foreach ($deals as $deal) {

                $dealRecord = array();
                $dealRecord['deal_status'] = 4;
                $dealRecord['repay_start_time'] = to_timespan(date("Y-m-d"));
                $deltaMonthTime = $dealModel->get_delta_month_time($deal['loantype'], $deal['repay_time']);

                if ($deal['loantype'] == 5) {
                    $dealRecord['next_repay_time'] = $dealModel->next_replay_day_with_delta($dealRecord['repay_start_time'], $deltaMonthTime);
                } else {
                    $dealRecord['next_repay_time'] = $dealModel->next_replay_month_with_delta($dealRecord['repay_start_time'], $deltaMonthTime);
                }

                $isSaved = $dealModel->updateBy($dealRecord,"id = ".$deal['id']);
                if (!$isSaved) {
                    throw new \Exception("修改标的状态或者放款时间错误");
                }

                syn_deal_status($deal['id']);
                syn_deal_match($deal['id']);

                $dealProService->updateProBorrowed($deal['project_id']);
                $dealProService->updateProLoaned($deal['project_id']);

                //更新标放款状态
                $saveStatus = $dealModel->changeLoansStatus($deal['id'], 2);
                if (!$saveStatus) {
                    throw new \Exception("更新标放款状态 is_has_loans 失败");
                }
            }

            $jobModel->priority = 119;
            $addJob = $jobModel->addJob($function, $param, get_gmtime() + 180);

            if (!$addJob) {
                throw new \Exception("项目放款任务添加失败");
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 专享项目放款jobs
     * @param $projectId 项目id
     * @return boolean 是否成功
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     */
    public function makeProjectDealLoansJob($projectId, $admin = array(), $submit_uid = 0){

        $project = DealProjectModel::instance()->find($projectId);

        $deals = $this->getDealByProId($projectId,array(4));
        if(count($deals) == 0){
            return false;
        }

        foreach($deals as $deal){
            $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
            $zhangzhongTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
            $deal['isDtb'] = 0;
            if($deal['type_id'] == $dtbTypeId){
                $deal['isDtb'] = 1;
            }

            $deal_ext = DealExtModel::instance()->getInfoByDeal($deal['id']);
            $agency_model = new DealAgencyModel();
            $agency_fee_user = $agency_model->find($deal['agency_id']);
            $advisory_fee_user = $agency_model->find($deal['advisory_id']);
            $loan_fee_user_id = $agency_model->getLoanAgencyUserId($deal['id']);
            $entrust_user = $agency_model->find($deal['entrust_agency_id']);
            $pay_agency_user = $agency_model->find($deal['pay_agency_id']);
            $canal_user = $agency_model->find($deal['canal_agency_id']);

            if (!$deal['pay_agency_id']) {
                $deal['pay_agency_id'] = $agency_model->getUcfPayAgencyId();
            }

            $management_user_id = 0;
            if($deal['isDtb'] == 1) {
                $management_agency_user = $agency_model->find($deal['management_agency_id']);
                $management_user_id = $management_agency_user['user_id'];
            }

            $type_id = $deal['type_id'];
            $is_auto_withdrawal = $deal_ext['is_auto_withdrawal'];
            $site_title = get_deal_domain_title($deal['id']);
        }



        $result = $this->makeProjectDealLoans($project, $advisory_fee_user['user_id'], $agency_fee_user['user_id'], $loan_fee_user_id, $pay_agency_user['user_id'], $management_user_id,$entrust_user['user_id'], $canal_user['user_id'], $admin, $is_auto_withdrawal,$submit_uid);

        if ($is_auto_withdrawal == 1 && $type_id != $zhangzhongTypeId) {
            $content = sprintf("您好，您在%s的借款 “%s”已招标成功。借款金额:%s元，扣除服务费%s元，实得%s元。",
                $site_title, $project['name'], format_price($project['borrow_amount'], 0),
                format_price($result['services_fee'], 0), format_price($result['money'],0)
            );

            $content .= "系统已进行提现处理，如您填写的账户信息正确无误，您的资金将会于3个工作日内到达您的银行账户。";
            send_user_msg("招标成功自动提现", $content, 0, $deal['user_id'], get_gmtime(), 0, true, 5);
        }

        return ($result === false) ? false : true;
    }

    public function makeProjectDealLoans($project, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id,$entrust_user_id, $canal_user_id, $admin = array(), $is_auto_withdrawal = 0,$submit_uid)
    {
        // 悲观锁，以group_id为锁的键名，防止重复生成
        $lockKey = "DealService-makeProjectDealLoansJob".$project['id'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 900)) {
            return false;
        }
        try {
            $GLOBALS['db']->startTrans();
            $deal_dao = new DealModel();
            $deals = $this->getDealByProId($project['id'],array(4));

            $deal_loan_type_dao = new DealLoanTypeModel();
            $coupon_deal_service = new CouponDealService();
            $loan_oplog_model = new LoanOplogModel();

            //记录每个标的的放款金额及手续费金额
            $deals_result = array();

            foreach($deals as $deal) {
                $borrow_user_id = $deal['user_id'];
                $result_make = $deal_dao->makeEntrustDealLoans($deal, $consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id,$entrust_user_id, $canal_user_id, $admin);
                if ($result_make === false) {
                    throw new \Exception("放款逻辑处理失败,返回对象：" . json_encode($result_make));
                }
                $deals_result['services_fee'] += $result_make['data']['services_fee'];
                $deals_result['borrow_amount'] += $result_make['data']['borrow_amount'];
                $deals_result['loan_fee'] += $result_make['data']['loan_fee'];
                $deals_result['consult_fee'] += $result_make['data']['consult_fee'];
                $deals_result['guarantee_fee'] += $result_make['data']['guarantee_fee'];
                $deals_result['pay_fee'] += $result_make['data']['pay_fee'];
                $deals_result['management_fee'] += $result_make['data']['management_fee'];
                $deals_result['canal_fee'] += $result_make['data']['canal_fee'];
                //自动提现费用获取
                if($is_auto_withdrawal == 1){
                    //平台费+咨询费+担保费+支付服务费
                    $services_fee[$deal['id']] = round($result_make['data']['services_fee'], 2);
                } else {
                    $result = true;
                }

            }

            $result_make_project = $deal_dao->makeEntrustProjectLoans($project,$deals_result,$borrow_user_id,$consult_user_id, $guarantee_user_id, $loan_user_id, $pay_user_id, $management_user_id ,$entrust_user_id, $canal_user_id, $admin);

            if ($result_make_project === false) {
                throw new \Exception("项目放款逻辑处理失败,返回对象：" . json_encode($result_make_project));
            }

            //放款后续处理
            foreach($deals as $deal) {
                $event = new DealLoansEvent($deal['id']);
                $event->execute();
                $function = '\core\service\DealLoanRepayService::finishDealLoans';
                $param = array($deal['id']);
                $job_model = new JobsModel();
                $job_model->priority = 50;
                if (!$job_model->addJob($function, $param, false, 99)) {
                    throw new \Exception('回款计划收尾任务添加失败');
                }


                //自动提现
                if($is_auto_withdrawal == 1){
                    $result = $this->withdrawal($deal,$services_fee[$deal['id']], $admin);
                }

                //更新消费分期返利天数
                $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal['type_id']);
                if (($type_tag == DealLoanTypeModel::TYPE_XFFQ) && ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'])) {
                    $repay_times = intval($deal['repay_time']) - 1;
                    $rebate_days = floor($repay_times * 30 + ($deal['first_repay_interest_day'] - $deal['repay_start_time']) / 86400); //  返利天数=第一期还款日期-起息日期+（期数-1）*30
                    if ($rebate_days < 0) {
                        throw new \Exception("优惠码返利天数不能为负值:rebate_days:" . $rebate_days);
                    }
                    // 更新优惠码返利天数
                    $coupon_res = $coupon_deal_service->updateRebateDaysByDealId(intval($deal['id']), $rebate_days);;
                    if (!$coupon_res) {
                        throw new \Exception("更新标优惠码返利天数失败");
                    }
                }
                $repay_time = $deal['repay_time'];
                $loan_type = $deal['loantype'];
                if (0 == $admin['adm_id']) {
                    throw new \Exception('管理员未通过!');
                } else {
                    $loan_oplog_model->op_type = LoanOplogModel::OP_TYPE_MAKE_LOAN;
                }

                $loan_oplog_model->loan_batch_no = '';
                $loan_oplog_model->deal_id = $deal['id'];
                $loan_oplog_model->deal_name = $deal['name'];
                $loan_oplog_model->borrow_amount = $deal['borrow_amount'];
                $loan_oplog_model->repay_time = $repay_time;
                $loan_oplog_model->loan_type = $loan_type;
                $loan_oplog_model->borrow_user_id = $deal['user_id'];
                $loan_oplog_model->op_user_id = $admin['adm_id'];
                $loan_oplog_model->loan_money_type = $project['loan_money_type'];
                $loan_oplog_model->op_time = get_gmtime();
                $loan_oplog_model->submit_uid = intval($submit_uid);
                $loan_oplog_model->loan_money = $deal['borrow_amount'] - $services_fee[$deal['id']];
                if(!$loan_oplog_model->insert()){
                    throw new \Exception("保存项目放款操作记录失败");
                };

            }

            //更新项目还款中状态

            $deal_project_model = new DealProjectModel();

            $deal_project_model->find($project["id"]);
            $deal_project_model->business_status = DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'];
            if(!$deal_project_model->changeProjectStatus($project["id"],DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'])){
                throw new \Exception("保存项目还款中状态失败");
            };


            $GLOBALS['db']->commit();

            $lock->releaseLock($lockKey);//解锁
        } catch (\Exception $e) {
            echo $e->getMessage();
            $log['desc'] = '提现申请失败，项目编号：'.$project['id'].' 错误消息：'.$e->getMessage();
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey);//解锁
            return false;
        }

        return $result;
    }

    /**
     * 生成用户投资资金扣除变动日志,并发送投资成交站内信
     * @param $dealId 标的id
     * @return array 资金变动记录
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/

    public function createUserSyncRemoteData($dealId){
        //将出借人冻结资金扣除
        $loanList = DealLoadModel::instance()->findAll("`deal_id`='".intval($dealId)."' ORDER BY `id` ASC");
        $deal = DealModel::instance()->find($dealId);
        $pro_service = new DealProjectService();
        if($pro_service->isProjectEntrustZX($deal['project_id'])){
            $agencyModel = new DealAgencyModel();
            $entrustUser = $agencyModel->find($deal['entrust_agency_id']);
            $receiverId = $entrustUser['user_id'];
        }else{
            $receiverId = $deal['user_id'];
        }
        foreach ($loanList as $k => $v) {
            if (bccomp($v['money'], '0.00', 2) > 0) {
                $result[] = array(
                    'outOrderId' => $dealId,
                    'payerId' => $v['user_id'],
                    'receiverId' => $receiverId,
                    'repaymentAmount' => bcmul($v['money'], 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $dealId,
                );
            }

            //不是公益标，发送站内信  非预约投资
            if ($deal['loantype'] != 7 && $v['source_type'] != DealLoadModel::$SOURCE_TYPE['reservation']) {
                $content = "您投资的“{$deal['name']}”已成交，投资款{$v['money']}元已放款，开始计息。";
                $msgbox = new MsgBoxService();
                $msgbox->create($v['user_id'], 19, '放款计息', $content);
            }
        }

        return $result;
    }

    /**
     * 生成标的各机构服务费信息
     * @param $dealId 标的id
     * @return array 服务费信息
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function makeServiceFee($dealId){

        $deal = $this->getDeal($dealId);
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId, false);

        $result = array();

        // 手续费
        if (!$dealExt['loan_fee_ext']) {
            if (DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE == $dealExt['loan_fee_rate_type']) { // 固定比例前收
                $result['loan_fee_rate'] = $deal['loan_fee_rate'];
            } else {
                $result['loan_fee_rate'] = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            }
            $result['loan_fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $result['loan_fee_rate'] / 100.0);
        } else {
            $loan_fee_arr = json_decode($dealExt['loan_fee_ext'], true);
            $result['loan_fee'] = $loan_fee_arr[0];
        }
        // 咨询费
        if (!$dealExt['consult_fee_ext']) {
            $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $result['consult_fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);
        } else {
            $consult_fee_arr = json_decode($dealExt['consult_fee_ext'], true);
            $result['consult_fee'] = $consult_fee_arr[0];
        }
        // 担保费
        if (!$dealExt['guarantee_fee_ext']) {
            $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $result['guarantee_fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);
        } else {
            $guarantee_fee_arr = json_decode($dealExt['guarantee_fee_ext'], true);
            $result['guarantee_fee'] = $guarantee_fee_arr[0];
        }

        // 支付服务费
        if (!$dealExt['pay_fee_ext']) {
            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $result['pay_fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);
        } else {
            $pay_fee_arr = json_decode($dealExt['pay_fee_ext'], true);
            $result['pay_fee'] = $pay_fee_arr[0];
        }

        // 管理服务费
        $result['management_fee'] = 0;//管理服务费
        if( $deal['isDtb'] == 1) {//多投宝收取管理服务费
            if (!$dealExt['management_fee_ext']) {
                $management_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['management_fee_rate'], $deal['repay_time'], false);
                $result['management_fee'] = $this->floorfix($deal['borrow_amount'] * $management_fee_rate / 100.0);
            } else {
                $management_fee_arr = json_decode($dealExt['management_fee_ext'], true);
                $result['management_fee'] = $management_fee_arr[0];
            }
        }

        // 渠道服务费
        if (!$dealExt['canal_fee_ext']) {
            $canal_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $result['canal_fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $canal_fee_rate / 100.0);
        } else {
            $canal_fee_arr = json_decode($dealExt['canal_fee_ext'], true);
            $result['canal_fee'] = $canal_fee_arr[0];
        }

        return $result;
    }

    /**
     * 验证专享标按照项目放款条件是否满足
     * @param $project 项目
     * @return boolean 验证是否成功
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function isOKForZxMakingLoans($project)
    {

        //检查项目上标金额和项目总金额差异
        if ($project['borrow_amount'] !== $project['money_borrowed']) {
            throw new \Exception("项目上标金额跟项目总金额不匹配!");
        }

        //检查项目投资金额与项目总金额差异
        if ($project['borrow_amount'] !== $project['money_loaned']) {
            throw new \Exception("项目投资额跟项目总金额不匹配!");
        }

        //检查项目下所有标的是否满足放款条件及标的的总金额是否跟项目总金额一致
        $deals = DealModel::instance()->getDealByProId($project['id']);
        $totalMoney = 0;
        $contractNewService = new ContractNewService();
        $dealContractModel = new DealContractModel();

        foreach ($deals as $deal) {
            // 标没有满标
            if (in_array($deal['deal_status'], array(2, 4))) {
                $totalMoney += $deal['borrow_amount'];

                //除公益标，附件合同除外，验证合同状态
                $contractCategoryInfo = $contractNewService->getCategoryByCid($deal['contract_tpl_type']);
                if (false === $contractCategoryInfo) {
                    throw new \Exception("合同服务异常");
                }
                if (!empty($contractCategoryInfo) && !in_array($contractCategoryInfo['typeTag'], ContractModel::$tpl_type_tag_attachment)) {
                    if (!$dealContractModel->getDealContractUnSignInfo($deal['id'])) {
                        //如果不是公益标,则合同不通过
                        if (!$this->isDealCrowdfunding($deal['id'])) {
                            throw new \Exception($deal['id'] . "借款人或担保,咨询公司,委托人的合同未通过");
                        }
                    }
                }
            }

            //已经放过款
            if ($deal['is_has_loans'] != 0) {
                throw new \Exception($deal['id'] . "已经放过款");
            }
        }
        //验证项目标的投资总额跟项目总金额是否匹配
        if (round($totalMoney, 2) === $project['borrow_amount']) {
            throw new \Exception("项目标的投资总额跟项目总金额不匹配!");
        }

        return true;
    }

    /**
     * @author : yanjun5@ucfgroup.com
     * @function : 根据期限和剩余可投金额筛选标的列表
     * @param : $minInvest
     * @param : $minRepayTime
     * @return : array()
     */
    public function getDealListByDiscount($minInvest, $minRepayTime, $dealType = '', $pageNum = 1, $pageSize = 20)
    {
        $offset = ($pageNum - 1) * $pageSize;
        $deals = DealModel::instance()->getDealListByDiscount($minInvest, $minRepayTime, $dealType, $offset, $pageSize);
        foreach ($deals as $k => $v){
            $deals[$k] = DealModel::instance()->handleDealNew($v);
        }
        return $deals;
    }

    /**
     * 保存标的各项费率
     * @param int $deal_id
     * @param array $period_fee_arr 分期手续费 二维数组 各项手续费默认为 0 ('loan_fee_arr' => 0, 'consult_fee_arr' => 0, 'guarantee_fee_arr' => 0, 'pay_fee_arr' => 0, 'management_fee_arr' => 0)
     * @param string-json $old_period_fee_json 原有的 fee_ext 信息，格式为 json_encode 结果
     * @param array $is_keep_old_fee_ext 如果是分期类的收费方式时，是否保留原有的 fee_ext 信息
     * @return boolean
     */
    static public function updateHandlingCharge($deal_id, $period_fee_arr = array(), $is_keep_old_fee_ext = false)
    {
        if (empty($period_fee_arr)) {
            $period_fee_arr = array('loan_fee_arr' => 0, 'consult_fee_arr' => 0, 'guarantee_fee_arr' => 0, 'pay_fee_arr' => 0, 'management_fee_arr' => 0);
        }

        $deal_obj = DealModel::instance()->find($deal_id);
        $deal_ext_obj = DealExtModel::instance()->findBy(sprintf('`deal_id` = %d', $deal_id));
        $loan_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->loan_fee_rate, $deal_ext_obj->loan_fee_rate_type, $period_fee_arr['loan_fee_arr'], $deal_ext_obj->loan_fee_ext, $is_keep_old_fee_ext); // 平台费
        $consult_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->consult_fee_rate, $deal_ext_obj->consult_fee_rate_type, $period_fee_arr['consult_fee_arr'], $deal_ext_obj->consult_fee_ext, $is_keep_old_fee_ext); // 咨询费
        $guarantee_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->guarantee_fee_rate, $deal_ext_obj->guarantee_fee_rate_type, $period_fee_arr['guarantee_fee_arr'], $deal_ext_obj->guarantee_fee_ext, $is_keep_old_fee_ext); // 担保费
        $pay_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->pay_fee_rate, $deal_ext_obj->pay_fee_rate_type, $period_fee_arr['pay_fee_arr'], $deal_ext_obj->pay_fee_ext, $is_keep_old_fee_ext); // 支付服务费
        $management_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->management_fee_rate, $deal_ext_obj->management_fee_rate_type, $period_fee_arr['management_fee_arr'], $deal_ext_obj->management_fee_ext, $is_keep_old_fee_ext); // 管理费
        $canal_fee_arr = self::calcHandlingCharge($deal_obj, $deal_obj->canal_fee_rate, $deal_ext_obj->canal_fee_rate_type, $period_fee_arr['canal_fee_arr'], $deal_ext_obj->canal_fee_ext, $is_keep_old_fee_ext); // 渠道费


        return DealExtModel::instance()->saveDealExtServicefee($deal_obj->id, $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr, $pay_fee_arr, $canal_fee_arr, $management_fee_arr);
    }

    /**
     * 计算标的各项手续费
     * @param object $deal_obj 标的数据对象
     * @param int $fee_rate 收费利率
     * @param int $fee_rate_type 收费方式
     * @param array $period_fee_arr 分期手续费 二维数组 ['loan_fee_arr' => [], 'consult_fee_arr' => [], 'guarantee_fee_arr' => [], 'pay_fee_arr' => [], 'management_fee_arr' => []]
     * @param string-json $old_period_fee_json 原有的 fee_ext 信息，格式为 json_encode 结果
     * @param boolean $is_keep_old_fee_ext 标识此项利率是否保持原有的 fee_ext 信息
     * @return array 各期应收费用 (元)
     */
    static private function calcHandlingCharge($deal_obj, $fee_rate, $fee_rate_type, $period_fee_arr, $old_period_fee_json, $is_keep_old_fee_ext)
    {
        if (!is_object($deal_obj)) {
            return array();
        }

        // 不同的收费方式
        $ext_fee_arr = array();
        if ($is_keep_old_fee_ext && in_array($fee_rate_type, array(DealExtModel::FEE_RATE_TYPE_PERIOD, DealExtModel::FEE_RATE_TYPE_PROXY))) { // 分期一类的收费方式，可以选择保留原值
            $ext_fee_arr = json_decode($old_period_fee_json, true);
        } else if (in_array($fee_rate_type, array(DealExtModel::FEE_RATE_TYPE_PERIOD, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD)) && is_array($period_fee_arr)) { // 分期收、固定比例分期收
            $ext_fee_arr = array_map('addslashes', $period_fee_arr);
        } else if($fee_rate_type == DealExtModel::FEE_RATE_TYPE_BEHIND) { // 后收
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00); // 尾期之前都为0
            $period_fee_rate = Finance::convertToPeriodRate($deal_obj->loantype, $fee_rate, $deal_obj->repay_time, false); // 期间利率
            array_push($ext_fee_arr, $deal_obj->floorfix($deal_obj->borrow_amount * $period_fee_rate / 100.0)); // 尾期
        } else if($fee_rate_type == DealExtModel::FEE_RATE_TYPE_PROXY && is_array($period_fee_arr)) { // 代销分期
            $period_fee_arr = array_map('addslashes', $period_fee_arr);
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00);
            $ext_fee_arr[0] = $period_fee_arr[0]; // 0 期
            $ext_fee_arr[] = $period_fee_arr[1]; // 尾期
        } else if (DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND == $fee_rate_type) { // 固定比例后收
            $repay_times = $deal_obj->getRepayTimes();
            $ext_fee_arr = array_fill(0, $repay_times, 0.00); // 尾期之前都为0
            array_push($ext_fee_arr, $deal_obj->floorfix($deal_obj->borrow_amount * $fee_rate / 100.0)); // 尾期
        }

        return $ext_fee_arr;
    }

    /**
     * 根据标的id 获取项目信息
     * @param int $deal_id
     * @return array
     */
    static public function getProjectInfoByDealId($deal_id)
    {
        $deal_obj = DealModel::instance()->findViaSlave($deal_id);
        if (empty($deal_obj)) {
            return array();
        } else {
            $project_obj = DealProjectModel::instance()->findViaSlave($deal_obj->project_id);
            return empty($project_obj) ? array() : $project_obj->getRow();
        }
    }

    /**
     * 判断手续费收费为固定收取类型
     * @param int $loan_fee_rate_type
     */
    static public function isDealFeeRateTypeFixed($loan_fee_rate_type)
    {
        return in_array($loan_fee_rate_type, array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD));
    }

    /**
     * 对指定标的进行自动放款操作
     * @param array $dealInfo 对应 deal 表字段
     * @return boolean
     */
    public function autoMakeLoans($dealInfo)
    {
        try {
            $GLOBALS['db']->startTrans();
            $this->isOKForMakingLoans($dealInfo); // 不符合条件抛出异常

            if ($this->saveServiceFeeExt($dealInfo) === false) {
                throw new \Exception("Save deal ext fail. Error:deal id:" . $dealInfo['id']);
            }


            //放款添加到jobs
            if(!$this->isP2pPath(intval($dealInfo['id']))) {
                // 添加jobs
                $function = '\core\service\DealService::makeDealLoansJob';
                $param = array('deal_id' => intval($dealInfo['id']), 'admin' => '', 'submit_uid' => 0);
            }else{
                $grantOrderId = Idworker::instance()->getId();
                $function = '\core\service\P2pDealGrantService::dealGrantRequest';
                $param = array(
                    'orderId'=>$grantOrderId,
                    'dealId'=>$dealInfo['id'],
                    'param'=>array('deal_id' => $dealInfo['id'], 'admin' => '', 'submit_uid' => 0),
                );
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知加入jobs orderId:".$grantOrderId." dealId:".$dealInfo['id']);
            }

            $job_model = new JobsModel();
            $job_model->priority = 99;
            //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
            if (false == $job_model->addJob($function, $param)) {
                throw new \Exception('addJob fail. Error:deal type:' . $dealType . ",deal id:" . $dealInfo['id']);
            }

            $deal_model = new DealModel();
            //更新标放款状态
            if (false == $deal_model->changeLoansStatus(intval($dealInfo['id']), 2)) {
                throw new \Exception('changeLoansStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
            }

            //更新标的还款中状态
            if (false == $deal_model->changeDealStatus(intval($dealInfo['id']))) {
                throw new \Exception('changeDealStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
            }

            // 更新标还款时间
            if (false == $deal_model->changeRepayStartTime(intval($dealInfo['id']), to_timespan(date('Y-m-d 00:00:00')))) {
                throw new \Exception('changeRepayStartTime fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
            }
            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:params:%s, func:%s [%s:%s]', json_encode($dealInfo), __FUNCTION__, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail:err-msg:%s, params:%s, func:%s [%s:%s]', $e->getMessage(), json_encode($dealInfo), __FUNCTION__, __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 根据审批单号获取标的状态
     * @param $approve_number
     * @return bool
     */
    public function getDealStatueByAppronum($approve_number) {
        if (empty($approve_number)) {
            return false;
        }
        return DealModel::instance() -> getDealStatueByAppronum($approve_number);
    }

    /**
     * 查询用户借款未还金额
     * @param $user_id
     * @return bool
     */
    public function getUnrepayMoneyByUid($user_id) {
        if (empty($user_id)) {
            return false;
        }
        return DealModel::instance() -> getUnrepayMoneyByUid(intval($user_id));
    }

    /**
     * 标的还款试算
     * @param $deal DealModel 对象
     * @param $dealRepayId dealRepay表对应的还款ID
     * @param $repayDay YYYY-mm-dd
     * @param $repayType 1--提前 2--正常
     * @return array
     * @throws \Exception
     */
    public function dealRepayTrial($deal,$dealRepayId,$repayDay,$repayType=false,$isForceTrial=false){
        if(!$deal instanceof DealModel){
            throw new \Exception("参数类型不正确 deal需要DealModel对象");
        }
        if($deal->deal_status != DealModel::$DEAL_STATUS['repaying']){
            throw new \Exception("标的状态需要还款中才能发起试算");
        }
        if($isForceTrial === false && $deal->is_during_repay == DealModel::DURING_REPAY){
            throw new \Exception("标的状正在还款不能发起试算");
        }

        $dealRepayModel = \core\dao\DealRepayModel::instance()->find($dealRepayId);
        if(!$dealRepayModel){
            throw new \Exception("还款信息不存在");
        }

        if($dealRepayModel->status != \core\dao\DealRepayModel::STATUS_WAITING){
            throw new \Exception("改期还款已完成不能进行试算");
        }

        $planRepayTime = strtotime(to_date($dealRepayModel->repay_time,'Y-m-d')); // 预计还款时间
        $repayTime = strtotime($repayDay); // 实际还款时间

        $imposeMoney = 0; // 预期罚息费用
        if($repayType == 1 || ($planRepayTime > $repayTime && $repayType == false)){ // 提前还款
            $dealExt = DealExtModel::instance()->findByViaSlave('deal_id='.$deal['id']);
            $type = 1;
            $ps = new \core\service\DealPrepayService();
            $psRes = $ps->prepayCalc($deal,$dealExt,$repayDay);

            $prepayCompensation = $psRes['prepay_compensation'];
            $repayPrincipal = $psRes['remain_principal'];
            $repayInterest = $psRes['prepay_interest'];
            $loanFee = $psRes['loan_fee'];
            $consultFee = $psRes['consult_fee'];
            $guaranteeFee = $psRes['guarantee_fee'];
            $payFee = $psRes['pay_fee'];
            $managentFee = $psRes['management_fee'];
            $interestDays = $psRes['remain_days'];
        }else{ // 正常还款
            $dps = new \core\service\DealRepayService();
            $lastInterestTime =  $dps->getMaxRepayTimeByDealId($deal);
            // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
            $lastInterestTime = to_timespan(to_date($lastInterestTime,'Y-m-d')); // 转换为零点开始
            $endInterestTime = to_timespan($repayDay); // 计息结束日期
            $interestDays = ceil(($endInterestTime - $lastInterestTime)/86400); // 利息天数

            $imposeMoney = ($planRepayTime < $repayTime) ? $dealRepayModel->feeOfOverdue() : 0;
            $type = 2;
            $prepayCompensation = 0;
            $repayPrincipal = $dealRepayModel->principal;
            $repayInterest = $dealRepayModel->interest;
            $loanFee = $dealRepayModel->loan_fee;
            $consultFee = $dealRepayModel->consult_fee;
            $guaranteeFee = $dealRepayModel->guarantee_fee;
            $payFee = $dealRepayModel->pay_fee;
            $managentFee = $dealRepayModel->management_fee;
        }

        $data = array(
            "deal_id" => $deal['id'],
            "type" => $type, // 1正常还款 2提前还款
            "repay_start_time" => to_date($deal->repay_start_time,'Y-m-d'), // 放款时间
            "interest_days" => $interestDays, // 利息天数
            "repay_principal" => $repayPrincipal,// 还款本金
            "repay_interest" => $repayInterest,// 还款利息
            "impose_money" => $imposeMoney, //预期罚息
            "prepay_compensation" => $prepayCompensation, // 提前还款违约金
            "loan_fee" => $loanFee,// 手续费
            "consult_fee" => $consultFee,//咨询费
            "guarantee_fee" => $guaranteeFee,//担保费,
            "pay_fee" => $payFee,// 支付服务费
            "management_fee" => $managentFee,
            "total_repay" => $repayPrincipal + $repayInterest + $loanFee + $consultFee + $guaranteeFee + $payFee + $managentFee + $prepayCompensation + $imposeMoney,
        );
        logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "标的还款试算" ,json_encode($data))));
        return $data;
    }

    public function getP2pSiteTags () {
        $return = array();
        $product_class_types = array();
        $loan_user_customer_types = array();

        $customerTypes = $GLOBALS['dict']['LOAN_USER_CUSTOMER_TYPE'];
        foreach ($customerTypes as $k=>$v) {
            $loan_user_customer_types[] = array('id'=>$k,'name'=>$v);
        }

        $dealTypeGradeService = new DealTypeGradeService();
        $sortCond= " ORDER BY FIELD(name,'车贷','供应链','个体经营贷','消费贷') DESC ";
        $secondLayers = $dealTypeGradeService->getAllSecondLayersByName('P2P',$sortCond);
        foreach ($secondLayers as $layer) {
            // 去掉p2p下的'国担支农系列产品'的产品大类
            if($layer['name'] === '国担支农贷'){
                continue;
            }
            $product_class_types[] = array('id'=>$layer['id'],'name'=>$layer['name']);
        }
        $return['product_class_types'] = $product_class_types;
        $return['loan_user_customer_types'] = $loan_user_customer_types;
        return $return;
    }

    /**
    *根据标id获取产品名称
    */
    public function getProductNameByDealId($dealId){
        $deal = DealModel::instance()->find(intval($dealId),"project_id", true);
        if(empty($deal)){
            throw new \Exception("标信息不存在");
        }
        $project = DealProjectModel::instance()->find(intval($deal['project_id']),"product_mix_3",true);
        return empty($project) ? '' : $project['product_mix_3'];
    }

    /**
     * [判断是否是部分用户还款标
     */
    public function isDealPartRepay($deal_id,$repay_id=0) {
        $m = new DealLoanPartRepayModel();
        return $m->isPartRepayDeal($deal_id,$repay_id) ? true :false;
    }

} // END class Deal
