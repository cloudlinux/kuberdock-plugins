<div class="form-group">
    <label for="<?php echo $variable?>" class="col-sm-3 control-label"><?php echo $data['description']?></label>

    <div class="col-sm-4">
        <input type="hidden" name="<?php echo $variable?>" id="<?php echo $variable?>" value="<?php echo $data['default']?>">
        <div class="kube-slider <?php echo $variable?>"></div><span class="kube-slider-value"></span>
    </div>
</div>