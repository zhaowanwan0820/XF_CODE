<?php

/**
 * This is the model class for table "dw_luckydraw_prizes".
 *
 * The followings are the available columns in table 'dw_luckydraw_prizes':
 * @property string $id
 * @property string $event_id
 * @property integer $status
 * @property string $type
 * @property integer $level
 * @property integer $order
 * @property string $name
 * @property string $pic_url
 * @property double $chance
 * @property string $money
 * @property string $description
 * @property integer $total
 * @property integer $winning_number
 * @property integer $out_number
 * @property integer $addtime
 * @property string $addip
 */
class LuckydrawPrizes extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return LuckydrawPrizes the static model class
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
		return 'dw_luckydraw_prizes';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, name, pic_url, addtime, addip', 'required'),
			array('status, level, order, total, winning_number, out_number, addtime', 'numerical', 'integerOnly'=>true),
			array('chance', 'numerical'),
			array('event_id, money', 'length', 'max'=>11),
			array('type', 'length', 'max'=>20),
			array('name', 'length', 'max'=>256),
			array('pic_url', 'length', 'max'=>500),
			array('description', 'length', 'max'=>1000),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, event_id, status, type, level, order, name, pic_url, chance, money, description, total, winning_number, out_number, addtime, addip', 'safe', 'on'=>'search'),
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
			'event_id' => 'Event',
			'status' => 'Status',
			'type' => 'Type',
			'level' => 'Level',
			'order' => 'Order',
			'name' => 'Name',
			'pic_url' => 'Pic Url',
			'chance' => 'Chance',
			'money' => 'Money',
			'description' => 'Description',
			'total' => 'Total',
			'winning_number' => 'Winning Number',
			'out_number' => 'Out Number',
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
		$criteria->compare('event_id',$this->event_id,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('level',$this->level);
		$criteria->compare('order',$this->order);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('pic_url',$this->pic_url,true);
		$criteria->compare('chance',$this->chance);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('total',$this->total);
		$criteria->compare('winning_number',$this->winning_number);
		$criteria->compare('out_number',$this->out_number);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}