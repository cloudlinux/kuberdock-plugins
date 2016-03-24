<tr>
    <td><input type="text" class="middle" name="volume[<%- i %>][mountPath]" placeholder="Empty" value="<%- volume.mountPath %>"></td>
    <td class="text-center">
        <input type="checkbox" name="volume[<%- i %>][persistent]" class="set-persistent" value="1">
    </td>
    <td>
        <input type="text" name="volume[<%- i %>][name]" class="short volume-name" autocomplete="off" placeholder="Empty" disabled>
        <div class="pd-list iselect-popup"></div>
    </td>
    <td>
        <input type="text" name="volume[<%- i %>][size]" class="short volume-size" placeholder="Empty" disabled>
    </td>
    <td class="text-center"><small>GB</small></td>
    <td>
        <button type="button" class="btn btn-default btn-sm delete-volume old-button">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </td>
</tr>