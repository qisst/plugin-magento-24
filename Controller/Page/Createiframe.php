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
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Magento\Framework\Encryption\EncryptorInterface $encryptor,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
    {
       $this->resultJsonFactory = $resultJsonFactory;
       $this->_scopeConfig = $scopeConfig;
       $this->_encryptor = $encryptor;
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

        $url = $this->_scopeConfig->getValue(
          'qp/config/qp_url',
          \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $curl = curl_init();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $shippingAddress = $cart->getQuote()->getShippingAddress();
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
          CURLOPT_POSTFIELDS =>'{"partner_id":"foree","fname":"'.$cart_data['firstname'].'","mname":"",
          "lname":"'.$cart_data['lastname'].'","email":"","phone_no":"'.$cart_data['telephone'].'","ip_addr":"'.$remote->getRemoteAddress().
          '","shipping_info":{"addr1":"'.$cart_data['street'].'","addr2":"","state":"'.$cart_data['region'].'","city":"'.$cart_data['city'].'","zip":"'.$cart_data['postcode'].'"},"billing_info":{"addr1":"'.$cart_data['street'].'","addr2":"","state":"'.$cart_data['region'].'","city":"'.$cart_data['city'].'","zip":"'.$cart_data['postcode'].'"},"total_amount":'.$cart_data['grand_total'].',"shipping_details":{},"card_details":{},"itemFlag":true,"line_items":[{"sku":"r","name":"gift3","type":"88","quantity":1,"category":"4","subcategory":"p","description":"This is a test description of a test product.","color":"blue","size":"M","brand":"gucci","unit_price":3,"amount":222,"shipping_attributes":{"weight":"5","dimensions":{"height":"5","width":"5","length":"5"}}}]}',
          CURLOPT_HTTPHEADER => array(
            'sec-ch-ua: "Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'Authorization: Basic '.$key,
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36',
            'sec-ch-ua-platform: "Windows"',
            'Cookie: XSRF-TOKEN=eyJpdiI6Im1XQkpKd3lYbTlObDFBRjk1NFo0S1E9PSIsInZhbHVlIjoiTWJKNEJoMlZTSUFsOStCSEp6WWpJdWJnZzh6VkViL0FJbjJvRW5JVm96MlN5a0lRQkJ6U2hScmZES3RoSHdOeEJxTjVZTWxPWWpCNHQydVplemwzOFBTcjIyVVp5Y2VNY2JPMEp3ZFF1M0YzWXNSeDYyekxCN3ppNGNHdTlMTlEiLCJtYWMiOiI2OGZhMzM0NDYzOWZkZjFhYTdiYjU3Yzg0ZTAzYjcxYjMxNDdhYWYxNDYxY2FiN2U1YmM5ZGI0ZTgyMzhhOWM4IiwidGFnIjoiIn0%3D; qisstpay_sandbox_session=eyJpdiI6IklpVnRIVkhxYW1YWDM3cUZGNHB1V0E9PSIsInZhbHVlIjoialdYNUxjUllzR3JhMDJuUVZ5SHRzdUt1N21VYVpYcE9neGZRN2pQYU4yS0lEWGtNaFFoSjFIWlI4aERNa2hEYVJiOFlSUVZicW84blBVQlBWaXlHWElEQitzTlNFcGExWjZld3FuOGRSSFQyMmo2R2Vmb2VFUHhNOGhSTmRaREYiLCJtYWMiOiJiZDdjNTYyOWVlN2I5YjAyMTZjNTllY2NlOGJiYTA1YjgyOTRmYTA4NTllMTE0OWYxZDQ2NmY3NmE5NmMwMWE0IiwidGFnIjoiIn0%3D'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);

        $result = $this->resultJsonFactory->create();

        return $result->setData(json_decode($response, 1));
    } 
}