<?php

/**
 * @group markup
 */
final class HWOPRuleLink
  extends PhutilRemarkupRule {

  const KEY_LINKS = 'links';

  public function apply($text) {
    return preg_replace_callback(
      '@\B\\[\\[([^|\\]]+)(?:\\|([^\\]]+))?\\]\\]\B@U',
      array($this, 'markupDocumentLink'),
      $text);
  }

  public function markupDocumentLink($matches) {
    $link = trim($matches[1]);
    $name = trim(idx($matches, 2, $link));
    $name = explode('/', trim($name, '/'));
    $name = end($name);

    $uri      = new PhutilURI($link);
    $slug     = $uri->getPath();
    $fragment = $uri->getFragment();
    $slug     = HWOPSlug::slugify($slug);
    $slug     = "/" . $slug;
    $href     = (string) id(new PhutilURI($slug))->setFragment($fragment);

    $links = $this->getEngine()->getTextMetadata(
        self::KEY_LINKS, array());
    $links[] = array($href, $name);
    $this->getEngine()->setTextMetadata(
        self::KEY_LINKS, $links);

    if ($this->getEngine()->getState('toc')) {
      $text = $name;
    } else {
      $text = phutil_tag(
          'a',
          array(
            'href'  => $href,
            'class' => 'markup-link',
          ),
          $name);
    }

    return $this->getEngine()->storeText($text);
  }

}
