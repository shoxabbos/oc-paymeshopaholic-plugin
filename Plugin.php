<?php namespace Shohabbos\Paymeshopaholic;

use Event;
use System\Classes\PluginBase;

use Lovata\OrdersShopaholic\Models\PaymentMethod;
use Shohabbos\Paymeshopaholic\Classes\PaymentGateway;

class Plugin extends PluginBase
{

	public $require = ['Lovata.OrdersShopaholic'];


    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }


    public function boot() {
        $this->addPaymentGateway();
        $this->extendPaymentModelFields();
    }


    private function addPaymentGateway() {
        Event::listen(PaymentMethod::EVENT_GET_GATEWAY_LIST, function() {
            $arPaymentMethodList = [
                'payme' => 'Payme',
            ];
            
            return $arPaymentMethodList;
        });


        PaymentMethod::extend(function ($obElement) {
            /** @var PaymentMethod $obElement */
            
            $obElement->addGatewayClass('payme', PaymentGateway::class);
        });
    }


    private function extendPaymentModelFields() {
        // Extend all backend form usage
        Event::listen('backend.form.extendFields', function($widget) {

            // Only for the PaymentMethod model
            if (!$widget->model instanceof PaymentMethod || $widget->model->gateway_id != 'payme') {
                return;
            }

            // Add an extra birthday field
            $widget->addTabFields([
                'gateway_property[min_amount]' => [
                    'label'   => 'Минимальная сумма (UZS)',
                    'comment' => 'Наименьшая допустимая сумма платежа в узбекских сумах',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                    'default' => 1000,
                ],
                'gateway_property[max_amount]' => [
                    'label'   => 'Максимальная сумма (UZS) ',
                    'comment' => 'Наибольшая допустимая сумма платежа в узбекских сумах',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                    'default' => 1000000,
                ],

                'gateway_property[type]' => [
                    'label'   => 'Тип счета',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                    'type'    => 'dropdown',
                    'options' => [
                        'single' => 'Одноразовый счет',
                        'multi'  => 'Накопительный счет'
                    ]
                ],

                'gateway_property[merchant_id]' => [
                    'label'   => 'ID',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                ],
                'gateway_property[login]' => [
                    'label'   => 'LOGIN',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                    'default' => 'Paycom'
                ],
                'gateway_property[key]' => [
                    'label'   => 'KEY',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                ],
                'gateway_property[mode]' => [
                    'label'   => 'MODE',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                    'type'    => 'dropdown',
                    'options' => [
                        'test_key' => 'Test',
                        'key'      => 'Production'
                    ]
                ],
                'gateway_property[test_key]' => [
                    'label'   => 'TEST KEY',
                    'tab'     => 'Настройки',
                    'span'    => 'auto',
                ],
            ]);
        });
    }


}
