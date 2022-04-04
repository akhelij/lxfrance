<?php
class PaymentModule extends PaymentModuleCore
{
    /*
    * module: frozenshipping
    * date: 2022-03-31 08:30:00
    * version: 1.0.0
    */
    /*
    * module: frozenshipping
    * date: 2022-03-31 08:30:00
    * version: 1.0.0
    */
    /*
    * module: frozenshipping
    * date: 2022-04-01 06:55:17
    * version: 1.0.0
    */
    protected function createOrderFromCart(
        Cart $cart,
        Currency $currency,
        $productList,
        $addressId,
        $context,
        $reference,
        $secure_key,
        $payment_method,
        $name,
        $dont_touch_amount,
        $amount_paid,
        $warehouseId,
        $cart_total_paid,
        $debug,
        $order_status,
        $id_order_state,
        $carrierId = null
    ) {
        $order = new Order();
        $order->product_list = $productList;
        $computingPrecision = Context::getContext()->getComputingPrecision();
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $address = new Address((int) $addressId);
            $context->country = new Country((int) $address->id_country, (int) $cart->id_lang);
            if (!$context->country->active) {
                throw new PrestaShopException('The delivery address country is not active.');
            }
        }


        $special_carrier = unserialize(Configuration::get('SPECIAL_CARRIER'));
        if (!isset($special_carrier)) {
            $special_carrier['id'] = null;
            $special_carrier['price'] = null;
            $special_carrier['price_with_tax'] = null;
        }


        $carrier = null;
        if (!$cart->isVirtualCart() && isset($carrierId)) {
            $carrier = new Carrier((int) $carrierId, (int) $cart->id_lang);
            $order->id_carrier = (int) $carrier->id;
            $carrierId = (int) $carrier->id;
        } else {
            $order->id_carrier = 0;
            $carrierId = 0;
        }


        $order->id_customer = (int) $cart->id_customer;
        $order->id_address_invoice = (int) $cart->id_address_invoice;
        $order->id_address_delivery = (int) $addressId;
        $order->id_currency = $currency->id;
        $order->id_lang = (int) $cart->id_lang;
        $order->id_cart = (int) $cart->id;
        $order->reference = $reference;
        $order->id_shop = (int) $context->shop->id;
        $order->id_shop_group = (int) $context->shop->id_shop_group;
        $order->secure_key = ($secure_key ? pSQL($secure_key) : pSQL($context->customer->secure_key));
        $order->payment = $payment_method;
        if (isset($name)) {
            $order->module = $name;
        }
        $order->recyclable = $cart->recyclable;
        $order->gift = (int) $cart->gift;
        $order->gift_message = $cart->gift_message;
        $order->mobile_theme = $cart->mobile_theme;
        $order->conversion_rate = $currency->conversion_rate;
        $amount_paid = !$dont_touch_amount ? Tools::ps_round((float) $amount_paid, $computingPrecision) : $amount_paid;
        $order->total_paid_real = 0;

        $order->total_products = Tools::ps_round(
            (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $order->product_list, $carrierId),
            $computingPrecision
        );
        $order->total_products_wt = Tools::ps_round(
            (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $order->product_list, $carrierId),
            $computingPrecision
        );
        $order->total_discounts_tax_excl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_discounts_tax_incl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_discounts = $order->total_discounts_tax_incl;
        $order->total_shipping_tax_excl = Tools::ps_round(
                (float) $cart->getPackageShippingCost($carrierId, false, null, $order->product_list),
                $computingPrecision
            ) + (int) $special_carrier['price'];
        $order->total_shipping_tax_incl = Tools::ps_round(
                (float) $cart->getPackageShippingCost($carrierId, true, null, $order->product_list),
                $computingPrecision
            ) + (int) $special_carrier['price_with_tax'];
        $order->total_shipping = $order->total_shipping_tax_incl;
        if (null !== $carrier && Validate::isLoadedObject($carrier)) {
            $order->carrier_tax_rate = $carrier->getTaxesRate(new Address((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }

        $order->total_wrapping_tax_excl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping_tax_incl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping = $order->total_wrapping_tax_incl;
        $order->total_paid_tax_excl = Tools::ps_round(
                (float) $cart->getOrderTotal(false, Cart::BOTH, $order->product_list, $carrierId),
                $computingPrecision
            ) + (int) $special_carrier['price'];

        $order->total_paid_tax_incl = Tools::ps_round(
                (float) $cart->getOrderTotal(true, Cart::BOTH, $order->product_list, $carrierId),
                $computingPrecision
            ) + (int) $special_carrier['price_with_tax'];

        $order->total_paid = $order->total_paid_tax_incl;
        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');
        $order->invoice_date = '0000-00-00 00:00:00';
        $order->delivery_date = '0000-00-00 00:00:00';
        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }

        $result = $order->add();
        if (!$result) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', (int) $cart->id, true);
            throw new PrestaShopException('Can\'t save Order');
        }
        if ($order_status->logable
            && number_format(
                $cart_total_paid,
                $computingPrecision
            ) != number_format(
                $amount_paid,
                $computingPrecision
            )
        ) {
            $id_order_state = Configuration::get('PS_OS_ERROR');
        }
        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderDetail is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }
        $order_detail = new OrderDetail(null, null, $context);
        $order_detail->createList($order, $cart, $id_order_state, $order->product_list, 0, true, $warehouseId);
        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderCarrier is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }
        if (null !== $carrier) {
            $order_carrier = new OrderCarrier();
            $order_carrier->id_order = (int) $order->id;
            $order_carrier->id_carrier = $carrierId;
            $order_carrier->weight = (float) $order->getTotalWeight();
            $order_carrier->shipping_cost_tax_excl = (float) $order->total_shipping_tax_excl;
            $order_carrier->shipping_cost_tax_incl = (float) $order->total_shipping_tax_incl;
            $order_carrier->add();
        }
        if (isset($special_carrier)) {
            $order_carrier = new OrderCarrier();
            $order_carrier->id_order = (int) $order->id;
            $order_carrier->id_carrier = (int) $special_carrier['id'];
            $order_carrier->weight = (float) $order->getTotalWeight();
            $order_carrier->shipping_cost_tax_excl = (float) $order->total_shipping_tax_excl;
            $order_carrier->shipping_cost_tax_incl = (float) $order->total_shipping_tax_incl;
            $order_carrier->add();
        }
        return ['order' => $order, 'orderDetail' => $order_detail];
    }
}