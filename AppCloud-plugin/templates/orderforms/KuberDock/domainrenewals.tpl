<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/css/style.css" />

<div id="order-modern">

<h1>{$LANG.domainrenewals}</h1>
<div align="center"><a href="#" onclick="showcats();return false;">({$LANG.cartchooseanothercategory})</a></div>

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

<br />

<p>{$LANG.domainrenewdesc}</p>

<form method="post" action="cart.php?a=add&renewals=true">

<table align="center" cellspacing="1" class="renewals">
<tr><th width="20"></th><th>{$LANG.orderdomain}</th><th>{$LANG.domainstatus}</th><th>{$LANG.domaindaysuntilexpiry}</th><th></th></tr>
{foreach from=$renewals item=renewal}
<tr><td>{if !$renewal.pastgraceperiod}<input type="checkbox" name="renewalids[]" value="{$renewal.id}" />{/if}</td><td>{$renewal.domain}</td><td>{$renewal.status}</td><td>
    {if $renewal.daysuntilexpiry > 30}
    <span class="textgreen">{$renewal.daysuntilexpiry} {$LANG.domainrenewalsdays}</span>
    {elseif $renewal.daysuntilexpiry > 0}
    <span class="textred">{$renewal.daysuntilexpiry} {$LANG.domainrenewalsdays}</span>
    {else}
    <span class="textblack">{$renewal.daysuntilexpiry*-1} {$LANG.domainrenewalsdaysago}</span>
    {/if}
    {if $renewal.ingraceperiod}
    <br />
    <span class="textred">{$LANG.domainrenewalsingraceperiod}<span>
    {/if}
</td><td>
    {if $renewal.beforerenewlimit}
    <span class="textred">{$LANG.domainrenewalsbeforerenewlimit|sprintf2:$renewal.beforerenewlimitdays}<span>
    {elseif $renewal.pastgraceperiod}
    <span class="textred">{$LANG.domainrenewalspastgraceperiod}<span>
    {else}
    <select name="renewalperiod[{$renewal.id}]">
    {foreach from=$renewal.renewaloptions item=renewaloption}
        <option value="{$renewaloption.period}">{$renewaloption.period} {$LANG.orderyears} @ {$renewaloption.price}</option>
    {/foreach}
    </select>
    {/if}
</td></tr>
{foreachelse}
<tr class="carttablerow"><td colspan="5">{$LANG.domainrenewalsnoneavailable}</td></tr>
{/foreach}
</table>

<p align="center"><input type="submit" value="{$LANG.ordernowbutton} &raquo;" /></p>

</form>

<br />

<p align="center"><input type="button" value="{$LANG.viewcart}" onclick="window.location='cart.php?a=view'" /></p>

</div>