<?php

/*
 * Ideal.php.
 */



class Girosolution_Giroconnect_Model_Payment_Ideal extends Girosolution_Giroconnect_Model_Payment_Abstract {
  
    protected $_code                    = 'giroconnect_ideal';
    protected $_formBlockType           = 'giroconnect/form_ideal';
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    
    public function __construct() {
	  } // End constructor
          
     public function getCheckout() {
        $result = Mage::getSingleton('checkout/session');
        return $result;
    } // End getCheckout
    
     /**
     * Return the redirect url.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(true)->save();
        $url = Mage::getUrl('giroconnect/ideal', array('_secure' => true));
        return $url;
    } // End getOrderPlaceRedirectUrl
    
    public function getIdealTransactionFields() {
      $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount   = trim(round($order->getGrandTotal(), 2));
        $description = Mage::helper('giroconnect')->__('Thank you for your visit');
        $issuer = Mage::getSingleton('checkout/session')->getIdealIssuer();
        
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $result = array(
            'amount'        => $amount,
            //'currency'      => 'EUR',
            'currency'      => $currencyCode,
            'txId'          => $order_id,
            'desc'          => $description,
            'issuer'      => $issuer,
        );
      
      return $result;
    } // End getGiropayTransactionFields
    
     public function assignData($data) {      
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        Mage::getSingleton('checkout/session')->setIdealIssuer($data->getGiroconnectIdealIssuer());
        return $this;
    } // End assignData
    
    public function validate() {
        $helper = Mage::helper('giroconnect/ideal');
        $proxy = $helper->getIdealPaymentProxy();
        $validationResult = $proxy->validate();
        if($validationResult['status'] != 1001) {
            Mage::throwException($validationResult['msg']);
            return $this;
        }    
        
        $errorMessage = '';
        $Issuer = Mage::getSingleton('checkout/session')->getIdealIssuer();
        
        if(!isset($Issuer)) {
            $Issuer = '';
            $errorMessage = 'No issuer bank selected.';
        }
        
        if($errorMessage != '') {
            Mage::throwException($errorMessage);
        }
        
      
      return $this;
    } // End validate

    
}// *** End class Girosolution_Giroconnect_Model_Payment_Ideal ***
?>
