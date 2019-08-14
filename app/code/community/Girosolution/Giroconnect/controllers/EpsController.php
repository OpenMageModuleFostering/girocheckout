<?php

/*
 * EpsController.php.
 */

class Girosolution_Giroconnect_EpsController extends Mage_Core_Controller_Front_Action {

  private static $instance = null;

  protected function _construct() {
    self::$instance = $this;
  }

// End custom constructor

  public static function getInstance() {
    return self::$instance;
  }

// End getInstance

  protected function _getCheckout() {
    $result = Mage::getSingleton('checkout/session');
    return $result;
  }

// End _getCheckout

  public function redirectToPath($path, $arguments = array()) {
    $this->_redirect($path, $arguments);
  }

// End redirectToPath

  public function indexAction() {
    $helper = Mage::helper('giroconnect/eps');
    $proxy = $helper->getEpsPaymentProxy();


    $session = $this->_getCheckout();
    Mage::getSingleton('checkout/session')->getQuote()->setIsActive(true)->save();
    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($session->getLastRealOrderId());
    if (!$order->getId()) {
      Mage::throwException('No order for processing found');
    }
    $status = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    $order->setState($status, true, Mage::helper('giroconnect')->__('The customer was redirected to eps.'));
    $order->save();


    $epsModel = Mage::getModel('giroconnect/payment_eps');
    $transactionData = $epsModel->getEpsTransactionFields();
    $amount = $transactionData['amount'];
    $currency = $transactionData['currency'];
    $transactionId = $transactionData['txId'];
    $BIC_eps = $transactionData['bic'];
    $purpose = $proxy->getPurpose($order);

    if( $order->getCustomerId() == NULL )
      $CustomerNr = "";
    else 
      $CustomerNr = $order->getCustomerId();

    $resultTransaction = $proxy->startTransaction($amount, $currency, $transactionId, $purpose, $BIC_eps, $CustomerNr, $order->getRealOrderId());

    if (is_array($resultTransaction) && $resultTransaction["status"] == 1001) {

      $payment = $order->getPayment();
      $payment->setTransactionId($resultTransaction['reference']);
      $transaction = $payment->addTransaction('order', null, false, '');
      $transaction->setParentTxnId($resultTransaction['reference']);
      $transaction->setIsClosed(1);
      $transaction->setAdditionalInformation("arrInfo", serialize($resultTransaction['gcTransInfo']));
      $transaction->save();
      $order->save();
      $this->_redirectUrl($resultTransaction["redirect"], array('_secure' => true));
    }
  }

// End indexAction

  public function redirectAction() {
    sleep(5);
    $helper = Mage::helper('giroconnect/eps');
    $proxy = $helper->getEpsPaymentProxy();

    //Retrieves the project password.
    $projectPassword = $proxy->getProjectPassword();

    $order = Mage::getModel('sales/order')->loadByIncrementId($_GET['gcMerchantTxId']);
    
    try {
      //Get the notification
      $notify = new GiroCheckout_SDK_Notify('epsTransaction');
      $notify->setSecret($projectPassword);
      $notify->parseNotification($_GET);

      //Checks if the payment was successful and redirects the user
      if (!$notify->paymentSuccessful()) {
        
        // Set messages or error codes
        $strMsg = Mage::helper('giroconnect')->__('Payment with eps failed');
        Mage::getSingleton('core/session')->addError($strMsg);

        if( $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ) {
          $orderFound = $proxy->modifyOrderAfterPayment(FALSE, $_GET['gcMerchantTxId'], TRUE, $strMsg, $notify->getResponseParams());
        }

        $this->_redirect('checkout/onepage', array('_secure' => true));
      } else {
        $strMsg = Mage::helper('giroconnect')->__('Payment with eps was successful'). " Ref." .$notify->getResponseParam('gcReference');

        if( $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ) {
          $orderFound = $proxy->modifyOrderAfterPayment(TRUE, $_GET['gcMerchantTxId'], TRUE, $strMsg, $notify->getResponseParams());
        }

        // Set customers shopping cart inactive
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
      }
    } catch (Exception $e) {
      Mage::getSingleton('core/session')->addError(Mage::helper('giroconnect')->__('An unexpected error occured, please check your order'));
      $this->_redirect('checkout/onepage', array('_secure' => true));
    }
  }

// End redirectAction

  public function notifyAction() {

    // do nothing, if not a GET request
    if (!$this->getRequest()->isGet()) {
      $this->norouteAction();
      return;
    }

    $helper = Mage::helper('giroconnect/eps');
    $proxy = $helper->getEpsPaymentProxy();

    //Retrieves the project password.
    $projectPassword = $proxy->getProjectPassword();

    // clear response
    $this->getResponse()->clearAllHeaders()->clearBody();

    $strMsg = "";
    $CustomerNr = "";
    $order = Mage::getModel('sales/order')->loadByIncrementId($_GET['gcMerchantTxId']);
    
    if( $order->getCustomerIsGuest() ){
      $CustomerNr = "";
    }
    //else, it's a normal registered user
    else {
     $CustomerNr = $order->getCustomerId();
    }
    
    try {
      //Get the notification
      $notify = new GiroCheckout_SDK_Notify('epsTransaction');
      $notify->setSecret($projectPassword);
      $notify->parseNotification($_GET);

      if( !$notify->paymentSuccessful() ) {
        $strMsg = Mage::helper('giroconnect')->__('Payment with eps failed'). " Ref." .$notify->getResponseParam('gcReference');

        if( $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ) {
          $orderFound = $proxy->modifyOrderAfterPayment(FALSE, $notify->getResponseParam('gcMerchantTxId'), TRUE, $strMsg, $notify->getResponseParams());
        }

        $notify->sendOkStatus();
        $notify->setNotifyResponseParam('Result', 'ERROR');
        $notify->setNotifyResponseParam('ErrorMessage', $strMsg);
        $notify->setNotifyResponseParam('MailSent', '');
        $notify->setNotifyResponseParam('OrderId', $order->getRealOrderId());
        $notify->setNotifyResponseParam('CustomerId', $CustomerNr);
        echo $notify->getNotifyResponseStringJson();      
      } else {
        $strMsg = Mage::helper('giroconnect')->__('Payment with eps was successful'). " Ref." .$notify->getResponseParam('gcReference');

        if( $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ) {
          $orderFound = $proxy->modifyOrderAfterPayment(TRUE, $notify->getResponseParam('gcMerchantTxId'), TRUE, $strMsg, $notify->getResponseParams());
        }

        $notify->sendOkStatus();
        $notify->setNotifyResponseParam('Result', 'OK');
        $notify->setNotifyResponseParam('ErrorMessage', '');
        $notify->setNotifyResponseParam('MailSent', '');
        $notify->setNotifyResponseParam('OrderId', $order->getRealOrderId());
        $notify->setNotifyResponseParam('CustomerId', $CustomerNr);
        echo $notify->getNotifyResponseStringJson();
      }
    } catch (Exception $e) {
      $notify->sendBadRequestStatus();
      $notify->setNotifyResponseParam('Result', 'ERROR');
      $notify->setNotifyResponseParam('ErrorMessage', $e->getMessage());
      $notify->setNotifyResponseParam('MailSent', '');
      $notify->setNotifyResponseParam('OrderId', $order->getRealOrderId());
      $notify->setNotifyResponseParam('CustomerId', $CustomerNr);
      echo $notify->getNotifyResponseStringJson();
    }
    
    exit;
  }

// End notifyAction
}
// *** End class Girosolution_Giroconnect_EpsController ***