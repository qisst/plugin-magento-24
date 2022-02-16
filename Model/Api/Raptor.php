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
      $tableName   = $connection->getTableName('sales_order_item');
      $tableNamefilter   = $connection->getTableName('sales_order_grid');
      $sql = "SELECT `order_id` FROM `".$tableName."` where item_id = ". $entityDesired;
      $itemno = $connection->fetchAll($sql);
      $orderst = $itemno[0]['order_id'];
      $ordern = (int)$orderst;
      $sqlfilter = "SELECT `increment_id` FROM `".$tableNamefilter."` where entity_id = ". $ordern;
      $orderno = $connection->fetchAll($sqlfilter);
      $orderst = $orderno[0]['increment_id'];
      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/qisstpayfilter.log');
      $logger = new \Zend\Log\Logger();
      $logger->addWriter($writer);
      $logger->info('Below are OrderNo Refined');
      $logger->info($ordern);
        return $orderst;
    }
/* This is Validator Function Only  End */
}
