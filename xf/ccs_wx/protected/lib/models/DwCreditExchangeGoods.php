<?php

/**
 * This is the model class for table "dw_credit_exchange_goods".
 *
 * The followings are the available columns in table 'dw_credit_exchange_goods':
 * @property string $id
 * @property string $gtype
 * @property integer $gstatus
 * @property string $displayorder
 * @property string $gtitle
 * @property string $gname
 * @property string $parvalue
 * @property integer $total
 * @property integer $exchanged_num
 * @property string $need_credit
 * @property string $pic_url
 * @property string $expiration
 * @property string $remark
 * @property string $params
 * @property string $addtime
 * @property string $modtime
 * @property integer $daily_num
 */
class DwCreditExchangeGoods extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DwCreditExchange the static model class
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
		return 'dw_credit_exchange_goods';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('gname, pic_url, remark, params, modtime', 'required'),
			array('gstatus, total, exchanged_num, daily_num', 'numerical', 'integerOnly'=>true),
			array('gtype', 'length', 'max'=>15),
			array('displayorder, parvalue', 'length', 'max'=>11),
			array('gtitle, gname', 'length', 'max'=>150),
			array('need_credit, expiration, addtime, modtime', 'length', 'max'=>10),
			array('pic_url', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, gtype, gstatus, displayorder, gtitle, gname, parvalue, total, exchanged_num, need_credit, pic_url, expiration, remark, params, addtime, modtime, daily_num', 'safe', 'on'=>'search'),
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
			'gtype' => 'Gtype',
			'gstatus' => 'Gstatus',
			'displayorder' => 'Displayorder',
			'gtitle' => 'Gtitle',
			'gname' => 'Gname',
			'parvalue' => 'Parvalue',
			'total' => 'Total',
			'exchanged_num' => 'Exchanged Num',
			'need_credit' => 'Need Credit',
			'pic_url' => 'Pic Url',
			'expiration' => 'Expiration',
			'remark' => 'Remark',
			'params' => 'Params',
			'addtime' => 'Addtime',
			'modtime' => 'Modtime',
			'daily_num' => 'Daily Num',
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
		$criteria->compare('gtype',$this->gtype,true);
		$criteria->compare('gstatus',$this->gstatus);
		$criteria->compare('displayorder',$this->displayorder,true);
		$criteria->compare('gtitle',$this->gtitle,true);
		$criteria->compare('gname',$this->gname,true);
		$criteria->compare('parvalue',$this->parvalue,true);
		$criteria->compare('total',$this->total);
		$criteria->compare('exchanged_num',$this->exchanged_num);
		$criteria->compare('need_credit',$this->need_credit,true);
		$criteria->compare('pic_url',$this->pic_url,true);
		$criteria->compare('expiration',$this->expiration,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('params',$this->params,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('modtime',$this->modtime,true);
		$criteria->compare('daily_num',$this->daily_num);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}