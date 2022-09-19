<?php

/**
 * This is the model class for table "firstp2p_auth_debt_exchange_log".
 *
 * The followings are the available columns in table 'firstp2p_auth_debt_exchange_log':
 * @property string $id
 * @property integer $auth_id
 * @property integer $user_id
 * @property string $card1
 * @property string $card2
 * @property string $card3
 * @property integer $auth_status
 * @property integer $verify_score
 * @property string $card_id
 * @property string $real_name
 * @property string $order_id
 * @property string $error_msg
 * @property string $result_status
 * @property string $auth_info
 * @property integer $auth_time
 * @property integer $addtime
 */
class AuthDebtExchangeLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AuthDebtExchangeLog the static model class
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
		return 'firstp2p_auth_debt_exchange_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, auth_status, auth_time, addtime', 'numerical', 'integerOnly'=>true),
			array('card1, card2, card3, auth_info', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, card1, card2, card3, auth_status, verify_score,card_id,real_name,order_id,error_msg,result_status, auth_info, auth_time, addtime', 'safe', 'on'=>'search'),
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
			'card1' => 'Card1',
			'card2' => 'Card2',
			'card3' => 'Card3',
			'auth_status' => 'Auth Status',
			'verify_score' => 'Verify Score',
			'auth_info' => 'Auth Info',
			'auth_time' => 'Auth Time',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('card1',$this->card1,true);
		$criteria->compare('card2',$this->card2,true);
		$criteria->compare('card3',$this->card3,true);
		$criteria->compare('auth_status',$this->auth_status);
		$criteria->compare('verify_score',$this->verify_score);
		$criteria->compare('card_id',$this->card_id);
		$criteria->compare('real_name',$this->real_name);
		$criteria->compare('order_id',$this->order_id);
		$criteria->compare('error_msg',$this->error_msg);
		$criteria->compare('result_status',$this->result_status);
		$criteria->compare('auth_info',$this->auth_info,true);
		$criteria->compare('auth_time',$this->auth_time);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}