{include file="$template/pageheader.tpl" title=$LANG.masspaytitle desc=$LANG.masspayintro}

<form method="post" action="clientarea.php?action=masspay" class="form-horizontal">
<input type="hidden" name="geninvoice" value="true" />

<br />

<table class="table custom striped">
    <thead>
        <tr>
            <th>{$LANG.invoicesdescription}</th>
            <th>{$LANG.invoicesamount}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$invoiceitems key=invid item=invoiceitem}
            <tr>
                <td colspan="2">
                    <strong>{$LANG.invoicenumber} {$invid}</strong>
                    <input type="hidden" name="invoiceids[]" value="{$invid}" />
                </td>
            </tr>
        {foreach from=$invoiceitem item=item}
            <tr>
                <td>{$item.description}</td>
                <td>{$item.amount}</td>
            </tr>
        {/foreach}
        {foreachelse}
            <tr>
                <td colspan="6" align="center">{$LANG.norecordsfound}</td>
            </tr>
        {/foreach}

    </tbody>
</table>
<div class="container-padding-default">
    <div class="textright">
        <div class="subtotal">
            <td style="text-align:right;">{$LANG.invoicessubtotal}:</td>
            <td>{$subtotal}</td>
        </div>
        {if $tax}
            <div class="tax">
                <td style="text-align:right;">{$LANG.invoicestax}:</td>
                <td>{$tax}</td>
            </div>
        {/if}
        {if $tax2}
            <div class="tax">
                <td style="text-align:right;">{$LANG.invoicestax} 2:</td>
                <td>{$tax2}</td>
            </div>
        {/if}
        {if $credit}
            <div class="credit">
                <td style="text-align:right;">{$LANG.invoicescredit}:</td>
                <td>{$credit}</td>
            </div>
        {/if}
        {if $partialpayments}
            <div class="credit">
                <td style="text-align:right;">{$LANG.invoicespartialpayments}:</td>
                <td>{$partialpayments}</td>
            </div>
        {/if}
        <div class="total">
            <td style="text-align:right;">{$LANG.invoicestotaldue}:</td>
            <td>{$total}</td>
        </div>
    </div>
</div>
<br/>
<br/>
<div class="textcenter">
    <div class="page-header">
        <div class="styled_title"><h1>{$LANG.orderpaymentmethod}</h1></div>
    </div>
    {foreach from=$gateways key=num item=gateway}
    <label class="radio inline">
    <input type="radio" class="radio inline" name="paymentmethod" value="{$gateway.sysname}"{if $gateway.sysname eq $defaultgateway} checked{/if} /> {$gateway.name}
    </label>
    {/foreach}
    <br/>
    <br/>
    <br/>
    <input type="submit" value="{$LANG.masspaymakepayment}" class="send-message" />
</div>
<br/>
<br/>

</form>