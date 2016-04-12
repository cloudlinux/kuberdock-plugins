<td>
    <a class="pod-details"><%- name %></a>
</td>
<td><%- model.getPublicIp() %></td>
<td><%- model.getPodIp() %></td>
<td><%- model.getStatus() %></td>
<td class="col-md-4">
    <button type="button" class="btn btn-<%- model.getButtonClass() %> btn-xs <%- model.getStatusClass() %>" title="<%- model.getStatusText() %>">
        <span class="glyphicon glyphicon-<%- model.getIconClass() %>" aria-hidden="true"></span>
        <span><%- model.getStatusText() %></span>
    </button>

    <button type="button" class="btn btn-primary btn-xs pod-edit" title="Edit">
        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
        <span>Edit</span>
    </button>

    <button type="button" class="btn btn-danger btn-xs pod-delete" data-target=".confirm-modal" title="Delete">
        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        <span>Delete</span>
    </button>
</td>