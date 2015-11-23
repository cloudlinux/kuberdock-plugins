{include file="$template/pageheader.tpl" title=$LANG.login}

<div class="halfwidthcontainer">

{if $incorrect}
<div class="alert alert-error textcenter">
    <p>{$LANG.loginincorrect}</p>
</div>
{/if}

<form method="post" action="{$systemsslurl}dologin.php" class="form-stacked">

<div class="logincontainer">

    <fieldset>
        <div class="control-group">
            <div class="controls">
                <input class="input-xlarge" name="username" id="username" type="text" placeholder="{$LANG.loginemail}"/>
                <span title="{$LANG.loginemail}"></span>
            </div>
            <div class="controls">
                <input class="input-xlarge" name="password" id="password" type="password" placeholder="{$LANG.loginpassword}"/>
                <span title="{$LANG.loginpassword}"></span>
            </div>
        </div>

        <div align="center">
          <label class="rememberme">
            <input type="checkbox" name="rememberme"{if $rememberme} checked="checked"{/if} />{$LANG.loginrememberme}
            <span></span>
          </label>
          <div class="loginbtn">
            <input id="login" type="submit" value="{$LANG.loginbutton}" />
          </div>
          <div class="clear"></div>
        </div>
        <a class="resetpswd" href="pwreset.php">{$LANG.loginforgotteninstructions}</a>
        <div class="clear"></div>
    </fieldset>

</div>

</form>

<script type="text/javascript">
$("#username").focus();
</script>

</div>