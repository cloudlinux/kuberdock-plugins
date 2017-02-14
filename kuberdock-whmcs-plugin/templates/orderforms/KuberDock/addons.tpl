<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">
    <div class="page-header">
        <div class="styled_title"><h1>{$LANG.cartproductaddons}</h1></div>
    </div>

    <div class="container-padding-default row-down">
        <div class="textcenter domainsearch-links">
            <div id="categories">
                {foreach key=num item=productgroup from=$productgroups}
                    {if $productgroup.gid neq $gid}<a href="cart.php?gid={$productgroup.gid}">{$productgroup.name}</a>{/if}
                {/foreach}
                {if $loggedin}
                    {if $gid neq "addons"}<a href="cart.php?gid=addons">{$LANG.cartproductaddons}</a>{/if}
                    {if $renewalsenabled && $gid neq "renewals"}<a href="cart.php?gid=renewals">{$LANG.domainrenewals}</a>{/if}
                {/if}
                {if $registerdomainenabled && $domain neq "register"}<a href="cart.php?a=add&domain=register">{$LANG.registerdomain}</a>{/if}
                {if $transferdomainenabled && $domain neq "transfer"}<a href="cart.php?a=add&domain=transfer">{$LANG.transferdomain}</a>{/if}
                <a href="cart.php?a=view">{$LANG.viewcart}</a>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    <br />

    {foreach from=$addons item=addon}
        <div class="product">
            <form method="post" action="{$smarty.server.PHP_SELF}?a=add">
                <input type="hidden" name="aid" value="{$addon.id}" />
                <div class="pricing">
                    {if $addon.free}
                        {$LANG.orderfree}
                    {else}
                        <span class="pricing">{$addon.recurringamount} {$addon.billingcycle}</span>
                        {if $addon.setupfee}<br />+ {$addon.setupfee} {$LANG.ordersetupfee}{/if}
                    {/if}
                </div>
                <div class="name">{$addon.name}</div>
                <div class="description">{$addon.description}</div>
                <div class="ordernowbox">
                    <select name="productid">
                        {foreach from=$addon.productids item=product}
                            <option value="{$product.id}">{$product.product}{if $product.domain} - {$product.domain}{/if}</option>
                        {/foreach}
                    </select>
                    <input type="submit" value="{$LANG.ordernowbutton}" class="ordernow" />
                </div>
            </form>
        </div>
    {/foreach}

    {if $noaddons}
        <div class="errorbox" style="display:block;">{$LANG.cartproductaddonsnone}</div>
    {/if}

    <br />
    <div class="textcenter">
        <input class="send-message" type="button" value="{$LANG.viewcart}" onclick="window.location='cart.php?a=view'" />
    </div>
    <br/>

</div>