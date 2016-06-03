<td>
    <a class="pod-details"><%- name %></a>
</td>
<td><%- model.getPublicIp() %></td>
<td><%- model.getPodIp() %></td>
<td><%- model.getStatus() %></td>
<td class="col-md-2">
    <div class="dropdown kd-dropdown">
        <button class="btn btn-default dropdown-toggle btn-sm" type="button" data-toggle="kd-dropdown" aria-haspopup="true" aria-expanded="false">
            Сhoose action
            <span class="caret"></span>
        </button>

        <ul class="dropdown-menu">
            <li>
                <a href="#" class="<%- model.getStatusClass() %>" data-target=".confirm-modal">
                    <span class="glyphicon glyphicon-<%- model.getIconClass() %>" aria-hidden="true"></span>
                    <span><%- model.getStatusText() %></span>
                </a>
            </li>
            <li>
                <a href="#" class="pod-edit" title="Edit">
                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    <span>Edit</span>
                </a>
            </li>
            <li>
                <a type="button" class="pod-delete" data-target=".confirm-modal" title="Delete">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span>Delete</span>
                </a>
            </li>
        </ul>
    </div>
</td>