<?php

/**
 * This is the model class for table "firstp2p_deal".
 *
 * The followings are the available columns in table 'firstp2p_deal':
 * @property integer $id
 * @property string $name
 * @property string $sub_name
 * @property integer $cate_id
 * @property integer $agency_id
 * @property integer $advisory_id
 * @property integer $pay_agency_id
 * @property integer $entrust_agency_id
 * @property integer $management_agency_id
 * @property integer $generation_recharge_id
 * @property integer $canal_agency_id
 * @property integer $user_id
 * @property string $manager
 * @property string $manager_mobile
 * @property string $description
 * @property integer $is_effect
 * @property integer $is_delete
 * @property integer $sort
 * @property integer $type_id
 * @property integer $icon_type
 * @property string $icon
 * @property string $seo_title
 * @property string $seo_keyword
 * @property string $seo_description
 * @property integer $product_class_type
 * @property integer $loan_user_customer_type
 * @property integer $is_float_min_loan
 * @property double $borrow_amount
 * @property double $min_loan_money
 * @property double $max_loan_money
 * @property integer $repay_time
 * @property string $rate
 * @property integer $day
 * @property integer $create_time
 * @property integer $update_time
 * @property string $name_match
 * @property string $name_match_row
 * @property string $deal_cate_match
 * @property string $deal_cate_match_row
 * @property integer $deal_crowd
 * @property integer $bid_restrict
 * @property string $tag_match
 * @property string $tag_match_row
 * @property string $type_match
 * @property string $type_match_row
 * @property integer $is_recommend
 * @property integer $buy_count
 * @property double $load_money
 * @property double $repay_money
 * @property integer $start_time
 * @property integer $success_time
 * @property integer $repay_start_time
 * @property integer $last_repay_time
 * @property integer $next_repay_time
 * @property integer $bad_time
 * @property integer $deal_status
 * @property integer $enddate
 * @property integer $voffice
 * @property integer $vposition
 * @property string $services_fee
 * @property integer $publish_wait
 * @property integer $is_send_bad_msg
 * @property string $bad_msg
 * @property integer $send_half_msg_time
 * @property integer $send_three_msg_time
 * @property integer $is_send_half_msg
 * @property integer $is_has_loans
 * @property integer $is_during_repay
 * @property integer $loantype
 * @property integer $warrant
 * @property integer $sub_count
 * @property integer $parent_id
 * @property integer $is_visible
 * @property integer $is_update
 * @property string $update_json
 * @property string $period_rate
 * @property string $point_percent
 * @property integer $is_sub_deal_loaded
 * @property string $loan_fee_rate
 * @property string $consult_fee_rate
 * @property string $guarantee_fee_rate
 * @property string $manage_fee_rate
 * @property string $pay_fee_rate
 * @property string $management_fee_rate
 * @property string $canal_fee_rate
 * @property string $income_fee_rate
 * @property string $income_total_rate
 * @property string $advisor_fee_rate
 * @property string $manage_fee_text
 * @property string $contract_tpl_type
 * @property integer $is_simple_interest
 * @property string $prepay_rate
 * @property string $compensation_days
 * @property string $loan_compensation_days
 * @property integer $prepay_penalty_days
 * @property string $prepay_days_limit
 * @property string $overdue_rate
 * @property integer $overdue_day
 * @property string $note
 * @property string $coupon
 * @property integer $coupon_type
 * @property integer $min_loan_total_count
 * @property string $min_loan_total_amount
 * @property integer $min_loan_total_limit_relation
 * @property string $deal_tag_name
 * @property string $deal_tag_desc
 * @property integer $project_id
 * @property string $approve_number
 * @property integer $deal_type
 * @property integer $is_doing
 * @property string $packing_rate
 * @property string $annual_payment_rate
 * @property integer $advance_agency_id
 * @property integer $report_status
 * @property integer $report_type
 * @property integer $site_id
 * @property integer $jys_id
 * @property string $jys_record_number
 * @property double $consult_fee_period_rate
 */
class PHDeal extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Deal the static model class
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
		return 'firstp2p_deal';
	}

    public function getDbConnection()
    {
        return Yii::app()->phdb;
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, sub_name, cate_id, agency_id, user_id, manager, manager_mobile, description, is_effect, is_delete, sort, type_id, icon_type, icon, seo_title, seo_keyword, seo_description, product_class_type, loan_user_customer_type, is_float_min_loan, borrow_amount, repay_time, rate, day, create_time, update_time, name_match, name_match_row, deal_cate_match, deal_cate_match_row, tag_match, tag_match_row, type_match, type_match_row, is_recommend, buy_count, load_money, repay_money, start_time, success_time, repay_start_time, last_repay_time, next_repay_time, bad_time, deal_status, enddate, voffice, vposition, services_fee, publish_wait, is_send_bad_msg, bad_msg, send_half_msg_time, send_three_msg_time, is_has_loans, loantype, warrant, update_json, manage_fee_text, note, approve_number', 'required'),
			array('cate_id, agency_id, advisory_id, pay_agency_id, entrust_agency_id, management_agency_id, generation_recharge_id, canal_agency_id, user_id, is_effect, is_delete, sort, type_id, icon_type, product_class_type, loan_user_customer_type, is_float_min_loan, repay_time, day, create_time, update_time, deal_crowd, bid_restrict, is_recommend, buy_count, start_time, success_time, repay_start_time, last_repay_time, next_repay_time, bad_time, deal_status, enddate, voffice, vposition, publish_wait, is_send_bad_msg, send_half_msg_time, send_three_msg_time, is_send_half_msg, is_has_loans, is_during_repay, loantype, warrant, sub_count, parent_id, is_visible, is_update, is_sub_deal_loaded, is_simple_interest, prepay_penalty_days, overdue_day, coupon_type, min_loan_total_count, min_loan_total_limit_relation, project_id, deal_type, is_doing, advance_agency_id, report_status, report_type, site_id, jys_id', 'numerical', 'integerOnly'=>true),
			array('borrow_amount, min_loan_money, max_loan_money, load_money, repay_money, consult_fee_period_rate', 'numerical'),
			array('sub_name, icon, manage_fee_text, contract_tpl_type, deal_tag_name, deal_tag_desc, approve_number, jys_record_number', 'length', 'max'=>255),
			array('manager, manager_mobile', 'length', 'max'=>50),
			array('rate, period_rate, loan_fee_rate, consult_fee_rate, guarantee_fee_rate, manage_fee_rate, pay_fee_rate, management_fee_rate, canal_fee_rate, income_fee_rate, advisor_fee_rate, prepay_rate, overdue_rate, packing_rate, annual_payment_rate', 'length', 'max'=>8),
			array('services_fee, min_loan_total_amount', 'length', 'max'=>20),
			array('point_percent', 'length', 'max'=>16),
			array('income_total_rate', 'length', 'max'=>11),
			array('compensation_days, loan_compensation_days, prepay_days_limit', 'length', 'max'=>10),
			array('coupon', 'length', 'max'=>1024),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, frozen_wait_capital,recipient_number,recipient_wait_capital,investor_number,investor_wait_capital,name, sub_name, cate_id, agency_id, advisory_id, pay_agency_id, entrust_agency_id, management_agency_id, generation_recharge_id, canal_agency_id, user_id, manager, manager_mobile, description, is_effect, is_delete, sort, type_id, icon_type, icon, seo_title, seo_keyword, seo_description, product_class_type, loan_user_customer_type, is_float_min_loan, borrow_amount, min_loan_money, max_loan_money, repay_time, rate, day, create_time, update_time, name_match, name_match_row, deal_cate_match, deal_cate_match_row, deal_crowd, bid_restrict, tag_match, tag_match_row, type_match, type_match_row, is_recommend, buy_count, load_money, repay_money, start_time, success_time, repay_start_time, last_repay_time, next_repay_time, bad_time, deal_status, enddate, voffice, vposition, services_fee, publish_wait, is_send_bad_msg, bad_msg, send_half_msg_time, send_three_msg_time, is_send_half_msg, is_has_loans, is_during_repay, loantype, warrant, sub_count, parent_id, is_visible, is_update, update_json, period_rate, point_percent, is_sub_deal_loaded, loan_fee_rate, consult_fee_rate, guarantee_fee_rate, manage_fee_rate, pay_fee_rate, management_fee_rate, canal_fee_rate, income_fee_rate, income_total_rate, advisor_fee_rate, manage_fee_text, contract_tpl_type, is_simple_interest, prepay_rate, compensation_days, loan_compensation_days, prepay_penalty_days, prepay_days_limit, overdue_rate, overdue_day, note, coupon, coupon_type, min_loan_total_count, min_loan_total_amount, min_loan_total_limit_relation, deal_tag_name, deal_tag_desc, project_id, approve_number, deal_type, is_doing, packing_rate, annual_payment_rate, advance_agency_id, report_status, report_type, site_id, jys_id, jys_record_number, consult_fee_period_rate', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'sub_name' => 'Sub Name',
			'cate_id' => 'Cate',
			'agency_id' => 'Agency',
			'advisory_id' => 'Advisory',
			'pay_agency_id' => 'Pay Agency',
			'entrust_agency_id' => 'Entrust Agency',
			'management_agency_id' => 'Management Agency',
			'generation_recharge_id' => 'Generation Recharge',
			'canal_agency_id' => 'Canal Agency',
			'user_id' => 'User',
			'manager' => 'Manager',
			'manager_mobile' => 'Manager Mobile',
			'description' => 'Description',
			'is_effect' => 'Is Effect',
			'is_delete' => 'Is Delete',
			'sort' => 'Sort',
			'type_id' => 'Type',
			'icon_type' => 'Icon Type',
			'icon' => 'Icon',
			'seo_title' => 'Seo Title',
			'seo_keyword' => 'Seo Keyword',
			'seo_description' => 'Seo Description',
			'product_class_type' => 'Product Class Type',
			'loan_user_customer_type' => 'Loan User Customer Type',
			'is_float_min_loan' => 'Is Float Min Loan',
			'borrow_amount' => 'Borrow Amount',
			'min_loan_money' => 'Min Loan Money',
			'max_loan_money' => 'Max Loan Money',
			'repay_time' => 'Repay Time',
			'rate' => 'Rate',
			'day' => 'Day',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'name_match' => 'Name Match',
			'name_match_row' => 'Name Match Row',
			'deal_cate_match' => 'Deal Cate Match',
			'deal_cate_match_row' => 'Deal Cate Match Row',
			'deal_crowd' => 'Deal Crowd',
			'bid_restrict' => 'Bid Restrict',
			'tag_match' => 'Tag Match',
			'tag_match_row' => 'Tag Match Row',
			'type_match' => 'Type Match',
			'type_match_row' => 'Type Match Row',
			'is_recommend' => 'Is Recommend',
			'buy_count' => 'Buy Count',
			'load_money' => 'Load Money',
			'repay_money' => 'Repay Money',
			'start_time' => 'Start Time',
			'success_time' => 'Success Time',
			'repay_start_time' => 'Repay Start Time',
			'last_repay_time' => 'Last Repay Time',
			'next_repay_time' => 'Next Repay Time',
			'bad_time' => 'Bad Time',
			'deal_status' => 'Deal Status',
			'enddate' => 'Enddate',
			'voffice' => 'Voffice',
			'vposition' => 'Vposition',
			'services_fee' => 'Services Fee',
			'publish_wait' => 'Publish Wait',
			'is_send_bad_msg' => 'Is Send Bad Msg',
			'bad_msg' => 'Bad Msg',
			'send_half_msg_time' => 'Send Half Msg Time',
			'send_three_msg_time' => 'Send Three Msg Time',
			'is_send_half_msg' => 'Is Send Half Msg',
			'is_has_loans' => 'Is Has Loans',
			'is_during_repay' => 'Is During Repay',
			'loantype' => 'Loantype',
			'warrant' => 'Warrant',
			'sub_count' => 'Sub Count',
			'parent_id' => 'Parent',
			'is_visible' => 'Is Visible',
			'is_update' => 'Is Update',
			'update_json' => 'Update Json',
			'period_rate' => 'Period Rate',
			'point_percent' => 'Point Percent',
			'is_sub_deal_loaded' => 'Is Sub Deal Loaded',
			'loan_fee_rate' => 'Loan Fee Rate',
			'consult_fee_rate' => 'Consult Fee Rate',
			'guarantee_fee_rate' => 'Guarantee Fee Rate',
			'manage_fee_rate' => 'Manage Fee Rate',
			'pay_fee_rate' => 'Pay Fee Rate',
			'management_fee_rate' => 'Management Fee Rate',
			'canal_fee_rate' => 'Canal Fee Rate',
			'income_fee_rate' => 'Income Fee Rate',
			'income_total_rate' => 'Income Total Rate',
			'advisor_fee_rate' => 'Advisor Fee Rate',
			'manage_fee_text' => 'Manage Fee Text',
			'contract_tpl_type' => 'Contract Tpl Type',
			'is_simple_interest' => 'Is Simple Interest',
			'prepay_rate' => 'Prepay Rate',
			'compensation_days' => 'Compensation Days',
			'loan_compensation_days' => 'Loan Compensation Days',
			'prepay_penalty_days' => 'Prepay Penalty Days',
			'prepay_days_limit' => 'Prepay Days Limit',
			'overdue_rate' => 'Overdue Rate',
			'overdue_day' => 'Overdue Day',
			'note' => 'Note',
			'coupon' => 'Coupon',
			'coupon_type' => 'Coupon Type',
			'min_loan_total_count' => 'Min Loan Total Count',
			'min_loan_total_amount' => 'Min Loan Total Amount',
			'min_loan_total_limit_relation' => 'Min Loan Total Limit Relation',
			'deal_tag_name' => 'Deal Tag Name',
			'deal_tag_desc' => 'Deal Tag Desc',
			'project_id' => 'Project',
			'approve_number' => 'Approve Number',
			'deal_type' => 'Deal Type',
			'is_doing' => 'Is Doing',
			'packing_rate' => 'Packing Rate',
			'annual_payment_rate' => 'Annual Payment Rate',
			'advance_agency_id' => 'Advance Agency',
			'report_status' => 'Report Status',
			'report_type' => 'Report Type',
			'site_id' => 'Site',
			'jys_id' => 'Jys',
			'jys_record_number' => 'Jys Record Number',
			'consult_fee_period_rate' => 'Consult Fee Period Rate',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('sub_name',$this->sub_name,true);
		$criteria->compare('cate_id',$this->cate_id);
		$criteria->compare('agency_id',$this->agency_id);
		$criteria->compare('advisory_id',$this->advisory_id);
		$criteria->compare('pay_agency_id',$this->pay_agency_id);
		$criteria->compare('entrust_agency_id',$this->entrust_agency_id);
		$criteria->compare('management_agency_id',$this->management_agency_id);
		$criteria->compare('generation_recharge_id',$this->generation_recharge_id);
		$criteria->compare('canal_agency_id',$this->canal_agency_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('manager',$this->manager,true);
		$criteria->compare('manager_mobile',$this->manager_mobile,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('is_effect',$this->is_effect);
		$criteria->compare('is_delete',$this->is_delete);
		$criteria->compare('sort',$this->sort);
		$criteria->compare('type_id',$this->type_id);
		$criteria->compare('icon_type',$this->icon_type);
		$criteria->compare('icon',$this->icon,true);
		$criteria->compare('seo_title',$this->seo_title,true);
		$criteria->compare('seo_keyword',$this->seo_keyword,true);
		$criteria->compare('seo_description',$this->seo_description,true);
		$criteria->compare('product_class_type',$this->product_class_type);
		$criteria->compare('loan_user_customer_type',$this->loan_user_customer_type);
		$criteria->compare('is_float_min_loan',$this->is_float_min_loan);
		$criteria->compare('borrow_amount',$this->borrow_amount);
		$criteria->compare('min_loan_money',$this->min_loan_money);
		$criteria->compare('max_loan_money',$this->max_loan_money);
		$criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('rate',$this->rate,true);
		$criteria->compare('day',$this->day);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('name_match',$this->name_match,true);
		$criteria->compare('name_match_row',$this->name_match_row,true);
		$criteria->compare('deal_cate_match',$this->deal_cate_match,true);
		$criteria->compare('deal_cate_match_row',$this->deal_cate_match_row,true);
		$criteria->compare('deal_crowd',$this->deal_crowd);
		$criteria->compare('bid_restrict',$this->bid_restrict);
		$criteria->compare('tag_match',$this->tag_match,true);
		$criteria->compare('tag_match_row',$this->tag_match_row,true);
		$criteria->compare('type_match',$this->type_match,true);
		$criteria->compare('type_match_row',$this->type_match_row,true);
		$criteria->compare('is_recommend',$this->is_recommend);
		$criteria->compare('buy_count',$this->buy_count);
		$criteria->compare('load_money',$this->load_money);
		$criteria->compare('repay_money',$this->repay_money);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('success_time',$this->success_time);
		$criteria->compare('repay_start_time',$this->repay_start_time);
		$criteria->compare('last_repay_time',$this->last_repay_time);
		$criteria->compare('next_repay_time',$this->next_repay_time);
		$criteria->compare('bad_time',$this->bad_time);
		$criteria->compare('deal_status',$this->deal_status);
		$criteria->compare('enddate',$this->enddate);
		$criteria->compare('voffice',$this->voffice);
		$criteria->compare('vposition',$this->vposition);
		$criteria->compare('services_fee',$this->services_fee,true);
		$criteria->compare('publish_wait',$this->publish_wait);
		$criteria->compare('is_send_bad_msg',$this->is_send_bad_msg);
		$criteria->compare('bad_msg',$this->bad_msg,true);
		$criteria->compare('send_half_msg_time',$this->send_half_msg_time);
		$criteria->compare('send_three_msg_time',$this->send_three_msg_time);
		$criteria->compare('is_send_half_msg',$this->is_send_half_msg);
		$criteria->compare('is_has_loans',$this->is_has_loans);
		$criteria->compare('is_during_repay',$this->is_during_repay);
		$criteria->compare('loantype',$this->loantype);
		$criteria->compare('warrant',$this->warrant);
		$criteria->compare('sub_count',$this->sub_count);
		$criteria->compare('parent_id',$this->parent_id);
		$criteria->compare('is_visible',$this->is_visible);
		$criteria->compare('is_update',$this->is_update);
		$criteria->compare('update_json',$this->update_json,true);
		$criteria->compare('period_rate',$this->period_rate,true);
		$criteria->compare('point_percent',$this->point_percent,true);
		$criteria->compare('is_sub_deal_loaded',$this->is_sub_deal_loaded);
		$criteria->compare('loan_fee_rate',$this->loan_fee_rate,true);
		$criteria->compare('consult_fee_rate',$this->consult_fee_rate,true);
		$criteria->compare('guarantee_fee_rate',$this->guarantee_fee_rate,true);
		$criteria->compare('manage_fee_rate',$this->manage_fee_rate,true);
		$criteria->compare('pay_fee_rate',$this->pay_fee_rate,true);
		$criteria->compare('management_fee_rate',$this->management_fee_rate,true);
		$criteria->compare('canal_fee_rate',$this->canal_fee_rate,true);
		$criteria->compare('income_fee_rate',$this->income_fee_rate,true);
		$criteria->compare('income_total_rate',$this->income_total_rate,true);
		$criteria->compare('advisor_fee_rate',$this->advisor_fee_rate,true);
		$criteria->compare('manage_fee_text',$this->manage_fee_text,true);
		$criteria->compare('contract_tpl_type',$this->contract_tpl_type,true);
		$criteria->compare('is_simple_interest',$this->is_simple_interest);
		$criteria->compare('prepay_rate',$this->prepay_rate,true);
		$criteria->compare('compensation_days',$this->compensation_days,true);
		$criteria->compare('loan_compensation_days',$this->loan_compensation_days,true);
		$criteria->compare('prepay_penalty_days',$this->prepay_penalty_days);
		$criteria->compare('prepay_days_limit',$this->prepay_days_limit,true);
		$criteria->compare('overdue_rate',$this->overdue_rate,true);
		$criteria->compare('overdue_day',$this->overdue_day);
		$criteria->compare('note',$this->note,true);
		$criteria->compare('coupon',$this->coupon,true);
		$criteria->compare('coupon_type',$this->coupon_type);
		$criteria->compare('min_loan_total_count',$this->min_loan_total_count);
		$criteria->compare('min_loan_total_amount',$this->min_loan_total_amount,true);
		$criteria->compare('min_loan_total_limit_relation',$this->min_loan_total_limit_relation);
		$criteria->compare('deal_tag_name',$this->deal_tag_name,true);
		$criteria->compare('deal_tag_desc',$this->deal_tag_desc,true);
		$criteria->compare('project_id',$this->project_id);
		$criteria->compare('approve_number',$this->approve_number,true);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('is_doing',$this->is_doing);
		$criteria->compare('packing_rate',$this->packing_rate,true);
		$criteria->compare('annual_payment_rate',$this->annual_payment_rate,true);
		$criteria->compare('advance_agency_id',$this->advance_agency_id);
		$criteria->compare('report_status',$this->report_status);
		$criteria->compare('report_type',$this->report_type);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('jys_id',$this->jys_id);
		$criteria->compare('jys_record_number',$this->jys_record_number,true);
		$criteria->compare('consult_fee_period_rate',$this->consult_fee_period_rate);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}