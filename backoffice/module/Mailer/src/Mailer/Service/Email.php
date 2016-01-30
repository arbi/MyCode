<?php
namespace Mailer\Service;

use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;

/**
 * Email service class.
 *
 *
 */
class Email
{
    /**
     * The mail transport
     *
     * @var TransportInterface
     */
    protected $transport = null;

    /**
     * The email renderer.
     *
     * @var RendererInterface
     */
    protected $renderer = null;

    /**
     * Initialize the mail service
     *
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport, RendererInterface $renderer) {
        $this->transport = $transport;
        $this->renderer = $renderer;
    }

    /**
     * Sends an email.
     *
     * @param string|Message $tpl
     * @param array          $data
     */
    public function send($tpl, array $data = null) {
        if ($tpl instanceof Message) {
            $mail = $tpl;
        } else {
            if ($data === null) {
                throw new \InvalidArgumentException('Expected data to be array, null given.');
            }

            $mail = $this->getMessage($tpl, $data);
        }

        $this->getTransport()->send($mail);
    }

    /**
     * @param  string  $tpl
     * @param  array   $data
     * @return Message
     */
    public function getMessage($tpl, array $data) {
        $mail = new Message();
        $mail->setEncoding('UTF-8');

        if (isset($data['encoding'])) {
            $mail->setEncoding($data['encoding']);
        }
        if (isset($data['from_address'])) {
            if (isset($data['from_name'])) {
                $mail->setFrom($data['from_address'], $data['from_name']);
            } else {
                $mail->setFrom($data['from_address']);
            }
        }
        if (isset($data['to'])) {
            if (isset($data['to_name'])) {
                $mail->setTo($data['to'], $data['to_name']);
            } else {
                $mail->setTo($data['to']);
            }
        }
        if (isset($data['cc'])) {
            $mail->setCc($data['cc']);
        }
        if (isset($data['bcc'])) {
            $mail->setBcc($data['bcc']);
        }
        if (isset($data['subject'])) {
            $mail->setSubject($data['subject']);
        }
        if (isset($data['sender'])) {
            $mail->setSender($data['sender']);
        }
        if (isset($data['replyTo'])) {
            $mail->setReplyTo($data['replyTo']);
        }

        $content = $this->renderMail($tpl, $data);
        $mail->setBody($content);
        $mail->getHeaders()
            ->addHeaderLine('Content-Type', 'text/html; charset=UTF-8')
            ->addHeaderLine('Content-Transfer-Encoding', '8bit');

        return $mail;
    }

    /**
     * Returns the mail transport
     *
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport() {
        return $this->transport;
    }

    /**
     * Sets the transport
     *
     * @param TransportInterface $transport
     */
    public function setTransport(TransportInterface $transport) {
        $this->transport = $transport;
    }

    /**
     * @return \Zend\View\Renderer\RendererInterface
     */
    public function getRenderer() {
        return $this->renderer;
    }

    /**
     * @param \Zend\View\Renderer\RendererInterface $renderer
     */
    public function setRenderer(RendererInterface $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * Render a given template with given data assigned.
     *
     * @param  string $tpl
     * @param  array  $data
     * @return string The rendered content.
     */
    protected function renderMail($tpl, array $data) {
        $viewModel = new ViewModel($data);

        if (isset($data['layout'])) {
            $viewModel->setTemplate('layout/' . $data['layout']);
        } else {
            $viewModel->setTemplate('layout/layout');
        }

        $viewModel->setVariables(['template' => $tpl]);

        return $this->renderer->render($viewModel);
    }
}