<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class TootTest extends TestCase {

  private $toots;
  private $defaultToot;

  public function __construct () {
    parent::__construct();

    $defaultToot = new Toot(
      [
        'url' => 'https://github.com/kalvn/shaarli2mastodon',
        'permalink' => 'https://links.kalvn.net/shaare/MmawMw',
        'title' => 'A nice text',
        'description' => "The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced by fox whelps. Bawds jog, flick quartz, vex nymphs. Waltz, bad nymph, for quick jigs vex! Fox nymphs grab quick-jived waltz. Brick quiz whangs jumpy veldt fox. Bright vixens jump; dozy fowl quack. Quick wafting zephyrs vex bold Jim. Quick zephyrs blow, vexing daft Jim. Sex-charged fop blew my junk TV quiz. How quickly daft jumping zebras vex. Two driven jocks help fax my big quiz. Quick, Baz, get",
        'tags' => 'blind text'
      ],
      "start \${title}\n\${url}\n\n\${description}\n\${tags} — \${permalink} end",
      ' '
    );

    $this->defaultToot = $defaultToot;

    $this->toots['regular'] = $defaultToot;
    $this->toots['short'] = $defaultToot->withFormat('${title}');
    $this->toots['cw'] = $defaultToot->withFormat("\${title}\n\${url}\${cw}\${description}\n\${tags} — \${permalink}");
  }

  // -----------

  public function testRegularToot (): void {
    $toot = $this->defaultToot;

    $this->assertEquals('start A nice text
https://github.com/kalvn/shaarli2mastodon

The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced by fox whelps. Bawds jog, flick quartz, vex nymphs. Waltz, bad nymph, for quick jigs vex! Fox nymphs grab quick-jived waltz. Brick quiz whangs jumpy veldt fox. Bright vixens jump; dozy fowl quack. Quick wafting zephyrs vex bold Jim. Quick zephyrs blow, vexing daft Jim. Sex-charged fop bl…
#blind #text — https://links.kalvn.net/shaare/MmawMw end', $toot->getText());
    $this->assertEquals('', $toot->getContentWarningText());
    $this->assertEquals(476, $toot->getLength());
    $this->assertEquals(586, $toot->getFullLength());
  }

  public function testVeryLongTitle (): void {
    $toot = $this->defaultToot
      ->withLink('title', 'The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced by fox whelps. Bawds jog, flick quartz, vex nymphs. Waltz, bad nymph, for quick jigs vex! Fox nymphs grab quick-jived waltz. Brick quiz whangs jumpy veldt fox. Bright vixens jump; dozy fowl quack. Quick wafting zephyrs vex bold Jim');

    $this->assertEquals('start The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced by fox whelps. Bawds jog, flick quartz, vex nymphs. Waltz, bad nymph, for quick jigs vex! Fox nymphs grab quick-jived waltz. Brick quiz whangs jumpy veldt fox. Bright vixens jump; dozy fowl quack. Quick wafting zephyrs vex bold Jim
https://github.com/kalvn/shaarli2mastodon

The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax…
#blind #text — https://links.kalvn.net/shaare/MmawMw end', $toot->getText());
    $this->assertEquals('', $toot->getContentWarningText());
    $this->assertEquals(476, $toot->getLength());
    $this->assertEquals(907, $toot->getFullLength());
  }

  public function testShortMaxLengthWithContentWarning (): void {
    $toot = $this->defaultToot
      ->withFormat("\${title}\n\${url}\${cw}\${description}\n\${tags} — \${permalink}")
      ->withMaxLength(100);

    $this->assertEquals('
https://github.com/kalvn/shaarli2mastodon', $toot->getMainText());
    $this->assertEquals('
#blind #text — https://links.kalvn.net/shaare/MmawMw', $toot->getContentWarningText());
    $this->assertEquals(64, $toot->getLength());
    $this->assertTrue($toot->hasContentWarning());
  }

  public function testGetMastodonLength (): void {
    $this->assertEquals(60, Toot::getMastodonLength('This is a nice toot! URL: https://kalvn.net - goodbye.'));
    $this->assertEquals(41, Toot::getMastodonLength('This is a nice toot! #foo #bar - goodbye.'));
  }

  public function testTagify (): void {
    $this->assertEquals('#test #dev', Toot::tagify('test dev', ' '));
    $this->assertEquals('#testdev', Toot::tagify('test dev', ','));
    $this->assertEquals('#thisisaforbiddentag', Toot::tagify('this-is-a-forbidden-tag', ' '));
    $this->assertEquals('#This #is #EV3N_worse', Toot::tagify('This is $EV3N_worse!', ' '));
    $this->assertEquals('#ThisisEV3N #worse', Toot::tagify('This is $EV3N_worse!', '_'));
  }
}
