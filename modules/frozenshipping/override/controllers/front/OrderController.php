<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

class OrderController extends OrderControllerCore
{
    public function displayAjaxCheckCarrier()
    {
        Configuration::updateValue('SPECIAL_CARRIER', serialize(Tools::getValue('special_carrier')));
        $this->context->cookie->__set('special_carrier_id', Tools::getValue('selected_carrier')["id"]);
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