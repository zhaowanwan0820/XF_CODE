<?php

/**
 * This is the model class for table "xf_plan_purchase".
 *
 * The followings are the available columns in table 'xf_plan_purchase':
 * @property integer $id
 * @property integer $area_id
 * @property string $discount
 * @property string $total_amount
 * @property string $purchased_amount
 * @property integer $traded_num
 * @property integer $trading_num
 * @property integer $user_id
 * @property integer $status
 * @property integer $starttime
 * @property integer $endtime
 * @property integer $add_user_id
 * @property string $add_ip
 * @property integer $add_time
 */
class PHXfPlanPurchase extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfPlanPurchase the static model class
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
        return Yii::app()->phdb;
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'xf_plan_purchase';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('starttime, endtime', 'required'),
            array('area_id, traded_num, trading_num, user_id, status, starttime, endtime, add_user_id, add_time', 'numerical', 'integerOnly'=>true),
            array('discount, total_amount,purchased_amount', 'length', 'max'=>11),
            array('add_ip', 'length', 'max'=>255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, area_id, discount, total_amount, purchased_amount, traded_num, trading_num, user_id, status, starttime, endtime, add_user_id, add_ip, add_time', 'safe', 'on'=>'search'),
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
            'area_id' => 'Area',
            'discount' => 'Discount',
            'total_amount' => 'Total Amount',
            'purchased_amount' => 'Purchased Amount',
            'traded_num' => 'Traded Num',
            'trading_num' => 'Trading Num',
            'user_id' => 'User',
            'status' => 'Status',
            'starttime' => 'Starttime',
            'endtime' => 'Endtime',
            'add_user_id' => 'Add User',
            'add_ip' => 'Add Ip',
            'add_time' => 'Add Time',
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

        $criteria->compare('id', $this->id);
        $criteria->compare('area_id', $this->area_id);
        $criteria->compare('discount', $this->discount, true);
        $criteria->compare('total_amount', $this->total_amount, true);
        $criteria->compare('purchased_amount', $this->purchased_amount, true);
        $criteria->compare('traded_num', $this->traded_num);
        $criteria->compare('trading_num', $this->trading_num);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('status', $this->status);
        $criteria->compare('starttime', $this->starttime);
        $criteria->compare('endtime', $this->endtime);
        $criteria->compare('add_user_id', $this->add_user_id);
        $criteria->compare('add_ip', $this->add_ip, true);
        $criteria->compare('add_time', $this->add_time);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
}
