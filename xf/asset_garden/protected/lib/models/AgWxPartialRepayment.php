<?php

/**
 * This is the model class for table "ag_wx_partial_repayment".
 *
 * The followings are the available columns in table 'ag_wx_partial_repayment':
 * @property integer $id
 * @property string $total_repayment
 * @property string $total_successful_amount
 * @property string $success_number
 * @property string $fail_number
 * @property string $total_fail_amount
 * @property string $admin_user_id
 * @property string $pay_user
 * @property string $pay_plan_time
 * @property integer $status
 * @property string $remark
 * @property string $template_url
 * @property string $proof_url
 * @property string $addtime
 * @property integer $updatetime
 */
class AgWxPartialRepayment extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxPartialRepayment the static model class
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
		return 'ag_wx_partial_repayment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, updatetime', 'numerical', 'integerOnly'=>true),
			array('total_repayment, total_successful_amount, total_fail_amount', 'length', 'max'=>10),
			array('success_number, fail_number, admin_user_id, pay_plan_time, addtime', 'length', 'max'=>11),
			array('pay_user', 'length', 'max'=>100),
			array('remark, template_url, proof_url', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, task_success_time,task_remark,total_repayment, total_successful_amount, success_number, fail_number, total_fail_amount, admin_user_id, pay_user, pay_plan_time, status, remark, template_url, proof_url, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'total_repayment' => 'Total Repayment',
			'total_successful_amount' => 'Total Successful Amount',
			'success_number' => 'Success Number',
			'fail_number' => 'Fail Number',
			'total_fail_amount' => 'Total Fail Amount',
			'admin_user_id' => 'Admin User',
			'pay_user' => 'Pay User',
			'pay_plan_time' => 'Pay Plan Time',
			'status' => 'Status',
			'remark' => 'Remark',
			'template_url' => 'Template Url',
			'proof_url' => 'Proof Url',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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
		$criteria->compare('total_repayment',$this->total_repayment,true);
		$criteria->compare('total_successful_amount',$this->total_successful_amount,true);
		$criteria->compare('success_number',$this->success_number,true);
		$criteria->compare('fail_number',$this->fail_number,true);
		$criteria->compare('total_fail_amount',$this->total_fail_amount,true);
		$criteria->compare('admin_user_id',$this->admin_user_id,true);
		$criteria->compare('pay_user',$this->pay_user,true);
		$criteria->compare('pay_plan_time',$this->pay_plan_time,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('template_url',$this->template_url,true);
		$criteria->compare('proof_url',$this->proof_url,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}