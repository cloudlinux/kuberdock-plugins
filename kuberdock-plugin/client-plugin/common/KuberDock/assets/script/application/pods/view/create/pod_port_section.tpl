<tr>
    <td><input type="text" name="ports[<%- i %>][containerPort]" placeholder="Empty" value="<%- port.containerPort ? port.containerPort : port.number %>"></td>
    <td>
        <select name="ports[<%- i %>][protocol]">
        <% _.each(['tcp', 'udp'], function(protocol) { %>
            <option value="<%- protocol %>"<% protocol == port.protocol ? ' selected' : '' %>><%- protocol %></option>
        <% }); %>
        </select>
    </td>
    <td><input type="text" name="ports[<%- i %>][hostPort]" placeholder="Empty" value="<%- port.hostPort ? port.hostPort : port.number %>"></td>
    <td class="text-center">
        <input type="checkbox" name="ports[<%- i %>][isPublic]" class="is-public" value="1"<%- port.isPublic ? ' checked' : '' %>>
    </td>
    <td>
        <button type="button" class="btn btn-default btn-sm delete-port old-button">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </td>
</tr>