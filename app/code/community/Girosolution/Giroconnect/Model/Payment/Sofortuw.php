<?php

/*
 * Sofortuw.php.
 */
class Girosolution_Giroconnect_Model_Payment_Sofortuw extends Girosolution_Giroconnect_Model_Payment_Abstract {
  
    protected $_code                    = 'giroconnect_sofortuw';
    protected $_formBlockType           = 'giroconnect/form_sofortuw';
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canUseCheckout          = true;
  
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
        $url = Mage::getUrl('giroconnect/sofortuw', array('_secure' => true));
        return $url;
    } // End getOrderPlaceRedirectUrl
    
    public function getSofortuwTransactionFields() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount   = trim(round($order->getGrandTotal(), 2));
        $description = Mage::helper('giroconnect')->__('Thank you for your visit');
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $result = array(
            'amount'        => $amount,
            //'currency'      => 'EUR',
            'currency'      => $currencyCode,
            'txId'          => $order_id,
            'desc'          => $description,
        );
        return $result;
    } // End getSofortuwTransactionFields
    
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        return $this;
    } // End assignData
    
    public function validate() {
        $helper = Mage::helper('giroconnect/sofortuw');
        $proxy = $helper->getSofortuwPaymentProxy();
        $validationResult = $proxy->validate();

        if($validationResult['status'] != 1001) {
            Mage::throwException($validationResult['msg']);
        }      
        return $this;
    } // End validate
    
} // *** End class Girosolution_Giroconnect_Model_Payment_Sofortuw
?>
