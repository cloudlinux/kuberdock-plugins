<table class="form kuber-settings-table" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr class="trial-form">
        <?php echo $product->renderConfigOption('enableTrial', $product->getConfigOption('enableTrial'), array('id' => 'enableTrial'))?>
        <?php echo $product->renderConfigOption('trialTime', $product->getConfigOption('trialTime'), array('id' => 'trialTime'))?>
    </tr>

    <tr>
        <?php echo $product->renderConfigOption('firstDeposit', $product->getConfigOption('firstDeposit'))?>
        <?php echo $product->renderConfigOption('paymentType', $product->getConfigOption('paymentType'))?>
    </tr>

    <tr>
        <?php echo $product->renderConfigOption('debug', $product->getConfigOption('debug'))?>
    </tr>

    <tr>
        <?php echo $product->renderConfigOption('priceIP', $product->getConfigOption('priceIP'))?>
    </tr>

    <tr>
        <?php echo $product->renderConfigOption('pricePersistentStorage', $product->getConfigOption('pricePersistentStorage'))?>
        <?php echo $product->renderConfigOption('priceOverTraffic', $product->getConfigOption('priceOverTraffic'))?>
    </tr>
</table>

<script>
    function trialManage() {
        if($('#enableTrial').prop('checked')) {
            $('.trial-form').find('td:gt(1)').show();
            $('.kuber-settings-table').find('tr:gt(0)').hide();
        } else {
            $('.trial-form').find('td:gt(1)').hide();
            $('.kuber-settings-table').find('tr:gt(0)').show();
        }
    }

    $(document).ready(function() {
        trialManage();
    });

    $('.kuber-settings-table').on('click', '#enableTrial', function(e) {
        trialManage();
    });
</script>