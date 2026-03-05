<?php
/**
 * Unit test for NotificationType and NotificationStatus enums
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;

class NotificationEnumsTest extends TestCase
{
    /**
     * Test NotificationType enum has expected cases
     *
     * @return void
     */
    public function test_notificationType_hasExpectedCases(): void
    {
        $arrCases = \eNotificationType::cases();

        $this->assertCount(6, $arrCases);
    }

    /**
     * Test NotificationType REPLY case
     *
     * @return void
     */
    public function test_notificationType_reply_hasCorrectValue(): void
    {
        $this->assertSame('reply', \eNotificationType::REPLY->value);
    }

    /**
     * Test NotificationType PRIVATE_MESSAGE case
     *
     * @return void
     */
    public function test_notificationType_privateMessage_hasCorrectValue(): void
    {
        $this->assertSame('private_message', \eNotificationType::PRIVATE_MESSAGE->value);
    }

    /**
     * Test NotificationType MENTION case
     *
     * @return void
     */
    public function test_notificationType_mention_hasCorrectValue(): void
    {
        $this->assertSame('mention', \eNotificationType::MENTION->value);
    }

    /**
     * Test NotificationType DRAFT_REMINDER case
     *
     * @return void
     */
    public function test_notificationType_draftReminder_hasCorrectValue(): void
    {
        $this->assertSame('draft_reminder', \eNotificationType::DRAFT_REMINDER->value);
    }

    /**
     * Test NotificationType THREAD_MOVED case
     *
     * @return void
     */
    public function test_notificationType_threadMoved_hasCorrectValue(): void
    {
        $this->assertSame('thread_moved', \eNotificationType::THREAD_MOVED->value);
    }

    /**
     * Test NotificationType USER_ACTIVATED case
     *
     * @return void
     */
    public function test_notificationType_userActivated_hasCorrectValue(): void
    {
        $this->assertSame('user_activated', \eNotificationType::USER_ACTIVATED->value);
    }

    /**
     * Test NotificationType can be instantiated from string
     *
     * @return void
     */
    public function test_notificationType_from_withValidValue_returnsCase(): void
    {
        $objType = \eNotificationType::from('reply');

        $this->assertSame(\eNotificationType::REPLY, $objType);
    }

    /**
     * Test NotificationStatus enum has expected cases
     *
     * @return void
     */
    public function test_notificationStatus_hasExpectedCases(): void
    {
        $arrCases = \eNotificationStatus::cases();

        $this->assertCount(2, $arrCases);
    }

    /**
     * Test NotificationStatus UNREAD case
     *
     * @return void
     */
    public function test_notificationStatus_unread_hasCorrectValue(): void
    {
        $this->assertSame('unread', \eNotificationStatus::UNREAD->value);
    }

    /**
     * Test NotificationStatus READ case
     *
     * @return void
     */
    public function test_notificationStatus_read_hasCorrectValue(): void
    {
        $this->assertSame('read', \eNotificationStatus::READ->value);
    }

    /**
     * Test NotificationStatus can be instantiated from string
     *
     * @return void
     */
    public function test_notificationStatus_from_withValidValue_returnsCase(): void
    {
        $objStatus = \eNotificationStatus::from('unread');

        $this->assertSame(\eNotificationStatus::UNREAD, $objStatus);
    }

    /**
     * Test NotificationType values are all strings
     *
     * @return void
     */
    public function test_notificationType_allCases_haveStringValues(): void
    {
        $arrCases = \eNotificationType::cases();

        foreach ($arrCases as $objCase) {
            $this->assertIsString($objCase->value);
            $this->assertNotEmpty($objCase->value);
        }
    }

    /**
     * Test NotificationStatus values are all strings
     *
     * @return void
     */
    public function test_notificationStatus_allCases_haveStringValues(): void
    {
        $arrCases = \eNotificationStatus::cases();

        foreach ($arrCases as $objCase) {
            $this->assertIsString($objCase->value);
            $this->assertNotEmpty($objCase->value);
        }
    }

    /**
     * Test NotificationType case names are unique
     *
     * @return void
     */
    public function test_notificationType_allCases_haveUniqueNames(): void
    {
        $arrCases = \eNotificationType::cases();
        $arrNames = array_map(fn($case) => $case->name, $arrCases);

        $this->assertSame(count($arrNames), count(array_unique($arrNames)));
    }

    /**
     * Test NotificationStatus case names are unique
     *
     * @return void
     */
    public function test_notificationStatus_allCases_haveUniqueNames(): void
    {
        $arrCases = \eNotificationStatus::cases();
        $arrNames = array_map(fn($case) => $case->name, $arrCases);

        $this->assertSame(count($arrNames), count(array_unique($arrNames)));
    }
}
