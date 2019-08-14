<?php

/*
 * DirectdebitController.php
 */
//define('__GIROCKECKOUT_SDK_DEBUG__',true);
class Girosolution_Giroconnect_DirectdebitController extends Mage_Core_Controller_Front_Action {
  
    private static $instance = null;
    
    protected function _construct() {
        self::$instance = $this;
    } // End custom constructor

    
    public static function getInstance() {
        return self::$instance;
    } // End getInstance
    
    protected function _getCheckout() {
        $result = Mage::getSingleton('checkout/session');
    	  return $result;
    } // End _getCheckout

    public function redirectToPath($path, $arguments = array()) {
        $this->_redirect($path, $arguments);
    } // End redirectToPath
    
    public function indexAction() {
        $helper = Mage::helper('giroconnect/directdebit');
        $proxy = $helper->getDirectdebitPaymentProxy();
        
        
        $session = $this->_getCheckout();
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(true)->save();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }
        $status = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $order->setState($status, true, Mage::helper('giroconnect')->__('The customer pays with Direct Debit.') );
        $order->save();
        
        
        $directdebitModel = Mage::getModel('giroconnect/payment_directdebit');
        $transactionData = $directdebitModel->getDirectdebitTransactionFields();
        $amount = $transactionData['amount'];
        $currency = $transactionData['currency'];
        $transactionId = $transactionData['txId'];
        $bankCode = $transactionData['bankcode'];
        $bankAccount = $transactionData['bankaccount'];
        $IBAN = $transactionData['iban'];
        $accountHolder = $transactionData['accountholder'];
        $databankcheck = $transactionData['cdatabankcheck'];
        $purpose = $proxy->getPurpose($order);

        if( $order->getCustomerId() == NULL )
          $CustomerNr = "";
        else 
          $CustomerNr = $order->getCustomerId();
        
        $resultTransaction = $proxy->startTransaction($amount, $currency, $transactionId, $purpose, $bankCode, $bankAccount, $IBAN, $accountHolder, $databankcheck, $CustomerNr, $order->getRealOrderId());
        
        if(is_array($resultTransaction) && $resultTransaction["status"]== 1001){
          $orderFound = $proxy->modifyOrderAfterPayment(TRUE, $transactionId, TRUE,$resultTransaction["gcRef"], $resultTransaction["gcTransInfo"]);
          $this->_redirect('checkout/onepage/success', array('_secure' => true));      
        }
        else {
        	$orderFound = $proxy->modifyOrderAfterPayment(FALSE, $transactionId, TRUE,$resultTransaction["gcRef"], $resultTransaction["gcTransInfo"]);
        	Mage::getSingleton('core/session')->addError(Mage::helper('giroconnect')->__('Payment with Direct Debit failed'));
        	$this->_redirect('checkout/onepage', array('_secure' => true));
        }
    } // End indexAction
    
    public function redirectAction() {

    } // End redirectAction
    
    public function notifyAction() {

        // do nothing, if not a GET request
        if (!$this->getRequest()->isGet()) {
            $this->norouteAction();
            return;
        }

        exit;
    } // End notifyAction
    
    
} // *** End class Girosolution_Giroconnect_DirectdebitController ***