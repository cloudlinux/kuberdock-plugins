<div id="app-page" class="container-fluid content">
    <div class="row">
        <div class="col-md-12 splitter">
            <h2>Application "<%- model.get('name') %>"</h2>
        </div>

        <div class="clearfix"></div>

        <% if (showDescription()) { %>
        <div class="alert alert-dismissible alert-success"  role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <pre><%= model.getPostDescription() %></pre>
        </div>
        <% } %>
    </div>

    <div class="row pod-details">
        <div class="col-md-12">
            <div class="splitter">
                <table class="table apps-list app-table">
                    <thead>
                    <tr>
                        <th class="col-md-4">Limits</th>
                        <th class="col-md-2">Public IP</th>
                        <th class="col-md-2">Pod IP</th>
                        <th class="col-md-2">Status</th>
                        <th class="col-md-2">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            CPU: <%- (kube.cpu * kubes).toFixed(2) %> <%- kube.cpu_units %><br>
                            Local storage: <%- kube.disk_space * kubes %> <%- kube.disk_space_units %><br>
                            Memory: <%- kube.memory * kubes %> <%- kube.memory_units %><br>
                            Traffic: <%- kube.included_traffic * kubes %> <%- kube.disk_space_units %><br>
                            Kube Type: <%- kube.name %> <br>
                            Number of Kubes: <%- kubes %>

                            <div class="top-offset">
                                <a class="pod-upgrade">
                                    <button title="Upgrade" class="btn btn-primary btn-xs" type="button">
                                        <span aria-hidden="true" class="glyphicon glyphicon-refresh"></span>
                                        <span>Upgrade</span>
                                    </button>
                                </a>
                            </div>
                        </td>
                        <td>
                            <%- model.getPublicIp() %>
                            <br>
                            <%
                                var ports = [];
                                _.each(model.get('containers'), function(e) {
                                    _.each(e.ports, function(p) {
                                        ports.push(p.containerPort);
                                    });
                                });
                            %>
                            <% if (!_.isEmpty(ports)) { %>Available ports: <%- ports.join(', ') %> <% } %>
                        </td>
                        <td><%- model.getPodIp() %></td>
                        <td><%- model.getStatus() %></td>
                        <td>
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
                                        <a href="#" class="pod-restart" data-target=".restart-modal" title="Restart">
                                            <span class="glyphicon glyphicon-eject" aria-hidden="true"></span>
                                            <span>Restart</span>
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
                    </tr>
                    </tbody>
                </table>

                <div class="total-price-wrapper">
                    <p class="total-price"><%- model.getTotalPrice(true) %></p>
                </div>
            </div>

            <a class="pull-left btn btn-default back">Back</a>
            <a href="#predefined/new/<%- model.get('template_id') %>" class="pull-right btn btn-primary predefined-new">Add more apps</a>
            <div class="clearfix"></div>
        </div>
    </div>
</div>