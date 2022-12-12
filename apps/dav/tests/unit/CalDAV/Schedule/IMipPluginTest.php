<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
 *
 * @author brad2014 <brad2014@users.noreply.github.com>
 * @author Brad Rubenstein <brad@wbr.tech>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Schedule;

use OCA\DAV\CalDAV\EventComparisonService;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IAttachment;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Test\TestCase;
use function array_merge;

class IMipPluginTest extends TestCase {

		/** @var IMessage|MockObject */
	private $mailMessage;

	/** @var IMailer|MockObject */
	private $mailer;

	/** @var IEMailTemplate|MockObject */
	private $emailTemplate;

	/** @var IAttachment|MockObject */
	private $emailAttachment;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var IMipPlugin */
	private $plugin;

	/** @var IMipService|MockObject */
	private $service;

	/** @var Defaults|MockObject */
	private $defaults;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var EventComparisonService|MockObject */
	private $eventComparisonService;

	protected function setUp(): void {
		$this->mailMessage = $this->createMock(IMessage::class);
		$this->mailMessage->method('setFrom')->willReturn($this->mailMessage);
		$this->mailMessage->method('setReplyTo')->willReturn($this->mailMessage);
		$this->mailMessage->method('setTo')->willReturn($this->mailMessage);

		$this->mailer = $this->createMock(IMailer::class);
		$this->mailer->method('createMessage')->willReturn($this->mailMessage);

		$this->emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->mailer->method('createEMailTemplate')->willReturn($this->emailTemplate);

		$this->emailAttachment = $this->createMock(IAttachment::class);
		$this->mailer->method('createAttachment')->willReturn($this->emailAttachment);

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1496912528); // 2017-01-01

		$this->config = $this->createMock(IConfig::class);

		$this->userManager = $this->createMock(IUserManager::class);

		$this->defaults = $this->createMock(Defaults::class);
		$this->defaults->method('getName')
			->willReturn('Instance Name 123');

		$this->service = $this->createMock(IMipService::class);

		$this->eventComparisonService = $this->createMock(EventComparisonService::class);

		$this->plugin = new IMipPlugin(
			$this->config,
			$this->mailer,
			$this->logger,
			$this->timeFactory,
			$this->defaults,
			$this->userManager,
			'user123',
			$this->service,
			$this->eventComparisonService
		);
	}

	public function testDelivery() {
		$this->config
	  ->expects($this->at(1))
			->method('getAppValue')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$message = $this->_testMessage();
		$this->_expectSend();
		$this->plugin->schedule($message);
		$this->assertEquals('1.0', $message->getScheduleStatus());
	}

	public function testFailedDelivery() {
		$this->config
	  ->expects($this->at(1))
			->method('getAppValue')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$message = $this->_testMessage();
		$this->mailer
			->method('send')
			->willThrowException(new \Exception());
		$this->_expectSend();
		$this->plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	public function testInvalidEmailDelivery() {
		$this->mailer->method('validateMailAddress')->willReturn(false);

		$message = $this->_testMessage();
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testDeliveryWithNoCommonName() {
		$this->config
	  ->expects($this->at(1))
			->method('getAppValue')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$message = $this->_testMessage();
		$message->senderName = null;

		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')->willReturn('Mr. Wizard');

		$this->userManager->expects($this->once())
			->method('get')
			->with('user123')
			->willReturn($user);

		$this->_expectSend();
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	/**
	 * @dataProvider dataNoMessageSendForPastEvents
	 */
	public function testNoMessageSendForPastEvents(array $veventParams, bool $expectsMail) {
		$this->config
	  ->method('getAppValue')
	  ->willReturn('yes');
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$message = $this->_testMessage($veventParams);

		$this->service->expects(self::once())
			->method('getLastOccurrence')
			->willReturn('1496912700');
		$this->mailer->expects(self::once())
			->method('validateMailAddress')
			->with('frodo@hobb.it')
			->willReturn(false);

		$this->plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	public function testFailedDelivery(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVcalendar = new VCalendar();
		$newVevent = new VEvent($newVcalendar, 'one', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting without (!) Boromir',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE',  'CN' => 'Frodo']);
		$message->message = $newVcalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		// save the old copy in the plugin
		$oldVcalendar = new VCalendar();
		$oldVevent = new VEvent($oldVcalendar, 'one', [
			'UID' => 'uid-1234',
			'SEQUENCE' => 0,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		]);
		$oldVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$oldVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$oldVevent->add('ATTENDEE', 'mailto:' . 'boromir@tra.it.or', ['RSVP' => 'TRUE']);
		$oldVcalendar->add($oldVevent);
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting without (!) Boromir',
			'attendee_name' => 'frodo@hobb.it'
		];
	}

	/**
	 * @dataProvider dataIncludeResponseButtons
	 */
	public function testIncludeResponseButtons(string $config_setting, string $recipient, bool $has_buttons) {
		$message = $this->_testMessage([],$recipient);
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$this->_expectSend($recipient, true, $has_buttons);
		$this->config
	  ->expects($this->at(1))
			->method('getAppValue')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn($config_setting);

		$this->plugin->schedule($message);
		$this->assertEquals('5.0', $message->getScheduleStatus());
	}

	public function testNoOldEvent(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->senderName = 'Mr. Wizard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting',
			'attendee_name' => 'frodo@hobb.it'
		];

	public function testMessageSendWhenEventWithoutName() {
		$this->config
			->method('getAppValue')
			->with('dav', 'invitation_link_recipients', 'yes')
			->willReturn('yes');
		$this->mailer->method('validateMailAddress')->willReturn(true);

		$message = $this->_testMessage(['SUMMARY' => '']);
		$this->_expectSend('frodo@hobb.it', true, true,'Invitation: Untitled event');
		$this->emailTemplate->expects($this->once())
			->method('addHeading')
			->with('Invitation');
		$this->plugin->schedule($message);
		$this->assertEquals('1.1', $message->getScheduleStatus());
	}

	public function testNoButtons(): void {
		$message = new Message();
		$message->method = 'REQUEST';
		$newVCalendar = new VCalendar();
		$newVevent = new VEvent($newVCalendar, 'VEVENT', array_merge([
			'UID' => 'uid-1234',
			'SEQUENCE' => 1,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00')
		], []));
		$newVevent->add('ORGANIZER', 'mailto:gandalf@wiz.ard');
		$newVevent->add('ATTENDEE', 'mailto:' . 'frodo@hobb.it', ['RSVP' => 'TRUE', 'CN' => 'Frodo']);
		$message->message = $newVCalendar;
		$message->sender = 'mailto:gandalf@wiz.ard';
		$message->recipient = 'mailto:' . 'frodo@hobb.it';
		$data = ['invitee_name' => 'Mr. Wizard',
			'meeting_title' => 'Fellowship meeting',
			'attendee_name' => 'frodo@hobb.it'
		];

	private function _expectSend(string $recipient = 'frodo@hobb.it', bool $expectSend = true, bool $expectButtons = true, string $subject = 'Invitation: Fellowship meeting') {

		// if the event is in the past, we skip out
		if (!$expectSend) {
			$this->mailer
				->expects($this->never())
				->method('send');
			return;
		}

		$this->emailTemplate->expects($this->once())
			->method('setSubject')
			->with($subject);
		$this->mailMessage->expects($this->once())
			->method('setTo')
			->with([$recipient => null]);
		$this->mailMessage->expects($this->once())
			->method('setReplyTo')
			->with(['gandalf@wiz.ard' => 'Mr. Wizard']);
		$this->mailMessage->expects($this->once())
			->method('setFrom')
			->with(['invitations-noreply@localhost' => 'Mr. Wizard via Instance Name 123']);
		$this->mailer
			->expects($this->once())
			->method('send');

		if ($expectButtons) {
			$this->queryBuilder->expects($this->at(0))
				->method('insert')
				->with('calendar_invitations')
				->willReturn($this->queryBuilder);
			$this->queryBuilder->expects($this->at(8))
				->method('values')
				->willReturn($this->queryBuilder);
			$this->queryBuilder->expects($this->at(9))
				->method('execute');
		} else {
			$this->queryBuilder->expects($this->never())
				->method('insert')
				->with('calendar_invitations');
		}
	}
}
