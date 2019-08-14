<?php

/*
 * Giropay.php.
 */

class Girosolution_Giroconnect_Model_Payment_Giropay extends Girosolution_Giroconnect_Model_Payment_Abstract {
  
    protected $_code                    = 'giroconnect_giropay';
    protected $_formBlockType           = 'giroconnect/form_giropay';
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
        $url = Mage::getUrl('giroconnect/giropay', array('_secure' => true));
        return $url;
    } // End getOrderPlaceRedirectUrl
    
    public function getGiropayTransactionFields() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount   = trim(round($order->getGrandTotal(), 2));
        $description = Mage::helper('giroconnect')->__('Thank you for your visit');
        $BIC = Mage::getSingleton('checkout/session')->getGiropayBic();
        
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $result = array(
            'amount'        => $amount,
            //'currency'      => 'EUR',
            'currency'      => $currencyCode,
            'txId'          => $order_id,
            'desc'          => $description,
            'bic'      => $BIC,
        );
        return $result;
    } // End getGiropayTransactionFields
    
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        Mage::getSingleton('checkout/session')->setGiropayBic($data->getGiroconnectGiropayBic());
        return $this;
    } // End assignData
    
    public function validate() {
        //parent::validate();
        $helper = Mage::helper('giroconnect/giropay');
        $proxy = $helper->getGiropayPaymentProxy();
        $validationResult = $proxy->validate();
        if($validationResult['status'] != 1001) {
            Mage::throwException($validationResult['msg']);
            return $this;
        }      
        
        $errorMessage = '';
        $BIC = Mage::getSingleton('checkout/session')->getGiropayBic();
        if(!isset($BIC)) {
            $BIC = '';
        }
        
        $validationResult = $proxy->validateBic($BIC);
        if($validationResult['status'] != 1001) {
            if(isset($validationResult['msg'])) {
                $errorMessage = $validationResult['msg'];
            }
        }
        
        if($errorMessage != '') {
            Mage::throwException($errorMessage);
        }
        return $this;
    } // End validate
    
} // *** End class Girosolution_Giroconnect_Model_Payment_Giropay ***
?>
