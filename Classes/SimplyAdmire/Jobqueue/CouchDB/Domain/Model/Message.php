<?php
namespace SimplyAdmire\Jobqueue\CouchDB\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ODM\CouchDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(indexed=true)
 */
class Message extends \TYPO3\Jobqueue\Common\Queue\Message {

	/**
	 * @var string
	 * @ODM\Id(type="string")
	 */
	protected $id;

	/**
	 * @var string Identifier of the message
	 * @ODM\Field(type="string")
	 * @ODM\Index
	 */
	protected $identifier;

	/**
	 * @var mixed The message payload
	 * @ODM\Field(type="mixed")
	 */
	protected $payload;

	/**
	 * @var integer State of the message, one of the Message::STATE_* constants
	 * @ODM\Field(type="integer")
	 */
	protected $state = self::STATE_NEW;

	/**
	 * @Flow\Transient
	 * @var string
	 */
	protected $originalValue;

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $id
	 * @return void
	 */
	public function setId($id) {
		$this->id = $id;
	}

}