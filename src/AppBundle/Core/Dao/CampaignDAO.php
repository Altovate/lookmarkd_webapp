<?php

namespace AppBundle\Core\Dao;


use AppBundle\Entity\Campaign;
use AppBundle\Entity\CampaignParticipants;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Monolog\Logger;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;

class CampaignDAO
{
    /**
     *
     * @var EntityManager
     */
    private $em;

    /**
     *
     * @var Logger
     */
    private $logger;

    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param $campaign Campaign
     * @return Campaign
     */
    public function saveOrUpdate($campaign)
    {
        $this->em->beginTransaction();
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->commit();
        return $campaign;
    }

    /**
     * @param $campaignParticipant CampaignParticipants
     * @return mixed CampaignParticipants
     */
    public function saveOrUpdateCampaignParticipant($campaignParticipant)
    {
        $this->em->beginTransaction();
        $this->em->persist($campaignParticipant);
        $this->em->flush();
        $this->em->commit();
        return $campaignParticipant;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getNewRequests($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('cp')
                ->from('AppBundle\Entity\CampaignParticipants', 'cp')
                ->join('cp.campaign', 'c')
                ->where('cp.participant = :user')
                ->andWhere('cp.status = :requestStatus')
                ->setParameter('user', $user)
                ->setParameter('requestStatus', CampaignParticipants::STATUS_REQUESTED)
                ->addSelect('c');
        return $qb->getQuery()->getResult();
    }

    public function updateCampaignParticipantStatus($campaignParticipantId, $status)
    {
        $q = $this->em->createQuery('UPDATE AppBundle\Entity\CampaignParticipants c SET c.status = :status WHERE c.id = :id');
        $q->setParameter('status', $status);
        $q->setParameter('id', $campaignParticipantId);
        $q->execute();
    }

    public function getAcceptedRequests($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('cp')
            ->from('AppBundle\Entity\CampaignParticipants', 'cp')
            ->join('cp.campaign', 'c')
            ->where('cp.participant = :user')
            ->andWhere('cp.status = :requestStatus')
            ->setParameter('user', $user)
            ->setParameter('requestStatus', CampaignParticipants::STATUS_ACCEPTED)
            ->addSelect('c')
            ->orderBy('c.start');
        return $qb->getQuery()->getResult();
    }

    public function getActiveRequestsCreatedByUser($user)
    {
        $currentDate = new \DateTime();
        $currentDate->setTimestamp(time());

        return $this->em->createQueryBuilder()
                            ->select('c')
                            ->from('AppBundle\Entity\Campaign', 'c')
            ->where('c.owner = :user')
            ->andWhere('c.start <= :currentDate')
            ->andWhere('c.end <= :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('user', $user)
            ->getQuery()->getResult();
    }

    public function getCampaignParticipantsForCampaignIds($campaignIds)
    {
        return $this->em->createQueryBuilder()
            ->select('cp')
            ->from('AppBundle\Entity\CampaignParticipants', 'cp')
            ->join('cp.campaign', 'c')
            ->where('c.id IN (:campaignIds)')
            ->setParameter('campaignIds', $campaignIds)
            ->getQuery()
            ->getResult();
    }
}