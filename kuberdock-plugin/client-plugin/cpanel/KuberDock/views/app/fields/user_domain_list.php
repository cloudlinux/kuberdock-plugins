<div class="form-group">
    <label for="<?php echo $variable?>" class="col-sm-3 control-label"><?php echo $data['description']?></label>
    <div class="col-sm-4">
        <select name="<?php echo $variable?>" id="<?php echo $variable?>">
            <option value="<?php echo $data['data']['main_domain']['domain']?>">
                <?php echo $data['data']['main_domain']['domain']?>
            </option>
        <?php if(isset($data['data']['sub_domains'])):?>
            <?php foreach($data['data']['sub_domains'] as $row):?>
                <option value="<?php echo $row['domain']?>"><?php echo $row['domain']?></option>
            <?php endforeach;?>
        <?php endif;?>
        </select>
    </div>
</div>