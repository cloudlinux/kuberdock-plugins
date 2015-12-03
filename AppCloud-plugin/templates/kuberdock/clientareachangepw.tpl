{include file="$template/pageheader.tpl" title=$LANG.clientareanavchangepw}

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

<form class="form-horizontal changepassword-form" method="post" action="{$smarty.server.PHP_SELF}?action=changepw">
    <div class="container-padding-default">
        <div class="center40">
            <div class="control-group">
                <label for="existingpw">{$LANG.existingpassword}</label>
                <input type="password" name="existingpw" id="existingpw" />
            </div>
            <div class="control-group">
                <label for="password">{$LANG.newpassword}</label>
                <input type="password" name="newpw" id="password" />
            </div>
            <div class="control-group">
                <label for="confirmpw">{$LANG.confirmnewpassword}</label>
                <input type="password" name="confirmpw" id="confirmpw" />
            </div>
            <div class="control-group">
                <label for="passstrength">{$LANG.pwstrength}</label>
                {include file="$template/pwstrength.tpl"}
            </div>
            <br/>
            <br/>
        </div>
    </div>
    <div class="container-padding-default">
        <div class="center40" style="font-size: 0;">
            <input class="gray-button" style="width: 49%; margin-right: 1%;" type="reset" value="{$LANG.cancel}" />
            <input class="send-message" style="width: 50%" type="submit" name="submit" value="{$LANG.clientareasavechanges}" />
            <br/>
            <br/>
            <br/>
            <br/>
        </div>
    </div>
</form>