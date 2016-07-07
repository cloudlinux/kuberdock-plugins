package KuberDock::Controller;

use strict;
use warnings FATAL => 'all';

use Whostmgr::ACLS;
use Whostmgr::HTMLInterface;
use Template;
use CGI::Session;

use KuberDock::PreApps;
use KuberDock::KubeCliConf;
use KuberDock::KCLI;
use KuberDock::API;
use KuberDock::Exception;
use KuberDock::JSON;
use KuberDock::Validate;
use Data::Dumper;

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

    $self->{_session} = new CGI::Session("driver:File", undef, {Directory=>"/tmp"});
    my $cookie = $cgi->cookie(CGISESSID => $self->{_session}->id);

    if(defined $form->{reqType} && $form->{reqType} eq 'json') {
        print "Content-type: application/json\n\n";
    } else {
        print $self->{_cgi}->header( -cookie => $cookie );
        Whostmgr::HTMLInterface::defheader('KuberDock', '/cgi/KuberDock/assets/images/kuberdock.png', '/cgi/addon_kuberdock.cgi');
    }

    if(!Whostmgr::ACLS::hasroot()) {
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
        print 'Action not found';
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
    my ($self, $activeTab) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi});
    my $api = KuberDock::API->new;
    my $json = KuberDock::JSON->new;
    my $cubeCliConf = KuberDock::KubeCliConf->new;
    my @packagesKubes;
    my $appName = $self->{_cgi}->param('app_name') || '';
    my $code = $self->{_cgi}->param('code');
    my $success = '';

    eval {
        @packagesKubes = $api->getPackagesKubes();
    };

    if($@) {
        $self->render('index.tmpl', {
            kubeCli => defined $self->{_cgi}->param('kubecli_url')
                ? $cubeCliConf->readCGI($self->{_cgi}) : $cubeCliConf->read,
            error => 1,
        });
        return;
    }

    my $defaults = $api->getDefaults();

    my $version = `rpm -q kuberdock-plugin`;
    $version =~ s/(kuberdock-plugin-)//;

    if ($self->{_session}->param('success')) {
        $success = $self->{_session}->param('success');
        $self->{_session}->param('success', '');
    }

    my $vars = {
        apps => [$apps->getList()],
        packagesKubes => $json->encode(@packagesKubes, 1),
        defaults => $json->encode($defaults, 1),
        kubeCli => $cubeCliConf->read,
        action => 'addon_kuberdock.cgi?a=createApp#create',
        yaml => $code || '# Please input your yaml here',
        appName => $appName,
        activeTab => $activeTab,
        version => $version,
        success => $success,
    };

    $self->render('index.tmpl', $vars);
}

sub createAppAction() {
    my ($self) = @_;
    my $appName = $self->{_cgi}->param('app_name');
    my $app = KuberDock::PreApps->new($self->{_cgi});
    my $uploadYaml = $self->{_cgi}->param('yaml_file');
    my $code = $self->{_cgi}->param('code');
    my $api = KuberDock::API->new;
    my $defaults = $api->getDefaults() || {};
    my $json = KuberDock::JSON->new;
    my $vars = {
        yaml => $code || '# Please input your yaml here',
        appName => $appName,
    };

    my $validator = KuberDock::Validate->new;
    my %rules = (
        app_name => { required => 1, min => 2, max => 64, alphanum => 1 },
    );
    my %vars = $self->{_cgi}->Vars;

    eval {
        $validator->validate(\%vars, \%rules);
    };

    if($@) {
        KuberDock::Exception::throw($@);
        $self->indexAction('#create');
        exit 0;
    }

    if($uploadYaml) {
        if($app->uploadFile('yaml_file', 'app.yaml')) {
            my $yaml;
            eval {
                $yaml = $app->readYamlFile('app.yaml');
            };

            $vars->{appName} = $yaml->{kuberdock}->{name} || $appName;
            $vars->{appId} = $yaml->{kuberdock}->{id} || $app->{'_appId'};
            if($@) {
                $vars->{error} = $@;
            } else {
                $vars->{yaml} = $app->readYamlFile('app.yaml', 1);
                $vars->{error} = 0;
            }

            print $json->encode($vars);
        }

        return;
    }

    if(defined $self->{_cgi}->param('save') && $code) {
        my $yaml;
        eval {
            $yaml = $app->readYaml($code);
        };

        if($@) {
            KuberDock::Exception::throw($@);
            $self->indexAction();
            return 0;
        }

        eval {
            $yaml->{kuberdock}->{name} = $appName;
        };

        if ($@) {
            if ($@ =~ "Can't use string") {
                KuberDock::Exception::throw('Wrong YAML syntax.');
            } else {
                KuberDock::Exception::throw($@);
            }
            $self->indexAction();
            return 0;
        }

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
                $iconPath = $app->uploadFileByUrl($yaml->{'kuberdock'}->{'icon'}, ('png'));
            };
            if($@) {
                KuberDock::Exception::throw($@);
                $self->render('pre-apps/form.tmpl', $vars);
                return 0;
            }
            if($iconPath) {
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        } elsif(-e $app->getFilePath($app->{'_appId'} . '_48.png')) {
            $app->executeSilent('/bin/rm -f ' . $app->getFilePath($app->{'_appId'} . '_48.png'));
        }

        $yaml->{kuberdock}->{'kuberdock_template_id'} = $template->{id};

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
    my $api = KuberDock::API->new;
    my $defaults = $api->getDefaults() || {};

    my $json = KuberDock::JSON->new;

    #if(!%{$template}) {
    #    KuberDock::Exception::throw('Template not founded');
    #    return 0;
    #}

    my $appName = $self->{_cgi}->param('app_name');
    my $yaml = $app->readYaml($template->{'template'});

    my $vars = {
        yaml => $code || $template->{'template'},
        appName => $yaml->{kuberdock}->{name} || $appName,
        update => 1,
        action => 'addon_kuberdock.cgi?a=updateApp&app=' . $app->{_templateId},
    };

    my $validator = KuberDock::Validate->new;
    my %rules = (
        app_name => { required => 1, min => 2, max => 64, alphanum => 1 },
    );
    my %vars = $self->{_cgi}->Vars;

    eval {
        $validator->validate(\%vars, \%rules);
    };

    if($@) {
        KuberDock::Exception::throw($@);
        $self->render('pre-apps/form.tmpl', $vars);
        exit 0;
    }

    if($uploadYaml) {
        if($app->uploadFile('yaml_file', 'app.yaml')) {
            my $yaml;
            eval {
                $yaml = $app->readYamlFile('app.yaml');
            };

            $vars->{appName} = $yaml->{kuberdock}->{name} || $appName;
            $vars->{appId} = $yaml->{kuberdock}->{id} || $app->{'_appId'};
            if($@) {
                $vars->{error} = $@;
            } else {
                $vars->{yaml} = $app->readYamlFile('app.yaml', 1);
                $vars->{error} = 0;
            }

            print $json->encode($vars);
        }

        return;
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
                $iconPath = $app->uploadFileByUrl($yaml->{'kuberdock'}->{'icon'}, ('png'));
            };
            if($@) {
                KuberDock::Exception::throw($@);
                $self->render('pre-apps/form.tmpl', $vars);
                return 0;
            }

            if($iconPath) {
                # fix x3 theme icon
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_32.png'), 32, 32, 1);
                $app->resizeImage($iconPath, $app->getFilePath($app->{'_appId'} . '_48.png'), 48, 48);
            }
        } elsif(-e $app->getFilePath($app->{'_appId'} . '_48.png')) {
            $app->executeSilent('/bin/rm -f ' . $app->getFilePath($app->{'_appId'} . '_48.png'));
        }

        $yaml->{kuberdock}->{'kuberdock_template_id'} = $template->{id};

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
    $self->{_session}->param('success', 'Application installed.');
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

sub uninstallAppAction() {
    my ($self) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->uninstall();
    $self->{_session}->param('success', 'Application uninstalled.');
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

sub deleteAppAction() {
    my ($self) = @_;
    my $apps = KuberDock::PreApps->new($self->{_cgi}, $self->{_cgi}->param('app'));

    $apps->delete();
    $self->{_session}->param('success', 'Application deleted.');
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#pre_apps');
}

sub getPackageKubesAction {
    my ($self) = @_;
    my $api = KuberDock::API->new;
    my $json = KuberDock::JSON->new;
    my $packageId = $self->{_form}->{packageId};

    my $response = $api->getPackageKubes($packageId);
    print $json->encode($response);
}

sub validateYamlAction {
    my ($self) = @_;
    my $api = KuberDock::API->new;
    my $json = KuberDock::JSON->new;
    my $template = $self->{_cgi}->param('template');

    my $response = $api->validateYaml($template);
    print $json->encode($response);
}

sub setDefaultsAction {
    my ($self) = @_;
    my $api = KuberDock::API->new;
    my $packageId = $self->{_cgi}->param('packageId');
    my $kubeType = $self->{_cgi}->param('kubeType');

    my $kubes = $api->getPackageKubesById($packageId);

    if(!grep {$_ eq $kubeType} @$kubes) {
        KuberDock::Exception::throw('There is no such kube type in package');
        $self->indexAction();
        exit;
    }

    my $data = {
        packageId => $packageId,
        kubeType => $kubeType,
    };

    $api->setDefaults($data);
    $self->{_session}->param('success', 'Settings saved.');
    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#defaults');
}

sub updateKubecliAction {
    my ($self) = @_;
    my $cubeCliConf = KuberDock::KubeCliConf->new;
    my $data = {
        url => $self->{_cgi}->param('kubecli_url'),
        user => $self->{_cgi}->param('kubecli_user'),
        password => $self->{_cgi}->param('kubecli_password'),
        registry => $self->{_cgi}->param('kubecli_registry'),
    };

    my $validator = KuberDock::Validate->new;
    my %rules = (
        kubecli_user => { required => 1, max => 50 },
        kubecli_password => { required => 1, max => 25 },
        kubecli_url => { required => 1, url => 1, max => 50 },
        kubecli_registry  => { required => 1, url => 1, max => 50 },
    );
    my %vars = $self->{_cgi}->Vars;

    eval {
        $validator->validate(\%vars, \%rules);
    };

    if($@) {
        KuberDock::Exception::throw($@);
        $self->indexAction('#kubecli');
        exit 0;
    }

    $cubeCliConf->save($data);
    $self->{_session}->param('success', 'Connection to KuberDock server was successful. Settings saved.');

    Whostmgr::HTMLInterface::redirect('addon_kuberdock.cgi#kubecli');
}

1;