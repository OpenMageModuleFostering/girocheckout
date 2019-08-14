<?php

/*
 * DirectdebitPaymentProxy.php
 */

class Girosolution_Giroconnect_Helper_DirectdebitPaymentProxy extends Girosolution_Giroconnect_Helper_AbstractPaymentProxy {

    private static $refINSTANCE = NULL;
    protected $showbankacc = false;
    
    public function __construct() {
        parent::__construct();
        self::$refINSTANCE = $this;
        $this->setPaymentMethod("gc_directdebit");
        $this->pReadConfiguration();
    }

// End constructor

    public static function getInstance() {
        if (isset(self::$refINSTANCE))
            return self::$refINSTANCE;
        else {
            $result = new Girosolution_Giroconnect_Helper_DirectdebitPaymentProxy();
            return $result;
        }
    }

    public function getShowBankAcc() {
      return $this->showbankacc;
    }

  // End getMerchantId
    
// End getInstance

    public function validate() {
        $result = array(
            'status' => 1001,
        );

        if ($this->merchantId == '' ||
                $this->projectId == '' ||
                $this->projectPassword == '') {
            $result['status'] = 1002;
            $result['msg'] = $this->getValidationErrorText();
        }

        return $result;
    }

// End validate

    public function modifyOrderAfterPayment($paymentSuccessful = FALSE, $orderid = '', $updateOrderState = FALSE, $gcRef = null, $gcTransInfo = null) {
        $paymentSuccessful = isset($paymentSuccessful) ? (is_bool($paymentSuccessful) ? $paymentSuccessful : FALSE) : FALSE;
        $orderid = isset($orderid) ? $orderid : '';
        $updateOrderState = isset($updateOrderState) ? (is_bool($updateOrderState) ? $updateOrderState : FALSE) : FALSE;
        if ($paymentSuccessful == TRUE) {
            $orderStateFinished = Mage_Sales_Model_Order::STATE_PROCESSING;
        }

        $directdebitModel = Mage::getModel('giroconnect/payment_directdebit');
        if (isset($directdebitModel)) {
            $result = $directdebitModel->modifyOrderAfterPayment($paymentSuccessful, $orderid, $updateOrderState, $this->getPaymentOrderComment($paymentSuccessful), $this->canSendEmail(), $this->canCreateInvoice(), $this->getInvoiceComment(), $gcRef, $gcTransInfo, $orderStateFinished);

            return $result;
        } else {
            return FALSE;
        }
    }

// End modifyOrderAfterPayment

    public function validateDirectDebitData($bankCode = '', $accountNumber = '', $IBAN = '', $accountHolder = '', $databankcheck = '') {
        $bankCode = isset($bankCode) ? (is_string($bankCode) ? trim($bankCode) : '') : '';
        $accountNumber = isset($accountNumber) ? (is_string($accountNumber) ? trim($accountNumber) : '') : '';
        $IBAN = isset($IBAN) ? (is_string($IBAN) ? trim($IBAN) : '') : '';
        $accountHolder = isset($accountHolder) ? (is_string($accountHolder) ? trim($accountHolder) : '') : '';
        $databankcheck = isset($databankcheck) ? (is_string($databankcheck) ? trim($databankcheck) : '') : '';
        $EinstellShowBank = $this->getShowBankAcc();
        
        $result = array(
            'status' => 1002,
            'msg' => Mage::helper('giroconnect')->__('Payment information missing.'),
        );

        //Validates that the Iban field or the Bank code and Account number fields are not empty.
        if( !$EinstellShowBank ) {
          if (!empty($IBAN) && !empty($accountHolder) ) {
              $result["status"] = 1001;
              $result["msg"] = "";
          }
        }
        else {
          if (!empty($databankcheck) && !empty($accountHolder)) {
              if ($databankcheck == "rbIbanDirectdebit") {
                  if (!empty($IBAN)) {
                      $result["status"] = 1001;
                      $result["msg"] = "";
                  }
              } else {
                  if (!empty($bankCode) && !empty($accountNumber)) {
                      $result["status"] = 1001;
                      $result["msg"] = "";
                  }
              }
          }
        }
        return $result;
    }

// End validateDirectDebitData

    public function startTransaction($amount = '', $currency = '', $transactionId = '', $purpose = '', $bankCode = '', $accountNumber = '', $IBAN = '', $accountHolder = '', $databankcheck = '', $clientId = '', $realorderId = '') {
        $this->transactionData = NULL;
        $EinstellShowBank = $this->getShowBankAcc();
        $amount = isset($amount) ? (is_string($amount) ? trim($amount) : '') : '';
        $currency = isset($currency) ? (is_string($currency) ? trim($currency) : 'EUR') : 'EUR';
        $transactionId = isset($transactionId) ? (is_string($transactionId) ? trim($transactionId) : '') : '';
        $bankCode = isset($bankCode) ? (is_string($bankCode) ? trim($bankCode) : '') : '';
        $accountNumber = isset($accountNumber) ? (is_string($accountNumber) ? trim($accountNumber) : '') : '';
        $IBAN = isset($IBAN) ? (is_string($IBAN) ? trim($IBAN) : '') : '';
        $IBAN = str_replace(" ", "", $IBAN);
        $accountHolder = isset($accountHolder) ? (is_string($accountHolder) ? trim($accountHolder) : '') : '';
        $databankcheck = isset($databankcheck) ? (is_string($databankcheck) ? trim($databankcheck) : '') : '';
        $sourceParam = $this->getGcSource();
        $result = array(
            'status' => 1002,
        );

        $accountHolder = substr($accountHolder, 0, 27);

        try {

            //Send request to Girocheckout based on which radiobutton is checked ($databankcheck).
            if ($databankcheck == "rbIbanDirectdebit" || ! $EinstellShowBank) {
                $request = new GiroCheckout_SDK_Request('directDebitTransaction');
                $request->setSecret($this->projectPassword);
                $request->addParam('merchantId', $this->merchantId)
                        ->addParam('projectId', $this->projectId)
                        ->addParam('merchantTxId', $transactionId)
                        ->addParam('amount', $amount * 100)
                        ->addParam('currency', $currency)
                        ->addParam('purpose', $purpose)
                        ->addParam('iban', $IBAN)
                        ->addParam('accountHolder', $accountHolder)
                        ->addParam('sourceId', $sourceParam)
                        ->addParam('orderId', $realorderId)
                        ->addParam('customerId', $clientId)
                        ->submit();
            } else {
                $request = new GiroCheckout_SDK_Request('directDebitTransaction');
                $request->setSecret($this->projectPassword);
                $request->addParam('merchantId', $this->merchantId)
                        ->addParam('projectId', $this->projectId)
                        ->addParam('merchantTxId', $transactionId)
                        ->addParam('amount', $amount * 100)
                        ->addParam('currency', $currency)
                        ->addParam('purpose', $purpose)
                        ->addParam('bankcode', $bankCode)
                        ->addParam('bankaccount', $accountNumber)
                        ->addParam('accountHolder', $accountHolder)
                        ->addParam('sourceId', $sourceParam)
                        ->addParam('orderId', $realorderId)
                        ->addParam('customerId', $clientId)
                        ->submit();
            }
            
            if ($request->requestHasSucceeded() && $request->paymentSuccessful()) {
                $result["status"] = 1001;
                $result["gcRef"] = $request->getResponseParam('reference');
                $result["gcTransInfo"] = $request->getResponseParams();
            } else {
                
                if (!$request->requestHasSucceeded()) {
                    $strResponseMsg = GiroCheckout_SDK_ResponseCode_helper::getMessage($request->getResponseParam('rc'), $this->languageCode);
                } else if (!$request->paymentSuccessful()) {
                    $strResponseMsg = GiroCheckout_SDK_ResponseCode_helper::getMessage($request->getResponseParam('resultPayment'), $this->languageCode);
                }

                $controller = Girosolution_Giroconnect_DirectdebitController::getInstance();
                if (isset($controller)) {
                    Mage::getSingleton('core/session')->addError($strResponseMsg);
                    $controller->redirectToPath('checkout/onepage', array('_secure' => true));
                } else {
                    $msg = $strResponseMsg;
                    Mage::throwException($msg);
                }
            }
        } catch (Exception $e) {

            $controller = Girosolution_Giroconnect_DirectdebitController::getInstance();
            if (isset($controller)) {
                Mage::getSingleton('core/session')->addError(GiroCheckout_SDK_ResponseCode_helper::getMessage(5100, $this->languageCode));
                $controller->redirectToPath('checkout/onepage', array('_secure' => true));
            } else {
                $msg = GiroCheckout_SDK_ResponseCode_helper::getMessage(5100, $this->languageCode);
                Mage::throwException($msg);
            }
        }
        return $result;
    }

// End startTransaction

    private function pReadConfiguration() {
        $baseurlweb = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $this->baseUrlWeb = $baseurlweb;

        $baseurl = Mage::getBaseUrl();
        $baseurl = rtrim($baseurl, "/\\");
        $this->baseUrl = $baseurl;

        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_mid');
        if (isset($value)) {
            $this->merchantId = $value;
        }
        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_pid');
        if (isset($value)) {
            $this->projectId = $value;
        }
        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_security');
        if (isset($value)) {
            $this->projectPassword = $value;
        }

        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_createinvoice');
        if (isset($value)) {
            $this->createInvoice = $this->getAsBoolean($value);
        }

        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_purpose');
        if (isset($value)) {
            $this->purpose = $value;
        }
        
        $value = Mage::getStoreConfig('payment/giroconnect_directdebit/directdebit_showbankacc');
        if (isset($value)) {
            $this->showbankacc = $value;
        }
        
        $value = Mage::helper('giroconnect')->getLanguageCode();
        if (isset($value)) {
            $this->languageCode = $value;
        }


        $this->urlRedirect = $this->baseUrl . '/giroconnect/directdebit/redirect';
        $this->urlNotify = $this->baseUrl . '/giroconnect/directdebit/notify';
    }

// End pReadConfiguration
}

// *** End class DirectdebitPaymentProxy ***
?>
