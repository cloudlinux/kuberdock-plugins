<div class="row">
    <div class="col-md-12">
        <h3>Give more power to your application.</h3>
        <p>
            Add kubes to allocate more resources for application "<strong><%- model.get('name') %></strong>"<br>
            Price per kube: <strong><%- model.getKubePrice(true) %></strong>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <form method="post" class="upgrade-form">
            <table class="table">
                <thead>
                <tr>
                    <th class="col-md-4">Image</th>
                    <th class="col-md-8">Kubes</th>
                </tr>
                </thead>
                <tbody>
                <% _.each(model.get('containers'), function(c) { %>
                <tr>
                    <th>
                        <b><%- c.image %></b>
                    </th>
                    <th>
                        <div class="slider"></div>
                        <div class="slider-value"></div>
                        <input type="hidden" name="<%- c.name %>_kubes" value="<%- c.kubes %>">
                    </th>
                </tr>
                <% }); %>
                </tbody>
            </table>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="center">
            <p><strong>Amount of allocated resources:</strong></p>

            <div class="resources">
                <span>CPU: X cores</span>
                <span>HDD: X GB</span>
                <span>RAM: X MB</span>
            </div>
        </div>

        <div class="pull-right">
            <strong>Total price: <span class="new-price"><%- model.getTotalPrice(true) %></span></strong>
        </div>
        <div class="clearfix"></div>

        <div style="margin: 15px auto;">
            <div class="pull-left">
                <a class="back">
                    <button class="btn btn-default">Cancel</button>
                </a>
            </div>

            <div class="pull-right">
                <button class="btn btn-primary pod-upgrade">Confirm upgrade</button>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>