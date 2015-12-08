<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="content-type" content="text/html; charset={$charset}" />
        <title>{$companyname} - {* This code should be uncommented for EU companies using the sequential invoice numbering so that when unpaid it is shown as a proforma invoice {if $status eq "Paid"}*}{$LANG.invoicenumber}{*{else}{$LANG.proformainvoicenumber}{/if}*}{$invoicenum}</title>
        <link href="templates/{$template}/css/invoice.css" rel="stylesheet">
    </head>
    <body>
        <div class="wrapper">
            {if $error}
                <div class="creditbox">{$LANG.invoiceserror}</div>
            {else}
                <table class="header">
                    <tr>
                        <td width="45%" nowrap>
                            {if $logo}
                                <p><img src="{$logo}" title="{$companyname}" /></p>
                            {else}
                                <h1>{$companyname}</h1>
                            {/if}
                        </td>
                        <td width="10%"></td>
                        <td width="45%" align="left" class="details">
                            {if $status eq "Unpaid"}
                                <font class="unpaid">{$LANG.invoicesunpaid}</font>
                                {if $allowchangegateway}
                                    <form method="post" action="{$smarty.server.PHP_SELF}?id={$invoiceid}">{$gatewaydropdown}</form>
                                {else}
                                    {$paymentmethod}
                                {/if}
                                <div>{$paymentbutton}</div>
                            {elseif $status eq "Paid"}
                                <font class="paid">{$LANG.invoicespaid}</font>
                                {$paymentmethod}
                                <div>({$datepaid})</div>
                            {elseif $status eq "Refunded"}
                                <font class="refunded">{$LANG.invoicesrefunded}</font>
                            {elseif $status eq "Cancelled"}
                                <font class="cancelled">{$LANG.invoicescancelled}</font>
                            {elseif $status eq "Collections"}
                                <font class="collections">{$LANG.invoicescollections}</font>
                            {/if}
                        </td>
                    </tr>
                </table>
                <div class="details">
                    {if $smarty.get.paymentsuccess}
                        <div class="paid">{$LANG.invoicepaymentsuccessconfirmation}</div>
                    {elseif $smarty.get.pendingreview}
                        <div class="paid">{$LANG.invoicepaymentpendingreview}</div>
                    {elseif $smarty.get.paymentfailed}
                        <div class="unpaid">{$LANG.invoicepaymentfailedconfirmation}</div>
                    {elseif $offlinepaid}
                        <div class="refunded">{$LANG.invoiceofflinepaid}</div>
                    {/if}
                </div>
                {if $manualapplycredit}
                    <form method="post" action="{$smarty.server.PHP_SELF}?id={$invoiceid}">
                        <input type="hidden" name="applycredit" value="true" />
                        <div class="creditbox">
                            {$LANG.invoiceaddcreditdesc1} {$totalcredit}. {$LANG.invoiceaddcreditdesc2}<br />
                            {$LANG.invoiceaddcreditamount}: <input type="text" name="creditamount" size="10" value="{$creditamount}" />
                            <input type="submit" value="{$LANG.invoiceaddcreditapply}" />
                        </div>
                    </form>
                {/if}
                <table cellpadding="0" width="100%" cellspacing="0" class="items clean">
                    <tr>
                        <td width="45%" alignb="left"><strong class="title">{$LANG.invoicesinvoicedto}</strong></td>
                        <td width="10%"></td>
                        <td width="45%" align="left"><strong class="title">{$LANG.invoicespayto}</strong></td>
                    </tr>
                    {if $clientsdetails.companyname}
                        <tr>
                            <td width="45%" align="left">{$clientsdetails.companyname}</td>
                            <td width="10%"></td>
                            <td width="45%" align="left">{$payto}</td>
                        </tr>
                    {/if}
                    {if $clientsdetails.firstname}
                        <tr>
                            <td width="45%" align="left">{$clientsdetails.firstname} {$clientsdetails.lastname}</td>
                            <td width="10%"></td>
                            <td width="45%" align="right"></td>
                        </tr>
                    {/if}
                    {if $clientsdetails.address1}
                        <tr>
                            <td width="45%" align="left">{$clientsdetails.address1}, {$clientsdetails.address2}</td>
                            <td width="10%"></td>
                            <td width="45%" align="right"></td>
                        </tr>
                    {/if}
                    {if $clientsdetails.city}
                        <tr>
                            <td width="45%" align="left">{$clientsdetails.city}, {$clientsdetails.state}, {$clientsdetails.postcode}</td>
                            <td width="10%"></td>
                            <td width="45%" align="right"></td>
                        </tr>
                    {/if}
                    {if $clientsdetails.country}
                        <tr>
                            <td width="45%" align="left">{$clientsdetails.country}</td>
                            <td width="10%"></td>
                            <td width="45%" align="right"></td>
                        </tr>
                    {/if}
                    {if $customfields}
                        {foreach from=$customfields item=customfield}
                            <tr>
                                <td width="45%" align="left">{$customfield.fieldname}: {$customfield.value}</td>
                                <td width="10%"></td>
                                <td width="45%" align="right"></td>
                            </tr>
                        {/foreach}
                    {/if}
                </table>
                <div class="row textcenter">
                    <div class="title">{* This code should be uncommented for EU companies using the sequential invoice numbering so that when unpaid it is shown as a proforma invoice {if $status eq "Paid"}*}{$LANG.invoicenumber}{*{else}{$LANG.proformainvoicenumber}{/if}*}{$invoicenum}</div>
                    <div class="subtitle">{$LANG.invoicesdatecreated}: {$datecreated} {$LANG.invoicesdatedue}: {$datedue}</div>
                </div>
                <table class="items">
                    <tr class="title textcenter">
                        <td width="70%" align="left"><strong>{$LANG.invoicesdescription}</strong></td>
                        <td width="30%" align="right"><strong>{$LANG.invoicesamount}</strong></td>
                    </tr>
                    {foreach from=$invoiceitems item=item}
                        <tr>
                            <td class="textright" colspan="2">{$item.description}{if $item.taxed eq "true"} *{/if} {$item.amount}</td>
                        </tr>
                    {/foreach}
                    <tr>
                        <td colspan="2" class="textright subtotal">{$LANG.invoicessubtotal}: {$subtotal}</td>
                    </tr>
                    {if $taxrate}
                        <tr>
                            <td class="textright subtotal">{$taxrate}% {$taxname}: {$tax}</td>
                        </tr>
                    {/if}
                    {if $taxrate2}
                        <tr>
                            <td colspan="2" class="textright subtotal">{$taxrate2}% {$taxname2}: {$tax2}</td>
                        </tr>
                    {/if}
                    <tr>
                        <td colspan="2" class="textright subtotal">{$LANG.invoicescredit}: {$credit}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="textright total">{$LANG.invoicestotal}: {$total}</td>
                    </tr>
                </table>
                {if $taxrate}<p>* {$LANG.invoicestaxindicator}</p>{/if}
                <div class="row textcenter">
                    <div class="title">{$LANG.invoicestransactions}</div>
                </div>
                <table class="items">
                    <tr class="textcenter">
                        <td width="30%" align="left"><strong>{$LANG.invoicestransdate}</strong></td>
                        <td width="25%"><strong>{$LANG.invoicestransgateway}</strong></td>
                        <td width="25%"><strong>{$LANG.invoicestransid}</strong></td>
                        <td width="20%" align="right"><strong>{$LANG.invoicestransamount}</strong></td>
                    </tr>
                    {foreach from=$transactions item=transaction}
                        <tr>
                            <td>{$transaction.date}</td>
                            <td class="textcenter">{$transaction.gateway}</td>
                            <td class="textcenter">{$transaction.transid}</td>
                            <td class="textright">{$transaction.amount}</td>
                        </tr>
                    {foreachelse}
                        <tr>
                            <td class="textcenter" colspan="4">{$LANG.invoicestransnonefound}</td>
                        </tr>
                    {/foreach}
                    <tr>
                        <td class="textright total" colspan="4">{$LANG.invoicesbalance}: {$balance}</td>
                    </tr>
                </table>
                {if $notes}
                    <p>{$LANG.invoicesnotes}: {$notes}</p>
                {/if}
            {/if}
        </div>
        <p class="check-buttons textcenter">
            <a href="clientarea.php">{$LANG.invoicesbacktoclientarea}</a>
            <a href="dl.php?type=i&amp;id={$invoiceid}">{$LANG.invoicesdownload}</a>
            <a href="javascript:window.close()">{$LANG.closewindow}</a>
        </p>
    </body>
</html>