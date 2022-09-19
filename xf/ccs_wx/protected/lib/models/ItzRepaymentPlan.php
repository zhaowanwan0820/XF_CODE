<?php

/**
 * This is the model class for table "itz_repayment_plan".
 *
 * The followings are the available columns in table 'itz_repayment_plan':
 * @property string $id
 * @property integer $type
 * @property integer $normal_time
 * @property string $borrow_list
 * @property string $wise_list
 * @property integer $repayment_percent
 * @property string $repayment_total
 * @property integer $plan_time
 * @property string $remark
 * @property integer $status
 * @property string $task_start_time
 * @property string $task_end_time
 * @property string $task_remark
 * @property integer $admin_id
 * @property string $admin_name
 * @property integer $addtime
 */
class ItzRepaymentPlan extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzRepaymentPlan the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_repayment_plan';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, normal_time, plan_time, status, admin_id, addtime', 'numerical', 'integerOnly'=>true),
			array('remark', 'length', 'max'=>300),
			array('repayment_total,task_start_time, task_end_time', 'length', 'max'=>11),
			array('task_remark', 'length', 'max'=>500),
			array('admin_name', 'length', 'max'=>100),
			array('borrow_list', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, normal_time, borrow_list, wise_list, repayment_percent, repayment_total, plan_time, remark, status, task_start_time, task_end_time, task_remark, admin_id, admin_name, addtime', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'normal_time' => 'Normal Time',
			'borrow_list' => 'Borrow List',
			'wise_list' => 'Wise List',
			'repayment_percent' => 'Repayment Percent',
			'repayment_total' => 'Repayment Total',
			'plan_time' => 'Plan Time',
			'remark' => 'Remark',
			'status' => 'Status',
			'task_start_time' => 'Task Start Time',
			'task_end_time' => 'Task End Time',
			'task_remark' => 'Task Remark',
			'admin_id' => 'Admin',
			'admin_name' => 'Admin Name',
			'addtime' => 'Addtime',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('normal_time',$this->normal_time);
		$criteria->compare('borrow_list',$this->borrow_list,true);
		$criteria->compare('wise_list',$this->wise_list,true);
		$criteria->compare('repayment_percent',$this->repayment_percent);
		$criteria->compare('repayment_total',$this->repayment_total,true);
		$criteria->compare('plan_time',$this->plan_time);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('task_start_time',$this->task_start_time,true);
		$criteria->compare('task_end_time',$this->task_end_time,true);
		$criteria->compare('task_remark',$this->task_remark,true);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('admin_name',$this->admin_name,true);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}