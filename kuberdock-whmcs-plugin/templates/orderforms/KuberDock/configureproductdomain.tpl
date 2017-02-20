<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />

<div id="order-modern">

<h1>{$LANG.cartproductselection}: {$productinfo.groupname} - {$productinfo.name}</h1>

<p>{$LANG.cartmakedomainselection}</p>

<form onsubmit="checkdomain();return false">

<div class="domainoptions">
{if $incartdomains}
<div class="option">
<label><input type="radio" name="domainoption" value="incart" id="selincart" />{$LANG.cartproductdomainuseincart}</label>
<div class="domainreginput" id="domainincart">
<select id="incartsld">
{foreach key=num item=incartdomain from=$incartdomains}
<option value="{$incartdomain}">{$incartdomain}</option>
{/foreach}
</select> <input type="submit" value="{$LANG.ordercontinuebutton}" />
</div>
</div>
{/if}
{if $registerdomainenabled}
<div class="option">
<label><input type="radio" name="domainoption" value="register" id="selregister" />{$LANG.cartregisterdomainchoice|sprintf2:$companyname}</label>
<div class="domainreginput" id="domainregister">
www. <input type="text" id="registersld" size="30" value="{$sld}" /> <select id="registertld">
{foreach key=num item=listtld from=$registertlds}
<option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
{/foreach}
</select> <input type="submit" value="{$LANG.checkavailability}" />
</div>
</div>
{/if}
{if $transferdomainenabled}
<div class="option">
<label><input type="radio" name="domainoption" value="transfer" id="seltransfer" />{$LANG.carttransferdomainchoice|sprintf2:$companyname}</label>
<div class="domainreginput" id="domaintransfer">
www. <input type="text" id="transfersld" size="30" value="{$sld}" /> <select id="transfertld">
{foreach key=num item=listtld from=$transfertlds}
<option value="{$listtld}"{if $listtld eq $tld} selected="selected"{/if}>{$listtld}</option>
{/foreach}
</select> <input type="submit" value="{$LANG.checkavailability}" />
</div>
</div>
{/if}
{if $owndomainenabled}
<div class="option">
<label><input type="radio" name="domainoption" value="owndomain" id="selowndomain" />{$LANG.cartexistingdomainchoice|sprintf2:$companyname}</label>
<div class="domainreginput" id="domainowndomain">
www. <input type="text" id="owndomainsld" size="30" value="{$sld}" /> . <input type="text" id="owndomaintld" size="5" value="{$tld|substr:1}" /> <input type="submit" value="{$LANG.ordercontinuebutton}" />
</div>
</div>
{/if}
{if $subdomains}
<div class="option">
<label><input type="radio" name="domainoption" value="subdomain" id="selsubdomain" />{$LANG.cartsubdomainchoice|sprintf2:$companyname}</label>
<div class="domainreginput" id="domainsubdomain">
http:// <input type="text" id="subdomainsld" size="30" value="{$sld}" /> <select id="subdomaintld">{foreach from=$subdomains key=subid item=subdomain}<option value="{$subid}">{$subdomain}</option>{/foreach}</select> <input type="submit" value="{$LANG.ordercontinuebutton}" />
</div>
</div>
{/if}
</div>

{if $freedomaintlds}<p>* <em>{$LANG.orderfreedomainregistration} {$LANG.orderfreedomainappliesto}: {$freedomaintlds}</em></p>{/if}

</form>

<div id="greyout"></div>
<div id="domainpopupcontainer">
<form id="domainfrm" onsubmit="completedomain();return false">
<div class="domainresults" id="domainresults"><img src="images/loading.gif" border="0" alt="Loading..." /></div>
</form>
</div>

<div id="prodconfigcontainer"></div>

</div>

{literal}
<script language="javascript">
jQuery(".domainreginput").hide();
jQuery(".domainoptions input:first").attr('checked','checked');
jQuery(".domainoptions input:first").parent().parent().addClass('optionselected');
jQuery("#domain"+jQuery(".domainoptions input:first").val()).show();
jQuery(document).ready(function(){
    jQuery(".domainoptions input:radio").click(function(){
        jQuery(".domainoptions .option").removeClass('optionselected');
        jQuery(this).parent().parent().addClass('optionselected');
        jQuery("#domainresults").slideUp();
        jQuery(".domainreginput").hide();
        jQuery("#domain"+jQuery(this).val()).show();
    });
});
function checkdomain() {
    jQuery("#greyout").fadeIn();
    jQuery("#domainpopupcontainer").slideDown();
    var domainoption = jQuery(".domainoptions input:checked").val();
    var sld = jQuery("#"+domainoption+"sld").val();
    var tld = '';
    if (domainoption=='incart') var sld = jQuery("#"+domainoption+"sld option:selected").text();
    if (domainoption=='subdomain') var tld = jQuery("#"+domainoption+"tld option:selected").text();
    else var tld = jQuery("#"+domainoption+"tld").val();
    jQuery.post("cart.php", { ajax: 1, a: "domainoptions", sld: sld, tld: tld, checktype: domainoption },
    function(data){
        jQuery("#domainresults").html(data);
    });
}
function cancelcheck() {
    jQuery("#domainpopupcontainer").slideUp('slow',function() {
        jQuery("#greyout").fadeOut();
        jQuery("#domainresults").html('<img src="images/loading.gif" border="0" alt="Loading..." />');
    });
}
function completedomain() {
    jQuery("#domainresults").append('<img src="images/loading.gif" border="0" alt="Loading..." />');
    jQuery.post("cart.php", 'ajax=1&a=add&pid={/literal}{$pid}{literal}&domainselect=1&'+jQuery("#domainfrm").serialize(),
    function(data){
        if (data=='') {
            window.location='cart.php?a=view';
        } else if (data=='nodomains') {
            jQuery("#domainpopupcontainer").slideUp('slow',function() {
                jQuery("#greyout").fadeOut();
            });
        } else {
            jQuery("#prodconfigcontainer").replaceWith(data);
            jQuery("#domainpopupcontainer").slideUp('slow',function() {
                jQuery("#greyout").fadeOut();
            });
            jQuery("#prodconfigcontainer").slideDown();
        }
    });
}
</script>
{/literal}
