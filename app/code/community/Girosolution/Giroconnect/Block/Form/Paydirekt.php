<?php

/*
 * Paydirekt.php.
 */
class Girosolution_Giroconnect_Block_Form_Paydirekt extends Mage_Payment_Block_Form {
  
     protected function _construct() {
        parent::_construct();
        $this->setTemplate('giroconnect/paydirekt/form.phtml');     
     } // End _construct
  
} // End Girosolution_Giroconnect_Block_Form_Paydirekt
?>
