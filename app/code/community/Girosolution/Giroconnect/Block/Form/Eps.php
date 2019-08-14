<?php

/*
 * eps.php
 */
class Girosolution_Giroconnect_Block_Form_Eps extends Mage_Payment_Block_Form {
  
     protected function _construct() {
        parent::_construct();
        $this->setTemplate('giroconnect/eps/form.phtml');
     } // End _construct
  
} // End Girosolution_Giroconnect_Block_Form_Eps
?>