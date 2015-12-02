package KuberDock::PreApps;

use strict;
use warnings FATAL => 'all';

use YAML;
use YAML::Tiny;
use YAML::Syck;
use Cpanel::Binaries;
use File::Basename;
use File::Fetch;
use Archive::Tar;
use LWP::UserAgent;

use KuberDock::JSON;
use KuberDock::KCLI;
use KuberDock::Exception;
use Data::Dumper;

use constant KUBERDOCK_APPS_DIR => '.kuberdock_pre_apps';
use constant KUBERDOCK_MAIN_APP_FILE => 'install.json';
use constant KUBERDOCK_DEFAULT_ICON => '/usr/local/cpanel/whostmgr/docroot/cgi/KuberDock/assets/images/default.png';
use constant CPANEL_INSTALL_PLUGIN=> '/usr/local/cpanel/scripts/install_plugin';

local $YAML::CompressSeries = 1;

sub new {
    my $class = shift;
    my $self = {
        _cgi => shift,
        _templateId => shift,
        _appsDir => glob('~/' . KUBERDOCK_APPS_DIR),
    };

    $self->{_appId} = 'kuberdock_' . ($self->{'_templateId'} || 'undefined'),
    $self->{_appDir} = $self->{_appsDir} . '/' . $self->{_appId};

    return bless $self, $class;
}

sub getAppDir() {
    my ($self) = @_;

    if(!-d $self->{_appsDir}) {
        mkdir($self->{_appsDir});
    }

    if(!-d $self->{_appDir}) {
        mkdir($self->{_appDir});
    }

    return $self->{_appDir};
}

sub getFilePath() {
    my ($self, $fileName) = @_;
    $fileName = 'Undefined' if !defined $fileName;

    return $self->getAppDir() . '/' .  $fileName;
}

sub getList() {
    my ($self) = @_;
    my $templates = KuberDock::KCLI::getTemplates();
    my @data;

    return @data if !defined $templates || scalar @$templates eq 0;

    foreach my $i (@$templates) {
        my $yaml = $self->readYaml($i->{'template'});
        my $appId = 'kuberdock_' . $i->{'id'};
        my $path = $self->{_appsDir} . '/'. $appId . '/install.json';

        $i->{'installed'} = -e dirname($path) . '/' . 'installed' ? 1 : 0;
        if(!-e dirname($path)) {
            $i->{'kuberdock'} = 1;
            $i->{'name'} = 'Template created in KuberDock. For use it in cPanel, please update it.';
        } else {
            $i->{'name'} = $yaml->{'kuberdock'}->{'name'} || 'Undefined';
        }

        $i->{'appId'} = $appId;

        push @data, $i;
    }

    return @data;
}

sub uploadFile() {
    my ($self, $inputName, $fileName, @allowed) = @_;
    my $buffer;
    my $bytesRead;
    my $type = '';
    my $fName;

    if(@allowed) {
        my $cd = $self->{_cgi}->uploadInfo($self->{_cgi}->param($inputName))->{'Content-Disposition'};
        if($fName = $self->getFileName($cd)) {
            $type = (split /\./, $fName)[1];
        }
        if(!grep {$_ eq $type} @allowed) {
            my $json = KuberDock::JSON->new;
            print $json->encode({
                error => 1,
                message => "Type '${type}' not allowed",
            });
            return;
        }
    }

    my $fh = $self->{_cgi}->upload($inputName);
    my $appPath = $self->getFilePath($fileName);

    if(defined $fh) {
        my $io_handle = $fh->handle;
        open(FILE, '>', $appPath);
        while($bytesRead = $io_handle->read($buffer, 1024)) {
            print FILE $buffer;
        }
        close(FILE);
    }

    return $appPath;
}

sub uploadFileByUrl() {
    my ($self, $url, @allowed) = @_;
    my $type = '';
    my $fileName;

    if($fileName = $self->getFileName($url)) {
        $type = (split /\./, $fileName)[1];
    }

    if(@allowed) {
        if(!grep {$_ eq $type} @allowed) {
            die "Type '${type}' not allowed";
        }
    }

    my $agent = LWP::UserAgent->new(ssl_opts => { verify_hostname => 0 },);
    my $req = HTTP::Request->new(GET => $url);
    my $request = $agent->request($req);

    if($request->{_rc} != 200) {
        die 'Cannot upload image by url ' . $url;
    }

    my $content = $request->content;

    open FILE, ">", $self->getFilePath($fileName) or die "$!";
    binmode FILE;
    print FILE $content;
    close FILE;

    return $self->getFilePath($fileName);
}

sub resizeImage() {
    my ($self, $file, $newFile, $width, $height, $keepOld) = @_;
    $keepOld = $keepOld ? 1: 0;
    my $convertBin = Cpanel::Binaries::get_binary_location('convert');

    if(!-e $file) {
        print 'Image file not exists.';
        return 0;
    }

    $self->execute($convertBin, '-size', "${width}x${height}", $file, '-resize', "${width}x${height}", $newFile);
    if(!$keepOld) {
        $self->execute('/bin/rm', '-f', $file);
    }
}

sub readYaml() {
    my ($self, $data) = @_;

    $data = 'Empty data' if !defined $data;

    return YAML::Syck::Load($data);
}

sub readYamlFile() {
    my ($self, $fileName, $asText) = @_;
    my $json = KuberDock::JSON->new;
    my $path = $self->getFilePath($fileName);
    my $yaml;

    #$yaml = YAML::LoadFile($path);
    $yaml = YAML::Tiny->read($path);

    if(defined $asText && $asText) {
        return $json->readFile($path);
    }

    return $yaml->[0];
}

sub saveYaml() {
    my ($self, $file, $data) = @_;
    my $path = $self->getFilePath($file);

    YAML::DumpFile($path, $data);
}

sub createInstall() {
    my ($self, $data) = @_;
    my $json = KuberDock::JSON->new;
    my $path = $self->getFilePath('install.json');

    my $defaults = {
        group_id => 'kuberdock_apps',
        name => 'App name',
        icon => 'default.png',
        order => 999,
        type => 'link',
        id => 'kuberdock-id',
        uri => 'KuberDock/kuberdock.live.php?c=app&a=installPredefined&template='.$self->{'_templateId'},
    };

    if(!-e $self->getFilePath($data->{icon})) {
        $self->execute('/bin/cp', KUBERDOCK_DEFAULT_ICON, $self->getFilePath('default.png'));
        $self->resizeImage($self->getFilePath('default.png'), $self->getFilePath($self->{_appId} . '_32.png'), 32, 32, 1);
        $self->resizeImage($self->getFilePath('default.png'), $self->getFilePath($self->{_appId} . '_48.png'), 48, 48);
        $data->{'icon'} = $self->{_appId} . '_48.png';
    }

    $json->saveFile($path, {%$defaults, %$data});
}

sub install() {
    my ($self) = @_;

    if(-e $self->getFilePath('installed')) {
        KuberDock::Exception::throw('Plugin already installed');
        return 0;
    }
    my $json = KuberDock::JSON->new();
    my $details = $json->loadFile($self->getFilePath('install.json'));
    my $tar = Archive::Tar->new();

    $tar->add_data('install.json', $json->readFile($self->getFilePath('install.json')));

    if($details->{icon} && -e $self->getFilePath($details->{icon})) {
        $tar->add_data($details->{icon}, $json->readFile($self->getFilePath($details->{icon})));
    }

    my $tarPath = $self->getFilePath($self->{_appId} . '.tgz');
    $tar->write($tarPath, COMPRESS_GZIP);

    if(-e $tarPath) {
        $self->execute(CPANEL_INSTALL_PLUGIN, $tarPath);
        $self->execute('/bin/touch', $self->getFilePath('installed'));
        $self->execute('/bin/cp', $self->getFilePath($self->{_appId} . '_32.png'),
            '/usr/local/cpanel/base/frontend/x3/branding/'.$self->{_appId}.'.png');
        $self->execute('/usr/local/cpanel/bin/rebuild_sprites', '-force');
    }

    return 1;
}

sub uninstall() {
    my ($self) = @_;

    if(!-e $self->getFilePath('installed')) {
        KuberDock::Exception::throw('Plugin already uninstalled');
        return 0;
    }
    my $json = KuberDock::JSON->new();
    my $details = $json->loadFile($self->getFilePath('install.json'));

    foreach my $theme ('x3', 'paper_lantern') {
        my $pluginPath = '/usr/local/cpanel/base/frontend/'. $theme .'/dynamicui/dynamicui_' . $details->{id} . '.conf';
        if(-e $pluginPath) {
            $self->execute('/bin/rm', '-f', $pluginPath);
        }
    }

    $self->execute('/bin/rm', '-f', $self->getFilePath('installed'));
    $self->execute('/usr/local/cpanel/bin/rebuild_sprites', '-force');

    return 1;
}

sub delete() {
    my ($self) = @_;

    if(-e $self->getFilePath('installed')) {
        $self->uninstall();
    }

    $self->execute('/bin/rm', '-R', $self->getAppDir());
    KuberDock::KCLI::deleteTemplate($self->{'_templateId'});
}

sub execute() {
    my $self = shift;
    push @_, '>/dev/null 2>&1';
    system(join(' ', @_));
}

sub getFileName() {
    my ($self, $data) = @_;

    if($data =~ /(filename="(.+)\.(\w+)")|(.*\/(.+)\.(\w+))$/) {
        if($2) {
            return $2 . '.' . lc($3);
        } else {
            return $5 . '.' . lc($6);
        }
    }

    return 0;
}

sub setTemplateId {
    my ($self, $id) = @_;
    $self->{'_templateId'} = $id;
    $self->{'_appId'} = 'kuberdock_' . $id;
    $self->{_appDir} = $self->{_appsDir} . '/' . $self->{_appId};

    return $self;
}

sub isInstalled {
    my ($self) = @_;
    return -e $self->getFilePath('installed');
}

1;