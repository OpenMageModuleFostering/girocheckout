<?php

/*
 * Sofortuw.php.
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'SofortuwPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Sofortuw extends Mage_Core_Helper_Abstract {
  
    public function getSofortuwPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_SofortuwPaymentProxy::getInstance();
        return $result;
    } // End getSofortuwPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Sofortuw ***
?>
