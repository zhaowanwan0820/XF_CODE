<?php
/**
 * @file TestArrayUtil.php
 *  
 **/


require_once "../InterestPayUtil.php";
class TestInterestPayUtil extends PHPUnit_Framework_TestCase {
        
    public function testInterestPay()
    {
        $data = array(
            "repayment_time" => strtotime("20150529"),
            "borrow_time"    => strtotime("today +2days"),
            "year_apr"       => 13,
            "account"        => "120000",
            "borrow_style"   => 5,
            "repay_months"   => 12,
        );
        var_dump(InterestPayUtil::EqualInterest($data));
    }

}
?>
