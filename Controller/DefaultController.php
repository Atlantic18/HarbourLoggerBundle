<?php

namespace Harbour\LoggerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Coral\ContentBundle\Controller\ConfigurableJsonController;
use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Exception\JsonException;
use Coral\CoreBundle\Exception\AuthenticationException;

use Harbour\LoggerBundle\Entity\Message;

/**
 * @Route("/v1/logger")
 */
class DefaultController extends ConfigurableJsonController
{
    /**
     * @Route("/add")
     * @Method("POST")
     */
    public function addAction()
    {
        $request       = new JsonParser($this->get("request")->getContent(), true);
        $configuration = $this->getConfiguration('config-logger');

        $logLevel = $configuration->getOptionalParam($request->getMandatoryParam('service') . '.level', 'warning');
        $notify   = $configuration->getOptionalParam($request->getMandatoryParam('service') . '.notify');

        $this->throwNotFoundExceptionIf(!Message::isAllowedLevel($request->getMandatoryParam('level')), 'Unknown level used.');

        if(Message::isLevelEqualOrAbove($logLevel, $request->getMandatoryParam('level')))
        {
            $this->get('logger')->warning(
                'Logger: ' .
                $request->getMandatoryParam('level') . ' ' .
                $request->getMandatoryParam('service') . ' ' .
                $request->getMandatoryParam('message')
            );

            $message = new Message;
            $message->setLevel($request->getMandatoryParam('level'));
            $message->setService($request->getMandatoryParam('service'));
            $message->setMessage($request->getMandatoryParam('message'));
            $message->setAccount($this->getAccount());

            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();

            if($notify)
            {
                $message = \Swift_Message::newInstance()
                    ->setSubject(
                        '[Harbour Notification]: ' .
                        $request->getMandatoryParam('service') . '.' .
                        $request->getMandatoryParam('level')
                    )
                    ->setTo($notify)
                    ->setBody($request->getMandatoryParam('message'));
                $this->get('mailer')->send($message);
            }

            return $this->createCreatedJsonResponse($message->getId());
        }

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/status")
     * @Method("GET")
     */
    public function statusAction()
    {
        $logs = $this->getDoctrine()->getManager()->createQuery(
                'SELECT m.service, COUNT(m.id) AS service_count
                FROM HarbourLoggerBundle:Message m
                WHERE m.account = :account_id
                GROUP BY m.service'
            )
            ->setParameter('account_id', $this->getAccount()->getId())
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $items = array();
        foreach ($logs as $log) {
            $items[$log['service']] = $log['service_count'];
        }

        return $this->createListJsonResponse($items);
    }
}
