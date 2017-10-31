<?php
require_once 'shaarli2mastodon.php';

class UtilityFunctionsTest extends PHPUnit_Framework_TestCase{

    public function testTootFormat(){
        $toot = $this->generateToot();
        $format = '#Shaarli: ${title} ${url} ${tags} - ${permalink} ${pouic} ${description}';
        $tootFormatted = formatToot($toot, $format);

        $expected = '#Shaarli: Whats up? - Kalvn http://kalvn.net #anothertest #test - https://links.kalvn.net/?qP8jTw ${pouic} Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, i…';
        $this->assertEquals($expected, $tootFormatted);
    }

    public function testMaximumLength(){
        $toot = $this->generateToot();
        $format = '#Shaarli: ${title} ${url} ${tags} - ${permalink} ${pouic} ${description}';
        $tootFormatted = formatToot($toot, $format);

        $expected = TOOT_LENGTH;
        $this->assertEquals($expected, strlen($tootFormatted));
    }

    public function testFalsePlaceholderDeletion(){
        $tootContainingFalsePlaceholders = 'This is a wonderful toot ${url} ${permalink} ${tags} ${toot}';

        $expected = 'This is a wonderful toot    ${toot}';
        $this->assertEquals($expected, removeRemainingPlaceholders($tootContainingFalsePlaceholders));
    }

    public function testUrlLength(){
        $toot = $this->getSimpleToot();
        $format = '${title} ${url} - ${description}';
        $tootFormatted = formatToot($toot, $format);

        $expected = 57; // 14 (title) + 16 (description) + 23 (url) + 4 (other stuff)
        $this->assertEquals($expected, getTootLength($tootFormatted));

        $format = '${title} ${url} - ${description} - ${permalink}';
        $tootFormatted = formatToot($toot, $format);

        $expected = 83; // 14 (title) + 16 (description) + 23 (url) + 23 (url) + 7 (other stuff)
        $this->assertEquals($expected, getTootLength($tootFormatted));
    }

    public function testPlaceholderPriority(){
        $toot = $this->generateToot();
        $format = '#Shaarli: ${title} ${url} ${tags} - ${description} - ${permalink}';
        $tootFormatted = formatToot($toot, $format);

        $expected = '#Shaarli: Whats up? - Kalvn http://kalvn.net #anothertest #test - Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdie… - https://links.kalvn.net/?qP8jTw';
        $this->assertEquals($expected, $tootFormatted);
    }

    public function testNonAlphanumericalTags(){
        $tagModified = tagify('firefox');
        $expected = '#firefox';
        $this->assertEquals($expected, $tagModified);

        $tagModified = tagify('this-is-a-forbidden-tag');
        $expected = '#thisisaforbiddentag';
        $this->assertEquals($expected, $tagModified);

        $tagModified = tagify('This is $EV3N_worse!');
        $expected = '#ThisisEV3Nworse';
        $this->assertEquals($expected, $tagModified);
    }


    // Helpers.

    /**
     * Generates a minimal toot.
     * @return array A minimal toot.
     */
    private function getSimpleToot(){
        return array(
            'title' => 'A simple title',
            'url' => 'https://mozilla.org',
            'description' => 'What a nice one!',
            'permalink' => 'https://links.kalvn.net/?abcde',
            'tags' => array(
                'firefox'
            )
        );
    }

    /**
     * Generates a complete toot with content longer than 500 characters.
     * @return array A complete toot.
     */
    private function generateToot(){
        return array(
            'title' => 'Whats up? - Kalvn',
            'url' => 'http://kalvn.net',
            'description' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,',
            'private' => 0,
            'updated' => null,
            'tags' => array(
                '#anothertest',
                '#test',
            ),
            'shorturl' => 'qP8jTw',
            'permalink' => 'https://links.kalvn.net/?qP8jTw',
        );
    }
}