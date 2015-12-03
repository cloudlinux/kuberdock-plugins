{include file="$template/pageheader.tpl" title=$LANG.clientareanavdetails}

<script type="text/javascript" src="{$BASE_PATH_JS}/StatesDropdown.js"></script>

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
        <ul>
            {$errormessage}
        </ul>
    </div>
    {/if}
</div>

<form class="form-horizontal register-form" method="post" action="{$smarty.server.PHP_SELF}?action=details">
    <div class="control-group container-padding-63 register" style="margin-bottom: 37px;">
        <div class="half">
            <div class="control-group">
                <label for="firstname">{$LANG.clientareafirstname}</label>
                <input type="text" name="firstname" id="firstname" value="{$clientfirstname}"{if in_array('firstname',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="lastname">{$LANG.clientarealastname}</label>
                <input type="text" name="lastname" id="lastname" value="{$clientlastname}"{if in_array('lastname',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
            <label for="companyname">{$LANG.clientareacompanyname}</label>
                    <input type="text" name="companyname" id="companyname" value="{$clientcompanyname}"{if in_array('companyname',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="email">{$LANG.clientareaemail}</label>
                <input type="text" name="email" id="email" value="{$clientemail}"{if in_array('email',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="paymentmethod">{$LANG.paymentmethod}</label>
                <select name="paymentmethod" id="paymentmethod">
                    <option value="none">{$LANG.paymentmethoddefault}</option>
                    {foreach from=$paymentmethods item=method}
                    <option value="{$method.sysname}"{if $method.sysname eq $defaultpaymentmethod} selected="selected"{/if}>{$method.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="control-group">
                <label for="billingcontact">{$LANG.defaultbillingcontact}</label>
                <select name="billingcid" id="billingcontact">
                    <option value="0">{$LANG.usedefaultcontact}</option>
                    {foreach from=$contacts item=contact}
                    <option value="{$contact.id}"{if $contact.id eq $billingcid} selected="selected"{/if}>{$contact.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="half">
            <div class="control-group">
                <label for="address1">{$LANG.clientareaaddress1}</label>
                <input type="text" name="address1" id="address1" value="{$clientaddress1}"{if in_array('address1',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="address2">{$LANG.clientareaaddress2}</label>
                <input type="text" name="address2" id="address2" value="{$clientaddress2}"{if in_array('address2',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="city">{$LANG.clientareacity}</label>
                <input type="text" name="city" id="city" value="{$clientcity}"{if in_array('city',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="state">{$LANG.clientareastate}</label>
                <input type="text" name="state" id="state" value="{$clientstate}"{if in_array('state',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="postcode">{$LANG.clientareapostcode}</label>
                <input type="text" name="postcode" id="postcode" value="{$clientpostcode}"{if in_array('postcode',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            <div class="control-group">
                <label for="country">{$LANG.clientareacountry}</label>
                {$clientcountriesdropdown}
            </div>
            <div class="control-group">
                <label for="phonenumber">{$LANG.clientareaphonenumber}</label>
                <input type="text" name="phonenumber" id="phonenumber" value="{$clientphonenumber}"{if in_array('phonenumber',$uneditablefields)} disabled="" class="disabled"{/if} />
            </div>
            {if $emailoptoutenabled}
            <div class="control-group">
                <label for="emailoptout">{$LANG.emailoptout}</label>
                <input type="checkbox" value="1" name="emailoptout" id="emailoptout" {if $emailoptout} checked{/if} /> {$LANG.emailoptoutdesc}
            </div>
            {/if}
        </div>
    </div>
    {if $customfields}
        {foreach from=$customfields key=num item=customfield}
            <div class="control-group">
                <label for="customfield{$customfield.id}">{$customfield.name}</label>
                {$customfield.input} {$customfield.description}
            </div>
        {/foreach}
    {/if}
    <div class="textcenter" style="margin-bottom: 62px;">
        <input class="gray-button" type="reset" value="{$LANG.cancel}" />
        <input class="send-message" type="submit" name="save" value="{$LANG.clientareasavechanges}" />
    </div>
</form>