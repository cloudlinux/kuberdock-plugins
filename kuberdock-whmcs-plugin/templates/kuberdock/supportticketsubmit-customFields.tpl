{foreach key=num item=customfield from=$customfields}
    <div class="control-group">
        <label class="control-label bold" for="customfield{$customfield.id}">{$customfield.name}</label>
        <div class="controls">
            {$customfield.input} {$customfield.description}
        </div>
    </div>
{/foreach}