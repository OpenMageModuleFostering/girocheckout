<?php

/*
 * Paydirekt.php.
 */

// include_once 'AbstractPaymentProxy.php';
// include_once 'PaydirektPaymentProxy.php';

class Girosolution_Giroconnect_Helper_Paydirekt extends Mage_Core_Helper_Abstract {
  
    public function getPaydirektPaymentProxy() {
        $result = Girosolution_Giroconnect_Helper_PaydirektPaymentProxy::getInstance();
        return $result;
    } // End getPaydirektPaymentProxy
  
} // *** End class Girosolution_Giroconnect_Helper_Paydirekt ***
?>
