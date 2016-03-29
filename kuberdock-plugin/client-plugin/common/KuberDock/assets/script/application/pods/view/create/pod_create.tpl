<div class="container-fluid content" id="app-page">
    <div class="container-fluid content">
        <div class="row">
            <p>
                You are about to launch new dedicated application. Please, select amount of resources you want to make
                available for that application. After your application will be launched it will be available during
                one minute.
            </p>

            <div class="page-header">
                <h2>Setup "<%- model.get('image') %>"</h2>
            </div>
        </div>

        <div class="row">
            <form class="form-horizontal pod-install" method="post">
                <input type="hidden" name="image" value="<%- model.get('image') %>">
                <input type="hidden" name="kube_count" id="kube_count" value="1">
                <input type="hidden" name="package_id" id="package_id" value="0">

                <div class="form-group">
                    <label for="kube_id" class="col-sm-3 control-label">Type</label>

                    <div class="col-sm-4">
                        <select class="form-control" name="kube_id" id="kube_id">
                            <% _.each(model.get('kubes'), function(kube) { %>
                            <option value="<%- kube.id %>" data-pid="<%- kube.package_id %>">
                                <%- kube.name %> (<%- kube.package_name %>)
                            </option>
                            <% }); %>
                        </select>
                    </div>

                    <div class="col-sm-4">
                        <div class="product-description"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kube_count" class="col-sm-3 control-label">Size</label>

                    <div class="col-sm-4">
                        <div class="kube-slider"></div>
                        <span class="kube-slider-value"></span>
                    </div>
                </div>

                <div class="form-group port-section">
                    <label class="col-sm-3 control-label">Ports</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_port">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add port
                            </button>
                        </p>

                        <table class="table table-stripped table-condensed" id="port_table">
                            <tr>
                                <th><small>Container port</small></th>
                                <th><small>Protocol</small></th>
                                <th><small>
                                    Host port
                                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                          title="Host port is external port of a container used to access container port from using public_ip:host_port">
                                    </span>
                                </small></th>
                                <th><small>
                                    Public
                                    <span class="glyphicon glyphicon-info-sign" aria-hidden="true" data-toggle="tooltip" data-placement="right"
                                          title="Provides application public IP for marked ports">
                                    </span>
                                </small></th>
                                <th></th>
                            </tr>

                            <% _.each(model.get('ports'), function(port, i) { %>
                                <%= renderPort(port, i) %>
                            <% }); %>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Environment variables</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_env">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add variable
                            </button>

                            <a href="http://<%- model.get('sourceUrl') %>" target="_blank" class="env-more-link">Learn more about this application</a>
                        </p>

                        <table class="table table-stripped table-condensed" id="env_table">
                            <tr>
                                <th><small>Name</small></th>
                                <th><small>Value</small></th>
                                <th></th>
                            </tr>

                            <% _.each(model.get('env'), function(env, i) { %>
                                <%= renderEnv(env, i) %>
                            <% }); %>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Volumes</label>

                    <div class="col-sm-8">
                        <p style="padding-top: 7px;">
                            <button type="button" class="btn btn-default btn-xs" id="add_volume">
                                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span> Add volume
                            </button>
                        </p>

                        <table class="table table-stripped table-condensed<?php echo !isset($pod->containers[0]['volumeMounts']) ? ' hidden' : ''?>" id="volume_table">
                            <tr>
                                <th><small>Container path</small></th>
                                <th><small>Persistent</small></th>
                                <th><small>Disc name</small></th>
                                <th><small>Disc size</small></th>
                                <th></th>
                                <th></th>
                            </tr>

                            <% _.each(model.get('volumeMounts'), function(volume, i) { %>
                                <%= renderVolume(volume, i) %>
                            <% }); %>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <strong>Total: </strong><span id="priceBlock"></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12 text-center">
                        <button type="submit" class="btn btn-primary start-button">
                            Start your App
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>