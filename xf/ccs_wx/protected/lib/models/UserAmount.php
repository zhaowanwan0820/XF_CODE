<?php

/**
 * This is the model class for table "dw_user_amount".
 *
 * The followings are the available columns in table 'dw_user_amount':
 * @property string $id
 * @property integer $user_id
 * @property string $credit
 * @property string $credit_use
 * @property string $credit_nouse
 * @property string $borrow_vouch
 * @property string $borrow_vouch_use
 * @property string $borrow_vouch_nouse
 * @property string $tender_vouch
 * @property string $tender_vouch_use
 * @property string $tender_vouch_nouse
 */
class UserAmount extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserAmount the static model class
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
		return 'dw_user_amount';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id', 'required'),
			array('user_id', 'numerical', 'integerOnly'=>true),
			array('credit, credit_use, credit_nouse, borrow_vouch, borrow_vouch_use, borrow_vouch_nouse, tender_vouch, tender_vouch_use, tender_vouch_nouse', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, credit, credit_use, credit_nouse, borrow_vouch, borrow_vouch_use, borrow_vouch_nouse, tender_vouch, tender_vouch_use, tender_vouch_nouse', 'safe', 'on'=>'search'),
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
			'credit' => 'Credit',
			'credit_use' => 'Credit Use',
			'credit_nouse' => 'Credit Nouse',
			'borrow_vouch' => 'Borrow Vouch',
			'borrow_vouch_use' => 'Borrow Vouch Use',
			'borrow_vouch_nouse' => 'Borrow Vouch Nouse',
			'tender_vouch' => 'Tender Vouch',
			'tender_vouch_use' => 'Tender Vouch Use',
			'tender_vouch_nouse' => 'Tender Vouch Nouse',
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
		$criteria->compare('credit',$this->credit,true);
		$criteria->compare('credit_use',$this->credit_use,true);
		$criteria->compare('credit_nouse',$this->credit_nouse,true);
		$criteria->compare('borrow_vouch',$this->borrow_vouch,true);
		$criteria->compare('borrow_vouch_use',$this->borrow_vouch_use,true);
		$criteria->compare('borrow_vouch_nouse',$this->borrow_vouch_nouse,true);
		$criteria->compare('tender_vouch',$this->tender_vouch,true);
		$criteria->compare('tender_vouch_use',$this->tender_vouch_use,true);
		$criteria->compare('tender_vouch_nouse',$this->tender_vouch_nouse,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}