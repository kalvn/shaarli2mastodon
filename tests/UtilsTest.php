<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase {

  public function __construct () {
    parent::__construct();
  }

  public function testIsLinkNote (): void {
    $this->assertTrue(Utils::isLinkNote([
      'url' => '/shaare/i6lwMw'
    ]));

    $this->assertFalse(Utils::isLinkNote([
      'url' => 'https://github.com/kalvn/shaarli2mastodon'
    ]));
  }
}
