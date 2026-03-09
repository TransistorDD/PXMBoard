<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use PXMBoard\Model\cMessageHeader;
use PXMBoard\Model\cUser;

/**
 * Unit test for cMessageHeader class
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cMessageHeaderTest extends TestCase
{
    private cMessageHeader $messageHeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageHeader = new cMessageHeader();
    }

    /**
     * Test setId and getId
     *
     * @return void
     */
    public function test_setId_andGetId_worksCorrectly(): void
    {
        $this->messageHeader->setId(42);
        $this->assertSame(42, $this->messageHeader->getId());
    }

    /**
     * Test setSubject and getSubject
     *
     * @return void
     */
    public function test_setSubject_andGetSubject_worksCorrectly(): void
    {
        $this->messageHeader->setSubject('Test Subject');
        $this->assertSame('Test Subject', $this->messageHeader->getSubject());
    }

    /**
     * Test getSubject with quote prefix adds prefix
     *
     * @return void
     */
    public function test_getSubject_withQuotePrefix_addsPrefix(): void
    {
        $this->messageHeader->setSubject('Test Subject');
        $sSubject = $this->messageHeader->getSubject('Re: ');

        $this->assertSame('Re: Test Subject', $sSubject);
    }

    /**
     * Test getSubject with existing quote prefix does not duplicate
     *
     * @return void
     */
    public function test_getSubject_withExistingQuotePrefix_doesNotDuplicate(): void
    {
        $this->messageHeader->setSubject('Re: Test Subject');
        $sSubject = $this->messageHeader->getSubject('Re: ');

        $this->assertSame('Re: Test Subject', $sSubject);
    }

    /**
     * Test getSubject with case insensitive prefix check
     *
     * @return void
     */
    public function test_getSubject_withCaseInsensitivePrefix_doesNotDuplicate(): void
    {
        $this->messageHeader->setSubject('RE: Test Subject');
        $sSubject = $this->messageHeader->getSubject('Re: ');

        // Should not add prefix because RE: already exists (case insensitive)
        $this->assertSame('RE: Test Subject', $sSubject);
    }

    /**
     * Test getSubject with different prefix adds it
     *
     * @return void
     */
    public function test_getSubject_withDifferentPrefix_addsNewPrefix(): void
    {
        $this->messageHeader->setSubject('Test Subject');
        $sSubject = $this->messageHeader->getSubject('Fwd: ');

        $this->assertSame('Fwd: Test Subject', $sSubject);
    }

    /**
     * Test setMessageTimestamp and getMessageTimestamp
     *
     * @return void
     */
    public function test_setMessageTimestamp_andGetMessageTimestamp_worksCorrectly(): void
    {
        $iTimestamp = 1640000000;
        $this->messageHeader->setMessageTimestamp($iTimestamp);
        $this->assertSame($iTimestamp, $this->messageHeader->getMessageTimestamp());
    }

    /**
     * Test setAuthorId and getAuthorId
     *
     * @return void
     */
    public function test_setAuthorId_andGetAuthorId_worksCorrectly(): void
    {
        $this->messageHeader->setAuthorId(123);
        $this->assertSame(123, $this->messageHeader->getAuthorId());
    }

    /**
     * Test setAuthorUserName sets author username
     *
     * @return void
     */
    public function test_setAuthorUserName_setsUsername(): void
    {
        $this->messageHeader->setAuthorUserName('TestUser');

        $objAuthor = $this->messageHeader->getAuthor();
        $this->assertSame('TestUser', $objAuthor->getUserName());
    }

    /**
     * Test setAuthorPublicMail sets author public mail
     *
     * @return void
     */
    public function test_setAuthorPublicMail_setsPublicMail(): void
    {
        $this->messageHeader->setAuthorPublicMail('test@example.com');

        $objAuthor = $this->messageHeader->getAuthor();
        $this->assertSame('test@example.com', $objAuthor->getPublicMail());
    }

    /**
     * Test setAuthorHighlightUser sets highlight flag
     *
     * @return void
     */
    public function test_setAuthorHighlightUser_setsHighlight(): void
    {
        $this->messageHeader->setAuthorHighlightUser(true);

        $objAuthor = $this->messageHeader->getAuthor();
        $this->assertTrue($objAuthor->highlightUser());
    }

    /**
     * Test getAuthor returns cUser object
     *
     * @return void
     */
    public function test_getAuthor_returnsCUserObject(): void
    {
        $objAuthor = $this->messageHeader->getAuthor();

        $this->assertInstanceOf(cUser::class, $objAuthor);
    }

    /**
     * Test initial state has default values
     *
     * @return void
     */
    public function test_initialState_hasDefaultValues(): void
    {
        $this->assertSame(0, $this->messageHeader->getId());
        $this->assertSame('', $this->messageHeader->getSubject());
        $this->assertSame(0, $this->messageHeader->getMessageTimestamp());
        $this->assertSame(0, $this->messageHeader->getAuthorId());
    }

    /**
     * Test getDataArray contains is_read and is_new fields (not the old 'new' field)
     *
     * @return void
     */
    public function test_getDataArray_containsIsReadAndIsNewFields(): void
    {
        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', 0);

        $this->assertArrayHasKey('is_read', $arrData);
        $this->assertArrayHasKey('is_new', $arrData);
        $this->assertArrayNotHasKey('new', $arrData);
    }

    /**
     * Test getDataArray is_read is 0 when m_bIsRead is null (no DB read status)
     *
     * @return void
     */
    public function test_getDataArray_isRead_isZero_whenNoDbStatus(): void
    {
        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', 0);

        $this->assertSame(0, $arrData['is_read']);
    }

    /**
     * Test getDataArray is_read is 1 when message has been read (setIsRead true)
     *
     * @return void
     */
    public function test_getDataArray_isRead_isOne_whenReadTrue(): void
    {
        $this->messageHeader->setIsRead(true);

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', 0);

        $this->assertSame(1, $arrData['is_read']);
    }

    /**
     * Test getDataArray is_read is 0 when message has not been read (setIsRead false)
     *
     * @return void
     */
    public function test_getDataArray_isRead_isZero_whenReadFalse(): void
    {
        $this->messageHeader->setIsRead(false);

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', 0);

        $this->assertSame(0, $arrData['is_read']);
    }

    /**
     * Test getDataArray is_new is 1 when message is newer than last_login
     *
     * @return void
     */
    public function test_getDataArray_isNew_isOne_whenMessageNewerThanLastLogin(): void
    {
        $iLastLoginTimestamp = 1000000;
        $this->messageHeader->setMessageTimestamp($iLastLoginTimestamp + 100);

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', $iLastLoginTimestamp);

        $this->assertSame(1, $arrData['is_new']);
    }

    /**
     * Test getDataArray is_new is 0 when message is older than last_login
     *
     * @return void
     */
    public function test_getDataArray_isNew_isZero_whenMessageOlderThanLastLogin(): void
    {
        $iLastLoginTimestamp = 1000000;
        $this->messageHeader->setMessageTimestamp($iLastLoginTimestamp - 100);

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', $iLastLoginTimestamp);

        $this->assertSame(0, $arrData['is_new']);
    }

    /**
     * Test is_new is 0 when last_login is 0 (guest / no last_login)
     *
     * @return void
     */
    public function test_getDataArray_isNew_isZero_whenLastLoginIsZero(): void
    {
        $this->messageHeader->setMessageTimestamp(time());

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', 0);

        $this->assertSame(0, $arrData['is_new']);
    }

    /**
     * Test is_read and is_new are orthogonal (new AND read simultaneously possible)
     *
     * @return void
     */
    public function test_getDataArray_isReadAndIsNew_areOrthogonal(): void
    {
        $iLastLoginTimestamp = 1000000;
        $this->messageHeader->setMessageTimestamp($iLastLoginTimestamp + 100);
        $this->messageHeader->setIsRead(true);

        $arrData = $this->messageHeader->getDataArray(0, 'd.m.Y', $iLastLoginTimestamp);

        // Message is both new (posted after last_login) and read (opened by user)
        $this->assertSame(1, $arrData['is_new']);
        $this->assertSame(1, $arrData['is_read']);
    }
}
