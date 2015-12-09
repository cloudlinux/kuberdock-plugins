<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
<script language="javascript">var statesTab=10;</script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<script type="text/javascript" src="includes/jscript/statesdropdown.js"></script>
<script type="text/javascript" src="includes/jscript/pwstrength.js"></script>
<script type="text/javascript" src="includes/jscript/creditcard.js"></script>

{literal}<script language="javascript">
function removeItem(type,num) {
    var response = confirm("{/literal}{$LANG.cartremoveitemconfirm}{literal}");
    if (response) {
        window.location = 'cart.php?a=remove&r='+type+'&i='+num;
    }
}
function emptyCart(type,num) {
    var response = confirm("{/literal}{$LANG.cartemptyconfirm}{literal}");
    if (response) {
        window.location = 'cart.php?a=empty';
    }
}
</script>{/literal}
<script>
window.langPasswordStrength = "{$LANG.pwstrength}";
window.langPasswordWeak = "{$LANG.pwstrengthweak}";
window.langPasswordModerate = "{$LANG.pwstrengthmoderate}";
window.langPasswordStrong = "{$LANG.pwstrengthstrong}";
</script>

<div id="order-modern">
    <div class="page-header">
        <div class="styled_title"><h1>{$LANG.cartreviewcheckout}</h1></div>
    </div>

    {if $errormessage}
        <div class="errorbox" style="display:block; margin: 0 auto;">
            {$errormessage|replace:'<li>':' &nbsp;#&nbsp; '} &nbsp;#&nbsp;
        </div>
        <br/>
        <br/>
        <br/>
    {elseif $promotioncode && $rawdiscount eq "0.00"}
        <div class="errorbox" style="display:block; margin: 0 auto;">{$LANG.promoappliedbutnodiscount}</div>
        <br/>
        <br/>
        <br/>
    {/if}

    {if $bundlewarnings}
        <div class="cartwarningbox">
            <strong>{$LANG.bundlereqsnotmet}</strong><br />
            {foreach from=$bundlewarnings item=warning}
                {$warning}<br />
            {/foreach}
        </div>
        <br/>
        <br/>
    {/if}

    {if !$loggedin && $currencies}
        <br/>
        <div id="currencychooser">
            {foreach from=$currencies item=curr}
                <a href="cart.php?a=view&currency={$curr.id}">
                    <img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}
                </a>
            {/foreach}
        </div>
        <div class="clear"></div>
    {/if}

    <form method="post" action="{$smarty.server.PHP_SELF}?a=view">
        <table class="teble custom striped" cellspacing="1" style="margin-top: -28px;">
            <thead>
                <tr align="left">
                    <th width="45%" style="padding-left:5%;">{$LANG.orderdesc}</th>
                    <th width="45%" style="padding-left:5%;">{$LANG.orderprice}</th>
                </tr>
            </thead>
            <tbody>
                {foreach key=num item=product from=$products}
                    <tr class="carttableproduct">
                        <td width="45%" style="padding-left:5%;">
                            <strong><em>{$product.productinfo.groupname}</em> - {$product.productinfo.name}</strong>{if $product.domain} ({$product.domain}){/if}
                            {if $product.configoptions}
                                {foreach key=confnum item=configoption from=$product.configoptions} &nbsp;&raquo; {$configoption.name}: {if $configoption.type eq 1 || $configoption.type eq 2}{$configoption.option}{elseif $configoption.type eq 3}{if $configoption.qty}{$LANG.yes}{else}{$LANG.no}{/if}{elseif $configoption.type eq 4}{$configoption.qty} x {$configoption.option}{/if}<br />{/foreach}
                            {/if}
                            <a href="{$smarty.server.PHP_SELF}?a=confproduct&i={$num}" class="cartedit">[{$LANG.carteditproductconfig}]</a>
                            <a href="#" onclick="removeItem('p','{$num}');return false" class="cartremove">[{$LANG.cartremove}]</a>
                            {if $product.allowqty}
                                <br /><br />
                                <div align="right">
                                {$LANG.cartqtyenterquantity}
                                <input type="text" name="qty[{$num}]" size="3" value="{$product.qty}" />
                                <input type="submit" value="{$LANG.cartqtyupdate}" /></div>
                            {/if}
                        </td>
                        <td width="45%" style="padding-left:5%;" class="textleft"><strong>{$product.pricingtext}{if $product.proratadate}<br />({$LANG.orderprorata} {$product.proratadate}){/if}</strong></td>
                    </tr>
                    {foreach key=addonnum item=addon from=$product.addons}
                        <tr class="carttableproduct">
                            <td width="45%" style="padding-left:5%;"><strong>{$LANG.orderaddon}</strong> - {$addon.name}</td>
                            <td width="45%" style="padding-left:5%;" class="textleft"><strong>{$addon.pricingtext}</strong></td>
                        </tr>
                    {/foreach}
                {/foreach}

            {foreach key=num item=addon from=$addons}
                <tr class="carttableproduct">
                    <td width="45%" style="padding-left:5%;">
                        <strong>{$addon.name}</strong><br />
                        {$addon.productname}{if $addon.domainname} - {$addon.domainname}<br />{/if}
                        <a href="#" onclick="removeItem('a','{$num}');return false" class="cartremove">[{$LANG.cartremove}]</a>
                    </td>
                    <td width="45%" style="padding-left:5%;" class="textleft"><strong>{$addon.pricingtext}</strong></td>
                </tr>
            {/foreach}

            {foreach key=num item=domain from=$domains}
                <tr class="carttableproduct">
                    <td width="45%" style="padding-left:5%;">
                        <strong>{if $domain.type eq "register"}{$LANG.orderdomainregistration}{else}{$LANG.orderdomaintransfer}{/if}</strong> - {$domain.domain} - {$domain.regperiod} {$LANG.orderyears}<br />
                        {if $domain.dnsmanagement}&nbsp;&raquo; {$LANG.domaindnsmanagement}<br />{/if}
                        {if $domain.emailforwarding}&nbsp;&raquo; {$LANG.domainemailforwarding}<br />{/if}
                        {if $domain.idprotection}&nbsp;&raquo; {$LANG.domainidprotection}<br />{/if}
                        <a href="{$smarty.server.PHP_SELF}?a=confdomains" class="cartedit">[{$LANG.cartconfigdomainextras}]</a> <a href="#" onclick="removeItem('d','{$num}');return false" class="cartremove">[{$LANG.cartremove}]</a>
                    </td>
                    <td width="45%" style="padding-left:5%;" class="textleft"><strong>{$domain.price}</strong></td>
                </tr>
            {/foreach}

            {foreach key=num item=domain from=$renewals}
                <tr class="carttableproduct">
                    <td width="45%" style="padding-left:5%;">
                        <strong>{$LANG.domainrenewal}</strong> - {$domain.domain} - {$domain.regperiod} {$LANG.orderyears}<br />
                        {if $domain.dnsmanagement}&nbsp;&raquo; {$LANG.domaindnsmanagement}<br />{/if}
                        {if $domain.emailforwarding}&nbsp;&raquo; {$LANG.domainemailforwarding}<br />{/if}
                        {if $domain.idprotection}&nbsp;&raquo; {$LANG.domainidprotection}<br />{/if}
                        <a href="#" onclick="removeItem('r','{$num}');return false" class="cartremove">[{$LANG.cartremove}]</a>
                    </td>
                    <td width="45%" style="padding-left:5%;" class="textleft"><strong>{$domain.price}</strong></td>
                </tr>
            {/foreach}

            {if $cartitems==0}
                <tr class="clientareatableactive">
                    <td colspan="2" class="textcenter">{$LANG.cartempty}</td>
                </tr>
            {/if}
            </tbody>
        </table>
        <div class="container-padding-default">
            <div class="subtotal textright">{$LANG.ordersubtotal}: &nbsp; {$subtotal}</div>
            {if $promotioncode}
                <div class="promotion textright">
                    {$promotiondescription}: &nbsp; {$discount}
                </div>
            {/if}
            {if $taxrate}
                <div class="subtotal textright">
                    {$taxname} @ {$taxrate}%: &nbsp;{$taxtotal}
                </div>
            {/if}
            {if $taxrate2}
                <div class="subtotal textright">
                    {$taxname2} @ {$taxrate2}%: &nbsp; {$taxtotal2}
                </div>
            {/if}
            <div class="total textright">
                {$LANG.ordertotalduetoday}: &nbsp; {$total}
            </div>
            {if $totalrecurringmonthly || $totalrecurringquarterly || $totalrecurringsemiannually || $totalrecurringannually || $totalrecurringbiennially || $totalrecurringtriennially}
                <div class="recurring">
                    {$LANG.ordertotalrecurring}: &nbsp;
                    {if $totalrecurringmonthly}{$totalrecurringmonthly} {$LANG.orderpaymenttermmonthly}<br />{/if}
                    {if $totalrecurringquarterly}{$totalrecurringquarterly} {$LANG.orderpaymenttermquarterly}<br />{/if}
                    {if $totalrecurringsemiannually}{$totalrecurringsemiannually} {$LANG.orderpaymenttermsemiannually}<br />{/if}
                    {if $totalrecurringannually}{$totalrecurringannually} {$LANG.orderpaymenttermannually}<br />{/if}
                    {if $totalrecurringbiennially}{$totalrecurringbiennially} {$LANG.orderpaymenttermbiennially}<br />{/if}
                    {if $totalrecurringtriennially}{$totalrecurringtriennially} {$LANG.orderpaymenttermtriennially}<br />{/if}
                </div>
            {/if}
        </div>
    </form>
    <div class="container-padding-default textright">
        <input type="button" value="{$LANG.emptycart}" onclick="emptyCart();return false" class="gray-button" />
        <input type="button" value="{$LANG.continueshopping}" onclick="window.location='cart.php'" class="send-message" />
    </div>

    {foreach from=$gatewaysoutput item=gatewayoutput}
        <div class="clear"></div>
        <div class="cartbuttons">{$gatewayoutput}</div>
    {/foreach}

    {if $cartitems!=0}
        <form method="post" action="{$smarty.server.PHP_SELF}?a=checkout" id="mainfrm">
            <input type="hidden" name="submit" value="true" />
            <input type="hidden" name="custtype" id="custtype" value="{$custtype}" />
            <br/>
            <div class="page-header">
                <div class="styled_title"><h1>{$LANG.yourdetails}</h1></div>
            </div>

            <div class="container-padding-default switcher">
                <div class="signuptype{if !$loggedin && $custtype neq "existing"} active{/if}"{if !$loggedin} id="newcust"{/if}>{$LANG.newcustomer}</div>
                <div class="signuptype{if $custtype eq "existing" && !$loggedin || $loggedin} active{/if}" id="existingcust">{$LANG.existingcustomer}</div>
                <div class="clear"></div>
            </div>

            <div class="halfwidthcontainer signupfields{if $custtype eq "existing" && !$loggedin}{else} hidden{/if}" id="loginfrm">
                <div class="logincontainer">
                    <br/>
                    <div class="control-group">
                        <div class="conrtols">
                            <input type="text" name="loginemail" id="loginemail" class="input-xlarge" placeholder="{$LANG.clientareaemail}" size="40" />
                            <span title="{$LANG.clientareaemail}"></span>
                        </div>
                        <div class="conrtols">
                            <input type="password" name="loginpw" id="loginpw" class="input-xlarge" size="25" placeholder="{$LANG.clientareapassword}"/>
                            <span title="{$LANG.clientareapassword}"></span>
                        </div>
                    </div>
                </div>
            </div>


            <div style="margin-top: 30px;" class="signupfields{if $custtype eq "existing" && !$loggedin} hidden{/if}" id="signupfrm">
                <div class="control-group container-padding-63 register">
                    <div class="half">
                        <div class="control-group">
                            <label for="firstname">{$LANG.clientareafirstname}</label>
                            {if $loggedin}
                                {$clientsdetails.firstname}
                            {else}
                                <input type="text" name="firstname" id="firstname" tabindex="1" value="{$clientsdetails.firstname}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="lastname">{$LANG.clientarealastname}</label>
                          {if $loggedin}
                                {$clientsdetails.lastname}
                            {else}
                                <input type="text" name="lastname" id="lastname" tabindex="2" value="{$clientsdetails.lastname}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="companyname">{$LANG.clientareacompanyname}</label>
                            {if $loggedin}
                                {$clientsdetails.companyname}
                            {else}
                                <input type="text" name="companyname" id="companyname" tabindex="3" value="{$clientsdetails.companyname}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="email">{$LANG.clientareaemail}</label>
                            {if $loggedin}
                                {$clientsdetails.email}
                            {else}
                                <input type="text" name="email" id="email" tabindex="4" value="{$clientsdetails.email}" />
                            {/if}
                        </div>
                        {if !$loggedin}
                            <div class="control-group">
                                <label for="password">{$LANG.clientareapassword}</label>
                                {if $loggedin}
                                    {$clientsdetails.email}
                                {else}
                                    <input type="password" name="password" id="password" tabindex="5" id="newpw" size="20" value="{$password}" />
                                {/if}
                            </div>
                            <div class="control-group">
                                <label for="password2">{$LANG.clientareaconfirmpassword}</label>
                                <input type="password" name="password2" id="password2" tabindex="6" size="20" value="{$password2}" />
                            </div>
                        {/if}
                        {if $customfields || $securityquestions}
                            {if $securityquestions && !$loggedin}
                                <div class="control-group">
                                    <label for="securityqid">{$LANG.clientareasecurityquestion}</label>
                                    <select name="securityqid" id="securityqid" tabindex="14">
                                        {foreach key=num item=question from=$securityquestions}
                                            <option value="{$question.id}"{if $question.id eq $securityqid} selected{/if}>{$question.question}</option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="control-group">
                                    <label for="securityqans">{$LANG.clientareasecurityanswer}</label>
                                    <input type="password" name="securityqans" id="securityqans" value="{$securityqans}" tabindex="15" size="30">
                                </div>
                            {/if}
                            {foreach key=num item=customfield from=$customfields}
                            <div class="control-group">
                                <label>{$customfield.name}</label>
                                {$customfield.input} {$customfield.description}
                            </div>
                            {/foreach}
                        {/if}
                    </div>

                    <div class="half">
                        <div class="control-group">
                            <label for="address1">{$LANG.clientareaaddress1}</label>
                            {if $loggedin}
                                {$clientsdetails.address1}
                            {else}
                                <input type="text" name="address1" id="address1" tabindex="7" value="{$clientsdetails.address1}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="address2">{$LANG.clientareaaddress2}</label>
                            {if $loggedin}
                                {$clientsdetails.address2}
                            {else}
                                <input type="text" name="address2" id="address2" tabindex="8" value="{$clientsdetails.address2}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="city">{$LANG.clientareacity}</label>
                            {if $loggedin}
                                {$clientsdetails.city}
                            {else}
                                <input type="text" name="city" id="city" tabindex="9" value="{$clientsdetails.city}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="state">{$LANG.clientareastate}</label>
                            {if $loggedin}
                                {$clientsdetails.state}
                            {else}
                                <input type="text" name="state" id="state" tabindex="10" value="{$clientsdetails.state}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="postcode">{$LANG.clientareapostcode}</label>
                            {if $loggedin}
                                {$clientsdetails.postcode}
                            {else}
                                <input type="text" name="postcode" id="postcode" tabindex="11" size="15" value="{$clientsdetails.postcode}" />
                            {/if}
                        </div>
                        <div class="control-group">
                            <label>{$LANG.clientareacountry}</label>
                            {if $loggedin}
                                {$clientsdetails.country}
                            {else}
                                {$clientcountrydropdown|replace:'<select':'<select tabindex="12"'}
                            {/if}
                        </div>
                        <div class="control-group">
                            <label for="phonenumber">{$LANG.clientareaphonenumber}</label>
                            {if $loggedin}
                                {$clientsdetails.phonenumber}
                            {else}
                                <input type="text" name="phonenumber" id="phonenumber" tabindex="13" size="20" value="{$clientsdetails.phonenumber}" />
                            {/if}
                        </div>
                    </div>
                </div>
            </div>


            {if $taxenabled && !$loggedin}
                <div class="carttaxwarning">
                    {$LANG.carttaxupdateselections}
                    <input type="submit" value="{$LANG.carttaxupdateselectionsupdate}" name="updateonly" />
                </div>
            {/if}

            {if $domainsinorder}
                <h2>{$LANG.domainregistrantinfo}</h2>
                    <select name="contact" id="domaincontact" onchange="domaincontactchange()">
                        <option value="">{$LANG.usedefaultcontact}</option>
                        {foreach from=$domaincontacts item=domcontact}
                            <option value="{$domcontact.id}"{if $contact==$domcontact.id} selected{/if}>{$domcontact.name}</option>
                        {/foreach}
                        <option value="addingnew"{if $contact eq "addingnew"} selected{/if}>{$LANG.clientareanavaddcontact}...</option>
                    </select>

                    <br />
                    <br />

                    <div class="signupfields{if $contact neq "addingnew"} hidden{/if}" id="domaincontactfields">
                        <table width="100%" cellspacing="0" cellpadding="0" class="configtable">
                            <tr>
                                <td class="fieldlabel">{$LANG.clientareafirstname}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactfirstname" style="width:80%;" value="{$domaincontact.firstname}" /></td>
                                <td class="fieldlabel">{$LANG.clientareaaddress1}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactaddress1" style="width:80%;" value="{$domaincontact.address1}" /></td>
                            </tr>
                            <tr>
                                <td class="fieldlabel">{$LANG.clientarealastname}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactlastname" style="width:80%;" value="{$domaincontact.lastname}" /></td>
                                <td class="fieldlabel">{$LANG.clientareaaddress2}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactaddress2" style="width:80%;" value="{$domaincontact.address2}" /></td>
                            </tr>
                            <tr>
                                <td class="fieldlabel">{$LANG.clientareacompanyname}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactcompanyname" style="width:80%;" value="{$domaincontact.companyname}" /></td>
                                <td class="fieldlabel">{$LANG.clientareacity}</td><td class="fieldarea"><input type="text" name="domaincontactcity" style="width:80%;" value="{$domaincontact.city}" /></td>
                            </tr>
                            <tr>
                                <td class="fieldlabel">{$LANG.clientareaemail}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactemail" style="width:90%;" value="{$domaincontact.email}" /></td>
                                <td class="fieldlabel">{$LANG.clientareastate}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactstate" style="width:80%;" value="{$domaincontact.state}" /></td>
                            </tr>
                            <tr>
                                <td class="fieldlabel">{$LANG.clientareaphonenumber}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactphonenumber" size="20" value="{$domaincontact.phonenumber}" /></td>
                                <td class="fieldlabel">{$LANG.clientareapostcode}</td>
                                <td class="fieldarea"><input type="text" name="domaincontactpostcode" size="15" value="{$domaincontact.postcode}" /></td>
                            </tr>
                            <tr>
                                <td class="fieldlabel"></td><td class="fieldarea"></td>
                                <td class="fieldlabel">{$LANG.clientareacountry}</td>
                                <td class="fieldarea">{$domaincontactcountrydropdown}</td>
                            </tr>
                    </table>
                </div>
            {/if}

            <div class="clear"></div>
            <div class="page-header">
                <div class="styled_title"><h1>{$LANG.orderpromotioncode}</h1></div>
            </div>
            {if $promotioncode}
                <div class="center95">
                    {$promotioncode} - {$promotiondescription}<br />
                    <a href="{$smarty.server.PHP_SELF}?a=removepromo">{$LANG.orderdontusepromo}</a>
                </div>
                <br/>
            {else}
                <div class="center95 textcenter">
                    <input type="text" name="promocode" size="20" value="" style="margin-bottom: 0;"/>
                    <input type="submit" name="validatepromo" value="{$LANG.orderpromovalidatebutton}" class="send-message"/>
                </div>
                <br/>
            {/if}

            <div class="clear"></div>
            {if $shownotesfield}
                <div class="page-header">
                    <div class="styled_title"><h1>{$LANG.ordernotes}</h1></div>
                </div>
                <div class="center95">
                    <textarea name="notes" rows="8" style="width:100%" onFocus="if(this.value=='{$LANG.ordernotesdescription}'){ldelim}this.value='';{rdelim}" onBlur="if (this.value==''){ldelim}this.value='{$LANG.ordernotesdescription}';{rdelim}" placeholder="{$LANG.ordernotesdescription}">{$notes}</textarea>
                </div>
            {/if}

            <div class="clear"></div>
            <div class="page-header">
                <div class="styled_title"><h1>{$LANG.orderpaymentmethod}</h1></div>
            </div>
            <div class="center95 textcenter">
                {foreach key=num item=gateway from=$gateways}
                    <label class="form-inline">
                        <input type="radio" name="paymentmethod" value="{$gateway.sysname}" id="pgbtn{$num}" onclick="{if $gateway.type eq "CC"}showCCForm(){else}hideCCForm(){/if}"{if $selectedgateway eq $gateway.sysname} checked{/if} /> {$gateway.name}
                    </label>
                {/foreach}
            </div>
            <br />

            <div>
            <div>
                <div id="ccinputform" class="signupfields{if $selectedgatewaytype neq "CC"} hidden{/if}">
                    <table width="100%" cellspacing="0" cellpadding="0" class="configtable textleft">
                        {if $clientsdetails.cclastfour}
                            <tr>
                                <td class="fieldlabel"></td>
                                <td class="fieldarea">
                                    <label>
                                        <input type="radio" name="ccinfo" value="useexisting" id="useexisting" onclick="useExistingCC()"{if $clientsdetails.cclastfour} checked{else} disabled{/if} /> {$LANG.creditcarduseexisting}{if $clientsdetails.cclastfour} ({$clientsdetails.cclastfour}){/if}
                                    </label>
                                    <br />
                                    <label>
                                        <input type="radio" name="ccinfo" value="new" id="new" onclick="enterNewCC()"{if !$clientsdetails.cclastfour || $ccinfo eq "new"} checked{/if} /> {$LANG.creditcardenternewcard}
                                    </label>
                                </td>
                            </tr>
                        {else}
                            <input type="hidden" name="ccinfo" value="new" />{/if}
                            <tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}>
                                <td class="fieldlabel">{$LANG.creditcardcardtype}</td><td class="fieldarea">
                                    <select name="cctype" id="cctype">
                                        {foreach key=num item=cardtype from=$acceptedcctypes}
                                            <option{if $cctype eq $cardtype} selected{/if}>{$cardtype}</option>
                                        {/foreach}
                                    </select>
                                </td>
                            </tr>
                            <tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}>
                                <td class="fieldlabel">{$LANG.creditcardcardnumber}</td>
                                <td class="fieldarea"><input type="text" name="ccnumber" size="30" value="{$ccnumber}" autocomplete="off" /></td>
                            </tr>
                            <tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}>
                                <td class="fieldlabel">{$LANG.creditcardcardexpires}</td>
                                <td class="fieldarea">
                                    <select name="ccexpirymonth" id="ccexpirymonth" class="newccinfo">
                                        {foreach from=$months item=month}
                                            <option{if $ccexpirymonth eq $month} selected{/if}>{$month}</option>
                                        {/foreach}
                                    </select> /
                                    <select name="ccexpiryyear" class="newccinfo">
                                        {foreach from=$expiryyears item=year}
                                        <option{if $ccexpiryyear eq $year} selected{/if}>{$year}</option>
                                        {/foreach}
                                    </select>
                                </td>
                            </tr>
                            {if $showccissuestart}
                                <tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}>
                                    <td class="fieldlabel">{$LANG.creditcardcardstart}</td>
                                    <td class="fieldarea">
                                        <select name="ccstartmonth" id="ccstartmonth" class="newccinfo">
                                            {foreach from=$months item=month}
                                                <option{if $ccstartmonth eq $month} selected{/if}>{$month}</option>
                                            {/foreach}
                                        </select> /
                                        <select name="ccstartyear" class="newccinfo">
                                            {foreach from=$startyears item=year}
                                                <option{if $ccstartyear eq $year} selected{/if}>{$year}</option>
                                            {/foreach}
                                        </select>
                                    </td>
                                </tr>
                                <tr class="newccinfo"{if $clientsdetails.cclastfour && $ccinfo neq "new"} style="display:none;"{/if}>
                                    <td class="fieldlabel">{$LANG.creditcardcardissuenum}</td>
                                    <td class="fieldarea">
                                        <input type="text" name="ccissuenum" value="{$ccissuenum}" size="5" maxlength="3" />
                                    </td>
                                </tr>
                            {/if}
                            <tr>
                                <td class="fieldlabel">{$LANG.creditcardcvvnumber}</td>
                                <td class="fieldarea">
                                    <input type="text" name="cccvv" id="cccvv" value="{$cccvv}" size="5" autocomplete="off" />
                                    <a href="#" onclick="window.open('images/ccv.gif','','width=280,height=200,scrollbars=no,top=100,left=100');return false">{$LANG.creditcardcvvwhere}</a>
                                </td>
                            </tr>
                            {if $shownostore}
                                <tr>
                                    <td class="fieldlabel"><input type="checkbox" name="nostore" id="nostore" /></td>
                                    <td><label for="nostore">{$LANG.creditcardnostore}</label></td>
                                </tr>
                            {/if}
                        </table>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            {if $accepttos}
                <div align="center">
                    <label>
                        <input type="checkbox" name="accepttos" id="accepttos" /> {$LANG.ordertosagreement}
                        <a href="{$tosurl}" target="_blank">{$LANG.ordertos}</a>
                    </label>
                </div>
                <br />
            {/if}
            <div align="center">
                <input type="submit" value="{$LANG.completeorder}"{if $cartitems==0} disabled{/if} onclick="this.value='{$LANG.pleasewait}'" class="ordernow send-message" />
            </div>
        </form>
    {else}
        <br />
        <br />
    {/if}
    <div class="cartwarningbox">
        <!-- <img src="images/padlock.gif" align="absmiddle" border="0" alt="Secure Transaction" /> &nbsp; -->{$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
    </div>
</div>


