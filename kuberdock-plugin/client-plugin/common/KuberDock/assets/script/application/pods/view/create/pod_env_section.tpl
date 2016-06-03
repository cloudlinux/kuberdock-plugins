<tr>
    <td><input type="text" name="env[<%- i %>][name]" placeholder="Empty" value="<%- env.name %>"></td>
    <td><input type="text" name="env[<%- i %>][value]" placeholder="Empty" value="<%- env.value %>"></td>
    <td>
        <button type="button" class="btn btn-default btn-sm delete-env">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </td>
</tr>