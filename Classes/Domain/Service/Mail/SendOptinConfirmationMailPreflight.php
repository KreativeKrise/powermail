<?php
declare(strict_types=1);
namespace In2code\Powermail\Domain\Service\Mail;

use In2code\Powermail\Domain\Model\Mail;
use In2code\Powermail\Domain\Repository\MailRepository;
use In2code\Powermail\Utility\FrontendUtility;
use In2code\Powermail\Utility\ObjectUtility;
use In2code\Powermail\Utility\HashUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class SendOptinConfirmationMailPreflight
 */
class SendOptinConfirmationMailPreflight
{

    /**
     * @var SendMailService
     */
    protected $sendMailService;

    /**
     * @var MailRepository
     */
    protected $mailRepository;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var array
     */
    protected $conf = [];

    /**
     * @param array $settings
     * @param array $conf
     * @throws Exception
     */
    public function __construct(array $settings, array $conf)
    {
        $this->settings = $settings;
        $this->conf = $conf;
        $this->sendMailService = ObjectUtility::getObjectManager()->get(SendMailService::class);
        $this->mailRepository = ObjectUtility::getObjectManager()->get(MailRepository::class);
    }

    /**
     * @param Mail $mail
     * @param array $ttContentData
     * @return void
     * @throws InvalidConfigurationTypeException
     * @throws InvalidControllerNameException
     * @throws InvalidExtensionNameException
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     * @throws Exception
     */
    public function sendOptinConfirmationMail(Mail $mail, $ttContentData): void
    {
        $senderService = ObjectUtility::getObjectManager()->get(SenderMailPropertiesService::class, $this->settings, $this->conf);
        $email = [
            'template' => 'Mail/OptinMail',
            'receiverEmail' => $this->mailRepository->getSenderMailFromArguments($mail),
            'receiverName' => $this->mailRepository->getSenderNameFromArguments(
                $mail,
                [$this->conf['sender.']['default.'], 'senderName']
            ),
            'senderEmail' => $senderService->getSenderEmail(),
            'senderName' => $senderService->getSenderName(),
            'replyToEmail' => $senderService->getSenderEmail(),
            'replyToName' => $senderService->getSenderName(),
            'subject' => $senderService->getOptinSubject(),
            'rteBody' => $this->settings['optin']['body'],
            'format' => $this->settings['sender']['mailformat'],
            'variables' => [
                'hash' => HashUtility::getHash($mail),
                'hashDisclaimer' => HashUtility::getHash($mail, 'disclaimer'),
                'mail' => $mail,
                'L' => FrontendUtility::getSysLanguageUid(),
                'ttContentData' => $ttContentData
            ]
        ];
        $this->sendMailService->sendMail($email, $mail, $this->settings, 'optin');
    }
}
