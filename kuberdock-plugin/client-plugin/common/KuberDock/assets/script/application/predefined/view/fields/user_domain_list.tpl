<div class="form-group">
    <label for="<%- variable %>" class="col-sm-12"><%- description %></label>
    <div class="col-sm-5">
        <select name="<%- variable %>" id="<%- variable %>">
        <% _.each(data, function(e) {
            var [domain, dir] = e;
        %>
            <option value="<%- domain %>"><%- domain %></option>
        <% }); %>
        </select>
    </div>
</div>