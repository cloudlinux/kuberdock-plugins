<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">
    <div class="page-header">
        <div class="styled_title">
            <h1>{$groupname}</h1>
        </div>
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

    {if !$loggedin && $currencies}
        <br/>
        <div id="currencychooser">
            {foreach from=$currencies item=curr}
                <a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
            {/foreach}
        </div>
        <div class="clear"></div>
    {/if}

{foreach from=$products key=num item=product}
<div class="products {$num}">
<div class="product" id="product{$num}" onclick="window.location='cart.php?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}'">


<div class="name">
    <span>{$product.name}{if $product.qty!=""}</span> <span class="qty">({$product.qty} {$LANG.orderavailable})</span>{/if}
</div>

<div class="left-side">
    {foreach from=$product.features key=feature item=value}
    <span class="prodfeature"><span class="feature">{$feature}</span><br />{$value}</span>
    {/foreach}
</div>


<div class="right-side">
    <div class="pricing">
        {if $product.bid}
            {$LANG.bundledeal}<br />
            {if $product.displayprice}<span class="pricing">{$product.displayprice}</span>{/if}
            {else}
            {if $product.pricing.hasconfigoptions}{$LANG.startingfrom}<br />{/if}
            {if $product.pricing.minprice.cycle eq "monthly"}
                {$LANG.orderpaymenttermmonthly}
            {elseif $product.pricing.minprice.cycle eq "quarterly"}
                    {$LANG.orderpaymenttermquarterly}
            {elseif $product.pricing.minprice.cycle eq "semiannually"}
                    {$LANG.orderpaymenttermsemiannually}
            {elseif $product.pricing.minprice.cycle eq "annually"}
                    {$LANG.orderpaymenttermannually}
            {elseif $product.pricing.minprice.cycle eq "biennially"}
                    {$LANG.orderpaymenttermbiennially}
            {elseif $product.pricing.minprice.cycle eq "triennially"}
                    {$LANG.orderpaymenttermtriennially}
            {/if}
            <span class="pricing">{$product.pricing.minprice.price}</span>
        {/if}
    </div>
    <form method="post" action="cart.php?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}">
        <div class="ordernowbox"><input class="cart-button" type="submit" value="{$LANG.ordernowbutton} &raquo;" class="ordernow" /></div>
    </form>
</div>

<div class="clear"></div>
<div class="description">{$product.featuresdesc}</div>

</div>
</div>
{if $num % 2}<div class="clear"></div>
{/if}
{/foreach}

<div class="clear"></div>

{if !$loggedin && $currencies}
<div id="currencychooser">
{foreach from=$currencies item=curr}
<a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
{/foreach}
</div>
<div class="clear"></div>
{/if}

<br />
<br />

</div>