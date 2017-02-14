{include file="$template/pageheader.tpl" title=$LANG.contacttitle desc=$LANG.contactheader}

{if $sent}

<br />

<div class="alert alert-success textcenter">
    <p><strong>{$LANG.contactsent}</strong></p>
</div>

{else}
<div class="center95">
    {if $errormessage}
    <div class="alert alert-error">
        <p class="bold">{$LANG.clientareaerrors}</p>
        <ul>
            {$errormessage}
        </ul>
    </div>
    {/if}
</div>

<form method="post" action="contact.php?action=send" class="form-stacked center95">

    <fieldset class="control-group">
        <div class="row">
            <div class="controls">
                <input type="text" name="name" id="name" value="{$name}" placeholder="{$LANG.supportticketsclientname}"/>
                <input type="text" name="email" id="email" value="{$email}" placeholder="{$LANG.supportticketsclientemail}"/>
                <input class="fullwidth" type="text" name="subject" id="subject" value="{$subject}" placeholder="{$LANG.supportticketsticketsubject}" />
                <textarea name="message" id="message" rows="12" class="fullwidth" placeholder="{$LANG.contactmessage}">{$message}</textarea>
            </div>
        </div>
    </fieldset>

{if $capatacha}
<div class="capatacha-wrap control-group">
<span class="capatacha-description">{$LANG.captchatitle}</span>
{if $capatacha eq "recaptcha"}
    {$recapatchahtml}
{else}
    <div class="capatacha-code-wrap controls">
        <input type="text"name="code" size="10" maxlength="5" />
        <img src="includes/verifyimage.php" align="middle" />
    </div>
{/if}

<!-- <p>{$LANG.captchaverify}</p> -->
{/if}
    <input type="submit" value="{$LANG.contactsend}" class="send-message" />
</div>
</form>

{/if}