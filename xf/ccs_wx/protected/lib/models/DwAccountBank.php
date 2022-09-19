<?php

/**
 * This is the model class for table "dw_account_bank".
 *
 * The followings are the available columns in table 'dw_account_bank':
 * @property string $id
 * @property integer $user_id
 * @property string $account
 * @property string $bank
 * @property string $branch
 * @property string $remark
 * @property integer $province
 * @property integer $city
 * @property integer $area
 * @property string $addtime
 * @property string $addip
 */
class DwAccountBank extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwAccountBank the static model class
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
		return 'dw_account_bank';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, province, city, area', 'numerical', 'integerOnly'=>true),
			array('account, branch, remark', 'length', 'max'=>100),
			array('bank', 'length', 'max'=>50),
			array('addtime', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, account, bank, branch, remark, province, city, area, addtime, addip', 'safe', 'on'=>'search'),
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
			'account' => 'Account',
			'bank' => 'Bank',
			'branch' => 'Branch',
			'remark' => 'Remark',
			'province' => 'Province',
			'city' => 'City',
			'area' => 'Area',
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
		$criteria->compare('account',$this->account,true);
		$criteria->compare('bank',$this->bank,true);
		$criteria->compare('branch',$this->branch,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('province',$this->province);
		$criteria->compare('city',$this->city);
		$criteria->compare('area',$this->area);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}