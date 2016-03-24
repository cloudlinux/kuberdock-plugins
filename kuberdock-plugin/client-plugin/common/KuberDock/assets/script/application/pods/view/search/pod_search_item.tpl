<td>
    <div>
        <%- page * index %>. <strong class="text-info"><%- name %></strong>
    </div>

    <%- description %>

    <div class="hidden info">
        <p>
        <% for (var i=0; i < stars; i++) { %>
            <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
        <% } %>
        </p>

        <p><%- is_official ? 'Official' : 'Unofficial' %></p>
    </div>

    <p>
        <a href="http://<%- source_url %>" target="_blank">More details</a>
    </p>
</td>
<td class="col-md-2" align="center">
    <a href="" class="btn btn-default install-app">Install</a>
</td>