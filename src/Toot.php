<?php
class Toot {

  const TOOT_ALLOWED_PLACEHOLDERS = [
    'url',
    'permalink',
    'title',
    'tags',
    'description',
    'cw',
  ];
  const MASTODON_URL_LENGTH = 23;
  const MASTODON_DEFAULT_MAX_LENGTH = 500;
  const EXCESS_MARGIN = 25;

  private $format;
  private $maxLength;
  private $link;
  private $tagDelimiter;

  public function __construct (array $link, string $format, string $tagDelimiter, int $maxLength = self::MASTODON_DEFAULT_MAX_LENGTH) {
    $link['tags'] = self::tagify($link['tags'], $tagDelimiter);
    $link['cw'] = ' ';

    $this->link = $link;
    $this->format = $format;
    $this->maxLength = empty($maxLength) ? self::MASTODON_DEFAULT_MAX_LENGTH : $maxLength;
    $this->tagDelimiter = $tagDelimiter;
  }

  public function __clone () {
    $linkClone = array_merge(array(), $this->link);
    return new self($linkClone, $this->format, $this->tagDelimiter, $this->maxLength);
  }

  public function getFullText () {
    $output = $this->format;
    foreach (self::TOOT_ALLOWED_PLACEHOLDERS as $placeholder) {
      $output = str_replace('${' . $placeholder . '}', $this->link[$placeholder], $output);
    }

    return htmlspecialchars_decode(str_replace('\n', "\n", $output));
  }

  public function getText () {
    $shrinkedLink = $this->getShrinkedLink();
    $output = $this->format;

    foreach (self::TOOT_ALLOWED_PLACEHOLDERS as $placeholder) {
      $output = str_replace('${' . $placeholder . '}', $shrinkedLink[$placeholder], $output);
    }

    return htmlspecialchars_decode(str_replace('\n', "\n", $output));
  }

  public function getMainText () {
    $shrinkedLink = $this->getShrinkedLink();
    $output = '';
    $parts = explode('${cw}', $this->format);

    if (count($parts) >= 1) {
      $output = $parts[0];
    }

    foreach (self::TOOT_ALLOWED_PLACEHOLDERS as $placeholder) {
      $output = str_replace('${' . $placeholder . '}', $shrinkedLink[$placeholder], $output);
    }

    return htmlspecialchars_decode(str_replace('\n', "\n", $output));
  }

  public function getContentWarningText () {
    $shrinkedLink = $this->getShrinkedLink();
    $output = '';
    $parts = explode('${cw}', $this->format);

    if (count($parts) >= 2) {
      $output = $parts[1];
    } else {
      return '';
    }

    foreach (self::TOOT_ALLOWED_PLACEHOLDERS as $placeholder) {
      $output = str_replace('${' . $placeholder . '}', $shrinkedLink[$placeholder], $output);
    }

    return str_replace('\n', "\n", $output);
  }

  /**
   * Return shrinked link.
   * @return array Shrinked link object.
   */
  public function getShrinkedLink (): array {
    // Clone the link.
    $link = array_merge(array(), $this->link);

    $length = $this->getFullLength();

    if ($length < $this->maxLength) {
      return $link;
    }

    $descriptionLength = self::getMastodonLength($link['description']);
    $lengthExcess = $descriptionLength > self::EXCESS_MARGIN ?
      $length - $this->maxLength + self::EXCESS_MARGIN :
      $length - $this->maxLength;

    if ($descriptionLength > $lengthExcess) {
      // Truncate description to reach the right size.
      $link['description'] = mb_substr($link['description'], 0, $descriptionLength - $lengthExcess) . '…';
      return $link;
    } else {
      // Truncating description is not enough, we need to truncate title.
      $link['description'] = '';

      $length = $this->getFullLength();

      $titleLength = self::getMastodonLength($link['title']);
      $lengthExcess = $titleLength > self::EXCESS_MARGIN ?
        $length - $this->maxLength + self::EXCESS_MARGIN :
        $length - $this->maxLength;

      if ($titleLength > $lengthExcess) {
        $link['title'] = mb_substr($link['title'], 0, $titleLength - $lengthExcess) . '…';
      } else {
        $link['title'] = '';
      }
    }

    return $link;
  }

  public function getFullLength (): int {
    $rawOutput = $this->getFullText();

    // Replaces URLs by the right number of characters.
    return self::getMastodonLength($rawOutput);
  }

  public function getLength (): int {
    return self::getMastodonLength($this->getText());
  }

  public function hasContentWarning (): bool {
    return strstr($this->format, '${cw}') !== false;
  }

  // ---

  public function getLink (): array {
    return $this->link;
  }

  public function setLink ($link): void {
    $this->link = $link;
  }

  public function setFormat ($format): void {
    $this->format = $format;
  }

  public function setMaxLength ($maxLength): void {
    $this->maxLength = $maxLength;
  }

  public function withLink ($key, $value): Toot {
    $clone = clone $this;

    $link = $clone->getLink();
    $link[$key] = $value;
    $clone->setLink($link);

    return $clone;
  }

  public function withFormat ($format): Toot {
    $clone = clone $this;
    $clone->setFormat($format);
    return $clone;
  }

  public function withMaxLength (int $maxLength): Toot {
    $clone = clone $this;
    $clone->setMaxLength($maxLength);
    return $clone;
  }

  // ---

  public static function getMastodonLength ($string) {
    $result = preg_replace('/http[s]?:\/\/[^ \\n\\t\\r]*/', str_repeat('X', self::MASTODON_URL_LENGTH), $string);
    return mb_strlen($result);
  }

  public static function tagify ($tagsStr, $tagDelimiter){
    // Regex inspired by https://gist.github.com/janogarcia/3946583
    // TODO validate real hashtag rules
    // - only UTF-8 characters plus underscore
    // - must not contain only numbers. At least one alpha character or underscore
    if (empty($tagsStr)) {
      return '';
    }

    $tags = explode($tagDelimiter, $tagsStr);

    $result = [];

    foreach ($tags as $tag) {
      array_push($result, '#' . preg_replace('/[^0-9_\p{L}]/u', '', $tag));
    }

    return implode(' ', $result);
  }
}
