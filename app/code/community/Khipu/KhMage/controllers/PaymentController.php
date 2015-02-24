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
 * @copyright  Copyright (c) 2015 Khipu SpA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Payment
 * @package    Khipu_KhMage
 * @author     khipu <developers@khipu.com>
 */
class Khipu_KhMage_PaymentController extends Mage_Core_Controller_Front_Action
{

    public function manualAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('khmage/payment/cancel');
            return;
        }

        $orderId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        $redirectUrl = Khipu_KhMage_Helper_Api::generateManualPayment($orderId, $order);

        if (!$redirectUrl) {
            $this->_redirect('khmage/payment/cancel');
        } else {
            $this->getResponse()->setRedirect($redirectUrl);
        }
    }

    public function choosebankAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('khmage/payment/cancel');
            return;
        }

        $orderId = $session->getLastRealOrderId();

        $bankSelector = $this->getBankSelector();

        if (!$bankSelector) {
            $this->_redirect('khmage/payment/cancel');
            return;
        }

        $this->loadLayout();
        $layout = $this->getLayout();
        $layout->getBlock('root')->setTemplate('page/1column.phtml');

        $khblock = $layout->createBlock('Mage_Core_Block_Template', 'khmage', array('template' => 'khmage/choosebank.phtml'));
        $khblock->assign('bankSelector', $bankSelector);
        $khblock->assign('orderId', $orderId);
        $khblock->assign('action', Mage::getUrl('khmage/payment/generateurl'));

        $layout->getBlock('content')->append($khblock);
        $this->renderLayout();

    }

    function base64url_decode_uncompress($data) {
        return gzuncompress(base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)));
    }


    function base64url_encode_compress($data) {
        return rtrim(strtr(base64_encode(gzcompress($data)), '+/', '-_'), '=');
    }

    public function generateurlAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('khmage/payment/cancel');
            return;
        }
        $orderId = $this->getRequest()->getPost("khmage_order_id");
        $bankId = $this->getRequest()->getPost("khmage_bank_id");


        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);

        $jsonString = Khipu_KhMage_Helper_Api::generatePayment($bankId, $orderId, $order);

        if (!$jsonString) {
            $this->_redirect('khmage/payment/cancel');
        } else {
            $this->_redirect('khmage/payment/dopay?json=' . $this->base64url_encode_compress($jsonString));
        }

    }

    public function dopayAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('khmage/payment/cancel');
            return;
        }
        $json = $this->base64url_decode_uncompress($this->getRequest()->getParam("json"));

        $this->loadLayout();
        $layout = $this->getLayout();
        $layout->getBlock('head')->addJs('khmage/jquery.min.js');
        $layout->getBlock('head')->addJs('khmage/noconflict.js');
        $layout->getBlock('head')->addJs('khmage/atmosphere.min.js');
        $layout->getBlock('head')->addJs('khmage/khipu-1.1.js');

        $layout->getBlock('root')->setTemplate('page/1column.phtml');
        $khblock = $layout->createBlock('Mage_Core_Block_Template', 'khmage', array('template' => 'khmage/dopay.phtml'));

        $khblock->assign('json', $json);

        $layout->getBlock('content')->append($khblock);
        $this->renderLayout();
    }


    private function getBankSelector()
    {
        $banks = json_decode(Khipu_KhMage_Helper_Api::getAvailableBanks());
        if (!$banks) {
            return false;
        }

        $bankSelector = <<<EOD


    <select id="root-bank" name="root-bank" style="width: auto;"></select>
        <select id="khmage_bank_id" name="khmage_bank_id" style="display: none; width: auto;"></select>
<script>
        (function ($) {
                var messages = [];
                var bankRootSelect = $('root-bank')
                var bankOptions = []
                var selectedRootBankId = 0
                var selectedBankId = 0
                //Element.writeAttribute(bankRootSelect,"disabled", "disabled");

EOD;
        foreach ($banks->banks as $bank) {
            if (!$bank->parent) {

                $bankSelector .= "bankRootSelect.insert(new Element('option', {value: \"$bank->id\"}).update(\"$bank->name\"));";

                $bankSelector .= "bankOptions['$bank->id'] = [];\n";
                $bankSelector .= "bankOptions['$bank->id'].push('<option value=\"$bank->id\">$bank->type</option>')\n";
            } else {
                $bankSelector .= "bankOptions['$bank->parent'].push('<option value=\"$bank->id\">$bank->type</option>');\n";
            }
        }
        $bankSelector .= <<<EOD
        function updateBankOptions(rootId, bankId) {
                if (rootId) {
                        Form.Element.setValue($('root-bank'),rootId);
                }

                var idx = Form.Element.getValue($('root-bank'));
                $$('#khmage_bank_id option').each(function(e){e.remove()});
                var options = bankOptions[idx];
                for (var i = 0; i < options.length; i++) {
                        $('khmage_bank_id').insert(options[i]);
                }
                if (options.length > 1) {
                        $('root-bank').addClassName('form-control-left');
                        $('khmage_bank_id').show();
                } else {
                        $('root-bank').removeClassName('form-control-left');
                        $('khmage_bank_id').hide();
                }
                if (bankId) {
                        Form.Element.setValue($('khmage_bank_id'),bankId);
                }
                //$('khmage_bank_id').change();
        }
        Event.observe($('root-bank'), 'change', function () {
                updateBankOptions();
        });
        updateBankOptions(selectedRootBankId, selectedBankId);
        bankRootSelect.removeAttribute("disabled");
})($);
</script>
EOD;
        return $bankSelector;

    }

    public function responseAction()
    {
        $orderId = $this->getRequest()->getParam("transaction_id");
        if ($orderId) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($orderId);
            $khipuauthorized = $this->__('Payment in khipu verification.');
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $khipuauthorized);
            Mage::getSingleton('checkout/session')->unsQuoteId();
            Mage_Core_Controller_Varien_Action::_redirect('khmage/payment/success', array('_secure' => true));
        } else {
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }


    public function successAction()
    {

        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
        }

        Mage::app('default');
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login/');
        } else {
            $this->loadLayout();
            $this->renderLayout();
        }

    }


    public function cancelAction()
    {
        $_SESSION['transaction_id'] = $_POST['transaction_id'];
        if (empty($_SESSION['transaction_id'])) {
            $this->_redirect('checkout/cart');
        }

        $declined = $this->__('client declined the payment in khipu.');
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $declined)->save();
                $this->_redirect('checkout/onepage/failure');
            }
        }
    }


    public function confirmAction()
    {
        $this->confirm('khmage');
    }

    public function confirmmanualAction()
    {
        $this->confirm('khmagemanual');
    }
    private function confirm($methodname) {
	$order = Khipu_KhMage_Helper_Api::getOrderFromNotification($methodname);
        if ($order != null) {
            if ($order->getId()) {
                if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
                    && $order->getState() != Mage_Sales_Model_Order::STATE_NEW
                ) {
                    $confirmAction = $this->__('confirmAction: Can not change status to order:'); // agregado 11-08-2013
                    Mage::log($confirmAction
                        . $order->getId() . " status:" . $order->getState(),
                        Zend_Log::WARN);

                    die;
                }
                $notificationIdentifier = Khipu_KhMage_Helper_Api::getNotificationIdentifier($methodname);
                
                $khipuhas = $this->__('khipu has confirmed the payment. Notification ID: '); // agregado 11-08-2013
                $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true,
                    $khipuhas . $notificationIdentifier)->save();
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);

                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transaction = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($order);
                $transaction->save();

                $invoice->setEmailSent(true);
                $invoice->save();
                $invoice->sendEmail();
                echo "OK";
                die;
            } else {
                $confirmActionlog = $this->__('confirmAction: Valid order not found: ');
                Mage::log($confirmActionlog
                    . $this->getRequest()->getParam('transaction_id'), Zend_Log::WARN);
            }
        }
    }
}
