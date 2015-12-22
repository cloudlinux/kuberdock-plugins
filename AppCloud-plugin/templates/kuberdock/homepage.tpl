{if $condlinks.domainreg || $condlinks.domaintrans || $condlinks.domainown}
    <form method="post" action="{$systemsslurl}domainchecker.php">
        <div class="well textcenter search-domain">
            <div class="styled_title">
                <h1>{$LANG.domaincheckerchoosedomain}</h1>
            </div>
            <p>{$LANG.domaincheckerenterdomain}</p><br>
            <input class="bigfield" name="domain" type="text" placeholder="{$LANG.domaincheckerdomainexample}" />
            <div class="textcenter">
                <div class="capatacha-wrap nomargin control-group">
                    {if $captcha}
                        <p>{$LANG.captchaverify}</p><br/><br/>
                        {if $captcha eq "recaptcha"}
                            <p>{$recaptchahtml}</p>
                        {else}
                            <div class="capatacha-code-wrap controls">
                                <img src="includes/verifyimage.php" align="middle" />
                                <input type="text" name="code" class="input-small" maxlength="5" />
                            </div>
                        {/if}
                    {/if}
                    <div class="internalpadding">
                        {if $condlinks.domainreg}
                            <input type="submit" value="{$LANG.checkavailability}" />
                        {/if}
                        {if $condlinks.domaintrans}
                            <input type="submit" name="transfer" value="{$LANG.domainstransfer}"/>
                        {/if}
                        {if $condlinks.domainown}
                            <input type="submit" name="hosting" value="{$LANG.domaincheckerhostingonly}" />
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </form>
{/if}

<div class="row">
    <div class="page-header">
        <div class="styled_title">
            <h1>{$LANG.navservicesorder}</h1><br/><small>{$LANG.clientareahomeorder}</small>
        </div>
        <form method="post" action="cart.php">
            <p align="center">
                <br/><input type="submit" value="{$LANG.clientareahomeorderbtn}" class="send-message" />
            </p>
        </form>
    </div>
    <div class="page-header">
        <div class="styled_title"><h1>{$LANG.manageyouraccount}<br/><small>{$LANG.clientareahomelogin}</small></h1></div>
        <form method="post" action="clientarea.php">
            <p align="center">
                <br/><input type="submit" value="{$LANG.clientareahomeloginbtn}" class="send-message" />
            </p>
        </form>
    </div>
</div>

<div class="row">
    {if $twitterusername}
        <div class="page-header">
            <div class="styled_title">
                <h1>{$LANG.twitterlatesttweets}</h1>
            </div>
            <div id="twitterfeed">
                <p><img src="{$BASE_PATH_IMG}/loading.gif"></p>
            </div>
            {literal}<script language="javascript">
            jQuery(document).ready(function(){
              jQuery.post("announcements.php", { action: "twitterfeed", numtweets: 3 },
                function(data){
                  jQuery("#twitterfeed").html(data);
                });
            });
            </script>{/literal}
        </div>
    {elseif $announcements}
        <div class="page-header">
            <div class="styled_title">
                <h1>{$LANG.latestannouncements}<br/><br/>
                <small>
                    {foreach from=$announcements item=announcement}
                        {$announcement.date} - <a href="{if $seofriendlyurls}announcements/{$announcement.id}/{$announcement.urlfriendlytitle}.html{else}announcements.php?id={$announcement.id}{/if}">{$announcement.title}</a><br />{$announcement.text|strip_tags|truncate:100:"..."}
                    {/foreach}
                </small>
                </h1>
            </div>

        </div>
    {/if}
</div>
