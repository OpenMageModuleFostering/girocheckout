<?php

/*
 * Directdebit.php
 */

class Girosolution_Giroconnect_Model_Payment_Directdebit extends Girosolution_Giroconnect_Model_Payment_Abstract {

  protected $_code = 'giroconnect_directdebit';
  protected $_formBlockType = 'giroconnect/form_directdebit';
  protected $_canUseInternal = false;
  protected $_canUseForMultishipping = false;
  protected $_isGateway = true;
  protected $_canAuthorize = true;
  protected $_canCapture = true;

  public function __construct() {
    
  }

// End constructor

  public function getCheckout() {
    $result = Mage::getSingleton('checkout/session');
    return $result;
  }

// End getCheckout

  /**
   * Return the redirect url.
   *
   * @return string
   */
  public function getOrderPlaceRedirectUrl() {
    Mage::getSingleton('checkout/session')->getQuote()->setIsActive(true)->save();
    $url = Mage::getUrl('giroconnect/directdebit', array('_secure' => true));
    return $url;
  }

// End getOrderPlaceRedirectUrl

  public function getDirectdebitTransactionFields() {
    $order_id = $this->getCheckout()->getLastRealOrderId();
    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
    $amount = trim(round($order->getGrandTotal(), 2));
    $description = Mage::helper('giroconnect')->__('Thank you for your visit');
    $bankcode = Mage::getSingleton('checkout/session')->getDirectdebitBankcode();
    $bankaccount = Mage::getSingleton('checkout/session')->getDirectdebitBankaccount();
    $IBAN = Mage::getSingleton('checkout/session')->getDirectdebitIban();
    $accountHolder = Mage::getSingleton('checkout/session')->getDirectdebitAccountholder();
    $databankcheck = Mage::getSingleton('checkout/session')->getDirectdebitCdatabankcheck();
    $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
    $result = array(
        'amount' => $amount,
        //'currency'      => 'EUR',
        'currency' => $currencyCode,
        'txId' => $order_id,
        'desc' => $description,
        'bankcode' => $bankcode,
        'bankaccount' => $bankaccount,
        'iban' => $IBAN,
        'accountholder' => $accountHolder,
        'cdatabankcheck' => $databankcheck,
    );
    return $result;
  }

// End getDirectdebitTransactionFields

  public function assignData($data) {
    if (!($data instanceof Varien_Object)) {
      $data = new Varien_Object($data);
    }
    Mage::getSingleton('checkout/session')->setDirectdebitBankcode($data->getGiroconnectDirectdebitBankcode());
    Mage::getSingleton('checkout/session')->setDirectdebitBankaccount($data->getGiroconnectDirectdebitBankaccount());
    Mage::getSingleton('checkout/session')->setDirectdebitIban($data->getGiroconnectDirectdebitIban());
    Mage::getSingleton('checkout/session')->setDirectdebitAccountholder($data->getGiroconnectDirectdebitAccountholder());
    Mage::getSingleton('checkout/session')->setDirectdebitCdatabankcheck($data->getGiroconnectDirectdebitCdatabankcheck());
    return $this;
  }

// End assignData

  public function validate() {
    //parent::validate();
    $helper = Mage::helper('giroconnect/directdebit');
    $proxy = $helper->getDirectdebitPaymentProxy();
    $validationResult = $proxy->validate();
    if ($validationResult['status'] != 1001) {
      Mage::throwException($validationResult['msg']);
    }

    $errorMessage = '';
    $bankcode = Mage::getSingleton('checkout/session')->getDirectdebitBankcode();
    if (!isset($bankcode)) {
      $bankcode = '';
    }
    $bankaccount = Mage::getSingleton('checkout/session')->getDirectdebitBankaccount();
    if (!isset($bankaccount)) {
      $bankaccount = '';
    }
    $IBAN = Mage::getSingleton('checkout/session')->getDirectdebitIban();
    if (!isset($IBAN)) {
      $IBAN = '';
    }
    $accountHolder = Mage::getSingleton('checkout/session')->getDirectdebitAccountholder();
    if (!isset($accountHolder)) {
      $accountHolder = '';
    }

    $databankcheck = Mage::getSingleton('checkout/session')->getDirectdebitCdatabankcheck();
    if (!isset($databankcheck)) {
      $databankcheck = '';
    }

    $validationResult = $proxy->validateDirectDebitData($bankcode, $bankaccount, $IBAN, $accountHolder, $databankcheck);
    if ($validationResult['status'] != 1001) {
      if (isset($validationResult['msg'])) {
        $errorMessage = $validationResult['msg'];
      }
    }

    if ($errorMessage != '') {
      Mage::throwException($errorMessage);
    }
    return $this;
  }

// End validate
}

// *** End class Girosolution_Giroconnect_Model_Payment_Directdebit ***
?>
