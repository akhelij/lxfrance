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


// if(typeof(AJAX_URL) != 'undefined'){
//     console.info(AJAX_URL);
    $('.mogh .js-frozen-carrier').on('click', function(){
        
        var selected_carrier = 0;

        $('.js-frozen-carrier[name="delivery_option[]"]').each(function(){
    
            // console.info($(this).val());
            // console.info($(this).is(':checked'));
            if($(this).is(':checked')){
                selected_carrier = $(this).val();
                console.info(selected_carrier);
            }
    });

        $.ajax({
            // url : AJAX_URL,
            url: 'http://lxfrance.test/en/module/frozenshipping/ajax',
            type : 'POST',
            async: true,
            dataType : "json",
            data: {
                action: 'checkCarrier',
                selected_carrier: selected_carrier,
                ajax: 1
            },
            success : function (result) {
                console.log(result);
                // $('#lnk_products_content').html(result);
            },
            error : function (error) {
                // console.log(error);
                console.log('error');
            },
        });
        // console.log(categoryId);
        
    });
// }