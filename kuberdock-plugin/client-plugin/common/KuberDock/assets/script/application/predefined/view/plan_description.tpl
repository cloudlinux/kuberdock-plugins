<div class="product-description hidden">
    <b>CPU:</b> <%- (kubes * kube.cpu).toFixed(2) %> <%- kube.cpu_units %><br>
    <b>Memory:</b> <%- kubes * kube.memory %> <%- kube.memory_units %><br>
    <b>Storage:</b> <%- kubes * kube.disk_space %> <%- kube.disk_space_units %><br>

<% if(pdSize) { %>
    <b>Persistent Storage:</b> <%- pdSize %> <%- kube.disk_space_units %><br>
<% } %>

<% if(publicIP) { %>
    <b>Public IP:</b> yes<br>
<% } %>
</div>