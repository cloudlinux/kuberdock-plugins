<div class="form-group">
    <label for="<?php echo $variable?>" class="col-sm-12"><?php echo $data['description']?></label>
    <div class="col-sm-5">
        <select name="<?php echo $variable?>" id="<?php echo $variable?>">
        <?php foreach($data['data'] as $row):
            list($domain, $directory) = $row;
        ?>
            <option value="<?php echo $domain?>"><?php echo $domain?></option>
        <?php endforeach;?>
        </select>
    </div>
</div>