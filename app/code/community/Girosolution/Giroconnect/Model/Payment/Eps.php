<?php

/*
 * Eps.php.
 */

class Girosolution_Giroconnect_Model_Payment_Eps extends Girosolution_Giroconnect_Model_Payment_Abstract {
  
    protected $_code                    = 'giroconnect_eps';
    protected $_formBlockType           = 'giroconnect/form_eps';
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
        $url = Mage::getUrl('giroconnect/eps', array('_secure' => true));
        return $url;
    } // End getOrderPlaceRedirectUrl
    
    public function getEpsTransactionFields() {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order    = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount   = trim(round($order->getGrandTotal(), 2));
        $description = Mage::helper('giroconnect')->__('Thank you for your visit');
        $BIC_eps = Mage::getSingleton('checkout/session')->getEpsBic();

        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $result = array(
            'amount'        => $amount,
            //'currency'      => 'EUR',
            'currency'      => $currencyCode,
            'txId'          => $order_id,
            'desc'          => $description,
            'bic'      => $BIC_eps,
        );
        return $result;
    } // End getEpsTransactionFields
    
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        Mage::getSingleton('checkout/session')->setEpsBic($data->getGiroconnectEpsBic());
        return $this;
    } // End assignData
    
    public function validate() {
        //parent::validate();
        $helper = Mage::helper('giroconnect/eps');
        $proxy = $helper->getEpsPaymentProxy();
        $validationResult = $proxy->validate();
        if($validationResult['status'] != 1001) {
            Mage::throwException($validationResult['msg']);
            return $this;
        }

        $errorMessage = '';
        $BIC_eps = Mage::getSingleton('checkout/session')->getEpsBic();
        if(!isset($BIC_eps)) {
            $BIC_eps = '';
        }
        
        $validationResult = $proxy->validateBicEps($BIC_eps);
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
    
} // *** End class Girosolution_Giroconnect_Model_Payment_Eps ***
?>
