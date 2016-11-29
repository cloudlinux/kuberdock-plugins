<?php

namespace tests\Kuberdock\classes;


use Kuberdock\classes\exceptions\CException;
use tests\MakePublicTrait;
use PHPUnit\Framework\TestCase;
use Kuberdock\classes\KcliCommand;


class KcliCommandTest extends TestCase
{
    use MakePublicTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|KcliCommand
     */
    protected $stub;

    public function setUp()
    {
        $this->setTestedClass(KcliCommand::class);

        $this->stub = $this->getMockBuilder(KcliCommand::class)
            ->setMethods([
                'getUserConfigPath',
                'execute',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->stub->expects($this->any())
            ->method('getUserConfigPath')
            ->with($this->equalTo(false))
            ->willReturn('.kubecli.conf');


        $this->stub->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function () {
                $args = func_get_args();
                $method = $this->getMethod('getAuth');
                $args = array_merge($args, $method->invoke($this->stub));
                $method = $this->getMethod('getCommandString');

                return $method->invokeArgs($this->stub, $args);
            }));

        $reflectedClass = new \ReflectionClass(KcliCommand::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($this->stub, 'token');
    }

    public function testConstructor()
    {
        $this->assertEquals('token', $this->stub->getToken());
        $this->assertEquals('.kubecli.conf', $this->stub->getUserConfigPath());
    }

    public function testGetAuth()
    {
        $method = $this->getMethod('getAuth');
        $this->assertEquals([
            '-c' => '.kubecli.conf',
        ], $method->invoke($this->stub));
    }

    public function testReturnTypeProperty()
    {
        $property = $this->getProperty('returnType');
        $this->assertEquals('--json', $property->getValue($this->stub));
    }

    public function testCommandPathProperty()
    {
        $property = $this->getProperty('commandPath');
        $this->assertEquals('/usr/bin/kcli', $property->getValue($this->stub));
    }

    public function testSetGetToken()
    {
        $this->assertEquals($this->stub, $this->stub->setToken('new_token'));
        $this->assertEquals('new_token', $this->stub->getToken());
    }

    public function testGetPods()
    {
        $this->assertEquals('/usr/bin/kcli --json kubectl get pods', $this->stub->getPods());
    }

    public function testGetPod()
    {
        $this->assertEquals("/usr/bin/kcli --json kubectl get pods 'pod 1'", $this->stub->getPod('pod 1'));
    }

    public function testDescribePod()
    {
        $this->assertEquals("/usr/bin/kcli --json kubectl describe pods 'pod 1'", $this->stub->describePod('pod 1'));
    }

    public function testDeletePod()
    {
        $this->assertEquals("/usr/bin/kcli --json kubectl delete 'pod 1'", $this->stub->deletePod('pod 1'));
    }

    public function testGetKubes()
    {
        //$this->assertEquals("/usr/bin/kcli --json kuberdock kube-types", $this->stub->getKubes());
        $this->markTestSkipped('Refactor');
    }

    public function testGetContainers()
    {
        $this->assertEquals('/usr/bin/kcli --json container list', $this->stub->getContainers());
    }

    public function testCreateContainer()
    {
        $response = $this->stub->createContainer('pod 1', 'nginx', 'Standard', 2);

        $this->assertEquals([
            0 => '--json',
            1 => 'kuberdock',
            'create' => "'pod 1'",
            '-C' => 'nginx',
            '--kube-type' => "'Standard'",
            '--kubes' => 2,
        ], $response);
    }

    public function testSetContainerPorts()
    {
        $response = $this->stub->setContainerPorts('pod 1', 'nginx', [
            [
                'isPublic' => 1,
                'containerPort' => 80,
                'hostPort' => 81,
                'protocol' => 'tcp',
            ],
            [
                'isPublic' => 0,
                'containerPort' => 443,
                'hostPort' => 444,
                'protocol' => 'udp',
            ]
        ], 2);
        $equal = "/usr/bin/kcli --json kuberdock set 'pod 1' -C nginx --container-port +80:81:tcp,443:444:udp --kubes 2";

        $this->assertEquals($equal, $response);
    }

    public function testSetContainerEnvVars()
    {
        $response = $this->stub->setContainerEnvVars('pod 1', 'nginx', [
            [
                'name' => 'name 1',
                'value' => 'value 1',
            ],
            [
                'name' => 'name 2',
                'value' => 'value 2',
            ]
        ], 2);
        $equal = "/usr/bin/kcli --json kuberdock set 'pod 1' -C nginx --env 'name 1:value 1,name 2:value 2' --kubes 2";

        $this->assertEquals($equal, $response);
    }

    public function testSetMountPath()
    {
        $response = $this->stub->setMountPath('pod 1', 'nginx', 0, [
            'mountPath' => '/path1',
        ], 2);
        $equal = "/usr/bin/kcli --json kuberdock set 'pod 1' -C nginx --mount-path /path1 --index 0 --kubes 2";

        $this->assertEquals($equal, $response);
    }

    public function testSetMountPathPersistentException()
    {
        $this->expectException(CException::class);

        $this->stub->setMountPath('pod 1', 'nginx', 0, [
            'mountPath' => '/path1',
            'persistent' => 1,
        ], 2);
    }

    public function testSaveContainer()
    {
        $this->assertEquals("/usr/bin/kcli --json kuberdock save 'pod 1'", $this->stub->saveContainer('pod 1'));
    }

    public function testStartContainer()
    {
        $this->assertEquals("/usr/bin/kcli --json kuberdock start 'pod 1'", $this->stub->startContainer('pod 1'));
    }

    public function testStopContainer()
    {
        $this->assertEquals("/usr/bin/kcli --json kuberdock stop 'pod 1'", $this->stub->stopContainer('pod 1'));
    }

    public function testDeleteContainer()
    {
        $this->assertEquals("/usr/bin/kcli --json kuberdock delete 'pod 1'", $this->stub->deleteContainer('pod 1'));
    }

    public function testSearchImages()
    {
        $response = $this->stub->searchImages('nginx', 2);
        $this->assertEquals("/usr/bin/kcli --json kuberdock search nginx -p 2", $response);
    }

    public function testSearchImagesEmptyName()
    {
        $response = $this->stub->searchImages('');
        $this->assertEquals(array(), $response);
    }

    public function testGetImage()
    {
        $response = $this->stub->getImage('nginx');
        $this->assertEquals('/usr/bin/kcli --json kuberdock image_info "nginx"', $response);
    }

    public function testAddPersistentDrive()
    {
        $response = $this->stub->addPersistentDrive('storage_name', 2);
        $this->assertEquals('/usr/bin/kcli --json kuberdock drives add storage_name --size 2', $response);
    }

    public function testDeletePersistentDrive()
    {
        $response = $this->stub->deletePersistentDrive('storage_name');
        $this->assertEquals('/usr/bin/kcli --json kuberdock drives delete storage_name', $response);
    }

    public function testGetPersistentDrives()
    {
        $response = $this->stub->getPersistentDrives();
        $this->assertEquals('/usr/bin/kcli --json kuberdock drives list', $response);
    }

    public function testGetYamlTemplate()
    {
        $response = $this->stub->getYAMLTemplate(1);
        $this->assertEquals('/usr/bin/kcli --json kubectl get template --id 1', $response);
    }

    public function testGetYamlTemplates()
    {
        $response = $this->stub->getYAMLTemplates();
        $this->assertEquals('/usr/bin/kcli --json kubectl get templates --origin cpanel', $response);
    }

    public function testCreatePodFromYaml()
    {
        $response = $this->stub->createPodFromYaml('yaml_path');
        $this->assertEquals('/usr/bin/kcli --json kubectl create pod --file yaml_path', $response);
    }
}