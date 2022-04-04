<?php
use PrestaShop\PrestaShop\Adapter\AddressFactory;
use PrestaShop\PrestaShop\Adapter\Cache\CacheAdapter;
use PrestaShop\PrestaShop\Adapter\Customer\CustomerDataProvider;
use PrestaShop\PrestaShop\Adapter\Database;
use PrestaShop\PrestaShop\Adapter\Group\GroupDataProvider;
use PrestaShop\PrestaShop\Adapter\Product\PriceCalculator;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Cart\Calculator;
use PrestaShop\PrestaShop\Core\Cart\CartRow;
use PrestaShop\PrestaShop\Core\Cart\CartRuleData;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;

class Cart extends CartCore
{
    /**
     * Return shipping total for the cart.
     *
     * @param array|null $delivery_option Array of the delivery option for each address
     * @param bool $use_tax Use taxes
     * @param Country|null $default_country Default Country
     *
     * @return float Shipping total
     */
    public function getTotalShippingCost($delivery_option = null, $use_tax = true, Country $default_country = null)
    {
        if (isset(Context::getContext()->cookie->id_country)) {
            $default_country = new Country(Context::getContext()->cookie->id_country);
        }
        if (null === $delivery_option) {
            $delivery_option = $this->getDeliveryOption($default_country, false, false);
        }
        $_total_shipping = [
            'with_tax' => 0,
            'without_tax' => 0,
        ];
        $delivery_option_list = $this->getDeliveryOptionList($default_country);

        $special_carrier = unserialize(Configuration::get('SPECIAL_CARRIER'));

        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address]) || !isset($delivery_option_list[$id_address][$key])) {
                continue;
            }
            $_total_shipping['with_tax'] += $delivery_option_list[$id_address][$key]['total_price_with_tax'] + (isset($special_carrier) ? $special_carrier["price_with_tax"] : 0);
            $_total_shipping['without_tax'] += $delivery_option_list[$id_address][$key]['total_price_without_tax'] + (isset($special_carrier) ? $special_carrier["price"] : 0);
        }
        return ($use_tax) ? $_total_shipping['with_tax'] : $_total_shipping['without_tax'];
    }


    /*
    * module: frozenshipping
    * date: 2022-04-01 06:55:17
    * version: 1.0.0
    */
    public function getDeliveryOptionList(Country $default_country = null, $flush = false)
    {
        $flush = Configuration::get('FROZENSHIPPING_LIVE_MODE', $flush);

        if (isset(static::$cacheDeliveryOptionList[$this->id]) && !$flush) {
            return static::$cacheDeliveryOptionList[$this->id];
        }
        $delivery_option_list = [];
        $carriers_price = [];
        $carrier_collection = [];
        $package_list = $this->getPackageList($flush);
        foreach ($package_list as $id_address => $packages) {
            $delivery_option_list[$id_address] = [];
            $carriers_price[$id_address] = [];
            $common_carriers = null;
            $best_price_carriers = [];
            $best_grade_carriers = [];
            $carriers_instance = [];
            if ($id_address) {
                $address = new Address($id_address);
                $country = new Country($address->id_country);
            } else {
                $country = $default_country;
            }
            foreach ($packages as $id_package => $package) {
                if (count($packages) == 1 && count($package['carrier_list']) == 1 && current($package['carrier_list']) == 0) {
                    $cache[$this->id] = [];
                    return $cache[$this->id];
                }
                $carriers_price[$id_address][$id_package] = [];
                if (null === $common_carriers) {
                    $common_carriers = $package['carrier_list'];
                } else {
                    $common_carriers = array_intersect($common_carriers, $package['carrier_list']);
                }
                $best_price = null;
                $best_price_carrier = null;
                $best_grade = null;
                $best_grade_carrier = null;
                foreach ($package['carrier_list'] as $id_carrier) {
                    if (!isset($carriers_instance[$id_carrier])) {
                        $carriers_instance[$id_carrier] = new Carrier($id_carrier);
                    }
                    $price_with_tax = $this->getPackageShippingCost((int)$id_carrier, true, $country, $package['product_list']);
                    $price_without_tax = $this->getPackageShippingCost((int)$id_carrier, false, $country, $package['product_list']);
                    if (null === $best_price || $price_with_tax < $best_price) {
                        $best_price = $price_with_tax;
                        $best_price_carrier = $id_carrier;
                    }
                    $carriers_price[$id_address][$id_package][$id_carrier] = [
                        'without_tax' => $price_without_tax,
                        'with_tax' => $price_with_tax,
                    ];
                    $grade = $carriers_instance[$id_carrier]->grade;
                    if (null === $best_grade || $grade > $best_grade) {
                        $best_grade = $grade;
                        $best_grade_carrier = $id_carrier;
                    }
                }
                $best_price_carriers[$id_package] = $best_price_carrier;
                $best_grade_carriers[$id_package] = $best_grade_carrier;
            }
            $best_price_carrier = [];
            $key = '';
            foreach ($best_price_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_price_carrier[$id_carrier])) {
                    $best_price_carrier[$id_carrier] = [
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => [],
                        'product_list' => [],
                    ];
                }
                $best_price_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_price_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_price_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_price_carrier[$id_carrier]['product_list'] = array_merge($best_price_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_price_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
                $real_best_price = !isset($real_best_price) || $real_best_price > $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] ?
                    $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] : $real_best_price;
                $real_best_price_wt = !isset($real_best_price_wt) || $real_best_price_wt > $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] ?
                    $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] : $real_best_price_wt;
            }
            $delivery_option_list[$id_address][$key] = [
                'carrier_list' => $best_price_carrier,
                'is_best_price' => true,
                'is_best_grade' => false,
                'unique_carrier' => (count($best_price_carrier) <= 1),
            ];
            $best_grade_carrier = [];
            $key = '';
            foreach ($best_grade_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_grade_carrier[$id_carrier])) {
                    $best_grade_carrier[$id_carrier] = [
                        'price_with_tax' => 0,
                        'price_without_tax' => 0,
                        'package_list' => [],
                        'product_list' => [],
                    ];
                }
                $best_grade_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_grade_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_grade_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_grade_carrier[$id_carrier]['product_list'] = array_merge($best_grade_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_grade_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
            }
            if (!isset($delivery_option_list[$id_address][$key])) {
                $delivery_option_list[$id_address][$key] = [
                    'carrier_list' => $best_grade_carrier,
                    'is_best_price' => false,
                    'unique_carrier' => (count($best_grade_carrier) <= 1),
                ];
            }
            $delivery_option_list[$id_address][$key]['is_best_grade'] = true;
            foreach ($common_carriers as $id_carrier) {
                $key = '';
                $package_list = [];
                $product_list = [];
                $price_with_tax = 0;
                $price_without_tax = 0;
                foreach ($packages as $id_package => $package) {
                    $key .= $id_carrier . ',';
                    $price_with_tax += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                    $price_without_tax += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                    $package_list[] = $id_package;
                    $product_list = array_merge($product_list, $package['product_list']);
                }
                if (!isset($delivery_option_list[$id_address][$key])) {
                    $delivery_option_list[$id_address][$key] = [
                        'is_best_price' => false,
                        'is_best_grade' => false,
                        'unique_carrier' => true,
                        'carrier_list' => [
                            $id_carrier => [
                                'price_with_tax' => $price_with_tax,
                                'price_without_tax' => $price_without_tax,
                                'instance' => $carriers_instance[$id_carrier],
                                'package_list' => $package_list,
                                'product_list' => $product_list,
                            ],
                        ],
                    ];
                } else {
                    $delivery_option_list[$id_address][$key]['unique_carrier'] = (count($delivery_option_list[$id_address][$key]['carrier_list']) <= 1);
                }
            }
        }
        $cart_rules = CartRule::getCustomerCartRules(Context::getContext()->cookie->id_lang, Context::getContext()->cookie->id_customer, true, true, false, $this, true);
        $result = false;
        if ($this->id) {
            $result = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'cart_cart_rule WHERE id_cart = ' . (int)$this->id);
        }
        $cart_rules_in_cart = [];
        if (is_array($result)) {
            foreach ($result as $row) {
                $cart_rules_in_cart[] = $row['id_cart_rule'];
            }
        }
        $total_products_wt = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $free_carriers_rules = [];
        $context = Context::getContext();
        foreach ($cart_rules as $cart_rule) {
            $total_price = $cart_rule['minimum_amount_tax'] ? $total_products_wt : $total_products;
            $total_price += $cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price : 0;
            $total_price += !$cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price_wt : 0;
            if ($cart_rule['free_shipping'] && $cart_rule['carrier_restriction']
                && in_array($cart_rule['id_cart_rule'], $cart_rules_in_cart)
                && $cart_rule['minimum_amount'] <= $total_price) {
                $cr = new CartRule((int)$cart_rule['id_cart_rule']);
                if (Validate::isLoadedObject($cr) &&
                    $cr->checkValidity($context, in_array((int)$cart_rule['id_cart_rule'], $cart_rules_in_cart), false, false)) {
                    $carriers = $cr->getAssociatedRestrictions('carrier', true, false);
                    if (is_array($carriers) && count($carriers) && isset($carriers['selected'])) {
                        foreach ($carriers['selected'] as $carrier) {
                            if (isset($carrier['id_carrier']) && $carrier['id_carrier']) {
                                $free_carriers_rules[] = (int)$carrier['id_carrier'];
                            }
                        }
                    }
                }
            }
        }
        foreach ($delivery_option_list as $id_address => $delivery_option) {
            foreach ($delivery_option as $key => $value) {
                $total_price_with_tax = 0;
                $total_price_without_tax = 0;
                $position = 0;
                foreach ($value['carrier_list'] as $id_carrier => $data) {
                    $total_price_with_tax += $data['price_with_tax'];
                    $total_price_without_tax += $data['price_without_tax'];
                    $total_price_without_tax_with_rules = (in_array($id_carrier, $free_carriers_rules)) ? 0 : $total_price_without_tax;
                    if (!isset($carrier_collection[$id_carrier])) {
                        $carrier_collection[$id_carrier] = new Carrier($id_carrier);
                    }
                    $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['instance'] = $carrier_collection[$id_carrier];
                    if (file_exists(_PS_SHIP_IMG_DIR_ . $id_carrier . '.jpg')) {
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = _THEME_SHIP_DIR_ . $id_carrier . '.jpg';
                    } else {
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = false;
                    }
                    $position += $carrier_collection[$id_carrier]->position;
                }
                $delivery_option_list[$id_address][$key]['total_price_with_tax'] = $total_price_with_tax;
                $delivery_option_list[$id_address][$key]['total_price_without_tax'] = $total_price_without_tax;
                $delivery_option_list[$id_address][$key]['is_free'] = !$total_price_without_tax_with_rules ? true : false;
                $delivery_option_list[$id_address][$key]['position'] = $position / count($value['carrier_list']);
            }
        }
        foreach ($delivery_option_list as &$array) {
            uasort($array, ['Cart', 'sortDeliveryOptionList']);
        }
        static::$cacheDeliveryOptionList[$this->id] = $delivery_option_list;
        return static::$cacheDeliveryOptionList[$this->id];
    }

    /*
    * module: frozenshipping
    * date: 2022-04-01 06:55:17
    * version: 1.0.0
    */
    public function getPackageList($flush = false)
    {
        $cache_key = (int)$this->id . '_' . (int)$this->id_address_delivery;
        if (isset(static::$cachePackageList[$cache_key]) && static::$cachePackageList[$cache_key] !== false && !$flush) {
            return static::$cachePackageList[$cache_key];
        }
        $product_list = $this->getProducts($flush);
        $warehouse_count_by_address = [];
        $stock_management_active = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');
        foreach ($product_list as &$product) {
            if ((int)$product['id_address_delivery'] == 0) {
                $product['id_address_delivery'] = (int)$this->id_address_delivery;
            }
            if (!isset($warehouse_count_by_address[$product['id_address_delivery']])) {
                $warehouse_count_by_address[$product['id_address_delivery']] = [];
            }
            $product['warehouse_list'] = [];
            if ($stock_management_active &&
                (int)$product['advanced_stock_management'] == 1) {
                $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute'], $this->id_shop);
                if (count($warehouse_list) == 0) {
                    $warehouse_list = Warehouse::getProductWarehouseList($product['id_product'], $product['id_product_attribute']);
                }
                $warehouse_in_stock = [];
                $manager = StockManagerFactory::getManager();
                foreach ($warehouse_list as $key => $warehouse) {
                    $product_real_quantities = $manager->getProductRealQuantities(
                        $product['id_product'],
                        $product['id_product_attribute'],
                        [$warehouse['id_warehouse']],
                        true
                    );
                    if ($product_real_quantities > 0 || Pack::isPack((int)$product['id_product'])) {
                        $warehouse_in_stock[] = $warehouse;
                    }
                }
                if (!empty($warehouse_in_stock)) {
                    $warehouse_list = $warehouse_in_stock;
                    $product['in_stock'] = true;
                } else {
                    $product['in_stock'] = false;
                }
            } else {
                $warehouse_list = [0 => ['id_warehouse' => 0]];
                $product['in_stock'] = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']) > 0;
            }
            foreach ($warehouse_list as $warehouse) {
                $product['warehouse_list'][$warehouse['id_warehouse']] = $warehouse['id_warehouse'];
                if (!isset($warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']])) {
                    $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']] = 0;
                }
                ++$warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']];
            }
        }
        unset($product);
        arsort($warehouse_count_by_address);
        $grouped_by_warehouse = [];
        foreach ($product_list as &$product) {
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']])) {
                $grouped_by_warehouse[$product['id_address_delivery']] = [
                    'in_stock' => [],
                    'out_of_stock' => [],
                ];
            }
            $product['carrier_list'] = [];
            $id_warehouse = 0;
            foreach ($warehouse_count_by_address[$product['id_address_delivery']] as $id_war => $val) {
                if (array_key_exists((int)$id_war, $product['warehouse_list'])) {
                    $product['carrier_list'] = array_replace($product['carrier_list'], Carrier::getAvailableCarrierList(new Product($product['id_product']), $id_war, $product['id_address_delivery'], null, $this));
                    if (!$id_warehouse) {
                        $id_warehouse = (int)$id_war;
                    }
                }
            }
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse])) {
                $grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse] = [];
                $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse] = [];
            }
            if (!$this->allow_seperated_package) {
                $key = 'in_stock';
            } else {
                $key = $product['in_stock'] ? 'in_stock' : 'out_of_stock';
                $product_quantity_in_stock = StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute']);
                if ($product['in_stock'] && $product['cart_quantity'] > $product_quantity_in_stock) {
                    $out_stock_part = $product['cart_quantity'] - $product_quantity_in_stock;
                    $product_bis = $product;
                    $product_bis['cart_quantity'] = $out_stock_part;
                    $product_bis['in_stock'] = 0;
                    $product['cart_quantity'] -= $out_stock_part;
                    $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse][] = $product_bis;
                }
            }
            if (empty($product['carrier_list'])) {
                $product['carrier_list'] = [0 => 0];
            }
            $grouped_by_warehouse[$product['id_address_delivery']][$key][$id_warehouse][] = $product;
        }
        unset($product);
        $grouped_by_carriers = [];
        foreach ($grouped_by_warehouse as $id_address_delivery => $products_in_stock_list) {
            if (!isset($grouped_by_carriers[$id_address_delivery])) {
                $grouped_by_carriers[$id_address_delivery] = [
                    'in_stock' => [],
                    'out_of_stock' => [],
                ];
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($grouped_by_carriers[$id_address_delivery][$key])) {
                    $grouped_by_carriers[$id_address_delivery][$key] = [];
                }
                foreach ($warehouse_list as $id_warehouse => $product_list) {
                    if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse])) {
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse] = [];
                    }
                    foreach ($product_list as $product) {
                        $package_carriers_key = implode(',', $product['carrier_list']);
                        if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key])) {
                            $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key] = [
                                'product_list' => [],
                                'carrier_list' => $product['carrier_list'],
                                'warehouse_list' => $product['warehouse_list'],
                            ];
                        }
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key]['product_list'][] = $product;
                    }
                }
            }
        }
        $package_list = [];
        foreach ($grouped_by_carriers as $id_address_delivery => $products_in_stock_list) {
            if (!isset($package_list[$id_address_delivery])) {
                $package_list[$id_address_delivery] = [
                    'in_stock' => [],
                    'out_of_stock' => [],
                ];
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($package_list[$id_address_delivery][$key])) {
                    $package_list[$id_address_delivery][$key] = [];
                }
                $carrier_count = [];
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($data['carrier_list'] as $id_carrier) {
                            if (!isset($carrier_count[$id_carrier])) {
                                $carrier_count[$id_carrier] = 0;
                            }
                            ++$carrier_count[$id_carrier];
                        }
                    }
                }
                arsort($carrier_count);
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    if (!isset($package_list[$id_address_delivery][$key][$id_warehouse])) {
                        $package_list[$id_address_delivery][$key][$id_warehouse] = [];
                    }
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($carrier_count as $id_carrier => $rate) {
                            if (array_key_exists($id_carrier, $data['carrier_list'])) {
                                if (!isset($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier])) {
                                    $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier] = [
                                        'carrier_list' => $data['carrier_list'],
                                        'warehouse_list' => $data['warehouse_list'],
                                        'product_list' => [],
                                    ];
                                }
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'] =
                                    array_intersect($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'], $data['carrier_list']);
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'] =
                                    array_merge($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'], $data['product_list']);
                                break;
                            }
                        }
                    }
                }
            }
        }

        $frozen_carriers = $this->filterCarriersByFrozenProducts();

        $final_package_list = [];
        foreach ($package_list as $id_address_delivery => $products_in_stock_list) {
            if (!isset($final_package_list[$id_address_delivery])) {
                $final_package_list[$id_address_delivery] = [];
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        if (isset($frozen_carriers)) {
                            $carrier_diffs = array_diff($data['carrier_list'], $frozen_carriers);
                            foreach ($carrier_diffs as $diff) {
                                unset($data['carrier_list'][$diff]);
                            }
                        }
                        $final_package_list[$id_address_delivery][] = [
                            'product_list' => $data['product_list'],
                            'carrier_list' => $data['carrier_list'],
                            'warehouse_list' => $data['warehouse_list'],
                            'id_warehouse' => $id_warehouse,
                        ];
                    }
                }
            }
        }
        static::$cachePackageList[$cache_key] = $final_package_list;
        return $final_package_list;
    }

    /*
     * To Show every time the appropriate carriers
     */
    public function filterCarriersByFrozenProducts()
    {
        $show_frozen_carriers = 0;
        if (Context::getContext()->cookie->__isset('show_frozen_carriers')) {
            $show_frozen_carriers = Context::getContext()->cookie->__get('show_frozen_carriers');
        }
        $filtered_carriers = Db::getInstance()->executeS("Select id_carrier from ps_carrier where active = 1 AND deleted = 0 AND frozen_shipping = " . $show_frozen_carriers);
        if (isset($filtered_carriers)) {
            $filtered_carriers = array_column($filtered_carriers, 'id_carrier');
        }
        return $filtered_carriers;
    }
}