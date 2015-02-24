<?php
/**
 * @category   Payment
 * @package    Khipu_KhMage
 * @author     khipu <developers@khipu.com>
 */
class Khipu_KhMage_Helper_Api extends Mage_Core_Helper_Abstract
{


    public static function getAvailableBanks()
    {
        require_once "lib-khipu/src/Khipu.php";
        $Khipu = new Khipu();
        $Khipu->authenticate(Mage::getStoreConfig('payment/khmage/receiver_id'), Mage::getStoreConfig('payment/khmage/api_secret'));
        $Khipu->setAgent('khmage-1.5.0;;'.Mage::app()->getStore()->getHomeUrl().';;'.Mage::app()->getStore()->getName());
        $service = $Khipu->loadService('ReceiverBanks');
        return $service->consult();
    }

    public static function generatePayment($bankId, $orderId, $order)
    {
        require_once "lib-khipu/src/Khipu.php";
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $store = $order->getStore();

        $Khipu = new Khipu();
        $Khipu->authenticate(Mage::getStoreConfig('payment/khmage/receiver_id'), Mage::getStoreConfig('payment/khmage/api_secret'));
        $Khipu->setAgent('khmage-1.5.0;;'.Mage::app()->getStore()->getHomeUrl().';;'.Mage::app()->getStore()->getName());
        $create_page_service = $Khipu->loadService('CreatePaymentURL');

        $return_url = Mage::getUrl('khmage/payment/response', array('_secure' => true)) . '?transaction_id=' . $orderId;
        $notify_url = Mage::getUrl('khmage/payment/confirm', array('_secure' => true));

        $create_page_service->setParameter('subject', $store->getGroup()->getName() . ' - Orden # ' . $orderId);
        $create_page_service->setParameter('body', '');
        $create_page_service->setParameter('amount', floor($order->getBaseGrandTotal()));
        $create_page_service->setParameter('transaction_id',  $orderId);
        $create_page_service->setParameter('custom', '');
        $create_page_service->setParameter('payer_email', $customer->getEmail());
        $create_page_service->setParameter('notify_url', $notify_url);
        $create_page_service->setParameter('bank_id', $bankId);
        $create_page_service->setParameter('return_url', $return_url);

        return $create_page_service->createUrl();

    }

    public static function generateManualPayment($orderId, $order)
    {
        require_once "lib-khipu/src/Khipu.php";
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $store = $order->getStore();

        $Khipu = new Khipu();
        $Khipu->authenticate(Mage::getStoreConfig('payment/khmagemanual/receiver_id'), Mage::getStoreConfig('payment/khmagemanual/api_secret'));
        $Khipu->setAgent('khmage-1.5.0;;'.Mage::app()->getStore()->getHomeUrl().';;'.Mage::app()->getStore()->getName());
        $create_page_service = $Khipu->loadService('CreatePaymentURL');

        $return_url = Mage::getUrl('khmage/payment/response', array('_secure' => true)) . '?transaction_id=' . $orderId;
        $notify_url = Mage::getUrl('khmage/payment/confirmmanual', array('_secure' => true));

        $create_page_service->setParameter('subject', $store->getGroup()->getName() . ' - Orden # ' . $orderId);
        $create_page_service->setParameter('body', '');
        $create_page_service->setParameter('amount', floor($order->getBaseGrandTotal()));
        $create_page_service->setParameter('transaction_id',  $orderId);
        $create_page_service->setParameter('custom', '');
        $create_page_service->setParameter('payer_email', $customer->getEmail());
        $create_page_service->setParameter('notify_url', $notify_url);
        $create_page_service->setParameter('bank_id', '');
        $create_page_service->setParameter('return_url', $return_url);

        $json_string = $create_page_service->createUrl();
        $response = json_decode($json_string);

        if (!$response) {
            return 0;
        }

        $manualUrl = 'manual-url';

        return $response->$manualUrl;
    }




    public static function getNotificationIdentifier($methodname)
    {
        $post = array_map('stripslashes', $_POST);
        if($post['api_version'] == '1.2') {
            return $post['notification_id'];
        }
        if($post['api_version'] == '1.3') {
            return $post['notification_token'];
        }
        return '';
    }

    public static function getOrderFromNotification($methodname)
    {
        $post = array_map('stripslashes', $_POST);
        if($post['api_version'] == '1.2') {
            return Khipu_KhMage_Helper_Api::getOrderFromNotification_1_2($methodname);
        }
        if($post['api_version'] == '1.3') {
            return Khipu_KhMage_Helper_Api::getOrderFromNotification_1_3($methodname);
        }
        return null;

    }

    public static function getOrderFromNotification_1_3($methodname) {
	require_once "lib-khipu/src/Khipu.php";
        $Khipu = new Khipu();
        $post = array_map('stripslashes', $_POST);
        $Khipu->authenticate(Mage::getStoreConfig("payment/$methodname/receiver_id"), Mage::getStoreConfig("payment/$methodname/api_secret"));
        $Khipu->setAgent('khmage-1.5.0;;'.Mage::app()->getStore()->getHomeUrl().';;'.Mage::app()->getStore()->getName());
        $service = $Khipu->loadService('GetPaymentNotification');
        $service->setDataFromPost();
        $response = json_decode($service->consult());
        if($response->receiver_id != Mage::getStoreConfig("payment/$methodname/receiver_id")) {
            return null;
        }
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($response->transaction_id);
        return $order;

    }

    public static function getOrderFromNotification_1_2($methodname)
    {
        require_once "lib-khipu/src/Khipu.php";
        $Khipu = new Khipu();
        $_POST = array_map('stripslashes', $_POST);
        $Khipu->authenticate(Mage::getStoreConfig("payment/$methodname/receiver_id"), Mage::getStoreConfig("payment/$methodname/api_secret"));
        $Khipu->setAgent('khmage-1.5.0;;'.Mage::app()->getStore()->getHomeUrl().';;'.Mage::app()->getStore()->getName());
        $create_page_service = $Khipu->loadService('VerifyPaymentNotification');
        $create_page_service->setDataFromPost();
        if ($_POST['receiver_id'] != Mage::getStoreConfig("payment/$methodname/receiver_id")) {
            return false;
        }

        $verify = $create_page_service->verify();
        $verified =  $verify['response'] == 'VERIFIED';
        if($verified) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($_POST['transaction_id']);
            return $order;
        }
        return null;
    }

}

