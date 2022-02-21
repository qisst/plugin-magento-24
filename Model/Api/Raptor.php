<?php
namespace Qisst\Magento24\Model\Api;
use Qisst\Magento24\Api\RaptorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\ResourceModel\Website;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
class Raptor implements RaptorInterface
{
    protected $productRepository;
    protected $resultJsonFactory;

    protected $config;


        /**
         * @var WebsiteFactory
         */
        private $websiteFactory;
        /**
         * @var Website
         */
        private $websiteResourceModel;
        /**
         * @var StoreFactory
         */
        private $storeFactory;
        /**
         * @var GroupFactory
         */
        private $groupFactory;
          /**
         * @var Group
         */
        private $groupResourceModel;
        /**
         * @var Store
         */
        private $storeResourceModel;
        /**
         * @var ManagerInterface
         */
        private $eventManager;

        /**
         * InstallData constructor.
         * @param WebsiteFactory $websiteFactory
         * @param Website $websiteResourceModel
         * @param Store $storeResourceModel
         * @param Group $groupResourceModel
         * @param StoreFactory $storeFactory
         * @param GroupFactory $groupFactory
         * @param ManagerInterface $eventManager
         */

    public function __construct(
      \Magento\Config\Model\ResourceModel\Config $config,
             WebsiteFactory $websiteFactory,
             Website $websiteResourceModel,
             Store $storeResourceModel,
             Group $groupResourceModel,
             StoreFactory $storeFactory,
             GroupFactory $groupFactory,
             ManagerInterface $eventManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
     {
        $this->config = $config;
        $this->websiteFactory = $websiteFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->storeFactory = $storeFactory;
        $this->groupFactory = $groupFactory;
        $this->groupResourceModel = $groupResourceModel;
        $this->storeResourceModel = $storeResourceModel;
        $this->eventManager = $eventManager;
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
     }
    /* This is Validator Function Only Start */
    public function returnOrderId($quoteid) {
      $entityDesired = $quoteid;
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
      $shippingAddress = $cart->getQuote()->getShippingAddress();
      $cartId = $cart->getQuote()->getId();
      $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
      $connection= $this->_resources->getConnection();
      $parentTable = $connection->getTableName('sales_order');
      $subSeqTable = $connection->getTableName('sales_order_grid');
      $customOrderGet = "SELECT `entity_id` FROM `".$parentTable."` where quote_id =".$entityDesired ;
      $customOrderNo = $connection->fetchAll($customOrderGet);
      if($customOrderNo){
        $customOrderReturn = $customOrderNo[0]['entity_id'];
        $sqlDetail = "SELECT `increment_id` FROM `".$subSeqTable."` where entity_id =".$customOrderReturn;
        $orderno = $connection->fetchAll($sqlDetail);
        $orderst = $orderno[0]['increment_id'];
      }else{return null;}
      if($orderst){return $orderst;}else{return null;}
    }


/* This is Validator Function Only  End */
}
