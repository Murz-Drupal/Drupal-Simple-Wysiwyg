<?php

namespace Drupal\simple_wysiwyg\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'simple_wysiwyg' field widget.
 *
 * @FieldWidget(
 *   id = "simple_wysiwyg",
 *   label = @Translation("Simple WYSIWYG"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   },
 * )
 */
class SimpleWysiwygWidget extends WidgetBase {

  const BUTTONS = [
    'bold' => ['button' => '<strong>B</strong>', 'command' => 'bold', 'title' => 'Bold'],
    'italic' => ['button' => '<em>I</em>', 'command' => 'italic', 'title' => 'Italic'],
    'underline' => ['button' => '<u>U</u>', 'command' => 'underline', 'title' => 'Underline'],
    'source' => ['button' => 'Source', 'command' => 'showSource', 'title' => 'Show source code'],
  ];

  const BUTTONS_ALL_ID = '_all';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'buttons_visible' => [self::BUTTONS_ALL_ID],
      'allowed_tags' => '<b><i><u><strong><em><a>',
      'multiline' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $buttonsOptions[self::BUTTONS_ALL_ID] = $this->t('All buttons');

    foreach (self::BUTTONS as $id => $settings) {
      $buttonsOptions[$id] = $this->t($settings['title']);
    }

    $element['buttons_visible'] = [
      '#type' => 'checkboxes',
      '#options' => $buttonsOptions,
      '#title' => $this->t('Visible buttons'),
      '#description' => 'Choose buttons to enable. Leave empty to show all buttons.',
      '#default_value' => $this->getSetting('buttons_visible'),
    ];

    $element['allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed tags'),
      '#description' => 'List of allowed tags in format: <code>&lt;b&gt;&lt;i&gt;&lt;a&gt;</code>.',
      '#default_value' => $this->getSetting('allowed_tags'),
    ];

    $element['multiline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple lines'),
      '#description' => 'Allows using Enter to make multiline input.',
      '#default_value' => $this->getSetting('multiline'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $buttonsVisible = $this->getSetting('buttons_visible');
    if (!in_array(self::BUTTONS_ALL_ID, $buttonsVisible)) {
      foreach ($buttonsVisible as $id => $value) {
        if (!$value) {
          continue;
        }
        $buttons[] = $this->t(self::BUTTONS[$id]['title']);
      }
      $summary[] = $this->t('Visible buttons: @buttons', ['@buttons' => implode(', ', $buttons)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $elementOrig, array &$form, FormStateInterface $formState) {

    $buttonsVisible = $this->getSetting('buttons_visible');
    if (in_array(self::BUTTONS_ALL_ID, $buttonsVisible)) {
      $buttons = self::BUTTONS;
    }
    else {
      foreach ($buttonsVisible as $id => $value) {
        if (!$value) {
          continue;
        }
        $buttons[$id] = self::BUTTONS[$id];
      }
    }

    foreach ($buttons as $button) {
      $buttonsHtml[] = "<a href=\"#\" class=\"{$button['command']}\" data-command=\"{$button['command']}\" title=\"{$button['title']}\">{$this->t($button['button'])}</a>";
    }

    $defaultValue = $items[$delta]->value ?? NULL;

    $element['editor_buttons'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => implode('', $buttonsHtml),
      '#attributes' => [
        'class' => ['simple-wysiwyg-buttons'],
      ],
      "#delta" => $delta,
    ];

    $element['editor'] = $elementOrig + [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'contenteditable' => "true",
        'class' => ['simple-wysiwyg-editor', 'form-element'],
        'data-simple-wysiwyg-settings' => json_encode($this->getSettings()),
      ],
      '#value' => $defaultValue,
      "#delta" => $delta,
    ];

    $element['value'] = [
      '#type' => 'hidden',
      '#default_value' => $defaultValue,
    ];

    $element['#attached']['library'][] = 'simple_wysiwyg/simple_wysiwyg';
    return $element;
  }

}
