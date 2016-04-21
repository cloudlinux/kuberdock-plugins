<div class="container-fluid content preapp-install-page">
    <div class="row">
        <div class="kd-page-header">
            <div class="col-xs-1 nopadding"><span class="header-ico-preapp-install"></span></div>
            <h2><%= templateModel.getName() %></h2>
        </div>
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <p class="pre-app-desc"><%= templateModel.getPreDescription() %></p>
        </div>
    </div>

    <form class="form-horizontal app-install predefined" method="post">
        <input type="hidden" name="plan" id="plan" value="<%- model.get('plan') %>">
        <input type="hidden" name="package_id" id="package_id" value="<%- templateModel.getPackageId() %>">
        <input type="hidden" name="kube_type" id="kube_type" value="<%- kube.id %>">

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
            <% _.each(_.keys(model.attributes), function(variable) { %>
                <%= renderField(variable) %>
            <% }); %>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-11 col-xs-offset-1 nopadding">
                <div class="col-sm-5 nopadding">
                    <div class="product-description"></div>
                </div>
            </div>
        </div>

        <div class="row total-border-wrapper">
            <div class="total-border">Total:
                <span id="priceBlock">
                    <%- planPackage.prefix %><%- templateModel.getTotalPrice(model.get('plan')) %>
                    <%- planPackage.suffix %> / <%- planPackage.period %>
                </span>
            </div>
        </div>

        <div class="row" style="margin-bottom: 23px;">
            <div class="text-right">
                <a class="btn btn-primary select-plan">
                    Choose different package
                </a>

                <button type="submit" class="btn btn-primary start-button">Start your App</button>
            </div>
        </div>
    </form>
</div>