<?php

/**
 * 企业用户下载申请书
 * @author liguizhi<liguizhi@ucfgroup.com>
 */

namespace web\controllers\enterprise;

use core\dao\EnterpriseRegisterModel;
use web\controllers\BaseAction;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class Download extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke() {
        $userId = $GLOBALS['user_info']['id'];

        //TODO 替换企业用户相关信息
        $enterpriseAccountInfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
        $induCate = $enterpriseAccountInfo['base']['indu_cate'];
        $enterpriseAccountInfo['base']['indu_cate_dec'] = UserAccountEnum::$inducateTypes[$induCate];
        $legalbodyCredentialsType = $enterpriseAccountInfo['base']['legalbody_credentials_type'];
        $enterpriseAccountInfo['base']['legalbody_credentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$legalbodyCredentialsType];
        // 开户行名称
        $bankList = $this->rpc->local('BankService\bankList', array());
        $bankId = $enterpriseAccountInfo['bank']['bank_id'];
        $enterpriseAccountInfo['bank']['bank_id_dec'] = '';
        foreach ($bankList as $item) {
            if ($item['id'] == $bankId) {
                $enterpriseAccountInfo['bank']['bank_id_dec'] = $item['name'];
                break;
            }
        }

        // 开户行所在地格式化
        $bankRegion2 = $enterpriseAccountInfo['bank']['region_lv2'];
        $bankRegion3 = $enterpriseAccountInfo['bank']['region_lv3'];
        $bankRegion4 = $enterpriseAccountInfo['bank']['region_lv4'];
        $regions2 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion2));
        $enterpriseAccountInfo['bank']['region_lv2_dec'] = $regions2->name;
        $regions3 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion3));
        $enterpriseAccountInfo['bank']['region_lv3_dec'] = $regions3->name;
        $regions4 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion4));
        $enterpriseAccountInfo['bank']['region_lv4_dec'] = $regions4->name;

        // 企业注册资金格式化
        $enterpriseAccountInfo['base']['reg_amt'] = bcdiv($enterpriseAccountInfo['base']['reg_amt'], 10000, 4);

        // 代理人证件类别
        $majorCondentialsType = $enterpriseAccountInfo['contact']['major_condentials_type'];
        $enterpriseAccountInfo['contact']['major_condentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$majorCondentialsType];

        // 企业信息回显
        $this->tpl->assign('enterpriseInfo', $enterpriseAccountInfo);
        $this->tpl->assign('user_purpose', $GLOBALS['user_info']['user_purpose']);
        $content1 = $this->tpl->fetch('web/views/v3/user/applycompany_table.html');
        $content2 = $this->tpl->fetch('web/views/v3/user/applycompany_table_puhui.html');

        return $this->outputPdf($content1, $content2, '开户申请书');
    }

    /**
     * 将html输出为pdf
     *
     * @param array $content
     * @param string $name
     */
    private function outputPdf($content1, $content2, $name) {
        \FP::import("libs.tcpdf.tcpdf");
        \FP::import("libs.tcpdf.mkpdf");
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $fontDir = dirname(__FILE__) . '/../../../system/tcpdf/simsun.ttf';
        $fontSimSun = $pdf->addTTFfont( $fontDir , 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($fontSimSun, '', 6);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // 网信
        // set bacground image
        $pdf->AddPage();
        $bMargin = $pdf->getBreakMargin();
        $auto_page_break = $pdf->getAutoPageBreak();
        $pdf->SetAutoPageBreak(false, 0);
        $imgFile = APP_ROOT_PATH.'public/static/v3/images/user/applycompany_bg.png';
        $pdf->Image($imgFile, 55, 106, 100, 100, '', '', '', false, 300, '', false, false, 0);
        $img1 = APP_ROOT_PATH.'public/static/v3/images/oauth/checkbox01.png';
        $pdf->Image($img1, 62, 159, 4, 5, '', '', '', false, 300, '', false, false, 0);
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        $pdf->setPageMark();
        $pdf->writeHTML($content1, true, false, true, false, '');

        // 网信普惠
        // set bacground image
        $pdf->AddPage();
        $bMargin = $pdf->getBreakMargin();
        $auto_page_break = $pdf->getAutoPageBreak();
        $pdf->SetAutoPageBreak(false, 0);
        $imgFile = APP_ROOT_PATH.'public/static/v3/images/oauth/bg_puhui.png';
        $pdf->Image($imgFile, 55, 106, 100, 100, '', '', '', false, 300, '', false, false, 0);
        $img1 = APP_ROOT_PATH.'public/static/v3/images/oauth/checkbox01.png';
        $pdf->Image($img1, 62, 159, 4, 5, '', '', '', false, 300, '', false, false, 0);
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        $pdf->setPageMark();
        $pdf->writeHTML($content2, true, false, true, false, '');

        // 输出
        $pdf->Output($name.'.pdf', 'I');
        return;
    }
}

