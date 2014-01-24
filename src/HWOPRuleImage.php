<?php
/**
 * this file is changed PhabricatorRemarkupRuleEmbedFile.php from
 * https://github.com/facebook/phabricator/ repos.
 */

/**
 * @group markup
 */
final class HWOPRuleImage
  extends PhutilRemarkupRule {

  public function apply($text) {
    return preg_replace_callback(
      "@{img ([^\.]\.(png|gif|jpg|svg))([^}]+?)?}@",
      array($this, 'markupImage'),
      $text);
  }

  public function markupImage($matches) {
    $file = $matches[1];
    $unparsed_options = idx($matches, 3, "");

    $options = array(
      'size'    => 'thumb',
      'layout'  => 'left',
      'float'   => false,
      'name'    => null,
    );

    if (!empty($unparsed_options)) {
      $unparsed_options = trim($unparsed_options, ', ');
      $parser = new PhutilSimpleOptions();
      $options = $parser->parse($unparsed_options) + $options;
    }

    $file_name = coalesce($options['name'], $file);
    $options['name'] = $file_name;

    $attrs = array();
    switch ($options['size']) {
      case 'full':
      default:
        $attrs['src'] = "/img/$file";
        $options['image_class'] = null;
        break;
      case 'thumb':
      default:
        $attrs['src'] = "/img/$file";
        $options['image_class'] = 'phabricator-remarkup-embed-image';
        break;
    }

    $embed = phutil_tag('img', $attrs);

    $layout_class = null;
    switch ($options['layout']) {
      case 'right':
      case 'center':
      case 'inline':
      case 'left':
        $layout_class = 'phabricator-remarkup-embed-layout-'.
          $options['layout'];
        break;
      default:
        $layout_class = 'phabricator-remarkup-embed-layout-left';
        break;
    }

    if ($options['float']) {
      switch ($options['layout']) {
        case 'center':
        case 'inline':
          break;
        case 'right':
          $layout_class .= ' phabricator-remarkup-embed-float-right';
          break;
        case 'left':
        default:
          $layout_class .= ' phabricator-remarkup-embed-float-left';
          break;
      }
    }

    if ($layout_class) {
      $embed = phutil_tag(
        'div',
        array(
          'class' => $layout_class,
        ),
        $embed);
    }

    return $this->getEngine()->storeText($embed);
  }

}
