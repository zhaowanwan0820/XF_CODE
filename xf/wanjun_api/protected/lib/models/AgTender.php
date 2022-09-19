<?php

/**
 * This is the model class for table "ag_tender".
 *
 * The followings are the available columns in table 'ag_tender':
 * @property string $id
 * @property integer $user_id
 * @property integer $platform_id
 * @property integer $project_id
 * @property string $bond_no
 * @property string $money
 * @property string $wait_capital
 * @property integer $status
 * @property integer $debt_status
 * @property integer $debt_src
 * @property integer $addtime
 * @property string $addip
 */
class AgTender extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgTender the static model class
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
		return 'ag_tender';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, platform_id, project_id, status, debt_status, debt_src, addtime', 'numerical', 'integerOnly'=>true),
			array('bond_no', 'length', 'max'=>127),
			array('money', 'length', 'max'=>10),
			array('wait_capital', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>31),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, purchase_order_id,serial_number,user_id, platform_id, project_id, bond_no, money, wait_capital, status, debt_status, debt_src, addtime, addip', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'platform_id' => 'Platform',
			'project_id' => 'Project',
			'bond_no' => 'Bond No',
			'money' => 'Money',
			'wait_capital' => 'Wait Capital',
			'status' => 'Status',
			'debt_status' => 'Debt Status',
			'debt_src' => 'Debt Src',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('project_id',$this->project_id);
		$criteria->compare('bond_no',$this->bond_no,true);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('wait_capital',$this->wait_capital,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('debt_status',$this->debt_status);
		$criteria->compare('debt_src',$this->debt_src);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}