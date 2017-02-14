{include file="$template/pageheader.tpl" title=$LANG.knowledgebasetitle}

<p>{$breadcrumbnav}</p>

<br />

<h2>{$kbarticle.title}</h2>

<br />

<blockquote>
<br /><br />
{$kbarticle.text}
<br /><br />
</blockquote>

<form method="post" action="{if $seofriendlyurls}{$WEB_ROOT}/knowledgebase/{$kbarticle.id}/{$kbarticle.urlfriendlytitle}.html{else}knowledgebase.php?action=displayarticle&amp;id={$kbarticle.id}{/if}">
<input type="hidden" name="useful" value="vote">
<p>
{if $kbarticle.voted}
<strong>{$LANG.knowledgebaserating}</strong> {$kbarticle.useful} {$LANG.knowledgebaseratingtext} ({$kbarticle.votes} {$LANG.knowledgebasevotes})
{else}
<strong>{$LANG.knowledgebasehelpful}</strong> <select name="vote"><option value="yes">{$LANG.knowledgebaseyes}</option><option value="no">{$LANG.knowledgebaseno}</option></select> <input type="submit" value="{$LANG.knowledgebasevote}" class="btn" />
{/if}
</p>
</form>

<p><img src="{$BASE_PATH_IMG}/print.gif" align="absmiddle" alt="{$LANG.knowledgebaseprint}" /> <a href="#" onclick="window.print();return false">{$LANG.knowledgebaseprint}</a></p>

{if $kbarticles}

<div class="kbalsoread">{$LANG.knowledgebasealsoread}</div>

{foreach key=num item=kbarticle from=$kbarticles}
<div class="kbarticle">
<img src="{$BASE_PATH_IMG}/article.gif" align="middle" alt="" /> <strong><a href="{if $seofriendlyurls}knowledgebase/{$kbarticle.id}/{$kbarticle.urlfriendlytitle}.html{else}knowledgebase.php?action=displayarticle&amp;id={$kbarticle.id}{/if}">{$kbarticle.title}</a></strong> <span class="kbviews">({$LANG.knowledgebaseviews}: {$kbarticle.views})</span>
</div>
{/foreach}

{/if}

<br />
