<div class="textcenter">
    <ul class="nav usr-links">
        <li class="icons urer {if $clientareaaction eq "details"}active{/if}"><a href="clientarea.php?action=details">{$LANG.clientareanavdetails}</a></li>
        {if $condlinks.updatecc}<li class="{if $clientareaaction eq "creditcard"}active{/if}"><a href="{$smarty.server.PHP_SELF}?action=creditcard">{$LANG.clientareanavccdetails}</a></li>{/if}
        <li class="icons urers {if $clientareaaction eq "contacts" ||  $clientareaaction eq "addcontact"}active{/if}"><a href="{$smarty.server.PHP_SELF}?action=contacts">{$LANG.clientareanavcontacts}</a></li>
        <li class="icons key {if $clientareaaction eq "changepw"}active{/if}"><a href="{$smarty.server.PHP_SELF}?action=changepw">{$LANG.clientareanavchangepw}</a></li>
        {if $condlinks.security}<li class="{if $clientareaaction eq "security"}active{/if}"><a href="{$smarty.server.PHP_SELF}?action=security">{$LANG.clientareanavsecurity}</a></li>{/if}
    </ul>
</div>
<div class="clear"></div>