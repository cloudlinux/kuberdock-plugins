<script type="text/javascript" src="{$BASE_PATH_JS}/CreditCardValidation.js"></script>

{include file="$template/pageheader.tpl" title=$LANG.clientareanavccdetails}

{include file="$template/clientareadetailslinks.tpl"}

{if $remoteupdatecode}

  <div align="center">
    {$remoteupdatecode}
  </div>

{else}

{if $successful}
<div class="alert alert-success">
    <p>{if $deletecc}{$LANG.creditcarddeleteconfirmation}{else}{$LANG.changessavedsuccessfully}{/if}</p>
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

  <fieldset class="onecol">

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardtype}</label>
        <div class="controls">
            <input type="text" value="{$cardtype}" disabled="true" class="input-medium" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardnumber}</label>
        <div class="controls">
            <div style="float:left">
                <input type="text" value="{$cardnum}" disabled="true" />
            </div>
            {if $allowcustomerdelete && $cardtype}
            <div style="float:left;margin-left:25px;">
                <form method="post" action="{$smarty.server.PHP_SELF}?action=creditcard">
                <input type="submit" name="remove" value="{$LANG.creditcarddelete}" class="btn btn-danger" />
                </form>
            </div>
{/if}
            <div class="clear"></div>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardexpires}</label>
        <div class="controls">
            <input type="text" value="{$cardexp}" disabled="true" class="input-small" />
        </div>
    </div>
{if $cardstart}
    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardstart}</label>
        <div class="controls">
            <input type="text" value="{$cardstart}" disabled="true" class="input-small" />
        </div>
    </div>
{/if}{if $cardissuenum}
    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcardissuenum}</label>
        <div class="controls">
            <input type="text" value="{$cardissuenum}" disabled="true" class="input-mini" />
        </div>
    </div>
{/if}
  </fieldset>

<div class="styled_title"><h3>{$LANG.creditcardenternewcard}</h3></div>

  <br />

<form class="form-horizontal" method="post" action="{$smarty.server.PHP_SELF}?action=creditcard">

  <fieldset class="onecol">

    <div class="control-group">
        <label class="control-label" for="cctype">{$LANG.creditcardcardtype}</label>
        <div class="controls">
            <select name="cctype" id="cctype">
            {foreach key=num item=cardtype from=$acceptedcctypes}
                <option>{$cardtype}</option>
            {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="ccnumber">{$LANG.creditcardcardnumber}</label>
        <div class="controls">
            <input type="text" name="ccnumber" id="ccnumber" autocomplete="off" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="ccexpirymonth">{$LANG.creditcardcardexpires}</label>
        <div class="controls">
            <select name="ccexpirymonth" id="ccexpirymonth">{foreach from=$months item=month}<option>{$month}</option>{/foreach}</select> / <select name="ccexpiryyear">{foreach from=$expiryyears item=year}<option>{$year}</option>{/foreach}</select>
        </div>
    </div>
{if $showccissuestart}
    <div class="control-group">
        <label class="control-label" for="ccstartmonth">{$LANG.creditcardcardstart}</label>
        <div class="controls">
            <select name="ccstartmonth" id="ccstartmonth">{foreach from=$months item=month}<option>{$month}</option>{/foreach}</select> / <select name="ccstartyear">{foreach from=$startyears item=year}<option>{$year}</option>{/foreach}</select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="ccissuenum">{$LANG.creditcardcardissuenum}</label>
        <div class="controls">
            <input type="text" name="ccissuenum" id="ccissuenum" maxlength="3" class="input-mini" autocomplete="off" />
        </div>
    </div>
{/if}

    <div class="control-group">
        <label class="control-label">{$LANG.creditcardcvvnumber}</label>
        <div class="controls">
            <input type="text" name="cardcvv" id="cardcvv" value="{$cardcvv}" maxlength="3" class="input-mini" autocomplete="off" />
        </div>
    </div>


  </fieldset>

  <div class="form-actions">
    <input class="btn btn-primary" type="submit" name="submit" value="{$LANG.clientareasavechanges}" />
    <input class="btn" type="reset" value="{$LANG.cancel}" />
  </div>

</form>

{/if}
