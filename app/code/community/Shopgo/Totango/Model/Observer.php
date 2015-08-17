<?php
/**
 * ShopGo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Shopgo
 * @package     Shopgo_Totango
 * @copyright   Copyright (c) 2015 Shopgo. (http://www.shopgo.me)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Observer model
 *
 * @category    Shopgo
 * @package     Shopgo_Totango
 * @authors     Ammar <ammar@shopgo.me>
 *              Emad  <emad@shopgo.me>
 *              Ahmad <ahmadalkaid@shopgo.me>
 *              Aya   <aya@shopgo.me>
 */
class Shopgo_Totango_Model_Observer
{
    /**
     * Track orders based on their statuses
     *
     * @param Varien_Event_Observer $observer
     * @return null
     */
    public function trackOrderStatus(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        // Current order state
        $orderState  = $observer->getOrder()->getState();
        // List of order states and their data
        $orderStates = array(
            Mage_Sales_Model_Order::STATE_COMPLETE => array(
                'tracker-name'   => 'complete_orders',
                'attribute-name' => 'Complete Orders'
            ),
            Mage_Sales_Model_Order::STATE_CANCELED => array(
                'tracker-name'   => 'canceled_orders',
                'attribute-name' => 'Canceled Orders'
            )
        );

        foreach ($orderStates as $state => $data) {
            if ($helper->isTrackerEnabled($data['tracker-name'])
                && $orderState == $state) {
                $orders = Mage::getModel('sales/order')
                          ->getCollection()
                          ->addAttributeToFilter('status', array(
                              'eq' => $state
                          ))->getSize();

                $helper->track('account-attribute', array(
                    $data['attribute-name'] => $orders
                ));
            }
        }
    }

    /**
    * Track newly added catalog products
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewProduct(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        if ($helper->isTrackerEnabled('product')) {
            $product   = $observer->getProduct();
            $isProduct = Mage::getModel('catalog/product')
                         ->getCollection()
                         ->addFieldToFilter('entity_id', $product->getId())
                         ->getFirstItem()
                         ->getId();

            if (!$isProduct) {
                $productsCount = Mage::getModel('catalog/product')
                                 ->getCollection()->getSize();

                $helper->track('user-activity', array(
                    'action' => 'NewProduct',
                    'module' => 'Catalog'
                ));

                $helper->track('account-attribute', array(
                    'Number of Catalog Products' => $productsCount + 1
                ));
            }
        }
    }

    /**
    * Track newly added catalog categories
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewCategory(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        if ($helper->isTrackerEnabled('category')) {
            $categoryId = $observer->getEvent()->getCategory()->getId();
            $categories = Mage::getModel('catalog/category')
                          ->getCollection()
                          ->getAllIds();

            if (!in_array($categoryId, $categories)) {
                $categoriesCount = Mage::getModel('catalog/category')
                                   ->getCollection()->getSize();

                $helper->track('user-activity', array(
                    'action' => 'NewCategory',
                    'module' => 'Catalog'
                ));

                $helper->track('account-attribute', array(
                    'Number of Catalog Categories' => $categoriesCount + 1
                ));
            }
        }
    }

    /**
    * Track newly added catalog attributes
    *
    * @param Varien_Event_Observer $observer
    * @return null
    */
    public function trackNewAttribute(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        if ($helper->isTrackerEnabled('attribute')) {
            $attributeId = $observer->getEvent()->getAttribute()->getId();
            $isAttribute = Mage::getModel('eav/entity_attribute')
                           ->load($attributeId)
                           ->getAttributeCode();

            if (!$isAttribute) {
                $attributesCount =
                    Mage::getResourceModel('catalog/product_attribute_collection')
                    ->addVisibleFilter()->getSize();

                $helper->track('user-activity', array(
                    'action' => 'NewAttribute',
                    'module' => 'Catalog'
                ));

                $helper->track('account-attribute', array(
                    'Number of Catalog Attributes' => $attributesCount
                ));
            }
        }
    }

    /**
     * Track admin user successful logins
     *
     * @param Varien_Event_Observer $observer
     * @return null
     */
    public function trackAdminSuccessfulLogin(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('totango');

        $adminUser     = $observer->getUser();
        $adminUsername = $adminUser->getUsername();

        $excludedAdminUsers = $helper->getExcludedAdminUsers();

        if ($helper->isTrackerEnabled('admin_login')
            && !in_array($adminUsername, $excludedAdminUsers)) {
            $helper->track('user-activity', array(
                'action' => 'AdminLogin',
                'module' => 'Admin'
            ));

            $helper->track('account-attribute', array(
                'Admin User Name' => $adminUser->getUsername(),
                'Admin Last Login Time' => $adminUser->getLogdate(),
                'Admin Login Number'    => $adminUser->getLognum()
            ));
        }
    }
}
