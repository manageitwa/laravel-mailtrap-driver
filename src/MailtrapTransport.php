<?php

namespace ManageItWA\LaravelMailtrapDriver;

use Illuminate\Mail\Transport\Transport;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Swift_Mime_SimpleMessage;

class MailtrapTransport extends Transport
{
    /**
     * Guzzle client instance.
     */
    protected ClientInterface $client;

    /**
     * The token to use for the Mailtrap API requests.
     */
    protected string $token;

    /**
     * The Mailtrap API endpoint
     */
    protected string $endpoint;

    /**
     * Create a new Mailtrap transport instance.
     *
     * @return void
     */
    public function __construct(
        ClientInterface $client,
        string $token,
        string $endpoint = null
    ) {
        $this->setClient($client);
        $this->token = $token;
        $this->endpoint = $endpoint ?? 'send.api.mailtrap.io';
    }

    /**
     * {@inheritDoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $response = $this->client->request(
            'POST',
            'https://' . $this->endpoint . '/api/send',
            $this->payload($message)
        );

        $message->getHeaders()->addTextHeader(
            'X-Mailtrap-Message-ID',
            $this->getMessageId($response),
        );

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Generates the JSON payload to send to Mailtrap.
     */
    protected function payload(Swift_Mime_SimpleMessage $message): array
    {
        // Process addresses for mail
        $payload = [
            'headers' => [
                'Api-Token' => $this->token,
            ],
            'json' => [
                'from' => $this->processAddresses($message->getFrom())[0],
                'to' => $this->processAddresses($message->getTo()),
                'subject' => $message->getSubject(),
            ],
        ];

        if (!empty($message->getCc())) {
            $payload['json']['cc'] = $this->processAddresses($message->getCc());
        }
        if (!empty($message->getBcc())) {
            $payload['json']['bcc'] = $this->processAddresses($message->getBcc());
        }

        // Process body content and attachments
        if (count($message->getChildren())) {
            // We're dealing with a multipart message
            if ($message->getBodyContentType() === 'text/html') {
                $payload['json']['html'] = $message->getBody();
            } elseif ($message->getBodyContentType() === 'text/plain') {
                $payload['json']['text'] = $message->getBody();
            }

            foreach ($message->getChildren() as $child) {
                if ($child->getBodyContentType() === 'text/html' && !isset($payload['json']['html'])) {
                    $payload['json']['html'] = $child->getBody();
                } elseif ($child->getBodyContentType() === 'text/plain' && !isset($payload['json']['text'])) {
                    $payload['json']['text'] = $child->getBody();
                } else {
                    if (!isset($payload['json']['attachments'])) {
                        $payload['json']['attachments'] = [];
                    }
                    $payload['json']['attachments'][] = [
                        'content' => base64_encode($child->getBody()),
                        'type' => $child->getBodyContentType(),
                        'filename' => \Illuminate\Support\Str::after(
                            $child->getHeaders()->get('content-disposition')->getFieldBody(),
                            'filename='
                        ),
                        'disposition' => 'attachment',
                    ];
                }
            }
        } elseif ($message->getBodyContentType() === 'text/html') {
            $payload['json']['html'] = $message->getBody();
        } elseif ($message->getBodyContentType() === 'text/plain') {
            $payload['json']['text'] = $message->getBody();
        }

        // Add category if it's available
        if ($message->getHeaders()->has('X-Mailtrap-Category')) {
            $payload['json']['category'] = $message->getHeaders()->get('X-Mailtrap-Category')->getFieldBody();
            $message->getHeaders()->remove('X-Mailtrap-Category');
        }

        return $payload;
    }

    /**
     * Processes SwiftMailer addresses into Mailtrap format.
     */
    protected function processAddresses(array $addresses): array
    {
        return array_map(function ($address, $name = null) {
            if (empty($name)) {
                return [
                    'email' => $address,
                ];
            } else {
                return [
                    'email' => $address,
                    'name' => $name,
                ];
            }
        }, array_keys($addresses), array_values($addresses));
    }

    /**
     * Get the message ID from the response.
     */
    protected function getMessageId(ResponseInterface $response)
    {
        $data = json_decode($response->getBody()->getContents(), true);
        return $data['message_ids'][0] ?? null;
    }

    /**
     * Sets the Guzzle client instance.
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Sets the token to use for the Mailtrap API requests.
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Sets the Mailtrap API endpoint
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }
}
