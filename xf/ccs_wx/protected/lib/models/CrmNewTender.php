<?php

/**
 * This is the model class for table "crm_new_tender".
 *
 * The followings are the available columns in table 'crm_new_tender':
 * @property integer $id
 * @property integer $type
 * @property integer $status
 * @property integer $remark
 * @property integer $addtime
 * @property integer $updatetime
 */
class CrmNewTender extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmNewTender the static model class
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
		return Yii::app()->crmdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'crm_new_tender';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id,tender_id,user_id,borrow_id,project_duration,debt_type,tender_time,admin_id,admin_pid,addtime,status,first', 'numerical', 'integerOnly'=>true),
			array('id,tender_id,user_id,borrow_id,project_duration,debt_type,tender_time,admin_id,admin_pid,addtime,status,first,borrow_name,account_init,apr', 'safe', 'on'=>'search'),
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
			'tender_id' => '投资编号',
			'user_id' => '用户编号',
			'borrow_id' => '标的编号',
			'project_duration' => '投资期限',
			'debt_type' => '项目类型',
			'tender_time' => '投资时间',
			'admin_id' => '客维编号',
			'admin_pid' => '客维组长编号',
			'addtime' => "添加时间",
			'status' => '状态',
			'first' => '是否首投',
			'borrow_name' => '标的名称',
			'account_init' => '投资金额',
			'apr' => '标的利率',
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
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('project_duration',$this->project_duration);
		$criteria->compare('debt_type',$this->debt_type);
		$criteria->compare('tender_time',$this->tender_time);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('admin_pid',$this->admin_pid);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('status',$this->status);
		$criteria->compare('first',$this->first);
		$criteria->compare('borrow_name',$this->borrow_name);
		$criteria->compare('account_init',$this->account_init);
		$criteria->compare('apr',$this->apr);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}