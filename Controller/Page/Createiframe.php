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
        //$orderId = $this->checkoutSession->getData('last_order_id');

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();
        //$themeTable = 'sales_order';
        $tableName   = $connection->getTableName('sales_order');
        //$sql = "SELECT `entity_id` FROM `sales_order` where entity_id = (SELECT MAX(`entity_id`) FROM `sales_order`)";
        $sql = "SELECT `entity_id` FROM `". $tableName ."` where entity_id = (SELECT MAX(`entity_id`) FROM `". $tableName ."`)";
        $orderIds = $connection->fetchAll($sql);
        $orderst = $orderIds[0]['entity_id'];
        $orderno = (int)$orderst;
        $orderno = $orderno +1;
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
            "merchant_order_id": "'.$orderno.'",
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

          // $post = [
          //   "partner_id":"magento",
          //   "fname":"'.$cart_data['firstname'].'",
          //   "mname":"",
          //   "lname":"'.$cart_data['lastname'].'",
          //   "email":"",
          //   "ip_addr":"'.$remote->getRemoteAddress().'",
          //   "shipping_info":{
          //     "addr1":"'.$cart_data['street'].'",
          //     "addr2":"",
          //     "state":"'.$cart_data['region'].'",
          //     "city":"'.$cart_data['city'].'",
          //     "zip":"'.$cart_data['postcode'].'"
          //   },
          //   "billing_info":{
          //     "addr1":"'.$cart_data['street'].'",
          //     "addr2":"",
          //     "state":"'.$cart_data['region'].'",
          //     "city":"'.$cart_data['city'].'",
          //     "zip":"'.$cart_data['postcode'].'"
          //   },
          //   "shipping_details":{},
          //   "card_details":{},
          //   "itemFlag":false,
          //   "line_items":[],
          //   "merchant_order_id": "'.$orderId.'",
          //   "total_amount":'.$cart_data['grand_total'].'
          // ];
          //$authorization = "Authorization: Bearer WjcbAvjYPEOjqNiVb6sF4aRPYhttzuuVu5cIUgPkSHl5MqKUS446f61h9ml8";
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
            "merchant_order_id": "'.$orderno.'",
            "total_amount":'.$cart_data['grand_total'].'
          }';
curl_setopt($ch, CURLOPT_POSTFIELDS, $jayParsedAry);
          // curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query(
          //   array (
          //     'partner_id' => 'magento',
          //     'fname' => 'Zubeer',
          //     'mname' => 'Test2',
          //     'lname' => 'Account',
          //     'total_amount' => 1500,
          //     'email' => 'zaheer.ahmed@qisstpay.com',
          //     'phone_no' => '923011000201',
          //     'shipping_details' =>
          //     array (
          //     ),
          //     'card_details' =>
          //     array (
          //     ),
          //     'shipping_info' =>
          //     array (
          //       'addr1' => 'Address line 1',
          //       'addr2' => '',
          //       'state' => 'Lahore',
          //       'city' => 'Lahore',
          //       'zip' => '',
          //     ),
          //     'billing_info' =>
          //     array (
          //       'addr1' => 'Address line 1',
          //       'addr2' => '',
          //       'state' => 'Karachi',
          //       'city' => 'Karachi',
          //       'zip' => '',
          //     ),
          //     'itemFlag' => true,
          //     'ip_addr' => '192.168.1.1',
          //     'line_items' =>
          //     array (
          //       0 =>
          //       array (
          //         'sku' => 'r',
          //         'name' => 'gift3',
          //         'type' => '88',
          //         'quantity' => 1,
          //         'category' => '4',
          //         'subcategory' => 'p',
          //         'description' => 'This is a test description of a test product.',
          //         'color' => 'blue',
          //         'size' => 'M',
          //         'brand' => 'gucci',
          //         'unit_price' => 3,
          //         'amount' => 5000,
          //         'shipping_attributes' =>
          //         array (
          //           'weight' => '5',
          //           'dimensions' =>
          //           array (
          //             'height' => '5',
          //             'width' => '5',
          //             'length' => '5',
          //           ),
          //         ),
          //       ),
          //     ),
          //     'currency' => 'PKR',
          //     'merchant_order_id' => '123123',
          //     'qisstpay_nid' => 'dgdh',
          //     'call_back_url' => 'https://example.com/webhook/listen',
          //     'redirect_url' => 'https://example.com/payment/success-or-failure',
          //     'tax_amount' => 1200,
          //     'shipping_amount' => 1200,
          //     'user_id' => 12,
          //   )
          //   // 'partner_id'=>'magento',
          //   //     'fname'=> $cart_data['firstname'],
          //   //     'mname'=>'',
          //   //     'lname'=>$cart_data['lastname'],
          //   //     'email'=>'',
          //   //     'ip_addr'=> $remote->getRemoteAddress(),
          //   //     'shipping_info'=>[
          //   //       'addr1'=>$cart_data['street'],
          //   //       'addr2'=>'',
          //   //       'state'=>$cart_data['region'],
          //   //       'city'=> $cart_data['city'],
          //   //       'zip'=> $cart_data['postcode']
          //   //     ],
          //   //     'billing_info'=>[
          //   //       'addr1'=>$cart_data['street'],
          //   //       'addr2'=>'',
          //   //       'state'=>$cart_data['region'],
          //   //       'city'=>$cart_data['city'],
          //   //       'zip'=>$cart_data['postcode']
          //   //     ],
          //   //     'shipping_details'=>[],
          //   //     'card_details'=>[],
          //   //     'itemFlag'=>false,
          //   //     'line_items'=>[],
          //   //     'merchant_order_id'=>$orderId,
          //   //     'total_amount'=>$cart_data['grand_total']
          //   ));
          $server_output = curl_exec($ch);
          curl_close ($ch);


        //   CURLOPT_POSTFIELDS =>[
        //     'partner_id'=>'magento',
        //     'fname'=> $cart_data["firstname"],
        //     'mname'=>'',
        //     'lname'=>$cart_data['lastname'],
        //     'email'=>'',
        //     'ip_addr'=> $remote->getRemoteAddress(),
        //     'shipping_info'=>[
        //       'addr1'=>$cart_data['street'],
        //       'addr2'=>'',
        //       'state'=>$cart_data['region'],
        //       'city'=> $cart_data['city'],
        //       'zip'=> $cart_data['postcode']
        //     ],
        //     'billing_info'=>[
        //       'addr1'=>$cart_data['street'],
        //       'addr2'=>'',
        //       'state'=>$cart_data['region'],
        //       'city'=>$cart_data['city'],
        //       'zip'=>$cart_data['postcode']
        //     ],
        //     'shipping_details'=>[],
        //     'card_details'=>[],
        //     'itemFlag'=>false,
        //     'line_items'=>[],
        //     'merchant_order_id'=>$orderId,
        //     'total_amount'=>$cart_data['grand_total']
        //   ]
        //   ,
        //     CURLOPT_HTTPHEADER => array(
        //       'sec-ch-ua: "Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
        //       'sec-ch-ua-mobile: ?0',
        //       'Authorization: Basic '.$key,
        //       'Content-Type: application/json',
        //       'Accept: */*',
        //       'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36',
        //       'sec-ch-ua-platform: "Windows"',
        //       'Cookie: XSRF-TOKEN=eyJpdiI6Im1XQkpKd3lYbTlObDFBRjk1NFo0S1E9PSIsInZhbHVlIjoiTWJKNEJoMlZTSUFsOStCSEp6WWpJdWJnZzh6VkViL0FJbjJvRW5JVm96MlN5a0lRQkJ6U2hScmZES3RoSHdOeEJxTjVZTWxPWWpCNHQydVplemwzOFBTcjIyVVp5Y2VNY2JPMEp3ZFF1M0YzWXNSeDYyekxCN3ppNGNHdTlMTlEiLCJtYWMiOiI2OGZhMzM0NDYzOWZkZjFhYTdiYjU3Yzg0ZTAzYjcxYjMxNDdhYWYxNDYxY2FiN2U1YmM5ZGI0ZTgyMzhhOWM4IiwidGFnIjoiIn0%3D; qisstpay_sandbox_session=eyJpdiI6IklpVnRIVkhxYW1YWDM3cUZGNHB1V0E9PSIsInZhbHVlIjoialdYNUxjUllzR3JhMDJuUVZ5SHRzdUt1N21VYVpYcE9neGZRN2pQYU4yS0lEWGtNaFFoSjFIWlI4aERNa2hEYVJiOFlSUVZicW84blBVQlBWaXlHWElEQitzTlNFcGExWjZld3FuOGRSSFQyMmo2R2Vmb2VFUHhNOGhSTmRaREYiLCJtYWMiOiJiZDdjNTYyOWVlN2I5YjAyMTZjNTllY2NlOGJiYTA1YjgyOTRmYTA4NTllMTE0OWYxZDQ2NmY3NmE5NmMwMWE0IiwidGFnIjoiIn0%3D'
        //     ),
        // ));



        //
        // $orderData =[
        //             'currency_id'  => 'PKR',
        //             'email'        => $cart_data['email'],
        //             'paymentMethod'=> [
        //               'method'=> 'cashondelivery'
        //             ],
        //             'shipping_address' =>[
        //                 'firstname'    => $fnameorder,
        //                 'lastname'     => $lnameorder,
        //                 'street' => $str1order,
        //                 'city' => $cityorder,
        //                 'country_id' => '',
        //                 'region' => $regionorder,
        //                 'region_id'=> $regionorder,
        //                 'postcode' => $postcodeorder,
        //                 'telephone' => $phoneorder,
        //                 'fax' => $phoneorder,
        //                 'save_in_address_book' => 1
        //             ],
        //             'items'=> [
        //                 //array of product which order you want to create
        //                 ['product_id'=>'1','qty'=>1],
        //                 ['product_id'=>'2','qty'=>1]
        //             ]
        //         ];




        $response = curl_exec($curl);
        //print_r($response);

        $result = curl_close($curl);

        $result = $this->resultJsonFactory->create();
//var_dump($server_output);
$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/qisstpay.log');
  $logger = new \Zend\Log\Logger();
  $logger->addWriter($writer);
  $logger->info('Below are OrderNo');
    $logger->info($orderno);
  $logger->info($server_output);
  $logger->info($orderId);

        return $result->setData(json_decode($response));
    }
}
