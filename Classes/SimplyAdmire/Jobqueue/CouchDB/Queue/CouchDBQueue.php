<?php
namespace SimplyAdmire\Jobqueue\CouchDB\Queue;

use TYPO3\Flow\Annotations as Flow;
use SimplyAdmire\Jobqueue\CouchDB\Domain\Repository\MessageRepository;
use SimplyAdmire\Jobqueue\CouchDB\Domain\Model\Message;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;

class CouchDBQueue implements QueueInterface {

	/**
	 * @Flow\Inject
	 * @var MessageRepository
	 */
	protected $messageRepository;

	/**
	 * @param \TYPO3\Jobqueue\Common\Queue\Message $message
	 * @return string|void
	 * @throws \Exception
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function publish(\TYPO3\Jobqueue\Common\Queue\Message $message) {
		$message->setState(Message::STATE_PUBLISHED);
		$message = $this->createDocument($message);

		$this->messageRepository->add($message);
		$this->messageRepository->flushDocumentManager();
	}

	/**
	 * @param null $timeout
	 * @return mixed|null|\TYPO3\Jobqueue\Common\Queue\Message
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function waitAndTake($timeout = NULL) {
		$message = $this->waitAndReserve();
		if ($message !== NULL) {
			$message->setState(Message::STATE_DONE);
			$this->messageRepository->remove($message);
		}
		return $message;
	}

	/**
	 * @param null $timeout
	 * @return mixed|null|\TYPO3\Jobqueue\Common\Queue\Message
	 */
	public function waitAndReserve($timeout = NULL) {
		$messages = $this->messageRepository->findAll();
		if (count($messages) > 0) {
			$message = array_shift($messages);
			return $message;
		}
		return NULL;
	}

	/**
	 * @param \TYPO3\Jobqueue\Common\Queue\Message $message
	 * @return boolean
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function finish(\TYPO3\Jobqueue\Common\Queue\Message $message) {
		$message = $this->messageRepository->findByIdentifier($message->getId());
		if ($message instanceof Message) {
			$message->setState(Message::STATE_DONE);
			$this->messageRepository->remove($message);
			$this->messageRepository->flushDocumentManager();
		}
		return TRUE;
	}

	/**
	 * @param integer $limit
	 * @return array
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function peek($limit = 1) {
		$messages = $this->messageRepository->findAll();
		if (count($messages) > 0) {
			$messages = array_slice($messages, 0, $limit);
			foreach ($messages as $message) {
				$message->setState(Message::STATE_PUBLISHED);
				$this->messageRepository->update($message);
			}
			$this->messageRepository->flushDocumentManager();
		}
		return array();
	}

	/**
	 * @param string $identifier
	 * @return object|\TYPO3\Jobqueue\Common\Queue\Message
	 */
	public function getMessage($identifier) {
		return $this->messageRepository->findByIdentifier($identifier);
	}

	/**
	 * @return integer
	 */
	public function count() {
		$messages = $this->messageRepository->findAll();
		return count($messages);
	}

	/**
	 * Encode a message
	 *
	 * Updates the original value property of the message to resemble the
	 * encoded representation.
	 *
	 * @param Message $message
	 * @return string
	 */
	protected function createDocument(\TYPO3\Jobqueue\Common\Queue\Message $message) {
		$properties = ObjectAccess::getGettableProperties($message);
		$messageDocument = new Message($message->getPayload(), $message->getIdentifier());

		foreach ($properties as $propertyName => $propertyValue) {
			ObjectAccess::setProperty($messageDocument, $propertyName, $propertyValue);
		}

		return $messageDocument;
	}

}