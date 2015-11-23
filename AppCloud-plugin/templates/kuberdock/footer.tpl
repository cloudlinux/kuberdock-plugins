

{if $pagetitle eq $LANG.carttitle}</div>{/if}

    </div>
</div>

<div class="footerdivider">
    <div class="fill"></div>
</div>

<div class="whmcscontainer">
    <div class="footer clearfix">
        {if $langchange}<div id="languagechooser">{$setlanguage}</div>{/if}
        <div id="copyright">{$LANG.copyright} &copy; {$date_year} {$companyname}. {$LANG.allrightsreserved}.</div>
    </div>
</div>

{$footeroutput}

</body>
</html>