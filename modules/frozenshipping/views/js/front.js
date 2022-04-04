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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

if(typeof(AJAX_URL) != 'undefined'){
    $('.frozenshipping_module .js-frozen-carrier').on('click', function(){
        var id = 0;
        var reference = 0;
        var price = 0;
        var price_with_tax = 0;

        $('.js-frozen-carrier[name="delivery_option[]"]').each(function(){
            if($(this).is(':checked')){
                id = $(this).val();
                reference = $(this)[0].dataset.reference;
                price = $(this)[0].dataset.price;
                price_with_tax = $(this)[0].dataset.pricewithtax;
            }
    });

        $.ajax({
            url : AJAX_URL,
            type : 'POST',
            async: true,
            dataType : "json",
            data: {
                action: 'checkCarrier',
                special_carrier: {
                    'id': id,
                    'reference': reference,
                    'price': price,
                    'price_with_tax': price_with_tax
                },
                ajax: 1
            },
            success : function (result) {
                $('#js-checkout-summary').replaceWith(result.preview);
            },
            error : function (error) {
                console.log('error');
            },
        });
    });
}