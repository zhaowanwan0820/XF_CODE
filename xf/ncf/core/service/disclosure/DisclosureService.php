<?php

namespace core\service\disclosure;

use core\service\BaseService;
use core\dao\disclosure\DisclosureModel;

class DisclosureService extends BaseService {

    public function getShowData(){
        return DisclosureModel::instance()->getShowData();
    }

    public function saveData($data){
        return DisclosureModel::instance()->saveData($data);
    }

    public function saveImage($data){
        return DisclosureModel::instance()->saveImage($data);
    }

}

