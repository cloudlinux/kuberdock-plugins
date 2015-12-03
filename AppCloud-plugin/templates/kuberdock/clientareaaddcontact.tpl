<script type="text/javascript" src="{$BASE_PATH_JS}/StatesDropdown.js"></script>

{include file="$template/pageheader.tpl" title=$LANG.clientareanavcontacts}
{include file="$template/clientareadetailslinks.tpl"}

<div class="container-padding-default">
    {if $successful}
    <div class="alert alert-success">
        <p>{$LANG.changessavedsuccessfully}</p>
    </div>
    {/if}
    {if $errormessage}
    <div class="alert alert-error">
        <p class="bold">{$LANG.clientareaerrors}</p>
        <ul>{$errormessage}</ul>
    </div>
    {/if}
</div>

<script type="text/javascript">
{literal}
jQuery(document).ready(function(){
    jQuery("#subaccount").click(function () {
        if (jQuery("#subaccount:checked").val()!=null) {
            jQuery("#subaccountfields").slideDown();
        } else {
            jQuery("#subaccountfields").slideUp();
        }
    });
});
{/literal}
function deleteContact() {ldelim}
if (confirm("{$LANG.clientareadeletecontactareyousure}")) {ldelim}
window.location='clientarea.php?action=contacts&delete=true&id={$contactid}';
{rdelim}{rdelim}
</script>
<br/>
<div class="well" style="margin-top: -37px;">
    <form method="post" class="form-inline choose-contact" action="{$smarty.server.PHP_SELF}?action=contacts">
        <span>{$LANG.clientareachoosecontact}:</span>
        <select name="contactid" onchange="submit()">
            {foreach item=contact from=$contacts}
                <option value="{$contact.id}">{$contact.name} - {$contact.email}</option>
            {/foreach}
            <option value="new" selected="selected">{$LANG.clientareanavaddcontact}</option>
        </select>
        <input class="send-message" type="submit" value="{$LANG.go}" />
    </form>
</div>
<br/>
<br/>

<form class="form-horizontal" method="post" action="{$smarty.server.PHP_SELF}?action=addcontact">
    <input type="hidden" name="submit" value="true" />
    <div class="">
        <div class="control-group container-padding-63 register" style="margin: 0;">
            <div class="half">
                <div class="control-group">
                    <label for="firstname">{$LANG.clientareafirstname}</label>
                    <input type="text" name="firstname" id="firstname" value="{$contactfirstname}" />
                </div>
                <div class="control-group">
                    <label for="lastname">{$LANG.clientarealastname}</label>
                    <input type="text" name="lastname" id="lastname" value="{$contactlastname}" />
                </div>
                <div class="control-group">
                    <label for="companyname">{$LANG.clientareacompanyname}</label>
                    <input type="text" name="companyname" id="companyname" value="{$contactcompanyname}" />
                </div>
                <div class="control-group">
                    <label for="email">{$LANG.clientareaemail}</label>
                    <input type="text" name="email" id="email" value="{$contactemail}" />
                </div>
                <div class="control-group">
                    <label for="billingcontact">{$LANG.subaccountactivate}</label>
                    <label class="checkbox">
                        <input type="checkbox" name="subaccount" id="subaccount"{if $subaccount} checked{/if} />
                        <span></span>
                        {$LANG.subaccountactivatedesc}
                    </label>
                </div>
                <div id="subaccountfields" class="{if !$subaccount} hide{/if}">
                    <div class="control-group">
                        <label for="password">{$LANG.clientareapassword}</label>
                        <input type="password" name="password" id="password" />
                    </div>
                    <div class="control-group">
                        <label for="password2">{$LANG.clientareaconfirmpassword}</label>
                        <input type="password" name="password2" id="password2" />
                    </div>
                    <div class="control-group">
                        <label for="passstrength">{$LANG.pwstrength}</label>
                        {include file="$template/pwstrength.tpl"}
                    </div>
                    <div class="control-group">
                        <label class="full">{$LANG.subaccountpermissions}</label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" value="profile"{if in_array('profile',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsprofile}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permcontacts" value="contacts"{if in_array('contacts',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermscontacts}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permproducts" value="products"{if in_array('products',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsproducts}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permmanageproducts" value="manageproducts"{if in_array('manageproducts',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsmanageproducts}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permdomains" value="domains"{if in_array('domains',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsdomains}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permmanagedomains" value="managedomains"{if in_array('managedomains',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsmanagedomains}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="perminvoices" value="invoices"{if in_array('invoices',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsinvoices}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permquotes" value="quotes"{if in_array('quotes', $permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsquotes}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permtickets" value="tickets"{if in_array('tickets',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermstickets}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permaffiliates" value="affiliates"{if in_array('affiliates',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsaffiliates}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permemails" value="emails"{if in_array('emails',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsemails}
                        </label>
                        <label class="checkbox">
                            <input type="checkbox" name="permissions[]" id="permorders" value="orders"{if in_array('orders',$permissions)} checked{/if} />
                            <span></span>
                            {$LANG.subaccountpermsorders}
                        </label>
                    </div>
                </div>
                <div class="control-group">
                    <label>{$LANG.clientareacontactsemails}</label>
                    <label class="checkbox">
                        <input type="checkbox" name="generalemails" id="generalemails" value="1"{if $generalemails} checked{/if} />
                        <span></span>
                        {$LANG.clientareacontactsemailsgeneral}
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="productemails" id="productemails" value="1"{if $productemails} checked{/if} />
                        <span></span>
                        {$LANG.clientareacontactsemailsproduct}
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="domainemails" id="domainemails" value="1"{if $domainemails} checked{/if} />
                        <span></span>
                        {$LANG.clientareacontactsemailsdomain}
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="invoiceemails" id="invoiceemails" value="1"{if $invoiceemails} checked{/if} />
                        <span></span>
                        {$LANG.clientareacontactsemailsinvoice}
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" name="supportemails" id="supportemails" value="1"{if $supportemails} checked{/if} />
                        <span></span>
                        {$LANG.clientareacontactsemailssupport}
                    </label>
                </div>
            </div>
            <div class="half">
                <div class="control-group">
                    <label for="address1">{$LANG.clientareaaddress1}</label>
                    <input type="text" name="address1" id="address1" value="{$contactaddress1}" />
                </div>
                <div class="control-group">
                    <label for="address2">{$LANG.clientareaaddress2}</label>
                    <input type="text" name="address2" id="address2" value="{$contactaddress2}" />
                </div>
                <div class="control-group">
                    <label for="city">{$LANG.clientareacity}</label>
                    <input type="text" name="city" id="city" value="{$contactcity}" />
                </div>
                <div class="control-group">
                    <label for="state">{$LANG.clientareastate}</label>
                    <input type="text" name="state" id="state" value="{$contactstate}" />
                </div>
                <div class="control-group">
                    <label for="postcode">{$LANG.clientareapostcode}</label>
                    <input type="text" name="postcode" id="postcode" value="{$contactpostcode}" />
                </div>
                <div class="control-group">
                    <label for="country">{$LANG.clientareacountry}</label>
                    {$countriesdropdown}
                </div>
                <div class="control-group">
                    <label for="phonenumber">{$LANG.clientareaphonenumber}</label>
                    <input type="text" name="phonenumber" id="phonenumber" value="{$contactphonenumber}" />
                </div>
            </div>
        </div>
    </div>
    <br/>
    <div class="textcenter">
        <input class="gray-button" type="reset" value="{$LANG.cancel}" />
        <input class="send-message" type="submit" name="submit" value="{$LANG.clientareasavechanges}" />
    </div>
    <br/>
    <br/>
</form>
