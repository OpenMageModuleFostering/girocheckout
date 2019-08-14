<?php

/*
 * EpsPaymentProxy.php
 */

class Girosolution_Giroconnect_Helper_EpsPaymentProxy extends Girosolution_Giroconnect_Helper_AbstractPaymentProxy {

  private static $refINSTANCE = NULL;

  public function __construct() {
    parent::__construct();
    self::$refINSTANCE = $this;
    $this->setPaymentMethod("gc_eps");
    $this->pReadConfiguration();
  }

// End constructor

  public static function getInstance() {
    if (isset(self::$refINSTANCE))
      return self::$refINSTANCE;
    else {
      $result = new Girosolution_Giroconnect_Helper_EpsPaymentProxy();
      return $result;
    }
  }

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
    if( $paymentSuccessful == TRUE ) { 
      $orderStateFinished = Mage_Sales_Model_Order::STATE_PROCESSING; 
    }

    $epsModel = Mage::getModel('giroconnect/payment_eps');
    if (isset($epsModel)) {
      $result = $epsModel->modifyOrderAfterPayment($paymentSuccessful, $orderid, $updateOrderState, $this->getPaymentOrderComment($paymentSuccessful), $this->canSendEmail(), $this->canCreateInvoice(), $this->getInvoiceComment(), $gcRef, $gcTransInfo, $orderStateFinished);
      return $result;
    } else {
      return FALSE;
    }
  }

// End modifyOrderAfterPayment

  public function validateBicEps($BIC_eps = '') {
    $BIC_eps = isset($BIC_eps) ? (is_string($BIC_eps) ? trim($BIC_eps) : '') : '';
    $result = array(
        'status' => 1002,
        'code' => 'sb999',
        'msg' => Mage::helper('giroconnect')->__('eps general error'),
    );
    $sourceParam = $this->getGcSource();

    try {
//Check if the bank provides eps from BIC.
        $request = new GiroCheckout_SDK_Request('epsBankstatus');
        $request->setSecret($this->projectPassword);
        $request->addParam('merchantId', $this->merchantId)
                ->addParam('projectId', $this->projectId)
                ->addParam('sourceId', $sourceParam)
                ->addParam('bic', $BIC_eps)
                ->submit();

        if ($request->requestHasSucceeded()) {
          $result["status"] = 1001;
          $result["msg"] = "";
        } else {
          $iReturnCode = $request->getResponseParam('rc');
          $result["msg"] = $request->getResponseMessage($iReturnCode, $this->languageCode);
          $result["status"] = 1002;
        }
      
    } catch (Exception $e) {
      
    }
    return $result;
  }

// End validateBankcode

  public function startTransaction($amount = '', $currency = '', $transactionId = '', $purpose = '', $BIC_eps = '', $clientId = '', $realorderId = '') {
    $this->transactionData = NULL;
    $amount = isset($amount) ? (is_string($amount) ? trim($amount) : '') : '';
    $currency = isset($currency) ? (is_string($currency) ? trim($currency) : 'EUR') : 'EUR';
    $transactionId = isset($transactionId) ? (is_string($transactionId) ? trim($transactionId) : '') : '';
    $BIC_eps = isset($BIC_eps) ? (is_string($BIC_eps) ? trim($BIC_eps) : '') : '';
    $sourceParam = $this->getGcSource();

    $result = array(
        'status' => 1002,
    );

    try {

//Sends request to Girocheckout.
      $request = new GiroCheckout_SDK_Request('epsTransaction');
      $request->setSecret($this->projectPassword);
      $request->addParam('merchantId', $this->merchantId)
              ->addParam('projectId', $this->projectId)
              ->addParam('merchantTxId', $transactionId)
              ->addParam('amount', $amount * 100)
              ->addParam('currency', $currency)
              ->addParam('purpose', $purpose)
              ->addParam('bic', $BIC_eps)
              ->addParam('urlRedirect', $this->urlRedirect)
              ->addParam('urlNotify', $this->urlNotify)
              ->addParam('sourceId', $sourceParam)
              ->addParam('orderId', $realorderId)
              ->addParam('customerId', $clientId)
              ->submit();

      if ($request->requestHasSucceeded()) {
        $strUrlRedirect = $request->getResponseParam('redirect');

        $result["status"] = 1001;
        $result["redirect"] = $strUrlRedirect;
        $result["reference"] = $request->getResponseParam('reference');
        $result["gcTransInfo"] = $request->getResponseParams();
      } else {

        $iReturnCode = $request->getResponseParam('rc');
        $strResponseMsg = $request->getResponseMessage($iReturnCode, $this->languageCode);

        if ($request->getResponseParam('reference')) {
          $order = Mage::getModel('sales/order');
          $order->loadByIncrementId($transactionId);

          $payment = $order->getPayment();
          $payment->setTransactionId($request->getResponseParam('reference'));
          $transaction = $payment->addTransaction('order', null, false, '');
          $transaction->setParentTxnId($request->getResponseParam('reference'));
          $transaction->setIsClosed(1);
          $transaction->setAdditionalInformation("arrInfo", serialize($request->getResponseParams()));
          $transaction->save();
          $order->save();
        }

        $controller = Girosolution_Giroconnect_EpsController::getInstance();
        if (isset($controller)) {
          Mage::getSingleton('core/session')->addError($strResponseMsg);
          $controller->redirectToPath('checkout/onepage', array('_secure' => true));
        } else {
          $msg = $strResponseMsg;
          Mage::throwException($msg);
        }
      }
    } catch (Exception $e) {
      $controller = Girosolution_Giroconnect_EpsController::getInstance();
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

    $value = Mage::getStoreConfig('payment/giroconnect_eps/eps_mid');
    if (isset($value)) {
      $this->merchantId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_eps/eps_pid');
    if (isset($value)) {
      $this->projectId = $value;
    }
    $value = Mage::getStoreConfig('payment/giroconnect_eps/eps_security');
    if (isset($value)) {
      $this->projectPassword = $value;
    }

    $value = Mage::getStoreConfig('payment/giroconnect_eps/eps_createinvoice');
    if (isset($value)) {
      $this->createInvoice = $this->getAsBoolean($value);
    }

    $value = Mage::getStoreConfig('payment/giroconnect_eps/eps_purpose');
    if (isset($value)) {
      $this->purpose = $value;
    }
    
    $value = Mage::helper('giroconnect')->getLanguageCode();
    if (isset($value)) {
      $this->languageCode = $value;
    }


    $this->urlRedirect = $this->baseUrl . '/giroconnect/eps/redirect';
    $this->urlNotify = $this->baseUrl . '/giroconnect/eps/notify';
  }

// End pReadConfiguration
}

// *** End class EpsPaymentProxy ***
?>
