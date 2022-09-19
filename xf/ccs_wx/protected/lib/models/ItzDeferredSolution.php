<?php

/**
 * This is the model class for table "itz_deferred_solution".
 *
 * The followings are the available columns in table 'itz_deferred_solution':
 * @property string $id
 * @property integer $type
 * @property string $company_name
 * @property integer $num
 * @property integer $status
 * @property string $excel_url
 * @property integer $addtime
 * @property string $pre_borrow_list
 * @property string $loan_contract_number
 */
class ItzDeferredSolution extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzDeferredSolution the static model class
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
		return 'itz_deferred_solution';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type', 'required'),
			array('type, num, status, addtime', 'numerical', 'integerOnly'=>true),
			array('company_name', 'length', 'max'=>50),
			array('excel_url', 'length', 'max'=>200),
			array('loan_contract_number', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, company_name, num, status, excel_url, addtime, pre_borrow_list, loan_contract_number', 'safe', 'on'=>'search'),
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
			'company_name' => 'Company Name',
			'num' => 'Num',
			'status' => 'Status',
			'excel_url' => 'Excel Url',
			'addtime' => 'Addtime',
			'pre_borrow_list' => 'Pre Borrow List',
			'loan_contract_number' => 'Loan Contract Number',
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
		$criteria->compare('company_name',$this->company_name,true);
		$criteria->compare('num',$this->num);
		$criteria->compare('status',$this->status);
		$criteria->compare('excel_url',$this->excel_url,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('pre_borrow_list',$this->pre_borrow_list,true);
		$criteria->compare('loan_contract_number',$this->loan_contract_number,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}