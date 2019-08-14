<?php

/*
 * Giropay.php.
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'GiropayPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Giropay extends Mage_Core_Helper_Abstract {
  
    public function getGiropayPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_GiropayPaymentProxy::getInstance();
        return $result;
    } // End getGiropayPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Giropay ***
?>
