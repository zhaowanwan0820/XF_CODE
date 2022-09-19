<?php

/**
 * This is the model class for table "xf_debt_exchange_deal_allow_list".
 *
 * The followings are the available columns in table 'xf_debt_exchange_deal_allow_list':
 * @property string $id
 * @property string $deal_id
 * @property integer $type
 * @property integer $appid
 * @property integer $status
 * @property string $remark
 * @property string $created_at
 * @property string $update_at
 * @property string $upload_id
 */
class XfDebtExchangeDealAllowList extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfDebtExchangeDealAllowList the static model class
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
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_debt_exchange_deal_allow_list';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, appid, status', 'numerical', 'integerOnly'=>true),
			array('deal_id, created_at, update_at, upload_id', 'length', 'max'=>10),
			array('remark', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, deal_id, type, appid, status, remark, created_at, update_at, upload_id', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'appid' => 'Appid',
			'status' => 'Status',
			'remark' => 'Remark',
			'created_at' => 'Created At',
			'update_at' => 'Update At',
			'upload_id' => 'Upload',
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
		$criteria->compare('deal_id',$this->deal_id,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('appid',$this->appid);
		$criteria->compare('status',$this->status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('update_at',$this->update_at,true);
		$criteria->compare('upload_id',$this->upload_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}