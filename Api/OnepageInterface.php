<?php
namespace Qisst\Magento24\Api;
interface OnepageInterface {

    /**
     * GET for Post api
     * @param string $orderfname
     * @param string $orderlname
     * @param string $orderemail
     * @param string $orderphone
     * @param string $orderaddress1
     * @param string $orderaddress2
     * @param string $ordersity
     * @param string $orderstate
     * @param string $orderpostcode
     * @param string $ordercountry
     * @param string $orderquantiry
     * @param string $orderprice
     * @param string $ordershipping
     * @param string $ordertax
     * @param string $ordernote
     * @return string
     */
    public function placeOrder($orderfname, $orderlname, $orderemail, $orderphone, $orderaddress1, $orderaddress2, $ordercity, $orderstate, $orderpostcode, $ordercountry, $orderquantiry, $orderprice, $ordershipping, $ordertax, $ordernote);
}
