<?php

/**
 * This is the model class for table "ag_wx_debt_black_list".
 *
 * The followings are the available columns in table 'ag_wx_debt_black_list':
 * @property string $id
 * @property integer $type
 * @property string $deal_id
 * @property string $deal_name
 * @property string $op_user_id
 * @property string $addtime
 * @property string $addip
 * @property integer $status
 * @property string $updatetime
 */
class AgWxDebtBlackList extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxDebtBlackList the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getDbConnection()
	{
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_wx_debt_black_list';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, status', 'numerical', 'integerOnly'=>true),
			array('deal_id, op_user_id, addtime, updatetime', 'length', 'max'=>10),
			array('deal_name, addip', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, deal_id, deal_name, op_user_id, addtime, addip, status, updatetime', 'safe', 'on'=>'search'),
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
			'deal_id' => 'Deal',
			'deal_name' => 'Deal Name',
			'op_user_id' => 'Op User',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'status' => 'Status',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('deal_id',$this->deal_id,true);
		$criteria->compare('deal_name',$this->deal_name,true);
		$criteria->compare('op_user_id',$this->op_user_id,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('updatetime',$this->updatetime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}