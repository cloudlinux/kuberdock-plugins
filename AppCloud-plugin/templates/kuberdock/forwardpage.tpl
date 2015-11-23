<br /><br />

<div class="alert alert-block alert-warn textcenter">
    <p>{$message}</p>
</div>

<p class="textcenter"><img src="{$BASE_PATH_IMG}/loading.gif" alt="Loading" border="0" /></p>

<br />

<div id="submitfrm">

    <div align="center" class="textcenter">{$code}</div>

    <form method="post" action="{if $invoiceid}viewinvoice.php?id={$invoiceid}{else}clientarea.php{/if}">
    </form>

</div>

<br /><br /><br />

{literal}
<script language="javascript">
setTimeout("autoForward()", 5000);
function autoForward() {
    var submitForm = $("#submitfrm").find("form:first");
    submitForm.submit();
}
</script>
{/literal}
