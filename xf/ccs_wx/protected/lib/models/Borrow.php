<?php

/**
 * This is the model class for table "dw_borrow".
 *
 * The followings are the available columns in table 'dw_borrow':
 * @property string $id
 * @property integer $site_id
 * @property integer $user_id
 * @property string $name
 * @property integer $status
 * @property integer $guarantor_status
 * @property integer $order
 * @property integer $hits
 * @property string $litpic
 * @property string $flag
 * @property integer $is_vouch
 * @property string $type
 * @property string $guarantors
 * @property string $original_creditor
 * @property string $original_creditor_ID
 * @property integer $view_type
 * @property string $vouch_award
 * @property string $vouch_user
 * @property string $vouch_account
 * @property integer $vouch_times
 * @property string $source
 * @property string $publish
 * @property string $customer
 * @property string $number_id
 * @property string $verify_user
 * @property string $verify_time
 * @property string $verify_remark
 * @property integer $repayment_user
 * @property string $forst_account
 * @property string $repayment_account
 * @property string $monthly_repayment
 * @property string $repayment_yesaccount
 * @property string $repayment_yesinterest
 * @property string $repayment_time
 * @property string $repayment_remark
 * @property string $success_time
 * @property string $end_time
 * @property string $payment_account
 * @property string $each_time
 * @property string $use
 * @property string $use_detail
 * @property string $repayment_source
 * @property string $mortgage_info
 * @property string $mortgage_rate
 * @property string $compensate_delay_days
 * @property string $risk_control
 * @property string $relevant_policies
 * @property string $market_analysis
 * @property string $borrow_remark
 * @property string $time_limit
 * @property string $style
 * @property string $account
 * @property string $account_yes
 * @property string $tender_times
 * @property string $apr
 * @property string $lowest_account
 * @property string $most_account
 * @property string $invest_step
 * @property string $valid_time
 * @property integer $delay_value_days
 * @property string $award
 * @property string $part_account
 * @property string $funds
 * @property string $is_false
 * @property string $open_account
 * @property string $open_borrow
 * @property string $open_tender
 * @property string $open_credit
 * @property string $content
 * @property string $agreement_template
 * @property string $addtime
 * @property integer $last_tender_time
 * @property string $addip
 * @property integer $is_do
 * @property string $guarantor_remark
 * @property string $guarantor_opinion
 * @property integer $is_rewarded
 * @property integer $is_join_reward
 * @property integer $organiser
 * @property integer $project_duration
 */
class Borrow extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $formal_time_start;
    public $formal_time_end;
    public $repayment_time_start;
    public $repayment_time_end;
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Borrow the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'dw_borrow';
    }

     //????????????
     protected $_status=array(  0=>'?????????',
                                1=>'???????????????',
                                3=>'???????????????????????????',
                                4=>'???????????????????????????',
                                5=>'?????????????????????',
                                6=>'?????????????????????',
                                7=>'???????????????',
                                8=>'??????????????????',
                                9=>'????????????????????????',
                                11=>'????????????',
                                100=>'????????????',
                                101=>'?????????',
                             );
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus($_status=NULL){
         if(isset($_status)){
             $this->status = $_status;
         }
         return $this->_status[$this->status];
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     }  
     
     //???????????????
     protected $_priority_type=array(-1=>'?????????',0=>'??????',2=>'?????????????????????',1=>'????????????????????????');
    
     public function getPriorityType(){
         return $this->_priority_type;
     }
     public function StrPriorityType(){
         return $this->_priority_type[$this->priority_type];
     }
     public function getStrPriorityType($key){
         return (array_key_exists($key, $this->_priority_type))?$this->_priority_type[$key]:"";
     }
    
    //????????????????????????
     protected $_guarantorStatus=array('?????????',"??????","?????????");
    
     public function getGuarantorStatus(){
         return $this->_guarantorStatus;
     }
     public function StrGuarantorStatus(){
         return $this->_guarantorStatus[$this->guarantor_status];
     }
     public function getStrGuarantorStatus($key){
         return (array_key_exists($key, $this->_guarantorStatus))?$this->_guarantorStatus[$key]:"";
     }

     //????????????
     protected  $_borrowMode = array('??????????????????','????????????','????????????');

     public function getBorrowMode(){
        return $this->_borrowMode;
     }

     public function StrBorrowMode(){
        return $this->_borrowMode[$this->borrow_mode];
     }

     public function getStrBorrowMode($key){
         return (array_key_exists($key, $this->_borrowMode))?$this->_borrowMode[$key]:"";
     }
     
     //????????????
     protected $_project_source=array('?????????',"??????????????????","??????????????????");
    
     public function getProjectSource(){
         return $this->_project_source;
     }
     public function StrProjectSource(){
         return $this->_project_source[$this->project_source];
     }
     public function getStrProjectSource($key){
         return (array_key_exists($key, $this->_project_source))?$this->_project_source[$key]:"";
     }
     
     //????????????????????????
     protected $_internal_audit_status=array('?????????',"??????","?????????");
    
     public function getInternal_audit_status(){
         return $this->_internal_audit_status;
     }
     public function StrInternal_audit_status(){
         return $this->_internal_audit_status[$this->internal_audit_status];
     }
     public function getStrInternal_audit_status($key){
         return (array_key_exists($key, $this->_internal_audit_status))?$this->_internal_audit_status[$key]:"";
     }
     
     //??????????????????
    public function getGuarantors(){
         $guarantorMosel = new GuarantorNew;
         $provinces = $guarantorMosel->findAll('status=1');
         foreach ($provinces as $guarantorMosel) {
             $res = $guarantorMosel->attributes;
             $guarantorName[$res['gid']] = $res['name'];
         }
         return $guarantorName;
     }
     public function StrGuarantors(){
         $provinces = $this->getGuarantors();
         return $provinces[$this->guarantors];
     }
     public function getStrGuarantors($key){
         $res  = $this->getGuarantors();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
     public function  clearCache($type,$id){
         $_url = '';
         switch ($type) {
             case '2'://?????????
                 $_url = 'invest';break;
             case '5'://?????????
                 $_url = 'lease';break;
             case '6':
                 $_url = 'factoring';break;
             case '7':
                 $_url = 'art';break;
             
             default://????????????
                 $_url = 'shengxin';break;
         }
         $url = 'https://www.itouzi.com/dinvest/'.$_url.'/detail?preview=1&id='.UrlUtil::_key2url($id).'&removeCache';
         return $url;
     }
        //?????????
        // ($data->type == 5)?(CHtml::link("?????????","",array("class" => "btn-success","target"=>"_blank"))):
                                   // (($data->type == 6)?(CHtml::link("?????????","http://www.itouzi.com/dinvest/factoring/detail?id=".UrlUtil::_key2url($data->id)."&removeCache",array("class" => "btn-success","target"=>"_blank"))):
                                   // (($data->type == 7)?(CHtml::link("?????????","http://www.itouzi.com/dinvest/art/detail?id=".UrlUtil::_key2url($data->id)."&removeCache",array("class" => "btn-success","target"=>"_blank"))):
                                   // (CHtml::link("?????????","http://www.itouzi.com/dinvest/invest/detail?id=".UrlUtil::_key2url($data->id)."&removeCache",array("class" => "btn-success","target"=>"_blank")))))';        
//         
                   
     //??????????????????
     public function getLowestAccount(){
         $lowest_account_list = array(
                                    100     =>'100???',
                                    10000   =>'1??????',
                                    50000   =>'5??????',
                                    100000  =>'10??????',
                                    500000  =>'50??????',
                                    1000000 =>'100??????',
                                    1500000 =>'150??????',
                                    2000000 =>'200??????',
                                    5000000 =>'500??????',
                                    10000000=>'1000??????',
                                );
        return $lowest_account_list;
     }
     
     
     //??????????????????
     public function getStyle(){
         return Yii::app()->params['repament_style'];
     }
     public function StrStyle(){
         return Yii::app()->params['repament_style'][$this->style];
     }
     public function getStrStyle($key){
         return (array_key_exists($key, Yii::app()->params['repament_style'])) ? Yii::app()->params['repament_style'][$key]:"";
     }
     
     
    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            //array('lease_subject', 'required'),
            array('site_id,internal_audit_status,user_id, status, guarantor_status, order, hits, is_vouch, view_type, vouch_times, repayment_user, delay_value_days, last_tender_time, is_do, is_rewarded, is_join_reward,organiser', 'numerical', 'integerOnly'=>true),
            array('time_limit', 'numerical', 'integerOnly'=>true, 'message'=>'??????????????????', 'min'=>0),
            array('name, litpic, verify_remark', 'length', 'max'=>255),
            array('flag, type, original_creditor, original_creditor_ID, vouch_account, source, publish, number_id, verify_time, forst_account, monthly_repayment, repayment_yesaccount, repayment_yesinterest, repayment_time, success_time, end_time, payment_account, each_time, use, time_limit, style, lowest_account, most_account, invest_step, valid_time, award, part_account, funds, is_false, open_account, open_borrow, open_tender, open_credit, agreement_template, addtime, addip,agreement_template_debt', 'length', 'max'=>50),
            array('guarantors', 'length', 'max'=>500),
            array('vouch_award', 'length', 'max'=>40),
            array('vouch_user', 'length', 'max'=>100),
            array('customer,verify_user, tender_times', 'length', 'max'=>11),
            array('repayment_remark, borrow_remark', 'length', 'max'=>250),
            array('mortgage_rate, apr', 'length', 'max'=>18),
            array('compensate_delay_days', 'length', 'max'=>1024),
            array('account, account_yes,risk_insurance', 'length', 'max'=>20),
            array('debtor_id,loan_contract_number,project_duration,project_duration_type,appointment_money,parent_mark,guarantor_status,parent_account_total,parent_id,rzt_contract_no,borrow_mode,special_welfare,banner_src,banner_link,increase_apr,project_stages,return_coupon,borrow_logo,is_renew,renewal_times,project_city,project_source,collection_basics,collection_worth,collection_context,collection_safekeeping,special_company,expire_time,expire_time_status,rzt_status,insurance_company,insurance_underwriting,time_limit,factoring_original_creditor,factoring_accounts_receivable,factoring_type,factoring_des,factoring_service_des,factoring_contract_name,factoring_deal_number,factoring_contract_number,factoring_contract_time,online_remark,priority_type,formal_time,lease_subject,internal_audit_status,repayment_account, use_detail, repayment_source, mortgage_info, risk_control, relevant_policies, market_analysis, content, guarantor_remark, guarantor_opinion,risk_insurance,score,complaint_information', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('debtor_id,loan_contract_number,appointment_money,formal_time_start,formal_time_end,repayment_time_start,repayment_time_end,parent_mark,guarantor_status,parent_account_total,parent_id,rzt_contract_no,borrow_mode,special_welfare,banner_src,banner_link,increase_apr,project_stages,return_coupon,borrow_logo,is_renew,renewal_times,project_city,project_source,collection_basics,collection_worth,collection_context,collection_safekeeping,special_company,expire_time,expire_time_status,rzt_status,insurance_company,insurance_underwriting,time_limit,factoring_original_creditor,factoring_accounts_receivable,factoring_type,factoring_des,factoring_service_des,factoring_contract_name,factoring_deal_number,factoring_contract_number,factoring_contract_time,online_remark,priority_type,formal_time,internal_audit_status,lease_subject,id, site_id, user_id, name, status, guarantor_status, order, hits, litpic, flag, is_vouch, type, guarantors, original_creditor, original_creditor_ID, view_type, risk_insurance,vouch_award, vouch_user, vouch_account, vouch_times, source, publish, customer, number_id, verify_user, verify_time, verify_remark, repayment_user, forst_account, repayment_account, monthly_repayment, repayment_yesaccount, repayment_yesinterest, repayment_time, repayment_remark, success_time, end_time, payment_account, each_time, use, use_detail, repayment_source, mortgage_info, mortgage_rate, compensate_delay_days, risk_control, relevant_policies, market_analysis, borrow_remark, time_limit, style, account, account_yes, tender_times, apr, lowest_account, most_account, invest_step, valid_time, delay_value_days, award, part_account, funds, is_false, open_account, open_borrow, open_tender, open_credit, content, agreement_template,agreement_template_debt, addtime, last_tender_time, addip, is_do, guarantor_remark, guarantor_opinion, is_rewarded, is_join_reward, organiser,score,complaint_information,project_duration,project_duration_type', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
       return array(
          "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
          "borrowInfo" =>  array(self::BELONGS_TO, 'BorrowTender', 'borrow_id'),
          "guarantorInfo" =>  array(self::BELONGS_TO, 'GuarantorNew', 'guarantors')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => '??????ID',
            'site_id' => 'Site',
            'user_id' => '????????????',
            'name' => '????????????',
            'status' => '????????????',
            'guarantor_status' => '??????????????????',
            'order' => 'Order',
            'hits' => 'Hits',
            'litpic' => 'Litpic',
            'flag' => '??????',
            'risk_insurance' => '???????????????',
            'is_vouch' => 'Is Vouch',
            'type' => '??????',
            'guarantors' => '??????????????????',
            'original_creditor' => '????????????',
            'original_creditor_ID' => '????????????????????????',
            'view_type' => 'View Type',
            'vouch_award' => 'Vouch Award',
            'vouch_user' => 'Vouch User',
            'vouch_account' => 'Vouch Account',
            'vouch_times' => 'Vouch Times',
            'source' => '??????',
            'publish' => '????????????',
            'lease_subject'=>'????????????',
            'customer' => '??????',
            'number_id' => 'Number',
            'verify_user' => '?????????',
            'verify_time' => '????????????',
            'verify_remark' => 'Verify Remark',
            'repayment_user' => 'Repayment User',
            'forst_account' => 'Forst Account',
            'repayment_account' => 'Repayment Account',
            'monthly_repayment' => '???????????????',
            'repayment_yesaccount' => '????????????',
            'repayment_yesinterest' => '????????????',
            'repayment_time' => '????????????',
            'repayment_remark' => '????????????',
            'success_time' => 'Success Time',
            'end_time' => 'End Time',
            'payment_account' => 'Payment Account',
            'each_time' => 'Each Time',
            'use' => '??????',
            'use_detail' => '??????????????????',
            'repayment_source' => '????????????',
            'mortgage_info' => '???????????????',
            'mortgage_rate' => '?????????',
            'compensate_delay_days' => '??????????????????',
            'risk_control' => '??????????????????',
            'relevant_policies' => '??????????????????',
            'market_analysis' => '????????????????????????',
            'borrow_remark' => '??????????????????',
            'time_limit' => '????????????',
            'style' => '????????????',
            'account' => '????????????',
            'account_yes' => '?????????',
            'tender_times' => 'Tender Times',
            'apr' => '??????',
            'lowest_account' => '??????????????????',
            'most_account' => '??????????????????',
            'invest_step' => '??????????????????',
            'valid_time' => '????????????',
            'delay_value_days' => '??????????????????',
            'award' => '????????????',
            'part_account' => '??????????????????',
            'funds' => '?????????????????????',
            'is_false' => '??????????????????',
            'open_account' => 'Open Account',
            'open_borrow' => 'Open Borrow',
            'open_tender' => 'Open Tender',
            'open_credit' => 'Open Credit',
            'content' => '????????????',
            'agreement_template' => '????????????',
            'agreement_template_debt' => '??????????????????',
            'addtime' => '????????????',
            'last_tender_time' => '????????????',
            'addip' => 'Addip',
            'is_do' => 'Is Do',
            'guarantor_remark' => '????????????????????????',
            'guarantor_opinion' => '??????????????????',
            'is_join_reward' => '????????????',
            'is_rewarded' => '????????????????????????',
            'organiser' => 'Organiser',
            'formal_time' => '??????????????????',
            'score'=>'??????',
            'complaint_information'=>'????????????',
            'internal_audit_status'=>'????????????????????????',
            'online_remark'=>'??????????????????',
            'insurance_company'=>'????????????',
            'insurance_underwriting'=>'????????????',
            'special_company'=>'??????????????????',
            'return_coupon'=>'???????????????????????????',
            'banner_src'=>'???????????????',
            'banner_link'=>'???????????????',
            'rzt_contract_no'=>'???????????????',
            'parent_account_total'=>'???????????????',
            'borrow_mode' => '????????????',
            'project_duration'=>'????????????'
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;
        $criteria->compare('id',$this->id,true);
        $criteria->compare('debtor_id',$this->debtor_id);
        $criteria->compare('loan_contract_number',$this->loan_contract_number);
        $criteria->compare('site_id',$this->site_id);
        $criteria->compare('internal_audit_status',$this->internal_audit_status);
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('name',$this->name,true);
        $criteria->compare('status',$this->status);
        $criteria->compare('guarantor_status',$this->guarantor_status);
        $criteria->compare('order',$this->order);
        $criteria->compare('hits',$this->hits);
        $criteria->compare('litpic',$this->litpic,true);
        $criteria->compare('flag',$this->flag);
        $criteria->compare('is_vouch',$this->is_vouch);
        $criteria->compare('type',$this->type);
        $criteria->compare('guarantors',$this->guarantors);
        $criteria->compare('original_creditor',$this->original_creditor,true);
        $criteria->compare('original_creditor_ID',$this->original_creditor_ID,true);
        $criteria->compare('view_type',$this->view_type);
        $criteria->compare('vouch_award',$this->vouch_award,true);
        $criteria->compare('vouch_user',$this->vouch_user,true);
        $criteria->compare('vouch_account',$this->vouch_account,true);
        $criteria->compare('vouch_times',$this->vouch_times);
        $criteria->compare('source',$this->source,true);
        $criteria->compare('publish',$this->publish,true);
        $criteria->compare('customer',$this->customer,true);
        $criteria->compare('number_id',$this->number_id,true);
        $criteria->compare('verify_user',$this->verify_user,true);
        if(!empty($this->verify_time))
             $criteria->addBetweenCondition('verify_time',strtotime($this->verify_time),(strtotime($this->verify_time)+86399));
        else
            $criteria->compare('verify_time',$this->verify_time);
        
        //formal_time???????????????
        if(!empty($this->formal_time_start)){
            if(!empty($this->formal_time_end)){
                $criteria->addBetweenCondition('formal_time',strtotime($this->formal_time_start),strtotime($this->formal_time_end)+86399);
            }else{
                $criteria->compare('formal_time',' >='.strtotime($this->formal_time_start));
            }
        }else{
            if(!empty($this->formal_time_end)){
                $criteria->compare('formal_time',' <='.(strtotime($this->formal_time_end)+86399));
            }
        }
        //repayment_time???????????????
        if(!empty($this->repayment_time_start)){
            if(!empty($this->repayment_time_end)){
                $criteria->addBetweenCondition('repayment_time',strtotime($this->repayment_time_start),strtotime($this->repayment_time_end)+86399);
            }else{
                $criteria->compare('repayment_time',' >='.strtotime($this->repayment_time_start));
            }
        }else{
            if(!empty($this->repayment_time_end)){
                $criteria->compare('repayment_time',' <='.(strtotime($this->repayment_time_end)+86399));
            }
        }
        
        $criteria->compare('verify_remark',$this->verify_remark,true);
        $criteria->compare('repayment_user',$this->repayment_user);
        $criteria->compare('forst_account',$this->forst_account,true);
        $criteria->compare('repayment_account',$this->repayment_account,true);
        $criteria->compare('monthly_repayment',$this->monthly_repayment,true);
        $criteria->compare('repayment_yesaccount',$this->repayment_yesaccount,true);
        $criteria->compare('repayment_yesinterest',$this->repayment_yesinterest,true);
        $criteria->compare('project_duration_type',$this->project_duration_type,true);
        $criteria->compare('repayment_remark',$this->repayment_remark,true);
        $criteria->compare('success_time',$this->success_time,true);
        $criteria->compare('end_time',$this->end_time,true);
        $criteria->compare('payment_account',$this->payment_account,true);
        $criteria->compare('each_time',$this->each_time,true);
        $criteria->compare('use',$this->use,true);
        $criteria->compare('use_detail',$this->use_detail,true);
        $criteria->compare('repayment_source',$this->repayment_source,true);
        $criteria->compare('mortgage_info',$this->mortgage_info,true);
        $criteria->compare('mortgage_rate',$this->mortgage_rate,true);
        $criteria->compare('compensate_delay_days',$this->compensate_delay_days,true);
        $criteria->compare('risk_control',$this->risk_control,true);
        $criteria->compare('relevant_policies',$this->relevant_policies,true);
        $criteria->compare('market_analysis',$this->market_analysis,true);
        $criteria->compare('borrow_remark',$this->borrow_remark,true);
        $criteria->compare('time_limit',$this->time_limit,true);
        $criteria->compare('style',$this->style,true);
        $criteria->compare('account',$this->account,true);
        $criteria->compare('account_yes',$this->account_yes,true);
        $criteria->compare('tender_times',$this->tender_times,true);
        $criteria->compare('apr',$this->apr,true);
        $criteria->compare('lowest_account',$this->lowest_account,true);
        $criteria->compare('most_account',$this->most_account,true);
        $criteria->compare('invest_step',$this->invest_step,true);
        $criteria->compare('valid_time',$this->valid_time,true);
        $criteria->compare('delay_value_days',$this->delay_value_days);
        $criteria->compare('award',$this->award,true);
        $criteria->compare('part_account',$this->part_account,true);
        $criteria->compare('funds',$this->funds,true);
        $criteria->compare('is_false',$this->is_false,true);
        $criteria->compare('open_account',$this->open_account,true);
        $criteria->compare('open_borrow',$this->open_borrow,true);
        $criteria->compare('open_tender',$this->open_tender,true);
        $criteria->compare('open_credit',$this->open_credit,true);
        $criteria->compare('content',$this->content,true);
        $criteria->compare('agreement_template',$this->agreement_template,true);
        $criteria->compare('agreement_template_debt',$this->agreement_template_debt);
        $criteria->compare('addtime',$this->addtime,true);
        if(!empty($this->last_tender_time))
             $criteria->addBetweenCondition('last_tender_time',strtotime($this->last_tender_time),(strtotime($this->last_tender_time)+86399));
        else
            $criteria->compare('last_tender_time',$this->last_tender_time);
        
        $criteria->compare('addip',$this->addip,true);
        $criteria->compare('is_do',$this->is_do);
        $criteria->compare('guarantor_remark',$this->guarantor_remark,true);
        $criteria->compare('guarantor_opinion',$this->guarantor_opinion,true);
        $criteria->compare('is_rewarded',$this->is_rewarded);
        $criteria->compare('is_join_reward',$this->is_join_reward);
        $criteria->compare('organiser',$this->organiser);
        $criteria->compare('borrow_mode',$this->borrow_mode);
        $criteria->compare('project_duration',$this->project_duration);
        return new CActiveDataProvider($this, array(
            'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
            'criteria'=>$criteria,
        ));
    }
    
    
}
