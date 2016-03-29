<div class="container-fluid content">
    <div class="row">
        <div class="col-md-10">
            <div class="page-header">
                <h2>Search applications</h2>

                <div id="templates"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            <p>Image registry: <a href="<%- imageRegistryURL %>" target="_blank"><%- imageRegistryURL %></a></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10" style="margin-bottom: 10px;">
            <div class="input-group">
                <input type="text" class="form-control" id="image" aria-label="..." placeholder="Search for apps" value="">
                <span class="input-group-btn">
                    <button class="btn btn-default image-search" type="button">
                        Search
                        <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                    </button>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            <table class="table table-bordered">
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>