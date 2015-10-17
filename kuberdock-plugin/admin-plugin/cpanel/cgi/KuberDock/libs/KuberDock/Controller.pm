package KuberDock::Controller;

use strict;
use warnings FATAL => 'all';

use Whostmgr::ACLS;
use Whostmgr::HTMLInterface;
use Template;
use LWP::UserAgent;
use Data::Dumper;

use KuberDock::Resellers;
use KuberDock::PreApps;
use KuberDock::KCLI;

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
    my $resellers = KuberDock::Resellers->new();
    my $apps = KuberDock::PreApps->new($self->{_cgi});

    my $vars = {
        resellers => [$resellers->get()],
        apps => [$apps->getList()],
        data => $resellers->loadData(),
    };

    $self->render('index.tmpl', $vars);
}

sub addResellerAction() {
    my ($self) = @_;
    my $reseller = KuberDock::Resellers->new();
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
    my $resellers = KuberDock::Resellers->new();

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

    my $reseller = KuberDock::Resellers->new();

    $reseller->delete($owner);
    $self->indexAction();
}

sub createAppAction() {
    my ($self) = @_;
    my $appName = $self->{_cgi}->param('app_name') || 'Undefined';
    my $app = KuberDock::PreApps->new($self->{_cgi});
    my $uploadYaml = $self->{_cgi}->param('yaml_file');
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
        my $yaml;
        eval {
            $yaml = $app->readYaml($code);
        };

        if($@) {
            KuberDock::Exception::throw($@);
            $self->render('pre-apps/form.tmpl', $vars);
            return 0;
        }
        $yaml->{kuberdock}->{name} = $appName;

        $app->saveYaml('app.yaml', $yaml);
        my $template = KuberDock::KCLI::createTemplate($app->getFilePath('app.yaml'), $appName);

        if(!$template) {
            KuberDock::Exception::throw('Cannot create template in KuberDock');
            $self->render('pre-apps/form.tmpl', $vars);
            return 0;
        }

        $app = $app->setTemplateId($template->{'id'});

        if(defined $yaml->{kuberdock}->{'icon'} && $yaml->{kuberdock}->{'icon'}) {
            my $iconPath;
            eval {
                $iconPath = $app->uploadFileByUrl($yaml->{'kuberdock'}->{'icon'});
            };
            if($@) {
                KuberDock::Exception::throw('Cannot upload icon by link or link used https connection');
                $self->render('pre-apps/form.tmpl', $vars);
                return 0;
            }
            if($iconPath) {
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        }

        $app->saveYaml('app.yaml', $yaml);
        KuberDock::KCLI::updateTemplate($template->{'id'}, $app->getFilePath('app.yaml'), $appName);

        my $installData = {
            id => $app->{'_appId'},
            name => $appName,
            icon => $app->{'_appId'} . '_48.png',
        };
        $app->createInstall($installData);
        Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
    }

    $self->render('pre-apps/form.tmpl', $vars);
}

sub updateAppAction() {
    my ($self) = @_;
    my $app = KuberDock::PreApps->new($self->{_cgi}, $self->{_form}->{'app'});
    my $uploadYaml = $self->{_cgi}->param('yaml_file');
    my $code = $self->{_cgi}->param('code');
    my $template = KuberDock::KCLI::getTemplate($app->{'_templateId'});

    #if(!%{$template}) {
    #    KuberDock::Exception::throw('Template not founded');
    #    return 0;
    #}

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
        my $yaml;
        eval {
            $yaml = $app->readYaml($code);
        };

        if($@) {
            KuberDock::Exception::throw($@);
            $self->render('pre-apps/form.tmpl', $vars);
            return 0;
        }

        $yaml->{kuberdock}->{name} = $appName;

        if(defined $yaml->{kuberdock}->{'icon'} && $yaml->{kuberdock}->{'icon'}) {
            my $iconPath;
            eval {
                $iconPath = $app->uploadFileByUrl($yaml->{'kuberdock'}->{'icon'});
            };
            if($@) {
                KuberDock::Exception::throw('Cannot upload icon by link or link used https connection');
                $self->render('pre-apps/form.tmpl', $vars);
                return 0;
            }

            if($iconPath) {
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        }

        $app->saveYaml('app.yaml', $yaml);
        my $template = KuberDock::KCLI::updateTemplate($app->{'_templateId'}, $app->getFilePath('app.yaml'), $appName);

        my $installData = {
            id => $app->{'_appId'},
            name => $appName,
            icon => $app->{'_appId'} . '_48.png',
        };
        $app->createInstall($installData);
        Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
    }

    $self->render('pre-apps/form.tmpl', $vars);
}

sub installAppAction() {
    my ($self) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->install();
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

sub uninstallAppAction() {
    my ($self) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->uninstall();
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

sub deleteAppAction() {
    my ($self) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->delete();
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

1;