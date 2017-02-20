{include file="$template/pageheader.tpl" title=$LANG.clientareanavdomains desc=$LANG.clientareadomainsintro}
<div class="container-padding-default">
    <div class="searchbox">
        <form method="post" action="clientarea.php?action=domains">
            <div class="input-append">
                <input type="text" name="q" value="{if $q}{$q}{else}{$LANG.searchenterdomain}{/if}" class="" onfocus="if(this.value=='{$LANG.searchenterdomain}')this.value=''" />
                <input type="submit" class="send-message" palceholder="{$LANG.searchfilter}" value="{$LANG.searchfilter}"/>
            </div>
        </form>
    </div>

    <div class="resultsbox">
    <p>{$numitems} {$LANG.recordsfound}, {$LANG.page} {$pagenumber} {$LANG.pageof} {$totalpages}</p>
    </div>

    <div class="clear"></div>
</div>

<br/>

{literal}
<script>
$(document).ready(function() {
    $(".setbulkaction").click(function(event) {
      event.preventDefault();
      $("#bulkaction").val($(this).attr('id'));
      $("#bulkactionform").submit();
    });
});
</script>
{/literal}
<form method="post" id="bulkactionform" action="clientarea.php?action=bulkdomain">
<input id="bulkaction" name="update" type="hidden" />

<table class="table custom">
    <thead>
        <tr>
            <th class="textcenter">
                <label class="checkbox">
                    <input type="checkbox" onclick="toggleCheckboxes('domids')" />
                    <span></span>
                </label>
            </th>
            <th{if $orderby eq "domain"} class="headerSort{$sort}"{/if}><a href="clientarea.php?action=domains{if $q}&q={$q}{/if}&orderby=domain">{$LANG.clientareahostingdomain}</a></th>
            <th{if $orderby eq "regdate"} class="headerSort{$sort}"{/if}><a href="clientarea.php?action=domains{if $q}&q={$q}{/if}&orderby=regdate">{$LANG.clientareahostingregdate}</a></th>
            <th{if $orderby eq "nextduedate"} class="headerSort{$sort}"{/if}><a href="clientarea.php?action=domains{if $q}&q={$q}{/if}&orderby=nextduedate">{$LANG.clientareahostingnextduedate}</a></th>
            <th{if $orderby eq "status"} class="headerSort{$sort}"{/if}><a href="clientarea.php?action=domains{if $q}&q={$q}{/if}&orderby=status">{$LANG.clientareastatus}</a></th>
            <th{if $orderby eq "autorenew"} class="headerSort{$sort}"{/if}><a href="clientarea.php?action=domains{if $q}&q={$q}{/if}&orderby=autorenew">{$LANG.domainsautorenew}</a></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
{foreach key=num item=domain from=$domains}
        <tr>
            <td class="textcenter">
                <label class="ceckbox">
                    <input type="checkbox" name="domids[]" class="domids" value="{$domain.id}" />
                    <span></span>
                </label>
            </td>
            <td><a href="http://{$domain.domain}/" target="_blank">{$domain.domain}</a></td>
            <td>{$domain.registrationdate}</td>
            <td>{$domain.nextduedate}</td>
            <td><span class="label {$domain.rawstatus}">{$domain.statustext}</span></td>
            <td>{if $domain.autorenew}{$LANG.domainsautorenewenabled}{else}{$LANG.domainsautorenewdisabled}{/if}</td>
            <td>
                <div class="btn-group">
                <a class="btn" href="clientarea.php?action=domaindetails&id={$domain.id}"> <i class="icon-wrench"></i> {$LANG.managedomain}</a>
                {if $domain.rawstatus == "active"}
                <a class="btn dropdown-toggle" href="#" data-toggle="dropdown"><span class="caret"></span></a>
                <ul class="dropdown-menu">
                    {if $domain.managens}<li><a href="clientarea.php?action=domaindetails&id={$domain.id}#tab3"><i class="icon-globe"></i> {$LANG.domainmanagens}</a></li>{/if}
                    <li><a href="clientarea.php?action=domaincontacts&domainid={$domain.id}"><i class="icon-user"></i> {$LANG.domaincontactinfoedit}</a></li>
                    <li><a href="clientarea.php?action=domaindetails&id={$domain.id}#tab2"><i class="icon-globe"></i> {$LANG.domainautorenewstatus}</a></li>
                    <li class="divider"></li>
                    <li><a href="clientarea.php?action=domaindetails&id={$domain.id}"><i class="icon-pencil"></i> {$LANG.managedomain}</a></li>
                </ul>
                {/if}
                </div>
            </td>
        </tr>
{foreachelse}
        <tr>
            <td colspan="7" class="textcenter">{$LANG.norecordsfound}</td>
        </tr>
{/foreach}
    </tbody>
</table>
<div class="container-padding-default">
    <div class="btn-group">
        <a class="btn" href="#" data-toggle="dropdown"></i> {$LANG.withselected}</a>
        <a class="btn dropdown-toggle" href="#" data-toggle="dropdown"><span class="caret"></span></a>
        <ul class="dropdown-menu">
            <li><a href="#" id="nameservers" class="setbulkaction">{$LANG.domainmanagens}</a></li>
            <li><a href="#" id="autorenew" class="setbulkaction">{$LANG.domainautorenewstatus}</a></li>
            <li><a href="#" id="reglock" class="setbulkaction">{$LANG.domainreglockstatus}</a></li>
            <li><a href="#" id="contactinfo" class="setbulkaction">{$LANG.domaincontactinfoedit}</a></li>
            {if $allowrenew}<li><a href="#" id="renew" class="setbulkaction">{$LANG.domainmassrenew}</a></li>{/if}
        </ul>
    </div>
</div>
</form>

{include file="$template/clientarearecordslimit.tpl" clientareaaction=$clientareaaction}

<div class="pagination">
    <ul>
        <li class="prev{if !$prevpage} disabled{/if}"><a href="{if $prevpage}clientarea.php?action=domains{if $q}&q={$q}{/if}&amp;page={$prevpage}{else}javascript:return false;{/if}">&larr; {$LANG.previouspage}</a></li>
        <li class="next{if !$nextpage} disabled{/if}"><a href="{if $nextpage}clientarea.php?action=domains{if $q}&q={$q}{/if}&amp;page={$nextpage}{else}javascript:return false;{/if}">{$LANG.nextpage} &rarr;</a></li>
    </ul>
</div>

</form>

<br />
<br />