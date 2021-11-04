<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Qisst\Magento24\Model\Payment;

class QpPayment extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "qppayment";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}

