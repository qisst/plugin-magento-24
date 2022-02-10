<?php /**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Qisst\Magento24\Controller\Page;

use Magento\Payment\Gateway\ConfigInterface;

class createiframe extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $_scopeConfig;

    protected $_encryptor;
    private $checkoutSession;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     */

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Magento\Framework\Encryption\EncryptorInterface $encryptor,
    \Magento\Checkout\Model\Session $checkoutSession,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
    {
       $this->resultJsonFactory = $resultJsonFactory;
       $this->_scopeConfig = $scopeConfig;
       $this->_encryptor = $encryptor;
       $this->checkoutSession = $checkoutSession;
       parent::__construct($context);
    }
    /**
     * Phone page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $key = $this->_scopeConfig->getValue(
            'qp/config/merchant_api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );


        $key = $this->_encryptor->decrypt($key);

        $is_live = $this->_scopeConfig->getValue(
          'qp/config/qp_is_live',
          \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $url = $is_live == 1? 'https://qisstpay.com/api/send-data':'https://sandbox.qisstpay.com/api/send-data';
        $curl = curl_init();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderId = $this->checkoutSession->getData('last_order_id');
        $orderId = $orderId +1;
	      $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $shippingAddress = $cart->getQuote()->getShippingAddress();
        $cartId = $cart->getQuote()->getId();
        $cart_data = $shippingAddress->getData();
        $objctManager = \Magento\Framework\App\ObjectManager::getInstance();
        $remote = $objctManager->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "partner_id":"magento",
            "fname":"'.$cart_data['firstname'].'",
            "mname":"",
            "lname":"'.$cart_data['lastname'].'",
            "email":"",
            "ip_addr":"'.$remote->getRemoteAddress().'",
            "shipping_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "billing_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "shipping_details":{},
            "card_details":{},
            "itemFlag":false,
            "line_items":[],
            "merchant_order_id": "'.$orderId.'",
            "total_amount":'.$cart_data['grand_total'].'
          }',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$key,
            'Content-Type: application/json',
            'Accept: */*'
              ),
        ));

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL,$url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');
          curl_setopt($ch, CURLOPT_ENCODING,'');
          curl_setopt($ch, CURLOPT_MAXREDIRS,10);
          curl_setopt($ch, CURLOPT_TIMEOUT,0);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
          curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic '.$key,
            'Content-Type: application/json',
            'Accept: */*'
          ));
          $jayParsedAry = '{
            "partner_id":"magento",
            "fname":"'.$cart_data['firstname'].'",
            "mname":"",
            "lname":"'.$cart_data['lastname'].'",
            "email":"",
            "ip_addr":"'.$remote->getRemoteAddress().'",
            "shipping_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "billing_info":{
              "addr1":"'.$cart_data['street'].'",
              "addr2":"",
              "state":"'.$cart_data['region'].'",
              "city":"'.$cart_data['city'].'",
              "zip":"'.$cart_data['postcode'].'"
            },
            "shipping_details":{},
            "card_details":{},
            "itemFlag":false,
            "line_items":[],
            "merchant_order_id": "'.$orderId.'",
            "total_amount":'.$cart_data['grand_total'].'
          }';
          curl_setopt($ch, CURLOPT_POSTFIELDS, $jayParsedAry);
          $server_output = curl_exec($ch);
          curl_close ($ch);
          $response = curl_exec($curl);
          $result = curl_close($curl);
          $result = $this->resultJsonFactory->create();
          // $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/qistpay.log');
          // $logger = new \Zend\Log\Logger();
          // $logger->addWriter($writer);
          // $logger->info('Your text message');
          // $logger->info($server_output);
          // $logger->info($response);

        return $result->setData(json_decode($response));
    }
}
