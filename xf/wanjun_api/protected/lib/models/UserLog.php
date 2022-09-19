<?php

/**
 * This is the model class for table "firstp2p_user_log".
 *
 * The followings are the available columns in table 'firstp2p_user_log':
 * @property integer $id
 * @property string $log_info
 * @property integer $log_time
 * @property integer $log_admin_id
 * @property integer $log_user_id
 * @property double $money
 * @property integer $score
 * @property integer $point
 * @property double $quota
 * @property double $lock_money
 * @property string $remaining_money
 * @property integer $user_id
 * @property string $deal_type
 * @property string $related_user_show_name
 * @property string $note
 * @property string $remaining_total_money
 * @property integer $is_delete
 * @property string $biz_token
 * @property string $deal_id
 * @property string $out_order_id
 */
class UserLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserLog the static model class
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
		return 'firstp2p_user_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('log_time, log_admin_id, log_user_id, money, score, point, quota, remaining_money, user_id, remaining_total_money', 'required'),
			array('log_time, log_admin_id, log_user_id, score, point, user_id, is_delete', 'numerical', 'integerOnly'=>true),
			array('money, quota, lock_money', 'numerical'),
			array('log_info, note', 'length', 'max'=>512),
			array('remaining_money, remaining_total_money, deal_id', 'length', 'max'=>20),
			array('deal_type', 'length', 'max'=>11),
			array('related_user_show_name', 'length', 'max'=>100),
			array('biz_token', 'length', 'max'=>255),
			array('out_order_id', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, log_info, log_time, log_admin_id, log_user_id, money, score, point, quota, lock_money, remaining_money, user_id, deal_type, related_user_show_name, note, remaining_total_money, is_delete, biz_token, deal_id, out_order_id', 'safe', 'on'=>'search'),
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
			'log_info' => 'Log Info',
			'log_time' => 'Log Time',
			'log_admin_id' => 'Log Admin',
			'log_user_id' => 'Log User',
			'money' => 'Money',
			'score' => 'Score',
			'point' => 'Point',
			'quota' => 'Quota',
			'lock_money' => 'Lock Money',
			'remaining_money' => 'Remaining Money',
			'user_id' => 'User',
			'deal_type' => 'Deal Type',
			'related_user_show_name' => 'Related User Show Name',
			'note' => 'Note',
			'remaining_total_money' => 'Remaining Total Money',
			'is_delete' => 'Is Delete',
			'biz_token' => 'Biz Token',
			'deal_id' => 'Deal',
			'out_order_id' => 'Out Order',
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
		$criteria->compare('log_info',$this->log_info,true);
		$criteria->compare('log_time',$this->log_time);
		$criteria->compare('log_admin_id',$this->log_admin_id);
		$criteria->compare('log_user_id',$this->log_user_id);
		$criteria->compare('money',$this->money);
		$criteria->compare('score',$this->score);
		$criteria->compare('point',$this->point);
		$criteria->compare('quota',$this->quota);
		$criteria->compare('lock_money',$this->lock_money);
		$criteria->compare('remaining_money',$this->remaining_money,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('deal_type',$this->deal_type,true);
		$criteria->compare('related_user_show_name',$this->related_user_show_name,true);
		$criteria->compare('note',$this->note,true);
		$criteria->compare('remaining_total_money',$this->remaining_total_money,true);
		$criteria->compare('is_delete',$this->is_delete);
		$criteria->compare('biz_token',$this->biz_token,true);
		$criteria->compare('deal_id',$this->deal_id,true);
		$criteria->compare('out_order_id',$this->out_order_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}