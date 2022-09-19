<?php

/**
 * This is the model class for table "ag_wx_repayment_plan".
 *
 * The followings are the available columns in table 'ag_wx_repayment_plan':
 * @property string $id
 * @property integer $deal_id
 * @property integer $repay_type
 * @property integer $repay_id
 * @property integer $repayment_form
 * @property integer $loan_repay_type
 * @property string $project_product_class
 * @property string $deal_name
 * @property string $jys_record_number
 * @property string $project_name
 * @property integer $deal_advisory_id
 * @property string $deal_advisory_name
 * @property integer $deal_user_id
 * @property string $deal_user_real_name
 * @property string $normal_time
 * @property string $loan_user_id
 * @property string $repayment_total
 * @property integer $plan_time
 * @property string $deal_loan_id
 * @property integer $status
 * @property string $evidence_pic
 * @property string $task_success_time
 * @property string $task_remark
 * @property integer $add_admin_id
 * @property integer $addtime
 * @property string $addip
 * @property integer $start_admin_id
 * @property integer $starttime
 * @property string $startip
 */
class WxRepaymentPlan extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return WxRepaymentPlan the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->fdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_wx_repayment_plan';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('deal_advisory_id, deal_user_id', 'required'),
			array('deal_id, repay_type, repay_id, repayment_form, loan_repay_type, deal_advisory_id, deal_user_id, plan_time, status, add_admin_id, addtime, start_admin_id, starttime', 'numerical', 'integerOnly'=>true),
			array('project_product_class', 'length', 'max'=>25),
			array('deal_name, deal_advisory_name, deal_user_real_name', 'length', 'max'=>127),
			array('jys_record_number, project_name, evidence_pic, task_remark', 'length', 'max'=>255),
			array('normal_time', 'length', 'max'=>1024),
			array('repayment_total', 'length', 'max'=>20),
			array('addip, startip', 'length', 'max'=>31),
			array('loan_user_id', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, deal_id, repay_type, repay_id, repayment_form, loan_repay_type, project_product_class, deal_name, jys_record_number, project_name, deal_advisory_id, deal_advisory_name, deal_user_id, deal_user_real_name, normal_time, loan_user_id, repayment_total, plan_time, deal_loan_id, status, evidence_pic, task_success_time, task_remark, add_admin_id, addtime, addip, start_admin_id, starttime, startip', 'safe', 'on'=>'search'),
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
			'deal_id' => 'Deal',
			'repay_type' => 'Repay Type',
			'repay_id' => 'Repay',
			'repayment_form' => 'Repayment Form',
			'loan_repay_type' => 'Loan Repay Type',
			'project_product_class' => 'Project Product Class',
			'deal_name' => 'Deal Name',
			'jys_record_number' => 'Approve Number',
			'project_name' => 'Project Name',
			'deal_advisory_id' => 'Deal Agency',
			'deal_advisory_name' => 'Deal Agency Name',
			'deal_user_id' => 'Deal User',
			'deal_user_real_name' => 'Deal User Real Name',
			'normal_time' => 'Normal Time',
			'loan_user_id' => 'Loan User',
			'repayment_total' => 'Repayment Total',
			'plan_time' => 'Plan Time',
			'status' => 'Status',
			'evidence_pic' => 'Evidence Pic',
			'task_success_time' => 'Task Success Time',
			'task_remark' => 'Task Remark',
			'add_admin_id' => 'Add Admin',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'start_admin_id' => 'Start Admin',
			'starttime' => 'Starttime',
			'startip' => 'Startip',
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
		$criteria->compare('deal_id',$this->deal_id);
		$criteria->compare('repay_type',$this->repay_type);
		$criteria->compare('repay_id',$this->repay_id);
		$criteria->compare('repayment_form',$this->repayment_form);
		$criteria->compare('loan_repay_type',$this->loan_repay_type);
		$criteria->compare('project_product_class',$this->project_product_class,true);
		$criteria->compare('deal_name',$this->deal_name,true);
		$criteria->compare('jys_record_number',$this->jys_record_number,true);
		$criteria->compare('project_name',$this->project_name,true);
		$criteria->compare('deal_advisory_id',$this->deal_advisory_id);
		$criteria->compare('deal_advisory_name',$this->deal_advisory_name,true);
		$criteria->compare('deal_user_id',$this->deal_user_id);
		$criteria->compare('deal_user_real_name',$this->deal_user_real_name,true);
		$criteria->compare('normal_time',$this->normal_time,true);
		$criteria->compare('loan_user_id',$this->loan_user_id,true);
		$criteria->compare('repayment_total',$this->repayment_total,true);
		$criteria->compare('plan_time',$this->plan_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('evidence_pic',$this->evidence_pic,true);
		$criteria->compare('task_success_time',$this->task_success_time,true);
		$criteria->compare('task_remark',$this->task_remark,true);
		$criteria->compare('add_admin_id',$this->add_admin_id);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('start_admin_id',$this->start_admin_id);
		$criteria->compare('starttime',$this->starttime);
		$criteria->compare('startip',$this->startip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}