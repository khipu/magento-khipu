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
class Khipu_KhMage_Model_KhMageManual extends Mage_Payment_Model_Method_Abstract
{

    // unique internal payment method identifier
    protected $_code = 'khmagemanual';


    // Is this payment method a gateway (online auth/charge) ?
    protected $_isGateway = true;

    // Can authorize online?
    protected $_canAuthorize = false;

    // Can capture funds online?
    protected $_canCapture = false;

    // Can capture partial amounts online?
    protected $_canCapturePartial = false;

    // Can refund online?
    protected $_canRefund = false;

    // Can void transactions online?
    protected $_canVoid = false;

    // Can use this payment method in administration panel?
    protected $_canUseInternal = true;

    // Can show this payment method as an option on checkout payment page?
    protected $_canUseCheckout = true;

    // Is this payment method suitable for multi-shipping checkout?
    protected $_canUseForMultishipping = true;

    // Can save credit card information for future processing?
    protected $_canSaveCc = false;

    // this method is called if we are just authorising a transaction
    public function authorize(Varien_Object $payment, $amount)
    {
        Mage::log("authorize");
    }

    // this method is called if we are authorising AND capturing a transaction
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::log("capture");
    }

    public function refund(Varien_Object $payment, $amount)
    {
        Mage::log("refund");
    }

    public function void(Varien_Object $payment)
    {
        Mage::log("void");
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('khmage/payment/manual', array('_secure' => true));
    }
}

?>