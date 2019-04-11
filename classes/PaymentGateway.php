<?php namespace Shohabbos\Paymeshopaholic\Classes;

use Lovata\OrdersShopaholic\Classes\Helper\AbstractPaymentGateway;

class PaymentGateway extends AbstractPaymentGateway
{
    /** @var array - response from payment gateway */
    protected $arResponse = [];
    protected $arRequestData = [];
    protected $sRedirectURL = '';
    protected $sMessage = '';
    
    protected $obResponse;
    
    /**
    * Get response array
    * @return array
    */
    public function getResponse() : array
    {
        return $this->arResponse;
    }
    
    /**
    * Get redirect URL
    * @return string
    */
    public function getRedirectURL() : string
    {
        return $this->sRedirectURL;
    }
    
    /**
    * Get error message from payment gateway
    * @return string
    */
    public function getMessage() : string
    {
        return $this->sMessage;
    }
    
    /**
    * Prepare data for request in payment gateway 
    */
    protected function preparePurchaseData()
    {
        $this->arRequestData = [
            'currency'   => $this->obPaymentMethod->gateway_currency,            
            'secred_key' => $this->getGatewayProperty('secred_key'),            
            'total_cost' => $this->obOrder->total_price_value,            
            'user_name'  => $this->getOrderProperty('name'),            
        ];
    }
    
    /**
    * Validate request data
    * @return bool 
    */
    protected function validatePurchaseData()
    {
        if (empty($this->arRequestData['currency'])) {
            $this->sMessage = 'Currency is required';
            return false;
        }
        
        return true;
    }
    
    /**
    * Send request to payment gateway
    */
    protected function sendPurchaseData()
    {
    }
    
    /**
    * Process response from payment gateway 
    */
    protected function processPurchaseResponse()
    {
        $data = [
            'm' => $this->getGatewayProperty('merchant_id'),
            'ac.id' => $this->obOrder->id,
            'a' => $this->obOrder->total_price_value * 100,
            'l' => 'ru',
            'c' => url()->current()
        ];

        $this->sRedirectURL = 'https://checkout.paycom.uz/'.base64_encode(http_build_query($data, '', ';'));
        $this->bIsRedirect = true;
    }


    /**
     * Process success request
     * @param string $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processSuccessRequest($orderId)
    {
        $this->initOrderObject($orderId);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        //Set success status in order
        $this->setSuccessStatus();
    }

    /**
     * Process cancel request
     * @param string $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processCancelRequest($orderId)
    {
        //Init order object
        $this->initOrderObject($orderId);
        if (empty($this->obOrder) || empty($this->obPaymentMethod)) {
            return Redirect::to('/');
        }

        //Set cancel status in order
        $this->setCancelStatus();
    }


}
