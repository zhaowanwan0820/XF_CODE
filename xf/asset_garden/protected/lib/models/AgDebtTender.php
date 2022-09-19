<?php

/**
 * This is the model class for table "ag_debt_tender".
 *
 * The followings are the available columns in table 'ag_debt_tender':
 * @property string $id
 * @property integer $debt_id
 * @property integer $user_id
 * @property integer $new_tender_id
 * @property integer $debt_src
 * @property string $amount
 * @property string $spend_amount
 * @property string $c_download_url
 * @property string $c_viewpdf_url
 * @property integer $addtime
 * @property string $addip
 */
class AgDebtTender extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgDebtTender the static model class
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
		return Yii::app()->agdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_debt_tender';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('debt_id, user_id, new_tender_id, debt_src, addtime', 'numerical', 'integerOnly'=>true),
			array('amount, spend_amount', 'length', 'max'=>11),
			array('c_download_url, c_viewpdf_url', 'length', 'max'=>255),
			array('addip', 'length', 'max'=>31),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, debt_id, user_id,bond_no,new_tender_id, discount, debt_src, amount, spend_amount, c_download_url, c_viewpdf_url, addtime, addip', 'safe', 'on'=>'search'),
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
			'debt_id' => 'Debt',
			'user_id' => 'User',
			'new_tender_id' => 'New Tender',
			'debt_src' => 'Debt Src',
			'amount' => 'Amount',
			'spend_amount' => 'Spend Amount',
			'c_download_url' => 'C Download Url',
			'c_viewpdf_url' => 'C Viewpdf Url',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('debt_id',$this->debt_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('new_tender_id',$this->new_tender_id);
		$criteria->compare('debt_src',$this->debt_src);
		$criteria->compare('amount',$this->amount,true);
		$criteria->compare('spend_amount',$this->spend_amount,true);
		$criteria->compare('c_download_url',$this->c_download_url,true);
		$criteria->compare('c_viewpdf_url',$this->c_viewpdf_url,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}