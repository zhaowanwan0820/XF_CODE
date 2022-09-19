<?php
namespace openapi\conf;


class OauthConf {

    /**
     *返回数据的过滤
     */
    public static $dataFilter = array(
        //JFB
        '7377f017474ea416a2b57548' => array(

                'deals/Detail' => array(
                        'type' => 'include',
                        'field' => array(
                                array(
                                    'dealId',
                                    'dealInfo',
                                    'projectIntro',
                                ),
                                array(
                                    'id',
                                    'name',
                                    'sub_name',
                                    'description',
                                    'is_effect',
                                    'is_delete',
                                    'sort',
                                    'borrow_amount',
                                    'min_loan_money',
                                    'max_loan_money',
                                    'repay_time',
                                    'rate',
                                    'tag_match',
                                    'start_time',
                                    'success_time',
                                    'deal_status',
                                    'loantype',
                                    'point_percent',
                                    'deal_type',
                                    'need_money_detail',
                                    'need_money',
                                    'compound_from',
                                    'intro',
                                ),
                            )
                    ),
                'deal/BidConfirm' => array(
                        'type' => 'include',
                        'field' => array(
                                array('contract'),
                            ),
                    ),

            ),

        );


    /**
     *白名单
     */
    public static $actionWhiteList = array(
        'SetProjectInfo' => array('3e2e8aac63354efdf2007076'),
        'DealProjectBankcardUpdater' => array('3e2e8aac63354efdf2007076'), // 信贷接口 - 更新项目银行卡信息
        'DoCombineRegist' => array('7b9bd46617b3f47950687351', 'db6c30dddd42e4343c82713e'),
        'ThirdCombineRegist' => array('7377f017474ea416a2b57548'),
        'ThirdRegister' => array('7377f017474ea416a2b57548'),
        //修改密码和忘记密码白名单
        'ModifyPwd' => array('7b9bd46617b3f47950687351',//firstp2p m域
                             'db6c30dddd42e4343c82713e',//wangxinlicai m域
                             '8365f78859915a7db00e37c6',//荣信汇
                             'd3e9e24156be0f5b8e1100ac',//房贷
                             '6d03d1ab2ac33258fb1b5fcf',//哈哈农场
                             '5610e2cd133cd29ecf8e32ee',//艺金融wap站
                             'oapi',//oapi自用
                            ),
        'ForgetPwd' => array('7b9bd46617b3f47950687351',
                             'db6c30dddd42e4343c82713e',
                             '8365f78859915a7db00e37c6',
                             'd3e9e24156be0f5b8e1100ac',
                             '6d03d1ab2ac33258fb1b5fcf',
                             '5610e2cd133cd29ecf8e32ee'
                            ),
        //享花项目接口
        'GetFourElement' => array('85feb125dfc0a0db06f22cb4', 'yuntutest'),
        'DealLoanInfo' => array('85feb125dfc0a0db06f22cb4', 'yuntutest'),
        'ThirdRepayApply' => array('85feb125dfc0a0db06f22cb4', 'yuntutest'),
        //网信房贷接口
        'StatusNotify' => array('99e4f8d09b6411cd68161dad5b0f98f6', 'wxfdtest'),
        //人脸识别
        'Recognize' => array('a0b778af1632d2719d51ea3ee3d7d05d'),
    );

    /**
     * 黑名单 client_id => 接口
     */
    public static $actionBlackList = array(
        '6d03d1ab2ac33258fb1b5fcf' => array('deals/Index', 'deals/Detail', 'deal/Bid', 'deal/ReserveList', 'deal/ReserveCardList', 'deal/Reserve', 'deal/ReserveIndex'),
    );


    /**
     *默认优惠码
     */
    public static $defaultCoupon = array(
        '7377f017474ea416a2b57548' => 'AGENCY_COUPON_JF',
    );



}


