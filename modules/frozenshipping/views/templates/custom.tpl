<div id="custom-text">
    <div class="delivery-options mogh">
        {foreach from=$delivery_options item=carrier key=carrier_id}                  
            {if $carrier.id_carrier == 5 || $carrier.id_carrier == 11}
            <div class="row delivery-option">
            <div class="col-sm-1">
                <span class="custom-radio float-xs-left">
                <input type="radio" class="js-frozen-carrier" name="delivery_option[]" id="delivery_option_{$carrier.id_carrier}" value="{$carrier.id_carrier}"{if $delivery_option == $carrier.id_carrier} checked{/if}>
                <span></span>
                </span>
            </div>
            <label for="delivery_option_{$carrier.id_carrier}" class="col-xs-9 col-sm-11 delivery-option-2">
                <div class="row">
                <div class="col-sm-5 col-xs-12">
                    <div class="row carrier{if $carrier.logo} carrier-hasLogo{/if}">
                    {if $carrier.logo}
                    <div class="col-xs-12 col-md-4 carrier-logo">
                        <img src="{$carrier.logo}" alt="{$carrier.name}" />
                    </div>
                    {/if}
                    <div class="col-xs-12 carriere-name-container{if $carrier.logo} col-md-8{/if}">
                        <span class="h6 carrier-name">{$carrier.name}</span>
                    </div>
                    </div>
                </div>
                <div class="col-sm-4 col-xs-12">
                    <span class="carrier-delay">{$carrier.delay}</span>
                </div>
                <div class="col-sm-3 col-xs-12">
                    <span class="carrier-price">{$carrier.price}</span>
                </div>
                </div>
            </label>
            </div>
            <div class="row carrier-extra-content"{if $delivery_option != $id_carrier} style="display:none;"{/if}>
            {$carrier.extraContent nofilter}
            </div>
            <div class="clearfix"></div>
            {/if}
        {/foreach}            
    </div>    
</div>