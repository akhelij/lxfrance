<?php

class frozenshippingAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->ajax = 1;
        $this->name = 'frozenshipping';
    }

    public function displayAjaxCheckCarrier()
    {
        die(
            Tools::jsonEncode([
                'id_carrier' => Tools::getValue('selected_carrier'),
            ])
        );
        // die(dump(Tools::getAllValues()));
    }
}