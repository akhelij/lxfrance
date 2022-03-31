<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Frozenshipping extends Module
{
    protected $config_form = false;
    protected $has_frozen_products = false;

    public function __construct()
    {
        $this->name = 'frozenshipping';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'lxfrance';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('frozen products shipping');
        $this->description = $this->l('This module define a shipping method for a certain category of products');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('FROZENSHIPPING_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionCarrierProcess') &&
            $this->registerHook('displayAfterCarrier') &&
            $this->registerHook('displayBeforeCarrier') &&
            $this->registerHook('displayCarrierList') &&
            $this->registerHook('extraCarrier') &&
            $this->registerHook('displayOrderConfirmation');
    }

    public function uninstall()
    {
        Configuration::deleteByName('FROZENSHIPPING_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitFrozenshippingModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitFrozenshippingModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $categories = Category::getCategories((int)$this->context->language->id, true, false);
        $carriers = Carrier::getCarriers((int)$this->context->language->id, true);

        $config = Configuration::get('CUSTOMSHIPPING_CARRIERS_CATEGORIES', "0::0,0");
        list($id_category, $id_carriers) = explode('::',unserialize($config));

        $category = $categories[array_search($id_category, array_column($categories, "id_category"))];

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $this->l('Live mode'),
                    //     'name' => 'CUSTOMSHIPPING_LIVE_MODE',
                    //     'is_bool' => true,
                    //     'desc' => $this->l('Use this module in live mode'),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => true,
                    //             'label' => $this->l('Enabled')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => false,
                    //             'label' => $this->l('Disabled')
                    //         )
                    //     ),
                    // ),
                    [
                        'type' => 'select',
                        'name' => 'id_category',
                        'label' => $this->trans('Category'),
                        'options' => [
                            'query' => $categories,
                            'id' => 'id_category',
                            'name' => 'name',
                            'default' => [
                                'label' => $category["name"],
                                'value' => $category["id_category"],
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'id_carriers[]',
                        'id' => 'id_carriers',
                        'label' => $this->trans('Choose Carriers :'),
                        'multiple' => true,
                        'options' => [
                            'query' => $carriers,
                            'id' => 'id_carrier',
                            'name' => 'name',
                        ],                        
                    ],
                    
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $config = Configuration::get('CUSTOMSHIPPING_CARRIERS_CATEGORIES', "0::0,0");
        list($id_category, $id_carriers) = explode('::',unserialize($config));
        return array(            
            'id_carriers[]' => explode(',', $id_carriers),
            // 'FROZENSHIPPING_LIVE_MODE' => Configuration::get('FROZENSHIPPING_LIVE_MODE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        //Save config as {category_id::carrier_id1,carrier_id2}
        Configuration::updateValue('CUSTOMSHIPPING_CARRIERS_CATEGORIES', serialize(Tools::getValue('id_category')."::".implode(',',Tools::getValue('id_carriers'))));
        
        // $form_values = $this->getConfigFormValues();
        // foreach (array_keys($form_values) as $key) {
        //     Configuration::updateValue($key, Tools::getValue($key));
        // }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayCarrierList()
    {
        /* Place your code here. */
    }

    public function hookDisplayOrderConfirmation()
    {
        /* Place your code here. */
    }

    public function hookDisplayBeforeCarrier($params)
    {
        $selected_carrier = null;

        $config = Configuration::get('CUSTOMSHIPPING_CARRIERS_CATEGORIES', "0::0,0");
        list($id_category, $id_carriers) = explode('::',unserialize($config));
        
        foreach($this->context->cart->getProducts() as $product)
        {
            if($this->has_frozen_products = in_array($id_category, (new Product($product))->getCategories()))
            {
                break;
            }
        }

        if($this->has_frozen_products)
        {
            if($this->context->cookie->__isset('special_carrier'))
            {
                $selected_carrier = $this->context->cookie->__get('special_carrier');
            }
            $special_carriers = [];
            // $carriers = Carrier::getCarriers((int)$this->context->language->id, true);

            foreach ($this->getDeliveryOptions() as $carrier) {
                if(in_array($carrier['id'], explode(',', $id_carriers)))
                {
                    array_push($special_carriers, $carrier);
                }
            }

            // die(dump($special_carriers));
            $this->context->smarty->assign(
                array(
                    'carriers' => $special_carriers,
                    'special_carrier'  => $selected_carrier
                )
            );

            return $this->display(__FILE__, 'views/templates/custom.tpl');
        } else {
            $this->context->cookie->__unset('special_carrier');
        }
    }

    public function hookActionCarrierProcess($params)
    {
        // Generating link to our ajax method;
        $ajax_link = Context::getContext()->link->getModuleLink($this->name, 'ajax');        
        Media::addJsDef([
            'AJAX_URL' => $ajax_link,
        ]);
        
        // if (isset($params['cart']->id_carrier)) {
        //     $carrier_name = Db::getInstance()->getValue('SELECT name FROM `'._DB_PREFIX_.'carrier` WHERE id_carrier = '.(int)$params['cart']->id_carrier);
        //     $this->_manageData('MBG.addCheckoutOption(2,\''.$carrier_name.'\');', 'A');
        //     var_dump($_POST);
        //     var_dump(Tools::getValue($_POST));
        //     //die();
        // }
    }

    public function hookDisplayAfterCarrier($params)
    {
        /* Place your code here. */       
        
        // die(dump($params));
        // Configuration::updateValue('custom_text', 'xxxx');
        // $this->context->smarty->assign(
        //     array('custom_text' => Configuration::get('custom_text'))
        // );
        // return $this->display(__FILE__, 'views/templates/custom.tpl');
    }

    public function hookExtraCarrier($params)
    {
       /* Place your code here. */
    }

    public function getDeliveryOptions()
    {
        $delivery_option_list = $this->context->cart->getDeliveryOptionList();
        $include_taxes = !Product::getTaxCalculationMethod((int) $this->context->cart->id_customer) && (int) Configuration::get('PS_TAX');
        $display_taxes_label = (Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'));

        $carriers_available = [];

        if (isset($delivery_option_list[$this->context->cart->id_address_delivery])) {
            foreach ($delivery_option_list[$this->context->cart->id_address_delivery] as $id_carriers_list => $carriers_list) {
                foreach ($carriers_list as $carriers) {
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrier) {
                            $carrier = array_merge($carrier, (new ObjectPresenter)->present($carrier['instance']));
                            $delay = $carrier['delay'][$this->context->language->id];
                            unset($carrier['instance'], $carrier['delay']);
                            $carrier['delay'] = $delay;
                            if ($this->isFreeShipping($this->context->cart, $carriers_list)) {
                                $carrier['price'] = $this->trans(
                                    'Free',
                                    [],
                                    'Shop.Theme.Checkout'
                                );
                            } else {
                                if ($include_taxes) {
                                    $carrier['price'] = (new PriceFormatter)->format($carriers_list['total_price_with_tax']);
                                    if ($display_taxes_label) {
                                        $carrier['price'] = $this->trans(
                                            '%price% tax incl.',
                                            ['%price%' => $carrier['price']],
                                            'Shop.Theme.Checkout'
                                        );
                                    }
                                } else {
                                    $carrier['price'] = (new PriceFormatter)->format($carriers_list['total_price_without_tax']);
                                    if ($display_taxes_label) {
                                        $carrier['price'] = $this->trans(
                                            '%price% tax excl.',
                                            ['%price%' => $carrier['price']],
                                            'Shop.Theme.Checkout'
                                        );
                                    }
                                }
                            }

                            if (count($carriers) > 1) {
                                $carrier['label'] = $carrier['price'];
                            } else {
                                $carrier['label'] = $carrier['name'] . ' - ' . $carrier['delay'] . ' - ' . $carrier['price'];
                            }

                            // If carrier related to a module, check for additionnal data to display
                            $carrier['extraContent'] = '';
                            if ($carrier['is_module']) {
                                if ($moduleId = Module::getModuleIdByName($carrier['external_module_name'])) {
                                    $carrier['extraContent'] = Hook::exec('displayCarrierExtraContent', ['carrier' => $carrier], $moduleId);
                                }
                            }

                            $carriers_available[$id_carriers_list] = $carrier;
                        }
                    }
                }
            }
        }

        return $carriers_available;
    }

    private function isFreeShipping($cart, array $carrier)
    {
        $free_shipping = false;

        if ($carrier['is_free']) {
            $free_shipping = true;
        } else {
            foreach ($cart->getCartRules() as $rule) {
                if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                    $free_shipping = true;

                    break;
                }
            }
        }

        return $free_shipping;
    }
}
