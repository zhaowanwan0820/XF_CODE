<?php
/**
 * $RCSfile$ FLogWidget.php
 * @touch date Wed 23 Nov 2011 08:26:53 PM CST
 * @author chris(<kuangjun@jike.com>)
 * @license http://www.zend.com/license/3_0.txt   PHP License 3.0
 * @version $Id: FLogWidget.php 3007 2011-12-20 14:22:09Z kuangjun@jike.com $ 
*/

class ItzLogWidget extends CWidget{

    /**
     *@var string object type , 一般来说是表名
     *
     */
    public $objectType;

    /**
     *@var string object id, 一般来说， 是指主键
     */
    public $objectId;

    /**
     *@var string title, Log Widget title
     */
    public $title = '修改日志';

    /**
     * @var int pageSize, default 10
     */
    public $pageSize = 10;

    /**
     * @var dataprovider
     */
    public $dataProvider;

    /**
     * Initialize Log Widget
     */
    public function init(){
        $model = new Log('search');
        $model->unsetAttributes();
        $model->object_type = $this->objectType;
        $model->object_id = $this->objectId;

        $this->dataProvider = $model->search();
    }

    public function run(){
        echo CHtml::openTag('h3');
        echo $this->title;
        echo CHtml::closeTag('h3');
        $this->widget('zii.widgets.grid.CGridView',array(
            'id'    =>  'log-grid',
            'dataProvider'  =>  $this->dataProvider,
            'template'  =>  '{items}{pager}{summary}',
            'columns'   =>  array(
                array(
                    'name'=>'time',
                    'htmlOptions'   =>array(
                        'style'=>'width: 120px',
                    ),
                ),
                array(
                    'name'  =>  'user_id',
                    'value' =>  '$data->user',
                    'htmlOptions'   =>  array(
                        'style' =>  'width:90px',
                        ),
                    ),
                array(
                    'name'  =>  'operation',
                    'htmlOptions'   =>  array(
                        'style' =>  'width:50px',
                        ),
                    ),
                array(
                    'name'  =>  'content',
                    'value' =>  'nl2br($data->content)',
                    'type'  =>  'html',
                ),

            ),
        ));
    }
}
