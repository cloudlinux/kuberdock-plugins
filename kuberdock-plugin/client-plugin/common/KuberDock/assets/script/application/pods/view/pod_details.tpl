<div id="app-page" class="container-fluid content">
    <div class="row">
        <div class="col-md-12 splitter">
            <h2>Application "<%- model.get('name') %>"</h2>
        </div>
    </div>

    <div class="row pod-details">
        <div class="col-md-12">
            <div class="splitter">
                <table class="table apps-list app-table">
                    <thead>
                    <tr>
                        <th class="col-md-2">Limits</th>
                        <th class="col-md-2">Public IP</th>
                        <th class="col-md-2">Pod IP</th>
                        <th class="col-md-2">Status</th>
                        <th class="col-md-4">Actions</th>
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
                        <td><%- model.getPublicIp() %></td>
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
            </div>
            <div>
                <label class="title">Ports</label>
                <table class="table apps-list app-table">
                    <thead>
                    <tr>
                        <th class="col-md-3">Container</th>
                        <th class="col-md-3">Protocol</th>
                        <th class="col-md-3">Host
                            <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                  title="Host port is external port of a container used to access container port from using public_ip:host_port">
                            </span>
                        </th>
                        <th class="col-md-3">Public</th>
                    </tr>
                    </thead>
                    <tbody>
                <% _.each(model.get('containers'), function(container) { %>
                    <% _.each(container.ports, function(port) { %>
                    <tr>
                        <td><small><%- port.containerPort %></small></td>
                        <td><small><%- port.protocol %></small></td>
                        <td><small><%- port.hostPort ? port.hostPort : port.containerPort %></small></td>
                        <td><input type="checkbox" disabled<%- port.isPublic ? ' checked' : '' %>></td>
                    </tr>
                    <% }); %>
                <% }); %>
                    </tbody>
                </table>
            </div>

            <% if (!_.isEmpty(model.get('volumes'))) { %>
            <label class="title">Volumes</label>
            <table class="table apps-list app-table">
                <thead>
                <tr>
                    <th class="col-md-3">Container path</th>
                    <th class="col-md-3">Persistent</th>
                    <th class="col-md-3">Name</th>
                    <th class="col-md-3">Size (<%- kube.disk_space_units %>)</th>
                </tr>
                </thead>
                <tbody>
            <% _.each(model.get('containers'), function(container) { %>
                <% _.each(container.volumeMounts, function(volumeMount) {
                    var volume = model.getVolume(volumeMount.name);
                %>
                <tr>
                    <td><%- volumeMount.mountPath %></td>
                    <td><input type="checkbox" disabled<%- volume.persistentDisk ? ' checked' : '' %>></td>
                    <td><%- volume.persistentDisk ? volume.persistentDisk.pdName : '' %></td>
                    <td><%- volume.persistentDisk ? volume.persistentDisk.pdSize : '' %></td>
                </tr>
                <% }); %>
            <% }); %>
                </tbody>
            </table>
            <% } %>

            <%
                var environments = [];
                _.each(model.get('containers'), function(container) {
                    _.each(container.env, function(env) {
                        environments.push(env);
                    });
                });
            %>

            <% if (!_.isEmpty(environments)) { %>
            <div class="splitter last">
                <label class="title">Environment variables</label>
                <table class="table apps-list app-table">
                    <thead>
                    <tr>
                        <th class="col-md-6">Name</th>
                        <th class="col-md-6">Value</th>
                    </tr>
                    </thead>
                    <tbody>
                <% _.each(environments, function(env) { %>
                    <tr>
                        <td><small><%- env.name %></small></td>
                        <td><small><%- escape(env.value) %></small></td>
                    </tr>
                <% }); %>
                    </tbody>
                </table>

                <div class="total-price-wrapper">
                    <p class="total-price"><%- model.getTotalPrice(true) %></p>
                </div>
            </div>
            <% } %>

            <a class="pull-left btn btn-default back">Back</a>
            <a class="pull-right btn btn-primary pod-search">Add more apps</a>
            <div class="clearfix"></div>
        </div>
    </div>
</div>