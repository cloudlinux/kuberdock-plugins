<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">
    <div class="page-header">
        <div class="styled_title"><h1>{if $domain eq "register"}{$LANG.registerdomain}{elseif $domain eq "transfer"}{$LANG.transferdomain}{/if}</h1></div>
    </div>

    <div class="container-padding-default">
        <div class="textcenter domainsearch-links">
            <div id="categories">
                {foreach key=num item=productgroup from=$productgroups}
                    {if $productgroup.gid neq $gid}
                        <a href="cart.php?gid={$productgroup.gid}">{$productgroup.name}</a>
                    {/if}
                {/foreach}
                {if $loggedin}
                    {if $gid neq "addons"}
                        <a href="cart.php?gid=addons">{$LANG.cartproductaddons}</a>
                    {/if}
                    {if $renewalsenabled && $gid neq "renewals"}
                        <a href="cart.php?gid=renewals">{$LANG.domainrenewals}</a>
                    {/if}
                {/if}
                {if $registerdomainenabled && $domain neq "register"}
                    <a href="cart.php?a=add&domain=register">{$LANG.registerdomain}</a>
                {/if}
                {if $transferdomainenabled && $domain neq "transfer"}
                    <a href="cart.php?a=add&domain=transfer">{$LANG.transferdomain}</a>
                {/if}
                <a href="cart.php?a=view">{$LANG.viewcart}</a>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    {if !$loggedin && $currencies}
    <br/>
        <div id="currencychooser">
            {foreach from=$currencies item=curr}
                <a href="cart.php?a=add&domain={$domain}&currency={$curr.id}">
                <img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
            {/foreach}
        </div>
        <div class="clear"></div>
    {/if}

    <div class="well textcenter search-domain" style="margin-top: 25px;">
        <div class="styled_title">
            {if $domain eq "register"}{$LANG.registerdomaindesc}{else}{$LANG.transferdomaindesc}{/if}
        </div>
        {if $errormessage}
            <div class="errorbox">{$errormessage|replace:'<li>':' &nbsp;#&nbsp; '} &nbsp;#&nbsp; </div><br />
        {/if}
        <form onsubmit="checkavailability();return false">
            <div class="textcenter">
                www. <input type="text" name="sld" id="sld" size="25" value="{$sld}" class="thame1" />
                <select class="thame1" name="tld" id="tld">
                    {foreach key=num item=listtld from=$tlds}
                        <option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
                    {/foreach}
                </select>
                <input type="submit" class="send-message" value=" {$LANG.checkavailability} " />
            </div>
        </form>
    </div>

    <div id="loading" class="loading">
        <img src="images/loading.gif" border="0" alt="{$LANG.loading}" />
    </div>

    <form method="post" action="cart.php?a=add&domain={$domain}">
        <div id="domainresults"></div>
    </form>

    {literal}
    <script language="javascript">
    function checkavailability() {
        jQuery("#loading").slideDown();
        jQuery.post("cart.php", { ajax: 1, a: "domainoptions", sld: jQuery("#sld").val(), tld: jQuery("#tld").val(), checktype: '{/literal}{$domain}{literal}' },
        function(data){
            jQuery("#domainresults").html(data);
            jQuery("#domainresults").slideDown();
            jQuery("#loading").slideUp();
        });
    }
    function cancelcheck() {
        jQuery("#domainresults").slideUp();
    }{/literal}
    {if $sld}{literal}
    jQuery(document).ready(function(){
        checkavailability();
    });
    {/literal}{/if}
    </script>
</div>