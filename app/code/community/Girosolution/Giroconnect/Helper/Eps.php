<?php

/*
 * Eps.php.
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'EpsPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Eps extends Mage_Core_Helper_Abstract {
  
    public function getEpsPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_EpsPaymentProxy::getInstance();
        return $result;
    } // End getEpsPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Eps ***
?>
