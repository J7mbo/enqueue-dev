<?php
namespace Enqueue\Dbal;

use Enqueue\Psr\PsrMessage;

class DbalMessage implements PsrMessage
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var boolean
     */
    private $redelivered;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var int
     */
    private $delay;

    /**
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     */
    public function __construct($body = null, array $properties = [], array $headers = [])
    {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->redelivered = false;
        $this->priority = 0;
        $this->delay = null;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ?$this->headers[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedelivered()
    {
        return $this->redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedelivered($redelivered)
    {
        $this->redelivered = $redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setReplyTo($replyTo)
    {
        $this->setHeader('reply_to', $replyTo);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplyTo()
    {
        return $this->getHeader('reply_to');
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set delay in seconds
     *
     * @param int $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorrelationId($correlationId)
    {
        $this->setHeader('correlation_id', $correlationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId()
    {
        return $this->getHeader('correlation_id', '');
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageId($messageId)
    {
        $this->setHeader('message_id', $messageId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId()
    {
        return $this->getHeader('message_id', '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp()
    {
        return $this->getHeader('timestamp');
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp($timestamp)
    {
        $this->setHeader('timestamp', (int) $timestamp);
    }
}
