<?php
/**
 * LinkService class file.
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\service\link;

use core\dao\link\LinkModel;
use core\data\DealData;
use core\service\BaseService;

/**
 * link service
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class LinkService extends BaseService {
	public function getLinks($num=false) {
        $deal_data = new DealData();
        $links = $deal_data->getLinks();
        if(!empty($links)) return $links; 

        $links = \SiteApp::init()->dataCache->call(LinkModel::instance(), 'getLinks', array($num), 600);
        $deal_data->setLinks($links);
        return $links;
	}

} // END class link
