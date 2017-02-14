{include file="$template/pageheader.tpl" title=$LANG.announcementstitle}

{foreach key=num item=announcement from=$announcements}
<div class="textcenter">
    <h2><a href="{if $seofriendlyurls}announcements/{$announcement.id}/{$announcement.urlfriendlytitle}.html{else}{$smarty.server.PHP_SELF}?id={$announcement.id}{/if}">{$announcement.title}</a></h2>
    <p>{$announcement.timestamp|date_format:"%A, %B %e, %Y"}</p>
</div>
<br/>
<div class="container-padding-63">
    <p class="textcenter">
        {$announcement.text|strip_tags|truncate:400:"..."}
        {if strlen($announcement.text)>400} <a href="{if $seofriendlyurls}announcements/{$announcement.id}/{$announcement.urlfriendlytitle}.html{else}{$smarty.server.PHP_SELF}?id={$announcement.id}{/if}">{$LANG.more}...</a>{/if}
    </p>
</div>
<br/>


{if $facebookrecommend}
{literal}
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) {return;}
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
{/literal}
<div class="fb-like" data-href="{$systemurl}{if $seofriendlyurls}announcements/{$announcement.id}/{$announcement.urlfriendlytitle}.html{else}announcements.php?id={$announcement.id}{/if}" data-send="true" data-width="450" data-show-faces="true" data-action="recommend"></div>
{/if}

{foreachelse}
<div align="center">{$LANG.announcementsnone}</div>
{/foreach}

{if $prevpage || $nextpage}

<div style="float: left; width: 200px;">
{if $prevpage}<a href="announcements.php?page={$prevpage}">{/if}{$LANG.previouspage}{if $prevpage}</a>{/if}
</div>

<div style="float: right; width: 200px; text-align: right;">
{if $nextpage}<a href="announcements.php?page={$nextpage}">{/if}{$LANG.nextpage}{if $nextpage}</a>{/if}
</div>

{/if}
<!--
<p align="left" style="padding-left: 20px;"><img src="{$BASE_PATH_IMG}/rssfeed.gif" alt="RSS" align="absmiddle" /> <a href="announcementsrss.php">{$LANG.announcementsrss}</a></p> -->