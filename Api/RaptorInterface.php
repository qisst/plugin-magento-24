<?php
namespace Qisst\Magento24\Api;
interface RaptorInterface {
    /**
     * GET for Post api
     * @param string $quoteid
     * @return string
     */
    public function returnOrderId($quoteid);

}
