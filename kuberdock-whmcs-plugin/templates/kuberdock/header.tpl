<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset={$charset}" />
    <title>{if $kbarticle.title}{$kbarticle.title} - {/if}{$pagetitle} - {$companyname}</title>

    {if $systemurl}<base href="{$systemurl}" />
    {/if}<script type="text/javascript" src="{$BASE_PATH_JS}/jquery.min.js"></script>
    {if $livehelpjs}{$livehelpjs}
    {/if}
    <link href="templates/{$template}/css/bootstrap.css" rel="stylesheet">
    <link href="templates/{$template}/css/whmcs.css" rel="stylesheet">

    <script src="templates/{$template}/js/whmcs.js"></script>

    {$headoutput}

  </head>

  <body>

{$headeroutput}

<div id="whmcsheader">
    <div class="whmcscontainer">
        <div id="whmcstxtlogo"><a href="index.php">{$companyname}</a></div>
        <div id="whmcsimglogo"><a href="index.php"><img src="templates/{$template}/img/whmcslogo.png" alt="{$companyname}" /></a></div>
    </div>
</div>

  <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container">
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <div class="nav-collapse">
        <ul class="nav">
            <li><a id="Menu-Home" href="{if $loggedin}clientarea{else}index{/if}.php">{$LANG.hometitle}</a></li>
        </ul>
{if $loggedin}
    <ul class="nav">
        <li class="dropdown"><a id="Menu-Services" class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navservices}&nbsp;<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a id="Menu-Services-My_Services" href="clientarea.php?action=products">{$LANG.clientareanavservices}</a></li>
            {if $condlinks.pmaddon}<li><a id="Menu-Services-My_Projects" href="index.php?m=project_management">{$LANG.clientareaprojects}</a></li>{/if}
            <li class="divider"></li>
            <li><a id="Menu-Services-Order_New_Services" href="cart.php">{$LANG.navservicesorder}</a></li>
            <li><a id="Menu-Services-View_Available_Addons" href="cart.php?gid=addons">{$LANG.clientareaviewaddons}</a></li>
          </ul>
        </li>
      </ul>


          {if $condlinks.domainreg || $condlinks.domaintrans}<ul class="nav">
            <li class="dropdown"><a id="Menu-Domains" class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navdomains}&nbsp;<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a id="Menu-Domains-My_Domains" href="clientarea.php?action=domains">{$LANG.clientareanavdomains}</a></li>
                <li class="divider"></li>
                <li><a id="Menu-Domains-Renew_Domains" href="cart.php?gid=renewals">{$LANG.navrenewdomains}</a></li>
                {if $condlinks.domainreg}<li><a id="Menu-Domains-Register_a_New_Domain" href="cart.php?a=add&domain=register">{$LANG.navregisterdomain}</a></li>{/if}
                {if $condlinks.domaintrans}<li><a id="Menu-Domains-Transfer_Domains_to_Us" href="cart.php?a=add&domain=transfer">{$LANG.navtransferdomain}</a></li>{/if}
                {if $enomnewtldsenabled}<li><a id="Menu-Domains-Preregister_New_TLDs" href="{$enomnewtldslink}">Preregister New TLDs</a></li>{/if}
                <li class="divider"></li>
                <li><a id="Menu-Domains-Whois_Lookup" href="domainchecker.php">{$LANG.domainlookupbutton}</a></li>
              </ul>
            </li>
          </ul>{/if}

          <ul class="nav">
            <li class="dropdown"><a id="Menu-Billing" class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navbilling}&nbsp;<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a id="Menu-Billing-My_Invoices" href="clientarea.php?action=invoices">{$LANG.invoices}</a></li>
                <li><a id="Menu-Billing-My_Quotes" href="clientarea.php?action=quotes">{$LANG.quotestitle}</a></li>
                <li class="divider"></li>
                {if $condlinks.addfunds}<li><a id="Menu-Billing-Add_Funds" href="clientarea.php?action=addfunds">{$LANG.addfunds}</a></li>{/if}
                {if $condlinks.masspay}<li><a id="Menu-Billing-Mass_Payment" href="clientarea.php?action=masspay&all=true">{$LANG.masspaytitle}</a></li>{/if}
                {if $condlinks.updatecc}<li><a id="Menu-Billing-Manage_Credit_Card" href="clientarea.php?action=creditcard">{$LANG.navmanagecc}</a></li>{/if}
              </ul>
            </li>
          </ul>

          <ul class="nav">
            <li class="dropdown"><a id="Menu-Support" class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.navsupport}&nbsp;<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a id="Menu-Support-Tickets" href="supporttickets.php">{$LANG.navtickets}</a></li>
                <li><a id="Menu-Support-Knowledgebase" href="knowledgebase.php">{$LANG.knowledgebasetitle}</a></li>
                <li><a id="Menu-Support-Downloads" href="downloads.php">{$LANG.downloadstitle}</a></li>
                <li><a id="Menu-Support-Network_Status" href="serverstatus.php">{$LANG.networkstatustitle}</a></li>
              </ul>
            </li>
          </ul>

          <ul class="nav">
            <li><a id="Menu-Open_Ticket" href="submitticket.php">{$LANG.navopenticket}</a></li>
          </ul>

          {if $condlinks.affiliates}<ul class="nav">
            <li><a id="Menu-Affiliates" href="affiliates.php">{$LANG.affiliatestitle}</a></li>
          </ul>{/if}

{if $livehelp}
          <ul class="nav">
            <li><a id="Menu-Live_Chat" href="#" class="LiveHelpButton">Live Chat - <span class="LiveHelpTextStatus">Offline</span></a></li>
          </ul>
{/if}

          <ul class="nav pull-right">
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" id="Menu-Hello_User">{$LANG.hello}, {$loggedinuser.firstname}!&nbsp;<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a id="Menu-Hello_User-Edit_Account_Details" href="clientarea.php?action=details">{$LANG.editaccountdetails}</a></li>
                {if $condlinks.updatecc}<li><a id="Menu-Hello_User-Contacts_Sub-Accounts" href="clientarea.php?action=creditcard">{$LANG.navmanagecc}</a></li>{/if}
                <li><a href="clientarea.php?action=contacts">{$LANG.clientareanavcontacts}</a></li>
                {if $condlinks.addfunds}<li><a id="Menu-Hello_User-Add_Funds" href="clientarea.php?action=addfunds">{$LANG.addfunds}</a></li>{/if}
                <li><a id="Menu-Hello_User-Email_History" href="clientarea.php?action=emails">{$LANG.navemailssent}</a></li>
                <li><a id="Menu-Hello_User-Change_Password" href="clientarea.php?action=changepw">{$LANG.clientareanavchangepw}</a></li>
                <li class="divider"></li>
                <li><a id="Menu-Hello_User-Logout" href="logout.php">{$LANG.logouttitle}</a></li>
              </ul>
            </li>
          </ul>
{else}
          <ul class="nav">
            <li><a id="Menu-Annoucements" href="announcements.php">{$LANG.announcementstitle}</a></li>
          </ul>

          <ul class="nav">
            <li><a id="Menu-Knowledgebase" href="knowledgebase.php">{$LANG.knowledgebasetitle}</a></li>
          </ul>

          <ul class="nav">
            <li><a id="Menu-Network_Status" href="serverstatus.php">{$LANG.networkstatustitle}</a></li>
          </ul>

          <ul class="nav">
            <li><a id="Menu-Affiliates" href="affiliates.php">{$LANG.affiliatestitle}</a></li>
          </ul>

          <ul class="nav">
            <li><a id="Menu-Contact_Us" href="contact.php">{$LANG.contactus}</a></li>
          </ul>

{if $livehelp}
          <ul class="nav">
            <li><a id="Menu-Live_Chat" href="#" class="LiveHelpButton">Live Chat - <span class="LiveHelpTextStatus">Offline</span></a></li>
          </ul>
{/if}

          <ul class="nav pull-right">
            <li class="dropdown"><a id="Menu-Account" class="dropdown-toggle" data-toggle="dropdown" href="#">{$LANG.account}&nbsp;<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a id="Menu-Account-Login" href="clientarea.php">{$LANG.login}</a></li>
                {if $condlinks.allowClientRegistration}
                    <li><a id="Menu-Account-Register" href="register.php">{$LANG.register}</a></li>
                {/if}
                <li class="divider"></li>
                <li><a id="Menu-Account-Forgot_Password" href="pwreset.php">{$LANG.forgotpw}</a></li>
              </ul>
            </li>
          </ul>
{/if}

        </div><!-- /.nav-collapse -->
      </div>
    </div><!-- /navbar-inner -->
  </div><!-- /navbar -->


<div class="whmcscontainer">
    <div class="contentpadded">

{if $pagetitle eq $LANG.carttitle}<div id="whmcsorderfrm">{/if}

