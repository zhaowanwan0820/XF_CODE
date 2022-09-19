<?php

/**
 * This is the model class for table "xf_debt_exchange_deal_allow_list".
 *
 * The followings are the available columns in table 'xf_debt_exchange_deal_allow_list':
 * @property string $id
 * @property string $deal_id
 * @property integer $type
 * @property integer $appid
 * @property integer $status
 * @property string $remark
 * @property string $created_at
 * @property string $update_at
 * @property string $upload_id
 * @property string $area_id
 */
class XfDebtExchangeDealAllowList extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return XfDebtExchangeDealAllowList the static model class
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
        return 'xf_debt_exchange_deal_allow_list';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('type, appid, status', 'numerical', 'integerOnly'=>true),
            array('deal_id, created_at, update_at, upload_id', 'length', 'max'=>10),
            array('remark', 'length', 'max'=>500),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id,area_id, deal_id, type, appid, status, remark, created_at, update_at, upload_id', 'safe', 'on'=>'search'),
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
            'deal_id' => 'Deal',
            'type' => 'Type',
            'appid' => 'Appid',
            'status' => 'Status',
            'remark' => 'Remark',
            'created_at' => 'Created At',
            'update_at' => 'Update At',
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
        $criteria->compare('deal_id', $this->deal_id, true);
        $criteria->compare('type', $this->type);
        $criteria->compare('appid', $this->appid);
        $criteria->compare('status', $this->status);
        $criteria->compare('remark', $this->remark, true);
        $criteria->compare('created_at', $this->created_at, true);
        $criteria->compare('update_at', $this->update_at, true);
        $criteria->compare('upload_id', $this->upload_id, true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    public $status_cn = [
        0=>'待审核',
        1=>'已生效',
        2=>'已解除',
        3=>'已撤销',
        4=>'失效(原因:重复录入)',
    ];
    public $type_cn = [
        0=>'',
        1=>'尊享',
        2=>'普惠',
        3=>'工厂微金',
        4=>'智多新',
        5=>'交易所',
    ];

    public function getList($params)
    {
        $where = ' 1 ';
        if (!empty($params['upload_id'])) {
            $where .= " and l.upload_id =".$params['upload_id'];
        }
        if (!empty($params['appid'])) {
            $where .= " and l.appid =".$params['appid'];
        }
        if (!empty($params['area_id'])) {
            $where .= " and l.area_id =".$params['area_id'];
        }
        if (isset($params['status'])) {
            $where .= " and l.status =".$params['status'];
        }
        if (!empty($params['deal_id'])) {
            $where .= " and l.deal_id =".$params['deal_id'];
        }
        if (isset($params['type']) && in_array($params['type'], [1,2,3,4,5])) {
            $where .= " and  l.type=".$params['type'];
        }
        if ($params['upload_id']) {
            $params['type'] = XfShopAllowListImportFile::model()->findByPk($params['upload_id'])->deal_type ;
        }
        //尊享
        if ($params['type']==1) {
            $deal_table = "firstp2p.firstp2p_deal";
            $db = 'fdb';
        } elseif ($params['type']==2) {//普惠
            $db = 'phdb';
            $deal_table = "ncfph.firstp2p_deal";
        } elseif (in_array($params['type'], [3,4,5])) {
            $db = 'offlinedb';
            $deal_table = "offline.offline_deal";
            //$where .= " and d.platform_id = {$params['type']}";
        }
        
        if (!empty($params['name'])) {
            $sql = "select id from  $deal_table where name = '".trim($params['name'])."'";
            $info = Yii::app()->$db->createCommand($sql)->queryRow();
            if (!$info) {
                return ['countNum' => 0, 'list' => []];
            }
            $where .= " and  l.deal_id=".$info['id'];
        }
        $_file=[];
        $countSql = "select count(1) from firstp2p.".$this->tableName()." as l  where ".$where;
       
        $countFile = self::model()->countBySql($countSql);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select l.* from firstp2p.".$this->tableName()." as l  where {$where} order by l.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->$db->createCommand($sql)->queryAll();
            $deal_id_attr = ArrayUntil::array_column($_file, 'deal_id');
            
            $deal_info= Yii::app()->$db->createCommand("select id,name from $deal_table where id in (".implode(',', $deal_id_attr).")")->queryAll();
            if ($deal_info) {
                foreach ($deal_info as $value) {
                    $deal_infos[$value['id']] = $value['name'];
                }
            }
            foreach ($_file as &$item) {
                $item['created_at']=date('Y-m-d H:i:s', $item['created_at']);
                $item['update_at']=$item['update_at']?date('Y-m-d H:i:s', $item['update_at']):'';
                $item['status_cn'] = $this->status_cn[$item['status']];
                $item['type_cn'] = $this->type_cn[$item['type']];
                $item['name'] = $deal_infos[$item['deal_id']];
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }
}
