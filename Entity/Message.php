<?php
namespace Harbour\LoggerBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="harbour_message", 
 *     indexes={
 *         @ORM\Index(name="LevelServiceIndex", columns={"level","service"}),
 *         @ORM\Index(name="ServiceIndex", columns={"service"}),
 *         @ORM\Index(name="ServiceCreatedIndex", columns={"service","created_at"}),
 *         @ORM\Index(name="ServiceLevelCreatedIndex", columns={"level","service","created_at"})
 *     }
 * )
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=8, nullable=false)
     */
    private $level;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $service;

    /**
     * @ORM\Column(type="string", length=1024, nullable=false)
     */
    private $message;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $created_at;

    /**
     * @ORM\ManyToOne(targetEntity="Coral\CoreBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     */
    private $account;

    /**
     * Log levels
     * @var array
     */
    private static $levels;

    public static function getLevels()
    {
        if(null === self::$levels)
        {
            self::$levels = array(
                1 => 'debug',
                2 => 'info',
                3 => 'warning',
                4 => 'error'
            );
        }

        return self::$levels;
    }

    public static function isAllowedLevel($level)
    {
        return in_array($level, self::getLevels());
    }

    public static function isLevelEqualOrAbove($referenceLevel, $levelToCompare)
    {
        if(self::isAllowedLevel($referenceLevel) && self::isAllowedLevel($levelToCompare))
        {
            $levels = array_flip(self::getLevels());
            return $levels[$referenceLevel] <= $levels[$levelToCompare];
        }

        return false;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set level
     *
     * @param string $level
     * @return Message
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set service
     *
     * @param string $service
     * @return Message
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Message
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Message
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set Account
     *
     * @param \Coral\CoreBundle\Entity\Account $account
     * @return Message
     */
    public function setAccount(\Coral\CoreBundle\Entity\Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get Account
     *
     * @return \Coral\CoreBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}