<?php

namespace Harbour\LoggerBundle\Tests\Controller;

use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Test\JsonTestCase;

class DefaultControllerTest extends JsonTestCase
{
    public function testStatus()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doGetRequest('/v1/logger/status');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(0, $jsonRequest->getMandatoryParam('items'));
    }

    public function testAddLevel()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "coral", "level": "debug", "message": "sample" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "mailer", "level": "warning", "message": "sample_warning" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        $client = $this->doGetRequest('/v1/logger/status');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(2, $jsonRequest->getMandatoryParam('items'));
        $this->assertEquals(1, $jsonRequest->getMandatoryParam('items.mailer'));
        $this->assertEquals(1, $jsonRequest->getMandatoryParam('items.coral'));
    }

    public function testAdd()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "coral", "level": "error", "message": "sample" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "mailer", "level": "info", "message": "sample" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $client = $this->doGetRequest('/v1/logger/status');
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(1, $jsonRequest->getMandatoryParam('items'));
        $services = $jsonRequest->getMandatoryParam('items');
        $this->assertTrue(isset($services['coral']));
        $this->assertEquals(1, $services['coral']);

        $client = $this->doAlternativeAccountGetRequest('/v1/logger/status');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(0, $jsonRequest->getMandatoryParam('items'));
    }

    public function testAddNotify()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $this->enableClientProfilerForNextRequest();
        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "coral", "level": "error", "message": "sample" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];

        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertRegExp('/coral.error/', $message->getSubject());
        $this->assertEquals('sample@email.not', key($message->getTo()));
        $this->assertEquals('sample', $message->getBody());

        $this->enableClientProfilerForNextRequest();
        $client = $this->doPostRequest(
            '/v1/logger/add',
            '{ "service": "mailer", "level": "error", "message": "sample" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }
}
