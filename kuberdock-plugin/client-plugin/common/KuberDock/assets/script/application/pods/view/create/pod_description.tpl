<pre>
    CPU: <%= cpu %>
    Memory: <%= memory %>
    Local storage: <%= hdd %>
    Traffic: <%= traffic %>
    <%- ip ? 'Public IP: yes' : '' %>
    <%- pd ? 'Persistent storage: ' + pd : '' %>
</pre>