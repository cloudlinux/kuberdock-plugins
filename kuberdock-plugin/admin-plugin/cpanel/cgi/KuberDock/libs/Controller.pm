package Controller;

use strict;
use warnings FATAL => 'all';

use Whostmgr::ACLS;
use Whostmgr::HTMLInterface;
use Template;
use Data::Dumper;

use Resellers;
use PreApps;
use KCLI;

use constant KUBERDOCK_TEMPLATE_PATH => '/usr/local/cpanel/whostmgr/docroot/cgi/KuberDock/templates';

sub new {
    my $class = shift;
    my $form = shift;
    my $cgi = shift;

    my $self = {
        _form => $form,     # GET values
        _cgi => $cgi,
        _action => (defined $form->{'a'} ? $form->{'a'} : 'index') . 'Action',
        _template => Template->new({
            INCLUDE_PATH => KUBERDOCK_TEMPLATE_PATH,
            INTERPOLATE  => 1,
        }) || die "$Template::ERROR\n",
        _user => $ENV{'REMOTE_USER'},
    };

    Whostmgr::ACLS::init_acls();

    print "Content-type: text/html\n\n";
    Whostmgr::HTMLInterface::defheader('KuberDock', '/cgi/KuberDock/assets/images/kuberdock.png', '/cgi/addon_kuberdock.cgi');

    if(!Whostmgr::ACLS::hasroot() && !Whostmgr::Resellers::is_reseller($self->{_user})) {
        print qq(<div align="center"><h1>Permission denied</h1></div>);
        exit;
    }

    return bless $self, $class;
}

sub run() {
    my ($self) = @_;

    if($self->can($self->{_action})) {
        my $method = $self->{_action};
        $self->$method();
    } else {
        print 'Action not founded';
        return 0;
    }
}

sub render() {
    my ($self, $template, $vars) = @_;
    $vars = '' unless defined $vars;

    # Render template
    $self->{_template}->process($template, $vars) || die $self->{_template}->error(), "\n";
}

sub indexAction() {
    my ($self) = @_;
    my $resellers = Resellers->new();
    my $apps = PreApps->new($self->{_cgi});

    my $vars = {
        resellers => [$resellers->get()],
        apps => [$apps->getList()],
        data => $resellers->loadData(),
    };

    $self->render('index.tmpl', $vars);
}

sub addResellerAction() {
    my ($self) = @_;
    my $reseller = Resellers->new();
    my %data = (
        $self->{_cgi}->param('newOwner') => {
            server => $self->{_cgi}->param('newServer'),
            username => $self->{_cgi}->param('newUsername'),
            password => $self->{_cgi}->param('newPassword'),
        }
    );

    $reseller->save(%data);
    $self->indexAction();
}

sub saveResellerAction() {
    my ($self) = @_;
    my $resellers = Resellers->new();

    my %data = (
        ALL => {
            server => $self->{_cgi}->param('ALL:server'),
            username => $self->{_cgi}->param('ALL:username'),
            password => $self->{_cgi}->param('ALL:password'),
        }
    );

    $resellers->save(%data);
    $self->indexAction();
}

sub deleteResellerAction() {
    my ($self) = @_;
    my $owner = $self->{_form}->{'o'};

    my $reseller = Resellers->new();

    $reseller->delete($owner);
    $self->indexAction();
}

sub createAppAction() {
    my ($self) = @_;
    my $appName = $self->{_cgi}->param('app_name') || 'Undefined';
    my $app = PreApps->new($self->{_cgi});
    my $uploadYaml = $self->{_cgi}->param('yaml_file');
    my $appIcon = $self->{_cgi}->param('app_icon');
    my $code = $self->{_cgi}->param('code');
    my $vars = {
        yaml => $code || '#Some yaml',
        appName => $appName,
    };

    if($uploadYaml) {
        if($app->uploadFile('yaml_file', 'app.yaml', ('yaml'))) {
            my $yaml = $app->readYamlFile('app.yaml');
            $vars->{appName} = $yaml->{kuberdock}->{name} || $appName;
            $vars->{appId} = $yaml->{kuberdock}->{id} || $app->{'_appId'};
            $vars->{yaml} = $app->readYamlFile('app.yaml', 1),
        }
    }

    if(defined $self->{_cgi}->param('save') && $code) {
        my $yaml = $app->readYaml($code);
        $yaml->{kuberdock}->{name} = $appName;

        $app->saveYaml('app.yaml', $yaml);
        my $template = KCLI::createTemplate($app->getFilePath('app.yaml'));

        if(!$template) {
            return 0;
        }

        $app = $app->setTemplateId($template->{'id'});

        if($appIcon) {
            my $iconPath = $app->uploadFile('app_icon', $appIcon, ('png'));
            if($iconPath) {
                $yaml->{kuberdock}->{icon} = "${appIcon}";
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        }

        $app->saveYaml('app.yaml', $yaml);
        KCLI::updateTemplate($template->{'id'}, $app->getFilePath('app.yaml'));

        my $installData = {
            id => $app->{'_appId'},
            name => $appName,
            icon => $app->{'_appId'} . '_48.png',
        };
        $app->createInstall($installData);
        $vars->{'created'} = 1;
    }

    $self->render('pre-apps/add.tmpl', $vars);
}

sub updateAppAction() {
    my ($self) = @_;
    my $app = PreApps->new($self->{_cgi}, $self->{_form}->{'app'});
    my $uploadYaml = $self->{_cgi}->param('yaml_file');
    my $appIcon = $self->{_cgi}->param('app_icon');
    my $code = $self->{_cgi}->param('code');
    my $template = KCLI::getTemplate($app->{'_templateId'});

    if(!$template) {
        return 0;
    }

    my $appName = $self->{_cgi}->param('app_name') || 'Undefined';
    my $yaml = $app->readYaml($template->{'template'});
    my $vars = {
        yaml => $code || $template->{'template'},
        appName => $yaml->{kuberdock}->{name} || $appName,
        update => 1,
    };

    if($uploadYaml) {
        if($app->uploadFile('yaml_file', 'app.yaml', ('yaml'))) {
            my $yaml = $app->readYamlFile('app.yaml');
            $vars->{appName} = $yaml->{kuberdock}->{name} || $appName;
            $vars->{appId} = $yaml->{kuberdock}->{id} || $app->{'_appId'};
            $vars->{yaml} = $app->readYamlFile('app.yaml', 1),
        }
    }

    if(defined $self->{_cgi}->param('save') && $code) {
        my $yaml = $app->readYaml($code);
        $yaml->{kuberdock}->{name} = $appName;

        if($appIcon) {
            my $iconPath = $app->uploadFile('app_icon', $appIcon, ('png'));
            if($iconPath) {
                $yaml->{kuberdock}->{icon} = "${appIcon}";
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        }

        #my $installed = $app->isInstalled();
        #$app->uninstall();
        $app->saveYaml('app.yaml', $yaml);
        my $template = KCLI::updateTemplate($app->{'_templateId'}, $app->getFilePath('app.yaml'));

        my $installData = {
            id => $app->{'_appId'},
            name => $appName,
            icon => $app->{'_appId'} . '_48.png',
        };
        $app->createInstall($installData);
        $vars->{'created'} = 1;

        #if($installed) {
        #    $app->install();
        #}
    }

    $self->render('pre-apps/add.tmpl', $vars);
}

sub installAppAction() {
    my ($self) = @_;
    my $apps = PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->install();
    $self->indexAction();
}

sub uninstallAppAction() {
    my ($self) = @_;
    my $apps = PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->uninstall();
    $self->indexAction();
}

sub deleteAppAction() {
    my ($self) = @_;
    my $apps = PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->delete();
    $self->indexAction();
}

1;