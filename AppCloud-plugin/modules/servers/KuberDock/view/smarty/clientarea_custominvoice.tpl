<div class="container" style="width: 40%; text-align: center">
    {if $error}
    <div class="alert alert-error">{$error}</div>
    {/if}

    <form method="post">
        <div class="row">
            <h2>Custom payment</h2>
        </div>

        <div class="row">
            <span class="bold" style="margin-right: 15px;">Make payment</span>

            <select name="payment">
                {foreach from=$paymentList item=payment}
                    <option value="{$payment}">{$currency->getFullPrice($payment)}</option>
                {/foreach}
            </select>
        </div>

        <div class="row left">
            <div class="center" style="text-align: center">
                <h2>Payment Method</h2>
            </div>

        {foreach from=$gateways item=gateway}
            <label class="radio">
                <input type="radio" name="gateway" value="{$gateway.module}" checked>
                {$gateway.displayname}
            </label>
        {/foreach}
        </div>

        <div class="row">
            <button class="btn btn-large btn-primary" type="submit">Pay Now</button>
        </div>
    </form>
</div>