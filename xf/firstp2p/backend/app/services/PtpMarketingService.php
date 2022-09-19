<?php
namespace NCFGroup\Ptp\services;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Protos\Ptp\RequestDiscountHexIds;

class PtpMarketingService extends ServiceBase
{
    public function deleteCacheWxDiscountTemplateBySiteId(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        $siteId = intval($params['siteId']);
        if ($siteId < 1) {
            return false;
        }

        return (new \core\service\DiscountService())->deleteCacheTemplateInfoBySiteId($siteId);
    }

    public function getDiscountHexIds(RequestDiscountHexIds $request)
    {
        $discountIds = $request->getDiscountIds();
        $data = array();

        $response = new ResponseBase();
        $shareHost = app_conf('API_BONUS_SHARE_HOST');
        $discountService = new \core\service\DiscountService();
        foreach ($discountIds as $discountId) {
            $data[$discountId] = sprintf('%s/discount/GetDiscount?sn=%s', $shareHost, $discountService->generateSN($discountId));
        }

        $response->data = $data;

        return $response;
    }

    public function convertSnToIds(RequestDiscountHexIds $request)
    {
        $discountIds = $request->getDiscountIds();
        $data = array();

        $response = new ResponseBase();
        $discountService = new \core\service\DiscountService();
        foreach ($discountIds as $sn) {
            $data[$sn] = $discountService->convertSnToId($sn);
        }

        $response->data = $data;

        return $response;
    }
}
