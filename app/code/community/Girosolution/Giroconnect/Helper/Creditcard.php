<?php

/*
 * Creditcard.php.
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'CreditcardPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Creditcard extends Mage_Core_Helper_Abstract {
  
    public function getCreditcardPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_CreditcardPaymentProxy::getInstance();
        return $result;
    } // End getCreditcardPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Creditcard ***
?>
