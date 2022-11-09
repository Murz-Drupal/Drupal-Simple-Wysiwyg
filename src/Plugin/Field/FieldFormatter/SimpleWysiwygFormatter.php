<?php

namespace Drupal\simple_wysiwyg\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines the 'simple_wysiwyg' field formatter.
 *
 * @FieldFormatter(
 *   id = "simple_wysiwyg",
 *   label = @Translation("Simple WYSIWYG"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class SimpleWysiwygFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $elements;
  }

}
