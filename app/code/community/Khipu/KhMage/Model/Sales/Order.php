<?php
/**
 * Khipu_KhMage extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Payment
 * @package    Khipu_KhMage
 * @copyright  Copyright (c) 2013 Magento Chile LLC
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Payment
 * @package    Khipu_KhMage
 * @author     khipu <developers@khipu.com>
 */
include_once('Mage/Sales/Model/Order.php');

class Khipu_Khmage_Model_Sales_Order extends Mage_Sales_Model_Order
{

    public function setState($state, $status = false, $comment = '', $isCustomerNotified = null)
    {
        return $this->_setState($state, $status, $comment, $isCustomerNotified, false);
    }


}
