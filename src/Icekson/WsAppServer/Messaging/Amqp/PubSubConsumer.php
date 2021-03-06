<?php
/**

 * User: a.itsekson
 * Date: 05.11.2015
 * Time: 13:37
 */

namespace Icekson\WsAppServer\Messaging;



use Bunny\Async\Client;
use Icekson\WsAppServer\Service\Support\PubSubListenerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Icekson\Utils\Logger;

class PubSubConsumer
{

    /**
     * @var null|AMQPChannel
     */
    private $channel = null;
    /**
     * @var null|AMQPConnection
     */
    private $connection = null;
    /**
     * @var PubSubListenerInterface
     */
    private $listener = null;
    private $serviceName = 'main-service';
    private $exchangeName = 'pubsub';
    /**
     * @var null|LoggerInterface
     */
    private $logger = null;
    /**
     * @var PubSubListenerInterface|null
     */
    private $pubSubListener = null;

    private $channelId = -1;

    /**
     * AMQPPubSubConsumer constructor.
     * @param array $config
     * @param PubSubListenerInterface $listener
     * @param string $serviceName
     * @param string $exchangeName
     */
    public function __construct(array $config, PubSubListenerInterface $listener, $serviceName = 'main-service', $exchangeName = 'pubsub')
    {
        // $this->logger = Logger::createLogger(get_class($this), $config);
        $this->serviceName = $serviceName;
        $this->exchangeName = $exchangeName;
        $this->pubSubListener = $listener;

        $host = $config['amqp']['host'];
        $port = $config['amqp']['port'];
        $user = $config['amqp']['user'];
        $password = $config['amqp']['password'];
        $vhost = $config['amqp']['vhost'];

        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channelId = mt_rand(1, 65535);
        $this->initConnection();

    }

    private function initConnection()
    {
        if (!$this->connection->isConnected()) {
            $this->connection->reconnect();
        }
        $this->channel = $this->connection->channel($this->channelId);
        $this->channel->exchange_declare($this->exchangeName, 'topic', false, true, false);
        $this->channel->queue_declare($this->exchangeName . "." . $this->serviceName, false, true, false, false);
        $this->channel->queue_bind($this->exchangeName . "." . $this->serviceName, $this->exchangeName, "#");
    }

    public function __destruct()
    {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Exception $e) {

        }
    }

    /**
     *
     */
    public function consume()
    {
        $consumer = $this;
        //  $this->logger->debug("pub sub consumer started");
        $this->channel->basic_consume($this->exchangeName . "." . $this->serviceName, '', false, false, false, false, [$this, 'proccessMsg']);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function proccessMsg(AMQPMessage $msg)
    {
        $consumer = $this;
        $routingKey = $msg->delivery_info['routing_key'];
        //  $consumer->logger->info("new pubsub event consumed: " . $msg->body, ['delivery_info' => $msg->delivery_info]);
        $message = '{"event": "' . $routingKey . '", "event_data": ' . $msg->body . '}';
        $socket = null;
        try {
            $this->pubSubListener->onPubSubMessage($message);
            //  $this->logger->info("message to callback dsn is sent");
            $consumer->channel->basic_ack($msg->delivery_info['delivery_tag']);
        }catch (\Exception $ex){
            $consumer->channel->basic_nack($msg->delivery_info['delivery_tag']);

            throw $ex;
        }
    }

}