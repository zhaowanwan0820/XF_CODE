<?php

/**
 * This is the model class for table "xf_user_shopping_info".
 *
 * The followings are the available columns in table 'xf_user_shopping_info':
 * @property string $id
 * @property integer $status
 * @property string $user_id
 * @property integer $platform_id
 * @property string $order_no
 * @property string $order_time
 * @property string $order_amount
 * @property string $debt_integral_amount
 * @property string $shop_integral_amount
 * @property string $goods_name
 * @property string $goods_price
 * @property string $goods_use_integral
 * @property string $exchange_no
 * @property string $delivery_name
 * @property string $delivery_no
 * @property string $send_time
 * @property string $add_time
 * @property string $upload_id
 */
class XfUserShoppingInfo extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfUserShoppingInfo the static model class
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
        return Yii::app()->fdb;
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'xf_user_shopping_info';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('status, platform_id', 'numerical', 'integerOnly'=>true),
            array('user_id, order_time, order_amount, debt_integral_amount, shop_integral_amount, goods_price, goods_use_integral, send_time, add_time, upload_id', 'length', 'max'=>10),
            array('order_no, exchange_no, delivery_no', 'length', 'max'=>100),
            array('goods_name', 'length', 'max'=>300),
            array('delivery_name', 'length', 'max'=>50),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, status, user_id, platform_id, order_no, order_time, order_amount, debt_integral_amount, shop_integral_amount, goods_name, goods_price, goods_use_integral, exchange_no, delivery_name, delivery_no, send_time, add_time, upload_id', 'safe', 'on'=>'search'),
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
            'status' => 'Status',
            'user_id' => 'User',
            'platform_id' => 'Platform',
            'order_no' => 'Order No',
            'order_time' => 'Order Time',
            'order_amount' => 'Order Amount',
            'debt_integral_amount' => 'Debt Integral Amount',
            'shop_integral_amount' => 'Shop Integral Amount',
            'goods_name' => 'Goods Name',
            'goods_price' => 'Goods Price',
            'goods_use_integral' => 'Goods Use Integral',
            'exchange_no' => 'Exchange No',
            'delivery_name' => 'Delivery Name',
            'delivery_no' => 'Delivery No',
            'send_time' => 'Send Time',
            'add_time' => 'Add Time',
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

        $criteria->compare('id', $this->id, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('user_id', $this->user_id, true);
        $criteria->compare('platform_id', $this->platform_id);
        $criteria->compare('order_no', $this->order_no, true);
        $criteria->compare('order_time', $this->order_time, true);
        $criteria->compare('order_amount', $this->order_amount, true);
        $criteria->compare('debt_integral_amount', $this->debt_integral_amount, true);
        $criteria->compare('shop_integral_amount', $this->shop_integral_amount, true);
        $criteria->compare('goods_name', $this->goods_name, true);
        $criteria->compare('goods_price', $this->goods_price, true);
        $criteria->compare('goods_use_integral', $this->goods_use_integral, true);
        $criteria->compare('exchange_no', $this->exchange_no, true);
        $criteria->compare('delivery_name', $this->delivery_name, true);
        $criteria->compare('delivery_no', $this->delivery_no, true);
        $criteria->compare('send_time', $this->send_time, true);
        $criteria->compare('add_time', $this->add_time, true);
        $criteria->compare('upload_id', $this->upload_id, true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    public static $status_cn = [
        0=>'待审核',
        1=>'已生效',
        3=>'已撤销',
    ];


    public function getList($params)
    {
        $where[] = "status in (0,1)";
        if (!empty($params['upload_id'])) {
            $where[] = "upload_id =".$params['upload_id'];
        }
       
        if (!empty($params['user_id'])) {
            $where[] = "user_id = '".$params['user_id']."'";
        }

        if (!empty($params['order_no'])) {
            $where[] = "order_no ='".$params['order_no']."'";
        }

        if (!empty($params['order_start'])) {
            $where[] = "order_time >= ".strtotime($params['order_start']);
        }

        if (!empty($params['order_end'])) {
            $where[] = "order_time <=".(strtotime($params['order_end'])+86400);
        }

        if (!empty($params['order_amount'])) {
            $where[] = "order_amount = '".$params['order_amount']."'";
        }

        if (!empty($params['delivery_no'])) {
            $where[] = "delivery_no = '".$params['delivery_no']."'";
        }

        if (!empty($params['goods_name'])) {
            $where[] = "goods_name = '".$params['goods_name']."'";
        }

        if (!empty($params['goods_price'])) {
            $where[] = "goods_price = '".$params['goods_price']."'";
        }

        if (!empty($params['send_start'])) {
            $where[] = "send_time >= ".strtotime($params['send_start']);
        }

        if (!empty($params['send_end'])) {
            $where[] = "send_time <=".(strtotime($params['send_end'])+86400);
        }

        if (!empty($params['exchange_no'])) {
            $where[] = "exchange_no = '".$params['exchange_no']."'";
        }

        $condition = ' where '.implode(' and ', $where);


        $_file=[];
        $countSql = "select count(1) from ".$this->tableName().$condition;
       
        $countFile = self::model()->countBySql($countSql);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select * from ".$this->tableName()."  {$condition} order by id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->fdb->createCommand($sql)->queryAll();
           
            foreach ($_file as &$item) {
                $item['order_time'] = date('Y-m-d H:i:s', $item['order_time']);
                $item['send_time'] = date('Y-m-d H:i:s', $item['send_time']);
                $item['status_cn']  = self::$status_cn[$item['status']];
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }
}
