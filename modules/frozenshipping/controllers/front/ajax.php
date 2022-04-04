<?php

class frozenshippingAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::init();
        $this->ajax = 1;
        $this->name = 'frozenshipping';
    }

    public function displayAjaxCheckCarrier()
    {
        $this->context->cookie->__set('special_carrier_id', Tools::getValue('selected_carrier')["selected_carrier_id"]);
        $this->context->cookie->__set('special_carrier_price', Tools::getValue('selected_carrier')["selected_carrier_price"]);
        $this->context->cookie->__set('special_carrier_price_with_tax', Tools::getValue('selected_carrier')["selected_carrier_price_with_tax"]);
        $this->context->cookie->write();

        $cart = $this->cart_presenter->present(
            $this->context->cart,
            true
        );

        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(Tools::jsonEncode([
            'preview' => $this->render('checkout/_partials/cart-summary', [
                'cart' => $cart,
                'static_token' => Tools::getToken(false),
            ]),
        ]));
     }
}